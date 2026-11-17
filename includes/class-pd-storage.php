<?php
defined( 'ABSPATH' ) || exit;

/**
 * Semua akses sistem fail melalui kelas ini. Laluan dari pelanggan sentiasa relatif
 * kepada akar storan, dan disahkan supaya tidak boleh keluar daripadanya.
 */
class PD_Storage {

	/** Sambungan yang tidak boleh dimuat naik atau dinamakan semula kepadanya. */
	const BLOCKED_EXT = array(
		'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar', 'inc',
		'cgi', 'pl', 'py', 'rb', 'asp', 'aspx', 'ashx', 'jsp', 'jspx', 'shtml', 'htaccess',
		'htpasswd', 'user.ini', 'sh', 'bash', 'bat', 'cmd', 'com', 'exe', 'msi', 'dll', 'scr',
		'vbs', 'ps1', 'hta', 'jar',
	);

	/** Jenis yang boleh dipaparkan terus dalam pelayar. */
	const INLINE_MIME = array(
		'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml', 'image/avif',
		'application/pdf', 'video/mp4', 'video/webm', 'video/ogg', 'audio/mpeg', 'audio/wav',
		'audio/ogg', 'audio/mp4', 'text/plain',
	);

	const EXTRA_MIME = array(
		'jfif' => 'image/jpeg',
		'jfi'  => 'image/jpeg',
		'jpe'  => 'image/jpeg',
		'webp' => 'image/webp',
		'avif' => 'image/avif',
		'svg'  => 'image/svg+xml',
		'heic' => 'image/heic',
		'md'   => 'text/plain',
		'log'  => 'text/plain',
		'csv'  => 'text/plain',
		'json' => 'text/plain',
		'xml'  => 'text/plain',
		'ini'  => 'text/plain',
		'm4a'  => 'audio/mp4',
		'mkv'  => 'video/x-matroska',
		'rar'  => 'application/vnd.rar',
		'7z'   => 'application/x-7z-compressed',
	);

	private static ?string $root = null;

	// ------------------------------------------------------------------ laluan

	public static function uploads_base(): string {
		$up = wp_upload_dir( null, false );
		return untrailingslashit( wp_normalize_path( $up['basedir'] ) );
	}

	public static function root(): string {
		if ( null === self::$root ) {
			$rel  = trim( (string) PD_Settings::get( 'storage_root' ), '/\\' );
			$root = self::uploads_base() . '/' . $rel;
			// Semakan penuh kandungan .htaccess dibuat semasa pasang/naik taraf (PD_Install).
			// Di sini cukup pastikan folder dan fail pelindung wujud, tanpa membaca fail setiap permintaan.
			if ( ! is_dir( $root ) || ! file_exists( $root . '/.htaccess' ) ) {
				self::ensure_protected_dir( $root );
			}
			$real       = realpath( $root );
			self::$root = untrailingslashit( wp_normalize_path( false !== $real ? $real : $root ) );
		}
		return self::$root;
	}

	/** Folder dalaman plugin (tong sampah, cache lakaran kecil), di luar akar fail. */
	public static function internal_dir( string $name ): string {
		$base = self::uploads_base() . '/peti-digital-data';
		$dir  = $base . '/' . $name;
		if ( ! is_dir( $dir ) || ! file_exists( $base . '/.htaccess' ) ) {
			self::ensure_protected_dir( $base );
			wp_mkdir_p( $dir );
		}
		return $dir;
	}

	/**
	 * Adakah fail dalam storan boleh dibuka terus melalui URL tanpa log masuk?
	 * Perlindungan bergantung pada .htaccess, yang diabaikan oleh nginx/IIS atau Apache
	 * dengan AllowOverride None. Semakan sebenar melalui HTTP, disimpan 12 jam.
	 *
	 * @return string 'protected' | 'exposed' | 'unknown' (pelayan tidak dapat menghubungi dirinya sendiri)
	 */
	public static function protection_status( bool $refresh = false ): string {
		$cached = get_transient( 'pd_protection' );
		if ( ! $refresh && is_string( $cached ) ) {
			return $cached;
		}
		$root   = self::root();
		$token  = wp_generate_password( 20, false, false );
		// Tanpa titik di depan: sesetengah pelayan menyekat fail titik sahaja, yang akan
		// memberi keputusan "dilindungi" yang palsu.
		$canary = $root . '/pd-semakan-' . $token . '.txt';
		$status = 'unknown';
		if ( false !== @file_put_contents( $canary, $token ) ) {
			$url = content_url( substr( $canary, strlen( wp_normalize_path( WP_CONTENT_DIR ) ) ) );
			$res = wp_remote_get( $url, array( 'timeout' => 5, 'redirection' => 0, 'sslverify' => false ) );
			@unlink( $canary );
			if ( ! is_wp_error( $res ) ) {
				$exposed = 200 === (int) wp_remote_retrieve_response_code( $res ) && str_contains( (string) wp_remote_retrieve_body( $res ), $token );
				$status  = $exposed ? 'exposed' : 'protected';
			}
		}
		set_transient( 'pd_protection', $status, 12 * HOUR_IN_SECONDS );
		return $status;
	}

	/** Cipta folder dan halang capaian HTTP terus: fail hanya boleh dicapai melalui portal. */
	public static function ensure_protected_dir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$marker = '# Peti Digital: fail hanya boleh dicapai melalui portal (perlu log masuk).';
		$ht     = $dir . '/.htaccess';
		$old    = file_exists( $ht ) ? (string) @file_get_contents( $ht ) : '';
		// .htaccess sedia ada (cth dari plugin lain) mungkin hanya menyekat *.php. Tambah
		// peraturan tolak-semua di atasnya dan kekalkan kandungan lama.
		if ( ! str_contains( $old, $marker ) ) {
			@file_put_contents(
				$ht,
				$marker . "\n" .
				"<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n" .
				"<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n" .
				( '' !== trim( $old ) ? "\n" . $old : '' )
			);
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			@file_put_contents( $dir . '/index.php', "<?php\n// Senyap.\n" );
		}
	}

	/** Normalkan laluan relatif dan tolak apa-apa yang cuba keluar dari akar. */
	public static function clean_rel( $rel ): string {
		$rel = str_replace( '\\', '/', (string) $rel );
		if ( str_contains( $rel, "\0" ) ) {
			throw new PD_Exception( 'Laluan tidak sah.' );
		}
		$parts = array();
		foreach ( explode( '/', $rel ) as $p ) {
			if ( '' === $p || '.' === $p ) {
				continue;
			}
			if ( '..' === $p || str_starts_with( $p, '.' ) || str_contains( $p, ':' ) ) {
				throw new PD_Exception( 'Laluan tidak sah.' );
			}
			$parts[] = $p;
		}
		return implode( '/', $parts );
	}

	public static function abs( $rel ): string {
		$rel  = self::clean_rel( $rel );
		$path = self::root() . ( '' === $rel ? '' : '/' . $rel );
		if ( file_exists( $path ) ) {
			$real = realpath( $path );
			if ( false === $real || ! self::is_within( wp_normalize_path( $real ), self::root() ) ) {
				throw new PD_Exception( 'Laluan tidak sah.' );
			}
		}
		return $path;
	}

	public static function is_within( string $path, string $base ): bool {
		$path = untrailingslashit( wp_normalize_path( $path ) );
		$base = untrailingslashit( wp_normalize_path( $base ) );
		if ( 'Windows' === PHP_OS_FAMILY ) {
			$path = strtolower( $path );
			$base = strtolower( $base );
		}
		return $path === $base || str_starts_with( $path, $base . '/' );
	}

	public static function rel_of( string $abs ): string {
		$abs  = wp_normalize_path( $abs );
		$root = self::root();
		return ltrim( substr( $abs, strlen( $root ) ), '/' );
	}

	public static function existing( $rel, ?string $type = null ): string {
		$path = self::abs( $rel );
		if ( ! file_exists( $path ) ) {
			throw new PD_Exception( 'Fail atau folder tidak dijumpai.', 404 );
		}
		if ( 'dir' === $type && ! is_dir( $path ) ) {
			throw new PD_Exception( 'Bukan folder.' );
		}
		if ( 'file' === $type && ! is_file( $path ) ) {
			throw new PD_Exception( 'Bukan fail.' );
		}
		return $path;
	}

	// ------------------------------------------------------------------ nama & jenis

	public static function sanitize_name( $name, bool $is_file = true ): string {
		$name = (string) $name;
		$name = preg_replace( '/[\x00-\x1F\x7F\\\\\/:*?"<>|]/u', '', $name );
		$name = trim( preg_replace( '/\s+/u', ' ', $name ) );
		$name = rtrim( $name, '. ' );
		$name = ltrim( $name, '.' );
		if ( '' === $name ) {
			throw new PD_Exception( 'Nama tidak sah.' );
		}
		if ( mb_strlen( $name ) > 150 ) {
			throw new PD_Exception( 'Nama terlalu panjang (maksimum 150 aksara).' );
		}
		$stem = strtoupper( strtok( $name, '.' ) );
		if ( preg_match( '/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/', $stem ) ) {
			throw new PD_Exception( 'Nama ini dikhaskan oleh sistem. Sila pilih nama lain.' );
		}
		if ( $is_file ) {
			self::assert_allowed_ext( $name );
		}
		return $name;
	}

	public static function assert_allowed_ext( string $name ): void {
		$lower = strtolower( $name );
		$segs  = explode( '.', $lower );
		array_shift( $segs );
		foreach ( $segs as $seg ) {
			if ( in_array( $seg, self::BLOCKED_EXT, true ) ) {
				throw new PD_Exception( 'Jenis fail .{ext} tidak dibenarkan atas sebab keselamatan.', 400, array( 'ext' => $seg ) );
			}
		}
		if ( str_ends_with( $lower, '.user.ini' ) || '.user.ini' === $lower ) {
			throw new PD_Exception( 'Jenis fail ini tidak dibenarkan.' );
		}
		// Jika fail boleh dibuka terus tanpa log masuk (pelayan mengabaikan .htaccess), fail
		// yang boleh menjalankan skrip dalam pelayar menjadi risiko XSS: tolak fail itu.
		$active = array( 'html', 'htm', 'xhtml', 'xht', 'svg', 'svgz', 'js', 'mjs', 'xml', 'xsl', 'swf' );
		if ( in_array( self::ext( $name ), $active, true ) && 'exposed' === self::protection_status() ) {
			throw new PD_Exception( 'Fail .{ext} disekat kerana pelayan ini belum menghalang capaian terus ke folder storan. Sila hubungi pentadbir.', 400, array( 'ext' => self::ext( $name ) ) );
		}
	}

	public static function ext( string $name ): string {
		$pos = strrpos( $name, '.' );
		return ( false === $pos || 0 === $pos ) ? '' : strtolower( substr( $name, $pos + 1 ) );
	}

	public static function mime( string $name ): string {
		$ext = self::ext( $name );
		if ( '' === $ext ) {
			return 'application/octet-stream';
		}
		if ( isset( self::EXTRA_MIME[ $ext ] ) ) {
			return self::EXTRA_MIME[ $ext ];
		}
		foreach ( wp_get_mime_types() as $exts => $mime ) {
			if ( in_array( $ext, explode( '|', $exts ), true ) ) {
				return $mime;
			}
		}
		return 'application/octet-stream';
	}

	public static function kind( string $name ): string {
		$ext = self::ext( $name );
		$map = array(
			'image'   => array( 'jpg', 'jpeg', 'jpe', 'jfif', 'jfi', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif', 'heic', 'tif', 'tiff', 'ico' ),
			'pdf'     => array( 'pdf' ),
			'doc'     => array( 'doc', 'docx', 'odt', 'rtf', 'pages' ),
			'sheet'   => array( 'xls', 'xlsx', 'ods', 'csv', 'numbers' ),
			'slide'   => array( 'ppt', 'pptx', 'odp', 'key' ),
			'video'   => array( 'mp4', 'webm', 'mov', 'avi', 'mkv', 'wmv', 'm4v', 'ogv' ),
			'audio'   => array( 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'wma' ),
			'archive' => array( 'zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2' ),
			'text'    => array( 'txt', 'md', 'log', 'json', 'xml', 'ini', 'yml', 'yaml' ),
			'code'    => array( 'html', 'htm', 'css', 'js', 'ts', 'sql' ),
		);
		foreach ( $map as $kind => $exts ) {
			if ( in_array( $ext, $exts, true ) ) {
				return $kind;
			}
		}
		return 'other';
	}

	public static function is_inline( string $name ): bool {
		return in_array( self::mime( $name ), self::INLINE_MIME, true );
	}

	/** Calon nama: "nama.ext", "nama (2).ext", "nama (3).ext"... */
	private static function candidate( string $dir, string $name, int $i ): string {
		if ( 1 === $i ) {
			return $dir . '/' . $name;
		}
		$ext  = self::ext( $name );
		$stem = '' === $ext ? $name : substr( $name, 0, -( strlen( $ext ) + 1 ) );
		return $dir . '/' . $stem . ' (' . $i . ')' . ( '' === $ext ? '' : '.' . $ext );
	}

	/**
	 * Tempah nama unik secara atomik: fail kosong dicipta dengan mod 'x' (gagal jika wujud),
	 * atau folder dengan mkdir. Dua muat naik serentak dengan nama sama tidak akan
	 * menulis ganti antara satu sama lain. Pemanggil kemudian menulis ganti tempahan itu.
	 */
	public static function reserve_path( string $dir, string $name, bool $is_dir = false ): string {
		for ( $i = 1; $i < 10000; $i++ ) {
			$candidate = self::candidate( $dir, $name, $i );
			if ( $is_dir ) {
				if ( @mkdir( $candidate, 0755 ) ) {
					return $candidate;
				}
			} else {
				$fh = @fopen( $candidate, 'x' );
				if ( $fh ) {
					fclose( $fh );
					return $candidate;
				}
			}
			if ( ! file_exists( $candidate ) ) {
				throw new PD_Exception( 'Server tidak dapat menulis ke folder ini. Semak kebenaran folder.', 500 );
			}
		}
		throw new PD_Exception( 'Terlalu banyak fail dengan nama yang sama.', 409 );
	}

	// ------------------------------------------------------------------ baca

	public static function item( string $abs ): array {
		$name  = basename( $abs );
		$isdir = is_dir( $abs );
		$item  = array(
			'name'     => $name,
			'path'     => self::rel_of( $abs ),
			'type'     => $isdir ? 'dir' : 'file',
			'modified' => gmdate( 'c', (int) @filemtime( $abs ) ),
		);
		if ( $isdir ) {
			$count         = 0;
			$h             = @opendir( $abs );
			while ( $h && false !== ( $e = readdir( $h ) ) ) {
				if ( '.' !== $e[0] && 'index.php' !== $e ) {
					++$count;
				}
			}
			$h && closedir( $h );
			$item['children'] = $count;
		} else {
			$item['size']    = (int) @filesize( $abs );
			$item['ext']     = self::ext( $name );
			$item['kind']    = self::kind( $name );
			$item['mime']    = self::mime( $name );
			$item['preview'] = self::is_inline( $name );
		}
		return $item;
	}

	/** Item tersembunyi: fail titik, dan index.php pelindung di akar. */
	private static function hidden( string $name ): bool {
		return '' === $name || '.' === $name[0] || 'index.php' === strtolower( $name );
	}

	public static function list_dir( $rel ): array {
		$dir   = self::existing( $rel, 'dir' );
		$items = array();
		foreach ( (array) @scandir( $dir ) as $name ) {
			if ( self::hidden( (string) $name ) ) {
				continue;
			}
			$items[] = self::item( $dir . '/' . $name );
		}
		usort(
			$items,
			static function ( $a, $b ) {
				if ( $a['type'] !== $b['type'] ) {
					return 'dir' === $a['type'] ? -1 : 1;
				}
				return strnatcasecmp( $a['name'], $b['name'] );
			}
		);
		return array(
			'path'  => self::rel_of( $dir ),
			'items' => $items,
		);
	}

	/**
	 * Iterasi semua fail dan folder di bawah satu folder (tidak termasuk tersembunyi).
	 * Jika $cb memulangkan false, iterasi berhenti serta-merta.
	 *
	 * @return bool false jika dihentikan oleh $cb.
	 */
	public static function walk( string $dir, callable $cb ): bool {
		$h = @opendir( $dir );
		if ( ! $h ) {
			return true;
		}
		$go = true;
		while ( $go && false !== ( $name = readdir( $h ) ) ) {
			if ( self::hidden( $name ) ) {
				continue;
			}
			$path = $dir . '/' . $name;
			if ( is_link( $path ) ) {
				continue;
			}
			if ( is_dir( $path ) ) {
				$go = false !== $cb( $path, true ) && self::walk( $path, $cb );
			} else {
				$go = false !== $cb( $path, false );
			}
		}
		closedir( $h );
		return $go;
	}

	public static function dir_size( string $dir ): int {
		$size = 0;
		self::walk(
			$dir,
			static function ( $p, $isdir ) use ( &$size ) {
				if ( ! $isdir ) {
					$size += (int) @filesize( $p );
				}
			}
		);
		return $size;
	}

	public static function search( string $q, int $limit = 200 ): array {
		$q = trim( $q );
		if ( mb_strlen( $q ) < 2 ) {
			throw new PD_Exception( 'Masukkan sekurang-kurangnya 2 aksara untuk carian.' );
		}
		$results = array();
		self::walk(
			self::root(),
			static function ( $p, $isdir ) use ( $q, &$results, $limit ) {
				if ( false !== mb_stripos( basename( $p ), $q ) ) {
					$results[] = self::item( $p );
				}
				return count( $results ) < $limit; // berhenti selepas cukup hasil
			}
		);
		return $results;
	}

	/** Statistik untuk halaman utama; disimpan sementara kerana memerlukan imbasan penuh. */
	public static function stats(): array {
		$cached = get_transient( 'pd_stats' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$root  = self::root();
		$stats = array(
			'files'   => 0,
			'folders' => 0,
			'size'    => 0,
			'kinds'   => array(),
			'top'     => array(),
			'recent'  => array(),
		);
		$recent = array();
		$top    = array();
		self::walk(
			$root,
			static function ( $p, $isdir ) use ( &$stats, &$recent, &$top, $root ) {
				$rel   = ltrim( substr( wp_normalize_path( $p ), strlen( $root ) ), '/' );
				$first = strtok( $rel, '/' );
				if ( $isdir ) {
					++$stats['folders'];
					if ( $first === $rel ) {
						$top[ $first ] = $top[ $first ] ?? array( 'name' => $first, 'path' => $first, 'files' => 0, 'size' => 0 );
					}
					return;
				}
				$size = (int) @filesize( $p );
				++$stats['files'];
				$stats['size'] += $size;
				$kind           = self::kind( $p );
				$stats['kinds'][ $kind ] = $stats['kinds'][ $kind ] ?? array( 'count' => 0, 'size' => 0 );
				++$stats['kinds'][ $kind ]['count'];
				$stats['kinds'][ $kind ]['size'] += $size;
				if ( $first !== $rel ) {
					$top[ $first ] = $top[ $first ] ?? array( 'name' => $first, 'path' => $first, 'files' => 0, 'size' => 0 );
					++$top[ $first ]['files'];
					$top[ $first ]['size'] += $size;
				}
				$recent[] = array( (int) @filemtime( $p ), $p );
			}
		);
		usort( $recent, static fn( $a, $b ) => $b[0] <=> $a[0] );
		foreach ( array_slice( $recent, 0, 8 ) as $r ) {
			$stats['recent'][] = self::item( $r[1] );
		}
		$top = array_values( $top );
		usort( $top, static fn( $a, $b ) => strnatcasecmp( $a['name'], $b['name'] ) );
		$stats['top'] = $top;
		set_transient( 'pd_stats', $stats, 10 * MINUTE_IN_SECONDS );
		// Imbasan penuh baru dibuat: kemas kini kiraan kuota sekali, tanpa imbasan kedua.
		set_transient( 'pd_used_bytes', $stats['size'], HOUR_IN_SECONDS );
		return $stats;
	}

	/**
	 * @param bool $keep_used Kekalkan kiraan ruang digunakan (pemanggil sudah mengemas kininya).
	 */
	public static function flush_stats( bool $keep_used = false ): void {
		delete_transient( 'pd_stats' );
		if ( ! $keep_used ) {
			delete_transient( 'pd_used_bytes' );
		}
	}

	/**
	 * Ruang digunakan untuk semakan kuota. Disimpan berasingan daripada statistik penuh
	 * dan ditambah setiap kali muat naik, supaya muat naik berkelompok tidak mengimbas
	 * keseluruhan storan bagi setiap fail.
	 */
	public static function used_bytes(): int {
		$used = get_transient( 'pd_used_bytes' );
		if ( false === $used ) {
			$used = self::dir_size( self::root() );
			set_transient( 'pd_used_bytes', $used, HOUR_IN_SECONDS );
		}
		return (int) $used;
	}

	private static function add_used_bytes( int $bytes ): void {
		$used = get_transient( 'pd_used_bytes' );
		if ( false !== $used ) {
			set_transient( 'pd_used_bytes', (int) $used + $bytes, HOUR_IN_SECONDS );
		}
	}

	// ------------------------------------------------------------------ tulis

	public static function mkdir( $parent_rel, $name ): array {
		$parent = self::existing( $parent_rel, 'dir' );
		$name   = self::sanitize_name( $name, false );
		$path   = $parent . '/' . $name;
		if ( file_exists( $path ) ) {
			throw new PD_Exception( 'Folder "{name}" sudah wujud di sini.', 409, array( 'name' => $name ) );
		}
		if ( ! @mkdir( $path, 0755 ) ) {
			throw new PD_Exception( 'Folder tidak dapat dicipta. Semak kebenaran folder di server.', 500 );
		}
		self::flush_stats();
		return self::item( $path );
	}

	public static function rename( $rel, $new_name ): array {
		$rel  = self::clean_rel( $rel );
		if ( '' === $rel ) {
			throw new PD_Exception( 'Folder utama tidak boleh dinamakan semula.' );
		}
		$path     = self::existing( $rel );
		$is_file  = is_file( $path );
		$new_name = self::sanitize_name( $new_name, $is_file );
		$target   = dirname( $path ) . '/' . $new_name;
		if ( basename( $path ) === $new_name ) {
			return self::item( $path );
		}
		// Pada sistem fail tidak peka huruf (Windows), "Laporan.pdf" ialah fail yang sama
		// dengan "laporan.pdf", jadi tukar huruf sahaja dibenarkan. Pada Linux ia mungkin fail
		// lain: bandingkan realpath supaya fail lain tidak ditulis ganti.
		$same_file = file_exists( $target ) && realpath( $target ) === realpath( $path );
		if ( file_exists( $target ) && ! $same_file ) {
			throw new PD_Exception( '"{name}" sudah wujud di folder ini.', 409, array( 'name' => $new_name ) );
		}
		if ( ! @rename( $path, $target ) ) {
			throw new PD_Exception( 'Tidak dapat menamakan semula. Fail mungkin sedang digunakan.', 500 );
		}
		self::flush_stats();
		return self::item( $target );
	}

	public static function check_quota( int $incoming ): void {
		$quota = PD_Settings::quota_bytes();
		if ( $quota <= 0 ) {
			return;
		}
		if ( self::used_bytes() + $incoming > $quota ) {
			throw new PD_Exception( 'Ruang storan tidak mencukupi. Kuota {quota} telah atau akan dilebihi.', 507, array( 'quota' => size_format( $quota ) ) );
		}
	}

	/** Simpan satu fail dari $_FILES. Pulangkan item yang disimpan. */
	public static function save_upload( array $file, $dir_rel ): array {
		$dir = self::existing( $dir_rel, 'dir' );
		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			$msg = array(
				UPLOAD_ERR_INI_SIZE   => 'Fail melebihi had muat naik server.',
				UPLOAD_ERR_FORM_SIZE  => 'Fail melebihi had muat naik.',
				UPLOAD_ERR_PARTIAL    => 'Muat naik terputus. Sila cuba lagi.',
				UPLOAD_ERR_NO_FILE    => 'Tiada fail dipilih.',
				UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara server tiada.',
				UPLOAD_ERR_CANT_WRITE => 'Server tidak dapat menulis fail.',
			);
			throw new PD_Exception( $msg[ (int) $file['error'] ] ?? 'Muat naik gagal.' );
		}
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			throw new PD_Exception( 'Muat naik tidak sah.' );
		}
		$size = (int) $file['size'];
		$max  = PD_Settings::max_upload_bytes();
		if ( $size > $max ) {
			throw new PD_Exception( 'Fail melebihi had {max}.', 413, array( 'max' => size_format( $max ) ) );
		}
		$name = self::sanitize_name( wp_unslash( $file['name'] ), true );

		// Semakan kuota + simpan + kemas kini kiraan mesti atomik, jika tidak muat naik
		// serentak semuanya lulus semakan dan kuota terlebih.
		$locked = self::lock( 'pd_quota' );
		try {
			self::check_quota( $size );
			$target = self::reserve_path( $dir, $name );
			if ( ! @move_uploaded_file( $file['tmp_name'], $target ) ) {
				@unlink( $target );
				throw new PD_Exception( 'Fail tidak dapat disimpan di server.', 500 );
			}
			@chmod( $target, 0644 );
			self::add_used_bytes( $size );
		} finally {
			if ( $locked ) {
				self::unlock( 'pd_quota' );
			}
		}
		self::flush_stats( true );
		return self::item( $target );
	}

	/** Kunci bernama MySQL (merentasi semua proses PHP). */
	private static function lock( string $name ): bool {
		global $wpdb;
		return '1' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 15)', $wpdb->prefix . $name ) );
	}

	private static function unlock( string $name ): void {
		global $wpdb;
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $wpdb->prefix . $name ) );
	}

	public static function rrmdir( string $path ): bool {
		if ( is_link( $path ) || is_file( $path ) ) {
			return @unlink( $path );
		}
		if ( ! is_dir( $path ) ) {
			return true;
		}
		foreach ( (array) @scandir( $path ) as $e ) {
			if ( '.' === $e || '..' === $e ) {
				continue;
			}
			self::rrmdir( $path . '/' . $e );
		}
		return @rmdir( $path );
	}

	/** Bina fail zip sementara daripada beberapa laluan. Pemanggil perlu padam fail itu. */
	public static function build_zip( array $rels ): string {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new PD_Exception( 'Sambungan PHP zip tidak dipasang di server.', 500 );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$tmp = wp_tempnam( 'peti-digital.zip' );
		$zip = new ZipArchive();
		if ( true !== $zip->open( $tmp, ZipArchive::OVERWRITE ) ) {
			throw new PD_Exception( 'Tidak dapat mencipta fail zip.', 500 );
		}
		$limit = 4 * GB_IN_BYTES;
		$total = 0;
		try {
			// Semak semua laluan dan jumlah saiz DAHULU, sebelum apa-apa ditulis ke zip,
			// supaya pilihan fail individu juga tertakluk kepada had.
			$sources = array();
			foreach ( $rels as $rel ) {
				$abs  = self::existing( $rel );
				$base = basename( $abs );
				if ( is_file( $abs ) ) {
					$sources[] = array( $abs, $base, false );
					$total    += (int) filesize( $abs );
				} else {
					$sources[] = array( $abs, $base, true );
					$total    += self::dir_size( $abs );
				}
				if ( $total > $limit ) {
					throw new PD_Exception( 'Terlalu besar untuk dimuat turun sebagai zip (had 4 GB). Pilih item yang lebih sedikit.', 413 );
				}
			}
			foreach ( $sources as list( $abs, $base, $isdir ) ) {
				if ( ! $isdir ) {
					$zip->addFile( $abs, $base );
					continue;
				}
				$zip->addEmptyDir( $base );
				$prefix = strlen( wp_normalize_path( $abs ) );
				self::walk(
					$abs,
					static function ( $p, $is_dir ) use ( $zip, $base, $prefix ) {
						$local = $base . substr( wp_normalize_path( $p ), $prefix );
						if ( $is_dir ) {
							$zip->addEmptyDir( $local );
						} else {
							$zip->addFile( $p, $local );
						}
					}
				);
			}
			if ( 0 === $zip->numFiles ) {
				$zip->addFromString( pd__( 'kosong.txt' ), pd__( 'Folder ini kosong.' ) );
			}
			$zip->close();
		} catch ( Throwable $e ) {
			// Jangan tinggalkan fail sementara apabila ralat berlaku di tengah jalan.
			@$zip->close();
			@unlink( $tmp );
			throw $e;
		}
		return $tmp;
	}
}
