<?php
/**
 * Halaman log masuk portal.
 *
 * @var array  $brand
 * @var string $error
 * @var string $notice
 * @var string $username
 * @var string $redirect
 */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html lang="<?php echo esc_attr( PD_I18n::html_lang() ); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php pd_e( 'Log Masuk' ); ?> · <?php echo esc_html( $brand['title'] ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( self::asset( 'portal.css' ) ); ?>">
</head>
<body class="pd-auth">
<main class="pd-auth__wrap">
	<section class="pd-auth__brand" aria-hidden="true">
		<div class="pd-auth__brand-inner">
			<div class="pd-logo pd-logo--lg"><?php include PD_DIR . 'templates/logo.php'; ?></div>
			<h2><?php echo esc_html( $brand['title'] ); ?></h2>
			<p><?php echo esc_html( $brand['subtitle'] ); ?></p>
			<ul class="pd-auth__points">
				<li><?php pd_e( 'Semua fail jabatan di satu tempat' ); ?></li>
				<li><?php pd_e( 'Pratonton gambar, PDF dan video terus dalam pelayar' ); ?></li>
				<li><?php pd_e( 'Tong sampah untuk pulihkan fail yang terpadam' ); ?></li>
			</ul>
		</div>
	</section>
	<section class="pd-auth__panel">
		<form class="pd-auth__form" method="post" action="<?php echo esc_url( self::url( 'login' ) ); ?>" novalidate>
			<nav class="pd-lang" aria-label="<?php pd_e( 'Bahasa' ); ?>">
				<?php foreach ( PD_I18n::LANGS as $code => $label ) : ?>
					<?php if ( PD_I18n::lang() === $code ) : ?>
						<span class="is-on" aria-current="true" lang="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( PD_I18n::switch_url( $code, self::url( 'login', $redirect !== self::url() ? array( 'redirect_to' => $redirect ) : array() ) ) ); ?>" lang="<?php echo esc_attr( $code ); ?>" hreflang="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>
			<div class="pd-auth__head">
				<div class="pd-logo pd-auth__mobile-logo"><?php include PD_DIR . 'templates/logo.php'; ?></div>
				<h1><?php pd_e( 'Log masuk' ); ?></h1>
				<p><?php echo esc_html( $brand['org'] ? $brand['org'] . ' · ' . $brand['title'] : $brand['title'] ); ?></p>
			</div>

			<?php if ( $error ) : ?>
				<div class="pd-alert pd-alert--error" role="alert"><?php echo esc_html( $error ); ?></div>
			<?php elseif ( $notice ) : ?>
				<div class="pd-alert pd-alert--ok" role="status"><?php echo esc_html( $notice ); ?></div>
			<?php endif; ?>

			<label class="pd-field">
				<span><?php pd_e( 'Nama pengguna atau emel' ); ?></span>
				<input type="text" name="log" value="<?php echo esc_attr( $username ); ?>" autocomplete="username" autocapitalize="off" required autofocus>
			</label>
			<label class="pd-field">
				<span><?php pd_e( 'Kata laluan' ); ?></span>
				<span class="pd-pass">
					<input type="password" name="pwd" autocomplete="current-password" required>
					<button type="button" class="pd-pass__toggle" aria-label="<?php pd_e( 'Tunjuk kata laluan' ); ?>" onclick="var i=this.previousElementSibling;i.type=i.type==='password'?'text':'password';this.setAttribute('aria-pressed',i.type==='text')">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
					</button>
				</span>
			</label>
			<div class="pd-auth__row">
				<label class="pd-check"><input type="checkbox" name="rememberme" value="1"> <?php pd_e( 'Ingat saya' ); ?></label>
				<a href="<?php echo esc_url( wp_lostpassword_url( self::url() ) ); ?>"><?php pd_e( 'Lupa kata laluan?' ); ?></a>
			</div>
			<?php wp_nonce_field( 'pd_login', '_pdnonce', false ); ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect ); ?>">
			<button type="submit" class="pd-btn pd-btn--primary pd-btn--block"><?php pd_e( 'Log masuk' ); ?></button>
			<p class="pd-auth__foot">&copy; <?php echo esc_html( wp_date( 'Y' ) . ' ' . ( $brand['org'] ?: $brand['title'] ) ); ?></p>
		</form>
	</section>
</main>
</body>
</html>
