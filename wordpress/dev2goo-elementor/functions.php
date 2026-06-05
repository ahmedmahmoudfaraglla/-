<?php
/**
 * Dev2Goo Elementor theme functions.
 *
 * @package Dev2Goo_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'D2G_THEME_VERSION', '1.0.0' );

function d2g_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'elementor' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'dev2goo-elementor' ),
			'footer'  => __( 'Footer Menu', 'dev2goo-elementor' ),
		)
	);
}
add_action( 'after_setup_theme', 'd2g_theme_setup' );

function d2g_theme_defaults() {
	return array(
		'phone'          => '+20 01008616316',
		'email'          => 'info@dev2goo.com',
		'address'        => 'Road 18, Sarayat El Maadi, 5th Floor, Cairo, Egypt',
		'cta_label'      => 'Start a project',
		'cta_url'        => home_url( '/contact/' ),
		'footer_summary' => 'Professional web development, hosting, website support, and digital growth services.',
	);
}

function d2g_theme_option( $key ) {
	$defaults = d2g_theme_defaults();
	return get_theme_mod( 'd2g_' . $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
}

function d2g_enqueue_assets() {
	wp_enqueue_style( 'dev2goo-elementor-style', get_stylesheet_uri(), array(), D2G_THEME_VERSION );
}
add_action( 'wp_enqueue_scripts', 'd2g_enqueue_assets' );

$d2g_setup = get_template_directory() . '/inc/setup.php';
if ( file_exists( $d2g_setup ) ) {
	require_once $d2g_setup;
}

function d2g_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'd2g_theme_options',
		array(
			'title'       => __( 'Dev2Goo Theme Options', 'dev2goo-elementor' ),
			'description' => __( 'Control header CTA, footer text, and contact details used across the theme.', 'dev2goo-elementor' ),
			'priority'    => 30,
		)
	);

	$fields = array(
		'phone'          => __( 'Phone', 'dev2goo-elementor' ),
		'email'          => __( 'Email', 'dev2goo-elementor' ),
		'address'        => __( 'Address', 'dev2goo-elementor' ),
		'cta_label'      => __( 'Header CTA Label', 'dev2goo-elementor' ),
		'cta_url'        => __( 'Header CTA URL', 'dev2goo-elementor' ),
		'footer_summary' => __( 'Footer Summary', 'dev2goo-elementor' ),
	);

	foreach ( $fields as $key => $label ) {
		$wp_customize->add_setting(
			'd2g_' . $key,
			array(
				'default'           => d2g_theme_option( $key ),
				'sanitize_callback' => 'cta_url' === $key ? 'esc_url_raw' : 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			'd2g_' . $key,
			array(
				'label'   => $label,
				'section' => 'd2g_theme_options',
				'type'    => 'footer_summary' === $key || 'address' === $key ? 'textarea' : 'text',
			)
		);
	}
}
add_action( 'customize_register', 'd2g_customize_register' );

function d2g_brand_markup() {
	?>
	<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr__( 'Dev2Goo home', 'dev2goo-elementor' ); ?>">
		<span class="brand-mark" aria-hidden="true">
			<svg viewBox="0 0 64 64" role="img">
				<path class="d2g-brace" d="M22 8h-5c-5 0-8 3-8 8v7c0 4-2 6-6 6v6c4 0 6 2 6 6v7c0 5 3 8 8 8h5v-8h-4c-2 0-3-1-3-3v-8c0-4-2-7-5-9 3-2 5-5 5-9v-8c0-2 1-3 3-3h4V8Z"/>
				<path class="d2g-brace" d="M42 8h5c5 0 8 3 8 8v7c0 4 2 6 6 6v6c-4 0-6 2-6 6v7c0 5-3 8-8 8h-5v-8h4c2 0 3-1 3-3v-8c0-4 2-7 5-9-3-2-5-5-5-9v-8c0-2-1-3-3-3h-4V8Z"/>
				<path class="d2g-two" d="M24 51v-8c0-5 3-8 8-8h7c2 0 3-1 3-3s-1-3-3-3H25v-8h15c7 0 11 4 11 11s-4 11-11 11h-7c-1 0-2 1-2 2v6h20v8H24Z"/>
				<circle class="d2g-dot-orange" cx="27" cy="20" r="3"/>
				<circle class="d2g-dot-blue" cx="36" cy="20" r="3"/>
				<rect class="d2g-pixel" x="32" y="5" width="8" height="8" rx="1"/>
			</svg>
		</span>
		<span class="brand-text">
			<strong>dev<span>2</span>goo</strong>
			<small>masters code</small>
		</span>
	</a>
	<?php
}

function d2g_menu_fallback() {
	$items = array(
		'Home'             => home_url( '/' ),
		'Services'         => home_url( '/services/' ),
		'Hosting & VPS'    => home_url( '/hosting/' ),
		'Support'          => home_url( '/support/' ),
		'About'            => home_url( '/about/' ),
		d2g_theme_option( 'cta_label' ) => d2g_theme_option( 'cta_url' ),
	);

	echo '<ul class="primary-menu">';
	foreach ( $items as $label => $url ) {
		$class = d2g_theme_option( 'cta_label' ) === $label ? ' class="menu-cta"' : '';
		printf( '<li%1$s><a href="%2$s">%3$s</a></li>', $class, esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}
