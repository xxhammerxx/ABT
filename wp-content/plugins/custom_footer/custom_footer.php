<?php

/*
	Plugin Name: Pete's Awesome footer
	Version: 1
	Plugin URI: 
	Description: This plugin creates a custom footer to be attached to the footer of the site
	Author: Peter Petersen.
	Author URI: 
	
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
//plugin settings

//this adds a custom menu to the theme

function pp_child_register_my_menu() { 


		
	  register_nav_menu('custom-footer-menu',__( 'Custom Footer Menu' )); 

	 }

add_action( 'init', 'pp_child_register_my_menu' );




//This adds code to the footer
add_action('wp_footer', 'pp_add_custom_footer_to_child');




function pp_add_custom_footer_to_child(){ ?>


<div class="custom-footer">

	<div class="footer-left">
		<h3>Policies &amp; Procedures</h3>

	</div>

	<div class="footer-middle">
		<h3>Site Map</h3>
<?php //this line of code add the footer nav to the footer ?>
<?php wp_nav_menu( array( 'theme_location' => 'custom-footer-menu' ) ); ?>

	
	</div>

	<div class="footer-right">
		<h3>&copy;&#32;Petersen</h3>

	</div>


</div>

<?php
}

?>