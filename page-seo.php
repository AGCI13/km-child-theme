<?php
/**
 *  Template Name: SEO Page
 * Template Post Type: page, post
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();

while ( have_posts() ) : the_post();
?>

<main id="content" <?php post_class( 'site-main' ); ?> class="seo-page">

    <?php if ( apply_filters( 'hello_elementor_page_title', true ) ) : ?>
        <header class="page-header">
            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
        </header>
    <?php endif; ?>

    <div class="page-content">
        <?php the_content(); ?>
        <div class="post-tags">
            <?php the_tags( '<span class="tag-links">' . esc_html__( 'Tagged ', 'hello-elementor' ), null, '</span>' ); ?>
        </div>
        <?php wp_link_pages(); ?>
    </div>

    <?php comments_template(); ?>

</main>

<?php
endwhile;

get_footer();
?>