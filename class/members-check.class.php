<?php

    class MEMBERS_CHECK{
		
        public static function checkMeberByNumber($memberId)			
        {
			
			$Id = str_pad($memberId, 6, "0", STR_PAD_LEFT); 
			
			global $wpdb;
            $query = '';
            //SELECT * FROM `kpo0h_mitglieder` WHERE mitgliednummer like '000100'
            $query .= 'SELECT * FROM  ' . $wpdb->prefix . 'mitglieder as member WHERE member.mitglNr like "'. $Id .'"'; 
            $member = $wpdb->query($query);
            return $member ;			
        }
				
    }