<?php

add_action('wp_enqueue_scripts', 'hello_child_enqueue');
function hello_child_enqueue(): void
{
    $js_directory = get_theme_file_uri() . '/assets/js/';
    $css_directory = get_theme_file_uri() . '/assets/css/';

    wp_register_script('front', $js_directory . 'front.js', 'jquery', '1.0');
    wp_register_script('footer', $js_directory . 'footer.js', 'jquery', '1.0', true);
    wp_register_style('parent-style', $css_directory. 'style.css');

    wp_enqueue_script('front');
    wp_enqueue_script('footer');
    wp_enqueue_style('parent-style');

    wp_enqueue_style('style-child', get_stylesheet_directory_uri() . '/assets/css/style.css');
    wp_enqueue_style('', get_template_directory_uri() . '/style.css');
    wp_localize_script('footer', 'frontend_ajax_object',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        )
    );
}


function loadScripts(): void
{
    global $post;
    if (is_page() || is_single()) {
        if (is_checkout()){
            wp_enqueue_script(
                'checkout',
                get_theme_root_uri() . '/hello-elementor-child/assets/js/checkout.js',
                array('jquery'),
                '',
                false
            );
        }
        /*
        if ($post->ID == '142') {
            wp_enqueue_script(
                'forfait',
                get_theme_root_uri() . '/child_theme/assets/js/forfait.js',
                array('jquery'),
                '',
                false
            );
        }*/
    }
}
add_action('wp_enqueue_scripts', 'loadScripts');


function mon_theme_child_load_textdomain(): void
{
    load_child_theme_textdomain( 'hello-elementor-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'mon_theme_child_load_textdomain' );

function ajouter_css_backend() {
    wp_enqueue_style('custom-backend', get_stylesheet_directory_uri() . '/assets/css/custom-backend.css');
}
add_action('admin_enqueue_scripts', 'ajouter_css_backend');


