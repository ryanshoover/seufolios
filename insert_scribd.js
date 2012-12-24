//scripts to insert scribd document

function insert_scribd_doc(this_doc_id, this_access_key, window_height, window_width) 
{ 
	
	if (window_height == null) {window_height=600;}
	if (window_width == null)  {window_width=700;}
	
	var scribd_doc = scribd.Document.getDoc( this_doc_id, this_access_key );
	
	
	var oniPaperReady = function(e){
	  //scribd_doc.api.setPage(3);
	}
	
	scribd_doc.addParam( 'jsapi_version', 1 );
	scribd_doc.addParam('height', window_height);
	scribd_doc.addParam('width', window_width);
	scribd_doc.addEventListener( 'iPaperReady', oniPaperReady );
	scribd_doc.write( 'embedded_flash' );
	
}

