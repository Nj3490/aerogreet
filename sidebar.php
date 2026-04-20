<?php

// Register widget area
function aerogreet_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar Widget Area', 'aerogreet' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Widgets in this area will be shown in the sidebar.', 'aerogreet' ),
        'before_widget' => '<div class="widget %s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'aerogreet_widgets_init' );

?>