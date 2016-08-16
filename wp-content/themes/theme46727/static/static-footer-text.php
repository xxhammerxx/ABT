<?php /* Static Name: Footer text */ ?>
<div id="footer-text" class="footer-text">
	<?php $myfooter_text = of_get_option('footer_text'); ?>
	
	<?php if($myfooter_text){?>
		<?php echo of_get_option('footer_text'); ?>
	<?php } else { ?>
		<a href="<?php echo home_url(); ?>/" title="<?php bloginfo('description'); ?>" class="site-name"><?php bloginfo('name'); ?></a> <?php echo theme_locals("powered_by"); ?> <a href="http://wordpress.org">WordPress</a> <a href="<?php if ( of_get_option('feed_url') != '' ) { echo of_get_option('feed_url'); } else bloginfo('rss2_url'); ?>" rel="nofollow" title="<?php echo theme_locals('entries_rss'); ?>"><?php echo theme_locals("entries_rss"); ?></a> and <a href="<?php bloginfo('comments_rss2_url'); ?>" rel="nofollow"><?php echo theme_locals("comments_rss"); ?></a>
	<?php } ?>
	<?php if( is_front_page() ) { ?>
		More Car Repair WordPress Themes at <a rel="nofollow" href="http://www.templatemonster.com/category/car-repair-wordpress-themes/" target="_blank">TemplateMonster.com</a>
	<?php } ?>
</div>