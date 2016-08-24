<?php get_header(); ?>

<div class="motopress-wrapper content-holder clearfix">
	<div class="container">
		<div class="row">
			<div class="<?php echo cherry_get_layout_class( 'full_width_content' ); ?>" data-motopress-wrapper-file="page.php" data-motopress-wrapper-type="content">
				<?php woocommerce_content(); ?>
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>