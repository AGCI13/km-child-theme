<?php

/**
 * Rends le code postal non-modifiable dans le tunnel de commande
 *
 * @param $fields
 * @return array
 */
function custom_override_checkout_fields($fields): array
{
    $fields['billing']['billing_postcode']['custom_attributes'] = array('readonly' => 'readonly');
    return $fields;
}

add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');


/**
 * Rends le code postal non modifiable dans l'adresse de livraison woocommerce
 *
 * @param $address_fields
 * @return array
 */
function custom_override_default_address_fields($address_fields): array
{
    // Vérifie si l'utilisateur est sur la page de livraison
    if (is_wc_endpoint_url('edit-address') && isset($_GET['address']) && $_GET['address'] === 'shipping') {
        $address_fields['postcode']['custom_attributes'] = array('readonly' => 'readonly');
    }
    return $address_fields;
}

add_filter('woocommerce_default_address_fields', 'custom_override_default_address_fields');

/**
 * Remplit automatiquement le champ code postal avec le cookie
 *
 * @return void
 */
function custom_override_checkout_init(): void
{
    if (isset($_COOKIE['zip_code'])) {
        $zip_code = explode('-', $_COOKIE['zip_code'])[0];
        $_POST['billing_postcode'] = $zip_code;
    }
}
add_action('woocommerce_checkout_init', 'custom_override_checkout_init');


function price_format($price)
{
    $tmp_price = htmlentities($price);
    $tmp = explode('ndash', $tmp_price);
    if (count($tmp) > 1) {
        $tmp[0] = substr($tmp[0], 0, -6);
        $tmp[1] = substr($tmp[1], 1);
        return html_entity_decode('de ' . $tmp[0] . ' à ' . $tmp[1]);
    } else {
        return $price;
    }
}
add_filter('woocommerce_get_price_html', 'price_format');



add_filter('woocommerce_package_rates', 'supprimer_option_livraison', 10, 2);
function supprimer_option_livraison($available_shipping_methods, $package) {
    // Remplacez 'nom_option_a_supprimer' par le nom de l'option que vous souhaitez supprimer.
    $option_a_supprimer = 'local_pickup_plus';

    $methods_to_remove = array();

    foreach($available_shipping_methods as $method_key => $method) {
        if(strpos($method->get_id(), $option_a_supprimer) !== false) {
            $methods_to_remove[] = $method_key;
        }
    }

    foreach ($methods_to_remove as $method_key) {
        unset($available_shipping_methods[$method_key]);
    }

    return $available_shipping_methods;
}