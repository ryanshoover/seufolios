<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link type="text/css" rel="StyleSheet" href="css/styles.css" />
</head>
<body>

<?php
$wp_path = urldecode($_POST['wp_path']);

//load WP features
include_once($wp_path .'/wp-config.php');
include_once($wp_path .'/wp-load.php');
include_once($wp_path .'/wp-includes/wp-db.php');
$student = get_userdata($_POST['studentid']);

global $wpdb;
$eval_table_name = "wp_seufolios_evaluations"; 
$results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $eval_table_name WHERE profid = " .$_POST['profid'] ." AND studentid = " .$_POST['studentid'] ) );

echo "<div id='sections'>
<h2>All done!</h2>
<p>Thanks for finishing the evaluation.</p>
<p>You submitted the following answers for <strong>$student->user_nicename</strong> on ". $results[0]->submittime."</p>
<ol>
";
foreach (explode('&', $results[0]->answers) as $chunk) {
    $param = explode("=", $chunk);
    if ($param) printf("<li><em>%s</em>:&nbsp;&nbsp;&nbsp;%s</li>\n", urldecode($param[0]), urldecode($param[1]));
}
echo "</ol></div>";
?>
</body>
</html>