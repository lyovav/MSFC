<?php
    /*
    * Project:     Clan Stat
    * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
    * Link:        http://creativecommons.org/licenses/by-nc-sa/3.0/
    * -----------------------------------------------------------------------
    * Began:       2011
    * Date:        $Date: 2011-10-24 11:54:02 +0200 $
    * -----------------------------------------------------------------------
    * @author      $Author: Edd, Exinaus, Shw  $
    * @copyright   2011-2012 Edd - Aleksandr Ustinov
    * @link        http://wot-news.com
    * @package     Clan Stat
    * @version     $Rev: 2.2.0 $
    *
    */
?>
<?php
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
    if (file_exists(dirname(__FILE__).'/function/mysql.php')) {
        define('ROOT_DIR', dirname(__FILE__));
    }else{
        define('ROOT_DIR', '.');    
    }
    //Starting script time execution timer
    $begin_time = microtime(true);

    //Cheker
    include_once(ROOT_DIR.'/including/check.php');

    //MYSQL
    include_once(ROOT_DIR.'/function/mysql.php');
    //Connecting to MySQL

    //HTML Dom
    include_once(ROOT_DIR.'/function/html_dom.php');
    //Multiget CURL
    include_once(ROOT_DIR.'/function/curl.php');
    include_once(ROOT_DIR.'/function/mcurl.php');

    // Include Module functions
    include_once(ROOT_DIR.'/function/rating.php');
    include_once(ROOT_DIR.'/function/func.php');
    include_once(ROOT_DIR.'/function/func_main.php');
    include_once(ROOT_DIR.'/function/oldfunc.php');
    include_once(ROOT_DIR.'/function/func_get.php');
    include_once(ROOT_DIR.'/function/func_gk.php');

    // Including main config files
    include_once(ROOT_DIR.'/function/config.php');
    include_once(ROOT_DIR.'/config/config_'.$config['server'].'.php');

    //Loding language pack
    foreach(scandir(ROOT_DIR.'/translate/') as $files){
        if (preg_match ("/_".$config['lang'].".php/", $files)){
            include_once(ROOT_DIR.'/translate/'.$files);
        }
    }

    $poss = array();
    if(is_valid_url($config['td']) == true){
        $poss = get_clan_province($config,$config['clan']);
    }

    //include_once(ROOT_DIR.'/views/header.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <?php if (!isset($config['theme'])) {
            $config['theme'] = 'ui-lightness'; } ?>
        <link rel="stylesheet" href="./theme/<?=$config['theme']; ?>/jquery-ui.css" type="text/css" media="print, projection, screen" />
        <link rel="stylesheet" href="./theme/style.css" type="text/css" media="print, projection, screen" />
        <script type="text/javascript" src="./js/jquery.js"></script>
        <script type="text/javascript" src="./js/jquery.metadata.js"></script>
        <script type="text/javascript" src="./js/jquery.tablesorter.js"></script>
        <script type="text/javascript" src="./js/jquery.tablesorter.widgets.js"></script>        
        <script type="text/javascript" id="js">
           $(document).ready(function()
           {
               $("#poss").tablesorter({
                  headerTemplate : "<div style=\'padding: 0px; padding-right:12px;\'>{content}</div>{icon}",
                  widgets: ["uitheme", "zebra"],
                  widthFixed : false,
                  sortList:[[1,0]],
                  widgetOptions: {uitheme : "jui"}
               });
           });
        </script>
    </head>
    <body>
<div align="center">
    <table id="poss" cellspacing="1" cellpadding="2" width="100%">
        <thead> 
            <tr>
                <th width="40"><?=$lang['type']; ?></th>
                <th><?=$lang['title_name']; ?></th>
                <th><?=$lang['map']; ?></th>
                <th><?=$lang['prime_time']; ?></th>
                <th><?=$lang['income']; ?></th>
                <th><?=$lang['owned']; ?></th>

            </tr> 
        </thead> 
        <tbody>
            <?php if (isset($poss['request_data'])) {
                    $total = 0;
                    foreach($poss['request_data']['items']  as $val){ 
                        if(strlen($val['attacked']) > 0){
                            $attack = '<img src="./images/attacked.png">';
                        }elseif(strlen($val['combats_running']) > 0){
                            $attack = '<img src="./images/combats_running.png">';
                        }else{
                            $attack = '';
                        }
                        $total += $val['revenue'];
            ?>
                <tr>
                    <td><img src="./images/<?php echo $val['type']; ?>.png"></td>
                    <td><a href="<?php echo $config['clan_link']; ?>maps/?province=<?php echo $val['id']; ?>" target="_blank"><?php echo $val['name'].'</a> '.$attack; ?></td>
                    <td><?php echo $val['arena_name']; ?></td>
                    <td align="center"><?php echo date('H:i',$val['prime_time']); ?></td>
                    <td align="center" style="color: #ba904d;"><?php echo $val['revenue']; ?> <img src="./images/currency-gold.png"></td>
                    <td align="center"><?php echo $val['occupancy_time']; echo $lang['days'];?> </td>
                </tr>
                <?php }
             if ((isset($poss['request_data']) && (count($poss['request_data']['items']) == 0 )) || !(isset($poss['request_data']))) {  ?>
                <tr>
                    <td colspan="6" align="Center"><?php echo $lang['no_province'];?></td>
                </tr>
             <?php }} else { ?>
                <tr>
                    <td colspan="6" align="Center"><?=$lang['error_1']?></td>
                </tr>
                <?php }; ?>
        </tbody>
    </table>
    <?php if (isset($total)) {
             if ($total != 0 ) { ?>
             <p><?=$lang['total_gold'].': <span style="color: #ba904d;">'.$total.'</span>'; ?> <img src="./images/currency-gold.png"></p>
    <?php    };
          }; ?>
                </div>
                    </body>
</html>