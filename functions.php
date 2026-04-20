<?php
/**
 * Functions.php for Aero Greet India WordPress Theme
 */

// Enqueue scripts and styles
function aerogreet_enqueue_scripts() {
    wp_enqueue_style('style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'aerogreet_enqueue_scripts');

// Custom post type for Airports Services
function aerogreet_airports_services_post_type() {
    register_post_type('airports_services', [
        'labels' => [
            'name' => __('Airports Services'),
            'singular_name' => __('Airport Service'),
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'rewrite' => ['slug' => 'airports-services'],
    ]);
}
add_action('init', 'aerogreet_airports_services_post_type');

// Custom post type for Testimonials
function aerogreet_testimonials_post_type() {
    register_post_type('testimonials', [
        'labels' => [
            'name' => __('Testimonials'),
            'singular_name' => __('Testimonial'),
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'rewrite' => ['slug' => 'testimonials'],
    ]);
}
add_action('init', 'aerogreet_testimonials_post_type');

// Elementor integration
function aerogreet_elementor_init() {
    // Add custom widget or settings for Elementor
}
add_action('elementor/widgets/widgets_registered', 'aerogreet_elementor_init');

// Register widget areas
function aerogreet_widgets_init() {
    register_sidebar([
        'name' => __('Main Sidebar'),
        'id' => 'main-sidebar',
        'before_widget' => '<div class="widget">',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ]);
}
add_action('widgets_init', 'aerogreet_widgets_init');

// Security features
function aerogreet_security_features() {
    // Implement security features like disabling file editing, etc.
    define('DISALLOW_FILE_EDIT', true);
}
aerogreet_security_features();
