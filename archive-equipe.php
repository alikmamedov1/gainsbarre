<?php
/**
 * Liste des membres de l'équipe (US-11.3).
 */
get_header();
?>
<div id="primary" class="content-area">
	<main id="main" class="site-main gb-team">
		<header class="gb-team__intro">
			<h1 class="entry-title">L'équipe du Gainsbarre</h1>
			<p>Rencontrez celles et ceux qui vous accueillent au bar de la Maison Gainsbourg, du midi au soir.</p>
		</header>
		<?php if ( have_posts() ) : ?>
			<ul class="gb-team__grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<li class="gb-team__item">
						<a href="<?php the_permalink(); ?>">
							<?php echo gainsbarre_equipe_photo( get_the_ID() ); ?>
							<h2 class="gb-team__name"><?php the_title(); ?></h2>
							<p class="gb-team__role"><?php echo esc_html( get_post_meta( get_the_ID(), 'equipe_poste', true ) ); ?></p>
						</a>
					</li>
				<?php endwhile; ?>
			</ul>
		<?php else : ?>
			<p>Aucun membre à afficher pour le moment.</p>
		<?php endif; ?>
	</main>
</div>
<?php
get_footer();
