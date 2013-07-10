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
    <p style='max-width:500px;'>Pick all of the portfolios that your department needs to evaluate this round. All of the portfolios that you pick will show up on the faculty guide page.</p>
    <p>Create a guide page by adding the shortcode to a page on the main site.<pre>[folios2eval]</pre>Just be sure to hide the page from everyone but Professors</p>
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
	
	$return .= "<style>.folios li{color: #666;}.callout-right{font-size:0.85em;margin:2em;padding:0.5em;float:right; background-color:white;border:thin solid #ccc; width:250px;max-width:40%;box-shadow: 5px 5px 3px #888888;}</style>\n";
	
	$time = date('Y-m-d H:i:s', time()-7776000);	
	$sql = "SELECT id FROM $eval_table_name WHERE profid=".$user->ID ." AND submittime>'".$time."'";
	$result = $wpdb->get_results($sql);
	$return .= "<p>Hi $user->user_nicename,</p><p>";
	if($wpdb->num_rows) {$return .= "Congratulations! ";}
	$return .= "You've completed <strong>".$wpdb->num_rows."</strong> evaluations so far this semester";
	$sql = "SELECT id FROM $eval_table_name WHERE profid=".$user->ID;
	$result = $wpdb->get_results($sql);
	$return .= " (and <strong>".$wpdb->num_rows."</strong> all time).</p>\n<hr>\n";
	
	$temp_return = "<p class='callout-right'>When you're ready to give feedback on the portfolio click the <em>Evaluate</em> link in the black admin bar at the top of the screen.<br><br>Oh, and once you've opened the evaluation form please either complete the evaluation or delete your feedback. Otherwise we assume you'll finish it and won't assign it to anyone else.</p>
	<p>There's still work to be done, though. The portfoios below need to be evaluated. Mind doing one?</p>\n<ul class='folios'>\n";
	$count = 0;
	foreach($folios as $folio) {
		$student = get_user_by('email',get_blog_option($folio->blog_id,'admin_email'));
		$result = $wpdb->get_results("SELECT id FROM $eval_table_name WHERE studentid=".$student->ID);
		if( $max_evals-$wpdb->num_rows > 0) {
			$temp_return .= "<li><a href='http://".$folio->domain .$folio->path ."' target='_blank'>".$student->user_nicename." - ".get_blog_option($folio->blog_id,'blogname')."</a>&nbsp;&nbsp;(Needs <strong>" .($max_evals-$wpdb->num_rows) ."</strong> more evaluation" .($max_evals-$wpdb->num_rows > 1 ? "s)" : ")") ."</li>\n";
			$count++;
		}
	}
	$temp_return .= "</ul>";
	
	if($count) {
		$return .= $temp_return;
	} else {
		$return .= "<p>Looks like the portfolios have all been evaluated this year. You're done!</p>";
	}
	
	//favorites
	$star_table_name = "wp_seufolios_starred";
	$portfolios = $wpdb->get_results( "SELECT * FROM $star_table_name WHERE deptid=$major" );
	if($portfolios) {
		$return .= "<h2>Department's Favorites</h2>\n<ul>";
		//$return .= print_r($portfolios, true);
		foreach ($portfolios as $p) {
			$profs = unserialize($p->profids);
			$p_url = parse_url($p->blogurl);
			if(strlen($p_url['path']) ) {
				if(substr($p_url['path'], -1) != '/') $p_url['path'] = $p_url['path'] .'/';
				$p_blog = get_blog_details( get_blog_id_from_url($p_url['host'], $p_url['path']) );
			} else $p_blog = get_blog_details( get_blog_id_from_url($p_url['host']) );
			$plist[]['txt'] = "<li><a href='$p->blogurl'>$p_blog->blogname</a> (" .count($profs) ." favorite" .(count($profs) > 1 ? "s)" : ")") ."</li>";
			$plist[]['count'] = count($profs);
		}
		usort($plist, "cmp_plist");
		foreach($plist as $pl) { $return .= $pl['txt']; }
		$return .= "</ul>";
	}
	
	return $return;
}

function cmp_plist($a, $b) {
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
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