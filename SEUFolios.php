<?php
/*
Plugin Name: SEU Folios
Description: Adds customization for eportfolio functions
Author: Ryan Hoover
Version: 2.2
Author URI: http://ryanhoover.net

Copyright 2013

What's new
- streamlined evaluation system
*/

//***Action hooks

//Enable whole plugin
add_option('enable_seufolios_features', 1); 				//adds the option if it doesn't already exist. Must be overridden in site settings. 0 means no go, 1 means go
if(get_option('enable_seufolios_features') != 0) {

	//signup disclaimer
	add_action('signup_extra_fields', 'add_disclaimer');
	//add_filter('wpmu_validate_user_signup', 'check_fields');
	
	$plugin_url = (trailingslashit( plugin_dir_path( __FILE__ ) ));
	//core functions, database setup
	require_once($plugin_url .'core.php');
	
	//multi-department features
	require_once($plugin_url .'departments.php');

	//scribd inclusion
	require_once($plugin_url .'scribd_functions.php');

	//pdf-viewer features
	require_once($plugin_url .'pdf-viewer.php');
	
	//role setup
	require_once($plugin_url .'role_setup.php');
	
	//evaluation features
	require_once($plugin_url .'evaluation.php');
	
	//help features
	require_once($plugin_url .'help.php');
	
	//post visibility
	require_once($plugin_url .'post_visibility.php');

} //end if get_option

//***Functions

//test for stedwards.edu email
/* commented out - not needed in LDAP setup
function check_fields($result) {
	$errors = new WP_Error();
	$user_email_explode = explode('@', $result['user_email']);
	 if( !preg_match("/stedwards.edu/i", $user_email_explode[1]) ) {
		$result['errors']->add('user_email', '<strong>ERROR</strong>: You have to use a stedwards.edu email address to create an account.');
	 }
	 return $result;
}
*/

function add_disclaimer() {
$output = '<p style="font-size:0.9em;margin-left:2em;">*Note, you <em>must</em> use a stedwards.edu email address to register.<br />You can change it after you\'ve set up your account.</p>';
echo $output;
	
}


//*****
//Create custom post type for reflection + documents
//***Not in use***
function add_reflection_post_type() {
	$reflection_taxonomies = array('courses', 'category', 'post_tag');
	
	register_post_type( 'seu_reflection',
		array(
			'labels' => array(
				'name' => __( 'Reflections' ),
				'singular_name' => __( 'Reflection' ),
				'add_new_item' => __( 'Add New Reflection' ),
			),
		'public' => true,
		'has_archive' => true,
		'menu_position' => 5,
		'taxonomies' => $reflection_taxonomies,
		)
	);
}

?>