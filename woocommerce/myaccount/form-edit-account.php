<?php

/**
 * Edit account form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-account.php.
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

do_action('woocommerce_before_edit_account_form'); ?>
<?php
$customer_id = get_current_user_id();

if (!wc_ship_to_billing_address_only() && wc_shipping_enabled()) {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing'  => __('Billing address', 'woocommerce'),
			'shipping' => __('Shipping address', 'woocommerce'),
		),
		$customer_id
	);
} else {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing' => __('Billing address', 'woocommerce'),
		),
		$customer_id
	);
}
?>


<div class="h2_grey_back"><h2>Adresses</h2></div>
<p><?php echo apply_filters('woocommerce_my_account_my_address_description', esc_html__('The following addresses will be used on the checkout page by default.', 'woocommerce')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

<?php if (!wc_ship_to_billing_address_only() && wc_shipping_enabled()) : ?>
    <h3>Adresse de facturation</h3>
    <div class="bloc-adresses">
        <?php 
        $address = wc_get_account_formatted_address('billing');
        ?>
        <div class="encart-adresse">
            <p><?php echo $address ? wp_kses_post($address) : esc_html_e('You have not set up this type of address yet.', 'woocommerce'); ?></p>
            <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address', 'billing')); ?>" class="edit modifier-adresses"><?php echo esc_html__('Modifier l\'adresse', 'woocommerce'); ?></a>
        </div>
    </div> <!-- fin bloc-adresses -->

    <h3>Adresse de livraison</h3>
    <div class="bloc-adresses">
        <?php 
        $address = wc_get_account_formatted_address('shipping');
        ?>
        <div class="encart-adresse">
			<p><?php echo $address ? wp_kses_post($address) : esc_html_e('You have not set up this type of address yet.', 'woocommerce'); ?></p>
			<a href="<?php echo esc_url(wc_get_endpoint_url('edit-address', 'shipping')); ?>" class="edit modifier-adresses"><?php echo esc_html__('Modifier l\'adresse', 'woocommerce'); ?></a>
        </div>
    </div> <!-- fin bloc-adresses -->
<?php endif; ?>
<div class="h2_grey_back"><h2>Informations personnelles</h2></div>
<form class="woocommerce-EditAccountForm edit-account" action="" method="post">
    <?php do_action('woocommerce_edit_account_form_start'); ?>
	<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
		<label for="account_first_name"><?php esc_html_e('First name', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr($user->first_name); ?>" />
	</p>
	<p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
		<label for="account_last_name"><?php esc_html_e('Last name', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr($user->last_name); ?>" />
	</p>
	<div class="clear"></div>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="account_display_name"><?php esc_html_e('Display name', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr($user->display_name); ?>" /> <span><em><?php esc_html_e('This will be how your name will be displayed in the account section and in reviews', 'woocommerce'); ?></em></span>
	</p>
	<div class="clear"></div>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="account_email"><?php esc_html_e('Email address', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
		<input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr($user->user_email); ?>" />
	</p>
    <p>
        <button type="submit" class="woocommerce-Button button">Enregistrer les informations</button>
    </p>
    <?php do_action('woocommerce_edit_account_form_end'); ?>
</form>
<div class="h2_grey_back"><h2>Mot de passe</h2></div>
<form class="woocommerce-EditAccountForm edit-account" action="" method="post">
    <?php do_action('woocommerce_edit_account_form_start'); ?>

	<p class="text-mdp"> Besoin de changer votre mot de passe ? Renseignez le : </p>
	<fieldset>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="password_current"><?php esc_html_e('Current password (leave blank to leave unchanged)', 'woocommerce'); ?></label>
			<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="off" />
		</p>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="password_1"><?php esc_html_e('New password (leave blank to leave unchanged)', 'woocommerce'); ?></label>
			<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="off" />
		</p>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="password_2"><?php esc_html_e('Confirm new password', 'woocommerce'); ?></label>
			<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="off" />
		</p>
	</fieldset>
    <p>
        <button type="submit" class="woocommerce-Button button">Changer le mot de passe</button>
    </p>
    <?php do_action('woocommerce_edit_account_form_end'); ?>
</form>
<?php do_action('woocommerce_after_edit_account_form'); ?>