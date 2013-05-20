<?php

//Scripts to set up the custom roles for SEUFolios

add_action('admin_init', 'insert_roles');
add_action('admin_init', 'include_professors');
delete_roles(); 

//Add various roles for SEUFolios
function insert_roles() {
  /*
  remove_role('engw_prof'); //legacy line of code for old version of SEUFolios
  remove_role('professors');
  remove_role('peers');
  */
  add_role('professor', 'Professor' ,array('read' => true));
  add_role('peer', 'Peer', array('read' => true));
}


//Remove various unneeded roles
function delete_roles() {
  $remove_roles = array ('author', 'contributor', 'subscriber', 'editor');
  foreach ($remove_roles as $role) 
  {
	  remove_role($role);	
  }
}

function include_professors() {
	
	$admin_id = get_user_id_from_string( get_blog_option($current_blog->blog_id, 'admin_email'));
	$blog_major = get_user_major(); //get_the_user_major($admin_id);
	//var_dump($blog_major);
	if ( ($GLOBALS[blog_id]) != 1 ) { //makes sure it's not the root blog
		$args =  array(
				  'blog_id' => 1,
				  'role' => 'professor'
				  );
		$professors = get_users( $args ); //get all profs from root blog
		//loop through profs, add as user if same major, delete if different major
		foreach($professors as $professor) {
			$prof_major = get_the_user_major($professor->ID);
			//var_dump($prof_major);
			if($prof_major == $blog_major) {
				$current_role = get_the_user_role($professor->user_login);
				if ($current_role != 'Administrator')
				  $result = add_existing_user_to_blog( array( 'user_id' => $professor->ID, 'role' => 'professor' ) );
			} else {
				$result = remove_user_from_blog($professor->ID, $current_blog->blog_id);
			}
		}
	} //end if
}


?>