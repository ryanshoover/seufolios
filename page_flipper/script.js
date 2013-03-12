// JavaScript Document
var selectedDoc = '';
var selectedDocName = '';

function clickNav(id) {
	var nav_link = '#'+id;
	var div_link = '#'+id+'-div';
	jQuery('.document-nav-menu div').removeClass('active');
	jQuery(nav_link).addClass('active');
	jQuery('.major-sec').hide();
	jQuery(div_link).show();
}

function startUpload(){
    document.getElementById('f1_upload_process').style.visibility = 'visible';
	document.getElementById('upload-form').style.visibility = 'hidden';
    return true;
}

jQuery(document).ready(function(e) {
	jQuery('#upload').click(function(e) {
        clickNav('upload');
    });
	jQuery('#doc-lib').click(function(e) {
        clickNav('doc-lib');
    });
	
	jQuery('.doc-div').click(function(e) {
		jQuery('.doc-div').removeClass('doc-selected');
		jQuery(this).addClass('doc-selected');
		
		selectedDoc = jQuery(this).attr('id');
		selectedDocName = jQuery(this).children('p').text();
		jQuery("#existing_title").val(selectedDocName);
		jQuery("#existing_post_id").val(selectedDoc);
	});
	
	
});