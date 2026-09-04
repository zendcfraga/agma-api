<?php

class Userauth extends CI_Controller{
    //create ta ug index para mao iyang pangitaon
    //default or first pangitaon sa browser
    
    
    public function __construct() {
        parent::__construct(); //everytime gina-open ang application under ana nga class 

        $this->load->model('Header'); //load is a property, to call Header class from model
        $this->Header->ApiHeader(); //to call ApiHeader function from Header
        $this->load->library('Api_auth'); // SECURITY UPDATE: Validate user-specific mobile access tokens in one place.
    }

    public function index(){
        
        echo json_encode($now,JSON_PRETTY_PRINT);
    }
    
    public function login(){
        $data = json_decode(file_get_contents('php://input'));

        if($data){
            // OLD CODE: Login required a shared TOKEN that could be extracted from the Android APK.
            // $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $username = filter_var($data->USERNAME_POST, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $password = filter_var($data->PASSWORD_POST, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            // SECURITY UPDATE: Username and password now establish the user's own expiring session.
                $verify = $this->db->where(
                    array(
                        'username' => $username,
                        'password' => md5($password)
                        )
                );
                
                $this->db->where('active',true);
                $verify = $this->db->get('tbl_users');
    
                $rw = $verify->row();
                
                if ($verify->num_rows() != 0) {
                    $rw = $verify->row();
                    $access_token = $this->api_auth->create_token($rw); // SECURITY UPDATE: Token is signed by the API server.

                    if (!$access_token) {
                        $this->output->set_status_header(500);
                        echo json_encode(array('message' => 'Authentication is not configured.', 'status' => 'error'));
                        return;
                    }

                    $response = array(
                        'message' => 'Login Successful',
                        'status' => 'success',
                        'areacode' => $rw->area,
                        'user_type' => $rw->type, // Assuming your table has a `user_type` column
                        'access_token' => $access_token, // SECURITY UPDATE: Mobile sends this in Authorization headers.
                        'expires_in' => 28800 // SECURITY UPDATE: Eight-hour login lifetime in seconds.
                    );
                } else {
                    $response = array(
                        'message' => 'Invalid/Inactive Username or Password!',
                        'status' => 'error'
                    );
                }
            // OLD CODE: Shared-token failure branch removed because login no longer trusts an app-wide secret.

            
        }else{
            $response = array('message' => 'Invalid parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function account_registration()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: Account creation required the shared APK token.
            // SECURITY UPDATE: Keep self-registration available before login; new users remain inactive until admin approval.
            $FULLNAME       = filter_var($data->FULLNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $USERNAME       = filter_var($data->USERNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $PASSWORD       = filter_var($data->PASSWORD, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $AREA           = filter_var($data->AREA, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            
                // ✅ Check if username already exists
                $this->db->where('username', $USERNAME);
                $query = $this->db->get('tbl_users');
    
                if ($query->num_rows() > 0) {
                    $response = [
                        'message' => 'Username already exists. Please choose another.',
                        'status' => 'failed'
                    ];
                } else {
                    $user_data = [
                        'fullname'          => $FULLNAME,
                        'username'          => $USERNAME,
                        'password'          => md5($PASSWORD), // Consider password_hash() for better security
                        'area'              => $AREA
                        
                    ];
    
                    $inserted = $this->db->insert('tbl_users', $user_data);
    
                    $response = $inserted
                        ? ['message' => 'Registration Successful!', 'status' => 'success']
                        : ['message' => 'Failed to register user', 'status' => 'failed'];
                }
        } else {
            $response = ['message' => 'Invalid parameters', 'status' => 'failed'];
        }
    
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function approve_user()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: $TOKEN came from the mobile request body.
            // SECURITY UPDATE: Account activation is restricted to the authenticated admin.
            if (!$this->api_auth->require_user('admin')) {
                return;
            }
            $USER_ID       = filter_var($data->USER_ID, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $ACTIVE       = filter_var($data->ACTIVE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            
                // ✅ Check if username already exists
                $this->db->where('userid', $USER_ID);
                $query = $this->db->get('tbl_users');
    
                if ($query->num_rows() > 0) {
                    
                    if($ACTIVE == "YES"){
                        //for deactivation
                        $this->db->set('active', false);
                    }else{
                        //for activation
                        $this->db->set('active', true);
                    }
                    $this->db->where('userid', $USER_ID);
                    $updated = $this->db->update('tbl_users');
    
                    if ($ACTIVE == 'YES'){
                        $response = $updated
                        ? ['message' => 'User has been successfully deactivated!', 'status' => 'success']
                        : ['message' => 'Failed to approve user', 'status' => 'failed'];
                    }else{
                        $response = $updated
                        ? ['message' => 'User has been successfully activated!', 'status' => 'success']
                        : ['message' => 'Failed to approve user', 'status' => 'failed'];
                    }
                    
                    
                } else {
                    $response = [
                        'message' => 'Username already exists. Please choose another.',
                        'status' => 'failed'
                    ];
                }
        } else {
            $response = ['message' => 'Invalid parameters', 'status' => 'failed'];
        }
    
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function userlist()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: $TOKEN was a shared secret stored inside the Android app.
            if (!$this->api_auth->require_user('admin')) { // SECURITY UPDATE: User management requires an admin token.
                return;
            }

                $info = array();

                $num = 1;
                //$this->db->where('active', true);
                $rs = $this->db->order_by('active','asc');
                $rs = $this->db->get('tbl_users');
                
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_userid' => $rw->userid,
                        'data_fullname' => $rw->fullname,
                        'data_username' => $rw->username,
                        'data_type'  => ($rw->type == 'admin') ? 'ADMIN' : 'USER',
                        'data_active' => ($rw->active == 1) ? 'YES' : 'NO',
                        'data_area'  => $rw->area
                    );
                    $num++;
                }
                $response = $info;
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function user_for_approval_list()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: $TOKEN was a shared secret stored inside the Android app.
            if (!$this->api_auth->require_user('admin')) { // SECURITY UPDATE: Approval list is admin-only.
                return;
            }

                $info = array();

                $num = 1;
                
                $verify = $this->db->where('active',false);
                $rs = $this->db->get('tbl_users');
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_userid' => $rw->userid,
                        'data_fullname' => $rw->fullname,
                        'data_username' => $rw->username,
                        'data_area'  => $rw->area
                    );
                    $num++;
                }
                $response = $info;
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
}
