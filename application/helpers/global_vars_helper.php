<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    
    if (!function_exists('get_cutoff')) {
        function get_cutoff() {
            return new DateTime("2026-09-13 12:00:00", new DateTimeZone('Asia/Manila'));
        }
    }
    
    if (!function_exists('get_now')) {
        function get_now() {
            return new DateTime("now", new DateTimeZone('Asia/Manila'));
        }
    }
    
    if (!function_exists('get_message')) {
        function get_message() {
            return "The cut-off time was 12 noon.";
        }
    }
