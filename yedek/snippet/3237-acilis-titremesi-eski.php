/**
 * IQOS Vitrin – Açılış titremesi düzeltmesi
 * 1) Başlıktaki yazı logosu sunucuda basılır (sayfa açılırken boş logo alanı görünmez)
 * 2) Mobil ana sayfada rafların yeri önceden ayrılır (önce büyük tanıtım kutusu görünüp sonra aşağı kaymaz)
 * Kapatınca eski davranışa döner.
 */
add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { return; }
	ob_start( function ( $html ) {
		if ( false === stripos( $html, '<html' ) ) { return $html; }
		$kilit = '<span class="ivg-kilit"><span class="ivg-ana"><span class="ivg-iqos">IQOS</span><span class="ivg-vitrin">VİTRİN</span></span><span class="ivg-alt"><span class="ivg-cizgi"></span><span>Türkiye’nin Güvenilir Tedarikçisi</span></span></span>';
		return preg_replace( '#(<div id="logo"[^>]*>\s*(?:<!--.*?-->\s*)?<a [^>]*rel="home"[^>]*>).*?(</a>)#s', '$1' . $kilit . '$2', $html, 1 );
	} );
}, 0 );
add_action( 'wp_head', function () {
	echo '<style id="iv-acilis-duzeltme">@media (max-width:768px){body.home #vr-raflar-yer:empty{min-height:120vh}}</style>';
}, 99 );
