<?php
defined( 'ABSPATH' ) || exit;

/**
 * Tong sampah: item yang dipadam dipindahkan ke folder dalaman dan direkod di DB
 * supaya boleh dipulihkan ke lokasi asal.
 */
class PD_Trash {

	const CRON = 'pd_trash_cleanup';

	public static function init(): void {
		add_action( self::CRON, array( __CLASS__, 'cleanup' ) );
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'pd_trash';
	}

	public static function create_table(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			'CREATE TABLE ' . self::table() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL DEFAULT '',
			original_path text NOT NULL,
			stored_name varchar(64) NOT NULL DEFAULT '',
			is_dir tinyint(1) NOT NULL DEFAULT 0,
			size bigint(20) unsigned NOT NULL DEFAULT 0,
			deleted_by bigint(20) unsigned NOT NULL DEFAULT 0,
			deleted_by_name varchar(190) NOT NULL DEFAULT '',
			deleted_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY deleted_at (deleted_at)
		) $charset;"
		);
	}

	private static function dir(): string {
		return PD_Storage::internal_dir( 'trash' );
	}

	public static function trash( $rel ): array {
		global $wpdb;
		$rel = PD_Storage::clean_rel( $rel );
		if ( '' === $rel ) {
			throw new PD_Exception( 'Folder utama tidak boleh dipadam.' );
		}
		$abs    = PD_Storage::existing( $rel );
		$is_dir = is_dir( $abs );
		$size   = $is_dir ? PD_Storage::dir_size( $abs ) : (int) filesize( $abs );
		$stored = wp_generate_password( 24, false, false );
		$target = self::dir() . '/' . $stored;
		$user   = wp_get_current_user();
		// Rekod DB dahulu: jika ia gagal, fail belum dipindahkan dan tiada apa yang hilang.
		$ok = $wpdb->insert(
			self::table(),
			array(
				'name'            => mb_substr( basename( $abs ), 0, 255 ),
				'original_path'   => $rel,
				'stored_name'     => $stored,
				'is_dir'          => $is_dir ? 1 : 0,
				'size'            => $size,
				'deleted_by'      => $user->ID,
				'deleted_by_name' => mb_substr( (string) $user->display_name, 0, 190 ),
				'deleted_at'      => current_time( 'mysql', true ),
			)
		);
		if ( ! $ok ) {
			throw new PD_Exception( 'Tidak dapat merekod item dalam tong sampah. Fail tidak dipadam.', 500 );
		}
		$row_id = (int) $wpdb->insert_id;
		if ( ! @rename( $abs, $target ) ) {
			$wpdb->delete( self::table(), array( 'id' => $row_id ) );
			throw new PD_Exception( 'Tidak dapat memadam "{name}". Fail mungkin sedang digunakan.', 500, array( 'name' => basename( $abs ) ) );
		}
		PD_Storage::flush_stats();
		return array( 'name' => basename( $abs ), 'is_dir' => $is_dir );
	}

	public static function list(): array {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT 1000' );
		$out  = array();
		foreach ( (array) $rows as $r ) {
			$out[] = array(
				'id'              => (int) $r->id,
				'name'            => $r->name,
				'original_path'   => $r->original_path,
				// dirname() pada Windows memulangkan "\" bagi item di akar: normalkan kepada "/".
				'location'        => str_replace( '\\', '/', dirname( '/' . $r->original_path ) ),
				'type'            => $r->is_dir ? 'dir' : 'file',
				'kind'            => $r->is_dir ? 'dir' : PD_Storage::kind( $r->name ),
				'ext'             => $r->is_dir ? '' : PD_Storage::ext( $r->name ),
				'size'            => (int) $r->size,
				'deleted_by_name' => $r->deleted_by_name,
				'deleted_at'      => gmdate( 'c', strtotime( $r->deleted_at . ' UTC' ) ),
			);
		}
		return $out;
	}

	private static function get( int $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
		if ( ! $row ) {
			throw new PD_Exception( 'Item tidak dijumpai dalam tong sampah.', 404 );
		}
		return $row;
	}

	public static function restore( int $id ): array {
		global $wpdb;
		$row    = self::get( $id );
		$stored = self::dir() . '/' . $row->stored_name;
		if ( ! file_exists( $stored ) ) {
			$wpdb->delete( self::table(), array( 'id' => $id ) );
			throw new PD_Exception( 'Kandungan item ini sudah tiada di server.', 410 );
		}
		$parent_rel = PD_Storage::clean_rel( dirname( '/' . $row->original_path ) );
		$parent     = PD_Storage::abs( $parent_rel );
		if ( ! is_dir( $parent ) ) {
			wp_mkdir_p( $parent );
		}
		// Tempah nama secara atomik supaya pemulihan tidak menulis ganti fail yang baru
		// dimuat naik dengan nama yang sama pada masa yang sama.
		$is_dir = is_dir( $stored );
		$target = PD_Storage::reserve_path( $parent, $row->name, $is_dir );
		$moved  = @rename( $stored, $target );
		if ( ! $moved && $is_dir ) {
			// Windows tidak membenarkan rename ke atas folder sedia ada (walaupun kosong):
			// lepaskan tempahan folder kosong itu dan cuba sekali lagi.
			@rmdir( $target );
			$moved = @rename( $stored, $target );
		}
		if ( ! $moved ) {
			if ( $is_dir ) {
				@rmdir( $target );
			} else {
				@unlink( $target );
			}
			throw new PD_Exception( 'Tidak dapat memulihkan item.', 500 );
		}
		$wpdb->delete( self::table(), array( 'id' => $id ) );
		PD_Storage::flush_stats();
		return PD_Storage::item( $target );
	}

	public static function purge( int $id ): string {
		global $wpdb;
		$row = self::get( $id );
		PD_Storage::rrmdir( self::dir() . '/' . $row->stored_name );
		$wpdb->delete( self::table(), array( 'id' => $id ) );
		return $row->original_path;
	}

	public static function purge_all(): int {
		global $wpdb;
		$ids = $wpdb->get_col( 'SELECT id FROM ' . self::table() );
		foreach ( $ids as $id ) {
			self::purge( (int) $id );
		}
		return count( $ids );
	}

	/** Dijalankan setiap hari oleh WP-Cron. */
	public static function cleanup(): void {
		global $wpdb;
		PD_Install::protect_dirs();
		$days = (int) PD_Settings::get( 'trash_days' );
		if ( $days <= 0 ) {
			return;
		}
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT id FROM ' . self::table() . ' WHERE deleted_at < %s',
				gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS )
			)
		);
		foreach ( $ids as $id ) {
			try {
				self::purge( (int) $id );
			} catch ( PD_Exception $e ) {
				continue;
			}
		}
	}
}
