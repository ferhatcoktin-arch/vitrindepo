<?php
/**
 * Yerel test sitesine kategori-yapisi.json'daki kategorileri, örnek ürünleri (çizilmiş görsellerle) ve sayfaları ekler.
 * Kullanım: php tohum.php <wordpress klasörü>
 */

$_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
require $argv[1] . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

switch_theme( 'vitrin-hizli' );
check_theme_switched();
// Kurulumun varsayılan bileşenleri (Arşivler, Kategoriler...) temanın alanlarına taşınmasın.
$sidebars = wp_get_sidebars_widgets();
foreach ( array( 'footer', 'anasayfa-ust' ) as $sidebar ) {
	$sidebars['wp_inactive_widgets'] = array_merge( (array) ( $sidebars['wp_inactive_widgets'] ?? array() ), (array) ( $sidebars[ $sidebar ] ?? array() ) );
	$sidebars[ $sidebar ]            = array();
}
wp_set_sidebars_widgets( $sidebars );
update_option( 'blogdescription', 'IQOS ILUMA Cihazları ve TEREA Çeşitleri' );

$structure = json_decode( file_get_contents( dirname( __DIR__, 2 ) . '/kategori-yapisi.json' ), true );

function tohum_kategoriler( $defs, $parent = 0 ) {
	foreach ( $defs as $def ) {
		$term = term_exists( $def['slug'], 'product_cat' );
		if ( ! $term ) {
			$term = wp_insert_term( $def['name'], 'product_cat', array( 'slug' => $def['slug'], 'parent' => $parent ) );
		}
		tohum_kategoriler( isset( $def['children'] ) ? $def['children'] : array(), (int) $term['term_id'] );
	}
}
tohum_kategoriler( $structure['categories'] );

function tohum_kutu( $im, $x1, $y1, $x2, $y2, $r, $color ) {
	imagefilledrectangle( $im, $x1 + $r, $y1, $x2 - $r, $y2, $color );
	imagefilledrectangle( $im, $x1, $y1 + $r, $x2, $y2 - $r, $color );
	foreach ( array( array( $x1 + $r, $y1 + $r ), array( $x2 - $r, $y1 + $r ), array( $x1 + $r, $y2 - $r ), array( $x2 - $r, $y2 - $r ) ) as $c ) {
		imagefilledellipse( $im, $c[0], $c[1], 2 * $r, 2 * $r, $color );
	}
}

function tohum_yazi( $im, $size, $y, $color, $text ) {
	$font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
	$box  = imagettfbbox( $size, 0, $font, $text );
	imagettftext( $im, $size, 0, (int) ( 400 - ( $box[2] - $box[0] ) / 2 ), $y, $color, $font, $text );
}

function tohum_gorsel( $title, $hex, $kind ) {
	$im = imagecreatetruecolor( 800, 800 );
	imagefill( $im, 0, 0, imagecolorallocate( $im, 244, 245, 247 ) );
	list( $r, $g, $b ) = sscanf( $hex, '#%02x%02x%02x' );
	$color = imagecolorallocate( $im, $r, $g, $b );
	$white = imagecolorallocate( $im, 255, 255, 255 );
	$dark  = imagecolorallocate( $im, 28, 30, 36 );

	if ( 'terea' === $kind ) {
		tohum_kutu( $im, 240, 120, 560, 680, 26, $color );
		imagefilledrectangle( $im, 240, 250, 560, 335, $white );
		tohum_yazi( $im, 38, 310, $dark, 'TEREA' );
		tohum_yazi( $im, 30, 450, $white, trim( str_ireplace( 'TEREA', '', $title ) ) );
	} elseif ( 'cihaz' === $kind ) {
		tohum_kutu( $im, 335, 110, 465, 690, 64, $color );
		imagefilledellipse( $im, 400, 215, 30, 30, $white );
	} elseif ( 'set' === $kind ) {
		tohum_kutu( $im, 180, 200, 400, 640, 22, imagecolorallocate( $im, 199, 129, 42 ) );
		tohum_kutu( $im, 470, 150, 580, 650, 55, $color );
		tohum_yazi( $im, 40, 740, $dark, 'SET' );
	} else {
		tohum_kutu( $im, 230, 230, 570, 570, 40, $color );
		tohum_yazi( $im, 26, 700, $dark, mb_strtoupper( wp_trim_words( str_replace( 'IQOS ', '', $title ), 3, '' ), 'UTF-8' ) );
	}

	$upload = wp_upload_dir();
	$file   = $upload['path'] . '/' . sanitize_title( $title ) . '.png';
	imagepng( $im, $file );
	imagedestroy( $im );

	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => $title,
			'post_status'    => 'inherit',
		),
		$file
	);
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	return $id;
}

// Ad, kategori, fiyat, indirimli fiyat, renk, görsel türü, satış adedi.
$products = array(
	array( 'IQOS ILUMA i PRIME Jade Green', 'iluma-i-serisi', 3999, '', '#2f6f5e', 'cihaz', 140 ),
	array( 'IQOS ILUMA i Midnight Black', 'iluma-i-serisi', 2799, 2499, '#23262d', 'cihaz', 210 ),
	array( 'IQOS ILUMA i ONE Breeze Blue', 'iluma-i-serisi', 1599, '', '#5b8fb9', 'cihaz', 260 ),
	array( 'IQOS ILUMA PRIME Obsidian Black', 'iluma-serisi', 3299, '', '#30343c', 'cihaz', 45 ),
	array( 'IQOS ILUMA ONE Moss Green', 'iluma-serisi', 1299, '', '#5d7a4a', 'cihaz', 60 ),
	array( 'TEREA Amber', 'terea-tutun-aromali', 95, '', '#c7812a', 'terea', 980 ),
	array( 'TEREA Yellow', 'terea-tutun-aromali', 95, '', '#e2b929', 'terea', 640 ),
	array( 'TEREA Silver', 'terea-tutun-aromali', 95, '', '#8f99a4', 'terea', 310 ),
	array( 'TEREA Russet', 'terea-tutun-aromali', 95, '', '#8e3b2b', 'terea', 220 ),
	array( 'TEREA Teak', 'terea-tutun-aromali', 95, '', '#7a5332', 'terea', 150 ),
	array( 'TEREA Turquoise', 'terea-mentollu', 95, '', '#1d9aa5', 'terea', 870 ),
	array( 'TEREA Green', 'terea-mentollu', 95, '', '#3c8d4f', 'terea', 410 ),
	array( 'TEREA Blue', 'terea-mentollu', 95, '', '#2c5fa8', 'terea', 330 ),
	array( 'TEREA Purple Wave', 'terea-kapsullu-meyveli', 95, '', '#7b3fa0', 'terea', 520 ),
	array( 'TEREA Sun Pearl', 'terea-kapsullu-meyveli', 95, '', '#e08a1e', 'terea', 380 ),
	array( 'TEREA Starling Pearl', 'terea-kapsullu-meyveli', 95, '', '#b0365f', 'terea', 290 ),
	array( 'TEREA Green Zing', 'terea-kapsullu-meyveli', 95, '', '#6aa832', 'terea', 170 ),
	array( 'IQOS ILUMA i ONE + 10 Paket TEREA Kampanya Seti', 'setler-kampanyalar', 2449, 2199, '#5b8fb9', 'set', 190 ),
	array( 'IQOS ILUMA i PRIME Deri Kılıf', 'kilif-kapak', 449, '', '#6b4a36', 'aksesuar', 85 ),
	array( 'IQOS ILUMA i Ön Kapak', 'kilif-kapak', 299, '', '#c9a86a', 'aksesuar', 70 ),
	array( 'IQOS USB-C Şarj Kablosu', 'sarj-kablo', 149, '', '#50555e', 'aksesuar', 120 ),
	array( 'IQOS Hızlı Şarj Adaptörü', 'sarj-kablo', 249, '', '#3a3f47', 'aksesuar', 55 ),
);

foreach ( $products as $p ) {
	list( $name, $category, $regular, $sale, $color, $kind, $sales ) = $p;
	$existing = get_posts( array( 'post_type' => 'product', 'title' => $name, 'fields' => 'ids' ) );
	if ( $existing ) {
		continue;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'product',
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_content' => 'Örnek ürün açıklaması. Orijinal, faturalı ürün; kapıda ödeme ile gönderilir.',
		)
	);
	wp_set_object_terms( $id, $category, 'product_cat' );
	update_post_meta( $id, '_regular_price', $regular );
	update_post_meta( $id, '_price', '' === $sale ? $regular : $sale );
	if ( '' !== $sale ) {
		update_post_meta( $id, '_sale_price', $sale );
	}
	update_post_meta( $id, 'total_sales', $sales );
	set_post_thumbnail( $id, tohum_gorsel( $name, $color, $kind ) );
}

$pages = array(
	'sepet'      => array( 'Sepet', 'Sepetinizde 2 ürün var.' ),
	'odeme'      => array( 'Ödeme', 'Ödeme formu burada.' ),
	'hesabim'    => array( 'Hesabım', 'Giriş yapın ya da kayıt olun.' ),
	'hakkimizda' => array( 'Hakkımızda', "IQOS Vitrin, orijinal IQOS ILUMA cihazları ve TEREA çeşitlerini güvenli kapıda ödeme ile gönderir.\n\nSiparişleriniz aynı gün kargoya verilir." ),
);
$footer_items = array();
foreach ( $pages as $slug => $page ) {
	$found = get_page_by_path( $slug );
	$id    = $found ? $found->ID : wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $page[0],
			'post_content' => $page[1],
		)
	);
	if ( in_array( $slug, array( 'hakkimizda', 'hesabim' ), true ) ) {
		$footer_items[] = $id;
	}
}

if ( ! wp_get_nav_menu_object( 'Alt Menü' ) ) {
	$menu_id = wp_create_nav_menu( 'Alt Menü' );
	foreach ( $footer_items as $page_id ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-object-id' => $page_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}
	set_theme_mod( 'nav_menu_locations', array( 'footer' => $menu_id ) );
}

flush_rewrite_rules();
echo 'tohum: ' . wp_count_posts( 'product' )->publish . " ürün, tema: " . get_stylesheet() . "\n";
