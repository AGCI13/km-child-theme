<?php

function km_header_cp_func( $atts = array() ) {
    wp_enqueue_style( 'km-header-postcode-style' );
    if ( isset( $atts['view'] ) && $atts['view'] === 'mobile' ) {
        wp_enqueue_script( 'header-cp-mobile-script' );
        get_template_part( '/templates/header_cp_mobile', 'header_cp_mobile' );
    } else {
        wp_enqueue_script( 'km-header-postcode-script' );
        get_template_part( '/templates/header-cp', 'header-cp' );
    }
}
add_shortcode( 'header_cp', 'km_header_cp_func' );

function km_archive_product_assets(): void {
    $cate   = get_queried_object();
    $vowels = array( 'a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U' );

    if ( $cate->parent != 0 ) {
        $category_name = get_term_by( 'id', $cate->parent, 'product_cat' )->name;
    } else {
        $category_name = $cate->name;
    }

    echo '<input type="hidden" value="' . mb_strtolower( $category_name, 'UTF-8' ) . '" id="cat_parent_name">';
    echo '<input type="hidden" value="' . mb_strtolower( $cate->name, 'UTF-8' ) . '" id="cat_name">';

    if ( in_array( substr( $category_name, 0, 1 ), $vowels ) ) {
        echo '<input type="hidden" value="1" id="cat_vowel">';
    } else {
        echo '<input type="hidden" value="0" id="cat_vowel">';
    }

    wp_enqueue_script( 'km-product-archive-script' );
    wp_enqueue_style( 'km-product-archive-style' );
}
add_shortcode( 'archive_product_assets', 'km_archive_product_assets' );


function km_tonnage_calculator( $atts = array() ): void {
    global $product;
    wp_enqueue_style( 'km-tonnage-calculator-style' );
    wp_enqueue_script( 'km-tonnage-calculator-script' );
    get_template_part( '/templates/tonnage-calculator', 'tonnage-calculator', );
}
add_shortcode( 'tonnage_calculator', 'km_tonnage_calculator' );


function km_avis_verifie_product_page( $atts = array() ): void {
    $product = wc_get_product( get_the_ID() );
    if ( !$product ) {
        return;
    }
    $id_product      = $product->get_id();
    $my_current_lang = '';
    $average         = ntav_get_netreviews_average( $id_product, $my_current_lang );
    $stars           = ntav_addStars( $average );
    $logo            = content_url() . '/plugins/netreviews/includes/images/' . ntav_get_img_by_lang()['sceau_lang'];

    $html  = '<div class="avis_verifie_product_page">';
    $html .= '<div><img src="' . $logo . '" alt="logo avis verifie"></div>';
    $html .= '<div class="netreviews_bg_stars_big headerStars" title="' . round(
        $average,
        1
    ) . '/5">';
    $html .= $stars;
    $html .= '</div>';
    $html .= ' <span itemprop="reviewCount">' . round( $average, 1 ) . '/5</span> ';

    echo $html;
}
add_shortcode( 'avis_verifie', 'km_avis_verifie_product_page' );
