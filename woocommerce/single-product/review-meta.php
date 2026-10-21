<?php
/**
 * Auteur, mention d'achat vérifié et date d'un avis (surcharge de WooCommerce 3.4.0).
 *
 * @package Gainsbarre
 */

defined( 'ABSPATH' ) || exit;

global $comment;
$verifie = wc_review_is_from_verified_owner( $comment->comment_ID );

if ( '0' === $comment->comment_approved ) { ?>

	<p class="meta">
		<em class="woocommerce-review__awaiting-approval">
			<?php esc_html_e( 'Merci ! Votre avis est en attente de validation par l’équipe du bar.', 'gainsbarre' ); ?>
		</em>
	</p>

<?php } else { ?>

	<p class="meta gb-review__meta">
		<strong class="woocommerce-review__author"><?php comment_author(); ?></strong>
		<?php
		if ( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) && $verifie ) {
			echo '<em class="woocommerce-review__verified verified gb-review__badge">' . esc_html__( 'Achat vérifié', 'gainsbarre' ) . '</em>';
		}
		?>
		<time class="woocommerce-review__published-date" datetime="<?php echo esc_attr( get_comment_date( 'c' ) ); ?>"><?php echo esc_html( get_comment_date( wc_date_format() ) ); ?></time>
	</p>

	<?php
}
