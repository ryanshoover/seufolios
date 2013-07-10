// JavaScript Document

var clickable = false;

function insertIframe(url, answers) {
	var existing_iframe = document.getElementById('iframe_holder');
	var admin_bar = document.getElementById('wp-admin-bar-seufolios-evaluation');
	
	var reg_highlight = new RegExp('(\\s|^)'+'highlight'+'(\\s|$)');
	
	//test if iframe exists
	if (existing_iframe != null) {
		if(existing_iframe.style.display == 'none') {existing_iframe.style.display = 'block'; admin_bar.className += ' highlight'; clickable = true; }
		else {existing_iframe.style.display = 'none'; admin_bar.className = admin_bar.className.replace(reg_highlight,' '); clickable = false; }
	} else {
		//create iframe
		var iframeHolder = document.createElement("div");
		iframeHolder.id = "iframe_holder";
		var iframe = document.createElement("iframe"); 
		iframe.id = 'eval_iframe';
		iframe.src = url + "evaluation/evaluation_html.php?" + answers;
		iframe.setAttribute('scrolling', 'no');
		iframeHolder.appendChild(iframe);
		var bodytag = document.getElementsByTagName('body')[0];
		bodytag.insertBefore(iframeHolder,bodytag.firstChild);
		
		admin_bar.className += ' highlight';
		
		jQuery( "#iframe_holder" ).resizable();
		
		//clickable = true;
		//jQuery("#container").click(function() { hideIframe(); }); 
	}
}

function hideIframe(myself) {
	var existing_iframe = document.getElementById('iframe_holder');
	var admin_bar = document.getElementById('wp-admin-bar-seufolios-evaluation');
	var reg_highlight = new RegExp('(\\s|^)'+'highlight'+'(\\s|$)');
	
	if(clickable) {
		existing_iframe.style.display = 'none'; 
		admin_bar.className = admin_bar.className.replace(reg_highlight,' ');
		clickable = false;
	} 
	
}