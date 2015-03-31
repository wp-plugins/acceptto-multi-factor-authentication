<?php

/**
 * Plugin Name: Acceptto Multi Factor Authentication
 * Plugin URI: https://www.acceptto.com/
 * Description: Simple Multifactor Secure Login for WordPress
 * Version: 1.3
 * Author: Acceptto
 * Author URI: https://www.acceptto.com
 * License: GPL3
 * Text Domain: acceptto
 */

/*
Copyright 2014 Acceptto <accounting@acceptto.com>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

    $AccepttoDebug = true;

    function get_curl_url($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if ( defined('WP_PROXY_HOST') && defined('WP_PROXY_PORT')) {
            curl_setopt( $ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP );
            curl_setopt( $ch, CURLOPT_PROXY, WP_PROXY_HOST );
            curl_setopt( $ch, CURLOPT_PROXYPORT, WP_PROXY_PORT );
        }

        $response = json_decode(curl_exec($ch));
        curl_close ($ch);
        if (!$response){
            return NULL;
        }
        return $response;
    }

    function acceptto_sign_request($user, $redirect) {
        $ikey = acceptto_get_option('acceptto_ikey');
        $skey = acceptto_get_option('acceptto_skey');
        $host = acceptto_get_option('acceptto_host');
        $user_acceptto_email = get_user_meta($user->ID, 'acceptto_email', true);

        if (is_user_logged_in()) {
            acceptto_debug_log('acceptto_sign_request: User is logged in.');
        }
        else {
            acceptto_debug_log('acceptto_sign_request: User is not logged in!');
        }

        $url = $host.'/api/v9/authenticate_with_options?message=WordPress+is+wishing+to+authorize&type=Login&email='.$user_acceptto_email.'&uid='.$ikey.'&secret='.$skey;
        $data = get_curl_url($url);
        $channel = $data->{'channel'};
        if($channel != '') {
            $_SESSION['channel'] = $channel;
            $_SESSION['user_id'] = $user->ID;
            $new_url = $host.'/mfa/index?channel='.$channel.'&callback_url=http://'.$_SERVER['HTTP_HOST'];
            wp_redirect($new_url);
            exit();
        }
    }

    function acceptto_get_roles(){
        global $wp_roles;
        // $wp_roles may not be initially set if wordpress < 3.3
        $wp_roles = isset($wp_roles) ? $wp_roles : new WP_Roles();
        return $wp_roles;
    }
    
    function acceptto_auth_enabled(){
        if (acceptto_get_option('acceptto_ikey', '') == '' || acceptto_get_option('acceptto_skey', '') == '' ||
            acceptto_get_option('acceptto_host', '') == '') {
            return false;
        }
        return true;
    }

    function acceptto_role_require_mfa($user){
        $wp_roles = acceptto_get_roles();
        $all_roles = array();
        foreach ($wp_roles->get_names() as $k=>$r) {
            $all_roles[$k] = $r;
        }

        $acceptto_roles = acceptto_get_option('acceptto_roles', $all_roles); 

        /*
         * WordPress < 3.3 does not include the roles by default
         * Create a User object to get roles info
         * Don't use get_user_by()
         */
        if (!isset($user->roles)){
            $user = new WP_User(0, $user->user_login);
        }

        /*
         * Mainly a workaround for multisite login:
         * if a user logs in to a site different from the one 
         * they are a member of, login will work however
         * it appears as if the user has no roles during authentication
         * "fail closed" in this case and require acceptto auth
         */
        if(empty($user->roles)) {
            return true;
        }

        foreach ($user->roles as $role) {
            if (array_key_exists($role, $acceptto_roles)) {
                return true;
            }
        }
        return false;
    }

    function acceptto_start_second_factor($user, $redirect_to=NULL){
        if (!$redirect_to){
            // Some custom themes do not provide the redirect_to value
            // Admin page is a good default
            $redirect_to = isset( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : admin_url();
        }

        #wp_logout();
        acceptto_sign_request($user, $redirect_to);
        exit();
    }
    
    function acceptto_authenticate_user($user="", $username="", $password="") {
        // play nicely with other plugins if they have higher priority than us
        if (is_a($user, 'WP_User')) {
            return $user;
        }

        if (! acceptto_auth_enabled()){
            acceptto_debug_log('Acceptto not enabled, skipping 2FA.');
            return;
        }

        if (strlen($username) > 0) {
            // primary auth
            // Don't use get_user_by(). It doesn't return a WP_User object if wordpress version < 3.3
            $user = new WP_User(0, $username);
            if (!$user) {
                error_log("Failed to retrieve WP user $username");
                return;
            }

            if(!acceptto_role_require_mfa($user)){
                acceptto_debug_log("Skipping 2FA for user: $username with roles: " . print_r($user->roles, true));
                return;
            }

            if(get_user_meta($user->ID, 'acceptto_email', true) == ''){
                acceptto_debug_log("Primary auth succeeded, user doesn't have an acceptto_email address, skipping two factor auth.");
                return;
            }

            remove_action('authenticate', 'wp_authenticate_username_password', 20);
            $user = wp_authenticate_username_password(NULL, $username, $password);
            if (!is_a($user, 'WP_User')) {
                // on error, return said error (and skip the remaining plugin chain)
                return $user;
            }
            else {
                acceptto_debug_log("Primary auth succeeded, starting second factor for $username");
                acceptto_start_second_factor($user);
            }
        }
        acceptto_debug_log('Starting primary authentication');
    }

    function acceptto_settings_page() {
        acceptto_debug_log('Displaying acceptto setting page');
?>
    <div class="wrap">
        <h2>Acceptto Multi Factor Authentication</h2>
        <?php if(is_multisite()) { ?>
            <form action="ms-options.php" method="post">
        <?php } else { ?>
            <form action="options.php" method="post"> 
        <?php } ?>
            <?php settings_fields('acceptto_settings'); ?>
            <?php do_settings_sections('acceptto_settings'); ?> 
            <p class="submit">
                <input name="Submit" type="submit" class="button primary-button" value="<?php esc_attr_e('Save Changes'); ?>" />
            </p>
        </form>
    </div>
<?php
    }

    function acceptto_settings_ikey() {
        $ikey = esc_attr(acceptto_get_option('acceptto_ikey'));
        echo "<input id='acceptto_ikey' name='acceptto_ikey' size='40' type='text' value='$ikey' />";
    }

    function acceptto_settings_skey() {
        $skey = esc_attr(acceptto_get_option('acceptto_skey'));
        echo "<input id='acceptto_skey' name='acceptto_skey' size='40' type='password' value='$skey' autocomplete='off' />";
    }

    function acceptto_settings_host() {
        $host = esc_attr(acceptto_get_option('acceptto_host'));
        echo "<input id='acceptto_host' name='acceptto_host' size='40' type='text' value='$host' />";
    }

    function acceptto_settings_roles() {
        $wp_roles = acceptto_get_roles();
        $roles = $wp_roles->get_names();
        $newroles = array();
        foreach($roles as $key=>$role) {
            $newroles[before_last_bar($key)] = before_last_bar($role);
        }

        $selected = acceptto_get_option('acceptto_roles', $newroles);

        foreach ($wp_roles->get_names() as $key=>$role) {
            //create checkbox for each role
?>
            <input id="acceptto_roles" name='acceptto_roles[<?php echo $key; ?>]' type='checkbox' value='<?php echo $role; ?>'  <?php if(in_array($role, $selected)) echo 'checked'; ?> /> <?php echo $role; ?> <br />
<?php
        }
    }

    function acceptto_roles_validate($options) {
        //return empty array
        if (!is_array($options) || empty($options) || (false === $options)) {
            return array();
        }

        $wp_roles = acceptto_get_roles();

        $valid_roles = $wp_roles->get_names();
        //otherwise validate each role and then return the array
        foreach ($options as $opt) {
            if (!in_array($opt, $valid_roles)) {
                unset($options[$opt]);
            }
        }
        return $options;
    }

    function acceptto_settings_text() {
        echo "<p>Acceptto Endpoint Configuration (You can register <a href='https://www.acceptto.com/organisation_users/sign_up' target='_blank'>here</a> and define a new application in your dashboard.)</p>";
        echo "<p>You can retrieve your application UID, SECRET, and API hostname by logging in to the <a href='https://www.acceptto.com/organisation_users/sign_in'>Acceptto dashboard.</a></p>";
    }

    function acceptto_ikey_validate($ikey) {
        return $ikey;
    }
    
    function acceptto_skey_validate($skey){
        return $skey;
    }

    function acceptto_host_validate($host) {
        acceptto_debug_log("validate acceptto host ...");
        $ikey = acceptto_get_option('acceptto_ikey');
        $skey = acceptto_get_option('acceptto_skey');
        $url = $host.'/api/v9/is_application_valid?&uid='.$ikey.'&secret='.$skey;
        $data = get_curl_url($url);
        $valid = $data->{'valid'};
        if ($valid == 0) {
            add_settings_error('acceptto_host', $host, "<strong>ERROR</strong>: Invalid Acceptto UID, Secret, Host specified, please sign into your <a href='https://acceptto.com/organisations/application'>Acceptto Dashboard</a> and copy these values from there.");
            return "";
        }

        return $host;
    }

    function acceptto_add_site_option($option, $value = '') {
        // Add multisite option only if it doesn't exist already
        // With Wordpress versions < 3.3, calling add_site_option will override old values
        if (acceptto_get_option($option) === FALSE){
            add_site_option($option, $value);
        }
    }

    function acceptto_admin_init() {
        if (is_multisite()) {
            $wp_roles = acceptto_get_roles();
            $roles = $wp_roles->get_names();
            $allroles = array();
            foreach($roles as $key=>$role) {
                $allroles[before_last_bar($key)] = before_last_bar($role);
            }
            
            acceptto_add_site_option('acceptto_ikey', '');
            acceptto_add_site_option('acceptto_skey', '');
            acceptto_add_site_option('acceptto_host', '');
            acceptto_add_site_option('acceptto_roles', $allroles);
        }
        else {
            add_settings_section('acceptto_settings', 'Main Settings', 'acceptto_settings_text', 'acceptto_settings');
            add_settings_field('acceptto_ikey', 'Acceptto UID', 'acceptto_settings_ikey', 'acceptto_settings', 'acceptto_settings');
            add_settings_field('acceptto_skey', 'Acceptto Secret', 'acceptto_settings_skey', 'acceptto_settings', 'acceptto_settings');
            add_settings_field('acceptto_host', 'Acceptto API hostname', 'acceptto_settings_host', 'acceptto_settings', 'acceptto_settings');
            add_settings_field('acceptto_roles', 'Enable for roles:', 'acceptto_settings_roles', 'acceptto_settings', 'acceptto_settings');
            register_setting('acceptto_settings', 'acceptto_ikey', 'acceptto_ikey_validate');
            register_setting('acceptto_settings', 'acceptto_skey', 'acceptto_skey_validate');
            register_setting('acceptto_settings', 'acceptto_host', 'acceptto_host_validate');
            register_setting('acceptto_settings', 'acceptto_roles', 'acceptto_roles_validate');
        }
    }

    function acceptto_mu_options() {

?>
        <h3>Acceptto Security</h3>
        <table class="form-table">
            <?php acceptto_settings_text();?></td></tr>
            <tr><th>Integration key</th><td><?php acceptto_settings_ikey();?></td></tr>
            <tr><th>Secret key</th><td><?php acceptto_settings_skey();?></td></tr>
            <tr><th>API hostname</th><td><?php acceptto_settings_host();?></td></tr>
            <tr><th>Roles</th><td><?php acceptto_settings_roles();?></td></tr>
        </table>
<?php
    }

    function acceptto_add_page() {
        if(! is_multisite()) {
            add_options_page('Acceptto Two-Factor', 'Acceptto Multi-Factor', 'manage_options', 'acceptto_wordpress', 'acceptto_settings_page');
        }
    }

    function acceptto_add_link($links, $file) {
        static $this_plugin;
        if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);

        if ($file == $this_plugin) {
            $settings_link = '<a href="options-general.php?page=acceptto_wordpress">'.__("Settings", "acceptto_wordpress").'</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

    /*
    * Returns current plugin version.
    *
    * @return string Plugin version
    */
    function acceptto_get_plugin_version() {
        if (!function_exists('get_plugin_data'))
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');

        $plugin_data = get_plugin_data( __FILE__ );
        return $plugin_data['Version'];
    }

    function acceptto_get_user_agent() {
        global $wp_version;
        $acceptto_wordpress_version = acceptto_get_plugin_version();
        return $_SERVER['SERVER_SOFTWARE'] . " WordPress/$wp_version acceptto_wordpress/$acceptto_wordpress_version";
    }

    function acceptto_verify_auth()
    {
        /*
            Verify the user is authenticated with Acceptto. Start 2FA otherwise
        */

        if(!session_id()) {
            session_start();
        }

        if (!acceptto_auth_enabled()) {
            if (is_multisite()) {
                $site_info = get_current_site();
                acceptto_debug_log("acceptto_verify_auth:: Acceptto not enabled on " . $site_info->site_name . ', skip cookie check.');
            } else {
                acceptto_debug_log('acceptto_verify_auth:: Acceptto not enabled, skip cookie check.');
            }
            return;
        }

        if ( isset($_SESSION['channel']) && isset($_SESSION['user_id']) ) {
            acceptto_debug_log('acceptto_verify_auth:: session channel and user_id is set, signing in user ...');
            $channel = $_SESSION['channel'];
            $user_id = $_SESSION['user_id'];
            $user = get_user_by( 'id', $user_id );
            $user_acceptto_email = get_user_meta($user->ID, 'acceptto_email', true);
            unset($_SESSION['channel']);
            unset($_SESSION['user_id']);

            if ($user_acceptto_email == '') {
                acceptto_debug_log('acceptto_verify_auth:: user acceptto email is empty ...');
            }
            else
            {
                acceptto_debug_log('acceptto_verify_auth:: calling api v9 check ...');
                $host = acceptto_get_option('acceptto_host');
                $url = $host.'/api/v9/check?channel='.$channel.'&email='.$user_acceptto_email;
                $data = get_curl_url($url);
                $status = $data->{'status'};
                acceptto_debug_log('acceptto_verify_auth:: authorization status is: '.$status);
                if($status == 'approved') {
                    acceptto_debug_log('acceptto_verify_auth:: set auth cookie for user: '.$user->ID);
                    wp_set_auth_cookie( $user->ID, false, '' );
                    wp_redirect( home_url() );
                    exit();
                }
            }
        }
    }

    function endSessions() {
        session_destroy();
    }

    function acceptto_debug_log($message) {
        global $AccepttoDebug;
        if ($AccepttoDebug) {
            error_log('Acceptto debug: ' . $message);
        }
    }

    /*-------------Register WordPress Hooks-------------*/

    if (!is_multisite()) {
        add_filter('plugin_action_links', 'acceptto_add_link', 10, 2 );
    }

    add_action('init', 'acceptto_verify_auth', 10);

    add_action('wp_logout', 'endSessions');
    add_action('wp_login', 'endSessions');

    add_filter('authenticate', 'acceptto_authenticate_user', 10, 3);
    
    //add single-site submenu option
    add_action('admin_menu', 'acceptto_add_page');

    // Custom fields in network settings
    add_action('wpmu_options', 'acceptto_mu_options');
    add_action('update_wpmu_options', 'acceptto_update_mu_options');

    add_action('admin_init', 'acceptto_admin_init');

    function acceptto_get_option($key, $default="") {
        if (is_multisite()) {
            return get_site_option($key, $default);
        }
        else {
            return get_option($key, $default);
        }
    }

    add_action( 'show_user_profile', 'acceptto_extra_user_profile_fields' );
    add_action( 'edit_user_profile', 'acceptto_extra_user_profile_fields' );
    function acceptto_extra_user_profile_fields( $user ) {
        ?>
            <h3><?php _e(__("User's Acceptto Email Address For Multi Factor", "acceptto"), "blank"); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="acceptto_email"><?php _e(__("Acceptto Email", "acceptto")); ?></label></th>
                    <td>
                        <input type="text" name="acceptto_email" id="acceptto_email" class="regular-text"
                               value="<?php echo esc_attr( get_the_author_meta( 'acceptto_email', $user->ID ) ); ?>" /><br />
                        <span class="description"><?php _e(__("Please enter your acceptto's account email address.", "acceptto")); ?></span>
                    </td>
                </tr>
            </table>
        <?php
    }

    add_action( 'personal_options_update', 'acceptto_save_extra_user_profile_fields' );

    add_action( 'user_profile_update_errors', 'validate_extra' );
    function validate_extra(&$errors, $update = null, &$user  = null)
    {
        if ($_POST['acceptto_email'])
        {
            $ikey = acceptto_get_option('acceptto_ikey');
            $skey = acceptto_get_option('acceptto_skey');
            $host = acceptto_get_option('acceptto_host');
            $url = $host.'/api/v9/is_user_valid?email='.$_POST['acceptto_email'].'&uid='.$ikey.'&secret='.$skey;
            $data = get_curl_url($url);
            $valid = $data->{'valid'};
            if ($valid == 0) {
                $errors->add('wrong_acceptto_email', "<strong>ERROR</strong>: Invalid Acceptto Email Address! Please use the email address you are signed in with on your Acceptto mobile application.");
            }
        }
    }

    add_action( 'edit_user_profile_update', 'acceptto_save_extra_user_profile_fields' );
    function acceptto_save_extra_user_profile_fields( $user_id ) {
        if ( current_user_can( 'edit_user', $user_id ) ) {
            update_user_meta($user_id, 'acceptto_email', $_POST['acceptto_email']);
        }

        return true;
    }

?>
