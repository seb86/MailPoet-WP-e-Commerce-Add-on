<?php
/**
 * Lists all newsletters created in MailPoet
 */

if(!defined('ABSPATH')) exit; // Exit if accessed directly.

/**
 * Output the list of newsletters.
 *
 * @access public
 * @return void
 */
function wp_ecommerce_mailpoet_list_subscription_newsletters($mailpoet_list){
	?>
	<table class="mailpoet widefat" cellspacing="0">
		<thead>
			<tr>
				<th width="1%"><?php _e('Enabled', 'mailpoet_wp_ecommerce'); ?></th>
				<th><?php _e('Newsletters', 'mailpoet_wp_ecommerce'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$checkout_lists = get_option('mailpoet_wp_ecommerce_subscribe_too');
			foreach($mailpoet_list as $key => $list){
				$list_id = $list['list_id'];
				$checked = '';
				if(isset($checkout_lists) && !empty($checkout_lists)){
					if(in_array($list_id, $checkout_lists)){ $checked = ' checked="checked"'; }
				}
				echo '<tr>
					<td width="1%" class="checkbox">
						<input type="checkbox" name="checkout_lists[]" value="'.esc_attr($list_id).'"'.$checked.' />
					</td>
					<td>
						<p><strong>'.$list['name'].'</strong></p>
					</td>
				</tr>';
			}
			?>
		</tbody>
	</table>
	<?php
}
add_action('wp_ecommerce_mailpoet_list_newsletters', 'wp_ecommerce_mailpoet_list_subscription_newsletters');
?>