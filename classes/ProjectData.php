<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;


class ProjectData
{
    /**
     * Function that returns the info array from a specific project
     * @param $project, the project id
     * @param $info_array, array that contains the conditionals
     * @param string $type, if its single or a multidimensional array
     * @return array, the info array
     */
    public static function getProjectInfoArray($records){
        $array = array();
        foreach ($records as $event) {
            foreach ($event as $data) {
                array_push($array,$data);
            }
        }

        return $array;
    }

    public static function getProjectInfoArrayRepeatingInstruments($records,$filterLogic=null){
        $array = array();
        $found = array();
        $index=0;
        foreach ($filterLogic as $filterkey => $filtervalue){
            array_push($found, false);
        }
        foreach ($records as $record=>$record_array) {
            $count = 0;
            foreach ($filterLogic as $filterkey => $filtervalue){
                $found[$count] = false;
                $count++;
            }
            foreach ($record_array as $event=>$data) {
                if($event == 'repeat_instances'){
                    foreach ($data as $eventarray){
                        $datarepeat = array();
                        foreach ($eventarray as $instrument=>$instrumentdata){
                            $count = 0;
                            foreach ($instrumentdata as $instance=>$instancedata){
                                foreach ($instancedata as $field_name=>$value){
                                    if(!array_key_exists($field_name,$array[$index])){
                                        $array[$index][$field_name] = array();
                                    }

                                    if($value != "" && (!is_array($value) || (is_array($value) && !empty($value)))){
                                        $datarepeat[$field_name][$instance] = $value;
                                        $count = 0;
                                        foreach ($filterLogic as $filterkey => $filtervalue){
                                            if($value == $filtervalue && $field_name == $filterkey){
                                                $found[$count] = true;
                                            }
                                            $count++;
                                        }
                                    }

                                }
                                $count++;
                            }
                        }
                        foreach ($datarepeat as $field=>$datai){
                            #check if non repeatable value is empty and add repeatable value
                            #empty value or checkboxes
                            if($array[$index][$field] == "" || (is_array($array[$index][$field]) && empty($array[$index][$field][1]))){
                                $array[$index][$field] = $datarepeat[$field];
                            }
                        }
                    }
                }else{
                    $array[$index] = $data;
                    foreach ($data as $fname=>$fvalue) {
                        $count = 0;
                        foreach ($filterLogic as $filterkey => $filtervalue){
                            if($fvalue == $filtervalue && $fname == $filterkey){
                                $found[$count] = true;
                            }
                            $count++;
                        }
                    }
                }
            }
            $found_total = true;
            foreach ($found as $fname=>$fvalue) {
                if($fvalue == false){
                    $found_total = false;
                    break;
                }
            }
            if(!$found_total && $filterLogic != null){
                unset($array[$index]);
            }

            $index++;
        }
        return $array;
    }

    public static function decodeData($encryptedCode, $secret_key, $secret_iv, $timestamp){
        $code = self::getCrypt($encryptedCode,"d",$secret_key,$secret_iv);
        if($code == "start_".$timestamp){
            return true;
        }
        return false;
    }

    public static function getCrypt($string, $action = 'e',$secret_key="",$secret_iv="" ) {
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $secret_key );
        $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

        if( $action == 'e' ) {
            $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
        }
        else if( $action == 'd' ){
            $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
        }

        return $output;
    }

    public static function startTest($encryptedCode, $secret_key, $secret_iv, $timestamp){
        $code = self::getCrypt($encryptedCode,"d",$secret_key,$secret_iv);
        if($code == "start_".$timestamp){
            return true;
        }
        return false;
    }

    public static function getRowQuestions()
    {
        $row_questions = array(2 => "2-15", 3 => "26-39", 4 => "40-55");
        return $row_questions;
    }
    public static function getRowQuestionsParticipantPerception()
    {
        $row_questions_1 = array(0 => "rpps_s_q1", 1 => "rpps_s_q17", 2 => "rpps_s_q18", 3 => "rpps_s_q19", 4 => "rpps_s_q20", 5 => "rpps_s_q21",
            6 => "rpps_up_q66", 7 => "rpps_s_q22", 8 => "rpps_s_q23", 9 => "rpps_s_q24", 10 => "rpps_s_q25", 11 => "rpps_up_q65",
            12 => "rpps_up_q67", 13 => "rpps_s_q57");
        return $row_questions_1;
    }
    public static function getRowQuestionsResponseRate(){
        $row_questions_2 = array(1 => "any", 2 => "partial", 3 => "complete", 4 => "breakoffs");
        return $row_questions_2;
    }
}
?>