<?php
/**
 * paEventFeed — the "custom connection" point for Blackthorn/Salesforce.
 * Prototype: reads data/sample-events.json. Production: replace pa_event_feed_items() with a cached
 * server-side call to Salesforce (Blackthorn Event + Event Group objects) — editors keep the same block.
 */
if ( ! function_exists( 'pa_event_feed_items' ) ) {
	function pa_event_feed_items( $filter, $count ) {
		$all = json_decode( (string) file_get_contents( PA_DIR . '/data/sample-events.json' ), true ) ?: array();
		/** Filter hook so the Salesforce connector can supply real events without touching this block. */
		$all = apply_filters( 'pa_event_feed_items', $all, $filter, $count );
		if ( $filter ) {
			$match = array_values( array_filter( $all, function ( $e ) use ( $filter ) {
				foreach ( array( $e['group'] ?? '', $e['catl'] ?? '' ) as $v ) {
					if ( $v && ( stripos( $v, $filter ) !== false || stripos( $filter, $v ) !== false ) ) {
						return true;
					}
				}
				return false;
			} ) );
			if ( $match ) {
				$all = array_merge( $match, array_filter( $all, fn( $e ) => ! in_array( $e, $match, true ) ) );
			}
		}
		return array_slice( $all, 0, max( 1, (int) $count ) );
	}
}
$layout = $attributes['layout'] ?? 'list';
$count  = (int) ( $attributes['count'] ?? 5 );
$filter = $attributes['filter'] ?? '';
$items  = pa_event_feed_items( $filter, $count );
$link   = fn( $e ) => esc_url( ! empty( $e['url'] ) ? $e['url'] : home_url( '/events/' ) );

$note = pa_opt( 'proto_banner' ) ? '<p class="pa-proto-note">Event feed · sample data · layout=' . esc_html( $layout ) . ' · count=' . $count . ( $filter ? ' · filter: ' . esc_html( $filter ) : '' ) . ' → production: live from Blackthorn via the Salesforce connection.</p>' : '';

$out = '';
if ( 'cards' === $layout ) {
	$cols = in_array( (int) ( $attributes['columns'] ?? 3 ), array( 2, 3, 4 ), true ) ? (int) $attributes['columns'] : 3;
	foreach ( $items as $e ) {
		$out .= sprintf(
			'<a class="pa-ev-card" href="%s"><span class="pa-date"><span class="pa-date-mo">%s</span><span class="pa-date-day">%s</span></span><span><span class="pa-ev-tag" data-cat="%s">%s</span><span class="pa-ev-title">%s</span><span class="pa-ev-meta">%s</span></span></a>',
			$link( $e ), esc_html( $e['mo'] ), esc_html( $e['day'] ), esc_attr( $e['cat'] ), esc_html( $e['catl'] ), esc_html( $e['title'] ), esc_html( $e['meta'] ?? '' )
		);
	}
	$out = '<div class="pa-grid pa-grid--' . $cols . '">' . $out . '</div>';
} else {
	foreach ( $items as $e ) {
		$out .= sprintf(
			'<li><a class="pa-ev-row" href="%s"><span class="pa-date"><span class="pa-date-mo">%s</span><span class="pa-date-day">%s</span></span><span><span class="pa-ev-tag" data-cat="%s">%s</span><span class="pa-ev-title">%s</span></span><span class="pa-ev-time">%s</span></a></li>',
			$link( $e ), esc_html( $e['mo'] ), esc_html( $e['day'] ), esc_attr( $e['cat'] ), esc_html( $e['catl'] ), esc_html( $e['title'] ), esc_html( $e['time'] ?? '' )
		);
	}
	$out = '<ul class="pa-ev-list">' . $out . '</ul>';
}
printf( '<div %s>%s%s</div>', get_block_wrapper_attributes( array( 'class' => 'pa-ev-feed', 'data-layout' => $layout ) ), $note, $out );
