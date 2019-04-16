<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {


    public function register() //Unfinished. Check NB towards the end of this method
    {       //echo $this->config->base_url(); exit;
        header('Content-type: application/json');
        
        $this->load->model('Auth_model');
        $this->logAccess("Login Called");
        
        $ip_address = trim($this->input->ip_address());
        $user_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders)) ||  (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                //$access_key = $reqHeaders['access-key'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Unga Stock-Take App is now available. "
                        . "Kindly update your current application in order for you to enjoy the new features.");
            $this->logAccess(json_encode($response));
                    echo json_encode($response);
                    exit;
                }
                
                 $origin = "";
                 $user_agent = "";
                 $client_agent = "";

                if (array_key_exists('Origin', $reqHeaders)) {
                    $origin = $reqHeaders['Origin'];
                }
                if (array_key_exists('User-Agent', $reqHeaders)) {
                    $client_agent = $reqHeaders['User-Agent'];
                }
                
                if ($this->input->post()) 
                {
                    $json = $this->input->get_post('request');
                }
                else{
                    $json = file_get_contents('php://input');
                }
                
        $this->logAccess('Request: '.$json);
        
        $data = json_decode($json);
        
        if (json_last_error() != 0) {
            $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                    }
                
        $f_name = trim($data->fname," "); 
        $l_name = trim($data->lname," ");
        $email = trim($data->email," ");
        $msisdn = trim($data->msisdn," ");
        $nationalid = trim($data->nationalid," ");
        $regno = trim($data->regno," ");
        $password = trim($data->password," ");
        $address_line1 = trim($data->address_line1," ");
        $speciaty = trim($data->speciaty," ");
        $hospital = trim($data->hospital," ");
        $zip_code = trim($data->zip_code," ");
        $city_name = trim($data->city_name," ");     
                
                $msisdn_exists = $this->Auth_model->check_msisdn($msisdn);
                if($msisdn_exists){ 
            $response = array("code" => 1010, "message"=>$msisdn." had already been used for registration. Please contact customer care.");
            echo json_encode($response);
            exit;
        } 
                
                $email_exists = $this->Auth_model->check_email($email);
                if($email_exists){  
            $response = array("code" => 1010, "message"=>$email." had already been used for registration. Please contact customer care.");
            echo json_encode($response);
            exit;
        } 
               
                
    
        $inserted = $this->Auth_model->register($f_name,
                        $l_name,
                        $email,$msisdn,$nationalid,$regno,$password,$address_line1,$speciaty, $hospital,
                        $zip_code,$city_name,$app_id,$app_version_name);
        $this->logAccess("inserted: ".$inserted);
                if(! $inserted){    
            $response = array("code" => 1010, "message"=>"Registration Failed.");
            $this->logAccess(json_encode($response));
                        echo json_encode($response);
            exit;
        }
        else{
                        //NB Should send email to verify the email address and also to send SMS containing OTP in the text message to verify msisdn
                        $data->userid = $inserted;
                        
            $response = array("code" => 1001, "message" => "Registration Successful.", "data" => $data);
            //$this->logAccess(json_encode($response));
                        echo json_encode($response);
            exit;
        }
    }

public function login()
	{		//echo $this->config->base_url(); exit;
		header('Content-type: application/json');
		
		$this->load->model('Auth_model');
		$this->logAccess("Login Called");
		
		$ip_address = trim($this->input->ip_address());
		$user_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders)) ||  (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                //$access_key = $reqHeaders['access-key'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Unga Stock-Take App is now available. "
                        . "Kindly update your current application in order for you to enjoy the new features.");
		    $this->logAccess(json_encode($response));
                    echo json_encode($response);
                    exit;
                }
                
                 $origin = "";
                 $user_agent = "";
                 $client_agent = "";

                if (array_key_exists('Origin', $reqHeaders)) {
                    $origin = $reqHeaders['Origin'];
                }
                if (array_key_exists('User-Agent', $reqHeaders)) {
                    $client_agent = $reqHeaders['User-Agent'];
                }
                
                if ($this->input->post()) 
                {
                    $json = $this->input->get_post('request');
                }
                else{
                    $json = file_get_contents('php://input');
                }
                
		$this->logAccess('Request: '.$json);		
		$data = json_decode($json);   
		
		if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
		}
		
		$username = $data->username;
		$password = $data->password;
					
		$user_login = $this->Auth_model->login($username,$password,$ip_address,$user_platform);
		if(! $user_login){	
			$response = array("code" => 1010, "message"=>$username.": Login failure. Please use your valid credentials.");
			echo json_encode($response);
			exit;
		} 
                
               
                
                $user_id = $user_login->id;
                                                   
                $user_details = $this->Auth_model->get_user_details($user_id);
                if(! $user_details){	
			$response = array("code" => 1010, "message"=>"Could not retrieve your details.");
			echo json_encode($response);
			exit;
		} 
                
                

                 $token = $user_id.'-'.md5($user_id.date('YmdHis').mt_rand(0,1000));
                 $expiry = 600;
                 $now = new DateTime ( NULL, new DateTimeZone('UTC'));
                 $created_date = $now->format('Y-m-d H:i:s');

                 $insert_data = array(
                        'user_id' => $user_id,
                        'token' => $token,
                        'app_id' => $app_id,
                        'app_version_name' => $app_version_name,
                        'client_ip_address' => ip2long($ip_address),
                        'client_platform' => $user_platform,
                        'client_agent' => $client_agent,
                        'origin' => $origin,
                        'created_at' => $created_date,
                        'modified_at' => $created_date
                    );

                  if(! $this->Auth_model->insert_token($insert_data)){
                        $this->logAccess("Client IP: ".$reqUserIp.", Could not add your session. Please retry!");
                        $this->output->set_status_header('500');
                        exit;
                    }

                    $response = array("code" => 1001, "message"=>"Success", "token"=>$token, 
                        "expiry"=>$expiry,"data" => $user_details);
                    echo json_encode($response);
                    exit;

	}

          public function logout()
    {       //echo $this->config->base_url(); exit;
            header('Content-type: application/json');

            $this->load->model('Auth_model');
            $this->logAccess("logout Called");
            $ip_address = trim($this->input->ip_address());
            $user_platform = $this->agent->platform();

            $reqHeaders = $this->input->request_headers();
            if((! array_key_exists('app-id', $reqHeaders)) || (! array_key_exists('version-name', $reqHeaders))){
                $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                $this->output->set_status_header('403');
                exit;
            }
            $app_id = $reqHeaders['app-id'];
            //$access_key = $reqHeaders['access-key'];
            $app_version_name = $reqHeaders['version-name'];

           $token = $reqHeaders['token'];

            if( ! is_version_current($app_id,$app_version_name) ){
                $response = array("code" => 1030, "message"=>"A new version of Unga Stock-Take App is now available. "
                    . "Kindly update your current application in order for you to enjoy the new features.");
                $this->logAccess(json_encode($response));
                echo json_encode($response);
                exit;
            }

            $this->logAccess("App ID: ".$app_id.", Version Name: ".$app_version_name.", Token: ".$token);


            if ($this->input->post()) 
            {
                $json = $this->input->get_post('request');
            }
            else{
                $json = file_get_contents('php://input');
            }

            $this->logAccess('Request: '.$json);        
            $data = json_decode($json); 

            if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                $this->output->set_status_header('403');
                exit;
                }

            $userid = $data->user_id;
            $tok = $data->token; 

           $logout = $this->Auth_model->logout($tok);
            if(! $logout){  
                $response = array("code" => 1010, "message"=>"Could not kill your session. Please retry.");
                echo json_encode($response);
                exit;
            } 
            else{ 
                $response = array("code" => 1001, "message"=>"Logout successful.");
                echo json_encode($response);
                exit;
            }                
    } 

        public function forgotPassword()
    {       //echo $this->config->base_url(); exit;
        header('Content-type: application/json');
        
        $this->load->model('Auth_model');
        $this->logAccess("Forgot Password Called");
        
        $ip_address = trim($this->input->ip_address());
        $client_platform = $this->agent->platform();
                
                $reqHeaders = $this->input->request_headers();
            if((! array_key_exists('app-id', $reqHeaders)) || (! array_key_exists('version-name', $reqHeaders))){
                $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                $this->output->set_status_header('403');
                exit;
            }
                $app_id = $reqHeaders['app-id'];
                //$access_key = $reqHeaders['access-key'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Unga Stock-Take App is now available. "
                        . "Kindly update your current application in order for you to enjoy the new features.");
            $this->logAccess(json_encode($response));
                    echo json_encode($response);
                    exit;
                }
                 $origin = "";
                 $user_agent = "";

                if (array_key_exists('Origin', $reqHeaders)) {
                    $origin = $reqHeaders['Origin'];
                }
                if (array_key_exists('User-Agent', $reqHeaders)) {
                    $client_agent = $reqHeaders['User-Agent'];
                }
                
                
                if ($this->input->post()) 
                {
                    $json = $this->input->get_post('request');
                }
                else{
                    $json = file_get_contents('php://input');
                }
                
        $this->logAccess('Request: '.$json);      
        $data = json_decode($json);   
        
        if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
        }
        
        $email = $data->email;
                                    
        $client_id = $this->Auth_model->get_client_id_by_email($email);
        if(! $client_id){   
            $response = array("code" => 1010, "message"=>"The email you provided does not match any record.");
            echo json_encode($response);
            exit;
        }
                
                $token = md5($client_id.date('YmdHis').mt_rand(0,1000));
                $expiry = 600;
                
                $code = mt_rand(100000,999999); 
                $now = new DateTime ( NULL, new DateTimeZone('UTC'));
                $created_date = $now->format('Y-m-d H:i:s');
                
                $deactivated = $this->Auth_model->deactivate_verification_code($client_id); 


                $insert_verification = array(
                       'user_id' => $client_id,
                       'token' => $token,
                       'app_id' => $app_id,
                       'client_ip_address' => ip2long($ip_address),
                       'client_platform' => $client_platform,
                       'client_agent' => $client_agent,
                       'origin' => $origin,
                       'created_at' => $created_date
                   );
                              

                
                $email_subject = "Verification Code";
                $email_body = "Your verification code is ".$code;
                $sender_name = "Verification";
                $sender_email = "morryzregz100@gmail.com";
                $recipient_email = $email;
                //$this->send_email($email_subject,$email_body,$sender_name,$recipient_email);

                 if(! $this->Auth_model->insert_verification_code($code,$client_id,$insert_verification)){
                       $this->logAccess("Client IP: ".$ip_address.", Internal Error occured.");
                       $this->output->set_status_header('500');
                       $response = array("code" => 1010, "message"=>"Internal Error occured.");
                        echo json_encode($response);
                       exit;
                   }

                   $response = array("code" => 1001, "message"=>"Your Password Verification Code has been sent to your email.", "token"=>$token, 
                       "expiry"=>$expiry);
                   echo json_encode($response);
                   exit;
        
    }
        
        public function updatePassword()
    {       //echo $this->config->base_url(); exit;
            header('Content-type: application/json');
        
            $this->load->model('Auth_model');
            $this->logAccess("Update Password Called");
        
            $ip_address = trim($this->input->ip_address());
            $user_platform = $this->agent->platform();

            if((! array_key_exists('app-id', $reqHeaders)) || (! array_key_exists('version-name', $reqHeaders))){
                $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                $this->output->set_status_header('403');
                exit;
            }
            $app_id = $reqHeaders['app-id'];
           // $access_key = $reqHeaders['access-key'];
            $token = $reqHeaders['token'];
            $app_version_name = $reqHeaders['version-name'];
                
            $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);

            if( ! is_version_current($app_id,$app_version_name) ){
                $response = array("code" => 1030, "message"=>"A new version of Unga Stock-Take App is now available. "
                    . "Kindly update your current application in order for you to enjoy the new features.");
                $this->logAccess(json_encode($response));
                echo json_encode($response);
                exit;
            }
            $this->logAccess("App ID: ".$app_id.", Token: ".$token);
  
            $result = false;
            $message = "";

            if ($this->input->post()) 
            {
                $json = $this->input->get_post('request');
            }
            else{
                $json = file_get_contents('php://input');
            }
            $this->logAccess('Request: '.$json);

            $request = json_decode($json);

            if (json_last_error() !== 0) {
                $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                $this->output->set_status_header('403');
                exit;
            }

            //$userid = $request->userid;
            $client_id = $this->Auth_model->get_client_id_from_verify($token);
            if (! $client_id) {
                $response = array("code" => 1010, "message"=>"Internal Error occured.");
                echo json_encode($response);
                exit;
            }
            
            $this->logAccess("Client ID: ".$client_id);
                
                
               if ($this->input->post()) 
                {
                    $json = $this->input->get_post('request');
                }
                else{
                    $json = file_get_contents('php://input');
                }

                $this->logAccess('Request: '.$json);      
        $data = json_decode($json);   
        
        if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
        }
        
        $verification_code = $data->verification_code;   
                $new_password = $data->new_password;          
        $confirm_new_password = $data->confirm_new_password;    
                
                if($confirm_new_password != $new_password){
                    $this->logAccess("Client IP: ".$ip_address.", Passwords do not match.");
                    $response = array("code" => 1010, "message"=>"Passwords do not match.");
                    echo json_encode($response);
                    exit;
                }
                
                $this->logAccess("Client IP: ".$ip_address.", Verification Code: ".$verification_code);
                
                $this->logAccess("Client ID: ".$client_id.", Verification Code: ".$verification_code);
                
                $verification_id = $this->Auth_model->get_verification_id($client_id,$verification_code);
                
                if(! $verification_id){
                    $this->logAccess("Client IP: ".$ip_address.", Invalid verification code.");
                    $response = array("code" => 1010, "message"=>"Invalid verification code.");
                    echo json_encode($response);
                    exit;
                }
               
                $is_new_password = $this->Auth_model->is_new_password($client_id,$new_password);
                if(! $is_new_password){
                    $this->logAccess("Client IP: ".$ip_address.", The new password matches the existing old password.");
                    $response = array("code" => 1010, "message"=>"The new password matches the existing old password.");
                    echo json_encode($response);
                    exit;
                }
                
                $updated = $this->Auth_model->update_password($client_id,$new_password);
                
                if(! $updated){
                    $this->logAccess("Client IP: ".$ip_address.", Error occured.");
                    $response = array("code" => 1010, "message"=>"Error occured.");
                    echo json_encode($response);
                    exit;
                }
                
               /* $email_subject = "Password Update";
                $email_body = "Your new password has been successfully been updated.";
                $sender_name = "Password";
                $sender_email = "customercare@sawa-pay.com";
                $recipient_email = $email;
                
                $insert_email = ['message' => $email_body,
                       'subject' => $email_subject,
                       'sender_name' => $sender_name,
                       'sender_email' => $sender_email,
                       'recipient_email' => $recipient_email,
                       'created_at' => $created_date
                       ];

                 if(! $this->Auth_model->insert_verification_code($insert_verification,$insert_email)){
                       $this->logAccess("Client IP: ".$ip_address.", Internal Error occured.");
                       $this->output->set_status_header('500');
                       $response = array("code" => 1010, "message"=>"Internal Error occured.");
                        echo json_encode($response);
                       exit;
                   } */
                   
                   $deactivated = $this->Auth_model->deactivate_verification_code($client_id);   

                   $response = array("code" => 1001, "message"=>"Password updated successfully.");
                   echo json_encode($response);
                   exit;
        
    } 

public function send_email($email_subject,$email_body,$sender_name,$recipient_email){
 $this->load->library('email');
$config = array();
$config['protocol'] = 'smtp';
$config['smtp_host'] = '127.0.0.0';
$config['smtp_user'] = 'morryzregz100@gmail.com';
$config['smtp_pass'] = '0723686428';
$config['smtp_port'] = 25;
$this->email->initialize($config);
 
$this->email->from('your@example.com', $sender_name);
$this->email->to($recipient_email);
$this->email->cc('morris.murega100@gmail.com');
$this->email->bcc('them@their-example.com');
$this->email->subject($email_subject);
$this->email->message($email_body);
$this->email->send();

echo $this->email->print_debugger();


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

