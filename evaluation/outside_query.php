<?php
/*
$_POST = array(
	'key' => 'abcdefg1234567',
	'dept' => 'ENGW',
	'function' => 'get_dept_data',
	'startdate' => '2012-10-10 11:11:11',
	'enddate' 	=> '2013-05-10 11:11:11',
);
*/
$key = 'abcdefg1234567';
if (!ereg($key, $_POST['key'])){
  echo "Invalid referer "; 
  die;
}

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

switch($_POST['function']) {
	case 'get_dept_data':
		echo get_dept_data();
		break;
	case 'get_evaluations':
		echo get_evaluations();
		break;
}

function get_dept_data() {
	$depts = get_depts();
	foreach($depts as &$dept) {
		$dept->eval_sections = get_sections($dept->id);
		foreach($dept->eval_sections as &$section) {
			$section->questions = get_questions($section->id);
		}
	}
	return serialize($depts);
}

function get_evaluations() {
	//***********
	//GET Evaluations
	global $wpdb;
	$eval_table_name = "wp_seufolios_evaluations"; 
	$depts = get_all_depts();

	$query_dept = $_POST['dept'];
	foreach($depts as $dept) 
		if ($query_dept == $dept->abbr) { $query_dept_id = $dept->id; }


	//setup dates for sql format
	$startdate = $_POST['startdate'];
	$enddate = $_POST['enddate'];

	//***sql query for evals
	$evals_o = $wpdb->get_results( "SELECT * FROM $eval_table_name WHERE submittime > '$startdate' AND submittime < '$enddate' AND answers != '' ORDER BY id");

	//filter for dept, add to array
	foreach ( $evals_o as $eval_o ) 
	{
		$eval_o->taxonomies = unserialize($eval_o->taxonomies);
		$major = get_major($eval_o->studentid);
		foreach($depts as $dept) {
			if ($major == $dept->id) $major_abbr = $dept->abbr;
		}
	
		if($major_abbr == $query_dept) $evals[] = get_object_vars($eval_o); 
	}

	//filter for 1 eval per prof per student
	$evals = super_unique($evals, 'profid', 'studentid');

	//convert user_ids to names
	for ($i=0; $i<count($evals); $i++) {
		//change profid
		$user = get_userdata($evals[$i]['profid']);
		if ($user->user_lastname && $user->user_firstname) {
			$evals[$i]['profid'] = $user->user_firstname .' ' .$user->user_lastname;
		} else $evals[$i]['profid'] = $user->user_email;
	
		//change studentid
		$user = get_userdata($evals[$i]['studentid']);
		if ($user->user_lastname && $user->user_firstname) {
			$evals[$i]['studentid'] = $user->user_firstname .' ' .$user->user_lastname;
		} else $evals[$i]['studentid'] = $user->user_email;
	}

	//***sql query for eval questions

	//sql query for eval sections from dept
	$sections_table_name = "wp_seufolios_eval_sections"; 
	$sql = "SELECT * FROM $sections_table_name WHERE dept_id = $query_dept_id ORDER BY order_loc ASC";
	$sections = $wpdb->get_results($sql);

	//sql query for eval questions from sections
	$questions_table_name = "wp_seufolios_eval_questions"; 
	foreach($sections as $section) {
		$sql = "SELECT * FROM $questions_table_name WHERE section_id = $section->id ORDER BY order_loc ASC";
		$questions[$section->id] = $wpdb->get_results($sql);
	}

	//***return results
	$result = serialize( array( 'sections' => $sections, 'questions' => $questions, 'taxes' => $all_taxonomies, 'evals' => $evals) );
	return $result;
	//$printable = unserialize($result);
	//print_r($printable);
}



//***functions
//generic function to return user's major
function get_major($user_id) {
  $user_blogs = get_blogs_of_user($user_id); //!!!!! hard coded to users's 1st blog. Need to add blog id to eval data

  $blog1 = array_shift($user_blogs); //gets first blog in list
  $major = get_blog_option($blog1->userblog_id, 'student_major');
  if($major === false) $major = get_site_option('seu_default_dept_id');
  
  return $major;
}

function get_all_depts() {
	global $wpdb;
	$dept_table_name = $wpdb->base_prefix . 'seufolios_depts';
	 
	$sql = "SELECT * FROM $dept_table_name ORDER BY abbr ASC";
	
	$results = $wpdb->get_results($sql);
	
	return $results;	
}

// screen duplicates out of multidimensional array
function super_unique($array,$key1, $key2) {
	$temp_array = array();
	foreach ($array as $v) {
		foreach($temp_array as $k => $temp) {
			if($v[$key1] == $temp[$key1] && $v[$key2] == $temp[$key2]) {
				unset($temp_array[$k]);
			}
		}
		$temp_array[] = $v;
	}
	
	$array = array_values($temp_array);
	return $array;
}

?>