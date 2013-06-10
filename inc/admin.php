<?php

if (!class_exists( 'bwpv_admin' ) ) {

	class bwpv_admin extends bit51_bwpv {
		
		/**
		 * Initialize admin function
		 */
		function __construct() {
			
			//add scripts and css
			add_action( 'admin_print_scripts', array( $this, 'config_page_scripts' ) );
			add_action( 'admin_print_styles', array( $this, 'config_page_styles' ) );
		
			//add menu items
			add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		
			//add settings
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			//add processing
			add_action( 'admin_init', array( $this, 'process_call' ) );
		
			//add action link
			add_filter( 'plugin_action_links', array( $this, 'add_action_link' ), 10, 2 );
		
			//add donation reminder
			add_action( 'admin_init', array( $this, 'ask' ) );
			
		}
	
		/**
		 * Register page settings
		 */
		function register_settings_page() {

			add_options_page( __( $this->pluginname, $this->hook ), __( $this->pluginname, $this->hook ), $this->accesslvl, $this->hook, array( $this,'bwpv_admin_init' ) );

		}	
		
		/**
		 * Register admin page main content
		 * To add more boxes to the admin page add a 2nd inner array item with title and callback function or content
		 */
		function bwpv_admin_init() {

			global $bwpv_error;

			if ( is_wp_error( $bwpv_error ) ) {
				echo '<div id="message" class="error"><p>' . __( 'ERROR: Could not clear cache. Contact your server administrator if this error persists.', $this->hook ) . '</p></div>';		
			}

			$this->admin_page( $this->pluginname . ' ' . __( 'Options', $this->hook ), 

				array(

					array( __( 'Instructions', $this->hook), 'install_instructions' ), //primary admin page content
					array( __( 'General Options', $this->hook), 'general_options' ), //primary admin page content

				)

			);

		}
		
		/**
		 * Create instructions block
		 */
		function install_instructions() {
			?>
			<p><?php _e( 'Simply enter your varnish server address and port below and select enable. Caches will automatically be cleared where appropriate (new content, comments, etc) and you can manually clear your cache via the admin bar. Please note that messages will only diplay when there is an error. You can confirm the cache has been cleared by looking for "varnish-cleared" at the end of the URL when manually clearing the cache or if there is no error message for all other occasions.', $this->hook ); ?></p>
			<?php
		}

		/**
		 * Process call to clear cache
		 * 
		 * @return void
		 */
		function process_call() {

			global $bwpvoptions, $bit51bwpv, $bwpv_error;

			if ( isset( $_GET['flush'] ) && isset( $_GET['id'] ) && wp_verify_nonce( filter_var( $_REQUEST['_wpnonce'], FILTER_SANITIZE_STRING ), 'bwpv-nonce') ) {
	
				if ( intval( $_GET['flush'] == filter_var( 'all', FILTER_SANITIZE_STRING ) ) ) {

					$result = $bit51bwpv->purgeAll();

				} else {

					$result = $bit51bwpv->purgePost( filter_var( $_GET['id'], FILTER_SANITIZE_STRING ) );					

				}

				if ( $result === false ) {

					$bwpv_error = new WP_Error( 'error', 'error' );

				} else {

					$returnaddr = esc_url( $_GET['return'] );
					
					if ( strpos( $returnaddr, '?' ) === false ) {
						$sep = '?';
					} else {
						$sep = '&';
					}

					wp_redirect( $_GET['return'] . $sep . 'varnish-cleared', 301 );
				}

			}

		}
		
		/**
		 * Create admin page main content
		 */
		function general_options() {
			global $bwpvoptions;
			?>
			<form method="post" action="options.php">
			<?php settings_fields( 'bit51_bwpv_options' ); //use main settings group ?>
				<table class="form-table">
					<tr valign="top">
						<td>
							<input type="checkbox" name="bit51_bwpv[enabled]" id="enabled" value="1" <?php if ( $bwpvoptions['enabled'] == 1 ) echo "checked"; ?> /> <label for="buffer"><?php _e( 'Enable Varnish Cache Purge', $this->hook ); ?></label><br />
							<label for"address"><?php _e( 'Server Address', $this->hook ); ?></label> <input name="bit51_bwpv[address]" id="header" value="<?php echo $bwpvoptions['address']; ?>" type="text"><br />
							<label for"port"><?php _e( 'Server Port', $this->hook ); ?></label> <input name="bit51_bwpv[port]" id="port" value="<?php echo $bwpvoptions['port']; ?>" type="text"><br />
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" /></p>
			</form>
			<?php
		}

		/**
		 * Validate input
		 */
		function bwpv_val_options( $input ) {
			
			//make sure boolean options are set
			$input['enabled'] = isset( $input['enabled'] ) ? $input['enabled'] : '0';

			$input['address'] = sanitize_text_field( $input['address'] );
			$input['port'] = intval( sanitize_text_field( $input['port'] ) );
		    
		    return $input;
		}
		
	}

}
