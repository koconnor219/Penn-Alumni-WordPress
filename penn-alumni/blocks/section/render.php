<?php
/** paContent / paCardGrid / paCtaBand shell: <section class="pa-block pa-bg-*"><div class="pa-block-inner">…</div></section> */
$bg    = in_array( $attributes['bg'] ?? 'white', array( 'white', 'cream', 'gray', 'blue', 'red' ), true ) ? $attributes['bg'] : 'white';
$extra = array( 'class' => pa_cls( 'pa-block', 'pa-bg-' . $bg, ! empty( $attributes['compact'] ) ? 'pa-block--compact' : '' ) );
if ( ! empty( $attributes['anchor'] ) ) {
	$extra['id'] = $attributes['anchor'];
}
if ( ! empty( $attributes['navLabel'] ) ) {
	$extra['data-nav-label'] = $attributes['navLabel'];
}
$inner = ( $attributes['inner'] ?? true ) ? '<div class="pa-block-inner">' . $content . '</div>' : $content;
printf( '<section %s>%s</section>', get_block_wrapper_attributes( $extra ), $inner );
