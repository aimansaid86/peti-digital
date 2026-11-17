<?php
defined( 'ABSPATH' ) || exit;

class PD_REST {

	const NS = 'peti-digital/v1';

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function can_access(): bool {
		return current_user_can( 'pd_access' );
	}

	public static function can_manage(): bool {
		return current_user_can( 'pd_manage' );
	}

	/** Bungkus pengendali supaya PD_Exception menjadi respons ralat yang kemas. */
	private static function wrap( callable $fn ): callable {
		return static function ( WP_REST_Request $req ) use ( $fn ) {
			try {
				return rest_ensure_response( $fn( $req ) );
			} catch ( PD_Exception $e ) {
				return new WP_Error( 'pd_error', $e->getMessage(), array( 'status' => $e->status ) );
			}
		};
	}

	private static function add( string $route, string $methods, callable $fn, bool $manage = false ): void {
		register_rest_route(
			self::NS,
			$route,
			array(
				'methods'             => $methods,
				'callback'            => self::wrap( $fn ),
				'permission_callback' => $manage ? array( __CLASS__, 'can_manage' ) : array( __CLASS__, 'can_access' ),
			)
		);
	}

	public static function routes(): void {
		// ---- fail
		self::add( '/files', 'GET', static fn( $r ) => PD_Storage::list_dir( (string) $r['path'] ) );
		self::add( '/search', 'GET', static fn( $r ) => array( 'items' => PD_Storage::search( (string) $r['q'] ) ) );

		self::add(
			'/folder',
			'POST',
			static function ( $r ) {
				$item = PD_Storage::mkdir( (string) $r['path'], (string) $r['name'] );
				PD_Activity::log( 'mkdir', $item['path'] );
				return $item;
			}
		);

		self::add(
			'/rename',
			'POST',
			static function ( $r ) {
				$old  = PD_Storage::clean_rel( (string) $r['path'] );
				$item = PD_Storage::rename( $old, (string) $r['name'] );
				if ( $item['path'] !== $old ) {
					PD_Activity::log( 'rename', $item['path'], 'Dari: ' . $old );
				}
				return $item;
			}
		);

		self::add(
			'/upload',
			'POST',
			static function ( WP_REST_Request $r ) {
				$files = $r->get_file_params();
				if ( empty( $files['file'] ) ) {
					throw new PD_Exception( 'Tiada fail diterima. Fail mungkin melebihi had muat naik server ({max}).', 400, array( 'max' => size_format( wp_max_upload_size() ) ) );
				}
				$item = PD_Storage::save_upload( $files['file'], (string) $r['path'] );
				PD_Activity::log( 'upload', $item['path'], size_format( $item['size'] ) );
				return $item;
			}
		);

		self::add(
			'/delete',
			'POST',
			static function ( $r ) {
				$paths = array_slice( (array) $r['paths'], 0, 500 );
				$done  = array();
				$errors = array();
				foreach ( $paths as $p ) {
					try {
						$res    = PD_Trash::trash( (string) $p );
						$done[] = $p;
						PD_Activity::log( 'delete', (string) $p, $res['is_dir'] ? 'Folder' : '' );
					} catch ( PD_Exception $e ) {
						$errors[] = $e->getMessage();
					}
				}
				if ( ! $done && $errors ) {
					throw new PD_Exception( $errors[0] );
				}
				return array( 'deleted' => $done, 'errors' => $errors );
			}
		);

		// ---- tong sampah
		self::add( '/trash', 'GET', static fn() => array( 'items' => PD_Trash::list(), 'days' => (int) PD_Settings::get( 'trash_days' ) ) );
		self::add(
			'/trash/restore',
			'POST',
			static function ( $r ) {
				$item = PD_Trash::restore( (int) $r['id'] );
				PD_Activity::log( 'restore', $item['path'] );
				return $item;
			}
		);
		self::add(
			'/trash/purge',
			'POST',
			static function ( $r ) {
				if ( ! empty( $r['all'] ) ) {
					$n = PD_Trash::purge_all();
					PD_Activity::log( 'purge', '', 'Kosongkan tong sampah (' . $n . ' item)' );
					return array( 'purged' => $n );
				}
				$path = PD_Trash::purge( (int) $r['id'] );
				PD_Activity::log( 'purge', $path );
				return array( 'purged' => 1 );
			},
			true
		);

		// ---- halaman utama
		self::add(
			'/dashboard',
			'GET',
			static function () {
				$stats         = PD_Storage::stats();
				$stats['quota'] = PD_Settings::quota_bytes();
				$recent        = PD_Activity::query( array( 'action' => PD_Activity::FILE_ACTIONS, 'per_page' => 8 ), false );
				$stats['activity'] = $recent['items'];
				$stats['users']    = PD_Users::count_active();
				$stats['uploads_week'] = PD_Activity::count_since( 'upload', WEEK_IN_SECONDS );
				if ( self::can_manage() ) {
					$stats['protection'] = PD_Storage::protection_status();
				}
				return $stats;
			}
		);

		// Ringkasan storan untuk bar sisi: hanya ruang digunakan (kiraan cache), tanpa imbasan penuh.
		self::add(
			'/storage',
			'GET',
			static fn() => array(
				'size'  => PD_Storage::used_bytes(),
				'quota' => PD_Settings::quota_bytes(),
			)
		);

		// Semak semula sama ada folder storan boleh dicapai terus (selepas membetulkan pelayan).
		self::add(
			'/protection',
			'POST',
			static fn() => array( 'protection' => PD_Storage::protection_status( true ) ),
			true
		);

		// ---- log aktiviti: pentadbir nampak semua, staf nampak aktiviti sendiri
		self::add(
			'/activity',
			'GET',
			static function ( $r ) {
				$manage = self::can_manage();
				$args   = array(
					'page'     => $r['page'],
					'per_page' => 25,
					'action'   => $r['action'] ? array( (string) $r['action'] ) : null,
					'search'   => (string) $r['search'],
					'user_id'  => $manage ? (int) $r['user_id'] : get_current_user_id(),
				);
				$res            = PD_Activity::query( $args, $manage );
				$res['labels']  = PD_Activity::labels();
				return $res;
			}
		);

		// ---- profil sendiri
		self::add( '/me', 'GET', static fn() => PD_Users::to_array( wp_get_current_user() ) );
		self::add( '/me', 'POST', static fn( $r ) => PD_Users::update_me( $r->get_params() ) );

		// ---- pentadbir: pengguna & tetapan
		self::add( '/users', 'GET', static fn() => array( 'items' => PD_Users::all() ), true );
		self::add( '/users', 'POST', static fn( $r ) => PD_Users::create( $r->get_params() ), true );
		self::add( '/users/(?P<id>\d+)', 'POST', static fn( $r ) => PD_Users::update( (int) $r['id'], $r->get_params() ), true );

		self::add( '/settings', 'GET', static fn() => PD_Settings::public_data(), true );
		self::add(
			'/settings',
			'POST',
			static function ( $r ) {
				PD_Settings::update( $r->get_params() );
				PD_Activity::log( 'settings' );
				return PD_Settings::public_data();
			},
			true
		);
	}
}
