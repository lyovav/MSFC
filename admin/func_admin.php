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
    function get_tanks_list() {
        global $db;
        $sql = "SELECT * FROM tanks;";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            return $q->fetchAll();
        } else {
            print_r($q->errorInfo());
            die();
        }
    }
    function update_tanks_list($array){
        global $db;
        foreach($array as $key => $val){
            $nm = 0;
            $insert = '';
            foreach($val as $column => $val){
                if($nm == 0){
                    $insert .= "`".$column."` = '".$val."'";  
                    $nm++;  
                }else{
                    $insert .= ', `'.$column."` = '".$val."'";
                }    
            }

            $sql = "UPDATE tanks SET ".$insert." WHERE id = '".$key."';";
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            }    
        }
    }
    function get_clanstat_ver()
    {
        //echo $search;
        $error = 0;
        $data = array();
        $request = "GET /ajax/clanstat HTTP/1.0\r\n";
        //$request = "GET /uc/clans/?type=table&search=\"The Red\"".$off." HTTP/1.0\r\n"; 
        //echo $request;
        $request.= "Accept: text/html, */*\r\n";
        $request.= "User-Agent: Mozilla/3.0 (compatible; easyhttp)\r\n";
        $request.= "X-Requested-With: XMLHttpRequest\r\n";
        $request.= "Host: wot-news.com\r\n";
        $request.= "Connection: Keep-Alive\r\n";
        $request.= "\r\n";
        $n = 0;
        print_r($request);
        while(!isset($fp)){  
            $fp = fsockopen('wot-news.com', 80, $errno, $errstr, 15);
            if($n == 3){
                break;
            }
            $n++;
        }
        if (!$fp) {
            echo "$errstr ($errno)<br>\n";
        } else {

            stream_set_timeout($fp,20);
            $info = stream_get_meta_data($fp);

            fwrite($fp, $request);
            $page = '';

            while (!feof($fp) && (!$info['timed_out'])) { 
                $page .= fgets($fp, 4096);
                $info = stream_get_meta_data($fp);
            }
            fclose($fp);
            if ($info['timed_out']) {
                $error = 1; //Connection Timed Out
            }
        }
        if($error == 0){
            preg_match_all("/{\"request(.*?)success\"}/", $page, $matches);
            $data = (json_decode($matches[0][0], true));
        }
        $new = &$data;
        return $new;
    }
    function base_dir($local = null)
    {
        if($local == null){
            $local = dirname($_SERVER['PHP_SELF']);
        }
        $full = dirname(__FILE__);
        $public_base = str_replace($local, "", $full);

        return $public_base;
    } 
    function error($msg) 
    {
        $data = '<div align="center" style="border:1px solid #CCC; background-color:#FAFAFA; color:#FF0000">';
        foreach ( $msg as $value ) {
            $data .= $value."<br />";
        }
        $data .= '</div>';
        return $data;
    }
    function insert_config($config)
    {
        global $db;
        unset($config['consub']);
        if(!isset($config['cron'])){
            $config['cron'] = 0;
        }
        if(!isset($config['news'])){
            $config['news'] = 0;
        }

        foreach($config as $name => $var){
            $sql = "UPDATE config SET value = '".$var."' WHERE name = '".$name."';";
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            }
        }
    }
    function new_user($post)
    {
        global $db,$auth;
        unset($post['newuser']);
        $sql = "SELECT COUNT(*) FROM users WHERE user = '".$post['user']."';";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $status_user = $q->fetchColumn();  
        }else{ 
            print_r($q->errorInfo());
            die();
        }
        if($status_user == 0){
            $post['password'] = $auth->encrypt($post['password']);
            $post['email'] = $post['user'].'@local.com'; 
            $sql = "INSERT INTO users (`".(implode("`,`",array_keys($post)))."`) VALUES ('".(implode("','",$post))."');";

            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            } 
            return FALSE; 
        }   
        return TRUE;
    }
    function edit_user($post)
    {
        global $db,$auth;
        unset($post['edituser']);
        $sql = "SELECT COUNT(*) FROM users WHERE user = '".$post['oldname']."';";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $status_user = $q->fetchColumn();  
        }else{ 
            print_r($q->errorInfo());
            die();
        }
        if($status_user == 1){
            $oldname = $post['oldname'];
            unset($post['oldname']);
            if(strlen($post['password']) > 0){
                $post['password'] = $auth->encrypt($post['password']);
            }else{
                unset($post['password']);
            }
            $post['email'] = $post['user'].'@local.com'; 
            $nm = 0;
            $insert = '';
            foreach($post as $column => $val){
                if($nm == 0){
                    $insert .= "`".$column."` = '".$val."'";  
                    $nm++;  
                }else{
                    $insert .= ', `'.$column."` = '".$val."'";
                }    
            }
            $sql = "UPDATE users SET ".$insert." WHERE user = '".$oldname."';";
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            }    
        }   

    }
    function delete_user($get)
    {
        global $db;
        if($get['userdel'] == 1){
            if(isset($get['id'])){
                if(is_numeric($get['id'])){
                    $sql = "SELECT COUNT(*) FROM users WHERE id = '".$get['id']."';";
                    $q = $db->prepare($sql);
                    if ($q->execute() == TRUE) {
                        $status_user = $q->fetchColumn();  
                    }else{ 
                        print_r($q->errorInfo());
                        die();
                    }
                    if($status_user > 0){

                        $sql = "DELETE FROM users WHERE id = '".$get['id']."';";
                        $q = $db->prepare($sql);
                        if ($q->execute() != TRUE) {
                            print_r($q->errorInfo());
                            die();
                        } 
                        return FALSE; 
                    }
                }
            }
        }
        return TRUE;
        //header ("Location: ./index.php?page=main#tabs-2");
    }

    function delete_tab($get)
    {
        global $db;
        if($get['del'] == 1){
            if($get['type'] == 0){
                $sql = "SELECT * FROM tabs WHERE id = '".$get['id']."';";
                $q = $db->prepare($sql);
                if ($q->execute() == TRUE) {
                    $info = $q->fetch();  
                }else{ 
                    print_r($q->errorInfo());
                    die();                                             
                }     
                $target_path = ROOT_DIR.'/tabs/'.$info['file'];
                unlink($target_path);
            }
            $sql = "DELETE FROM tabs WHERE id = '".$get['id']."';";
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            } 
        }elseif($get['del'] == 2){
            $file = '';
            $new = explode('php',$get['file']);
            array_pop($new);
            foreach($new as $var){
                $file .= $var; 
            }
            $target_path = ROOT_DIR.'/tabs/'.$file.'.php';
            unlink($target_path);
        }

        //header ("Location: ./index.php?page=main#tabs-2");
    }

    function creat_ajax_tab($post)
    {
        global $db;
        unset($post['ajaxcre']);
        $post['file'] = trim($post['file']);
        $sql = "SELECT COUNT(*) FROM tabs WHERE file = '".$post['file']."';";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $status_tab = $q->fetchColumn();  
        }else{ 
            print_r($q->errorInfo());
            die();
        }
        if($status_tab == 0){ 
            $sql = "SELECT MAX(id) FROM tabs";
            $q = $db->prepare($sql);
            if ($q->execute() == TRUE) {
                $max = $q->fetchColumn();  
            }else{ 
                print_r($q->errorInfo());
                die();
            } 
            $max = (int) $max;
            $post['id'] = $max + 10;
            $post['name'] = '...';
            $post['type'] = '1';
            $post['status'] = '0';
            $sql = "INSERT INTO tabs (`".(implode("`,`",array_keys($post)))."`) VALUES ('".(implode("','",$post))."');";
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            }     
        }else{
            return TRUE;
        }
        return FALSE;
    }

    function tabs_info_db($post)
    {
        global $db;
        $error = 0;
        unset($post['tabsub']);
        //print_r($post);
        foreach($post as $key => $var){
            $tmp = explode('_',$key);
            $type = array_pop($tmp);
            $tmp_key = implode('_',$tmp);
            if(!isset($post[$tmp_key.'_status']) && !isset($new[$tmp_key]['status'])){
                $new[$tmp_key]['status'] = 0;        
            }
            if($type == 'status'){
                $var = 1;
            }
            if(strlen($var) > 0){
                $new[$tmp_key][$type] = $var;
            }
        }    
        foreach($new as $vals){ 
            //print_r($vals);
            if(count($vals) == 6){
                $sql = "SELECT COUNT(*) FROM tabs WHERE file = '".$vals['file']."';";
                $q = $db->prepare($sql);
                if ($q->execute() == TRUE) {
                    $num = $q->fetchColumn();  
                }else{ 
                    print_r($q->errorInfo());
                    die();
                }  
                if($num == 0){
                    $sql = "INSERT INTO tabs (`".(implode("`,`",array_keys($vals)))."`) VALUES ('".(implode("','",$vals))."');";
                    $q = $db->prepare($sql);
                    if ($q->execute() != TRUE) {
                        print_r($q->errorInfo());
                        die();
                    }     

                }else{
                    $nm = 0;
                    $insert = '';
                    foreach($vals as $column => $val){
                        if($nm == 0){
                            $insert .= "`".$column."` = '".$val."'";  
                            $nm++;  
                        }else{
                            $insert .= ', `'.$column."` = '".$val."'";
                        }    
                    }
                    $sql = "UPDATE tabs SET ".$insert." WHERE file = '".$vals['file']."';";
                    //echo $sql;
                    $q = $db->prepare($sql);
                    if ($q->execute() != TRUE) {
                        print_r($q->errorInfo());
                        die();
                    }     
                }

            }else{
                $error = 1;
            }
        }
        if($error == 1) {
            return TRUE;
        }else{
            return FALSE;
        }
    }

    function read_tabs_dir()
    {
        foreach(scandir(ROOT_DIR.'/tabs/') as $file){
            if (preg_match ("/\.php/", $file)){
                $files_list[] = $file; 
            }
        }
        return $files_list;
    }
    function check_tabs_db($tabs)
    {
        global $db;
        foreach($tabs as $tab){
            $sql = "SELECT COUNT(*) FROM tabs WHERE file = '".$tab."';";
            $q = $db->prepare($sql);
            if ($q->execute() == TRUE) {
                $status_tab[$tab] = $q->fetchColumn();  
            }else{ 
                print_r($q->errorInfo());
                die();
            }  
        }
        return $status_tab;
    }
    function read_users()
    {
        global $db;
        $sql = "SELECT * FROM users ORDER BY id ASC;";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            return $q->fetchAll();  
        }else{ 
            print_r($q->errorInfo());
            die();
        }  
    }
    function insert_file($filename)
    {
        global $db;
        $templine = '';
        $lines = file($filename);
        foreach ($lines as $line)
        {
            if (substr($line, 0, 2) == '--' || $line == '')
                continue;
            $templine .= $line;
            if (substr(trim($line), -1, 1) == ';')
            {
                $q = $db->prepare($templine);
                if ($q->execute() != TRUE) {
                    print_r($q->errorInfo());
                    die();
                } 
                $templine = '';
            }
        }
    }
    function recreat_db()
    {
        global $db;

        $sql = "show tables like '%%'"; 
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $tables = $q->fetchAll();  
        }else{ 
            print_r($q->errorInfo());
            die();
        }  
        foreach($tables as $tab){
            $sql = "DROP TABLE IF EXISTS ".$tab[0].";";
            //echo $sql;
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            }    
        }
    }
    function sync_roster($load)
    {
        global $db;
        $sql = "SELECT id,account_id FROM players;";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $inbase = $q->fetchAll();  
        }else{ 
            print_r($q->errorInfo());
            die();
        }  
        foreach($load as $val){
            $new_load[$val['account_id']] = 1;
        }
        $sql = "show tables like 'tank_%';";
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $nation_db = $q->fetchAll();
        } else {
            print_r($q->errorInfo());
            die();
        }

        foreach($inbase as $val){
            if(!isset($new_load[$val['account_id']])){
                $sql = "SELECT COUNT(*) FROM players WHERE account_id = '".$val['account_id']."';";
                $q = $db->prepare($sql);
                if ($q->execute() == TRUE) {
                    $num = $q->fetchColumn();
                }else{
                    print_r($q->errorInfo());
                    die();
                }
                if($num == 1){

                    $sql = "DELETE FROM players WHERE account_id = '".$val['account_id']."';";
                    //echo $sql;
                    $q = $db->prepare($sql);
                    if ($q->execute() != TRUE) {
                        print_r($q->errorInfo());
                        die();
                    }
                    foreach($nation_db as $nat){
                        $sql = "DELETE FROM ".$nat[0]." WHERE id = '".$val['id']."';";
                        //echo $sql;
                        $q = $db->prepare($sql);
                        if ($q->execute() != TRUE) {
                            print_r($q->errorInfo());
                            die();
                        }   
                    }
                }
            }
        }

    }

    /***** Exinaus *****/
    function get_top_tanks_list() {
        global $db;
        $top_tanks=array();
        check_top_tanks_db();

        $sql='SELECT tt.lvl, tt.type, tt.shortname, tt.show, tt.order, t.tank, tt.title, tt.index
        FROM top_tanks tt, tanks t
        WHERE t.title = tt.title;';
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $top_tanks_unsorted = $q->fetchAll();
        }else{
            print_r($q->errorInfo());
            die();
        }

        foreach($top_tanks_unsorted as $val) {
            $top_tanks[$val['tank']]['lvl'] = $val['lvl'];
            $top_tanks[$val['tank']]['title'] = $val['title'];
            $top_tanks[$val['tank']]['type'] = $val['type'];
            $top_tanks[$val['tank']]['show'] = ($val['show'] == 1) ? 'checked="checked"' : '';
            $top_tanks[$val['tank']]['order'] = $val['order'];
            $top_tanks[$val['tank']]['shortname'] = isset($val['shortname']) ? $val['shortname'] : '';
            $top_tanks[$val['tank']]['index'] = $val['index'];
        }

        return $top_tanks;
    }
    function update_top_tanks($config)
    {
        global $db;

        foreach($config as $name => $var){
            $var['show'] = isset($var['show']) ? 1 : 0;
            $sql = 'UPDATE top_tanks
            SET
            `show` = "'.$var['show'].'",
            `order` = "'.$var['order'].'",
            `shortname` = "'.$var['shortname'].'",
            `index` = "'.$var['index'].'"
            WHERE top_tanks.title = "'.$name.'";';
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            }
            unset($q);
        }
    }

    function delete_top_tank($info) {
        global $db;

        $sql = 'DELETE FROM top_tanks
        WHERE title = "'.$info.'";';
        $q = $db->prepare($sql);
        if ($q->execute() != TRUE) {
            print_r($q->errorInfo());
            die();
        }
    }
    function add_top_tanks($lvl,$type) {
        global $db;

        $sql = 'select t.title
        from
        tanks t
        left join top_tanks tt
        on t.title = tt.title
        where tt.title is null AND t.lvl = "'.$lvl.'" AND t.type = "'.$type.'";';
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $tanks = $q->fetchAll(PDO :: FETCH_ASSOC);
        } else {
            print_r($q->errorInfo());
            die();
        }
        //print_r($tanks);

        if(count($tanks) > 0) {
            unset($q);
            $i = count($tanks);
            $j = 1;
            $sql = 'INSERT INTO top_tanks (`title`, `lvl`, `type`) VALUES ';

            foreach($tanks as $val) {
                $sql .= "('{$val['title']}', '$lvl', '$type')";
                if($i != $j) { $sql .= ', '; $j++; } else { $sql .= ';'; }
            }
            //echo $sql;
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die();
            }
        }
    }
    function delete_top_tanks($lvl,$type) {
        global $db;

        $sql = 'DELETE FROM top_tanks
        WHERE lvl = "'.$lvl.'" AND type = "'.$type.'";';
        $q = $db->prepare($sql);
        if ($q->execute() != TRUE) {
            print_r($q->errorInfo());
            die();
        }
    }
?>