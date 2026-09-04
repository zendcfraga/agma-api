<?php

class Reg extends CI_Controller{
    //create ta ug index para mao iyang pangitaon
    //default or first pangitaon sa browser
    
    private $now;
    private $cutoff;
    private $cutoff_msg;

    public function __construct() {
        parent::__construct(); //everytime gina-open ang application under ana nga class 

        $this->load->model('Header'); //load is a property, to call Header class from model
        $this->Header->ApiHeader(); //to call ApiHeader function from Header
        $this->load->library('Api_auth'); // SECURITY UPDATE: Mobile registration/report calls use user-specific Bearer tokens.
        
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

    public function attendance_registration_mobile(){

        $data = json_decode(file_get_contents('php://input')); 
        if($data){
                
                // OLD CODE: TOKEN was read from the request body.
            if ($this->api_auth->require_user(null, false)) { // SECURITY UPDATE: Require the logged-in mobile user.
                
                $now = $this->now;
                $now= $now->format('Y-m-d h:i:s');
                
                $MEMBER_UNQID = filter_var($data->ID, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);//$MEMBER_UNQID = filter_var($data->POST->id, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $MEMBER_NO = filter_var($data->MEMBER_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $ACCOUNT_NO = filter_var($data->ACCOUNT_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $TOWN = filter_var($data->TOWN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $CONTACT_NO = filter_var($data->CONTACT_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $VENUE = filter_var($data->VENUE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $REG_MODE = filter_var($data->REG_MODE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                //Registration Date
                $LASTNAME = filter_var($data->LASTNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $BRGY = filter_var($data->BRGY, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $USERNAME = filter_var($data->USERNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $SOURCE = filter_var($data->SOURCE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

                $this->db->where('db_town_name',$TOWN);
                $town_code = $this->db->get('tbl_town');

                if ($town_code->num_rows() > 0) {
                    foreach ($town_code->result()as $row) {
                        $TOWN = $row->town_code;
                    }
                }

                /*$this->db->where('db_brgy_name',$BRGY);
                $this->db->where('db_town_code',$TOWN);
                $brgy_code = $this->db->get('tbl_brgys');

                if ($brgy_code->num_rows() > 0) {
                    foreach ($brgy_code->result()as $rbrgy) {
                        $BRGY = $rbrgy->db_brgy_code;
                    }
                }*/

                $validate_duplicate_reg = $this->db->where('db_member_unqid',$MEMBER_UNQID);
                $validate_duplicate_reg = $this->db->where('db_account_no',$ACCOUNT_NO);
                $validate_duplicate_reg = $this->db->where('active',true);
                $validate_duplicate_reg = $this->db->get('tbl_attendees');

                if ($VENUE == "" || $BRGY == ""){
                    $response = array('message' => 'Kindly select the Venue/Barangay.', 'status' => 'venue_error');
                }else{
                    
                    $registration = array(
                    'db_member_unqid' => $MEMBER_UNQID,
                    'db_last' => $LASTNAME,
                    'db_brgy' => $BRGY,
                    'db_member_no'=>$MEMBER_NO,
                    'db_account_no'=>$ACCOUNT_NO,
                    'db_town' => $TOWN,
                    'db_contact_no' => $CONTACT_NO,
                    'db_venue' => $VENUE,
                    'db_registration_mode' => $REG_MODE, //or online or onsite
                    'username' => $USERNAME,
                    'mode' =>$SOURCE,
                    'db_registration_date' => $now
                    );
                    
                    if($validate_duplicate_reg->num_rows() == 0 ){
                        $this->db->insert('tbl_attendees', $registration);
                        $response = array('message' => 'Registration Successful!', 'status' => 'Success!');
                    }else{
                        $response = array('message' => 'Already registered!', 'status' => 'error');
                    }
                }
            }else{
                $response =  array('message' => 'You are not authorized. Please contact the Administrator.', 'status' => 'error');
            }
        }else{
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response,JSON_PRETTY_PRINT);
    }
    
    public function att_reg_membership(){
         $data = json_decode(file_get_contents('php://input')); 
        if($data){
                
                // OLD CODE: TOKEN was read from the request body.
            if ($this->api_auth->require_user(null, false)) { // SECURITY UPDATE: Require the logged-in mobile user.
                
                $now = $this->now;
                $now= $now->format('Y-m-d h:i:s');

                
                $MEMBER_UNQID = filter_var($data->POST->id, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $MEMBER_NO = filter_var($data->MEMBER_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $ACCOUNT_NO = filter_var($data->ACCOUNT_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $TOWN = filter_var($data->TOWN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $CONTACT_NO = filter_var($data->CONTACT_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $VENUE = filter_var($data->VENUE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $REG_MODE = filter_var($data->REG_MODE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                //Registration Date
                $LASTNAME = filter_var($data->LASTNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $FIRSTNAME = filter_var($data->FIRSTNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $MIDDLE = filter_var($data->MIDDLE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $SPOUSE = filter_var($data->SPOUSE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $BRGY = filter_var($data->BRGY, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $PUROK = filter_var($data->PUROK, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $SPOUSE_REG = filter_var($data->SPOUSE_REG, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                $USERNAME = filter_var($data->USERNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                

                $this->db->where('db_town_name',$TOWN);
                $town_code = $this->db->get('tbl_town');

                if ($town_code->num_rows() > 0) {
                    foreach ($town_code->result()as $row) {
                        $TOWN = $row->town_code;
                    }
                }

                $this->db->where('db_brgy_name',$BRGY);
                $this->db->where('db_town_code',$TOWN);
                $brgy_code = $this->db->get('tbl_brgys');

                if ($brgy_code->num_rows() > 0) {
                    foreach ($brgy_code->result()as $rbrgy) {
                        $BRGY = $rbrgy->db_brgy_code;
                    }
                }
                
                //$validate_duplicate_reg = $this->db->where('db_member_unqid',$MEMBER_UNQID);
                //$validate_duplicate_reg = $this->db->where('db_account_no',$ACCOUNT_NO);
                //$validate_duplicate_reg = $this->db->where('db_first',$FIRSTNAME);
                //$validate_duplicate_reg = $this->db->get('tbl_attendees');

                /*$this->db->where('db_member_unqid', $MEMBER_UNQID);
                $this->db->where('db_account_no', $ACCOUNT_NO);
                $this->db->where('db_first', $FIRSTNAME);
                $this->db->group_start();
                $this->db->like('db_last', $FIRSTNAME);
                $this->db->like('db_last', $LASTNAME);
                $this->db->group_end();
                $validate_duplicate_reg = $this->db->get('tbl_attendees');*/
                
                $query = "SELECT * FROM `tbl_attendees` WHERE active = 1 ";
                $query .= "AND db_member_unqid = '" . $MEMBER_UNQID . "' ";
                $query .= "AND (db_account_no = '" . $ACCOUNT_NO . "' ";
                $query .= "OR db_account_no = 0) ";
                $query .= "AND db_first = '" . $FIRSTNAME . "' ";
                $query .= "OR (db_last LIKE '%" . $FIRSTNAME . "%' ";
                $query .= "AND db_last LIKE '%" . $LASTNAME . "%') ";
                $query .= "AND db_town = " . $TOWN . " ";
                        
                $validate_duplicate_reg = $this->db->query($query);
                
                $query = "SELECT * FROM `tbl_attendees` WHERE active = 1 ";
                $query .= "AND db_account_no = '" . $ACCOUNT_NO . "' ";
                $query .= "AND db_first = '" . $FIRSTNAME . "' ";
                $query .= "AND db_town = " . $TOWN;
                        
                $validate_duplicate_acctno = $this->db->query($query);

                if ($VENUE == "" || $BRGY == ""){
                    $response = array('message' => 'Kindly select the Venue/Barangay.', 'status' => 'venue_error');
                }else{
                    
                    $registration = array(
                    'db_member_unqid' => $MEMBER_UNQID,
                    'db_last' => $LASTNAME,
                    'db_first' => $FIRSTNAME,
                    'db_middle' => $MIDDLE,
                    'db_spouse' => $SPOUSE,
                    'db_brgy' => $BRGY,
                    'db_purok' => $PUROK,
                    'db_member_no'=>$MEMBER_NO,
                    'db_account_no'=>$ACCOUNT_NO,
                    'db_town' => $TOWN,
                    'db_contact_no' => $CONTACT_NO,
                    'db_venue' => $VENUE,
                    'db_registration_mode' => $REG_MODE, //or online
                    'db_spouse_member' => $SPOUSE_REG,
                    'username' => $USERNAME,
                    'mode' => 'search',
                    'db_registration_date' => $now
                    );
                    
                    if($validate_duplicate_reg->num_rows() > 0 || $validate_duplicate_acctno->num_rows() > 0){
                        $response = array('message' => 'Already registered!', 'status' => 'error');
                    }else{
                        $this->db->insert('tbl_attendees', $registration);
                        $response = array('message' => 'Registration Successful!', 'status' => 'Success!');
                    }
                }

                echo json_encode($response,JSON_PRETTY_PRINT);
            }else{
                $response =  array('message' => 'You are not authorized. Please contact the Administrator.', 'status' => 'error');
            }
        }else{
            $response = array('message' => 'Invalid Parameters');
        }
    }
    
    private function getMemberID($account_no,$town,$lname,$fname,$middle,$spouse,$brgy) {
        $memberid = "";
        
        $townname = $this->db->where('town_code',$town);
        $townname = $this->db->get('tbl_town');
            
        if ($townname->num_rows() > 0) {
            foreach ($townname->result()as $row) {
                $town = $row->db_town_name;
            }
        } 
                    
                    
        $brgyname = $this->db->where('db_brgy_code',$brgy);
        $brgyname = $this->db->get('tbl_brgys');
            
        if ($brgyname->num_rows() > 0) {
            foreach ($brgyname->result()as $row) {
                $brgy = $row->db_brgy_name;
            }
        }
    
        $query = "SELECT * FROM `tbl_member` WHERE ";
                    $query .= "db_lastname = '" . $lname . "' ";
                    $query .= "AND db_firstname = '" . $fname . "' ";
                    $query .= "AND db_middinit LIKE CONCAT(LEFT('" . $middle . "', 1), '%') ";
                    $query .= "AND db_town = '" . $town . "' ";
                    $query .= "AND db_brgy = '" . $brgy . "' ";
                    $query .= "AND (db_spouse = '" . $spouse . "' ";
                    $query .= "OR db_account_no = '" . $account_no . "' ";
                    $query .= "OR db_firstname = '" . $spouse . "' ";
                    $query .= "OR db_spouse = '" . $fname . "')";
                        
                    $member_no = $this->db->query($query);
                    
                    if ($member_no->num_rows() > 0) {
                        foreach ($member_no->result()as $row) {
                            $memberid = $row->db_member_id;
                         }
                    }
    
        return $memberid;
    }

    public function attendance_registration_online(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // SECURITY UPDATE: Registration changes data, so only accept POST.
            http_response_code(405);
            echo json_encode(array('message' => 'Method not allowed', 'status' => 'error'));
            return;
        }
        
        // Use the global variables
        $now = $this->now;
        $cutoff = $this->cutoff;
        
        if ($now < $cutoff) {
            $data = json_decode(file_get_contents('php://input')); 
           if($data){
                // SECURITY UPDATE: Consent must be proven by a valid short-lived intent created on the consent page.
                if (!isset($data->REGISTRATION_INTENT) || !is_string($data->REGISTRATION_INTENT) || trim($data->REGISTRATION_INTENT) === '') {
                    http_response_code(403);
                    echo json_encode(array(
                        'message' => 'Please agree to the Data Privacy Statement before registering.',
                        'status' => 'error'
                    ));
                    return;
                }

                $intent_hash = hash('sha256', trim($data->REGISTRATION_INTENT)); // Compare hashes; never store the usable token.
                $intent = $this->db
                    ->where('token_hash', $intent_hash)
                    ->where('used_at IS NULL', NULL, FALSE)
                    ->where('expires_at >=', $this->now->format('Y-m-d H:i:s'))
                    ->limit(1)
                    ->get('tbl_registration_intents');

                if ($intent->num_rows() !== 1) { // Reject missing, expired, already-used, or made-up tokens.
                    http_response_code(403);
                    echo json_encode(array(
                        'message' => 'Your consent permission is invalid or expired. Please start again.',
                        'status' => 'error'
                    ));
                    return;
                }

                $intent_id = $intent->row()->id; // Keep the row ID so the exact intent can be consumed after validation.

                // SECURITY UPDATE: The two old lines below are intentionally disabled because React cannot keep a secret.
                // $TOKEN = filter_var($data->TOKEN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                // if ($TOKEN == 'OLD_BROWSER_TOKEN_REMOVED')
                // {
                        $now = $this->now;
                        $now= $now->format('Y-m-d h:i:s');
    
                        
                        $MEMBER_UNQID = filter_var($data->UNQ_ID, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $MEMBER_NO = filter_var($data->MEMBER_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $ACCOUNT_NO = filter_var($data->ACCOUNT_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $AREA = filter_var($data->AREA, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $TOWN = filter_var($data->TOWN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $CONTACT_NO = filter_var($data->CONTACT_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        // $VENUE = filter_var($data->VENUE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW); // OLD: Browser could change this value.
                        // $REG_MODE = filter_var($data->REG_MODE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW); // OLD: Browser could change this value.
                        $VENUE = 'online'; // SECURITY UPDATE: The API decides the trusted value for this online-only endpoint.
                        $REG_MODE = 'online'; // SECURITY UPDATE: Do not trust a client-provided registration mode.
                        $COMMENTS = filter_var($data->COMMENTS, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $LASTNAME = filter_var($data->LASTNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $FIRSTNAME = filter_var($data->FIRSTNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $MIDDLE = filter_var($data->MIDDLE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $SPOUSE = filter_var($data->SPOUSE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $PUROK = filter_var($data->PUROK, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $BRGY = filter_var($data->BRGY, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $BIRTHDATE = filter_var($data->BIRTHDATE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                        $AGE = filter_var($data->AGE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW); // Kept for compatibility; validate/calculated value can be added later.
                        $CIVIL_STATUS = filter_var($data->CIVIL_STATUS, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
    
                        $MEMBER_NO = $this->getMemberID($ACCOUNT_NO,$TOWN,$LASTNAME,$FIRSTNAME,$MIDDLE,$SPOUSE,$BRGY);
        
                        $registration = array(
                            'db_member_unqid' => $MEMBER_UNQID,
                            'db_last' => $LASTNAME,
                            'db_first' => $FIRSTNAME,
                            'db_middle' => substr($MIDDLE, 0, 1),
                            'db_spouse' => $SPOUSE,
                            'db_purok' => $PUROK,
                            'db_brgy' => $BRGY,
                            'db_birthdate' => $BIRTHDATE,
                            'db_age' => $AGE,
                            'db_member_no'=>$MEMBER_NO,
                            'db_account_no'=>$ACCOUNT_NO,
                            'db_area' => $AREA,
                            'db_town' => $TOWN,
                            'db_contact_no' => $CONTACT_NO,
                            'db_venue' => $VENUE,
                            'db_registration_mode' => $REG_MODE, //or online
                            'db_comments' => $COMMENTS,
                            'db_civil_status' => $CIVIL_STATUS,
                            'db_registration_date' => $now
                            
                        );
    
                        $validate_accountno = $this->db->where('db_account_no',$ACCOUNT_NO);
                        $validate_accountno = $this->db->get('tbl_billing_api');
    
                        if ($validate_accountno->num_rows() > 0){
                            //check for duplicate account no already registered
                            $validate_duplicate_accountno = $this->db->where('db_account_no',$ACCOUNT_NO);
                            $validate_duplicate_accountno = $this->db->where('active',true);
                            $validate_duplicate_accountno = $this->db->get('tbl_attendees');
    
                            //if no duplicate account # found then check for duplicate names
                            if($validate_duplicate_accountno->num_rows() == 0 ){
                                $validate_duplicate_reg = $this->db->where('active',true);
                                $validate_duplicate_reg = $this->db->where('db_last',$LASTNAME);
                                $validate_duplicate_reg = $this->db->where('db_first',$FIRSTNAME);
                                $validate_duplicate_reg = $this->db->where('db_middle LIKE ', '%'.substr($MIDDLE, 0, 1).'%');
                                $validate_duplicate_reg = $this->db->where('db_brgy',$BRGY);
                                $validate_duplicate_reg = $this->db->where('db_town',$TOWN);
                                $validate_duplicate_reg = $this->db->get('tbl_attendees');
    
                                if($validate_duplicate_reg->num_rows() == 0 ){
                                    // SECURITY UPDATE: Recheck the cutoff immediately before writing the registration.
                                    $final_now = get_now();
                                    if ($final_now >= $this->cutoff) {
                                        http_response_code(403);
                                        $response = array('message' => 'Registrations are now closed. ' . $this->cutoff_msg, 'status' => 'error');
                                    }else{
                                        // SECURITY UPDATE: Consume the intent and insert registration in one transaction.
                                        // If either action fails, both are rolled back and the visitor can safely try again.
                                        $this->db->trans_begin();

                                        $receipt_expires_at = clone $final_now;
                                        $receipt_expires_at->modify('+5 minutes'); // SECURITY UPDATE: Give the successful browser a short time to open confirmation.

                                        $this->db
                                            ->where('id', $intent_id)
                                            ->where('used_at IS NULL', NULL, FALSE)
                                            ->where('expires_at >=', $final_now->format('Y-m-d H:i:s'))
                                            ->update('tbl_registration_intents', array(
                                                'used_at' => $final_now->format('Y-m-d H:i:s'),
                                                'expires_at' => $receipt_expires_at->format('Y-m-d H:i:s'),
                                                'confirmation_viewed_at' => NULL
                                            ));

                                        if ($this->db->affected_rows() !== 1) { // Another request already used this same intent.
                                            $this->db->trans_rollback();
                                            http_response_code(403);
                                            $response = array(
                                                'message' => 'This consent permission was already used or has expired.',
                                                'status' => 'error'
                                            );
                                        }else{
                                            $inserted = $this->db->insert('tbl_attendees', $registration);

                                            if (!$inserted || $this->db->trans_status() === FALSE) {
                                                $this->db->trans_rollback();
                                                http_response_code(500);
                                                $response = array('message' => 'Registration could not be saved. Please try again.', 'status' => 'error');
                                            }else{
                                                $this->db->trans_commit();
                                                // SECURITY UPDATE: Return the original intent as a receipt only after a successful commit.
                                                $response = array(
                                                    'message' => 'Registration Successful!',
                                                    'status' => 'success!',
                                                    'registration_receipt' => $data->REGISTRATION_INTENT
                                                );
                                            }
                                        }
                                    }
                                }else{
                                    $response = array('message' => 'Already registered.', 'status' => 'error!');
                                }
                            }else{ //there is a duplicate account no already registered 
                                $response = array('message' => 'The Account Number has already been used to register.', 'status' => 'error');
                            }
                        }
                        else{
                            $response = array('message' => 'Enter a valid Account Number, found in your electric bill.', 'status' => 'error');
                        }
                    
                    
                    
                // }else{ // OLD TOKEN closing
                //     $response = array('message' => 'You are not authorized. Please contact the Administrator.', 'status' => 'error');
                // }
                
           }else{
               http_response_code(400); // SECURITY UPDATE: Invalid JSON is a bad request, not an authentication error.
               $response = array('message' => 'Invalid Parameters');
           }
        }else {
            http_response_code(403); // SECURITY UPDATE: Closure is enforced by the API, not only hidden in React.
            $response = array('message' => 'Registrations are now closed. ' . $this->cutoff_msg, 'status' => 'error');
        }
        
       echo json_encode($response,JSON_PRETTY_PRINT);
    }
    
    public function attendance_registration_manual(){
        $data = json_decode(file_get_contents('php://input')); 
       if($data){
            // OLD CODE: TOKEN was read from the request body.
            if ($this->api_auth->require_user(null, false)) // SECURITY UPDATE: Require the logged-in mobile user.
            {
                    
                    $MEMBER_UNQID = filter_var($data->UNQ_ID, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $MEMBER_NO = filter_var($data->MEMBER_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $ACCOUNT_NO = filter_var($data->ACCOUNT_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $AREA = filter_var($data->AREA, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $TOWN = filter_var($data->TOWN, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $CONTACT_NO = filter_var($data->CONTACT_NO, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $VENUE = filter_var($data->VENUE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $REG_MODE = filter_var($data->REG_MODE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $COMMENTS = filter_var($data->COMMENTS, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $LASTNAME = filter_var($data->LASTNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $FIRSTNAME = filter_var($data->FIRSTNAME, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $MIDDLE = filter_var($data->MIDDLE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $SPOUSE = filter_var($data->SPOUSE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $PUROK = filter_var($data->PUROK, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $BRGY = filter_var($data->BRGY, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $BIRTHDATE = filter_var($data->BIRTHDATE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $AGE = filter_var($data->AGE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    $CIVIL_STATUS = filter_var($data->CIVIL_STATUS, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
                    
                    
                    $MEMBER_NO = $this->getMemberID($ACCOUNT_NO,$TOWN,$LASTNAME,$FIRSTNAME,$MIDDLE,$SPOUSE,$BRGY);
                    
                    // Query to get the maximum value of db_id
                    $this->db->select_max('db_id');
                    $max_id_query = $this->db->get('tbl_attendees');
                    $max_id_result = $max_id_query->row_array();
                    $max_id = $max_id_result['db_id'];
        
                    // Increment the maximum value to generate a new unique ID
                    $MEMBER_UNQID = $max_id + 1;
                    
                    if ($MEMBER_NO != ''){
                        $acct_no = $this->db->where('db_member_id',$MEMBER_NO);
                        $acct_no = $this->db->get('tbl_member');
                        if ($acct_no->num_rows() > 0) {
                            foreach ($acct_no->result()as $row) {
                                $ACCOUNT_NO = $row->db_account_no;
                            }
                        }
                    }
                    
                    $registration = array(
                        'db_member_unqid' => $MEMBER_UNQID,
                        'db_last' => $LASTNAME,
                        'db_first' => $FIRSTNAME,
                        'db_middle' => substr($MIDDLE, 0, 1),
                        'db_spouse' => $SPOUSE,
                        'db_purok' => $PUROK,
                        'db_brgy' => $BRGY,
                        'db_birthdate' => $BIRTHDATE,
                        'db_age' => $AGE,
                        'db_member_no'=>$MEMBER_NO,
                        'db_account_no'=>$ACCOUNT_NO,
                        'db_area' => $AREA,
                        'db_town' => $TOWN,
                        'db_contact_no' => $CONTACT_NO,
                        'db_venue' => $VENUE,
                        'db_registration_mode' => $REG_MODE, //or online
                        'db_comments' => $COMMENTS,
                        'db_civil_status' => $CIVIL_STATUS 
                    );

                        //if no duplicate account # found then check for duplicate names
                            $validate_duplicate_reg = $this->db->where('db_last',$LASTNAME);
                            $validate_duplicate_reg = $this->db->where('db_first',$FIRSTNAME);
                            $validate_duplicate_reg = $this->db->where('db_middle LIKE ', '%'.substr($MIDDLE, 0, 1).'%');
                            $validate_duplicate_reg = $this->db->where('db_brgy',$BRGY);
                            $validate_duplicate_reg = $this->db->where('db_town',$TOWN);
                            $validate_duplicate_reg = $this->db->get('tbl_attendees');

                            if($validate_duplicate_reg->num_rows() == 0 ){
                                $this->db->insert('tbl_attendees', $registration);
                                $response = array('message' => 'Registration Successful!', 'status' => 'success!');
                            }else{
                                $response = array('message' => 'Already registered.', 'status' => 'error!');
                            }
                
                            echo json_encode($response,JSON_PRETTY_PRINT);
                
            }else{ //TOKEN closing
                $response =  array('message' => 'You are not authorized. Please contact the Administrator.', 'status' => 'error');
            }
            
       }else{
           $response = array('message' => 'Invalid Parameters');
       }
    }

    public function attendees_per_venue()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: Attendance overview required a login token.
            // SECURITY UPDATE: This endpoint contains guest-safe aggregate counts only.
                $response = array();
                
                // Performing the query to get the count of attendees per venue
                $query = "SELECT tbl_venue.db_venue_desc, COUNT(tbl_attendees.db_id) AS cnt ";
                $query .= "FROM tbl_venue ";
                $query .= "LEFT OUTER JOIN tbl_attendees ON tbl_attendees.db_venue = tbl_venue.db_venue_desc AND tbl_attendees.db_registration_mode != 'manual' AND tbl_attendees.active = 1 ";
                $query .= "GROUP BY tbl_venue.db_venue_desc";
                
                // Executing the query
                $result = $this->db->query($query);

                // Fetching the result
                foreach ($result->result() as $rw) {
                    // Creating response data
                    $info[] = array(
                        'db_venue_desc' => $rw->db_venue_desc,
                        'attendee_count' => $rw->cnt
                    );
                }
                $response = $info;
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function att_per_user()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: Attendance Per User required a login token.
            $VENUE = filter_var($data->VENUE, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);

            // SECURITY UPDATE: This guest report is public, but its venue input is still bound safely below.
                $response = array();
                
                // Performing the query to get the count of attendees per venue
                $query = "SELECT A.username as username,U.fullname as fullname, count(A.username) as cnt ";
                $query .= "FROM `tbl_attendees` A  ";
                $query .= "JOIN tbl_users U ON U.username = A.username ";
                // OLD CODE: $query .= "WHERE db_venue = '". $VENUE ."' AND A.active = 1  ";
                $query .= "WHERE db_venue = ? AND A.active = 1  "; // SECURITY UPDATE: Never join public input into SQL.
                $query .= "GROUP BY A.username";
                
                // Executing the query
                $result = $this->db->query($query, array($VENUE)); // SECURITY UPDATE: Bind the public venue value safely.
                
                //initialize the $info para dili mag-error if $result is empty or walay sulod na data
                $info = array();

                // Fetching the result
                foreach ($result->result() as $rw) {
                    // Creating response data
                    $info[] = array(
                        'username' => $rw->username,
                        'attendee_count' => $rw->cnt,
                        'fullname' => $rw->fullname
                    );
                }
                $response = $info;
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    public function attendees_per_lungsod()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: Attendance overview required a login token.
            // SECURITY UPDATE: This endpoint contains guest-safe aggregate counts only.
                $response = array();
                
                // Performing the query to get the count of attendees per venue
                $query = "SELECT tbl_town.db_town_name, COUNT(tbl_attendees.db_id) AS cnt ";
                $query .= "FROM tbl_town ";
                $query .= "LEFT OUTER JOIN tbl_attendees ON tbl_attendees.db_town = tbl_town.town_code AND tbl_attendees.db_registration_mode != 'manual' AND tbl_attendees.active = 1 ";
                $query .= "GROUP BY tbl_town.db_town_name";
                
                // Executing the query
                $result = $this->db->query($query);

                // Fetching the result
                foreach ($result->result() as $rw) {
                    // Creating response data
                    $info[] = array(
                        'db_town_name' => $rw->db_town_name,
                        'attendee_count' => $rw->cnt
                    );
                }
                $response = $info;
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function online_attendees()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: Attendance overview required a login token.
            // SECURITY UPDATE: This endpoint contains a guest-safe aggregate count only.
                $response = array();
                
                // Performing the query to get the count of attendees per venue
                $query = "SELECT COUNT(db_id) as cnt FROM `tbl_attendees` WHERE db_venue = 'online' and active = 1;";
                
                // Executing the query
                $result = $this->db->query($query);

                // Fetching the result
                foreach ($result->result() as $rw) {
                    // Creating response data
                    $info[] = array(
                        'attendee_count' => $rw->cnt
                    );
                }
                $response = $info;
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function total_attendees()
    {
        $data = json_decode(file_get_contents('php://input'));
        if ($data) {
            // OLD CODE: Attendance overview required a login token.
            // SECURITY UPDATE: This endpoint contains a guest-safe aggregate count only.
                $response = array();
                
                // Performing the query to get the count of attendees per venue
                $query = "SELECT COUNT(db_id) as cnt FROM `tbl_attendees` WHERE db_registration_mode != 'manual' and active = 1";
                
                // Executing the query
                $result = $this->db->query($query);

                // Fetching the result
                foreach ($result->result() as $rw) {
                    // Creating response data
                    $info[] = array(
                        'attendee_count' => $rw->cnt
                    );
                }
                $response = $info;
        } else {
            $response = array('message' => 'Invalid Parameters');
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
}
