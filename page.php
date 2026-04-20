<?php
/**
 * Template Name: Aero Greet
 * Template Post Type: page
 * 
 * This is a WordPress page template that supports Elementor for the Aero Greet India theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();

if ( have_posts() ) : while ( have_posts() ) : the_post();
    the_content();
endwhile; endif;

get_footer();
