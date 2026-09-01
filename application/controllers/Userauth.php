<?php

class Userauth extends CI_Controller{
    //create ta ug index para mao iyang pangitaon
    //default or first pangitaon sa browser
    
    
    public function __construct() {
        parent::__construct(); //everytime gina-open ang application under ana nga class 

        $this->load->model('Header'); //load is a property, to call Header class from model
        $this->Header->ApiHeader(); //to call ApiHeader function from Header
    }

    public function index(){
        
        echo json_encode($now,JSON_PRETTY_PRINT);
    }
    
    public function login(){
        $data = json_decode(file_get_contents('php://input'));

        if($data){
            $TOKEN     = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $username = filter_var($data->USERNAME_POST, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $password = filter_var($data->PASSWORD_POST, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN === 'AGMA-06-01-2024-A$ELC0') {
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
                    $response = array(
                        'message' => 'Login Successful',
                        'status' => 'success',
                        'areacode' => $rw->area,
                        'user_type' => $rw->type // Assuming your table has a `user_type` column
                    );
                } else {
                    $response = array(
                        'message' => 'Invalid/Inactive Username or Password!',
                        'status' => 'error'
                    );
                }
            }else {
                $response = ['message' => 'Unauthorized token', 'status' => 'failed'];
            }

            
        }else{
            $response = array('message' => 'Invalid parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function account_registration()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            $TOKEN          = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $FULLNAME       = filter_var($data->FULLNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $USERNAME       = filter_var($data->USERNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $PASSWORD       = filter_var($data->PASSWORD, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $AREA           = filter_var($data->AREA, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            
            if ($TOKEN === 'AGMA-06-01-2024-A$ELC0') {
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
                $response = ['message' => 'Unauthorized token', 'status' => 'failed'];
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
            $TOKEN          = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $USER_ID       = filter_var($data->USER_ID, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $ACTIVE       = filter_var($data->ACTIVE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            
            if ($TOKEN === 'AGMA-06-01-2024-A$ELC0') {
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
                $response = ['message' => 'Unauthorized token', 'status' => 'failed'];
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
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            }
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function user_for_approval_list()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            }
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
}
