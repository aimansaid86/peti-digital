<?php
defined( 'ABSPATH' ) || exit;

/**
 * Laluan portal (halaman penuh, bebas daripada tema):
 *   /peti-digital/            aplikasi
 *   /peti-digital/log-masuk/  log masuk
 *   /peti-digital/log-keluar/ log keluar
 *   /peti-digital/fail/       hantar fail (pratonton / muat turun)
 *   /peti-digital/lakaran/    lakaran kecil gambar
 *   /peti-digital/zip/        muat turun folder / pilihan sebagai zip
 */
class PD_Portal {

	const ROUTES = array( 'app' => '', 'login' => 'log-masuk', 'logout' => 'log-keluar', 'file' => 'fail', 'thumb' => 'lakaran', 'zip' => 'zip' );

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'rewrites' ) );
		add_filter( 'query_vars', static fn( $v ) => array_merge( $v, array( 'pd_route' ) ) );
		add_action( 'parse_request', array( __CLASS__, 'dispatch' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'home_redirect' ), 1 );

		// Halaman log masuk WordPress digantikan dengan halaman log masuk portal.
		add_filter( 'login_url', array( __CLASS__, 'filter_login_url' ), 10, 3 );
		add_action( 'login_init', array( __CLASS__, 'redirect_wp_login' ) );
		add_filter( 'logout_redirect', array( __CLASS__, 'filter_logout_redirect' ), 10, 2 );
	}

	public static function filter_login_url( $url, $redirect = '', $force_reauth = false ) {
		$args = array();
		if ( $redirect ) {
			$args['redirect_to'] = $redirect;
		}
		if ( $force_reauth ) {
			$args['reauth'] = '1';
		}
		return self::url( 'login', $args );
	}

	/**
	 * Lawatan biasa ke wp-login.php dihantar ke portal. Tindakan lain (lupa / set semula
	 * kata laluan, log keluar, kata laluan halaman, log masuk interim dalam wp-admin)
	 * kekal di wp-login.php kerana portal tidak menggantikannya.
	 */
	public static function redirect_wp_login(): void {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login';
		if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) || 'login' !== $action || ! empty( $_REQUEST['interim-login'] ) ) {
			return;
		}
		$args = array();
		if ( ! empty( $_GET['redirect_to'] ) ) {
			$args['redirect_to'] = wp_unslash( (string) $_GET['redirect_to'] );
		}
		if ( ! empty( $_GET['loggedout'] ) ) {
			$args['loggedout'] = '1';
		}
		wp_safe_redirect( self::url( 'login', $args ) );
		exit;
	}

	public static function filter_logout_redirect( $to, $requested ) {
		return $requested ? $to : self::url( 'login', array( 'loggedout' => '1' ) );
	}

	public static function rewrites(): void {
		foreach ( self::ROUTES as $route => $slug ) {
			$regex = '^' . PD_SLUG . ( '' === $slug ? '' : '/' . $slug ) . '/?$';
			add_rewrite_rule( $regex, 'index.php?pd_route=' . $route, 'top' );
		}
	}

	public static function url( string $route = 'app', array $args = array() ): string {
		$slug = self::ROUTES[ $route ] ?? '';
		if ( get_option( 'permalink_structure' ) ) {
			$url = home_url( '/' . PD_SLUG . '/' . ( '' === $slug ? '' : $slug . '/' ) );
		} else {
			$url = add_query_arg( 'pd_route', $route, home_url( '/' ) );
		}
		return $args ? add_query_arg( array_map( 'rawurlencode', $args ), $url ) : $url;
	}

	public static function home_redirect(): void {
		if ( PD_Settings::get( 'home_redirect' ) && ( is_front_page() || is_home() ) ) {
			wp_safe_redirect( self::url() );
			exit;
		}
	}

	public static function dispatch( WP $wp ): void {
		$route = $wp->query_vars['pd_route'] ?? '';
		if ( '' === $route || ! isset( self::ROUTES[ $route ] ) ) {
			return;
		}
		// ?pd_lang=en|ms pada mana-mana laluan portal menukar bahasa, kemudian kembali ke URL
		// yang sama tanpa parameter itu (pelayar mengekalkan #hash aplikasi).
		if ( isset( $_GET['pd_lang'] ) && 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			PD_I18n::set( sanitize_key( wp_unslash( $_GET['pd_lang'] ) ) );
			wp_safe_redirect( remove_query_arg( 'pd_lang' ) );
			exit;
		}
		nocache_headers();
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: same-origin' );
		try {
			switch ( $route ) {
				case 'login':
					self::login_page();
					break;
				case 'logout':
					self::logout();
					break;
				case 'file':
					self::require_access();
					self::send_file( wp_unslash( (string) ( $_GET['path'] ?? '' ) ), ! empty( $_GET['dl'] ) );
					break;
				case 'thumb':
					self::require_access();
					self::send_thumb( wp_unslash( (string) ( $_GET['path'] ?? '' ) ) );
					break;
				case 'zip':
					self::require_access();
					// Pilihan besar dihantar melalui POST (senarai laluan dalam URL boleh melebihi
					// had panjang URL pelayan). POST memerlukan nonce supaya tidak boleh dipalsukan.
					if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
						if ( ! isset( $_POST['_pdnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_pdnonce'] ), 'pd_zip' ) ) {
							throw new PD_Exception( 'Sesi tamat. Muat semula halaman dan cuba lagi.', 403 );
						}
						$paths = (array) wp_unslash( $_POST['paths'] ?? array() );
					} else {
						$paths = (array) wp_unslash( $_GET['paths'] ?? array() );
					}
					self::send_zip( $paths );
					break;
				default:
					self::app_page();
			}
		} catch ( PD_Exception $e ) {
			status_header( $e->status );
			self::message_page( pd__( 'Ralat' ), $e->getMessage() );
		}
		exit;
	}

	private static function require_access(): void {
		if ( ! is_user_logged_in() ) {
			throw new PD_Exception( 'Sila log masuk untuk mengakses fail ini.', 401 );
		}
		if ( ! current_user_can( 'pd_access' ) ) {
			throw new PD_Exception( 'Anda tiada akses kepada Peti Digital.', 403 );
		}
	}

	// ------------------------------------------------------------------ halaman

	private static function brand(): array {
		return array(
			'title'    => (string) PD_Settings::get( 'portal_title' ),
			'subtitle' => PD_I18n::setting_text( (string) PD_Settings::get( 'portal_subtitle' ), (string) PD_Settings::get( 'portal_subtitle_en' ) ),
			'org'      => PD_I18n::setting_text( (string) PD_Settings::get( 'org_name' ), (string) PD_Settings::get( 'org_name_en' ) ),
		);
	}

	private static function asset( string $file ): string {
		$path = PD_DIR . 'assets/' . $file;
		return PD_URL . 'assets/' . $file . '?v=' . ( file_exists( $path ) ? filemtime( $path ) : PD_VERSION );
	}

	private static function app_page(): void {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( self::url( 'login' ) );
			exit;
		}
		if ( ! current_user_can( 'pd_access' ) ) {
			status_header( 403 );
			self::message_page( pd__( 'Tiada akses' ), pd__( 'Akaun anda belum diberi akses kepada portal ini. Sila hubungi pentadbir.' ), true );
			return;
		}
		$user   = wp_get_current_user();
		$brand  = self::brand();
		$config = array(
			'rest'     => esc_url_raw( rest_url( PD_REST::NS ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'zipNonce' => wp_create_nonce( 'pd_zip' ),
			'user'     => PD_Users::to_array( $user ),
			'canManage' => current_user_can( 'pd_manage' ),
			'isWpAdmin' => current_user_can( 'manage_options' ),
			'urls'     => array(
				'file'   => self::url( 'file' ),
				'thumb'  => self::url( 'thumb' ),
				'zip'    => self::url( 'zip' ),
				'logout' => wp_nonce_url( self::url( 'logout' ), 'pd_logout' ),
				'wpAdmin' => admin_url(),
				'lang'    => self::url(),
			),
			'brand'    => $brand,
			'lang'     => PD_I18n::lang(),
			'locale'   => PD_I18n::js_locale(),
			'langs'    => PD_I18n::LANGS,
			'i18n'     => (object) PD_I18n::js_dict(),
			'settings' => array(
				'maxUpload' => PD_Settings::max_upload_bytes(),
				'trashDays' => (int) PD_Settings::get( 'trash_days' ),
			),
		);
		include PD_DIR . 'templates/app.php';
	}

	private static function login_page(): void {
		$redirect = isset( $_REQUEST['redirect_to'] ) ? wp_validate_redirect( wp_unslash( (string) $_REQUEST['redirect_to'] ), self::url() ) : self::url();
		// reauth=1 datang dari auth_redirect() wp-admin apabila kuki admin tidak sah walaupun
		// kuki log masuk masih ada. Seperti wp-login.php: kosongkan kuki dan papar borang,
		// jika tidak portal dan wp-admin akan saling mengalih tanpa henti.
		if ( ! empty( $_REQUEST['reauth'] ) && 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			wp_clear_auth_cookie();
			wp_set_current_user( 0 );
		} elseif ( is_user_logged_in() && current_user_can( 'pd_access' ) ) {
			wp_safe_redirect( $redirect );
			exit;
		}
		$error    = '';
		$notice   = '';
		$username = '';
		if ( isset( $_GET['loggedout'] ) ) {
			$notice = pd__( 'Anda telah log keluar.' );
		}
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$username = sanitize_user( wp_unslash( (string) ( $_POST['log'] ?? '' ) ) );
			// Had cubaan gagal dikuatkuasakan dalam PD_Users::throttle untuk semua laluan log masuk.
			if ( ! isset( $_POST['_pdnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_pdnonce'] ), 'pd_login' ) ) {
				$error = pd__( 'Sesi borang tamat. Sila cuba lagi.' );
			} else {
				$user = wp_signon(
					array(
						'user_login'    => $username,
						'user_password' => (string) wp_unslash( $_POST['pwd'] ?? '' ),
						'remember'      => ! empty( $_POST['rememberme'] ),
					),
					is_ssl()
				);
				if ( is_wp_error( $user ) ) {
					$error = in_array( $user->get_error_code(), array( 'pd_disabled', 'pd_locked' ), true )
						? $user->get_error_message()
						: pd__( 'Nama pengguna atau kata laluan tidak betul.' );
				} elseif ( ! user_can( $user, 'pd_access' ) && ! ( user_can( $user, 'edit_posts' ) && str_starts_with( $redirect, admin_url() ) ) ) {
					// Pengguna tanpa akses portal hanya dibenarkan jika mereka log masuk untuk ke wp-admin.
					wp_logout();
					$error = pd__( 'Akaun anda belum diberi akses kepada portal ini. Sila hubungi pentadbir.' );
				} else {
					wp_safe_redirect( $redirect );
					exit;
				}
			}
		}
		$brand = self::brand();
		include PD_DIR . 'templates/login.php';
	}

	private static function logout(): void {
		if ( is_user_logged_in() ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'pd_logout' ) ) {
				self::message_page( pd__( 'Log keluar' ), pd__( 'Pautan log keluar tidak sah atau telah tamat tempoh.' ), true );
				return;
			}
			PD_Activity::log( 'logout' );
			wp_logout();
		}
		wp_safe_redirect( self::url( 'login', array( 'loggedout' => '1' ) ) );
		exit;
	}

	public static function message_page( string $title, string $message, bool $show_logout = false ): void {
		$brand = self::brand();
		include PD_DIR . 'templates/message.php';
	}

	// ------------------------------------------------------------------ hantar fail

	private static function stream_headers( string $name, string $mime, bool $inline ): void {
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		if ( session_status() === PHP_SESSION_ACTIVE ) {
			session_write_close();
		}
		$ascii = preg_replace( '/[^\x20-\x7E]|["\\\\]/', '_', $name );
		header( 'Content-Type: ' . $mime . ( str_starts_with( $mime, 'text/' ) ? '; charset=utf-8' : '' ) );
		header( 'Content-Disposition: ' . ( $inline ? 'inline' : 'attachment' ) . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode( $name ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		// Fail yang dipaparkan tidak boleh menjalankan skrip (cth SVG berniat jahat).
		header( "Content-Security-Policy: default-src 'none'; img-src 'self' data:; media-src 'self'; style-src 'unsafe-inline'; sandbox" );
	}

	private static function send_file( string $rel, bool $download ): void {
		$abs  = PD_Storage::existing( $rel, 'file' );
		$name = basename( $abs );
		$mime = PD_Storage::mime( $name );
		// PDF perlu dipaparkan tanpa sandbox kerana pemapar PDF pelayar tidak berfungsi dalam sandbox.
		$inline = ! $download && PD_Storage::is_inline( $name );
		$size   = (int) filesize( $abs );
		if ( $download ) {
			PD_Activity::log( 'download', PD_Storage::rel_of( $abs ), size_format( $size ) );
		}
		self::stream_headers( $name, $inline ? $mime : 'application/octet-stream', $inline );
		if ( 'application/pdf' === $mime && $inline ) {
			header_remove( 'Content-Security-Policy' );
		}
		header( 'Accept-Ranges: bytes' );

		$start = 0;
		$end   = $size - 1;
		if ( $size > 0 && isset( $_SERVER['HTTP_RANGE'] ) && preg_match( '/bytes=(\d*)-(\d*)/', (string) $_SERVER['HTTP_RANGE'], $m ) ) {
			if ( '' === $m[1] ) {
				$start = max( 0, $size - (int) $m[2] );
			} else {
				$start = (int) $m[1];
				$end   = '' === $m[2] ? $end : min( (int) $m[2], $end );
			}
			if ( $start > $end || $start >= $size ) {
				status_header( 416 );
				header( 'Content-Range: bytes */' . $size );
				return;
			}
			status_header( 206 );
			header( 'Content-Range: bytes ' . $start . '-' . $end . '/' . $size );
		}
		$length = $size > 0 ? $end - $start + 1 : 0;
		header( 'Content-Length: ' . $length );
		if ( 'HEAD' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) || 0 === $length ) {
			return;
		}
		@set_time_limit( 0 );
		$fh = fopen( $abs, 'rb' );
		fseek( $fh, $start );
		$left = $length;
		while ( $left > 0 && ! feof( $fh ) && ! connection_aborted() ) {
			$chunk = fread( $fh, (int) min( 1048576, $left ) );
			echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput
			flush();
			$left -= strlen( $chunk );
		}
		fclose( $fh );
	}

	private static function send_thumb( string $rel ): void {
		$abs  = PD_Storage::existing( $rel, 'file' );
		$name = basename( $abs );
		if ( 'image' !== PD_Storage::kind( $name ) ) {
			throw new PD_Exception( 'Bukan gambar.' );
		}
		$ext   = PD_Storage::ext( $name );
		$cache = PD_Storage::internal_dir( 'thumbs' ) . '/' . md5( $abs . '|' . filemtime( $abs ) . '|' . filesize( $abs ) ) . '.jpg';
		if ( ! file_exists( $cache ) && ! in_array( $ext, array( 'svg', 'heic', 'ico', 'tif', 'tiff' ), true ) ) {
			$editor = wp_get_image_editor( $abs, array( 'mime_type' => PD_Storage::mime( $name ) ) );
			if ( ! is_wp_error( $editor ) ) {
				$editor->resize( 480, 480, false );
				$editor->set_quality( 78 );
				$saved = $editor->save( $cache, 'image/jpeg' );
				if ( is_wp_error( $saved ) ) {
					@unlink( $cache );
				}
			}
		}
		if ( file_exists( $cache ) ) {
			self::stream_headers( 'lakaran.jpg', 'image/jpeg', true );
			header( 'Cache-Control: private, max-age=604800' );
			header( 'Content-Length: ' . filesize( $cache ) );
			readfile( $cache );
			return;
		}
		// Gagal menjana lakaran (cth SVG): hantar fail asal jika selamat dipaparkan.
		if ( ! PD_Storage::is_inline( $name ) ) {
			throw new PD_Exception( 'Lakaran tidak tersedia.', 404 );
		}
		self::send_file( $rel, false );
	}

	private static function send_zip( array $paths ): void {
		$paths = array_values( array_filter( array_map( 'strval', $paths ), 'strlen' ) );
		if ( ! $paths ) {
			throw new PD_Exception( 'Tiada item dipilih.' );
		}
		$paths = array_slice( $paths, 0, 500 );
		$tmp   = PD_Storage::build_zip( $paths );
		// Padam fail sementara walaupun pengguna membatalkan muat turun di tengah jalan.
		ignore_user_abort( true );
		register_shutdown_function(
			static function () use ( $tmp ) {
				if ( file_exists( $tmp ) ) {
					@unlink( $tmp );
				}
			}
		);
		$name  = 1 === count( $paths ) ? basename( PD_Storage::clean_rel( $paths[0] ) ) : 'Peti Digital ' . wp_date( 'Y-m-d His' );
		if ( '' === $name ) {
			$name = PD_Settings::get( 'portal_title' );
		}
		PD_Activity::log( 'download', implode( ', ', $paths ), 'Zip, ' . size_format( filesize( $tmp ) ) );
		self::stream_headers( $name . '.zip', 'application/zip', false );
		header( 'Content-Length: ' . filesize( $tmp ) );
		@set_time_limit( 0 );
		$fh = fopen( $tmp, 'rb' );
		while ( $fh && ! feof( $fh ) && ! connection_aborted() ) {
			echo fread( $fh, 1048576 ); // phpcs:ignore WordPress.Security.EscapeOutput
			flush();
		}
		if ( $fh ) {
			fclose( $fh );
		}
		@unlink( $tmp );
	}
}
