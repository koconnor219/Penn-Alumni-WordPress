<?php
/**
 * Settings → Penn Alumni
 * One screen for the values that used to be "properties" in the Experience Cloud spec:
 * contact block, MyPenn link, Blackthorn org ID, Form Assembly address, prototype banner.
 */
defined( 'ABSPATH' ) || exit;

function pa_settings_fields() {
	return array(
		'contact' => array(
			'title'  => 'Footer contact block',
			'fields' => array(
				'address' => array( 'Address (one line per row)', 'textarea', "E. Craig Sweeten Alumni House\n3533 Locust Walk\nPhiladelphia, PA 19104" ),
				'phone'   => array( 'Phone', 'text', '215-898-7811' ),
				'email'   => array( 'Email', 'text', 'alumni@ben.dev.upenn.edu' ),
				'mypenn'  => array( 'MyPenn button link', 'url', 'https://mypenn.upenn.edu' ),
			),
		),
		'blackthorn' => array(
			'title'  => 'Blackthorn Events',
			'intro'  => 'From any Blackthorn event or event-group URL, e.g. https://events.blackthorn.io/en/<b>3itLbC6</b>/g/ZZ8AC6qtdC — the bold part is the Org ID. Add this site\'s domains (live + Pantheon dev/test) to Blackthorn\'s embed allowlist.',
			'fields' => array(
				'bt_org'  => array( 'Blackthorn Org ID', 'text', '' ),
				'bt_path' => array( 'Default event-group path (calendar)', 'text', '' ),
			),
		),
		'formassembly' => array(
			'title'  => 'Form Assembly',
			'intro'  => 'Base address of Penn\'s Form Assembly instance, e.g. https://upenn.tfaforms.net. Each Form Assembly block then only needs a form number.',
			'fields' => array(
				'fa_base' => array( 'Form Assembly base URL', 'url', '' ),
			),
		),
		'proto' => array(
			'title'  => 'Prototype',
			'fields' => array(
				'proto_banner' => array( 'Show prototype banner and notes', 'checkbox', '1' ),
			),
		),
	);
}

add_action( 'admin_menu', function () {
	add_options_page( 'Penn Alumni', 'Penn Alumni', 'manage_options', 'penn-alumni', 'pa_settings_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'pa_settings', 'pa_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$out = array();
			foreach ( pa_settings_fields() as $sec ) {
				foreach ( $sec['fields'] as $k => $f ) {
					$v = $in[ $k ] ?? '';
					$out[ $k ] = 'textarea' === $f[1] ? sanitize_textarea_field( $v ) : ( 'url' === $f[1] ? esc_url_raw( $v ) : ( 'checkbox' === $f[1] ? ( $v ? '1' : '0' ) : sanitize_text_field( $v ) ) );
				}
			}
			return $out;
		},
	) );
} );

function pa_settings_page() {
	$o = get_option( 'pa_settings', array() );
	echo '<div class="wrap"><h1>Penn Alumni settings</h1><form method="post" action="options.php">';
	settings_fields( 'pa_settings' );
	foreach ( pa_settings_fields() as $sec ) {
		echo '<h2>' . esc_html( $sec['title'] ) . '</h2>';
		if ( ! empty( $sec['intro'] ) ) {
			echo '<p>' . wp_kses_post( $sec['intro'] ) . '</p>';
		}
		echo '<table class="form-table">';
		foreach ( $sec['fields'] as $k => $f ) {
			[ $label, $type, $def ] = $f;
			$v    = $o[ $k ] ?? $def;
			$name = 'pa_settings[' . $k . ']';
			echo '<tr><th><label for="pa-' . esc_attr( $k ) . '">' . esc_html( $label ) . '</label></th><td>';
			if ( 'textarea' === $type ) {
				echo '<textarea id="pa-' . esc_attr( $k ) . '" name="' . esc_attr( $name ) . '" rows="4" class="large-text">' . esc_textarea( $v ) . '</textarea>';
			} elseif ( 'checkbox' === $type ) {
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0"><input id="pa-' . esc_attr( $k ) . '" type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( '1', $v, false ) . '>';
			} else {
				echo '<input id="pa-' . esc_attr( $k ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $v ) . '" class="regular-text">';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}
	submit_button();
	echo '</form></div>';
}
