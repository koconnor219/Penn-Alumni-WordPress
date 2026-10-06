<?php
/**
 * Blackthorn Events embed (https://docs.blackthorn.io/docs/iframes-advanced-embedding).
 * Advanced mode: embed.js + EventsApp, auto-resizes and scrolls to top on navigation.
 * Simple mode: the one-line /loader script.
 * Org ID lives in Settings → Penn Alumni; each block only needs the path (g/… for a group, or an event path).
 */
$org  = pa_opt( 'bt_org' );
$path = trim( (string) ( $attributes['path'] ?: pa_opt( 'bt_path' ) ), '/' );
$h    = max( 400, (int) ( $attributes['height'] ?? 900 ) );
$wrap = get_block_wrapper_attributes( array( 'class' => 'pa-bt-embed' ) );

if ( ! $org || ! $path ) {
	$label = $attributes['label'] ?: 'Blackthorn events calendar';
	printf(
		'<div %s><div class="pa-embed"><div class="pa-embed-head"><h3>%s</h3><span class="pa-badge">Blackthorn</span></div><div class="pa-embed-body"><p class="pa-proto-note">This is where the live Blackthorn %s appears. To connect it: Settings → Penn Alumni → enter the Blackthorn Org ID, then give this block an event-group path (e.g. <code>g/ZZ8AC6qtdC</code>). Registration happens inside this frame, on this page. Sample events are shown below until then.</p></div></div>%s</div>',
		$wrap,
		esc_html( $label ),
		$attributes['path'] ? 'view (<code>' . esc_html( $attributes['path'] ) . '</code>)' : 'calendar',
		render_block( array( 'blockName' => 'penn/event-feed', 'attrs' => array( 'layout' => 'cards', 'count' => 6, 'columns' => 2, 'filter' => $attributes['path'] ? $attributes['label'] : '' ), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) )
	);
	return;
}

if ( 'simple' === ( $attributes['mode'] ?? 'advanced' ) ) {
	printf(
		'<div %s><script src="https://events.blackthorn.io/loader" data-path="/en/%s/%s" auto-resize="true"></script></div>',
		$wrap, esc_attr( $org ), esc_attr( $path )
	);
	return;
}

$id = wp_unique_id( 'pa-bt-' );
wp_enqueue_script( 'blackthorn-embed', 'https://events.blackthorn.io/embed.js', array(), null, true );
wp_add_inline_script( 'blackthorn-embed', sprintf(
	'(function(){var el=document.getElementById(%1$s);if(!el||typeof EventsApp==="undefined")return;var app=new EventsApp({orgId:%2$s,path:%3$s,height:"100%%",width:"100%%",scrolling:"no",listeners:[{event:"CONTENT_SIZE_CHANGED",handler:function(p){el.style.height=p.height+"px";}},{event:"ROUTE_CHANGED",handler:function(){el.scrollIntoView({behavior:"smooth"});}},{event:"FORM_SUBMITTED",handler:function(p){(window.dataLayer=window.dataLayer||[]).push({event:"blackthorn_form_submitted",path:%3$s});}}]});app.mount("#"+%1$s);})();',
	wp_json_encode( $id ), wp_json_encode( $org ), wp_json_encode( $path )
) );
printf( '<div %s><div id="%s" class="pa-bt-frame" style="min-height:%dpx"></div></div>', $wrap, esc_attr( $id ), $h );
