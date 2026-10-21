<?php
/**
 * Créneaux de livraison et de retrait (IT-20).
 * Champs ajoutés à l'étape « Informations sur la commande » du formulaire de commande WooCommerce :
 * livraison : jour et plage horaire ; retrait en boutique (click and collect) : jour et plage horaire.
 * Le client ne voit que les champs qui correspondent au mode choisi.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plages horaires proposées.
 */
function gainsbarre_plages_horaires() {
	return apply_filters(
		'gainsbarre_plages_horaires',
		array( '10h – 12h', '12h – 14h', '14h – 16h', '16h – 18h', '18h – 20h' )
	);
}

/**
 * Jours proposés : les 7 prochains jours d'ouverture à partir de demain (le lundi, le bar est fermé).
 * Retourne des options de champ : valeur AAAA-MM-JJ et libellé « mardi 27 octobre ».
 */
function gainsbarre_jours_disponibles() {
	$options = array();
	$jour    = current_datetime();
	while ( count( $options ) < 7 ) {
		$jour = $jour->modify( '+1 day' );
		if ( '1' === $jour->format( 'N' ) ) {
			continue;
		}
		$options[] = array(
			'value' => $jour->format( 'Y-m-d' ),
			'label' => wp_date( 'l j F', $jour->getTimestamp() ),
		);
	}
	return $options;
}

/**
 * Options de la liste des plages horaires.
 */
function gainsbarre_plages_options() {
	$options = array();
	foreach ( gainsbarre_plages_horaires() as $plage ) {
		$options[] = array(
			'value' => $plage,
			'label' => $plage,
		);
	}
	return $options;
}

/**
 * Règle d'affichage : la valeur voulue pour « le client a choisi le retrait en boutique ».
 */
function gainsbarre_regle_retrait( $retrait ) {
	return array(
		'type'       => 'object',
		'properties' => array(
			'cart' => array(
				'properties' => array(
					'prefers_collection' => array( 'const' => $retrait ),
				),
			),
		),
	);
}

function gainsbarre_declarer_creneaux() {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	$champs = array(
		array( 'livraison', 'Jour de livraison', 'Plage horaire de livraison', false ),
		array( 'retrait', 'Jour de retrait', 'Plage horaire de retrait', true ),
	);

	foreach ( $champs as $champ ) {
		list( $mode, $label_jour, $label_plage, $retrait ) = $champ;

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'gainsbarre/jour_' . $mode,
				'label'    => $label_jour,
				'location' => 'order',
				'type'        => 'select',
				'placeholder' => 'Choisir un jour',
				'options'     => gainsbarre_jours_disponibles(),
				'required' => gainsbarre_regle_retrait( $retrait ),
				'hidden'   => gainsbarre_regle_retrait( ! $retrait ),
			)
		);

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'gainsbarre/plage_' . $mode,
				'label'    => $label_plage,
				'location' => 'order',
				'type'        => 'select',
				'placeholder' => 'Choisir une plage horaire',
				'options'     => gainsbarre_plages_options(),
				'required' => gainsbarre_regle_retrait( $retrait ),
				'hidden'   => gainsbarre_regle_retrait( ! $retrait ),
			)
		);
	}
}
add_action( 'woocommerce_init', 'gainsbarre_declarer_creneaux' );

/**
 * Créneau choisi pour une commande : array( titre, texte ) ou null (commande sans créneau).
 * Exemple : array( 'Retrait', 'vendredi 23 octobre, 12h – 14h' ).
 */
function gainsbarre_creneau_commande( $commande ) {
	foreach ( array(
		'retrait'   => 'Retrait',
		'livraison' => 'Livraison',
	) as $mode => $titre ) {
		$jour  = $commande->get_meta( '_wc_other/gainsbarre/jour_' . $mode );
		$plage = $commande->get_meta( '_wc_other/gainsbarre/plage_' . $mode );
		if ( $jour && $plage ) {
			return array( $titre, wp_date( 'l j F', strtotime( $jour ) ) . ', ' . $plage );
		}
	}
	return null;
}

/**
 * Affichage du créneau sur la page de confirmation, dans Mon compte et dans les e-mails.
 */
function gainsbarre_afficher_creneau_page( $commande ) {
	$creneau = gainsbarre_creneau_commande( $commande );
	if ( $creneau ) {
		echo '<h2>' . esc_html( $creneau[0] . ' : créneau choisi' ) . '</h2><p>' . esc_html( $creneau[1] ) . '</p>';
	}
}
add_action( 'woocommerce_order_details_after_order_table', 'gainsbarre_afficher_creneau_page' );

function gainsbarre_afficher_creneau_email( $commande, $envoi_admin, $texte_brut ) {
	$creneau = gainsbarre_creneau_commande( $commande );
	if ( ! $creneau ) {
		return;
	}
	if ( $texte_brut ) {
		echo "\n" . $creneau[0] . ' : ' . $creneau[1] . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}
	echo '<div class="gb-email-pickup"><h2>' . esc_html( $creneau[0] . ' : créneau choisi' ) . '</h2><p>' . esc_html( $creneau[1] ) . '</p></div>';
}
add_action( 'woocommerce_email_after_order_table', 'gainsbarre_afficher_creneau_email', 9, 3 );
