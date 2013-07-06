<?php
//*****
//Contains functions that create department-specific features
//Features include student profile modifications, course taxonomy terms
//*****

//include other classes
require_once('classes/wp-admin-menu-classes.php'); //allows us to modify the order of admin menus

//***WP Hooks
//Add user profile w WP hooks
//add_action('signup_extra_fields', 'add_custom_signup_fields');
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

//Add network admin screen for departments
add_action('network_admin_menu', 'control_dept_info');
add_action('wp_ajax_set_default_dept', 'set_default_dept_ajax' );

//Setup department taxonomy settings page
add_action('wp_ajax_tax_select_dept', 'tax_select_dept_ajax');
add_action('wp_ajax_tax_show_terms', 'tax_show_terms_ajax');
add_action('wp_ajax_tax_add_tax', 'tax_add_tax_ajax');
add_action('wp_ajax_tax_edit_tax', 'tax_edit_tax_ajax');
add_action('wp_ajax_tax_delete_tax', 'tax_delete_tax_ajax');
add_action('wp_ajax_tax_add_term', 'tax_add_term_ajax');
add_action('wp_ajax_tax_edit_term', 'tax_edit_term_ajax');
add_action('wp_ajax_tax_delete_term', 'tax_delete_term_ajax');

//Setup department courses settings pages
add_action('wp_ajax_add_dept_submit', 'add_dept_save_ajax' );
add_action('wp_ajax_add_course_select_dept', 'add_course_deptselect');
add_action('wp_ajax_add_course_submit', 'add_course_save_ajax');
add_action('wp_ajax_add_course_delete', 'add_course_delete_ajax');

//Setup department evaluation settings pages ajax
add_action('wp_ajax_eval_select_dept', 'eval_select_dept');
add_action('wp_ajax_eval_add_section', 'eval_add_section');
add_action('wp_ajax_eval_edit_section', 'eval_edit_section');
add_action('wp_ajax_eval_delete_section', 'eval_delete_section');
add_action('wp_ajax_eval_show_questions', 'eval_show_questions');
add_action('wp_ajax_eval_add_question', 'eval_add_question');
add_action('wp_ajax_eval_edit_question', 'eval_edit_question');
add_action('wp_ajax_eval_delete_question', 'eval_delete_question');

//***Functions

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
		case 'RATS': 
			setup_rats_student();	
			break;
	}
	
	//get taxes from database, add to all post types
	$taxes_arr = get_taxes($major);
	foreach($taxes_arr as $t) {
		$taxes[$t->id]['slug'] = $t->tax_slug;
		$taxes[$t->id]['settings'] = $t->tax_settings;
		$taxes[$t->id]['terms'] = get_tax_terms($t->id);
	}
	
	$post_types = get_post_types();
	unset($post_types['attachment'], $post_types['revision'], $post_types['nav_menu_item']);
	
	//create custom post types 
	$create_posts = false;
	foreach($taxes as $tax) {
		if( strtolower($tax['slug']) == 'posts' ) 
			$create_posts = true;
	}
	if ($create_posts) {
		$reserved_posts = array('post', 'page','attachment','revision','nav_menu_item');
		$slug = strtolower(str_replace(' ', '_', $tax['slug']));
		$existing_taxes = get_taxonomies();
		$default_settings = array('public' => true, 'capability_type' => 'page', 'hierarchical' => true, 'taxonomies' => $existing_taxes );
		
		if ( !in_array($slug, $reserved_posts) ) { 	// make sure we're not trying to add a reserved post type
			foreach($tax['terms'] as $p) {
				if(strlen($p->term_settings)) {
					$saved_settings = array();
					$settings_temp = explode("\n", $p->term_settings);
					foreach($settings_temp as $st) {
						$tmp = explode("=", $st, 2);
						$tmp[1] = str_replace(', ', ',', $tmp[1]);
						if(strpos($tmp[1], ',')) $tmp[1] = explode(',', $tmp[1]);
						$saved_settings[ $tmp[0] ] = $tmp[1];
					}
					$settings = array_merge($default_settings, $saved_settings);
				} else $settings = $default_settings;
				
				//$settings = ( is_array($p->term_settings) ? array_merge($default_settings, $p->term_settings) : $default_settings);	//merge the default settings and user settings
				if(!isset($settings['label'])) $settings['label'] = str_replace(' ', '_', $p->term_slug); 
				register_post_type(
					strtolower(str_replace(' ', '_', $p->term_slug)), //post type slug
					$settings	//settings
				);
			}
		}
	}
	
	//create taxonomies
	// !!! still needs settings incorporated
	foreach($taxes as $tax) {
		if( strtolower($tax['slug']) != 'posts' ) {
			$slug = strtolower(str_replace(' ', '_', $tax['slug']));
			$default_settings = array(
				'post_types' => $post_types,
				'args'   => array('label'=>$slug, 'rewrite'=>array('slug'=>$slug), 'show_admin_column'=> true)
			);
			
			$settings = array();
			$saved_settings = array();
			$settings_temp = explode("\n", $tax['settings']);
			foreach($settings_temp as $st) {
				$tmp = explode("=", $st, 2);
				$tmp[1] = str_replace(', ', ',', $tmp[1]);
				if(strpos($tmp[1], ',')) $tmp[1] = explode(',', $tmp[1]);
				$saved_settings[ $tmp[0] ] = $tmp[1];
			}
			
			if(isset($saved_settings['post_types'])) 
				$settings['post_types'] = $saved_settings['post_types'];
			else 
				$settings['post_types'] = $default_settings['post_types'];
		
			unset($saved_settings['post_types']);
			$settings['args'] = array_merge($default_settings['args'], $saved_settings);
			
			register_taxonomy(
				$slug,
				$settings['post_types'],
				$settings['args']
			  );
				
			foreach($tax['terms'] as $term) {
				$term_slug = strtolower(str_replace(' ', '_', $term->term_slug));
				$default_settings = array('slug'=>$term_slug, 'description'=>$term_slug);
				$settings = array();
				$saved_settings = array();
				
				$settings_temp = explode("\n", $term->term_settings);
				foreach($settings_temp as $st) {
					$tmp = explode("=", $st, 2);
					$tmp[1] = str_replace(', ', ',', $tmp[1]);
					if(strpos($tmp[1], ',')) $tmp[1] = explode(',', $tmp[1]);
					$saved_settings[ $tmp[0] ] = $tmp[1];
				}
				$settings = array_merge($default_settings, $saved_settings);
				
				if( !term_exists($term_slug, $slug) ) {
					wp_insert_term(
						$term_slug, 	// the term 
						$slug, 			// the taxonomy
						$settings 		// the settings
					  );
				} //if
				
			} //foreach
		} //if
		
	} //foreach
} //function

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
						<ol>
						<li>Mark the learning outcomes and course in the boxes to the right.</li>
						<li>Inside this text box, type a brief description of the course, its major units, and how performance was measured (e.g. exams, papers, presentations).</li>
						<li>Inside this text box, write a brief narrative that explains what material you examined and what you learned that allowed you to fulfill the learning outcomes you marked.</li>
						<li>Inside this text box, clearly describe how your performance was measured in the course. Include any papers, tests, and other documents that demonstrate what you learned in this class. Insert these documents using the Add Media &rarr; Insert Document feature directly above this text box.</li></ol>";
        	break;
        case 'related_life':
            $content = "Concise description of the activity:<br>
						Dates<br>
						From: &nbsp;&nbsp;&nbsp;&nbsp;To: <hr>
						<ol>
						<li>Mark the learning outcomes in the box to the right.</li>
						<li>Inside this text box, type a brief description of the life experience.</li>
						<li>Inside this text box, write a brief narrative that explains what you learned that allowed you to fulfill the learning outcomes you marked.</li>
						<li>Include any papers, tests, and other documents that demonstrate what you learned in this class. Insert these documents using the Add Media &rarr; Insert Document feature directly above this text box.</li></ol>";
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
			$help = str_replace("\n", "\\\n", return_help($help_key));
			echo "<script type='text/javascript'>
				  jQuery(document).ready(function(){
					  jQuery('#rats_outcomes-tabs').append(\"" .$help ."\");
				  });</script>";
			break;
		case 'courses':
			$help_key = 'mark_courses';
			$help = str_replace("\n", "\\\n", return_help($help_key));
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
	//add_submenu_page( 'seufolios_departments', 'Department Courses', 'Courses', 'manage_options', 'seufolios_departments_subcourses', 'control_course_list' );
	add_submenu_page( 'seufolios_departments', 'Department Taxonomies', 'Taxonomies', 'manage_options', 'seufolios_departments_taxes', 'control_tax_list' );
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
            <h2>Add New Department</h2>
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

function control_tax_list() {
//sets up network admin page for custom taxonomies
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	?>
	<style>
	div.main_group {
		float:left;
		margin:0;	
	}
	table.seufolios { border-spacing:0; }
	table.seufolios th, table.seufolios td {
		text-align:left;
		padding:0.5em 1em;
	}
	table.seufolios th {
		background-color: #FFF;	
		border-bottom:thin solid gray;
	}
	table.seufolios td { 
		border-bottom: thin solid #ddd;	
	}
	table.seufolios tr {
		background-color: #f6f6f6;
	}
	table.seufolios tr.selected td { 
		background-color:#FFF !important; 
		font-weight:bold;
		position: relative;
		z-index: 2;
		-moz-box-shadow:    0 8px 10px -7px #000;
		-webkit-box-shadow: 0 8px 10px -7px #000;
		box-shadow:         0 8px 10px -7px #000;
	}
	table.seufolios tr.selected td:first-of-type { 
		-moz-box-shadow:    -8px 8px 10px -7px #000;
		-webkit-box-shadow: -8px 8px 10px -7px #000;
		box-shadow:         -8px 8px 10px -7px #000;
	}
	td.desc {
		font-size:0.8em;
		font-weight:400;
		color:gray;
	}
	table.seufolios td.question { width:200px; }
	td.title {padding-left:5px;}
	
	div#choose_dept{margin-bottom: 20px;}
	div#taxes_list, div#terms_list {  min-height:100px; }
	div#terms_list {
		position: relative;
		z-index: 1;
		-moz-box-shadow:    -8px 8px 10px -7px #000;
		-webkit-box-shadow: -8px 8px 10px -7px #000;
		box-shadow:         -8px 8px 10px -7px #000;
	}
	div#terms_list tr { background-color: #fff; }
	div#taxes_form, div#tax_terms_form { 
		position:relative;
		display:none; 
		margin-top: 20px;
		padding: 0 10px;
	}
	div#tax_instructions {
		position:relative;
		clear:both;
		padding-top:3em;
	}
	
	form * {vertical-align:middle;}
	form.inline * {padding:0; margin:0; height:20px;}
	.align_right {text-align:right; margin-right:2em;}
	</style>
	<div class="wrap">
        <h2>Department Taxonomies and Post Types</h2>
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
        
        <!--Taxes-->
        <div id="taxes" class="main_group">
            <div id="taxes_list">
                
            </div>
            <div id="taxes_form">
                <h3>Add New Taxonomy</h3>
                <form name="add_new_tax" id="add_new_tax" method="POST">
                	<table>
                    <tr>
                    <td class="align_right"><label for="tax_slug">Taxonomy</label></td><td><input type="text" name="tax_slug" size="10"></td>
                    </tr><tr>
                    <td class="align_right"><label for="tax_settings">Settings</label></td><td><textarea name="tax_settings"></textarea></td>
                    </tr><tr>
                    <td class="align_right"></td><td><input type="submit" value="Add Taxonomy" /></td>
                    </tr></table>
                </form>
            </div>
        </div>
        
        <!--Terms-->
        <div id="terms" class="main_group">
            <div id="terms_list">
                
            </div>
            <div id="tax_terms_form">
                <h3>Add New Term</h3>
                <form name="add_new_term" id="add_new_term" method="POST">
                	<input type="hidden" name="tax_id" id="tax_id" value="">
                	<table>
                    <tr>
                    <td class="align_right"><label for="term_slug">Term Slug</label></td><td><input type="text" name="term_slug" size="5"></td>
                    </tr><tr>
                    <td class="align_right"><label for="term_settings">Term Settings</label></td><td><textarea name="term_settings" size="20"></textarea></td>
					</tr><tr>
                    <td class="align_right"></td><td><input type="submit" value="Add Term" /></td>
                    </tr></table>
                </form>
            </div>
        </div>
        <div id="tax_instructions">
            <p>A full list of the settings for the <em>Taxonomy</em> and <em>Term</em> entries are available at the WordPress codex for <a href="http://codex.wordpress.org/Function_Reference/register_taxonomy">taxonomies</a> and for <a href="http://codex.wordpress.org/Function_Reference/wp_insert_term">terms</a>. Create the array by putting <strong>=</strong> between the key and value and a <strong>line break</strong> (return key) between different settings. If you need an array in the value, separate the entries with a comma (<strong>,</strong>).</p> 
			<p>Example settings for a <em>Taxonomy</em> entry:<br>label=Courses<br>hierarchical=false<br>post_types=page, post</em>
			<p>Create a <em>Post Type</em> using the same form as you use to create a <em>Taxonomy</em>. The <em>Term Title</em> will be the slug of the post type.</p>
            <p>A full list of the settings for the Post Type are available at the <a href="http://codex.wordpress.org/Function_Reference/register_post_type">WordPress codex</a>.</p>
            <p>An example settings entry:<br>capability_type=post<br>hierarchical=false<br>label=Reflections</p>
        </div>
    </div>
    
    <script>
		//Department Select
		jQuery('#dept_select').change(function() 
		{
		   var b = jQuery(this).val();
		   jQuery.post( ajaxurl, 
		  		  {
		  			'action':'tax_select_dept', 
					'data': b
				  },
				  function (response) {
					  jQuery('#taxes_list').html(response);
				  });
			jQuery('#taxes_form').css('display','block');
		});
		
		//Show terms
		function show_terms(tax_id, button) {
			jQuery.post( ajaxurl, 
		  		  {
		  			'action':'tax_show_terms', 
					'data': tax_id
				  },
				  function (response) {
					  jQuery('#terms_list').html(response);
				  });
		  jQuery('#tax_terms_form').css('display','block');
		  jQuery('#tax_id').val(tax_id);
		  
		  //reset selected class
		  jQuery('.selected').removeClass(); 
		  //add new selected class
		  var tableRow = button.parentNode.parentNode;
		  tableRow.className = 'selected';
		  return false;	
		}
		
		//Add new tax
		jQuery('#add_new_tax').submit(function() {
		  var b = jQuery(this).serialize() + '&dept_id=' + jQuery('#dept_select').val();
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'tax_add_tax', 
					'data': b
				  },
				  function (response) {
					  jQuery('#taxes_list').html(response);
				  });
				  
		  jQuery(this).each (function(){
			this.reset();
		  });
		  
		  return false;
		});
		
		//Edit a tax
		function edit_tax(id) {
			var tds = jQuery('#tax_row_'+id+' td');
			var old_tax_slug = tds[0].innerHTML;
			var old_tax_settings = tds[1].innerHTML;
			var innerHTML = "<td><input class='edit_form_field' type='hidden' name='tax_id' id='tax_id' value='" + id + "'>" +
							"<input class='edit_form_field' type='text' name='tax_slug' id='tax_slug' size='10' value='" +old_tax_slug+ "'></td>" +
							"<td><textarea class='edit_form_field' name='tax_settings' id='tax_settings'>"+old_tax_settings+"</textarea>" +
							"<td><button type='submit' onclick='edit_tax_submit();'>Done</button> &nbsp; " +
							"<button id='delete_"+id+"' class='delete_button' type='button' onclick='delete_tax("+id+")'>Delete</button></td>";
			
			jQuery('#tax_row_'+id).html(innerHTML);
			return false;	
		}
		
		//Submit the Edit Section
		function edit_tax_submit() {
		  var b = jQuery(".edit_form_field").serialize() + '&dept_id=' + jQuery('#dept_select').val();
		  console.log(b);
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'tax_edit_tax', 
					'data': b
				  },
				  function (response) {
					  jQuery('#taxes_list').html(response);
				  });
		  return false;
		}
		
		//Delete a section
		function delete_tax(tax_id) {
			jQuery.post( ajaxurl, 
		  		  {
		  			'action':'tax_delete_tax', 
					'data': 'tax_id='+tax_id +'&dept_id=' + jQuery('#dept_select').val(),
				  },
				  function (response) {
					  jQuery('#taxes_list').html(response);
				  });
		  
		  return false;			
		}
		
		//Add new term
		jQuery('#add_new_term').submit(function() {
		  var b = jQuery(this).serialize() + '&dept_id=' + jQuery('#dept_select').val();
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'tax_add_term', 
					'data': b
				  },
				  function (response) {
					  jQuery('#terms_list').html(response);
				  });
				  
		  jQuery(this).each (function(){
			this.reset();
		  });
		  
		  return false;
		});
		
		//Edit a term
		function edit_term(id) {
			var tds = jQuery('#term_row_'+id+' td');
			var old_term_slug = tds[0].innerHTML;
			var old_term_settings = tds[1].innerHTML;
			var innerHTML = "<td><input class='edit_form_field' type='hidden' name='term_id' id='term_id' value='" + id + "'>" +
							"<input class='edit_form_field' type='hidden' name='tax_id' id='tax_id' value='" + jQuery('div#taxes_list table tr.selected').attr('id').match(/[\d]+$/) + "'>" +
							"<input class='edit_form_field' type='text' name='term_slug' id='term_slug' size='10' value='" +old_term_slug+ "'></td>" +
							"<td><textarea class='edit_form_field' type='text' name='term_settings' id='term_settings'>"+old_term_settings+"</textarea>" +
							"<td><button type='submit' onclick='edit_term_submit();'>Done</button> &nbsp; " +
							"<button id='delete_"+id+"' class='delete_button' type='button' onclick='delete_term("+id+")'>Delete</button></td>";
			jQuery('#term_row_'+id).html(innerHTML);
			return false;	
		}
		
		//Submit the Edit Term
		function edit_term_submit() {
		  var b = jQuery(".edit_form_field").serialize();
		  console.log(b);
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'tax_edit_term', 
					'data': b
				  },
				  function (response) {
					  jQuery('#terms_list').html(response);
				  });
		  return false;
		}
		
		//Delete a term
		function delete_term(term_id) {
			jQuery.post( ajaxurl, 
		  		  {
		  			'action':'tax_delete_term', 
					'data': jQuery('.edit_form_field').serialize(),
				  },
				  function (response) {
					  jQuery('#terms_list').html(response);
				  });
		  
		  return false;			
		}
	</script>
    
    <?php
}

function create_tax_table($dept_id) {
//used in taxes network admin page
	//create html and return
	$result = "<table class='seufolios'>
		<tr><th>Taxonomy Slug</th><th>Settings</th><th></th></tr>";
	$taxes = get_taxes($dept_id);
	foreach($taxes as $tax)
		$result .= "
		<tr id='tax_row_$tax->id'>
		<td>$tax->tax_slug</td>
		<td>$tax->tax_settings</td>
		<td><button id='edit_$tax->id' class='edit_button' type='button' onclick='edit_tax($tax->id)'>Edit</button>
		<button id='show_terms_$tax->id' class='show_terms_button' type='button' onclick='show_terms($tax->id, this)'>&raquo;</button></td></tr>\n</td>
		</tr>\n";	
	
	$result .= "</table>";
	
	return $result;
}

function create_terms_table($tax_id) {
//used in taxes network admin page
	//create html and return
	$result = "<table class='seufolios'>
		<tr><th>Term Slug</th><th>Term Settings</th><th></th></tr>";
	$terms = get_tax_terms($tax_id);
	foreach($terms as $term)
		$result .= "<tr id='term_row_$term->id'><td>$term->term_slug</td><td>$term->term_settings</td> <td><button id='edit_$term->id' class='edit_button' type='button' onclick='edit_term($term->id)'>Edit</button></td></tr>\n";	
	
	$result .= "</table>";
	
	return $result;
}

function tax_select_dept_ajax() {
	$dept_id = $_POST['data'];
	echo create_tax_table($dept_id);
	die();
}

function tax_show_terms_ajax() {
	$tax_id = $_POST['data'];
	echo create_terms_table($tax_id);
	die();
}

function tax_add_tax_ajax() {
	//used in taxes network page to save a new taxonomy	
	//create array from post data
	parse_str($_POST['data'], $data);
	
	//insert new dept in to table
	global $wpdb;
	$taxes_table_name = $wpdb->base_prefix . "seufolios_taxes"; 
	$rows_affected = $wpdb->insert( $taxes_table_name, array( 'dept_id' => $data['dept_id'], 'tax_slug' => $data['tax_slug'], 'tax_settings' => $data['tax_settings'] ) );
	
	$result = create_tax_table($data['dept_id']);
	
	echo $result;
	die();
}

function tax_edit_tax_ajax() {
//edit existing taxonomy
	//create array from post data
	parse_str($_POST['data'], $data);
	
	global $wpdb;
	$taxes_table_name = $wpdb->base_prefix . "seufolios_taxes"; 
	$rows_affected = $wpdb->update( $taxes_table_name, array( 'tax_slug' => $data['tax_slug'], 'tax_settings' => $data ['tax_settings'] ), array( 'id' => $data['tax_id'])  );
	
	echo create_tax_table($data['dept_id']);
	die();
}

function tax_delete_tax_ajax() {
	//create array from post data
	parse_str($_POST['data'], $data);
	
	//delete course from table
	global $wpdb;
	$taxes_table_name = $wpdb->base_prefix . "seufolios_taxes"; 
	$sql = "DELETE FROM $taxes_table_name WHERE id=" .$data['tax_id'];
	$rows_affected = $wpdb->query($sql);
	
	echo create_tax_table($data['dept_id']);
	die();
}

function tax_add_term_ajax() {
	//used in taxes network page to save a new taxonomy	
	//create array from post data
	parse_str($_POST['data'], $data);
	
	//insert new dept in to table
	global $wpdb;
	$terms_table_name = $wpdb->base_prefix . "seufolios_taxes_terms"; 
	$rows_affected = $wpdb->insert( $terms_table_name, array( 'tax_id' => $data['tax_id'], 'term_slug' => $data['term_slug'], 'term_settings' => $data['term_settings'] ) );
	
	$result = create_terms_table($data['tax_id']);
	
	echo $result;
	die();
}

function tax_edit_term_ajax() {
//edit existing taxonomy
	//create array from post data
	parse_str($_POST['data'], $data);
	
	global $wpdb;
	$terms_table_name = $wpdb->base_prefix . "seufolios_taxes_terms"; 
	$rows_affected = $wpdb->update( $terms_table_name, array('term_slug' => $data['term_slug'], 'term_settings' => $data ['term_settings'] ), array( 'id' => $data['term_id'])  );
	
	echo create_terms_table($data['tax_id']);
	die();
}

function tax_delete_term_ajax() {
	//create array from post data
	parse_str($_POST['data'], $data);
	
	//delete course from table
	global $wpdb;
	$terms_table_name = $wpdb->base_prefix . "seufolios_taxes_terms"; 
	$sql = "DELETE FROM $terms_table_name WHERE id=" .$data['term_id'];
	$rows_affected = $wpdb->query($sql);
	
	echo create_terms_table($data['tax_id']);
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
        <h2>Department Courses</h2>
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
        <div id="courses_list">
            
        </div>
        <div id="courses_form">
            <h2>Add New Course</h2>
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
		$dept_table_name = $wpdb->base_prefix . "seufolios_depts"; 
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
	$course_table_name = $wpdb->base_prefix . "seufolios_courses"; 
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
	$course_table_name = $wpdb->base_prefix . "seufolios_courses"; 
	$sql = "DELETE FROM $course_table_name WHERE id=$course_id";
	$rows_affected = $wpdb->query($sql);
	
	echo create_course_table($dept_id);
	die();
}

function create_course_table($dept_id) {
//used in courses network admin page

	//create html and return
	$result = "<table class='seufolios'>\n
		<tr><th>Number</th><th>Title</th></tr>";
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
		margin:0 2em 0 0;	
	}
	table.seufolios { border-spacing:0; }
	table.seufolios th, table.seufolios td {
		text-align:left;
		padding-right:2em;
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
	td.title {padding-left:5px;}
	
	div#sections_form, div#questions_form {	display:none; }
	form * {vertical-align:middle;}
	form.inline * {padding:0; margin:0; height:20px;}
	.align_right {text-align:right; margin-right:2em;}
	</style>
	<div class="wrap">
        <h2>Department Evaluations</h2>
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
			document.getElementById('sections_form').style.display = 'block';
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
					<td>
					    <button id='edit_$section->id' class='edit_button' type='button' onclick='edit_section($section->id, $section->order_loc);'>Edit</button> &nbsp;&nbsp;
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
	$eval_sections_table_name = $wpdb->base_prefix . "seufolios_eval_sections"; 
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
	$eval_sections_table_name = $wpdb->base_prefix . "seufolios_eval_sections"; 
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
	$eval_sections_table_name = $wpdb->base_prefix . "seufolios_eval_sections"; 
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
	$eval_questions_table_name = $wpdb->base_prefix . "seufolios_eval_questions"; 
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
	$eval_questions_table_name = $wpdb->base_prefix . "seufolios_eval_questions"; 
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
	$eval_questions_table_name = $wpdb->base_prefix . "seufolios_eval_questions"; 
	$sql = "DELETE FROM $eval_questions_table_name WHERE id=$question_id";
	$rows_affected = $wpdb->query($sql);
	
	echo create_question_table($sec_id);
	die();
}