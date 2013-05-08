<?php

$wp_path = urldecode($_GET['wp_path']);
//load WP features
include_once($wp_path .'/wp-config.php');
include_once($wp_path .'/wp-load.php');
include_once($wp_path .'/wp-includes/wp-db.php');

$profid = $_GET['profid'];
$studentid = $_GET['studentid'];
global $wpdb;
$eval_table_name = "wp_seufolios_evaluations"; 

if( isset($_GET['deleteIcon']) ) {
	$results = $wpdb->query( $wpdb->prepare("DELETE FROM $eval_table_name WHERE profid = $profid AND studentid = $studentid" ) );
	if($results) echo 'Deleted';
	else echo "Oops. Something went wrong.<br>$results";
	die();
}
if( isset($_GET['starIcon']) ) {
	$table_name = "wp_seufolios_starred";
	$blogurl = urldecode($_GET['blogurl']);
	$deptid = $_GET['deptid'];
	
	//create record if it doesn't exist
	$exists = $wpdb->get_var( $wpdb->prepare("SELECT id FROM $table_name WHERE blogurl='$blogurl' AND deptid=$deptid"));
	if(!$exists) $exists = $wpdb->insert( $table_name, array('blogurl'=>$blogurl, 'deptid'=>$deptid ) ); 
	
	if($_GET['starIcon']) {
	//add profid to list
		$profidsS = $wpdb->get_results("SELECT profids FROM $table_name WHERE blogurl='$blogurl' AND deptid=$deptid");
		if($profidsS) $profids = unserialize($profidsS[0]->profids);
		
		if( !$profidsS || !in_array($profid, $profids) ) {
			$profids[] = $profid;
			$profidsS = serialize($profids);
			$results = $wpdb->query( $wpdb->prepare("UPDATE $table_name SET profids='$profidsS' WHERE blogurl='$blogurl' AND deptid=$deptid" ) );
		}
		
		if($results) echo 'Starred';
		else echo "Oops. Something went wrong. \n$results";
		die();
	} else {
	//remove profid from list
		$profidsS = $wpdb->get_results("SELECT profids FROM $table_name WHERE blogurl='$blogurl' AND deptid=$deptid");
		if($profidsS) $profids = unserialize($profidsS[0]->profids);
		
		if( $profidsS && ($key = array_search($profid, $profids)) !== false) {
			unset($profids[$key]);
			$profidsS = serialize($profids);
			$results = $wpdb->query( $wpdb->prepare("UPDATE $table_name SET profids='$profidsS' WHERE blogurl='$blogurl' AND deptid=$deptid" ) );
		}
		
		if($results) echo 'Unstarred';
		else echo "Oops. Something went wrong. \n$results";
		die();
	}
}

$stringData = $_GET;
unset($stringData['profid'], $stringData['studentid'], $stringData['wp_path']);
$string = http_build_query($stringData);

$results = $wpdb->update( $eval_table_name, array('answers'=>$string), array('profid'=>$profid, 'studentid'=>$studentid) );

if($results) echo 'Saved';
else echo "Oops. Something went wrong. \n$results";
die();
?>