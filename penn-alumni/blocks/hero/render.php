<?php
/** paHero: variant page | home | solid | compact; photo or video background; text + buttons are inner blocks. */
$v     = $attributes['variant'] ?? 'page';
$mod   = array( 'page' => '', 'home' => 'pa-hero--home', 'solid' => 'pa-hero--solid', 'compact' => 'pa-hero--solid pa-hero--compact' );
$media = '';
if ( ! empty( $attributes['videoUrl'] ) && in_array( $v, array( 'home', 'page' ), true ) ) {
	$media = '<div class="pa-hero-media" aria-hidden="true"><iframe src="' . esc_url( $attributes['videoUrl'] ) . '" allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" title="Background video" tabindex="-1"></iframe></div>';
} elseif ( ! empty( $attributes['mediaUrl'] ) && ! in_array( $v, array( 'solid', 'compact' ), true ) ) {
	$media = '<div class="pa-hero-media" style="background-image:url(\'' . esc_url( pa_theme_url( $attributes['mediaUrl'] ) ) . '\')" aria-hidden="true"></div>';
}
$overlay = in_array( $v, array( 'solid', 'compact' ), true ) ? '' : '<div class="pa-hero-overlay" aria-hidden="true"></div>';
printf(
	'<section %s>%s%s<div class="pa-hero-content"><div class="pa-hero-text">%s</div></div></section>',
	get_block_wrapper_attributes( array( 'class' => pa_cls( 'pa-hero', $mod[ $v ] ?? '' ), 'data-variant' => $v ) ),
	$media,
	$overlay,
	$content
);
