<?php
/** Small shared helpers. */
defined( 'ABSPATH' ) || exit;

/** Theme option (Settings → Penn Alumni) with a default. */
function pa_opt( $key, $default = null ) {
	$o = get_option( 'pa_settings', array() );
	if ( isset( $o[ $key ] ) && '' !== $o[ $key ] ) {
		return $o[ $key ];
	}
	if ( null === $default && function_exists( 'pa_settings_fields' ) ) {
		foreach ( pa_settings_fields() as $sec ) {
			if ( isset( $sec['fields'][ $key ] ) ) {
				return $sec['fields'][ $key ][2];
			}
		}
	}
	return $default ?? '';
}

/** Lucide icon set (data/icons.json → name => inner SVG paths). */
function pa_icons() {
	static $icons = null;
	if ( null === $icons ) {
		$icons = json_decode( (string) file_get_contents( PA_DIR . '/data/icons.json' ), true ) ?: array();
	}
	return $icons;
}

/** Inline Lucide SVG, same markup as the HTML prototype. */
function pa_icon( $name ) {
	$i = pa_icons();
	if ( empty( $i[ $name ] ) ) {
		return '';
	}
	return '<svg aria-hidden="true" focusable="false" class="pa-icon lucide-' . esc_attr( $name ) . '" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $i[ $name ] . '</svg>';
}

/** Replace the {{theme}} token used in imported content/sample data with the theme URL. */
function pa_theme_url( $s ) {
	return str_replace( '{{theme}}', PA_URI, (string) $s );
}

/** Classes string helper. */
function pa_cls( ...$parts ) {
	return trim( implode( ' ', array_filter( array_map( 'trim', $parts ) ) ) );
}

/** Read a pipe- or comma-delimited data file from /data (or an uploaded override URL path). */
function pa_read_rows( $file, $cols, $delim = '|' ) {
	$path = PA_DIR . '/data/' . $file;
	if ( ! file_exists( $path ) ) {
		return array();
	}
	$rows = array();
	foreach ( preg_split( '/\r?\n/', trim( (string) file_get_contents( $path ) ) ) as $line ) {
		if ( '' === trim( $line ) || '#' === $line[0] ) {
			continue;
		}
		$vals   = array_pad( explode( $delim, $line ), count( $cols ), '' );
		$rows[] = array_combine( $cols, array_slice( array_map( 'trim', $vals ), 0, count( $cols ) ) );
	}
	return $rows;
}
