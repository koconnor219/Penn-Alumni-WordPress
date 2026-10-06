<?php
/**
 * Registers the Penn blocks (/blocks/<name>/block.json + render.php).
 * Rendering happens in PHP, so the markup always matches the Section Library
 * and a design change is one edit to the theme, not an edit to every page.
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'block_categories_all', function ( $cats ) {
	array_unshift( $cats, array( 'slug' => 'penn', 'title' => 'Penn Alumni' ) );
	return $cats;
} );

add_action( 'init', function () {
	wp_register_script(
		'penn-blocks-editor',
		PA_URI . '/assets/js/blocks-editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data', 'wp-i18n' ),
		PA_THEME_VERSION,
		true
	);
	// Icon list + defaults for the editor UI.
	wp_add_inline_script( 'penn-blocks-editor', 'window.pennIcons = ' . wp_json_encode( pa_icons() ) . '; window.pennThemeUrl = ' . wp_json_encode( PA_URI ) . ';', 'before' );

	foreach ( glob( PA_DIR . '/blocks/*/block.json' ) as $json ) {
		register_block_type( dirname( $json ) );
	}
} );

/** Inner-blocks helper: dynamic container blocks get their saved inner HTML as $content. */
function pa_wrapper( $extra = array() ) {
	return get_block_wrapper_attributes( $extra );
}
