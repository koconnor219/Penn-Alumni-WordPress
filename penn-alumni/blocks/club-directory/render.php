<?php
/**
 * paDirectory (clubs). Reads data/clubs.txt — one line per club — and renders the same searchable
 * cards as the prototype. Annual update = replace that file (or swap pa_clubs() for a Salesforce query).
 */
if ( ! function_exists( 'pa_clubs' ) ) {
	function pa_clubs() {
		return apply_filters( 'pa_clubs', pa_read_rows( 'clubs.txt', array( 'name', 'city', 'state', 'area', 'country', 'region', 'community' ) ) );
	}
}
$clubs  = pa_clubs();
$rkey   = array( 'Northeast' => 'northeast', 'Southeast' => 'southeast', 'Midwest' => 'midwest', 'West Coast' => 'west', 'International' => 'international' );
$social = array( 'Penn Club of Phoenix', 'Penn Club of San Diego', 'Penn Club of Colorado', 'Penn Club of Baltimore', 'Penn Club of Minneapolis', 'Penn Club of Nashville', 'Penn Club of Austin', 'Penn Club of Toronto', 'Penn Club of Australia', 'Penn Club of Germany', 'Penn Club of Korea', 'Penn Club of Mexico', 'Penn Club of Singapore', 'Penn Club of Switzerland' ); // [placeholder] until club records carry social URLs
$email  = 'alumniclubs@upenn.edu'; // [placeholder] → per-club contact email
$tags   = array( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'Sed do eiusmod tempor incididunt ut labore et dolore.', 'Ut enim ad minim veniam, quis nostrud exercitation.', 'Duis aute irure dolor in reprehenderit in voluptate.', 'Excepteur sint occaecat cupidatat non proident.' );
$lorem  = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.';
$tones  = 'acdfbe';
$where  = function ( $c ) {
	if ( 'United States' !== $c['country'] ) {
		return $c['city'] ? $c['city'] . ' · ' . $c['country'] : $c['country'];
	}
	$local = $c['city'] ?: $c['area'];
	return $local ? $local . ' · ' . $c['state'] : $c['state'];
};
$place = fn( $c ) => 'United States' === $c['country'] ? $c['state'] : $c['country'];
$ext   = fn( $label, $href ) => '<a href="' . esc_url( $href ) . '" class="pa-btn pa-btn-primary" target="_blank" rel="noopener">' . esc_html( $label ) . ' <span aria-hidden="true">↗</span><span class="pa-sr-only"> (opens in a new tab)</span></a>';

$cards = '';
foreach ( $clubs as $i => $c ) {
	$type = 'memberverse' === strtolower( $c['community'] ) ? 'memberverse' : ( in_array( $c['name'], $social, true ) ? 'social' : 'email' );
	if ( 'memberverse' === $type ) {
		$action = '<div class="pa-btn-row">' . $ext( 'Visit Club Community', '#' ) . '</div><p class="pa-facts-note pa-embed-note">Events, membership, and club news for this club live on its MemberVerse community.</p>';
	} elseif ( 'social' === $type ) {
		$action = '<h4>Follow the Club</h4><ul class="pa-linklist"><li><a href="#">' . pa_icon( 'camera' ) . ' Instagram</a></li><li><a href="#">' . pa_icon( 'users' ) . ' Facebook</a></li><li><a href="#">' . pa_icon( 'briefcase' ) . ' LinkedIn</a></li></ul>';
	} else {
		$action = '<div class="pa-btn-row"><a href="mailto:' . esc_attr( $email ) . '?subject=' . rawurlencode( "I'd like to get involved with the " . $c['name'] ) . '" class="pa-btn pa-btn-primary">Get Involved</a></div><p class="pa-facts-note pa-embed-note">Opens an email to the club\'s volunteer leaders.</p>';
	}
	$name  = esc_html( $c['name'] );
	$left  = '<div><h4>About the ' . $name . '</h4><div class="pa-rich"><p>' . $lorem . '</p></div>' . $action . '</div>';
	$right = '<div><div class="pa-aside-image pa-tone-' . $tones[ $i % 6 ] . '" role="img" aria-label="Placeholder club photo"></div><h4>Club at a Glance</h4><div class="pa-facts"><dl><div><dt>Location</dt><dd>' . esc_html( str_replace( ' · ', ', ', $where( $c ) ) ) . '</dd></div><div><dt>Region</dt><dd>' . esc_html( $c['region'] ) . '</dd></div></dl></div>'
		. '<div class="pa-people pa-people--contacts"><div class="pa-people-group"><div class="pa-people-label">Club Leadership</div><div class="pa-people-grid"><div class="pa-person"><div class="pa-avatar">CP</div><div><div class="pa-person-name">Club President</div><div class="pa-person-role">Name from club record [placeholder]</div></div></div></div></div></div></div>';
	$cards .= sprintf(
		'<details class="pa-dir-card" data-item data-region="%s" data-place="%s" data-cta="%s"><summary><span class="pa-dir-title">%s</span><span class="pa-dir-sub">%s</span><span class="pa-dir-tag">%s</span><span class="pa-dir-more"><span class="open-txt">View details</span><span class="close-txt">Close</span></span></summary><div class="pa-dir-panel"><div class="pa-dir-panel-grid">%s%s</div></div></details>',
		esc_attr( $rkey[ $c['region'] ] ?? 'international' ), esc_attr( sanitize_title( $place( $c ) ) ), $type, $name, esc_html( $where( $c ) ), esc_html( $tags[ $i % 5 ] ), $left, $right
	);
}

$us   = array_unique( array_map( $place, array_filter( $clubs, fn( $c ) => 'United States' === $c['country'] ) ) );
$intl = array_unique( array_map( $place, array_filter( $clubs, fn( $c ) => 'United States' !== $c['country'] ) ) );
sort( $us );
sort( $intl );
$opts = '<option value="all">All locations</option>';
foreach ( array_merge( $us, $intl ) as $p ) {
	$opts .= '<option value="' . esc_attr( sanitize_title( $p ) ) . '">' . esc_html( $p ) . '</option>';
}
$pills = '';
foreach ( array( 'all' => 'All', 'northeast' => 'Northeast', 'southeast' => 'Southeast', 'midwest' => 'Midwest', 'west' => 'West Coast', 'international' => 'International' ) as $k => $l ) {
	$pills .= '<button class="pa-pill" data-pill="region:' . $k . '" aria-pressed="' . ( 'all' === $k ? 'true' : 'false' ) . '">' . $l . '</button>';
}
$filters = '<div class="pa-filters"><div class="pa-field"><label for="f-place">State / Country</label><select id="f-place" data-filter="place">' . $opts . '</select></div><div class="pa-field pa-field--grow"><label for="f-q">Search</label><input id="f-q" type="search" data-filter="q" placeholder="Search by club name, city, state, or country…"></div><div class="pa-pills" role="group" aria-label="Filter">' . $pills . '</div></div><p class="pa-results-count" data-results-count></p>';
$head    = '<div class="pa-head"><div class="pa-head-eyebrow">Club Directory</div><h2 class="pa-head-title">Find your <em>Penn club</em></h2><p class="pa-head-intro">' . count( $clubs ) . ' clubs across ' . count( $us ) . ' U.S. states and territories and ' . count( $intl ) . ' countries. Search by name or location, or filter by region.</p></div>';

printf(
	'<div %s>%s<div data-filter-scope>%s<div class="pa-dir-grid pa-dir-grid--names">%s</div><p class="pa-proto-note" data-empty hidden>No clubs match. Try a different search or clear the filters.</p></div></div>',
	get_block_wrapper_attributes( array( 'class' => 'pa-directory' ) ), $head, $filters, $cards
);
