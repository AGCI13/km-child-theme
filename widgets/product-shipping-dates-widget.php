<?php
class Product_Shipping_Dates_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'km_product_shipping_dates';
	}

	public function get_title() {
		return esc_html__( 'KM Dates de livraison produit', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	public function get_categories() {
		return array( 'kingmateriaux', 'woocommerce' );
	}

	public function get_keywords() {
		return array( 'product' );
	}

	protected function render() {
		echo km_get_shipping_dates( 'product' );
	}
}
