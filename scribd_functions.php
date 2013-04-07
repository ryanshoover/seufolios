<?php

//Scribd settings
//Unique for SEUFolios account
add_option('scribd_api_key', 'cpkion25nntjfxqiom2u');
add_option('scribd_secret', 'sec-bdw42wqvtbfk6gjrq39cibgw47');
add_option('scribd_publisher_id', 'pub-9427655662973965355');

//Action hooks
add_action('wp_head', 'seufolios_headerinfo');
add_filter('media_upload_tabs', 'seu_scribd_media_menu');
add_action('media_upload_scribd', 'scribd_media_menu_handle');
add_action('init', 'clear_shortcode'); //used to clear the Scribd shortcode used by Jetpack pluging
//add_shortcode( 'scribd', 'scribd_shortcode' );
add_action( 'admin_menu' , 'add_scribd_management');
add_action('admin_head-media-upload-popup', 'file_upload_instruction');

//*****
//Add Scribd media type

//add scribd javascript to header
function seufolios_headerinfo() {
		$plugin_url = (plugins_url().'/seufolios/');
		?>
		<!--SEUFolios-->
		<script type="text/javascript" src="http://www.scribd.com/javascripts/view.js"></script>
		<!--end SEUFolios-->
		<?php
	}

//add scribd to media tab menu
function seu_scribd_media_menu($tabs) {
  $newtab = array('scribd' => __('Insert Document', 'scribd'));
  return array_merge($tabs, $newtab);
}

//adds help bubble to upload from computer window
function file_upload_instruction($d) {
	wp_enqueue_script('jquery');
	$help_key = 'explain_file_upload';
	$help = return_help($help_key);
	echo "<script type='text/javascript'>
				jQuery(document).ready(function(){ if(jQuery('.media-title').html()=='Add media files from your computer') jQuery('.media-title').append(\"" .$help ."\"); });
		  </script>";
	
}

//clear the Jetpack Scribd shortcode to prevent conflicts
function clear_shortcode() {
	remove_shortcode('scribd');	
	add_shortcode( 'scribd', 'scribd_shortcode' );
}

//create scribd shortcode
function scribd_shortcode( $atts ) {
	extract( shortcode_atts( array(
		'doc_id' => '123456789',
		'access_key' => 'key_12345',
		'linked_text' => 'My Document',
		'lightbox_width' => '700px',
		'lightbox_height' => '600px',
		'lightbox' => false,
		'caption' => 'This is my document'
	), $atts ) );

	$plugin_url = (plugins_url().'/seufolios/');
	
	if ($lightbox) {
	  return '<a href="' .$plugin_url .'insert_scribd.php?doc_id=' .$doc_id .'&amp;key=' .$access_key .'&amp;width=' .$lightbox_width .'&amp;height=' .$lightbox_height .'" class="lightwindow" title="' .$linked_text .'" caption="' .$caption .'" params="lightwindow_type=external,lightwindow_height=' .$lightbox_height .',lightwindow_width=' .$lightbox_width .'">' .$linked_text .'</a>';
	} else {
	  $embed = "<div id='embedded_flash_$doc_id'><a href='http://www.scribd.com'>Scribd</a></div>";
	  $embed .= '<script type="text/javascript">';
	  $embed .= "var scribd_doc_$doc_id = scribd.Document.getDoc( " .$doc_id .', "' .$access_key .'" );';
	  $embed .= 'scribd_doc.addParam( "jsapi_version", 1 );';
      $embed .= "scribd_doc.write( 'embedded_flash_$doc_id' );";
	  $embed .= '</script>';
	  return $embed;
	}
	
}

//scribd doc mgmt page
function add_scribd_management() {
	add_media_page( 'Manage Scribd documents', 'Scribd docs', 'upload_files', 'managescribd', manage_scribd_docs );
}


//lays out the add scribd media html
function media_seu_scribd() {
  //global $type;
  $type = 'scribd';
  
  wp_enqueue_script('media-upload');
  wp_enqueue_script('thickbox');
  wp_enqueue_style('thickbox');

  media_upload_header();
  echo '<h3 class="media-title" style="margin:1em 0 1em 1em; ">Add media file with Scribd ';
  insert_help('explain_scribd');
  echo '</h3>';
  
  //get current user id
  global $current_user;
  get_currentuserinfo();
  
  require_once 'classes/scribd.php';
  $scribd_api_key = get_option('scribd_api_key');
  $scribd_secret = get_option('scribd_secret'); 
  $my_user_id = $current_user->user_login;  //creates sub-accounts in the Scribd account based on WP username

  $scribd = new Scribd($scribd_api_key, $scribd_secret);
  $scribd->my_user_id = $my_user_id;
  $current_docs = $scribd->getList();
  
  $post_id = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : 0;
	
  //Upload new doc to Scribd
  ?>
  
  
  <form enctype="multipart/form-data" id="new_doc" action="" method="post"  class="media-upload-form type-form validate">
  <input type="hidden" name="insert_new" value="1">
  <div id="media-items">
  <h3>Insert a new document</h3>
  
  <table class="describe">
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="datafile">Choose a file</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
  </th>
  <td class="field">
  <input type="file" name="datafile" size="40" aria-required="true">
  </td></tr>
  <!--
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="doc_type">Document type</label>
  </th>
  <td class="field">
  <select name="doc_type"><option value="">---</option><option value="PDF">PDF</option><option value="DOC">DOC</option><option value="TXT">TXT</option><option value="PPT">PPT</option></select>
  </td></tr>
  -->
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="linked_text">Text for the link</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
  </th>
  <td class="field">
  <input type="text" name="linked_text" aria-required="true" placeholder="Used as the document's hyperlink"></input>
  </td></tr>
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="lightbox_width">Width of window</label>
  </th>
  <td class="field">
  <input type="text" name="lightbox_width" placeholder=" leave blank to use default"></input>
  </td></tr>
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="lightbox_height">Height of window</label>
  </th>
  <td class="field">
  <input type="text" name="lightbox_height" placeholder=" leave blank to use default"></input>
    </td></tr>
  <tr>
  <th valign="top" scope="row" class="label">
  </th>
  <td class="field">
  <input type="checkbox" name="lightbox"></input> Use the Lightbox pop-up window? <? insert_help('explain_lightbox'); ?>
  </td></tr>
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="caption">Lightbox caption</label>
  </th>
  <td class="field">
  <input type="text" name="caption" placeholder="Appears below the popup"></input>
  </td></tr>
  <tr>
  <th valign="top" scope="row" class="label">
  </th>
  <td class="field">
  <input type="submit" class="button" value="Insert new document"></input>
  </td></tr></table>
  
  </div>
  </form>
  
  <?php
  //handles submit function of form
  if (isset($_POST['insert_new']))
	{
	 insert_new_scribd_into_post();
	}
	
  //Choose existing Scribd doc
  ?> 
  <form id="existing_doc" action="" method="post" class="media-upload-form type-form validate">
  <div id="media-items">
  <h3>Insert an existing document</h3>
  <table class="describe">
  <tr>
  <th valign="top" scope="row" class="label">
  <span class="alignleft"><label for="my_doc_id">Choose Document</label></span><span class="alignright"><abbr title="required" class="required">*</abbr></span>
  </th>
  <td class="field">
  <select name="my_doc_id" aria-required="true">
  <option value="">---</option>
  <?php
  foreach ($current_docs as $a_doc) {
	 //echo('<input type="radio" name="my_doc_id" value="' .$a_doc[doc_id] .'?' .$a_doc[access_key] .'" aria-required="true">' .$a_doc[title].'</input><br />'); 
     echo('<option value="' .$a_doc[doc_id] .'?' .$a_doc[access_key] .'">' .$a_doc[title].'</option>'); 
  }
  ?>
  </select>
  </td>
  </tr>
  
  <tr>
  <th scope="row" class="label">
  <label for="linked_text">Text for the link</label>
  <span class="alignright"><abbr title="required" class="required">*</abbr></span>
  </th>
  <td class="field">
  <input type="text" name="linked_text" aria-required="true" placeholder="Used as the document's hyperlink"></input>
  </td></tr>
  <tr>
  <th scope="row" class="label">
  <label for="lightbox_width">Width of window</label>
  </th>
  <td class="field">
  <input type="text" name="lightbox_width" placeholder=" leave blank to use default"></input>
  </td></tr>
  <tr>
  <th scope="row" class="label">
  <label for="lightbox_height">Height of window</label>
  </th>
  <td class="field">
  <input type="text" name="lightbox_height" placeholder=" leave blank to use default"></input>
  </td></tr>
  <tr>
  <th scope="row" class="label">
  </th>
  <td class="field">
  <input type="checkbox" name="lightbox"></input> Use the Lightbox pop-up window? <? insert_help('explain_lightbox'); ?>
  </td></tr>
  <tr>
  <th scope="row" class="label">
  <label for="caption">Lightbox caption</label>
  </th>
  <td class="field">
  <input type="text" name="caption" placeholder="Appears below the popup"></input>
  </td></tr>
  <tr>
  <th scope="row" class="label">
  </th>
  <td class="field">
  <input type="submit" class="button" value="Insert existing document"></input>
  </td>
  </tr>
  </table>
  </div>
  </form>
  
  <?php
  //handles submit function of form
  if (isset($_POST['my_doc_id']))
	{
	 insert_existing_scribd_into_post();
	}
}

//executes the insert into post function
function insert_existing_scribd_into_post() {
	//get_currentuserinfo();
	
	$scribd_info = explode('?', $_POST['my_doc_id']);
	$doc_id = $scribd_info[0];
	$access_key = $scribd_info [1];
	$lightbox = $_POST['lightbox'];
	$linked_text = $_POST['linked_text'];
	$caption = $_POST['caption'];
	//$author = $current_user->display_name;
	$lightbox_width = $_POST['lightbox_width'];
	$lightbox_height = $_POST['lightbox_height'];
	  
	
	if ($lightbox_height == null) {$lightbox_height=600;}
	if ($lightbox_width == null) {$lightbox_width=700;}
	
	$html = '[scribd doc_id="' .$doc_id .'" access_key="' .$access_key .'" linked_text="' .$linked_text .'" lightbox_width="' .$lightbox_width .'" lightbox_height="' .$lightbox_height .'" lightbox="' .$lightbox .'" caption="' .$caption .'"]';
	  
	$html = apply_filters('media_send_to_editor', $html, $send_id, $attachment);
	return media_send_to_editor($html);
}

function insert_new_scribd_into_post() {
	//get_currentuserinfo();
	
	$file = $_POST['datafile'];
	$lightbox = $_POST['lightbox'];
	$linked_text = $_POST['linked_text'];
	$caption = $_POST['caption'];
	//$author = $current_user->display_name;
	$lightbox_width = $_POST['lightbox_width'];
	$lightbox_height = $_POST['lightbox_height'];
	
	//upload file to Wordpress
	$upload_dir =  wp_upload_dir();
	$target_path = $upload_dir['path'];
	$target_path = $target_path .'/' . basename( $_FILES['datafile']['name']);
	
	if(move_uploaded_file($_FILES['datafile']['tmp_name'], $target_path)) {
		 //echo ("File was successfully uploaded\n");
	} else{
		 echo ("There was an error uploading the file. Please try again.\n");
	}
	
	//get current user id
	global $current_user;
	get_currentuserinfo();
	
	require_once 'classes/scribd.php';
	$scribd_api_key = get_option('scribd_api_key');
    $scribd_secret = get_option('scribd_secret');  
	$my_user_id = $current_user->user_login;  //creates sub-accounts in the Scribd account based on WP username
  
	$scribd = new Scribd($scribd_api_key, $scribd_secret);
	$scribd->my_user_id = $my_user_id;
	
	$file = $target_path;
	$doc_type = $_POST['doc_type'];
	$access = 'private';
	$rev_id = null;
	
	if ($data = $scribd->upload($file, $doc_type, $access, $rev_id)) {
		//echo ("Put to Scribd successful\n");
	} else {
		echo ("Put to Scribd failed\n");
	}
	 // returns Array ( [doc_id] => 1026598 [access_key] => key-23nvikunhtextwmdjm2i )
	$doc_id = $data[doc_id];
	$access_key = $data[access_key];
	
	if ($lightbox_height == null) {$lightbox_height=600;}
	if ($lightbox_width == null) {$lightbox_width=700;}
	
	
	$html = '[scribd doc_id="' .$doc_id .'" access_key="' .$access_key .'" linked_text="' .$linked_text .'" lightbox_width="' .$lightbox_width .'" lightbox_height="' .$lightbox_height .'" lightbox="' .$lightbox .'" caption="' .$caption .'"]';

	$html = apply_filters('media_send_to_editor', $html, $send_id, $attachment);
	return media_send_to_editor($html);
}

//creates the iframe for media window
function scribd_media_menu_handle() {
   return wp_iframe( 'media_seu_scribd');
}

function manage_scribd_docs() {
	
	echo '<div class="wrap"><h2>Manage Scribd Documents';
	insert_help('explain_scribd');
	echo '</h2>';
	//Form to Upload new doc to Scribd
  ?>
  <style>
  th.label { text-align:right; }
  </style>
  <div id="message_box">&nbsp;</div>
  
  <form enctype="multipart/form-data" id="new_doc" action="" method="post"  class="media-upload-form type-form validate">
  <input type="hidden" name="insert_new" value="1">
  <div id="media-items">
  <h3>Add a document to your library</h3>
  
  <table class="describe">
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="datafile">Choose a file</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
  </th>
  <td class="field">
  <input type="file" name="datafile" size="40" aria-required="true">
  </td></tr>
  <!--
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="doc_type">Document type</label>
  </th>
  <td class="field">
  <select name="doc_type" id="doc_type"><option value="">---</option><option value="pdf">PDF</option><option value="doc">DOC</option><option value="txt">TXT</option><option value="ppt">PPT</option><option value="">Other</option></select>
  </td></tr>-->
  <tr>
  <th valign="top" scope="row" class="label">
  </th>
  <td class="field">
  <input type="submit" class="button" value="Upload new document"></input>
  </td></tr></table>
  
  </div>
  </form>
  
  <?php
  //handles submit function of form
  if (isset($_POST['insert_new']))
	{
	 upload_new_scribd();
	}
	
  //&&&&Form to Delete doc	
  //get current user id
  global $current_user;
  get_currentuserinfo();
  
  require_once 'classes/scribd.php';
  $scribd_api_key = get_option('scribd_api_key');
  $scribd_secret = get_option('scribd_secret');  
  $my_user_id = $current_user->user_login;  //creates sub-accounts in the Scribd account based on WP username

  $scribd = new Scribd($scribd_api_key, $scribd_secret);
  $scribd->my_user_id = $my_user_id;
  $current_docs = $scribd->getList();
  ?>
  <br /><br />
  <form enctype="multipart/form-data" id="delete_doc" action="" method="post"  class="media-upload-form type-form validate">
  <div id="media-items">
  <h3>Delete a document from your library</h3>
  
  <table class="describe">
  <tr>
  <th valign="top" scope="row" class="label">
  <span class="alignleft"><label for="my_doc_id">Choose Document</label></span>
  </th>
  <td class="field">
  <select name="delete_doc_id" aria-required="true">
  <option value="">---</option>
  <?php
  foreach ($current_docs as $a_doc) {
	 //echo('<input type="radio" name="my_doc_id" value="' .$a_doc[doc_id] .'?' .$a_doc[access_key] .'" aria-required="true">' .$a_doc[title].'</input><br />'); 
     echo('<option value="' .$a_doc[doc_id] .'?' .$a_doc[access_key] .'">' .$a_doc[title].'</option>'); 
  }
  ?>
  </select>
  </td>
  </tr>
  <tr>
  <td>&nbsp;</td>
  <td>
  <input type="submit" class="button" value="Delete document from Scribd"></input>
  </td></tr></table>
  
  </div>
  </form>
  
  <?php
  //handles submit function of form
  if (isset($_POST['delete_doc_id']))
	{
	 delete_scribd();
	}
	
  
   //&&&&Form to *update* doc	
  //get current user id
  global $current_user;
  get_currentuserinfo();
  
  require_once 'classes/scribd.php';
  $scribd_api_key = get_option('scribd_api_key');
  $scribd_secret = get_option('scribd_secret');  
  $my_user_id = $current_user->user_login;  //creates sub-accounts in the Scribd account based on WP username

  $scribd = new Scribd($scribd_api_key, $scribd_secret);
  $scribd->my_user_id = $my_user_id;
  $current_docs = $scribd->getList();
  ?>
  <br /><br />
  <script type="text/javascript"> 
  var allDocs = new Array();
  var oneDoc = new Array();
  <?php
  foreach($current_docs as $my_doc) {
	$my_values = $scribd->getSettings($my_doc[doc_id]);
	$my_doc[title] = trim($my_doc[title]);
	$my_doc[description] = trim($my_doc[description]);
	
    ?>oneDoc=["<?php echo $my_doc[doc_id]?>"," <?php echo $my_doc[title] ?>","<?php echo $my_doc[description] ?>","<?php echo $my_values[access] ?>"];
    allDocs.push(oneDoc);<?php
  }
  ?>
  
  function updateScribdUpdate(sel){ 
  	//alert('in updateScribdUpdate');
	for (i=0;i<allDocs.length;i++)
	  {
	  	if(allDocs[i][0] == sel.options[sel.selectedIndex].value) {
			//alert(allDocs[i][1] + " " + allDocs[i][2]);
			document.getElementById('doc_title').value = allDocs[i][1];
			document.getElementById('doc_description').innerHTML = allDocs[i][2];
			if(allDocs[i][3]=="public"){document.getElementById('access_type').selectedIndex = 1;};
		}
	  }	
  } 
  </script> 
  <form enctype="multipart/form-data" id="update_doc" action="" method="post"  class="media-upload-form type-form validate">
  <div id="media-items">
  <h3>Update a document in your library</h3>
  
  <table class="describe">
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="my_doc_id">Choose Document</label>
  </th>
  <td class="field">
  <select name="update_doc_id" aria-required="true" onchange="return updateScribdUpdate(this)">
  <option value="">---</option>
  <?php
  foreach ($current_docs as $a_doc) {
	 $a_doc[title] = trim($a_doc[title]);
	 echo('<option value="' .$a_doc[doc_id] .'">' .$a_doc[title].'</option>'); 
  }
  ?>
  </select>
  </td>
  </tr>
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="doc_title">New document title</label>
  </th>
  <td class="field">
  <input type="text" name="doc_title" id="doc_title" aria-required="true"></input>
  </td></tr>
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="doc_description">New document description</label>
  </th>
  <td class="field">
  <textarea name="doc_description" id="doc_description" cols="40" rows="5" aria-required="true"></textarea>
  </td></tr>
  <tr>
  <th valign="top" scope="row" class="label">
  <label for="access_type">Public on Scribd.com?</label>
  </th>
  <td class="field">
  <select name="access_type" id="access_type" aria-required="true">
  <option value="private">private</option>
  <option value="public">public</option>
  </select>
  </td></tr>
  <tr>
  <td>&nbsp;</td>
  <td>
  <input type="submit" class="button" value="Update document"></input>
  </td></tr></table>
  
  </div>
  </form>
  
  <?php
  //handles submit function of form
  if (isset($_POST['update_doc_id']))
	{
	 update_scribd();
	}

  echo '</div>';		
}

function upload_new_scribd() {
	$file = $_POST['datafile'];
	$doc_title = $_POST['doc_title'];
	$doc_type = $_POST['doc_type'];
	
	//upload file to Wordpress
	$upload_dir =  wp_upload_dir();
	$target_path = $upload_dir['path'];
	$target_path = $target_path .'/' . basename( $_FILES['datafile']['name']);
	
	if(move_uploaded_file($_FILES['datafile']['tmp_name'], $target_path)) {
		 //nothing
    } else{
		 echo ('<script type="text/javascript">document.getElementById("message_box").innerHTML="<div id=\'inside_message_box\'>There was an error uploading the file. Please try again.</div>"</script>');
	}
	
	//get current user id
	global $current_user;
	get_currentuserinfo();
	
	require_once 'classes/scribd.php';
	$scribd_api_key = get_option('scribd_api_key');
    $scribd_secret = get_option('scribd_secret');  
	$my_user_id = $current_user->user_login;  //creates sub-accounts in the Scribd account based on WP username
  
	$scribd = new Scribd($scribd_api_key, $scribd_secret);
	$scribd->my_user_id = $my_user_id;
	
	$file = $target_path;
	$access = 'private';
	$rev_id = null;
	
	try{ 
	$data = $scribd->upload($file, $doc_type, $access, $rev_id); 
		echo ('<script type="text/javascript">document.getElementById("message_box").innerHTML="<div id=\'inside_message_box\' class=\'updated fade\'>The file was successfully uploaded. <a href=\'javascript:location.reload(true)\'>Refresh this page to see the changes.</a></div>"</script>');
	} catch (Exception $e) {
		echo ('<script type="text/javascript">document.getElementById("message_box").innerHTML="<div id=\'inside_message_box\' class=\'updated fade\'>Uploading to Scribd failed. Please try again.</div>"</script>');
	}
}

function delete_scribd() {
	$doc_id = $_POST['delete_doc_id'];
	
	//get current user id
	global $current_user;
	get_currentuserinfo();
	
	require_once 'classes/scribd.php';
	$scribd_api_key = get_option('scribd_api_key');
    $scribd_secret = get_option('scribd_secret');  
	$my_user_id = $current_user->user_login;  //creates sub-accounts in the Scribd account based on WP username
  
	$scribd = new Scribd($scribd_api_key, $scribd_secret);
	$scribd->my_user_id = $my_user_id;
	
	
	try {
		$data = $scribd->delete($doc_id);
	    echo ('<script type="text/javascript">document.getElementById("message_box").innerHTML="<div id=\'inside_message_box\' class=\'updated fade\'>The file was successfully deleted. <a href=\'javascript:location.reload(true)\'>Refresh this page to see the changes.</a></div>"</script>');
    } catch(Exception $e) {
		echo ('<script type="text/javascript">document.getElementById("message_box").innerHTML="<div id=\'inside_message_box\' class=\'updated fade\'>There was a problem deleting the file. Please try again.</div>"</script>');
	}
}

function update_scribd() {
	$doc_id = $_POST['update_doc_id'];
	$doc_title = $_POST['doc_title'];
	$doc_description = $_POST['doc_description'];
	$access_type = $_POST['access_type'];
	
	//get current user id
	global $current_user;
	get_currentuserinfo();
	
	require_once 'classes/scribd.php';
	$scribd_api_key = get_option('scribd_api_key');
    $scribd_secret = get_option('scribd_secret');  
	$my_user_id = $current_user->user_login;  //creates sub-accounts in the Scribd account based on WP username
  
	$scribd = new Scribd($scribd_api_key, $scribd_secret);
	$scribd->my_user_id = $my_user_id;
	
	
	try {
		$data = $scribd->changeSettings($doc_id, $doc_title, $doc_description, $access_type);
		echo ('<script type="text/javascript">document.getElementById("message_box").innerHTML="<div id=\'inside_message_box\' class=\'updated fade\'>The file was successfully updated. <a href=\'javascript:location.reload(true)\'>Refresh this page to see the changes.</a></div>"</script>');
	} catch(Exception $e) {
		echo ('<script type="text/javascript">document.getElementById("message_box").innerHTML="<div id=\'inside_message_box\' class=\'updated fade\'>There was a problem updating the file. Please try again.</div>"</script>');
	}
}

?>