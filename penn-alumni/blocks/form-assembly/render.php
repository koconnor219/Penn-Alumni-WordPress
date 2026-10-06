<?php
/** Form Assembly form by number. Base URL (e.g. https://upenn.tfaforms.net) lives in Settings → Penn Alumni. */
$base  = untrailingslashit( (string) pa_opt( 'fa_base' ) );
$id    = preg_replace( '/\D/', '', (string) ( $attributes['formId'] ?? '' ) );
$title = $attributes['title'] ?: 'Form';
$h     = max( 300, (int) ( $attributes['height'] ?? 900 ) );
$wrap  = get_block_wrapper_attributes( array( 'class' => 'pa-fa-embed' ) );

if ( ! $base || ! $id ) {
	printf(
		'<div %s><div class="pa-embed"><div class="pa-embed-head"><h3>%s</h3><span class="pa-badge">Form Assembly</span></div><div class="pa-embed-body"><p class="pa-proto-note">Form Assembly form%s loads here. Set the Form Assembly address in Settings → Penn Alumni and the form number on this block. Form fields, logic and notifications are edited in Form Assembly — no website change needed.</p><form class="pa-form-grid" onsubmit="return false" aria-hidden="true"><div class="pa-field"><label>First name</label><input type="text" disabled></div><div class="pa-field"><label>Last name</label><input type="text" disabled></div><div class="pa-field pa-field--full"><label>Email address</label><input type="email" disabled></div><div class="pa-field pa-field--full"><label>Message</label><textarea rows="4" disabled></textarea></div></form></div></div></div>',
		$wrap, esc_html( $title ), $id ? ' #' . esc_html( $id ) : ''
	);
	return;
}
printf(
	'<div %s><iframe src="%s" title="%s" style="width:100%%;height:%dpx;border:0" loading="lazy"></iframe></div>',
	$wrap, esc_url( $base . '/' . $id ), esc_attr( $title ), $h
);
