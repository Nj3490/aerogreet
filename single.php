<?php
/**
 * The template for displaying single posts.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Aero_Greet_India
 */

get_header();
?>

<main id="main" class="site-main">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <header class="entry-header">
            <?php
            the_title( '<h1 class="entry-title">', '</h1>' );
            if ( has_post_thumbnail() ) {
                the_post_thumbnail();
            }
            ?>
        </header><!-- .entry-header -->

        <div class="entry-content">
            <?php
            the_content();
            ?>
        </div><!-- .entry-content -->

        <footer class="entry-footer">
            <?php
            // Optionally display post meta (author, date, categories, etc.)
            the_post_navigation();
            ?>
        </footer><!-- .entry-footer -->
    </article><!-- #post-<?php the_ID(); ?> -->
</main><!-- #main -->

<?php
get_footer();
?>
