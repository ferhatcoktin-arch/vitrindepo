<?php
/**
 * Üst bölüm: logo, büyük arama kutusu, hesap/sepet ve 5 başlıklı menü.
 *
 * @package Vitrin_Hizli
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#icerik"><?php esc_html_e( 'İçeriğe geç', 'vitrin-hizli' ); ?></a>

<header class="vt-header">
	<div class="vt-wrap vt-header__top">
		<div class="vt-brand"><?php vitrin_brand(); ?></div>
		<?php vitrin_search_form(); ?>
		<?php vitrin_quick_links(); ?>
	</div>
	<nav class="vt-nav" aria-label="<?php esc_attr_e( 'Ana menü', 'vitrin-hizli' ); ?>">
		<div class="vt-wrap">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'vt-menu',
					'depth'          => 2,
					'fallback_cb'    => 'vitrin_menu_fallback',
				)
			);
			?>
		</div>
	</nav>
</header>

<main id="icerik" class="vt-main">
