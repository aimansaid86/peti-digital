<?php
defined( 'ABSPATH' ) || exit;

/**
 * Ralat yang mesejnya selamat dipaparkan kepada pengguna. Mesej ditulis dalam Bahasa
 * Melayu dan diterjemah mengikut bahasa pengguna; nilai dinamik melalui $vars ({nama}).
 */
class PD_Exception extends Exception {
	public int $status;

	public function __construct( string $message, int $status = 400, array $vars = array() ) {
		parent::__construct( PD_I18n::t( $message, $vars ) );
		$this->status = $status;
	}
}
