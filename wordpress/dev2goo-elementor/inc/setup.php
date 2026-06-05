<?php
/**
 * Dev2Goo theme onboarding: required plugins + one-click demo import.
 *
 * Activating the theme prompts the user to install Elementor (from WordPress.org)
 * and the bundled Dev2Goo Site companion plugin, then import the full demo with
 * a single click.
 *
 * @package Dev2Goo_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'D2G_SETUP_SLUG', 'dev2goo-setup' );
define( 'D2G_ELEMENTOR_FILE', 'elementor/elementor.php' );
define( 'D2G_CORE_FILE', 'dev2goo-site/dev2goo-site.php' );

/**
 * Register the "Dev2Goo Setup" page under Appearance.
 */
function d2g_setup_menu() {
	add_theme_page(
		__( 'Dev2Goo Setup', 'dev2goo-elementor' ),
		__( 'Dev2Goo Setup', 'dev2goo-elementor' ),
		'manage_options',
		D2G_SETUP_SLUG,
		'd2g_setup_render'
	);
}
add_action( 'admin_menu', 'd2g_setup_menu' );

/**
 * Redirect to the setup page right after the theme is activated.
 */
function d2g_setup_after_switch() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	set_transient( 'd2g_activation_redirect', 1, 60 );
}
add_action( 'after_switch_theme', 'd2g_setup_after_switch' );

function d2g_setup_maybe_redirect() {
	if ( ! get_transient( 'd2g_activation_redirect' ) ) {
		return;
	}
	delete_transient( 'd2g_activation_redirect' );
	if ( is_admin() && current_user_can( 'manage_options' ) && ! isset( $_GET['activate-multi'] ) ) {
		wp_safe_redirect( admin_url( 'themes.php?page=' . D2G_SETUP_SLUG ) );
		exit;
	}
}
add_action( 'admin_init', 'd2g_setup_maybe_redirect' );

/**
 * Show a reminder until both required plugins are active.
 */
function d2g_setup_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && false !== strpos( (string) $screen->id, D2G_SETUP_SLUG ) ) {
		return;
	}
	if ( d2g_is_elementor_active() && d2g_is_core_active() ) {
		return;
	}
	$url = admin_url( 'themes.php?page=' . D2G_SETUP_SLUG );
	echo '<div class="notice notice-info"><p><strong>Dev2Goo:</strong> ';
	echo esc_html__( 'Finish setup to install the required plugins and import the demo.', 'dev2goo-elementor' );
	echo ' <a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Open Dev2Goo Setup', 'dev2goo-elementor' ) . '</a>';
	echo '</p></div>';
}
add_action( 'admin_notices', 'd2g_setup_admin_notice' );

function d2g_is_elementor_active() {
	return defined( 'ELEMENTOR_VERSION' ) || d2g_is_plugin_active( D2G_ELEMENTOR_FILE );
}

function d2g_is_core_active() {
	return class_exists( 'Dev2Goo_Site' ) || d2g_is_plugin_active( D2G_CORE_FILE );
}

function d2g_is_plugin_active( $file ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	return is_plugin_active( $file );
}

function d2g_is_plugin_installed( $file ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$plugins = get_plugins();
	return isset( $plugins[ $file ] );
}

/**
 * Install a plugin from a package (WordPress.org download link or local zip path).
 *
 * @param string $package Download URL or absolute local zip path.
 * @return true|WP_Error
 */
function d2g_install_package( $package ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();

	$skin     = new Automatic_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $package );

	if ( is_wp_error( $result ) ) {
		return $result;
	}
	if ( false === $result || null === $result ) {
		return new WP_Error( 'd2g_install_failed', __( 'The plugin could not be installed automatically. Please upload it manually.', 'dev2goo-elementor' ) );
	}
	return true;
}

/**
 * Resolve the WordPress.org download link for a plugin slug.
 *
 * @param string $slug Plugin slug.
 * @return string|WP_Error
 */
function d2g_wporg_download_link( $slug ) {
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	$api = plugins_api(
		'plugin_information',
		array(
			'slug'   => $slug,
			'fields' => array( 'sections' => false ),
		)
	);
	if ( is_wp_error( $api ) ) {
		return $api;
	}
	if ( empty( $api->download_link ) ) {
		return new WP_Error( 'd2g_no_link', __( 'Could not resolve the plugin download link.', 'dev2goo-elementor' ) );
	}
	return $api->download_link;
}

function d2g_activate( $file ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( $file ) ) {
		return true;
	}
	$result = activate_plugin( $file );
	return is_wp_error( $result ) ? $result : true;
}

/**
 * Handle: install + activate Elementor from WordPress.org.
 */
function d2g_handle_install_elementor() {
	d2g_setup_guard( 'd2g_install_elementor' );

	$status = 'elementor_done';
	if ( ! d2g_is_plugin_installed( D2G_ELEMENTOR_FILE ) ) {
		$link = d2g_wporg_download_link( 'elementor' );
		if ( is_wp_error( $link ) ) {
			d2g_setup_redirect( 'elementor_error', $link->get_error_message() );
		}
		$installed = d2g_install_package( $link );
		if ( is_wp_error( $installed ) ) {
			d2g_setup_redirect( 'elementor_error', $installed->get_error_message() );
		}
	}
	$activated = d2g_activate( D2G_ELEMENTOR_FILE );
	if ( is_wp_error( $activated ) ) {
		d2g_setup_redirect( 'elementor_error', $activated->get_error_message() );
	}
	d2g_setup_redirect( $status );
}
add_action( 'admin_post_d2g_install_elementor', 'd2g_handle_install_elementor' );

/**
 * Handle: install + activate the bundled Dev2Goo Site companion plugin.
 */
function d2g_handle_install_core() {
	d2g_setup_guard( 'd2g_install_core' );

	if ( ! d2g_is_plugin_installed( D2G_CORE_FILE ) ) {
		$zip = get_template_directory() . '/lib/dev2goo-site.zip';
		if ( ! file_exists( $zip ) ) {
			d2g_setup_redirect( 'core_error', __( 'Bundled plugin file was not found in the theme.', 'dev2goo-elementor' ) );
		}
		$installed = d2g_install_package( $zip );
		if ( is_wp_error( $installed ) ) {
			d2g_setup_redirect( 'core_error', $installed->get_error_message() );
		}
	}
	$activated = d2g_activate( D2G_CORE_FILE );
	if ( is_wp_error( $activated ) ) {
		d2g_setup_redirect( 'core_error', $activated->get_error_message() );
	}
	d2g_setup_redirect( 'core_done' );
}
add_action( 'admin_post_d2g_install_core', 'd2g_handle_install_core' );

/**
 * Handle: one-click demo import (pages + front page + menus).
 */
function d2g_handle_import() {
	d2g_setup_guard( 'd2g_import' );

	if ( ! d2g_is_core_active() || ! class_exists( 'Dev2Goo_Site' ) ) {
		d2g_setup_redirect( 'import_error', __( 'Activate the Dev2Goo Site plugin first.', 'dev2goo-elementor' ) );
	}

	$page_ids = Dev2Goo_Site::run_build();
	d2g_build_menus( $page_ids );

	d2g_setup_redirect( 'import_done' );
}
add_action( 'admin_post_d2g_import', 'd2g_handle_import' );

/**
 * Create a primary/footer menu from the imported pages for theme-level templates.
 *
 * @param array $page_ids Map of slug => page ID.
 */
function d2g_build_menus( $page_ids ) {
	$menu_name = 'Dev2Goo Menu';
	$menu      = wp_get_nav_menu_object( $menu_name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	} else {
		$menu_id = (int) $menu->term_id;
		$items   = wp_get_nav_menu_items( $menu_id );
		if ( $items ) {
			foreach ( $items as $item ) {
				wp_delete_post( $item->ID, true );
			}
		}
	}
	if ( is_wp_error( $menu_id ) ) {
		return;
	}

	$labels = array(
		'home'     => __( 'Home', 'dev2goo-elementor' ),
		'services' => __( 'Services', 'dev2goo-elementor' ),
		'hosting'  => __( 'Hosting & VPS', 'dev2goo-elementor' ),
		'support'  => __( 'Support', 'dev2goo-elementor' ),
		'about'    => __( 'About', 'dev2goo-elementor' ),
		'contact'  => __( 'Contact', 'dev2goo-elementor' ),
	);

	foreach ( $labels as $slug => $label ) {
		if ( empty( $page_ids[ $slug ] ) ) {
			continue;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $label,
				'menu-item-object-id' => (int) $page_ids[ $slug ],
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}

	$locations            = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	$locations['footer']  = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Shared nonce/permission guard for setup actions.
 *
 * @param string $action Nonce action name.
 */
function d2g_setup_guard( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'dev2goo-elementor' ) );
	}
	check_admin_referer( $action );
}

/**
 * Redirect back to the setup page with a status flag.
 *
 * @param string $status  Status key.
 * @param string $message Optional error message.
 */
function d2g_setup_redirect( $status, $message = '' ) {
	$args = array(
		'page'       => D2G_SETUP_SLUG,
		'd2g_status' => $status,
	);
	if ( '' !== $message ) {
		$args['d2g_msg'] = rawurlencode( $message );
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'themes.php' ) ) );
	exit;
}

/**
 * Output a small action form (button) for the setup steps.
 *
 * @param string $action Admin-post action.
 * @param string $label  Button text.
 * @param bool   $primary Whether to use the primary style.
 * @param bool   $disabled Whether the button is disabled.
 */
function d2g_setup_button( $action, $label, $primary = true, $disabled = false ) {
	$class = $primary ? 'button button-primary button-hero' : 'button button-hero';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin:0 8px 8px 0;">';
	echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
	wp_nonce_field( $action );
	echo '<button type="submit" class="' . esc_attr( $class ) . '"' . ( $disabled ? ' disabled' : '' ) . '>' . esc_html( $label ) . '</button>';
	echo '</form>';
}

/**
 * Status badge helper.
 *
 * @param bool $ok Whether the step is complete.
 */
function d2g_badge( $ok ) {
	if ( $ok ) {
		echo '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#e6f4ea;color:#137333;font-weight:600;">' . esc_html__( 'Active', 'dev2goo-elementor' ) . '</span>';
	} else {
		echo '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#fce8e6;color:#c5221f;font-weight:600;">' . esc_html__( 'Not active', 'dev2goo-elementor' ) . '</span>';
	}
}

/**
 * Render the setup wizard page.
 */
function d2g_setup_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$status = isset( $_GET['d2g_status'] ) ? sanitize_text_field( wp_unslash( $_GET['d2g_status'] ) ) : '';
	$msg    = isset( $_GET['d2g_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['d2g_msg'] ) ) : '';

	$elementor_ok = d2g_is_elementor_active();
	$core_ok      = d2g_is_core_active();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Dev2Goo Setup', 'dev2goo-elementor' ); ?></h1>
		<p style="font-size:14px;max-width:760px;">
			<?php esc_html_e( 'Follow the three steps below. Step 1 and 2 install the required plugins, and step 3 imports the full Dev2Goo demo (all pages, header, footer, and homepage) as Elementor-editable Canvas pages.', 'dev2goo-elementor' ); ?>
		</p>

		<?php if ( 'import_done' === $status ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Demo imported successfully.', 'dev2goo-elementor' ); ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php esc_html_e( 'View your website', 'dev2goo-elementor' ); ?></a></p></div>
		<?php elseif ( 'elementor_done' === $status ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Elementor is installed and active.', 'dev2goo-elementor' ); ?></p></div>
		<?php elseif ( 'core_done' === $status ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Dev2Goo Site plugin is installed and active.', 'dev2goo-elementor' ); ?></p></div>
		<?php elseif ( in_array( $status, array( 'elementor_error', 'core_error', 'import_error' ), true ) ) : ?>
			<div class="notice notice-error"><p>
				<?php esc_html_e( 'Something went wrong:', 'dev2goo-elementor' ); ?>
				<?php echo esc_html( $msg ); ?>
			</p></div>
		<?php endif; ?>

		<div class="card" style="max-width:820px;padding:20px 24px;margin-top:18px;">
			<h2><?php esc_html_e( 'Step 1 — Install Elementor', 'dev2goo-elementor' ); ?> <?php d2g_badge( $elementor_ok ); ?></h2>
			<p><?php esc_html_e( 'Elementor is the free page builder used to edit your pages.', 'dev2goo-elementor' ); ?></p>
			<?php if ( ! $elementor_ok ) : ?>
				<?php d2g_setup_button( 'd2g_install_elementor', __( 'Install & Activate Elementor', 'dev2goo-elementor' ) ); ?>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width:820px;padding:20px 24px;margin-top:18px;">
			<h2><?php esc_html_e( 'Step 2 — Install Dev2Goo Site plugin', 'dev2goo-elementor' ); ?> <?php d2g_badge( $core_ok ); ?></h2>
			<p><?php esc_html_e( 'The companion plugin carries the design and the demo content (bundled with the theme).', 'dev2goo-elementor' ); ?></p>
			<?php if ( ! $core_ok ) : ?>
				<?php d2g_setup_button( 'd2g_install_core', __( 'Install & Activate Dev2Goo Site', 'dev2goo-elementor' ) ); ?>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width:820px;padding:20px 24px;margin-top:18px;">
			<h2><?php esc_html_e( 'Step 3 — Import the demo', 'dev2goo-elementor' ); ?></h2>
			<p><?php esc_html_e( 'Creates Home, Services, Hosting & VPS, Support, About, and Contact, sets the homepage, and builds the menus.', 'dev2goo-elementor' ); ?></p>
			<?php if ( $core_ok ) : ?>
				<?php d2g_setup_button( 'd2g_import', __( 'Import Dev2Goo Demo', 'dev2goo-elementor' ) ); ?>
			<?php else : ?>
				<p><em><?php esc_html_e( 'Complete step 2 to enable the import.', 'dev2goo-elementor' ); ?></em></p>
			<?php endif; ?>
		</div>

		<p style="margin-top:18px;">
			<a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><?php esc_html_e( 'Edit Theme Options (logo, colors, contact details) in the Customizer', 'dev2goo-elementor' ); ?></a>
		</p>
	</div>
	<?php
}
