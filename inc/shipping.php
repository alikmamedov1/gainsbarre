<?php
/**
 * Livraison selon le poids (IT-19).
 * Le mode d'expédition s'ajoute à une zone depuis WooCommerce > Réglages > Expédition,
 * où l'administrateur règle les 3 tranches de poids et leurs tarifs.
 */

defined( 'ABSPATH' ) || exit;

function gainsbarre_charger_livraison_poids() {
	if ( ! class_exists( 'WC_Shipping_Method' ) ) {
		return;
	}

	class Gainsbarre_Livraison_Poids extends WC_Shipping_Method {

		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'gainsbarre_poids';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = 'Livraison selon le poids';
			$this->method_description = 'Tarif de livraison calculé selon le poids total du panier (3 tranches).';
			$this->supports           = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );
			$this->init_form_fields();
			$this->init_settings();
			$this->title      = $this->get_option( 'title' );
			$this->tax_status = 'none';
		}

		public function init_form_fields() {
			$this->instance_form_fields = array(
				'title'        => array(
					'title'   => 'Nom affiché',
					'type'    => 'text',
					'default' => 'Livraison',
				),
				'tranche1_max' => array(
					'title'    => 'Tranche 1 : jusqu\'à (kg)',
					'type'     => 'decimal',
					'default'  => '1',
					'desc_tip' => 'Poids maximum de la première tranche.',
				),
				'tranche1_prix' => array(
					'title'   => 'Tranche 1 : tarif (€)',
					'type'    => 'price',
					'default' => '4',
				),
				'tranche2_max' => array(
					'title'    => 'Tranche 2 : jusqu\'à (kg)',
					'type'     => 'decimal',
					'default'  => '5',
					'desc_tip' => 'Poids maximum de la deuxième tranche.',
				),
				'tranche2_prix' => array(
					'title'   => 'Tranche 2 : tarif (€)',
					'type'    => 'price',
					'default' => '8',
				),
				'tranche3_prix' => array(
					'title'       => 'Tranche 3 : tarif (€)',
					'type'        => 'price',
					'default'     => '15',
					'description' => 'Au-delà de la tranche 2.',
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Tarif d'un poids donné (en kg).
		 */
		public function tarif_pour_poids( $poids ) {
			if ( $poids <= (float) $this->get_option( 'tranche1_max' ) ) {
				return (float) $this->get_option( 'tranche1_prix' );
			}
			if ( $poids <= (float) $this->get_option( 'tranche2_max' ) ) {
				return (float) $this->get_option( 'tranche2_prix' );
			}
			return (float) $this->get_option( 'tranche3_prix' );
		}

		public function calculate_shipping( $package = array() ) {
			$poids = 0;
			foreach ( $package['contents'] as $ligne ) {
				$poids += (float) $ligne['data']->get_weight() * $ligne['quantity'];
			}
			$poids_kg = wc_get_weight( $poids, 'kg' );

			$this->add_rate(
				array(
					'id'    => $this->get_rate_id(),
					'label' => sprintf( '%s (%s kg)', $this->title, wc_format_localized_decimal( round( $poids_kg, 2 ) ) ),
					'cost'  => $this->tarif_pour_poids( $poids_kg ),
				)
			);
		}
	}
}
add_action( 'woocommerce_shipping_init', 'gainsbarre_charger_livraison_poids' );

function gainsbarre_declarer_livraison_poids( $methodes ) {
	$methodes['gainsbarre_poids'] = 'Gainsbarre_Livraison_Poids';
	return $methodes;
}
add_filter( 'woocommerce_shipping_methods', 'gainsbarre_declarer_livraison_poids' );
