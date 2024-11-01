<?php

$i = 0;
$dirname = dirname( __FILE__ );
do {
	$dirname = dirname( $dirname );
	$wp_load = "{$dirname}/wp-load.php";
}
while( ++$i < 10 && !file_exists( $wp_load ) );

require_once( $wp_load );
require_once( ABSPATH . '/wp-admin/includes/admin.php' );

global $ultimatemember, $ultimatemembergallery;

$id = esc_attr( $_POST['key'] );
$ultimatemember->fields->set_id = esc_attr( $_POST['set_id'] );
$ultimatemember->fields->set_mode = esc_attr( $_POST['set_mode'] );

$type =  esc_attr( $_POST['type'] );
$ret['error'] = null;
$ret = array();

if(isset($_FILES[$id]['name'])) {

    if(!is_array($_FILES[$id]['name'])) {
	
		$newupload = media_handle_upload( $id, 0 );

		$ultimatemembergallery->handle_media_upload($newupload, $type);
		
		if( $type == 'video' ){
			$uploaded =  wp_get_attachment_url($newupload);		
			echo json_encode(array('reload'=>true));die();
		}
		else{
			$uploaded =  wp_get_attachment_image_src($newupload);
			$full =  wp_get_attachment_image_src($newupload, 'full');
			$ret[] =  array(
					'src' 	=>	$uploaded[0],
					'full'	=>	$full[0],
					'id'	=>	$newupload					
					
			);
		}
		
    }
	
} else {
	$ret['error'] = __('Server error!','ultimatemember');
}

echo json_encode($ret);