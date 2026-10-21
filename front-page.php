<?php
/**
 * Page d'accueil statique (IT-16) : présentation, nouveautés, sections par catégorie, retrait.
 * Les produits sont lus dans le catalogue : rien n'est écrit en dur.
 */
get_header();

$boutique = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

/**
 * Affiche une grille de produits avec les gabarits WooCommerce (mêmes cartes que la boutique).
 *
 * @param WC_Product[] $produits Produits à afficher.
 */
function gainsbarre_home_grille( $produits ) {
	global $post;
	$ancien_post = $post;
	woocommerce_product_loop_start();
	foreach ( $produits as $produit ) {
		$post = get_post( $produit->get_id() );
		setup_postdata( $post );
		wc_get_template_part( 'content', 'product' );
	}
	woocommerce_product_loop_end();
	$post = $ancien_post;
	if ( $post ) {
		setup_postdata( $post );
	}
}

// Les 3 derniers produits ajoutés au catalogue (US-16.1).
$nouveautes = wc_get_products(
	array(
		'status'  => 'publish',
		'limit'   => 3,
		'orderby' => 'date',
		'order'   => 'DESC',
	)
);

// Ce midi : deux plats de la carte du midi.
$midi_ids = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 2,
		'fields'         => 'ids',
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'tax_query'      => array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => 'midi',
			),
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => 'a-manger',
			),
		),
	)
);
$midi_noms  = array_map( 'get_the_title', $midi_ids );
$lien_midi  = get_term_link( 'midi', 'product_tag' );

$sections = array(
	array( 'cocktails', 'Cocktails', 'Les classiques de Serge Gainsbourg.', 'Voir tous les cocktails' ),
	array( 'a-manger', 'À manger', 'Un menu simple, pour déjeuner dans une atmosphère douce.', 'Voir toute la carte' ),
	array( 'sans-alcool', 'Sans alcool', 'Mocktails et jus bio, sans compromis.', 'Voir tous les sans alcool' ),
	array( 'vins', 'Vins', 'Notre sélection, au verre ou à la bouteille.', 'Voir tous les vins' ),
);

$horaires = array(
	'Lundi'    => 'Fermé',
	'Mardi'    => '10h – minuit',
	'Mercredi' => '10h – minuit',
	'Jeudi'    => '10h – 2h',
	'Vendredi' => '10h – 2h',
	'Samedi'   => '10h – 2h',
	'Dimanche' => '10h – 20h',
);
?>
<div id="primary" class="content-area">
	<main id="main" class="site-main gb-home">

		<section class="gb-home__row gb-home__hero">
			<p class="gb-home__label">Le Gainsbarre</p>
			<div>
				<h1 class="gb-home__title">Commandez, retirez au bar.</h1>
				<p>Restaurant et bar à cocktails de la Maison Gainsbourg.<br>Choisissez en ligne, récupérez sur place.</p>
				<div class="gb-home__actions">
					<a class="gb-home__button gb-home__button--primary" href="<?php echo esc_url( $boutique ); ?>">Voir la carte</a>
					<a class="gb-home__button" href="#retrait">Retrait et horaires</a>
				</div>
				<p class="gb-home__quote">“ J’aime la nuit, j’ai les idées plus claires dans le noir ! ”<br>– Serge Gainsbourg</p>
			</div>
		</section>

		<section class="gb-home__row gb-home__rule gb-home__today">
			<span class="gb-home__spacer"></span>
			<div>
				<?php if ( $midi_noms && ! is_wp_error( $lien_midi ) ) : ?>
					<div class="gb-home__today-line">
						<span class="gb-home__pill">Ce midi : <?php echo esc_html( implode( ', ', $midi_noms ) ); ?></span>
						<a href="<?php echo esc_url( $lien_midi ); ?>">Voir la carte du midi →</a>
					</div>
				<?php endif; ?>
				<ul class="gb-home__anchors">
					<?php foreach ( $sections as $s ) : ?>
						<li><a href="#<?php echo esc_attr( $s[0] ); ?>"><?php echo esc_html( $s[1] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>

		<section class="gb-home__row gb-home__rule">
			<p class="gb-home__label">Comment ça marche</p>
			<ol class="gb-home__steps">
				<li><strong>1. Choisissez</strong><span>Parcourez la carte du midi ou du soir.</span></li>
				<li><strong>2. Commandez</strong><span>Payez en ligne ou au retrait.</span></li>
				<li><strong>3. Retirez</strong><span>Au bar, 14 rue de Verneuil, sans attendre.</span></li>
			</ol>
		</section>

		<?php if ( $nouveautes ) : ?>
			<section class="gb-home__block gb-home__rule" id="nouveautes">
				<div class="gb-home__row gb-home__head">
					<p class="gb-home__label">Nouveautés</p>
					<div class="gb-home__head-text">
						<span>Les derniers arrivés à la carte.</span>
						<a href="<?php echo esc_url( $boutique ); ?>">Voir toute la carte →</a>
					</div>
				</div>
				<div class="gb-home__row gb-home__grid">
					<span class="gb-home__spacer"></span>
					<div><?php gainsbarre_home_grille( $nouveautes ); ?></div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		foreach ( $sections as $s ) :
			$terme = get_term_by( 'slug', $s[0], 'product_cat' );
			if ( ! $terme ) {
				continue;
			}
			$produits = wc_get_products(
				array(
					'status'   => 'publish',
					'limit'    => 3,
					'orderby'  => 'menu_order',
					'order'    => 'ASC',
					'category' => array( $s[0] ),
				)
			);
			if ( ! $produits ) {
				continue;
			}
			?>
			<section class="gb-home__block gb-home__rule" id="<?php echo esc_attr( $s[0] ); ?>">
				<div class="gb-home__row gb-home__head">
					<p class="gb-home__label"><?php echo esc_html( $s[1] ); ?></p>
					<div class="gb-home__head-text">
						<span><?php echo esc_html( $s[2] ); ?></span>
						<a href="<?php echo esc_url( get_term_link( $terme ) ); ?>"><?php echo esc_html( $s[3] ); ?> →</a>
					</div>
				</div>
				<div class="gb-home__row gb-home__grid">
					<span class="gb-home__spacer"></span>
					<div><?php gainsbarre_home_grille( $produits ); ?></div>
				</div>
			</section>
		<?php endforeach; ?>

		<section class="gb-home__row gb-home__rule" id="retrait">
			<p class="gb-home__label">Retrait</p>
			<div>
				<p class="gb-home__address">14 rue de Verneuil, 75007 Paris</p>
				<p>Retrait gratuit. Pièce d’identité demandée pour les boissons alcoolisées (18 ans et plus).</p>
				<dl class="gb-home__hours">
					<?php foreach ( $horaires as $jour => $heures ) : ?>
						<dt><?php echo esc_html( $jour ); ?></dt>
						<dd><?php echo esc_html( $heures ); ?></dd>
					<?php endforeach; ?>
				</dl>
			</div>
		</section>

	</main>
</div>
<?php
get_footer();
