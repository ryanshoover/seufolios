<?php

function seu_get_wp_config_path()
{
    $base = dirname(__FILE__);
   
	if (@file_exists($base."/wp-config.php"))
    {
        return $base;
    } 
	
	while($base != '/') {
		$base = dirname($base);
		if (@file_exists($base."/wp-config.php"))
		  {
			  return $base;
		  }
	}
	
	return false;
}
$path = seu_get_wp_config_path();

//load WP features
include_once($path .'/wp-config.php');
include_once($path .'/wp-load.php');
include_once($path .'/wp-includes/wp-db.php');

$user = wp_get_current_user();
global $blog_id;

/*
//Get all relevant taxonomies
$taxs = get_taxonomies();
$taxs = array_diff($taxs, array('link_category', 'nav_menu'));
print_r($taxs);

//Loop through all posts/pages/etc
$args = array( 'author' => $_POST['studentid'], 'post_type' => 'any' );
$loop = new WP_Query( $args );
while ( $loop->have_posts() ) : $loop->the_post();
	$post_taxs = wp_get_post_terms( get_the_ID(), $taxs );
	$this_tax = array();
	foreach($post_taxs as $tax) {
		$this_tax[] = array('post_id'=>get_the_ID(), 'taxonomy'=>$tax->taxonomy, 'name'=>$tax->name); 	
	}
	$all_taxonomies[] = $this_tax;
endwhile;
print_r($all_taxonomies);
*/
echo 'Saved!';

?>