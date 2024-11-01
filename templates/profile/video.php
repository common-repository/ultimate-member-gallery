<?php

global $ultimatemember, $ultimatemembergallery;

$user_id = um_user('ID');

$videos = $ultimatemembergallery->get_gallery_video_id();

echo '<div class="gallery-video-container" data-gallery_key="'. $ultimatemembergallery->get_gallery_video_key() .'">';

if( count($videos) > 0 ){

	
	
	$count = 1;
	
	foreach($videos as $video ){
	    
	    if( !$video->internal ){
	        $oembed = wp_oembed_get($video->value, array('width' => '280', 'height' => '240'));
	        
	        if( !$oembed && !$ultimatemembergallery->is_owner() ){
	            continue;
	        }
	        
	    }
		$col_class = $count % 2 == 1 ? 'first' : ($count % 2 == 0 ? 'last' : '');
		
		$count++;
		
	?>
		<div class="gallery-field gallery-video-field <?php echo $col_class?> media-id-<?php echo $video->id?>">
			<div class="um-gallery-single-video um-gallery-single">
			    <div class="um-gallery-single-holder">
			    <?php if( $video->internal ):?>
			    <?php $src = wp_get_attachment_url($video->value);?>
				<video class="video-js vjs-default-skin gallery_video_single vjs-big-play-centered" width="280" height="240" loop controls data-setup='{"example_option":true}'>
		            <source src="<?php echo $src;?>">
		        </video>
		        <?php else:?>
		              <?php 
		                  if( $oembed ){
		                     echo $oembed;
		                  }
		                  else{
		                      echo '<p class="error">This video url is not supported. For a list of supported providers please go <a href="https://codex.wordpress.org/Embeds">here</a></p>';
		                  }
		              ?>
		        <?php endif;?>
		        </div>
		        <div class="preview">
    		        <?php 
    		        	$ultimatemembergallery->remove_link(array(
    						'id'				=>	$video->id,
    						'header'			=>	__('Delete this video', 'ultimate-member-gallery'),
    						'text'				=>	__('Are you sure you want to delete this video?', 'ultimate-member-gallery')
    					));
    		        ?>
		        </div>
			</div>
			
		</div>
	<?php 
	}
		

}

else{

	echo '<p>No video found</p>';

}
echo '</div>';
echo '<div class="clear clearfix"></div>';

?>


<?php if( is_ultimate_member_on_gallery() ) : ?>

	<div class="profile-videos-tab" style="position:relative">
       
		<a class="um-button gallery-video-add" href="#">Add Videos</a>
		
		<div class="um-profile-item um-trigger-menu-on-click photo-uploader" data-user_id="<?php echo um_profile_id(); ?>">
			<?php $ultimatemember->fields->add_hidden_field( 'gallery_video_uploader' );?>
			<?php 
    			$items = array(
    			    '<a class="umg-manual-trigger" href="#"	data-parent=".photo-uploader" data-child=".um-btn-auto-width" data-modal_t="upload">Upload Video</a>',
    			    '<a class="umg-manual-trigger" href="#"	data-parent=".photo-uploader" data-child=".um-btn-auto-width" data-modal_t="url">From URL</a>',
    			    '<a href="#" class="um-dropdown-hide">'.__('Cancel','ultimatemember').'</a>',
    			);
    				
    			echo $ultimatemember->menu->new_ui( 'bc', 'div.profile-videos-tab', 'click', $items );
    		?>
		</div>
	</div>
	<div class="upload-btn">	
		
	</div>

<?php endif;?>
	
	
	
	
	