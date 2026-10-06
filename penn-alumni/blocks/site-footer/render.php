<?php
/** paFooter: contact block from Settings → Penn Alumni; link columns from the three Footer menus. */
$cols = array( 'footer-explore' => 'Explore', 'footer-resources' => 'Resources', 'footer-follow' => 'Follow' );
$locs = get_nav_menu_locations();
$html = '';
foreach ( $cols as $loc => $title ) {
	if ( empty( $locs[ $loc ] ) ) {
		continue;
	}
	$menu  = wp_get_nav_menu_object( $locs[ $loc ] );
	$links = '';
	foreach ( wp_get_nav_menu_items( $locs[ $loc ] ) ?: array() as $it ) {
		$links .= '<a href="' . esc_url( $it->url ) . '"' . ( '_blank' === $it->target ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( $it->title ) . '</a>';
	}
	$html .= '<div class="pa-footer-col"><h2 class="pa-footer-title">' . esc_html( $title ) . '</h2>' . $links . '</div>';
}
$addr  = nl2br( esc_html( pa_opt( 'address' ) ) );
$phone = pa_opt( 'phone' );
$email = pa_opt( 'email' );
$logo  = PA_URI . '/assets/logos/Penn%20Alumni%20Reverse_Logo.png';
?>
<footer <?php echo get_block_wrapper_attributes( array( 'class' => 'pa-site-footer' ) ); ?>>
  <div class="pa-footer-brand"><img src="<?php echo esc_url( $logo ); ?>" alt="Penn Alumni — University of Pennsylvania" height="76"></div>
  <div class="pa-footer-inner">
    <div class="pa-footer-col">
      <h2 class="pa-footer-title">Contact</h2>
      <address><?php echo $addr; ?><br>
        <a href="tel:<?php echo esc_attr( preg_replace( '/\D/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><br>
        <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
      </address>
    </div>
    <?php echo $html; ?>
  </div>
  <div class="pa-footer-bottom">Copyright © <?php echo esc_html( gmdate( 'Y' ) ); ?> University of Pennsylvania</div>
</footer>
