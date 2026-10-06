<?php
/**
 * Penn Alumni theme bootstrap.
 *
 * Everything the site needs ships inside this theme — no third-party plugins.
 *   inc/setup.php     theme supports, menus, CSS/JS, block styles
 *   inc/markup.php    maps core block output onto the Section Library classes (buttons, accordions)
 *   inc/blocks.php    registers the Penn blocks in /blocks (section, hero, card grid, event feed…)
 *   inc/settings.php  Settings → Penn Alumni (contact info, Blackthorn org ID, Form Assembly URL)
 *   inc/importer.php  Tools → Penn Alumni Starter Content (creates the prototype pages + menus)
 */

defined( 'ABSPATH' ) || exit;

define( 'PA_THEME_VERSION', '0.1.0' );
define( 'PA_DIR', get_template_directory() );
define( 'PA_URI', get_template_directory_uri() );

require PA_DIR . '/inc/helpers.php';
require PA_DIR . '/inc/setup.php';
require PA_DIR . '/inc/markup.php';
require PA_DIR . '/inc/settings.php';
require PA_DIR . '/inc/blocks.php';
require PA_DIR . '/inc/importer.php';
