<?php
/**
 Plugin Name: Ultimate Member Gallery
 Plugin URI: http://mak.bdmonkey.com/dev/plugins/ultimate-member-gallery
 Description: An extension for the Ultimate Member plugin to allow users to upload multiple gallery images in teir profile.
 Version: 1.1.3
 Author: MAK JOy
 Author URI: http://mak.bdmonkey.com/
 */

if( !defined('ABSPATH') )	exit;

define('UM_GALLERY_DIR', plugin_dir_path(__FILE__));
define('UM_GALLERY_URI', plugin_dir_url(__FILE__));
define('UM_GALLERY_VERSION', '1.1.3' );
define('UM_GALLERY_DB_VERSION', '3' );

require_once UM_GALLERY_DIR . 'functions.php';

ultimate_member_gallery_install();

if ( ! is_ultimate_member_plugin_active() ) {
	add_action( 'admin_notices', 'ultimate_member_plugin_inactive_notice' );
	return;
}

class Nom_Ulimate_Member_Gallery{
	
	private $version = '';
	
	public function __construct(){

	    $this->version = UM_GALLERY_VERSION;
	    
		add_action( 'plugins_loaded', array($this, 'load_plugin_textdomain') );
	  
	    $this->setup();
	    
	    if( is_admin() ){
	        add_filter('redux-sections', array($this, 'admin_sections') );
	    }
	    		
	}
	
	
	public function is_user_allowed_gallery(){
	    
	    if( !is_user_logged_in() ){
	        return true;
	    }
	    
	    global $ultimatemember;
	    
	    $user_id = $ultimatemember->user->profile['ID'];
	    
	    $user = get_user_by('id', $user_id);
	    
	    $user_roles = $user->roles;
	    
	    $allowed = ( isset($ultimatemember->options['gallery_roles']) ) ? $ultimatemember->options['gallery_roles'] : array();
	    
	    if( empty($allowed) ){
	        return true;
	    }
	    
	    return count(array_intersect($user_roles, $allowed)) !== 0;
	}
	
	public function setup(){
	    
	    add_filter( 'um_profile_tabs', array( $this, 'um_profile_tabs'), 200 );
	    add_action( 'um_profile_content_gallery_photo', array( $this, 'content_gallery_photo') );
	    add_action( 'um_profile_content_gallery_video', array( $this, 'content_gallery_video') );
	    add_action( 'wp_enqueue_scripts',  array(&$this, 'wp_enqueue_scripts'), 10000 );
	    add_filter( 'ultimate_member_gallery_empty', array($this, 'empty_gallery') );
	    
        add_filter('um_core_fields_hook', array($this, 'gallery_field_custom') );
        add_filter('um_predefined_fields_hook', array( $this, 'hook_gallery_filed') );
        add_filter("um_gallery_photo_uploader_form_edit_field", array(&$this, 'gallery_photo_field_edit'), 20, 2);
        add_filter("um_gallery_video_uploader_form_edit_field", array(&$this, 'gallery_video_field_edit'), 20, 2);
        
        add_action('wp_ajax_ultimatemembergallery_remove_file', array($this, 'remove_media') );
        add_action('wp_ajax_ultimatemembergallery_video_from_url', array($this, 'video_from_url') );
	    
	}
	
	/**
	 * enqueue scripts in right place
	 */
	
	public function wp_enqueue_scripts(){
		if( get_query_var('profiletab') == 'gallery_photo' || get_query_var('profiletab') == 'gallery_video' ){
				
		    $prefix = '.min';
		    
		    if( defined('UM_GALLERY_DEBUG') ){
		        $prefix = '';
		    }
		    
			wp_register_script('um_gallery_scripts', UM_GALLERY_URI . "assets/js/um-gallery{$prefix}.js", array('jquery'), $this->version, true );
			wp_register_script('prettyphoto', UM_GALLERY_URI . 'assets/prettyphoto/js/jquery.prettyPhoto.js', array('jquery'), '3.1.6', true );
				
			wp_register_script('videojs', UM_GALLERY_URI . 'assets/video-js/video.js', array('jquery'), '3.1.6', true );
				
			wp_enqueue_script('um_gallery_scripts');
			wp_enqueue_script('prettyphoto');
				
			wp_localize_script( 'um_gallery_scripts', 'um_gallery_scripts', array(
			     'galleryimageupload' => UM_GALLERY_URI . 'lib/upload/um-image-upload.php',
			     'umg_admin_ajax' => admin_url('admin-ajax.php')
					)
			);
				
			wp_register_style('um_gallery_style', UM_GALLERY_URI . "assets/css/um-gallery{$prefix}.css", '', $this->version, 'all' );
			wp_register_style('prettyphoto', UM_GALLERY_URI . 'assets/prettyphoto/css/prettyPhoto.css', '', '3.1.6', 'all' );
				
			wp_register_style('videojs', UM_GALLERY_URI . 'assets/video-js/video-js.min.css', '', '3.1.6', 'all' );
				
			wp_enqueue_style('um_gallery_style');
			wp_enqueue_style('prettyphoto');
	
			if( get_query_var('profiletab') == 'gallery_video' ){
				wp_enqueue_script('videojs');
				wp_enqueue_style('videojs');
	
				add_action('wp_footer', array($this, 'setup_videojs'), 10000);
			}
		}
	}
	
	public function setup_videojs(){
		echo '<script>videojs.options.flash.swf = "'. UM_GALLERY_URI .'assets/video-js/video-js.swf"</script>';
	}
	
	/**
	 * Load the textdomain for this plugin
	 */
	
	public function load_plugin_textdomain(){
	
		$locale = apply_filters( 'plugin_locale', get_locale(), 'ultimate-member-gallery' );
	
		// Allow upgrade safe, site specific language files in /wp-content/languages/ultimate-member-gallery/
		load_textdomain( 'ultimate-member-gallery', WP_LANG_DIR.'/ultimate-member-gallery-'.$locale.'.mo' );
	
		$plugin_rel_path = apply_filters( 'ultimate_member_gallery_translation_file_rel_path', dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	
		// Then check for a language file in /wp-content/plugins/ultimate-member-gallery/languages/ (this will be overriden by any file already loaded)
		load_plugin_textdomain( 'ultimate-member-gallery', false, $plugin_rel_path );
	}
	
	/**
	 * 
	 * Filter to configure the text to be shown after deletion of all the contents in a given gallery. 
	 * This text will be displayed if users delete all their contents in their gallery.
	 * 
	 * @param array $data
	 * @return array
	 */
	
	public function empty_gallery($data){
		$data['gallery_photo_uploader']	= __('No photo found in this gallery.', 'ultimate-mamber-gallery');
		$data['gallery_video_uploader']	= __('No video found in this gallery.', 'ultimate-mamber-gallery');
		return $data;
	}
	
	
	/**
	 * Handler to remove/delete content from the gallery.
	 */
	
	public function remove_media(){

		
		$id = $_REQUEST['id'];
		$key = $_REQUEST['key'];
		
		$empty = apply_filters('ultimate_member_gallery_empty', array());
		
		if( ! isset($empty[$key]) ){
			$empty[$key] = __('No item found in this gallery.', 'ultimate-mamber-gallery');
		}
				
		if ( $id ) {
		
			$user_id = um_profile_id();
			
			
			
			$media = $this->get_media_by('id', $id);
			
			wp_delete_attachment($media->value, true);
			
			$this->delete_media($id);
			
			echo json_encode(array('id' => $id, 'key' => $key, 'r' =>'ok', 'empty' => $empty[$key]));
			
			die(0);
		} else {
						
			echo json_encode(array('id' => $id, 'key' => $key, 'r' =>'Not a valid file'));
			
			die(0);
		}
		
	}
	
	public function video_from_url(){

		$key = $_REQUEST['key'];
		$val = $_REQUEST['value'];
		
		$empty = apply_filters('ultimate_member_gallery_empty', array());
		
		if( ! isset($empty[$key]) ){
			$empty[$key] = __('No item found in this gallery.', 'ultimate-mamber-gallery');
		}
		
		if( $val == '' ){
		    die('0000');
		}
				
        $this->handle_media_insertion( 'video', $val);
				
		echo '1111';
		
		die(0);
	}
	
	/**
	 * 
	 * This function returns all the content required to insert a gallery photo upload button
	 * 
	 * @param string $output
	 * @param string $mode
	 * @return string
	 */
	
	public function gallery_photo_field_edit($output, $mode){
 	
	 	global $ultimatemember;
	 	$key = 'gallery_photo_uploader';
	 	
	 	$array = $ultimatemember->fields->get_field($key);
	 	
	 	if ( !isset( $array['crop'] ) ) $array['crop'] = 0;
	 	
	 	if ( $array['crop'] == 0 ) {
	 		$array['crop_data'] = 0;
	 	} else if ( $array['crop'] == 1 ) {
	 		$array['crop_data'] = 'square';
	 	} else if ( $array['crop'] == 2 ) {
	 		$array['crop_data'] = 'cover';
	 	} else {
	 		$array['crop_data'] = 'user';
	 	}
	 	
	 	if ( !isset( $array['modal_size'] ) ) $array['modal_size'] = 'normal';
	 	
	 	if ( $array['crop'] > 0 ) {
	 		$array['crop_class'] = 'crop';
	 	} else {
	 		$array['crop_class'] = '';
	 	}
	 	
	 	if ( !isset( $array['ratio'] ) ) $array['ratio'] = 1.0;
	 	
	 	if ( !isset( $array['min_width'] ) ) $array['min_width'] = '';
	 	if ( !isset( $array['min_height'] ) ) $array['min_height'] = '';
	 	
	 	if ( $array['min_width'] == '' && $array['crop'] == 1 ) $array['min_width'] = 600;
	 	if ( $array['min_height'] == '' && $array['crop'] == 1 ) $array['min_height'] = 600;
	 	
	 	if ( $array['min_width'] == '' && $array['crop'] == 3 ) $array['min_width'] = 600;
	 	if ( $array['min_height'] == '' && $array['crop'] == 3 ) $array['min_height'] = 600;
	 	
	 	if (!isset($array['invalid_image'])) $array['invalid_image'] = __("Please upload a valid photo!",'ultimate-member-gallery');
	 	if (!isset($array['allowed_types'])) {
	 		$array['allowed_types'] = 'gif,jpg,jpeg,png';
	 	}
	 	
	 	if (!isset($array['upload_text'])) $array['upload_text'] = '';
	 	if (!isset($array['button_text'])) $array['button_text'] = __('Upload','ultimate-member-gallery');
	 	if (!isset($array['extension_error'])) $array['extension_error'] =  __("Sorry this is not a valid photo.",'ultimate-member-gallery');
	 	if (!isset($array['max_size_error'])) $array['max_size_error'] = __("This photo is too large!",'ultimate-member-gallery');
	 	if (!isset($array['min_size_error'])) $array['min_size_error'] = __("This photo is too small!",'ultimate-member-gallery');
	 	if (!isset($array['max_files_error'])) $array['max_files_error'] = __("You can only upload one photo",'ultimate-member-gallery');
	 	if (!isset($array['max_size'])) $array['max_size'] = 999999999;
	 	if (!isset($array['upload_help_text'])) $array['upload_help_text'] = '';
	 	if (!isset($array['icon']) ) $array['icon'] = '';
	 	
	 	extract($array);
	 	
	 	$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="'.$key.'">';
				
		$output .= '<input type="hidden" name="'.$key.$ultimatemember->form->form_suffix.'" id="'.$key.$ultimatemember->form->form_suffix.'" value="" />';
					
		if ( isset( $data['label'] ) ) {
			$output .= $ultimatemember->fields->field_label($label, $key, $array);
		}
					
		$modal_label = ( isset( $array['label'] ) ) ? $array['label'] : __('Upload Photo','ultimate-member-gallery');

		$output .= '<div class="um-field-area" style="text-align: center">';
		
		$output .= '<div class="um-single-image-preview '. $crop_class .'" data-crop="'.$crop_data.'" data-key="'.$key.'">
				<a href="#" class="cancel"><i class="um-icon-close"></i></a>
				<img src="" alt="" />
			</div><a href="#" data-modal_t="upload" data-modal="umgallery_upload_single" data-modal-size="'.$modal_size.'" class="um-button um-btn-auto-width">'. $button_text . '</a>';
			
		$output .= '</div>';

		/* modal hidden */
		$output .= '<div class="um-modal-hidden-content upload">';

		$output .= '<div class="um-modal-header"> ' . $modal_label . '</div>';
		
		$output .= '<div class="um-modal-body">';
		
		if ( isset( $ultimatemember->fields->set_id ) ) {
			$set_id = $ultimatemember->fields->set_id;
			$set_mode = $ultimatemember->fields->set_mode;
		} else {
			$set_id = 0;
			$set_mode = '';
		}
					
		$output .= '<div class="um-image-preview '. $crop_class .'" data-crop="'.$crop_data.'" data-ratio="'.$ratio.'" data-min_width="'.$min_width.'" data-min_height="'.$min_height.'" data-coord=""><div class="hold"></div></div>';
					
				
		$output .= '<div class="um-gallery-image-upload" data-gallery_type="photo" data-icon="'.$icon.'" data-set_id="'.$set_id.'" data-set_mode="'.$set_mode.'" data-type="'.$type.'" data-key="'.$key.'" data-max_size="'.$max_size.'" data-max_size_error="'.$max_size_error.'" data-min_size_error="'.$min_size_error.'" data-extension_error="'.$extension_error.'"  data-allowed_types="'.$allowed_types.'" data-upload_text="'.$upload_text.'" data-max_files_error="'.$max_files_error.'" data-upload_help_text="'.$upload_help_text.'">' .$button_text. '</div>';
		$output .= '<div class="um-tip"><p class="filetypetip"><small>Allowed Filetypes ' . $allowed_types . '; maximum filesize to upload '. wp_max_upload_size_to_hr() .'</small></p></div>';
		$output .= '<div class="um-modal-footer">
						<div class="um-modal-right">
							<a href="#" class="um-modal-btn um-finish-upload image disabled" data-key="'.$key.'" data-change="'.__('Change photo','ultimate-member-gallery').'" data-processing="'.__('Processing...','ultimate-member-gallery').'"> ' . __('Okay','ultimate-member-gallery') . '</a>
							<a href="#" class="um-modal-btn alt" data-action="um_remove_modal"> ' . __('Cancel','ultimate-member-gallery') . '</a>
						</div>
						<div class="um-clear"></div>
					</div>';
					
		$output .= '</div>';
		
		$output .= '</div>';
		
		/* end */
		
		if ( $ultimatemember->fields->is_error($key) ) {
			$output .= $ultimatemember->fields->field_error( $this->show_error($key) );
		}
		
		$output .= '</div>';
 	
 		return $output; 
 	}
 	
 	/**
 	 * This function returns all the content required to generate a gallery video upload button.
 	 * @param string $output
 	 * @param string $mode
 	 * @return string
 	 */
 	
	public function gallery_video_field_edit($output, $mode){
 	
	 	global $ultimatemember;
	 	$key = 'gallery_video_uploader';
	 	
	 	$array = $ultimatemember->fields->get_field($key);
	 	
	 	if ( !isset( $array['crop'] ) ) $array['crop'] = 0;
	 	
	 	if ( $array['crop'] == 0 ) {
	 		$array['crop_data'] = 0;
	 	} else if ( $array['crop'] == 1 ) {
	 		$array['crop_data'] = 'square';
	 	} else if ( $array['crop'] == 2 ) {
	 		$array['crop_data'] = 'cover';
	 	} else {
	 		$array['crop_data'] = 'user';
	 	}
	 	
	 	if ( !isset( $array['modal_size'] ) ) $array['modal_size'] = 'normal';
	 	
	 	if ( $array['crop'] > 0 ) {
	 		$array['crop_class'] = 'crop';
	 	} else {
	 		$array['crop_class'] = '';
	 	}
	 	
	 	if ( !isset( $array['ratio'] ) ) $array['ratio'] = 1.0;
	 	
	 	if ( !isset( $array['min_width'] ) ) $array['min_width'] = '';
	 	if ( !isset( $array['min_height'] ) ) $array['min_height'] = '';
	 	
	 	if ( $array['min_width'] == '' && $array['crop'] == 1 ) $array['min_width'] = 600;
	 	if ( $array['min_height'] == '' && $array['crop'] == 1 ) $array['min_height'] = 600;
	 	
	 	if ( $array['min_width'] == '' && $array['crop'] == 3 ) $array['min_width'] = 600;
	 	if ( $array['min_height'] == '' && $array['crop'] == 3 ) $array['min_height'] = 600;
	 	
	 	if (!isset($array['invalid_image'])) $array['invalid_image'] = __("Please upload a valid video!",'ultimate-member-gallery');
	 	
	 	
	 	if (!isset($array['allowed_types'])) $array['allowed_types'] = 'mp4';
	 	
	 	if (!isset($array['upload_text'])) $array['upload_text'] = '';
	 	if (!isset($array['button_text'])) $array['button_text'] = __('Upload','ultimate-member-gallery');
	 	if (!isset($array['extension_error'])) $array['extension_error'] =  __("Sorry either this is not a valid video or this format is not supported.",'ultimate-member-gallery');
	 	if (!isset($array['max_size_error'])) $array['max_size_error'] = __("This video is too large!",'ultimate-member-gallery');
	 	if (!isset($array['min_size_error'])) $array['min_size_error'] = __("This video is too small!",'ultimate-member-gallery');
	 	if (!isset($array['max_files_error'])) $array['max_files_error'] = __("You can only upload one video",'ultimate-member-gallery');
	 	
	 	if (!isset($array['upload_help_text'])) $array['upload_help_text'] = '';
	 	if (!isset($array['icon']) ) $array['icon'] = '';
	 	
	 	extract($array);
	 	
	 	$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="'.$key.'">';
				
		$output .= '<input type="hidden" name="'.$key.$ultimatemember->form->form_suffix.'" id="'.$key.$ultimatemember->form->form_suffix.'" value="" />';
					
		if ( isset( $data['label'] ) ) {
			$output .= $ultimatemember->fields->field_label($label, $key, $array);
		}
					
		$modal_label = ( isset( $array['label'] ) ) ? $array['label'] : __('Upload Video','ultimate-member-gallery');

		$output .= '<div class="um-field-area" style="text-align: center">';
		
		$output .= '<div class="um-single-image-preview '. $crop_class .'" data-crop="'.$crop_data.'" data-key="'.$key.'">
				<a href="#" class="cancel"><i class="um-icon-close"></i></a>
				<img src="" alt="" />
			</div><a href="#" data-modal="umgallery_upload_single" data-modal-size="'.$modal_size.'" class="um-button um-btn-auto-width">'. $button_text . '</a>';
			
		$output .= '</div>';

		/* modal hidden */
		$output .= '<div class="um-modal-hidden-content upload">';

		$output .= '<div class="um-modal-header"> ' . $modal_label . '</div>';
		
		$output .= '<div class="um-modal-body">';
		
		if ( isset( $ultimatemember->fields->set_id ) ) {
			$set_id = $ultimatemember->fields->set_id;
			$set_mode = $ultimatemember->fields->set_mode;
		} else {
			$set_id = 0;
			$set_mode = '';
		}
					
		$output .= '<div class="um-single-image-preview '. $crop_class .'" data-crop="'.$crop_data.'" data-ratio="'.$ratio.'" data-min_width="'.$min_width.'" data-min_height="'.$min_height.'" data-coord=""><a href="#" class="cancel"><i class="um-icon-close"></i></a><img src="" alt="" /></div>';
					
				
		$output .= '<div class="um-gallery-image-upload" data-gallery_type="video" data-icon="'.$icon.'" data-set_id="'.$set_id.'" data-set_mode="'.$set_mode.'" data-type="'.$type.'" data-key="'.$key.'" data-max_size="'.$max_size.'" data-max_size_error="'.$max_size_error.'" data-min_size_error="'.$min_size_error.'" data-extension_error="'.$extension_error.'"  data-allowed_types="'.$allowed_types.'" data-upload_text="'.$upload_text.'" data-max_files_error="'.$max_files_error.'" data-upload_help_text="'.$upload_help_text.'">' .$button_text. '</div>';
		$output .= '<div class="um-tip"><p><small>Allowed Filetypes ' . $allowed_types . '; maximum filesize to upload '. wp_max_upload_size_to_hr() .'</small></p></div>';
		$output .= '<div class="um-modal-footer">
						<div class="um-modal-right">
							<a href="#" class="um-modal-btn um-finish-upload image disabled" data-key="'.$key.'" data-change="'.__('Change photo','ultimate-member-gallery').'" data-processing="'.__('Processing...','ultimate-member-gallery').'"> ' . __('Okay','ultimate-member-gallery') . '</a>
							<a href="#" class="um-modal-btn alt" data-action="um_remove_modal"> ' . __('Cancel','ultimate-member-gallery') . '</a>
						</div>
						<div class="um-clear"></div>
					</div>';
					
		$output .= '</div>';
		
		$output .= '</div>';
		
		/* end */
		
		/* modal hidden */
		$output .= '<div class="um-modal-hidden-content url">';

		$output .= '<div class="um-modal-header"> ' . $modal_label . '</div>';
		
		$output .= '<div class="um-modal-body">';
		$output .= '<div class="um-modal-form"><form action="" method="POST">';
		
		if ( isset( $ultimatemember->fields->set_id ) ) {
			$set_id = $ultimatemember->fields->set_id;
			$set_mode = $ultimatemember->fields->set_mode;
		} else {
			$set_id = 0;
			$set_mode = '';
		}
				
		$output .= '  <div class="form-elem">
		                  <textarea style="width:100%" name="embed_video" data-key="'. $key .'"></textarea>
		                  <div class="um-tip"><small>Paste the url of your video</small></div>
		              </div>
		              <div class="um-modal-footer">
						<div class="um-modal-right">
							<a href="#" class="um-modal-btn um-finish-upload image url" data-key="'.$key.'" data-change="'.__('Change photo','ultimate-member-gallery').'" data-processing="'.__('Processing...','ultimate-member-gallery').'"> ' . __('Save','ultimate-member-gallery') . '</a>
							<a href="#" class="um-modal-btn alt" data-action="um_remove_modal"> ' . __('Cancel','ultimate-member-gallery') . '</a>
						</div>
						<div class="um-clear"></div>
					</div>';
					
		$output .= '</form>';
		$output .= '</div>';
		$output .= '</div>';
		
		$output .= '</div>';
		
		/* end */
		
		if ( $ultimatemember->fields->is_error($key) ) {
			$output .= $ultimatemember->fields->field_error( $this->show_error($key) );
		}
		
		$output .= '</div>';
 	
 		return $output; 
 	}
 	
 	/**
 	 * Hook in the dewfault list of fields in the ultimate member core so that it 
 	 * recognizes the gallery photo and video uploader fields.
 	 * 
 	 * @param array $fields
 	 * @return array
 	 */
	
	public function hook_gallery_filed($fields){
		
		$fields['gallery_photo_uploader'] = array(
				'title' => __('Upload Photo','ultimatemember'),
				'metakey' => 'gallery_photo',
				'type' => 'gallery_photo',
				'label' => __('Add photos in your profile','ultimatemember'),
				'upload_text' => __('Upload your photo here','ultimatemember'),
				'icon' => 'um-faicon-camera',
				'allowed_types'	=>	implode(',',apply_filters('allowed_gallery_photo_types', array('gif','jpg','jpeg','png')) ),
				'crop' => 0,
				'max_size' => apply_filters('ultimate_member_gallery_photo_max_size', wp_max_upload_size() ),
				'min_width' => str_replace('px','',um_get_option('profile_photosize')),
				'min_height' => str_replace('px','',um_get_option('profile_photosize')),
		);
		
		$fields['gallery_video_uploader'] = array(
				'title' => __('Upload Video','ultimatemember'),
				'metakey' => 'gallery_video',
				'type' => 'gallery_video',
				'label' => __('Add videos in your profile','ultimatemember'),
				'upload_text' => __('Upload your video here','ultimatemember'),
				'allowed_types' => implode(',',apply_filters('allowed_gallery_video_types', array('mp4')) ),
				'icon' => 'um-faicon-video-camera',
				'crop' => 0,
				'max_size' => apply_filters('ultimate_member_gallery_video_max_size', wp_max_upload_size() ),
				'min_width' => str_replace('px','',um_get_option('profile_photosize')),
				'min_height' => str_replace('px','',um_get_option('profile_photosize')),
		);
		
		return $fields;
	}
	
	/**
	 * Required metadata for hokking the field types in ultimate member
	 * plugin's core fields
	 * 
	 * @param unknown $field
	 * @return unknown
	 */
	
	public function gallery_field_custom($field){

		$field['gallery_photo'] = array(
				'name' => 'Gallery Photo Upload',
				'col1' => array('_title','_metakey','_help','_allowed_types','_max_size','_crop','_visibility'),
				'col2' => array('_label','_public','_roles','_upload_text','_upload_help_text','_button_text'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
						'_title' => array(
								'mode' => 'required',
								'error' => 'You must provide a title'
						),
						'_metakey' => array(
								'mode' => 'unique',
						),
						'_max_size' => array(
								'mode' => 'numeric',
								'error' => 'Please enter a valid size'
						),
				)
		);
		$field['gallery_video'] = array(
				'name' => 'Gallery Video Upload',
				'col1' => array('_title','_metakey','_help','_allowed_types','_max_size','_crop','_visibility'),
				'col2' => array('_label','_public','_roles','_upload_text','_upload_help_text','_button_text'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
						'_title' => array(
								'mode' => 'required',
								'error' => 'You must provide a title'
						),
						'_metakey' => array(
								'mode' => 'unique',
						),
						'_max_size' => array(
								'mode' => 'numeric',
								'error' => 'Please enter a valid size'
						),
				)
		);
		
		return $field;		
	}
	
	/**
	 * Load the photo gallery template
	 */
	
	public function content_gallery_photo(){
		$this->content_gallery('profile/gallery');
	}
	
	/**
	 * Load the video gallery template
	 */
	
	public function content_gallery_video(){
		$this->content_gallery('profile/video');
	}
	
	/**
	 * Load the template 
	 * @param string $tpl
	 */
	
	public function content_gallery($tpl){
		global $ultimatemember;
		
		$loop = ( $ultimatemember->shortcodes->loop ) ? $ultimatemember->shortcodes->loop : '';
		
		if ( isset( $ultimatemember->shortcodes->set_args ) && is_array( $ultimatemember->shortcodes->set_args ) ) {
			$args = $ultimatemember->shortcodes->set_args;
			extract( $args );
		}
		
		$file = UM_GALLERY_DIR . 'templates/' . $tpl . '.php';
		$theme_file = get_stylesheet_directory() . '/ultimate-member/templates/' . $tpl . '.php';
		
		if ( file_exists( $theme_file ) )
			$file = $theme_file;
			
		if ( file_exists( $file ) )
			include $file;
		
	}
	
	/**
	 * Tabs in the user profile nav bar
	 * @param array $tabs
	 * @return array
	 */
	
	public function um_profile_tabs($tabs){
	    
	    if( !$this->is_user_allowed_gallery() ){
	        return $tabs;
	    }
		
	    global $ultimatemember;
		
	    $tab_photo_name = ( isset($ultimatemember->options['gallery_photo_title']) &&  $ultimatemember->options['gallery_photo_title'] != '') ? $ultimatemember->options['gallery_photo_title'] : apply_filters( 'ultimate_member_gallery_photo_tab_name', __('Photo Gallery','ultimate-member-gallery') );
	    $tab_photo_icon = ( isset($ultimatemember->options['gallery_photo_icon']) &&  $ultimatemember->options['gallery_photo_icon'] != '') ? $ultimatemember->options['gallery_photo_icon'] : apply_filters( 'ultimate_member_gallery_photo_tab_icon', 'um-faicon-photo' );
	    
	    $tab_video_name = ( isset($ultimatemember->options['gallery_video_title']) &&  $ultimatemember->options['gallery_video_title'] != '') ? $ultimatemember->options['gallery_video_title'] : apply_filters('ultimate_member_gallery_video_tab_name', __('Video Gallery','ultimate-member-gallery') );
	    $tab_video_icon = ( isset($ultimatemember->options['gallery_video_icon']) &&  $ultimatemember->options['gallery_video_icon'] != '') ? $ultimatemember->options['gallery_video_icon'] : apply_filters('ultimate_member_gallery_video_tab_icon', 'um-faicon-video-camera');
	    
		$tabs['gallery_photo'] = array(
				'name' 		=> $tab_photo_name,
				'icon' 		=> $tab_photo_icon,
		);
		
		$tabs['gallery_video'] = array(
				'name' 		=> $tab_video_name,
				'icon' 		=> $tab_video_icon,
		);
		
		return $tabs;
	}
	
	/**
	 * get the list of the photos in a gallery
	 * @return array
	 */
	
	public function get_gallery_photo_id(){
		return $this->get_media('photo', um_user('ID'));
	}
	
	/**
	 * Get the list of the videos in a gallery
	 * @return array
	 */
	
	public function get_gallery_video_id(){
		return $this->get_media('video', um_user('ID'));
	}
	
	
	public function get_gallery_video_key(){
		return 'gallery_video_uploader';
	}
	
	public function get_gallery_photo_key(){
		return 'gallery_photo_uploader';
	}
	
	public function is_owner(){
		return um_user('ID') == get_current_user_id();
	}
	
	/**
	 * 
	 * Render a item delete link the the gallery pages.
	 * 
	 * @param array $args
	 * @return void|string
	 */
	
	public function remove_link( $args = array() ){
		
		$defaults = array(
				'id'				=>	0,
				'button_icon'		=>	'um-faicon-trash',
				'button_text'		=>	'',
				'header'			=>	__('Delete this item', 'ultimate-member-gallery'),
				'text'				=>	__('Are you sure you want to delete this item?', 'ultimate-member-gallery'),
				'button_error'		=>	__('Error', 'ultimate-member-gallery'),
				'button_processing'	=>	__('Deleting...', 'ultimate-member-gallery'),
				'button_complete'	=>	__('Complete', 'ultimate-member-gallery'),
				'button_confirm'	=>	__('Confirm', 'ultimate-member-gallery'),
				'button_cancel'		=>	__('Cancel', 'ultimate-member-gallery'),
				'echo'				=>	true
		);
		
		$args = wp_parse_args($args, $defaults);
		
		if( !$this->is_owner() ){
			return;
		}
		
		if( !$args['id'] ){
			echo 'INVALID ID';
		}
		
		$content = '					
					<a title="delete this video" href="#" class="cancel"><i class="' . $args['button_icon'] . '"></i>' . $args['button_text'] . '</a>
					<div class="um-modal-hidden-content">
						<div class="um-modal-header">' . $args['header'] . '</div>
						<div class="um-modal-body">
							<div class="um-single-preview">
								<h3>' . $args['text'] . '</h3>
							</div>
							<div class="um-modal-footer">
								<div class="um-modal-right">
									<a href="#" class="um-modal-btn um-confirm-deletion image" data-error="' . $args['button_error'] . '" data-processing="' . $args['button_processing'] . '" data-complete="' . $args['button_complete'] . '" data-key="' . $this->get_gallery_key() .'" data-id="' . $args['id'] . '">' . $args['button_confirm'] . '</a>
									<a href="#" class="um-modal-btn alt" data-action="um_remove_modal">' . $args['button_cancel'] . '</a>
								</div>
								<div class="um-clear"></div>
							</div>
						</div>
					</div>
				';
		
		if(isset($args['echo'])){
			echo $content;
			return;
		}
		else{
			return $content;
		}
	}
	
	public function handle_media_upload($id, $type){
	    
	    $attachment = get_post( $id );
	    
	    $data = array(
	        'user_id'  =>  get_current_user_id(),
            'type'     =>  $type,  
            'label'    =>  $attachment->post_title,
            'value'    =>  $id,
            'orderby'  =>  'id',
            'privacy'  =>  1,
            'internal' =>  1,
	        'content'  =>  $attachment->post_content,
	        'guid'     =>  get_permalink($id),
	        'meta_id'  =>  0
	    );
	    
	    $this->insert_media($data);
	}
	
	public function handle_media_insertion($type, $src){
	     
	    $data = array(
	        'user_id'  =>  get_current_user_id(),
	        'type'     =>  $type,
	        'label'    =>  '',
	        'value'    =>  $src,
	        'orderby'  =>  'id',
	        'privacy'  =>  1,
	        'internal' =>  0,
	        'content'  =>  '',
	        'guid'     =>  $src,
	        'meta_id'  =>  0
	    );
	     
	    $this->insert_media($data);
	}
	
	public function insert_media($data){
	    
	    global $wpdb;
	    
	    $table = "{$wpdb->prefix}ultimate_member_gallery_user_contents";	  
	      
	    $defaults = array(
	        'user_id'  =>  0,
            'type'     =>  '',  
            'label'    =>  '',
            'value'    =>  '',
            'orderby'  =>  'id',
            'privacy'  =>  1,
            'internal' =>  1,
	        'content'  =>  '',
	        'guid'     =>  '',
	        'meta_id'  =>  0
	    );
	    
	    $format = array( '%d','%s', '%s', '%s', '%s', '%d', '%d' );
	    
	    $data = wp_parse_args( $data, $defaults );
	    
	    if( $data['user_id'] == 0 ){
	        $data['user_id'] = get_current_user_id();
	    }
	    
	    $wpdb->insert($table, $data, $format);
	}
	
	public function delete_media($id){
	    
	    global $wpdb;
	     
	    $table = "{$wpdb->prefix}ultimate_member_gallery_user_contents";
	    
	    $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
	}
	
	public function get_media_by($by, $value){
	    global $wpdb;
	    
	    $table = "{$wpdb->prefix}ultimate_member_gallery_user_contents";
	     
	    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE `{$by}`='%s'", $value ) );
	}
	
	public function get_media($type, $user_id, $meta_id = 0, $privacy = 1){

	    global $wpdb;
	     
	    $table = "{$wpdb->prefix}ultimate_member_gallery_user_contents";
	    
	    return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE `type`='%s' AND `user_id`='%s' AND `meta_id`='%s' AND `privacy`='%s'", $type, $user_id, $meta_id, $privacy ) ); 
	}
	
	public function get_gallery_key(){
		
		switch( get_query_var('profiletab') ){
			case 'gallery_video' : 
				return $this->get_gallery_video_key();
			case 'gallery_photo' :
				return $this->get_gallery_photo_key();
			default: 
				return '';
		}
	}
	
	public function admin_sections($sections){
	
	   $sections[] = array(
            'icon'       => 'um-faicon-cog',
            'title'      => __( 'Gallery','ultimatemember'),
            'fields'     => array(
        
                array(
                        'id'      		=> 'gallery_photo_title',
                        'type'     		=> 'text',
                        'title'    		=> __('Photo Gallery Title','ultimate-member-gallery'),
                        'default'  		=> 'Images',
        				'desc' 	   		=> __('This is the title of the photo gallery tab.','ultimate-member-gallery'),
                ),
                array(
                        'id'      		=> 'gallery_photo_icon',
                        'type'     		=> 'text',
                        'title'    		=> __('Photo Gallery Icon Class','ultimate-member-gallery'),
                        'default'  		=> 'um-faicon-photo',
        				'desc' 	   		=> __('This is the icon of the photo gallery tab.','ultimate-member-gallery'),
                ),
//                 array(
//                         'id'      		=> 'gallery_photo_max',
//                         'type'     		=> 'text',
//                         'title'    		=> __('Maximum photo allowed','ultimate-member-gallery'),
//                         'default'  		=> '0',
//         				'desc' 	   		=> __('This is maximum number of photos allowed in the gallery. Use 0 for no restriction.','ultimate-member-gallery'),
//                 ),
                
                array(
                        'id'      		=> 'gallery_video_title',
                        'type'     		=> 'text',
                        'title'    		=> __('Video Gallery Title','ultimate-member-gallery'),
                        'default'  		=> 'Videos',
        				'desc' 	   		=> __('This is the title of the video gallery tab.','ultimate-member-gallery'),
                ),
                array(
                        'id'      		=> 'gallery_video_icon',
                        'type'     		=> 'text',
                        'title'    		=> __('Video Gallery Icon Class','ultimate-member-gallery'),
                        'default'  		=> 'um-faicon-video-camera',
        				'desc' 	   		=> __('This is the icon of the video gallery tab.','ultimate-member-gallery'),
                ),
//                 array(
//                          'id'      		=> 'gallery_video_max',
//                          'type'     	=> 'text',
//                          'title'    	=> __('Maximum video allowed','ultimate-member-gallery'),
//                          'default'  	=> '0',
//                          'desc' 	   	=> __('This is maximum number of videos allowed in the gallery. Use 0 for no restriction.','ultimate-member-gallery'),
//                 ),
        
                array(
        				'id'       		=> 'gallery_roles',
                        'type'     		=> 'select',
                        'data'          => 'roles',
                        'multi'         =>  true,
                        'title'    		=> __( 'Allowed User Roles','ultimate-member-gallery' ),
        				'desc'			=> __('Users form these selected roles will see the gallery tabs in their profile.','ultimate-member-gallery')
                ),
        		
        	)
        
        );
	
	    return $sections;
	}
}

global $ultimatemembergallery;

$ultimatemembergallery = new Nom_Ulimate_Member_Gallery();


