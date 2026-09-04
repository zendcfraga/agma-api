<?php
    /* class Header extends CI_Model{

        public function ApiHeader(){
            header("Content-type: application/json; charset=UTF-8");
            header("Access-Control-Allow-Origin: *"); 
            header("Access-Control-Allow-Methods: POST"); 
            header("Access-Control-Max-Age: 86400");
            header("Access-Control-Allow-Headers:  Content-Type, Access-Control-Allow-Methods, Authorization, X-Request-With");
        }
    } */

defined('BASEPATH') OR exit('No direct script access allowed');

class Header extends CI_Model
{
    public function ApiHeader()
    {
        // SECURITY UPDATE: Use exact browser origins; never use Access-Control-Allow-Origin: * for this API.
        // OLD CODE: header("Access-Control-Allow-Origin: *");
        $allowedOrigins = array(
            'http://localhost:5173', // Local Vite development server for agma-web-app.
            'http://127.0.0.1:5173', // Same local Vite server when opened through 127.0.0.1.
            'http://localhost:8100', // SECURITY UPDATE: Local Ionic development server for agma-mobile-app.
            'http://127.0.0.1:8100', // SECURITY UPDATE: Same Ionic server when opened through 127.0.0.1.
            'https://localhost', // SECURITY UPDATE: Capacitor Android uses this secure local WebView origin.
            'capacitor://localhost' // SECURITY UPDATE: Keep compatibility if the Capacitor origin changes by platform.
            // 'https://YOUR-AGMA-WEB-DOMAIN' // Add the exact HTTPS origin here before production deployment.
        );

        $origin = isset($_SERVER['HTTP_ORIGIN'])
            ? $_SERVER['HTTP_ORIGIN']
            : '';

        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }

        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // SECURITY UPDATE: Only methods currently required by the web/API.
        header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With'); // Keep mobile-compatible headers.
        header('Access-Control-Max-Age: 600'); // Cache a successful browser preflight for ten minutes.
        header('X-Content-Type-Options: nosniff'); // SECURITY UPDATE: Prevent browsers from guessing a non-JSON content type.
        header('Referrer-Policy: no-referrer'); // SECURITY UPDATE: Do not leak API paths through the Referer header.
        header('Cache-Control: no-store'); // SECURITY UPDATE: Registration/API responses may contain personal information.

        if (
            isset($_SERVER['REQUEST_METHOD']) &&
            $_SERVER['REQUEST_METHOD'] === 'OPTIONS'
        ) {
            http_response_code(204);
            exit;
        }
    }
}
