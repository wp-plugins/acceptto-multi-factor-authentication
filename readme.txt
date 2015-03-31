=== Plugin Name ===
Contributors: Acceptto
Tags: two factor authentication, multi factor authentication, security
Requires at least: 3.0.1
Tested up to: 4.1.1
Stable tag: 1.3
License: GPLv3
License URI: http://www.gnu.org/copyleft/gpl.html

Easily add Acceptto multi factor authentication to your WordPress website. Enable multi factor authentication for your admins and/or users.

== Description ==

Acceptto provides multi factor authentication as a service to protect against account takeover and data theft. Using the Acceptto plugin you can easily add multi factor authentication to your WordPress website in just a few minutes!

When they log in, your users have multiple ways they can authenticate, including:

One-tap authentication Acceptto's mobile app push notification (our fastest, easiest way to authenticate, works with internet on your phone.)
SMS Passcode generated that we will send to your phone number (works even with no internet access)
Secure automated phone call delivered to any valid phone number, mobile or landline! (works even with no internet access)

== Installation ==

1. Upload `acceptto.zip` to the `/wp-content/plugins/` directory and extract it or just install the plugin from wordpress plugin repository.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Acceptto Web site and register a relying party account: https://mfa.acceptto.com/organisation_users/sign_up
4. Register a new application in your relying party dashboard
4. Go to Acceptto plugin settings and set your uid and secret.
5. Go to Users page on Wordpress and Find and edit your own user's Acceptto email address field to match your account on Acceptto's mobile app.

== Changelog ==

= 1.3 =
* Bug Fixes

= 1.2 =
* Bug fixes

= 1.1 =
* Enabled Two Factor authorization with Phone call and SMS as well as Push Notification.

= 1.0 =
* Initial release of plugin.