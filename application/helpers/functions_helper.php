<?php
function is_version_current($appID,$appVersionName){
    //http_response_code(408);
    //exit;
    if($appID == '1'){
        if( ($appVersionName == '1.0.0') || ($appVersionName == '1.0.0') || ($appVersionName == '1.0.0') || ($appVersionName == '1.2.9') || ($appVersionName == '1.2.8') || ($appVersionName == '1.2.7') || ($appVersionName == '1.2.6')  ){
            return true;
        }elseif( (trim($appVersionName) == '')  ){
            return true;
        }else{
            return false;
        }
    }
    else if($appID == '1'){
        if( ($appVersionName == '1') ){
            return true;
        }else{
            return false;
        }
    }
    return true;
}