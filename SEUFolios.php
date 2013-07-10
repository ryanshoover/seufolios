<?php
/*
Plugin Name: SEU Folios
Description: Adds customization for eportfolio functions
Author: Ryan Hoover
Version: 2.5
Author URI: http://ryanhoover.net

Copyright 2013

What's new
- network admin moved back to multiple pages
- streamlined evaluation system
- flexible custom taxonomy and post type
- pdf viewer using pdf.js
*/

//used for enable end user features
add_option('enable_seufolios_features', 0); 	//adds the option if it doesn't already exist. Must be overridden in site settings. 0 means no go, 1 means go

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


//Add network admin screen for departments *under settings*
//add_action('network_admin_menu', 'setup_network_admin_page');

//enable features for end user, based on site option
if(get_option('enable_seufolios_features') != 0) {
	enable_departments();
	enable_evaluation();
	enable_post_visibility();
	//enable_role_setup(); //taken out 02222013- may be messing up edublogs
} //end if get_option


//***Functions
//universal admin page based in Network Settings
function setup_network_admin_page() {
	
	add_submenu_page(
       'settings.php',
       'SEUFolios Settings',
       'SEUFolios',
       'manage_network_options',
       'seufolios_settings',
       'seufolios_admin_page'
  );
  
}

function seufolios_admin_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	wp_enqueue_script('jquery');
	?>
    <style>
		.major_div {display:none; width:90%;}
	</style>
    <div class="wrap">
    <h2>Pick a section to manage</h2>
    <form name="major_options" id="major_options">
    	<select name="change_major_options" id="change_major_options">
        	<option selected>--Choose a section--</option>
            <option value="all_depts">All Departments</option>
            <option value="ind_dept">Individual Department</option>
            <option value="eval_questions">Evaluation Questions</option>
            <option value="help_fields">Help Fields</option>
        </select>
    </form>
    </div>
    
    <div id="all_depts" class="major_div"><?php control_dept_options(); ?></div>
    <div id="ind_dept" class="major_div"><?php control_course_list(); control_evaluation_questions(); ?></div>
    <div id="eval_questions" class="major_div"><?php control_eval_ques_options(); ?></div>
    <div id="help_fields" class="major_div"><?php control_help_fields(); ?></div>
    
    <script>
	(function($) {
		$('#change_major_options').change(function() {
		   jQuery('.major_div').hide();
		   jQuery("#" + jQuery(this).attr('value') ).show();
		});
	})( jQuery );
	</script>
    
	<?php
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