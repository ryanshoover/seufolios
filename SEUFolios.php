<?php
/*
Plugin Name: SEU Folios
Description: Adds customization for eportfolio functions
Author: Ryan Hoover
Version: 2.3.2
Author URI: http://ryanhoover.net

Copyright 2013

What's new
- network admin moved to network settings submenu
- reworked network admin screens
- user roles changed to match edublogs roles
- site option only controls end-user features
- modification of WP roles is disabled
- Default WP roles are hidden in the page visibility settings
*/


//used for enable end user features
add_option('enable_seufolios_features', 0); 	//adds the option if it doesn't already exist. Must be overridden in site settings. 0 means no go, 1 means go

//course and doc_type taxonomies
add_action( 'init', 'add_taxonomies' ); 

$plugin_url = (trailingslashit( plugin_dir_path( __FILE__ ) ));
//multi-department features
require_once($plugin_url .'departments.php');

//scribd inclusion
require_once($plugin_url .'scribd_functions.php');

//role setup
require_once($plugin_url .'role_setup.php');

//evaluation features
require_once($plugin_url .'evaluation.php');

//help features
require_once($plugin_url .'help.php');

//post visibility
require_once($plugin_url .'post_visibility.php');

//Add network admin screen for departments
add_action('network_admin_menu', 'setup_network_admin_page');

//enable features for end user, based on site option
if(get_option('enable_seufolios_features') != 0) {
	enable_departments();
	enable_evaluation();
	enable_post_visibility();
	//enable_role_setup(); //taken out 02222013- may be messing up edublogs
	enable_scribd_functions();
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

//call all functions for extra taxonomies
function add_taxonomies () {
	add_course_taxonomy();
	add_engw_doctype_taxonomy();
	add_rats_outcomes_taxonomy();
	//add_reflection_post_type();  //removed unless needed
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



//*****
//Add the course taxonomy
function add_course_taxonomy() {
	// create a new taxonomy
	$labels = array(
		'name' => _x( 'Courses', 'taxonomy general name' ),
		'singular_name' => _x( 'Course', 'taxonomy singular name' ),
		'edit_item' => __( 'Edit Course' ), 
		'update_item' => __( 'Update Course' ),
		'add_new_item' => __( 'Add New Course' ),
		'new_item_name' => __( 'New Course Name' ),
		'separate_items_with_commas' => __( 'Separate courses with commas' ),
		'add_or_remove_items' => __( 'Add or remove courses' ),
		'choose_from_most_used' => __( 'Choose from the most used courses' ),
		'menu_name' => __( 'Courses' ),
		'all_items' =>__( 'All Courses' ),
    ); 
	
	$post_types = array('post', 'page');
	
	register_taxonomy(
		'courses',
		$post_types,
		array(
			'hierarchical' => true,
			'labels' => $labels,
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array('slug' => 'course'),
			
		)
	);
	
}


//*****
//Add the doctype taxonomy
function add_engw_doctype_taxonomy() {
	// create a new taxonomy
	$labels = array(
		'name' => _x( 'Doc Types', 'taxonomy general name' ),
		'singular_name' => _x( 'Doc Type', 'taxonomy singular name' ),
		'edit_item' => __( 'Edit Doc Type' ), 
		'update_item' => __( 'Update Doc Type' ),
		'add_new_item' => __( 'Add New Doc Type' ),
		'new_item_name' => __( 'New Doc Type' ),
		'separate_items_with_commas' => __( 'Separate doc types with commas' ),
		'add_or_remove_items' => __( 'Add or remove doc types' ),
		'choose_from_most_used' => __( 'Choose from the most used doc types' ),
		'menu_name' => __( 'Doc Types' ),
		'all_items' =>__( 'All Doc Types' ),
    ); 
	
	//$post_types = array('post', 'page');
	$post_types = array();
	
	register_taxonomy(
		'engw_doctypes',
		$post_types,
		array(
			'hierarchical' => true,
			'labels' => $labels,
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array('slug' => 'doctype'),
			
		)
	);
	
	
	//set up array with all courses inside it
	$all_doctypes[] = array("id"=>"essay", "title"=>"Class Essay");
	$all_doctypes[] = array("id"=>"resume", "title"=>"Resume");
	$all_doctypes[] = array("id"=>"website", "title"=>"Website");
	$all_doctypes[] = array("id"=>"professional", "title"=>"Professional Document");
	$all_doctypes[] = array("id"=>"poem", "title"=>"Poem");
	$all_doctypes[] = array("id"=>"short_story", "title"=>"Short Story");
	$all_doctypes[] = array("id"=>"novel", "title"=>"Novel");
	$all_doctypes[] = array("id"=>"screen_play", "title"=>"Screen Play");
	$all_doctypes[] = array("id"=>"creative", "title"=>"Creative Piece");

	//loop through the courses array, adding each as a new term
	foreach ($all_doctypes as $a_doctype) {
	wp_insert_term(
	  $a_doctype["id"], // the term 
	  'engw_doctypes', // the taxonomy (hard-coded to courses taxonomy)
	  array(
		'description'=> $a_doctype["title"],
		'slug' => strtolower($a_doctype["id"]),
		//'parent'=> $parent_term_id
	  )
	);
	}
}

function add_rats_outcomes_taxonomy() {
	//Create Learning Outcomes taxonomy
	// create a new taxonomy
	$labels = array(
		'name' => _x( "Student Learning Outcomes", 'taxonomy general name' ),
		'singular_name' => _x( 'Outcomes', 'taxonomy singular name' ),
		'edit_item' => __( 'Edit Learning Outcomes' ), 
		'update_item' => __( 'Update Learning Outcomes' ),
		'add_new_item' => __( 'Add New Learning Outcome' ),
		'new_item_name' => __( 'New Learning Outcome' ),
		'separate_items_with_commas' => __( 'Separate learning outcomes with commas' ),
		'add_or_remove_items' => __( 'Add or remove learning outcomes' ),
		'choose_from_most_used' => __( 'Choose from the most used learning outcomes' ),
		'menu_name' => __( 'Outcomes' ),
		'all_items' =>__( 'All Learning Outcomes' ),
    ); 
	
	$post_types = array(); //array('classroom', 'related_life', 'post'); //disabled - will be added manually to classroom and related_life array('post', 'page');
	
	register_taxonomy(
		'rats_outcomes',
		$post_types,
		array(
			'hierarchical' => true,
			'labels' => $labels,
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array('slug' => 'outcome'),
			
		)
	);
	
	//set up array with all outcomes inside it
	$outcomes[] = array('id'=>'moral_reasoning', 'title'=>'Moral Reasoning');
	$outcomes[] = array('id'=>'world_religion', 'title'=>'World Religions');
	$outcomes[] = array('id'=>'historical', 'title'=>'Historical Understanding');
	$outcomes[] = array('id'=>'biblical', 'title'=>'Biblical Reasoning');
	$outcomes[] = array('id'=>'theological', 'title'=>'Theological Reasoning');
	$outcomes[] = array('id'=>'research', 'title'=>'Advanced Research');
	
	//loop through the outcomes array, adding each as a new term
	foreach ($outcomes as $outcome) {
	wp_insert_term(
	  $outcome["id"], // the term 
	  'rats_outcomes', // the taxonomy
	  array(
		'description'=> $outcome["title"],
		'slug' => strtolower($outcome["id"]),
	  )
	);
	}
}



?>