<?php
/** Generic linked box (homepage MyPenn tiles, stats…). The CSS class comes from "Additional CSS class". */
$href   = $attributes['href'] ?? '';
$nested = str_contains( $content, '<a ' );
$tag    = ( $href && ! $nested ) ? 'a' : 'div';
$attrs  = array();
if ( 'a' === $tag ) {
	$attrs['href'] = esc_url( pa_theme_url( $href ) );
	if ( ! empty( $attributes['newTab'] ) ) {
		$attrs['target'] = '_blank';
		$attrs['rel']    = 'noopener';
	}
}
printf( '<%1$s %2$s>%3$s</%1$s>', $tag, get_block_wrapper_attributes( $attrs ), $content );
