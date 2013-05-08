<?php
/*
$_POST = array(
	'key' => 'abcdefg1234567',
	'dept' => 'ENGW',
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

//$user = wp_get_current_user();
//global $blog_id;

//************
//Get all Taxonomies
//!!!!!THIS IS BROKEN!!!!!
//Loop through all posts/pages/etc
/*
$args = array( 'author' => $_POST['studentid'], 'post_type' => 'any' );
$loop = new WP_Query( $args );
while ( $loop->have_posts() ) : $loop->the_post();
	$post_taxs = wp_get_post_terms( get_the_ID(), $taxs );
	$this_tax = array();
	foreach($post_taxs as $tax) {
		$this_tax[] = array('post_id'=>get_the_ID(), 'taxonomy'=>$tax->taxonomy, 'name'=>$tax->name); 	
	}
	$all_taxonomies[] = $this_tax;
endwhile;
//print_r($all_taxonomies);
*/

//***********
//GET Evaluations
global $wpdb;
$eval_table_name = "wp_seufolios_evaluations"; 
$depts = get_all_depts();
	

$query_dept = $_POST['dept'];
foreach($depts as $dept) {
	if ($query_dept == $dept->abbr) {$test['dept_abbr'] = $dept->abbr; $test['dept_id'] = $dept->id; $query_dept_id = $dept->id;}
}

//setup dates for sql format
$startdate = $_POST['startdate'];
$enddate = $_POST['enddate'];

//***sql query for evals
$evals_o = $wpdb->get_results( 
	"
	SELECT *
	FROM $eval_table_name

	WHERE submittime > '$startdate' 
		AND submittime < '$enddate'
		
	ORDER BY id
	"
);

//filter for dept, add to array
foreach ( $evals_o as $eval_o ) 
{
	$eval_o->taxonomies = unserialize($eval_o->taxonomies);
	$major = get_major($eval_o->studentid);
	$test['major'] = $major;
	foreach($depts as $dept) {
		if ($major == $dept->id) { $major_abbr = $dept->abbr; }
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
echo $result;
//$printable = unserialize($result);
//print_r($printable);








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
	$dept_table_name = 'wp_seufolios_depts';  //disabled because prefix changes in multisite $wpdb->prefix . "seufolios_depts";
	 
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