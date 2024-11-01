<?php

if( !function_exists('is_ultimate_member_plugin_active') ){
	
	function is_ultimate_member_plugin_active(){
		
		$active_plugins = (array) get_option( 'active_plugins', array() );
		
		if ( is_multisite() ){
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		
		return in_array( 'ultimate-member/index.php', $active_plugins ) || array_key_exists( 'ultimate-member/index.php', $active_plugins );
		
	}
}

if( !function_exists('ultimate_member_plugin_inactive_notice') ){
	function ultimate_member_plugin_inactive_notice(){

		if ( current_user_can( 'activate_plugins' ) ) : ?>
		
		<div id="message" class="error">
			<p><?php printf( __( '%sUltimate Member Gallery is inactive.%s The %sUltimate Member plugin%s must be active for Ultimate Member Gallery to work. Please %sinstall & activate Ultimate Member%s', 'ultimate-member-gallery' ), '<strong>', '</strong>', '<a href="https://wordpress.org/plugins/ultimate-member/">', '</a>', '<a href="' . admin_url( 'plugins.php' ) . '">', '&nbsp;&raquo;</a>' ); ?></p>
		</div>
		
		<?php endif;
			
	}
}

function is_ultimate_member_on_gallery(){
	return um_user('ID') == get_current_user_id();
}

function is_ultimate_member_allowed_to_add($type){
    if( !is_ultimate_member_on_gallery() ){
        return false;
    }
    
    global $ultimatemembergallery;
    
    switch( $type ){
        
        case 'photo':
            $max = um_get_option('gallery_photo_max');

            if( !$max ){
                return true;
            }
            
            $count = count( $ultimatemembergallery->get_gallery_photo_id() );
            
            if( $max > $count ){
                return true;
            }            
            break;
            
        case 'video':
            $max = um_get_option('gallery_video_max');
            
            if( !$max ){
                return true;
            }
            
            $count = count( $ultimatemembergallery->get_gallery_video_id() );
            
            if( $max > $count ){
                return true;
            }
            break;
    }
    return false;
    
}

function wp_max_upload_size_to_hr(){
	$bytes = wp_max_upload_size();
	
	$kb = $bytes / 1024;
	
	if( $kb < 1024 ){
		return $kb . ' KB';
	}
	
	$mb = $kb / 1024;
	
	if( $mb < 1024 ){
		return $mb . ' MB';
	}
	
	$gb = $mb / 1024;
	
	return $gb . ' GB';
}

function ultimate_member_gallery_install(){
    
    $version = get_option('ultimate_member_gallery_db_version', 0);
    
    if( version_compare( $version, UM_GALLERY_DB_VERSION, '<' ) ){
        
        global $wpdb;
        
        $wpdb->hide_errors();
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $collate = '';
        
        if ( $wpdb->has_cap( 'collation' ) ) {
            if ( ! empty( $wpdb->charset ) ) {
                $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if ( ! empty( $wpdb->collate ) ) {
                $collate .= " COLLATE $wpdb->collate";
            }
        }
        
        $sql = "
			    CREATE TABLE {$wpdb->prefix}ultimate_member_gallery_user_contents (
                  id bigint(20) NOT NULL auto_increment,
                  user_id bigint(20) NOT NULL,
                  type varchar(10) NOT NULL,
                  label varchar(200) NULL,
                  value varchar(200) NULL,
                  content longtext NOT NULL,
                  guid varchar(200) NOT NULL,
                  orderby varchar(200) NOT NULL,
                  privacy int(2) NOT NULL DEFAULT 1,
                  internal int(1) NOT NULL DEFAULT 1,
                  meta_id bigint(20) NOT NULL,
                  PRIMARY KEY  (id)
                ) $collate;
			  ";
        
        dbDelta( $sql );
        
        update_option('ultimate_member_gallery_db_version', UM_GALLERY_DB_VERSION);
    }
}