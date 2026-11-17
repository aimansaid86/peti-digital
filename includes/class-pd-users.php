<?php
defined( 'ABSPATH' ) || exit;

class PD_Users {

	const ROLE_STAFF = 'pd_staff';
	const ROLE_ADMIN = 'pd_admin';

	const ROLE_LABELS = array(
		'administrator' => 'Pentadbir WordPress',
		'pd_admin'      => 'Pentadbir Portal',
		'pd_staff'      => 'Staf',
	);

	/** Cubaan gagal dibenarkan bagi setiap pasangan IP + nama pengguna, dan bagi setiap IP. */
	const LOCK_MAX_USER = 5;
	const LOCK_MAX_IP   = 20;
	const LOCK_TTL      = 15 * MINUTE_IN_SECONDS;

	public static function init(): void {
		add_filter( 'authenticate', array( __CLASS__, 'throttle' ), 98, 2 );
		add_filter( 'authenticate', array( __CLASS__, 'block_disabled' ), 99 );
		// Portal tidak menggunakan XML-RPC; menutupnya juga menutup laluan teka kata laluan.
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_action( 'wp_login', array( __CLASS__, 'on_login' ), 10, 2 );
		add_action( 'wp_login_failed', array( __CLASS__, 'on_login_failed' ) );
		// Akaun dinyahaktifkan tidak dikenali sebagai log masuk melalui APA-APA kaedah (kuki,
		// kata laluan aplikasi, dsb.), kerana penapis ini dijalankan setiap kali pengguna ditentukan.
		add_filter( 'determine_current_user', array( __CLASS__, 'drop_disabled' ), 100 );
		// Portal tidak menggunakan kata laluan aplikasi; tutup supaya tiada laluan REST tersembunyi.
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
		add_filter( 'show_admin_bar', array( __CLASS__, 'admin_bar' ) );
		add_action( 'admin_init', array( __CLASS__, 'keep_staff_out_of_admin' ) );
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
	}

	public static function register_roles(): void {
		add_role( self::ROLE_STAFF, self::ROLE_LABELS['pd_staff'], array( 'read' => true, 'pd_access' => true ) );
		add_role( self::ROLE_ADMIN, self::ROLE_LABELS['pd_admin'], array( 'read' => true, 'pd_access' => true, 'pd_manage' => true ) );
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'pd_access' );
			$admin->add_cap( 'pd_manage' );
		}
	}

	public static function is_disabled( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, 'pd_disabled', true );
	}

	public static function block_disabled( $user ) {
		if ( $user instanceof WP_User && self::is_disabled( $user->ID ) ) {
			return new WP_Error( 'pd_disabled', pd__( 'Akaun ini telah dinyahaktifkan. Sila hubungi pentadbir.' ) );
		}
		return $user;
	}

	public static function drop_disabled( $user_id ) {
		return ( $user_id && self::is_disabled( (int) $user_id ) ) ? 0 : $user_id;
	}

	private static function lock_keys( string $username ): array {
		$ip = PD_Activity::ip();
		return array(
			'pd_lk_u_' . md5( $ip . '|' . strtolower( trim( $username ) ) ),
			'pd_lk_ip_' . md5( $ip ),
		);
	}

	/**
	 * Had cubaan log masuk untuk SEMUA laluan (portal, wp-login.php, REST/aplikasi), kerana
	 * ia berjalan dalam penapis authenticate dan bukan dalam borang portal sahaja.
	 */
	public static function throttle( $user, $username ) {
		$username = (string) $username;
		if ( '' === $username ) {
			return $user;
		}
		list( $ku, $ki ) = self::lock_keys( $username );
		if ( (int) get_transient( $ku ) >= self::LOCK_MAX_USER || (int) get_transient( $ki ) >= self::LOCK_MAX_IP ) {
			return new WP_Error( 'pd_locked', pd__( 'Terlalu banyak cubaan gagal. Sila cuba lagi selepas 15 minit.' ) );
		}
		return $user;
	}

	public static function on_login( $login, $user ): void {
		update_user_meta( $user->ID, 'pd_last_login', time() );
		delete_transient( self::lock_keys( (string) $login )[0] );
		if ( user_can( $user, 'pd_access' ) ) {
			PD_Activity::log( 'login', '', '', $user );
		}
	}

	public static function on_login_failed( $username ): void {
		foreach ( self::lock_keys( (string) $username ) as $key ) {
			set_transient( $key, (int) get_transient( $key ) + 1, self::LOCK_TTL );
		}
		PD_Activity::log( 'login_failed', '', 'Nama pengguna: ' . sanitize_user( (string) $username ), new WP_User( 0 ) );
	}

	/** Staf portal (bukan editor/pentadbir WP) tidak perlu nampak bar admin. */
	private static function portal_only( $user = null ): bool {
		$user = $user ?: wp_get_current_user();
		return $user && $user->ID && user_can( $user, 'pd_access' ) && ! user_can( $user, 'edit_posts' );
	}

	public static function admin_bar( $show ) {
		return self::portal_only() ? false : $show;
	}

	public static function keep_staff_out_of_admin(): void {
		if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}
		if ( self::portal_only() ) {
			wp_safe_redirect( PD_Portal::url() );
			exit;
		}
	}

	public static function login_redirect( $to, $requested, $user ) {
		if ( $user instanceof WP_User && self::portal_only( $user ) ) {
			return PD_Portal::url();
		}
		return $to;
	}

	// ------------------------------------------------------------------ data pengguna

	public static function role_of( WP_User $u ): string {
		foreach ( array( 'administrator', self::ROLE_ADMIN, self::ROLE_STAFF ) as $r ) {
			if ( in_array( $r, (array) $u->roles, true ) ) {
				return $r;
			}
		}
		return '';
	}

	public static function to_array( WP_User $u ): array {
		$role = self::role_of( $u );
		$last = (int) get_user_meta( $u->ID, 'pd_last_login', true );
		return array(
			'id'         => $u->ID,
			'username'   => $u->user_login,
			'name'       => $u->display_name,
			'email'      => $u->user_email,
			'role'       => $role,
			'role_label' => pd__( self::ROLE_LABELS[ $role ] ?? 'Tiada akses' ),
			'access'     => user_can( $u, 'pd_access' ),
			'disabled'   => self::is_disabled( $u->ID ),
			'last_login' => $last ? gmdate( 'c', $last ) : null,
			'registered' => gmdate( 'c', strtotime( $u->user_registered . ' UTC' ) ),
			'initials'   => self::initials( $u->display_name ?: $u->user_login ),
		);
	}

	public static function initials( string $name ): string {
		$words = preg_split( '/\s+/u', trim( $name ) );
		$out   = '';
		foreach ( array_slice( array_filter( $words ), 0, 2 ) as $w ) {
			$out .= mb_strtoupper( mb_substr( $w, 0, 1 ) );
		}
		return $out ?: '?';
	}

	/** Bilangan pengguna aktif dengan akses portal, dikira terus dalam pangkalan data. */
	public static function count_active(): int {
		$q = new WP_User_Query(
			array(
				'capability'  => 'pd_access',
				'meta_query'  => array( array( 'key' => 'pd_disabled', 'compare' => 'NOT EXISTS' ) ),
				'fields'      => 'ID',
				'number'      => 1,
				'count_total' => true,
			)
		);
		return (int) $q->get_total();
	}

	public static function all(): array {
		$users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC', 'number' => 1000 ) );
		return array_map( array( __CLASS__, 'to_array' ), $users );
	}

	private static function validate_role( string $role ): string {
		if ( ! in_array( $role, array( self::ROLE_STAFF, self::ROLE_ADMIN, 'none' ), true ) ) {
			throw new PD_Exception( 'Peranan tidak sah.' );
		}
		return $role;
	}

	private static function validate_password( string $pass ): void {
		if ( mb_strlen( $pass ) < 8 ) {
			throw new PD_Exception( 'Kata laluan mesti sekurang-kurangnya 8 aksara.' );
		}
	}

	public static function create( array $in ): array {
		$username = sanitize_user( (string) ( $in['username'] ?? '' ), true );
		$name     = trim( sanitize_text_field( (string) ( $in['name'] ?? '' ) ) );
		$email    = sanitize_email( (string) ( $in['email'] ?? '' ) );
		$role     = self::validate_role( (string) ( $in['role'] ?? self::ROLE_STAFF ) );
		$password = (string) ( $in['password'] ?? '' );
		$generated = false;

		if ( strlen( $username ) < 3 ) {
			throw new PD_Exception( 'Nama pengguna mesti sekurang-kurangnya 3 aksara (huruf, nombor, titik, sengkang).' );
		}
		if ( username_exists( $username ) ) {
			throw new PD_Exception( 'Nama pengguna "{name}" sudah digunakan.', 409, array( 'name' => $username ) );
		}
		if ( '' === $name ) {
			throw new PD_Exception( 'Nama penuh diperlukan.' );
		}
		if ( ! is_email( $email ) ) {
			throw new PD_Exception( 'Alamat emel tidak sah.' );
		}
		if ( email_exists( $email ) ) {
			throw new PD_Exception( 'Emel ini sudah digunakan oleh pengguna lain.', 409 );
		}
		if ( '' === $password ) {
			$password  = wp_generate_password( 12, false );
			$generated = true;
		}
		self::validate_password( $password );

		$id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_pass'    => $password,
				'user_email'   => $email,
				'display_name' => $name,
				'first_name'   => $name,
				'nickname'     => $name,
				'role'         => 'none' === $role ? '' : $role,
			)
		);
		if ( is_wp_error( $id ) ) {
			throw new PD_Exception( $id->get_error_message() );
		}

		$folder = null;
		if ( ! empty( $in['create_folder'] ) ) {
			try {
				$folder = PD_Storage::mkdir( '', $name )['path'];
			} catch ( PD_Exception $e ) {
				// Folder dengan nama ini mungkin sudah wujud: itu bukan ralat untuk pengguna baharu.
				$folder = null;
			}
		}
		$user = get_user_by( 'id', $id );
		PD_Activity::log( 'user_create', $username, 'Peranan: ' . ( self::ROLE_LABELS[ $role ] ?? 'Tiada akses' ) . ( $folder ? '; folder: ' . $folder : '' ) );
		return array(
			'user'     => self::to_array( $user ),
			'password' => $generated ? $password : null,
			'folder'   => $folder,
		);
	}

	public static function update( int $id, array $in ): array {
		$user = get_user_by( 'id', $id );
		if ( ! $user ) {
			throw new PD_Exception( 'Pengguna tidak dijumpai.', 404 );
		}
		$me = get_current_user_id();
		// Pentadbir portal tidak boleh mengubah akaun pentadbir WordPress (kata laluan, emel,
		// status): itu akan membolehkan peranan portal mengambil alih seluruh laman.
		if ( $id !== $me && user_can( $user, 'manage_options' ) && ! current_user_can( 'manage_options' ) ) {
			throw new PD_Exception( 'Hanya pentadbir WordPress boleh mengubah akaun pentadbir WordPress.', 403 );
		}
		$data    = array( 'ID' => $id );
		$changes = array();

		if ( isset( $in['name'] ) ) {
			$name = trim( sanitize_text_field( (string) $in['name'] ) );
			if ( '' === $name ) {
				throw new PD_Exception( 'Nama penuh diperlukan.' );
			}
			$data['display_name'] = $name;
			$changes[]            = 'nama';
		}
		if ( isset( $in['email'] ) ) {
			$email = sanitize_email( (string) $in['email'] );
			if ( ! is_email( $email ) ) {
				throw new PD_Exception( 'Alamat emel tidak sah.' );
			}
			$owner = email_exists( $email );
			if ( $owner && (int) $owner !== $id ) {
				throw new PD_Exception( 'Emel ini sudah digunakan oleh pengguna lain.', 409 );
			}
			$data['user_email'] = $email;
			$changes[]          = 'emel';
		}
		if ( ! empty( $in['password'] ) ) {
			self::validate_password( (string) $in['password'] );
			$data['user_pass'] = (string) $in['password'];
			$changes[]         = 'kata laluan';
		}
		// Sahkan SEMUA medan sebelum apa-apa disimpan, supaya permintaan yang ditolak
		// tidak meninggalkan perubahan separuh jalan (cth emel sudah ditukar tetapi ralat dipaparkan).
		$is_wp_admin = in_array( 'administrator', (array) $user->roles, true );
		$role        = null;
		if ( isset( $in['role'] ) && ! $is_wp_admin ) {
			$role = self::validate_role( (string) $in['role'] );
			if ( $id === $me && self::ROLE_ADMIN !== $role ) {
				throw new PD_Exception( 'Anda tidak boleh menurunkan peranan akaun anda sendiri.' );
			}
		}
		$disabled = isset( $in['disabled'] ) ? (bool) $in['disabled'] : null;
		if ( $disabled && $id === $me ) {
			throw new PD_Exception( 'Anda tidak boleh menyahaktifkan akaun anda sendiri.' );
		}

		if ( count( $data ) > 1 ) {
			$res = wp_update_user( $data );
			if ( is_wp_error( $res ) ) {
				throw new PD_Exception( $res->get_error_message() );
			}
		}

		if ( null !== $role ) {
			$user = get_user_by( 'id', $id );
			$user->remove_role( self::ROLE_STAFF );
			$user->remove_role( self::ROLE_ADMIN );
			if ( 'none' !== $role ) {
				$user->add_role( $role );
			}
			$changes[] = 'peranan';
		}
		if ( null !== $disabled ) {
			if ( $disabled ) {
				update_user_meta( $id, 'pd_disabled', 1 );
				WP_Session_Tokens::get_instance( $id )->destroy_all();
			} else {
				delete_user_meta( $id, 'pd_disabled' );
			}
			$changes[] = $disabled ? 'dinyahaktifkan' : 'diaktifkan';
		}
		clean_user_cache( $id );
		$user = get_user_by( 'id', $id );
		if ( $changes ) {
			PD_Activity::log( 'user_update', $user->user_login, implode( ', ', array_unique( $changes ) ) );
		}
		return self::to_array( $user );
	}

	/** Pengguna mengemas kini profil sendiri. Tukar kata laluan perlu kata laluan semasa. */
	public static function update_me( array $in ): array {
		$user = wp_get_current_user();
		$data = array( 'ID' => $user->ID );
		if ( isset( $in['name'] ) ) {
			$name = trim( sanitize_text_field( (string) $in['name'] ) );
			if ( '' === $name ) {
				throw new PD_Exception( 'Nama penuh diperlukan.' );
			}
			$data['display_name'] = $name;
		}
		$email_changed = false;
		if ( isset( $in['email'] ) ) {
			$email = sanitize_email( (string) $in['email'] );
			if ( ! is_email( $email ) ) {
				throw new PD_Exception( 'Alamat emel tidak sah.' );
			}
			$owner = email_exists( $email );
			if ( $owner && (int) $owner !== $user->ID ) {
				throw new PD_Exception( 'Emel ini sudah digunakan oleh pengguna lain.', 409 );
			}
			$email_changed      = 0 !== strcasecmp( $email, $user->user_email );
			$data['user_email'] = $email;
		}
		$changed_pass = ! empty( $in['new_password'] );
		// Emel menentukan siapa boleh set semula kata laluan, jadi menukarnya perlu
		// pengesahan kata laluan semasa, sama seperti menukar kata laluan.
		if ( $email_changed || $changed_pass ) {
			if ( ! wp_check_password( (string) ( $in['current_password'] ?? '' ), $user->user_pass, $user->ID ) ) {
				throw new PD_Exception( 'Kata laluan semasa tidak betul. Ia diperlukan untuk menukar emel atau kata laluan.', 403 );
			}
		}
		if ( $changed_pass ) {
			self::validate_password( (string) $in['new_password'] );
			$data['user_pass'] = (string) $in['new_password'];
		}
		$res = wp_update_user( $data );
		if ( is_wp_error( $res ) ) {
			throw new PD_Exception( $res->get_error_message() );
		}
		// Tiada wp_set_auth_cookie di sini: wp_update_user sudah menetapkan semula kuki
		// pengguna semasa apabila kata laluan ditukar, dan mengekalkan pilihan "Ingat saya".
		PD_Activity::log( 'profile', '', $changed_pass ? 'Termasuk kata laluan' : '' );
		clean_user_cache( $user->ID );
		return self::to_array( get_user_by( 'id', $user->ID ) );
	}
}
