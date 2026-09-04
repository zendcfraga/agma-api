<?php

class Api extends CI_Controller{
    //create ta ug index para mao iyang pangitaon
    //default or first pangitaon sa browser
    
    private $now;
    private $cutoff;
    private $cutoff_msg;

    public function __construct() {
        parent::__construct(); //everytime gina-open ang application under ana nga class 

        $this->load->model('Header'); //load is a property, to call Header class from model
        $this->Header->ApiHeader(); //to call ApiHeader function from Header
        $this->load->library('Api_auth'); // SECURITY UPDATE: Mobile endpoints now validate the logged-in user's Bearer token.
        
        // Load helper for global variables
        $this->load->helper('global_vars');

        // Assign helper values to class properties
        $this->now = get_now();
        $this->cutoff = get_cutoff();
        $this->cutoff_msg = get_message();
    }

    public function index(){
        //$now = $this->now;
        //$now= $now->format('Y-m-d h:i:s');
        
        echo json_encode($this->cutoff_msg,JSON_PRETTY_PRINT);
    }
    
    public function cut_off(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') { // SECURITY UPDATE: Cutoff status is public read-only data.
            http_response_code(405);
            echo json_encode(array('message' => 'Method not allowed', 'status' => 'error'));
            return;
        }

        if ($this->now < $this->cutoff) {
            $response = array('message' => 'Registration still open.', 'status' => 'ok');
        } else {
            $response = array(
                'message' => 'Registrations are now closed. ' . $this->cutoff_msg,
                'status' => 'error'
            );
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    // SECURITY UPDATE: Create a short-lived, one-use permission after the visitor agrees to the privacy statement.
    // This replaces the old browser-only consent check, which users could skip by typing /registration-page directly.
    public function registration_intent()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Creating an intent changes data, so GET is not allowed.
            http_response_code(405);
            echo json_encode(array('message' => 'Method not allowed', 'status' => 'error'));
            return;
        }

        if ($this->now >= $this->cutoff) { // SECURITY UPDATE: Do not issue new consent permissions after cutoff.
            http_response_code(403);
            echo json_encode(array(
                'message' => 'Registrations are now closed. ' . $this->cutoff_msg,
                'status' => 'error'
            ));
            return;
        }

        $data = json_decode(file_get_contents('php://input'));

        if (!$data || !isset($data->CONSENT_ACCEPTED) || $data->CONSENT_ACCEPTED !== true) {
            http_response_code(422); // The visitor must explicitly send true after selecting I Agree.
            echo json_encode(array(
                'message' => 'You must agree to the Data Privacy Statement before continuing.',
                'status' => 'error'
            ));
            return;
        }

        $raw_token = bin2hex(random_bytes(32)); // SECURITY UPDATE: A new unpredictable value is created for this visitor only.
        $token_hash = hash('sha256', $raw_token); // Store only the hash so the database does not contain usable tokens.
        $expires_at = clone $this->now;
        $expires_at->modify('+15 minutes'); // Keep the permission short-lived to reduce reuse or theft.

        $saved = $this->db->insert('tbl_registration_intents', array(
            'token_hash' => $token_hash,
            'privacy_version' => '2026-09-02', // Records which privacy statement version was accepted.
            'consented_at' => $this->now->format('Y-m-d H:i:s'),
            'expires_at' => $expires_at->format('Y-m-d H:i:s'),
            'used_at' => NULL,
            'created_ip' => $this->input->ip_address()
        ));

        if (!$saved) {
            http_response_code(500);
            echo json_encode(array('message' => 'Unable to start registration.', 'status' => 'error'));
            return;
        }

        http_response_code(201);
        echo json_encode(array(
            'status' => 'success',
            'registration_intent' => $raw_token, // Only the browser receives the one-use original token.
            'expires_in' => 900
        ));
    }
    
    public function validate_registration_mobile(){
        
        // Use the global variables
        $now = $this->now;
        $cutoff = $this->cutoff;
        
        // Check if the current time is before 12 noon
        //if ($now->format('H') < 12) 
        if ($now < $cutoff) {
            $data = json_decode(file_get_contents('php://input')); 
            if($data){
                    
            // OLD CODE: TOKEN was read from the request body; Api_auth now verifies the Bearer header.
                if ($this->api_auth->require_user(null, false)) { // SECURITY UPDATE: Replace the shared APK token with a user token.
                    $MEMBER_UNQID = filter_var($data->ACCOUNTNO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
    
                    //$validate_duplicate_reg = $this->db->where('db_member_unqid',$MEMBER_UNQID);
                    $validate_duplicate_reg = $this->db->where('db_account_no',$MEMBER_UNQID);
                    $validate_duplicate_reg = $this->db->where('active',true);
                    $validate_duplicate_reg = $this->db->get('tbl_attendees');
                        
                    if($validate_duplicate_reg->num_rows() == 0 ){
                        
                        $response = array('message' => 'Registration Successful!', 'status' => 'success!');
                    }else{
                        foreach ($validate_duplicate_reg->result() as $rw) {
                            $reg_venue = $rw->db_venue;
                        }
                        $response = array('message' => 'Already registered at ' . $reg_venue .'!', 'status' => 'error');
                    }
                }else{
                    $response =  array('message' => 'You are not authorized. Please contact the Administrator.', 'status' => 'error');
                }
            }else{
                $response = array('message' => 'Invalid Parameters');
            }
        }else {
            $response = array('message' => 'Registrations are now closed. ' . $this->cutoff_msg, 'status' => 'error');
        }
        echo json_encode($response,JSON_PRETTY_PRINT);
    }

    //displays list of members from the DB
    public function members()
    {
        // Get current time in Asia/Manila timezone
        $now = new DateTime("now", new DateTimeZone('Asia/Manila'));
        $cutoff = new DateTime("2024-05-21 12:00:00", new DateTimeZone('Asia/Manila'));
        
        // Check if the current time is before 12 noon
        //if ($now->format('H') < 12) 
        if ($now < $cutoff) {
            $data = json_decode(file_get_contents('php://input'));
            if ($data) {
            // OLD CODE: TOKEN was read from the request body; Api_auth now verifies the Bearer header.
    
                if ($this->api_auth->require_user(null, false)) { // SECURITY UPDATE: Require the logged-in mobile user.
                    $info = array();
    
                    $num = 1;
                    $rs = $this->db->order_by('db_lastname','asc');
                    $rs = $this->db->get('tbl_member_api',50);
    
                    
                    foreach ($rs->result() as $rw) {
                        $info[] = array(
                            'data_num' => $num,
                            'data_id' => $rw->id,
                            'data_memberno' => $rw->db_member_id,
                            'data_lastname' => $rw->db_lastname,
                            'data_firstname' => $rw->db_firstname,
                            'data_middle' => $rw->db_middinit,
                            'data_spouse' => $rw->db_spouse,
                            'data_purok' => $rw->db_purok,
                            'data_brgy' => $rw->db_brgy,
                            'data_town' => $rw->db_town,
                            'data_account_no' => $rw->db_account_no,
                            'data_unqid' => $rw->db_unq_id,
                        );
                        $num++;
                    }
                    $response = $info;
                }
            } else { $response = array('message' => 'Invalid Parameters'); }
            
        }else {
            $$response = array('message' => 'Registrations are now closed. ' . $this->cutoff_msg,'status' => 'error');
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function billing()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: TOKEN was read from the request body; Api_auth now verifies the Bearer header.
            if ($this->api_auth->require_user(null, false)) { // SECURITY UPDATE: Billing data remains login-protected.
                $info = array();

                $num = 1;
                $rs = $this->db->order_by('db_name','asc');
                $rs = $this->db->get('tbl_billing_api',50);
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_num' => $num,
                        'data_id' => $rw->db_id,
                        'data_account_no' => $rw->db_account_no,
                        'data_consumername' => $rw->db_name,
                        'data_address' => $rw->db_address,
                        'data_con_type' => $rw->db_con_type,
                        'data_status' => $rw->db_status,
                        'data_town' => $rw->db_town
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

    public function venue_list()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: TOKEN was read from the request body; Api_auth now verifies the Bearer header.
            $AREA = filter_var($data->AREA, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($this->api_auth->require_user(null, false)) { // SECURITY UPDATE: Require the logged-in mobile user.
                $info = array();

                $num = 1;
                $rs = $this->db->where('db_area',$AREA);
                $rs = $this->db->order_by('db_venue_desc','asc');
                $rs = $this->db->get('tbl_venue');
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_num' => $num,
                        'data_venue_id' => $rw->db_venue_id ,
                        'data_venue_desc' => $rw->db_venue_desc
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
    
    public function venues()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: This guest venue list required a login token.
            // SECURITY UPDATE: Attendance Per User needs this read-only venue list before login.
                $info = array();

                $rs = $this->db->order_by('db_venue_desc','asc');
                $rs = $this->db->get('tbl_venue');
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_venue_id' => $rw->db_venue_id ,
                        'data_venue_desc' => $rw->db_venue_desc
                    );
                }
                $response = $info;
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function area_list()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: Area lookup required the shared APK token.
            // SECURITY UPDATE: This read-only list stays available for the pre-login Create Account form.
                $info = array();

                $rs = $this->db->order_by('area','asc');
                $rs = $this->db->get('tbl_area');
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_areacode' => $rw->areacode ,
                        'data_area' => $rw->area
                    );
                }
                $response = $info;
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function billing_search()
    {
        
        // Use the global variables
        $now = $this->now;
        $cutoff = $this->cutoff;
                
        if ($now < $cutoff) {
            $data = json_decode(file_get_contents('php://input'));
            if ($data) {
            // OLD CODE: TOKEN was read from the request body; Api_auth now verifies the Bearer header.
                $SEARCH_VALUE = filter_var($data->SEARCH_VALUE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $SEARCH_CONDITION = filter_var($data->SEARCH_CONDITION, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
    
                if ($this->api_auth->require_user(null, false)) { // SECURITY UPDATE: Require the logged-in mobile user.
                    $info = array();
    
                    $num = 1;
                    if ($SEARCH_CONDITION == "account"){
                        $rs = $this->db->where('tbl_billing_api.db_account_no', $SEARCH_VALUE);
                    }else{
                        //name
                        $rs = $this->db->where('tbl_billing_api.db_name LIKE', '%'. $SEARCH_VALUE .'%');
                    }
                    //$rs = $this->db->order_by('db_name','asc');
                    //$rs = $this->db->get('tbl_billing',50);
                    
                    $this->db->select('DISTINCT tbl_billing_api.*, trim(tbl_attendees.db_account_no) as attendee_account_no', false);
                    $this->db->from('tbl_billing_api');
                    $this->db->join('tbl_attendees', 'tbl_billing_api.db_account_no = tbl_attendees.db_account_no and tbl_attendees.active = 1', 'left');
                    $this->db->order_by('tbl_billing_api.db_name, tbl_billing_api.db_town', 'asc');
                    $this->db->limit(10);
                    
                    $rs = $this->db->get();
    
                    
                    foreach ($rs->result() as $rw) {
                        if ($rw->db_con_type == '1'){
                            $conntype = 'Residential';
                        }elseif ($rw->db_con_type == '2'){
                            $conntype = 'Commercial';
                        }elseif ($rw->db_con_type == '3'){
                            $conntype = 'High Voltage';
                        }elseif ($rw->db_con_type == '4'){
                            $conntype = 'Industrial';
                        }elseif ($rw->db_con_type == '5'){
                            $conntype = 'Public Bldg';
                        }elseif ($rw->db_con_type == '6'){
                            $conntype = 'Street Lights';
                        }else{
                            $conntype = 'Others';
                        }
                        $info[] = array(
                            'data_num' => $num,
                            'data_id' => $rw->db_id,
                            'data_account_no' => $rw->db_account_no,
                            'data_consumername' => $rw->db_name,
                            'data_address' => $rw->db_address,
                            'data_con_type' => $conntype,
                            'data_status' => $rw->db_status,
                            'data_town' => $rw->db_town,
                            'data_reg_account' => $rw->attendee_account_no
                        );
                        $num++;
                    }
                    $response = $info;
                }
            } else {
                $response = array('message' => 'Invalid Parameters');
            }
        }else {
            $response = array('message' => 'Registrations are now closed. ' . $this->cutoff_msg,'status' => 'error');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }


    //GETS MEMBER INFO
    public function member_search()
    {
        // Use the global variables
        $now = $this->now;
        $cutoff = $this->cutoff;
                
        
        if ($now < $cutoff) {
            $data = json_decode(file_get_contents('php://input'));
            if ($data) {
            // OLD CODE: TOKEN was read from the request body; Api_auth now verifies the Bearer header.
                $SEARCH_VALUE = filter_var($data->SEARCH_VALUE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
    
                if ($this->api_auth->require_user(null, false)) { // SECURITY UPDATE: Require the logged-in mobile user.
                    $info = array();
    
                    $num = 1;
                    
                    $this->db->select('tbl_member_api.*, tbl_attendees.db_spouse_member as reg_spouse');
                    $this->db->from('tbl_member_api');
                    $this->db->join('tbl_attendees', 'tbl_member_api.db_unq_id = tbl_attendees.db_member_unqid AND tbl_member_api.db_member_id = tbl_attendees.db_member_no and tbl_attendees.active = 1', 'left');
                    $this->db->where('db_member_id', $SEARCH_VALUE);
                    $this->db->or_where("CONCAT(db_lastname,', ', db_firstname) LIKE ", "%". $SEARCH_VALUE ."%");
                    $this->db->or_where("CONCAT(db_last,', ', db_first) LIKE ", "%". $SEARCH_VALUE ."%");
                    $this->db->order_by('db_lastname,db_firstname','asc');
                    $this->db->limit(5);
                    
                    $rs = $this->db->get();
    
                    foreach ($rs->result() as $rw) {
                        
                        if ($rw->reg_spouse == '1'){
                            $reg_spouse = "Spouse registered!";
                        }elseif ($rw->reg_spouse == '0'){
                            $reg_spouse = "Primary registered!";
                        } elseif ($rw->reg_spouse == null){
                            $reg_spouse = "na";
                        }
                        
                        $info[] = array(
                            'data_num' => $num,
                            'data_id' => $rw->id,
                            'data_memberno' => $rw->db_member_id,
                            'data_lastname' => $rw->db_lastname,
                            'data_firstname' => $rw->db_firstname,
                            'data_middle' => $rw->db_middinit,
                            'data_spouse' => $rw->db_spouse,
                            'data_purok' => $rw->db_purok,
                            'data_brgy' => $rw->db_brgy,
                            'data_town' => $rw->db_town,
                            'data_account_no' => $rw->db_account_no,
                            'data_unqid' => $rw->db_unq_id,
                            'data_reg_spouse' => $reg_spouse ,
                        );
                        $num++;
                    }
                    $response = $info;
                }
            } else {
                $response = array('message' => 'Invalid Parameters');
            }
        }else {
            $response = array('message' => 'Registrations are now closed. ' . $this->cutoff_msg,'status' => 'error');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    //GETS MEMBER INFO
    public function member_info()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: TOKEN was read from the request body; Api_auth now verifies the Bearer header.
            $ID = filter_var($data->POST->id, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($this->api_auth->require_user(null, false)) { // SECURITY UPDATE: Require the logged-in mobile user.
                $info = array();

                $num = 1;
                $rs = $this->db->where('db_unq_id', $ID);
                $rs = $this->db->get('tbl_member_api',3);

                //$rs = $this->db->query("SELECT * FROM tbl_member WHERE db_lastname LIKE '". $SEARCH_VALUE ."%'");
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_num' => $num,
                        'data_id' => $rw->id,
                        'data_memberno' => $rw->db_member_id,
                        'data_lastname' => $rw->db_lastname,
                        'data_firstname' => $rw->db_firstname,
                        'data_middle' => $rw->db_middinit,
                        'data_spouse' => $rw->db_spouse,
                        'data_purok' => $rw->db_purok,
                        'data_brgy' => $rw->db_brgy,
                        'data_town' => $rw->db_town,
                        'data_account_no' => $rw->db_account_no,
                        'data_unqid' => $rw->db_unq_id,
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
    
    /*public function member_info()
    {
            $data = json_decode(file_get_contents('php://input'));
            if ($data) {
            // OLD CODE: TOKEN was read from the request body; Api_auth now verifies the Bearer header.
                $ID = filter_var($data->POST->id, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
        
                if ($TOKEN == 'OLD_SHARED_APP_TOKEN') { // OLD COMMENTED CODE: Redacted because exposed tokens must not stay in source.
                    $rs = $this->db->where('db_unq_id', $ID)
                                   ->get('tbl_member', 3);
        
                    if ($rs->num_rows() > 0) {
                        foreach ($rs->result() as $rw) {
                            Check if account_no exists in tbl_attendees
                            $exists = $this->db->where('db_account_no', $rw->db_account_no)
                                               ->where('db_last', $rw->db_lastname)
                                               ->where('db_first', $rw->db_firstname)
                                               ->get('tbl_attendees')
                                               ->num_rows() > 0;
        
                            if ($exists) {
                                Immediately send error response
                                $response = array(
                                    'status'  => 'error',
                                    'message' => $rw->db_lastname . ', ' .$rw->db_firstname.' with Account # '. $rw->db_account_no. ' already exists in attendees list.'
                                );
                                echo json_encode($response, JSON_PRETTY_PRINT);
                                return; stop execution
                            }
        
                            If not exists, prepare success response
                            $info[] = array(
                                'data_id'        => $rw->id,
                                'data_memberno'  => $rw->db_member_id,
                                'data_lastname'  => $rw->db_lastname,
                                'data_firstname' => $rw->db_firstname,
                                'data_middle'    => $rw->db_middinit,
                                'data_spouse'    => $rw->db_spouse,
                                'data_purok'     => $rw->db_purok,
                                'data_brgy'      => $rw->db_brgy,
                                'data_town'      => $rw->db_town,
                                'data_account_no'=> $rw->db_account_no,
                                'data_unqid'     => $rw->db_unq_id
                            );
                        }
        
                        $response = $info;
                    } else {
                        $response = array('status' => 'error', 'message' => 'Member not found');
                    }
                } else {
                    $response = array('status' => 'error', 'message' => 'Invalid token');
                }
            } else {
                $response = array('status' => 'error', 'message' => 'Invalid parameters');
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }*/

    private function separateNames($accountNames) {
        $separatedNames = array();
    
        foreach ($accountNames as $name) {
            $nameParts = explode(', ', $name);
            
            // Ensure that $nameParts has at least 2 elements (Lastname, Firstname)
            if (count($nameParts) >= 2) {
                $lastName = $nameParts[0];
                $firstNameMiddle = explode(' ', $nameParts[1]);
                $firstName = $firstNameMiddle[0];
                $middleInitial = isset($firstNameMiddle[1]) ? substr($firstNameMiddle[1], 0, 1) : '';
                $nameExtension = isset($nameParts[2]) ? $nameParts[2] : '';
    
                $separatedName = array(
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'middle_initial' => $middleInitial,
                    'name_extension' => $nameExtension
                );
    
                $separatedNames[] = $separatedName;
            } else {
                // Handle invalid format gracefully, you might want to log this
                // or take appropriate action based on your application's requirements.
            }
        }
    
        return $separatedNames;
    }
    
    private function getMemberNo($last, $first, $town, $accountno){

        $member_no = null; // Initializing member_no to null

        $this->db->where('db_account_no', $accountno);
        $rs = $this->db->get('tbl_member_api');

        if ($rs->num_rows() == 1){
            foreach ($rs->result() as $rw) {
                $member_no = $rw->db_member_id;
            }
        }else{
            $this->db->where('db_lastname', $last);
            $this->db->group_start();
            $this->db->where('db_firstname', $first);
            $this->db->or_like('db_spouse', $first);
            $this->db->group_end();
            $this->db->where('db_town', $town);
            $rs = $this->db->get('tbl_member_api');

            if ($rs->num_rows() == 1){
                foreach ($rs->result() as $rw) {
                    $member_no = $rw->db_member_id;
                }
            }
        }
        return $member_no;
    }
    
    public function billing_info()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: TOKEN was read from the request body; Api_auth now verifies the Bearer header.
            //$ID = filter_var($data->POST->id, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $ID = filter_var($data->ID, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($this->api_auth->require_user(null, false)) { // SECURITY UPDATE: Require the logged-in mobile user.
                $info = array();

                $num = 1;
                $rs = $this->db->where('db_account_no', $ID)->get('tbl_billing_api');

                foreach ($rs->result() as $rw) {
                    $consumerNameArray = $this->separateNames(array($rw->db_name));
                    if (!empty($consumerNameArray)) {
                        $consumerName = $consumerNameArray[0];
                        $member_no = $this->getMemberNo($consumerName['last_name'], $consumerName['first_name'], trim($rw->db_town), trim($rw->db_account_no));

                        $info[] = array(
                            'data_num' => $num,
                            'data_id' => $rw->db_id,
                            'data_account_no' => $rw->db_account_no,
                            'data_consumername' => $rw->db_name,
                            'data_address' => $rw->db_address,
                            'data_con_type' => $rw->db_con_type,
                            'data_status' => $rw->db_status,
                            'data_town' => trim($rw->db_town),
                            'data_lastname' => isset($consumerName['last_name']) ? $consumerName['last_name'] : null,
                            'data_firstname' => isset($consumerName['first_name']) ? $consumerName['first_name'] : null,
                            'data_middle' => isset($consumerName['middle_initial']) ? $consumerName['middle_initial'] : null,
                            'data_member_no' => $member_no
                        );
                        $num++;
                    } else {
                        $info[] = array(
                            'data_num' => $num,
                            'data_id' => $rw->db_id,
                            'data_account_no' => $rw->db_account_no,
                            'data_consumername' => $rw->db_name,
                            'data_address' => $rw->db_address,
                            'data_con_type' => $rw->db_con_type,
                            'data_status' => $rw->db_status,
                            'data_town' => trim($rw->db_town),
                            'data_lastname' => '',
                            'data_firstname' => '',
                            'data_middle' => '',
                            'data_member_no' => ''
                        );
                        $num++;
                    }
                }
                $response = $info;
            }
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function town_list()
    {
        // SECURITY UPDATE: This is public dropdown data; a token shipped in React cannot protect it.
        // $data = json_decode(file_get_contents('php://input'));
        // $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
        // if ($TOKEN == 'OLD_BROWSER_TOKEN_REMOVED') {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') { // Only allow the read-only HTTP method used by agma-web-app.
            http_response_code(405);
            echo json_encode(array('message' => 'Method not allowed', 'status' => 'error'));
            return;
        }

                $info = array(); // SECURITY UPDATE: Always initialize a safe empty response.

                $num = 1;
                $rs = $this->db->order_by('db_town_name','asc');
                $rs = $this->db->get('tbl_town');
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_num' => $num,
                        'data_town_code' => $rw->town_code ,
                        'data_town_name' => $rw->db_town_name
                    );
                    $num++;
                }
                $response = $info;
        // } // OLD TOKEN CHECK CLOSING
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function civil_status_list()
    {
        // SECURITY UPDATE: This is public dropdown data; do not pretend a browser token is secret.
        // $data = json_decode(file_get_contents('php://input'));
        // $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
        // if ($TOKEN == 'OLD_BROWSER_TOKEN_REMOVED') {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') { // Only allow read-only requests.
            http_response_code(405);
            echo json_encode(array('message' => 'Method not allowed', 'status' => 'error'));
            return;
        }

                $info = array(); // SECURITY UPDATE: Always initialize a safe empty response.

                $num = 1;
                $rs = $this->db->order_by('db_civil_code','asc');
                $rs = $this->db->get('tbl_civil_status');
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_num' => $num,
                        'data_civil_code' => $rw->db_civil_code, // SECURITY UPDATE: Removed the accidental space in the JSON key.
                        'data_civil_desc' => $rw->db_civil_desc
                    );
                    $num++;
                }
                $response = $info;
        // } // OLD TOKEN CHECK CLOSING
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function brgy_list()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // SECURITY UPDATE: This lookup expects JSON input.
            http_response_code(405);
            echo json_encode(array('message' => 'Method not allowed', 'status' => 'error'));
            return;
        }

        $data = json_decode(file_get_contents('php://input'));
        if ($data && isset($data->TOWN_CODE)) { // SECURITY UPDATE: Validate the required property before reading it.
            // $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW); // OLD: Browser token was public.
            $TOWN_CODE = filter_var($data->TOWN_CODE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $REQUEST = isset($data->REQUEST) ? filter_var($data->REQUEST, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW) : 'web'; // SECURITY UPDATE: Safe default for web.

            // if ($TOKEN == 'OLD_BROWSER_TOKEN_REMOVED') { // OLD: Never authenticate a public browser with a shared secret.
                $info = array();

                if ($REQUEST=="mobile"){
                    if($TOWN_CODE == "Bayugan" || $TOWN_CODE == "BAYUGAN"){
                        $town = $this->db->where('db_town_name LIKE', '%'. "BAYUGAN" .'%');
                    }else if ($TOWN_CODE == "Sta. Josefa" || $TOWN_CODE == "STA. JOSEFA"){
                        $town = $this->db->where('db_town_name LIKE', '%'. "JOSEFA" .'%');
                    }
                    else{
                        $town = $this->db->where('db_town_name',$TOWN_CODE);
                    }
                    $town = $this->db->get('tbl_town');

                    if ($town->num_rows() > 0) {
                        foreach ($town->result()as $row) {
                            $TOWN_CODE = $row->town_code;
                         }
                    } 
                }

                $num = 1;
                $rs = $this->db->where('db_town_code',$TOWN_CODE);
                $rs = $this->db->order_by('db_brgy_name','asc');
                $rs = $this->db->get('tbl_brgys');
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_num' => $num,
                        'data_brgy_code' => $rw->db_brgy_code ,
                        'data_brgy_name' => $rw->db_brgy_name
                    );
                    $num++;
                }
                $response = $info;
            // } // OLD TOKEN CHECK CLOSING
        } else {
            http_response_code(422); // SECURITY UPDATE: Tell the client that required input is missing.
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function zoom_details()
    {
        // OLD CODE: GET returned Zoom details to anyone who knew the public URL.
        // if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // SECURITY UPDATE: Receipt is sent in a JSON POST body.
            http_response_code(405);
            echo json_encode(array('message' => 'Method not allowed', 'status' => 'error'));
            return;
        }

        $data = json_decode(file_get_contents('php://input'));

        if (!$data || !isset($data->REGISTRATION_RECEIPT) || !is_string($data->REGISTRATION_RECEIPT) || trim($data->REGISTRATION_RECEIPT) === '') {
            http_response_code(403);
            echo json_encode(array('message' => 'Successful registration is required.', 'status' => 'error'));
            return;
        }

        $now = get_now();
        $receipt_hash = hash('sha256', trim($data->REGISTRATION_RECEIPT)); // Compare the receipt without storing its usable value.

        // SECURITY UPDATE: Claim the receipt first so two requests cannot both see the Zoom details.
        $this->db->trans_begin();

        $this->db
            ->where('token_hash', $receipt_hash)
            ->where('used_at IS NOT NULL', NULL, FALSE)
            ->where('confirmation_viewed_at IS NULL', NULL, FALSE)
            ->where('expires_at >=', $now->format('Y-m-d H:i:s'))
            ->update('tbl_registration_intents', array(
                'confirmation_viewed_at' => $now->format('Y-m-d H:i:s')
            ));

        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            http_response_code(403);
            echo json_encode(array(
                'message' => 'Zoom details were already viewed or the receipt has expired.',
                'status' => 'error'
            ));
            return;
        }

                $info = array();

               //$rs = $this->db->order_by('db_civil_code','asc');
                $rs = $this->db->get('tbl_zoom_details');
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_link' => $rw->db_link,
                        'data_meeting_id' => $rw->db_meeting_id,
                        'data_passcode' => $rw->db_passcode
                    );
                }
                $response = $info;

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            http_response_code(500);
            echo json_encode(array('message' => 'Unable to load meeting details.', 'status' => 'error'));
            return;
        }

        $this->db->trans_commit(); // SECURITY UPDATE: Receipt becomes permanently viewed only when details were loaded.
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function getInfo()
    {
        // SECURITY UPDATE: Event heading information is public and does not need a browser token.
        // $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW); // OLD browser token.
        // if ($TOKEN == 'OLD_BROWSER_TOKEN_REMOVED') {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') { // Only allow read-only requests.
            http_response_code(405);
            echo json_encode(array('message' => 'Method not allowed', 'status' => 'error'));
            return;
        }

                $info = array();
                
                //$this->db->order_by('id', 'DESC');
                //$this->db->limit(1);
                $rs = $this->db->get('tbl_info');
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_date' => $rw->db_date,
                        'data_theme1' => $rw->db_theme1 ,
                        'data_theme2' => $rw->db_theme2
                    );
                }
                $response = $info;
        // } // OLD TOKEN CHECK CLOSING
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

//TESTER----------------------------------------------------------------------------------------------------------------------------------------------------------------
    /*public function get_member_no_tester(){
        $data = json_decode(file_get_contents('php://input')); 
       if($data){
            // OLD CODE: TOKEN was read from the request body; Api_auth now verifies the Bearer header.
            if ($TOKEN == 'OLD_SHARED_APP_TOKEN') // OLD COMMENTED CODE: Redacted because exposed tokens must not stay in source.
            {
                    $TOWN = filter_var($data->TOWN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $LASTNAME = filter_var($data->LASTNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $FIRSTNAME = filter_var($data->FIRSTNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $MIDDLE = filter_var($data->MIDDLE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $SPOUSE = filter_var($data->SPOUSE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $BRGY = filter_var($data->BRGY, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $ACCOUNT_NO = filter_var($data->ACCOUNT_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    
        
                    $townname = $this->db->where('town_code',$TOWN);
                    $townname = $this->db->get('tbl_town');
            
                    if ($townname->num_rows() > 0) {
                        foreach ($townname->result()as $row) {
                            $TOWN = $row->db_town_name;
                        }
                    } 
                    
                    
                    $brgyname = $this->db->where('db_brgy_code',$BRGY);
                    $brgyname = $this->db->get('tbl_brgys');
            
                    if ($brgyname->num_rows() > 0) {
                        foreach ($brgyname->result()as $row) {
                            $BRGY = $row->db_brgy_name;
                        }
                    }
                    
                    $info = array();
                    $query = "SELECT * FROM `tbl_member` WHERE ";
                    $query .= "db_lastname = '" . $LASTNAME . "' ";
                    $query .= "AND db_firstname = '" . $FIRSTNAME . "' ";
                    $query .= "AND db_middinit LIKE CONCAT(LEFT('" . $MIDDLE . "', 1), '%') ";
                    $query .= "AND db_town = '" . $TOWN . "' ";
                    $query .= "AND db_brgy = '" . $BRGY . "' ";
                    $query .= "AND (db_spouse = '" . $SPOUSE . "' ";
                    $query .= "OR db_account_no = '" . $ACCOUNT_NO . "' ";
                    $query .= "OR db_firstname = '" . $SPOUSE . "' ";
                    $query .= "OR db_spouse = '" . $FIRSTNAME . "')";
                        
                    $member_no = $this->db->query($query);
                    
                    if ($member_no->num_rows() > 0) {
                        foreach ($member_no->result()as $row) {
                            $info[] = array(
                            'data_member_no' => $row->db_member_id,
                            'data_town' =>$TOWN,
                            'data_brgy' =>$BRGY,
                            );
                         }
                    }
                    $response = $info;
                    
                echo json_encode($response,JSON_PRETTY_PRINT);
                
            }else{ //TOKEN closing
                $response =  array('message' => 'You are not authorized. Please contact the Administrator.', 'status' => 'error');
            }
       }else{
           $response = array('message' => 'Invalid Parameters');
       }
    } */
}
