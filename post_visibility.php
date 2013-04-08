<?php
//code to control individual post visibility
//based on plugin social-access-control (should be able to Google it)



//action hooks
function enable_post_visibility() {
	include_once('post_visibility/post_visibility_options.php');
	
	add_filter('comments_array', 'hide_comment', 10000);
	add_filter('get_comments_number', 'hide_comment_number', 10000);
	
	add_filter('posts_where', 'filter_posts', 10000);
	add_filter('single_post_title', 'filter_title', 10000, 2);
	add_filter('the_title', 'filter_title', 10000, 2);
	add_filter('the_title_rss', 'filter_title', 10000, 2);
	
	add_filter('the_content', 'filter_content', 10000);
	add_filter('the_excerpt', 'hide_text', 10000);
	
	//added by rsh - implement privacy with pages
	add_filter('wp_list_pages_excludes', 'filter_pages', 10000);
	
	// Added by Justin at Multinc
	add_action('edit_form_advanced','edit_post_controls', 10000);
	add_action('edit_page_form', 'edit_post_controls', 10000); //added by rsh - include edit_post in page edits
	
	add_action('wp_insert_post', 'save_post', 10000);
	
	//add_filter('getarchives_where',	array('social_access_control','filter_getarchives_where'), 10000);
}

//----------------------------------------
//for general setup and values

global $social_access_control_default_private_message;
$social_access_control_default_private_message = 'Sorry, you do not have sufficient privileges to view this post.';

function get_private_message() {
	$message = get_option("Social_Access_Control_private_message");

	if (!$message) {
		//global $social_access_control_default_private_message;
		//$message = $social_access_control_default_private_message;
		$message = 'Sorry, you do not have sufficient privileges to view this post.';
	}

	return $message;
}

function get_user_default_setting($role) {
	if ( $role == 'Administrator' ) return 'allow'; //ALWAYS allow admins to see everything
	
	$setting = get_option("user_default_setting_$role", 'allow'); //default setting is allow
	return $setting;
}

// --------------------------------------------------------------------
//to check if a post should be hidden
function post_should_be_hidden($postid)
{
	global $current_user;
	
	$user = $current_user;
	
	//designed for unique_url_authentication plugin not currently in SEUFolios (for private feeds)
	// we let the users of unique_url_authentication pass through if it's enabled
	if (is_callable(array('unique_url_authentication','get_user_id_from_authentication'))) {
		$personalized_url_user = unique_url_authentication::get_user_id_from_authentication();
		if ($user->ID==0 && $personalized_url_user!=0)
			$user = new WP_User($personalized_url_user);
	}
	
	return post_should_be_hidden_to_user($postid, $user);
}

function post_should_be_hidden_to_user($postid, $current_user) {
	// Sometimes this is called as post_should_be_hidden($post->ID) when $post
	// is null. This happens for admin pages, plugins, etc.. In this case
	// $postid will also be null, and we want to return false.
	if (is_null($postid))
		return false;

	$post = get_post($postid);
	
	if ($post->post_status == 'static')//!!! || $post->post_type == 'page')
		return false;

	if (!isset($postid))
		return true;

	$role = get_the_user_role($current_user->user_login);
	
	$per_post_setting = get_user_post_setting($role, $postid);
	
	if ($per_post_setting=='deny')
		return true;
		
	if ($per_post_setting=='allow')
		return false;
		
	// original continues...
	return false;
}

function get_user_post_setting($role, $postid) {
	$accessiable_list = get_post_meta($postid, '_accessible_users');
	$inaccessiable_list = get_post_meta($postid, '_inaccessible_users');
	
    if ($accessiable_list && in_array($role, $accessiable_list))
		return "allow";
		
	if ($inaccessiable_list && in_array((string)$role, $inaccessiable_list))
		return "deny";
	
	//else, return default
	$setting = get_user_default_setting($role);
	return $setting;
}

// Added by Justin at Multinc
function get_posts_visible_to_user($user) {
	global $wpdb;
	
	$sql = "SELECT ID FROM $wpdb->posts";
	$posts = $wpdb->get_col($sql);
	foreach ($posts as $key => $value) {
		if (post_should_be_hidden_to_user((int)$value, $user)){
			unset($posts[$key]);
		}
	}
	return $posts;
}

// --------------------------------------------------------------------
// !!not used
function get_social_access_control_for_user($category_id, $user) {
	$user = wp_get_current_user();
	
	if ($user->has_cap('manage_categories')) 
		return true;

	$role = get_the_user_role($user->login);
	
	if ($role == 'Administrator')
		return true;
	
	if ($role == 0)
		return get_option("Social_Access_Control_cat_${category_id}_anonymous");

	$visible = get_option("Social_Access_Control_cat_${category_id}_user_${user_id}"); //check

	if ($visible == false)
		$visible = get_option("Social_Access_Control_cat_${category_id}_default");
		
	// Added by Justin at Multinc
	// we also let logged in users to gain access to the public categories
	// assuming public categories are categories that can be viewd by anonymous users 
	if ($visible == false)
		$visible = get_option("Social_Access_Control_cat_${category_id}_anonymous");

	return $visible;
}

//--------------------------------------
//filtering the parts of a post
function filter_posts($sql)
{
	global $current_user;
	
	if (is_feed() && get_option('Social_Access_Control_show_title_in_feeds') ||
			strpos($sql, 'post_status = "static"') !== false ||
			strpos($sql, 'post_type = \'page\'') !== false)
		return $sql;

	if (!is_feed() && (
			get_option('Social_Access_Control_post_policy') == 'show title' ||
			get_option('Social_Access_Control_post_policy') == 'show message' ||
			// For backwards compatibility
			get_option('Social_Access_Control_show_private_message') ))
		return $sql;

	// Added by Justin
	// to filter out posts that user don't have permission to read
	$visible_posts = get_posts_visible_to_user($current_user);
    $sql = $sql." AND ID IN (".implode(",", $visible_posts).")";
	return $sql;
}

function filter_title($text, $post_to_check=null)
{
	$post_id = $post_to_check->ID;

	global $post;

	if (is_null($post_id))
		$post_id = $post->ID;

	if (!post_should_be_hidden($post_id)) {
		return $text;
	}

	$filtered_title = $text; 
	$policy = get_option('Social_Access_Control_post_policy');
	
	if ($policy == 'hide' || !$policy ) 
		$filtered_title = '';
		
	foreach (debug_backtrace() as $bt)  //solves a bug in his code where it was posting the <div> to html title
		if ($bt['function'] == 'single_post_title')
			return "$filtered_title";

	return $filtered_title;
}

function filter_content($text)
{
	global $post, $current_user;
	
	if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') == true)
		return $text;

	if (post_should_be_hidden($post->ID)) {
		if (get_option('Social_Access_Control_post_policy') == 'show title')
			$text = "<div class='social_access_control_protected_post'>" .
				get_private_message() . "</div>";
		else
			$text = '';
	}

	return $text;
}

function hide_text($text)
{
	if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') == true)
		return $text;

	global $post;

	if (post_should_be_hidden($post->ID))
		$text = '';

	return $text;
}

function filter_pages()
{
	global $current_user;
	global $wpdb;
	
	if (!(get_option('Social_Access_Control_post_policy') == 'show title' || get_option('Social_Access_Control_post_policy') == 'show message' ||
			// For backwards compatibility
			get_option('Social_Access_Control_show_private_message')) )
    {
	
		$sql = "SELECT ID FROM $wpdb->posts";
		$posts = $wpdb->get_col($sql);
		$excluded_posts = array();
		foreach ($posts as $key => $value) {
			if (post_should_be_hidden_to_user((int)$value, $current_user)){
				$excluded_posts[]=(int)$value;
			}
		}
		return $excluded_posts;
	}

}

// --------------------------------------------------------------------
//comments 
function hide_comment($text)
{
	global $current_user;
	$user_role = get_the_user_role($current_user->user_login);

	$comment_can_read = get_option(user_comment_visible, 'deny');
	
	if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') == true)
		return $text;

	global $post;

	if (post_should_be_hidden($post->ID))
		$text = '';
	
	if ($user_role == 'World')
		if ($comment_can_read == 'deny')
			$text = '';
	
	return $text;
}

function hide_comment_number($count)
{
	global $current_user;
	$user_role = get_the_user_role($current_user->user_login);
	$comment_can_read = get_option(user_comment_visible, 'deny');
	
	if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') == true)
		return $count;

	global $post;

	if (post_should_be_hidden($post->ID))
		$count = 0;
	
	if ($user_role == 'World' && $comment_can_read == 'deny')
		$count = 0;
	
	return $count;
}

//-----------------------------------------------------
//edit post html
function edit_post_controls() {
	global $post;
	echo '<div id="socialaccessdiv" class="postbox if-js-closed">';
	echo '<h3>Post Visibility</h3>';
	echo '<div class="inside">';
	echo '<p>Choose who is allowed to see this post and its comments</p>';
	echo "<table>";
	echo "<tr>";
	echo "<th></th>"; //changed RSH 07.19.2011 from <th>Users</th>
	echo "<th>Allow</th>";
	echo "<th>Deny</th>";
	//echo "<th>Default</th>";
	echo "</tr>";
	
	global $wp_roles;
	$roles = $wp_roles->role_names;
	unset($roles['author'], $roles['contributor'], $roles['subscriber'], $roles['editor']); //removes core roles, not used by students
	$roles[] = 'World';  //add 'world' (the non-logged-in option) to the array

	foreach ($roles as $role) {
	  if($role == 'Administrator') {
		//give administrators blanket access to all posts  
		echo "<input type=\"hidden\" name=\"user_post_access_setting_$role\" value=\"allow\">   ";  
	  } else {
		echo "<tr>";
		echo "<td>$role</td>";
		echo "<td align=\"center\"><input type=\"radio\" name=\"user_post_access_setting_$role\" value=\"allow\"";
		$per_post_setting = get_user_post_setting($role, $post->ID);
		if ($per_post_setting=='allow')
			echo " checked";
		echo " /></td>";
		echo "<td align=\"center\"><input type=\"radio\" name=\"user_post_access_setting_$role\" value=\"deny\"";
		if ($per_post_setting=='deny')
			echo " checked";
		echo " /></td>";
		echo "</tr>";
	  }
	}
	
	echo "</table>";
	echo "</div>";
	echo "</div>";
}

function save_post($postid) {
	global $wp_roles;
	$roles = $wp_roles->role_names;
	$roles[] = 'World'; //add world (the non-logged-in option) to the roles array
	
	foreach ($roles as $role) {
		if (isset($_POST["user_post_access_setting_$role"])) {
			if ($_POST["user_post_access_setting_$role"]=='allow')
				add_post_meta($postid, '_accessible_users', $role);
			else
				delete_post_meta($postid, '_accessible_users', $role);
				
			if ($_POST["user_post_access_setting_$role"]=='deny')
				add_post_meta($postid, '_inaccessible_users', $role);
			else
				delete_post_meta($postid, '_inaccessible_users', $role);
				
			if ($_POST["user_post_access_setting_$role"]=='default') {
				delete_post_meta($postid, '_accessible_users', $role);
				delete_post_meta($postid, '_inaccessible_users', $role);
			}
		}
	}
	
}

?>