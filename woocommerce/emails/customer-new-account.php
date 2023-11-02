<?php

/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-new-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 6.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<h1 class="email-heading"><?php printf( __( 'Bienvenue dans la King Family ! 🎉', 'woocommerce' ), esc_html( $invoice_number ) ); ?></h1>

<p><?php echo __( 'Tu fais officiellement partie de la famille King Matériaux !', 'woocommerce' ); ?></p>

<p><?php echo __( 'Maintenant que ton compte est activé tu peux profiter des King bénéfices :', 'woocommerce' ); ?></p>

<ul>
    <li>
        <?php echo __( '🫅 Suis tes commandes depuis ton compte', 'woocommerce' ); ?>
    </li>
    <li>
        <?php echo __( '❤️ Consulte tes favoris', 'woocommerce' ); ?>
    </li>
    <li>
        <?php echo __( '💳 Ajoute un ou des moyens de paiement pour faciliter ton/tes prochains achats !', 'woocommerce' ); ?>
    </li>
</ul>

<p style="margin-bottom:30px;"><?php echo __( 'Sans parler du Service Client disponible 6J/7 jusqu`\'à 20h00 sauf le dimanche et les jours fériés et des produits de qualité ! ✨', 'woocommerce' ); ?></p>

<a id="km-cta" href="<?php echo get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ); ?>"><?php echo __( 'Visiter mon compte', 'woocommmerce' ); ?></a>

<?php
do_action( 'woocommerce_email_footer', $email );
