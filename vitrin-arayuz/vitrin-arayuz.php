<?php
/**
 * Plugin Name: Vitrin Arayüz
 * Description: iqosvitrin.com.tr için hızlı ve sade arayüz: büyük arama kutusu, 5 başlıklı menü, ana sayfa blokları ([vitrin_ana]) ve mobil alt menü çubuğu. Flatsome ile çalışır; eklenti kapatılınca site eski haline döner.
 * Version: 1.0.1
 * Author: IQOS Vitrin
 * Requires Plugins: woocommerce
 * Text Domain: vitrin-arayuz
 */

defined( 'ABSPATH' ) || exit;

define( 'VA_VER', '1.0.1' );
define( 'VA_URL', plugin_dir_url( __FILE__ ) );
define( 'VA_DIR', plugin_dir_path( __FILE__ ) );

/* ------------------------------------------------------------------
 * Ayarlar — gerekirse burayı değiştir.
 * ---------------------------------------------------------------- */
function va_ayar( $k ) {
	$a = array(
		'whatsapp'   => '905548920404',
		'wa_mesaj'   => 'Merhaba, sipariş vermek istiyorum.',
		// Menü: [başlık, kategori slug'ı ya da tam URL, alt kategoriler gösterilsin mi]
		'menu'       => array(
			array( 'IQOS Cihazlar', 'iqos-cihazlar', true ),
			array( 'TEREA', 'terea', true ),
			array( 'Setler & Kampanyalar', 'setler-kampanyalar', false ),
			array( 'Vozol', 'vozol-star-40000', false ),
			array( 'Çok Satanlar', '/magaza/?orderby=popularity', false ),
		),
		// Ana sayfa kategori kutuları (sırasıyla)
		'kutular'    => array( 'iluma-i-serisi', 'terea-tutun-aromali', 'terea-mentollu', 'terea-kapsullu-meyveli', 'setler-kampanyalar', 'vozol-star-40000' ),
		// Aroma seçici sekmeleri: [etiket, kategori slug'ı, renk]
		'aromalar'   => array(
			array( 'Tütün', 'terea-tutun-aromali', '#b0783f' ),
			array( 'Mentollü', 'terea-mentollu', '#1aa39a' ),
			array( 'Kapsüllü / Meyveli', 'terea-kapsullu-meyveli', '#8e4fc4' ),
			array( 'Vozol', 'vozol-star-40000', '#e0567a' ),
		),
		'cok_satan'  => 10,
		'bar_gizle'  => true, // WP Bottom Menu eklentisinin çubuğunu gizle
	);
	return apply_filters( 'va_ayar', $a[ $k ] ?? null, $k );
}

/* ------------------------------------------------------------------
 * Önizleme modu: eklenti etkinleşince arayüzü SADECE yöneticiler görür.
 * Canlıya almak için: Ayarlar → Okuma sayfasının altındaki
 * "Vitrin Arayüz canlı" kutusunu işaretle.
 * ---------------------------------------------------------------- */
function va_gorunur() {
	return (bool) get_option( 'va_canli' ) || current_user_can( 'manage_options' );
}
add_action( 'admin_init', function () {
	register_setting( 'reading', 'va_canli', array( 'type' => 'boolean', 'sanitize_callback' => 'absint', 'default' => 0 ) );
	add_settings_field( 'va_canli', 'Vitrin Arayüz canlı', function () {
		echo '<label><input type="checkbox" name="va_canli" value="1" ' . checked( 1, (int) get_option( 'va_canli' ), false ) . '> Arama, menü, ana sayfa blokları ve mobil alt çubuk tüm ziyaretçilere gösterilsin (işaretli değilse sadece yöneticiler görür)</label>';
	}, 'reading' );
} );
add_action( 'update_option_va_canli', function () {
	va_cache_bos();
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
		sg_cachepress_purge_cache();
	}
} );

/* ------------------------------------------------------------------
 * Yardımcılar
 * ---------------------------------------------------------------- */
function va_link( $hedef ) {
	if ( 0 === strpos( $hedef, '/' ) || 0 === strpos( $hedef, 'http' ) ) {
		return 0 === strpos( $hedef, '/' ) ? home_url( $hedef ) : $hedef;
	}
	$t = get_term_by( 'slug', $hedef, 'product_cat' );
	if ( ! $t ) {
		return '';
	}
	$l = get_term_link( $t );
	return is_wp_error( $l ) ? '' : $l;
}

function va_kat( $slug ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	return ( $t && ! is_wp_error( $t ) ) ? $t : null;
}

/** Kategorinin görseli; yoksa kategorideki ilk stoklu ürünün görseli. */
function va_kat_gorsel_id( $t ) {
	$id = (int) get_term_meta( $t->term_id, 'thumbnail_id', true );
	if ( $id ) {
		return $id;
	}
	$q = wc_get_products( array(
		'status'       => 'publish',
		'limit'        => 1,
		'category'     => array( $t->slug ),
		'stock_status' => 'instock',
		'orderby'      => 'popularity',
		'return'       => 'ids',
	) );
	return $q ? (int) get_post_thumbnail_id( $q[0] ) : 0;
}

function va_wa_link() {
	return 'https://wa.me/' . va_ayar( 'whatsapp' ) . '?text=' . rawurlencode( va_ayar( 'wa_mesaj' ) );
}

/** Önbellek: ürün/kategori değişince temizlenir. */
function va_cache_get( $k ) {
	return get_transient( 'va_' . $k . '_' . get_option( 'va_cache_v', 1 ) );
}
function va_cache_set( $k, $v ) {
	set_transient( 'va_' . $k . '_' . get_option( 'va_cache_v', 1 ), $v, 6 * HOUR_IN_SECONDS );
}
function va_cache_bos() {
	update_option( 'va_cache_v', (int) get_option( 'va_cache_v', 1 ) + 1, false );
}
add_action( 'save_post_product', 'va_cache_bos' );
add_action( 'woocommerce_product_set_stock_status', 'va_cache_bos' );
add_action( 'edited_product_cat', 'va_cache_bos' );
add_action( 'created_product_cat', 'va_cache_bos' );
add_action( 'woocommerce_order_status_completed', 'va_cache_bos' );

/* ------------------------------------------------------------------
 * CSS / JS — sadece ön yüzde, küçük ve ertelenmiş
 * ---------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! va_gorunur() ) {
		return;
	}
	wp_enqueue_style( 'vitrin-arayuz', VA_URL . 'assets/va.css', array(), VA_VER );
	if ( va_ayar( 'bar_gizle' ) ) {
		wp_add_inline_style( 'vitrin-arayuz', '.wp-bottom-menu{display:none!important}' );
	}
	wp_enqueue_script( 'vitrin-arayuz', VA_URL . 'assets/va.js', array(), VA_VER, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script( 'vitrin-arayuz', 'VA', array(
		'api'    => esc_url_raw( rest_url( 'wc/store/v1/products' ) ),
		'arama'  => esc_url_raw( home_url( '/' ) ),
		'sepet'  => esc_url_raw( wc_get_cart_url() ),
		'para'   => get_woocommerce_currency_symbol(),
	) );
}, 20 );

/* ------------------------------------------------------------------
 * 1) Üst şerit: büyük arama + 5 başlıklı menü
 * ---------------------------------------------------------------- */
function va_ust_serit() {
	static $basildi = false;
	if ( $basildi || is_admin() || ! va_gorunur() ) {
		return;
	}
	$basildi = true;
	$aktif   = '';
	if ( is_product_category() ) {
		$q     = get_queried_object();
		$aktif = $q->slug;
		if ( $q->parent ) {
			$p = get_term( $q->parent, 'product_cat' );
			if ( $p && ! is_wp_error( $p ) ) {
				$aktif .= ' ' . $p->slug;
			}
		}
	}
	?>
	<div class="va-serit" id="va-serit">
		<div class="va-kap">
			<form class="va-ara" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" autocomplete="off">
				<svg class="va-ara-ikon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
				<input type="search" name="s" id="va-ara-kutu" placeholder="TEREA aroması, cihaz ya da renk ara…" aria-label="Ürün ara" value="<?php echo esc_attr( get_search_query() ); ?>">
				<input type="hidden" name="post_type" value="product">
				<button type="submit">Ara</button>
				<div class="va-oneri" id="va-oneri" role="listbox" hidden></div>
			</form>
			<nav class="va-menu" aria-label="Kategoriler">
				<ul>
				<?php foreach ( va_ayar( 'menu' ) as $m ) :
					list( $baslik, $hedef, $alt ) = $m;
					$url = va_link( $hedef );
					if ( ! $url ) {
						continue;
					}
					$cocuklar = array();
					if ( $alt && ( $t = va_kat( $hedef ) ) ) {
						$cocuklar = get_terms( array(
							'taxonomy'   => 'product_cat',
							'parent'     => $t->term_id,
							'hide_empty' => true,
							'orderby'    => 'menu_order',
						) );
						$cocuklar = is_wp_error( $cocuklar ) ? array() : $cocuklar;
					}
					$cls = ( $aktif && false !== strpos( ' ' . $aktif . ' ', ' ' . $hedef . ' ' ) ) ? ' aktif' : '';
					?>
					<li class="<?php echo $cocuklar ? 'va-alt' : ''; echo esc_attr( $cls ); ?>">
						<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $baslik ); ?></a>
						<?php if ( $cocuklar ) : ?>
							<ul>
								<?php foreach ( $cocuklar as $c ) : ?>
									<li><a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?> <small><?php echo (int) $c->count; ?></small></a></li>
								<?php endforeach; ?>
								<li><a href="<?php echo esc_url( $url ); ?>"><strong>Tümünü gör →</strong></a></li>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				</ul>
			</nav>
		</div>
	</div>
	<?php
}
add_action( 'flatsome_after_header', 'va_ust_serit' );
// Flatsome dışı temada yedek konum
add_action( 'wp_body_open', function () {
	if ( 'flatsome' !== get_template() ) {
		va_ust_serit();
	}
}, 20 );

/* ------------------------------------------------------------------
 * 2) Ana sayfa blokları: [vitrin_ana]  (ya da tek tek:
 *    [vitrin_kutular] [vitrin_aroma] [vitrin_cok_satan])
 * ---------------------------------------------------------------- */
function va_kart( $p ) {
	$img   = $p->get_image_id();
	$stok  = $p->is_in_stock();
	$url   = get_permalink( $p->get_id() );
	ob_start();
	?>
	<div class="va-kart<?php echo $stok ? '' : ' tukendi'; ?>">
		<a class="va-kart-gorsel" href="<?php echo esc_url( $url ); ?>">
			<?php echo $img ? wp_get_attachment_image( $img, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width:600px) 45vw, 220px' ) ) : wc_placeholder_img( 'woocommerce_thumbnail' ); ?>
			<?php if ( $p->is_on_sale() ) : ?><span class="va-rozet">İndirim</span><?php endif; ?>
			<?php if ( ! $stok ) : ?><span class="va-rozet gri">Tükendi</span><?php endif; ?>
		</a>
		<a class="va-kart-ad" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $p->get_name() ); ?></a>
		<div class="va-kart-fiyat"><?php echo wp_kses_post( $p->get_price_html() ); ?></div>
		<?php if ( $stok && $p->is_type( 'simple' ) ) : ?>
			<a class="va-sepet add_to_cart_button ajax_add_to_cart" href="<?php echo esc_url( $p->add_to_cart_url() ); ?>" data-quantity="1" data-product_id="<?php echo (int) $p->get_id(); ?>" rel="nofollow">Sepete Ekle</a>
		<?php else : ?>
			<a class="va-sepet ikincil" href="<?php echo esc_url( $url ); ?>"><?php echo $stok ? 'İncele' : 'Haber Ver'; ?></a>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function va_sc_kutular() {
	if ( $h = va_cache_get( 'kutular' ) ) {
		return $h;
	}
	$h = '<section class="va-blok"><div class="va-baslik"><h2>Kategoriler</h2></div><div class="va-kutular">';
	foreach ( va_ayar( 'kutular' ) as $slug ) {
		$t = va_kat( $slug );
		if ( ! $t || ! $t->count ) {
			continue;
		}
		$img = va_kat_gorsel_id( $t );
		$ust = $t->parent ? get_term( $t->parent, 'product_cat' ) : null;
		$h  .= '<a class="va-kutu" href="' . esc_url( get_term_link( $t ) ) . '">'
			. '<span class="va-kutu-gorsel">' . ( $img ? wp_get_attachment_image( $img, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width:600px) 45vw, 180px' ) ) : '' ) . '</span>'
			. '<span class="va-kutu-ad">' . ( $ust && ! is_wp_error( $ust ) && 'TEREA' === $ust->name ? '<small>TEREA</small>' : '' ) . esc_html( $t->name ) . '</span>'
			. '<span class="va-kutu-say">' . (int) $t->count . ' ürün</span></a>';
	}
	$h .= '</div></section>';
	va_cache_set( 'kutular', $h );
	return $h;
}

function va_sc_aroma() {
	$sekmeler = array();
	foreach ( va_ayar( 'aromalar' ) as $a ) {
		$t = va_kat( $a[1] );
		if ( $t && $t->count ) {
			$sekmeler[] = array( $a[0], $t, $a[2] );
		}
	}
	if ( ! $sekmeler ) {
		return '';
	}
	$ilk = $sekmeler[0][1];
	// İlk sekme sunucuda basılır (hızlı ilk görüntü, SEO); diğerleri tıklanınca API'den gelir.
	$ilk_html = va_cache_get( 'aroma_ilk' );
	if ( ! $ilk_html ) {
		$ilk_html = '';
		$liste = va_urunler( array( 'category' => array( $ilk->slug ), 'limit' => 12 ) );
		usort( $liste, function ( $a, $b ) {
			return (int) $b->is_in_stock() - (int) $a->is_in_stock(); // stoktakiler önce
		} );
		foreach ( $liste as $p ) {
			$ilk_html .= va_kart( $p );
		}
		va_cache_set( 'aroma_ilk', $ilk_html );
	}
	$h  = '<section class="va-blok va-aroma"><div class="va-baslik"><h2>Aromanı Seç</h2><a id="va-aroma-tum" href="' . esc_url( get_term_link( $ilk ) ) . '">Tümü →</a></div>';
	$h .= '<div class="va-sekmeler" role="tablist">';
	foreach ( $sekmeler as $i => $s ) {
		$h .= '<button type="button" role="tab" class="va-sekme' . ( 0 === $i ? ' aktif' : '' ) . '" style="--renk:' . esc_attr( $s[2] ) . '" data-kat="' . (int) $s[1]->term_id . '" data-link="' . esc_url( get_term_link( $s[1] ) ) . '" aria-selected="' . ( 0 === $i ? 'true' : 'false' ) . '">' . esc_html( $s[0] ) . '</button>';
	}
	$h .= '</div><div class="va-serit-urun" id="va-aroma-liste">' . $ilk_html . '</div></section>';
	return $h;
}

function va_urunler( $args ) {
	return wc_get_products( array_merge( array(
		'status'  => 'publish',
		'orderby' => 'popularity',
		'order'   => 'DESC',
	), $args ) );
}

function va_sc_cok_satan() {
	if ( $h = va_cache_get( 'cok_satan' ) ) {
		return $h;
	}
	$kartlar = '';
	foreach ( va_urunler( array( 'limit' => va_ayar( 'cok_satan' ), 'stock_status' => 'instock' ) ) as $p ) {
		$kartlar .= va_kart( $p );
	}
	$h = '<section class="va-blok"><div class="va-baslik"><h2>Çok Satanlar</h2><a href="' . esc_url( home_url( '/magaza/?orderby=popularity' ) ) . '">Tümü →</a></div><div class="va-izgara">' . $kartlar . '</div></section>';
	va_cache_set( 'cok_satan', $h );
	return $h;
}

add_shortcode( 'vitrin_kutular', 'va_sc_kutular' );
add_shortcode( 'vitrin_aroma', 'va_sc_aroma' );
add_shortcode( 'vitrin_cok_satan', 'va_sc_cok_satan' );
add_shortcode( 'vitrin_ana', function () {
	if ( ! va_gorunur() ) {
		return '';
	}
	wp_enqueue_script( 'wc-add-to-cart' );
	return '<div class="va-ana">' . va_sc_kutular() . va_sc_aroma() . va_sc_cok_satan() . '</div>';
} );

/* ------------------------------------------------------------------
 * 3) Mobil alt menü çubuğu
 * ---------------------------------------------------------------- */
add_action( 'wp_footer', function () {
	if ( is_checkout() || ! va_gorunur() ) {
		return; // ödeme sayfasında dikkat dağıtmasın
	}
	$adet = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
	?>
	<nav class="va-alt-bar" aria-label="Hızlı menü">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="<?php echo is_front_page() ? 'aktif' : ''; ?>">
			<svg viewBox="0 0 24 24"><path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/></svg><span>Ana Sayfa</span></a>
		<a href="<?php echo esc_url( home_url( '/magaza/' ) ); ?>" data-va="kategoriler">
			<svg viewBox="0 0 24 24"><rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/></svg><span>Kategoriler</span></a>
		<a href="#va-serit" data-va="ara">
			<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><span>Ara</span></a>
		<a href="<?php echo esc_url( va_wa_link() ); ?>" class="wa" target="_blank" rel="noopener">
			<svg viewBox="0 0 24 24"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a4 4 0 0 1-2-2l.8-1-1-2z"/></svg><span>WhatsApp</span></a>
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
			<svg viewBox="0 0 24 24"><path d="M3 4h2l2.4 11h10.2L20 7H6.2"/><circle cx="9" cy="19.5" r="1.3"/><circle cx="17" cy="19.5" r="1.3"/></svg>
			<b class="va-sepet-adet"<?php echo $adet ? '' : ' hidden'; ?>><?php echo (int) $adet; ?></b><span>Sepet</span></a>
	</nav>
	<div class="va-cekmece" id="va-cekmece" hidden>
		<div class="va-cekmece-ic">
			<div class="va-cekmece-bas"><strong>Kategoriler</strong><button type="button" data-va="kapat" aria-label="Kapat">✕</button></div>
			<?php foreach ( va_ayar( 'menu' ) as $m ) :
				$url = va_link( $m[1] );
				if ( ! $url ) {
					continue;
				}
				echo '<a class="ana" href="' . esc_url( $url ) . '">' . esc_html( $m[0] ) . '</a>';
				if ( $m[2] && ( $t = va_kat( $m[1] ) ) ) {
					$c = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $t->term_id, 'hide_empty' => true, 'orderby' => 'menu_order' ) );
					foreach ( is_wp_error( $c ) ? array() : $c as $k ) {
						echo '<a class="alt" href="' . esc_url( get_term_link( $k ) ) . '">' . esc_html( $k->name ) . '</a>';
					}
				}
			endforeach; ?>
		</div>
	</div>
	<?php
}, 5 );

// Sepete ekleyince alt çubuktaki sayı güncellensin (sayfa önbellekli olsa bile)
add_filter( 'woocommerce_add_to_cart_fragments', function ( $f ) {
	$adet = WC()->cart->get_cart_contents_count();
	$f['b.va-sepet-adet'] = '<b class="va-sepet-adet"' . ( $adet ? '' : ' hidden' ) . '>' . (int) $adet . '</b>';
	return $f;
} );
