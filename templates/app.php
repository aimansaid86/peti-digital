<?php
/**
 * Cangkerang aplikasi portal. Kandungan dijana oleh assets/app.js.
 *
 * @var array $config
 * @var array $brand
 */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html lang="<?php echo esc_attr( PD_I18n::html_lang() ); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0f1f3d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $brand['title'] ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( self::asset( 'portal.css' ) ); ?>">
</head>
<body class="pd-app-body">
<div id="pd-app" class="pd-app">
	<aside class="pd-side" id="pd-side">
		<a class="pd-side__brand" href="#/utama">
			<span class="pd-logo"><?php include PD_DIR . 'templates/logo.php'; ?></span>
			<span>
				<strong><?php echo esc_html( $brand['title'] ); ?></strong>
				<small><?php echo esc_html( $brand['org'] ); ?></small>
			</span>
		</a>
		<nav class="pd-nav" id="pd-nav" aria-label="<?php pd_e( 'Menu utama' ); ?>"></nav>
		<div class="pd-side__storage" id="pd-storage"></div>
		<div class="pd-side__user" id="pd-usercard"></div>
	</aside>
	<div class="pd-scrim" id="pd-scrim"></div>
	<div class="pd-main">
		<header class="pd-topbar">
			<button class="pd-iconbtn pd-topbar__menu" id="pd-menu-btn" aria-label="<?php pd_e( 'Buka menu' ); ?>">
				<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
			</button>
			<a class="pd-topbar__brand" href="#/utama">
				<span class="pd-logo"><?php include PD_DIR . 'templates/logo.php'; ?></span>
				<strong><?php echo esc_html( $brand['title'] ); ?></strong>
			</a>
		</header>
		<main class="pd-view" id="pd-view" tabindex="-1">
			<div class="pd-loading"><span class="pd-spinner"></span></div>
		</main>
		<nav class="pd-tabbar" id="pd-tabbar" aria-label="<?php pd_e( 'Menu pantas' ); ?>"></nav>
	</div>
</div>
<div id="pd-layer"></div>
<div class="pd-toasts" id="pd-toasts" aria-live="polite"></div>
<noscript><p style="padding:24px"><?php pd_e( 'Portal ini memerlukan JavaScript.' ); ?></p></noscript>
<script>window.PD = <?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ); ?>;</script>
<script src="<?php echo esc_url( self::asset( 'app.js' ) ); ?>"></script>
</body>
</html>
