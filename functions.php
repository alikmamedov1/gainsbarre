<?php
/**
 * Thème enfant Gainsbarre : chargement des styles.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'gainsbarre-style',
		get_stylesheet_uri(),
		array( 'storefront-style' ),
		wp_get_theme()->get( 'Version' )
	);
} );