<?php
/**
 * paCard. Same markup as the prototype: the whole card is the link (<a class="pa-c">) when it has a URL
 * and no links inside it. Styles: image | icon | number | tag | stat | row | text.
 */
$style  = $attributes['cardStyle'] ?? 'icon';
$href   = $attributes['href'] ?? '';
$icon   = ! empty( $attributes['icon'] ) ? '<span class="pa-c-icon">' . pa_icon( $attributes['icon'] ) . '</span>' : '';
$nested = str_contains( $content, '<a ' );
$tag    = ( $href && ! $nested ) ? 'a' : 'div';
$cls    = pa_cls( 'pa-c', 'pa-c--' . $style, ! empty( $attributes['center'] ) ? 'pa-c--center' : '' );
$attrs  = array( 'class' => $cls );
if ( 'a' === $tag ) {
	$attrs['href'] = esc_url( pa_theme_url( $href ) );
	if ( ! empty( $attributes['newTab'] ) ) {
		$attrs['target'] = '_blank';
		$attrs['rel']    = 'noopener';
	}
}
$media = '';
if ( 'image' === $style ) {
	$img   = ! empty( $attributes['imageUrl'] ) ? '<img src="' . esc_url( pa_theme_url( $attributes['imageUrl'] ) ) . '" alt="">' : 'P';
	$badge = ! empty( $attributes['badge'] ) ? '<span class="pa-badge pa-badge--attn">' . esc_html( $attributes['badge'] ) . '</span>' : '';
	$media = '<div class="pa-c-media pa-tone-' . esc_attr( $attributes['tone'] ?? 'a' ) . '">' . $img . $badge . '</div>';
}
if ( 'row' === $style ) {
	$body = $icon . '<div class="pa-c-body">' . $content . '</div><span class="pa-c-arrow" aria-hidden="true">→</span>';
} else {
	$body = $media . '<div class="pa-c-body">' . $icon . $content . '</div>';
}
$cover = ( $href && $nested ) ? '<a class="pa-cover-link" href="' . esc_url( pa_theme_url( $href ) ) . '" aria-hidden="true" tabindex="-1"></a>' : '';
printf( '<%1$s %2$s>%3$s%4$s</%1$s>', $tag, get_block_wrapper_attributes( $attrs ), $body, $cover );
