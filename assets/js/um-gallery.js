jQuery(document).ready(function() {
	
	
	
	jQuery('body').bind('ulimate_member_gallery_updated', function(){
		if( typeof jQuery.fn.prettyPhoto != "undefined" ){
			jQuery("a[data-rel='prettyphoto']").prettyPhoto();
		}	
	});
	
	
	jQuery(document).on('click', 'a[data-modal^="umgallery_"], span[data-modal^="umgallery_"], .um-modal a', function(e){
		e.preventDefault();
		return false;
	});
	
	jQuery(document).on('click', 'a.umg-manual-trigger', function(){
		var child = jQuery(this).attr('data-child');
		var parent = jQuery(this).attr('data-parent');
		jQuery(this).parents( parent ).find( child ).data('modal_t', jQuery(this).data('modal_t')).trigger('click')
	});
	
	jQuery(document).on('click', 'a[data-modal^="umgallery_"], span[data-modal^="umgallery_"]', function(e){

		var id = 'um_upload_single';
		var MT = jQuery(this).data('modal_t'); 
		
		if ( jQuery(this).data('modal-size')  ) {
			var size = jQuery(this).data('modal-size');
		} else {
			var size = 'normal';
		}
		
		jQuery('#' + id).html( jQuery(this).parents('.um-field').find('.um-modal-hidden-content.'+MT).html() );
		
		if ( jQuery(this).parents('.um-profile-photo').attr('data-user_id') ) {
			jQuery('#' + id).attr('data-user_id', jQuery(this).parents('.um-profile-photo').attr('data-user_id') );
		}
		
		if ( jQuery(this).parents('.um-cover').attr('data-ratio') ) {
			jQuery('#' + id).attr('data-ratio',  jQuery(this).parents('.um-cover').attr('data-ratio')  );
		}
		
		if ( jQuery(this).parents('.um-cover').attr('data-user_id') ) {
			jQuery('#' + id).attr('data-user_id',  jQuery(this).parents('.um-cover').attr('data-user_id')  );
		}
		
		var modal = jQuery('body').find('.um-modal-overlay');
		
		if ( modal.length == 0 ) {

			jQuery('.tipsy').hide();

			UM_hide_menus();
			
			jQuery('body,html,textarea').css("overflow", "hidden");
			
			jQuery(document).bind("touchmove", function(e){e.preventDefault();});
			jQuery('.um-modal').on('touchmove', function(e){e.stopPropagation();});
			
			jQuery('body').append('<div class="um-modal-overlay" /><div class="um-modal no-photo" />');
						
			jQuery('#' + id).prependTo('.um-modal');
			
			jQuery('#' + id).show();
			jQuery('.um-modal').show();

			um_modal_size( size );
		
			if( MT == 'url' ){
				//	insert from url
				
				trigger = jQuery('.um-modal:visible').find('.um-modal-form');
				
				trigger.find('[name="embed_video"]').on('focus', function(e){
					trigger.find('.um-error-block').remove();
				});
				
				trigger.find('.um-finish-upload.url').on('click', function(e){
					e.preventDefault();
					
					var key = trigger.find('[name="embed_video"]').data('key');
					var val = trigger.find('[name="embed_video"]').val();
					
					trigger.find('.um-error-block').remove();
					
					if( val == '' ){
						trigger.find('.form-elem').append('<div class="um-error-block">Video url field is empty.</div>');
						return false;
					}
					
					var pattern = new RegExp(/^(https?:\/\/)?((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|((\d{1,3}\.){3}\d{1,3}))(\:\d+)?(\/[-a-z\d%_.~+]*)*(\?[;&a-z\d%_.~+=-]*)?(\#[-a-z\d_]*)?$/i);
					
					if(!pattern.test(val)) {
						trigger.find('.form-elem').append('<div class="um-error-block">It does not seem like a valid url.</div>');
						return false;
					}
					
					var data = {
							action: 'ultimatemembergallery_video_from_url',
							key:	key,
							value:	val
					};
					
					jQuery.post(um_gallery_scripts.umg_admin_ajax, data, function(response){
						if( response == '1111' ){
							window.location.reload()
						}
						else{
							trigger.find('.form-elem').append('<div class="um-error-block">Failed to save. Please try again</div>');
						}
						um_modal_responsive();	
					});
					
					return false;
				});
				
				
				
				um_modal_responsive();	
			}
			else if( MT == 'upload' ){
				
				trigger = jQuery('.um-modal:visible').find('.um-gallery-image-upload');

				if (trigger.data('upload_help_text')){
					upload_help_text = '<span class="help">' + trigger.data('upload_help_text') + '</span>';
				} else {
					upload_help_text = '';
				}
				
				if ( trigger.data('icon') ) {
					icon = '<span class="icon"><i class="'+ trigger.data('icon') + '"></i></span>';
				} else {
					icon = '';
				}

				if ( trigger.data('upload_text') ) {
					upload_text = '<span class="str">' + trigger.data('upload_text') + '</span>';
				} else {
					upload_text = '';
				}
				
				trigger.uploadFile({
					url: um_gallery_scripts.galleryimageupload,
					method: "POST",
					multiple: true,
					sequential: true,
					serialize: true,
					formData: {key: trigger.data('key'), set_id: trigger.data('set_id'), set_mode: trigger.data('set_mode'), type: trigger.data('gallery_type') },
					fileName: trigger.data('key'),
					allowedTypes: trigger.data('allowed_types'),
					maxFileSize: trigger.data('max_size'),
					dragDropStr: icon + upload_text + upload_help_text,
					sizeErrorStr: trigger.data('max_size_error'),
					extErrorStr: trigger.data('extension_error'),
					maxFileCountErrorStr: trigger.data('max_files_error'),		
					showDelete: false,
					showAbort: false,
					showDone: false,
					showFileCounter: false,
					showStatusAfterSuccess: true,
					afterUploadAll: function(){
						trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.upload-statusbar,.filetypetip').hide(0);
						trigger.parents('.um-modal-body').find('.um-image-preview').show(0);	
						
						if( typeof jQuery.ajaxSettings.async == "boolean" ){
							jQuery.ajaxSettings.async = true;
						}
					},
					onSubmit:function(files){
						trigger.parents('.um-modal-body').find('.um-error-block').remove();
						trigger.parents('.um-modal').removeClass('normal').addClass('large');
						
					},
					onSuccess:function(files,data,xhr){
											
						trigger.selectedFiles = 0;
						data = jQuery.parseJSON(data);
						
						if (data.error && data.error != '') {

							trigger.parents('.um-modal-body').append('<div class="um-error-block">'+data.error+'</div>');
							trigger.parents('.um-modal-body').find('.upload-statusbar').hide(0);
							um_modal_responsive();
							
						} 
						else if(data.reload){
							window.location.reload();
						}
						else {

							jQuery.each( data, function(key, value) {
								
								var img_tag = jQuery('<img style="margin:5px" src=\'' + value.src + '\' />');
								var container = trigger.parents('.um-modal-body').find('.um-image-preview');
								var img_id = jQuery(img_tag).appendTo(container);
								
								img_id.load(function(){
								
									trigger.parents('.um-modal-body').find('.um-modal-btn.um-finish-upload.disabled').removeClass('disabled');
									//trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.upload-statusbar,.filetypetip').hide(0);
									trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.filetypetip').hide(0);
									

									var key = trigger.data('key');
									var gallery = jQuery('[data-gallery_key="'+ key +'"]');
									var col = gallery.find('.gallery-field').length + 1; 
									
									if( col == 1 ){
										gallery.html('');
									}
									
									var col_class = col%3==1?'first':(col%3==0?'last':'');
									
									var $data_html = '<div class="gallery-field gallery-image-field ' + col_class + ' media-id-'+ value.id +'"><div class="um-gallery-single-image um-gallery-single"><img src="' + value.src + '">';								
									$data_html += '<div class="preview"><a href="' + value.full +'" data-rel="prettyphoto" class="preview-elem"><i class="um-faicon-photo"></i></a>';									
									$data_html += '<a href="#" class="cancel"><i class="um-faicon-trash"></i></a><div class="um-modal-hidden-content"><div class="um-modal-header">Delete this Photo</div>';
									$data_html += '<div class="um-modal-body"><div class="um-single-preview"><h3>Are you sure you want to delete this photo?</h3></div>';
									$data_html += '<div class="um-modal-footer"><div class="um-modal-right"><a href="#" class="um-modal-btn um-confirm-deletion image" data-error="Error" '; 
									$data_html += 'data-processing="Deleting..." data-complete="Complete" data-key="'+ key +'" data-id="' + value.id + '">Confirm</a>';
									$data_html += '<a href="#" class="um-modal-btn alt" data-action="um_remove_modal"> Cancel</a></div><div class="um-clear"></div></div></div></div></div></div></div>';
									
									gallery.append($data_html);
									
									jQuery('body').trigger('ulimate_member_gallery_updated');
									
									//container.find('.hold').html('<h2>Complete</h2>');
									//container.show(0);
									
									um_modal_responsive();
									
								});
								
								
							});						

						}
						
					}
				});


			
				um_modal_responsive();	
			}
			else{
				// neither upload nor url
			}
			
					
					
		}
		
	});
	

	jQuery(document).on('click', '.um .um-gallery-single a.cancel', function(e){
		e.preventDefault();
		
		var id = 'um_upload_single';
		var modal = jQuery('body').find('.um-modal-overlay');
		var size = 'normal';
		
		jQuery('#' + id).html( jQuery(this).parents('.preview').find('.um-modal-hidden-content').html() );
		
		var modal = jQuery('body').find('.um-modal-overlay');
		
		if ( modal.length == 0 ) {

			jQuery('.tipsy').hide();

			UM_hide_menus();
			
			jQuery('body,html,textarea').css("overflow", "hidden");
			
			jQuery(document).bind("touchmove", function(e){e.preventDefault();});
			jQuery('.um-modal').on('touchmove', function(e){e.stopPropagation();});
			
			jQuery('body').append('<div class="um-modal-overlay" /><div class="um-modal no-photo" />');
						
			jQuery('#' + id).prependTo('.um-modal');
			
			jQuery('#' + id).show();
			jQuery('.um-modal').show();

			um_modal_size( size );
		
		
			um_modal_responsive();			
					
		}
		
		return false;
	});
	
	jQuery(document).on('click', '.um-confirm-deletion', function(e){
		
		var elem = jQuery(this);
		elem.text(elem.data('processing'));
		
		jQuery.ajax({
			url: um_scripts.ajaxurl,
			type: 'post',
			data: {
				action: 'ultimatemembergallery_remove_file',
				id: elem.data('id'),
				key: elem.data('key')
			},
			success:function(e){
				e = jQuery.parseJSON(e);
				if( e.r == 'ok' ){
					jQuery('.media-id-' + e.id).fadeOut().remove();
					var gallery = jQuery('[data-gallery_key="'+ e.key +'"]');
					
					if( gallery.find('.gallery-field').length == 0 ){
						gallery.html('<div class="gallery-field"><div class="um-gallery-single"><p>' + e.empty + '</p></div></div>');
					}
					um_remove_modal();
				}
				else{
					elem.text(elem.data('error') + ': ' + e.r);
				}
			}
		});
	});
	

	jQuery('body').trigger('ulimate_member_gallery_updated');
	
});