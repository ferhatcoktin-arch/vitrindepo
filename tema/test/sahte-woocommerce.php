<?php
/**
 * Plugin Name: Sahte WooCommerce (yalnızca yerel tema testi)
 * Description: Temanın kullandığı WooCommerce parçalarını, gerçek WooCommerce'in HTML çıktısıyla aynı yapıda taklit eder. Siteye yüklenmez.
 *
 * Taklit edilenler: product / product_cat, [products] kısa kodu, woocommerce_content(), içerik ekmek kırıntısı,
 * Store API ürün araması (wc/store/v1/products), sepet sayısı, WooCommerce gövde sınıfları ve şablon seçimi.
 */

class WooCommerce {
	public $cart;

	public function __construct() {
		$this->cart = new Sahte_WC_Cart();
	}
}

class Sahte_WC_Cart {
	public function get_cart_contents_count() {
		return 2;
	}
}

function WC() { // phpcs:ignore WordPress.NamingConventions
	static $wc = null;
	if ( null === $wc ) {
		$wc = new WooCommerce();
	}
	return $wc;
}

add_action(
	'init',
	function () {
		register_post_type(
			'product',
			array(
				'label'       => 'Ürünler',
				'public'      => true,
				'has_archive' => 'magaza',
				'rewrite'     => array( 'slug' => 'urun' ),
				'supports'    => array( 'title', 'editor', 'thumbnail' ),
			)
		);
		register_taxonomy(
			'product_cat',
			'product',
			array(
				'label'        => 'Ürün kategorileri',
				'hierarchical' => true,
				'public'       => true,
				'rewrite'      => array(
					'slug'         => 'urun-kategori',
					'hierarchical' => true,
				),
			)
		);
		add_image_size( 'woocommerce_thumbnail', 400, 400, true );
		add_shortcode( 'products', 'sahte_wc_products_shortcode' );
	},
	5
);

// Gerçek WooCommerce stilleri de 10 önceliğiyle eklenir.
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'woocommerce-general', content_url( 'mu-plugins/sahte-woocommerce.css' ), array(), '1' );
	}
);

function wc_get_page_permalink( $page ) {
	if ( 'shop' === $page ) {
		return get_post_type_archive_link( 'product' );
	}
	$slugs = array(
		'myaccount' => 'hesabim',
		'cart'      => 'sepet',
		'checkout'  => 'odeme',
	);
	$post  = get_page_by_path( isset( $slugs[ $page ] ) ? $slugs[ $page ] : $page );
	return $post ? get_permalink( $post ) : home_url( '/' );
}

function wc_get_cart_url() {
	return wc_get_page_permalink( 'cart' );
}

function is_shop() {
	return is_post_type_archive( 'product' );
}

function is_product_category( $term = '' ) {
	return is_tax( 'product_cat', $term );
}

function is_product() {
	return is_singular( 'product' );
}

function is_woocommerce() {
	return is_shop() || is_product_category() || is_product();
}

function is_cart() {
	return is_page( 'sepet' );
}

function is_checkout() {
	return is_page( 'odeme' );
}

function is_account_page() {
	return is_page( 'hesabim' );
}

add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_woocommerce() ) {
			$classes[] = 'woocommerce';
			$classes[] = 'woocommerce-page';
		}
		foreach ( array( 'cart', 'checkout', 'account' ) as $page ) {
			if ( call_user_func( 'account' === $page ? 'is_account_page' : 'is_' . $page ) ) {
				$classes[] = 'woocommerce-' . $page;
				$classes[] = 'woocommerce-page';
			}
		}
		return $classes;
	}
);

add_filter(
	'template_include',
	function ( $template ) {
		if ( is_woocommerce() ) {
			$woocommerce = locate_template( 'woocommerce.php' );
			if ( $woocommerce ) {
				return $woocommerce;
			}
		}
		return $template;
	}
);

function sahte_wc_price( $amount ) {
	return '<span class="woocommerce-Price-amount amount"><bdi>' . number_format( $amount, 2, ',', '.' ) . '&nbsp;<span class="woocommerce-Price-currencySymbol">&#8378;</span></bdi></span>';
}

function sahte_wc_price_html( $id ) {
	$regular = (float) get_post_meta( $id, '_regular_price', true );
	$sale    = get_post_meta( $id, '_sale_price', true );
	if ( '' === $sale ) {
		return sahte_wc_price( $regular );
	}
	return '<del aria-hidden="true">' . sahte_wc_price( $regular ) . '</del> <span class="screen-reader-text">Orijinal fiyat: ' . number_format( $regular, 2, ',', '.' ) . ' ₺.</span><ins aria-hidden="true">' . sahte_wc_price( (float) $sale ) . '</ins><span class="screen-reader-text">Şu andaki fiyat: ' . number_format( (float) $sale, 2, ',', '.' ) . ' ₺.</span>';
}

/**
 * WooCommerce'in content-product.php şablonunun varsayılan çıktısı.
 */
function sahte_wc_loop_item( $post, $index, $columns ) {
	$id      = $post->ID;
	$sale    = '' !== get_post_meta( $id, '_sale_price', true );
	$classes = array( 'product', 'type-product', 'post-' . $id, 'status-publish', 'instock', 'has-post-thumbnail', 'shipping-taxable', 'purchasable', 'product-type-simple' );
	if ( 0 === $index % $columns ) {
		$classes[] = 'first';
	} elseif ( $columns - 1 === $index % $columns ) {
		$classes[] = 'last';
	}
	if ( $sale ) {
		$classes[] = 'sale';
	}
	$title = get_the_title( $post );
	echo '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
	echo '<a href="' . esc_url( get_permalink( $post ) ) . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">';
	if ( $sale ) {
		echo '<span class="onsale">İndirim!</span>';
	}
	echo get_the_post_thumbnail( $post, 'woocommerce_thumbnail' );
	echo '<h2 class="woocommerce-loop-product__title">' . esc_html( $title ) . '</h2>';
	echo '<span class="price">' . sahte_wc_price_html( $id ) . '</span>';
	echo "\n</a>";
	echo '<a href="?add-to-cart=' . $id . '" aria-describedby="woocommerce_loop_add_to_cart_link_describedby_' . $id . '" data-quantity="1" class="button product_type_simple add_to_cart_button ajax_add_to_cart" data-product_id="' . $id . '" data-product_sku="" aria-label="' . esc_attr( 'Sepete ekle: “' . $title . '”' ) . '" rel="nofollow">Sepete ekle</a>';
	echo '<span id="woocommerce_loop_add_to_cart_link_describedby_' . $id . '" class="screen-reader-text"></span>';
	echo "</li>\n";
}

function sahte_wc_products_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'limit'        => 8,
			'columns'      => 4,
			'category'     => '',
			'orderby'      => 'title',
			'best_selling' => '',
		),
		$atts
	);
	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => (int) $atts['limit'],
		'orderby'        => 'title',
		'order'          => 'ASC',
	);
	if ( $atts['category'] ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
			),
		);
	}
	if ( 'true' === $atts['best_selling'] || 'popularity' === $atts['orderby'] ) {
		$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'DESC';
	}
	$query   = new WP_Query( $args );
	$columns = (int) $atts['columns'];
	ob_start();
	echo '<div class="woocommerce columns-' . $columns . ' ">';
	if ( $query->posts ) {
		echo '<ul class="products columns-' . $columns . '">' . "\n";
		foreach ( $query->posts as $i => $post ) {
			sahte_wc_loop_item( $post, $i, $columns );
		}
		echo '</ul>';
	}
	echo '</div>';
	return ob_get_clean();
}

// Gerçek WooCommerce'in wc-template-hooks.php'deki varsayılanları; tema sarmalayıcıları kaldırır, ekmek kırıntısı kalır.
function woocommerce_output_content_wrapper() {
	echo '<div id="primary" class="content-area"><main id="main" class="site-main" role="main">';
}

function woocommerce_output_content_wrapper_end() {
	echo '</main></div>';
}

add_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
add_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
add_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

function woocommerce_breadcrumb() {
	$args   = apply_filters(
		'woocommerce_breadcrumb_defaults',
		array(
			'delimiter'   => '&nbsp;&#47;&nbsp;',
			'wrap_before' => '<nav class="woocommerce-breadcrumb" aria-label="Breadcrumb">',
			'wrap_after'  => '</nav>',
		)
	);
	$crumbs = array( array( 'Ana Sayfa', home_url( '/' ) ) );
	if ( is_product_category() ) {
		$term = get_queried_object();
		foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );
			$crumbs[] = array( $ancestor->name, get_term_link( $ancestor ) );
		}
		$crumbs[] = array( $term->name, '' );
	} elseif ( is_product() ) {
		$crumbs[] = array( get_the_title( get_queried_object_id() ), '' );
	} elseif ( is_search() ) {
		$crumbs[] = array( 'Arama sonuçları: “' . get_search_query() . '”', '' );
	} elseif ( is_shop() ) {
		$crumbs[] = array( 'Mağaza', '' );
	}
	$parts = array();
	foreach ( $crumbs as $crumb ) {
		$parts[] = $crumb[1] ? '<a href="' . esc_url( $crumb[1] ) . '">' . esc_html( $crumb[0] ) . '</a>' : esc_html( $crumb[0] );
	}
	echo $args['wrap_before'] . implode( $args['delimiter'], $parts ) . $args['wrap_after']; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * woocommerce_content() ile aynı akış: başlık, woocommerce_archive_description, sonuç sayısı, sıralama, liste.
 */
function woocommerce_content() {
	if ( is_singular( 'product' ) ) {
		while ( have_posts() ) {
			the_post();
			$id = get_the_ID();
			echo '<div id="product-' . $id . '" class="product type-product status-publish instock has-post-thumbnail product-type-simple">';
			echo '<div class="woocommerce-product-gallery woocommerce-product-gallery--with-images images">' . get_the_post_thumbnail( null, 'large' ) . '</div>';
			echo '<div class="summary entry-summary"><h1 class="product_title entry-title">' . esc_html( get_the_title() ) . '</h1>';
			echo '<p class="price">' . sahte_wc_price_html( $id ) . '</p>';
			echo '<div class="woocommerce-product-details__short-description">' . wp_kses_post( wpautop( get_the_content() ) ) . '</div>';
			echo '<form class="cart" method="post"><div class="quantity"><label class="screen-reader-text" for="adet">Adet</label><input type="number" id="adet" class="input-text qty text" name="quantity" value="1" min="1" step="1"></div>';
			echo '<button type="submit" name="add-to-cart" value="' . $id . '" class="single_add_to_cart_button button alt">Sepete ekle</button></form>';
			echo '</div></div>';
		}
		return;
	}

	if ( is_search() ) {
		$title = 'Arama sonuçları: “' . get_search_query() . '”';
	} elseif ( is_product_category() ) {
		$title = single_term_title( '', false );
	} else {
		$title = 'Mağaza';
	}
	echo '<h1 class="page-title">' . esc_html( $title ) . '</h1>';
	do_action( 'woocommerce_archive_description' );

	global $wp_query;
	if ( ! have_posts() ) {
		echo '<div class="woocommerce-no-products-found"><div class="woocommerce-info">Seçiminizle eşleşen ürün bulunamadı.</div></div>';
		return;
	}
	echo '<p class="woocommerce-result-count">' . (int) $wp_query->found_posts . ' sonucun tümü gösteriliyor</p>';
	echo '<form class="woocommerce-ordering" method="get"><select name="orderby" class="orderby" aria-label="Mağaza siparişi"><option>Varsayılan sıralama</option><option>En çok satanlar</option></select></form>';
	echo '<ul class="products columns-4">' . "\n";
	$i = 0;
	while ( have_posts() ) {
		the_post();
		sahte_wc_loop_item( get_post(), $i++, 4 );
	}
	echo '</ul>';
}

// Store API: temanın canlı araması bu uç noktayı çağırır.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wc/store/v1',
			'/products',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function ( $request ) {
					$query = new WP_Query(
						array(
							'post_type'      => 'product',
							's'              => (string) $request['search'],
							'posts_per_page' => min( 100, max( 1, (int) $request['per_page'] ) ),
						)
					);
					return array_map( 'sahte_wc_store_product', $query->posts );
				},
			)
		);
	}
);

function sahte_wc_store_product( $post ) {
	$price = (float) get_post_meta( $post->ID, '_price', true );
	$thumb = get_the_post_thumbnail_url( $post, 'woocommerce_thumbnail' );
	return array(
		'id'        => $post->ID,
		'name'      => esc_html( $post->post_title ),
		'permalink' => get_permalink( $post ),
		'images'    => $thumb ? array(
			array(
				'src'       => get_the_post_thumbnail_url( $post, 'full' ),
				'thumbnail' => $thumb,
				'alt'       => '',
			),
		) : array(),
		'prices'    => array(
			'price'                       => (string) round( $price * 100 ),
			'currency_code'               => 'TRY',
			'currency_symbol'             => '₺',
			'currency_minor_unit'         => 2,
			'currency_decimal_separator'  => ',',
			'currency_thousand_separator' => '.',
			'currency_prefix'             => '',
			'currency_suffix'             => "\u{00a0}₺",
			'price_range'                 => null,
		),
	);
}
