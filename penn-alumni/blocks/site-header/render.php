<?php
/**
 * paHeader. Same markup as components/header.html, built from Appearance → Menus:
 *   "Main navigation": top-level items = dropdown buttons; their Description = dropdown intro; children = dropdown links.
 *   "Utility bar": the blue Penn-wide links strip.
 */
if ( ! function_exists( 'pa_menu_tree' ) ) {
	function pa_menu_tree( $location ) {
		$locs = get_nav_menu_locations();
		if ( empty( $locs[ $location ] ) ) {
			return array();
		}
		$items = wp_get_nav_menu_items( $locs[ $location ] ) ?: array();
		$tree  = array();
		foreach ( $items as $it ) {
			if ( ! $it->menu_item_parent ) {
				$tree[ $it->ID ] = array( 'item' => $it, 'children' => array() );
			}
		}
		foreach ( $items as $it ) {
			if ( $it->menu_item_parent && isset( $tree[ $it->menu_item_parent ] ) ) {
				$tree[ $it->menu_item_parent ]['children'][] = $it;
			}
		}
		return $tree;
	}
	function pa_menu_link( $it, $ext_mark = true ) {
		$ext = '_blank' === $it->target;
		return '<a href="' . esc_url( $it->url ) . '"' . ( $ext ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( $it->title ) . ( $ext && $ext_mark ? ' <span class="pa-ext" aria-label="(external site)">↗</span>' : '' ) . '</a>';
	}
}
$chev   = pa_icon( 'chevron-down' ) ?: '<svg aria-hidden="true" focusable="false" class="pa-icon lucide-chevron-down" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
$main   = pa_menu_tree( 'primary' );
$util   = pa_menu_tree( 'utility' );
$mypenn = pa_opt( 'mypenn' );
$logo   = PA_URI . '/assets/logos/Penn%20Alumni_Logo.png';

$util_html = '';
foreach ( $util as $n ) {
	$util_html .= pa_menu_link( $n['item'], false );
}

$lis = $mobile = '';
foreach ( $main as $id => $n ) {
	$it    = $n['item'];
	$key   = sanitize_title( $it->title );
	$links = $mlinks = '';
	foreach ( $n['children'] as $c ) {
		$links  .= '<li>' . pa_menu_link( $c ) . '</li>';
		$mlinks .= pa_menu_link( $c, false );
	}
	$wide   = count( $n['children'] ) > 7 ? ' pa-mega--wide' : '';
	$intro  = $it->description ? '<p>' . esc_html( $it->description ) . '</p>' : '';
	$over   = $it->attr_title ?: 'Overview';
	$lis   .= '<li data-nav="' . esc_attr( $key ) . '"><button class="pa-nav-top" aria-expanded="false" aria-controls="mega-' . esc_attr( $key ) . '">' . esc_html( $it->title ) . ' ' . $chev . '</button>'
		. '<div class="pa-mega' . $wide . '" id="mega-' . esc_attr( $key ) . '"><div class="pa-mega-intro"><h3>' . esc_html( $it->title ) . '</h3>' . $intro . '<a class="pa-btn-text" href="' . esc_url( $it->url ) . '" data-overview>' . esc_html( $over ) . '</a></div><ul class="pa-mega-links">' . $links . '</ul></div></li>';
	$mobile .= '<details><summary>' . esc_html( $it->title ) . '</summary><a href="' . esc_url( $it->url ) . '">' . esc_html( $over ) . '</a>' . $mlinks . '</details>';
}
if ( ! $main && current_user_can( 'edit_theme_options' ) ) {
	$lis = '<li><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">Set up the Main navigation menu →</a></li>';
}
$search = pa_icon( 'search' );
$menu   = '<svg aria-hidden="true" focusable="false" class="pa-icon lucide-menu" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16"/><path d="M4 18h16"/><path d="M4 6h16"/></svg>';
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pa-header-wrap' ) ); ?>>
<a class="pa-skip" href="#main">Skip to content</a>
<header class="pa-site-header">
<?php if ( $util_html ) : ?><div class="pa-utility-nav" role="navigation" aria-label="Penn-wide links"><?php echo $util_html; ?></div><?php endif; ?>
<nav class="pa-main-nav" aria-label="Main">
  <div class="pa-main-nav-inner">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="pa-brand" aria-label="Penn Alumni home"><img src="<?php echo esc_url( $logo ); ?>" alt="Penn Alumni — University of Pennsylvania" height="48"></a>
    <ul class="pa-nav-links"><?php echo $lis; ?></ul>
    <div class="pa-nav-right">
      <button class="pa-nav-search" aria-label="Search" aria-expanded="false" aria-controls="pa-search"><?php echo $search; ?></button>
      <a href="<?php echo esc_url( $mypenn ); ?>" class="pa-nav-login">MyPenn →</a>
      <button class="pa-nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="pa-mobile"><?php echo $menu; ?></button>
    </div>
  </div>
  <div class="pa-search-panel" id="pa-search">
    <form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
      <label for="pa-q" class="pa-sr">Search Penn Alumni</label>
      <input id="pa-q" name="s" type="search" placeholder="Search events, programs, pages…" value="<?php echo esc_attr( get_search_query() ); ?>">
      <button class="pa-btn pa-btn-primary" type="submit">Search</button>
    </form>
  </div>
  <div class="pa-mobile-menu" id="pa-mobile"><?php echo $mobile; ?><a class="pa-btn pa-btn-secondary pa-mobile-cta" href="<?php echo esc_url( $mypenn ); ?>">MyPenn →</a></div>
</nav>
</header>
</div>
