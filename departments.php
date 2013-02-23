<?php
//*****
//Contains functions that create department-specific features
//Features include student profile modifications, course taxonomy terms
//*****


//setup depts
//include other classes
require_once('classes/wp-admin-menu-classes.php'); //allows us to modify the order of admin menus

//Setup departments sql table
$core_plugin_url = (trailingslashit( plugin_dir_path( __FILE__ ) )) .'SEUFolios.php';
register_activation_hook( $core_plugin_url, 'create_dept_sqltable' );

//Add network admin screen for departments
//Setup department courses settings pages
add_action('wp_ajax_add_dept_submit', 'add_dept_save_ajax' );
add_action('wp_ajax_add_course_select_dept', 'add_course_deptselect');
add_action('wp_ajax_add_course_submit', 'add_course_save_ajax');
add_action('wp_ajax_add_course_delete', 'add_course_delete_ajax');
add_action('wp_ajax_set_default_dept', 'set_default_dept_ajax' );

//turn on single user features
function enable_departments() {
	//***WP Hooks
	//Add user profile w WP hooks
	//add_action('signup_extra_fields', 'add_custom_signup_fields'); //can't get it to work, not needed in edublogs
	add_action( 'show_user_profile', 'add_custom_user_profile_fields' );
	add_action( 'edit_user_profile', 'add_custom_user_profile_fields' );
	add_action( 'personal_options_update', 'save_custom_user_profile_fields' );
	add_action( 'edit_user_profile_update', 'save_custom_user_profile_fields' );
	
	//Hook for adding custom post and taxonomies
	add_action('init', 'setup_custom_tax');
	add_filter( 'wp_page_menu', 'custom_page_menu' ,10,2 ); //adds custom posts to nav menu
	
	//adds help bubble for taxonomy meta-block
	add_action( "admin_head-post-new.php", 'meta_box_instruction' ); //new post
	add_action( "admin_head-post.php", 'meta_box_instruction' );    //edit post
	
	//Setup department evaluation settings pages ajax
	add_action('wp_ajax_eval_select_dept', 'eval_select_dept');
	add_action('wp_ajax_eval_add_section', 'eval_add_section');
	add_action('wp_ajax_eval_edit_section', 'eval_edit_section');
	add_action('wp_ajax_eval_delete_section', 'eval_delete_section');
	add_action('wp_ajax_eval_show_questions', 'eval_show_questions');
	add_action('wp_ajax_eval_add_question', 'eval_add_question');
	add_action('wp_ajax_eval_edit_question', 'eval_edit_question');
	add_action('wp_ajax_eval_delete_question', 'eval_delete_question');

}

//***Functions

//generic function to return user's major
function get_user_major() {    //($user_id) {
  /* old system, moved to site option
  $key = 'major';
  $single = false;
  $major = get_user_meta( $user_id, $key, $single ); 
  return $major[0];
  */
  $major = get_option('student_major');
  if($major === false) $major = get_site_option('seu_default_dept_id');
  
  return $major;
  
}

//function to unregister a custom post type
//not used yet = could be used for RAGS as they have custom post types
function unregister_post_type( $post_type, $slug = '' ){

	global $wp_post_types;

	if ( isset( $wp_post_types[ $post_type ] ) ) {
            unset( $wp_post_types[ $post_type ] );
        	
            $slug = ( !$slug ) ? 'edit.php?post_type=' . $post_type : $slug;
            remove_menu_page( $slug );
	}
}

//add in custom user profile fields in profile page
function add_custom_user_profile_fields( $user ) {
	global $wpdb;
	//$wpdb->flush();
	
	$major = get_user_major(); //get_user_major($user->id);
	$depts = get_depts();
	
?>
	<h3><?php _e('Student Information', 'your_textdomain'); ?></h3>
	<table class="form-table">
		<tr>
			<th>
			<label for="major"><?php _e('Major', 'your_textdomain'); ?>
			</label></th>
			<td>
            	<select name="major" id="major">
                	<option>---</option>
                <?php
					foreach ($depts as $dept) {
						if($major == $dept->id) echo "<option value='$dept->id' selected='selected'>$dept->name</option>";
						else                    echo "<option value='$dept->id'>$dept->name</option>";	
					}
				?>
                </select>
				<span class="description"><?php _e('Select your major.', 'your_textdomain'); insert_help('choose_major'); ?></span>
			</td>
		</tr>
	</table>
<?php }

//save custom user profile fields
function save_custom_user_profile_fields( $user_id ) {
	if ( !current_user_can( 'edit_user', $user_id ) ) return FALSE;
	
	//change user meta field
	update_usermeta( $user_id, 'major', $_POST['major'] ); //included for professors so we can add them into blogs as users - they have to be a usermeta since they don't have portfolios
	update_option('student_major', $_POST['major']);
	
	replace_course_terms($user_id);
	setup_custom_tax();
	
	//used to enable new post types
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

//add in custom user profile fields in signup page
function add_custom_signup_fields() {
	/*commented out - can't figure out how to save fields on registration submit
	$major = get_user_major($user->id);
	$depts = get_depts();
?>
	<label for="major"><?php _e('Major', 'your_textdomain'); ?></label>
    <select name="major" id="major">
	<?php
        foreach ($depts as $dept) {
            if($major == $dept->id) echo "<option value='$dept->id' selected='selected'>$dept->name</option>";
            else                    echo "<option value='$dept->id'>$dept->name</option>";	
        }
    ?>
    </select>
    <span class="description"><?php _e('Select your major.', 'your_textdomain'); ?></span>

<?php 
	*/
}

//function to rewrite course taxonomy terms based on major
function replace_course_terms($user_id) {
	
	$major = get_user_major(); //replaced by site option($user_id);
	
	//delete ALL course taxonomy terms
	$courses_terms = get_terms( 'courses', array('hide_empty'=>'0') );
	
	foreach($courses_terms as $term) {
		wp_delete_term( $term->term_id, 'courses' );
	}
	
	$courses = get_courses($major);
	
	//loop through the $all_courses array, adding each as a new term
	foreach ($courses as $a_course) {
		wp_insert_term(
		  $a_course->number, // the term 
		  'courses', // the taxonomy (hard-coded to courses taxonomy)
		  array(
			'description'=> $a_course->title,
			'slug' => strtolower($a_course->number)
			//'parent'=> $parent_term_id
		  )
		);
	}

}

function setup_custom_tax() {
	$user_id = get_current_user_id();
	$major = get_user_major(); //($user_id);
	$depts = get_depts();
	foreach($depts as $dept) {
		if ($major == $dept->id) $major_abbr = $dept->abbr;
	}

	switch ($major_abbr) {
		//RATS
		case 'RATS': 
			setup_rats_student();	
			break;
		
		//ENGW
		case 'ENGW':
			register_taxonomy_for_object_type('engw_doctypes', 'post' );
			register_taxonomy_for_object_type('engw_doctypes', 'page' );
			break;
	}

}

function setup_rats_student() {
//adds in features that are custom for RATS students
	
	//Create Personal Statements post type
	register_post_type( 'personal_statement',
		array('labels' => array('name' => __( 'Personal Statements' ),'singular_name' => __( 'Personal Statement' ), 'add_new_item' => __('Add new Personal Statement') ),
		'description' => 'Your written statement of who you are academically.',
		'public' => true,
		'has_archive' => true,
		'menu_position' => 6,
		'capability_type' => 'page',
		'show_in_nav_menus' => true,
		'hierarchical' => true
		)
	);
	//Create Classroom Experiences post type
	register_post_type( 'classroom',
		array('labels' => array('name' => __( 'Classroom Experiences' ), 'singular_name' => __( 'Classroom Experience' ), 'add_new_item' => __('Add new Classroom Experience') ),
		'description' => 'A post that describes what you learned in a particular class',
		'public' => true,
		'has_archive' => true,
		'menu_position' => 7,
		'capability_type' => 'post',
		'show_in_nav_menus' => true,
		'taxonomies' => array('rats_outcomes', 'courses'),
		'hierarchical' => false
		)
	);
	//Create Related Life Experiences post type
	register_post_type( 'related_life',
		array('labels' => array('name' => __( 'Related Life Experiences' ),'singular_name' => __( 'Related Experience' ), 'add_new_item' => __('Add new Life Experience') ),
		'description' => 'A post that describes a life experience you had that is related to your learning. An example would be a school-sponsored mission trip.',
		'public' => true,
		'has_archive' => true,
		'menu_position' => 8,
		'capability_type' => 'post',
		'show_in_nav_menus' => true,
		'taxonomies' => array('rats_outcomes'),
		'hierarchical' => false
		)
	);
	//Create Resume post type
	register_post_type( 'resume',
		array('labels' => array('name' => __( 'Resumes' ),'singular_name' => __( 'Resume' ), 'add_new_item' => __('Add new Resume') ),
		'description' => 'Your current resume or curriculum vita.',
		'public' => true,
		'has_archive' => true,
		'menu_position' => 5,
		'capability_type' => 'page',
		'show_in_nav_menus' => true,
		'hierarchical' => true
		)
	);

	
	//order the admin menus
	add_action('admin_menu','order_admin_menu');
	//-> this goes in the function : swap_admin_menu_sections('Pages', 'Posts');
	

	//sets up the default content for the 2 custom post types
	add_filter( 'default_content', 'rats_default_content', 10, 2 );	
	
}

function order_admin_menu() {
//reorders the left menu in admin screen for the new post types
	global $menu;
	swap_admin_menu_sections('Posts', 'Resumes');
}

function custom_page_menu($menu, $args) {
//adds the custom posts to the default nav menu
	// get supplied args
	$list_args = $args;
	
	// Overide some menu settings
	$list_args['echo'] = false;
	$list_args['title_li'] = '';
	$list_args['show_home'] = false;
	$list_args['exclude'] = 4; // excluding the homepage as I am manually adding it to the start below
	
	// get the current page object as we will need to refer to it when setting current items below
	global $wp_query;
	$current_page = $wp_query->get_queried_object();  
	
	// Show Home item at the start of the menu
	$menu .= '<li ' . $class . '><a href="' . home_url( '/' ) . '" title="' . esc_attr(__('Home')) . '">' . $args['link_before'] . __('Home') . $args['link_after'] . '</a></li>';
	
	// Now add the normal page collection which belongs in the nav
	$list_args['post_type'] = 'page';
	$menu .= wp_list_pages($list_args) ;
	
	// Loop through the custom post types HIERARCHICAL and add them to the menu
	$pages_args = array('public'=>true, '_builtin'=>false, 'hierarchical'=>true);
	$pages = get_post_types($pages_args, 'objects');
	//sort the pages array by menu_order
	$sorter=array();
    $ret=array();
    reset($pages);
    foreach ($pages as $page) {
        $sorter[$page->rewrite['slug']] = $page->menu_position;
    }
    asort($sorter);
	foreach ($sorter as $ii => $va) {
		foreach ($pages as $pt) {
        	if($pt->rewrite['slug'] == $ii) $ret[]=$pt;
		}
    }
    $pages=$ret;
	
	foreach($pages as $obj_pt) {
		$list_args['post_type'] = $obj_pt->rewrite['slug'];
		$menu .= wp_list_pages($list_args); //this does not allow ordering of menu items - it goes by date descending
	}
	
	// Loop through the custom post types NOT HIERARCHICAL and add them to the menu
	$posts_args = array('public'=>true, '_builtin'=>false, 'hierarchical'=>false);
	$posts = get_post_types($posts_args, 'objects');
	//sort the posts array by menu_order
	$sorter=array();
    $ret=array();
    reset($posts);
    foreach ($posts as $post) {
        $sorter[$post->rewrite['slug']] = $post->menu_position;
    }
    asort($sorter);
	foreach ($sorter as $ii => $va) {
		foreach ($posts as $pt) {
        	if($pt->rewrite['slug'] == $ii) $ret[]=$pt;
		}
    }
    $posts=$ret;
	
	foreach($posts as $obj_pt) {
		$submenu = '';
		$list_args['post_type'] = $obj_pt->rewrite['slug'];
		$the_query = new WP_Query( 'post_type='.$obj_pt->rewrite['slug'] );
		
		//show main link if there are any posts of that type
		if ($the_query->have_posts() )
			$menu .= '<li><a href="' .home_url( '/' )  . $obj_pt->rewrite['slug'] . '/">' . $obj_pt->labels->name . '</a><ul>';
		// The Loop - add in all posts of that type
		while ( $the_query->have_posts() ) : $the_query->the_post();
			$submenu .= '<li><a href="' .get_permalink() .'">' .get_the_title() .'</a></li>';
		endwhile;
		$menu .= $submenu;
		// Reset Post Data
		wp_reset_postdata();
		$menu .= '</ul></li>';
	}
	
		
	// glue the menu together and send back
	if ( $menu )
	$menu = '<ul>' . $menu . '</ul>';
	
	$menu = '<div>' . $menu . "</div>\n";
	
	return $menu;
}

function rats_default_content( $content, $post ) {
//sets up the default content for the custom RATS post types
    switch( $post->post_type ) {
		case 'resume':
            $content = "<p>Include your current resume or curriculum vita on this page.</p>
						<p>You can either type your resume into this textbox as an html document or insert a Word/PDF version using Scribd.
						<br>If you want to insert a Word or PDF version, use the <em>Upload/Insert -> From Scribd</em> feature directly above this textbox.</p>";
        	break;
		case 'personal_statement':
            $content = "<p>At the minimum, you must include the personal statement you write as a Senior. Either copy and paste the content or use the <em>Upload/Insert -> From Scribd</em> feature directly <em>above</em> this textbox.</p>
						<p>You may also want to include the personal statement that you wrote as a Freshman in the Methods course.</p>
						<p>You should probably only show this page to professors. <strong>Deny</strong> the World and <strong>Allow</strong> professors in the radio buttons directly <em>below</em> this post.";
        	break;
        case 'classroom':
            $content = "Course title: <br>
						Instructor: <br>
						School: <br>
						Semester: <hr>
						<p>Describe how this course fulfilled the learning outcomes you marked in the box to the right of this textbox.  In your response, be sure to include a brief description of the course, its major units, and how performance was measured (e.g. exams, papers, presentations).<br>(Be sure to mark the learning outcomes you fulfilled in the box to the right of this textbox.)</p>
						<p>Include any papers, tests, and other documents that demonstrate what you learned in this class. Insert these documents using the <em>Upload/Insert -> From Scribd</em> feature directly above this textbox.</p>";
        	break;
        case 'related_life':
            $content = "Concise description of the activity:<br>
						Dates<br>
						From: &nbsp;&nbsp;&nbsp;&nbsp;To: <hr>
						<p>Describe how this experience fulfilled the learning outcomes you marked in the box to the right of this textbox.  In your response, be sure to include a brief description of your primary responsibilities or focus.<br>(Be sure to mark the learning outcomes you fulfilled in the box to the right of this textbox.)</p>
						<p>Include any documents or evidence that demonstrate what you learned in this class. Insert these documents using the <em>Upload/Insert -> From Scribd</em> feature directly above this textbox.</p>";
        	break;
    }

    return $content;
}

function meta_box_instruction($d) { 
//adds help bubble to meta-boxes in new post pages
   global $post;
   foreach(get_object_taxonomies($post->post_type) as $tax) {
	   switch ($tax) {
	   case 'rats_outcomes': 
			$help_key = 'rats_outcomes';
			$help = return_help($help_key);
			echo "<script type='text/javascript'>
				  jQuery(document).ready(function(){
					  jQuery('#rats_outcomes-tabs').append(\"" .$help ."\");
				  });</script>";
			break;
		case 'courses':
			$help_key = 'mark_courses';
			$help = return_help($help_key);
			echo "<script type='text/javascript'>
				  jQuery(document).ready(function(){
					  jQuery('#courses-tabs').append(\"" .$help ."\");
				  });</script>";
			break;
	   }
   }
}

function control_dept_info() {
	add_menu_page('SEUFolios Departments', 'Departments', 'manage_options', 'seufolios_departments', 'control_dept_options','' , 21);
	add_submenu_page( 'seufolios_departments', 'Department Courses', 'Courses', 'manage_options', 'seufolios_departments_subcourses', 'control_course_list' );
	add_submenu_page( 'seufolios_departments', 'Department Evaluations', 'Evaluations', 'manage_options', 'seufolios_departments_evaluations', 'control_evaluation_questions');
}

function control_dept_options() {
//creates the dept network admin page
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<style>
	table.seufolios {
		border-spacing:0;
	}
	table.seufolios th, table.seufolios td {
		text-align:left;
		padding-right:2em;
	}
	table.seufolios tr:nth-child(even) {
    	background-color: #EEE;
	}
	</style>
    
	<div class="wrap">
        <h2>Current Departments</h2>
        <div id="dept_body">
            <table class="seufolios">
            <tr><th>Abbr</th><th>Name</th></tr>
            <?php
            $depts = get_depts();
            foreach($depts as $dept) {
                echo "<tr><td>$dept->abbr</td><td>$dept->name</td></tr>\n";	
            }
            ?>
            </table>
        </div>
        <div id="dept_form">
            <h3>Add New Department</h3>
            <form name="add_new" id="add_new" method="POST">
                <label for="new_abbr">Dept. Abbreviation</label> <input type="text" name="new_abbr" maxlength="4">
                <label for="new_title">Dept. Title</label> <input type="text" name="new_title" >
                <br><br>
                <input type="submit" value="Add Department" />
            </form>
        </div>
        <div id="default_dept_container">
        	<h2>Set Default Department</h2>
            <p>What department should students be included in by default?<br>
            	Students may override this in their user profile settings.</p>
            <form name="set_default_dept" id="set_default_dept" method="POST">
            	<select name="default_dept" id="default_dept">
                	<option>---</option>
                <?php
					$depts = get_depts();
					$current_default = get_site_option('seu_default_dept_id');
					foreach ($depts as $dept) {
						if($current_default == $dept->id) echo "<option value='$dept->id' selected='selected'>$dept->name</option>";
						else                    echo "<option value='$dept->id'>$dept->name</option>";	
					}
				?>
                </select>
                <input type="submit" value="Set Default">             
            </form>
            <br>
            <div id="default_dept_feedback" style="color:#333;">&nbsp;</div>
        </div>
    </div>
    
    <script>
		jQuery('#add_new').submit(function() {
		  var b = jQuery(this).serialize();
		  
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'add_dept_submit', 
					'data': b
				  },
				  function (response) {
					  jQuery('#dept_body').html(response);
				  });
		  return false;
		});
		jQuery('#set_default_dept').submit(function() {
		  var b = jQuery(this).serialize();
		  
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'set_default_dept', 
					'data': b
				  },
				  function (response) {
					  jQuery('#default_dept_feedback').html(response);
				  });
		  return false;
		});
	</script>
    
    <?php
	
}

function set_default_dept_ajax() {
	//controls the default department option submission
	//create array from post data
		$data = array();
		$data_1 = explode('&', $_POST['data']);
		foreach($data_1 as $line) {
			$line1 = explode('=', $line);
			$data[$line1[0]] = $line1[1];	
		}
	
	$result = update_site_option('seu_default_dept_id', $data['default_dept']);
	echo "$result <br>";
	echo "Default department id set to " .$data['default_dept'];
	die();
}

function control_course_list() {
//creates the courses network admin page
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	?>
	<style>
	table.seufolios {
		border-spacing:0;
	}
	table.seufolios th, table.seufolios td {
		text-align:left;
		padding-right:2em;
	}
	table.seufolios tr:nth-child(even) {
    	background-color: #EEE;
	}
	</style>
	<div class="wrap">
        <h2>Pick a department</h2>
        <div id="choose_dept">
        	<form name="choose_dept" id="choose_dept">
                <select name="dept_select" id="dept_select">
                	<option value="0">--Select a Department--</option>
                	<?php
					$depts = get_depts();
					foreach($depts as $dept) {
						echo "<option value='$dept->id'> $dept->name</option>\n";	
					}
					?>
                </select>
            </form>
        </div>
        <h2>Courses</h2>
        <div id="courses_list">
            
        </div>
        <div id="courses_form">
            <h3>Add New Course</h3>
            <form name="add_new" id="add_new" method="POST">
                <label for="new_num">Course Number</label> <input type="text" name="new_num">
                <label for="new_title">Course Title</label> <input type="text" name="new_title" >
                <br><br>
                <input type="submit" value="Add Course" />
            </form>
        </div>
    </div>
    
    <script>
		jQuery('#dept_select').change(function() 
		{
		   var b = jQuery(this).attr('value');
		   jQuery.post( ajaxurl, 
		  		  {
		  			'action':'add_course_select_dept', 
					'data': b
				  },
				  function (response) {
					  jQuery('#courses_list').html(response);
				  });
			
		});
		
		jQuery('#add_new').submit(function() {
		  
		  var b = jQuery(this).serialize() + '&dept_id=' + jQuery('#dept_select').attr('value');
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'add_course_submit', 
					'data': b
				  },
				  function (response) {
					  jQuery('#courses_list').html(response);
				  });
				  
		  jQuery(this).each (function(){
			this.reset();
		  });
		  
		  return false;
		});
		
		function delete_course(course_id) {
			jQuery.post( ajaxurl, 
		  		  {
		  			'action':'add_course_delete', 
					'data': course_id +'&' + jQuery('#dept_select').attr('value')
				  },
				  function (response) {
					  jQuery('#courses_list').html(response);
				  });
		  
		  return false;			
		}
		
	</script>
    
    <?php
}

function create_dept_sqltable() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
    //Department
	$dept_table_name = $wpdb->prefix . "seufolios_depts"; 
   
	$sql_dept = "CREATE TABLE $dept_table_name (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  abbr CHAR(4) NOT NULL,
	  name tinytext NOT NULL,
	  UNIQUE KEY id (id)
	);";
	
	dbDelta($sql_dept);

	//Courses	
	$courses_table_name = $wpdb->prefix . "seufolios_courses"; 
   
	$sql_courses = "CREATE TABLE $courses_table_name (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  dept_id mediumint(9) NOT NULL,
	  number tinytext NOT NULL,
	  title tinytext NOT NULL,
	  UNIQUE KEY id (id),
	  FOREIGN KEY (dept_id) REFERENCES $dept_table_name(id)
	);";
	
	dbDelta($sql_courses);

	//Evaluation Sections	
	$eval_sections_table_name = $wpdb->prefix . "seufolios_eval_sections"; 
   
	$sql_eval_sections = "CREATE TABLE $eval_sections_table_name (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  dept_id mediumint(9) NOT NULL,
	  title tinytext NOT NULL,
	  description tinytext,
	  order_loc tinyint,
	  UNIQUE KEY id (id),
	  FOREIGN KEY (dept_id) REFERENCES $dept_table_name(id)
	);";
	
	dbDelta($sql_eval_sections);
	
	//Evaluation Questions (subset of sections)
	$eval_questions_table_name = $wpdb->prefix . "seufolios_eval_questions"; 
   
	$sql_eval_questions = "CREATE TABLE $eval_questions_table_name (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  section_id mediumint(9) NOT NULL,
	  slug tinytext NOT NULL,
	  question text NOT NULL,
	  type mediumint(9) NOT NULL,
	  enabled tinyint,
	  order_loc tinyint,
	  UNIQUE KEY id (id),
	  FOREIGN KEY (section_id) REFERENCES $eval_sections_table_name(id)
	);";
	
	dbDelta($sql_eval_questions);
}

function create_dept_sqltable_data() {
	//not used - a testing function
   global $wpdb;
   $dept_table_name = $wpdb->prefix . "seufolios_depts"; 
   
   $rows_affected = $wpdb->insert( $dept_table_name, array( 'abbr' => 'ENGW', 'name' => 'English Writing and Rhetoric' ) );
}

//***Generic functions
function get_depts() {
	global $wpdb;
	$dept_table_name = 'wp_seufolios_depts';  //disabled because prefix changes in multisite $wpdb->prefix . "seufolios_depts";
	 
	$sql = "SELECT * FROM $dept_table_name ORDER BY abbr ASC";
	
	$results = $wpdb->get_results($sql);
	
	return $results;	
}

function get_courses($dept_id) {
	global $wpdb;
	$courses_table_name = "wp_seufolios_courses"; 
	$sql = "SELECT * FROM $courses_table_name WHERE dept_id = $dept_id ORDER BY number ASC";
	
	$results = $wpdb->get_results($sql);
	
	return $results;
}

function get_sections($dept_id) {
	global $wpdb;
	$sections_table_name = "wp_seufolios_eval_sections"; 
	$sql = "SELECT * FROM $sections_table_name WHERE dept_id = $dept_id ORDER BY order_loc ASC";
	
	$results = $wpdb->get_results($sql);
	
	return $results;
}

function get_questions($sec_id) {
	global $wpdb;
	$questions_table_name = "wp_seufolios_eval_questions"; 
	$sql = "SELECT * FROM $questions_table_name WHERE section_id = $sec_id ORDER BY order_loc ASC";
	
	$results = $wpdb->get_results($sql);
	
	return $results;
}

//***AJAX functions
function add_dept_save_ajax() { 
//used in dept network page to save a new department	
		//create array from post data
		$data = array();
		$data_1 = explode('&', $_POST['data']);
		foreach($data_1 as $line) {
			$line1 = explode('=', $line);
			$data[$line1[0]] = $line1[1];	
		}
		
		//move array to variables & changes + to space
		$new_abbr  = $data['new_abbr'];
		$new_title = $data['new_title'];
		$new_title = str_replace('+',' ',$new_title);
		
		//insert new dept in to table
		global $wpdb;
		$dept_table_name = $wpdb->prefix . "seufolios_depts"; 
		$rows_affected = $wpdb->insert( $dept_table_name, array( 'abbr' => $new_abbr, 'name' => $new_title ) );
		
		//get all depts from table
		$sql = "SELECT * FROM $dept_table_name";
		$depts = $wpdb->get_results($sql);
		
		//create html and return
		$result = "<table> \n
				   <tr><th>Abbr</th><th>Name</th></tr>";
		 foreach($depts as $dept) {
		   $result .= "<tr><td>$dept->abbr</td><td>$dept->name</td></tr>\n";	
		 }
		$result .= "</table>";
		
		echo $result;
		die();
		 
}

function add_course_deptselect() {
//Used in courses network page to display courses from a dept
	$dept_id = $_POST['data'];
	
	echo create_course_table($dept_id);
	die();
}

function add_course_save_ajax() {
//Used in courses network page ot save a new course
	//create array from post data
	$data = array();
	$data_1 = explode('&', $_POST['data']);
	foreach($data_1 as $line) {
		$line1 = explode('=', $line);
		$data[$line1[0]] = $line1[1];	
	}
	
	//move array to variables & changes + to space
	$new_num  = $data['new_num'];
	$new_num = urldecode($new_num);
	$new_title = $data['new_title'];
	$new_title = urldecode($new_title);
	$dept_id = $data['dept_id'];
	
	//insert new course in to table
	global $wpdb;
	$course_table_name = $wpdb->prefix . "seufolios_courses"; 
	$rows_affected = $wpdb->insert( $course_table_name, array( 'dept_id' => $dept_id, 'number' => $new_num, 'title' => $new_title ) );
	
	//create html and return
	echo create_course_table($dept_id);
	die();
}

function add_course_delete_ajax() {
//used in courses network admin page to delete a course	
	//get post data
	$data = explode('&', $_POST['data']);
	$course_id = $data[0];
	$dept_id   = $data[1];
	
	//delete course from table
	global $wpdb;
	$course_table_name = $wpdb->prefix . "seufolios_courses"; 
	$sql = "DELETE FROM $course_table_name WHERE id=$course_id";
	$rows_affected = $wpdb->query($sql);
	
	echo create_course_table($dept_id);
	die();
}

function create_course_table($dept_id) {
//used in courses network admin page

	//create html and return
	$result = "<table class='seufolios'>\n
		<tr><th>Number</th><th>Title</th><th>&nbsp;</th></tr>";
	$courses = get_courses($dept_id);
	foreach($courses as $course) {
		$result .= "<tr><td>$course->number</td><td>$course->title</td> <td><button id='delete_$course->id' class='delete_button' type='button' onclick='delete_course($course->id)'>Delete</button></td></tr>\n";	
	}
	
	$result .= "</table>";
	
	return $result;
}


//***Evaluation stuff
function control_evaluation_questions() {
	//creates the evaluation network admin page
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	$q_types = get_question_types();
	
	?>
	<style>
	div.main_group {
		float:left;
		margin:0 1% 0 0;	
	}
	div#sections { width:35%; }
	div#questions { width: 60%; }
	table.seufolios { border-spacing:0; }
	table.seufolios th, table.seufolios td {
		text-align:left;
		padding-right:1em;
	}
	table.seufolios th {
		background-color: #FFF;	
		border-bottom:thin solid gray;
	}
	table.seufolios td { 
		padding:0.5em 1em;
		
	}
	table.seufolios tr {
		background-color: #EFEFEF;	
	}
	table.seufolios tr.selected { 
		background-color:#FFF !important; 
		font-weight:bold;
	}
	table.seufolios td.question { width:200px; }
	td.desc {
		font-size:0.8em;
		font-weight:400;
		color:gray;
	}
	table.seufolios td.edit { width:75px !important; }
	td.title {padding-left:5px;}
	
	div#sections_form, div#questions_form {	display:none; }
	form * {vertical-align:middle;}
	form.inline * {padding:0; margin:0; height:20px;}
	.align_right {text-align:right; margin-right:2em;}
	</style>
	<div class="wrap">
        <h2>Evaluation Questions</h2>
        <!--Commented out - duplicate of option in control_course_list, not needed in single page setup
        <div id="choose_dept">
        	
            <form name="choose_dept-eval" id="choose_dept-eval">
                <select name="dept_select-eval" id="dept_select-eval">
                	<option value="0">--Select a Department--</option>
                	<?php
					$depts = get_depts();
					foreach($depts as $dept) {
						echo "<option value='$dept->id'> $dept->name</option>\n";	
					}
					?>
                </select>
            </form>
        </div>-->
        
        <!--Sections-->
        <div id="sections" class="main_group">
            <div id="sections_list">
                
            </div>
            <div id="sections_form">
                <h3>Add New Evaluation Section</h3>
                <form name="add_new_sec" id="add_new_sec" method="POST">
                	<table>
                    <tr>
                    <td class="align_right"><label for="sec_title">Title</label></td><td><input type="text" name="sec_title" size="10"></td>
                    </tr><tr>
                    <td class="align_right"><label for="sec_description">Description</label></td><td><input type="text" name="sec_description"></td>
                    </tr><tr>
                    <td class="align_right"><label for="sec_order">Order Placement</label></td><td><input type="text" name="sec_order" size="2"></td>
					</tr><tr>
                    <td class="align_right"></td><td><input type="submit" value="Add Section" /></td>
                    </tr></table>
                </form>
            </div>
        </div>
        
        <!--Questions-->
        <div id="questions" class="main_group">
            <div id="questions_list">
                
            </div>
            <div id="questions_form">
                <h3>Add New Question</h3>
                <form name="add_new_question" id="add_new_question" method="POST">
                	<input type="hidden" name="sec_id" id="sec_id" value="">
                	<table>
                    <tr>
                    <td class="align_right"><label for="ques_slug">Slug (no spaces)</label></td><td><input type="text" name="ques_slug" size="10"></td>
                    </tr><tr>
                    <td class="align_right"><label for="ques_question">Question text</label></td><td><textarea name="ques_question" id="ques_question" cols="40" rows="3"></textarea></td>
                    </tr><tr>
                    <td class="align_right"><label for="ques_type">Type</label></td><td><select name="ques_type">
                    <?php
					foreach ($q_types as $q) {
						echo "<option value='$q->id'>$q->displayName</option>\n";	
					}
					?>
                    </select></td>
                    </tr><tr>
                    <td class="align_right"><label for="ques_enabled">Enabled</label></td><td><input type="checkbox" value="checked" name="ques_enabled"></td>
                    </tr><tr>
                    <td class="align_right"><label for="ques_order">Order Placement</label></td><td><input type="text" name="ques_order" size="2"></td>
					</tr><tr>
                    <td class="align_right"></td><td><input type="submit" value="Add Section" /></td>
                    </tr></table>
                </form>
            </div>
        </div>
    </div>
    
    <script>
		//Department Select
		jQuery('#dept_select').change(function() 
		{
		   var b = jQuery(this).attr('value');
		   jQuery.post( ajaxurl, 
		  		  {
		  			'action':'eval_select_dept', 
					'data': b
				  },
				  function (response) {
					  jQuery('#sections_list').html(response);
				  });
			jQuery('#sections_form').show();//document.getElementById('sections_form').style.display = 'block';
			jQuery('#questions_list').html('&nbsp;');
			jQuery('#questions_form').hide();
		});
		
		//Add new section
		jQuery('#add_new_sec').submit(function() {
		  
		  var b = jQuery(this).serialize() + '&dept_id=' + jQuery('#dept_select').attr('value');
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'eval_add_section', 
					'data': b
				  },
				  function (response) {
					  jQuery('#sections_list').html(response);
				  });
				  
		  jQuery(this).each (function(){
			this.reset();
		  });
		  
		  return false;
		});
		
		//Edit a section
		function edit_section(sec_id, sec_order) {
			var tr = document.getElementById('secrow_' + sec_id);
			var td_title = tr.getElementsByClassName('title');
			var old_title = td_title[0].innerHTML;
			var td_desc = tr.getElementsByClassName('desc');
			var old_desc = td_desc[0].innerHTML;
			var innerHTML = "<td><input type='hidden' name='sec_id' id='sec_id' value='" + sec_id + "'>" +
							"<input type='text' name='sec_title' id='sec_title' size='10' value='" +old_title+ "'></td>" +
							"<td><input type='text' name='sec_description' id='sec_description' value='" +old_desc+ "'></td>" +
							"<td><input type='text' name='sec_order' id='sec_order' size='2' value='"+sec_order+"'>" +
							"<button type='submit' onclick='edit_sec_submit();'>Done</button> &nbsp;&nbsp; " +
							"<button id='delete_"+sec_id+"' class='delete_button' type='button' onclick='delete_section("+sec_id+")'>Delete</button></td>";
			
			tr.innerHTML = innerHTML;
			return false;	
		}
		
		//Submit the Edit Section
		function edit_sec_submit() {
		  
		  var b = 'sec_id=' +document.getElementById('sec_id').value + '&sec_title=' +document.getElementById('sec_title').value + '&sec_description=' +document.getElementById('sec_description').value + '&sec_order=' +document.getElementById('sec_order').value + '&dept_id=' + jQuery('#dept_select').attr('value');
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'eval_edit_section', 
					'data': b
				  },
				  function (response) {
					  jQuery('#sections_list').html(response);
				  });
		  return false;
		}
		
		//Delete a section
		function delete_section(sec_id) {
			jQuery.post( ajaxurl, 
		  		  {
		  			'action':'eval_delete_section', 
					'data': sec_id +'&' + jQuery('#dept_select').attr('value')
				  },
				  function (response) {
					  jQuery('#sections_list').html(response);
				  });
		  
		  return false;			
		}
		
		//Show questions for a section 
		function show_questions(sec_id, button) {
			jQuery.post( ajaxurl, 
		  		  {
		  			'action':'eval_show_questions', 
					'data': sec_id
				  },
				  function (response) {
					  jQuery('#questions_list').html(response);
				  });
		  document.getElementById('questions_form').style.display = 'block';
		  document.getElementById('sec_id').value = sec_id;
		  
		  //reset selected class
		  var tableRows = document.getElementsByClassName('selected');
		  for(i=0;i<tableRows.length;i++) {
			tableRows[i].className = '';  
		  }
		  
		  //add new selected class
		  var tableRow = button.parentNode.parentNode;
		  tableRow.className = 'selected';
		  return false;	
		}
		
		//Add new question
		jQuery('#add_new_question').submit(function() {
		  
		  var b = jQuery(this).serialize();
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'eval_add_question', 
					'data': b
				  },
				  function (response) {
					  jQuery('#questions_list').html(response);
				  });
				  
		  jQuery(this).each (function(){
			this.reset();
		  });
		  
		  return false;
		});
		
		//Edit a question
		function edit_question(q_id, q_order) {
			var tr = document.getElementById('qrow_' + q_id);
			var td_slug = tr.getElementsByClassName('slug');
			var old_slug = td_slug[0].innerHTML;
			var td_question = tr.getElementsByClassName('question');
			var old_question = td_question[0].innerHTML;
			var td_type = tr.getElementsByClassName('type');
			var old_type = td_type[0].innerHTML;
			var td_enabled = tr.getElementsByClassName('enabled');
			var old_enabled = (td_enabled[0].innerHTML == 'Yes') ? 'checked' : '';
			
			var innerHTML = "<td><input type='hidden' name='q_id' id='q_id' value='" + q_id + "'>" +
							"<input type='text' name='q_slug' id='q_slug' size='10' value='" +old_slug+ "'></td>" +
							"<td><input type='text' name='q_question' id='q_question' value='" +old_question+ "'></td>" +
							"<td><select name='q_type' id='q_type'><?php
					foreach ($q_types as $q) {
						echo "<option value='$q->id'>$q->displayName</option>";	
					}
					?></select></td>" +
							"<td><input type='checkbox' name='q_enabled' id='q_enabled' "+old_enabled+"></td>" +
							"<td><input type='text' name='q_order' id='q_order' size='2' value='"+q_order+"'>" +
							"<button type='submit' onclick='edit_question_submit();'>Done</button> &nbsp;&nbsp; "+
							"<button id='delete_"+q_id+"' class='delete_button' type='button' onclick='delete_question("+q_id+")'>Delete</button></td>";
			
			tr.innerHTML = innerHTML;
			return false;	
		}
		
		//Submit the Edit Questiob
		function edit_question_submit() {
		  
		  var b = 'id=' +document.getElementById('q_id').value + '&slug=' +document.getElementById('q_slug').value + '&question=' + document.getElementById('q_question').value + '&type=' +document.getElementById('q_type').value + '&enabled=' +document.getElementById('q_enabled').checked + '&q_order=' +document.getElementById('q_order').value + '&sec_id=' + jQuery('#sec_id').attr('value');
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'eval_edit_question', 
					'data': b
				  },
				  function (response) {
					  jQuery('#questions_list').html(response);
				  });
		  return false;
		}
		
		//Delete a question
		function delete_question(question_id) {
			jQuerypost( ajaxurl, 
		  		  {
		  			'action':'eval_delete_question', 
					'data': question_id +'&' + jQuery('#sec_id').attr('value')
				  },
				  function (response) {
					  jQuery('#questions_list').html(response);
				  });
		  
		  return false;	
		}
		
	</script>
    
    <?php
}

function create_sec_table($dept_id) {
	//generates html for section table
	//create html and return
	$result = "<h3>Sections</h3>\n
		<table class='seufolios' id='seufolios_sections_table'>\n
		<tr><th>Title</th><th>Description</th><th></th></tr>";
	$sections = get_sections($dept_id);
	foreach($sections as $section) {
		$result .= "<tr id='secrow_$section->id'>
					<td class='title'>$section->title</td>
					<td class='desc'>$section->description</td>
					<td class='edit'>
					    <button id='edit_$section->id' class='edit_button' type='button' onclick='edit_section($section->id, $section->order_loc);'>Edit</button>&nbsp;
						<button id='show_questions_$section->id' class='show_questions_button' type='button' onclick='show_questions($section->id, this)'>&raquo;</button></td></tr>\n";	
	}
	
	$result .= "</table>";
	
	return $result;
}

function create_question_table($sec_id) {
	//generates html for questions table
	//create html and return
	$result = "<h3>Questions</h3>\n
		<table class='seufolios' id='seufolios_questions_table'>\n
		<tr><th>Slug</th><th>Question</th><th>Type</th><th>Enabled</th><th></th></tr>";
	$questions = get_questions($sec_id);
	$q_types = get_question_types();
	
	foreach($questions as $question) {
		$enabled = ($question->enabled == 1 ? 'Yes' : 'No');
		$result .= "<tr id='qrow_$question->id'>\n
					<td class='slug'>$question->slug</td>\n
					<td class='question'>" .stripslashes($question->question) ."</td>\n
					<td class='type'>";
			foreach($q_types as $q) {		
				if ($q->id == $question->type) $result .= $q->displayName;
			}
		$result .= "</td>\n
					<td class='enabled'>$enabled</td>\n
					<td><button id='edit_$question->id' class='edit_button' type='button' onclick='edit_question($question->id, $question->order_loc)'>Edit</button> &nbsp;&nbsp;";	
	}
	
	$result .= "</table>";
	
	return $result;
}

function eval_select_dept() {
//Used in courses network page to display courses from a dept
	$dept_id = $_POST['data'];
	echo create_sec_table($dept_id);
	die();
}

function eval_add_section() {
//add new evaluation section
	//create array from post data
	$data = array();
	$data_1 = explode('&', $_POST['data']);
	foreach($data_1 as $line) {
		$line1 = explode('=', $line);
		$data[$line1[0]] = $line1[1];	
	}
	
	//move array to variables & changes + to space
	$sec_title = urldecode($data['sec_title']);
	$sec_description = urldecode($data['sec_description']);
	$sec_order = str_replace('+',' ',$data['sec_order']);
	$dept_id = $data['dept_id'];
	
	//insert new course in to table
	global $wpdb;
	$eval_sections_table_name = $wpdb->prefix . "seufolios_eval_sections"; 
	$rows_affected = $wpdb->insert( $eval_sections_table_name, array( 'dept_id' => $dept_id, 'title' => $sec_title, 'description' => $sec_description, 'order_loc' => $sec_order ) );
	
	//create html and return
	echo create_sec_table($dept_id);
	die();
}

function eval_edit_section() {
//edit existing evaluation section
	//create array from post data
	$data = array();
	$data_1 = explode('&', $_POST['data']);
	foreach($data_1 as $line) {
		$line1 = explode('=', $line);
		$data[$line1[0]] = $line1[1];	
	}
	
	//move array to variables & changes + to space
	$sec_title = urldecode($data['sec_title']);
	$sec_description = urldecode($data['sec_description']);
	$sec_order = str_replace('+',' ',$data['sec_order']);
	$sec_id = $data['sec_id'];
	$dept_id = $data['dept_id'];
	
	//edit section in wpdb
	global $wpdb;
	$eval_sections_table_name = $wpdb->prefix . "seufolios_eval_sections"; 
	$rows_affected = $wpdb->update( $eval_sections_table_name, array( 'title' => $sec_title, 'description' => $sec_description, 'order_loc' => $sec_order ), array( 'id' => $sec_id) );
	
	//create html and return
	echo create_sec_table($dept_id);
	die();
}

function eval_delete_section() {
	$data = explode('&', $_POST['data']);
	$section_id = $data[0];
	$dept_id   = $data[1];
	
	//delete course from table
	global $wpdb;
	$eval_sections_table_name = $wpdb->prefix . "seufolios_eval_sections"; 
	$sql = "DELETE FROM $eval_sections_table_name WHERE id=$section_id";
	$rows_affected = $wpdb->query($sql);
	
	echo create_sec_table($dept_id);
	die();
}

function eval_show_questions() {
	$sec_id = $_POST['data'];
	echo create_question_table($sec_id);
	die();
}

function eval_add_question() {
	//add new evaluation question
	//create array from post data
	$data = array();
	$data_1 = explode('&', $_POST['data']);
	foreach($data_1 as $line) {
		$line1 = explode('=', $line);
		$data[$line1[0]] = $line1[1];	
	}
	
	//move array to variables & changes + to space
	$ques_slug = str_replace(' ','_', urldecode($data['ques_slug']));
	$ques_question = urldecode($data['ques_question']);
	$ques_order = $data['ques_order'];
	$ques_type = '0'; //temp disabled $data['ques_type'];
	$ques_enabled = ($data['ques_enabled'] == 'checked' ? 1 : 0);
	$sec_id = $data['sec_id'];
	
	//insert new course in to table
	global $wpdb;
	$eval_questions_table_name = $wpdb->prefix . "seufolios_eval_questions"; 
	$rows_affected = $wpdb->insert( $eval_questions_table_name, array( 'section_id' => $sec_id, 'slug' => $ques_slug, 'question' => $ques_question, 'type' => $ques_type, 'enabled' => $ques_enabled, 'order_loc' => $ques_order ) );
	
	//create html and return
	echo create_question_table($sec_id);
	die();
}

function eval_edit_question() {
	//edit existing evaluation section
	//create array from post data
	$data = array();
	$data_1 = explode('&', $_POST['data']);
	foreach($data_1 as $line) {
		$line1 = explode('=', $line);
		$data[$line1[0]] = $line1[1];	
	}
	
	//move array to variables & changes + to space
	$id = $data['id'];
	$slug = urldecode($data['slug']);
	$question = urldecode($data['question']);
	$type = urldecode($data['type']);
	$enabled = ($data['enabled']) ? 1 : 0;
	$q_order = $data['q_order'];
	$sec_id = $data['sec_id'];
	
	//edit section in wpdb
	global $wpdb;
	$eval_questions_table_name = $wpdb->prefix . "seufolios_eval_questions"; 
	$rows_affected = $wpdb->update( $eval_questions_table_name, array( 'slug'=>$slug, 'question'=>$question, 'type'=>$type, 'enabled'=>$enabled, 'order_loc'=>$q_order), array( 'id' => $id) );
	
	//create html and return
	echo create_question_table($sec_id);
	die();
}

function eval_delete_question() {
	$data = explode('&', $_POST['data']);
	$question_id = $data[0];
	$sec_id   = $data[1];
	
	//delete course from table
	global $wpdb;
	$eval_questions_table_name = $wpdb->prefix . "seufolios_eval_questions"; 
	$sql = "DELETE FROM $eval_questions_table_name WHERE id=$question_id";
	$rows_affected = $wpdb->query($sql);
	
	echo create_question_table($sec_id);
	die();
}