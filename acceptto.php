<?php

/**
 * Plugin Name: Acceptto Multi Factor Authentication
 * Plugin URI: https://www.acceptto.com/
 * Description: Simple Multifactor Secure Login for WordPress
 * Version: 1.2
 * Author: Acceptto
 * Author URI: https://www.acceptto.com
 * License: GPL3
 * Text Domain: acceptto
 */

load_plugin_textdomain('acceptto', false, basename( dirname( __FILE__ ) ) . '/languages' );

function acceptto_admin_menu(){
	add_menu_page('Config', __('Acceptto', 'acceptto'), 'activate_plugins', 'acceptto_conf', 'config_review');
    add_action( 'admin_init', 'register_mysettings' );
}
function acceptto_sub_menu(){
	add_submenu_page('acceptto_conf', 'Setting', __('Settings', 'acceptto'), 'activate_plugins', 'acceptto_settings', 'acceptto_option');
}
function register_mysettings() {
	//register our settings
	register_setting( 'acceptto_group', 'acceptto_uid' );
	register_setting( 'acceptto_group', 'acceptto_secret' );
	register_setting( 'acceptto_group', 'acceptto_enable_mfa' );
}

function acceptto_option(){
?>
<div class="wrap">
<h2>
	<?php echo __('Acceptto Endpoint Configuration', 'acceptto') ?>
</h2>
<form method="post" action="options.php">
    <?php settings_fields( 'acceptto_group' ); ?>
    <?php do_settings_sections( 'acceptto_group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row"><?php echo __('API UId', 'acceptto') ?></th>
        <td><input type="text" name="acceptto_uid" value="<?php echo get_option('acceptto_uid'); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row"><?php echo __('API Secret', 'acceptto') ?></th>
        <td><input type="text" name="acceptto_secret" value="<?php echo get_option('acceptto_secret'); ?>" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><?php echo __('Multi Factor Authentication', 'acceptto') ?></th>
        <td>
        	<input type="radio" name="acceptto_enable_mfa" <?php if(get_option('acceptto_enable_mfa') == 1){echo 'checked="checked"';}?> value="1" />
			<?php echo __('Enable', 'acceptto') ?>
        	<br />
        	<br />
        	<input type="radio" name="acceptto_enable_mfa" <?php if(get_option('acceptto_enable_mfa') == 0){echo 'checked="checked"';}?> value="0" />
			<?php echo __('Disable', 'acceptto') ?>
        </td>
        </tr>
		
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php 
}

function config_review(){
	?>
	<div class="wrap">
		<h2>
			<?php echo __('Acceptto Multi Factor Overview.', 'acceptto') ?>
		</h2>
		<p>
		    <br>
		    <br>
		    <h3>
				<?php echo __('What Is Two/Multi Factor Authentication?', 'acceptto') ?>
			</h3>

            <br>
            <br>
			<?php echo __('Two and Multi Factor Authentication in the world of computer authentication systems refer to , where the user is to present two or more independent pieces of information (something beyond the username and password) as means of authentication such as:', 'acceptto') ?>

            <br>
            <br>
            <ul>
             <li>
				 <?php echo __('- Something only the user knows (e.g. password, PIN, pattern).', 'acceptto') ?>
			 </li>
             <li>
				 <?php echo __('- Something only the user has (e.g. smart card, key fob, mobile phone)', 'acceptto') ?>
			 </li>
             <li>
				 <?php echo __('- Something only the user has (e.g. biometric such as fingerprint, face or voice)', 'acceptto') ?>
			 </li>
             <li>
				 <?php echo __('- Some unique contextual data associated with the user (e.g. location, known device token, known network, etc.)', 'acceptto') ?>
			 </li>
            </ul>
		</p>
	</div>
	<?php
}
function get_cUrl($url){
	$ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
          echo curl_error($ch);
          echo "\n<br />";
          $data = '';
        } else {
          curl_close($ch);
        }
        return $data;
}

function acceptto_login_styles() {
		wp_enqueue_script('jquery');
		 ?>
	    <style type="text/css">
	    	form#loginform p.galogin {
				background: none repeat scroll 0 0 #2EA2CC;
			    border-color: #0074A2;
			    box-shadow: 0 1px 0 rgba(120, 200, 230, 0.5) inset, 0 1px 0 rgba(0, 0, 0, 0.15);
			    color: #FFFFFF;
			    text-decoration: none;
	            text-align: center;
	            vertical-align: middle;
	            border-radius: 3px;
			    padding: 4px;
			    height: 27px;
			    font-size: 14px;
			    margin-bottom: <?php echo $options['ga_poweredby'] ? '6' : '16' ?>px;
	        }
	        
	        form#loginform p.galogin a {
	        	color: #FFFFFF;
	        	line-height: 27px;
	        	font-weight: bold;
	        	text-decoration: none;
	        }

        	form#loginform p.galogin a:hover {
	        	color: #CCCCCC;
	        }
	        
	        h3.galogin-or {
	        	text-align: center;
	        	margin-top: 16px;
	        	margin-bottom: 16px;
	        }
	        
	        p.galogin-powered {
			    font-size: 0.7em;
			    font-style: italic;
			    text-align: right;
	        }
	        
	        p.galogin-logout {
	          	background-color: #FFFFFF;
    			border: 4px solid #CCCCCC;
    			box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);
    			padding: 12px;
    			margin: 12px 0px;
	        }
	        
	        
	        div#login form#loginform p label[for=user_login], 
	        div#login form#loginform p label[for=user_pass],
	        div#login form#loginform p label[for=rememberme],
	        div#login form#loginform p.submit,
	        div#login p#nav {
	        	/*display: none;*/
	        } 
	       
	     </style>
	<?php 
}
function unsetAll(){
	ob_start();
	unset($_SESSION['channel']);
	unset($_SESSION['email']);
	unset($_SESSION['status']);
	ob_end_flush();
}
function acceptto_login_form(){
	if(is_user_logged_in()){
		wp_redirect( home_url() ); exit;
	}else{
		unset($_SESSION['channel']);
		unset($_SESSION['email']);
		unset($_SESSION['status']);
	}
}

function acceptto_auth(){
	if(isset($_SESSION['email']) && isset($_SESSION['channel'])){
		$_SESSION['autoLlogin'] = true;
	}else{
		$_SESSION['autoLlogin'] = false;
	}
	if((isset($_POST['login_acceptto']) && $_POST['email'] != '') || (isset($_SESSION['email']) && isset($_SESSION['channel'])) || (isset($_SESSION['autoLlogin'])) ){
		if(($_SESSION['email'] == '' && $_SESSION['channel'] == '') || (isset($_GET['autoLlogin']))){
		if ( is_user_logged_in() ) {
 		$user_s = wp_get_current_user();
 		$eml = get_user_meta($user_s->ID, 'acceptto_email', true);
		}
		if($eml != ''){
			$email = $eml;
		}else{
			$email = $_POST['email'];
		}
		//$url = 'https://mfa.acceptto.com/api/v9/authenticate?message=WordPress+is+wishing+to+authorize&type=Login&email='.$email.'&uid='.get_option('acceptto_uid').'&secret='.get_option('acceptto_secret');
		
		$url = 'https://mfa.acceptto.com/api/v9/authenticate_with_options?message=WordPress+is+wishing+to+authorize&type=Login&email='.$email.'&uid='.get_option('acceptto_uid').'&secret='.get_option('acceptto_secret');
		$data = json_decode(get_cUrl($url));
		$channel = $data->{'channel'};
		if($channel == ''){echo 'Please fill correct email address. url: '.$url;}else{
		$_SESSION['channel'] = $channel;
		$_SESSION['email'] = $email;
		$new_url = 'https://mfa.acceptto.com/mfa/index?channel='.$channel.'&callback_url=http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&autoLlogin';
		wp_redirect($new_url);
		//$data = get_cUrl($new_url);
		echo '<style>.header{display:none;}</style>';
		echo $data;
		}
		}elseif($_SESSION['email'] != '' && ($_SESSION['channel'] != '')){
			if($_GET['channel'] != $_SESSION['channel']){
				$_SESSION['channel'] = $_GET['channel'];
			}
			$url = 'https://mfa.acceptto.com/api/v9/check?channel='.$_SESSION['channel'].'&email='.$_SESSION['email'];
			$data = json_decode(get_cUrl($url));
			$status = $data->{'status'};

			if($status == 'approved'){
				if ( is_user_logged_in() ) {
 					$current_user = wp_get_current_user();
				}
				$user = get_user_by('email', $current_user->user_email);
				$_SESSION['status'] = $status;
				$userdidnotexist = false;
				if ( is_wp_error($user) )
	                return $user;
				if (!$user) {
					$userdidnotexist = true;
				}else{
					wp_set_auth_cookie($user->ID, false, '');
				}
				
			}
			
			if(!$userdidnotexist){
				switch($status){
					case 'approved':
							echo 'Successfully loged in.</script>';
							wp_redirect( home_url() ); exit;
					 	break;
					case 'rejected':
					 		echo '<p>Sorry Your Milti-Factor authorization request declined.</p>';
					 		unsetAll(); wp_logout();
							echo '<script>BackToUrl();</script>';
					 	break;
					case 'expired':
					 		echo '<p>Sorry Your Milti-Factor authorization request is expired.</p>';
					 		unsetAll(); wp_logout();
							echo '<script>BackToUrl();</script>';
					 	break;
					 case '':
					 		echo '<script>ReloadMe();</script>';
					 	break;
					 
				}
			}else{
				
			 	unset($_SESSION['channel']);
				unset($_SESSION['email']);
				unset($_SESSION['status']);
				echo '<p>Sorry email dose not exist.</p>';
				
			}
		}
	}elseif(isset($_POST['login_acceptto']) && $_POST['email'] == ''){

	?>
	<div class="model_pop">
		<form action="" method="post">
			<?php if(get_user_meta($user->ID, 'acceptto_email', true) == '') :?>
			<p><label>Email</label><input type="text" name="email" style="border-color:red"></p>
			<?php else: ?>
			<input type="hidden"  name="email" value="<?php echo get_user_meta($user->ID, 'acceptto_email', true); ?>" style="border-color:red">
			<?php endif;?>
			<p><input type="submit" name="login_acceptto" value="Login"> <input type="submit" name="logout_acceptto" value="Back"></p>
			<p>&nbsp;</p>
		</form>
	</div>
	<?php
	}else if(isset($_POST['logout_acceptto'])){
		wp_logout();
		wp_redirect( home_url() ); exit;
	}else{
		if ( is_user_logged_in() ) {
 					$user = wp_get_current_user();
		}
	?>
	<div class="model_pop">
		<form action="" method="post">
			<?php if(get_user_meta($user->ID, 'acceptto_email', true) == '') :?>
			<p><label>Email</label><input type="text" name="email" style="border-color:red"></p>
			<?php else: ?>
			<input type="hidden"  name="email" value="<?php echo get_user_meta($user->ID, 'acceptto_email', true); ?>" style="border-color:red">
			<?php endif;?>
			<p><input type="submit" name="login_acceptto" value="Login"> <input type="submit" name="logout_acceptto" value="Back"></p>
			<p>&nbsp;</p>
		</form>
	</div>
	<?php
	}
}

function two_validate(){
	if(is_user_logged_in()){
		if($_SESSION['status'] == 'approved'){
			//wp_redirect( home_url() ); exit;
		}else{
			$user = wp_get_current_user();
			if(get_user_meta($user->ID, 'acceptto_email', true) != ''){
				if(get_option('acceptto_enable_mfa')){
					include( plugin_dir_path(__FILE__).'/acceptto-auth.php' );
					die;
				}
			}
		}
	}
}
add_action('init', 'myStartSession', 1);
function myStartSession() {
    if(!session_id()) {
        session_start();
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
add_action( 'edit_user_profile_update', 'acceptto_save_extra_user_profile_fields' );
function acceptto_save_extra_user_profile_fields( $user_id ) {
  $saved = false;
  if ( current_user_can( 'edit_user', $user_id ) ) {
    update_user_meta( $user_id, 'acceptto_email', $_POST['acceptto_email'] );
    $saved = true;
  }
  return true;
}

add_action('init','two_validate',1);
//add_shortcode( 'acceptto_auth', 'acceptto_auth' );
//add_action('login_enqueue_scripts', 'acceptto_login_styles');
add_action('login_form', 'acceptto_login_form');
add_action('admin_menu', 'acceptto_admin_menu');
add_action('admin_menu', 'acceptto_sub_menu');
?>