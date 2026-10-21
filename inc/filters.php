<?php
/**
 * Filtres de la boutique (IT-18) : prix (widget du thème) et taille (attribut WooCommerce).
 * Les widgets se placent dans la colonne de gauche depuis Apparence > Widgets.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tranches de prix proposées : libellé => array( minimum, maximum ). Un maximum à 0 = sans limite.
 */
function gainsbarre_tranches_prix() {
	return apply_filters(
		'gainsbarre_tranches_prix',
		array(
			'Moins de 10 €' => array( 0, 9.99 ),
			'De 10 € à 19 €' => array( 10, 19.99 ),
			'De 20 € à 99 €' => array( 20, 99.99 ),
			'100 € et plus'  => array( 100, 0 ),
		)
	);
}

class Gainsbarre_Widget_Prix extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'gainsbarre_prix',
			'Gainsbarre : filtre par prix',
			array( 'description' => 'Liste de tranches de prix pour filtrer la boutique.' )
		);
	}

	public function widget( $args, $instance ) {
		if ( ! ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) ) {
			return;
		}
		$titre   = ! empty( $instance['title'] ) ? $instance['title'] : 'Prix';
		$base    = remove_query_arg( array( 'min_price', 'max_price', 'paged' ) );
		$minimum = isset( $_GET['min_price'] ) ? (float) $_GET['min_price'] : null;
		$maximum = isset( $_GET['max_price'] ) ? (float) $_GET['max_price'] : null;

		echo $args['before_widget'];
		echo $args['before_title'] . esc_html( $titre ) . $args['after_title'];
		echo '<ul class="gb-prix">';
		foreach ( gainsbarre_tranches_prix() as $libelle => $tranche ) {
			$lien = $base;
			$lien = add_query_arg( 'min_price', $tranche[0], $lien );
			if ( $tranche[1] > 0 ) {
				$lien = add_query_arg( 'max_price', $tranche[1], $lien );
			}
			$actif = null !== $minimum && (float) $tranche[0] === $minimum && ( $tranche[1] > 0 ? (float) $tranche[1] === $maximum : null === $maximum );
			printf(
				'<li class="%s"><a href="%s">%s</a></li>',
				$actif ? 'chosen' : '',
				esc_url( $lien ),
				esc_html( $libelle )
			);
		}
		echo '</ul>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$titre = isset( $instance['title'] ) ? $instance['title'] : 'Prix';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Titre</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $titre ); ?>">
		</p>
		<?php
	}

	public function update( $nouveau, $ancien ) {
		return array( 'title' => sanitize_text_field( $nouveau['title'] ) );
	}
}

add_action( 'widgets_init', function () {
	register_widget( 'Gainsbarre_Widget_Prix' );
} );
