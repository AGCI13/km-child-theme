<?php

/**
 * Customer completed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Récupérer les données du champ ACF 'transporteur'
$transp_name = get_post_meta( $order->get_id(), 'transporteur', true );
$transp_slug = sanitize_title( $transp_name );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<p><?php esc_html_e( 'L’équipe King Matériaux vous remercie encore une fois pour votre confiance.', 'woocommerce' ); ?></p>

<?php

switch ( $transp_slug ) {
    case 'king':
        echo '<p>Votre commande est en cours de préparation sur notre parc. Notre responsable transport vous contactera par téléphone sur le numéro renseigné sur votre commande 24h à 48h après la réception de ce mail pour une prise de rendez-vous.</p>'
            . '<p>Pour toute demande concernant une annulation ou un changement, merci de contacter directement le service transport au 07 87 18 06 17.</p>';
        break;
    case 'kuehne':
        echo '<p>Votre commande est sur le point d’être expédiée. Elle sera bientôt entre les mains de notre transporteur partenaire. Celui-ci vous contactera par téléphone 4 à 5 jours ouvrés avant la livraison.</p>'
            . '<p>Le délai moyen de livraison par ce transporteur est de 5 à 10 jours ouvrés (du lundi au vendredi, hors jours fériés).</p>'
            . '<h3>Rappel des modalités de livraison : </h3>'
            . '<p>Les big bag de 400 et 800kg seront livrés avec un camion 19T avec hayon. Attention : ils seront déposés au pas de porte*, sans manutention supplémentaire. Il n’y aura pas de grue sur le camion.</p>'
            . '<p>Taille du camion : 2,55m x 10m et 3,3m de hauteur.</p>'
            . '<p>Pour toute demande concernant une annulation ou un changement, merci de contacter directement le service transport au 07 87 18 06 17. Notez qu’une modification ou une annulation de commande peut engendrer des coûts supplémentaires. Veuillez prendre connaissance des différentes pénalités pouvant s’appliquer sur notre page livraison :</p><br>https://kingmateriaux.com/livraison-big-bag-sur-palette/'
            . '<br><p>A noter : Si vous avez commandé un kit terrain de pétanque ou des géotextiles avec des big bag, vous recevrez vos géotextiles par Colissimo, DPD ou GLS avant vos big bag. Vous recevrez un suivi par SMS sur le numéro communiqué dans votre commande.</p>'
            . '<p style="font-size:13px;font-style:italic;>*pas de porte : Livraison qui s’effectue en bas de votre immeuble ou à l’entrée de votre habitation (suivant l’accès). La livraison s’entend en limite de propriété (« pas de porte »), au plein air et sans manutention supplémentaire. Vous devez donc prendre vos dispositions pour pouvoir réceptionner votre colis et le transporter par vos propres moyens.</p>';
        break;
    case 'fragner':
        echo '<p>Votre commande est sur le point d’être expédiée. Elle sera bientôt entre les mains de notre transporteur partenaire. Celui-ci vous contactera par téléphone 48h à 72h avant la livraison.</p>'
            . '<p>Le délai moyen de livraison sur votre zone de livraison est de 10 à 20 jours ouvrés (du lundi au vendredi, hors jours fériés).</p>';
        break;
    case 'geotextile':
        echo '<p>Votre commande est en cours de préparation chez notre fournisseur et vous sera expédiée au plus vite via GLS, Colissimo ou DPD. Vous recevrez un suivi par SMS sur le numéro communiqué dans votre commande.</p>'
            . '<p>Pour toute demande concernant une annulation ou un changement, merci de contacter directement le service client au 06 38 58 69 27.</p>';
        break;
    case 'tred':
        echo '<p>Votre commande est sur le point d’être expédiée. Elle sera bientôt entre les mains de notre transporteur partenaire. Vous recevrez d’abord un questionnaire d’accessibilité obligatoire auquel vous devrez répondre. Celui-ci vous proposera ensuite une date de livraison par SMS 1 à 2 semaines avant le jour de livraison, via le numéro communiqué lors de votre commande.</p>'
            . '<p>Le délai moyen de livraison par ce transporteur est de 10 à 20 jours ouvré (du lundi au vendredi, hors jours fériés).</p>'
            . '<h3>Rappel des modalités de livraison : </h3>'
            . '<p>Les big bag 1.5T seront livrés par un camion semi-remorque avec chariot embarqué motorisé. Attention : ils seront déposés au pas de porte*, sans manutention supplémentaire. Il n’y aura pas de grue sur le camion.</p>'
            . '<p>Taille du camion : 2,55m x 12m et 3,3m de hauteur.</p>'
            . '<p>Pour toute demande concernant une annulation ou un changement, merci de contacter directement le service transport au 07 87 18 06 17. Notez qu’une modification ou une annulation de commande peut engendrer des coûts supplémentaires. Veuillez prendre connaissance des différentes pénalités pouvant s’appliquer sur notre page livraison :<br>https://kingmateriaux.com/livraison-big-bag-sur-palette/</p>';
        break;
    default:
        echo '<p>' . esc_html_e( 'We have finished processing your order.', 'woocommerce' ) . '</p>';
        break;
}
do_action( 'woocommerce_email_footer', $email );
