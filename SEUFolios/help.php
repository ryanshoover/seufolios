<?php
//Content related to the inline help options for SEUFolios

//*** hooks

add_action('insert_help_hook','insert_help'); //custom hook
add_action('enable_help_hook', 'enable_help'); //custom hook

add_action('wp_ajax_replace_help', 'replace_help');
add_action('wp_ajax_add_help_fields', 'add_help_fields');
add_action('wp_ajax_help_edit_field', 'help_edit_field');
add_action('wp_ajax_help_delete_field', 'help_delete_field');

//Add network admin screen for departments
add_action('network_admin_menu', 'setup_admin_page');

//Setup help sql table
$core_plugin_url = (trailingslashit( plugin_dir_path( __FILE__ ) )) .'SEUFolios.php';
register_activation_hook( $core_plugin_url, 'create_help_sqltable' );

//Global vars
//global $help;
$help = array();

function insert_help($help_key) {
//inserts the help icon
	do_action('enable_help_hook'); //lets us use inline help
	
	$img_path = (trailingslashit( plugin_dir_url( __FILE__ ) )) .'help/questionmark.png';
	echo "<img src='$img_path' class='help_button' onclick='showHelp(this, \"$help_key\");'>";
}

function return_help($help_key) {
//inserts the help icon
	do_action('enable_help_hook'); //lets us use inline help
	
	$img_path = (trailingslashit( plugin_dir_url( __FILE__ ) )) .'help/questionmark.png';
	return "<img src='$img_path' class='help_button' onclick='showHelp(this, \\\"" .$help_key ."\\\");'>";    //'" .$help_key ."');'>";
}

function enable_help() {
//includes the background files and creates the div
	$path = (trailingslashit( plugin_dir_url( __FILE__ ) )) .'help/';
	wp_enqueue_style('help_css', $path. 'styles.css', false, '1.1','all');
	wp_enqueue_script('help_js', $path. 'scripts.js', false, '1.1', false);
}

function call_help($help_key){
	// not used
	do_action('insert_help_hook');
}

function replace_help() {
	global $wpdb;
	$help_table_name = 'wp_seufolios_help';  //disabled because prefix changes in multisite $wpdb->prefix . "seufolios_depts"; 
	$sql = "SELECT content FROM $help_table_name WHERE help_key='" .$_POST['data'] ."'";
	$results = $wpdb->get_results($sql);
	echo $results[0]->content;
	die();
}

function create_help_sqltable() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
    //Department
	$help_table_name = "wp_seufolios_help"; 
   
	$sql_help = "CREATE TABLE $help_table_name (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  help_key TINYTEXT NOT NULL,
	  content MEDIUMTEXT NOT NULL,
	  UNIQUE KEY id (id)
	);";
	
	dbDelta($sql_help);
}

function get_helps() {
	global $wpdb;
	$help_table_name = 'wp_seufolios_help';  //disabled because prefix changes in multisite $wpdb->prefix . "seufolios_depts";
	 
	$sql = "SELECT * FROM $help_table_name ORDER BY help_key ASC";
	
	$results = $wpdb->get_results($sql);
	
	return $results;	
}

function setup_admin_page() {
	add_menu_page('SEUFolios Help Fields', 'Help Fields', 'manage_options', 'seufolios_help', 'control_help_fields','' , 23);
}

function control_help_fields() {
//creates the help network admin page
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	?>
	<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js?ver=1.3.2'></script>
    <style>
	table.seufolios {
		border-spacing:0;
		width: 80%;
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
        <h2>SEUFolios Help Fields</h2>
        
        <div id="help_fields">
            <?php echo create_help_table(); ?>
        </div>
        <div id="help_form">
            <h2>Add New Help Field</h2>
            <form name="add_new" id="add_new" method="POST">
            	<table>
                <tr><td><label for="new_key">Key</label><? insert_help('help_key'); ?></td><td><input type="text" name="new_key"></td></tr>
                <tr><td><label for="new_content">Content</label><? insert_help('help_content'); ?></td><td><textarea name="new_content" cols="50" rows="5" ></textarea></td></tr>
                <tr><td></td><td><input type="submit" value="Add Help Field" /></td></tr>
                </table>
            </form>
        </div>
    </div>
    
    <script>
		jQuery('#add_new').submit(function() {
		  
		  var b = jQuery(this).serialize();
		  jQuery.post( ajaxurl, 
		  		  {
		  			'action':'add_help_fields', 
					'data': b
				  },
				  function (response) {
					  jQuery('#help_fields').html(response);
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

function create_help_table() {
	$return =  "<table class='seufolios'> \n <tr><th>Key</th><th>Content</th><th></th></tr>";
	$helps = get_helps();
	foreach($helps as $help) {
		$return .= "<tr id='row_$help->id'><td>$help->help_key</td><td>$help->content</td><td><button onclick='editHelp(\"$help->id\");'>Edit</button></td></tr>\n";
	}
	$return .= "</table>\n";
	
	return $return;
}

function add_help_fields() {
//add new help field
	//create array from post data
	$data = array();
	$data_1 = explode('&', $_POST['data']);
	foreach($data_1 as $line) {
		$line1 = explode('=', $line);
		$data[$line1[0]] = $line1[1];	
	}
	
	//move array to variables & changes + to space
	$key = urldecode($data['new_key']);
	$content = urldecode($data['new_content']);
	
	//insert new course in to table
	global $wpdb;
	$help_table_name = "wp_seufolios_help"; 
	$rows_affected = $wpdb->insert( $help_table_name, array( 'help_key' => $key, 'content' => $content ) );
	
	//create html and return
	echo create_help_table();
	die();
}

function help_edit_field() {
	//create array from post data
	$data = array();
	$data_1 = explode('&', $_POST['data']);
	foreach($data_1 as $line) {
		$line1 = explode('=', $line);
		$data[$line1[0]] = $line1[1];	
	}
	
	//move array to variables & changes + to space
	$key = urldecode($data['help_key']);
	$content = urldecode($data['content']);
	$id = $data['id'];
	
	//insert new course in to table
	global $wpdb;
	$help_table_name = "wp_seufolios_help"; 
	$rows_affected = $wpdb->update( $help_table_name, array( 'help_key'=>$key, 'content'=>$content), array( 'id' => $id) );
	
	//create html and return
	echo create_help_table();
	die();
}

function help_delete_field() {
	$id = $_POST['data'];
	
	//delete help from table
	global $wpdb;
	$help_table_name = "wp_seufolios_help"; 
	$sql = "DELETE FROM $help_table_name WHERE id=$id";
	$rows_affected = $wpdb->query($sql);
	
	echo create_help_table();
	die();
}


?>