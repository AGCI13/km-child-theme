<?php

add_action( 'wp_ajax_calcul_tonnage', 'km_calcul_tonnage' );
add_action( 'wp_ajax_nopriv_calcul_tonnage', 'km_calcul_tonnage' );
function km_calcul_tonnage() {
    if ( !isset( $_GET['lon'] ) && !isset( $_GET['lar'] ) && !isset( $_GET['epa'] ) && !isset( $_GET['den'] ) ) {
        wp_send_json_error();
    }

    $conditionnement = array(
        'BIG BAG 1.5T'  => 1500,
        'BIG BAG 1T'    => 1000,
        'BIG BAG 800KG' => 800,
        'BIG BAG 600KG' => 600,
        'BIG BAG 400KG' => 400,
        'BIG BAG 200KG' => 200,
    );

    $longueur  = (float) $_GET['lon'];
    $largeur   = (float) $_GET['lar'];
    $epaisseur = (float) $_GET['epa'];
    $densite   = (float) $_GET['den'];

    $result    = $longueur * $largeur * $epaisseur * $densite;
    $kg_result = $result /1000;

    $meilleurConditionnement = '';
    $capaciteConditionnement = 60000;

    foreach ( $conditionnement as $nomConditionnement => $capacite ) {
        if ( $kg_result <= $capacite && ( $capacite - $kg_result ) < ( $capaciteConditionnement - $kg_result ) ) {
            $meilleurConditionnement = $nomConditionnement;
            $capaciteConditionnement = $capacite;
        }
    }

    if ( $result > 1000000 ) {
        $unit   = 'T';
        $result = round( $result /1000000, 2 );
    } else {
        $unit   = 'Kg';
        $result = round( $result /1000, 2 );
    }

    wp_send_json_success(
        array(
            'lon'             => $longueur,
            'lar'             => $largeur,
            'epa'             => $epaisseur,
            'den'             => $densite,
            'res'             => $result,
            'unit'            => $unit,
            'conditionnement' => $meilleurConditionnement,
        )
    );
    die();
}
