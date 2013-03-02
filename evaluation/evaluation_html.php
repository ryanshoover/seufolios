<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
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

//Load all taxonomies
$args=array(
  'public'   => true,
  '_builtin' => false
  
); 
$output = 'names'; // or objects
$operator = 'and'; // 'and' or 'or'
$taxonomies=get_taxonomies($args,$output,$operator);
$terms_array=get_terms($taxonomies);
$terms = '';
foreach($terms_array as $term) {
	$terms .= $term->name .":" .$term->count ."~";
}
$terms = substr($terms, 0, -1);

//create javascript array for saved values
$script = "\n<script type='text/javascript'>\n
var values = new Array(";
$values = $_GET;	
	foreach($values as $key =>$value) {
		 $script .= (is_numeric($value) ? $value .',' : "'" .$value ."',");
		}
$script = substr($script, 0, -1);
$script .= ");\n</script>";
echo $script;
?>

<script src="http://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" src="js/range.js"></script>
<script type="text/javascript" src="js/timer.js"></script>
<script type="text/javascript" src="js/slider.js"></script>
<script type="text/javascript" src="js/scripts.js"></script>
<link type="text/css" rel="StyleSheet" href="css/bluecurve/bluecurve.css" />
<link type="text/css" rel="StyleSheet" href="css/styles.css" />

<div id="full">
    <form name="evaluation" id="evaluation" action="finalSave.php" method="post">
    <input type="hidden" id='profid' name='profid' value='<?php echo $saved_values['profid']; ?>'>
    <input type="hidden" id='studentid' name='studentid' value='<?php echo $saved_values['studentid']; ?>'>
    <input type="hidden" id='siteurl' name='siteurl' value='<?php echo $saved_values['siteurl']; ?>'>
    <input type="hidden" id='terms' name='terms' value='<?php echo $terms;  ?>'>
    
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
        <div id='savestatus'>&nbsp;</div>
    </div>
    </form>
</div><!--full-->

<script type="text/javascript">

function setupSliders() {
	//test to see if range input can exist
	var testrange=document.createElement("input")
	testrange.setAttribute("type", "range") 
	if (testrange.type=="range") var html5 = true;
	else var html5 = false;

	var sliders = new Array();
	var divs = jQuery('.slider');
	inputs = jQuery('.slider-input');
	
	for(i=0; i<divs.length; i++) {
		if (html5) {
			inputs[i].setAttribute("type", "range");
			inputs[i].setAttribute('min', '1');
			inputs[i].setAttribute('max', '6');
			inputs[i].setAttribute('step', '1');
			inputs[i].className += ' html5slider';
			divs[i].className += ' html5div';
		}
		else {
			sliders.push( new Slider(divs[i], inputs[i]) );
			if( inputs[i].value ) sliders[i].setValue(inputs[i].value); //set slider to saved input value
		}
	}
}

function setupEventListeners() {
	jQuery(':input').change(function() { updateDisplay(this); });
	jQuery(':input').change(function() { startTimer(this);    });
}

function updateDisplay(input) {
	var inputID = input.id.toString();
	var displayID = inputID.substr(0, inputID.length-5) + 'displayvalue';
	jQuery('#'+displayID).html(input.value);
}

function startTimer(input) {
	if(typeof ajaxTimer != 'undefined') {
		if(ajaxTimer < 500) clearTimeout(ajaxTimer);
	}
	ajaxTimer = setTimeout(function() {sendAjax(input)},500);	
}

function sendAjax(input) {
	document.getElementById('savestatus').innerHTML = "saving...";
	dataString = jQuery('#evaluation').serialize();
	jQuery.ajax({  
	  type: "GET",  
	  url: "tempSave.php",  
	  data: dataString 
	}).done(function( msg ) { document.getElementById('savestatus').innerHTML = msg; });  
	clearTimeout(ajaxTimer);
	return false;  

}

var ajaxTimer;
var inputs = new Array();
var dataString = '';
setupSliders();
setupEventListeners();
</script>


</body>
</html>