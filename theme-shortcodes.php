<?php
/*
Plugin Name: Theme Shortcodes
Plugin URI: http://memberbuddy.com/docs/theme-shortcodes
Description: A collection of all handy theme related shortcodes, provided as a plugin so as to be theme agnostic
Author: Rob Holmes
Author URI: http://memberbuddy.com/people/rob
Version: 0.0.4
Tags: Wordpress
License: GPL2

Copyright 2011  Rob Holmes (email : rob@onemanonelaptop.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

class wpThemeShortcodes {	
	
	static $domain = 'theme-shortcodes';
	
	// define some global flags
	public $plusone_flag = false;	// Has the plusone shortcode been used on this page
	public $twitter_flag = false;	// Has the twitter shortcode been used on this page
	public $like_flag = false;		// Has the facebook like shortcode been used on this page
	public $adsense_flag = false;	// has the adsense shortcode been used yet
	public $gmap_flag = false;		 // has the google map shortcode been used yet
	public $gmap_inline_script = ''; // Store the google maps inline javascript for the shortcodes
	public $views_flag = false;	// Has the views counter already been incremented on this page view
	public $original_tags = array();
	
	// All the shortcodes that we are defining
	public $shortcodes = array('the-year','site-link','site-login-link','query-counter','nav-menu','entry-title-link','entry-title','entry-author-link','entry-author','entry-terms','entry-comments-link',
	'entry-published','entry-edit-link','entry-shortlink','entry-url','entry-date','entry-cats','entry-tags','entry-views','comment-published','comment-author','comment-edit-link',
	'comment-reply-link','comment-permalink','comment-count','child-pages','sibling-pages','list-pages','category-group-posts','page-nav','chart','bloginfo','plus','like','tweet','esc','adsense','map');
	
	// Force singelton
	static $instance = false;
	public static function getInstance() {
		return (self::$instance ? self::$instance : self::$instance = new self);
	}

	// Construct
	private function __construct() {
		// load up the current settings
		$options = get_option('active_shortcodes');
		
		// Register the plugin options/settings
		add_action('admin_init',array(&$this,'shortcode_register_settings'));
	
		/* Add theme-specific shortcodes. */
		if ($options['the-year']) 			 { add_shortcode( 'the-year', array(&$this,'shortcode_the_year' )); }
		
		if ($options['site-link']) 			 { add_shortcode( 'site-link',array(&$this, 'shortcode_site_link' )); }
		if ($options['site-login-link']) 		 { add_shortcode( 'site-login-link', array(&$this,'shortcode_loginout_link' )); }
		if ($options['query-counter'])		 { add_shortcode( 'query-counter',array(&$this, 'shortcode_query_counter' )); }
		if ($options['nav-menu']) 			 { add_shortcode( 'nav-menu',array(&$this, 'shortcode_nav_menu' )); }

		/* Add entry-specific shortcodes. */
		if ($options['entry-title-link']) 	 { add_shortcode( 'entry-title-link', array(&$this,'shortcode_entry_title_link' )); }
		if ($options['entry-title']) 		 { add_shortcode( 'entry-title', array(&$this,'shortcode_entry_title' )); }
		if ($options['entry-author-link']) 	 { add_shortcode( 'entry-author-link', array(&$this,'shortcode_entry_author_link' )); }
		if ($options['entry-author']) 		 { add_shortcode( 'entry-author', array(&$this,'shortcode_entry_author' )); }
		if ($options['entry-terms'])		 { add_shortcode( 'entry-terms', array(&$this,'shortcode_entry_terms' )); }
		if ($options['entry-comments-link']) { add_shortcode( 'entry-comments-link', array(&$this,'shortcode_entry_comments_link' )); }
		if ($options['entry-published']) 	 { add_shortcode( 'entry-published', array(&$this,'shortcode_entry_published' )); }
		if ($options['entry-edit-link']) 	 { add_shortcode( 'entry-edit-link', array(&$this,'shortcode_entry_edit_link' )); }
		if ($options['entry-shortlink'])	 { add_shortcode( 'entry-shortlink', array(&$this,'shortcode_entry_shortlink' )); }
		if ($options['entry-url']) 			 { add_shortcode( 'entry-url', array(&$this,'shortcode_entry_url' )); }
		if ($options['entry-date']) 		 { add_shortcode( 'entry-date', array(&$this,'shortcode_entry_date' )); }
		if ($options['entry-cats']) 		 { add_shortcode( 'entry-cats', array(&$this,'shortcode_entry_cats' )); }
		if ($options['entry-tags']) 		 { add_shortcode( 'entry-tags', array(&$this,'shortcode_entry_tags' )); }
		
		// Views counter
		if ($options['entry-views']) { 
			add_shortcode( 'entry-views', array(&$this,'shortcode_entry_views' ));
			add_filter('the_content', array(&$this,'shortcode_entry_views_filter_the_content'));
		}
		
		/* Add comment-specific shortcodes. */
		if ($options['comment-published']) 	 { add_shortcode( 'comment-published', array(&$this,'shortcode_comment_published' ));   }
		if ($options['comment-author']) 	 { add_shortcode( 'comment-author', array(&$this,'shortcode_comment_author' ));			}
		if ($options['comment-edit-link']) 	 { add_shortcode( 'comment-edit-link',array(&$this, 'shortcode_comment_edit_link' ));	}
		if ($options['comment-reply-link'])  { add_shortcode( 'comment-reply-link', array(&$this,'shortcode_comment_reply_link' )); }
		if ($options['comment-permalink']) 	 { add_shortcode( 'comment-permalink', array(&$this,'shortcode_comment_permalink' )); 	}
		if ($options['comment-count']) 		 { add_shortcode( 'comment-count', array(&$this,'shortcode_entry_comments_count' )); 	}

		// List Pages Shortcodes by Aaron Harp, Ben Huson http://www.aaronharp.com
		if ($options['child-pages']) 	 { add_shortcode( 'child-pages',array(&$this, 'shortcode_list_pages' )); }
		if ($options['sibling-pages']) 	 { add_shortcode( 'sibling-pages', array(&$this,'shortcode_list_pages' )); }
		if ($options['list-pages']) 	 { add_shortcode( 'list-pages', array(&$this,'shortcode_list_pages' )); }
		
		// Other shortcodes
		if ($options['category-group-posts']) 	 { add_shortcode( 'category-group-posts', array(&$this,'shortcode_category_group_posts' )); }
		//add_shortcode( 'category-group-category', array(&$this,'shortcode_category_group_category' ));
	
		// Navigation
		if ($options['page-nav']) 	 { add_shortcode( 'page-nav', array(&$this,'shortcode_page_nav' )); }
		
		// Google Charts
		if ($options['chart']) 	 { add_shortcode('chart', array(&$this,'shortcode_chart')); }
		
		// Blog Information
		if ($options['bloginfo']) 	 { add_shortcode('bloginfo', array(&$this,'shortcode_bloginfo')); }
		
		// google plus one
		if ($options['plus']) 	 { 
			add_shortcode('plus', array(&$this,'shortcode_plusone'));
			add_action('init', array(&$this, 'shortcode_plusone_register_script'));
			add_action('wp_footer', array(&$this, 'shortcode_plusone_print_script'));	
		}
		
		// facebook like
		if ($options['like']) 	 {
			add_shortcode('like',  array(&$this,'shortcode_like'));
			add_action('init', array(&$this, 'shortcode_like_register_script'));
			add_action('wp_footer', array(&$this, 'shortcode_like_print_script'));	
		}
		
		
		// Twitter Tweet
		if ($options['tweet']) 	 {
			add_shortcode('tweet',  array(&$this,'shortcode_twitter'));
			add_action('init', array(&$this, 'shortcode_twitter_register_script'));
			add_action('wp_footer', array(&$this, 'shortcode_twitter_print_script'));	
		}
		
		// Escape a shortcode
		if ($options['esc']) 	 { add_shortcode('esc',  array(&$this,'shortcode_escape')); }
		
		// Adsense
		if ($options['adsense']) 	 { 
			add_shortcode('adsense', array(&$this,'shortcode_adsense'));
			add_action('init', array(&$this, 'shortcode_adsense_register_script'));
			add_action('wp_footer', array(&$this, 'shortcode_adsense_print_script'));
		}
		
		// Google maps
		if ($options['map']) 	 { 
			add_shortcode('map', array(&$this,'shortcode_gmap'));		
			add_action('init', array(&$this, 'shortcode_gmap_register_script'));
			add_action('wp_head', array(&$this,'shortcode_gmap_print_style')); 
			add_action('wp_footer', array(&$this, 'shortcode_gmap_print_script'));
		}

		// Turn shortcodes on when activated
		register_activation_hook( __FILE__, array(&$this, 'shortcode_activation') );

	}

	// Turn all the shortcodes on when the plugin is activated
	function shortcode_activation() {
		$options = array();
		foreach ($this->shortcodes as $key => $value) {
			$options[$value] = 1;
		}
		update_option('active_shortcodes',$options);
	} // function
		
	// Register some options on the writing page
	function shortcode_register_settings() {
		add_settings_section('shortcodes_setting_section', 'Shortcodes', array(&$this,'shortcode_settings_section_callback'), 'writing');
		add_settings_field('active_shortcodes',	'Enable / Disable Shortcodes', array(&$this,'shortcode_settings_field_callback'), 'writing', 'shortcodes_setting_section');
		register_setting('writing','active_shortcodes');
	} // function
 
	// Section callback 
	function shortcode_settings_section_callback() {
		echo 'Below are all the shortcodes that were defined by the <em><a href="http://wordpress.org/extend/plugins/theme-shortcodes/">Theme Shortcodes Plugin</a></em>. You have the option of disabling any shortcodes you are not using. You can read more about the available options for each of the shortcodes <a href="http://memberbuddy.com/docs/theme-shortcodes" target="_blank">here</a>.';
	} // function
	 
	// List the checkboxes
	function shortcode_settings_field_callback() {
		$options = get_option('active_shortcodes');
		foreach ($this->shortcodes as $key => $value) {
			echo '<div style="width:170px; float:left;"><input name="active_shortcodes[' . $value . ']"  type="checkbox" value="1" class="code" ' . checked( 1, $options[$value], false ) . ' />' . " [" . $value . "] </div>";
		}
	} // function
	
	// Category group posts
	function shortcode_category_group_posts($atts, $content, $tag) {
		global $post;	
		$defaults = array(
			'type'                     => 'post',
			'child_of'                 => 0,
			'parent'                   => 0,
			'orderby'                  => 'name',
			'order'                    => 'ASC',
			'hide_empty'               => 1,
			'hierarchical'             => 0,
			'exclude'                  => '',
			'include'                  => '',
			'number'                   => '',
			'more' 					   => 'more...',
			'columns' 				   => 2,
			'taxonomy'                 => 'category',
			'separator'				   => ', ',
			'pad_counts'               => false );
		
		// Merge user provided atts with defaults
		$atts = shortcode_atts( $defaults, $atts );
		
		$categories=  get_categories($atts); 
		
		// Go through each of the categories one by one
		$counter = 0;
		$output = '';
		$current_column = 0;
		
		foreach ($categories as $category) {
			
			$build = '<div class="category-group-entry"><div class="category-group-category"><a href="' . get_category_link( $category->cat_ID ) . '">' . $category->cat_name . '</a> <span class="category-group-count">('. $category->category_count .')</span></div>';
			$build_items = array();
			
			// For each category that exists get the posts in that category
			$args = array( 'numberposts' => 5, 'offset'=> 0, 'category' => $category->cat_ID );
			$myposts = get_posts( $args );
			foreach( $myposts as $post ) {
				setup_postdata($post); 
				$build_items[] = '<span class="category-group-post"><a href="' .  get_permalink() . '">' . get_the_title() . '</a></span>';
			}
			$build .= implode( $atts['separator'] ,$build_items);
			$build .= ' <a href="' . get_category_link( $category->cat_ID ) . '">' . $atts['more'] . '</a>';
			$build .= '</div>'; // close the wrapping div
			
			$counter++;
			
			// If the counter is divisible by the column number then increment the counter
			if ( $counter % $atts['columns']) { $current_column++; }
			
			// Wrap the counter back to zero after reaching max columsn
			if ($current_column >= $atts['columns']) { $current_column = 0; }
			
			$output[$current_column] .= $build;
			
		}
		
		$final = '';
		$final .= '<table class="category-group"><tr>';
			foreach ($output as $html) {
				$final .=  '<td>' . $html. '</td>';
			}
		
		$final .=  '</tr></table>';
		return $final;	
	} // function
	
	// List Pages Shortcode based on code by Aaron Harp, Ben Huson http://www.aaronharp.com
	function shortcode_list_pages( $atts, $content, $tag ) {
		global $post;
		
		// Child Pages
		$child_of = 0;
		if ( $tag == 'child-pages' )
			$child_of = $post->ID;
		if ( $tag == 'sibling-pages' )
			$child_of = $post->post_parent;
		
		// Set defaults
		$defaults = array(
			'class'       => $tag,
			'depth'       => 0,
			'show_date'   => '',
			'date_format' => get_option( 'date_format' ),
			'exclude'     => '',
			'include'     => '',
			'child_of'    => $child_of,
			'title_li'    => '',
			'authors'     => '',
			'sort_column' => 'menu_order, post_title',
			'sort_order'  => '',
			'link_before' => '',
			'link_after'  => '',
			'exclude_tree'=> '',
			'meta_key'    => '',
			'meta_value'  => '',
			'offset'      => '',
			'exclude_current_page' => 0
		);
		
		// Merge user provided atts with defaults
		$atts = shortcode_atts( $defaults, $atts );
		
		// Set necessary params
		$atts['echo'] = 0;
		if ( $atts['exclude_current_page'] && absint( $post->ID ) ) {
			if ( !empty( $atts['exclude'] ) )
				$atts['exclude'] .= ',';
			$atts['exclude'] .= $post->ID;
		}
		
		$atts = apply_filters( 'shortcode_list_pages_attributes', $atts, $content, $tag );
		
		// Create output
		$out = wp_list_pages( $atts );
		if ( !empty( $out ) )
			$out = '<ul class="' . $atts['class'] . '">' . $out . '</ul>';
		
		return apply_filters( 'shortcode_list_pages', $out, $atts, $content, $tag );	
	} // function

	// Shortcode to display the current year based on code by Justin Tadlock/Hybrid theme
	function shortcode_the_year() {
		return date( __( 'Y', $domian ) );
	} // function

	// Shortcode to display a link back to the site based on code by Justin Tadlock/Hybrid theme
	function shortcode_site_link() {
		return '<a class="site-link" href="' . home_url() . '" title="' . esc_attr( get_bloginfo( 'name' ) ) . '" rel="home"><span>' . get_bloginfo( 'name' ) . '</span></a>';
	} // function

	// Shortcode to display a link to the theme page based on code by Justin Tadlock/Hybrid theme
	function shortcode_theme_link() {
		$data = get_theme_data( trailingslashit( TEMPLATEPATH ) . 'style.css' );
		return '<a class="theme-link" href="' . esc_url( $data['URI'] ) . '" title="' . esc_attr( $data['Name'] ) . '"><span>' . esc_attr( $data['Name'] ) . '</span></a>';
	} // function

	// Shortcode to display a link to the child theme's page based on code by Justin Tadlock/Hybrid theme
	function shortcode_child_link() {
		$data = get_theme_data( trailingslashit( STYLESHEETPATH ) . 'style.css' );
		return '<a class="child-link" href="' . esc_url( $data['URI'] ) . '" title="' . esc_attr( $data['Name'] ) . '"><span>' . esc_attr( $data['Name'] ) . '</span></a>';
	} // function
	
	// Shortcode to display a login link or logout link based on code by Justin Tadlock/Hybrid theme
	function shortcode_loginout_link() {
		$domain = $domian;
		if ( is_user_logged_in() )
			$out = '<a class="logout-link" href="' . esc_url( wp_logout_url( site_url( $_SERVER['REQUEST_URI'] ) ) ) . '" title="' . esc_attr__( 'Log out of this account', $domain ) . '">' . __( 'Log out', $domain ) . '</a>';
		else
			$out = '<a class="login-link" href="' . esc_url( wp_login_url( site_url( $_SERVER['REQUEST_URI'] ) ) ) . '" title="' . esc_attr__( 'Log into this account', $domain ) . '">' . __( 'Log in', $domain ) . '</a>';

		return $out;
	} // function

	// Displays query count and load time if the current user can edit themes. Based on code by Justin Tadlock/Hybrid theme
	function shortcode_query_counter() {
		if ( current_user_can( 'edit_themes' ) )
			$out = sprintf( __( 'This page loaded in %1$s seconds with %2$s database queries.', $domian ), timer_stop( 0, 3 ), get_num_queries() );
		return $out;
	} // function

	// Displays a nav menu that has been created from the Menus screen in the admin. Based on code by Justin Tadlock/Hybrid theme
	function shortcode_nav_menu( $attr ) {
		$attr = shortcode_atts(
			array(
				'menu' => '',
				'container' => 'div',
				'container_id' => '',
				'container_class' => 'nav-menu',
				'menu_id' => '',
				'menu_class' => '',
				'link_before' => '',
				'link_after' => '',
				'before' => '',
				'after' => '',
				'fallback_cb' => 'wp_page_menu',
				'walker' => ''
			),
			$attr
		);
		$attr['echo'] = false;
		return wp_nav_menu( $attr );
	} // function

	// Displays the edit link for an individual post. Based on code by Justin Tadlock/Hybrid theme
	function shortcode_entry_edit_link( $attr ) {
		global $post;
		$domain = $domian;
		$post_type = get_post_type_object( $post->post_type );

		if ( !current_user_can( $post_type->cap->edit_post, $post->ID ) )
			return '';

		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );

		return $attr['before'] . '<span class="entry-edit"><a class="entry-edit-link" href="' . get_edit_post_link( $post->ID ) . '" title="' . sprintf( esc_attr__( 'Edit %1$s', $domain ), $post_type->labels->singular_name ) . '">' . __( 'Edit', $domain ) . '</a></span>' . $attr['after'];
	} // function

	// Displays the published date of an individual post.  Based on code by Justin Tadlock/Hybrid theme
	function shortcode_entry_published( $attr ) {
		$domain = $domian;
		$attr = shortcode_atts( array( 'before' => '', 'after' => '', 'format' => get_option( 'date_format' ) ), $attr );

		$published = '<span class="entry-published" title="' . sprintf( get_the_time( esc_attr__( 'l, F jS, Y, g:i a', $domain ) ) ) . '">' . sprintf( get_the_time( $attr['format'] ) ) . '</abbr>';
		return $attr['before'] . $published . $attr['after'];
	} // function

	// Shortcode entry date 
	function shortcode_entry_date( $attr ) {
		$domain = $domian;
		$attr = shortcode_atts( array( 'before' => '', 'after' => '', 'format' => get_option( 'date_format' ) ), $attr );
		$published = '<span class="entry-date" >' . sprintf( get_the_time( $attr['format'] ) ) . '</span>';
		return $attr['before'] . $published . $attr['after'];
	} // function

	// Displays a post's number of comments wrapped in a link to the comments area.
	function shortcode_entry_comments_link( $attr ) {

		$domain = $domian;
		$comments_link = '';
		$number = get_comments_number();
		$attr = shortcode_atts( array( 'zero' => __( 'Leave a response', $domain ), 'one' => __( '%1$s Response', $domain ), 'more' => __( '%1$s Responses', $domain ), 'css_class' => 'comments-link', 'none' => '', 'before' => '', 'after' => '' ), $attr );

		if ( 0 == $number && !comments_open() && !pings_open() ) {
			if ( $attr['none'] )
				$comments_link = '<span class="' . esc_attr( $attr['css_class'] ) . '">' . sprintf( $attr['none'], number_format_i18n( $number ) ) . '</span>';
		}
		elseif ( 0 == $number )
			$comments_link = '<a class="' . esc_attr( $attr['css_class'] ) . '" href="' . get_permalink() . '#respond" title="' . sprintf( esc_attr__( 'Comment on %1$s', $domain ), the_title_attribute( 'echo=0' ) ) . '">' . sprintf( $attr['zero'], number_format_i18n( $number ) ) . '</a>';
		elseif ( 1 == $number )
			$comments_link = '<a class="' . esc_attr( $attr['css_class'] ) . '" href="' . get_comments_link() . '" title="' . sprintf( esc_attr__( 'Comment on %1$s', $domain ), the_title_attribute( 'echo=0' ) ) . '">' . sprintf( $attr['one'], number_format_i18n( $number ) ) . '</a>';
		elseif ( 1 < $number )
			$comments_link = '<a class="' . esc_attr( $attr['css_class'] ) . '" href="' . get_comments_link() . '" title="' . sprintf( esc_attr__( 'Comment on %1$s', $domain ), the_title_attribute( 'echo=0' ) ) . '">' . sprintf( $attr['more'], number_format_i18n( $number ) ) . '</a>';

		if ( $comments_link )
			$comments_link = $attr['before'] . $comments_link . $attr['after'];

		return $comments_link;
	} // function



	// Return The Comment Count
	function shortcode_entry_comments_count( $attr ) {
		$domain = $domian;
		$comments_link = '';
		$number = get_comments_number();
		return '<span class="comment-count">' . $number . " Comments" . "</span>";
	} // function

	// Return the categories
	function shortcode_entry_cats_shortcode ($attr) {
		global $post;
		$post_categories = wp_get_post_categories( $post->ID );
		$cats = array();
			
		foreach($post_categories as $c){
			$cat = get_category( $c );
			$cats[] = array( 'id' => $c, 'name' => $cat->name, 'slug' => $cat->slug );
		}

		$output = array();
		foreach ($cats as $cat) {
			$output[] = "<a href='" . get_category_link( $cat['id']  ) . "' >" . $cat['name']  . "</a>";
		}
		
		return '<span class="entry-cats">' . implode(', ',$output) . "</span>";
	} // function

	// Return the tags
	function shortcode_entry_tags($attr) {
		global $post;
		$tags = wp_get_post_tags($post->ID);

		$output = array();
		foreach ($tags as $tag) {
			$output[] = "<a href='" . $tag->slug . "' >" . $tag->name  . "</a>";
		}
		return '<span class="entry-tags">' . implode(', ',$output) . "</span>";
	} // function

	 // Displays an individual post's author with a link to his or her archive.
	function shortcode_entry_author_link( $attr ) {
		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
		$author = '<span class="entry-author-link"><a  href="' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '" title="' . esc_attr( get_the_author_meta( 'display_name' ) ) . '">' . get_the_author_meta( 'display_name' ) . '</a></span>';
		return $attr['before'] . $author . $attr['after'];
	} // function
	
	// Displays just the authors name
	function shortcode_entry_author( $attr ) {
		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
		$author = '<span class="entry-author">' . get_the_author_meta( 'display_name' ) . '</span>';
		return $attr['before'] . $author . $attr['after']; 
	} // function
	
	// Entry Terms
	function shortcode_entry_terms( $attr ) {
		global $post;

		$attr = shortcode_atts( array( 'id' => $post->ID, 'taxonomy' => 'post_tag', 'separator' => ', ', 'before' => '', 'after' => '' ), $attr );

		$attr['before'] = ( empty( $attr['before'] ) ? '<span class="' . $attr['taxonomy'] . '">' : '<span class="' . $attr['taxonomy'] . '"><span class="before">' . $attr['before'] . '</span>' );
		$attr['after'] = ( empty( $attr['after'] ) ? '</span>' : '<span class="after">' . $attr['after'] . '</span></span>' );

		return get_the_term_list( $attr['id'], $attr['taxonomy'], $attr['before'], $attr['separator'], $attr['after'] );
	} // function

	// Page title
	function shortcode_entry_title() {
		global $post;
		$title = get_the_title();
		return $title;
	} // function

	// Displays a post's title with a link to the post.
	function shortcode_entry_title_link() {
		global $post;

		if ( is_front_page() && !is_home() )
			$title = the_title( '<h2 class="' . esc_attr( $post->post_type ) . '-title entry-title-link"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h2>', false );

		elseif ( is_singular() )
			$title = the_title( '<h1 class="' . esc_attr( $post->post_type ) . '-title entry-title-link"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h1>', false );

		elseif ( 'link_category' == get_query_var( 'taxonomy' ) )
			$title = false;

		else
			$title = the_title( '<h2 class="entry-title-link"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h2>', false );

		/* If there's no post title, return a clickable '(No title)'. */
		if ( empty( $title ) && 'link_category' !== get_query_var( 'taxonomy' ) ) {

			if ( is_singular() )
				$title = '<h1 class="' . esc_attr( $post->post_type ) . '-title entry-title no-entry-title"><a href="' . get_permalink() . '" rel="bookmark">' . __( '(Untitled)', $domian ) . '</a></h1>';

			else
				$title = '<h2 class="entry-title no-entry-title"><a href="' . get_permalink() . '" rel="bookmark">' . __( '(Untitled)', $domian ) . '</a></h2>';
		}

		return $title;
	} // function

	
	// Displays the shortlinke of an individual entry.
	function shortcode_entry_shortlink( $attr ) {
		global $post;
		$attr = shortcode_atts(
			array(
				'text' => __( 'Shortlink', $domian ),
				'title' => the_title_attribute( array( 'echo' => false ) ),
				'before' => '',
				'after' => ''
			),
			$attr
		);
		$shortlink = wp_get_shortlink( $post->ID );
		return "{$attr['before']}<a class='entry-shortlink' href='{$shortlink}' title='" . esc_attr( $attr['title'] ) . "' rel='shortlink'>{$attr['text']}</a>{$attr['after']}";
	} // function

	// returns just the permalink
	function shortcode_entry_url($attr) {
		global $post;
		return  get_permalink( $post->ID) ;
	} // function

	
	// Displays the published date and time of an individual comment.
	function shortcode_comment_published() {
		$domain = $domian;
		$link = '<span class="comment-published">' . sprintf( __( '%1$s at %2$s', $domain ), '<abbr class="comment-date" title="' . get_comment_date( esc_attr__( 'l, F jS, Y, g:i a', $domain ) ) . '">' . get_comment_date() . '</abbr>', '<abbr class="comment-time" title="' . get_comment_date( esc_attr__( 'l, F jS, Y, g:i a', $domain ) ) . '">' . get_comment_time() . '</abbr>' ) . '</span>';
		return $link;
	} // function

	// Displays the comment author of an individual comment.
	function shortcode_comment_author( $attr ) {
		global $comment;

		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );

		$author = esc_html( get_comment_author( $comment->comment_ID ) );
		$url = esc_url( get_comment_author_url( $comment->comment_ID ) );

		/* Display link and cite if URL is set. Also, properly cites trackbacks/pingbacks. */
		if ( $url )
			$output = '<cite class="fn" title="' . $url . '"><a href="' . $url . '" title="' . $author . '" class="url" rel="external nofollow">' . $author . '</a></cite>';
		else
			$output = '<cite class="fn">' . $author . '</cite>';

		$output = '<div class="comment-author vcard">' . $attr['before'] . apply_filters( 'get_comment_author_link', $output ) . $attr['after'] . '</div><!-- .comment-author .vcard -->';

		/* @deprecated 0.8. Create a custom shortcode instead of filtering shortcode_comment_author. */
		return apply_filters( shortcode_get_prefix() . '_comment_author', $output );
	} // function  

	// Displays the permalink to an individual comment.
	function shortcode_comment_permalink( $attr ) {
		global $comment;

		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
		$domain = $domian;
		$link = '<a class="permalink" href="' . get_comment_link( $comment->comment_ID ) . '" title="' . sprintf( esc_attr__( 'Permalink to comment %1$s', $domain ), $comment->comment_ID ) . '">' . __( 'Permalink', $domain ) . '</a>';
		return $attr['before'] . $link . $attr['after'];
	} // function  

	// Displays a comment's edit link to users that have the capability to edit the comment.
	function shortcode_comment_edit_link( $attr ) {
		global $comment;

		$edit_link = get_edit_comment_link( $comment->comment_ID );

		if ( !$edit_link )
			return '';

		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
		$domain = $domian;

		$link = '<a class="comment-edit-link" href="' . $edit_link . '" title="' . sprintf( esc_attr__( 'Edit %1$s', $domain ), $comment->comment_type ) . '"><span class="edit">' . __( 'Edit', $domain ) . '</span></a>';
		$link = apply_filters( 'edit_comment_link', $link, $comment->comment_ID );

		return $attr['before'] . $link . $attr['after'];
	} // function  

	// Displays a reply link for the 'comment' comment_type if threaded comments are enabled.
	function shortcode_comment_reply_link( $attr ) {
		$domain = $domian;

		if ( !get_option( 'thread_comments' ) || 'comment' !== get_comment_type() )
			return '';

		$defaults = array(
			'reply_text' => __( 'Reply', $domain ),
			'login_text' => __( 'Log in to reply.', $domain ),
			'depth' => $GLOBALS['comment_depth'],
			'max_depth' => get_option( 'thread_comments_depth' ),
			'before' => '',
			'after' => ''
		);
		$attr = shortcode_atts( $defaults, $attr );

		return get_comment_reply_link( $attr );
	} // function  

	
	function shortcode_page_nav($atts) { 
		global $post;
		
		$defaults = array(
			'prev' => '&laquo; Newer Entries',
			'next' => 'Older Entries &raquo;'
		);
		
		// Merge user provided atts with defaults
		$atts = shortcode_atts( $defaults, $atts );
		
		if (function_exists('wp_pagenavi') ) { 
			wp_pagenavi();
		} else {   
			if ( get_next_posts_link() || get_previous_posts_link() ) { ?>
			
				<div class="page-nav">
					<div class="page-nav-prev fl"><?php previous_posts_link(__($atts['prev'], $this->domain)) ?></div>
					<div class="page-nav-next fr"><?php next_posts_link(__($atts['next'], $this->domain)) ?></div>
				</div>	
			
			<?php } 
		}   
	} // function  



	// Google charts integration - http://blue-anvil.com/archives/8-fun-useful-shortcode-functions-for-wordpress/
	function shortcode_chart( $atts ) {
		extract(shortcode_atts(array(
			'data' => '',
			'colors' => '',
			'size' => '400x200',
			'bg' => 'ffffff',
			'title' => '',
			'labels' => '',
			'advanced' => '',
			'type' => 'pie'
		), $atts));

		switch ($type) {
			case 'line' :
				$charttype = 'lc'; break;
			case 'xyline' :
				$charttype = 'lxy'; break;
			case 'sparkline' :
				$charttype = 'ls'; break;
			case 'meter' :
				$charttype = 'gom'; break;
			case 'scatter' :
				$charttype = 's'; break;
			case 'venn' :
				$charttype = 'v'; break;
			case 'pie' :
				$charttype = 'p3'; break;
			case 'pie2d' :
				$charttype = 'p'; break;
			default :
				$charttype = $type;
			break;
		}

		if ($title) $string .= '&chtt='.$title.'';
		if ($labels) $string .= '&chl='.$labels.'';
		if ($colors) $string .= '&chco='.$colors.'';
		$string .= '&chs='.$size.'';
		$string .= '&chd=t:'.$data.'';
		$string .= '&chf='.$bg.'';

		return '<img class="google-chart" title="'.$title.'" src="http://chart.apis.google.com/chart?cht='.$charttype.''.$string.$advanced.'" alt="'.$title.'" />';
	} // function

	
	
	// Blog Info - http://blue-anvil.com/archives/8-fun-useful-shortcode-functions-for-wordpress/
	function bloginfo_shortcode( $atts ) {
		$defaults = array(
			'key' => '',
		);
		$atts = shortcode_atts( $defaults, $atts );
		
		return get_bloginfo($key);
	} // function


	
	
	// Google Plus One Shortcode
	function shortcode_plusone( $atts, $content=null ){
		$this->plusone_flag = true;
	 
		extract(shortcode_atts(array(
				'url' => '',
				'lang' => 'en-US',
				'parsetags' => 'onload',
				'count' => 'false',
				'size' => 'medium',
				'callback' => '',
		 
				), $atts));

			// Check for $content and set to URL if not provided
			if($content != null) $url = $content;
			$plus1_code = '<div class="g-plusone entry-plus" data-href="' . $url . '" data-count="' . $count . '" data-size="' . $size . '" data-callback="' . $callback . '"  ></div>';
	 
		return $plus1_code;
	} // function
 
 
 	// Register the twitter script
	function shortcode_plusone_register_script() { } // function

	// Print the twitter script only if the shortcode was used
	function shortcode_plusone_print_script() {
		if($this->plusone_flag){
			echo <<<HTML
	 
			<script type="text/javascript">
			  (function() {
				var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
				po.src = 'https://apis.google.com/js/plusone.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
			  })();
			</script>
HTML;
		}
	} // function
 
 
 
 
 


	// Facebook like button
	function shortcode_like( $atts, $content=null ){
		// set the flag if the shortcode is used
		$this->like_flag = true;
	
		extract(shortcode_atts(array(
				'send' => 'false',
				'layout' => 'standard',
				'show_faces' => 'true',
				'width' => '400px',
				'action' => 'like',
				'font' => '',
				'colorscheme' => 'light',
				'ref' => '',
				'locale' => 'en_US',
				'appId' => '' // Put your AppId here is you have one
		), $atts));
	
		// set the global locale variable
		$this->likelocale = $locale;
		$this->likeappId = $appId;
		
    $output = <<<HTML
        <fb:like ref="$ref" href="$content" layout="$layout" colorscheme="$colorscheme" action="$action" send="$send" width="$width" show_faces="$show_faces" font="$font"></fb:like>
HTML;
 
		return $output;
	} // function

	function shortcode_like_register_script() {}
	
	// Add the facebook like javascript if the shortcode was used
	function shortcode_like_print_script() {
		wp_register_script('like','http://connect.facebook.net/' . $this->likelocale . '/all.js#appId=' . $this->likeappId . '&amp;xfbml=1');
		if ($this->like_flag) {
			wp_print_scripts('like'); 
		} else {
			return;
		}
	} // function
	
	
	
	// Based on code by Nicholas P. Iler -- http://www.ilertech.com/2011/07/add-twitter-share-button-to-wordpress-3-0-with-a-simple-shortcode/
	function shortcode_twitter( $atts, $content=null ) {
		// set the flag so we know it has been used
		$this->twitter_flag = true;
		
		// extract the defaults
		extract(shortcode_atts(array(
			'url' => null,
			'counturl' => null,
			'via' => '',
			'text' => '',
			'related' => '',
			'countbox' => 'none', // none, horizontal, vertical
	 
		), $atts));
 
		// Check for count url and set to $url if not provided
		if($counturl == null) $counturl = $url;
	 
		// build the html
		$output = <<<HTML
	<a href="http://twitter.com/share" class="entry-tweet"
		data-url="$url"
		data-counturl="$counturl"
		data-via="$via"
		data-text="$text"
		data-related="$related"
		data-count="$countbox"></a>
HTML;
	 
		return $output;
	} // function
	
	// Register the twitter script
	function shortcode_twitter_register_script() {
			wp_register_script('twitterwidgets','http://platform.twitter.com/widgets.js');	
	} // function

	// Print the twitter script only if the shortcode was used
	function shortcode_twitter_print_script() {
		if ($this->twitter_flag) {
			wp_print_scripts('twitterwidgets'); 
		} else {
			return;
		}
	}
	
	

	// HTML Special Chars shortcode
	function shortcode_escape( $atts, $content=null ){
			$output = htmlentities($content);
			return $output;
	} // function
 
 
 
	// Add the google adsense script to the site
	function shortcode_adsense_register_script() {
			wp_register_script('adsense','http://pagead2.googlesyndication.com/pagead/show_ads.js');	
	} // function
 
	// adsense shortcode
	function shortcode_adsense( $atts ) {
		$this->adsense_flag = true;
        extract(shortcode_atts(array(
                'ad_client' => '',
                'ad_slot' => '',
                'width' => '',
                'height' => '',
        ), $atts));
        
		$return .='<script type="text/javascript"><!--'. "\n";
		$return .='google_ad_client = "' . $ad_client . '"'. "\n";
		$return .='google_ad_slot = "' . $ad_slot . '"'. "\n";
		$return .='google_ad_width = ' . $width . ''. "\n";
		$return .='google_ad_height = ' . $height . ''. "\n";
		$return .='//-->'. "\n";
		$return .='</script>'. "\n";

		return $return;
	} // function

	// adsense scripts
	function shortcode_adsense_print_script() {
		if (!$this->adsense_flag) { return; }
		wp_print_scripts('adsense'); 
	} // function
	
	
	
	
	// output the entry views
	function shortcode_entry_views() {
		global $post;
		$count = get_post_meta($post->ID, 'entry_views_count', true);
		if($count==''){
			delete_post_meta($post->ID, 'entry_views_count');
			add_post_meta($post->ID, 'entry_views_count', '0');
			$count = 0;
		}
		return '<span class="entry-views">' . $count . ' Views</span>';
	}
	
	// attach the increment counter to the_content filter
	function shortcode_entry_views_filter_the_content($content ) {

		if  (!$this->views_flag) {
			$this->shortcode_increment_entry_views(get_the_ID());
		}
		$this->views_flag = true;
		return $content;
	} // function

	// increment the views counter
	function shortcode_increment_entry_views($postID) {
		$count_key = 'entry_views_count';
		$count = get_post_meta($postID, $count_key, true);
		if($count==''){
			$count = 0;
			delete_post_meta($postID, $count_key);
			add_post_meta($postID, $count_key, '0');
		}else{
			$count++;
			update_post_meta($postID, $count_key, $count);
		}
	} // function
	 

	
	
	
	// **********************************************************************************************
	
	


	// Add the image fix for google maps only if the shortcode has been used
	function shortcode_gmap_print_style() {
		if  ($this->gmap_flag) {
		?>
		<style type="text/css">
			.entry-content img {max-width: 100000%; /* override */}
		</style> 
		<?php
		}
	} // function

	// insert the script for google maps
	function shortcode_gmap_register_script() {
		wp_register_script('gmap','http://maps.google.com/maps/api/js?sensor=false');		
	} // function

	// gmap scripts
	function shortcode_gmap_print_script() {
		if (!$this->gmap_flag) { return; }
		wp_print_scripts('gmap'); 
		print $this->gmap_inline_script;
	} // function

	
	
	
	// Based on code by http://gis.yohman.com/gmaps-plugin/
	function shortcode_gmap($attr) {
		$this->gmap_flag = true;
		// default atts
		$attr = shortcode_atts(array(	
										'lat'   => '0', 
										'lon'    => '0',
										'id' => 'map',
										'zoom' => '1',
										'width' => '400',
										'height' => '300',
										'maptype' => 'ROADMAP',
										'address' => '',
										'kml' => '',
										'kmlautofit' => 'yes',
										'marker' => '',
										'markerimage' => '',
										'traffic' => 'no',
										'bike' => 'no',
										'fusion' => '',
										'start' => '',
										'end' => '',
										'text' => '',
										'infowindowdefault' => 'yes',
										'directions' => '',
										'hidecontrols' => 'false',
										'scale' => 'false',
										'scrollwheel' => 'true'
										
										), $attr);
										
		$attr['id'] .=  md5(serialize($attr)) ;

		$returnme = '
		<div id="' .$attr['id'] . '"  style="width:' . $attr['width'] . 'px;height:' . $attr['height'] . 'px;"></div>
		';
		
		//directions panel
		if($attr['start'] != '' && $attr['end'] != '') 
		{
			$panelwidth = $attr['width']-20;
			$returnme .= '
			<div id="directionsPanel" style="width:' . $panelwidth . 'px;height:' . $attr['height'] . 'px;border:1px solid gray;padding:10px;overflow:auto;"></div><br>
			';
		}


		$this->gmap_inline_script .= '
		

		<script type="text/javascript">

			var latlng = new google.maps.LatLng(' . $attr['lat'] . ', ' . $attr['lon'] . ');
			var myOptions = {
				zoom: ' . $attr['zoom'] . ',
				center: latlng,
				scrollwheel: ' . $attr['scrollwheel'] .',
				scaleControl: ' . $attr['scale'] .',
				disableDefaultUI: ' . $attr['hidecontrols'] .',
				mapTypeId: google.maps.MapTypeId.' . $attr['maptype'] . '
			};
			var ' . $attr['id'] . ' = new google.maps.Map(document.getElementById("' . $attr['id'] . '"),
			myOptions);
			';
					
			//kml
			if($attr['kml'] != '') 
			{
				if($attr['kmlautofit'] == 'no') 
				{
					$this->gmap_inline_script .= '
					var kmlLayerOptions = {preserveViewport:true};
					';
				}
				else
				{
					$this->gmap_inline_script .= '
					var kmlLayerOptions = {preserveViewport:false};
					';
				}
				$returnme .= '
				var kmllayer = new google.maps.KmlLayer(\'' . html_entity_decode($attr['kml']) . '\',kmlLayerOptions);
				kmllayer.setMap(' . $attr['id'] . ');
				';
			}

			//directions
			if($attr['start'] != '' && $attr['end'] != '') 
			{
				$this->gmap_inline_script .= '
				var directionDisplay;
				var directionsService = new google.maps.DirectionsService();
				directionsDisplay = new google.maps.DirectionsRenderer();
				directionsDisplay.setMap(' . $attr['id'] . ');
				directionsDisplay.setPanel(document.getElementById("directionsPanel"));

					var start = \'' . $attr['start'] . '\';
					var end = \'' . $attr['end'] . '\';
					var request = {
						origin:start, 
						destination:end,
						travelMode: google.maps.DirectionsTravelMode.DRIVING
					};
					directionsService.route(request, function(response, status) {
						if (status == google.maps.DirectionsStatus.OK) {
							directionsDisplay.setDirections(response);
						}
					});


				';
			}
			
			//traffic
			if($attr['traffic'] == 'yes')
			{
				$this->gmap_inline_script .= '
				var trafficLayer = new google.maps.TrafficLayer();
				trafficLayer.setMap(' . $attr['id'] . ');
				';
			}
		
			//bike
			if($attr['bike'] == 'yes')
			{
				$this->gmap_inline_script .= '			
				var bikeLayer = new google.maps.BicyclingLayer();
				bikeLayer.setMap(' . $attr['id'] . ');
				';
			}
			
			//fusion tables
			if($attr['fusion'] != '')
			{
				$this->gmap_inline_script .= '			
				var fusionLayer = new google.maps.FusionTablesLayer(' . $attr['fusion'] . ');
				fusionLayer.setMap(' . $attr['id'] . ');
				';
			}
		
			//address
			if($attr['address'] != '')
			{
				$this->gmap_inline_script .= '
				var geocoder_' . $attr['id'] . ' = new google.maps.Geocoder();
				var address = \'' . $attr['address'] . '\';
				geocoder_' . $attr['id'] . '.geocode( { \'address\': address}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						' . $attr['id'] . '.setCenter(results[0].geometry.location);
						';
						
						if ($attr['marker'] !='')
						{
							//add custom image
							if ($attr['markerimage'] !='')
							{
								$this->gmap_inline_script .= 'var image = "'. $attr['markerimage'] .'";';
							}
							$this->gmap_inline_script.= '
							var marker = new google.maps.Marker({
								map: ' . $attr['id'] . ', 
								';
								if ($attr['markerimage'] !='')
								{
									$returnme .= 'icon: image,';
								}
							$this->gmap_inline_script .= '
								position: ' . $attr['id'] . '.getCenter()
							});
							';

							//infowindow
							if($attr['text'] != '') 
							{
								//first convert and decode html chars
								$thiscontent = htmlspecialchars_decode($attr['text']);
								$this->gmap_inline_script .= '
								var contentString = \'' . $thiscontent . '\';
								var infowindow = new google.maps.InfoWindow({
									content: contentString
								});
											
								google.maps.event.addListener(marker, \'click\', function() {
								  infowindow.open(' . $attr['id'] . ',marker);
								});
								';

								//infowindow default
								if ($attr['infowindowdefault'] == 'yes')
								{
									$this->gmap_inline_script .= '
										infowindow.open(' . $attr['id'] . ',marker);
									';
								}
							}
						}
				$this->gmap_inline_script .= '
					} else {
					alert("Geocode was not successful for the following reason: " + status);
				}
				});
				';
			}

			//marker: show if address is not specified
			if ($attr['marker'] != '' && $attr['address'] == '')
			{
				//add custom image
				if ($attr['markerimage'] !='')
				{
					$this->gmap_inline_script .= 'var image = "'. $attr['markerimage'] .'";';
				}

				$this->gmap_inline_script .= '
					var marker = new google.maps.Marker({
					map: ' . $attr['id'] . ', 
					';
					if ($attr['markerimage'] !='')
					{
						$returnme .= 'icon: image,';
					}
				$this->gmap_inline_script .= '
					position: ' . $attr['id'] . '.getCenter()
				});
				';

				//infowindow
				if($attr['text'] != '') 
				{
					$this->gmap_inline_script .= '
					var contentString = \'' . $attr['text'] . '\';
					var infowindow = new google.maps.InfoWindow({
						content: contentString
					});
								
					google.maps.event.addListener(marker, \'click\', function() {
					  infowindow.open(' . $attr['id'] . ',marker);
					});
					';
					//infowindow default
					if ($attr['infowindowdefault'] == 'yes')
					{
						$this->gmap_inline_script .= '
							infowindow.open(' . $attr['id'] . ',marker);
						';
					}				
				}
			}

		$this->gmap_inline_script .= '</script>';
			
			return $returnme;

	} // function

	
	

}


	$wpthemeshortcodes = wpThemeShortcodes::getInstance();

?>