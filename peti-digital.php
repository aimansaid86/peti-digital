<?php
/**
 * Plugin Name:       Peti Digital
 * Description:       Portal fail kongsi jabatan: halaman utama, pengurus fail, pengurusan pengguna dan log aktiviti. Portal di /peti-digital/.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Text Domain:       peti-digital
 * License:           GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'PD_VERSION', '1.1.0' );
define( 'PD_FILE', __FILE__ );
define( 'PD_DIR', plugin_dir_path( __FILE__ ) );
define( 'PD_URL', plugin_dir_url( __FILE__ ) );
define( 'PD_SLUG', 'peti-digital' );

require_once PD_DIR . 'includes/class-pd-i18n.php';
require_once PD_DIR . 'includes/class-pd-exception.php';
require_once PD_DIR . 'includes/class-pd-settings.php';
require_once PD_DIR . 'includes/class-pd-storage.php';
require_once PD_DIR . 'includes/class-pd-activity.php';
require_once PD_DIR . 'includes/class-pd-trash.php';
require_once PD_DIR . 'includes/class-pd-users.php';
require_once PD_DIR . 'includes/class-pd-rest.php';
require_once PD_DIR . 'includes/class-pd-portal.php';
require_once PD_DIR . 'includes/class-pd-install.php';

register_activation_hook( __FILE__, array( 'PD_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PD_Install', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		PD_Install::maybe_upgrade();
		PD_I18n::init();
		PD_Users::init();
		PD_Trash::init();
		PD_REST::init();
		PD_Portal::init();
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( PD_Portal::url() ) . '">' . esc_html( pd__( 'Buka portal' ) ) . '</a>' );
		return $links;
	}
);
