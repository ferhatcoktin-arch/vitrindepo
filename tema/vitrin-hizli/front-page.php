<?php
/**
 * Ana sayfa: kategori kutuları, aroma seçici, çok satanlar.
 *
 * @package Vitrin_Hizli
 */

get_header();

$vitrin_categories = vitrin_main_categories();
$vitrin_aromas     = vitrin_has_wc() ? vitrin_aroma_categories() : array();
?>
<h1 class="screen-reader-text"><?php bloginfo( 'name' ); ?> – <?php bloginfo( 'description' ); ?></h1>

<?php if ( is_active_sidebar( 'anasayfa-ust' ) ) : ?>
	<div class="vt-wrap vt-home-top"><?php dynamic_sidebar( 'anasayfa-ust' ); ?></div>
<?php endif; ?>

<?php if ( $vitrin_categories ) : ?>
	<section class="vt-wrap vt-section" aria-labelledby="vt-baslik-kategoriler">
		<div class="vt-section__head">
			<h2 id="vt-baslik-kategoriler" class="vt-section__title"><?php esc_html_e( 'Kategoriler', 'vitrin-hizli' ); ?></h2>
		</div>
		<ul class="vt-cats">
			<?php
			foreach ( $vitrin_categories as $vitrin_term ) {
				vitrin_category_card( $vitrin_term );
			}
			?>
		</ul>
	</section>
<?php endif; ?>

<?php if ( $vitrin_aromas ) : ?>
	<section class="vt-wrap vt-section" aria-labelledby="vt-baslik-aroma">
		<div class="vt-section__head">
			<h2 id="vt-baslik-aroma" class="vt-section__title"><?php esc_html_e( 'Aromanı seç', 'vitrin-hizli' ); ?></h2>
		</div>
		<div class="vt-tabs">
			<?php foreach ( $vitrin_aromas as $vitrin_i => $vitrin_term ) : ?>
				<input class="vt-tabs__radio" type="radio" name="vt-aroma" id="vt-aroma-<?php echo (int) $vitrin_i; ?>" <?php checked( 0, $vitrin_i ); ?>>
				<label class="vt-tabs__label" for="vt-aroma-<?php echo (int) $vitrin_i; ?>"><?php echo esc_html( $vitrin_term->name ); ?></label>
			<?php endforeach; ?>
			<?php foreach ( $vitrin_aromas as $vitrin_term ) : ?>
				<div class="vt-tabs__panel vt-rail">
					<?php echo do_shortcode( sprintf( '[products category="%s" limit="8" columns="4" orderby="popularity"]', esc_attr( $vitrin_term->slug ) ) ); ?>
					<a class="vt-more" href="<?php echo esc_url( vitrin_term_url( $vitrin_term ) ); ?>">
						<?php
						/* translators: %s: aroma kategorisinin adı */
						echo esc_html( sprintf( __( 'Tüm %s ürünleri', 'vitrin-hizli' ), $vitrin_term->name ) );
						?>
						→
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( vitrin_has_wc() ) : ?>
	<section class="vt-wrap vt-section" aria-labelledby="vt-baslik-cok-satan">
		<div class="vt-section__head">
			<h2 id="vt-baslik-cok-satan" class="vt-section__title"><?php esc_html_e( 'Çok satanlar', 'vitrin-hizli' ); ?></h2>
			<a class="vt-more" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Tümünü gör', 'vitrin-hizli' ); ?> →</a>
		</div>
		<div class="vt-rail">
			<?php echo do_shortcode( '[products limit="8" columns="4" best_selling="true"]' ); ?>
		</div>
	</section>
<?php endif; ?>

<?php
get_footer();
