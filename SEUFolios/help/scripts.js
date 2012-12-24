// JavaScript Document
function createDiv() { 
	var _body = document.getElementsByTagName('body') [0];
	var _div = document.createElement('div');
	var _text = document.createTextNode(" ")
	_div.appendChild(_text);
	_div.id = 'help_box';
	_div.className = 'hidden';
	_div.setAttribute('onclick','closeHelp();');
	_body.appendChild(_div);
}

function showHelp(image, key) {
	 var viewportwidth; //complex for browser compatibility
	 // the more standards compliant browsers (mozilla/netscape/opera/IE7) use window.innerWidth and window.innerHeight
	 if (typeof window.innerWidth != 'undefined') viewportwidth = window.innerWidth;
	// IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)
	 else if (typeof document.documentElement != 'undefined' && typeof document.documentElement.clientWidth != 'undefined' && document.documentElement.clientWidth != 0) viewportwidth = document.documentElement.clientWidth;
	 // older versions of IE
	 else  viewportwidth = document.getElementsByTagName('body')[0].clientWidth;
	 
	 var imageOffset = getOffset(image).left;
	
	helpDiv = document.getElementById('help_box');
	if(helpDiv.className == 'show') {
		closeHelp();
		return false;
	}
	
	jQuery.post( ajaxurl, 
			{
			  'action':'replace_help', 
			  'data': key,
			  'async': false,
			},
			function (response) {
				//alert(response);
				jQuery('#help_box').html(response);
			});
	
	if(imageOffset + 250 < viewportwidth) helpDiv.style.left = (imageOffset + 20) + 'px'; 
	else helpDiv.style.left = (imageOffset - 280 ) + 'px';
	helpDiv.style.top  = getOffset(image).top  + 'px';  //image.offsetTop +'px';
	helpDiv.className  = 'show';
	
	
	
	return false;
  
}

function closeHelp() {
	helpDiv = document.getElementById('help_box');
	helpDiv.className = 'hidden';
	helpDiv.innerHTML = ' ';
}

function editHelp(tr_id) {
	var tr = document.getElementById('row_' + tr_id);
	var tdArr = tr.getElementsByTagName('td');
	var old_key = tdArr[0].innerHTML;
	var old_content = tdArr[1].innerHTML;
	//var td_title = tr.getElementsByClassName('title');
	//var old_title = td_title[0].innerHTML;
	//var td_desc = tr.getElementsByClassName('desc');
	//var old_desc = td_desc[0].innerHTML;
	var innerHTML = "<td><input type='hidden' name='help_id' id='help_id' value='" + tr_id + "'>" +
					"<input type='text' name='help_key' id='help_key' size='10' value='" +old_key+ "'></td>" +
					"<td><textarea name='help_content' id='help_content' cols=30>" +old_content+ "</textarea></td>" +
					"<td><button type='submit' onclick='editHelp_submit();'>Done</button> &nbsp;&nbsp; " +
					"<button id='delete_"+tr_id+"' class='delete_button' type='button' onclick='deleteHelp("+tr_id+")'>Delete</button></td>";
	
	tr.innerHTML = innerHTML;
	return false;
}

function editHelp_submit() {
	var b = 'id=' + document.getElementById('help_id').value + '&help_key=' +document.getElementById('help_key').value + '&content=' +document.getElementById('help_content').value;
	jQuery.post( ajaxurl, 
			{
			  'action':'help_edit_field', 
			  'data': b
			},
			function (response) {
				jQuery('#help_fields').html(response);
			});
	return false;	
}

function deleteHelp(id) {
	jQuery.post( ajaxurl, 
		{
		  'action':'help_delete_field', 
		  'data': id
		},
		function (response) {
			jQuery('#help_fields').html(response);
		});
	
	return false;	
}

function getOffset( el ) {
    var _x = 0;
    var _y = 0;
    while( el && !isNaN( el.offsetLeft ) && !isNaN( el.offsetTop ) ) {
        _x += el.offsetLeft;
        _y += el.offsetTop;
        el = el.offsetParent;
    }
    return { top: _y, left: _x };
}


window.onload = createDiv;