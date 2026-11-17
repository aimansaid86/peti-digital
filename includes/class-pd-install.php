<?php
defined( 'ABSPATH' ) || exit;

class PD_Install {

	const DB_VERSION = '2';

	public static function activate(): void {
		self::install();
		PD_Portal::rewrites();
		flush_rewrite_rules();
		// Pengguna sedia ada berperanan "subscriber" ialah staf sistem fail lama: beri akses portal.
		foreach ( get_users( array( 'role' => 'subscriber', 'fields' => 'all' ) ) as $u ) {
			$u->add_role( PD_Users::ROLE_STAFF );
		}
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( PD_Trash::CRON );
		flush_rewrite_rules();
	}

	public static function install(): void {
		PD_Users::register_roles();
		PD_Activity::create_table();
		PD_Trash::create_table();
		self::protect_dirs();
		if ( ! wp_next_scheduled( PD_Trash::CRON ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', PD_Trash::CRON );
		}
		update_option( 'pd_db_version', self::DB_VERSION );
	}

	/** Semakan penuh kandungan .htaccess pelindung (pasang, naik taraf, dan cron harian). */
	public static function protect_dirs(): void {
		PD_Storage::ensure_protected_dir( PD_Storage::root() );
		PD_Storage::ensure_protected_dir( PD_Storage::uploads_base() . '/peti-digital-data' );
		PD_Storage::internal_dir( 'trash' );
		delete_transient( 'pd_protection' );
	}

	public static function maybe_upgrade(): void {
		if ( get_option( 'pd_db_version' ) !== self::DB_VERSION ) {
			self::install();
			add_action( 'init', 'flush_rewrite_rules', 99 );
		}
	}
}
