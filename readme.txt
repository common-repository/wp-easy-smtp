=== WP Easy SMTP ===
Contributors: iprodev
Donate link: https://iprodev.com/wordpress-easy-smtp-send-emails-from-your-wordpress-site-using-a-smtp-server
Tags: mail, wordpress smtp, phpmailer, smtp, wp_mail, email, gmail, yahoo, hotmail, sendgrid, sparkpost, postmark, mandrill, pepipost, outgoing mail, privacy, security, sendmail, ssl, tls, wp-phpmailer, mail smtp, wp smtp
Requires at least: 4.3
Tested up to: 5.2.0
Stable tag: 1.1.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html

Easily send emails from your WordPress blog using your preferred SMTP server.
Supports Gmail, Yahoo, Hotmail, SendGrid, SparkPost, Postmark, Mandrill, Pepipost's SMTP server also.

== Description ==

WP Easy SMTP allows you to configure and send all outgoing emails via a SMTP server. This will prevent your emails from going into the junk/spam folder of the recipients.

You can also send emails to your WordPress blog Users & Commenters using your SMTP server.

= WP Easy SMTP Features =

* Send email using a SMTP sever.
* You can use Gmail, Yahoo, Hotmail, SendGrid, SparkPost, Postmark, Mandrill, Pepipost's SMTP server if you have an account with them.
* Send emails to your WordPress blog Users & Commenters.
* Seamlessly connect your WordPress blog with a mail server to handle all outgoing emails (it's as if the email has been composed inside your mail account).
* Securely deliver emails to your recipients.

= Localization =
* Persian (fa_IR) - [Hemn Chawroka](https://iprodev.com/author/admin/) (plugin author)
* Kurdish (ckb) - [Nasr Chawroka](https://iprodev.com/author/kurddata2006/) (plugin author)
* French (fr_FR) - [Hemn Chawroka](https://iprodev.com/author/admin/) (plugin author)
* Portuguese (pt_PT) - [Hemn Chawroka](https://iprodev.com/author/admin/) (plugin author)
* Spanish (es_ES) - [Hemn Chawroka](https://iprodev.com/author/admin/) (plugin author)

= WP Easy SMTP Plugin Usage =

Once you have installed the plugin there are some options that you need to configure in the plugin setttings (go to `Settings->WP Easy SMTP` from your WordPress Dashboard).

**a)** WP Easy SMTP General Settings

The general settings section consists of the following options

* From Email Address: The email address that will be used to send emails to your recipients
* From Name: The name your recipients will see as part of the "from" or "sender" value when they receive your message
* Mailer: Integrated support for Gmail, Yahoo, Hotmail, SendGrid, SparkPost, Postmark, Mandrill, Pepipost
* SMTP Host: Your outgoing mail server (example: smtp.gmail.com)
* Type of Encryption: none/SSL/TLS
* SMTP Port: The port that will be used to relay outbound mail to your mail server (example: 465)
* SMTP Authentication: No/Yes (This option should always be checked "Yes")
* Username: The username that you use to login to your mail server
* Password: The password that you use to login to your mail server

For detailed documentation on how you can configure these options please visit the [WordPress Easy SMTP](https://iprodev.com/wordpress-easy-smtp-send-emails-from-your-wordpress-site-using-a-smtp-server) plugin page

**b)** WP Easy SMTP Testing & Debugging Settings

This section allows you to perform some email testing to make sure that your WordPress site is ready to relay all outgoing emails to your configured SMTP server. It consists of the following options:

* To: The email address that will be used to send emails to your recipients
* Subject: The subject of your message
* Message: A textarea to write your test message.

Once you click the "Send Test Email" button the plugin will try to send an email to the recipient specified in the "To" field.

== Installation ==

1. Go to the Add New plugins screen in your WordPress admin area
1. Click the upload tab
1. Browse for the plugin file (wp-easy-smtp.zip)
1. Click Install Now and then activate the plugin
1. Now, go to the settings menu of the plugin and follow the instructions

== Frequently Asked Questions ==

= Can this plugin be used to send emails via SMTP? =

Yes.

= Can this plugin be used to send emails to my Users? =

Yes.

= Can this plugin be used to send emails to my Commenters? =

Yes.

= My plugin still sends mail via the mail() function =

If other plugins you're using are not coded to use the wp_mail() function but instead call PHP's mail() function directly, they will bypass the settings of this plugin. Normally, you can edit the other plugins and simply replace the `mail(` calls with `wp_mail(` (just adding wp_ in front) and this will work. I've tested this on a couple of plugins and it works, but it may not work on all plugins.

= Can I use this plugin to send email via Gmail / Google Apps =

Yes.

== Screenshots ==

1. WP Easy SMTP

For screenshots please visit the [WordPress Easy SMTP](https://iprodev.com/wordpress-easy-smtp-send-emails-from-your-wordpress-site-using-a-smtp-server) plugin page


== Changelog ==

= 1.1.2 =
* Disabled browser autocomplete for username and password fields to prevent them from being replaced by WP login credentials (if those were saved in browser).
* Fixed some bugs.

= 1.1.1 =
* Fixed some bugs.

= 1.1.0 =
* Supported WordPress version up to 4.9.
* Added new settings option to specify a reply-to email address.
* Test email message body is no longer having excess slashes inserted.

= 1.0.4 =
* Fixed possible XSS vulnerability with the email subject and email body input fields.
* Fixed added slashes to the email subject and email body input fields.

= 1.0.3 =
* Added Sending email to users and commenters.
* Fixed some bugs.

= 1.0.2 =
* Fixed some bugs.

= 1.0.1 =
* Added French language.
* Added Portuguese language.
* Added Spanish language.

= 1.0.0 =
* First commit of the plugin.
