<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_auth
{
    private $CI;
    private $secret;

    public function __construct()
    {
        $this->CI =& get_instance();
        // SECURITY UPDATE: Keep the signing secret on the API server. Never place it in the Android app.
        $this->secret = getenv('AGMA_AUTH_SECRET');
    }

    public function create_token($user)
    {
        if (!$this->secret || strlen($this->secret) < 32) {
            log_message('error', 'AGMA_AUTH_SECRET is missing or shorter than 32 characters.');
            return false;
        }

        $now = time();
        $payload = array(
            'sub' => (string) $user->userid,
            'username' => (string) $user->username,
            'area' => (string) $user->area,
            'type' => (string) $user->type,
            'iat' => $now,
            'exp' => $now + (8 * 60 * 60) // SECURITY UPDATE: Mobile login expires after eight hours.
        );

        $encoded_payload = $this->base64_url_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $encoded_payload, $this->secret, true);

        return $encoded_payload . '.' . $this->base64_url_encode($signature);
    }

    public function require_user($required_type = null, $write_response = true)
    {
        $authorization = $this->authorization_header();

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return $this->unauthorized('Missing access token. Please log in again.', $write_response);
        }

        $user = $this->validate_token(trim($matches[1]));

        if (!$user) {
            return $this->unauthorized('Invalid or expired access token. Please log in again.', $write_response);
        }

        if ($required_type !== null && $user->type !== $required_type) {
            $this->CI->output->set_status_header(403);
            if ($write_response) {
                echo json_encode(array('message' => 'You are not allowed to perform this action.', 'status' => 'failed'));
            }
            return false;
        }

        return $user;
    }

    private function validate_token($token)
    {
        if (!$this->secret || strlen($this->secret) < 32) {
            log_message('error', 'AGMA_AUTH_SECRET is missing or shorter than 32 characters.');
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return false;
        }

        $expected_signature = $this->base64_url_encode(
            hash_hmac('sha256', $parts[0], $this->secret, true)
        );

        if (!hash_equals($expected_signature, $parts[1])) {
            return false;
        }

        $payload = json_decode($this->base64_url_decode($parts[0]));
        if (!$payload || empty($payload->sub) || empty($payload->exp) || $payload->exp < time()) {
            return false;
        }

        // SECURITY UPDATE: Recheck the database so deactivated users lose access immediately.
        $query = $this->CI->db
            ->where('userid', $payload->sub)
            ->where('active', true)
            ->get('tbl_users');

        if ($query->num_rows() === 0) {
            return false;
        }

        return $query->row();
    }

    private function authorization_header()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        // SECURITY UPDATE: Some Apache configurations rename the Authorization server variable.
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        // SECURITY UPDATE: Use CodeIgniter's header reader when PHP does not create HTTP_AUTHORIZATION.
        $codeigniter_header = $this->CI->input->get_request_header('Authorization', true);
        if ($codeigniter_header) {
            return trim($codeigniter_header);
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    return trim($value);
                }
            }
        }

        // SECURITY UPDATE: getallheaders() is available on servers where apache_request_headers() is not.
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    return trim($value);
                }
            }
        }

        return '';
    }

    private function unauthorized($message, $write_response)
    {
        $this->CI->output->set_status_header(401);
        if ($write_response) {
            echo json_encode(array('message' => $message, 'status' => 'failed'));
        }
        return false;
    }

    private function base64_url_encode($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64_url_decode($value)
    {
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'));
    }
}
