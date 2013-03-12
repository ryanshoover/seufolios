<?php
/**
*   Modification of plugin: Page Flipper
*   Description: Ryan's page flipper
*   Author: Ryan Hoover
*   Author URI: http://ryanhoover.net
*   Version: 1.00
**/

//for initialization of features
class page_flipper_init{
	public function __construct() {
		require_once 'classes/phmagick/phmagick.php';
		
		//add doc upload mgmt into media management screen
		add_filter('media_upload_tabs', array($this, 'add_media_menu'));
		add_action('media_upload_document', array($this, 'media_menu_handle'));
		//set up doc type and shortcode
		add_action( 'init', array($this, 'add_post_type') );
		//add_action( 'admin_menu', array($this, 'add_doc_management') );
		add_shortcode( 'page-flipper', array( &$this, 'display' ) );
		
		wp_enqueue_script("jquery");
	}
	
	//add document to media tab menu
	public function add_media_menu($tabs) {
	  $newtab = array('document' => __('Insert Document', 'document'));
	  return array_merge($tabs, $newtab);
	}
	
	//creates the iframe for media window
	public function media_menu_handle() {
	   return wp_iframe( array($this, 'media_html'));
	}
	
	//returns the shortcode to the tinymce box
	public function insert_doc_into_post($post) {
		$lightbox = $_POST['lightbox'];
		$booklet = $_POST['booklet'];
		$width = $_POST['width'];
		$post_id = $_POST['post_id'];
		$linked_text = $_POST['linktext'];
		$coverpage = $_POST['coverpage'];
		
		$html = '[page-flipper post_id="' .$post_id .'" linked_text="' .$linked_text .'" width="' .$width .'" lightbox="' .$lightbox .'" booklet="' .$booklet .'" coverpage="' .$coverpage .'"]';
	  	$html = apply_filters('media_send_to_editor', $html, $send_id, $attachment);
		
		return media_send_to_editor($html);
	}
	
	//lays out the add document media html
	public function media_html() {
	  $type = 'document';
	  $myBase = plugins_url("", __FILE__) .'/page_flipper';
	 
	  wp_enqueue_media();
	  wp_enqueue_script('jquery');
	  wp_enqueue_script('media-upload');
	  wp_enqueue_script('thickbox');
	  wp_enqueue_style( 'thickbox');
	  wp_enqueue_script('page-flipper', $myBase .'/script.js');
	  wp_enqueue_style( 'page-flipper', $myBase .'/style.css');
	  
	  media_upload_header();
	  
	  //get current user id
	  global $current_user;
	  get_currentuserinfo();
	  
	  //insert new doc into post
		if( isset($_POST['insert_existing']) ) {
			$this->insert_doc_into_post($_POST);
		}
		if( isset($_POST['insert_new']) ) { 
		   	$pf = new page_flipper();
		   	$post_id = $pf->upload($_POST);
			if(is_numeric($post_id)) {
				$_POST['post_id'] = $post_id;
				$this->insert_doc_into_post($_POST);
			} else {
				echo "Oops. Something went wrong uploading the file.";	
			}
		}
	  
	  //Upload new doc 
	  ?>
	  <div class='document-nav-menu'>
        	<div id="upload" class="active">Upload Document</div>
            <div id="doc-lib" class="">Document Library</div>
      </div>
      <div style="clear:both;" id="upload_div"></div>
      <div id="upload-div" class="major-sec">
      	<form id="upload-form" action="" method="post" enctype="multipart/form-data">
        	<input type="hidden" name="insert_new" value="1">
            <div id="media-items">
            <h3 class="media-title">Upload a new document</h3>
            
            <table class="describe">
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="title">Document title</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
            </th>
            <td class="field">
            <input type="text" name="title" aria-required="true" placeholder="Used as the document's title"></input>
            </td></tr>
            
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="uploadfile">Choose a file</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
            </th>
            <td class="field">
            <input type="file" name="uploadfile" size="40" aria-required="true">
            </td></tr>
            
            <tr>
            <td colspan="2">&nbsp;</td>
            </tr>
            
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="lightbox">Lightbox</label>
            </th>
            <td class="field">
            <input type="checkbox" name="lightbox"> Insert a link to a popup window view of the document?
            </td></tr>
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="linktext">Linked text</label>
            </th>
            <td class="field">
            <input type="text" name="linktext" placeholder="What should the link to the lightbox say?">
            </td></tr>
            
            <tr>
            <td colspan="2">&nbsp;</td>
            </tr>
            
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="booklet">Booklet</label>
            </th>
            <td class="field">
            <input type="checkbox" name="booklet"> Display as a booklet with side-by-side pages?
            </td></tr>
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="coverpage">Cover page</label>
            </th>
            <td class="field">
            <input type="checkbox" name="coverpage"> Does the PDF have a cover page?
            </td></tr>
            
            
            <tr>
            <td colspan="2">&nbsp;</td>
            </tr>
            
            <tr>
            <th valign="top" scope="row" class="label">
            </th>
            <td class="field">
            <input type="submit" class="button" value="Insert new document"></input>
            </td></tr>
            
            <tr>
            <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
            <td colspan="2"><em>Note: It may take several minutes to process large PDF files</em></td>
            </tr>
            
            </table>
            
            </div>
        </form>
        <p id="f1_upload_process">Loading...<br/><img src="<?php echo $myBase; ?>/page_flipper/AjaxUpload/loader.gif" /></p>
		<p id="result"></p>
      </div>
	  
	  <?php  
	  //Choose existing  doc
	  ?> 
      <div id="doc-lib-div" class="major-sec">
      <h3 class="media-title">Pick a document to insert</h3>
      <?php
	  $args = array (
	  	'post_type' => 'seu_document',
		'numberposts' => -1
	  );
	  $docs = get_posts($args);
	  foreach($docs as $doc) {
		echo "<div class='doc-div' id='".$doc->ID."'><img src='".$doc->guid ."/page-000.png'><p>" .$doc->post_name."</p></div>";  
	  }
	  ?>
        <form id="doc-lib-form" action="" method="post" style="clear:both;">
        	<input type="hidden" name="insert_existing" value="1">
        	<input type="hidden" id="existing_post_id" name="post_id" value="1">
            <input type="hidden" id="existing_title" name="title" value="none">
          <table class="describe">
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="lightbox">Lightbox</label>
            </th>
            <td class="field">
            <input type="checkbox" name="lightbox"> Insert a link to a popup window view of the document?
            </td></tr>
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="linktext">Linked text</label>
            </th>
            <td class="field">
            <input type="text" name="linktext" placeholder="What should the link to the lightbox say?">
            </td></tr>
            
            <tr>
            <td colspan="2">&nbsp;</td>
            </tr>
            
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="booklet">Booklet</label>
            </th>
            <td class="field">
            <input type="checkbox" name="booklet"> Display as a booklet with side-by-side pages?
            </td></tr>
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="coverpage">Cover page</label>
            </th>
            <td class="field">
            <input type="checkbox" name="coverpage"> Does the PDF have a cover page?
            </td></tr>
            <tr>
            <th scope="row" class="label">
            </th>
            <td class="field">
              <input type="submit" class="button" value="Insert document"></input>
            </td>
            </tr>
          </table>
        </form>
	  </div>
	  <?php
	}
	
	//creates post type for docs
	public function add_post_type() {
		$options = array(
			'label' 		=> 'Documents',
			'description'	=> 'Inserts PDF as responsive flipper',
		);
		register_post_type('seu_document', $options);	
	}
	
	//creates mgmt page
	public function add_doc_management() {
		add_media_page( 'Manage Documents', 'Documents', 'upload_files', 'managedocs', array($this, 'upload_form') );
	}
	
	//add shortcode
	public function display($atts) {
		$pf = new page_flipper();
		return $pf->display($atts);
		
	}
	
	//sets up WP mgmt form
	public function upload_form() {
		
		wp_enqueue_media(); //setup all necessary upload media scripts
		?>
		<form enctype="multipart/form-data" id="new_doc" action="" method="post"  class="media-upload-form type-form validate">
		<div id="media-items">
		<h3>Insert a new document</h3>
		
		<table class="describe">
		<tr>
		<th valign="top" scope="row" class="label">
		<label for="title">Title</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
		</th>
		<td class="field">
		<input type="text" name="title" aria-required="true" placeholder="Used as the document's title"></input>
		</td></tr>
        
        <tr>
		<th valign="top" scope="row" class="label">
		<label for="uploadfile">Choose a file</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
		</th>
		<td class="field">
		<input type="file" name="uploadfile" size="40" aria-required="true">
		</td></tr>
		
		<tr>
		<th valign="top" scope="row" class="label">
		</th>
		<td class="field">
		<input type="submit" class="button" value="Insert new document"></input>
		</td></tr></table>
		
		</div>
		</form>
        
		<?php
		//handles submit function of form
		if (isset($_POST['title']))
		  {
		   	$pf = new page_flipper();
		   	$pf->upload($_POST);
		  }
		
	}
}

//handles specific functions
class page_flipper{
	public function __construct() {
		require_once 'classes/phmagick/phmagick.php';
		$this->load_booklet_js();
	}
	
	//handles the upload and processing of document
	public function upload($input) {
		$filename = urlencode( basename( $input['title']) );
		$wp_upload_dir = wp_upload_dir();
		$dir = $wp_upload_dir['path'] . '/' .$filename .'-' .time();
		$url = $wp_upload_dir['url'] . '/' .$filename .'-' .time();
		$success = mkdir($dir);
		$destination = $dir ."/page";
		
		$post = array(
			'post_name'		=> $filename,
			'post_type'		=> 'seu_document',
			'post_content'	=> $dir,
			'post_status'	=> 'publish',
			'guid'			=> $url
		);
		$post_id = wp_insert_post($post);
		
		$target = $dir .'/' .$_FILES['uploadfile']['name'];
		if(move_uploaded_file($_FILES['uploadfile']['tmp_name'], $target ) ) {
			  $p = new phmagick( $target, $destination);
			  //$p->setImageMagickPath('/opt/local/bin/'); //This is only needed if your server doesn't have a PATH to imagemagick
			  $count = $p->getPageCount();
			  $p->setImageQuality(90);
			  $p->setDensity(600);
			  
			  for($i=0; $i<$count; $i++) {
				  $p->setSource($target ."[$i]");
				  $p->setDestination($destination .'-' .str_pad($i, 3, '0', STR_PAD_LEFT) .'.png');
				  $p->convert();
			  }
			  echo $dir;
		} else{
			 $error = "There was an error uploading the file. Please try again.\n";
			 return $error;
		}
		
		return $post_id;
		
	}
	
	//handles the shortcode
	public function display($atts) {
		extract( shortcode_atts( array(
			'post_id' => 'test',
			'linked_text' => 'My Document',
			'width' => '100%',
			'lightbox' => false,
			'iframe' => false,
			'booklet' => true,
			'coverpage' => false,
		), $atts ) );
		
		$post = get_post($post_id);
		$post_path = $post->post_content;
		$post_pdf_a = glob($post_path . '/*.pdf');
		$post_pdf = str_replace($_SERVER['DOCUMENT_ROOT'], '', $post_pdf_a[0]);
		$pages = glob($post_path . '/page*.png');
		$image_size = getimagesize($pages[0]);
		$myBase = plugins_url("", __FILE__) .'/page_flipper';
		
		$this->load_fancybox();
		$send_data = json_encode($atts);
		
		if($lightbox) {
			$output = "<a href='" .$myBase ."/insert_doc.php?data=" .$send_data ."' class='fancybox' title='" .$linked_text ."'>" .$linked_text ."</a>";
			$output .="
			<script>
			jQuery(function ($) {
				$(function() {
					$('.fancybox').fancybox({
						//fsBtn: true,
						type     : 'iframe',
						autoSize : false,
						width    : '95%',
						height   : '92%',
						helpers  : {
							title: {
								type: 'inside'
							}
						}
					});
				});
			});
			</script>
			";
			return $output;
		}
		
		$output = "<div id='" .$post->post_name ."-dad' class='booklet-dad' style='width:$width;'>";
		$output .= "<div id='" .$post->post_name ."-container' class='booklet-container'>";
		$output .= "<div id='" .$post->post_name ."'>";
		
		foreach($pages as $page) {
			$page = str_replace($_SERVER['DOCUMENT_ROOT'], '', $page);
			$output .= "
			<div>
				<img src='" .$page ."' width='100%'>
			</div>";	
		}       
         
		$output .= "
			</div><!--/booklet-->
			</div><!--/booklet-container-->
			<div id='booklet-nav'>
				<img src='$myBase/images/zoom-in.png' id='booklet-in' class='booklet-zoom'>
				<img src='$myBase/images/zoom-out.png' id='booklet-out' class='booklet-zoom'>";
		if(!$iframe) $output .= "<a href='" .$myBase ."/insert_doc.php?data=" .$send_data ."' class='fancybox booklet-zoom white' title='" .$linked_text ."'><img src='$myBase/images/fullscreen.png' class='booklet-zoom'></a>";
		$output .= "<a href='$post_pdf' class='fancy-box white booklet-zoom'>download</a>";
		$output .= "<div id='switch-mode' class='fancy-box white booklet-zoom'>view single page</div>";
		$output .= "
			</div><!--/booklet-nav-->
			</div><!--/booklet-dad-->";
		
		$output .= "<script type='text/javascript'>
					var imgw = 0;
					var imgh = 0;
					";
		if($iframe) {
			$output .= "
				h = $(window).height() -35;
				w = h*1.544; //hard coded to 8.5x11 dimensions
				$(function() {
					$('#" .$post->post_name ."-dad').width(w);
					$('#" .$post->post_name ."-dad').height(h);
					$('#" .$post->post_name ."').booklet({
						width: w,
						height: h,
						overlays: false
					});
				";	
		} else {
		 
			$output .= "
				w = jQuery('#" .$post->post_name ."-dad').width();
				h = w*" .($image_size[1])/($image_size[0]*2) .";
				jQuery(function ($) {
					$('#" .$post->post_name ."-container').height(h);";
			if($booklet) {
				$output.= "	
					$(function() {
						$('#" .$post->post_name ."').booklet({
							width: w,
							height:h,
							overlays: false, ";
					if($coverpage) { $output .= "
							closed	:	true,
							covers	: true,
							autoCenter	: true, ";  }
				$output .= "
						});
					});
				";
			} else {
				$output .= "
					$('#switch-mode').html('view booklet');";
			}
		}
		$output .="
				//switch mode function
				$('#switch-mode').click(function() {
						$('#" .$post->post_name ."').booklet({
							  width: w,
							  height:h,
							  overlays: false, ";
		if($coverpage) { $output .= "
							  closed	:	true,
							  covers	: true,
							  autoCenter	: true, ";  }

		$output .= "
						  });
						if($(this).html() == 'view single page') {
							$(this).html('view booklet');
							$('#" .$post->post_name ."').booklet('destroy');
						} else {
							$(this).html('view single page');
						}
					});
				//zoom functions
				$('#booklet-in').click(function() { 
					imgw = $('#" .$post->post_name ."').width() *1.2;
					imgh = $('#" .$post->post_name ."').height() *1.2;
					$('#" .$post->post_name ."').booklet('option', 'width', imgw);
					$('#" .$post->post_name ."').booklet('option', 'height', imgh);\n";
		if(iframe) $output .="$('.booklet-dad').width( imgw);";	
		$output .= "
				});
				$('#booklet-out').click(function() { 
					imgw = $('#" .$post->post_name ."').width() /1.2;
					imgh = $('#" .$post->post_name ."').height() /1.2;
					$('#" .$post->post_name ."').booklet('option', 'width', imgw);
					$('#" .$post->post_name ."').booklet('option', 'height', imgh);\n";
		if(iframe) $output .="$('.booklet-dad').width( imgw);";	
		$output .= "
				});
			});
			</script>
			";
		return $output;
	}
	
	public function load_booklet_js() {
		$myBase = plugins_url("", __FILE__);
       // -- Booklet --
        wp_enqueue_script('jqueryui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.8/jquery-ui.min.js');
        wp_enqueue_script('jqueryeasing', $myBase .'/booklet/jquery.easing.1.3.js');
        wp_enqueue_script('booklet', $myBase ."/booklet/jquery.booklet.latest.js");
        wp_enqueue_style('bookletcss', $myBase ."/booklet/jquery.booklet.latest.css");
	}
	
	public function load_fancybox() {
		$myBase = plugins_url("", __FILE__);
		wp_enqueue_style('fancyboxcss', $myBase ."/fancybox/jquery.fancybox.css");
		wp_enqueue_script('fancyboxjs', $myBase ."/fancybox/jquery.fancybox.pack.js");
		//wp_enqueue_script('fancyboxfullscreenjs', $myBase ."/fancybox/jquery.fullscreen.js");
	}

}

?>
