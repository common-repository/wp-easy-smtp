<?php
/*
Plugin Name: WordPress Easy SMTP
Version: 1.1.2
Plugin URI: https://iprodev.com/wordpress-easy-smtp-send-emails-from-your-wordpress-site-using-a-smtp-server
Author: iProDev
Author URI: https://iprodev.com/
Description: Reconfigures the wp_mail() function to use SMTP instead of mail() and creates an options page to manage the settings.
Text Domain: wp-easy-smtp
Domain Path: /languages
*/

defined('ABSPATH') or die('You\'re not supposed to be here.');

/**
 * 
 * 
 * @author iProDev
 */
if (!class_exists('WP_Easy_SMTP')):
class WP_Easy_SMTP {
	public $VERSION = '1.1.2';
	public $MAIN;
	public $PATH;
	public $BASE;
	public $OPTIONS;
	public $SLUG = "wpesmtp";
	private $DEFAULT_OPTIONS = array( 'from_email_field' => '', 'from_name_field' => '', 'reply_to_email' => '', 'mailer' => 'smtp', 'smtp_settings' => array( 'host' => '', 'type_encryption' => 'ssl', 'port' => 465, 'autentication' => 'yes', 'username' => '', 'password' => '' ) );

	/**
	 * The WordPress Easy SMTP constructor function
	 *
	 * @param   string   $file  The plugin file path
	 * @return  object          Returns all WordPress Easy SMTP public methods and properties.
	 */
	function __construct( $file ) {
		$this->MAIN = $file;
		$this->BASE = plugin_basename( $file );
		$this->PATH = str_replace( DIRECTORY_SEPARATOR, '/', dirname( $file ) );
		$this->OPTIONS = get_option( "{$this->SLUG}_options" );

		if ( !$this->OPTIONS ) {
			$this->OPTIONS = $this->DEFAULT_OPTIONS;
		}

		/**
		 * Add all hooks
		 */
		register_activation_hook( $file, array(
			 $this,
			'activate' 
		) );
		register_deactivation_hook( $file, array(
			 $this,
			'uninstall' 
		) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array(
				 $this,
				'admin_menu' 
			) );
			add_action( 'wp_ajax_' . $this->SLUG, array(
				 $this,
				'ajax_actions' 
			) );

			add_action( 'admin_enqueue_scripts', array(
				 $this,
				'admin_head' 
			) );
			add_action( 'admin_notices', array(
				 $this,
				'admin_notice' 
			) );

			add_filter( 'plugin_action_links', array(
				 $this,
				'action_links' 
			), 10, 2 );
			add_filter( 'plugin_row_meta', array(
				 $this,
				'register_plugin_links' 
			), 10, 2 );
		}

		require_once 'includes/cron.class.php';

		// Add cron if its not there
		new iProDevNotify( $file );
	}

	/**
	 * Activating handler.
	 * @return void
	 */
	public function activate() {
		/* install the default options */
		if ( !get_option( "{$this->SLUG}_options" ) ) {
			add_option( "{$this->SLUG}_options", $this->DEFAULT_OPTIONS, '', 'yes' );
		}
	}

	/**
	 * Uninstalling handler.
	 * @return void
	 */
	public function uninstall() {
		/* delete plugin options */
		delete_site_option( "{$this->SLUG}_options" );
		delete_site_option( "{$this->SLUG}_smtp_test_mail" );
		delete_option( "{$this->SLUG}_options" );
		delete_option( "{$this->SLUG}_smtp_test_mail" );

		//Clear iProDevNotify
		iProDevNotify::clear_schedule_cron( __FILE__ );
	}

	/**
	 * Add menu and submenu.
	 * @return void
	 */
	public function admin_menu() {
		add_options_page( __( 'WP Easy SMTP', 'wp-easy-smtp' ), __( 'WP Easy SMTP', 'wp-easy-smtp' ), 'manage_options', "{$this->SLUG}_settings", array(
			 $this,
			'page_init' 
		) );
	}

	/**
	 * Add action links on plugin page in to Plugin Name block
	 * @param  $links array() action links
	 * @param  $file  string  relative path to pugin "wp-easy-smtp/wp-easy-smtp.php"
	 * @return $links array() action links
	 */
	public function action_links( $links, $file ) {
		if ( $file == $this->BASE ) {
			$settings_link = '<a href="options-general.php?page=wpesmtp_settings">' . __( 'Settings', 'wp-easy-smtp' ) . '</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}
	
	/**
	 * Add action links on plugin page in to Plugin Description block
	 * @param  $links array() action links
	 * @param  $file  string  relative path to pugin "wp-easy-smtp/wp-easy-smtp.php"
	 * @return $links array() action links
	 */
	public function register_plugin_links( $links, $file ) {
		if ( $file == $this->BASE ) {
			$links[] = '<a href="options-general.php?page=wpesmtp_settings">' . __( 'Settings', 'wp-easy-smtp' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Page contents initialize.
	 *
	 * @return  void
	 */
	public function page_init() {
		echo '<div class="wrap" id="wpesmtp-mail">';
		echo '<h2>' . __( "WP Easy SMTP Settings", 'wp-easy-smtp' ) . '</h2>';
		echo '<div><div id="post-body">';

		$display_add_options = $message = $error = $result = '';

		$options           = $this->OPTIONS;
		$smtp_test_default = array(
			'wpesmtp_to' => '',
			'wpesmtp_send_to' => 'custom',
			'wpesmtp_subject' => '',
			'wpesmtp_message' => '' 
		);

		if ( $smtp_test_mail = get_option( "{$this->SLUG}_smtp_test_mail" ) ) {
			$smtp_test_mail = array_merge( $smtp_test_default, $smtp_test_mail );
		}
		else {
			$smtp_test_mail = $smtp_test_default;
		}
?>
		<div class="wpesmtp-green-box">
			<?php printf( __( 'Please visit the <a target="_blank" href="%s">WP Easy SMTP</a> plugin\'s documentation page for usage instructions.', 'wp-easy-smtp' ), esc_url( "https://iprodev.com/wordpress-easy-smtp-send-emails-from-your-wordpress-site-using-a-smtp-server" ) ); ?>
		</div>

		<div id="wpesmtp-settings-notice" class="wpesmtp-yellow-box" style="display:none">
			<strong><?php _e( "Notice:", 'wp-easy-smtp' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.", 'wp-easy-smtp' ); ?>
		</div>

		<div class="wpesmtp-box">
			<div class="box-title"><h3><?php _e( 'SMTP Configuration Settings', 'wp-easy-smtp' ); ?></h3></div>
			<div class="inside">

				<p><?php _e( "You can request your hosting provider for the SMTP details of your site. Use the SMTP details provided by your hosting provider to configure the following settings.", 'wp-easy-smtp' ); ?></p>
				
				<form autocomplete="off" id="wpesmtp_settings_form" method="post" action="">
					<input type="hidden" name="wpesmtp_task" value="settings">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( "From Email", 'wp-easy-smtp' ); ?></th>
							<td>
								<input type="text" name="wpesmtp_from_email" value="<?php echo esc_attr( $options['from_email_field'] ); ?>"/><br />
								<p class="description"><?php _e( "You can specify the email address that emails should be sent from. If you leave this blank, the default email will be used.", 'wp-easy-smtp' ); ?></p>
							</td>

							<th scope="row"><?php _e( "From Name", 'wp-easy-smtp' ); ?></th>
							<td>
								<input type="text" name="wpesmtp_from_name" value="<?php echo esc_attr( $options['from_name_field'] ); ?>"/><br />
								<p class="description"><?php _e( "You can specify the name that emails should be sent from. If you leave this blank, the emails will be sent from WordPress.", 'wp-easy-smtp' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Reply-To Email Address', 'wp-easy-smtp' ); ?></th>
							<td colspan="3">
								<input type="text" name="wpesmtp_reply_to_email" value="<?php echo esc_attr( $options['reply_to_email'] ); ?>"/><br />
								<p class="description"><?php _e( "This email address will be used in the 'Reply-To' field of the email. Leave it blank to use 'From Email' as the reply-to value.", 'wp-easy-smtp' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Mailer', 'wp-easy-smtp' ); ?></th>
							<td colspan="3">
								<div class="switch-field">
									<input type="radio" id="wpesmtp_mailer_smtp" name="wpesmtp_mailer" value="smtp"<?php if ( 'smtp' == $options['mailer'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_mailer_smtp"><?php _e( 'SMTP', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_mailer_gmail" name="wpesmtp_mailer" value="gmail"<?php if ( 'gmail' == $options['mailer'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_mailer_gmail"><?php _e( 'Gmail', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_mailer_yahoo" name="wpesmtp_mailer" value="yahoo"<?php if ( 'yahoo' == $options['mailer'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_mailer_yahoo"><?php _e( 'Yahoo', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_mailer_hotmail" name="wpesmtp_mailer" value="hotmail"<?php if ( 'hotmail' == $options['mailer'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_mailer_hotmail"><?php _e( 'Hotmail', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_mailer_sendgrid" name="wpesmtp_mailer" value="sendgrid"<?php if ( 'sendgrid' == $options['mailer'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_mailer_sendgrid"><?php _e( 'SendGrid', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_mailer_sparkpost" name="wpesmtp_mailer" value="sparkpost"<?php if ( 'sparkpost' == $options['mailer'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_mailer_sparkpost"><?php _e( 'SparkPost', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_mailer_postmark" name="wpesmtp_mailer" value="postmark"<?php if ( 'postmark' == $options['mailer'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_mailer_postmark"><?php _e( 'Postmark', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_mailer_mandrill" name="wpesmtp_mailer" value="mandrill"<?php if ( 'mandrill' == $options['mailer'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_mailer_mandrill"><?php _e( 'Mandrill', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_mailer_pepipost" name="wpesmtp_mailer" value="pepipost"<?php if ( 'pepipost' == $options['mailer'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_mailer_pepipost"><?php _e( 'Pepipost', 'wp-easy-smtp' ); ?></label>
								</div>
								<p class="description"><?php _e( "Your mail delivery service", 'wp-easy-smtp' ); ?></p>
							</td>
						</tr>
					</table>
					<table class="form-table">
						<tr class="ad_opt wpesmtp_smtp_options field" rel="host">
							<th><?php _e( 'SMTP Host', 'wp-easy-smtp' ); ?></th>
							<td colspan="3">
								<input type='text' name='wpesmtp_smtp_host' value='<?php echo esc_attr( $options['smtp_settings']['host'] ); ?>' /><br />
								<p class="description"><?php _e( "Your mail server", 'wp-easy-smtp' ); ?></p>
							</td>
						</tr>
						<tr class="ad_opt wpesmtp_smtp_options field" rel="port">
							<th><?php _e( 'SMTP Port', 'wp-easy-smtp' ); ?></th>
							<td colspan="3">
								<input type='number' name='wpesmtp_smtp_port' value='<?php echo esc_attr( $options['smtp_settings']['port'] ); ?>' /><br />
								<p class="description"><?php _e( "The port to your mail server", 'wp-easy-smtp' ); ?></p>
							</td>
						</tr>
						<tr class="ad_opt wpesmtp_smtp_options field" rel="encryption">
							<th><?php _e( 'Encryption', 'wp-easy-smtp' ); ?></th>
							<td colspan="3">
								<div class="switch-field">
									<input type="radio" id="wpesmtp_smtp_type_encryption_1" name="wpesmtp_smtp_type_encryption" value='none'<?php if ( 'none' == $options['smtp_settings']['type_encryption'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_smtp_type_encryption_1"><?php _e( 'None', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_smtp_type_encryption_2" name="wpesmtp_smtp_type_encryption" value='ssl'<?php if ( 'ssl' == $options['smtp_settings']['type_encryption'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_smtp_type_encryption_2"><?php _e( 'SSL', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_smtp_type_encryption_3" name="wpesmtp_smtp_type_encryption" value='tls'<?php if ( 'tls' == $options['smtp_settings']['type_encryption'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_smtp_type_encryption_3"><?php _e( 'TLS', 'wp-easy-smtp' ); ?></label>
								</div>
								<p class="description"><?php _e( "TLS is not the same as STARTTLS. For most servers SSL is the recommended option", 'wp-easy-smtp' ); ?></p>
							</td>
						</tr>
						<tr class="ad_opt wpesmtp_smtp_options field" rel="auth">
							<th><?php _e( 'Authentication', 'wp-easy-smtp' ); ?></th>
							<td colspan="3">
								<div class="switch-field">
									<input type="radio" id="wpesmtp_smtp_autentication_no" name="wpesmtp_smtp_autentication" value='no'<?php if ( 'no' == $options['smtp_settings']['autentication'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_smtp_autentication_no"><?php _e( 'No', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_smtp_autentication_yes" name="wpesmtp_smtp_autentication" value='yes'<?php if ( 'yes' == $options['smtp_settings']['autentication'] ) echo ' checked="checked"'; ?> />
									<label for="wpesmtp_smtp_autentication_yes"><?php _e( 'Yes', 'wp-easy-smtp' ); ?></label>
								</div>
								<p class="description"><?php _e( "This options should always be checked 'Yes'", 'wp-easy-smtp' ); ?></p>
							</td>
						</tr>
						<tr class="ad_opt wpesmtp_smtp_options field" rel="userpass">
							<th><?php _e( 'Username', 'wp-easy-smtp' ); ?></th>
							<td>
								<input type='text' name='wpesmtp_smtp_username' value='<?php echo esc_attr( $options['smtp_settings']['username'] ); ?>' /><br />
								<p class="description"><?php _e( "The username to login to your mail server", 'wp-easy-smtp' ); ?></p>
							</td>
							<th><?php _e( 'Password', 'wp-easy-smtp' ); ?></th>
							<td>
								<input type='password' name='wpesmtp_smtp_password' value='<?php echo esc_attr( wpesmtp_get_password() ); ?>' autocomplete='new-password' /><br />
								<p class="description"><?php _e( "The password to login to your mail server", 'wp-easy-smtp' ); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" id="settings-form-submit" class="button-primary" value="<?php _e( 'Save Changes', 'wp-easy-smtp' ); ?>" />
						<input type="hidden" name="wpesmtp_form_submit" value="submit" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'wpesmtp_nonce_name' ); ?>
						<span class="circle-loader"><span class="checkmark draw"></span></span>
						<span class="wpesmtp_ajax_message">Error</span>
					</p>
				</form>
			</div><!-- end of inside -->
		</div><!-- end of postbox -->

		<div class="wpesmtp-box">
			<div class="box-title"><h3><?php _e( 'Testing And Debugging Settings', 'wp-easy-smtp' ); ?></h3></div>
			<div class="inside">
				<p><?php _e( 'You can use this section to send an email from your server using the above configured SMTP details to see if the email gets delivered.', 'wp-easy-smtp' ); ?></p>
				
				<form id="wpesmtp_testing_form" method="post" action="">
					<input type="hidden" name="wpesmtp_task" value="test_mail">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( "To", 'wp-easy-smtp' ); ?>:</th>
							<td>
								<div class="switch-field">
									<input type="radio" id="wpesmtp_send_to-1" name="wpesmtp_send_to" value='users'<?php checked( $smtp_test_mail['wpesmtp_send_to'], 'users' ); ?> />
									<label for="wpesmtp_send_to-1"><?php _e( 'Users', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_send_to-2" name="wpesmtp_send_to" value='commenters'<?php checked( $smtp_test_mail['wpesmtp_send_to'], 'commenters' ); ?> />
									<label for="wpesmtp_send_to-2"><?php _e( 'Commenters', 'wp-easy-smtp' ); ?></label>
									<input type="radio" id="wpesmtp_send_to-3" name="wpesmtp_send_to" value='custom'<?php checked( $smtp_test_mail['wpesmtp_send_to'], 'custom' ); ?> />
									<label for="wpesmtp_send_to-3"><?php _e( 'Custom', 'wp-easy-smtp' ); ?></label>
								</div><br />
								<div id="send_to">
									<input type="text" name="wpesmtp_to" value="<?php echo $smtp_test_mail['wpesmtp_to']; ?>" /><br />
									<p class="description"><?php _e( "Enter the recipient's email address", 'wp-easy-smtp' ); ?></p>
								</div>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( "Subject", 'wp-easy-smtp' ); ?>:</th>
							<td>
								<input type="text" name="wpesmtp_subject" value="<?php echo esc_html( $smtp_test_mail['wpesmtp_subject'] ); ?>" /><br />
								<p class="description"><?php _e( "Enter a subject for your message", 'wp-easy-smtp' ); ?><br /><?php _e( "Variable values are:", 'wp-easy-smtp' ); ?> <code>%first_name%</code>, <code>%last_name%</code> and <code>%email%</code>.</p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( "Message", 'wp-easy-smtp' ); ?>:</th>
							<td>
								<textarea name="wpesmtp_message" id="wpesmtp_message" rows="8"><?php echo esc_textarea( $smtp_test_mail['wpesmtp_message'] ); ?></textarea><br />
								<p class="description"><?php _e( "Write your email message", 'wp-easy-smtp' ); ?><br /><?php _e( "Variable values are:", 'wp-easy-smtp' ); ?> <code>%first_name%</code>, <code>%last_name%</code> and <code>%email%</code>.</p>
							</td>
						</tr>				
					</table>
					<p class="submit">
						<input type="submit" id="settings-form-submit" class="button-primary" value="<?php _e( 'Send Test Email', 'wp-easy-smtp' ); ?>" />
						<input type="hidden" name="wpesmtp_test_submit" value="submit" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'wpesmtp_nonce_name' ); ?>
						<span class="circle-loader"><span class="checkmark draw"></span></span>
						<span class="wpesmtp_ajax_message">Error</span>
					</p>				
				</form>
			</div><!-- end of inside -->
		</div><!-- end of postbox -->

		<?php
		echo '</div></div>'; //<!-- end of #poststuff and #post-body -->
		echo '</div>'; //<!--  end of .wrap #wpesmtp-mail .wpesmtp-mail -->
	}
	
	/**
	 * Function to add plugin scripts
	 * @return void
	 */
	public function admin_head() {
		if ( isset( $_REQUEST['page'] ) && 'wpesmtp_settings' == $_REQUEST['page'] ) {
			wp_enqueue_style( 'wpesmtp_stylesheet', plugins_url( 'css/style.css', __FILE__ ), null, $this->VERSION );
			wp_enqueue_script( 'wpesmtp_script', plugins_url( 'js/script.js', __FILE__ ), array(
				 'jquery' 
			), $this->VERSION );
		}
	}

	public function admin_notice() {
		if ( !wpesmtp_is_ok() ) {
			$settings_url = admin_url() . 'options-general.php?page=wpesmtp_settings';
?>
			<div class="notice notice-error">
				<p><?php printf( __( 'Please configure your SMTP credentials in the <a href="%s">settings menu</a> in order to send email using WP Easy SMTP plugin.', 'wp-easy-smtp' ), esc_url( $settings_url ) ); ?></p>
			</div>
			<?php
		}
	}
	
	/**
	 * Function to test mail sending
	 * @return text or errors
	 */
	public function test_mail( $to_email, $subject, $message ) {
		if ( !wpesmtp_is_ok() ) {
			return;
		}
		$errors = '';

		$options = $this->OPTIONS;

		require_once( ABSPATH . WPINC . '/class-phpmailer.php' );
		$mail = new PHPMailer();

		$charset       = get_bloginfo( 'charset' );
		$mail->CharSet = $charset;

		$current_user =  wp_get_current_user();
		$from_name    = !empty( $options['from_name_field'] ) ? $options['from_name_field'] : $current_user->display_name;
		$from_email   = !empty( $options['from_email_field'] ) ? $options['from_email_field'] : $current_user->user_email;

		$mail->IsSMTP();
		
		/* If using smtp auth, set the username & password */
		if ( 'yes' == $options['smtp_settings']['autentication'] ) {
			$mail->SMTPAuth = true;
			$mail->Username = $options['smtp_settings']['username'];
			$mail->Password = wpesmtp_get_password();
		}

		/* Set the SMTPSecure value, if set to none, leave this blank */
		if ( $options['smtp_settings']['type_encryption'] !== 'none' ) {
			$mail->SMTPSecure = $options['smtp_settings']['type_encryption'];
		}

		/* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
		$mail->SMTPAutoTLS = false;

		/* Set the other options */
		$mail->Host = $options['smtp_settings']['host'];
		$mail->Port = $options['smtp_settings']['port'];

		//set Reply-To option if needed
		if ( !empty( $options['reply_to_email'] ) ) {
			$mail->addReplyTo( $options['reply_to_email'], $from_name );
		}

		$mail->SetFrom( $from_email, $from_name );
		$mail->isHTML( true );
		$mail->Subject = $subject;
		$mail->MsgHTML( $message );
		$mail->AddAddress( $to_email );
		$mail->SMTPDebug   = 0;
		$mail->SMTPOptions = array(
			 'ssl' => array(
				 'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true 
			) 
		);
		$mail->addCustomHeader('X-SMTP-BY', "WordPress Easy SMTP {$this->VERSION} (https://goo.gl/UjUNai)");
		
		/* Send mail and return result */
		if ( !$mail->Send() )
			$errors = $mail->ErrorInfo;
		
		$mail->ClearAddresses();
		$mail->ClearAllRecipients();
		
		if ( !empty( $errors ) ) {
			return $errors;
		} else {
			return true;
		}
	}
	
	/**
	 * Function to get user info
	 * @return text or errors
	 */
	public function user_info( $user_id = 1 ) {
		$user      = get_userdata( $user_id );
		$user_meta = get_user_meta( $user_id );

		return array(
			"first_name" => implode( '', $user_meta['first_name'] ),
			"last_name" => implode( '', $user_meta['last_name'] ),
			"email" => $user->user_email
		);
	}
	
	/**
	 * Function to get emails list
	 * @return text or errors
	 */
	public function get_emails_list( $type = 'users' ) {
		global $wpdb;
		$list = array();

		if ( $type === 'users' ) {
			$users = $wpdb->get_results("SELECT ID FROM $wpdb->users GROUP BY user_email");

			foreach ( $users as $user ) {
				$list[] = $this->user_info( $user->ID );
			}
		}
		else {
			$comments = $wpdb->get_results("SELECT comment_author_email, comment_author, user_id FROM $wpdb->comments WHERE comment_author_email != '' AND comment_approved<>'spam' GROUP BY comment_author_email");

			foreach ( $comments as $comment ) {
				if ( $comment->user_id ) {
					$list[] = $this->user_info( $comment->user_id );
				} else {
					$parts = explode( ' ', $comment->comment_author );
					$first_name = array_shift( $parts );
					$last_name = array_pop( $parts );
					$list[] = array(
						"first_name" => $first_name,
						"last_name" => $last_name,
						"email" => $comment->comment_author_email
					);
				}
			}
		}

		return $list;
	}

	/**
	 * Register ajax actions.
	 *
	 * @return  {void}
	 */
	public function ajax_actions() {
		$result = array();
		$p      = @stripslashes_deep( $_POST );

		$task = @$p['wpesmtp_task'];

		unset( $p['wpesmtp_task'] );

		// check for rights
		if ( !current_user_can( "manage_options" ) || !$task || !check_ajax_referer( plugin_basename( __FILE__ ), 'wpesmtp_nonce_name', false ) ) {
			$result = array(
				 'status' => 403,
				'message' => __( 'You are not allowed to change SMTP configuration settings.', 'wp-easy-smtp' ) 
			);
		} else {
			$options = $this->OPTIONS;
			$message = '';
			$error   = array();

			if ( $task == "settings" ) {
				/* Update settings */
				$options['from_name_field'] = isset( $p['wpesmtp_from_name'] ) ? sanitize_text_field( wp_unslash( $p['wpesmtp_from_name'] ) ) : $this->DEFAULT_OPTIONS['from_name_field'];

				if ( isset( $p['wpesmtp_from_email'] ) && !empty( $p['wpesmtp_from_email'] ) ) {
					if ( is_email( $p['wpesmtp_from_email'] ) ) {
						$options['from_email_field'] = sanitize_email( $p['wpesmtp_from_email'] );
					} else {
						$error[] = "<li>" . sprintf( __( "Please enter a valid email address in the '%s' field.", 'wp-easy-smtp' ), __( "From Email", 'wp-easy-smtp' ) ) . "</li>";
					}
				} else {
					$options['from_email_field'] = $this->DEFAULT_OPTIONS['from_email_field'];
				}

				if ( isset( $p['wpesmtp_reply_to_email'] ) && !empty( $p['wpesmtp_reply_to_email'] ) ) {
					if ( is_email( $p['wpesmtp_reply_to_email'] ) ) {
						$options['reply_to_email'] = sanitize_email( $p['wpesmtp_reply_to_email'] );
					} else {
						$error[] = "<li>" . sprintf( __( "Please enter a valid email address in the '%s' field.", 'wp-easy-smtp' ), __( "Reply-To Email Address", 'wp-easy-smtp' ) ) . "</li>";
					}
				} else {
					$options['reply_to_email'] = $this->DEFAULT_OPTIONS['reply_to_email'];
				}

				$options['mailer'] = isset( $p['wpesmtp_mailer'] ) ? sanitize_text_field( wp_unslash( $p['wpesmtp_mailer'] ) ) : $this->DEFAULT_OPTIONS['mailer'];

				/* Check value from "SMTP Host" option */
				if ( isset( $p['wpesmtp_smtp_host'] ) ) {
					if ( empty( $p['wpesmtp_smtp_host'] ) ) {
						$options['smtp_settings']['host'] = '';
						$error[]                          = "<li>" . __( "Please enter a valid host in the 'SMTP Host' field.", 'wp-easy-smtp' ) . "</li>";
					} else {
						$options['smtp_settings']['host'] = sanitize_text_field( $p['wpesmtp_smtp_host'] );
					}
				}
				$options['smtp_settings']['type_encryption'] = ( isset( $p['wpesmtp_smtp_type_encryption'] ) ) ? sanitize_text_field( $p['wpesmtp_smtp_type_encryption'] ) : 'none';
				$options['smtp_settings']['autentication']   = ( isset( $p['wpesmtp_smtp_autentication'] ) ) ? sanitize_text_field( $p['wpesmtp_smtp_autentication'] ) : 'yes';

				/* Check value from "Username & Password" option */
				if ( $options['smtp_settings']['autentication'] === 'yes' ) {
					if ( empty( $p['wpesmtp_smtp_username'] ) ) {
						$error[] = "<li>" . __( "Please enter a valid username in the 'Username' field.", 'wp-easy-smtp' ) . "</li>";
					} elseif ( empty( $p['wpesmtp_smtp_password'] ) ) {
						$error[] = "<li>" . __( "Please enter a valid password in the 'Password' field.", 'wp-easy-smtp' ) . "</li>";
					} else {
						$options['smtp_settings']['username'] = sanitize_text_field( $p['wpesmtp_smtp_username'] );
						$smtp_password                        = sanitize_text_field( $p['wpesmtp_smtp_password'] );
						$options['smtp_settings']['password'] = base64_encode( $smtp_password );
					}
				}

				/* Check value from "SMTP port" option */
				if ( isset( $p['wpesmtp_smtp_port'] ) ) {
					if ( empty( $p['wpesmtp_smtp_port'] ) || 1 > intval( $p['wpesmtp_smtp_port'] ) || ( !preg_match( '/^\d+$/', $p['wpesmtp_smtp_port'] ) ) ) {
						$options['smtp_settings']['port'] = '25';
						$error[]                          = "<li>" . __( "Please enter a valid port in the 'SMTP Port' field.", 'wp-easy-smtp' ) . "</li>";
					} else {
						$options['smtp_settings']['port'] = sanitize_text_field( $p['wpesmtp_smtp_port'] );
					}
				}

				/* Update settings in the database */
				if ( empty( $error ) ) {
					update_option( "{$this->SLUG}_options", $options );
					$message = __( "Settings saved.", 'wp-easy-smtp' );
				} else {
					$message = __( "Settings are not saved.", 'wp-easy-smtp' );
				}

				$result = array(
					 'status' => empty( $error ) ? 200 : 403,
					'error' => $error,
					'message' => $message 
				);
			}

			else if ( $task == "test_mail" ) {
				$smtp_test_mail = get_option( "{$this->SLUG}_smtp_test_mail" );
				if ( empty( $smtp_test_mail ) ) {
					$smtp_test_mail = array(
						'wpesmtp_to' => '',
						'wpesmtp_send_to' => '',
						'wpesmtp_subject' => '',
						'wpesmtp_message' => '' 
					);
				}

				$wpesmtp_to = '';
				$wpesmtp_send_to = isset( $p['wpesmtp_send_to'] ) ? sanitize_text_field( $p['wpesmtp_send_to'] ) : 'custom';
				if ( isset( $p['wpesmtp_to'] ) && $wpesmtp_send_to === 'custom' ) {
					if ( is_email( $p['wpesmtp_to'] ) ) {
						$wpesmtp_to = $p['wpesmtp_to'];
					} else {
						$error[] = "<li>" . __( "Please enter a valid email address in the recipient email field.", 'wp-easy-smtp' ) . "</li>";
					}
				}
				$wpesmtp_subject = isset( $p['wpesmtp_subject'] ) ? sanitize_text_field( $p['wpesmtp_subject'] ) : '';
				$wpesmtp_message = isset( $p['wpesmtp_message'] ) ? sanitize_text_field( $p['wpesmtp_message'] ) : '';

				//Save the test mail details so it doesn't need to be filled in everytime.
				$smtp_test_mail['wpesmtp_to']      = $wpesmtp_to;
				$smtp_test_mail['wpesmtp_send_to'] = $wpesmtp_send_to;
				$smtp_test_mail['wpesmtp_subject'] = $wpesmtp_subject;
				$smtp_test_mail['wpesmtp_message'] = $wpesmtp_message;
				update_option( "{$this->SLUG}_smtp_test_mail", $smtp_test_mail );

				if ( empty( $error ) ) {
					if ( $wpesmtp_send_to != 'custom' ) {
						$recipients = $this->get_emails_list( $wpesmtp_send_to );
						foreach ( $recipients as $recipient ) {
							$search_for = array( '%first_name%', '%last_name%', '%email%' );
							$replace_to = array( $recipient['first_name'], $recipient['last_name'], $recipient['email'] );
							$wpesmtp_subject = str_ireplace( $search_for, $replace_to, $wpesmtp_subject );
							$wpesmtp_message = str_ireplace( $search_for, $replace_to, $wpesmtp_message );
							$result = $this->test_mail( $recipient['email'], $wpesmtp_subject, $wpesmtp_message );
						}
					} else {
						$result = $this->test_mail( $wpesmtp_to, $wpesmtp_subject, $wpesmtp_message );
					}

					if ( is_bool( $result ) && $result ) {
						$message = __( 'Test mail was sent', 'wp-easy-smtp' );
					} else {
						$error[] = "<li>" . $result . "</li>";
					}
				}

				if ( !empty( $error ) )
					$message = __( 'Test mail was not sent', 'wp-easy-smtp' );
				
				$result = array(
					 'status' => empty( $error ) ? 200 : 403,
					'error' => $error,
					'message' => $message 
				);
			}
			
			else
				$result = array(
					 'status' => 400,
					'message' => __( "Bad Request", 'wp-easy-smtp' ) 
				);
		}
		
		wp_die( json_encode( $result ) );
	}
}

// Run WP_Easy_SMTP
$WP_Easy_SMTP = new WP_Easy_SMTP( __FILE__ );

add_action( 'plugins_loaded', 'wpesmtp_i18n' );
add_action( 'phpmailer_init', 'wpesmtp_init_smtp' );

//Load Translation files
if( !function_exists( 'wpesmtp_i18n' )) {
	function wpesmtp_i18n() {
		$path = path_join( dirname( plugin_basename( __FILE__ ) ), 'languages/' );
		load_plugin_textdomain( 'wp-easy-smtp', false, $path );
	}
}

if( !function_exists( 'wpesmtp_init_smtp' )) {
	/**
	 * Function to add smtp options in the phpmailer_init
	 * @return void
	 */
	function wpesmtp_init_smtp( $phpmailer ) {
		//check if SMTP credentials have been configured.
		if ( !wpesmtp_is_ok() ) {
			return;
		}

		$options = get_option( "wpesmtp_options" );

		/* Set the mailer type as per config above, this overrides the already called isMail method */
		$phpmailer->isSMTP();


		if ( strtolower( trim( $options['from_email_field'] ) ) === strtolower( $phpmailer->From ) ) {
			$from_email = trim( $options['from_email_field'] );
			$from_name = trim( $options['from_name_field'] );
			$from_email = !empty( $from_email ) ? $from_email : get_option( 'admin_email' );
			$from_name = !empty( $from_name ) ? $from_name : wp_specialchars_decode( get_option( 'blogname' ) );

			$phpmailer->From = $from_email;
			$phpmailer->FromName = $from_name;

			//set Reply-To option if needed
			if ( !empty( $options['reply_to_email'] ) )
				$phpmailer->addReplyTo( $options['reply_to_email'], $phpmailer->FromName );
		}

		$phpmailer->SetFrom( $phpmailer->From, $phpmailer->FromName );
		$phpmailer->addCustomHeader('X-SMTP-BY', "WordPress Easy SMTP {$WP_Easy_SMTP->VERSION} (https://goo.gl/UjUNai)");

		/* Set the SMTPSecure value */
		if ( $options['smtp_settings']['type_encryption'] !== 'none' ) {
			$phpmailer->SMTPSecure = $options['smtp_settings']['type_encryption'];
		}

		/* Set the other options */
		$phpmailer->Host        = $options['smtp_settings']['host'];
		$phpmailer->Port        = $options['smtp_settings']['port'];
		$phpmailer->SMTPOptions = array(
			 'ssl' => array(
				 'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true 
			) 
		);

		/* If we're using smtp auth, set the username & password */
		if ( 'yes' == $options['smtp_settings']['autentication'] ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $options['smtp_settings']['username'];
			$phpmailer->Password = wpesmtp_get_password();
		}
		//PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate.
		$phpmailer->SMTPAutoTLS = false;
	}
}

if( !function_exists( 'wpesmtp_get_password' )) {
	function wpesmtp_get_password() {
		$options = get_option( "wpesmtp_options" );
		$temp_password = $options['smtp_settings']['password'];
		$password      = "";
		$decoded_pass  = base64_decode( $temp_password );
		/* no additional checks for servers that aren't configured with mbstring enabled */
		if ( !function_exists( 'mb_detect_encoding' ) ) {
			return $decoded_pass;
		}
		/* end of mbstring check */
		if ( base64_encode( $decoded_pass ) === $temp_password ) { //it might be encoded
			if ( false === mb_detect_encoding( $decoded_pass ) ) { //could not find character encoding.
				$password = $temp_password;
			} else {
				$password = base64_decode( $temp_password );
			}
		} else { //not encoded
			$password = $temp_password;
		}
		return $password;
	}
}

if( !function_exists( 'wpesmtp_is_ok' )) {
	function wpesmtp_is_ok() {
		$options = get_option( "wpesmtp_options" );
		$is_ok   = true;

		if ( !isset( $options['smtp_settings']['host'] ) || empty( $options['smtp_settings']['host'] ) ) {
			$is_ok = false;
		} else if ( !isset( $options['smtp_settings']['port'] ) || empty( $options['smtp_settings']['port'] ) ) {
			$is_ok = false;
		} else if ( isset( $options['smtp_settings']['autentication'] ) && $options['smtp_settings']['autentication'] == "yes" ) {
			if ( !isset( $options['smtp_settings']['username'] ) || empty( $options['smtp_settings']['username'] ) ) {
				$is_ok = false;
			} else if ( !isset( $options['smtp_settings']['password'] ) || empty( $options['smtp_settings']['password'] ) ) {
				$is_ok = false;
			}
		}

		return $is_ok;
	}
}
endif;