// JavaScript Document

function showSection(position) {
	var sections = jQuery(".major_section");
	var navigation = jQuery("#navigation"); 
	var links = jQuery('#navigation li'); 
	
	for(var i=0;i<sections.length;i++) {
		sections[i].style.marginLeft = '-999em';	
		links[i].className = '';
	}
	sections[position].style.marginLeft = '0px'
	links[position].className = 'selected';
}

function setupSliders() {
	//test to see if range input can exist
	var testrange=document.createElement("input")
	testrange.setAttribute("type", "range") 
	if (testrange.type=="range") html5 = true;
	else html5 = false;

	var sliders = new Array();
	var divs = jQuery('.slider');
	inputs = jQuery('.slider-input');
	
	for(i=0; i<divs.length; i++) {
		if (html5) {
			//inputs[i].setAttribute("type", "range");
			//if n/a option, set the min to 0
			jQuery(inputs[i]).hasClass('na-option') ? inputs[i].setAttribute('min', '0') :inputs[i].setAttribute('min', '1');
			//inputs[i].setAttribute('max', '6');
			inputs[i].setAttribute('step', '1');
			inputs[i].className += ' html5slider';
			divs[i].className += ' html5div';
			
			//change input value with slider movement
			jQuery(inputs[i]).change(function() {
				el = jQuery(this);
				width = el.width();
				newPoint = (el.val() - el.attr("min")) / (el.attr("max") - el.attr("min"));
				offset = 1.5;
				if (newPoint < 0) { newPlace = 0; }
				 else if (newPoint > 1) { newPlace = width; }
				 else { newPlace = width * newPoint + offset; offset -= newPoint; }
				jQuery('#' + jQuery(this).attr('id').replace('_input', '_displayvalue') ).css({'left': newPlace, 'marginLeft': offset+"%" });
			})
			.trigger('change');
		}
		else {
			in_val = inputs[i].value;
			sliders.push( new Slider(divs[i], inputs[i]) );
			sliders[i].setMaximum(inputs[i].getAttribute('max'));
			if( jQuery(inputs[i]).hasClass('na-option') ) sliders[i].setMinimum(0);
			sliders[i].setValue(in_val); //set slider to saved input value
			
			//change with slider movement
			jQuery(inputs[i]).change(function() {
				jQuery('#' + jQuery(this).attr('id').replace('_input', '_displayvalue') ).css('margin-left', parseFloat(jQuery(this).parent().children('.handle').css('left')) + 5 +'px' );
			})
			.trigger('change');
		}
	}
	
	//convert 0 to n/a
	jQuery("div.na-option").each(function(i) { if(jQuery(this).html()=='0') jQuery(this).html('n/a'); });
}

function setupEventListeners() {
	jQuery(':input').change(function() { updateDisplay(this); startTimer(this); });
	$('textarea').bind('keyup',function(e) { startTimer(this); })
}

function updateDisplay(input) {
	var inputID = input.id.toString();
	var displayID = inputID.substr(0, inputID.length-5) + 'displayvalue';
	
	if(jQuery('#'+displayID).hasClass('na-option') && input.value==0) {
		jQuery('#'+displayID).html('n/a');
	} else {
		jQuery('#'+displayID).html(input.value);
	}
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
	}).done(function( msg ) { 
		clearTimeout(submitTimer);
		jQuery('#savestatus').html(msg); 
		submitTimer = setTimeout(function() { jQuery('#savestatus').html(''); },3000);
	});   
	clearTimeout(ajaxTimer);
	return false;  

}

function setupTrashcan() {
	jQuery("#delete-entry").click(function() {
		document.getElementById('savestatus').innerHTML = "deleting...";
		dataString = jQuery('#evaluation').serialize() + "&deleteIcon=1";
		jQuery.ajax({  
		  type: "GET",  
		  url: "tempSave.php",  
		  data: dataString 
		}).done(function( msg ) { 
			clearTimeout(submitTimer);
			jQuery('#savestatus').html(msg); 
			submitTimer = setTimeout(function() { jQuery('#savestatus').html(''); },3000);
			parent.location.reload(); 
		});
		return false;
	});
}
function setupStar() {
	jQuery("#star-entry").hover(function() {$(this).css('background-position-y', '-20px');}, function() {$(this).css('background-position-y', '0'); });
	jQuery("#star-entry").click(function() { 
		document.getElementById('savestatus').innerHTML = "starring...";
		dataString = "starIcon=" + starIcon + "&blogurl=<?php echo urlencode($blog); ?>&deptid=<?php echo $dept_id; ?>&" +jQuery('#evaluation').serialize();
		jQuery.ajax({  
		  type: "GET",  
		  url: "tempSave.php",  
		  data: dataString 
		}).done(function( msg ) { 
			clearTimeout(submitTimer);
			jQuery('#savestatus').html(msg); 
			submitTimer = setTimeout(function() { jQuery('#savestatus').html(''); },3000);
			jQuery("#star-entry").toggleClass('starred'); 
			if(starIcon == 0) {starIcon = 1; }
			else {starIcon = 0; }
		  });
		return false;
	 });
}

var ajaxTimer;
var submitTimer;
var inputs = new Array();
var step = new Array();
var dataString = '';

jQuery(function() {
	jQuery('div#sections').css('height', jQuery('div#full').height() -jQuery('ul#navigation').height() -jQuery('div#buttons').height() + 'px' );
	jQuery(window).resize(function() { 
		jQuery('div#sections').css('height', jQuery('div#full').height() -jQuery('ul#navigation').height() -jQuery('div#buttons').height() + 'px' ); 
		jQuery(".slider-input").trigger('change');
	});
	setupSliders();
	setupEventListeners();
	setupTrashcan();
	setupStar();
});