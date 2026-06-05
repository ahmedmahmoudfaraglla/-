<?php
/**
 * Plugin Name: Dev2Goo Demo Importer
 * Plugin URI: https://dev2goo.com/
 * Description: Imports the complete Dev2Goo Elementor demo pages, menus, and homepage settings.
 * Version: 1.0.0
 * Author: Dev2Goo
 * Author URI: https://dev2goo.com/
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: elementor
 * Text Domain: dev2goo-demo-importer
 *
 * @package Dev2Goo_Demo_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Dev2Goo_Demo_Importer {
	const NONCE_ACTION = 'd2g_import_demo';
	const MENU_SLUG    = 'dev2goo-main-menu';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_post_d2g_import_demo', array( $this, 'handle_import' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	public function admin_menu() {
		add_theme_page(
			__( 'Dev2Goo Demo Import', 'dev2goo-demo-importer' ),
			__( 'Dev2Goo Demo Import', 'dev2goo-demo-importer' ),
			'manage_options',
			'dev2goo-demo-import',
			array( $this, 'render_page' )
		);
	}

	public function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) || class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		$install_url = wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=elementor' ),
			'install-plugin_elementor'
		);

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'Dev2Goo Demo Importer works best with Elementor installed and active.', 'dev2goo-demo-importer' );
		echo ' <a href="' . esc_url( $install_url ) . '">' . esc_html__( 'Install Elementor', 'dev2goo-demo-importer' ) . '</a>';
		echo '</p></div>';
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$imported = isset( $_GET['d2g_imported'] ) ? sanitize_text_field( wp_unslash( $_GET['d2g_imported'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Dev2Goo Elementor Demo Import', 'dev2goo-demo-importer' ); ?></h1>
			<?php if ( 'yes' === $imported ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Demo imported successfully. Visit the homepage to review the site.', 'dev2goo-demo-importer' ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'This importer creates Home, Services, Hosting & VPS, Support, About, and Contact pages as Elementor editable pages, then assigns the homepage and menus.', 'dev2goo-demo-importer' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="d2g_import_demo">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<?php submit_button( __( 'Import Dev2Goo Demo', 'dev2goo-demo-importer' ), 'primary large' ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import this demo.', 'dev2goo-demo-importer' ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		$page_ids = $this->import_pages();
		$this->create_menu( $page_ids );

		if ( ! empty( $page_ids['home'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', (int) $page_ids['home'] );
		}

		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		wp_safe_redirect( add_query_arg( 'd2g_imported', 'yes', admin_url( 'themes.php?page=dev2goo-demo-import' ) ) );
		exit;
	}

	private function import_pages() {
		$pages    = $this->pages();
		$page_ids = array();

		foreach ( $pages as $slug => $page ) {
			$page_ids[ $slug ] = $this->upsert_page( $slug, $page['title'], $page['html'] );
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

		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $page_id, '_elementor_version', '3.0.0' );
		update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $this->elementor_data( $html ) ) ) );
		update_post_meta( $page_id, '_wp_page_template', 'default' );

		return (int) $page_id;
	}

	private function elementor_data( $html ) {
		return array(
			array(
				'id'       => $this->element_id(),
				'elType'   => 'section',
				'settings' => array(
					'layout'       => 'full_width',
					'_css_classes' => 'd2g-elementor-demo-section',
				),
				'elements' => array(
					array(
						'id'       => $this->element_id(),
						'elType'   => 'column',
						'settings' => array(
							'_column_size' => 100,
						),
						'elements' => array(
							array(
								'id'         => $this->element_id(),
								'elType'     => 'widget',
								'widgetType' => 'html',
								'settings'   => array(
									'html' => $html,
								),
								'elements'   => array(),
							),
						),
					),
				),
			),
		);
	}

	private function element_id() {
		return substr( md5( wp_generate_uuid4() ), 0, 7 );
	}

	private function create_menu( $page_ids ) {
		$menu = wp_get_nav_menu_object( self::MENU_SLUG );
		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( self::MENU_SLUG );
		} else {
			$menu_id = (int) $menu->term_id;
			$items   = wp_get_nav_menu_items( $menu_id );
			if ( $items ) {
				foreach ( $items as $item ) {
					wp_delete_post( $item->ID, true );
				}
			}
		}

		$labels = array(
			'home'     => 'Home',
			'services' => 'Services',
			'hosting'  => 'Hosting & VPS',
			'support'  => 'Support',
			'about'    => 'About',
			'contact'  => 'Start a project',
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
					'menu-item-classes'   => 'contact' === $slug ? array( 'menu-cta' ) : array(),
				)
			);
		}

		$locations            = get_theme_mod( 'nav_menu_locations', array() );
		$locations['primary'] = $menu_id;
		$locations['footer']  = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	private function pages() {
		return array(
			'home'     => array(
				'title' => 'Home',
				'html'  => $this->home_html(),
			),
			'services' => array(
				'title' => 'Services',
				'html'  => $this->services_html(),
			),
			'hosting'  => array(
				'title' => 'Hosting & VPS',
				'html'  => $this->hosting_html(),
			),
			'support'  => array(
				'title' => 'Website Support',
				'html'  => $this->support_html(),
			),
			'about'    => array(
				'title' => 'About',
				'html'  => $this->about_html(),
			),
			'contact'  => array(
				'title' => 'Contact',
				'html'  => $this->contact_html(),
			),
		);
	}

	private function home_html() {
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

	private function services_html() {
		return <<<'HTML'
<section class="d2g-page-hero"><div class="d2g-container d2g-page-hero-grid"><div><p class="d2g-eyebrow">World-class development services</p><h1>Digital services that look premium, work fast, and scale with your business.</h1><p>Dev2Goo creates websites, stores, interfaces, campaigns, and consulting systems with one connected delivery team.</p></div><div class="d2g-page-card"><span>Core promise</span><strong>Design + code + growth + support</strong><p>Everything is planned to help your customers understand, trust, and contact your business faster.</p></div></div></section>
<section class="d2g-section"><div class="d2g-container d2g-grid-3"><article class="d2g-card"><span class="d2g-number">01</span><h3>Web Development</h3><p>Professional websites, landing pages, company profiles, portals, dashboards, booking flows, and custom platforms.</p><ul class="d2g-list"><li>Responsive front-end layouts</li><li>CMS-ready content structure</li><li>Fast pages and clean technical SEO</li><li>Contact, WhatsApp, analytics, and CRM integrations</li></ul></article><article class="d2g-card"><span class="d2g-number">02</span><h3>eCommerce Solutions</h3><p>Online stores that help customers browse, buy, pay, and return with confidence.</p><ul class="d2g-list"><li>Product catalogs</li><li>Payment and shipping workflows</li><li>Promotions and coupons</li><li>Store analytics</li></ul></article><article class="d2g-card"><span class="d2g-number">03</span><h3>UI/UX Design</h3><p>Modern interfaces built around clarity, conversion, and a strong brand experience.</p><ul class="d2g-list"><li>Wireframes</li><li>Design systems</li><li>Premium hero sections</li><li>Mobile-first usability</li></ul></article><article class="d2g-card"><span class="d2g-number">04</span><h3>SEO & Digital Marketing</h3><p>Search-ready websites and campaigns that help your business gain visibility.</p><ul class="d2g-list"><li>Technical SEO</li><li>Metadata and sitemap readiness</li><li>Social campaign support</li><li>Advertising landing pages</li></ul></article><article class="d2g-card"><span class="d2g-number">05</span><h3>IT Consulting</h3><p>Practical guidance for choosing the right stack, hosting, security model, and workflow.</p><ul class="d2g-list"><li>Website audits</li><li>DNS, email, and hosting reviews</li><li>Migration planning</li><li>Automation recommendations</li></ul></article><article class="d2g-card d2g-featured"><span class="d2g-number">06</span><h3>Complete Digital Packages</h3><p>Bundle design, development, hosting, support, SEO, and marketing into one delivery plan.</p><a class="d2g-button-primary" href="/contact/">Build my package</a></article></div></section>
<section class="d2g-section d2g-soft"><div class="d2g-container"><div class="d2g-heading"><p class="d2g-eyebrow">Service tracks</p><h2>Pick the track that matches your next move.</h2></div><div class="d2g-grid-3"><article class="d2g-package"><h3>Launch</h3><p>For a new business website or landing page.</p><ul class="d2g-list"><li>Brand-aligned page design</li><li>Core company pages</li><li>Contact and analytics setup</li></ul></article><article class="d2g-package d2g-featured"><h3>Scale</h3><p>For companies that need stronger pages and growth systems.</p><ul class="d2g-list"><li>Advanced services structure</li><li>SEO-ready content architecture</li><li>Hosting, support, and optimization</li></ul></article><article class="d2g-package"><h3>Operate</h3><p>For businesses that need ongoing technical care.</p><ul class="d2g-list"><li>Maintenance and issue handling</li><li>Backups and security checks</li><li>Monthly improvements</li></ul></article></div></div></section>
<section class="d2g-band"><div class="d2g-container d2g-band-grid"><div><p class="d2g-eyebrow">Ready to build?</p><h2>Let us shape the right service plan for your business.</h2></div><a class="d2g-button-primary" href="/contact/">Request a proposal</a></div></section>
HTML;
	}

	private function hosting_html() {
		return <<<'HTML'
<section class="d2g-page-hero"><div class="d2g-container d2g-page-hero-grid"><div><p class="d2g-eyebrow">Hosting services and VPS hosting</p><h1>Fast, secure hosting and managed servers for serious websites.</h1><p>We configure, migrate, secure, and monitor hosting environments so your website has a stable foundation.</p></div><div class="d2g-server-visual"><div class="d2g-server-row"><i></i><strong>Web node</strong><em>Online</em></div><div class="d2g-server-row"><i></i><strong>Database</strong><em>Healthy</em></div><div class="d2g-server-row"><i></i><strong>Backups</strong><em>Synced</em></div><div class="d2g-server-row"><i></i><strong>SSL</strong><em>Active</em></div></div></div></section>
<section class="d2g-section"><div class="d2g-container"><div class="d2g-heading"><p class="d2g-eyebrow">Infrastructure options</p><h2>Hosting that matches your business stage.</h2><p>Start lean, upgrade confidently, and keep your website protected as traffic grows.</p></div><div class="d2g-grid-3"><article class="d2g-package"><span>Starter</span><h3>Managed Web Hosting</h3><p>For company websites, landing pages, blogs, and small business platforms.</p><ul class="d2g-list"><li>Domain and DNS support</li><li>SSL configuration</li><li>Email setup guidance</li><li>Backup recommendations</li></ul></article><article class="d2g-package d2g-featured"><span>Growth</span><h3>Business Hosting</h3><p>For websites that need stronger speed, uptime monitoring, and technical care.</p><ul class="d2g-list"><li>Migration support</li><li>Performance optimization</li><li>Security hardening</li><li>Monthly health checks</li></ul></article><article class="d2g-package"><span>Advanced</span><h3>Managed VPS</h3><p>For stores, portals, high-traffic sites, and businesses that need more control.</p><ul class="d2g-list"><li>VPS provisioning</li><li>Firewall and server hardening</li><li>Monitoring and update planning</li><li>Scalable resources</li></ul></article></div></div></section>
<section class="d2g-section d2g-dark"><div class="d2g-container d2g-feature-grid"><div><p class="d2g-eyebrow">Server care desk</p><h2>From DNS to deployment, your infrastructure is handled.</h2><p>Dev2Goo helps with server setup, email records, SSL, backups, migration, monitoring, and emergency troubleshooting.</p></div><div class="d2g-stack"><article><span>01</span><strong>Migration</strong><p>Move websites, databases, emails, and domains with a controlled launch checklist.</p></article><article><span>02</span><strong>Security</strong><p>Apply SSL, firewall rules, permissions, update routines, and access hygiene.</p></article><article><span>03</span><strong>Performance</strong><p>Optimize caching, compression, assets, server settings, and page delivery.</p></article></div></div></section>
<section class="d2g-band"><div class="d2g-container d2g-band-grid"><div><p class="d2g-eyebrow">Need hosting help?</p><h2>Ask for a hosting or VPS recommendation.</h2></div><a class="d2g-button-primary" href="/contact/">Discuss hosting</a></div></section>
HTML;
	}

	private function support_html() {
		return <<<'HTML'
<section class="d2g-page-hero"><div class="d2g-container d2g-page-hero-grid"><div><p class="d2g-eyebrow">Managed website support</p><h1>Keep your website secure, updated, fast, and ready every day.</h1><p>Your website should not stop after launch. Dev2Goo handles maintenance, urgent fixes, content edits, backups, monitoring, and continuous improvements.</p></div><div class="d2g-page-card"><span>Popular service</span><strong>Website Care Desk</strong><p>Monthly support for business websites, WordPress sites, eCommerce stores, and hosted platforms.</p></div></div></section>
<section class="d2g-section"><div class="d2g-container d2g-grid-3"><article class="d2g-panel"><h3>Maintenance</h3><p>Keep your website healthy with planned updates, compatibility checks, plugin reviews, theme updates, and routine care.</p><ul class="d2g-list"><li>CMS, plugin, and theme updates</li><li>Compatibility and visual checks</li><li>Monthly website health reports</li></ul></article><article class="d2g-panel d2g-featured"><h3>Emergency Fixes</h3><p>When something breaks, our support flow helps diagnose, prioritize, and resolve the issue quickly.</p><ul class="d2g-list"><li>Bug investigation</li><li>Downtime or error response</li><li>Restore from backups when needed</li></ul></article><article class="d2g-panel"><h3>Growth Support</h3><p>Make continuous improvements to campaigns, landing pages, speed, SEO, content, forms, and conversion paths.</p><ul class="d2g-list"><li>Content and page edits</li><li>Landing page improvements</li><li>Performance and SEO support</li></ul></article></div></section>
<section class="d2g-section d2g-soft"><div class="d2g-container"><div class="d2g-heading"><p class="d2g-eyebrow">Support coverage</p><h2>Everything your website team needs after launch.</h2></div><div class="d2g-grid-3"><article class="d2g-card"><h3>Security</h3><p>SSL checks, update routines, access hygiene, malware awareness, and safer admin practices.</p></article><article class="d2g-card"><h3>Backups</h3><p>Backup planning, restoration support, migration safety, and recovery workflows.</p></article><article class="d2g-card"><h3>Content</h3><p>Service edits, campaign pages, banners, forms, portfolio updates, and seasonal changes.</p></article><article class="d2g-card"><h3>Performance</h3><p>Speed reviews, image optimization guidance, caching, and technical cleanups.</p></article><article class="d2g-card"><h3>Integrations</h3><p>WhatsApp, analytics, pixels, CRM forms, email tools, and payment integrations.</p></article><article class="d2g-card"><h3>Reporting</h3><p>Clear summaries of what changed, what was fixed, and what should improve next.</p></article></div></div></section>
<section class="d2g-band"><div class="d2g-container d2g-band-grid"><div><p class="d2g-eyebrow">Need ongoing care?</p><h2>Ask for a support plan that fits your website.</h2></div><a class="d2g-button-primary" href="/contact/">Get support</a></div></section>
HTML;
	}

	private function about_html() {
		return <<<'HTML'
<section class="d2g-page-hero"><div class="d2g-container d2g-page-hero-grid"><div><p class="d2g-eyebrow">Why choose dev2goo?</p><h1>We turn digital requirements into reliable business systems.</h1><p>Dev2Goo is an integrated solutions provider in digital development, hosting services, support, and marketing production.</p></div><div class="d2g-page-card"><span>Our focus</span><strong>Expert team. Target fulfil. Complete digital care.</strong><p>We combine solution experts, full-stack developers, designers, support engineers, and marketing knowledge.</p></div></div></section>
<section class="d2g-section"><div class="d2g-container d2g-split"><div><p class="d2g-eyebrow">Our story</p><h2>Built for businesses that need more than a pretty website.</h2></div><p>We believe a strong digital presence needs design, development, infrastructure, support, and growth thinking working together. That is why Dev2Goo delivers websites, hosting, VPS, eCommerce, SEO, marketing, and technical support as connected services.</p></div></section>
<section class="d2g-section d2g-soft"><div class="d2g-container d2g-feature-grid"><div><p class="d2g-eyebrow">What makes us different</p><h2>Complete execution with clear communication.</h2><p>Our process is designed to make technical work easier for business owners and internal teams.</p></div><div class="d2g-grid-1"><article class="d2g-card"><h3>Expert Team</h3><p>Talented solution experts and full-stack developers focused on dependable delivery.</p></article><article class="d2g-card"><h3>Target Fulfil</h3><p>Every project is planned around business goals, client satisfaction, and measurable results.</p></article><article class="d2g-card"><h3>All-in-one Partner</h3><p>Design, build, host, maintain, optimize, and market your digital presence from one place.</p></article></div></div></section>
<section class="d2g-stats"><div class="d2g-container d2g-grid-4"><div><strong>120+</strong><span>Completed Projects</span></div><div><strong>95+</strong><span>Satisfied Clients</span></div><div><strong>12+</strong><span>Team Members</span></div><div><strong>180+</strong><span>Hosted Sites</span></div></div></section>
<section class="d2g-section"><div class="d2g-container"><div class="d2g-heading"><p class="d2g-eyebrow">Values</p><h2>The standards behind every delivery.</h2></div><div class="d2g-grid-3"><article class="d2g-card"><h3>Clarity</h3><p>Clear scope, clear pages, clear communication, and clear next steps.</p></article><article class="d2g-card"><h3>Reliability</h3><p>Stable code, stable hosting, careful launches, and practical support.</p></article><article class="d2g-card"><h3>Growth</h3><p>Every design and technical decision should help the business move forward.</p></article></div></div></section>
<section class="d2g-band"><div class="d2g-container d2g-band-grid"><div><p class="d2g-eyebrow">Work with the team</p><h2>Let Dev2Goo support your next digital move.</h2></div><a class="d2g-button-primary" href="/contact/">Contact us</a></div></section>
HTML;
	}

	private function contact_html() {
		return <<<'HTML'
<section class="d2g-contact-page"><div class="d2g-container d2g-contact-grid"><div><p class="d2g-eyebrow">Send message</p><h1>Tell us what you want to build, improve, host, or support.</h1><p>Whether you need a new website, a redesign, VPS hosting, ongoing support, eCommerce, SEO, or social media marketing, Dev2Goo can help you move with confidence.</p><div class="d2g-pills"><span>+20 01008616316</span><span>info@dev2goo.com</span><span>Road 18, Sarayat El Maadi, Cairo</span></div></div><form class="d2g-form"><input type="text" placeholder="Your name"><input type="email" placeholder="you@example.com"><input type="tel" placeholder="+20 ..."><select><option>Website development</option><option>Website support</option><option>Hosting or VPS</option><option>eCommerce</option><option>SEO and marketing</option><option>UI/UX design</option></select><textarea rows="5" placeholder="Tell us about your project"></textarea><button class="d2g-button-primary" type="submit">Send message</button></form></div></section>
HTML;
	}
}

new Dev2Goo_Demo_Importer();
