<?php
/**
 * Alt bilgi, mobil alt menü çubuğu ve kategoriler paneli.
 *
 * @package Vitrin_Hizli
 */

?>
</main>

<footer class="vt-footer">
	<div class="vt-wrap">
		<?php if ( is_active_sidebar( 'footer' ) ) : ?>
			<div class="vt-footer__widgets"><?php dynamic_sidebar( 'footer' ); ?></div>
		<?php endif; ?>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'vt-footer__menu',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		?>
		<p class="vt-footer__copy">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
	</div>
</footer>

<?php vitrin_bottom_bar(); ?>
<?php vitrin_category_sheet(); ?>
<?php wp_footer(); ?>
</body>
</html>
