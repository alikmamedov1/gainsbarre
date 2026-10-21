<?php
/**
 * Onglet « Avis » de la fiche produit (surcharge de WooCommerce 11.2.0).
 * Résumé de la note, liste des avis, puis formulaire réservé aux clients ayant acheté le produit.
 *
 * @package Gainsbarre
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! comments_open() ) {
	return;
}

$nombre  = (int) $product->get_review_count();
$moyenne = (float) $product->get_average_rating();
$a_achete = wc_customer_bought_product( '', get_current_user_id(), $product->get_id() );
$verifie  = 'no' !== get_option( 'woocommerce_review_rating_verification_required' );
?>
<div id="reviews" class="woocommerce-Reviews gb-reviews">
	<div id="comments">
		<?php if ( $nombre && wc_review_ratings_enabled() ) : ?>
			<div class="gb-reviews__summary">
				<span class="gb-reviews__average"><?php echo esc_html( number_format_i18n( $moyenne, 1 ) ); ?></span>
				<div>
					<?php echo wc_get_rating_html( $moyenne, $nombre ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="gb-reviews__count">
						<?php
						/* translators: %s: nombre d'avis */
						echo esc_html( sprintf( _n( 'Moyenne de %s avis', 'Moyenne de %s avis', $nombre, 'gainsbarre' ), $nombre ) );
						?>
					</span>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( have_comments() ) : ?>
			<ol class="commentlist">
				<?php wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments' ) ) ); ?>
			</ol>

			<?php
			if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
				echo '<nav class="woocommerce-pagination">';
				paginate_comments_links(
					apply_filters(
						'woocommerce_comment_pagination_args',
						array(
							'prev_text' => '&larr;',
							'next_text' => '&rarr;',
							'type'      => 'list',
						)
					)
				);
				echo '</nav>';
			endif;
			?>
		<?php else : ?>
			<p class="woocommerce-noreviews"><?php esc_html_e( 'Il n’y a pas encore d’avis.', 'gainsbarre' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! $verifie || $a_achete ) : ?>
		<div id="review_form_wrapper">
			<div id="review_form">
				<?php
				$formulaire = array(
					'title_reply'         => esc_html__( 'Donnez votre avis', 'gainsbarre' ),
					'title_reply_to'      => esc_html__( 'Répondre à %s', 'gainsbarre' ),
					'title_reply_before'  => '<span id="reply-title" class="comment-reply-title" role="heading" aria-level="3">',
					'title_reply_after'   => '</span>',
					'comment_notes_after' => '',
					'label_submit'        => esc_html__( 'Envoyer l’avis', 'gainsbarre' ),
					'logged_in_as'        => '',
					'comment_field'       => '',
				);

				$compte = wc_get_page_permalink( 'myaccount' );
				if ( $compte ) {
					$formulaire['must_log_in'] = '<p class="must-log-in">' . sprintf( esc_html__( 'Vous devez être %1$sconnecté%2$s pour publier un avis.', 'gainsbarre' ), '<a href="' . esc_url( $compte ) . '">', '</a>' ) . '</p>';
				}

				if ( wc_review_ratings_enabled() ) {
					$formulaire['comment_field'] = '<div class="comment-form-rating"><label for="rating" id="comment-form-rating-label">' . esc_html__( 'Votre note', 'gainsbarre' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) . '</label><select name="rating" id="rating" required>
						<option value="">' . esc_html__( 'Noter…', 'gainsbarre' ) . '</option>
						<option value="5">' . esc_html__( 'Parfait', 'gainsbarre' ) . '</option>
						<option value="4">' . esc_html__( 'Bien', 'gainsbarre' ) . '</option>
						<option value="3">' . esc_html__( 'Moyen', 'gainsbarre' ) . '</option>
						<option value="2">' . esc_html__( 'Pas terrible', 'gainsbarre' ) . '</option>
						<option value="1">' . esc_html__( 'Très décevant', 'gainsbarre' ) . '</option>
					</select></div>';
				}

				$formulaire['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Votre avis', 'gainsbarre' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="6" required></textarea></p>';

				comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $formulaire ) );
				?>
			</div>
		</div>
	<?php else : ?>
		<p class="gb-reviews__notice woocommerce-verification-required">
			<?php
			if ( is_user_logged_in() ) {
				esc_html_e( 'Seuls les clients ayant acheté ce produit peuvent laisser un avis.', 'gainsbarre' );
			} else {
				echo esc_html__( 'Seuls les clients connectés ayant acheté ce produit peuvent laisser un avis.', 'gainsbarre' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">' . esc_html__( 'Se connecter', 'gainsbarre' ) . '</a>';
			}
			?>
		</p>
	<?php endif; ?>

	<div class="clear"></div>
</div>
