<?php
/**
 * The site header.
 *
 * @package Dev2Goo_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
	<nav class="site-nav site-container" aria-label="<?php esc_attr_e( 'Main navigation', 'dev2goo-elementor' ); ?>">
		<?php d2g_brand_markup(); ?>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'primary-menu',
				'fallback_cb'    => 'd2g_menu_fallback',
			)
		);
		?>
	</nav>
</header>
<main class="site-main">
