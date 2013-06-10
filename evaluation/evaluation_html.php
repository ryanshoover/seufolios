<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
html, body { height: 100%; width: 100%; margin: 0; padding: 0;}
</style>
</head>

<body onLoad="showSection(0);">
<meta name="viewport" content="initial-scale=1.0">
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
$wp_path = seu_get_wp_config_path();

//load WP features
include_once($wp_path .'/wp-config.php');
include_once($wp_path .'/wp-load.php');
include_once($wp_path .'/wp-includes/wp-db.php');

//load eval sections
global $wpdb;
$dept_id = get_user_major(); //get_option('student_major');//changed to site option feature //get_user_meta( $_GET['studentid'], 'major', false ); 

//find the major abbr of the blog
$depts = get_depts();
foreach($depts as $dept) {
	if ($dept_id[0] == $dept->id) $major_abbr = $dept->abbr;
}

//sql query for eval sections from dept
$sections_table_name = "wp_seufolios_eval_sections"; 
$sql = "SELECT * FROM $sections_table_name WHERE dept_id = $dept_id[0] ORDER BY order_loc ASC";
$sections = $wpdb->get_results($sql);

//sql query for eval questions from sections
$questions_table_name = "wp_seufolios_eval_questions"; 
foreach($sections as $section) {
	$sql = "SELECT * FROM $questions_table_name WHERE section_id = $section->id ORDER BY order_loc ASC";
	$questions[$section->id] = $wpdb->get_results($sql);
}

//move $_GET values to array
foreach($_GET as $key=>$value) {
	$slug = str_replace('_input', '', $key);
	$saved_values[$slug] = $value;
}

//Set saved values for the eval questions
foreach($questions as $question_array) {
	foreach($question_array as $question) {
		$found=false;
		foreach($saved_values as $key=>$value) {
			if($question->slug == $key) {$question->value = $value; $found=true;}
		}
		if(!$found) {$question->value = false;} //default value = 1 if no saved values
	}
}

//Load RATS-only count of custom taxonomies marked
if ($major_abbr == 'RATS') {
	$args = array(
		'type'                     => 'post',
		'child_of'                 => 0,
		'parent'                   => '',
		'orderby'                  => 'name',
		'order'                    => 'ASC',
		'hide_empty'               => 0,
		'hierarchical'             => 1,
		'exclude'                  => '',
		'include'                  => '',
		'number'                   => '',
		'taxonomy'                 => 'rats_outcomes',
		'pad_counts'               => false );
	$categories = get_categories( $args );
	
	foreach($categories as $category) {
		$query = 'post_type=classroom&rats_outcomes=' .$category->name;
		$posts = get_posts($query);
		$outcomes_count[$category->name] = count($posts);
	}
}

//Load all custom taxonomies
$args=array( 'public' => true, '_builtin' => false ); 
$output = 'names'; // or objects
$operator = 'and'; // 'and' or 'or'
$taxonomies=get_taxonomies($args,$output,$operator);
$terms_array=get_terms($taxonomies);
foreach($terms_array as $term) {
	$terms[$term->taxonomy][$term->name] = $term->count;
}

//create initial value in evaluations table
$blog = get_bloginfo('wpurl');
$eval_table_name = 'wp_seufolios_evaluations';
$results = $wpdb->get_var( $wpdb->prepare("SELECT id FROM $eval_table_name WHERE profid=".$saved_values['profid'] ." AND studentid=".$saved_values['studentid']));
if(!$results) $results = $wpdb->insert( $eval_table_name, array('profid'=>$saved_values['profid'], 'studentid'=>$saved_values['studentid'], 'siteurl'=>$blog, 'taxonomies'=> serialize($terms) ) ); 

//set up favorites star
$star_table_name = "wp_seufolios_starred";
$profidsS = $wpdb->get_var( $wpdb->prepare("SELECT profids FROM $star_table_name WHERE blogurl='".$blog."' AND deptid=" .$dept_id ) );
if($profidsS) {
	$profids = unserialize($profidsS);
	if( ($key = array_search($saved_values['profid'], $profids)) !== false) $favorite = true;
	else $favorite = false;
} else $favorite = false;

//create javascript array for saved values
$script = "\n<script type='text/javascript'>\n
var values = new Array(";
$values = $_GET;	
	foreach($values as $key =>$value) {
		 $script .= (is_numeric($value) ? $value .',' : "'" .$value ."',");
		}
$script = substr($script, 0, -1);
$script .= ");
var starIcon = ". ($favorite ? "0" : "1" ) .";
</script>";
echo $script;
?>

<script src="http://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" src="js/range.js"></script>
<script type="text/javascript" src="js/timer.js"></script>
<script type="text/javascript" src="js/slider.js"></script>
<script type="text/javascript" src="js/scripts.js"></script>
<link type="text/css" rel="StyleSheet" href="css/bluecurve/bluecurve.css" />
<link type="text/css" rel="StyleSheet" href="css/styles.css?ver=3.5.1" />


<form name="evaluation" id="evaluation" action="finalSave.php" method="post">
  <input type="hidden" id='profid' name='profid' value='<?php echo $saved_values['profid']; ?>'>
  <input type="hidden" id='studentid' name='studentid' value='<?php echo $saved_values['studentid']; ?>'>
  <input type="hidden" id='wp_path' name='wp_path' value='<?php echo urlencode($wp_path);  ?>'>

  <div id="full">
    <ul id="navigation">
    	<?php
		$sec_num = 0;
		foreach($sections as $section) {
			echo "<li onclick='showSection($sec_num);'>$section->title</li>\n";
			$sec_num++;
		}
		?>
    </ul>
    
    <div id="sections">
    	<?php
		$q_types = get_question_types();
		foreach($sections as $section) {
			//start section div
			echo "<div id='$section->title' class='major_section'>\n
				  <h2>$section->title</h2>\n";
				  
			//load each question
			foreach($questions[$section->id] as $question) {
				echo "<!-- $question->slug -->\n
					<div id='" .$question->slug ."_container' class='container'>\n
					<div class='label'>" .stripslashes($question->question);
				if($major_abbr == 'RATS') echo "<br>&nbsp;&nbsp;(classes meeting this objective: <strong>" .$outcomes_count[$question->slug] ."</strong>)"; //custom field for RATS that shows the count of classroom posts matching this taxonomy
				echo "</div>\n";
				foreach($q_types as $q) {
					if($question->type == $q->id) eval(urldecode($q->code));  //eval is restricted to code entered by super admins. only way to provide a gui for question format
				}
				echo "</div><!-- $question->slug -->";
			}
			
			//close section div	  
			echo "</div><!-- $section->title -->";
		}
		?>
        
    </div> <!--sections-->

    <div id="buttons">
        <button value='submit'>Submit final evaluation</button>
        <img src="trash.png" id="delete-entry" class="icon" title="Delete evaluation">
        <div id="star-entry" class="icon<?php if($favorite) echo ' starred'; ?>" title="Mark as favorite"></div>
        <div id='savestatus'>&nbsp;</div>
    </div>
   
  </div><!--full-->
</form>


</body>
</html>