<?php
defined( 'ABSPATH' ) || exit;

class PD_Settings {
	const OPTION = 'pd_settings';

	public static function defaults(): array {
		return array(
			'portal_title'    => 'Peti Digital',
			'portal_subtitle' => 'Akses, urus dan susun fail Jabatan IT di satu tempat.',
			'org_name'        => 'Jabatan IT',
			// Relatif kepada folder uploads WordPress. Fail lama Bit File Manager ada di sini.
			'storage_root'    => 'file-manager/PetiDigital',
			'max_upload_mb'   => 100,
			'quota_gb'        => 0,
			'trash_days'      => 30,
			'home_redirect'   => false,
			// Dwibahasa: bahasa lalai bagi pengguna yang belum memilih, dan teks versi English
			// (kosong = terjemahan automatik teks Melayu jika ada dalam kamus).
			'default_lang'       => 'ms',
			'portal_subtitle_en' => '',
			'org_name_en'        => '',
		);
	}

	public static function all(): array {
		$saved = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	public static function get( string $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	/** Had muat naik sebenar dalam bait: tetapan portal atau had PHP, mana lebih kecil. */
	public static function max_upload_bytes(): int {
		$mb = (int) self::get( 'max_upload_mb' );
		$wp = (int) wp_max_upload_size();
		$ours = $mb > 0 ? $mb * MB_IN_BYTES : $wp;
		return $wp > 0 ? min( $ours, $wp ) : $ours;
	}

	public static function quota_bytes(): int {
		return (int) round( (float) self::get( 'quota_gb' ) * GB_IN_BYTES );
	}

	/** Tetapan yang boleh diubah dari portal (storage_root sengaja tidak didedahkan). */
	public static function update( array $input ): array {
		$current = self::all();
		$text    = array( 'portal_title' => 60, 'portal_subtitle' => 200, 'org_name' => 80, 'portal_subtitle_en' => 200, 'org_name_en' => 80 );
		foreach ( $text as $key => $max ) {
			if ( array_key_exists( $key, $input ) ) {
				$val = trim( sanitize_text_field( (string) $input[ $key ] ) );
				if ( 'portal_title' === $key && '' === $val ) {
					throw new PD_Exception( 'Nama portal tidak boleh kosong.' );
				}
				$current[ $key ] = mb_substr( $val, 0, $max );
			}
		}
		if ( array_key_exists( 'max_upload_mb', $input ) ) {
			$current['max_upload_mb'] = max( 1, min( 10240, (int) $input['max_upload_mb'] ) );
		}
		if ( array_key_exists( 'quota_gb', $input ) ) {
			$current['quota_gb'] = max( 0, min( 100000, round( (float) $input['quota_gb'], 2 ) ) );
		}
		if ( array_key_exists( 'trash_days', $input ) ) {
			$current['trash_days'] = max( 0, min( 3650, (int) $input['trash_days'] ) );
		}
		if ( array_key_exists( 'default_lang', $input ) ) {
			$current['default_lang'] = isset( PD_I18n::LANGS[ (string) $input['default_lang'] ] ) ? (string) $input['default_lang'] : 'ms';
		}
		if ( array_key_exists( 'home_redirect', $input ) ) {
			$current['home_redirect'] = (bool) $input['home_redirect'];
		}
		update_option( self::OPTION, $current );
		return $current;
	}

	/** Data tetapan untuk antara muka. */
	public static function public_data(): array {
		$s = self::all();
		unset( $s['storage_root'] );
		$s['max_upload_bytes'] = self::max_upload_bytes();
		$s['php_max_upload']   = (int) wp_max_upload_size();
		$s['quota_bytes']      = self::quota_bytes();
		return $s;
	}
}
