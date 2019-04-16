<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model {
	
	 public function __construct(array $data = null){
			
                   parent::__construct($data);
	 }

	 public function get_locations($site_code){
            $this->db->select('*'); 
            //$this->db->where('is_deleted', 0);
            $this->db->where('plant_code', $site_code);  
            $query = $this->db->get('tbl_storage_location');
            if (! $query->num_rows() > 0 )
            {
                    return false;
            }
            
            return $query->result();
        }

        public function get_locations_entry($site_code,$user_id){
           $data = array();
            $this->db->select('*'); 
            //$this->db->where('is_deleted', 0);
            $this->db->where('plant_code', $site_code);  
            $query = $this->db->get('tbl_storage_location');
            if (! $query->num_rows() > 0 )
            {
                    return false;
            }
            $this->db->where('plant_code', $site_code);  
            $query = $this->db->get('tbl_storage_location');
            if (! $query->num_rows() > 0 )
            {
                    return false;
            }
            $data['locations'] = $query->result();

            $this->db->select('count(*) AS pending'); 
            $this->db->where('user_id', $user_id);
            $this->db->where('status', "0");  
            $this->db->where('MONTH(created_at)', date('m')); //For current month
            $this->db->where('YEAR(created_at)', date('Y')); // For current year 
            $query1 = $this->db->get('tbl_product_transactions');
           if ($query1->num_rows() == 0 )
            {
                    return false;
            }
            
            $data['pending'] = $query1->result();

            $this->db->select('count(*) AS approved'); 
            $this->db->where('user_id', $user_id);
            $this->db->where('status', "1");  
             $this->db->where('MONTH(created_at)', date('m')); //For current month
            $this->db->where('YEAR(created_at)', date('Y')); // For current year  
            $query2 = $this->db->get('tbl_product_transactions');
           if ($query2->num_rows() == 0 )
            {
                    return false;
            }
            
            $data['approved'] = $query2->row();

            $this->db->select('count(*) as rejected'); 
            $this->db->where('user_id', $user_id);
            $this->db->where('status', "2");  
             $this->db->where('MONTH(created_at)', date('m')); //For current month
            $this->db->where('YEAR(created_at)', date('Y')); // For current year  
            $query3 = $this->db->get('tbl_product_transactions');
           if ($query3->num_rows() == 0 )
            {
                    return false;
            }
            
            $data['rejected'] = $query3->result();
            
            return $data;
        }

         public function get_categories(){
            $this->db->select('*'); 
            //$this->db->where('is_deleted', 0);
            $query = $this->db->get('tbl_material_group');
           if (! $query->num_rows() > 0 )
            {
                    return false;
            }
            
            return $query->result();
        }
        public function get_category_id($category_name){
            $this->db->select('material_group_code'); 
            $this->db->where('material_group_name', $category_name);  
            $query = $this->db->get('tbl_material_group');
           if (! $query->num_rows() > 0 )
            {
                    return false;
            }
            $row = $query->row();

            return $row->id;  
            
        }

        public function get_stock_sheet($location_id){
            $this->db->select('*'); 
            $this->db->where('storage_location_code', $location_id);  
            //$this->db->where('category_id', $category_id); 
            $this->db->where('status', 0);   
            $query = $this->db->get('tbl_material');
           if ($query->num_rows() == 0 )
            {
                    return false;
            }else{
                return $query->result();
            }
            
            
        }


        public function get_user_stock_entry($user_id,$status){
             $this->db->select('*'); 
            $this->db->where('user_id', $user_id);
            $this->db->where('status', $status);   
            $query = $this->db->get('tbl_product_transactions');
           if ($query->num_rows() == 0 )
            {
                    return false;
            }
            
            return $query->result();

        }

         public function get_sap_data($stocksheet_id){
            $sap_data = array();
             $this->db->select('closing_stock,unit_price'); 
            $this->db->where('id', $stocksheet_id); 
            $query = $this->db->get('tbl_material');
           if ($query->num_rows() == 0 )
            {
                    return false;
            }

            
            $row = $query->row();
            

            return $row; 

        }

         public function insert_product($pcode, $pname,$c_code,$l_id,$user_id,$count_type,$quantity,$amount_sap,$unit_price,
            $amount_variance, $price_variance,$stocksheet_id)
    {
         $this->logAccess("inside model");
         $now = new DateTime ( NULL, new DateTimeZone('UTC'));
           $this->db->trans_start();
           $product = array('product_code' => $pcode, 
                    'product_name' => $pname, 'product_category' => $c_code, 
                    'location_id' => $l_id, 'user_id' => $user_id, 'count_type' => $count_type, 'quantity' => $quantity,
                     'amount_SAP' => $amount_sap,'unit_price' => $unit_price, 'amount_variance' => $amount_variance,
                     'price_variance' => $price_variance,
                    'created_at' => $now->format('Y-m-d H:i:s'));
           $this->logAccess("data to insert".json_encode($product));

             $id =$this->db->insert('tbl_product_transactions',$product);
              $error = $this->db->error();
              $this->logAccess("error".json_encode($error));

        $update_data = array('status' => 1);
        $this->db->where('id', $stocksheet_id);
        $this->db->update('tbl_material', $update_data); 

        $approval_data = array('product_id' => $id, 'plant_accountant' => 0,'inventory_controller' => 0, 'plant_controller' => 0,'plant_manager' => 0,  
            'business_controller' => 0,'financial_controller' => 0, 'general_manager' => 0,
         'next_to_update' => 2, 'timestamp' => $now->format('Y-m-d H:i:s'));
        $this->db->insert('tbl_approvals',$approval_data);


              $this->db->trans_complete();
             if ($this->db->trans_status() === FALSE)
                {
                    return false;
                } 

            return $id;
    }


    public function edit_product($product_id,$user_id,$count_type,$quantity,$comments)
    {
         $this->logAccess("inside model");
         $now = new DateTime ( NULL, new DateTimeZone('UTC'));
           $this->db->trans_start();
           $update_data = array('count_type' => $count_type, 'quantity' => $quantity,'comments' => $comments,
                    'updated_at' => $now->format('Y-m-d H:i:s'), 'updated_by' => $user_id);
           $this->db->where('id', $product_id);
           $id =$this->db->update('tbl_product_transactions', $update_data); 

           $this->db->trans_complete();
             if ($this->db->trans_status() === FALSE)
                {
                    return false;
                } 

            return $id;
         }

    public function get_location_name($location_id){
        $this->db->select('storage_location_name'); 
            $this->db->where('id', $location_id);   
           $query = $this->db->get('tbl_storage_location');
            if ($query->num_rows() <= 0 )
            {
                    return false;
            }
             $row = $query->row();

            return $row->name; 
    }

     public function get_category_name($category_id){
        $this->db->select('name'); 
            $this->db->where('id', $location_id);   
            $query = $this->db->get('tbl_categories');
           if ($query->num_rows() <= 0 )
            {
                    return false;
            }else{
                return $query->row();
            }

    }

    public function get_entry_report_data($user_id){
    $sql = "SELECT `tbl_product_transactions`.`product_category`,`tbl_product_transactions`.`created_at`,SUM(`tbl_product_transactions`.`quantity`) as total, `tbl_material_group`.`material_group_code`, `tbl_material_group`.`material_group_name` FROM tbl_product_transactions INNER JOIN `tbl_material_group` ON `tbl_material_group`.`material_group_code` = `tbl_product_transactions`.`product_category` WHERE user_id = '$user_id' AND MONTH(CURDATE()) = MONTH(`tbl_product_transactions`.`created_at`) AND YEAR(`tbl_product_transactions`.`created_at`) = YEAR(CURDATE()) GROUP BY product_category" ;
            $query = $this->db->query($sql);
            return $query->result();

    }
    public function get_location_report_data($user_id,$location_id){

    $sql = "SELECT `tbl_product_transactions`.`product_category`,`tbl_product_transactions`.`created_at`,SUM(`tbl_product_transactions`.`quantity`) as total, `tbl_material_group`.`material_group_code`, `tbl_material_group`.`material_group_name` FROM tbl_product_transactions INNER JOIN `tbl_material_group` ON `tbl_material_group`.`material_group_code` = `tbl_product_transactions`.`product_category` WHERE user_id = '$user_id' AND location_id = '$location_id' AND MONTH(CURDATE()) = MONTH(`tbl_product_transactions`.`created_at`) AND YEAR(`tbl_product_transactions`.`created_at`) = YEAR(CURDATE()) GROUP BY product_category" ;
            $query = $this->db->query($sql);
            return $query->result();

    }


    public function get_variance_report_data($user_id){

    $sql = "SELECT `tbl_product_transactions`.`product_category`,(`tbl_product_transactions`.`amount_SAP`-`tbl_product_transactions`.`quantity`) as difference, `tbl_material_group`.`material_group_code`, `tbl_material_group`.`material_group_name`FROM tbl_product_transactions  INNER JOIN `tbl_material_group` ON `tbl_material_group`.`material_group_code` = `tbl_product_transactions`.`product_category` WHERE user_id = '$user_id' AND MONTH(CURDATE()) = MONTH(`tbl_product_transactions`.`created_at`) AND YEAR(`tbl_product_transactions`.`created_at`) = YEAR(CURDATE()) GROUP BY product_category" ;
            $query = $this->db->query($sql);
            return $query->result();

    }
    public function get_location_variance_report_data($user_id,$location_id){

    $sql = "SELECT `tbl_product_transactions`.`product_category`,(`tbl_product_transactions`.`amount_SAP`-`tbl_product_transactions`.`quantity`) as difference, `tbl_material_group`.`material_group_code`, `tbl_material_group`.`material_group_name`FROM tbl_product_transactions  INNER JOIN `tbl_material_group` ON `tbl_material_group`.`material_group_code` = `tbl_product_transactions`.`product_category` WHERE user_id = '$user_id' AND location_id = '$location_id' AND MONTH(CURDATE()) = MONTH(`tbl_product_transactions`.`created_at`) AND YEAR(`tbl_product_transactions`.`created_at`) = YEAR(CURDATE()) GROUP BY product_category" ;
            $query = $this->db->query($sql);
            return $query->result();

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
