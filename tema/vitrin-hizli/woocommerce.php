<?php
/**
 * Mağaza, kategori, arama sonuçları ve ürün sayfaları.
 *
 * @package Vitrin_Hizli
 */

get_header();
?>
<div class="vt-wrap vt-shop">
	<?php
	// Ekmek kırıntısı ve bu kancaya bağlanan eklentiler; WooCommerce'in kendi sarmalayıcıları functions.php'de kaldırıldı.
	do_action( 'woocommerce_before_main_content' );
	woocommerce_content();
	do_action( 'woocommerce_after_main_content' );
	?>
</div>
<?php
get_footer();
