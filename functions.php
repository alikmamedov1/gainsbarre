<?php
/**
 * Thème enfant Gainsbarre (parent : Storefront)
 * Préfixe des classes et fonctions personnalisées : gb- / gainsbarre_
 */

/* --------------------------------------------------------------------------
   1. Styles et scripts
   Priorité 100 : après Storefront (30) et WooCommerce, pour que nos règles l'emportent.
   -------------------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
	$version = wp_get_theme()->get( 'Version' );
	$uri     = get_stylesheet_directory_uri();

	// Police de Storefront (Source Sans Pro) inutile : on utilise Anonymous Pro (base.css).
	wp_dequeue_style( 'storefront-fonts' );
	wp_dequeue_style( 'source-sans-pro' );

	$fichiers = array( 'variables', 'base', 'layout', 'components', 'shop', 'product' );
	$parent   = 'storefront-style';
	foreach ( $fichiers as $fichier ) {
		$handle = 'gainsbarre-' . $fichier;
		wp_enqueue_style( $handle, $uri . '/css/' . $fichier . '.css', array( $parent ), $version );
		$parent = $handle; // chaque fichier dépend du précédent : ordre garanti
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_enqueue_script( 'gainsbarre-quantity', $uri . '/js/quantity.js', array(), $version, true );
	}
}, 100 );

/* --------------------------------------------------------------------------
   2. En-tête : logo SVG à la place du titre, sans la recherche
   -------------------------------------------------------------------------- */
add_action( 'init', function () {
	remove_action( 'storefront_header', 'storefront_site_branding', 20 );
	remove_action( 'storefront_header', 'storefront_product_search', 40 );
	add_action( 'storefront_header', 'gainsbarre_site_branding', 20 );

	// Pied de page : remplace les widgets et le crédit Storefront.
	remove_action( 'storefront_footer', 'storefront_footer_widgets', 10 );
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
	add_action( 'storefront_footer', 'gainsbarre_footer', 10 );

	// Boutique : Storefront répète la liste de tri et le nombre de résultats sous la grille
	// (doublon visible en bas de page). On les retire en bas ; la pagination reste.
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
	// Le nombre de résultats n'existe pas sur la maquette : on ne garde que la liste de tri.
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
}, 20 );

/**
 * Lien du panier de l'en-tête, au format de la maquette : « 2 – 16,00 € ».
 * Cette fonction remplace celle de Storefront (qui est « pluggable » : le thème enfant
 * est chargé en premier). Elle sert aussi à la mise à jour AJAX du panier.
 */
function storefront_cart_link() {
	if ( ! storefront_woo_cart_available() ) {
		return;
	}
	$nombre   = WC()->cart->get_cart_contents_count();
	$sous_tot = WC()->cart->get_cart_subtotal();
	/* translators: 1: nombre d'articles, 2: total */
	$libelle = sprintf( _n( 'Panier : %1$d article, %2$s', 'Panier : %1$d articles, %2$s', $nombre, 'gainsbarre' ), $nombre, wp_strip_all_tags( $sous_tot ) );
	?>
	<a class="cart-contents" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php echo esc_attr( $libelle ); ?>">
		<span class="count"><?php echo (int) $nombre; ?></span>
		<span class="gb-cart__separator" aria-hidden="true">–</span>
		<span class="gb-cart__total"><?php echo wp_kses_post( $sous_tot ); ?></span>
	</a>
	<?php
}

/* Prix des produits sans « ,00 » (« 18 € » comme sur la maquette). Panier et paiement gardent les centimes. */
add_filter( 'woocommerce_get_price_html', function ( $html ) {
	$sep = preg_quote( wc_get_price_decimal_separator(), '/' );
	return preg_replace( '/' . $sep . '0+(?=(&nbsp;|\s|\x{00A0}|<))/u', '', $html );
}, 20 );

function gainsbarre_site_branding() {
	$assets = get_stylesheet_directory_uri() . '/assets/';
	?>
	<div class="site-branding">
		<a class="gb-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php esc_attr_e( 'Maison Gainsbourg - Accueil', 'gainsbarre' ); ?>">
			<picture>
				<source media="(max-width: 47.99em)" srcset="<?php echo esc_url( $assets . 'logo-primary-mobile.svg' ); ?>">
				<img class="gb-logo__image" src="<?php echo esc_url( $assets . 'logo-primary.svg' ); ?>" alt="Maison Gainsbourg" width="442" height="20">
			</picture>
		</a>
	</div>
	<?php
}

/* --------------------------------------------------------------------------
   3. Pied de page : signature, coordonnées, mentions, newsletter, mention 18+
   -------------------------------------------------------------------------- */
function gainsbarre_footer() {
	$assets = get_stylesheet_directory_uri() . '/assets/';
	?>
	<div class="gb-footer">
		<hr class="gb-divider">
		<img class="gb-footer__logo" src="<?php echo esc_url( $assets . 'logo-secondary.svg' ); ?>" alt="Maison Gainsbourg" width="433" height="266">
		<hr class="gb-divider">
		<div class="gb-footer__columns">
			<div class="gb-footer__column">
				<h2 class="gb-footer__title">Maison Gainsbourg</h2>
				<address class="gb-footer__text">14 rue de Verneuil<br>75007 Paris</address>
			</div>
			<div class="gb-footer__column">
				<h2 class="gb-footer__title">Mentions légales</h2>
				<ul class="gb-footer__list">
					<li>CGV</li>
					<li>Politique de confidentialité</li>
				</ul>
			</div>
			<div class="gb-footer__column">
				<h2 class="gb-footer__title">Newsletter</h2>
				<p class="gb-footer__text">Inscrivez-vous à notre newsletter pour recevoir nos actualités</p>
			</div>
		</div>
		<p class="gb-footer__legal">Vente d’alcool interdite aux mineurs de moins de 18 ans.</p>
	</div>
	<?php
}

/* --------------------------------------------------------------------------
   4. Étiquette de service MIDI / SOIR (étiquettes produit « midi » et « soir »)
   -------------------------------------------------------------------------- */
function gainsbarre_service_label( $product_id ) {
	$midi = has_term( 'midi', 'product_tag', $product_id );
	$soir = has_term( 'soir', 'product_tag', $product_id );
	if ( $midi && $soir ) {
		return 'Midi - Soir';
	}
	if ( $midi ) {
		return 'Midi';
	}
	if ( $soir ) {
		return 'Soir';
	}
	return '';
}

function gainsbarre_service_tag() {
	global $product;
	if ( ! $product ) {
		return;
	}
	$label = gainsbarre_service_label( $product->get_id() );
	if ( '' !== $label ) {
		echo '<span class="gb-tag">' . esc_html( $label ) . '</span>';
	}
}
// Carte (boutique) : après la miniature (10). Fiche produit : au-dessus de la galerie (20).
add_action( 'woocommerce_before_shop_loop_item_title', 'gainsbarre_service_tag', 15 );
add_action( 'woocommerce_before_single_product_summary', 'gainsbarre_service_tag', 19 );

/* --------------------------------------------------------------------------
   5. Carte produit : description courte sous le titre
   -------------------------------------------------------------------------- */
add_action( 'woocommerce_after_shop_loop_item_title', function () {
	global $product;
	$texte = $product ? $product->get_short_description() : '';
	if ( $texte ) {
		echo '<p class="gb-product-description">' . wp_kses_post( wp_strip_all_tags( $texte ) ) . '</p>';
	}
}, 5 );

/* --------------------------------------------------------------------------
   6. Fiche produit
   -------------------------------------------------------------------------- */
// Bloc retrait sous le bouton « Ajouter au panier ».
add_action( 'woocommerce_single_product_summary', function () {
	echo '<p class="gb-pickup-notice">Retrait gratuit au 14 rue de Verneuil</p>';
}, 35 );

// Pas de référence (UGS) visible côté client.
add_filter( 'wc_product_sku_enabled', function ( $enabled ) {
	return is_admin() ? $enabled : false;
} );

// Titre de la section des produits associés.
add_filter( 'woocommerce_product_related_products_heading', function () {
	return 'Vous aimerez aussi';
} );

// Trois produits associés, sur trois colonnes.
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 3;
	$args['columns']        = 3;
	return $args;
} );
