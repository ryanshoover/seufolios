<html>
<head>

<?php 
$doc_id = $_GET['doc_id'];
$access_key = $_GET['key'];
$window_height = $_GET['height'];
$window_width = $_GET['width'];
?>

<script type='text/javascript' src='http://www.scribd.com/javascripts/view.js'></script>
<script type='text/javascript' src='./insert_scribd.js'></script>

</head>
<body onload='insert_scribd_doc(<?php echo $doc_id;?>, "<?php echo $access_key; ?>", <?php echo $window_height; ?>, <?php echo $window_width; ?>);'>





<div id='embedded_flash'><a href="http://www.scribd.com">Scribd</a></div>



</body>
</html>