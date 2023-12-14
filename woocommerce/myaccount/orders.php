<?php

/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_account_orders', $has_orders); ?>

<?php if ($has_orders) : ?>
	<div class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
		<div class="h2_grey_back"><h2><?php echo esc_html('Commande(s)'); ?></h2></div>
		<div class="bloc-commandes">
			<?php
			foreach ($customer_orders->orders as $customer_order) {
				$order      = wc_get_order($customer_order); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$item_count = $order->get_item_count() - $order->get_item_count_refunded();
			?>
			<div class="encart-commande">
				<div class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr($order->get_status()); ?> order">
					<?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) : ?>
						<div class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr($column_id); ?>" data-title="<?php echo esc_attr($column_name); ?>">
							<?php if ('order-number' === $column_id) : ?>
								<span class="order-number"><?php echo esc_html(_x('#', 'hash before order number', 'woocommerce') . $order->get_order_number()); ?></span>
							<?php elseif ('order-total' === $column_id) : ?>
								<span class="order-total"><?php echo $order->get_formatted_order_total(); ?></span>
								<span class="order-item-count"><?php echo sprintf(_n('%s item', '%s items', $item_count, 'woocommerce'), $item_count); ?></span>
							<?php elseif ('order-date' === $column_id) : ?>
    							<?php $order_date = $order->get_date_created()->date('d/m/Y'); // Format the date ?>
    							<time datetime="<?php echo esc_attr($order->get_date_created()->date('c')); ?>"><?php echo esc_html($order_date); ?></time>
								<?php elseif ('order-status' === $column_id) : ?>
									<?php $order_status = $order->get_status(); ?>
									<span class="order-status">Statut : 
										<span class="status-<?php echo esc_attr($order_status); ?>">
											<?php echo esc_html(wc_get_order_status_name($order_status)); ?>
										</span>
									</span>
								<?php elseif ('order-actions' === $column_id) : ?>
								<?php
								$actions = wc_get_account_orders_actions($order);
								if (!empty($actions)) {
									foreach ($actions as $key => $action) {
										if ($key === 'view') {
											echo '<a class="btn btn-primary" href="' . esc_url($action['url']) . '" class="woocommerce-button button ' . sanitize_html_class($key) . '">Détail de la commande</a>';
										}
										if ($key === 'invoice') {
											continue;
										}
										if ($key !== 'view') {
											echo '<a href="' . esc_url($action['url']) . '" class="woocommerce-button button ' . sanitize_html_class($key) . '">' . esc_html($action['name']) . '</a>';
										}
									}
								}
								?>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div> <!-- Fin .encart-commande -->
			<?php
			}
			?>
		</div>
	</div>

	<?php do_action('woocommerce_before_account_orders_pagination'); ?>

	<?php if (1 < $customer_orders->max_num_pages) : ?>
		<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
			<?php if (1 !== $current_page) : ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page - 1)); ?>"><?php esc_html_e('Previous', 'woocommerce'); ?></a>
			<?php endif; ?>

			<?php if (intval($customer_orders->max_num_pages) !== $current_page) : ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page + 1)); ?>"><?php esc_html_e('Next', 'woocommerce'); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

<?php else : ?>
	<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
		<a class="woocommerce-Button button" href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>"><?php esc_html_e('Browse products', 'woocommerce'); ?></a>
		<?php esc_html_e('No order has been made yet.', 'woocommerce'); ?>
	</div>
<?php endif; ?>

<?php do_action('woocommerce_after_account_orders', $has_orders); ?>
