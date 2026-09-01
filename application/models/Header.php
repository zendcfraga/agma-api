<?php
    class Header extends CI_Model{

        public function ApiHeader(){
            header("Content-type: application/json; charset=UTF-8");
            header("Access-Control-Allow-Origin: *"); 
            header("Access-Control-Allow-Methods: POST"); 
            header("Access-Control-Max-Age: 86400");
            header("Access-Control-Allow-Headers:  Content-Type, Access-Control-Allow-Methods, Authorization, X-Request-With");
        }
    }