<?php
/*
Plugin Name: MailPoet WP e-Commerce Add-on
Plugin URI: http://www.mailpoet.com/
Description: Subscribe your customers to MailPoet newsletters.
Version: 1.0.2
Author: Sebs Studio
Author Email: sebastien@sebs-studio.com
Author URI: http://www.sebs-studio.com/
Requires at least: 3.7.1
Tested up to: 3.8.1

License:

  Copyright 2013 Sebs Studio (sebastien@sebs-studio.com)

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

if(!defined('ABSPATH')) exit; // Exit if accessed directly

// Check if WP e-Commerce is installed and activated first before activating this plugin.
if(in_array('wp-e-commerce/wp-shopping-cart.php', apply_filters('active_plugins', get_option('active_plugins')))){

class MailPoet_WP_E_Commerce_Add_on {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'MailPoet WP e-Commerce Add-on';
	const slug = 'mailpoet_wp_ecommerce_add_on';

	/**
	 * Constructor
	 */
	public function __construct(){
		// Register an activation hook for the plugin
		register_activation_hook(__FILE__, array(&$this, 'install_mailpoet_wp_ecommerce_add_on'));
		register_deactivation_hook(__FILE__, array(&$this, 'remove_checkout_field'));

		add_action('init', array(&$this, 'init_mailpoet_wp_ecommerce_add_on'));

		// hook into order processing
		add_action('wpsc_submit_checkout', array(&$this,'on_process_order'), 10, 1);
	}

	/**
	 * Runs when the plugin is activated.
	 */
	public function install_mailpoet_wp_ecommerce_add_on(){
		add_option('mailpoet_enable_checkout', 0);
		add_option('mailpoet_checkout_label', __('Yes, add me to your mailing list', 'mailpoet_wp_ecommerce'));

		// Install checkout field.
		$this->add_checkout_field();
	}

	/**
	 * Runs when the plugin is initialized.
	 */
	public function init_mailpoet_wp_ecommerce_add_on(){
		// Setup localization
		load_plugin_textdomain(self::slug, false, dirname(plugin_basename(__FILE__)).'/lang');

		if(is_admin()){
			// Adds a menu item under WP e-Commerce for the settings page.
			add_action('wpsc_add_submenu', array(&$this, 'add_mailpoet_wp_ecommerce_settings_menu'), 10);
		}
	}

	// Adds the mailpoet menu link under WP e-Commerce.
	public function add_mailpoet_wp_ecommerce_settings_menu(){
		add_submenu_page('edit.php?post_type=wpsc-product', __('MailPoet WP e-Commerce Add-On', 'mailpoet_wp_ecommerce'), 'MailPoet', 'manage_options', 'mailpoet', array(&$this, 'add_wp_ecommerce_mailpoet_settings_page'));
	}

	// Displays the settings page for MailPoet subscribe on checkout.
	public function add_wp_ecommerce_mailpoet_settings_page(){
		global $wpdb;

		$mailpoet_settings_page = 'edit.php?post_type=wpsc-product&page=mailpoet';

		if(isset($_POST['action']) && $_POST['action'] == 'save'){
			update_option('mailpoet_enable_checkout', $_POST['mailpoet_enable_checkout']);
			update_option('mailpoet_checkout_label', $_POST['mailpoet_checkout_label']);

			if($_POST['mailpoet_enable_checkout'] == '1'){
				$query = "UPDATE `".WPSC_TABLE_CHECKOUT_FORMS."` SET `active` = '1' WHERE `unique_name` = 'mailpoet_subscribe'";
			}
			else{
				$query = "UPDATE `".WPSC_TABLE_CHECKOUT_FORMS."` SET `active` = '0' WHERE `unique_name` = 'mailpoet_subscribe'";
			}
			$wpdb->query($query);
			//echo mysql_error();

			if(isset($_POST['checkout_lists'])){
				$checkout_lists = $_POST['checkout_lists'];
				$lists = $checkout_lists;
				update_option('mailpoet_wp_ecommerce_subscribe_too', $lists);
			}
			else{
				delete_option('mailpoet_wp_ecommerce_subscribe_too');
			}
			$location = admin_url($mailpoet_settings_page.'&status=saved');
			wp_safe_redirect($location);
			exit;
		}
		if(isset($_GET['status']) && $_GET['status'] == 'saved'){
			echo '<div id="message" class="updated"><p>'.__('Settings saved', 'mailpoet_wp_ecommerce').'</p></div>';
		}

		$enable_checkout = get_option('mailpoet_enable_checkout');
		$checkout_label = get_option('mailpoet_checkout_label');
	?>
	<div class="wrap wp_ecommerce">
		<div class="icon32" id="icon-options-general"></div>

		<h2><?php _e('MailPoet WP e-Commerce Add-On', 'mailpoet_wp_ecommerce'); ?></h2>

		<form name="mailpoet-settings" method="post" id="mailpoet-settings" action="<?php echo admin_url($mailpoet_settings_page); ?>" class="form-valid" autocomplete="off">

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="enable_checkout"><?php _e('Enable subscribe on checkout', 'mailpoet_wp_ecommerce'); ?></label>
					</th>
					<td>
						<label for="mailpoet_enable_checkout"><input type="radio" value="1" name="mailpoet_enable_checkout"<?php if($enable_checkout == '1') echo ' checked="checked"'; ?> id="enable_checkout-1"> <?php _e('Yes', 'mailpoet_wp_ecommerce'); ?></label>
						<label for="mailpoet_enable_checkout"><input type="radio" value="0" name="mailpoet_enable_checkout"<?php if($enable_checkout != '1') echo ' checked="checked"'; ?> id="enable_checkout-0"> <?php _e('No', 'mailpoet_wp_ecommerce'); ?></label>
						<p class="description">
							<label for="enable_checkout"><?php _e('Add a subscribe checkbox to your checkout page?', 'mailpoet_wp_ecommerce'); ?></label>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mailpoet_checkout_label"><?php _e('Checkout label', 'mailpoet_wp_ecommerce'); ?></label>
					</th>
					<td>
						<input type="text" name="mailpoet_checkout_label" value="<?php echo $checkout_label; ?>" placeholder="<?php _e('Yes, add me to your mailing list', 'mailpoet_wp_ecommerce'); ?>" id="checkout_label" size="80">
					</td>
				</tr>
			</tbody>
		</table>
		<br>

		<?php
		$mailpoet_list = mailpoet_lists();
		include_once(dirname(__FILE__).'/include/settings-newsletters.php');
		do_action('wp_ecommerce_mailpoet_list_newsletters', $mailpoet_list);
		?>

		<p class="submit">
			<input type="submit" value="<?php _e('Save Settings', 'mailpoet_wp_ecommerce'); ?>" class="button-primary mailpoet">
			<?php wp_nonce_field('save_mailpoet_wp_ecommerce_settings'); ?>
			<input type="hidden" value="save" name="action">
		</p>

		</form>
	</div>
	<?php
	}

	/**
	 * This adds a checkout field in the 
	 * database when the plugin is activated.
	 */
	public function add_checkout_field(){
		global $wpdb;

		$field_installed = get_option('mailpoet_wp_ecommerce_active');
		if(empty($field_installed) || $field_installed != 'yes'){
			$checkout_label = get_option('mailpoet_checkout_label');

			$checkout_order = $wpdb->get_var("SELECT checkout_order FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `checkout_set` = 0 ORDER BY checkout_order DESC LIMIT 1");

			$data = array(
				'name' 				=> __('Newsletter', 'mailpoet_wp_ecommerce'),
				'type' 				=> 'checkbox',
				'checkout_order' 	=> $checkout_order+1,
				'options' 			=> serialize(array($checkout_label => '1')),
				'unique_name' 		=> 'mailpoet_subscribe',
				'active' 			=> '0'
			);

			$wpdb->insert(WPSC_TABLE_CHECKOUT_FORMS, $data);
			//echo mysql_error();

			update_option('mailpoet_wp_ecommerce_active', 'yes');
		}
	}

	/**
	 * This removed the checkout field when 
	 * the plugin has been deactivated.
	 */
	public function remove_checkout_field(){
		global $wpdb;

		$wpdb->query("DELETE FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `unique_name` = 'mailpoet_subscribe'");
		//echo mysql_error();

		update_option('mailpoet_wp_ecommerce_active', 'no');
	}

	/**
	 * This process the customers subscription if any 
	 * to the newsletters along with their order.
	 */
	function on_process_order($params){
		$purchase_log_id  = $params['purchase_log_id'];
		$collected_data   = $_REQUEST['collected_data'];
		$index            = $this->getCheckoutFormFieldIndex();

		$billingemail     = $collected_data[$index['billingemail']->id];
		$billingfirstname = $collected_data[$index['billingfirstname']->id];
		$billinglastname  = $collected_data[$index['billinglastname']->id];

		// If the check box has been ticked then the customer is added to the MailPoet lists enabled.
		if(isset($collected_data[$index['mailpoet_subscribe']->id][0]) && $collected_data[$index['mailpoet_subscribe']->id][0] == 1){

			$checkout_lists = get_option('mailpoet_wp_ecommerce_subscribe_too');

			$user_data = array(
				'email'		=> $billingemail,
				'firstname'	=> $billingfirstname,
				'lastname'	=> $billinglastname
			);

			$data_subscriber = array(
				'user' 		=> $user_data,
				'user_list' => array('list_ids' => $checkout_lists)
			);

			$userHelper = &WYSIJA::get('user','helper');
			$userHelper->addSubscriber($data_subscriber);
		}
	} // on_process_order()

	// Get all of WPEC checkout form fields.
	public function getCheckoutFormFields(){
		global $wpdb;

		$result = $wpdb->get_results("SELECT id, name, unique_name FROM ".$wpdb->prefix."wpsc_checkout_forms WHERE NOT unique_name = '' ORDER BY checkout_order ASC");
		//echo mysql_error();

		return $result;
	}

	// Get index of all WPEC checkout form fields.
	public function getCheckoutFormFieldIndex(){
		$fields = $this->getCheckoutFormFields();
		$index = array();

		foreach($fields as $field){
			$index[$field->unique_name] = $field;
		}
		return $index;
	}

} // end class
$mailpoet_wp_ecommerce_add_on = new MailPoet_WP_E_Commerce_Add_on();

// Get all mailpoet lists.
if( ! function_exists( 'mailpoet_lists' ) ) {
	function mailpoet_lists(){
		// This will return an array of results with the name and list_id of each mailing list
		$model_list = WYSIJA::get('list','model');
		$mailpoet_lists = $model_list->get(array('name','list_id'), array('is_enabled' => 1));

		return $mailpoet_lists;
	}
}

}
else{
	add_action('admin_notices', 'mailpoet_wp_ecommerce_add_on_active_error_notice');
	// Displays an error message if WP e-Commerce is not installed or activated.
	function mailpoet_wp_ecommerce_add_on_active_error_notice(){
		global $current_screen;

		if($current_screen->parent_base == 'plugins'){
			echo '<div class="error"><p>'.sprintf(__('MailPoet WP e-Commerce Add-on requires WP e-Commerce. Please install and activate <a href="%s">WP e-Commerce</a> first.', 'mailpoet_wp_ecommerce'), admin_url('plugin-install.php?tab=search&type=term&s=WP+e-Commerce')).'</p></div>';
		}
		$plugin = plugin_basename(__FILE__);
		// Deactivate this plugin until WP e-Commerce has been installed and activated first.
		if(is_plugin_active($plugin)){ deactivate_plugins($plugin); }
	}
}
?>