<?php
/**
 *
 *
 */

// Fon
function wrap_shortcode($atts, $content = null) {
	
	$output = '<div class="wrap">';
	$output .= do_shortcode($content);
	$output .= '</div> <!-- fon (end) -->';
   
	return $output;
}
add_shortcode('wrap', 'wrap_shortcode');

function fon_wrap_shortcode($atts, $content = null) {
	
	$output = '<div class="fon-wrap">';
	$output .= do_shortcode($content);
	$output .= '</div> <!-- fon-wrap (end) -->';
   
	return $output;
}
add_shortcode('fon_wrap', 'fon_wrap_shortcode');

?>