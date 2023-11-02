<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require_once 'config/import.php';
require_once 'config/filter.php';
require_once 'config/action.php';
require_once 'config/ajax.php';
require_once 'config/shortcode.php';
require_once 'config/price.php';
require_once 'config/atoosync.php';

/* ---- Début Ajout AGCI ---- */

require_once 'includes/wc-order-functions.php';

function km_enqueue_custom_admin_style() {
    $admin_stylesheet_path = get_stylesheet_directory() . '/assets/css/admin-style.css';

    if ( file_exists( $admin_stylesheet_path ) ) {
        wp_enqueue_style( 'custom-admin-style', get_stylesheet_directory_uri() . '/assets/css/admin-style.css' );
    }
}
add_action( 'admin_enqueue_scripts', 'km_enqueue_custom_admin_style' );

/* ---- Fin ajout AGCI ---- */

add_action( 'wp_ajax_wc_woocommerce_clear_cart_url', 'td_woocommerce_clear_cart_url' );
add_action( 'wp_ajax_nopriv_wc_woocommerce_clear_cart_url', 'td_woocommerce_clear_cart_url' );

function td_woocommerce_clear_cart_url() {
     global $woocommerce;

    $returned = array( 'status' => 'error' );
    $woocommerce->cart->empty_cart();

    if ( $woocommerce->cart->get_cart_contents_count() == 0 ) {
        $returned = array( 'status' => 'success' );
    }

    die( json_encode( $returned ) );
}

add_action( 'wp_ajax_wc_before_popup', 'td_before_popup' );
add_action( 'wp_ajax_nopriv_wc_before_popup', 'td_before_popup' );

function td_before_popup() {
     global $woocommerce;

    if ( !isset( $_POST['product_id'] ) || !isset( $_POST['qty'] ) ) {
        die( json_encode( array( 'status' => 'error' ) ) );
    }

    // Vérifier si un "produit en vrac" est déjà dans le panier
    $isBulkProductInCart = false;

    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $cart_item ) {
        $_product     = $cart_item['data'];
        $addedProduct = wc_get_product( $_POST['variation_id'] !== '' ? $_POST['variation_id'] : $_POST['product_id'] );

        if ( strpos( strtoupper( $_product->get_name() ), 'VRAC A LA TONNE' ) !== false &&
            strpos( strtoupper( $addedProduct->get_name() ), 'VRAC A LA TONNE' ) !== false ) {
            $isBulkProductInCart = true;
            break;
        }
    }

    if ( $isBulkProductInCart === true ) {
        // Afficher la popup pour indiquer que le produit en vrac est déjà dans le panier
        die(
            json_encode(
                array(
                    'status'  => 'vrac_popup',
                    'content' => "<style>
#popup-content {
  position: fixed;
  width: 600px;
  height: 400px;
  top: 50%;
  left: 50%;
  margin-top: -100px;
  margin-left: -250px;
  background: white;
  display: flex;
  flex-flow: column;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

#close-popup {
  position: absolute;
  top: 12px;
  right: 20px;
}

#popup-title {
  font-weight: bold;
  font-size: 22px;
}

#popup-content p {
  text-align: center;
  padding: 10px;
  width: 370px;
}

#popup-content sub {
  display: block;
  height: 50px;
  font-size: 13px;
  width: 300px;
  bottom: 0;
  bottom: 0;
  line-height: 20px;
  line-height: 20px;
  text-align: center;
}

#popup-btn button {
  background-color: #bc9c64;
  border-color: #bc9c64;
  color: white;
  font-weight: bold;
  margin-top: 12px;
  margin-bottom: 20px;
}

#popup-cart-link a {
  font-size: 14px;
  color: #bc9c64;
  text-decoration: underline;
  font-weight: 400;
}

#popup-shadow {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #0000001a;
    z-index: 0;
}

#close-popup {
    cursor: pointer;
}
</style><div id='popup-shadow'></div><div id='popup-content'><span id='close-popup' onclick='hidePopup()'>✖</span><p>Il est impossible d'ajouter des vracs de différents agrégats (gravier, galet, sable ou terre) dans le même camion. Merci de choisir des big bags si vous souhaitez différents agrégats ou choisir une option de livraison dans votre panier pour une livraison en vrac de l'ensemble des produits</p><div id='popup-btn'><button onclick='hidePopup()'>J'ai compris le message</button></div><div id='popup-cart-link'><a href='/panier'>Voir mon panier</a></div></div>",
                )
            )
        );
    }

    $_product = wc_get_product( $_POST['product_id'] );

    if ( !is_object( $_product ) ) {
        die( json_encode( array( 'status' => 'error' ) ) );
    }

    if ( get_post_meta( $_POST['product_id'], '_quantite_par_palette', true ) !== false ) {
        $nb_items_per_pallet = get_post_meta( $_POST['product_id'], '_quantite_par_palette', true );

        if ( $nb_items_per_pallet ) {
            $nb_pallet_required = get_nb_palette_required( $_POST['product_id'], $_POST['qty'] );
            $palette_id         = get_palette_id( (string) get_post_meta( $_POST['product_id'], '_sku', true ) );

            WC()->cart->add_to_cart( $_POST['product_id'], $_POST['qty'] );
            // WC()->cart->add_to_cart($palette_id, $nb_pallet_required);

            // Condition pour afficher la popup seulement si $nb_pallet_required est supérieur à 0
            if ( $nb_pallet_required >= 1 ) {
                die(
                    json_encode(
                        array(
                            'status'  => 'palette_popup',
                            'content' => "<style>
#popup-content {
  position: fixed;
  width: 600px;
  height: 400px;
  top: 50%;
  left: 50%;
  margin-top: -100px;
  margin-left: -250px;
  background: white;
  display: flex;
  flex-flow: column;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

#close-popup {
  position: absolute;
  top: 12px;
  right: 20px;
}

#popup-title {
  font-weight: bold;
  font-size: 22px;
}

#popup-content p {
  text-align: center;
  padding: 10px;
  width: 370px;
}

#popup-content sub {
  display: block;
  height: 50px;
  font-size: 13px;
  width: 300px;
  bottom: 0;
  bottom: 0;
  line-height: 20px;
  line-height: 20px;
  text-align: center;
}

#popup-btn button {
  background-color: #bc9c64;
  border-color: #bc9c64;
  color: white;
  font-weight: bold;
  margin-top: 12px;
  margin-bottom: 20px;
}

#popup-cart-link a {
  font-size: 14px;
  color: #bc9c64;
  text-decoration: underline;
  font-weight: 400;
}

#popup-shadow {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #0000001a;
    z-index: 0;
}

#close-popup {
    cursor: pointer;
}
</style><div id='popup-shadow'></div><div id='popup-content'><span id='close-popup'>✖</span><p id='popup-title'>Produit ajouté au panier !</p><p>En plus de votre produit, <b>une/des palettes consignée(s)</b> ont été ajoutées automatiquement à votre panier.</p><sub>(28,80 € TTC la palette, remboursable à hauteur de 20,40 € TTC par palette).</sub><div id='popup-btn'><button>J'ai compris le message</button></div><div id='popup-cart-link'><a href='/panier'>Voir mon panier</a></div></div>",
                        )
                    )
                );
            }
        }
    }

    die( json_encode( array( 'status' => 'success' ) ) );
}

/**
 * Todo: TD | Fonctions après OAUTH, déclenchées sur la thank you page pour ajout d'un event au calendrier
 */
/*
add_action('woocommerce_thankyou', 'add_outlook_event');

function add_outlook_event($order_id)
{
    if (!$order_id) {
        return false;
    }

    // Allow code execution only once
    if (!get_post_meta($order_id, '_thankyou_action_done', true)) {
        // Get an instance of the WC_Order object
        $order = wc_get_order($order_id);

        // Get the order key
        $order_key = $order->get_order_key();

        // Get the order number
        $order_key = $order->get_order_number();

        // Order is paid
        if ($order->is_paid()) {
            // Loop through order items
            foreach ($order->get_items() as $item_id => $item) {

                // Get the product object
                $product = $item->get_product();

                // Get the product Id
                $product_id = $product->get_id();

                // Get the product name
                $product_id = $item->get_name();
            }

            // Add the calendar event
            $client = getClient();
            $event = addEvent($client, $order);

            // Flag the action as done (to avoid repetitions on reload for example)
            $order->update_meta_data('_thankyou_action_done', true);
            $order->save();
        }
    }
}*/

/*
function addEvent($client, $model)
{
    $startDate = new DateTime($model->date);
    $startDate->sub(new \DateInterval('PT1H'));

    $endDate =  isset($model->ending_date) ? new DateTime($model->ending_date) : new DateTime($model->date);
    $endDate->sub(new \DateInterval('PT1H'));

    $startDate = date_format($startDate, DateTimeInterface::RFC3339);
    $endDate = date_format($endDate, DateTimeInterface::RFC3339);

    $event = [
        'subject' => $model->label,
        'start' => [
            'dateTime' => $startDate,
            'timeZone' => 'Europe/Paris',
        ],
        'end' => [
            'dateTime' => $endDate,
            'timeZone' => 'Europe/Paris',
        ],
        'body' => [
            'content' => $model->description ?? "",
            'contentType' => 'text'
        ]
    ];

    $response = $client->createRequest('POST', '/me/events')
        ->attachBody($event)
        ->setReturnType(Model\Event::class)
        ->execute();

    return true;
}*/

/*
function getClient()
{
    // Todo: put the access token here
    $accessToken = json_encode("");

    $graph = new Graph();
    $graph->setAccessToken($accessToken);

    // Todo: put the Client ID + secret + redirect url
    $client = new GenericProvider([
        'clientId' => "",
        'clientSecret' => "",
        'redirectUri' => "",
        'urlAuthorize' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
        'urlAccessToken' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
        'urlResourceOwnerDetails' => '',
        'scopes' => "openid profile offline_access user.read mailboxsettings.read calendars.readwrite"
    ]);

    // Todo: add refresh token here
    $accessToken = $client->getAccessToken('refresh_token', [
        'refresh_token' => ""
    ]);

    $graph = new Graph();
    $graph->setAccessToken($accessToken);

    return $graph;
}*/

/**
 * Todo: TD | Fonctions OAUTH Outlook
 */
/*
function oauthMicrosoft(Request $request)
{
    $client = getMicrosoftClient();

    if (isset($_GET['code']) && $_GET['code'] !== "") {
        $expectedState = session('oauthState');
        $request->session()->forget('oauthState');
        $providedState = $request->query('state');

        if (!isset($expectedState)) {
            return redirect('/');
        }

        if (!isset($providedState) || $expectedState != $providedState) {
            return redirect('/')
                ->with('error', 'Invalid auth state')
                ->with('errorDetail', 'The provided auth state did not match the expected value');
        }

        // Authorization code should be in the "code" query param
        $authCode = $request->query('code');

        if (isset($authCode)) {
            try {
                // Make the token request
                $accessToken = $client->getAccessToken('authorization_code', [
                    'code' => $authCode
                ]);

                $this->setCustomParams([
                    'access_token' => $accessToken->getToken(),
                    'refresh_token' => $accessToken->getRefreshToken(),
                    'expires_in' => $accessToken->getExpires(),
                    'created' => date('Y-m-d H:i:s'),
                ], $this->moduleSlug . "_microsoft");

                flash(trans('saas.account_details_updated'))->success();
                return redirect()->route('account.modules.index');
            } catch (IdentityProviderException $e) {
                return redirect('/')->with('error', $e->getMessage());
            }
        }

        return redirect('/')
            ->with('error', $request->query('error'))
            ->with('errorDetail', $request->query('error_description'));
    }

    $authUrl = $client->getAuthorizationUrl();

    // Save client state so we can validate in callback
    session(['oauthState' => $client->getState()]);
    return redirect()->away($authUrl);
}*/

function register_hello_world_widget( $widgets_manager ) {

    require_once __DIR__ . '/widgets/account.php';

    $widgets_manager->register( new \Account_Widget() );

}
add_action( 'elementor/widgets/register', 'register_hello_world_widget' );

/* Test git workflow */
