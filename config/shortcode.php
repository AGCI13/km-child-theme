<?php

function header_cp_func($atts = [])
{
    wp_enqueue_style('header_cp_style', get_stylesheet_directory_uri() . '/assets/css/header_cp.css');
    if (isset($atts['view'])) {
        if ($atts['view'] == 'mobile') {
            wp_enqueue_script('header_cp_script_mobile', get_stylesheet_directory_uri() . '/assets/js/header_cp_mobile.js', array('jquery'));
            get_template_part(
                '/assets/template/header_cp_mobile',
                'header_cp_mobile'
            );
        }
    }else {
        wp_enqueue_script('header_cp_script', get_stylesheet_directory_uri() . '/assets/js/header_cp.js', array('jquery'));
        get_template_part(
            '/assets/template/header_cp',
            'header_cp'
        );
    }
}
add_shortcode('header_cp', 'header_cp_func');

function archive_product_assets(): void
{
    $cate = get_queried_object();
    $vowels = array('a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U');

    if ($cate->parent != 0) {
        $category_name = get_term_by('id', $cate->parent, 'product_cat')->name;
    } else {
        $category_name = $cate->name;
    }

    echo '<input type="hidden" value="' . mb_strtolower($category_name, 'UTF-8') . '" id="cat_parent_name">';
    echo '<input type="hidden" value="' . mb_strtolower($cate->name, 'UTF-8') . '" id="cat_name">';

    if (in_array(substr($category_name, 0, 1), $vowels)) {
        echo '<input type="hidden" value="1" id="cat_vowel">';
    } else {
        echo '<input type="hidden" value="0" id="cat_vowel">';
    }

    wp_enqueue_script(
        'archive_product',
        get_stylesheet_directory_uri() . '/assets/js/archive_product.js',
        array('jquery'),
        '',
        false
    );
    wp_enqueue_style('archive_product', get_stylesheet_directory_uri() . '/assets/css/archive_product.css');
}
add_shortcode('archive_product_assets', 'archive_product_assets');


function calcul_de_tonnage($atts = []): void
{
    global $product;

    wp_enqueue_style('calcul_de_tonnage', get_stylesheet_directory_uri() . '/assets/css/calcul_de_tonnage.css');
    wp_enqueue_script('calcul_de_tonnage', get_stylesheet_directory_uri() . '/assets/js/calcul_de_tonnage.js', array('jquery'));
    get_template_part(
        '/assets/template/calcul_de_tonnage',
        'calcul_de_tonnage',
    );
}
add_shortcode('calcul_de_tonnage', 'calcul_de_tonnage');


function avis_verifie_product_page($atts = []): void
{
    $product = wc_get_product(get_the_ID());
    if (!$product){
        return;
    }
    $id_product = $product->get_id();
    $my_current_lang = '';
    $average = ntav_get_netreviews_average($id_product, $my_current_lang);
    $stars = ntav_addStars($average);
    $logoFile = content_url() . '/plugins/netreviews/includes/images/' . ntav_get_img_by_lang()['sceau_lang'];

    $html = '<div class="avis_verifie_product_page">';
    $html .= '<div><img src="'.$logoFile.'" alt="logo avis verifie"></div>';
    $html .= '<div class="netreviews_bg_stars_big headerStars" title="' . round(
            $average,
            1
        ) . '/5">';
    $html .= $stars;
    $html .= '</div>';
    $html .= ' <span itemprop="reviewCount">' . round($average, 1) . '/5</span> ';

    echo $html;
}
add_shortcode('avis_verifie', 'avis_verifie_product_page');