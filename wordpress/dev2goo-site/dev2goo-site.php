<?php
/**
 * Plugin Name: Dev2Goo Site (Elementor)
 * Plugin URI: https://dev2goo.com/
 * Description: One self-contained plugin that builds the full Dev2Goo website (header, footer, and all pages) as Elementor-editable Canvas pages. Works with ANY active theme. Just activate, open Settings > Dev2Goo, and click Build / Import.
 * Version: 2.0.0
 * Author: Dev2Goo
 * Author URI: https://dev2goo.com/
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: dev2goo-site
 *
 * @package Dev2Goo_Site
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DEV2GOO_SITE_VERSION', '2.0.0' );
define( 'DEV2GOO_SITE_URL', plugin_dir_url( __FILE__ ) );
define( 'DEV2GOO_SITE_PATH', plugin_dir_path( __FILE__ ) );

final class Dev2Goo_Site {
	const OPTION_KEY  = 'dev2goo_site_options';
	const NONCE       = 'dev2goo_site_build';
	const MENU_SLUG   = 'dev2goo-site';

	/**
	 * Singleton bootstrap.
	 */
	public static function init() {
		$instance = new self();
		add_action( 'wp_enqueue_scripts', array( $instance, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( $instance, 'admin_menu' ) );
		add_action( 'admin_init', array( $instance, 'register_settings' ) );
		add_action( 'admin_post_dev2goo_build_site', array( $instance, 'handle_build' ) );
		add_action( 'admin_notices', array( $instance, 'admin_notice' ) );
		return $instance;
	}

	/**
	 * Default options.
	 */
	public function defaults() {
		return array(
			'phone'    => '+20 01008616316',
			'email'    => 'info@dev2goo.com',
			'address'  => 'Road 18, Sarayat El Maadi, 5th Floor, Cairo, Egypt',
			'cta_label'=> 'Start a project',
			'cta_url'  => '/contact/',
			'whatsapp' => '+20 01008616316',
		);
	}

	/**
	 * Merged options.
	 */
	public function options() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, $this->defaults() );
	}

	public function opt( $key ) {
		$options = $this->options();
		return isset( $options[ $key ] ) ? $options[ $key ] : '';
	}

	/**
	 * Load the design CSS on the whole front-end so pages always look right.
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'dev2goo-site',
			DEV2GOO_SITE_URL . 'assets/dev2goo.css',
			array(),
			DEV2GOO_SITE_VERSION
		);
	}

	/**
	 * Admin menu under Settings.
	 */
	public function admin_menu() {
		add_menu_page(
			__( 'Dev2Goo Site', 'dev2goo-site' ),
			__( 'Dev2Goo Site', 'dev2goo-site' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_admin_page' ),
			'dashicons-layout',
			59
		);
	}

	public function register_settings() {
		register_setting(
			'dev2goo_site_group',
			self::OPTION_KEY,
			array( $this, 'sanitize_options' )
		);
	}

	public function sanitize_options( $input ) {
		$clean = array();
		$input = is_array( $input ) ? $input : array();
		$clean['phone']     = isset( $input['phone'] ) ? sanitize_text_field( $input['phone'] ) : '';
		$clean['email']     = isset( $input['email'] ) ? sanitize_email( $input['email'] ) : '';
		$clean['address']   = isset( $input['address'] ) ? sanitize_text_field( $input['address'] ) : '';
		$clean['cta_label'] = isset( $input['cta_label'] ) ? sanitize_text_field( $input['cta_label'] ) : '';
		$clean['cta_url']   = isset( $input['cta_url'] ) ? esc_url_raw( $input['cta_url'] ) : '';
		$clean['whatsapp']  = isset( $input['whatsapp'] ) ? sanitize_text_field( $input['whatsapp'] ) : '';
		return $clean;
	}

	public function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( (string) $screen->id, self::MENU_SLUG ) ) {
			return;
		}
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			echo '<div class="notice notice-info"><p>';
			echo esc_html__( 'Tip: install and activate Elementor to edit the imported pages visually. The site still renders correctly without it.', 'dev2goo-site' );
			echo '</p></div>';
		}
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = $this->options();
		$built   = isset( $_GET['dev2goo_built'] ) ? sanitize_text_field( wp_unslash( $_GET['dev2goo_built'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Dev2Goo Site', 'dev2goo-site' ); ?></h1>
			<?php if ( 'yes' === $built ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php esc_html_e( 'Done! All pages, header, footer, and homepage were created. Open your site to preview.', 'dev2goo-site' ); ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php esc_html_e( 'View website', 'dev2goo-site' ); ?></a>
				</p></div>
			<?php endif; ?>

			<h2 class="title"><?php esc_html_e( '1) Business details', 'dev2goo-site' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'dev2goo_site_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="d2g_phone"><?php esc_html_e( 'Phone', 'dev2goo-site' ); ?></label></th>
						<td><input class="regular-text" type="text" id="d2g_phone" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[phone]" value="<?php echo esc_attr( $options['phone'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="d2g_email"><?php esc_html_e( 'Email', 'dev2goo-site' ); ?></label></th>
						<td><input class="regular-text" type="email" id="d2g_email" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[email]" value="<?php echo esc_attr( $options['email'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="d2g_address"><?php esc_html_e( 'Address', 'dev2goo-site' ); ?></label></th>
						<td><input class="large-text" type="text" id="d2g_address" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[address]" value="<?php echo esc_attr( $options['address'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="d2g_cta_label"><?php esc_html_e( 'Header button label', 'dev2goo-site' ); ?></label></th>
						<td><input class="regular-text" type="text" id="d2g_cta_label" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cta_label]" value="<?php echo esc_attr( $options['cta_label'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="d2g_cta_url"><?php esc_html_e( 'Header button URL', 'dev2goo-site' ); ?></label></th>
						<td><input class="regular-text" type="text" id="d2g_cta_url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cta_url]" value="<?php echo esc_attr( $options['cta_url'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="d2g_whatsapp"><?php esc_html_e( 'WhatsApp', 'dev2goo-site' ); ?></label></th>
						<td><input class="regular-text" type="text" id="d2g_whatsapp" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[whatsapp]" value="<?php echo esc_attr( $options['whatsapp'] ); ?>"></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save details', 'dev2goo-site' ) ); ?>
			</form>

			<hr>
			<h2 class="title"><?php esc_html_e( '2) Build the website', 'dev2goo-site' ); ?></h2>
			<p><?php esc_html_e( 'This creates Home, Services, Hosting & VPS, Support, About, and Contact as Elementor Canvas pages with the Dev2Goo header and footer baked in, then sets Home as the front page. Re-running updates the same pages.', 'dev2goo-site' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dev2goo_build_site">
				<?php wp_nonce_field( self::NONCE ); ?>
				<?php submit_button( __( 'Build / Import Dev2Goo Site', 'dev2goo-site' ), 'primary large' ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_build() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'dev2goo-site' ) );
		}
		check_admin_referer( self::NONCE );

		$this->build_site();

		wp_safe_redirect( add_query_arg( 'dev2goo_built', 'yes', admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) );
		exit;
	}

	/**
	 * Public entry point so a theme (or any caller) can run the demo build.
	 *
	 * @return array Map of page slug => page ID.
	 */
	public static function run_build() {
		$instance = new self();
		return $instance->build_site();
	}

	/**
	 * Build/refresh all pages, set the front page, and clear Elementor cache.
	 *
	 * @return array Map of page slug => page ID.
	 */
	public function build_site() {
		$page_ids = array();
		foreach ( $this->pages() as $slug => $page ) {
			$page_ids[ $slug ] = $this->upsert_page( $slug, $page['title'], $this->full_page_html( $slug, $page['body'] ) );
		}

		if ( ! empty( $page_ids['home'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', (int) $page_ids['home'] );
		}

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		return $page_ids;
	}

	private function upsert_page( $slug, $title, $html ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		$postarr  = array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => $html,
		);

		if ( $existing ) {
			$postarr['ID'] = $existing->ID;
			$page_id       = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$page_id = wp_insert_post( wp_slash( $postarr ), true );
		}

		if ( is_wp_error( $page_id ) ) {
			wp_die( esc_html( $page_id->get_error_message() ) );
		}

		update_post_meta( $page_id, '_wp_page_template', 'elementor_canvas' );
		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $page_id, '_elementor_version', '3.0.0' );
		update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $this->elementor_data( $html ) ) ) );

		return (int) $page_id;
	}

	/**
	 * One full-width section -> column -> HTML widget carrying the whole page.
	 */
	private function elementor_data( $html ) {
		return array(
			array(
				'id'       => $this->eid(),
				'elType'   => 'section',
				'settings' => array(
					'layout'            => 'full_width',
					'gap'               => 'no',
					'padding'           => array(
						'unit'     => 'px',
						'top'      => '0',
						'right'    => '0',
						'bottom'   => '0',
						'left'     => '0',
						'isLinked' => true,
					),
				),
				'elements' => array(
					array(
						'id'       => $this->eid(),
						'elType'   => 'column',
						'settings' => array( '_column_size' => 100 ),
						'elements' => array(
							array(
								'id'         => $this->eid(),
								'elType'     => 'widget',
								'widgetType' => 'html',
								'settings'   => array( 'html' => $html ),
								'elements'   => array(),
							),
						),
					),
				),
			),
		);
	}

	private function eid() {
		return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 7 );
	}

	/**
	 * Brand SVG mark.
	 */
	private function brand( $footer = false ) {
		$class = $footer ? 'brand footer-brand' : 'brand';
		return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( home_url( '/' ) ) . '" aria-label="Dev2Goo home">'
			. '<span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 64 64" role="img">'
			. '<path class="d2g-brace" d="M22 8h-5c-5 0-8 3-8 8v7c0 4-2 6-6 6v6c4 0 6 2 6 6v7c0 5 3 8 8 8h5v-8h-4c-2 0-3-1-3-3v-8c0-4-2-7-5-9 3-2 5-5 5-9v-8c0-2 1-3 3-3h4V8Z"/>'
			. '<path class="d2g-brace" d="M42 8h5c5 0 8 3 8 8v7c0 4 2 6 6 6v6c-4 0-6 2-6 6v7c0 5-3 8-8 8h-5v-8h4c2 0 3-1 3-3v-8c0-4 2-7 5-9-3-2-5-5-5-9v-8c0-2-1-3-3-3h-4V8Z"/>'
			. '<path class="d2g-two" d="M24 51v-8c0-5 3-8 8-8h7c2 0 3-1 3-3s-1-3-3-3H25v-8h15c7 0 11 4 11 11s-4 11-11 11h-7c-1 0-2 1-2 2v6h20v8H24Z"/>'
			. '<circle class="d2g-dot-orange" cx="27" cy="20" r="3"/><circle class="d2g-dot-blue" cx="36" cy="20" r="3"/>'
			. '<rect class="d2g-pixel" x="32" y="5" width="8" height="8" rx="1"/></svg></span>'
			. '<span class="brand-text"><strong>dev<span>2</span>goo</strong><small>masters code</small></span></a>';
	}

	/**
	 * Header with navigation + CTA.
	 */
	private function header_html() {
		$cta_label = $this->opt( 'cta_label' );
		$cta_url   = $this->opt( 'cta_url' );
		$links     = array(
			'Home'          => '/',
			'Services'      => '/services/',
			'Hosting & VPS' => '/hosting/',
			'Support'       => '/support/',
			'About'         => '/about/',
		);
		$items = '';
		foreach ( $links as $label => $url ) {
			$items .= '<li><a href="' . esc_url( home_url( $url ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
		$items .= '<li class="menu-cta"><a href="' . esc_url( home_url( $cta_url ) ) . '">' . esc_html( $cta_label ) . '</a></li>';

		return '<header class="site-header"><div class="site-container site-nav">'
			. $this->brand()
			. '<ul class="primary-menu">' . $items . '</ul>'
			. '</div></header>';
	}

	/**
	 * Footer with details from settings.
	 */
	private function footer_html() {
		$links = array(
			'Services'      => '/services/',
			'Hosting & VPS' => '/hosting/',
			'Support'       => '/support/',
			'About'         => '/about/',
			'Contact'       => '/contact/',
		);
		$items = '';
		foreach ( $links as $label => $url ) {
			$items .= '<li><a href="' . esc_url( home_url( $url ) ) . '">' . esc_html( $label ) . '</a></li>';
		}

		$year = esc_html( gmdate( 'Y' ) );

		return '<footer class="site-footer"><div class="site-container footer-grid">'
			. $this->brand( true )
			. '<ul class="footer-menu">' . $items . '</ul>'
			. '<p class="copyright">&copy; ' . $year . ' Dev2Goo. ' . esc_html( $this->opt( 'phone' ) ) . '</p>'
			. '</div></footer>';
	}

	/**
	 * Wrap header + page body + footer inside the scoped design container.
	 */
	private function full_page_html( $slug, $body ) {
		return '<div class="d2g-site">' . $this->header_html() . $body . $this->footer_html() . '</div>';
	}

	private function pages() {
		return array(
			'home'     => array( 'title' => 'Home', 'body' => $this->home_body() ),
			'services' => array( 'title' => 'Services', 'body' => $this->services_body() ),
			'hosting'  => array( 'title' => 'Hosting & VPS', 'body' => $this->hosting_body() ),
			'support'  => array( 'title' => 'Website Support', 'body' => $this->support_body() ),
			'about'    => array( 'title' => 'About', 'body' => $this->about_body() ),
			'contact'  => array( 'title' => 'Contact', 'body' => $this->contact_body() ),
		);
	}

	private function home_body() {
		return <<<'HTML'
<section class="d2g-hero"><div class="d2g-container d2g-hero-grid"><div><p class="d2g-eyebrow">Smart digital solutions is our game.</p><h1>Websites, servers, and support built for businesses that want to grow.</h1><p class="d2g-lead">Dev2Goo is a full digital partner for professional websites, hosting, VPS servers, eCommerce, SEO, marketing, and managed support.</p><div class="d2g-actions"><a class="d2g-button-primary" href="/contact/">Request consultation</a><a class="d2g-button-secondary" href="/services/">Explore services</a></div><div class="d2g-pills"><span>Web Development</span><span>Managed Hosting</span><span>Website Support</span><span>Digital Growth</span></div></div><div class="d2g-dashboard"><p class="d2g-code-line"><span>deploy</span> conversion-ready website</p><p class="d2g-code-line"><span>secure</span> VPS + hosting stack</p><p class="d2g-code-line"><span>support</span> updates, backups, fixes</p><p class="d2g-code-line"><span>grow</span> SEO + social campaigns</p><div class="d2g-metric"><strong>360</strong><span>Digital services partner</span></div></div></div></section>
<section class="d2g-section"><div class="d2g-container d2g-split"><div><p class="d2g-eyebrow">We are dev2goo</p><h2>An integrated digital solutions provider.</h2></div><p>Welcome to Dev2Goo, your go-to solution for web development, mobile-ready experiences, eCommerce, IT consulting, hosting services, VPS management, website support, and marketing production.</p></div></section>
<section class="d2g-section d2g-soft"><div class="d2g-container"><div class="d2g-heading"><p class="d2g-eyebrow">What we do</p><h2>World-class services for every stage of your digital business.</h2><p>From the first interface to the server behind it, Dev2Goo handles the full lifecycle.</p></div><div class="d2g-grid-3"><article class="d2g-card"><span class="d2g-number">01</span><h3>Web Development</h3><p>Modern websites, landing pages, portals, dashboards, and custom web platforms.</p></article><article class="d2g-card d2g-featured"><span class="d2g-number">02</span><h3>Website Support</h3><p>Maintenance, security updates, content edits, backups, fixes, and technical care.</p></article><article class="d2g-card"><span class="d2g-number">03</span><h3>Hosting & VPS</h3><p>Fast hosting, VPS setup, server hardening, migration, email, SSL, DNS, and monitoring.</p></article><article class="d2g-card"><span class="d2g-number">04</span><h3>eCommerce</h3><p>Stores, payment integrations, catalog management, checkout flows, and analytics.</p></article><article class="d2g-card"><span class="d2g-number">05</span><h3>UI/UX Design</h3><p>Clean design systems, user journeys, wireframes, and premium visual experiences.</p></article><article class="d2g-card"><span class="d2g-number">06</span><h3>SEO & Marketing</h3><p>Technical SEO, speed optimization, social campaigns, and content foundations.</p></article></div></div></section>
<section class="d2g-section d2g-dark"><div class="d2g-container d2g-feature-grid"><div><p class="d2g-eyebrow">Built around your business</p><h2>Launch fast, run safely, and keep improving.</h2><p>We design the website, configure the hosting, secure the server, connect analytics, and stay beside you with practical support.</p></div><div class="d2g-stack"><article><span>Strategy</span><strong>Business-ready structure</strong><p>Clear pages, service funnels, calls to action, and conversion paths.</p></article><article><span>Infrastructure</span><strong>Stable hosting stack</strong><p>SSL, DNS, email, backups, uptime checks, security, and speed foundations.</p></article><article><span>Care</span><strong>Managed website support</strong><p>Ongoing improvements, updates, issue handling, and monthly technical help.</p></article></div></div></section>
<section class="d2g-stats"><div class="d2g-container d2g-grid-4"><div><strong>120+</strong><span>Completed Projects</span></div><div><strong>95+</strong><span>Satisfied Clients</span></div><div><strong>12+</strong><span>Team Members</span></div><div><strong>180+</strong><span>Hosted Sites</span></div></div></section>
<section class="d2g-section"><div class="d2g-container"><div class="d2g-heading"><p class="d2g-eyebrow">How we work</p><h2>A clear process from idea to launch and support.</h2></div><div class="d2g-grid-4"><article class="d2g-card"><span class="d2g-number">01</span><h3>Discover</h3><p>We map your goals, audience, current website, content, hosting needs, and priorities.</p></article><article class="d2g-card"><span class="d2g-number">02</span><h3>Design</h3><p>We create modern interfaces, user journeys, and page structures that match your brand.</p></article><article class="d2g-card"><span class="d2g-number">03</span><h3>Develop</h3><p>We build responsive, fast, SEO-ready pages and connect your business systems.</p></article><article class="d2g-card"><span class="d2g-number">04</span><h3>Operate</h3><p>We launch, monitor, maintain, and improve the website and infrastructure.</p></article></div></div></section>
<section class="d2g-band"><div class="d2g-container d2g-band-grid"><div><p class="d2g-eyebrow">Your business is digitally ready</p><h2>Need a website, hosting, or reliable support?</h2><p>Tell us about your project and we will recommend the right solution.</p></div><a class="d2g-button-primary" href="/contact/">Talk to Dev2Goo</a></div></section>
HTML;
	}

	private function services_body() {
		return <<<'HTML'
<section class="d2g-page-hero"><div class="d2g-container d2g-page-hero-grid"><div><p class="d2g-eyebrow">World-class development services</p><h1>Digital services that look premium, work fast, and scale with your business.</h1><p>Dev2Goo creates websites, stores, interfaces, campaigns, and consulting systems with one connected delivery team.</p></div><div class="d2g-page-card"><span>Core promise</span><strong>Design + code + growth + support</strong><p>Everything is planned to help your customers understand, trust, and contact your business faster.</p></div></div></section>
<section class="d2g-section"><div class="d2g-container d2g-grid-3"><article class="d2g-card"><span class="d2g-number">01</span><h3>Web Development</h3><p>Professional websites, landing pages, company profiles, portals, dashboards, booking flows, and custom platforms.</p><ul class="d2g-list"><li>Responsive front-end layouts</li><li>CMS-ready content structure</li><li>Fast pages and clean technical SEO</li><li>Contact, WhatsApp, analytics, and CRM integrations</li></ul></article><article class="d2g-card"><span class="d2g-number">02</span><h3>eCommerce Solutions</h3><p>Online stores that help customers browse, buy, pay, and return with confidence.</p><ul class="d2g-list"><li>Product catalogs</li><li>Payment and shipping workflows</li><li>Promotions and coupons</li><li>Store analytics</li></ul></article><article class="d2g-card"><span class="d2g-number">03</span><h3>UI/UX Design</h3><p>Modern interfaces built around clarity, conversion, and a strong brand experience.</p><ul class="d2g-list"><li>Wireframes</li><li>Design systems</li><li>Premium hero sections</li><li>Mobile-first usability</li></ul></article><article class="d2g-card"><span class="d2g-number">04</span><h3>SEO & Digital Marketing</h3><p>Search-ready websites and campaigns that help your business gain visibility.</p><ul class="d2g-list"><li>Technical SEO</li><li>Metadata and sitemap readiness</li><li>Social campaign support</li><li>Advertising landing pages</li></ul></article><article class="d2g-card"><span class="d2g-number">05</span><h3>IT Consulting</h3><p>Practical guidance for choosing the right stack, hosting, security model, and workflow.</p><ul class="d2g-list"><li>Website audits</li><li>DNS, email, and hosting reviews</li><li>Migration planning</li><li>Automation recommendations</li></ul></article><article class="d2g-card d2g-featured"><span class="d2g-number">06</span><h3>Complete Digital Packages</h3><p>Bundle design, development, hosting, support, SEO, and marketing into one delivery plan.</p><a class="d2g-button-primary" href="/contact/">Build my package</a></article></div></section>
<section class="d2g-section d2g-soft"><div class="d2g-container"><div class="d2g-heading"><p class="d2g-eyebrow">Service tracks</p><h2>Pick the track that matches your next move.</h2></div><div class="d2g-grid-3"><article class="d2g-package"><h3>Launch</h3><p>For a new business website or landing page.</p><ul class="d2g-list"><li>Brand-aligned page design</li><li>Core company pages</li><li>Contact and analytics setup</li></ul></article><article class="d2g-package d2g-featured"><h3>Scale</h3><p>For companies that need stronger pages and growth systems.</p><ul class="d2g-list"><li>Advanced services structure</li><li>SEO-ready content architecture</li><li>Hosting, support, and optimization</li></ul></article><article class="d2g-package"><h3>Operate</h3><p>For businesses that need ongoing technical care.</p><ul class="d2g-list"><li>Maintenance and issue handling</li><li>Backups and security checks</li><li>Monthly improvements</li></ul></article></div></div></section>
<section class="d2g-band"><div class="d2g-container d2g-band-grid"><div><p class="d2g-eyebrow">Ready to build?</p><h2>Let us shape the right service plan for your business.</h2></div><a class="d2g-button-primary" href="/contact/">Request a proposal</a></div></section>
HTML;
	}

	private function hosting_body() {
		return <<<'HTML'
<section class="d2g-page-hero"><div class="d2g-container d2g-page-hero-grid"><div><p class="d2g-eyebrow">Hosting services and VPS hosting</p><h1>Fast, secure hosting and managed servers for serious websites.</h1><p>We configure, migrate, secure, and monitor hosting environments so your website has a stable foundation.</p></div><div class="d2g-server-visual"><div class="d2g-server-row"><i></i><strong>Web node</strong><em>Online</em></div><div class="d2g-server-row"><i></i><strong>Database</strong><em>Healthy</em></div><div class="d2g-server-row"><i></i><strong>Backups</strong><em>Synced</em></div><div class="d2g-server-row"><i></i><strong>SSL</strong><em>Active</em></div></div></div></section>
<section class="d2g-section"><div class="d2g-container"><div class="d2g-heading"><p class="d2g-eyebrow">Infrastructure options</p><h2>Hosting that matches your business stage.</h2><p>Start lean, upgrade confidently, and keep your website protected as traffic grows.</p></div><div class="d2g-grid-3"><article class="d2g-package"><span>Starter</span><h3>Managed Web Hosting</h3><p>For company websites, landing pages, blogs, and small business platforms.</p><ul class="d2g-list"><li>Domain and DNS support</li><li>SSL configuration</li><li>Email setup guidance</li><li>Backup recommendations</li></ul></article><article class="d2g-package d2g-featured"><span>Growth</span><h3>Business Hosting</h3><p>For websites that need stronger speed, uptime monitoring, and technical care.</p><ul class="d2g-list"><li>Migration support</li><li>Performance optimization</li><li>Security hardening</li><li>Monthly health checks</li></ul></article><article class="d2g-package"><span>Advanced</span><h3>Managed VPS</h3><p>For stores, portals, high-traffic sites, and businesses that need more control.</p><ul class="d2g-list"><li>VPS provisioning</li><li>Firewall and server hardening</li><li>Monitoring and update planning</li><li>Scalable resources</li></ul></article></div></div></section>
<section class="d2g-section d2g-dark"><div class="d2g-container d2g-feature-grid"><div><p class="d2g-eyebrow">Server care desk</p><h2>From DNS to deployment, your infrastructure is handled.</h2><p>Dev2Goo helps with server setup, email records, SSL, backups, migration, monitoring, and emergency troubleshooting.</p></div><div class="d2g-stack"><article><span>01</span><strong>Migration</strong><p>Move websites, databases, emails, and domains with a controlled launch checklist.</p></article><article><span>02</span><strong>Security</strong><p>Apply SSL, firewall rules, permissions, update routines, and access hygiene.</p></article><article><span>03</span><strong>Performance</strong><p>Optimize caching, compression, assets, server settings, and page delivery.</p></article></div></div></section>
<section class="d2g-band"><div class="d2g-container d2g-band-grid"><div><p class="d2g-eyebrow">Need hosting help?</p><h2>Ask for a hosting or VPS recommendation.</h2></div><a class="d2g-button-primary" href="/contact/">Discuss hosting</a></div></section>
HTML;
	}

	private function support_body() {
		return <<<'HTML'
<section class="d2g-page-hero"><div class="d2g-container d2g-page-hero-grid"><div><p class="d2g-eyebrow">Managed website support</p><h1>Keep your website secure, updated, fast, and ready every day.</h1><p>Your website should not stop after launch. Dev2Goo handles maintenance, urgent fixes, content edits, backups, monitoring, and continuous improvements.</p></div><div class="d2g-page-card"><span>Popular service</span><strong>Website Care Desk</strong><p>Monthly support for business websites, WordPress sites, eCommerce stores, and hosted platforms.</p></div></div></section>
<section class="d2g-section"><div class="d2g-container d2g-grid-3"><article class="d2g-panel"><h3>Maintenance</h3><p>Keep your website healthy with planned updates, compatibility checks, plugin reviews, theme updates, and routine care.</p><ul class="d2g-list"><li>CMS, plugin, and theme updates</li><li>Compatibility and visual checks</li><li>Monthly website health reports</li></ul></article><article class="d2g-panel d2g-featured"><h3>Emergency Fixes</h3><p>When something breaks, our support flow helps diagnose, prioritize, and resolve the issue quickly.</p><ul class="d2g-list"><li>Bug investigation</li><li>Downtime or error response</li><li>Restore from backups when needed</li></ul></article><article class="d2g-panel"><h3>Growth Support</h3><p>Make continuous improvements to campaigns, landing pages, speed, SEO, content, forms, and conversion paths.</p><ul class="d2g-list"><li>Content and page edits</li><li>Landing page improvements</li><li>Performance and SEO support</li></ul></article></div></section>
<section class="d2g-section d2g-soft"><div class="d2g-container"><div class="d2g-heading"><p class="d2g-eyebrow">Support coverage</p><h2>Everything your website team needs after launch.</h2></div><div class="d2g-grid-3"><article class="d2g-card"><h3>Security</h3><p>SSL checks, update routines, access hygiene, malware awareness, and safer admin practices.</p></article><article class="d2g-card"><h3>Backups</h3><p>Backup planning, restoration support, migration safety, and recovery workflows.</p></article><article class="d2g-card"><h3>Content</h3><p>Service edits, campaign pages, banners, forms, portfolio updates, and seasonal changes.</p></article><article class="d2g-card"><h3>Performance</h3><p>Speed reviews, image optimization guidance, caching, and technical cleanups.</p></article><article class="d2g-card"><h3>Integrations</h3><p>WhatsApp, analytics, pixels, CRM forms, email tools, and payment integrations.</p></article><article class="d2g-card"><h3>Reporting</h3><p>Clear summaries of what changed, what was fixed, and what should improve next.</p></article></div></div></section>
<section class="d2g-band"><div class="d2g-container d2g-band-grid"><div><p class="d2g-eyebrow">Need ongoing care?</p><h2>Ask for a support plan that fits your website.</h2></div><a class="d2g-button-primary" href="/contact/">Get support</a></div></section>
HTML;
	}

	private function about_body() {
		return <<<'HTML'
<section class="d2g-page-hero"><div class="d2g-container d2g-page-hero-grid"><div><p class="d2g-eyebrow">Why choose dev2goo?</p><h1>We turn digital requirements into reliable business systems.</h1><p>Dev2Goo is an integrated solutions provider in digital development, hosting services, support, and marketing production.</p></div><div class="d2g-page-card"><span>Our focus</span><strong>Expert team. Target fulfil. Complete digital care.</strong><p>We combine solution experts, full-stack developers, designers, support engineers, and marketing knowledge.</p></div></div></section>
<section class="d2g-section"><div class="d2g-container d2g-split"><div><p class="d2g-eyebrow">Our story</p><h2>Built for businesses that need more than a pretty website.</h2></div><p>We believe a strong digital presence needs design, development, infrastructure, support, and growth thinking working together. That is why Dev2Goo delivers websites, hosting, VPS, eCommerce, SEO, marketing, and technical support as connected services.</p></div></section>
<section class="d2g-section d2g-soft"><div class="d2g-container d2g-feature-grid"><div><p class="d2g-eyebrow">What makes us different</p><h2>Complete execution with clear communication.</h2><p>Our process is designed to make technical work easier for business owners and internal teams.</p></div><div class="d2g-grid-1"><article class="d2g-card"><h3>Expert Team</h3><p>Talented solution experts and full-stack developers focused on dependable delivery.</p></article><article class="d2g-card"><h3>Target Fulfil</h3><p>Every project is planned around business goals, client satisfaction, and measurable results.</p></article><article class="d2g-card"><h3>All-in-one Partner</h3><p>Design, build, host, maintain, optimize, and market your digital presence from one place.</p></article></div></div></section>
<section class="d2g-stats"><div class="d2g-container d2g-grid-4"><div><strong>120+</strong><span>Completed Projects</span></div><div><strong>95+</strong><span>Satisfied Clients</span></div><div><strong>12+</strong><span>Team Members</span></div><div><strong>180+</strong><span>Hosted Sites</span></div></div></section>
<section class="d2g-section"><div class="d2g-container"><div class="d2g-heading"><p class="d2g-eyebrow">Values</p><h2>The standards behind every delivery.</h2></div><div class="d2g-grid-3"><article class="d2g-card"><h3>Clarity</h3><p>Clear scope, clear pages, clear communication, and clear next steps.</p></article><article class="d2g-card"><h3>Reliability</h3><p>Stable code, stable hosting, careful launches, and practical support.</p></article><article class="d2g-card"><h3>Growth</h3><p>Every design and technical decision should help the business move forward.</p></article></div></div></section>
<section class="d2g-band"><div class="d2g-container d2g-band-grid"><div><p class="d2g-eyebrow">Work with the team</p><h2>Let Dev2Goo support your next digital move.</h2></div><a class="d2g-button-primary" href="/contact/">Contact us</a></div></section>
HTML;
	}

	private function contact_body() {
		return <<<'HTML'
<section class="d2g-contact-page"><div class="d2g-container d2g-contact-grid"><div><p class="d2g-eyebrow">Send message</p><h1>Tell us what you want to build, improve, host, or support.</h1><p>Whether you need a new website, a redesign, VPS hosting, ongoing support, eCommerce, SEO, or social media marketing, Dev2Goo can help you move with confidence.</p><div class="d2g-pills"><span>+20 01008616316</span><span>info@dev2goo.com</span><span>Road 18, Sarayat El Maadi, Cairo</span></div></div><form class="d2g-form"><input type="text" placeholder="Your name"><input type="email" placeholder="you@example.com"><input type="tel" placeholder="+20 ..."><select><option>Website development</option><option>Website support</option><option>Hosting or VPS</option><option>eCommerce</option><option>SEO and marketing</option><option>UI/UX design</option></select><textarea rows="5" placeholder="Tell us about your project"></textarea><button class="d2g-button-primary" type="submit">Send message</button></form></div></section>
HTML;
	}
}

Dev2Goo_Site::init();
