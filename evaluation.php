<?php
//All code related to the evaluation tools

//Add link in admin bar
add_action('wp_enqueue_scripts', 'toolbar_scripts_method');
add_action( 'admin_bar_menu', 'toolbar_evaluation', 999 );

//Add network admin screen for evaluation question types
add_action('network_admin_menu', 'control_eval_ques');

//Setup eval sql table
$core_plugin_url = (trailingslashit( plugin_dir_path( __FILE__ ) )) .'SEUFolios.php';
register_activation_hook( $core_plugin_url, 'create_eval_sqltable' ); //individual evaluations
register_activation_hook( $core_plugin_url, 'create_eval_types_sqltable' ); //types of eval questions

//ajax hooks
add_action('wp_ajax_add_ques_type_submit', 'add_eval_type_save_ajax' );
add_action('wp_ajax_eval_edit_question_type', 'add_eval_type_edit_ajax' );
add_action('wp_ajax_eval_delete_question_type', 'add_eval_type_delete_ajax' );

function toolbar_scripts_method() {
	$current_user = wp_get_current_user();
	$user_role = get_user_role($current_user);
	global $blog_id;
	$site_details = get_blog_details($blog_id, 'siteurl');
	
	if($user_role == 'Professor' || $user_role == 'Super_admin') {
		echo "<script> var siteurl='" .urlencode($site_details->siteurl) ."'; </script>";
		$plugin_url = plugins_url() .'/SEUFolios/';
		wp_enqueue_script('seufolios_iframe_script', plugins_url( 'evaluation/js/scripts.js' , __FILE__ ) );
		wp_register_style( 'prefix-style', plugins_url('evaluation/css/styles.css', __FILE__) );
		wp_enqueue_style( 'prefix-style' );
	}
}


function toolbar_evaluation( $wp_admin_bar ) {
	if(is_admin()) return false;
	
	global $wpdb;
    $eval_table_name = "wp_seufolios_evaluations"; 

	$current_user = wp_get_current_user();
	$user_role = get_user_role($current_user);
	$admin_id = get_user_id_from_string( get_blog_option($current_blog->blog_id, 'admin_email'));
	$submit = "profid=$current_user->id" ."&" ."studentid=$admin_id" ."&";

	$sql = "SELECT answers FROM $eval_table_name WHERE profid=$current_user->id and studentid=$admin_id";
	$results = $wpdb->get_results($sql);
	
	if(count($results) > 0) $submit .= $results[0]->answers;
	
	if($user_role == 'Professor' || $user_role == 'Super_admin') {
		$plugin_url = plugins_url() .'/SEUFolios/';
		  
		$args = array(
		  'id' => 'seufolios-evaluation',
		  'title' => 'Evaluate',
		  'href' => 'http://ryanhoover.net',
		  'meta' => array('onclick' => "insertIframe(\"$plugin_url\", \"$submit\"); return false;")
		);
	  
		$wp_admin_bar->add_node($args);
	}
}

function create_eval_sqltable() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$eval_table_name = "wp_seufolios_evaluations"; 
   
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
}

function get_user_role($user) { 
        global $wpdb, $wp_roles; 
		
		//$user = get_user_by('login', $user_login );
        if ( !isset($wp_roles) ) 
            $wp_roles = new WP_Roles(); 
			
		if ( current_user_can('manage_network') )
			return 'Super_admin';
	 
	 	//return $wp_roles;
		foreach($wp_roles->role_names as $role => $Role) {
		  $caps = $wpdb->prefix . 'capabilities'; 
		  
		  if (!empty($user->$caps) && array_key_exists($role, $user->$caps)) {
			  return $Role; 
		  } 
		} 

} 

require_once('evaluation/folios2eval.php');

//*******************************
//network admin page

function control_eval_ques() {
	add_menu_page('SEUFolios Evaluation Question Types', 'Eval Q Types', 'manage_options', 'seufolios_eval_ques', 'control_eval_ques_options','' , 22);
}

function control_eval_ques_options() {
//creates the eval questions network admin page
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js?ver=1.3.2'></script>
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
	table.seufolios .code {
		font-family:"Courier New", Courier, monospace;	
		max-width: 500px;
		max-height:3em;
		overflow:scroll;
	}
	</style>
    
	<div class="wrap">
    	<div id="eval_body">
        <?php echo create_eval_q_table(); ?>
        </div>
        <div id="dept_form">
            <h2>Add New Question Type</h2>
            <form name="add_new" id="add_new" method="POST">
                <label for="new_slug">Slug</label> <input type="text" name="new_slug" id="new_slug">
                <label for="new_display">Display Name</label> <input type="text" name="new_display" id="new_display">
                <br>
                <label for="new_code">Question Code</label><br>
                <textarea rows="10" cols="50" name="new_code" id="new_code"></textarea>
                <br><br>
                <input type="submit" value="Add Question Type" />
            </form>
        </div>
    </div>
    
    <script>
		jQuery('#add_new').submit(function() {
		  //var b = jQuery(this).serialize();
		  var b = 'new_slug=' +escape(document.getElementById('new_slug').value) + '&new_display=' + escape(document.getElementById('new_display').value) + '&new_code=' + escape(document.getElementById('new_code').value);
		  
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'add_ques_type_submit', 
					'data': b
				  },
				  function (response) {
					  jQuery('#eval_body').html(response);
				  });
		  //reset form
		  jQuery(this).each (function(){
			this.reset();
		  });
		  return false;
		});
		
		//Edit a section
		function edit_q(q_id) {
			var tr = document.getElementById('secrow_' + q_id);
			var td_slug = tr.getElementsByClassName('slug');
			var old_slug = td_slug[0].innerHTML;
			var td_displayName = tr.getElementsByClassName('displayName');
			var old_displayName = td_displayName[0].innerHTML;
			var td_code = tr.getElementsByClassName('code');
			var old_code = td_code[0].innerHTML;
			old_code = old_code.replace('<xmp>', '');
			old_code = old_code.replace('</xmp>', '');
			var innerHTML = "<td><input type='hidden' name='q_id' id='q_id' value='" + q_id + "'>" +
							"<input type='text' name='q_slug' id='q_slug' size='10' value='" + old_slug + "'></td>" +
							"<td><input type='text' name='q_displayName' id='q_displayName' value='" + old_displayName + "'></td>" +
							"<td><textarea name='q_code' id='q_code' cols='25' rows='3'>"+old_code+"</textarea></td>" +
							"<td><button type='submit' onclick='edit_q_submit();'>Done</button> &nbsp;&nbsp; " +
							"<button id='delete_"+q_id+"' class='delete_button' type='button' onclick='delete_q("+q_id+")'>Delete</button></td>";
			tr.innerHTML = innerHTML;
			return false;	
		}
		
		//Submit the Edit Section
		function edit_q_submit() {
		  
		  var b = 'id=' +document.getElementById('q_id').value + '&slug=' +escape(document.getElementById('q_slug').value) + '&displayName=' + escape(document.getElementById('q_displayName').value) + '&code=' + escape(document.getElementById('q_code').value);
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'eval_edit_question_type', 
					'data': b
				  },
				  function (response) {
					  jQuery('#eval_body').html(response);
				  });
		  return false;
		}
		
		//Delete a section
		function delete_q(q_id) {
			jQuery.post( ajaxurl, 
		  		  {
		  			'action':'eval_delete_question_type', 
					'data': q_id
				  },
				  function (response) {
					  jQuery('#eval_body').html(response);
				  });
		  
		  return false;			
		}
	</script>
    
    <?php
}

function create_eval_q_table() {
	//generates html for section table
	//create html and return
	$result = "<h2>Evaluation Questions</h2>\n
		<table class='seufolios' id='seufolios_questions_table'>\n
		<tr><th>slug</th><th>Display Name</th><th>Code</th><th></th></tr>";
	$questions = get_question_types();
	foreach($questions as $q) {
		$code = urldecode($q->code);
		$result .= "<tr id='secrow_$q->id'>
					<td class='slug'>$q->slug</td>
					<td class='displayName'>$q->displayName</td>
					<td class='code'><xmp>$code</xmp></td>
					<td>
					    <button id='edit_$q->id' class='edit_button' type='button' onclick='edit_q($q->id);'>Edit</button>
					</td></tr>\n";	
	}
	
	$result .= "</table>";
	
	return $result;
}

function add_eval_type_save_ajax() {
	//used in eval ques network page to save a new ques type	
		//create array from post data
		$data = array();
		$data_1 = explode('&', $_POST['data']);
		foreach($data_1 as $line) {
			$line1 = explode('=', $line);
			$data[$line1[0]] = $line1[1];	
		}

		//move array to variables & changes + to space
		$new_slug  = $data['new_slug'];
		$new_display = $data['new_display'];
		//$new_display = str_replace('+',' ',$new_display);
		$new_code = $data['new_code'];
		
		//insert new ques in to table
		global $wpdb;
		$eval_ques_table_name = "wp_seufolios_eval_ques_types"; 
		$rows_affected = $wpdb->insert( $eval_ques_table_name, array( 'slug' => $new_slug, 'displayName' => $new_display, 'code' => $new_code ) );
		
		//get all depts from table
		$sql = "SELECT * FROM $eval_ques_table_name";
		$questions = $wpdb->get_results($sql);
		echo create_eval_q_table();
		/*
		//create html and return
		$result = "<table> \n
				   <tr><th>Abbr</th><th>Name</th></tr>";
		 foreach($depts as $dept) {
		   $result .= "<tr><td>$dept->abbr</td><td>$dept->name</td></tr>\n";	
		 }
		$result .= "</table>";
		
		echo $result;
		*/
		die();
}

function add_eval_type_edit_ajax() {
	//edit existing evaluation section
	//create array from post data
	$data = array();
	$data_1 = explode('&', $_POST['data']);
	foreach($data_1 as $line) {
		$line1 = explode('=', $line);
		$data[$line1[0]] = $line1[1];	
	}
	//move array to variables & changes + to space
	$slug = urldecode($data['slug']);
	$displayName = urldecode($data['displayName']);
	$code = $data['code'];
	$id = $data['id'];
	
	//edit section in wpdb
	global $wpdb;
	$eval_table_name = "wp_seufolios_eval_ques_types"; 
	$rows_affected = $wpdb->update( $eval_table_name, array( 'slug' => $slug, 'displayName' => $displayName, 'code' => $code ), array( 'id' => $id) );
	
	//create html and return
	echo create_eval_q_table();
	die();
}

function add_eval_type_delete_ajax() {
	$data = $_POST['data'];
	$id = $data;
	
	//delete course from table
	global $wpdb;
	$eval_table_name = "wp_seufolios_eval_ques_types"; 
	$sql = "DELETE FROM $eval_table_name WHERE id=$id";
	$rows_affected = $wpdb->query($sql);
	
	echo create_eval_q_table();
	die();
}

function get_question_types() {
	global $wpdb;
	$eval_table_name = 'wp_seufolios_eval_ques_types';  //disabled because prefix changes in multisite $wpdb->prefix . "seufolios_depts";
	 
	$sql = "SELECT * FROM $eval_table_name ORDER BY slug ASC";
	
	$results = $wpdb->get_results($sql);
	
	return $results;
}

function create_eval_types_sqltable() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
    //Eval question types
	$eval_table_name = "wp_seufolios_eval_ques_types"; 
   
	$sql_eval = "CREATE TABLE $eval_table_name (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  slug tinytext NOT NULL,
	  displayName tinytext NOT NULL,
	  code mediumtext NOT NULL,
	  UNIQUE KEY id (id)
	);";
	
	dbDelta($sql_eval);
}