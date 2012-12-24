<?php
//GUI options settings for post visibility

add_action('admin_menu', 'setup_social_access_control_options_page');

function setup_social_access_control_options_page() {
	if (function_exists('add_options_page'))
		add_options_page('Post Visibility', 'Post Visibility', 'manage_options', 'post_visibility_options','manage_social_access_control_options');
}

function manage_social_access_control_options() {
	handle_action();
	print_html();
}

//---------------------------------------
/*
function get_user_default_setting($role) {
	$setting = get_option("user_default_setting_$role", 'deny');
	return $setting;
}
*/
// --------------------------------------------------------------------

function handle_action() {
	global $_POST;

	if ($_POST['submit'] == 'Reset All Options') {
		delete_option('Social_Access_Control_private_message',false);
		delete_option('Social_Access_Control_post_policy',false);
		delete_option('Social_Access_Control_post_policy',false);
		delete_option('Social_Access_Control_show_if_any_category_visible',false);
		delete_option('Social_Access_Control_show_padlock_on_private_posts',false);

		delete_option('Social_Access_Control_show_title_in_feeds',false);

		delete_option('Social_Access_Control_show_private_categories',false);
		delete_option('Social_Access_Control_show_padlock_on_private_categories', false);
        
        //delete option for visible comments
        delete_option("user_comment_visible", false);
        
        
        //delete options for role visibility
        global $wp_roles;
		$roles = $wp_roles->role_names;
		$roles[] = 'World'; //add world (the non-logged-in option) to the roles array
        
        foreach ($roles as $role) {
          delete_option("user_default_setting_$role", false);
        }
		
		return;
	}

	if ( strpos($_POST['submit'], 'Update Options') !== false) {
		global $wp_roles;
		$roles = $wp_roles->role_names;
		$roles[] = 'World'; //add world (the non-logged-in option) to the roles array
        
        foreach ($roles as $role) {
            if (isset($_POST["user_default_access_setting_$role"])) {
                if ($_POST["user_default_access_setting_$role"]=='allow')
                    update_option("user_default_setting_$role", 'allow');
                else if($_POST["user_default_access_setting_$role"]=='deny')
                    update_option("user_default_setting_$role", 'deny');       
            }
        }

	if (isset($_POST["comment_visible"])) {
		if($_POST["comment_visible"]=='allow')
			update_option("user_comment_visible", 'allow');
		else if($_POST["comment_visible"]=='deny')
			update_option("user_comment_visible", 'deny');
	}
		

		update_option('Social_Access_Control_post_policy',
			$_POST['Social_Access_Control_post_policy']);

		if ($_POST['Social_Access_Control_show_if_any_category_visible'] == 'on')
			update_option('Social_Access_Control_show_if_any_category_visible', true);
		else
			update_option('Social_Access_Control_show_if_any_category_visible', false);

		update_option('Social_Access_Control_private_message',
			$_POST['Social_Access_Control_private_message']);

		if ($_POST['Social_Access_Control_show_padlock_on_private_posts'] == 'on')
			update_option('Social_Access_Control_show_padlock_on_private_posts', true);
		else
			update_option('Social_Access_Control_show_padlock_on_private_posts', false);


		//if ($_POST['Social_Access_Control_show_title_in_feeds'] == 'on')
        if ($_POST['Social_Access_Control_show_title_in_feeds'] == 'yes') //modified by RSH 01.10.2012 to simplify settings
			update_option('Social_Access_Control_show_title_in_feeds', true);
		else
			update_option('Social_Access_Control_show_title_in_feeds', false);


		// Old data
		delete_option('Social_Access_Control_show_private_message',false);

		if ($_POST['Social_Access_Control_show_private_categories'] == 'on')
			update_option('Social_Access_Control_show_private_categories', true);
		else
			update_option('Social_Access_Control_show_private_categories', false);

		if ($_POST['Social_Access_Control_show_padlock_on_private_categories'] == 'on')
			update_option('Social_Access_Control_show_padlock_on_private_categories', true);
		else
			update_option('Social_Access_Control_show_padlock_on_private_categories', false);

		if ($_POST['Social_Access_Control_show_warning_message'] == 'on') {
			update_option('Social_Access_Control_show_warning_message', true);
			update_option('Social_Access_Control_warning_message', $_POST['Social_Access_Control_warning_message']);
		}
		else
			update_option('Social_Access_Control_show_warning_message', false);

		return;
	}
}

function print_html() {
	print "<div class='wrap'>\n";
	print "\n<h2>" . 'Post Visibility Default Options' . "</h2>\n";

	$page_uri = $_SERVER['PHP_SELF'] . "?page=post_visibility_options"; //disabled - hardcoded the link name . plugin_basename(__FILE__);

	

	print "\n<form name='social_access_control' method='post' action='$page_uri'>\n";

	// --------------------------------------------------------------

	print <<<EOT
<script language="javascript">

function show_role()
{
	the_form = document.social_access_control;

	selected_id = the_form.role_selection.options[the_form.role_selection.selectedIndex].value;

	for(i=0; i < document.getElementById("visibilities").childNodes.length; i++)
	{
		childNode = document.getElementById("visibilities").childNodes[i];

		if (childNode.nodeName != "DIV") 
			continue;

		if (childNode.id == selected_id)
			iState = true;
		else
			iState = false;

		childNode.style.display = iState ? "" : "none";
	} 
}

function check_categories(role,checked)
{
	the_form = document.social_access_control;

	all_check_boxes = the_form.getElementsByTagName('input');

	for (var i = 0; i < all_check_boxes.length; i++)
		if (all_check_boxes[i].className == 'category_id_' + role)
			all_check_boxes[i].checked = checked;
}

</script>

EOT;


	$social_access_control_for = 'Default Access';
    global $wp_roles;
    $roles = $wp_roles->role_names;
    $roles[] = 'World'; //add world (the non-logged-in option) to the roles array

 
	print <<<EOT

<h3>$social_access_control_for</h3>
<p>By default, do you want to allow people to see posts, or deny them from seeing posts?<br />
You can override this setting at the bottom of each post or page you create.</p>
<table>
	<tr>
	<th></th>
	<th>Allow</th>
	<th>Deny</th>
	</tr>
EOT;

	foreach ($roles as $role) {
	  if($role == 'Administrator') {
		echo "<input type=\"hidden\" name=\"user_default_access_setting_$role\" value=\"allow\">   ";  
	  } else {
		echo "<tr>";
		echo "<td>$role</td>";
		echo "<td align=\"center\"><input type=\"radio\" name=\"user_default_access_setting_$role\" value=\"allow\"";
		$per_post_setting = get_user_default_setting($role);
		if ($per_post_setting=='allow')
			echo " checked";
		echo " /></td>";
		echo "<td align=\"center\"><input type=\"radio\" name=\"user_default_access_setting_$role\" value=\"deny\"";
		if ($per_post_setting=='deny')
			echo " checked";
		echo " /></td>";
		echo "</tr>";
	  }
	}
	
	echo "</table>";

	print "\n</div>\n\n";

	// --------------------------------------------------------------
	/* Removed on 01.10.2012 by RSH -- Simplifying the settings page
	$setting = get_option("user_comment_visible", 'deny');	
	
	print '<div id="comments">';
	print '<h3>Comments Visibility</h3>';
	print '<p>Do you want comments on your posts visible to the world?';
	print '<br /><b>*Note</b> for professors and peers, comments have the same visibility as their posts</p>';
    //print '<br />(Choose &quot;No&quot; if you have grade-related comments from professors on your blog)</p>';
	
	print '<table>';
	print '<tr>';
	
	echo "<td><input type=\"radio\" name=\"comment_visible\" value=\"allow\"";
	if ($setting=='allow')
			echo " checked";
	echo " />Yes&nbsp;&nbsp;&nbsp;</td>";
	echo "<td><input type=\"radio\" name=\"comment_visible\" value=\"deny\"";
	if ($setting=='deny')
			echo " checked";
	echo " />No</td>";
	
	print '</tr>';
	print '</table>';
	print '</div>';
	*/
    echo "<input type=\"hidden\" name=\"comment_visible\" value=\"allow\" />"; //Added by RSH - hidden value for the setting
	// -----------------------------------------
	
    /* Removed on 01.10.2012 by RSH -- Simplifying the settings page
	print "<h3>". __('Protected Posts in the Blog', 'social-access-control') ."</h3>\n";

	print '
<script language="javascript">

// Return the value of the radio button that is checked. Return an empty
// string if none are checked, or there are no radio buttons

function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if (radioLength == undefined) {
		if (radioObj.checked)
			return radioObj.value;
		else
			return "";
	}
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked)
			return radioObj[i].value;
	}
	return "";
}

function enable_disable_form_elements()
{
	the_form = document.social_access_control;

	private_message_div = document.getElementById("private_message_div");
	if (getCheckedValue(the_form.Social_Access_Control_post_policy) != "hide") {
		private_message_div.style.color = "black";
		the_form.Social_Access_Control_private_message.disabled = false;
		the_form.Social_Access_Control_private_message.style.color = "black";
		the_form.Social_Access_Control_show_padlock_on_private_posts.disabled = false;
	} else {
		private_message_div.style.color = "gray";
		the_form.Social_Access_Control_private_message.disabled = true;
		the_form.Social_Access_Control_private_message.style.color = "gray";
		the_form.Social_Access_Control_show_padlock_on_private_posts.disabled = true;
	}

	private_categories_div = document.getElementById("private_categories_div");
	if (the_form.Social_Access_Control_show_private_categories.checked) {
		private_categories_div.style.color = "black";
		the_form.Social_Access_Control_show_padlock_on_private_categories.disabled = false;
	} else {
		private_categories_div.style.color = "gray";
		the_form.Social_Access_Control_show_padlock_on_private_categories.disabled = true;
	}
	
	if (getCheckedValue(the_form.Post_View_Expire) == "counter") {
		the_form.Post_View_Expire_On_Time_In_Day.disabled = true;
		the_form.Post_View_Expire_On_Time_In_Day.style.color = "gray";
		the_form.Post_View_Expire_On_Counter_Number.disabled = false;
		the_form.Post_View_Expire_On_Counter_Number.style.color = "black";
	} else if (getCheckedValue(the_form.Post_View_Expire) == "time") {
		the_form.Post_View_Expire_On_Counter_Number.disabled = true;
		the_form.Post_View_Expire_On_Counter_Number.style.color = "gray";
		the_form.Post_View_Expire_On_Time_In_Day.disabled = false;
		the_form.Post_View_Expire_On_Time_In_Day.style.color = "black";
	} else {
		the_form.Post_View_Expire_On_Counter_Number.disabled = true;
		the_form.Post_View_Expire_On_Counter_Number.style.color = "gray";
		the_form.Post_View_Expire_On_Time_In_Day.disabled = true;
		the_form.Post_View_Expire_On_Time_In_Day.style.color = "gray";
	}
}
</script>

';

	print 'When someone doesn\'t have permission to see a post or page, what do you want to happen?';

	print '<p><input type="radio" name="Social_Access_Control_post_policy" onClick="enable_disable_form_elements()" value="hide"' .
		(get_option('Social_Access_Control_post_policy') == 'hide' ||
		// For backwards compatibility
		get_option('Social_Access_Control_post_policy') == false &&
		get_option('Social_Access_Control_show_private_message') == false ? ' checked' : '') .
		"> ". __('Hide the entire post.', 'social-access-control') ."<br>\n";

	print '<input type="radio" name="Social_Access_Control_post_policy" onClick="enable_disable_form_elements()" value="show title"' .
		(get_option('Social_Access_Control_post_policy') == 'show title' ? ' checked' : '') .
		"> ". __('Show the post\'s title, and a private message for the body text.', 'social-access-control') ."<br>\n";

	print '<input type="radio" name="Social_Access_Control_post_policy" onClick="enable_disable_form_elements()" value="show message"' .
		(get_option('Social_Access_Control_post_policy') == 'show message' ||
		// For backwards compatibility
		get_option('Social_Access_Control_show_private_message') == true ? ' checked' : '') .
		"> ". __('Show a private message for the title and nothing for the body text. (Known bug: Doesn\'t work with pages)', 'social-access-control') ."</p>\n";

	print "<div id=private_message_div>\n";

	$private_message = social_access_control::get_private_message();

	print "<p>". __('The private message:', 'social-access-control') ."<br>" .
		"<input name=Social_Access_Control_private_message type=text size=50" .
		" value=\"$private_message\" /></p>\n";
		
	print "</div>\n";
    */
    echo "<input type=\"hidden\" name=\"Social_Access_Control_post_policy\" value=\"hide\" />"; //Added by RSH - hidden value for the setting
    

    /* disabling padlock option - too buggy
	print "<p><input name=Social_Access_Control_show_padlock_on_private_posts type=checkbox" .
		 (get_option('Social_Access_Control_show_padlock_on_private_posts') ? " checked" : "") .
		 "> ". __('Show a padlock icon on the private post message.', 'social-access-control') ."</p>\n";
    
	print "</div>\n";

	print "<p><input name=Social_Access_Control_show_if_any_category_visible type=checkbox" .
		(get_option('Social_Access_Control_show_if_any_category_visible') ? " checked" : "") .
		"> ". __('Consider a message to be visible if the user can view <em>any</em> of its categories (rather than <em>all</em> of its categories).', 'social-access-control') ."</p>\n";
    
	// Added by Justin at Multinc
	print "<h3>". __('Warning message', 'social-access-control') ."</h3>\n";

	print "<p><input name=Social_Access_Control_show_warning_message type=checkbox " .
		(get_option('Social_Access_Control_show_warning_message') ? " checked" : "") .
		"> ". __('Show a warning message in the post.', 'social-access-control') ."</p>\n";

	print "<p><input name=Social_Access_Control_warning_message type=text size=50" .
		" value=\"".get_option('Social_Access_Control_warning_message')."\" /></p>\n";

	// Added end
    */
    /* Removed on 01.10.2012 by RSH -- Simplifying the settings page
	print "<h3>". __('Protected Posts in Feeds', 'social-access-control') ."</h3>\n";

	echo "<p>". __('Users can always see the entire feed in their web browser by logging into the blog and checking the <em>Remember me</em> option. This will store a cookie on their computer that will be read by wordpress when the browser requests the feed. However, for feed readers that do not have cookie support, you can set the following option to show the title but not the text of protected posts in your feeds.', 'social-access-control') ."</p>";

	print "<p><input name=Social_Access_Control_show_title_in_feeds type=checkbox " .
		(get_option('Social_Access_Control_show_title_in_feeds') ? " checked" : "") .
		"> ". __('Show the title and links (but not the summary or content) instead of hiding posts.', 'social-access-control') ."</p>\n";
    */
    echo "<input type=\"hidden\" name=\"Social_Access_Control_show_title_in_feeds\" value=\"yes\" />"; //Added by RSH - hidden value for the setting
    

/*disabled by RSH
	print "<h3>". __('The Category List', 'social-access-control') ."</h3>\n";

	print "<p><input name=Social_Access_Control_show_private_categories type=checkbox onClick='enable_disable_form_elements()'" .
		(get_option('Social_Access_Control_show_private_categories') ? " checked" : "") .
		"> ". __('Show private categories.', 'social-access-control') ."</p>\n";

	print "<div id=private_categories_div style='padding-left:2em'>\n";

	print "<p><input name=Social_Access_Control_show_padlock_on_private_categories type=checkbox " .
		(get_option('Social_Access_Control_show_padlock_on_private_categories') ? " checked" : "" ) .
		"> ". __('Show a padlock icon next to private categories.', 'social-access-control') ."</p>\n";
*/
	//print "</div>\n";
    
	print '
<script language="javascript">
enable_disable_form_elements();
</script>

<p class="submit">
<input type="submit" name="submit" value="Update Options &raquo;" /> 
</p>
<!--
<p class="submit">
<input type="submit" name="submit" value="Reset All Options" />
</p>
-->
</form>
';

}





?>
