<?php
/**
 * Page d'un membre de l'équipe (US-11.3).
 */
get_header();
?>
<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<?php
		while ( have_posts() ) :
			the_post();
			$id         = get_the_ID();
			$poste      = get_post_meta( $id, 'equipe_poste', true );
			$depuis     = get_post_meta( $id, 'equipe_depuis', true );
			$specialite = get_post_meta( $id, 'equipe_specialites', true );
			?>
			<article <?php post_class( 'gb-member' ); ?>>
				<?php echo gainsbarre_equipe_photo( $id, 'large' ); ?>
				<div class="gb-member__body">
					<a class="gb-member__back" href="<?php echo esc_url( get_post_type_archive_link( 'equipe' ) ); ?>">← Retour à l'équipe</a>
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<?php if ( $poste ) : ?>
						<p class="gb-member__role"><?php echo esc_html( $poste ); ?></p>
					<?php endif; ?>
					<div class="gb-member__text"><?php the_content(); ?></div>
					<?php if ( $depuis || $specialite ) : ?>
						<dl class="gb-member__facts">
							<?php if ( $depuis ) : ?>
								<div><dt>Au Gainsbarre depuis</dt><dd><?php echo esc_html( $depuis ); ?></dd></div>
							<?php endif; ?>
							<?php if ( $specialite ) : ?>
								<div><dt>Spécialités</dt><dd><?php echo esc_html( $specialite ); ?></dd></div>
							<?php endif; ?>
						</dl>
					<?php endif; ?>
					<?php if ( function_exists( 'wc_get_page_id' ) ) : ?>
						<a class="button alt gb-member__cta" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">Revenir à la carte</a>
					<?php endif; ?>
				</div>
			</article>
		<?php endwhile; ?>
	</main>
</div>
<?php
get_footer();
