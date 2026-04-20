<?php
/**
 * The footer for our theme.
 * This is the footer template for the Aero Greet India theme.
 */

?>

<footer>
    <div class="footer-widgets">
        <div class="widget">
            <?php dynamic_sidebar('footer-1'); ?>
        </div>
        <div class="widget">
            <?php dynamic_sidebar('footer-2'); ?>
        </div>
        <div class="widget">
            <?php dynamic_sidebar('footer-3'); ?>
        </div>
    </div>
    <div class="copyright">
        &copy; <?php echo date('Y'); ?> Aero Greet India. All rights reserved.
    </div>
</footer>
