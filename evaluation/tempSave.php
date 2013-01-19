<?php

function seu_get_wp_config_path()
{
    $base = dirname(__FILE__);
   
	if (@file_exists($base."/wp-config.php"))
    {
        return $base;
    } 
	
	while($base != '/') {
		$base = dirname($base);
		if (@file_exists($base."/wp-config.php"))
		  {
			  return $base;
		  }
	}
	
	return false;
}
$config_path = seu_get_wp_config_path();

//load WP features
include_once($config_path .'/wp-config.php');
include_once($config_path .'/wp-load.php');
include_once($config_path .'/wp-includes/wp-db.php');

$stringData = '';
$profid = $_GET['profid'];
$studentid = $_GET['studentid'];
$siteurl = $_GET['siteurl'];

//create answers array
foreach($_GET as $key => $value) {
	//move $_GET values to array
	$key = str_replace('_input', '', $key);
	
	if ($key != 'profid' && $key != 'studentid' && $key != 'siteurl' && $key != 'terms') {
		$stringData .= "$key=$value&";
	}
}
$stringData = substr($stringData,0,-1);

//create terms array
$terms_temp = explode("~", $_GET['terms']);
$terms = '';
foreach($terms_temp as $t) {
	list($key, $val) = explode(':', $t);
	$terms .= "$key=$val&";
}
$terms = substr($terms,0,-1);
if(strlen($terms) == 1) $terms = ''; //if no terms load, make the string empty

global $wpdb;
$eval_table_name = "wp_seufolios_evaluations"; 

//$results = $wpdb->update( $eval_table_name, array('answers'=>$stringData, 'submittime' =>time()), array('profid'=>$profid, 'studentid'=>$studentid) );
//if(!$results) $results2 = $wpdb->insert( $eval_table_name, array('profid'=>$profid, 'studentid'=>$studentid, 'answers'=>$stringData, 'submittime' =>time()) ); 
$results = $wpdb->update( $eval_table_name, array('answers'=>$stringData), array('profid'=>$profid, 'studentid'=>$studentid) );
if(!$results) $results2 = $wpdb->insert( $eval_table_name, array('profid'=>$profid, 'studentid'=>$studentid, 'answers'=>$stringData, 'siteurl'=>$siteurl, 'taxonomies'=>$terms) ); 

if($results || $results2) echo 'Saved';
else echo "Oops. Something went wrong. \n$results\n$results2";

?>