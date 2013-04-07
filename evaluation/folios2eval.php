<?php

register_activation_hook( $core_plugin_url, 'create_eval_day_sqltable' ); //portfolios to evaluate
add_action('network_admin_menu', 'folios2eval');
add_action('wp_ajax_folios2eval_select_dept', 'folios2eval_deptselect');
add_shortcode( 'folios2eval', 'folios2eval_profpage' );

function create_eval_day_sqltable() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$eval_table_name = "wp_seufolios_folios2eval"; 
   
	$sql_eval = "CREATE TABLE $eval_table_name (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  blogid mediumint(9) NOT NULL,
	  deptid mediumint(9) NOT NULL,
	  UNIQUE KEY id (id)
	);";
	
	dbDelta($sql_eval);
}

function folios2eval() {
	add_submenu_page( 'seufolios_departments', 'Portfolios to evaluate', 'Folios to Eval', 'manage_options', 'seufolios_folios2eval', 'setup_folios2eval');
}

function setup_folios2eval() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	if( isset( $_POST['folios2eval'] )) {
		global $wpdb;
		$folios_table_name = "wp_seufolios_folios2eval"; 
		$sql = "DELETE FROM $folios_table_name WHERE deptid=" .$_POST['dept_id'];
		$result = $wpdb->query( $wpdb->prepare($sql) );
		
		foreach($_POST['folios2eval'] as $key=>$value) {
			$wpdb->insert( 
				  $folios_table_name, 
				  array( 
					  'blogid' => $value, 
					  'deptid' => $_POST['dept_id'] 
				  ), 
				  array( 
					  '%d', 
					  '%d' 
				  ) 
			  );
		}
	}
	
	?>
    <style>
	#folios_list {
		margin:1em 0 0 1em;	
	}
	</style>
    <div class="wrap">
    <h2>Select portfolios for evaluation</h2>
    <p style='max-width:500px;'>Pick all of the portfolios that your department needs to evaluate this round. Once you've picked them, click <em>Set Portfolios</em>. All of the portfolios that you pick will show up on the faculty guide page [INSERT LINK].</p>
    <div id="choose_dept">
        <form name="folios2eval_choose_dept" id="folios2eval_choose_dept">
            <select name="folios2eval_dept_select" id="folios2eval_dept_select">
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
    
    <div id="folios_list">
    
    </div>
    </div><!--/wrap-->
    
    <script>
    jQuery('#folios2eval_dept_select').change(function() 
		{
		   var b = jQuery(this).attr('value');
		   jQuery.post( ajaxurl, 
		  		  {
		  			'action':'folios2eval_select_dept', 
					'data': b
				  },
				  function (response) {
					  jQuery('#folios_list').html(response);
				  });
			
		});
	</script>
    
    <?php
}

function folios2eval_deptselect() {
	global $wpdb;
	$dept_id = $_POST['data'];
	
	//create html and return
	$result = "
	<form name='folios2eval_list' id='folios2eval_list' method='post' action='#'>
	<input type='hidden' value='$dept_id' name='dept_id'>
	";
	
	$folios = get_folios($dept_id);
	if(!empty($folios) ) {
		foreach($folios as $folio) {
			$user = get_user_by('email',get_blog_option($folio->blog_id,'admin_email'));
			$checked = $wpdb->get_var( $wpdb->prepare("SELECT id FROM wp_seufolios_folios2eval WHERE blogid=".$folio->blog_id ." AND deptid=".$dept_id) );
			
			$result .= "<input type='checkbox' name='folios2eval[]' value='".$folio->blog_id."'";
			if($checked) $result .= " checked";
			$result .= ">&nbsp;<label for='folios2eval-$'>".$user->user_nicename." - ".get_blog_option($folio->blog_id,'blogname')."</label><br>\n";	
		}
	} else $result .= "<p>Sorry, but this department doesn't have any portfolios yet.</p>";
	
	$result .= "<br><input type='submit' value='Set Portfolios'></form>";
	echo $result;
	die();
}

function folios2eval_profpage($atts) {
	global $wpdb;
	$user = wp_get_current_user();
	$major = get_the_user_major($user->ID);
	$eval_table_name = 'wp_seufolios_evaluations';
	
	$max_evals = 2; //!!HARD CODED!!!
	
	$return = '';
	
	$folio_ids = $wpdb->get_results("SELECT blogid FROM wp_seufolios_folios2eval WHERE deptid=".$major);
	foreach($folio_ids as $id) 
		$folios[] = get_blog_details($id->blogid);
	
	$return .= "<style>.folios li{color: #666;}</style>\n";
	
	$time = date('Y-m-d H:i:s', time()-7776000);	
	$sql = "SELECT id FROM $eval_table_name WHERE profid=".$user->ID ." AND submittime>'".$time."'";
	$result = $wpdb->get_results($sql);
	$return .= "<p>You've completed <strong>".$wpdb->num_rows."</strong> evaluations so far this semester.</p>\n";
	
	$return .= "<p>These portfoios need to be evaluated. Take your pick!</p>\n";
	$return .= "<ul class='folios'>\n";
	foreach($folios as $folio) {
		$student = get_user_by('email',get_blog_option($folio->blog_id,'admin_email'));
		$result = $wpdb->get_results("SELECT id FROM $eval_table_name WHERE studentid=".$student->ID);
		if( $max_evals-$wpdb->num_rows > 0) $return .= "<li><a href='http://".$folio->domain .$folio->path ."' target='_blank'>".$student->user_nicename." - ".get_blog_option($folio->blog_id,'blogname')."</a>&nbsp;&nbsp;(Needs <strong>" .($max_evals-$wpdb->num_rows) ."</strong> more evaluations)</li>\n";
	}
	$return .= "</ul>";
	
	return $return;
}

function get_folios($dept_id) {
	global $wpdb;
	$folios_table_name = "wp_blogs"; 
	$sql = "SELECT * FROM $folios_table_name";
	
	$blogs = $wpdb->get_results($sql);
	foreach($blogs as $blog) {
		if(get_blog_option($blog->blog_id, 'student_major', 0) == $dept_id) $folios[] = $blog;
	}
	
	return $folios;	
}

?>