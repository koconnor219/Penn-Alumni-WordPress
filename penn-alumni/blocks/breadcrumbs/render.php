<?php
/** Auto breadcrumb from the page hierarchy: Home / Parent / Current. */
$post = get_post();
if ( ! $post || is_front_page() ) {
	return;
}
$parts = array( '<a href="' . esc_url( home_url( '/' ) ) . '">Home</a><span class="pa-crumb-sep">/</span>' );
foreach ( array_reverse( get_post_ancestors( $post ) ) as $id ) {
	$parts[] = '<a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a><span class="pa-crumb-sep">/</span>';
}
$parts[] = '<span class="pa-crumb-current">' . esc_html( get_the_title( $post ) ) . '</span>';
printf( '<nav %s aria-label="Breadcrumb">%s</nav>', get_block_wrapper_attributes( array( 'class' => 'pa-page-breadcrumb' ) ), implode( '', $parts ) );
