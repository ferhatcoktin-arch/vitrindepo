<?php
/**
 * Plugin Name: Vitrin Arayüz
 * Description: iqosvitrin.com.tr için büyük arama kutusu ve ana sayfada kategori rafları (Çok Satanlar tasarımında, yavaşça kendiliğinden kayan, elle kaydırılabilen). Flatsome + mevcut WPCode snippet'leriyle çalışır; eklenti kapatılınca site eski haline döner.
 * Version: 1.5.2
 * Author: IQOS Vitrin
 * Requires Plugins: woocommerce
 * Text Domain: vitrin-arayuz
 */

defined( 'ABSPATH' ) || exit;

define( 'VA_VER', '1.5.2' );
// SiteGround Optimizer küçültülmüş dosyayı tutamaç adıyla (handle.min.css) kaydediyor ve sorgu dizesini siliyor;
// güncellemeden sonra eski dosya gelmesin diye tutamaç ve dosya adında sürüm var.
define( 'VA_H', 'vitrin-arayuz-' . str_replace( '.', '', VA_VER ) );
define( 'VA_URL', plugin_dir_url( __FILE__ ) );

/* ------------------------------------------------------------------
 * Ayarlar
 * ---------------------------------------------------------------- */
function va_ayar( $k ) {
	$a = array(
		// Ana sayfa rafları, yukarıdan aşağıya: [başlık, kategori slug'ları, üst etiket, "Tümünü Gör" adresi (boşsa kategori sayfası)]
		// Kategori listesi boşsa raf "Çok Satanlar" olur (en çok satan, stoktaki ürünler).
		// Bu kategorilerin sayfasında ürünlerin üstünde "Markalar" kayan şeridi gösterilir
		// (şerit "Kayan Şeritler (Marka + TEREA)" WPCode snippet'inden gelir; TEREA kategorilerindeki TEREA şeridine dokunulmaz)
		'marka_kategorileri' => array( 'iqos-cihazlar', 'iluma-i-serisi', 'iluma-serisi', 'yeni-iqos-iluma-i-one', 'yeni-iqos-uluma-i-duo', 'yeni-iqos-iluma-i-prime' ),
		'raflar' => array(
			// 28 Eyl 2026: kullanıcının verdiği sıra (iqosvitrin, iqossepeti ve smartcorestick'te aynı)
			array( 'IQOS ILUMA i ONE', array( 'yeni-iqos-iluma-i-one' ), 'Cihaz', '' ),
			array( 'IQOS ILUMA i DUO', array( 'yeni-iqos-uluma-i-duo' ), 'Cihaz', '' ),
			array( 'IQOS ILUMA i PRIME', array( 'yeni-iqos-iluma-i-prime' ), 'Cihaz', '' ),
			array( 'IQOS Dubai TEREA', array( 'dubai-terea-cesitleri' ), 'TEREA', '' ),
			array( 'IQOS Avrupa TEREA', array( 'avrupa-terea-cesitleri' ), 'TEREA', '' ),
			array( "IQOS Karışık 10'lu Paket", array( 'karisik-terea-cesitleri', 'karisik-remix-terea-cesitleri' ), 'TEREA', '/magaza/?product_cat=karisik-terea-cesitleri,karisik-remix-terea-cesitleri' ),
			array( 'IQOS Kıbrıs TEREA', array( 'kibris-terea-cesitleri' ), 'TEREA', '' ),
			array( 'IQOS Patlatmalı TEREA', array( 'patlatmali-ozel-seri-terea-cesitleri' ), 'TEREA', '' ),
			array( 'IQOS Japonya TEREA', array( 'japonya-terea-cesitleri' ), 'TEREA', '' ),
			array( 'IQOS Premium TEREA', array( 'premium-ozel-seri-terea-cesitleri' ), 'TEREA', '' ),
			array( 'IQOS i ONE + Karton', array( 'kampanyali-urunler-iqos-iluma-i-one-1-karton-karisik-terea' ), 'Kampanya', '' ),
			array( 'IQOS i DUO + Karton', array( 'kampanyali-urunler-iqos-iluma-i-duo-1-karton-karisik-terea' ), 'Kampanya', '' ),
			array( 'IQOS i PRIME + Karton', array( 'kampanyali-urunler-iqos-iluma-i-prime-1-karton-karisik-terea' ), 'Kampanya', '' ),
			array( 'Vozol', array( 'vozol-star-40000' ), 'Vozol Star 40000', '' ),
			array( 'Çok Satanlar', array(), 'IQOS Vitrin seçkisi', '/magaza/?orderby=popularity' ),
		),
	);
	return apply_filters( 'va_ayar', $a[ $k ] ?? null, $k );
}

/* ------------------------------------------------------------------
 * Önizleme modu: etkinleşince arayüzü SADECE yöneticiler görür.
 * Canlıya almak için: Ayarlar → Okuma → "Vitrin Arayüz canlı".
 * ---------------------------------------------------------------- */
function va_gorunur() {
	return (bool) get_option( 'va_canli' ) || current_user_can( 'manage_options' );
}
add_action( 'admin_init', function () {
	register_setting( 'reading', 'va_canli', array( 'type' => 'boolean', 'sanitize_callback' => 'absint', 'default' => 0 ) );
	add_settings_field( 'va_canli', 'Vitrin Arayüz canlı', function () {
		echo '<label><input type="checkbox" name="va_canli" value="1" ' . checked( 1, (int) get_option( 'va_canli' ), false ) . '> Arama kutusu ve ana sayfa rafları tüm ziyaretçilere gösterilsin (işaretli değilse sadece yöneticiler görür)</label>';
	}, 'reading' );
} );
// REST'ten de açılıp kapatılabilsin (/wp-json/wp/v2/settings → va_canli)
add_action( 'init', function () {
	register_setting( 'va', 'va_canli', array( 'type' => 'boolean', 'default' => false, 'show_in_rest' => true, 'description' => 'Vitrin Arayüz canlı' ) );
} );
add_action( 'update_option_va_canli', function () {
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
		sg_cachepress_purge_cache();
	}
} );

/* ------------------------------------------------------------------
 * Raf ayarını sayfaya gidecek hale getir (slug → ID, adres)
 * ---------------------------------------------------------------- */
function va_raflar() {
	$out = array();
	foreach ( va_ayar( 'raflar' ) as $r ) {
		list( $baslik, $sluglar, $ust, $link ) = $r;
		$ids = array();
		foreach ( $sluglar as $s ) {
			$t = get_term_by( 'slug', $s, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) ) {
				$ids[] = (int) $t->term_id;
				if ( ! $link ) {
					$l    = get_term_link( $t );
					$link = is_wp_error( $l ) ? '' : $l;
				}
			}
		}
		if ( $sluglar && ! $ids ) {
			continue; // kategori silinmiş/adı değişmiş: rafı atla
		}
		$out[] = array(
			'b' => $baslik,
			'u' => $ust,
			'k' => implode( ',', $ids ),
			'l' => 0 === strpos( $link, '/' ) ? home_url( $link ) : $link,
		);
	}
	return $out;
}

/* ------------------------------------------------------------------
 * CSS / JS
 * ---------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! va_gorunur() ) {
		return;
	}
	wp_enqueue_style( VA_H, VA_URL . 'assets/va-' . VA_VER . '.css', array(), VA_VER );
	wp_enqueue_script( VA_H, VA_URL . 'assets/va-' . VA_VER . '.js', array(), VA_VER, array( 'strategy' => 'defer', 'in_footer' => true ) );
	$veri = array(
		'api'   => esc_url_raw( rest_url( 'wc/store/v1/products' ) ),
		'arama' => esc_url_raw( home_url( '/' ) ),
		// 1.5.0: kartlarda Sepete Ekle + stok rozetleri (iqv_sales_badges ucu WPCode snippet'inden)
		'ajax'  => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
		'wc'    => class_exists( 'WC_AJAX' ) ? esc_url_raw( WC_AJAX::get_endpoint( 'add_to_cart' ) ) : '',
		'sepet' => function_exists( 'wc_get_cart_url' ) ? esc_url_raw( wc_get_cart_url() ) : '',
		'odeme' => function_exists( 'wc_get_checkout_url' ) ? esc_url_raw( wc_get_checkout_url() ) : '',
	);
	if ( is_front_page() ) {
		$veri['raflar'] = va_raflar();
		// Eski kategori kataloğu ve eski Çok Satanlar yerini raflara bırakır (snippet'ler silinmedi, sadece gizli)
		wp_add_inline_style( VA_H, 'body.home #ivk-katalog,body.home #iqv-best{display:none!important}' );
	}
	wp_localize_script( VA_H, 'VA', $veri );
}, 20 );

/* ------------------------------------------------------------------
 * Üst şerit: büyük arama kutusu
 * ---------------------------------------------------------------- */
function va_ust_serit() {
	static $basildi = false;
	if ( $basildi || is_admin() || ! va_gorunur() ) {
		return;
	}
	$basildi = true;
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
			<?php if ( is_front_page() ) : ?>
			<ul class="va-guven" aria-label="Alışveriş güvenceleri">
				<li class="ana"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 9.5v5M18 9.5v5"/></svg><span><b>Kapıda Ödeme</b><small>İstanbul içi</small></span></li>
				<li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="17" cy="17.5" r="1.6"/></svg><span><b>Aynı Gün Teslim</b><small>Motor kurye</small></span></li>
				<li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/></svg><span><b>%100 Orijinal</b><small>Garantili ürün</small></span></li>
			</ul>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( is_front_page() ) : ?>
	<div id="vr-raflar-yer"></div><?php // mobilde raflar buraya, arama + güven şeridinin hemen altına yerleşir ?>
	<?php endif; ?>
	<?php
}
add_action( 'flatsome_after_header', 'va_ust_serit' );
add_action( 'wp_body_open', function () {
	if ( 'flatsome' !== get_template() ) {
		va_ust_serit();
	}
}, 20 );

/* ------------------------------------------------------------------
 * Cihaz kategorilerinde "Markalar" kayan şeridi
 * ---------------------------------------------------------------- */
add_action( 'woocommerce_before_shop_loop', function () {
	if ( ! va_gorunur() || ! function_exists( 'ivs_marka_seridi' ) || ! is_product_category() ) {
		return;
	}
	$t = get_queried_object();
	if ( $t && ! empty( $t->slug ) && in_array( $t->slug, (array) va_ayar( 'marka_kategorileri' ), true ) ) {
		echo ivs_marka_seridi(); // phpcs:ignore -- snippet kendi çıktısını kaçışlıyor
	}
}, 4 );
