<?php
/**
 * The site footer.
 *
 * @package Dev2Goo_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<footer class="site-footer">
	<div class="site-container footer-grid">
		<a class="brand footer-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr__( 'Dev2Goo home', 'dev2goo-elementor' ); ?>">
			<span class="brand-text">
				<strong>dev<span>2</span>goo</strong>
				<small>masters code</small>
			</span>
		</a>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'footer-menu',
				'fallback_cb'    => false,
			)
		);
		?>
		<p class="copyright">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Dev2Goo. <?php esc_html_e( 'All rights reserved.', 'dev2goo-elementor' ); ?></p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
