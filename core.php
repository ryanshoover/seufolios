<?php
//*************************************************
//core and universal functions for SEUFolios plugin
//*************************************************

//set up database version number
global $seufolios_db_version;
$seufolios_db_version = "2.4";

//test for manual activation / initial install of plugin
register_activation_hook( plugins_url("", __FILE__) .'/SEUFolios.php' , 'setup_seufolios_db' );
//test for automatic update
add_action( 'plugins_loaded', 'seufolios_update_db_check' );

function seufolios_update_db_check() {
    global $seufolios_db_version;
    if (get_site_option( 'seufolios_db_version' ) != $seufolios_db_version)
        setup_seufolios_db();
}

function setup_seufolios_db() {
   global $wpdb;
   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   
   global $seufolios_db_version;
   $installed_ver = get_option( "seufolios_db_version" );
   
   if( $installed_ver != $seufolios_db_version ) {
	  //Departments
	  $dept_table_name = $wpdb->base_prefix . "seufolios_depts"; 
	  $sql_dept = "CREATE TABLE $dept_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		abbr CHAR(4) NOT NULL,
		name tinytext NOT NULL,
		UNIQUE KEY id (id)
	  );";
	  dbDelta($sql_dept);
  
	  //Taxonomies	
	  $taxes_table_name = $wpdb->base_prefix . "seufolios_taxes"; 
	  $sql_taxes = "CREATE TABLE $taxes_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		dept_id mediumint(9) NOT NULL,
		taxonomy tinytext NOT NULL,
		term_slug tinytext NOT NULL,
		term_title tinytext NOT NULL,
		UNIQUE KEY id (id)
	  );";
	  dbDelta($sql_taxes);
  
	  //Evaluation Sections	
	  $eval_sections_table_name = $wpdb->base_prefix . "seufolios_eval_sections"; 
	  $sql_eval_sections = "CREATE TABLE $eval_sections_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		dept_id mediumint(9) NOT NULL,
		title tinytext NOT NULL,
		description tinytext,
		order_loc tinyint,
		UNIQUE KEY id (id)
	  );";
	  dbDelta($sql_eval_sections);
	  
	  //Evaluation Questions (subset of sections)
	  $eval_questions_table_name = $wpdb->base_prefix . "seufolios_eval_questions"; 
	  $sql_eval_questions = "CREATE TABLE $eval_questions_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		section_id mediumint(9) NOT NULL,
		slug tinytext NOT NULL,
		question text NOT NULL,
		type mediumint(9) NOT NULL,
		enabled tinyint,
		order_loc tinyint,
		UNIQUE KEY id (id)
	  );";
	  dbDelta($sql_eval_questions);
	  
	  //evaluations table
	  $eval_table_name = $wpdb->base_prefix . "seufolios_evaluations"; 
	  $sql_eval = "CREATE TABLE $eval_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		profid mediumint(9) NOT NULL,
		studentid mediumint(9) NOT NULL,
		siteurl mediumtext,
		answers mediumtext NOT NULL,
		taxonomies mediumtext,
		submittime timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY id (id)
	  );";
	  dbDelta($sql_eval);
	  
	  //Eval question types
	  $eval_table_name = $wpdb->base_prefix . "seufolios_eval_ques_types"; 
	  $sql_eval = "CREATE TABLE $eval_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		slug tinytext NOT NULL,
		displayName tinytext NOT NULL,
		code mediumtext NOT NULL,
		UNIQUE KEY id (id)
	  );";
	  dbDelta($sql_eval);
	  
	  //Help bubble text
	  $help_table_name = $wpdb->base_prefix . "seufolios_help"; 
	  $sql_help = "CREATE TABLE $help_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		help_key TINYTEXT NOT NULL,
		content MEDIUMTEXT NOT NULL,
		UNIQUE KEY id (id)
	  );";
	  dbDelta($sql_help);
	  
	  //Star-favorites table
	  $star_table_name = $wpdb->base_prefix . "seufolios_folios2eval"; 
	  $sql_star = "CREATE TABLE $star_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		blogid mediumint(9) NOT NULL,
		deptid mediumint(9) NOT NULL,
		UNIQUE KEY id (id)
	  );";
	  dbDelta($sql_star);
	   
	  update_option( "seufolios_db_version", $seufolios_db_version );
   }
}


// *** Generic Department functions
function get_depts() {
	global $wpdb;
	$dept_table_name = $wpdb->base_prefix . 'seufolios_depts'; 
	$sql = "SELECT * FROM $dept_table_name ORDER BY abbr ASC";
	$results = $wpdb->get_results($sql);
	return $results;	
}

function get_taxes($dept_id) {
	global $wpdb;
	$taxes_table_name = $wpdb->base_prefix . "seufolios_taxes"; 
	$sql = "SELECT * FROM $taxes_table_name WHERE dept_id = $dept_id ORDER BY taxonomy";
	$results = $wpdb->get_results($sql);
	return $results;
}

function get_courses($dept_id) {
	global $wpdb;
	$courses_table_name = $wpdb->base_prefix . "seufolios_courses"; 
	$sql = "SELECT * FROM $courses_table_name WHERE dept_id = $dept_id ORDER BY number ASC";
	$results = $wpdb->get_results($sql);
	return $results;
}

function get_sections($dept_id) {
	global $wpdb;
	$sections_table_name = $wpdb->base_prefix . "seufolios_eval_sections"; 
	$sql = "SELECT * FROM $sections_table_name WHERE dept_id = $dept_id ORDER BY order_loc ASC";
	$results = $wpdb->get_results($sql);
	return $results;
}

function get_questions($sec_id) {
	global $wpdb;
	$questions_table_name = $wpdb->base_prefix . "seufolios_eval_questions"; 
	$sql = "SELECT * FROM $questions_table_name WHERE section_id = $sec_id ORDER BY order_loc ASC";
	$results = $wpdb->get_results($sql);
	return $results;
}

function get_question_types() {
	global $wpdb;
	$eval_table_name = $wpdb->base_prefix . 'seufolios_eval_ques_types';	 
	$sql = "SELECT * FROM $eval_table_name ORDER BY slug ASC";
	$results = $wpdb->get_results($sql);
	return $results;
}

// *** User roles
//get user's role in a site
function get_the_user_role($user) { 
	global $wpdb, $wp_roles; 
	
	if ( !isset($wp_roles) ) 
		$wp_roles = new WP_Roles(); 
		
	if ($user && current_user_can('manage_network') )
		return 'Super_admin';
	
	if ($user && current_user_can('manage_categories')) //test if user exists (logged in) then if it has admin caps
		return 'Administrator'; //Give admins and superadmins Administrator role
			
	foreach($wp_roles->role_names as $role => $Role) {
	  $caps = $wpdb->base_prefix . 'capabilities'; 
	  if (!empty($user->$caps) && array_key_exists($role, $user->$caps)) {
		  return $Role; 
	  } 
	} 
	$no_role = 'World';
	return $no_role;
} 

//generic function to return user's major when it's the user meta field
function get_the_user_major($user_id) {
	$key = 'major';
	$single = false;
	$major = get_user_meta( $user_id, $key, $single ); 
	return $major[0];
}

//generic function to return user's major when it's the site option
function get_user_major() {    //($user_id) {
	$major = get_option('student_major');
	if($major === false) $major = get_site_option('seu_default_dept_id');
	return $major;
}

?>