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
    
    public function validate_registration_mobile(){
        
        // Use the global variables
        $now = $this->now;
        $cutoff = $this->cutoff;
        
        // Check if the current time is before 12 noon
        //if ($now->format('H') < 12) 
        if ($now < $cutoff) {
            $data = json_decode(file_get_contents('php://input')); 
            if($data){
                    
                    $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
                $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
    
                if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $AREA = filter_var($data->AREA, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            }
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function area_list()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            }
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
                $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $SEARCH_VALUE = filter_var($data->SEARCH_VALUE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $SEARCH_CONDITION = filter_var($data->SEARCH_CONDITION, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
    
                if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
                $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $SEARCH_VALUE = filter_var($data->SEARCH_VALUE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
    
                if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $ID = filter_var($data->POST->id, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
                $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $ID = filter_var($data->POST->id, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
        
                if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            //$ID = filter_var($data->POST->id, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $ID = filter_var($data->ID, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
                $info = array();

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
            }
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function civil_status_list()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
                $info = array();

                $num = 1;
                $rs = $this->db->order_by('db_civil_code','asc');
                $rs = $this->db->get('tbl_civil_status');
                
                foreach ($rs->result() as $rw) {
                    $info[] = array(
                        'data_num' => $num,
                        'data_civil_code ' => $rw->db_civil_code  ,
                        'data_civil_desc' => $rw->db_civil_desc
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

    public function brgy_list()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $TOWN_CODE = filter_var($data->TOWN_CODE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            $REQUEST = filter_var($data->REQUEST, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            }
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function zoom_details()
    {
         $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            }
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function getInfo()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') {
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
            }
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

//TESTER----------------------------------------------------------------------------------------------------------------------------------------------------------------
    /*public function get_member_no_tester(){
        $data = json_decode(file_get_contents('php://input')); 
       if($data){
            $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
            if ($TOKEN == 'AGMA-06-01-2024-A$ELC0') 
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
