<?php
	// Loading child theme textdomain
	load_child_theme_textdomain( CURRENT_THEME, get_stylesheet_directory() . '/languages' );

	// WP Pointers
	add_action('admin_enqueue_scripts', 'myHelpPointers');
	function myHelpPointers() {
	//First we define our pointers 
	$pointers = array(
	   	array(
	       'id' => 'xyz1',   // unique id for this pointer
	       'screen' => 'options-permalink', // this is the page hook we want our pointer to show on
	       'target' => '#submit', // the css selector for the pointer to be tied to, best to use ID's
	       'title' => theme_locals("submit_permalink"),
	       'content' => theme_locals("submit_permalink_desc"),
	       'position' => array( 
	                          'edge' => 'top', //top, bottom, left, right
	                          'align' => 'left', //top, bottom, left, right, middle
	                          'offset' => '0 5'
	                          )
	       ),

	    array(
	       'id' => 'xyz2',   // unique id for this pointer
	       'screen' => 'themes', // this is the page hook we want our pointer to show on
	       'target' => '#toplevel_page_options-framework', // the css selector for the pointer to be tied to, best to use ID's
	       'title' => theme_locals("import_sample_data"),
	       'content' => theme_locals("import_sample_data_desc"),
	       'position' => array( 
	                          'edge' => 'bottom', //top, bottom, left, right
	                          'align' => 'top', //top, bottom, left, right, middle
	                          'offset' => '0 -10'
	                          )
	       ),

	    array(
	       'id' => 'xyz3',   // unique id for this pointer
	       'screen' => 'toplevel_page_options-framework', // this is the page hook we want our pointer to show on
	       'target' => '#toplevel_page_options-framework', // the css selector for the pointer to be tied to, best to use ID's
	       'title' => theme_locals("import_sample_data"),
	       'content' => theme_locals("import_sample_data_desc_2"),
	       'position' => array( 
	                          'edge' => 'left', //top, bottom, left, right
	                          'align' => 'top', //top, bottom, left, right, middle
	                          'offset' => '0 18'
	                          )
	       )
	    // more as needed
	    );
		//Now we instantiate the class and pass our pointer array to the constructor 
		$myPointers = new WP_Help_Pointer($pointers); 
	};

/*-----------------------------------------------------------------------------------*/
/* Breadcrumbs
/*-----------------------------------------------------------------------------------*/
if ( !function_exists( 'breadcrumbs' ) ) {
	function breadcrumbs() {

	$showOnHome  = 1; // 1 - show "breadcrumbs" on home page, 0 - hide
	$delimiter   = '<li class="divider">&thinsp;|&thinsp;</li>'; // divider
	$home        = get_the_title( get_option('page_on_front', true) ); // text for link "Home"
	$showCurrent = 1; // 1 - show title current post/page, 0 - hide
	$before      = '<li class="active">'; // open tag for active breadcrumb
	$after       = '</li>'; // close tag for active breadcrumb

	global $post;
	$homeLink = home_url();

	if (is_front_page()) {
		if ($showOnHome == 1) 
			echo '<ul class="breadcrumb breadcrumb__t"><li><a href="' . $homeLink . '">' . $home . '</a><li></ul>';
		} else {
			echo '<ul class="breadcrumb breadcrumb__t"><li><a href="' . $homeLink . '">' . $home . '</a></li>' . $delimiter;

			if ( is_home() ) {
				$blog_text = of_get_option('blog_text');
				if ($blog_text == '' || empty($blog_text)) {
					echo theme_locals("blog");
				}
				echo $before . $blog_text . $after;
			} 
			elseif ( is_category() ) {
				$thisCat = get_category(get_query_var('cat'), false);
				if ($thisCat->parent != 0) echo get_category_parents($thisCat->parent, TRUE, ' ' . $delimiter . ' ');
				echo $before . theme_locals("category_archives").': "' . single_cat_title('', false) . '"' . $after;
			} 
			elseif ( is_search() ) {
				echo $before . theme_locals("fearch_for") . ': "' . get_search_query() . '"' . $after;
			} 
			elseif ( is_day() ) {
				echo '<li><a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a></li> ' . $delimiter . ' ';
				echo '<li><a href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '">' . get_the_time('F') . '</a></li> ' . $delimiter . ' ';
				echo $before . get_the_time('d') . $after;
			} 
			elseif ( is_month() ) {
				echo '<li><a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a></li> ' . $delimiter . ' ';
				echo $before . get_the_time('F') . $after;
			} 
			elseif ( is_year() ) {
				echo $before . get_the_time('Y') . $after;
			}
			elseif ( is_tax(get_post_type().'_category') ) {
				$post_name = get_post_type();
				echo $before . ucfirst($post_name) . ' ' . theme_locals('category') . ': ' . single_cat_title( '', false ) . $after;
			}
			elseif ( is_single() && !is_attachment() ) {
				if ( get_post_type() != 'post' ) {
					$post_id = get_the_ID();
					$post_name = get_post_type();
					$post_type = get_post_type_object(get_post_type());
					// echo '<li><a href="' . $homeLink . '/' . $post_type->labels->name . '/">' . $post_type->labels->name . '</a></li>';

					$terms = get_the_terms( $post_id, $post_name.'_category');
					if ( $terms && ! is_wp_error( $terms ) ) {
						echo '<li><a href="' .get_term_link(current($terms)->slug, $post_name.'_category') .'">'.current($terms)->name.'</a></li>';
						echo ' ' . $delimiter . ' ';
					} else {
						// echo '<li><a href="' . $homeLink . '/' . $post_type->labels->name . '/">' . $post_type->labels->name . '</a></li>';
					}

					if ($showCurrent == 1)
						echo $before . get_the_title() . $after;
				} else {
					$cat = get_the_category();
					if (!empty($cat)) {
						$cat  = $cat[0];
						$cats = get_category_parents($cat, TRUE, '</li>' . $delimiter . '<li>');
						if ($showCurrent == 0) 
							$cats = preg_replace("#^(.+)\s$delimiter\s$#", "$1", $cats);
						echo '<li>' . substr($cats, 0, strlen($cats)-4);
					}
					if ($showCurrent == 1) 
						echo $before . get_the_title() . $after;
				}
			}
			elseif ( !is_single() && !is_page() && get_post_type() != 'post' && !is_404() ) {
				$post_type = get_post_type_object(get_post_type());
				if ( isset($post_type) ) {
					echo $before . $post_type->labels->singular_name . $after;
				}
			} 
			elseif ( is_attachment() ) {
				$parent = get_post($post->post_parent);
				$cat    = get_the_category($parent->ID);
				if ( isset($cat) && !empty($cat)) {
					$cat    = $cat[0];
					echo get_category_parents($cat, TRUE, ' ' . $delimiter . ' ');
					echo '<li><a href="' . get_permalink($parent) . '">' . $parent->post_title . '</a></li>';
				}
				if ($showCurrent == 1) 
					echo $before . get_the_title() . $after;
			} 
			elseif ( is_page() && !$post->post_parent ) {
				if ($showCurrent == 1) 
					echo $before . get_the_title() . $after;
			} 
			elseif ( is_page() && $post->post_parent ) {
				$parent_id  = $post->post_parent;
				$breadcrumbs = array();
				while ($parent_id) {
					$page          = get_page($parent_id);
					$breadcrumbs[] = '<li><a href="' . get_permalink($page->ID) . '">' . get_the_title($page->ID) . '</a></li>';
					$parent_id     = $page->post_parent;
				}
				$breadcrumbs = array_reverse($breadcrumbs);
				for ($i = 0; $i < count($breadcrumbs); $i++) {
					echo $breadcrumbs[$i];
					if ($i != count($breadcrumbs)-1) echo ' ' . $delimiter . ' ';
				}
				if ($showCurrent == 1) 
					echo ' ' . $delimiter . ' ' . $before . get_the_title() . $after;
			} 
			elseif ( is_tag() ) {
				echo $before . theme_locals("tag_archives") . ': "' . single_tag_title('', false) . '"' . $after;
			} 
			elseif ( is_author() ) {
				global $author;
				$userdata = get_userdata($author);
				echo $before . theme_locals("by") . ' ' . $userdata->display_name . $after;
			} 
			elseif ( is_404() ) {
				echo $before . '404' . $after;
			}
			echo '</ul>';
		}
	} // end breadcrumbs()
}
require_once('theme_shortcodes/wrap.php');
require_once('theme_shortcodes/posts_grid.php');
require_once('theme_shortcodes/posts_list.php');
require_once('theme_shortcodes/shortcodes.php');
require_once('theme_shortcodes/mini_posts_list.php');
require_once('theme_shortcodes/banner.php');
?>