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
    * @version     $Rev: 2.0.2 $
    *
    */
?>
<?php
    function insert_stat($data,$roster,$config){   

        global $db;
        if(count($data['data']) > 0){

            $sql = "SELECT id,tank,nation,title FROM tanks;";
            $q = $db->prepare($sql);
            if ($q->execute() == TRUE) {
                $current_tmp = $q->fetchAll();
            } else {
                print_r($q->errorInfo());
                die();
            }

            foreach($current_tmp as $val){
                $current[$val['id']] = $val['title'].'_'.$val['nation'];    
            }


            if(!isset($current)){
                $current = array();
            }
            //print_r($current);
            $tmp = array();
            $current_flip = &array_flip($current);
            if(isset($data['data']['vehicles'])){
                foreach($data['data']['vehicles'] as $val){
                    if(!in_array($val['name'].'_'.$val['nation'],$current)){
                        $tank = array(
                        'tank' => trim($val['localized_name']),
                        'nation' => $val['nation'],
                        'type' => $val['class'],
                        'lvl' => $val['level'],
                        'link' => $val['image_url'],
                        'title' => $val['name'],
                        );
                        $tsql = "INSERT INTO tanks (".(implode(",",array_keys($tank))).") VALUES ('".(implode("','",$tank))."');";
                        $q = $db->prepare($tsql);
                        if ($q->execute() !== TRUE) {
                            print_r($q->errorInfo());
                            die();
                        }
                        $sql = "SELECT id FROM tanks WHERE title = '".$val['name']."';";
                        $q = $db->prepare($sql);
                        if ($q->execute() == TRUE) {
                            $id = $q->fetch();
                            $id = $id['id'];
                        } else {
                            print_r($q->errorInfo());
                            die();
                        }
                        /**
                        if(!is_numeric($id)){
                        $myFile = now().".txt";
                        $fh = fopen($myFile, 'w') or die("can't open file");
                        $stringData = $id.' '.$val['name'].' '.$val['class']."\n";
                        fwrite($fh, $stringData);
                        fclose($fh);
                        }
                        **/
                        //$nation_db = $db->query("show tables like 'tank_".$val['nation']."';")->fetchAll(); 

                        $sql = "show tables like 'col_tank_".$val['nation']."';";
                        $q = $db->prepare($sql);
                        if ($q->execute() == TRUE) {
                            $nation_db = $q->fetchAll();
                        } else {
                            print_r($q->errorInfo());
                            die();
                        }
                        $sql = "show tables like 'col_rating_tank_".$val['nation']."';";
                        $q = $db->prepare($sql);
                        if ($q->execute() == TRUE) {
                            $col_nation_db = $q->fetchAll();
                        } else {
                            print_r($q->errorInfo());
                            die();
                        }
                        if(count($nation_db) < 1){
                            $db->prepare("CREATE TABLE col_tank_".$val['nation']." (account_id INT(12)) ENGINE=MYISAM;;")->execute(); 
                            $db->prepare("ALTER TABLE `col_tank_".$val['nation']."` ADD `up` INT( 12 ) NOT NULL;")->execute();   
                        }
                        if(count($col_nation_db) < 1){
                            $db->prepare("CREATE TABLE col_rating_tank_".$val['nation']." (account_id INT(12)) ENGINE=MyISAM;")->execute(); 
                            $db->prepare("ALTER TABLE `col_rating_tank_".$val['nation']."` ADD `up` INT( 12 ) NOT NULL;")->execute();   
                        }
                        $ask =  "ALTER TABLE `col_tank_".$val['nation']."` ADD `".$id."_w` INT( 12 ) NOT NULL;";
                        $ask .= "ALTER TABLE `col_tank_".$val['nation']."` ADD `".$id."_t` INT( 12 ) NOT NULL;";
                        $ask .= "ALTER TABLE `col_rating_tank_".$val['nation']."` ADD `".$id."_sp` INT( 12 ) NOT NULL;";
                        $ask .= "ALTER TABLE `col_rating_tank_".$val['nation']."` ADD `".$id."_dd` INT( 12 ) NOT NULL;";
                        $ask .= "ALTER TABLE `col_rating_tank_".$val['nation']."` ADD `".$id."_sb` INT( 12 ) NOT NULL;";
                        $ask .= "ALTER TABLE `col_rating_tank_".$val['nation']."` ADD `".$id."_fr` INT( 12 ) NOT NULL;";
                        $db->prepare($ask)->execute();
                    }     
                }
            }
        }
    }



    function pars_data2($result,$fname,$stat_config,$trans,$roster)
    {

        //Даты
        $new['date']['reg'] = $stat_config['reg'].' '.date('d.m.Y',$result['data']['created_at']);
        $new['date']['reg_num'] = $result['data']['created_at'];
        $new['date']['local'] = $stat_config['dateof'].' '.date('d.m.Y',$result['data']['updated_at']);
        $new['date']['local_num'] = $result['data']['updated_at'];
        //Общие результаты
        $new['overall'][$trans['games_p']] = $result['data']['summary']['battles_count'];
        $new['overall'][$trans['victories']] = $result['data']['summary']['wins'];
        $new['overall'][$trans['defeats']] = $result['data']['summary']['losses'];
        $new['overall'][$trans['battles_s']] = $result['data']['summary']['survived_battles'];


        //Боевая эффективность
        $new['perform'][$trans['destroyed']] = $result['data']['battles']['frags'];
        $new['perform'][$trans['spotted']] = $result['data']['battles']['spotted'];
        $new['perform'][$trans['hit_ratio']] = $result['data']['battles']['hits_percents'];
        $new['perform'][$trans['damage']] = $result['data']['battles']['damage_dealt'];
        $new['perform'][$trans['capture']] = $result['data']['battles']['capture_points'];
        $new['perform'][$trans['defend']] = $result['data']['battles']['dropped_capture_points'];

        //Боевой опыт
        $new['exp'][$trans['total_exp']] = $result['data']['experience']['xp'];
        $new['exp'][$trans['exp_battle']] = $result['data']['experience']['battle_avg_xp'];
        $new['exp'][$trans['exp_max']] = $result['data']['experience']['max_xp'];


        //Рейтинг
        $new['rating'][$trans['gr']]['type'] = 'GR';
        $new['rating'][$trans['gr']]['link'] = $stat_config['rating_link'].'gr.png';
        $new['rating'][$trans['gr']]['name'] = $trans['gr'];
        $new['rating'][$trans['gr']]['value'] = $result['data']['ratings']['integrated_rating']['value'];
        $new['rating'][$trans['gr']]['place'] = $result['data']['ratings']['integrated_rating']['place'];

        $new['rating'][$trans['wb']]['type'] = 'W/B';
        $new['rating'][$trans['wb']]['link'] = $stat_config['rating_link'].'wb.png';
        $new['rating'][$trans['wb']]['name'] = $trans['wb'];
        $new['rating'][$trans['wb']]['value'] = $result['data']['ratings']['battle_avg_performance']['value'].'%';
        $new['rating'][$trans['wb']]['place'] = $result['data']['ratings']['battle_avg_performance']['place'];

        $new['rating'][$trans['eb']]['type'] = 'E/B';
        $new['rating'][$trans['eb']]['link'] = $stat_config['rating_link'].'eb.png';
        $new['rating'][$trans['eb']]['name'] = $trans['eb'];
        $new['rating'][$trans['eb']]['value'] = $result['data']['ratings']['battle_avg_xp']['value'];
        $new['rating'][$trans['eb']]['place'] = $result['data']['ratings']['battle_avg_xp']['place'];

        $new['rating'][$trans['win']]['type'] = 'WIN';
        $new['rating'][$trans['win']]['link'] = $stat_config['rating_link'].'win.png';
        $new['rating'][$trans['win']]['name'] = $trans['win'];
        $new['rating'][$trans['win']]['value'] = $result['data']['ratings']['battle_wins']['value'];
        $new['rating'][$trans['win']]['place'] = $result['data']['ratings']['battle_wins']['place'];

        $new['rating'][$trans['gpl']]['type'] = 'GPL';
        $new['rating'][$trans['gpl']]['link'] = $stat_config['rating_link'].'gpl.png';
        $new['rating'][$trans['gpl']]['name'] = $trans['gpl'];
        $new['rating'][$trans['gpl']]['value'] = $result['data']['ratings']['battles']['value'];
        $new['rating'][$trans['gpl']]['place'] = $result['data']['ratings']['battles']['place'];

        $new['rating'][$trans['cpt']]['type'] = 'CPT';
        $new['rating'][$trans['cpt']]['link'] = $stat_config['rating_link'].'cpt.png';
        $new['rating'][$trans['cpt']]['name'] = $trans['cpt'];
        $new['rating'][$trans['cpt']]['value'] = $result['data']['ratings']['ctf_points']['value'];
        $new['rating'][$trans['cpt']]['place'] = $result['data']['ratings']['ctf_points']['place'];

        $new['rating'][$trans['dmg']]['type'] = 'DMG';
        $new['rating'][$trans['dmg']]['link'] = $stat_config['rating_link'].'dmg.png';
        $new['rating'][$trans['dmg']]['name'] = $trans['dmg'];
        $new['rating'][$trans['dmg']]['value'] = $result['data']['ratings']['damage_dealt']['value'];
        $new['rating'][$trans['dmg']]['place'] = $result['data']['ratings']['damage_dealt']['place'];

        $new['rating'][$trans['dpt']]['type'] = 'DPT';
        $new['rating'][$trans['dpt']]['link'] = $stat_config['rating_link'].'dpt.png';
        $new['rating'][$trans['dpt']]['name'] = $trans['dpt'];
        $new['rating'][$trans['dpt']]['value'] = $result['data']['ratings']['dropped_ctf_points']['value'];
        $new['rating'][$trans['dpt']]['place'] = $result['data']['ratings']['dropped_ctf_points']['place'];

        $new['rating'][$trans['frg']]['type'] = 'FRG';
        $new['rating'][$trans['frg']]['link'] = $stat_config['rating_link'].'frg.png';
        $new['rating'][$trans['frg']]['name'] = $trans['frg'];
        $new['rating'][$trans['frg']]['value'] = $result['data']['ratings']['frags']['value'];
        $new['rating'][$trans['frg']]['place'] = $result['data']['ratings']['frags']['place'];

        $new['rating'][$trans['spt']]['type'] = 'SPT';
        $new['rating'][$trans['spt']]['link'] = $stat_config['rating_link'].'spt.png';
        $new['rating'][$trans['spt']]['name'] = $trans['spt'];
        $new['rating'][$trans['spt']]['value'] = $result['data']['ratings']['spotted']['value'];
        $new['rating'][$trans['spt']]['place'] = $result['data']['ratings']['spotted']['place'];

        $new['rating'][$trans['exp']]['type'] = 'EXP';
        $new['rating'][$trans['exp']]['link'] = $stat_config['rating_link'].'exp.png';
        $new['rating'][$trans['exp']]['name'] = $trans['exp'];
        $new['rating'][$trans['exp']]['value'] = $result['data']['ratings']['xp']['value'];
        $new['rating'][$trans['exp']]['place'] = $result['data']['ratings']['xp']['place'];


        foreach($result['data']['vehicles'] as $veh){
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['link'] = $stat_config['td'].$veh['image_url'];
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['lvl'] = $veh['level'];
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['type'] = $veh['localized_name'];
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['total'] = $veh['battle_count'];
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['win'] = $veh['win_count'];
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['nation'] = $veh['nation'];
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['class'] = $veh['class'];  
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['spotted'] = (int) str_replace(' ','',$veh['spotted']);
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['damageDealt'] = (int) str_replace(' ','',$veh['damageDealt']);
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['survivedBattles'] = (int) str_replace(' ','',$veh['survivedBattles']);
            $new['tank'][$veh['level']][$veh['class']][$veh['localized_name']]['frags'] = (int) str_replace(' ','',$veh['frags']);     
        }

        foreach($result['data']['achievements'] as $name => $medal){
            if($name == 'maxDiehardSeries'){
                $medn['diehard']['max'] = $medal;
                $medn['diehard']['max_name'] = 'maxDiehardSeries';    
            }elseif($name == 'maxInvincibleSeries'){
                $medn['invincible']['max'] = $medal;
                $medn['invincible']['max_name'] = 'maxInvincibleSeries';    
            }elseif($name == 'maxPiercingSeries'){
                $medn['armorPiercer']['max'] = $medal;
                $medn['armorPiercer']['max_name'] = 'maxPiercingSeries';    
            }elseif($name == 'maxKillingSeries'){
                $medn['handOfDeath']['max'] = $medal; 
                $medn['handOfDeath']['max_name'] = 'maxKillingSeries';   
            }elseif($name == 'maxSniperSeries'){
                $medn['titleSniper']['max'] = $medal; 
                $medn['titleSniper']['max_name'] = 'maxSniperSeries';   
            }else{
                $medn[$name]['value'] = $medal;
            }
        }

        foreach(array_keys($medn) as $name){
            $medn[$name]['title'] = $trans['medal_'.$name];     
        }

        $medn['medalCarius']['img'] = 'images/medals/MedalCarius'.$medn['medalCarius']['value'].'.png';
        $medn['medalHalonen']['img'] = 'images/medals/MedalHalonen.png';
        $medn['invader']['img'] = 'images/medals/Invader.png';
        $medn['medalFadin']['img'] = 'images/medals/MedalFadin.png';
        $medn['armorPiercer']['img'] = 'images/medals/ArmorPiercer.png';
        $medn['medalEkins']['img'] = 'images/medals/MedalEkins'.$medn['medalEkins']['value'].'.png';
        $medn['mousebane']['img'] = 'images/medals/Mousebane.png';
        $medn['medalKay']['img'] = 'images/medals/MedalKay'.$medn['medalKay']['value'].'.png';
        $medn['defender']['img'] = 'images/medals/Defender.png';
        $medn['medalLeClerc']['img'] = 'images/medals/MedalLeClerc'.$medn['medalLeClerc']['value'].'.png';
        $medn['supporter']['img'] = 'images/medals/Supporter.png';
        $medn['steelwall']['img'] = 'images/medals/Steelwall.png';
        $medn['medalAbrams']['img'] = 'images/medals/MedalAbrams'.$medn['medalAbrams']['value'].'.png';
        $medn['medalPoppel']['img'] = 'images/medals/MedalPoppel'.$medn['medalPoppel']['value'].'.png';
        $medn['medalOrlik']['img'] = 'images/medals/MedalOrlik.png';
        $medn['handOfDeath']['img'] = 'images/medals/HandOfDeath.png';
        $medn['sniper']['img'] = 'images/medals/Sniper.png';
        $medn['warrior']['img'] = 'images/medals/Warrior.png';
        $medn['titleSniper']['img'] = 'images/medals/TitleSniper.png';
        $medn['medalWittmann']['img'] = 'images/medals/MedalBoelter.png';
        $medn['medalBoelter']['img'] = 'images/medals/MedalBoelter.png';
        $medn['medalBurda']['img'] = 'images/medals/MedalBurda.png';
        $medn['scout']['img'] = 'images/medals/Scout.png';
        $medn['beasthunter']['img'] = 'images/medals/Beasthunter.png';
        $medn['kamikaze']['img'] = 'images/medals/Kamikaze.png';
        $medn['raider']['img'] = 'images/medals/Raider.png';
        $medn['medalOskin']['img'] = 'images/medals/MedalOskin.png';
        $medn['medalBillotte']['img'] = 'images/medals/MedalBillotte.png';
        $medn['medalLavrinenko']['img'] = 'images/medals/MedalLavrinenko'.$medn['medalLavrinenko']['value'].'.png';
        $medn['medalKolobanov']['img'] = 'images/medals/MedalKolobanov.png';
        $medn['invincible']['img'] = 'images/medals/Invincible.png';
        $medn['lumberjack']['img'] = 'images/medals/Invincible.png';
        $medn['tankExpert']['img'] = 'images/medals/TankExpert.png';
        $medn['diehard']['img'] = 'images/medals/Diehard.png';
        $medn['medalKnispel']['img'] = 'images/medals/MedalKnispel'.$medn['medalKnispel']['value'].'.png'; 


        $medn['medalCarius']['type'] = 'major';
        $medn['medalHalonen']['type'] = 'epic';
        $medn['invader']['type'] = 'hero';
        $medn['medalFadin']['type'] = 'epic';
        $medn['armorPiercer']['type'] = 'special';
        $medn['medalEkins']['type'] = 'major';
        $medn['mousebane']['type'] = 'special';
        $medn['medalKay']['type'] = 'major';
        $medn['defender']['type'] = 'hero';
        $medn['medalLeClerc']['type'] = 'major';
        $medn['supporter']['type'] = 'hero';
        $medn['steelwall']['type'] = 'hero';
        $medn['medalAbrams']['type'] = 'major';
        $medn['medalPoppel']['type'] = 'major';
        $medn['medalOrlik']['type'] = 'epic';
        $medn['handOfDeath']['type'] = 'special';
        $medn['sniper']['type'] = 'hero';
        $medn['warrior']['type'] = 'hero';
        $medn['titleSniper']['type'] = 'special';
        $medn['medalWittmann']['type'] = 'epic';
        $medn['medalBoelter']['type'] = 'epic';
        $medn['medalBurda']['type'] = 'epic';
        $medn['scout']['type'] = 'hero';
        $medn['beasthunter']['type'] = 'special';
        $medn['kamikaze']['type'] = 'special';
        $medn['raider']['type'] = 'special';
        $medn['medalOskin']['type'] = 'epic';
        $medn['medalBillotte']['type'] = 'epic';
        $medn['medalLavrinenko']['type'] =  'major';
        $medn['medalKolobanov']['type'] = 'epic';
        $medn['invincible']['type'] = 'special';
        $medn['lumberjack']['type'] = 'special';
        $medn['tankExpert']['type'] = 'special';
        $medn['diehard']['type'] = 'special';
        $medn['medalKnispel']['type'] =  'major';

        foreach($medn as $name => $val){
            $nmedn[$val['type']][$name] = $val;
        }
        unset($nmedn['special']['lumberjack']);
        unset($nmedn['epic']['medalWittmann']);
        $new['medals'] = $nmedn;
        //print_r($new);
        return $new;
    }

    function get_last_roster()
    {
        global $db;
        global $config;
        $error = 1;

        $sql = "
        SELECT p.name, p.id, p.account_id, p.role, p.member_since
        FROM players p,
        (SELECT max(up) as maxup
        FROM players
        LIMIT 1) maxresults
        WHERE p.up > (maxresults.maxup - ".($config['cache']*60*60).")
        ORDER BY p.up DESC;";

        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $roster['request_data']['items'] = $q->fetchAll();
        } else {
            print_r($q->errorInfo());
            die();
        }

        if(count($roster['request_data']['items']) == 0)  {
            $error = 21; //No entries in MySQL
        }

        //print_r(restr($roster));
        $new['error'] = &$error;
        $new['data'] = &restr($roster);
        return $new;
    }

    function roster_sort($array)
    {
        $new = array();
        foreach($array as $val){
            $new[$val['name']] = $val;
        }
        return $new;  //new
    }
    function roster_resort_id($roster)
    {
        foreach($roster as $val){
            $new[$val['account_id']] = $val;
        }
        return $new;
    }

    function tanks_group($array){
        $name = array();
        foreach($array as $val){
            if(isset($val['tank'])){
                if(is_array($val['tank'])) {
                    foreach($val['tank'] as $lvl => $types){
                        foreach($types as $type => $tanks){
                            foreach($tanks as $tank){
                                $name[$type][$lvl][($tank['type'])] = true;
                            }
                        }
                    }
                }
            }
        }

        return $name;
    }
    function tanks_group_full($array,$nation_s,$type_s,$lvl_s){
        $name = array();
        
        foreach($array as $val){
            if(isset($val['tank'])){
                if(is_array($val['tank'])) {
                    foreach($val['tank'] as $lvl => $types){
                        foreach($types as $type => $tanks){
                            foreach($tanks as $tank){
                                if(in_array($tank['lvl'],$lvl_s) && in_array($tank['class'],$type_s) && in_array($tank['nation'],$nation_s)){
                                $name[$type][$lvl][($tank['type'])] = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $name;
    }

    function restr($array)
    {
        foreach(array_keys($array) as $val){
            if(is_array($array[$val])){
                foreach(array_keys($array[$val]) as $v){
                    if(is_numeric($v)){
                        unset($array[$val][$v]);
                    }
                }
            }else{
                if(is_numeric($val)){
                    unset($array[$val]);
                }
            }
        }
        return $array;
    }
    function tanks_nations() {
        global $db;
        $sql='SELECT DISTINCT nation FROM tanks;';
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            return $q->fetchAll();
        }else{
            print_r($q->errorInfo());
            die();
        }
    }
    function tanks_types() {
        global $db;
        $sql='SELECT DISTINCT type FROM tanks;';
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            return $q->fetchAll();
        }else{
            print_r($q->errorInfo());
            die();
        }
    }
    function tanks_lvl() {
        global $db;
        $sql='SELECT DISTINCT lvl FROM tanks;';
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            return $q->fetchAll();
        }else{
            print_r($q->errorInfo());
            die();
        }
    }
    /***** Exinaus *****/
    function check_top_tanks_db() {
        global $db;
        $sql='SHOW TABLES LIKE "top_tanks";';
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $tmp = $q->fetchAll();
        }else{
            print_r($q->errorInfo());
            die();
        }

        if(!(count($tmp)>0)) {
            $sql='CREATE TABLE IF NOT EXISTS `top_tanks` (
            `title` varchar(25) NOT NULL,
            `lvl` tinyint(3) unsigned NOT NULL,
            `type` varchar(15) NOT NULL,
            `show` tinyint(1) NOT NULL DEFAULT "1",
            `order` smallint(5) unsigned NOT NULL DEFAULT "0",
            `shortname` varchar(20) NOT NULL DEFAULT "",
            `index` tinyint(10) unsigned NOT NULL DEFAULT "1",
            PRIMARY KEY (`title`),
            KEY `index` (`index`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
            $q = $db->prepare($sql);
            if ($q->execute() != TRUE) {
                print_r($q->errorInfo());
                die('Ошибка при создании таблицы в БД');
            }
        }
        return 0;
    }
    function get_top_tanks_tab($index)
    {
        global $db;
        $top_tanks=array();
        check_top_tanks_db();

        $sql='SELECT tt.lvl, tt.type, tt.shortname, t.tank
        FROM top_tanks tt, tanks t
        WHERE t.title = tt.title AND tt.show = "1" AND tt.index = "'.$index.'"
        ORDER BY tt.order ASC, t.tank ASC;';
        $q = $db->prepare($sql);
        if ($q->execute() == TRUE) {
            $top_tanks_unsorted = $q->fetchAll();
        }else{
            print_r($q->errorInfo());
            die();
        }

        foreach($top_tanks_unsorted as $val) {
            $top_tanks[$val['tank']]['lvl'] = $val['lvl'];
            $top_tanks[$val['tank']]['type'] = $val['type'];
            $top_tanks[$val['tank']]['short'] = isset($val['shortname']) ? $val['shortname'] : '';
        }

        return $top_tanks;
    }
?>