// JavaScript Document

function showSection(position) {
	var sections = $(".major_section");
	var navigation = $("#navigation"); 
	var links = $('#navigation li'); 
	
	for(var i=0;i<sections.length;i++) {
		sections[i].style.marginLeft = '-999em';	
		links[i].className = '';
	}
	sections[position].style.marginLeft = '0px'
	links[position].className = 'selected';
}

var clickable = false;

function insertIframe(url, answers) {
	var existing_iframe = document.getElementById('eval_iframe');
	var admin_bar = document.getElementById('wp-admin-bar-seufolios-evaluation');
	
	var reg_highlight = new RegExp('(\\s|^)'+'highlight'+'(\\s|$)');
	
	//test if iframe exists
	if (existing_iframe != null) {
		if(existing_iframe.style.display == 'none') {existing_iframe.style.display = 'block'; admin_bar.className += ' highlight'; clickable = true; }
		else {existing_iframe.style.display = 'none'; admin_bar.className = admin_bar.className.replace(reg_highlight,' '); clickable = false; }
	} else {
		//create iframe
		var iframe = document.createElement("iframe"); 
		iframe.id = 'eval_iframe';
		iframe.src = url + "evaluation/evaluation_html.php?siteurl=" + siteurl + "&" + answers;
		iframe.setAttribute('scrolling', 'no');
		var bodytag = document.getElementsByTagName('body')[0];
		bodytag.insertBefore(iframe,bodytag.firstChild);
		
		admin_bar.className += ' highlight';
		
		clickable = true;
		jQuery("#container").click(function() { hideIframe(); }); 
	}
}

function hideIframe(myself) {
	var existing_iframe = document.getElementById('eval_iframe');
	var admin_bar = document.getElementById('wp-admin-bar-seufolios-evaluation');
	var reg_highlight = new RegExp('(\\s|^)'+'highlight'+'(\\s|$)');
	
	if(clickable) {
		existing_iframe.style.display = 'none'; 
		admin_bar.className = admin_bar.className.replace(reg_highlight,' ');
		clickable = false;
	} 
	
}