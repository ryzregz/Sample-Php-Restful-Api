<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product extends CI_Controller {

	public function addProduct(){
    $this->load->model('Product_model');
  header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
		$client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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


                $this->logAccess("Client IP: ".$ip_address.", Valid Request Received.");
                $pcode = trim($data->product_code," ");
                $pname = trim($data->product_name," ");
                $c_code = trim($data->product_category," ");
                $l_id = trim($data->location_id," ");
                $user_id = trim($data->user_id," ");
                $count_type = trim($data->count_type," ");
                $quantity = trim($data->quantity," ");
                $stocksheet_id = trim($data->stocksheet_id," ");

          $sap_data =$this->Product_model->get_sap_data($stocksheet_id);
          $amount_sap = $sap_data->closing_stock;
          $unit_price = $sap_data->unit_price;
          $amount_variance = $amount_sap - intval($quantity);
          $price_variance = $amount_variance * $unit_price;

                //$this->logAccess("here: ".$pcode);

        $inserted = $this->Product_model->insert_product($pcode,$pname, $c_code, $l_id,$user_id,$count_type,$quantity,$amount_sap,$unit_price,
        $amount_variance, $price_variance,$stocksheet_id);
        $this->logAccess("after insertion: ".$stocksheet_id);
            $this->logAccess("inserted: ".$inserted);
         if(!$inserted){    
            $response = array("code" => 1010, "message"=>"Error occured while recording the transaction.");
            $this->logAccess(json_encode($response));
                        echo json_encode($response);
            exit;
        }
        else{
             $data->id = $inserted;
                        
            $response = array("code" => 1001, "message" => "Product Data Added Succesfuly.", "data" => $data);
            $this->logAccess(json_encode($response));
                        echo json_encode($response);
            exit;
        }
            


	}

    public function editProduct(){
        $this->load->model('Product_model');
       header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
        $client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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


                $this->logAccess("Client IP: ".$ip_address.", Valid Request Received.");
                $product_id = trim($data->entry_id," ");
                $user_id = trim($data->user_id," ");
                $count_type = trim($data->count_type," ");
                $quantity = trim($data->quantity," ");
                $comments = trim($data->comments," ");

                //$this->logAccess("here: ".$pcode);

        $updated = $this->Product_model->edit_product($product_id,$user_id,$count_type,$quantity,$comments);
            $this->logAccess("inserted: ".$updated);
         if(!$updated){    
            $response = array("code" => 1010, "message"=>"Error occured while updating the transaction.");
            $this->logAccess(json_encode($response));
                        echo json_encode($response);
            exit;
        }
        else{
             $data->id = $updated;
                        
            $response = array("code" => 1001, "message" => "Entry Data Updated Succesfuly.", "data" => $data);
            $this->logAccess(json_encode($response));
                        echo json_encode($response);
            exit;
        }
            


    }

	public function locations(){
    $this->load->model('Product_model');


		 header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
		$client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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
                
		//$this->logAccess('Request: '.$json);		
		$data = json_decode($json);   
		
		if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
		}
        $site_code = $data->plant_code;

		$location_details = $this->Product_model->get_locations($site_code);
		if(! $location_details){
			$this->logAccess("No Locations Found");
                    $response = array("code" => 1010, "message"=>"No Storage Locations Found!.");
                    echo json_encode($response);
                    exit;
		}

		$response = array("code" => 1001, "message"=>"Success", 
                       "data" => $location_details);
                    echo json_encode($response);
                    exit;




	}

    public function initiate(){
    $this->load->model('Product_model');


         header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
        $client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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
                
        //$this->logAccess('Request: '.$json);      
        $data = json_decode($json);   
        
        if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
        }
        $site_code = $data->plant_code;
        $user_id=intval($data->user_id);

        $location_details = $this->Product_model->get_locations_entry($site_code,$user_id);
        if(! $location_details){
            $this->logAccess("No Locations Found");
                    $response = array("code" => 1010, "message"=>"No Storage Locations Found!.");
                    echo json_encode($response);
                    exit;
        }

        $response = array("code" => 1001, "message"=>"Success", 
                       "data" => $location_details);
                    echo json_encode($response);
                    exit;




    }


	public function categories(){
     $this->load->model('Product_model');

		 header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
		$client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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
                
		//$this->logAccess('Request: '.$json);		
		$data = json_decode($json);   
		
		if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
		}

		$category_details = $this->Product_model->get_categories();
		if(! $category_details){
			$this->logAccess("Could not retrieve categories");
                    $response = array("code" => 1010, "message"=>"Internal error has occured.");
                    echo json_encode($response);
                    exit;
		}

		$response = array("code" => 1001, "message"=>"Success", 
                       "data" => $category_details);
                    echo json_encode($response);
                    exit;


	}
  public function stockSheet(){
     $this->load->model('Product_model');

     header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
    $client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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
                
    //$this->logAccess('Request: '.$json);    
    $data = json_decode($json);   
    
    if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
    }

    $location_id=$data->location_id;
    //$category=$data->category;

    // $category_id = $this->Product_model->get_category_id($category);

    // if(! $category_id){
    //    $this->logAccess("Could not retrieve the category");
    //                 $response = array("code" => 1010, "message"=>"Category not found.");
    //                 echo json_encode($response);
    //                 exit;
    // }
     // $response = array("code" => 1001, "message"=>"Success", 
     //                   "category_id" => $category_id);
     //                echo json_encode($response);
     //                exit;


     $stock = $this->Product_model->get_stock_sheet($location_id);

     if(! $stock){
      $this->logAccess("The location selected has no stock sheet yet");
                    $response = array("code" => 1010, "message"=>"The location selected has no stock sheet yet.");
                    echo json_encode($response);
                    exit;
    }

    $response = array("code" => 1001, "message"=>"Success", 
                       "data" => $stock);
                    echo json_encode($response);
                    exit;


  }

  public function userEntries(){

     $this->load->model('Product_model');

     header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
    $client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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

    $user_id=intval($data->user_id);
    $status=intval($data->status);
    $stock_entry = $this->Product_model->get_user_stock_entry($user_id,$status);
    if(! $stock_entry){
        $this->logAccess("Could not retrieve user entries");
      $this->logAccess("Could not retrieve user entries");
                    $response = array("code" => 1010, "message"=>"No Entries for this user found!.");
                    echo json_encode($response);
                    exit;
    }
    $response = array("code" => 1001, "message"=>"Success", 
                       "data" => $stock_entry);
                    echo json_encode($response);
                    exit;


  }

  public function getMonthlyEntryReport(){
      
     $this->load->model('Product_model');

     header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
    $client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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
    $user_id=intval($data->user_id);   
    
    if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
    }

     $report = $this->Product_model->get_entry_report_data($user_id);    
                
        $response = array("code" => 1001, "message"=>"Successful", "data" => $report);
                echo json_encode($response);



  }

   public function getMonthlyEntryReportByLocation(){
      
     $this->load->model('Product_model');

     header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
    $client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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
    $user_id=intval($data->user_id); 
    $location_id=intval($data->location_id);    
    
    if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
    }

     $report = $this->Product_model->get_location_report_data($user_id,$location_id);    
                
        $response = array("code" => 1001, "message"=>"Successful", "data" => $report);
                echo json_encode($response);



  }

  public function getMonthlyVarianceReport(){
      
     $this->load->model('Product_model');

     header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
    $client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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
    $user_id=intval($data->user_id);   
    
    if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
    }

     $report = $this->Product_model->get_variance_report_data($user_id);    
                
        $response = array("code" => 1001, "message"=>"Successful", "data" => $report);
                echo json_encode($response);



  }


     public function getMonthlyVarianceReportByLocation(){
      
     $this->load->model('Product_model');

     header('Content-type: application/json');

  $ip_address = trim($this->input->ip_address());
    $client_platform = $this->agent->platform();

     $reqHeaders = $this->input->request_headers();
                if((! array_key_exists('app-id', $reqHeaders))|| (! array_key_exists('version-name', $reqHeaders))){
                    $this->logAccess("Client IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
                }
                $app_id = $reqHeaders['app-id'];
                $app_version_name = $reqHeaders['version-name'];
                
                $this->logAccess("App ID: ".$app_id.", App Version: ".$app_version_name);
                
                if( ! is_version_current($app_id,$app_version_name) ){
                    $response = array("code" => 1030, "message"=>"A new version of Stock-Take App is now available. "
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
    $user_id=intval($data->user_id); 
    $location_id=intval($data->location_id);    
    
    if (json_last_error() !== 0) {
                    $this->logAccess("User IP: ".$ip_address.", Bad Request Received.");
                    $this->output->set_status_header('403');
                    exit;
    }

     $report = $this->Product_model->get_location_variance_report_data($user_id,$location_id);    
                
        $response = array("code" => 1001, "message"=>"Successful", "data" => $report);
                echo json_encode($response);



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


	 public function logAccess_refund($msg)
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
