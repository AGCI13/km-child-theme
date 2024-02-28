<?php
class Account_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'km_my_account';
	}

	public function get_title() {
		return esc_html__( 'KM Mon compte', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-person';
	}

	public function get_categories() {
		return array( 'kingmateriaux', 'woocommerce' );
	}

	public function get_keywords() {
		return array( 'account' );
	}

	protected function render() {
		$permalink = is_user_logged_in( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ) ? get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) : get_permalink( 563 );
		?>

		<div class="king-account">
			<a href="<?php echo esc_url( $permalink ); ?>">
			<img src="<?php echo esc_url( get_stylesheet_directory_uri() ) . '/assets/img/account.svg'; ?>" alt="My Account">
			<?php if ( is_user_logged_in() ) : ?>
				<span class="king-account-content"><?php esc_html_e( 'Mon compte', 'kingmateriaux' ); ?></span>
			<?php else : ?>
				<span class="king-account-content"><?php esc_html_e( 'Se connecter', 'kingmateriaux' ); ?></span>
			<?php endif; ?>
			</a>
		</div>

		<?php
	}
}
