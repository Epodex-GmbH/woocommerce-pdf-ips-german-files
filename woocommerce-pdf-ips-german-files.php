<?php
/*
	Plugin Name: WooCommerce PDF Invoices & Packing Slips German Documents
	Version: 1.0.1
	Author: S.K
	Text Domain: pips
*/

if ( ! defined( 'PIPS_GERMAN_CODE' ) ) {
	define( 'PIPS_GERMAN_CODE', 'de_DE' );
}

if ( ! defined( 'PIPS_DOCS_PATH' ) ) {
	define( 'PIPS_DOCS_PATH', '/var/www/vhosts/epodex.com/httpdocs/medien/wc-pdfs/' );
}

register_activation_hook( __FILE__, 'pdf_ips_german_files_activate' );

function pdf_ips_german_files_activate () {
	global $wpdb;
	$save_path = PIPS_DOCS_PATH . str_replace( '_', '', $wpdb->prefix );
	pdf_ips_german_files_create_path( $save_path );
}

function pdf_ips_german_files_create_path ( $path ) {
	if ( is_dir( $path ) ) {
		return true;
	}
	$prev_path = substr( $path, 0, strrpos( $path, '/', - 2 ) + 1 );
	$return    = pdf_ips_german_files_create_path( $prev_path );

	return ( $return && is_writable( $prev_path ) ) ? mkdir( $path ) : false;
}

add_action( 'wpo_wcpdf_email_attachment', 'epodex_doc_created', 10, 3 );
function epodex_doc_created ( $path, $type, $th ) {
	if ( $type == 'credit-note' ) {
		return true;
	}

	$locale = PIPS_GERMAN_CODE;
	switch_to_locale( $locale );
	add_filter( 'plugin_locale', function( $locale, $plugin ) {
		if ( in_array( $plugin, [ 'woocommerce', 'woocommerce-pdf-invoices-packing-slips' ] ) ) {
			return force_epodex_language();
		}

		return $locale;
	}, 99999, 2 );
	switch_to_locale( $locale );
	unload_textdomain( 'woocommerce' );
	unload_textdomain( 'woocommerce-pdf-invoices-packing-slips' );
	unload_textdomain( 'wpo_wcpdf' );
	unload_textdomain( 'wpo_wcpdf_pro' );
	unload_textdomain( 'dmd-theme' );

	// reload text domains
	WC()->load_plugin_textdomain();
	WPO_WCPDF()->translations();
	WPO_WCPDF_Pro()->translations();

	add_filter( "wpo_wcpdf_footer_settings_text", function( $text, $obj ) {
		$settings = get_option( 'wcpdficr_settings' );
		if ( @$settings[ 'DE' ] ) {
			return $settings[ 'DE' ]['text'];
		}

		return $text;
	}, 999, 2 );


	$document = wcpdf_get_document( $th->get_type(), (array) ( $th->order_id ) );

	do_action( 'wpo_wcpdf_before_pdf', $document->get_type(), $document );

	$html         = $document->get_html();
	$pdf_settings = array(
		'paper_size'        => apply_filters( 'wpo_wcpdf_paper_format', $document->settings->paper_siize, $document->get_type(), $document ),
		'paper_orientation' => apply_filters( 'wpo_wcpdf_paper_orientation', 'portrait', $document->get_type(), $document ),
		'font_subsetting'   => $document->settings->font_subsetting
	);

	$pdf_maker = wcpdf_get_pdf_maker( $html, $pdf_settings );
	$pdf       = apply_filters( 'wpo_wcpdf_pdf_data', $pdf_maker->output(), $document );

	do_action( 'wpo_wcpdf_after_pdf', $document->get_type(), $document );

	global $wpdb;
	$filename = PIPS_DOCS_PATH . str_replace( '_', '', $wpdb->prefix ) . '/' . $type . '-' . $document->get_number() . '.pdf';

	file_put_contents( $filename, $pdf );
}

function force_epodex_language () {
	$locale = PIPS_GERMAN_CODE;

	return $locale;
}