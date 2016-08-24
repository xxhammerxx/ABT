<?php


/*
	Plugin Name: Add custom css and script files
	Version: 1
	Plugin URI: 
	Description: This plugin add custom css and scripts
	Author: Peter Petersen.
	Author URI: 
	
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
//plugin settings




// Load HTML5 Blank styles
function pp_enqueue_custom_styles()
{

    wp_register_style('fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css', array(), '1.0', 'all');
    wp_enqueue_style('fontawesome'); // Enqueue it!
    
}



add_action('wp_enqueue_scripts', 'pp_enqueue_custom_styles'); // Add Theme Stylesheet

?>