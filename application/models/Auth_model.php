<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth_model extends CI_Model {
	
	 public function __construct(array $data = null){
			
                   parent::__construct($data);
	 }

   public function register($f_name,
                        $l_name,
                        $email,$msisdn,$nationalid,$regno,$pass,$address_line1,$speciaty, $hospital,
                        $zip_code,$city_name.$app_id,$app_version)
  { 
                $this->logAccess("inside model");
               
                $now = new DateTime ( NULL, new DateTimeZone('UTC'));
    //$now = new DateTime ();
    $password = md5($pass);
                
                $this->db->trans_start();
    $this->logAccess("start db trx");
                        
    $profile = array('firstname' => $f_name, 
                    'lastname' => $l_name, 
                    'email' => $email, 
                    'mobile_number'=>$msisdn,
                    'national_id' => $nationalid,
                    'address' => $addressline1,
                    'zip_code' => $zipcode, 
                    'city' => $city_name,
                    'reg_no' => $regno,
                    'specialization' => $speciaty,
                    'hospital' => $hospital,
                    'user_type' => 1,
                    'app_id' => $app_id,
                    'app_version' => $app_version,
                    'password' => $password
                    );
            $id = $this->db->insert('users',$profile);
                
                $this->db->trans_complete();
             
                if ($this->db->trans_status() === FALSE)
                {
                    return false;
                }  
    
    return $id;
  }


public function login($username,$pass,$ip_address,$user_platform)
	{	
	$now = new DateTime ( NULL, new DateTimeZone('UTC'));
		$ip_addr = ip2long($ip_address); //its opposite is long2ip. MySQL functions are INET_ATON and INET_NTOA
		//$now = new DateTime (); 
		
		$this->db->select('id,password,user_status');
		$this->db->where('email', $username); 
		$query = $this->db->get('tbl_users');


		if (! $query->num_rows() > 0 )
		{
			return false;
		}
		$row = $query->row();

		$hashed_password = $row->password;
		$p = password_verify($pass, $hashed_password);

		if(!$p){
            return false;
		}

		
		$id = 0;
		
		$data = array('last_login' =>$now->format('Y-m-d H:i:s'), 'last_ip_address' => $ip_addr, 'last_client_platform' => $user_platform);
		$this->db->where('id', $row->id);
		$this->db->update('tbl_users', $data); 
		
		if(! $this->db->affected_rows()){
			return false;
		}              
                
        $data = array('active' => 0);
		$this->db->where('user_id', $row->id);
		$this->db->update('auth_token', $data); 
                				
		return $row;
	}

	 public function insert_token($data)
	{	
            if(! $this->db->insert('auth_token',$data)){
                    logError($this->db->_error_number()." ".$this->db->_error_message()); 
                    return false;
            }
                				
	    return true;
	}


	 public function get_user_details($id){
            $details = array();
            $now = date('Y-m-d H:i:s');
            $exploded_date = explode("-",$now);
            $last_string = $exploded_date[2];
            $last_string_exploded = explode(" ",$last_string);
            $no_of_days =  $last_string_exploded[0];
            $this->logAccess("No of days to subtract ".$no_of_days);
            $start_date = date("Y-m-d H:i:s", strtotime("-".$no_of_days." day"));

            $this->logAccess("Current Date ".$now);
            $this->logAccess("Last Date ".$start_date);
            
            $this->db->select('id,msisdn,email');
            $this->db->where('id', $id);  
            $query = $this->db->get('tbl_users');
            if (! $query->num_rows() > 0 )
            {
                    return false;
            }
            $details['auth'] = $query->row();
            
            $this->db->select('f_name,l_name,'
                    . 'town,county,plant_code');
            $this->db->where('id', $id);  
            $query2 = $this->db->get('tbl_users');
            if (! $query2->num_rows() > 0 )
            {
                    return false;
            }
            $details['profile'] = $query2->row();

            $this->db->select('id');
            $this->db->where('user_id', $id);
            $this->db->where('status', 0);
            $this->db->where('created_at >',$start_date);
            $this->db->where('created_at <=',$now);    
            $query3 = $this->db->get('tbl_product_transactions');
            $details['pending'] = $query3->num_rows();

            $this->db->select('id');
            $this->db->where('user_id', $id);
            $this->db->where('status', 1);
            $this->db->where('created_at >',$start_date);
            $this->db->where('created_at <=',$now);    
            $query3 = $this->db->get('tbl_product_transactions');
            $details['approved'] = $query3->num_rows();

            $this->db->select('id');
            $this->db->where('user_id', $id);
            $this->db->where('status', 2);
            $this->db->where('created_at >',$start_date);
            $this->db->where('created_at <=',$now);    
            $query3 = $this->db->get('tbl_product_transactions');
            $details['rejected'] = $query3->num_rows();
         
           

            return $details;
            
        }

        public function get_client_id_by_email($email){
            //$details = array();
            
            $this->db->select('id');
            $this->db->where('email', $email);  
            $this->db->where('user_status', 1);
            $this->db->where('is_deleted', 0);
            $query = $this->db->get('tbl_users');
            
            if (! $query->num_rows() > 0 )
            {
                return false;
            }
            
            $row = $query->row();
            return $row->id;            
        }

          public function deactivate_verification_code($id)
  {       
            $data = array('verification_code_active' => 0);
            $this->db->where('id', $id);
            $this->db->update('tbl_users', $data); 
                        
      return $this->db->affected_rows();
  }
        
        public function insert_verification_code($code,$id,$data1)
  { 

             $this->db->trans_start();
              $now = new DateTime ( NULL, new DateTimeZone('UTC'));
                $created_date = $now->format('Y-m-d H:i:s');
              $data = array('verification_code' => $code,'verification_code_active' => 1,'verification_code_date' => $created_date);
            $this->db->where('id', $id);
             $this->db->update('tbl_users', $data); 

             $this->insert_token($data1);
             
             $this->db->trans_complete();
             
             if ($this->db->trans_status() === FALSE)
            {
                return false;
            }          
                        
      return true;
  }

  public function get_client_id_from_verify($token){
            //$details = array();
            
            $this->db->select('user_id'); 
            $this->db->where('token', $token);  
            $this->db->where('active', 1);
            $query = $this->db->get('auth_token');
            
            if (! $query->num_rows() > 0 )
            {
                return false;
            }
            
            $row = $query->row();
            return $row->client_id;            
        }
public function is_new_password($clientID,$newPassword){

    $this->db->select('password,user_status');
    $this->db->where('user_id', $clientID); 
    $query = $this->db->get('tbl_users');


    if (! $query->num_rows() > 0 )
    {
      return false;
    }
    $row = $query->row();

    $hashed_password = $row->password;
    $p = password_verify($newPassword, $hashed_password);

    if($p){
            return false;
    }
     return true;

        }

        public function get_verification_id($clientID,$verificationCode){
            //$details = array();
            
            $this->db->select('id');
            $this->db->where('user_id', $clientID);  
            $this->db->where('verification_code', $verificationCode);  
            $this->db->where('verification_code_active', 1);
            $query = $this->db->get('tbl_users');
            
            if (! $query->num_rows() > 0 )
            {
                return false;
            }
            
            $row = $query->row();
            return $row->id;            
        }

            public function update_password($clientID,$newPassword)
  {       
            //$pass = md5($newPassword);
            $pass = password_hash($newPassword,PASSWORD_DEFAULT);
            $data = array('password' => $pass);
            $this->db->where('id', $clientID);
            $this->db->update('tbl_users', $data); 
                        
      return $this->db->affected_rows();
  }


    public function logout($tok){
        $data = array('active' => 0);
	    $this->db->where('token', $tok);
	    $this->db->update('auth_token', $data); 
            return $this->db->affected_rows();
        }

 public function logAccess($msg)
   { 
       $file_name = APPPATH . 'logs/access.log';
       // open file
       //$fd = fopen($filename, "a");
       $fd = fopen($file_name, 'a');
       // append date/time to message
       $str = "[" . date("Y/m/d H:i:s", time()) . "] " . $msg; 
       // write string
       fwrite($fd, $str . "\n");
       // close file
       fclose($fd);
   }
   
   public function logError($msg)
   { 
       $file_name = APPPATH . 'logs/error.log';
       // open file
       //$fd = fopen($filename, "a");
       $fd = fopen($file_name, 'a');
       // append date/time to message
       $str = "[" . date("Y/m/d H:i:s", time()) . "] " . $msg; 
       // write string
       fwrite($fd, $str . "\n");
       // close file
       fclose($fd);
   }


	}
