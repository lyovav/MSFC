<?php
    /*
    * Project:     Clan Stat
    * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
    * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
    * -----------------------------------------------------------------------
    * Began:       2011
    * Date:        $Date: 2011-10-24 11:54:02 +0200 $
    * -----------------------------------------------------------------------
    * @author      $Author: Edd $
    * @copyright   2011-2012 Edd - Aleksandr Ustinov
    * @link        http://wot-news.com
    * @package     Clan Stat
    * @version     $Rev: 2.0.1 $
    *
    */
?>
<?php

    if (preg_match ("/func.php/", $_SERVER['PHP_SELF']))
    {
        header ("Location: ./index.php");
        exit;
    }
    function redirect($url)
    {
        preg_match('%^(https?://)([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i',$url,$matches);
        if(count($matches) == 0){
            $url = 'http://'.$url;
        }
        echo "<script>window.location = '".$url."'</script>";
    }
    function reform($array){
        $new = array();
        foreach($array as $val){
            $new[] = $val[0];
        }
        return $new;
    }
    function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    function now(){
        return strtotime(date("Y-m-d H:i:s"));
    }
    function today(){
        return strtotime(date("Y-m-d"));
    }
    function fnow(){
        return strtotime(date("Y-m-d H:i"));
    }

    function error_rep($error,$lang){
        if($error == 1){
            return $lang['error_2'];    
        }elseif($error == 21){
            return $lang['error_3'];
        }

    }

    function array_special_merge($array1,$array2)
    {
        foreach($array2 as $key2 => $val2){
            if(!array_key_exists($key2,$array1)){
                $array1[$key2] = $val2;
            }else{
                $array1[] = $val2;
            }

        }
        return $array1;

    }

    function number_transform($num)
    {
        $data['I'] = 1;
        $data['II'] = 2;
        $data['III'] = 3;
        $data['IV'] = 4;
        $data['V'] = 5;
        $data['VI'] = 6;
        $data['VII'] = 7;
        $data['VIII'] = 8;
        $data['IX'] = 9;
        $data['X'] = 10;
        $return = $data[trim($num)];

        return $return;

    }

    function lockin_mysql()
    {
        global $db;
        //$check_if = $db->query("SELECT value FROM config WHERE name = 'lockin';")->fetch();

        $sql = "SELECT value FROM config WHERE name = 'lockin';";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $check_if = $q->fetch();
        } else {
            print_r($q->errorInfo());
            die();
        }

        if((now() - $check_if['value']) > 900){
            $sql = "UPDATE config SET value = '".now()."' WHERE name = 'lockin';";
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            }
            return true;
        }elseif($check_if['value'] != 0){
            return false;
        }else{
            $sql = "UPDATE config SET value = '".now()."' WHERE name = 'lockin';";
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            }
            return true;
        }    
    }
    function lockout_mysql()
    {
        global $db;
        $db->prepare("UPDATE config SET value = '0' WHERE name = 'lockin';")->execute();   
    }
    function lock_check()
    {
        global $db;
        //$check_if = $db->query("SELECT value FROM config WHERE name = 'lockin';")->fetch();

        $sql = "SELECT value FROM config WHERE name = 'lockin';";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $check_if = $q->fetch();
        } else {
            print_r($q->errorInfo());
            die();
        }

        if((now() - $check_if['value']) > 900){
            $sql = "UPDATE config SET value = '0' WHERE name = 'lockin';";
            $db->prepare($sql)->execute();
            return true;
        }elseif($check_if['value'] != 0){
            return false;
        }else{
            return true;
        }

    }


    function get_headers_curl($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,            $url);
        curl_setopt($ch, CURLOPT_HEADER,         true);
        curl_setopt($ch, CURLOPT_NOBODY,         true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,        10);

        $r = @curl_exec($ch);
        $r = @explode("\n", $r);
        return $r;
    } 

    function is_valid_url( $url, $mr = 10) {
        $timeout = 7;
        $ch = curl_init($url);
        if (!ini_get('open_basedir') && !ini_get('safe_mode')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch,CURLOPT_NOBODY, true);
            curl_setopt($ch,CURLOPT_USERAGENT , "page-check/1.0");
            curl_exec($ch);
            if(curl_errno($ch)) {
                curl_close($ch);
                return false;
            }
            return true;
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($mr > 0) {
                $newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

                $rch = curl_copy_handle($ch);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_TIMEOUT, $timeout);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true); 
                do {
                    curl_setopt($rch, CURLOPT_URL, $newurl);
                    $header = curl_exec($rch);
                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/', $header, $matches);
                            $newurl = trim(array_pop($matches));
                        } else {
                            $code = 0;
                        }
                    }
                } while ($code && --$mr);
                curl_close($rch);
                if (!$mr) {
                    return false;
                }
                curl_setopt($ch, CURLOPT_URL, $newurl);
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                curl_setopt($ch,CURLOPT_NOBODY, true);
                curl_setopt($ch,CURLOPT_USERAGENT , "page-check/1.0");
                curl_exec($ch);
                if(curl_errno($ch)) {
                    curl_close($ch);
                    return false;
                }
                return true;
            }
        }
    }
    /*
    function is_valid_url($url)
    {   
    $timeout = 7;
    $ch = curl_init($url);  

    // Set request options
    curl_setopt_array($ch, array(
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_NOBODY => true,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_USERAGENT => "page-check/1.0" 
    ));

    // Execute request
    curl_exec($ch);

    if(curl_errno($ch)) {
    curl_close($ch);
    return false;
    }
    return true;
    }
    */
    function get_config()
    {
        global $db; 

        $sql = "SELECT * FROM config;";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            foreach($q->fetchAll() as $val){
                $new[$val['name']] = $val['value'];
            }

        } else {
            //print_r($q->errorInfo());
            $new['lang'] = 'en';
            $new['server'] = 'ru';
            $new['error'] = '2'; // 2 - no base installed

        }       
        return $new;
    }      
    function read_tabs()
    {
        global $db;
        $sql = "SELECT * FROM tabs ORDER BY id ASC;";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            return $q->fetchAll();  
        }else{ 
            print_r($q->errorInfo());
            die();
        }  
    }
    function sort_id($a, $b)
    {
        return strnatcmp($a["id"], $b["id"]);
    }
    function update_array($array,$update)
    {
        foreach($update as $key => $val){
            if(isset($array[$key])){
                $array[$key] = $val;
            }
        }
        return $array;
    }
    function get_url($url)
    {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
?>