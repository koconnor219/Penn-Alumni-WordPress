<?php
/**
 * Maps core block output onto Section Library markup so pennDesignTokens.css
 * styles WordPress pages exactly like the HTML prototype — no duplicated CSS.
 *
 *   Button (style "Red", "Outline"…) → <a class="pa-btn pa-btn-primary">
 *   Buttons                          → <div class="pa-btn-row">
 *   Paragraph style "Eyebrow"        → adds .pa-head-eyebrow (etc.)
 */
defined( 'ABSPATH' ) || exit;

const PA_BUTTON_STYLES = array(
	'pa-primary'     => 'pa-btn pa-btn-primary',
	'pa-secondary'   => 'pa-btn pa-btn-secondary',
	'pa-ghost'       => 'pa-btn pa-btn-ghost',
	'pa-ghost-light' => 'pa-btn pa-btn-ghost-light',
	'pa-white'       => 'pa-btn pa-btn-white',
	'pa-text'        => 'pa-btn-text',
);

add_filter( 'render_block_core/button', function ( $html, $block ) {
	$cn = $block['attrs']['className'] ?? '';
	$class = 'pa-btn pa-btn-primary'; // default look for any button
	foreach ( PA_BUTTON_STYLES as $style => $classes ) {
		if ( str_contains( $cn, 'is-style-' . $style ) ) {
			$class = $classes;
		}
	}
	$p = new WP_HTML_Tag_Processor( $html );
	if ( ! $p->next_tag( 'a' ) ) {
		return $html; // a <button> with no link: leave it
	}
	$href   = $p->get_attribute( 'href' );
	$target = $p->get_attribute( 'target' );
	$rel    = $p->get_attribute( 'rel' );
	if ( ! preg_match( '#<a\b[^>]*>(.*)</a>#s', $html, $m ) ) {
		return $html;
	}
	$label = $m[1];
	$ext   = '_blank' === $target ? '<span class="pa-sr-only"> (opens in a new tab)</span>' : '';
	return sprintf(
		'<a href="%s" class="%s"%s%s>%s%s</a>',
		esc_url( (string) $href ),
		esc_attr( $class ),
		$target ? ' target="' . esc_attr( $target ) . '"' : '',
		$rel ? ' rel="' . esc_attr( $rel ) . '"' : ( $target ? ' rel="noopener"' : '' ),
		$label,
		$ext
	);
}, 10, 2 );

add_filter( 'render_block_core/buttons', function ( $html, $block ) {
	$inner = '';
	foreach ( $block['innerBlocks'] as $b ) {
		$inner .= render_block( $b );
	}
	$extra = $block['attrs']['className'] ?? '';
	return '<div class="' . esc_attr( pa_cls( 'pa-btn-row', $extra ) ) . '">' . $inner . '</div>';
}, 10, 2 );

add_filter( 'render_block_core/paragraph', function ( $html, $block ) {
	$cn  = $block['attrs']['className'] ?? '';
	$map = array( 'is-style-pa-eyebrow' => 'pa-head-eyebrow', 'is-style-pa-lede' => 'pa-lede', 'is-style-pa-note' => 'pa-proto-note' );
	foreach ( $map as $style => $cls ) {
		if ( str_contains( $cn, $style ) ) {
			$p = new WP_HTML_Tag_Processor( $html );
			if ( $p->next_tag( 'p' ) ) {
				$p->add_class( $cls );
				$html = $p->get_updated_html();
			}
		}
	}
	return $html;
}, 10, 2 );

/* FAQ / accordion: wrap the answer in .pa-acc-body to match paAccordion. */
add_filter( 'render_block_core/details', function ( $html ) {
	return preg_replace( '#(</summary>)(.*)(</details>)#s', '$1<div class="pa-acc-body">$2</div>$3', $html, 1 );
} );
