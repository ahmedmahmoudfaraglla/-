<?php
/**
 * Main template file.
 *
 * @package Dev2Goo_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="d2g-section">
	<div class="d2g-container">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
		endif;
		?>
	</div>
</section>
<?php
get_footer();
