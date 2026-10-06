<?php
/**
 * Tools → Penn Alumni Starter Content
 * Creates (or refreshes) the prototype pages from content/pages.json, builds the menus from
 * data/menus.json, sets the homepage and pretty permalinks. Safe to run more than once:
 * pages are matched by URL and updated in place.
 *
 * Also callable from WP-CLI / Pantheon Terminus:  wp eval 'pa_import_starter_content();'
 */
defined( 'ABSPATH' ) || exit;

function pa_import_starter_content() {
	$log = array();
	if ( ! get_current_user_id() ) {
		$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
		$admins && wp_set_current_user( (int) $admins[0] );
	}
	kses_remove_filters(); // keep the prototype's SVG icons, iframes and data attributes intact
	$manifest = json_decode( (string) file_get_contents( PA_DIR . '/content/pages.json' ), true ) ?: array();

	// Pretty permalinks so URLs match docs/url-map.csv.
	if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
		$log[] = 'Permalinks set to /%postname%/';
	}

	$ids = array( '/' => 0 );
	usort( $manifest, fn( $a, $b ) => substr_count( $a['url'], '/' ) <=> substr_count( $b['url'], '/' ) );
	$order = 0;
	foreach ( $manifest as $p ) {
		$path = trim( $p['url'], '/' );
		// Make sure parent pages exist (e.g. /events/alumni-weekend/ before its reunion pages).
		$parent_id = 0;
		if ( $p['parent'] ) {
			$parent_id = $ids[ $p['parent'] ] ?? 0;
			if ( ! $parent_id ) {
				$pp        = get_page_by_path( trim( $p['parent'], '/' ) );
				$parent_id = $pp ? $pp->ID : 0;
			}
		}
		$content  = str_replace( '{{theme}}', PA_URI, (string) file_get_contents( PA_DIR . '/content/pages/' . $p['file'] ) );
		$existing = '' === $path ? get_page_by_path( 'home' ) : get_page_by_path( $path );
		$data     = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $p['title'],
			'post_name'    => $p['slug'],
			'post_parent'  => $parent_id,
			'post_content' => wp_slash( $content ),
			'post_excerpt' => $p['description'] ?? '',
			'menu_order'   => $order++,
		);
		if ( $existing ) {
			$data['ID'] = $existing->ID;
			$id         = wp_update_post( $data, true );
			$log[]      = 'Updated ' . $p['url'];
		} else {
			$id    = wp_insert_post( $data, true );
			$log[] = 'Created ' . $p['url'];
		}
		if ( is_wp_error( $id ) ) {
			$log[] = '  ! ' . $id->get_error_message();
			continue;
		}
		$ids[ $p['url'] ] = $id;
		update_post_meta( $id, '_pa_source_file', $p['source'] );
		if ( '/' === $p['url'] ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $id );
		}
	}

	// Remove WordPress's demo content if it is still there.
	foreach ( array( 'sample-page' => 'page', 'hello-world' => 'post' ) as $slug => $type ) {
		$d = get_page_by_path( $slug, OBJECT, $type );
		if ( $d ) {
			wp_trash_post( $d->ID );
		}
	}

	$log = array_merge( $log, pa_import_menus( $ids ) );
	flush_rewrite_rules();
	return $log;
}

function pa_import_menus( $ids ) {
	$log   = array();
	$menus = json_decode( (string) file_get_contents( PA_DIR . '/data/menus.json' ), true ) ?: array();
	$names = array(
		'primary'          => 'Main navigation',
		'utility'          => 'Utility bar',
		'footer-explore'   => 'Footer · Explore',
		'footer-resources' => 'Footer · Resources',
		'footer-follow'    => 'Footer · Follow',
	);
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	foreach ( $names as $loc => $name ) {
		if ( empty( $menus[ $loc ] ) ) {
			continue;
		}
		$existing = wp_get_nav_menu_object( $name );
		if ( $existing ) {
			wp_delete_nav_menu( $existing->term_id ); // rebuild cleanly
		}
		$menu_id = wp_create_nav_menu( $name );
		foreach ( $menus[ $loc ] as $item ) {
			$parent = pa_add_menu_item( $menu_id, $item, 0, $ids );
			foreach ( $item['children'] ?? array() as $child ) {
				pa_add_menu_item( $menu_id, $child, $parent, $ids );
			}
		}
		$locations[ $loc ] = $menu_id;
		$log[]             = 'Menu "' . $name . '" (' . count( $menus[ $loc ] ) . ' items)';
	}
	set_theme_mod( 'nav_menu_locations', $locations );
	return $log;
}

function pa_add_menu_item( $menu_id, $item, $parent, $ids ) {
	$url  = $item['url'];
	$args = array(
		'menu-item-title'       => $item['title'],
		'menu-item-status'      => 'publish',
		'menu-item-parent-id'   => $parent,
		'menu-item-description' => $item['description'] ?? '',
		'menu-item-attr-title'  => $item['overviewLabel'] ?? '',
		'menu-item-target'      => ! empty( $item['newTab'] ) ? '_blank' : '',
	);
	$base = strtok( $url, '#' );
	if ( isset( $ids[ $base ] ) && $ids[ $base ] && ! str_contains( $url, '#' ) ) {
		$args['menu-item-object-id'] = $ids[ $base ];
		$args['menu-item-object']    = 'page';
		$args['menu-item-type']      = 'post_type';
	} else {
		$args['menu-item-url']  = str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
		$args['menu-item-type'] = 'custom';
	}
	return wp_update_nav_menu_item( $menu_id, 0, $args );
}

/* Admin screen: Tools → Penn Alumni Starter Content */
add_action( 'admin_menu', function () {
	add_management_page( 'Penn Alumni Starter Content', 'Penn Alumni Starter Content', 'manage_options', 'pa-starter', function () {
		echo '<div class="wrap"><h1>Penn Alumni starter content</h1>';
		if ( isset( $_POST['pa_import'] ) && check_admin_referer( 'pa_import' ) ) {
			$log = pa_import_starter_content();
			echo '<div class="notice notice-success"><p><strong>Done.</strong></p><pre style="white-space:pre-wrap">' . esc_html( implode( "\n", $log ) ) . '</pre></div>';
		}
		echo '<p>Creates or refreshes the prototype pages (from the theme\'s <code>content/</code> folder) and the header/footer menus. Pages are matched by URL and overwritten — edits made to those pages in WordPress will be replaced.</p>';
		echo '<form method="post">';
		wp_nonce_field( 'pa_import' );
		submit_button( 'Import / refresh starter content', 'primary', 'pa_import' );
		echo '</form></div>';
	} );
} );
