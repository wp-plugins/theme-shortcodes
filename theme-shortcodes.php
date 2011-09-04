<?php
/*
Plugin Name: Theme Shortcodes
Plugin URI: http://memberbuddy.com/docs/theme-shortcodes
Description: A collection of all handy theme related shortcodes, provided as a plugin so as to be theme agnostic
Author: Rob Holmes
Author URI: http://memberbuddy.com/people/rob
Version: 0.0.1
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
	
	// Force singelton - Technique by Aaron D. Campbell
	static $instance = false;
	public static function getInstance() {
		return (self::$instance ? self::$instance : self::$instance = new self);
	}

	private function __construct() {
		/* Add theme-specific shortcodes. */
		add_shortcode( 'the-year', array(&$this,'shortcode_the_year' ));
		add_shortcode( 'site-link',array(&$this, 'shortcode_site_link' ));
		add_shortcode( 'loginout-link', array(&$this,'shortcode_loginout_link' ));
		add_shortcode( 'query-counter',array(&$this, 'shortcode_query_counter' ));
		add_shortcode( 'nav-menu',array(&$this, 'shortcode_nav_menu' ));

		/* Add entry-specific shortcodes. */
		add_shortcode( 'entry-title', array(&$this,'shortcode_entry_title' ));
		add_shortcode( 'page-title', array(&$this,'shortcode_page_title' ));
		add_shortcode( 'entry-author-link', array(&$this,'shortcode_entry_author_link' ));
		add_shortcode( 'entry-author', array(&$this,'shortcode_entry_author' ));
		add_shortcode( 'entry-terms', array(&$this,'shortcode_entry_terms' ));
		add_shortcode( 'entry-comments-link', array(&$this,'shortcode_entry_comments_link' ));
		add_shortcode( 'entry-published', array(&$this,'shortcode_entry_published' ));
		add_shortcode( 'entry-edit-link', array(&$this,'shortcode_entry_edit_link' ));
		add_shortcode( 'entry-shortlink', array(&$this,'shortcode_entry_shortlink' ));
		add_shortcode( 'entry-link', array(&$this,'shortcode_entry_link' ));
		add_shortcode( 'entry-date', array(&$this,'shortcode_entry_date' ));
		add_shortcode( 'entry-cats', array(&$this,'shortcode_entry_cats' ));
		add_shortcode( 'entry-tags', array(&$this,'shortcode_entry_tags' ));
		 
		/* Add comment-specific shortcodes. */
		add_shortcode( 'comment-published', array(&$this,'shortcode_comment_published' ));
		add_shortcode( 'comment-author', array(&$this,'shortcode_comment_author' ));
		add_shortcode( 'comment-edit-link',array(&$this, 'shortcode_comment_edit_link' ));
		add_shortcode( 'comment-reply-link', array(&$this,'shortcode_comment_reply_link' ));
		add_shortcode( 'comment-permalink', array(&$this,'shortcode_comment_permalink' ));
		add_shortcode( 'comment-count', array(&$this,'shortcode_entry_comments_count' ));

		
		// List Pages Shortcodes Originally by Aaron Harp, Ben Huson http://www.aaronharp.com
		add_shortcode( 'child-pages',array(&$this, 'shortcode_list_pages' ));
		add_shortcode( 'sibling-pages', array(&$this,'shortcode_list_pages' ));
		add_shortcode( 'list-pages', array(&$this,'shortcode_list_pages' ));

	}

	
	
	// List Pages Shortcodes Originally by Aaron Harp, Ben Huson http://www.aaronharp.com
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


	
	
	// Shortcode to display the current year. - functionality provided by Justin Tadlock/Hybrid theme
	function shortcode_the_year() {
		return date( __( 'Y', $domian ) );
	}

	
	// Shortcode to display a link back to the site.  - functionality provided by Justin Tadlock/Hybrid theme
	function shortcode_site_link() {
		return '<a class="site-link" href="' . home_url() . '" title="' . esc_attr( get_bloginfo( 'name' ) ) . '" rel="home"><span>' . get_bloginfo( 'name' ) . '</span></a>';
	}


	
	// Shortcode to display a link to the theme page.  - functionality provided by Justin Tadlock/Hybrid theme
	function shortcode_theme_link() {
		$data = get_theme_data( trailingslashit( TEMPLATEPATH ) . 'style.css' );
		return '<a class="theme-link" href="' . esc_url( $data['URI'] ) . '" title="' . esc_attr( $data['Name'] ) . '"><span>' . esc_attr( $data['Name'] ) . '</span></a>';
	}


	// Shortcode to display a link to the child theme's page.  - functionality provided by Justin Tadlock/Hybrid theme
	function shortcode_child_link() {
		$data = get_theme_data( trailingslashit( STYLESHEETPATH ) . 'style.css' );
		return '<a class="child-link" href="' . esc_url( $data['URI'] ) . '" title="' . esc_attr( $data['Name'] ) . '"><span>' . esc_attr( $data['Name'] ) . '</span></a>';
	}
	
	// Shortcode to display a login link or logout link.  - functionality provided by Justin Tadlock/Hybrid theme
	function shortcode_loginout_link() {
		$domain = $domian;
		if ( is_user_logged_in() )
			$out = '<a class="logout-link" href="' . esc_url( wp_logout_url( site_url( $_SERVER['REQUEST_URI'] ) ) ) . '" title="' . esc_attr__( 'Log out of this account', $domain ) . '">' . __( 'Log out', $domain ) . '</a>';
		else
			$out = '<a class="login-link" href="' . esc_url( wp_login_url( site_url( $_SERVER['REQUEST_URI'] ) ) ) . '" title="' . esc_attr__( 'Log into this account', $domain ) . '">' . __( 'Log in', $domain ) . '</a>';

		return $out;
	}

	// Displays query count and load time if the current user can edit themes.  - functionality provided by Justin Tadlock/Hybrid theme
	function shortcode_query_counter() {
		if ( current_user_can( 'edit_themes' ) )
			$out = sprintf( __( 'This page loaded in %1$s seconds with %2$s database queries.', $domian ), timer_stop( 0, 3 ), get_num_queries() );
		return $out;
	}

	// Displays a nav menu that has been created from the Menus screen in the admin.  - functionality provided by Justin Tadlock/Hybrid theme
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
	}


	// Displays the edit link for an individual post.  - functionality provided by Justin Tadlock/Hybrid theme
	function shortcode_entry_edit_link( $attr ) {
		global $post;
		$domain = $domian;
		$post_type = get_post_type_object( $post->post_type );

		if ( !current_user_can( $post_type->cap->edit_post, $post->ID ) )
			return '';

		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );

		return $attr['before'] . '<span class="edit"><a class="post-edit-link" href="' . get_edit_post_link( $post->ID ) . '" title="' . sprintf( esc_attr__( 'Edit %1$s', $domain ), $post_type->labels->singular_name ) . '">' . __( 'Edit', $domain ) . '</a></span>' . $attr['after'];
	}

	// Displays the published date of an individual post.  - functionality provided by Justin Tadlock/Hybrid theme
	function shortcode_entry_published( $attr ) {
		$domain = $domian;
		$attr = shortcode_atts( array( 'before' => '', 'after' => '', 'format' => get_option( 'date_format' ) ), $attr );

		$published = '<span class="entry-published" title="' . sprintf( get_the_time( esc_attr__( 'l, F jS, Y, g:i a', $domain ) ) ) . '">' . sprintf( get_the_time( $attr['format'] ) ) . '</abbr>';
		return $attr['before'] . $published . $attr['after'];
	}

	// Shortcode entry date 
	function shortcode_entry_date( $attr ) {
		$domain = $domian;
		$attr = shortcode_atts( array( 'before' => '', 'after' => '', 'format' => get_option( 'date_format' ) ), $attr );

		$published = '<span class="entry-date" >' . sprintf( get_the_time( $attr['format'] ) ) . '</span>';
		return $attr['before'] . $published . $attr['after'];
	}





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
	}


	// Return The Comment Count
	function shortcode_entry_comments_count( $attr ) {

		$domain = $domian;
		$comments_link = '';
		$number = get_comments_number();
		return '<span class="comment-count">' . $number . " Comments" . "</span>";
	}

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
	}

	// Return the tags
	function shortcode_entry_tags($attr) {
		global $post;
		$tags = wp_get_post_tags($post->ID);

		$output = array();
		foreach ($tags as $tag) {
			$output[] = "<a href='" . $tag->slug . "' >" . $tag->name  . "</a>";
		}
		
		return '<span class="entry-tags">' . implode(', ',$output) . "</span>";
		
	}


	
	 // Displays an individual post's author with a link to his or her archive.
	function shortcode_entry_author_link( $attr ) {
		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
		$author = '<span class="entry-author-link"><a  href="' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '" title="' . esc_attr( get_the_author_meta( 'display_name' ) ) . '">' . get_the_author_meta( 'display_name' ) . '</a></span>';
		return $attr['before'] . $author . $attr['after'];
	}
	function shortcode_entry_author( $attr ) {
		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
		$author = '<span class="entry-author">' . get_the_author_meta( 'display_name' ) . '</span>';
		return $attr['before'] . $author . $attr['after']; 
	}
	
	
	// Entry Terms
	function shortcode_entry_terms( $attr ) {
		global $post;

		$attr = shortcode_atts( array( 'id' => $post->ID, 'taxonomy' => 'post_tag', 'separator' => ', ', 'before' => '', 'after' => '' ), $attr );

		$attr['before'] = ( empty( $attr['before'] ) ? '<span class="' . $attr['taxonomy'] . '">' : '<span class="' . $attr['taxonomy'] . '"><span class="before">' . $attr['before'] . '</span>' );
		$attr['after'] = ( empty( $attr['after'] ) ? '</span>' : '<span class="after">' . $attr['after'] . '</span></span>' );

		return get_the_term_list( $attr['id'], $attr['taxonomy'], $attr['before'], $attr['separator'], $attr['after'] );
	}

	// Page title
	function shortcode_page_title() {
		global $post;
		$title = get_the_title();
		return $title;
	}


	
	// Displays a post's title with a link to the post.
	function shortcode_entry_title() {
		global $post;

		if ( is_front_page() && !is_home() )
			$title = the_title( '<h2 class="' . esc_attr( $post->post_type ) . '-title entry-title"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h2>', false );

		elseif ( is_singular() )
			$title = the_title( '<h1 class="' . esc_attr( $post->post_type ) . '-title entry-title"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h1>', false );

		elseif ( 'link_category' == get_query_var( 'taxonomy' ) )
			$title = false;

		else
			$title = the_title( '<h2 class="entry-title"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h2>', false );

		/* If there's no post title, return a clickable '(No title)'. */
		if ( empty( $title ) && 'link_category' !== get_query_var( 'taxonomy' ) ) {

			if ( is_singular() )
				$title = '<h1 class="' . esc_attr( $post->post_type ) . '-title entry-title no-entry-title"><a href="' . get_permalink() . '" rel="bookmark">' . __( '(Untitled)', $domian ) . '</a></h1>';

			else
				$title = '<h2 class="entry-title no-entry-title"><a href="' . get_permalink() . '" rel="bookmark">' . __( '(Untitled)', $domian ) . '</a></h2>';
		}

		return $title;
	}

	
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

		return "{$attr['before']}<a class='shortlink' href='{$shortlink}' title='" . esc_attr( $attr['title'] ) . "' rel='shortlink'>{$attr['text']}</a>{$attr['after']}";
	}


	function shortcode_entry_link($attr) {
		global $post;
		return  get_permalink( $post->ID) ;
	}

	
	// Displays the published date and time of an individual comment.

	function shortcode_comment_published() {
		$domain = $domian;
		$link = '<span class="published">' . sprintf( __( '%1$s at %2$s', $domain ), '<abbr class="comment-date" title="' . get_comment_date( esc_attr__( 'l, F jS, Y, g:i a', $domain ) ) . '">' . get_comment_date() . '</abbr>', '<abbr class="comment-time" title="' . get_comment_date( esc_attr__( 'l, F jS, Y, g:i a', $domain ) ) . '">' . get_comment_time() . '</abbr>' ) . '</span>';
		return $link;
	}

	//Displays the comment author of an individual comment.

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
	}

	// Displays the permalink to an individual comment.
	function shortcode_comment_permalink( $attr ) {
		global $comment;

		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
		$domain = $domian;
		$link = '<a class="permalink" href="' . get_comment_link( $comment->comment_ID ) . '" title="' . sprintf( esc_attr__( 'Permalink to comment %1$s', $domain ), $comment->comment_ID ) . '">' . __( 'Permalink', $domain ) . '</a>';
		return $attr['before'] . $link . $attr['after'];
	}

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
	}

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
	}
		
}


	$wpthemeshortcodes = wpThemeShortcodes::getInstance();

?>