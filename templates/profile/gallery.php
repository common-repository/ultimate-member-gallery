<?php

global $ultimatemember, $ultimatemembergallery;



$user_id = um_user('ID');

$images = $ultimatemembergallery->get_gallery_photo_id();

echo '<div class="gallery-image-container" data-gallery_key="'. $ultimatemembergallery->get_gallery_photo_key() .'">';

if( count($images) > 0 ){
	
	$count = 1;
	
	foreach($images as $image ){
			    
		$src = wp_get_attachment_image_src($image->value);
		$full = wp_get_attachment_image_src($image->value, 'full');
		
		$col_class = $count % 3 == 1 ? 'first' : ($count % 3 == 0 ? 'last' : '');
		
		if( !$ultimatemembergallery->is_owner() && empty($src) ){
			continue;
		}
		
		$count++;
		
	?>
		<div class="gallery-field gallery-image-field <?php echo $col_class?> media-id-<?php echo $image->id?>">
			<div class="um-gallery-single-image um-gallery-single">
				
				<img src="<?php echo $src[0];?>">
				
				<div class="preview">
					<a href="<?php echo $full[0];?>" data-rel="prettyphoto" class="preview-elem"><i class="um-faicon-photo"></i></a>
					<?php 
			        	$ultimatemembergallery->remove_link(array(
							'id'				=>	$image->id,
							'header'			=>	__('Delete this photo', 'ultimate-member-gallery'),
							'text'				=>	__('Are you sure you want to delete this photo?', 'ultimate-member-gallery')
						));
			        ?>
				</div>
			</div>
		</div>
	<?php 
	}
	
}
else{

	echo '<p>No photo found</p>';

}

echo '</div>';
echo '<div class="clear clearfix"></div>';
?>





<?php if( is_ultimate_member_on_gallery() ) : ?>

	<div class="profile-photos-tab">
		<div class="um-profile-item um-trigger-menu-on-click photo-uploader" data-user_id="<?php echo um_profile_id(); ?>">
			<?php $ultimatemember->fields->add_hidden_field( 'gallery_photo_uploader' );?>
			<a class="um-manual-trigger um-button" href="#"
				data-parent=".photo-uploader" data-child=".um-btn-auto-width">Upload Photo</a>
			
		</div>
	</div>

<?php endif;?>
		
	
	
	