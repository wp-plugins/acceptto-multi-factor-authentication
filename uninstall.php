<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
        exit ();
    
    if (is_multisite()){
        delete_site_option('acceptto_uid');
        delete_site_option('acceptto_secret');
        delete_site_option('acceptto_enable_mfa');
    }
    else {
        delete_option('acceptto_uid');
        delete_option('acceptto_secret');
        delete_option('acceptto_enable_mfa');
    }

    $meta_type  = 'user';
    $user_id    = 0; // This will be ignored, since we are deleting for all users.
    $meta_key   = 'acceptto_email';
    $meta_value = ''; // Also ignored. The meta will be deleted regardless of value.
    $delete_all = true;

    delete_metadata( $meta_type, $user_id, $meta_key, $meta_value, $delete_all );
?>
