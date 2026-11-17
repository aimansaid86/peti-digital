<?php
defined( 'ABSPATH' ) || exit;

/**
 * Dwibahasa: Bahasa Melayu (sumber) dan English.
 *
 * Teks sumber ditulis dalam Bahasa Melayu terus dalam kod. Kamus English di
 * languages/en.php memetakan teks Melayu kepada English dan dikongsi dengan
 * aplikasi JS. Teks yang tiada dalam kamus dipaparkan seperti asal.
 *
 * Pilihan bahasa: meta pengguna (pd_lang) jika log masuk, jika tidak kuki pd_lang,
 * jika tidak bahasa lalai dalam tetapan portal.
 */
class PD_I18n {

	const LANGS  = array( 'ms' => 'Bahasa Melayu', 'en' => 'English' );
	const COOKIE = 'pd_lang';
	const META   = 'pd_lang';

	private static ?string $lang = null;
	private static ?array $dict  = null;

	public static function init(): void {
		// Pilihan yang dibuat di halaman log masuk dibawa ke akaun selepas log masuk.
		add_action(
			'wp_login',
			static function ( $login, $user ) {
				$c = self::cookie_lang();
				if ( $c ) {
					update_user_meta( $user->ID, self::META, $c );
				}
				self::$lang = null;
			},
			5,
			2
		);
		add_action( 'set_current_user', static function () { self::$lang = null; } );
	}

	private static function valid( $lang ): ?string {
		$lang = is_string( $lang ) ? strtolower( $lang ) : '';
		return isset( self::LANGS[ $lang ] ) ? $lang : null;
	}

	private static function cookie_lang(): ?string {
		return self::valid( $_COOKIE[ self::COOKIE ] ?? '' );
	}

	public static function default_lang(): string {
		return self::valid( PD_Settings::get( 'default_lang' ) ) ?? 'ms';
	}

	public static function lang(): string {
		if ( null !== self::$lang ) {
			return self::$lang;
		}
		$lang = null;
		$uid  = get_current_user_id();
		if ( $uid ) {
			$lang = self::valid( get_user_meta( $uid, self::META, true ) );
		}
		$lang       = $lang ?? self::cookie_lang() ?? self::default_lang();
		self::$lang = $lang;
		return $lang;
	}

	public static function is_en(): bool {
		return 'en' === self::lang();
	}

	/** Tetapkan bahasa untuk pelawat ini (kuki) dan, jika log masuk, untuk akaunnya. */
	public static function set( string $lang ): bool {
		$lang = self::valid( $lang );
		if ( ! $lang ) {
			return false;
		}
		if ( ! headers_sent() ) {
			setcookie(
				self::COOKIE,
				$lang,
				array(
					'expires'  => time() + YEAR_IN_SECONDS,
					'path'     => COOKIEPATH ?: '/',
					'domain'   => COOKIE_DOMAIN ?: '',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}
		$_COOKIE[ self::COOKIE ] = $lang;
		$uid = get_current_user_id();
		if ( $uid ) {
			update_user_meta( $uid, self::META, $lang );
		}
		self::$lang = $lang;
		return true;
	}

	/** Atribut lang HTML. */
	public static function html_lang(): string {
		return self::is_en() ? 'en' : 'ms';
	}

	/** Kod tempatan untuk Intl dalam pelayar. */
	public static function js_locale(): string {
		return self::is_en() ? 'en-GB' : 'ms-MY';
	}

	public static function dict(): array {
		if ( null === self::$dict ) {
			$file       = PD_DIR . 'languages/en.php';
			$data       = file_exists( $file ) ? include $file : array();
			self::$dict = is_array( $data ) ? $data : array();
		}
		return self::$dict;
	}

	/**
	 * Terjemah teks sumber Melayu. Pemegang tempat {nama} diganti dengan $vars.
	 * Entri kamus boleh berupa array [tunggal, jamak]; dipilih mengikut $vars['n'].
	 */
	public static function t( string $text, array $vars = array() ): string {
		if ( self::is_en() ) {
			$d = self::dict();
			if ( isset( $d[ $text ] ) ) {
				$tr = $d[ $text ];
				if ( is_array( $tr ) ) {
					$tr = ( isset( $vars['n'] ) && 1 === (int) $vars['n'] ) ? $tr[0] : ( $tr[1] ?? $tr[0] );
				}
				$text = (string) $tr;
			}
		}
		if ( $vars ) {
			$map = array();
			foreach ( $vars as $k => $v ) {
				$map[ '{' . $k . '}' ] = (string) $v;
			}
			$text = strtr( $text, $map );
		}
		return $text;
	}

	/** Teks yang dimasukkan pentadbir (cth penerangan portal): guna versi English jika ada. */
	public static function setting_text( string $ms, string $en ): string {
		if ( ! self::is_en() ) {
			return $ms;
		}
		return '' !== trim( $en ) ? $en : self::t( $ms );
	}

	/**
	 * Butiran log aktiviti disimpan dalam Bahasa Melayu (rekod lama juga). Terjemah
	 * corak yang diketahui semasa dipaparkan, mengikut jenis tindakan.
	 */
	public static function activity_details( string $action, string $details ): string {
		if ( ! self::is_en() || '' === $details ) {
			return $details;
		}
		switch ( $action ) {
			case 'rename':
				return preg_replace( '/^Dari: /u', 'From: ', $details );
			case 'login_failed':
				return preg_replace( '/^Nama pengguna: /u', 'Username: ', $details );
			case 'purge':
				if ( preg_match( '/^Kosongkan tong sampah \((\d+) item\)$/u', $details, $m ) ) {
					return self::t( 'Kosongkan tong sampah ({n} item)', array( 'n' => $m[1] ) );
				}
				return $details;
			case 'profile':
				return self::t( $details );
			case 'user_create':
				return preg_replace_callback(
					'/^Peranan: ([^;]*)/u',
					static fn( $m ) => 'Role: ' . self::t( $m[1] ),
					$details
				);
			case 'user_update':
				$words = array_map( 'trim', explode( ',', $details ) );
				return implode( ', ', array_map( array( __CLASS__, 't' ), $words ) );
		}
		return $details;
	}

	/** URL halaman semasa dengan pd_lang ditukar (untuk pautan pilihan bahasa). */
	public static function switch_url( string $lang, string $base ): string {
		return add_query_arg( 'pd_lang', $lang, $base );
	}

	/** Kamus untuk aplikasi JS (kosong jika Bahasa Melayu). */
	public static function js_dict(): array {
		return self::is_en() ? self::dict() : array();
	}
}

/** Terjemah teks sumber Melayu mengikut bahasa semasa. */
function pd__( string $text, array $vars = array() ): string {
	return PD_I18n::t( $text, $vars );
}

/** Terjemah dan paparkan dengan esc_html. */
function pd_e( string $text, array $vars = array() ): void {
	echo esc_html( PD_I18n::t( $text, $vars ) );
}
