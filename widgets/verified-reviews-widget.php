<?php
class Verified_Reviews_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'km_verified_reviews';
	}

	public function get_title() {
		return esc_html__( 'KM Avis vérifiés', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-check-circle';
	}

	public function get_categories() {
		return array( 'kingmateriaux', 'woocommerce' );
	}

	protected function render() {

		global $product;

		if ( ! function_exists( 'ntav_get_netreviews_average' ) || ! function_exists( 'ntav_addStars' ) ) {
			return;
		}

		if ( ! $product ) {
			$product = wc_get_product( get_the_ID() );
			if ( ! $product instanceof WC_Product ) {
				return;
			}
		}

		$my_current_lang = '';
		$average         = ntav_get_netreviews_average( $product->get_id(), $my_current_lang );
		$note            = round( $average, 1 );
		$logo            = content_url() . '/plugins/netreviews/includes/images/' . ntav_get_img_by_lang()['sceau_lang'];
		?>
		<div class="verified-review">
			<img src="<?php echo esc_url( $logo ); ?>" alt="logo avis verifie" />
			<div class="netreviews_bg_stars_big headerStars" title="<?php echo esc_html( $note ); ?>/5">
				<?php echo ntav_addStars( $average ); ?>
			</div>
			<span itemprop="reviewCount"><?php echo esc_html( $note ); ?>/5</span> 
		</div>
		<?php
	}
}
