<?php
/**
 * Sayfalar (sepet, ödeme, hesabım dahil), yazılar, arşivler, arama ve 404.
 *
 * @package Vitrin_Hizli
 */

get_header();
?>
<div class="vt-wrap vt-content">
	<?php if ( have_posts() ) : ?>

		<?php if ( is_search() ) : ?>
			<h1 class="vt-page-title">
				<?php
				/* translators: %s: aranan kelime */
				printf( esc_html__( '“%s” için sonuçlar', 'vitrin-hizli' ), esc_html( get_search_query() ) );
				?>
			</h1>
		<?php elseif ( is_archive() ) : ?>
			<?php the_archive_title( '<h1 class="vt-page-title">', '</h1>' ); ?>
		<?php elseif ( is_home() && ! is_front_page() ) : ?>
			<h1 class="vt-page-title"><?php single_post_title(); ?></h1>
		<?php endif; ?>

		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'vt-entry' ); ?>>
				<?php if ( is_singular() ) : ?>
					<?php the_title( '<h1 class="vt-page-title">', '</h1>' ); ?>
					<div class="vt-entry__content">
						<?php the_content(); ?>
						<?php wp_link_pages(); ?>
					</div>
				<?php else : ?>
					<h2 class="vt-entry__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>

		<?php the_posts_pagination(); ?>

	<?php else : ?>

		<h1 class="vt-page-title">
			<?php is_404() ? esc_html_e( 'Sayfa bulunamadı', 'vitrin-hizli' ) : esc_html_e( 'Sonuç bulunamadı', 'vitrin-hizli' ); ?>
		</h1>
		<p><?php esc_html_e( 'Aradığını üstteki arama kutusundan ya da kategorilerden bulabilirsin.', 'vitrin-hizli' ); ?></p>

	<?php endif; ?>
</div>
<?php
get_footer();
