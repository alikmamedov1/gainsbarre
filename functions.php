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
		wp_enqueue_script( 'gainsbarre-variation', $uri . '/js/variation.js', array( 'jquery' ), $version, true );
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

	// Pagination : une seule, sous la grille (celle du haut fait doublon), hors du cadre « tri » de Storefront.
	remove_action( 'woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30 );
	remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper', 9 );
	remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper_close', 31 );
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
   4 bis. Étiquette de stock sur la carte produit : Disponible / Stock faible / Épuisé
   Même composant que l'étiquette de service, en bas à gauche de la photo (US-3.1).
   -------------------------------------------------------------------------- */
/**
 * Niveau de stock d'un produit : 'epuise', 'faible' ou 'disponible'.
 * Produit variable : épuisé si aucune variante n'est en stock, faible si toutes les variantes
 * en stock sont sous le seuil WooCommerce, sinon disponible.
 */
function gainsbarre_stock_level( $product ) {
	if ( $product->is_type( 'variable' ) ) {
		$en_stock = 0;
		$faibles  = 0;
		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation || 'publish' !== $variation->get_status() || ! $variation->is_in_stock() ) {
				continue;
			}
			++$en_stock;
			$quantite = $variation->get_stock_quantity();
			if ( $variation->managing_stock() && null !== $quantite && $quantite <= wc_get_low_stock_amount( $variation ) ) {
				++$faibles;
			}
		}
		if ( 0 === $en_stock ) {
			return 'epuise';
		}
		return ( $faibles === $en_stock ) ? 'faible' : 'disponible';
	}

	if ( ! $product->is_in_stock() ) {
		return 'epuise';
	}
	$quantite = $product->get_stock_quantity();
	if ( $product->managing_stock() && null !== $quantite && $quantite <= wc_get_low_stock_amount( $product ) ) {
		return 'faible';
	}
	return 'disponible';
}

function gainsbarre_stock_tag_html( $product ) {
	$niveau   = gainsbarre_stock_level( $product );
	$libelles = array(
		'disponible' => 'Disponible',
		'faible'     => 'Stock faible',
		'epuise'     => 'Épuisé',
	);
	return '<span class="gb-tag gb-tag--stock gb-tag--' . esc_attr( $niveau ) . '">' . esc_html( $libelles[ $niveau ] ) . '</span>';
}

function gainsbarre_stock_tag() {
	global $product;
	if ( $product ) {
		echo gainsbarre_stock_tag_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput -- déjà échappé
	}
}

// Carte produit : la photo et ses étiquettes (service en haut, stock en bas) sont dans un même cadre,
// pour que les étiquettes restent sur la photo quelle que soit la largeur de l'écran.
add_action( 'woocommerce_before_shop_loop_item_title', function () {
	echo '<div class="gb-card-media">';
}, 5 );
add_action( 'woocommerce_before_shop_loop_item_title', 'gainsbarre_stock_tag', 16 );
add_action( 'woocommerce_before_shop_loop_item_title', function () {
	echo '</div>';
}, 20 );

// Fiche produit : étiquette de stock en bas à gauche de la photo principale (dans la galerie).
add_filter( 'woocommerce_single_product_image_thumbnail_html', function ( $html ) {
	static $deja_ajoutee = false;
	global $product;
	if ( $deja_ajoutee || ! $product || ! is_product() ) {
		return $html;
	}
	$deja_ajoutee = true;
	$etiquette    = gainsbarre_stock_tag_html( $product );
	$position     = strrpos( $html, '</div>' );
	return false === $position ? $html . $etiquette : substr_replace( $html, $etiquette, $position, 0 );
} );

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

/* --------------------------------------------------------------------------
   7. Produits variables (sélecteur d'options, prix, stock)
   -------------------------------------------------------------------------- */
// 12 produits par page, comme la maquette.
add_filter( 'loop_shop_per_page', function () {
	return 12;
}, 20 );

// Carte produit : « Choisir les options » au lieu de « Sélectionner les options ».
add_filter( 'woocommerce_product_add_to_cart_text', function ( $texte, $product ) {
	return $product && $product->is_type( 'variable' ) ? 'Choisir les options' : $texte;
}, 10, 2 );

// Prix d'un produit variable : « dès 17 € » quand les variantes n'ont pas toutes le même prix.
add_filter( 'woocommerce_variable_price_html', function ( $html, $product ) {
	$min = $product->get_variation_price( 'min', true );
	$max = $product->get_variation_price( 'max', true );
	if ( '' === $min ) {
		return $html;
	}
	$prix = wc_price( $min ) . $product->get_price_suffix();
	return ( $min !== $max ) ? 'dès ' . $prix : $prix;
}, 10, 2 );

// Lien d'annulation du choix : « Réinitialiser » comme sur la maquette.
add_filter( 'woocommerce_reset_variations_link', function () {
	return '<a class="reset_variations" href="#" aria-label="Réinitialiser le choix">Réinitialiser</a>';
} );

// Niveaux de stock d'une variante : En stock / Plus que N en stock (seuil WooCommerce) / Épuisé.
add_filter( 'woocommerce_get_availability', function ( $disponibilite, $product ) {
	if ( ! $product instanceof WC_Product_Variation || $product->is_on_backorder() ) {
		return $disponibilite;
	}
	if ( ! $product->is_in_stock() ) {
		return array(
			'availability' => 'Épuisé',
			'class'        => 'out-of-stock',
		);
	}
	$quantite = $product->get_stock_quantity();
	if ( $product->managing_stock() && null !== $quantite && $quantite <= wc_get_low_stock_amount( $product ) ) {
		return array(
			/* translators: %d: quantité restante */
			'availability' => sprintf( 'Plus que %d en stock', $quantite ),
			'class'        => 'in-stock gb-stock--low',
		);
	}
	return array(
		'availability' => 'En stock',
		'class'        => 'in-stock',
	);
}, 10, 2 );

// Produit sans photo : zone grise « Photo à ajouter » (maquette), côté visiteur seulement.
add_filter( 'woocommerce_placeholder_img_src', function ( $src ) {
	return is_admin() ? $src : get_stylesheet_directory_uri() . '/assets/photo-a-ajouter.svg';
} );

/* --------------------------------------------------------------------------
   8. Informations produit (champs SCF) : Composition, Allergènes, Valeurs nutritionnelles
   Les champs sont créés par le groupe « Informations produit » (script create-fields-it4.php),
   affichés sur la fiche produit AVANT le prix (US-4.3), seulement quand ils sont renseignés.
   -------------------------------------------------------------------------- */
function gainsbarre_product_info() {
	if ( ! function_exists( 'get_field' ) ) {
		return; // Secure Custom Fields désactivé : on n'affiche rien plutôt que de provoquer une erreur.
	}
	$produit_id = get_the_ID();
	$champs     = array(
		'composition'             => 'Composition',
		'allergenes'              => 'Allergènes',
		'valeurs_nutritionnelles' => 'Valeurs nutritionnelles',
	);
	$lignes = '';
	foreach ( $champs as $nom => $libelle ) {
		$valeur = trim( (string) get_field( $nom, $produit_id, false ) ); // valeur brute, échappée ci-dessous
		if ( '' === $valeur ) {
			continue;
		}
		$lignes .= '<div class="gb-product-info__row"><dt class="gb-product-info__label">' . esc_html( $libelle ) . '</dt>'
			. '<dd class="gb-product-info__value">' . nl2br( esc_html( $valeur ) ) . '</dd></div>';
	}
	if ( '' !== $lignes ) {
		echo '<dl class="gb-product-info">' . $lignes . '</dl>'; // $lignes est déjà échappé
	}
}
// Titre = 5, prix = 10 : priorité 8 pour passer entre les deux.
add_action( 'woocommerce_single_product_summary', 'gainsbarre_product_info', 8 );

/* --------------------------------------------------------------------------
   9. Instructions pour le retrait : texte modifiable dans le back office (US-6.1),
   affiché dans le formulaire de commande (US-6.2).
   Réglage : menu WooCommerce > Instructions de retrait (option gainsbarre_pickup_instructions).
   -------------------------------------------------------------------------- */
function gainsbarre_pickup_instructions_default() {
	return 'Retrait gratuit au 14 rue de Verneuil, 75007 Paris. Présentez votre numéro de commande au comptoir. Une pièce d’identité est demandée pour les boissons alcoolisées (18 ans et plus).';
}

function gainsbarre_pickup_instructions_text() {
	$texte = trim( (string) get_option( 'gainsbarre_pickup_instructions', '' ) );
	return '' === $texte ? gainsbarre_pickup_instructions_default() : $texte;
}

// Back office : page de réglage sous le menu WooCommerce.
add_action( 'admin_menu', function () {
	add_submenu_page(
		'woocommerce',
		'Instructions pour le retrait',
		'Instructions de retrait',
		'manage_woocommerce',
		'gainsbarre-retrait',
		'gainsbarre_pickup_instructions_page'
	);
} );

add_action( 'admin_init', function () {
	register_setting(
		'gainsbarre_retrait',
		'gainsbarre_pickup_instructions',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'wp_kses_post',
			'default'           => '',
		)
	);
} );

function gainsbarre_pickup_instructions_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Instructions pour le retrait</h1>
		<p>Ce texte s’affiche sous le titre « Instructions pour le retrait » dans le formulaire de commande, quand le client choisit le retrait en boutique.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'gainsbarre_retrait' ); ?>
			<?php
			wp_editor(
				gainsbarre_pickup_instructions_text(),
				'gainsbarre_pickup_instructions',
				array(
					'textarea_name' => 'gainsbarre_pickup_instructions',
					'textarea_rows' => 8,
					'media_buttons' => false,
					'teeny'         => true,
				)
			);
			?>
			<?php submit_button( 'Enregistrer' ); ?>
		</form>
	</div>
	<?php
}

// Formulaire de commande (bloc WooCommerce) : le texte est inséré après « Lieux de retrait »,
// avec les classes des étapes du bloc pour garder le même style de titre.
add_filter( 'render_block_woocommerce/checkout', function ( $contenu ) {
	$bloc = '<div class="gb-pickup-instructions wc-block-components-checkout-step">'
		. '<div class="wc-block-components-checkout-step__heading"><h2 class="wc-block-components-title wc-block-components-checkout-step__title">Instructions pour le retrait</h2></div>'
		. '<div class="wc-block-components-checkout-step__container"><div class="wc-block-components-checkout-step__content">'
		. wp_kses_post( wpautop( gainsbarre_pickup_instructions_text() ) )
		. '</div></div></div>';

	// Après « Lieux de retrait » ; à défaut, avant le paiement.
	$apres = '#(<div[^>]*data-block-name="woocommerce/checkout-pickup-options-block"[^>]*></div>)#';
	if ( preg_match( $apres, $contenu ) ) {
		return preg_replace( $apres, '$1' . $bloc, $contenu, 1 );
	}
	$avant = '#(<div[^>]*data-block-name="woocommerce/checkout-payment-block"[^>]*></div>)#';
	if ( preg_match( $avant, $contenu ) ) {
		return preg_replace( $avant, $bloc . '$1', $contenu, 1 );
	}
	return $contenu;
}, 20 );
