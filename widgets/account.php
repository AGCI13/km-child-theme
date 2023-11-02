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
        return [ 'basic' ];
    }

    public function get_keywords() {
        return [ 'account'];
    }

    protected function render() {
        ?>

        <div class="king-account">
            <a href="<?php if (is_user_logged_in()) { echo get_permalink(get_option('woocommerce_myaccount_page_id')); } else { echo '/connexion'; } ?>">
            <img src="/wp-content/themes/hello-elementor-child/assets/img/account.svg" alt="My Account">
            <?php if (is_user_logged_in()) : ?>
                <span class="king-account-content">Mon compte</span>
            <?php else: ?>
                <span class="king-account-content">Se connecter</span>
            <?php endif; ?>
            </a>
        </div>

        <?php
    }
}
