<?php
$wp_path = urldecode($_GET['wp_path']);
//load WP features
include_once($wp_path .'/wp-config.php');
include_once($wp_path .'/wp-load.php');
include_once($wp_path .'/wp-includes/wp-db.php');

$profid = $_GET['profid'];
$studentid = $_GET['studentid'];

$stringData = $_GET;
unset($stringData['profid'], $stringData['studentid']);
$string = http_build_query($stringData);

global $wpdb;
$eval_table_name = "wp_seufolios_evaluations"; 

$results = $wpdb->update( $eval_table_name, array('answers'=>$string), array('profid'=>$profid, 'studentid'=>$studentid) );

if($results) echo 'Saved';
else echo "Oops. Something went wrong. \n$results";

?>