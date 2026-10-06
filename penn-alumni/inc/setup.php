<?php
/** Theme supports, menus, assets, block styles, editor guardrails. */
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	remove_theme_support( 'core-block-patterns' ); // only Penn patterns in the inserter

	// Same stylesheet in the editor so pages look like the live site while you edit.
	add_editor_style( array( 'assets/css/pennDesignTokens.css', 'assets/css/wp-adapter.css', 'assets/css/editor.css' ) );

	// Menus = the "Navigation Menu" settings from the component library (paHeader / paFooter).
	register_nav_menus( array(
		'primary'          => 'Main navigation (top bar + dropdowns)',
		'utility'          => 'Utility bar (Penn Home, Make a Gift…)',
		'footer-explore'   => 'Footer · Explore',
		'footer-resources' => 'Footer · Resources',
		'footer-follow'    => 'Footer · Follow',
	) );
} );

// Appearance → Menus stays available in a block theme because menu locations are registered.
// Each top-level item's Description (turn on under Screen Options) is the intro text in its dropdown.

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'penn-tokens', PA_URI . '/assets/css/pennDesignTokens.css', array(), PA_THEME_VERSION );
	wp_enqueue_style( 'penn-wp-adapter', PA_URI . '/assets/css/wp-adapter.css', array( 'penn-tokens' ), PA_THEME_VERSION );
	wp_enqueue_script( 'penn-site', PA_URI . '/assets/js/pa-site.js', array(), PA_THEME_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

/* Block style variations: editors pick a named Penn style instead of typing classes. */
add_action( 'init', function () {
	$buttons = array(
		'pa-primary'     => 'Red (primary)',
		'pa-secondary'   => 'Blue',
		'pa-ghost'       => 'Outline',
		'pa-ghost-light' => 'Outline (on dark)',
		'pa-white'       => 'White',
		'pa-text'        => 'Text link →',
	);
	foreach ( $buttons as $name => $label ) {
		register_block_style( 'core/button', array( 'name' => $name, 'label' => $label ) );
	}
	$paras = array(
		'pa-eyebrow' => 'Eyebrow (red label)',
		'pa-lede'    => 'Lede (italic intro)',
		'pa-note'    => 'Prototype note',
	);
	foreach ( $paras as $name => $label ) {
		register_block_style( 'core/paragraph', array( 'name' => $name, 'label' => $label ) );
	}
	register_block_style( 'core/details', array( 'name' => 'pa-faq', 'label' => 'Penn FAQ' ) );

	// Pattern categories shown in the inserter.
	register_block_pattern_category( 'penn-sections', array( 'label' => 'Penn · Sections' ) );
	register_block_pattern_category( 'penn-pages', array( 'label' => 'Penn · Page templates' ) );
} );

/* Guardrails: hide core blocks that would let editors go off-brand. */
add_filter( 'allowed_block_types_all', function ( $allowed, $ctx ) {
	if ( empty( $ctx->post ) ) {
		return $allowed; // Site Editor keeps everything.
	}
	$core = array(
		'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/buttons', 'core/button',
		'core/group', 'core/details', 'core/image', 'core/quote', 'core/separator', 'core/table',
		'core/html', 'core/embed', 'core/block', 'core/shortcode', 'core/pattern',
	);
	$penn = array_keys( array_filter(
		WP_Block_Type_Registry::get_instance()->get_all_registered(),
		fn( $b ) => str_starts_with( $b->name, 'penn/' )
	) );
	return array_merge( $core, $penn );
}, 10, 2 );

/* Prototype banner (Settings → Penn Alumni → "Show prototype banner"). */
add_action( 'wp_body_open', function () {
	if ( pa_opt( 'proto_banner', '1' ) ) {
		echo '<div class="pa-proto-banner"><span>●</span> Prototype · WordPress build · Section Library v0.1</div>';
	}
} );
