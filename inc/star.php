<?php
/**
 * Produit star (iteration W) : le produit le plus vendu de la boutique.
 * WooCommerce compte les ventes de chaque produit dans le champ total_sales
 * (mis à jour quand une commande passe en cours, en attente ou terminée).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Identifiant du produit le plus vendu, ou 0 si rien n'a encore été vendu.
 * En cas d'égalité, le produit au titre le premier par ordre alphabétique l'emporte.
 */
function gainsbarre_produit_star_id() {
	static $id = null;
	if ( null !== $id ) {
		return $id;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'total_sales',
			'meta_value'     => 0,
			'meta_compare'   => '>',
			'meta_type'      => 'NUMERIC',
			'orderby'        => array(
				'meta_value_num' => 'DESC',
				'title'          => 'ASC',
			),
		)
	);
	$id = $ids ? (int) $ids[0] : 0;
	return $id;
}

/**
 * Badge « Produit star » sur la photo : cartes de la boutique et fiche produit.
 */
function gainsbarre_star_tag() {
	global $product;
	if ( $product && gainsbarre_produit_star_id() === $product->get_id() ) {
		echo '<span class="gb-tag gb-tag--star">Produit star</span>';
	}
}
add_action( 'woocommerce_before_shop_loop_item_title', 'gainsbarre_star_tag', 17 );
add_action( 'woocommerce_before_single_product_summary', 'gainsbarre_star_tag', 19 );

/**
 * Colonne « Ventes » dans la liste des produits du back office : le produit star y est signalé.
 */
function gainsbarre_star_colonne( $colonnes ) {
	$colonnes['gb_ventes'] = 'Ventes';
	return $colonnes;
}
add_filter( 'manage_edit-product_columns', 'gainsbarre_star_colonne' );

function gainsbarre_star_colonne_contenu( $colonne, $produit_id ) {
	if ( 'gb_ventes' !== $colonne ) {
		return;
	}
	echo esc_html( (int) get_post_meta( $produit_id, 'total_sales', true ) );
	if ( gainsbarre_produit_star_id() === (int) $produit_id ) {
		echo ' <strong>★ Produit star</strong>';
	}
}
add_action( 'manage_product_posts_custom_column', 'gainsbarre_star_colonne_contenu', 10, 2 );
