<?php
/**
 * Performances (iteration Y) : alléger les pages en retirant ce que le site n'utilise pas.
 * Les mesures viennent du rapport Lighthouse (page produit, mobile).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retire les feuilles de style de Storefront et de WooCommerce que le thème ne reprend pas :
 * styles des blocs Gutenberg, marques de produits et visionneuse photo.
 */
function gainsbarre_retirer_styles_inutiles() {
	foreach ( array( 'storefront-gutenberg-blocks', 'storefront-woocommerce-brands-style', 'photoswipe', 'photoswipe-default-skin' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'gainsbarre_retirer_styles_inutiles', 200 );

/**
 * Une seule photo par produit : ni zoom, ni diaporama, ni visionneuse (trois scripts en moins).
 */
function gainsbarre_galerie_simple() {
	remove_theme_support( 'wc-product-gallery-zoom' );
	remove_theme_support( 'wc-product-gallery-lightbox' );
	remove_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'gainsbarre_galerie_simple', 20 );

/**
 * Retire jQuery Migrate (compatibilité avec d'anciens scripts, inutile ici).
 */
function gainsbarre_sans_jquery_migrate( $scripts ) {
	if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
		$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
	}
}
add_action( 'wp_default_scripts', 'gainsbarre_sans_jquery_migrate' );

/**
 * Retire la barre « Ajouter au panier » collante de Storefront (script et balisage).
 */
function gainsbarre_sans_barre_collante() {
	remove_action( 'storefront_after_footer', 'storefront_sticky_single_add_to_cart', 999 );
	wp_dequeue_script( 'storefront-sticky-add-to-cart' );
}
add_action( 'wp_enqueue_scripts', 'gainsbarre_sans_barre_collante', 200 );
