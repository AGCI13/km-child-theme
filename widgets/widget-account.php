<?php
class Account_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'king_account';
    }

    public function get_title() {
        return esc_html__( 'King - My Account', 'elementor-addon' );
    }

    public function get_icon() {
        return 'eicon-person';
    }

    public function get_categories() {
        return array( 'basic' );
    }

    public function get_keywords() {
        return array( 'account' );
    }

    protected function render() {
        if ( is_user_logged_in() ) {
            $permalink = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
        } else {
            $permalink = '/connexion';
        }
        ?>

        <div class="king-account">
            <a href="<?php echo $permalink; ?>         ">
            <img src="<?php echo get_stylesheet_directory_uri() . '/assets/img/account.svg'; ?>" alt="My Account">
            <?php if ( is_user_logged_in() ) : ?>
                <span class="king-account-content"><?php echo __( 'Mon compte', 'kingmateriaux' ); ?></span>
            <?php else : ?>
                <span class="king-account-content"><?php echo __( 'Se connecter', 'kingmateriaux' ); ?></span>
            <?php endif; ?>
            </a>
        </div>

        <?php
    }
}
