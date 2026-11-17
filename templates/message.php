<?php
/**
 * Halaman mesej ringkas (ralat / tiada akses).
 *
 * @var array  $brand
 * @var string $title
 * @var string $message
 * @var bool   $show_logout
 */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html lang="<?php echo esc_attr( PD_I18n::html_lang() ); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $title . ' · ' . $brand['title'] ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( self::asset( 'portal.css' ) ); ?>">
</head>
<body class="pd-auth">
<main class="pd-message">
	<div class="pd-logo"><?php include PD_DIR . 'templates/logo.php'; ?></div>
	<h1><?php echo esc_html( $title ); ?></h1>
	<p><?php echo esc_html( $message ); ?></p>
	<div class="pd-message__actions">
		<?php if ( $show_logout && is_user_logged_in() ) : ?>
			<a class="pd-btn" href="<?php echo esc_url( wp_nonce_url( self::url( 'logout' ), 'pd_logout' ) ); ?>"><?php pd_e( 'Log keluar' ); ?></a>
		<?php endif; ?>
		<a class="pd-btn pd-btn--primary" href="<?php echo esc_url( is_user_logged_in() ? self::url() : self::url( 'login' ) ); ?>"><?php pd_e( 'Kembali ke portal' ); ?></a>
	</div>
</main>
</body>
</html>
