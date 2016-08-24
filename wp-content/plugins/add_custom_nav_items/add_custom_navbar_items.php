<?php

/*
	Plugin Name: Pete's Awesome custom nav items
	Version: 1
	Plugin URI: 
	Description: This plugin creates a custom menu items for the primary menu
	Author: Peter Petersen.
	Author URI: 
	
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
//plugin settings

	add_filter('wp_nav_menu_items','pp_add_search_box_to_menu', 10, 2);

	function pp_add_search_box_to_menu( $items, $args ) {

	    if( $args->theme_location == 'header_menu' )

	        return $items.
	    "<li class='menu-header-search'>
	    	
	    	<section id='navbar-search'>
     
	    	<form action='http://localhost/wordpress/' id='searchform' method='get'>

	    	<label for='search-input'>
	    		<i class='fa fa-search' aria-hidden='true'></i>
	    	</label>

	    	<input type='text' id='search-input' name='s' id='s' placeholder='Search'>

	    	

	    	</form>

	    	 </section>

	    	</li>";

 

	    return $items;

	}


//Adds cart link to menu

		add_filter('wp_nav_menu_items','pp_add_cart_to_menu', 10, 2);

	function pp_add_cart_to_menu( $items, $args ) {

	    if( $args->theme_location == 'header_menu' )

	        return $items.

	    "<li id='menu-item-cart' class='menu-item menu-item-type-post_type menu-item-object-page'>

	  		  <a href=\"http://localhost/wordpress/cart\"'><i class='fa fa-shopping-cart' aria-hidden='true'></i>
</a>

	    </li>";

 

	    return $items;

	}