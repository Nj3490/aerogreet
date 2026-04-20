<?php
/**
 * The main template file for the Aero Greet India theme.
 */

get_header();

if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        the_content();
    endwhile;
else :
    echo '<h2>No content found</h2>';
endif;

get_footer();
