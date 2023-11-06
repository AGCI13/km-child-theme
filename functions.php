<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require_once 'config/ajax.php';
require_once 'config/enqueue.php';
require_once 'config/filter.php';
require_once 'config/shortcodes.php';
require_once 'config/atoosync.php';

require_once 'widgets/register-widgets.php';

require_once 'includes/wc-order-functions.php';

require_once 'includes/class-singleton-trait.php';
require_once 'includes/class-shipping-zone.php';
require_once 'includes/class-delivery-options.php';
require_once 'includes/class-dynamic-pricing.php';

$km_dynamic_pricing  = KM_Dynamic_Pricing::get_instance();
$km_delivery_options = KM_Delivery_Options::get_instance();

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


function km_elementor_archive_posts_query( $query ) {
    if ( is_admin() || !$query->is_main_query() ) {
        return;
    }

    if ( is_search() ) {
        $query->set( 'post_type', 'product' );
    }
}
add_action( 'pre_get_posts', 'km_elementor_archive_posts_query' );
