<?php
/**
 * Factures PDF (IT-17).
 * - US-17.1 : une facture PDF est créée pour chaque commande validée (statuts « en cours » et « terminée »).
 * - US-17.2 : le logo et les coordonnées de l'enseigne sont personnalisables (WooCommerce > Facture).
 * - US-17.3 : le client télécharge sa facture depuis « Mes commandes » ; l'administrateur depuis la commande.
 * Génération avec la bibliothèque FPDF (inc/fpdf), sans dépendance externe.
 */

defined( 'ABSPATH' ) || exit;

/* --------------------------------------------------------------------------
   Réglages de l'enseigne
   -------------------------------------------------------------------------- */
function gainsbarre_facture_reglages() {
	return array(
		'gainsbarre_facture_nom'     => array( 'Raison sociale', 'Maison Gainsbourg - Le Gainsbarre' ),
		'gainsbarre_facture_adresse' => array( 'Adresse', '14 rue de Verneuil, 75007 Paris' ),
		'gainsbarre_facture_contact' => array( 'Téléphone / e-mail', '' ),
		'gainsbarre_facture_siret'   => array( 'SIRET', '000 000 000 00000' ),
		'gainsbarre_facture_tva'     => array( 'Numéro de TVA', 'FR00000000000' ),
		'gainsbarre_facture_mention' => array( 'Mention de pied de page', 'Vente d\'alcool interdite aux mineurs de moins de 18 ans.' ),
	);
}

function gainsbarre_facture_valeur( $cle ) {
	$reglages = gainsbarre_facture_reglages();
	$defaut   = isset( $reglages[ $cle ] ) ? $reglages[ $cle ][1] : '';
	return (string) get_option( $cle, $defaut );
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'woocommerce', 'Facture', 'Facture', 'manage_woocommerce', 'gainsbarre-facture', 'gainsbarre_facture_page' );
} );

add_action( 'admin_init', function () {
	foreach ( array_keys( gainsbarre_facture_reglages() ) as $cle ) {
		register_setting( 'gainsbarre_facture', $cle, array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
	}
} );

function gainsbarre_facture_page() {
	?>
	<div class="wrap">
		<h1>Facture</h1>
		<p>Ces informations figurent sur les factures PDF, à côté du logo de l'enseigne.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'gainsbarre_facture' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( gainsbarre_facture_reglages() as $cle => $champ ) : ?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $cle ); ?>"><?php echo esc_html( $champ[0] ); ?></label></th>
						<td><input name="<?php echo esc_attr( $cle ); ?>" id="<?php echo esc_attr( $cle ); ?>" type="text" value="<?php echo esc_attr( gainsbarre_facture_valeur( $cle ) ); ?>" class="regular-text"></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Enregistrer' ); ?>
		</form>
	</div>
	<?php
}

/* --------------------------------------------------------------------------
   Numérotation et génération
   -------------------------------------------------------------------------- */
function gainsbarre_facture_statut_valide( $order ) {
	return $order && in_array( $order->get_status(), array( 'processing', 'completed' ), true );
}

function gainsbarre_facture_dossier() {
	$upload = wp_upload_dir();
	$dossier = trailingslashit( $upload['basedir'] ) . 'gainsbarre-factures';
	if ( ! is_dir( $dossier ) ) {
		wp_mkdir_p( $dossier );
		file_put_contents( $dossier . '/index.php', "<?php\n// Silence.\n" );
		file_put_contents( $dossier . '/.htaccess', "Require all denied\nDeny from all\n" );
	}
	return $dossier;
}

function gainsbarre_facture_numero( $order ) {
	$numero = $order->get_meta( '_gainsbarre_facture_numero' );
	if ( $numero ) {
		return $numero;
	}
	// Un autre objet commande a pu attribuer le numéro entre-temps : on relit la base avant d'en créer un.
	$relue  = wc_get_order( $order->get_id() );
	$numero = $relue ? $relue->get_meta( '_gainsbarre_facture_numero' ) : '';
	if ( $numero ) {
		$order->update_meta_data( '_gainsbarre_facture_numero', $numero );
		$order->update_meta_data( '_gainsbarre_facture_date', $relue->get_meta( '_gainsbarre_facture_date' ) );
		return $numero;
	}
	$compteur = (int) get_option( 'gainsbarre_facture_compteur', 0 ) + 1;
	update_option( 'gainsbarre_facture_compteur', $compteur, false );
	$numero = sprintf( 'F-%s-%04d', wp_date( 'Y' ), $compteur );
	$order->update_meta_data( '_gainsbarre_facture_numero', $numero );
	$order->update_meta_data( '_gainsbarre_facture_date', time() );
	$order->save();
	return $numero;
}

function gainsbarre_facture_chemin( $order ) {
	$numero = gainsbarre_facture_numero( $order );
	return gainsbarre_facture_dossier() . '/' . $numero . '-' . $order->get_id() . '.pdf';
}

/**
 * Retourne le chemin du PDF de la commande (le crée si besoin), ou '' si la commande n'est pas validée.
 */
function gainsbarre_facture_assurer( $order, $forcer = false ) {
	if ( ! gainsbarre_facture_statut_valide( $order ) ) {
		return '';
	}
	$chemin = gainsbarre_facture_chemin( $order );
	if ( $forcer || ! file_exists( $chemin ) ) {
		gainsbarre_facture_creer_pdf( $order, $chemin );
	}
	return file_exists( $chemin ) ? $chemin : '';
}

foreach ( array( 'woocommerce_order_status_processing', 'woocommerce_order_status_completed' ) as $crochet ) {
	add_action( $crochet, function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			gainsbarre_facture_assurer( $order );
		}
	}, 20 );
}

function gainsbarre_facture_texte( $texte ) {
	$texte = html_entity_decode( wp_strip_all_tags( (string) $texte ), ENT_QUOTES, 'UTF-8' );
	// Les caractères absents de la police du PDF (alphabet cyrillique, par exemple) sont ignorés au lieu d'afficher « ? ».
	$ancien = mb_substitute_character();
	mb_substitute_character( 'none' );
	$texte = mb_convert_encoding( $texte, 'Windows-1252', 'UTF-8' );
	mb_substitute_character( $ancien );
	return trim( $texte );
}

function gainsbarre_facture_montant( $valeur, $order ) {
	$symbole = html_entity_decode( get_woocommerce_currency_symbol( $order->get_currency() ), ENT_QUOTES, 'UTF-8' );
	return number_format( (float) $valeur, 2, ',', ' ' ) . ' ' . $symbole;
}

function gainsbarre_facture_creer_pdf( $order, $chemin ) {
	if ( ! class_exists( 'FPDF' ) ) {
		define( 'FPDF_FONTPATH', get_stylesheet_directory() . '/inc/fpdf/font/' );
		require_once get_stylesheet_directory() . '/inc/fpdf/fpdf.php';
	}
	$t      = 'gainsbarre_facture_texte';
	$numero = gainsbarre_facture_numero( $order );
	$date   = (int) $order->get_meta( '_gainsbarre_facture_date' );
	$date   = $date ? $date : time();

	$pdf = new FPDF( 'P', 'mm', 'A4' );
	$pdf->SetMargins( 20, 20, 20 );
	$pdf->SetAutoPageBreak( true, 25 );
	$pdf->SetTitle( 'Facture ' . $numero, true );
	$pdf->SetAuthor( gainsbarre_facture_valeur( 'gainsbarre_facture_nom' ), true );
	$pdf->AddPage();
	$pdf->SetTextColor( 0, 0, 0 );
	$pdf->SetDrawColor( 0, 0, 0 );

	// Logo de l'enseigne.
	$logo = get_stylesheet_directory() . '/assets/email-logo.png';
	if ( file_exists( $logo ) ) {
		$pdf->Image( $logo, 20, 18, 90 );
	}

	// Titre et références.
	$pdf->SetFont( 'Courier', 'B', 20 );
	$pdf->SetXY( 20, 36 );
	$pdf->Cell( 0, 10, 'FACTURE', 0, 1 );
	$pdf->SetFont( 'Courier', '', 10 );
	$pdf->Cell( 0, 5, $t( 'Numéro : ' . $numero ), 0, 1 );
	$pdf->Cell( 0, 5, $t( 'Date : ' . wp_date( 'j F Y', $date ) ), 0, 1 );
	$pdf->Cell( 0, 5, $t( 'Commande #' . $order->get_order_number() . ' du ' . wp_date( 'j F Y', $order->get_date_created() ? $order->get_date_created()->getTimestamp() : $date ) ), 0, 1 );

	// Enseigne (gauche) et client (droite).
	$y = 70;
	$pdf->SetXY( 20, $y );
	$pdf->SetFont( 'Courier', 'B', 10 );
	$pdf->Cell( 80, 5, 'ENSEIGNE', 0, 1 );
	$pdf->SetFont( 'Courier', '', 10 );
	$lignes = array_filter( array(
		gainsbarre_facture_valeur( 'gainsbarre_facture_nom' ),
		gainsbarre_facture_valeur( 'gainsbarre_facture_adresse' ),
		gainsbarre_facture_valeur( 'gainsbarre_facture_contact' ),
		'SIRET : ' . gainsbarre_facture_valeur( 'gainsbarre_facture_siret' ),
		'TVA : ' . gainsbarre_facture_valeur( 'gainsbarre_facture_tva' ),
	) );
	foreach ( $lignes as $ligne ) {
		$pdf->SetX( 20 );
		$pdf->MultiCell( 80, 5, $t( $ligne ), 0, 'L' );
	}

	$pdf->SetXY( 110, $y );
	$pdf->SetFont( 'Courier', 'B', 10 );
	$pdf->Cell( 80, 5, 'CLIENT', 0, 1 );
	$pdf->SetFont( 'Courier', '', 10 );
	$client = array_filter( array(
		trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
		$order->get_billing_address_1(),
		trim( $order->get_billing_postcode() . ' ' . $order->get_billing_city() ),
		$order->get_billing_email(),
	) );
	foreach ( $client as $ligne ) {
		$pdf->SetX( 110 );
		$pdf->MultiCell( 80, 5, $t( $ligne ), 0, 'L' );
	}

	// Lignes de la commande.
	$pdf->SetY( 115 );
	$pdf->SetFont( 'Courier', 'B', 10 );
	$pdf->SetLineWidth( 0.4 );
	$pdf->Line( 20, $pdf->GetY(), 190, $pdf->GetY() );
	$pdf->Cell( 88, 8, 'DESIGNATION', 0, 0 );
	$pdf->Cell( 14, 8, 'QTE', 0, 0, 'R' );
	$pdf->Cell( 34, 8, 'PRIX TTC', 0, 0, 'R' );
	$pdf->Cell( 34, 8, 'TOTAL TTC', 0, 1, 'R' );
	$pdf->Line( 20, $pdf->GetY(), 190, $pdf->GetY() );
	$pdf->SetFont( 'Courier', '', 10 );
	$pdf->SetLineWidth( 0.2 );

	foreach ( $order->get_items() as $item ) {
		$quantite = (int) $item->get_quantity();
		$total    = (float) $item->get_total() + (float) $item->get_total_tax();
		$unitaire = $quantite ? $total / $quantite : $total;
		$y        = $pdf->GetY();
		$pdf->SetXY( 20, $y + 2 );
		$pdf->MultiCell( 88, 5, $t( $item->get_name() ), 0, 'L' );
		$hauteur = $pdf->GetY() - $y;
		$pdf->SetXY( 108, $y + 2 );
		$pdf->Cell( 14, 5, (string) $quantite, 0, 0, 'R' );
		$pdf->Cell( 34, 5, $t( gainsbarre_facture_montant( $unitaire, $order ) ), 0, 0, 'R' );
		$pdf->Cell( 34, 5, $t( gainsbarre_facture_montant( $total, $order ) ), 0, 0, 'R' );
		$pdf->SetY( $y + $hauteur + 2 );
		$pdf->Line( 20, $pdf->GetY(), 190, $pdf->GetY() );
	}

	foreach ( $order->get_fees() as $frais ) {
		$total = (float) $frais->get_total() + (float) $frais->get_total_tax();
		$pdf->SetX( 20 );
		$pdf->Cell( 102, 7, $t( $frais->get_name() ), 0, 0 );
		$pdf->Cell( 34, 7, '', 0, 0 );
		$pdf->Cell( 34, 7, $t( gainsbarre_facture_montant( $total, $order ) ), 0, 1, 'R' );
		$pdf->Line( 20, $pdf->GetY(), 190, $pdf->GetY() );
	}

	// Totaux.
	$pdf->Ln( 4 );
	$remise = (float) $order->get_total_discount( false );
	if ( $remise > 0 ) {
		$pdf->SetX( 20 );
		$pdf->Cell( 136, 6, 'Reduction', 0, 0, 'R' );
		$pdf->Cell( 34, 6, $t( '-' . gainsbarre_facture_montant( $remise, $order ) ), 0, 1, 'R' );
	}
	if ( (float) $order->get_total_tax() > 0 ) {
		$pdf->SetX( 20 );
		$pdf->Cell( 136, 6, 'dont TVA', 0, 0, 'R' );
		$pdf->Cell( 34, 6, $t( gainsbarre_facture_montant( $order->get_total_tax(), $order ) ), 0, 1, 'R' );
	}
	$pdf->SetFont( 'Courier', 'B', 12 );
	$pdf->SetX( 20 );
	$pdf->Cell( 136, 9, 'TOTAL TTC', 0, 0, 'R' );
	$pdf->Cell( 34, 9, $t( gainsbarre_facture_montant( $order->get_total(), $order ) ), 0, 1, 'R' );

	// Paiement et retrait.
	$pdf->SetFont( 'Courier', '', 10 );
	$pdf->Ln( 6 );
	$pdf->SetX( 20 );
	$pdf->MultiCell( 170, 5, $t( 'Moyen de paiement : ' . $order->get_payment_method_title() ), 0, 'L' );
	$pdf->SetX( 20 );
	$pdf->MultiCell( 170, 5, $t( 'Retrait au bar : ' . gainsbarre_facture_valeur( 'gainsbarre_facture_adresse' ) ), 0, 'L' );

	// Pied de page.
	$pdf->SetAutoPageBreak( false );
	$pdf->SetXY( 20, 280 );
	$pdf->SetFont( 'Courier', '', 8 );
	$pdf->Cell( 170, 4, $t( gainsbarre_facture_valeur( 'gainsbarre_facture_mention' ) ), 0, 0, 'C' );

	$pdf->Output( 'F', $chemin );
}

/* --------------------------------------------------------------------------
   Téléchargement (client propriétaire de la commande ou administrateur)
   -------------------------------------------------------------------------- */
function gainsbarre_facture_url( $order ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=gainsbarre_facture&commande=' . $order->get_id() ), 'gainsbarre_facture_' . $order->get_id() );
}

add_action( 'admin_post_gainsbarre_facture', function () {
	$id    = isset( $_GET['commande'] ) ? absint( $_GET['commande'] ) : 0;
	$order = $id ? wc_get_order( $id ) : false;
	if ( ! $order || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gainsbarre_facture_' . $id ) ) {
		wp_die( 'Lien de facture invalide.', 'Facture', array( 'response' => 403 ) );
	}
	$autorise = current_user_can( 'manage_woocommerce' ) || ( get_current_user_id() && (int) $order->get_customer_id() === get_current_user_id() );
	if ( ! $autorise ) {
		wp_die( 'Vous ne pouvez pas télécharger cette facture.', 'Facture', array( 'response' => 403 ) );
	}
	$chemin = gainsbarre_facture_assurer( $order );
	if ( ! $chemin ) {
		wp_die( 'Aucune facture pour cette commande : elle n\'est pas encore validée.', 'Facture', array( 'response' => 404 ) );
	}
	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: attachment; filename="facture-' . $order->get_meta( '_gainsbarre_facture_numero' ) . '.pdf"' );
	header( 'Content-Length: ' . filesize( $chemin ) );
	readfile( $chemin );
	exit;
} );

// Mon compte > Mes commandes : bouton « Facture ».
add_filter( 'woocommerce_my_account_my_orders_actions', function ( $actions, $order ) {
	if ( gainsbarre_facture_statut_valide( $order ) ) {
		$actions['facture'] = array(
			'url'  => gainsbarre_facture_url( $order ),
			'name' => 'Facture',
		);
	}
	return $actions;
}, 20, 2 );

// Mon compte > détail d'une commande : lien sous le tableau.
add_action( 'woocommerce_order_details_after_order_table', function ( $order ) {
	if ( is_account_page() && gainsbarre_facture_statut_valide( $order ) ) {
		echo '<p class="gb-facture"><a class="button" href="' . esc_url( gainsbarre_facture_url( $order ) ) . '">Télécharger la facture (PDF)</a></p>';
	}
} );

// Administration : lien dans l'écran de la commande.
add_action( 'woocommerce_admin_order_data_after_order_details', function ( $order ) {
	if ( gainsbarre_facture_statut_valide( $order ) ) {
		echo '<p class="form-field form-field-wide"><strong>Facture :</strong> <a href="' . esc_url( gainsbarre_facture_url( $order ) ) . '">Télécharger la facture PDF</a></p>';
	} else {
		echo '<p class="form-field form-field-wide"><strong>Facture :</strong> disponible quand la commande est « En cours » ou « Terminée ».</p>';
	}
} );
