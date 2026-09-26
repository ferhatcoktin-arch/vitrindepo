<?php
/**
 * Vitrin Hızlı: iqosvitrin.com.tr için hafif WooCommerce teması.
 *
 * Ana kategoriler ve aroma sekmeleri kategori-yapisi.json'daki kısa adla (slug), o yoksa aynı seviyedeki aynı adla
 * bulunur; kategori aracı da sitedeki aynı adlı kategoriyi yeniden kullanır. Yeni yapı sitede henüz yoksa tema
 * mevcut üst kategorileri gösterir ve aroma seçiciyi gizler.
 *
 * @package Vitrin_Hizli
 */

defined( 'ABSPATH' ) || exit;

define( 'VITRIN_VERSION', '1.0.0' );
define( 'VITRIN_ACCENT', '#0d6e79' );

/**
 * Menü, ana sayfa kutuları ve mobil kategori paneli için ana kategoriler, bu sırayla (kısa ad => ad).
 * kategori-yapisi.json ile aynı olmalı.
 *
 * @return array<string, string>
 */
function vitrin_main_category_names() {
	return apply_filters(
		'vitrin_main_category_names',
		array(
			'iqos-cihazlar'      => 'IQOS Cihazlar',
			'terea'              => 'TEREA',
			'setler-kampanyalar' => 'Setler & Kampanyalar',
			'aksesuarlar'        => 'Aksesuarlar',
		)
	);
}

/**
 * Aroma seçicinin sekmeleri: TEREA'nın alt kategorileri, bu sırayla (kısa ad => ad).
 *
 * @return array<string, string>
 */
function vitrin_aroma_category_names() {
	return apply_filters(
		'vitrin_aroma_category_names',
		array(
			'terea-tutun-aromali'    => 'Tütün Aromalı',
			'terea-mentollu'         => 'Mentollü',
			'terea-kapsullu-meyveli' => 'Kapsüllü / Meyveli',
		)
	);
}

function vitrin_has_wc() {
	return class_exists( 'WooCommerce' );
}

/*
 * Kurulum
 */

add_action(
	'after_setup_theme',
	function () {
		$GLOBALS['content_width'] = 1200;

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 48,
				'width'       => 200,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 400,
				'single_image_width'    => 800,
				'product_grid'          => array(
					'default_columns' => 4,
					'default_rows'    => 4,
					'min_columns'     => 2,
					'max_columns'     => 4,
				),
			)
		);
		add_theme_support( 'wc-product-gallery-slider' );
		add_theme_support( 'wc-product-gallery-lightbox' );

		register_nav_menus(
			array(
				'primary' => __( 'Ana menü (5 başlık)', 'vitrin-hizli' ),
				'footer'  => __( 'Alt bilgi menüsü', 'vitrin-hizli' ),
			)
		);
	}
);

add_action(
	'widgets_init',
	function () {
		$common = array(
			'before_widget' => '<div id="%1$s" class="vt-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="vt-widget__title">',
			'after_title'   => '</h2>',
		);
		register_sidebar(
			array(
				'id'          => 'anasayfa-ust',
				'name'        => __( 'Ana sayfa üst alan', 'vitrin-hizli' ),
				'description' => __( 'Ana sayfada kategori kutularının üstünde görünür; kampanya görseli için.', 'vitrin-hizli' ),
			) + $common
		);
		register_sidebar(
			array(
				'id'          => 'footer',
				'name'        => __( 'Alt bilgi', 'vitrin-hizli' ),
				'description' => __( 'Sayfa altında; iletişim, adres, çalışma saatleri için.', 'vitrin-hizli' ),
			) + $common
		);
	}
);

/*
 * Hız: tek CSS ve tek küçük JS dosyası, emoji betiği yok.
 */

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );

add_action(
	'wp_enqueue_scripts',
	function () {
		// WooCommerce stilleri 10 önceliğiyle eklenir; tema onlardan sonra gelip gerekenleri ezer.
		wp_enqueue_style( 'vitrin', get_template_directory_uri() . '/style.css', array(), VITRIN_VERSION );
		wp_add_inline_style( 'vitrin', vitrin_color_css() );

		wp_enqueue_script(
			'vitrin',
			get_template_directory_uri() . '/assets/vitrin.js',
			array(),
			VITRIN_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		$data = array( 'search' => vitrin_has_wc() ? rest_url( 'wc/store/v1/products' ) : '' );
		wp_add_inline_script( 'vitrin', 'window.vitrin=' . wp_json_encode( $data ) . ';', 'before' );
	},
	20
);

/*
 * Özelleştirici: vurgu rengi ve 18+ penceresi (Görünüm → Özelleştir → Vitrin teması).
 */

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'vitrin',
			array(
				'title'    => __( 'Vitrin teması', 'vitrin-hizli' ),
				'priority' => 30,
			)
		);

		$wp_customize->add_setting(
			'vitrin_accent',
			array(
				'default'           => VITRIN_ACCENT,
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'vitrin_accent',
				array(
					'label'   => __( 'Vurgu rengi (buton, arama kutusu)', 'vitrin-hizli' ),
					'section' => 'vitrin',
				)
			)
		);

		$wp_customize->add_setting(
			'vitrin_agegate',
			array(
				'default'           => true,
				'sanitize_callback' => 'wp_validate_boolean',
			)
		);
		$wp_customize->add_control(
			'vitrin_agegate',
			array(
				'type'    => 'checkbox',
				'label'   => __( 'İlk girişte 18+ yaş doğrulama penceresi göster', 'vitrin-hizli' ),
				'section' => 'vitrin',
			)
		);
	}
);

/**
 * Vurgu rengini, açık tonunu ve üstündeki yazı rengini (siyah ya da beyaz, hangisi daha okunaklıysa) CSS değişkeni yapar.
 */
function vitrin_color_css() {
	$accent = sanitize_hex_color( get_theme_mod( 'vitrin_accent', VITRIN_ACCENT ) );
	if ( ! $accent ) {
		$accent = VITRIN_ACCENT;
	}
	$hex = ltrim( $accent, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$rgb = array_map( 'hexdec', str_split( $hex, 2 ) );

	$soft = '#';
	foreach ( $rgb as $v ) {
		$soft .= sprintf( '%02x', (int) round( $v + ( 255 - $v ) * 0.88 ) );
	}

	$lin = array_map(
		function ( $v ) {
			$v /= 255;
			return $v <= 0.03928 ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 );
		},
		$rgb
	);
	$luminance = 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
	$on_accent = 1.05 / ( $luminance + 0.05 ) >= ( $luminance + 0.05 ) / 0.05 ? '#ffffff' : '#16181d';

	return ":root{--vt-accent:{$accent};--vt-accent-soft:{$soft};--vt-on-accent:{$on_accent};}";
}

/*
 * Kategoriler
 */

function vitrin_fold( $text ) {
	$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
}

/**
 * Ürünü olan (kendisinde ya da alt kategorisinde) istenen kategoriler, istenen sırayla.
 * Önce kısa ad, yoksa aynı seviyedeki aynı ad eşleşir.
 *
 * @param array<string, string> $wanted Kısa ad => ad.
 * @param int                   $parent Üst kategori (0: en üst seviye).
 * @return WP_Term[]
 */
function vitrin_find_categories( $wanted, $parent ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $parent,
			'hide_empty' => true,
		)
	);
	if ( ! is_array( $terms ) ) {
		return array();
	}
	$found = array();
	foreach ( $wanted as $slug => $name ) {
		$match = null;
		foreach ( $terms as $term ) {
			if ( $term->slug === $slug ) {
				$match = $term;
				break;
			}
			if ( ! $match && vitrin_fold( $term->name ) === vitrin_fold( $name ) ) {
				$match = $term;
			}
		}
		if ( $match ) {
			$found[ $match->term_id ] = $match;
		}
	}
	return array_values( $found );
}

/**
 * Ürünü olan üst kategoriler (en çok 8): önce ana kategoriler bu sırayla, sonra sitede kalan diğerleri.
 * Yeni düzen uygulanmadan önce eski kategoriler de böylece görünür; sonra boşalıp kendiliğinden kaybolurlar.
 *
 * @return WP_Term[]
 */
function vitrin_main_categories() {
	static $terms = null;
	if ( null !== $terms ) {
		return $terms;
	}
	$terms = array();
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return $terms;
	}

	$terms  = vitrin_find_categories( vitrin_main_category_names(), 0 );
	$others = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'exclude'    => array_merge( wp_list_pluck( $terms, 'term_id' ), array( (int) get_option( 'default_product_cat' ) ) ),
		)
	);
	if ( is_array( $others ) ) {
		$terms = array_merge( $terms, $others );
	}
	$terms = array_slice( $terms, 0, 8 );
	return $terms;
}

/**
 * Aroma seçicide gösterilecek, ürünü olan TEREA alt kategorileri.
 *
 * @return WP_Term[]
 */
function vitrin_aroma_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$terea = vitrin_find_categories( apply_filters( 'vitrin_aroma_parent', array( 'terea' => 'TEREA' ) ), 0 );
	return $terea ? vitrin_find_categories( vitrin_aroma_category_names(), $terea[0]->term_id ) : array();
}

/**
 * Ürünü olan alt kategoriler.
 *
 * @param WP_Term $term Üst kategori.
 * @return WP_Term[]
 */
function vitrin_child_categories( $term ) {
	$children = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $term->term_id,
			'hide_empty' => true,
		)
	);
	return is_array( $children ) ? $children : array();
}

function vitrin_term_url( $term ) {
	$url = get_term_link( $term );
	return is_wp_error( $url ) ? '' : $url;
}

function vitrin_is_current_category( $term ) {
	if ( ! is_tax( 'product_cat' ) ) {
		return false;
	}
	$current = get_queried_object();
	return $current->term_id === $term->term_id || term_is_ancestor_of( $term, $current, 'product_cat' );
}

function vitrin_category_icon( $term ) {
	$icons = array(
		'iqos-cihazlar'      => 'device',
		'terea'              => 'sticks',
		'setler-kampanyalar' => 'gift',
		'aksesuarlar'        => 'plug',
	);
	foreach ( vitrin_main_category_names() as $slug => $name ) {
		if ( isset( $icons[ $slug ] ) && ( $term->slug === $slug || vitrin_fold( $term->name ) === vitrin_fold( $name ) ) ) {
			return vitrin_icon( $icons[ $slug ] );
		}
	}
	return vitrin_icon( 'tag' );
}

/*
 * Parçalar
 */

/**
 * Satır içi SVG simge (dışarıdan dosya ya da yazı tipi yüklemez).
 */
function vitrin_icon( $name ) {
	$paths = array(
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'home'   => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/>',
		'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
		'bag'    => '<path d="M5 8h14l-1.2 12.1a1 1 0 0 1-1 .9H7.2a1 1 0 0 1-1-.9L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
		'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'device' => '<rect x="8" y="2.5" width="8" height="19" rx="4"/><path d="M12 6.5v2"/>',
		'sticks' => '<rect x="4.5" y="4" width="4" height="16" rx="1.2"/><rect x="10" y="4" width="4" height="16" rx="1.2"/><rect x="15.5" y="4" width="4" height="16" rx="1.2"/><path d="M4.5 9h15"/>',
		'gift'   => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v9h14v-9M12 8v13"/><path d="M12 8S10.5 3.5 8 4.5 9 8 12 8Zm0 0s1.5-4.5 4-3.5S15 8 12 8Z"/>',
		'plug'   => '<path d="M9 3v5M15 3v5"/><path d="M6.5 8h11v3a5.5 5.5 0 0 1-11 0V8Z"/><path d="M12 16.5V21"/>',
		'tag'    => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		$name = 'tag';
	}
	return '<svg class="vt-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

function vitrin_brand() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	printf( '<a class="vt-brand__name" href="%s" rel="home">%s</a>', esc_url( home_url( '/' ) ), esc_html( get_bloginfo( 'name' ) ) );
}

/**
 * Üstteki büyük arama kutusu. Yazarken öneriler assets/vitrin.js ile WooCommerce Store API'den gelir.
 */
function vitrin_search_form() {
	?>
	<form role="search" method="get" class="vt-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label for="vt-s" class="screen-reader-text"><?php esc_html_e( 'Ürün ara', 'vitrin-hizli' ); ?></label>
		<input type="search" id="vt-s" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'TEREA, ILUMA, aksesuar ara…', 'vitrin-hizli' ); ?>" autocomplete="off" enterkeyhint="search" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="vt-oneri">
		<?php if ( vitrin_has_wc() ) : ?>
			<input type="hidden" name="post_type" value="product">
		<?php endif; ?>
		<button type="submit" class="vt-search__btn" aria-label="<?php esc_attr_e( 'Ara', 'vitrin-hizli' ); ?>"><?php echo vitrin_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<div class="vt-suggest" hidden>
			<div id="vt-oneri" role="listbox" aria-label="<?php esc_attr_e( 'Öneriler', 'vitrin-hizli' ); ?>"></div>
			<p class="vt-suggest__empty" hidden><?php esc_html_e( 'Sonuç bulunamadı.', 'vitrin-hizli' ); ?></p>
			<button type="submit" class="vt-suggest__all"><?php esc_html_e( 'Tüm sonuçları gör', 'vitrin-hizli' ); ?> →</button>
		</div>
	</form>
	<?php
}

function vitrin_cart_count() {
	return vitrin_has_wc() && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

function vitrin_cart_count_html() {
	$count = vitrin_cart_count();
	return sprintf(
		'<span class="vt-cart-count" data-count="%1$d"><span class="screen-reader-text">%2$s </span>%1$d</span>',
		$count,
		esc_html__( 'Sepetteki ürün:', 'vitrin-hizli' )
	);
}

/**
 * Masaüstünde arama kutusunun sağındaki hesap ve sepet bağlantıları.
 */
function vitrin_quick_links() {
	if ( ! vitrin_has_wc() ) {
		return;
	}
	?>
	<nav class="vt-quick" aria-label="<?php esc_attr_e( 'Hesap ve sepet', 'vitrin-hizli' ); ?>">
		<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php echo vitrin_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Hesabım', 'vitrin-hizli' ); ?></span></a>
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php echo vitrin_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Sepet', 'vitrin-hizli' ); ?></span><?php echo vitrin_cart_count_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	</nav>
	<?php
}

/**
 * Görünüm → Menüler'de "Ana menü" atanmamışken: ilk 4 üst kategori + Tüm Ürünler = 5 başlık.
 */
function vitrin_menu_fallback() {
	echo '<ul class="vt-menu">';
	foreach ( array_slice( vitrin_main_categories(), 0, 4 ) as $term ) {
		$children = vitrin_child_categories( $term );
		$classes  = array( 'menu-item' );
		if ( $children ) {
			$classes[] = 'menu-item-has-children';
		}
		if ( vitrin_is_current_category( $term ) ) {
			$classes[] = 'current-menu-item';
		}
		printf( '<li class="%s"><a href="%s">%s</a>', esc_attr( implode( ' ', $classes ) ), esc_url( vitrin_term_url( $term ) ), esc_html( $term->name ) );
		if ( $children ) {
			echo '<ul class="sub-menu">';
			foreach ( $children as $child ) {
				printf( '<li class="menu-item"><a href="%s">%s</a></li>', esc_url( vitrin_term_url( $child ) ), esc_html( $child->name ) );
			}
			echo '</ul>';
		}
		echo '</li>';
	}
	if ( vitrin_has_wc() ) {
		printf(
			'<li class="menu-item%s"><a href="%s">%s</a></li>',
			is_shop() && ! is_front_page() && ! is_search() ? ' current-menu-item' : '',
			esc_url( wc_get_page_permalink( 'shop' ) ),
			esc_html__( 'Tüm Ürünler', 'vitrin-hizli' )
		);
	}
	echo '</ul>';
}

/**
 * Ana sayfadaki bir kategori kutusu.
 *
 * @param WP_Term $term Kategori.
 */
function vitrin_category_card( $term ) {
	$url = vitrin_term_url( $term );
	if ( ! $url ) {
		return;
	}
	$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	$children     = vitrin_child_categories( $term );
	?>
	<li class="vt-cat">
		<div class="vt-cat__media">
			<?php
			if ( $thumbnail_id ) {
				echo wp_get_attachment_image(
					$thumbnail_id,
					'woocommerce_thumbnail',
					false,
					array(
						'alt'   => '',
						'sizes' => '(min-width: 900px) 280px, 46vw',
					)
				);
			} else {
				echo vitrin_category_icon( $term ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
		<div class="vt-cat__body">
			<a class="vt-cat__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $term->name ); ?></a>
			<?php if ( $term->count ) : ?>
				<span class="vt-cat__count">
					<?php
					/* translators: %s: ürün sayısı */
					echo esc_html( sprintf( _n( '%s ürün', '%s ürün', $term->count, 'vitrin-hizli' ), number_format_i18n( $term->count ) ) );
					?>
				</span>
			<?php endif; ?>
			<?php if ( $children ) : ?>
				<ul class="vt-cat__subs">
					<?php foreach ( $children as $child ) : ?>
						<li><a href="<?php echo esc_url( vitrin_term_url( $child ) ); ?>"><?php echo esc_html( $child->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</li>
	<?php
}

/**
 * Mobil alt menü çubuğu: Ana Sayfa, Kategoriler, Ara, Sepet, Hesabım.
 */
function vitrin_bottom_bar() {
	$wc    = vitrin_has_wc();
	$items = array(
		array(
			'icon'    => 'home',
			'label'   => __( 'Ana Sayfa', 'vitrin-hizli' ),
			'url'     => home_url( '/' ),
			'current' => is_front_page(),
		),
	);
	if ( vitrin_main_categories() ) {
		$items[] = array(
			'icon'    => 'grid',
			'label'   => __( 'Kategoriler', 'vitrin-hizli' ),
			'url'     => $wc ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
			'attr'    => 'data-vt-open="vt-kategoriler" aria-haspopup="dialog"',
			'current' => $wc && ( is_shop() || is_product_category() ) && ! is_front_page() && ! is_search(),
		);
	}
	$items[] = array(
		'icon'    => 'search',
		'label'   => __( 'Ara', 'vitrin-hizli' ),
		'url'     => '#vt-s',
		'attr'    => 'data-vt-search',
		'current' => is_search(),
	);
	if ( $wc ) {
		$items[] = array(
			'icon'    => 'bag',
			'label'   => __( 'Sepet', 'vitrin-hizli' ),
			'url'     => wc_get_cart_url(),
			'current' => is_cart() || is_checkout(),
			'badge'   => true,
		);
		$items[] = array(
			'icon'    => 'user',
			'label'   => __( 'Hesabım', 'vitrin-hizli' ),
			'url'     => wc_get_page_permalink( 'myaccount' ),
			'current' => is_account_page(),
		);
	}

	echo '<nav class="vt-bar" aria-label="' . esc_attr__( 'Mobil menü', 'vitrin-hizli' ) . '">';
	foreach ( $items as $item ) {
		printf(
			'<a class="vt-bar__item" href="%1$s"%2$s%3$s>%4$s<span>%5$s</span>%6$s</a>',
			esc_url( $item['url'] ),
			$item['current'] ? ' aria-current="page"' : '',
			isset( $item['attr'] ) ? ' ' . $item['attr'] : '', // phpcs:ignore WordPress.Security.EscapeOutput -- sabit metin.
			vitrin_icon( $item['icon'] ), // phpcs:ignore WordPress.Security.EscapeOutput
			esc_html( $item['label'] ),
			empty( $item['badge'] ) ? '' : vitrin_cart_count_html() // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}
	echo '</nav>';
}

/**
 * Alt çubuktaki "Kategoriler"e basınca açılan panel.
 */
function vitrin_category_sheet() {
	$categories = vitrin_main_categories();
	if ( ! $categories ) {
		return;
	}
	?>
	<dialog id="vt-kategoriler" class="vt-sheet" aria-labelledby="vt-kategoriler-baslik">
		<div class="vt-sheet__head">
			<h2 id="vt-kategoriler-baslik"><?php esc_html_e( 'Kategoriler', 'vitrin-hizli' ); ?></h2>
			<form method="dialog"><button class="vt-sheet__close" aria-label="<?php esc_attr_e( 'Kapat', 'vitrin-hizli' ); ?>">&times;</button></form>
		</div>
		<ul class="vt-sheet__list">
			<?php foreach ( $categories as $term ) : ?>
				<li>
					<a href="<?php echo esc_url( vitrin_term_url( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
					<?php $children = vitrin_child_categories( $term ); ?>
					<?php if ( $children ) : ?>
						<ul>
							<?php foreach ( $children as $child ) : ?>
								<li><a href="<?php echo esc_url( vitrin_term_url( $child ) ); ?>"><?php echo esc_html( $child->name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
			<?php if ( vitrin_has_wc() ) : ?>
				<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Tüm Ürünler', 'vitrin-hizli' ); ?></a></li>
			<?php endif; ?>
		</ul>
	</dialog>
	<?php
}

/*
 * 18+ yaş doğrulama: çerez yoksa <html>'e sınıf eklenir, pencere ilk boyamada görünür.
 * Betik satır içindedir; tema JS dosyası yüklenemese de pencere kapatılabilir.
 */

function vitrin_agegate_enabled() {
	return (bool) get_theme_mod( 'vitrin_agegate', true );
}

add_action(
	'wp_head',
	function () {
		if ( vitrin_agegate_enabled() ) {
			wp_print_inline_script_tag( "if(document.cookie.indexOf('vt_18=1')<0){document.documentElement.classList.add('vt-age');}" );
		}
	},
	1
);

add_action(
	'wp_body_open',
	function () {
		if ( ! vitrin_agegate_enabled() ) {
			return;
		}
		?>
		<div class="vt-agegate" role="dialog" aria-modal="true" aria-labelledby="vt-age-baslik" aria-describedby="vt-age-metin">
			<div class="vt-agegate__box">
				<div class="vt-agegate__badge" aria-hidden="true">18+</div>
				<h2 id="vt-age-baslik"><?php esc_html_e( '18 yaşından büyük müsün?', 'vitrin-hizli' ); ?></h2>
				<p id="vt-age-metin"><?php esc_html_e( 'Bu sitede tütün ürünleri satılır; site yalnızca 18 yaşından büyükler içindir.', 'vitrin-hizli' ); ?></p>
				<div class="vt-agegate__actions">
					<button type="button" class="vt-btn" data-vt-age="evet"><?php esc_html_e( 'Evet, 18 yaşından büyüğüm', 'vitrin-hizli' ); ?></button>
					<button type="button" class="vt-btn vt-btn--ghost" data-vt-age="hayir"><?php esc_html_e( 'Hayır', 'vitrin-hizli' ); ?></button>
				</div>
				<p class="vt-agegate__no" role="alert" hidden><?php esc_html_e( 'Üzgünüz, bu site yalnızca 18 yaşından büyükler içindir.', 'vitrin-hizli' ); ?></p>
			</div>
		</div>
		<?php
		wp_print_inline_script_tag(
			"(function(g){if(!g)return;g.addEventListener('click',function(e){var a=e.target.getAttribute('data-vt-age');" .
			"if(a==='evet'){document.cookie='vt_18=1;path=/;max-age=2592000;SameSite=Lax';document.documentElement.classList.remove('vt-age');}" .
			"else if(a==='hayir'){g.querySelector('.vt-agegate__no').hidden=false;}});" .
			"if(document.documentElement.classList.contains('vt-age')){g.querySelector('button').focus();}})(document.querySelector('.vt-agegate'));"
		);
	},
	5
);

/*
 * WooCommerce
 */

add_action(
	'init',
	function () {
		// Kenar çubuğu yok, ürünler tam genişlikte; sarmalayıcıyı woocommerce.php veriyor.
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	}
);

// Sepete AJAX ile ürün eklenince üstteki ve alttaki sepet sayısı yenilenir.
add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		$fragments['span.vt-cart-count'] = vitrin_cart_count_html();
		return $fragments;
	}
);

add_filter(
	'woocommerce_breadcrumb_defaults',
	function ( $defaults ) {
		$defaults['delimiter'] = '<span class="vt-crumb-sep" aria-hidden="true"> › </span>';
		return $defaults;
	}
);

/**
 * Kategori sayfasında alt kategoriler (ya da kardeş kategoriler) için hızlı geçiş çipleri.
 * Örn. TEREA sayfasında: Tümü · Tütün Aromalı · Mentollü · Kapsüllü / Meyveli.
 */
function vitrin_subcategory_chips() {
	if ( ! is_product_category() ) {
		return;
	}
	$current  = get_queried_object();
	$parent   = $current;
	$children = vitrin_child_categories( $current );
	if ( ! $children && $current->parent ) {
		$parent   = get_term( $current->parent, 'product_cat' );
		$children = $parent instanceof WP_Term ? vitrin_child_categories( $parent ) : array();
	}
	if ( ! $children ) {
		return;
	}
	echo '<ul class="vt-subcats">';
	printf(
		'<li><a href="%s"%s>%s</a></li>',
		esc_url( vitrin_term_url( $parent ) ),
		$parent->term_id === $current->term_id ? ' aria-current="page"' : '',
		esc_html__( 'Tümü', 'vitrin-hizli' )
	);
	foreach ( $children as $child ) {
		printf(
			'<li><a href="%s"%s>%s</a></li>',
			esc_url( vitrin_term_url( $child ) ),
			$child->term_id === $current->term_id ? ' aria-current="page"' : '',
			esc_html( $child->name )
		);
	}
	echo '</ul>';
}
add_action( 'woocommerce_archive_description', 'vitrin_subcategory_chips', 20 );

// Mağaza sayfası ana sayfa olarak seçilmiş olsa da ana sayfada bu temanın vitrini görünsün.
add_filter(
	'template_include',
	function ( $template ) {
		if ( vitrin_has_wc() && is_front_page() && is_shop() && ! is_paged() ) {
			$front = locate_template( 'front-page.php' );
			if ( $front ) {
				return $front;
			}
		}
		return $template;
	},
	99
);
