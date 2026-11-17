<?php
defined( 'ABSPATH' ) || exit;

class PD_Activity {

	const LABELS = array(
		'login'        => 'Log masuk',
		'login_failed' => 'Log masuk gagal',
		'logout'       => 'Log keluar',
		'upload'       => 'Muat naik',
		'download'     => 'Muat turun',
		'mkdir'        => 'Cipta folder',
		'rename'       => 'Nama semula',
		'delete'       => 'Padam',
		'restore'      => 'Pulih',
		'purge'        => 'Padam kekal',
		'user_create'  => 'Tambah pengguna',
		'user_update'  => 'Kemas kini pengguna',
		'profile'      => 'Kemas kini profil',
		'settings'     => 'Tetapan',
	);

	/** Label tindakan dalam bahasa pengguna. */
	public static function labels(): array {
		return array_map( 'pd__', self::LABELS );
	}

	/** Tindakan berkaitan fail: dipaparkan kepada semua staf di halaman utama. */
	const FILE_ACTIONS = array( 'upload', 'mkdir', 'rename', 'delete', 'restore', 'purge' );

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'pd_activity';
	}

	public static function create_table(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			'CREATE TABLE ' . self::table() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_name varchar(190) NOT NULL DEFAULT '',
			action varchar(32) NOT NULL DEFAULT '',
			target text NOT NULL,
			details text NOT NULL,
			ip varchar(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY created_at (created_at)
		) $charset;"
		);
	}

	public static function ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return substr( $ip, 0, 64 );
	}

	public static function log( string $action, string $target = '', string $details = '', ?WP_User $user = null ): void {
		global $wpdb;
		$user = $user ?? wp_get_current_user();
		$wpdb->insert(
			self::table(),
			array(
				'user_id'    => $user && $user->ID ? $user->ID : 0,
				'user_name'  => $user && $user->ID ? $user->display_name : '',
				'action'     => $action,
				'target'     => mb_substr( $target, 0, 2000 ),
				'details'    => mb_substr( $details, 0, 2000 ),
				'ip'         => self::ip(),
				'created_at' => current_time( 'mysql', true ),
			)
		);
	}

	private static function row( $r, bool $with_ip ): array {
		$out = array(
			'id'           => (int) $r->id,
			'user_id'      => (int) $r->user_id,
			'user_name'    => $r->user_name,
			'action'       => $r->action,
			'action_label' => isset( self::LABELS[ $r->action ] ) ? pd__( self::LABELS[ $r->action ] ) : $r->action,
			'target'       => $r->target,
			'details'      => PD_I18n::activity_details( (string) $r->action, (string) $r->details ),
			'created_at'   => gmdate( 'c', strtotime( $r->created_at . ' UTC' ) ),
		);
		if ( $with_ip ) {
			$out['ip'] = $r->ip;
		}
		return $out;
	}

	public static function query( array $args, bool $with_ip ): array {
		global $wpdb;
		$per   = max( 1, min( 100, (int) ( $args['per_page'] ?? 25 ) ) );
		$page  = max( 1, (int) ( $args['page'] ?? 1 ) );
		$where = array( '1=1' );
		$vals  = array();
		if ( ! empty( $args['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$vals[]  = (int) $args['user_id'];
		}
		if ( ! empty( $args['action'] ) ) {
			$actions = array_values( array_intersect( (array) $args['action'], array_keys( self::LABELS ) ) );
			if ( $actions ) {
				$where[] = 'action IN (' . implode( ',', array_fill( 0, count( $actions ), '%s' ) ) . ')';
				$vals    = array_merge( $vals, $actions );
			}
		}
		if ( ! empty( $args['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
			$where[] = '(target LIKE %s OR details LIKE %s OR user_name LIKE %s)';
			array_push( $vals, $like, $like, $like );
		}
		$sql_where = implode( ' AND ', $where );
		$table     = self::table();
		$count_sql = "SELECT COUNT(*) FROM $table WHERE $sql_where";
		$total     = (int) ( $vals ? $wpdb->get_var( $wpdb->prepare( $count_sql, $vals ) ) : $wpdb->get_var( $count_sql ) );
		$list_sql  = "SELECT * FROM $table WHERE $sql_where ORDER BY id DESC LIMIT %d OFFSET %d";
		$rows      = $wpdb->get_results( $wpdb->prepare( $list_sql, array_merge( $vals, array( $per, ( $page - 1 ) * $per ) ) ) );
		return array(
			'items' => array_map( static fn( $r ) => self::row( $r, $with_ip ), (array) $rows ),
			'total' => $total,
			'page'  => $page,
			'pages' => (int) max( 1, ceil( $total / $per ) ),
		);
	}

	public static function count_since( string $action, int $seconds ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::table() . ' WHERE action = %s AND created_at >= %s',
				$action,
				gmdate( 'Y-m-d H:i:s', time() - $seconds )
			)
		);
	}
}
