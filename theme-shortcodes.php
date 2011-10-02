<?php
/*
Plugin Name: Theme Shortcodes
Plugin URI: https://github.com/OneManOneLaptop/theme-shortcodes
Description: A collection of all handy theme related shortcodes, provided as a plugin so as to be theme agnostic
Author: Rob Holmes
Author URI: http://onemanonelaptop.com
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

/**
 * This class contains the shorcode definitions
 *
 * @package theme-shortcodes
 * @since 0.1
 */
class wpThemeShortcodes {	

	var $options; 		// Store the plugin options
	var $slug;			// Store the plugin slug, used for text domain
	var $options_name; 	// Store the options field name
	var $prefix;		// Store the generated function prefix string
	var $filename;
	
	var $flags; 		// Store the usage of each shortcode at runtime

	
	// Define the shortcodes that do not require cripts or additional hooks	
	var $shortcodes = array(
		'general'	=> 	array('chart','esc','adsense','map'),
		'comments' 	=>  array('comment-published','comment-author','comment-edit-link','comment-reply-link','comment-permalink','comment-count','category-group-posts'),
		'posts' 	=> 	array('post-title-link','post-title','post-author-link','post-author','post-terms','post-comments-link','post-published','post-edit-link','post-shortlink','post-url','post-date','post-cats','post-tags','post-views','page-nav'),
		'site' 		=>	array('the-year','site-link','site-login-link','query-counter','nav-menu','breadcrumbs'),
		'wrappers'	=>	array('child-pages','sibling-pages','list-pages','bloginfo','popular-posts'),
		'social' 	=> 	array('plus','like','tweet','tweetmeme','twitter','odesk','gravatar','w3c-valid'),
		
		'scientific'=> 	array('latex'),
		'wordpress' => 	array('plugin-downloads','plugin-rating','plugin-compatible','plugin-last-updated','plugin-list', 'plugin-required-version','plugin-download-link','wp'),
		'other'		=> 	array('github-projects','github-issues', 'gist', 'jsfiddle','gtmetrix','pagespeed','yslow','stylesheetdirectory'),
	);
	
	// Define the shortcodes that do not require cripts or additional hooks	
	var $shortcodes_with_scripts = array('plus','like','adsense','map','tweet');			
	
	// Force singelton
	static $instance = false;
	public static function getInstance() {
		return (self::$instance ? self::$instance : self::$instance = new self);
	}
	
	/**
	* PHP5 constructor
	*/
	private function __construct() {
		$this->options_menu_link = 'Theme Shortcodes';												// Define the menu link anchor text
		$this->options_page_title = 'Theme Shortcodes Options';										// Define the title for the options page.
		$this->slug = 'theme-shortcodes';															// Define the plugin slug.
		$this->prefix = str_replace('-','_',$this->slug);											// Generate a prefix from the slug.
		$this->option_name = $this->prefix . '_options';											// Generate the options field name.
		$this->filename = plugin_basename(__FILE__);
		 
		// If we have no options in the database, let's add them now.
		if ( false === get_option( $this->prefix . '_options') ){
			add_option( $this->prefix . '_options', $this->options_page_defaults() );
		} 
		
		// Load up the options
		$this->options = get_option($this->option_name);
		
		add_action( 'admin_init', array(&$this,'options_page_register_options') ); 					// Register the options iwth the settings API.
		add_action( 'admin_init', array(&$this,'options_page_register_meta_boxes') );				// Register the meta boxes on the options page.	
		add_action( 'admin_menu', array(&$this,'options_page_menu' ));								// Add a menu link.
		add_filter( 'screen_layout_columns', array(&$this, 'options_page_layout_columns'), 10, 2);	// Set the options page to show two columns.
		
		// Register the callbacks for all active shortcodes
		foreach ($this->shortcodes as $category => $values) {
			foreach ($values as $shortcode) {
				// If active then define the shortcode
				if ( $this->options[$shortcode] ) {
					add_shortcode( $shortcode, array( &$this, 'shortcode_' .  str_replace('-','_',$shortcode) ) );
				} 
			} 
		} // foreach
		
		// Register the callbacks for all active shortcodes that require scripts
		foreach ($this->shortcodes_with_scripts as $shortcode) {
			if ( $this->options[$shortcode] ) {
				add_action('init', array(&$this, 'shortcode_' . str_replace('-','_',$shortcode) . '_register_scripts'));
				add_action('wp_footer', array(&$this, 'shortcode_' . str_replace('-','_',$shortcode) . '_print_scripts'));		
			} 
		} // foreach
		
		
		// Add miscellaneous callback for some of the shortcodes
		if ( $this->options['post-views'] ) { 
			add_filter('the_content', array(&$this,'shortcode_post_views_filter_the_content'));
		}
		
		add_filter('widget_text', 'do_shortcode');
	} // function
	
	function section_null() {} // Null callback for metaboxes
	
	// Add the metaboxes
	function options_page_register_meta_boxes() {
		add_settings_section('admin-section-support', '', array(&$this, 'section_null'), $this->page );
		add_settings_section('admin-section-main', '', array(&$this, 'section_null'), $this->page );		
		add_meta_box('admin-section-main','Active Shortcodes', array(&$this, 'options_page_main_metabox'), $this->page, 'normal', 'core',array('section' => 'admin-section-main'));
		add_meta_box('admin-section-support','Support', array(&$this, 'options_page_support_metabox'), $this->page, 'side', 'core',array('section' => 'admin-section-support'));	
	} // function
	
	// on the options page make sure there are two columns
	function options_page_layout_columns($columns, $screen) {
		if ($screen == $this->page) {
			$columns[$this->page] = 2;
		}
		return $columns;
	} // function
	
	// Runs only on the theme options page load hook, enables the options screen boxes
	function options_page_loading() {
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
	} // function	
	
	// Meta box for the support info
	function options_page_main_metabox() {
		foreach ($this->shortcodes as $shortcodegroup => $values) {
			echo '<h4 style="clear:both;">' . ucwords($shortcodegroup) . '</h4>';
			foreach ($values as $shortcode) {
				echo '<div style="width:170px; float:left; margin-bottom:5px;"><input name="theme_shortcodes_options[' .$shortcode . ']"  type="checkbox" value="1" class="code" ' . checked( 1, $this->options[$shortcode], false ) . ' />' . " [" . $shortcode . "] </div>";
			}
			echo '<br style="clear:both;"/>';
		}
	} // function
	
	// Meta box for the support info
	function options_page_support_metabox() {
		print "<ul id='admin-section-support-wrap'>";
		print "<li><a id='framework-support' href='http://themeshortcodes.com/docs' target='_blank' style=''>Documentation</a></li>";
		print "<li><a id='framework-support' href='https://github.com/OneManOneLaptop/theme-shortcodes/issues' target='_blank' style=''>Report a Bug</a></li>";
		print "</ul>"; 
	} // function
	
	
	/**
	 * Init plugin options to white list our options
	 */
	function options_page_register_options(){
		register_setting( $this->prefix . '_options_group', $this->prefix . '_options', array(&$this,'options_page_validate') );
	}

	/**
	 * Load up the menu page
	 */
	function options_page_menu() {
		$this->page = add_options_page( $this->options_menu_link, $this->options_menu_link, 'manage_options',  $this->slug, array(&$this,'options_page_render' ));
		add_filter( 'plugin_action_links', array(&$this, 'options_page_add_settings_link'), 10, 2 );		
		add_action('load-'.$this->page,  array(&$this, 'options_page_loading'));
	}
	
	// Add a settings link to the plugin list page
	function options_page_add_settings_link($links, $file) {
		if ( $file ==  $this->filename  ){
			$settings_link = '<a href="options-general.php?page=' .$this->slug . '">' . __('Settings', $this->slug) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	} // function
		
	/**
	 * Sanitize and validate input. Accepts an array, return a sanitized array.
	 */
	function options_page_validate( $input ) {	
		$output = $defaults = $this->options_page_defaults();
		$output = $input;
		return apply_filters( 'theme_shortcodes_options_validate', $output, $input, $defaults );
	}
	
	/**
	 * Returns the options array
	 */
	function plugin_get_options_page_options() {
		return get_option( 'theme_shortcodes_options', $this->options_page_defaults() );
	}
	
	/**
	 * Returns the default options
	 */
	function options_page_defaults() {
		// Define the theme default values
		$default = array ( 
			'site-title' => '1', 
			'site-slogan' => '1', 
			'site-logo' => '', 
			'nav-links' => '1', 
			'thumb-width' => '150', 
			'thumb-height' => '150', 
			'overall-width' => '960', 
			'left-width' => '200', 
			'right-width' => '200', 
			'header-height' => '100', 
			'navbar-height' => '40', 
			'footer-height' => '100', 
			'sticky-footer' => '1'
		);

		return $default;
		
		
	}
	
	/**
	 * Create the options page
	 */
	function options_page_render() {
		global $screen_layout_columns;
			?>
			<div class="wrap">
				<?php screen_icon('options-general'); ?>
				<h2><?php print $this->options_page_title; ?></h2>
				<form id="settings" action="options.php" method="post" enctype="multipart/form-data">
					<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
					<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
					<?php settings_fields($this->prefix . '_options_group'); ?>
					<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
						<div id="side-info-column" class="inner-sidebar">
							<?php do_meta_boxes($this->page, 'side', $data); ?>
						</div>
						<div id="post-body" class="has-sidebar">
							<div id="post-body-content" class="has-sidebar-content">
								<?php do_meta_boxes($this->page, 'normal',''); ?>
								<br/>
								<p>
									<input type="submit" value="Save Changes" class="button-primary" name="Submit"/>	
								</p>
							</div>
						</div>
						<br class="clear"/>				
					</div>	
				</form>
			</div>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready( function($) {
					<?php // $('.if-js-closed').removeClass('if-js-closed').addClass('closed'); ?>
					postboxes.add_postbox_toggles('<?php echo $this->page; ?>');
				});
				//]]>
			</script>
			<?php
	} // function
	
	
	
	
	/* Shortcode Definitions */
	
	

	/**
	* category-group-posts
	*
	* Category group posts by Rob Holmes http://onemanonelaptop.com
	*/
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
			$args = array( 'numberposts' => 5, 'offset'=> 0, 'category' => $category->cat_ID, 'post_type'=>$atts['type'] );
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
	
	
	/**
	* list-pages 					
	*
	* List Pages by Aaron Harp, Ben Huson http://www.aaronharp.com
	*/	
		function shortcode_child_pages( $atts, $content, $tag ) {
			return $this->shortcode_list_pages( $atts, $content, $tag );
		}
	
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

	
	/* =the-year 						by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */
	
	function shortcode_the_year() {
		return date( __( 'Y', $domian ) );
	} // function

	
	/* =site-link 						by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */
	
	function shortcode_site_link() {
		return '<a class="site-link" href="' . home_url() . '" title="' . esc_attr( get_bloginfo( 'name' ) ) . '" rel="home"><span>' . get_bloginfo( 'name' ) . '</span></a>';
	} // function

	
	/* =theme-link 						by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */
	function shortcode_theme_link() {
		$data = get_theme_data( trailingslashit( TEMPLATEPATH ) . 'style.css' );
		return '<a class="theme-link" href="' . esc_url( $data['URI'] ) . '" title="' . esc_attr( $data['Name'] ) . '"><span>' . esc_attr( $data['Name'] ) . '</span></a>';
	} // function

		
	/* =child-link 						by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */
	function shortcode_child_link() {
		$data = get_theme_data( trailingslashit( STYLESHEETPATH ) . 'style.css' );
		return '<a class="child-link" href="' . esc_url( $data['URI'] ) . '" title="' . esc_attr( $data['Name'] ) . '"><span>' . esc_attr( $data['Name'] ) . '</span></a>';
	} // function
	
	
	/* =site-login-logout				by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */
	
	function shortcode_loginout_link() {
		$domain = $domian;
		if ( is_user_logged_in() )
			$out = '<a class="logout-link" href="' . esc_url( wp_logout_url( site_url( $_SERVER['REQUEST_URI'] ) ) ) . '" title="' . esc_attr__( 'Log out of this account', $domain ) . '">' . __( 'Log out', $domain ) . '</a>';
		else
			$out = '<a class="login-link" href="' . esc_url( wp_login_url( site_url( $_SERVER['REQUEST_URI'] ) ) ) . '" title="' . esc_attr__( 'Log into this account', $domain ) . '">' . __( 'Log in', $domain ) . '</a>';

		return $out;
	} // function

	
	/* =query-counter					by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */
	
	// Displays query count and load time if the current user can edit themes. Based on code by Justin Tadlock/Hybrid theme
	function shortcode_query_counter() {
		if ( current_user_can( 'edit_themes' ) )
			$out = sprintf( __( 'This page loaded in %1$s seconds with %2$s database queries.', $domian ), timer_stop( 0, 3 ), get_num_queries() );
		return $out;
	} // function

	
	/* =nav-menu						by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */

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

	
	/* =post-edit-link					by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */

	function shortcode_post_edit_link( $attr ) {
		global $post;
		$domain = $domian;
		$post_type = get_post_type_object( $post->post_type );

		if ( !current_user_can( $post_type->cap->edit_post, $post->ID ) )
			return '';

		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );

		return $attr['before'] . '<span class="post-edit"><a class="post-edit-link" href="' . get_edit_post_link( $post->ID ) . '" title="' . sprintf( esc_attr__( 'Edit %1$s', $domain ), $post_type->labels->singular_name ) . '">' . __( 'Edit', $domain ) . '</a></span>' . $attr['after'];
	} // function

	
	/* =post-published						by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */
	
	// Displays the published date of an individual post.  Based on code by Justin Tadlock/Hybrid theme
	function shortcode_post_published( $attr ) {
		$domain = $domian;
		$attr = shortcode_atts( array( 'before' => '', 'after' => '', 'format' => get_option( 'date_format' ) ), $attr );

		$published = '<span class="post-published" title="' . sprintf( get_the_time( esc_attr__( 'l, F jS, Y, g:i a', $domain ) ) ) . '">' . sprintf( get_the_time( $attr['format'] ) ) . '</abbr>';
		return $attr['before'] . $published . $attr['after'];
	} // function

	
	/* =post-date							by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */

	function shortcode_post_date( $attr ) {
		$domain = $domian;
		$attr = shortcode_atts( array( 'before' => '', 'after' => '', 'format' => get_option( 'date_format' ) ), $attr );
		$published = '<span class="post-date" >' . sprintf( get_the_time( $attr['format'] ) ) . '</span>';
		return $attr['before'] . $published . $attr['after'];
	} // function

	
	/* =post-comments-link					by Justin Tadlock/Hybrid theme
	------------------------------------------------------------------------ */
	
	// Displays a post's number of comments wrapped in a link to the comments area.
	function shortcode_post_comments_link( $attr ) {

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



	/* =post-comments-count				
	------------------------------------------------------------------------ */
	function shortcode_comment_count( $attr ) {
	
		extract(shortcode_atts(
			array(
				'id' => 0,
			),
			$attr
		));
		
	
		$domain = $domian;
		$comments_link = '';
		$number = get_comments_number($id);
		return '<span class="comment-count">' . $number . " Comments" . "</span>";
	} // function

	
	/* =post-cats				
	------------------------------------------------------------------------ */

	function shortcode_post_cats ($attr) {
		global $post;
		extract(shortcode_atts(
			array(
				'id' => $post->ID,
			),
			$attr
		));
		
		
		$post_categories = wp_get_post_categories( $id );
		$cats = array();
			
		foreach($post_categories as $c){
			$cat = get_category( $c );
			$cats[] = array( 'id' => $c, 'name' => $cat->name, 'slug' => $cat->slug );
		}

		$output = array();
		foreach ($cats as $cat) {
			$output[] = "<a href='" . get_category_link( $cat['id']  ) . "' >" . $cat['name']  . "</a>";
		}
		
		return '<span class="post-cats">' . implode(', ',$output) . "</span>";
	} // function

	
	/* =post-tags				
	------------------------------------------------------------------------ */

	function shortcode_post_tags($attr) {
		global $post;
		$tags = wp_get_post_tags($post->ID);

		$output = array();
		foreach ($tags as $tag) {
			$output[] = "<a href='" . $tag->slug . "' >" . $tag->name  . "</a>";
		}
		return '<span class="post-tags">' . implode(', ',$output) . "</span>";
	} // function

	
	
	/* =post-author-link				
	------------------------------------------------------------------------ */

	 // Displays an individual post's author with a link to his or her archive.
	function shortcode_post_author_link( $attr ) {
		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
		$author = '<span class="post-author-link"><a  href="' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '" title="' . esc_attr( get_the_author_meta( 'display_name' ) ) . '">' . get_the_author_meta( 'display_name' ) . '</a></span>';
		return $attr['before'] . $author . $attr['after'];
	} // function


	
	/* =post-author			
	------------------------------------------------------------------------ */

	function shortcode_post_author ( $attr ) {
		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
		$author = '<span class="post-author">' . get_the_author_meta( 'display_name' ) . '</span>';
		return $attr['before'] . $author . $attr['after']; 
	} // function
	
	
	
	
	/* =post-terms			
	------------------------------------------------------------------------ */
	
	function shortcode_post_terms( $attr ) {
		global $post;

		$attr = shortcode_atts( array( 'id' => $post->ID, 'taxonomy' => 'post_tag', 'separator' => ', ', 'before' => '', 'after' => '' ), $attr );

		$attr['before'] = ( empty( $attr['before'] ) ? '<span class="' . $attr['taxonomy'] . '">' : '<span class="' . $attr['taxonomy'] . '"><span class="before">' . $attr['before'] . '</span>' );
		$attr['after'] = ( empty( $attr['after'] ) ? '</span>' : '<span class="after">' . $attr['after'] . '</span></span>' );

		return get_the_term_list( $attr['id'], $attr['taxonomy'], $attr['before'], $attr['separator'], $attr['after'] );
	} // function

	
	/* =post-title			
	------------------------------------------------------------------------ */
	
	function shortcode_post_title() {
		global $post;
		$title = get_the_title();
		return $title;
	} // function

	
	
	/* =post-title-link	
	------------------------------------------------------------------------ */

	function shortcode_post_title_link() {
		global $post;

		if ( is_front_page() && !is_home() )
			$title = the_title( '<h2 class="' . esc_attr( $post->post_type ) . '-title post-title-link"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h2>', false );

		elseif ( is_singular() )
			$title = the_title( '<h1 class="' . esc_attr( $post->post_type ) . '-title post-title-link"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h1>', false );

		elseif ( 'link_category' == get_query_var( 'taxonomy' ) )
			$title = false;

		else
			$title = the_title( '<h2 class="post-title-link"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h2>', false );

		/* If there's no post title, return a clickable '(No title)'. */
		if ( empty( $title ) && 'link_category' !== get_query_var( 'taxonomy' ) ) {

			if ( is_singular() )
				$title = '<h1 class="' . esc_attr( $post->post_type ) . '-title post-title no-post-title"><a href="' . get_permalink() . '" rel="bookmark">' . __( '(Untitled)', $domian ) . '</a></h1>';

			else
				$title = '<h2 class="post-title no-post-title"><a href="' . get_permalink() . '" rel="bookmark">' . __( '(Untitled)', $domian ) . '</a></h2>';
		}

		return $title;
	} // function

	
	/* =post-shortlink	
	------------------------------------------------------------------------ */

	function shortcode_post_shortlink( $attr ) {
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
		return "{$attr['before']}<a class='post-shortlink' href='{$shortlink}' title='" . esc_attr( $attr['title'] ) . "' rel='shortlink'>{$attr['text']}</a>{$attr['after']}";
	} // function

	
	/* =post-permalink	
	------------------------------------------------------------------------ */
	function shortcode_post_url($attr) {
		global $post;
		return  get_permalink( $post->ID) ;
	} // function

	
	/* =comment-published	
	------------------------------------------------------------------------ */
	function shortcode_comment_published() {
		$domain = $domian;
		$link = '<span class="comment-published">' . sprintf( __( '%1$s at %2$s', $domain ), '<abbr class="comment-date" title="' . get_comment_date( esc_attr__( 'l, F jS, Y, g:i a', $domain ) ) . '">' . get_comment_date() . '</abbr>', '<abbr class="comment-time" title="' . get_comment_date( esc_attr__( 'l, F jS, Y, g:i a', $domain ) ) . '">' . get_comment_time() . '</abbr>' ) . '</span>';
		return $link;
	} // function

	
	
	/* =comment-author
	------------------------------------------------------------------------ */
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

		
		return apply_filters( 'jumpstart_comment_author', $output );
	} // function  

	
	/* =comment-permalink
	------------------------------------------------------------------------ */
	function shortcode_comment_permalink( $attr ) {
		global $comment;

		$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
		$domain = $domian;
		$link = '<a class="permalink" href="' . get_comment_link( $comment->comment_ID ) . '" title="' . sprintf( esc_attr__( 'Permalink to comment %1$s', $domain ), $comment->comment_ID ) . '">' . __( 'Permalink', $domain ) . '</a>';
		return $attr['before'] . $link . $attr['after'];
	} // function  

	
	/* =comment-edit-link
	------------------------------------------------------------------------ */
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

	
	/* =comment-reply-link
	------------------------------------------------------------------------ */
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

	
	/* =page-nav
	------------------------------------------------------------------------ */
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


	/* =chart
	------------------------------------------------------------------------ */
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
		$string = '';
		if ($title) $string .= '&chtt='.$title.'';
		if ($labels) $string .= '&chl='.$labels.'';
		if ($colors) $string .= '&chco='.$colors.'';
		$string .= '&chs='.$size.'';
		$string .= '&chd=t:'.$data.'';
		$string .= '&chf='.$bg.'';

		return '<img class="google-chart" title="'.$title.'" src="http://chart.apis.google.com/chart?cht='.$charttype.''.$string.$advanced.'" alt="'.$title.'" />';
	} // function

	
	/* =bloginfo
	------------------------------------------------------------------------ */
	function shortcode_bloginfo( $atts ) {
		$defaults = array(
			'key' => '',
		);
		extract(shortcode_atts( $defaults, $atts ));
		
		return get_bloginfo($key);
	} // function


	
	/* =plusone
	------------------------------------------------------------------------ */
	function shortcode_plus( $atts, $content=null ){
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
			$plus1_code = '<div class="g-plusone post-plus" data-href="' . $url . '" data-count="' . $count . '" data-size="' . $size . '" data-callback="' . $callback . '"  ></div>';
	 
		return $plus1_code;
	} // function
 
 
	/* =twitter
	------------------------------------------------------------------------ */
 	// Register the twitter script
	function shortcode_plus_register_scripts() { } // function

	// Print the twitter script only if the shortcode was used
	function shortcode_plus_print_scripts() {
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
 
 
 
 
 


	/* =like
	------------------------------------------------------------------------ */
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

	function shortcode_like_register_scripts() {}
	
	// Add the facebook like javascript if the shortcode was used
	function shortcode_like_print_scripts() {
		wp_register_script('like','http://connect.facebook.net/' . $this->likelocale . '/all.js#appId=' . $this->likeappId . '&amp;xfbml=1');
		if ($this->like_flag) {
			wp_print_scripts('like'); 
		} else {
			return;
		}
	} // function
	

		
	// Based on code by Nicholas P. Iler -- http://www.ilertech.com/2011/07/add-twitter-share-button-to-wordpress-3-0-with-a-simple-shortcode/
	function shortcode_tweet( $atts, $content=null ) {
		// set the flag so we know it has been used
		$this->flags['twitter'] = true;
		
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
	<span class="post-tweet"><a href="http://twitter.com/share" class="twitter-share-button"
		data-url="$url"
		data-counturl="$counturl"
		data-via="$via"
		data-text="$text"
		data-related="$related"
		data-count="$countbox"></a></span>
HTML;
	 
		return $output;
	} // function
	
	// Register the twitter script
	function shortcode_tweet_register_scripts() {
			
	} // function

	// Print the twitter script only if the shortcode was used
	function shortcode_tweet_print_scripts() {
		wp_register_script('twitterwidgets','http://platform.twitter.com/widgets.js');	
		if ($this->flags['twitter']) {
			wp_print_scripts('twitterwidgets'); 
		} else {
			return;
		}
	}
	
	/* =tweetmeme
	------------------------------------------------------------------------ */
	function shortcode_tweetmeme(){
		return '<div class="tweetmeme"><script type="text/javascript" src="http://tweetmeme.com/i/scripts/button.js"></script></div>';
	}
	
	
	/* =esc
	------------------------------------------------------------------------ */

	// HTML Special Chars shortcode
	function shortcode_esc( $atts, $content=null ){
			$output = htmlentities($content);
			return $output;
	} // function
 
 
	/* =adsense
	------------------------------------------------------------------------ */
	// Add the google adsense script to the site
	function shortcode_adsense_register_scripts() {
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
	function shortcode_adsense_print_scripts() {
		if (!$this->adsense_flag) { return; }
		wp_print_scripts('adsense'); 
	} // function
	
	
	
	
	/* =post-views				by Rob Holmes http://onemanonelaptop.com
	------------------------------------------------------------------------ */

	function shortcode_post_views() {
		global $post;
		$count = get_post_meta($post->ID, 'post_views_count', true);
		if($count==''){
			delete_post_meta($post->ID, 'post_views_count');
			add_post_meta($post->ID, 'post_views_count', '0');
			$count = 0;
		}
		return '<span class="post-views">' . $count . ' Views</span>';
	} // function
	
	// attach the increment counter to the_content filter
	function shortcode_post_views_filter_the_content($content ) {

		if  (!$this->views_flag) {
			$this->shortcode_increment_post_views(get_the_ID());
		}
		$this->views_flag = true;
		return $content;
	} // function

	// increment the views counter
	function shortcode_increment_post_views($postID) {
		$count_key = 'post_views_count';
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
	 

	/* =map
	------------------------------------------------------------------------ */
	// Add the image fix for google maps only if the shortcode has been used
	function shortcode_map_print_styles() {
		if  ($this->gmap_flag) {
		?>
		<style type="text/css">
			.post-content img {max-width: 100000%; /* override */}
		</style> 
		<?php
		}
	} // function

	// insert the script for google maps
	function shortcode_map_register_scripts() {
		wp_register_script('gmap','http://maps.google.com/maps/api/js?sensor=false');		
	} // function

	// gmap scripts
	function shortcode_map_print_scripts() {
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

	
	/* =breadcrumbs 		by Joost de Valk http://yoast.com/
	-------------------------------------------------------------- */
	function shortcode_breadcrumbs($attr) {
		global $wp_query, $post;

		$attr = shortcode_atts(array(	
			'lat'   => '',
			'sep'=> '&raquo;',
			'home'=> 'Home',
			'blog'=> '',
			'prefix'=> 'You are here:',
			'archiveprefix'=> 'Archives for',
			'searchprefix'=> 'Search for',
			'singlecatprefix'=> true,
			'singleparent'=> 0,
			'boldlast'=> true,
			'nofollowhome'=> false
			
			
			), $attr);
	

		if (!function_exists('bold_or_not')) {
			function bold_or_not($input) {
				$attr = get_option("yoast_breadcrumbs");
				if ($attr['boldlast']) {
					return '<strong>'.$input.'</strong>';
				} else {
					return $input;
				}
			}		
		}

		if (!function_exists('yoast_get_category_parents')) {
			// Copied and adapted from WP source
			function yoast_get_category_parents($id, $link = FALSE, $separator = '/', $nicename = FALSE){
				$chain = '';
				$parent = &get_category($id);
				if ( is_wp_error( $parent ) )
				   return $parent;

				if ( $nicename )
				   $name = $parent->slug;
				else
				   $name = $parent->cat_name;

				if ( $parent->parent && ($parent->parent != $parent->term_id) )
				   $chain .= get_category_parents($parent->parent, true, $separator, $nicename);

				$chain .= bold_or_not($name);
				return $chain;
			}
		}
		
		$nofollow = ' ';
		if ($attr['nofollowhome']) {
			$nofollow = ' rel="nofollow" ';
		}
		
		$on_front = get_option('show_on_front');
		
		if ($on_front == "page") {
			$homelink = '<a'.$nofollow.'href="'.get_permalink(get_option('page_on_front')).'">'.$attr['home'].'</a>';
			$bloglink = $homelink.' '.$attr['sep'].' <a href="'.get_permalink(get_option('page_for_posts')).'">'.$attr['blog'].'</a>';
		} else {
			$homelink = '<a'.$nofollow.'href="'.get_bloginfo('url').'">'.$attr['home'].'</a>';
			$bloglink = $homelink;
		}
			
		if ( ($on_front == "page" && is_front_page()) || ($on_front == "posts" && is_home()) ) {
			$output = bold_or_not($attr['home']);
		} elseif ( $on_front == "page" && is_home() ) {
			$output = $homelink.' '.$attr['sep'].' '.bold_or_not($attr['blog']);
		} elseif ( !is_page() ) {
			$output = $bloglink.' '.$attr['sep'].' ';
			if ( ( is_single() || is_category() || is_tag() || is_date() || is_author() ) && $attr['singleparent'] != false) {
				$output .= '<a href="'.get_permalink($attr['singleparent']).'">'.get_the_title($attr['singleparent']).'</a> '.$attr['sep'].' ';
			} 
			if (is_single() && $attr['singlecatprefix']) {
				$cats = get_the_category();
				$cat = $cats[0];
				if ( is_object($cat) ) {
					if ($cat->parent != 0) {
						$output .= get_category_parents($cat->term_id, true, " ".$attr['sep']." ");
					} else {
						$output .= '<a href="'.get_category_link($cat->term_id).'">'.$cat->name.'</a> '.$attr['sep'].' '; 
					}
				}
			}
			if ( is_category() ) {
				$cat = intval( get_query_var('cat') );
				$output .= yoast_get_category_parents($cat, false, " ".$attr['sep']." ");
			} elseif ( is_tag() ) {
				$output .= bold_or_not($attr['archiveprefix']." ".single_cat_title('',false));
			} elseif ( is_date() ) { 
				$output .= bold_or_not($attr['archiveprefix']." ".single_month_title(' ',false));
			} elseif ( is_author() ) { 
				$user = get_userdatabylogin($wp_query->query_vars['author_name']);
				$output .= bold_or_not($attr['archiveprefix']." ".$user->display_name);
			} elseif ( is_search() ) {
				$output .= bold_or_not($attr['searchprefix'].' "'.stripslashes(strip_tags(get_search_query())).'"');
			} else if ( is_tax() ) {
				$taxonomy 	= get_taxonomy ( get_query_var('taxonomy') );
				$term 		= get_query_var('term');
				$output .= $taxonomy->label .': '.bold_or_not( $term );
			} else {
				$output .= bold_or_not(get_the_title());
			}
		} else {
			$post = $wp_query->get_queried_object();

			// If this is a top level Page, it's simple to output the breadcrumb
			if ( 0 == $post->post_parent ) {
				$output = $homelink." ".$attr['sep']." ".bold_or_not(get_the_title());
			} else {
				if (isset($post->ancestors)) {
					if (is_array($post->ancestors))
						$ancestors = array_values($post->ancestors);
					else 
						$ancestors = array($post->ancestors);				
				} else {
					$ancestors = array($post->post_parent);
				}

				// Reverse the order so it's oldest to newest
				$ancestors = array_reverse($ancestors);

				// Add the current Page to the ancestors list (as we need it's title too)
				$ancestors[] = $post->ID;

				$links = array();			
				foreach ( $ancestors as $ancestor ) {
					$tmp  = array();
					$tmp['title'] 	= strip_tags( get_the_title( $ancestor ) );
					$tmp['url'] 	= get_permalink($ancestor);
					$tmp['cur'] = false;
					if ($ancestor == $post->ID) {
						$tmp['cur'] = true;
					}
					$links[] = $tmp;
				}

				$output = $homelink;
				foreach ( $links as $link ) {
					$output .= ' '.$attr['sep'].' ';
					if (!$link['cur']) {
						$output .= '<a href="'.$link['url'].'">'.$link['title'].'</a>';
					} else {
						$output .= bold_or_not($link['title']);
					}
				}
			}
		}
		if ($attr['prefix'] != "") {
			$output = $attr['prefix']." ".$output;
		}
		
			return $output;
		
	}
	
	/* =popular-posts by
	-------------------------------------------------------------- */
	function shortcode_popular_posts($atts) {
		// extract the defaults
		extract(shortcode_atts(array(
			'numberposts'     => 5,
			'offset'          => 0,
			'category'        => '',
			'orderby'         => 'meta_value_num',
			'order'           => 'DESC',
			'include'         => '',
			'exclude'         => '',
			'meta_key'        => 'post_views_count',
			'meta_value'      => '3',
			'meta_compare'	  => '!=',
			'post_type'       => 'post',
			'post_mime_type'  => '',
			'post_parent'     => '',
			'post_status'     => 'publish'
		), $atts));
	
		$output = '<div class="popular"><div class="popular-inner"><ul class="popular-list">';
		
		$myposts = get_posts($atts );
		foreach( $myposts as $post ) {
			setup_postdata($post); 
			$output .= '<li class="popular-item"><span class="popular-title"><a href="' . get_permalink($post->ID) . '">' . get_the_title($post->ID) . '</a></span>' . apply_filters('shortcode_after_popular_title','',$post->ID) . '</li>';
		}
		
		$output .= '</ul></div></div>';
		return $output;
	} // function
	
	
	
	
	/* =wpdownloads 	by Viper007Bond http://www.viper007bond.com/
	-------------------------------------------------------------- */
	
	function shortcode_wpdownloads($atts) {
		$data = $this->shortcode_wpdownloads_get_data();
		return '<span class="wpdownloads">' . $data['downloads'] . '</span>';
	}
	
	
	// Get the stats. This is sourced either from a cache or a remote request.
	 function shortcode_wpdownloads_get_data() {
		// Check for a cached copy (we don't want to do an HTTP request too often)
		$cache = get_transient('wpdownloads');
		if ( false !== $cache )
			return $cache;

		$data = array();

		// Fetch the data
		if ( $response = wp_remote_retrieve_body( wp_remote_get( 'http://wordpress.org/download/counter/?json=1' ) ) ) {
			// Decode the json response
			if ( $response = json_decode( $response, true ) ) {
				// Double check we have all our data
				if ( !empty($response['wpcounter']) && !empty($response['wpcounter']['branch']) && !empty($response['wpcounter']['downloads']) ) {
					$data = $response['wpcounter'];
				}
			}
		}
		// On a failed scrape, cache that fail for a full minute
		else {
			set_transient( 'wpdownloads', $data, 60 );
		}

		// Cache the data for future usage
		if ( $this->cachetime < 2 )
			$this->cachetime = 2;
		set_transient( 'wpdownloads', $data, $this->cachetime - 1 );

		return $data;
	}
	
/* =plugin-downloads by
-------------------------------------------------------------- */

	
	/**
	* [plugin-downloads] Shortcode Callback
	*/
	function shortcode_plugin_downloads($atts) {
	
	// extract the defaults
		extract(shortcode_atts(array(		
			'slug'     => null,
		), $atts));
		
		if (!$slug) {
		 return;
		}
		
		$response = $this->shortcode_plugin_fetch_data($slug);
		// scrape the data
		$regex_pattern = "/<strong>Downloads:<\/strong>(.*?)<br \/>/is";

		preg_match_all($regex_pattern,$response,$matches);
		$data = trim($matches[1][0]);
		
		return '<span class="plugin-downloads plugin-' . $slug. '">' . $data . '</span>';
	}

	/**
	* [plugin-last-updated:] Shortcode Callback
	*/
	function shortcode_plugin_last_updated($atts) {
	
	// extract the defaults
		extract(shortcode_atts(array(		
			'slug'     => null,
		), $atts));
		
		if (!$slug) {
		 return;
		}
		
		$response = $this->shortcode_plugin_fetch_data($slug);
		// scrape the data
		$regex_pattern = "/<strong>Last Updated:<\/strong>(.*?)<strong>/is";

		preg_match_all($regex_pattern,$response,$matches);
		$data = trim($matches[1][0]);
		
		return '<span class="plugin-last-updated plugin-' . $slug. '">' . $data . '</span>';
	}
	
	/**
	* [plugin-required-version] Shortcode Callback
	*/
	function shortcode_plugin_required_version($atts) {
	
	// extract the defaults
		extract(shortcode_atts(array(		
			'slug'     => null,
		), $atts));
		
		if (!$slug) {
		 return;
		}
		
		$response = $this->shortcode_plugin_fetch_data($slug);
		// scrape the data
		$regex_pattern = "/<strong>Requires WordPress Version:<\/strong>(.*?)<br \/>/is";

		preg_match_all($regex_pattern,$response,$matches);
		$data = trim($matches[1][0]);
		
		return '<span class="plugin-required-version plugin-' . $slug. '">' . $data . '</span>';
	}
	
	
	
	/**
	* [plugin-shortcode_plugin_download_link-version] Shortcode Callback
	*/
	function shortcode_plugin_download_link($atts) {
	
	// extract the defaults
		extract(shortcode_atts(array(		
			'slug'     => null,
		), $atts));
		
		if (!$slug) {
		 return;
		}
		
		$response = $this->shortcode_plugin_fetch_data($slug);
		// scrape the data
		$regex_pattern = '/<p class="button">(.*?)<\/p>/is';

		preg_match_all($regex_pattern,$response,$matches);
		$data = trim($matches[1][0]);
		
		return '<span class="plugin-download-link plugin-' . $slug. '">' . $data . '</span>';
	}
	
	
	
	
	/**
	* [plugin-compatible] Shortcode Callback
	*/
	function shortcode_plugin_compatible($atts) {
	
	// extract the defaults
		extract(shortcode_atts(array(		
			'slug'     => null,
		), $atts));
		
		if (!$slug) {
		 return;
		}
		
		$response = $this->shortcode_plugin_fetch_data($slug);
		// scrape the data
		$regex_pattern = "/<strong>Compatible up to:<\/strong>(.*?)<br \/>/is";

		preg_match_all($regex_pattern,$response,$matches);
		$data = trim($matches[1][0]);
		
		return '<span class="plugin-compatible plugin-' . $slug. '">' . $data . '</span>';
	}
	
	
	function shortcode_plugin_fetch_data($slug) {
		$transient = 'wp-plugin-downloads-' . $slug;
		// Get any existing copy of our transient data
		if ( false === ( $response = get_transient( $transient ) ) ) {
			// It wasn't there, so regenerate the data and save the transient
			 $response = wp_remote_retrieve_body( wp_remote_get( 'http://wordpress.org/extend/plugins/' . $slug) );
			 set_transient( $transient,  $response, 60 * 60 * 24  );
		}
		return $response;
	} // function
	
	/**
	* [plugin-rating] Shortcode Callback
	*/
	function shortcode_plugin_rating($atts) {
	
	// extract the defaults
		extract(shortcode_atts(array(		
			'slug'     => null,
		), $atts));
		
		if (!$slug) {
		 return;
		}
		
		$response = $this->shortcode_plugin_fetch_data($slug);
		// scrape the data
		$regex_pattern = "/<span>\((.*?)ratings\)<\/span>/is";

		preg_match_all($regex_pattern,$response,$matches);
		$data = trim($matches[1][0]);
		
		return '<span class="plugin-ratings plugin-' . $slug. '">' . $data . '</span>';
	}
	
	
	/* =circulation
	-------------------------------------------------------------- */

	function shortcode_feedburner_circulation($atts) {
	
		// extract the defaults
		extract(shortcode_atts(array(		
			'feed'     => null,
		), $atts));
		
		if (!$feed) {
		 return;
		}
		
		$data = $this->shortcode_plugin_downloads_get_data();
		return '<span class="feedburner-circulation">' . $data . '</span>';
	} // function


	function shortcode_feedburner_circulation_get_data() {
		$transient = 'feedburner-circulation';
		$cachetime = 15;
		
		$cache = get_transient($transient);
		if ( false !== $cache )
			return $cache;
			
			
			
		// Load FeedBurner API Data
		$xml = @simplexml_load_file("https://feedburner.google.com/api/awareness/1.0/GetFeedData?uri=htnet");

		if (!$xml) {
			// If for some reason we can't access the Feedburner API data XML, just display a realistic figure
			// Feel free to change to your own subscriber count
			set_transient($transient, '-', 60 );
		} else { 
			// All's well! Retrieve the feed count!
			$data = $xml->feed->entry['circulation'];
		}
			
		set_transient( $transient, 
		
		
		
		$data, $cachetime - 1 );
		return $data;
	
	} // function
	
	
/* =stylesheetdirectory
-------------------------------------------------------------- */
	function shortcode_stylesheetdirectory($atts) {
		return get_stylesheet_directory_uri();
	}
	
	
/* =author-comment-count
-------------------------------------------------------------- */
	
	function shortcode_author_comment_count(){
 
		$oneText = '1';
		$moreText = '%';
	 
		global $wpdb;
	 
		$result = $wpdb->get_var('
			SELECT
				COUNT(comment_ID)
			FROM
				'.$wpdb->comments.'
			WHERE
				comment_author_email = "'.get_comment_author_email().'"'
		);
	 
		if($result == 1): 
	 
			echo str_replace('%', $result, $oneText);
	 
		elseif($result > 1): 
	 
			echo str_replace('%', $result, $moreText);
	 
		endif;
 
	}
	
	
	
	
	/* =shortcode_github_projects
	-------------------------------------------------------------- */

	function shortcode_github_projects($atts) {	
		// extract the defaults
		extract(shortcode_atts(array(		
			'user'     => null,
		), $atts));
		
		if (!$user) {
			return;
		}
		
		$data = $this->shortcode_github_fetch_projects($user);

		$xml          = new SimpleXMLElement($data);
		$repositories = array();		
	
		foreach ($xml->repositories->repository as $repository)	{
			$result = array();
			foreach ($repository->children() as $key => $value) {
				$result[$key] = (string) $value;
			}
			$repositories[] = $result;
		}
		
		$output = '<div class"github-projects"><ul class="github-projects-list">';
		foreach ($repositories as $repository) {
			$output .=  '<li class="github-projects-list-item"><a href="' . $repository['url'] . '" title="' . str_replace('"', "'", $repository['description']) .  '">' . $repository['name'] . '</a></li>';
		}
		$output .=  '</ul></div>';

		return $output;	
	} // function


	function shortcode_github_fetch_projects($user) {
		$transient = 'github-' . $user;
		// Get any existing copy of our transient data
		if ( false === ( $response = get_transient( $transient ) ) ) {
			// It wasn't there, so regenerate the data and save the transient
			 $response = wp_remote_retrieve_body( wp_remote_get( 'http://github.com/api/v1/xml/' . $user ) );
			 set_transient( $transient,  $response, 60 );
		}
		return $response;
	} // function
	
	
	
	
	
	
	function shortcode_github_issues($atts) {	
		// extract the defaults
		extract(shortcode_atts(array(		
			'user'     => null,
			'project'     => null,
			'template' => '{link}{body}{comments}',
		), $atts));
		
		if (!$user || !$project) {
			return;
		}
		
		
		
		$json = $this->shortcode_github_fetch_project_issues($user,$project);
		$data = json_decode($json);
		
		$output = '<ul class="github-issues github-issues-' . $user . '-' . $project. '">';
		foreach ($data->issues as $key => $issue) {
			$output .= '<li class="github-issue">' . $this->shortcode_github_template($template,$issue) . '</li>';
		}
		return $output;	
	} // function
	
	function shortcode_github_template($template,$issue) {
		$output = str_replace('{title}','<span class="github-issue-comments">' . $issue->title . '</span>' ,$template);
		$output = str_replace('{link}','<a href="' . $issue->html_url. '" >'. $issue->title . '</a>',$output);
		$output = str_replace('{body}','<div class="github-issue-body">' . $issue->body . '</div>' ,$output);
		$output = str_replace('{comments}','<span class="github-issue-comments">' . $issue->comments . '</span>' ,$output);
		$output = str_replace('{url}',$issue->html_url,$output);
		$output = str_replace('{state}','<span class="github-issue-state">' .$issue->state . '</span>',$output);
		return $output;
	}
	
	function shortcode_github_fetch_project_issues($user,$project) {
		$transient = 'github-issues-' . $user . '-' . $project;
		// Get any existing copy of our transient data
		if ( false === ( $response = get_transient( $transient ) ) ) {
			// It wasn't there, so regenerate the data and save the transient
			 $response = wp_remote_retrieve_body( wp_remote_get( 'http://github.com/api/v2/json/issues/list/' . $user . '/' . $project . '/open' ) );
			 set_transient( $transient,  $response, 60 );
		}
		return $response;
	} // function
	
	
	
	/**
	* [gist] Shortcode Callback
	*/
	function shortcode_gist($atts, $content = null) {
		extract(shortcode_atts(array(
			'id' => null
		), $atts));

		if (!$id) {
			return;
		}
		
		$html =  '<script src="http://gist.github.com/'.trim($id).'.js"></script>';
		
		if($content != null){
			$html .= '<noscript><code class="gist"><pre>'.$content.'</pre></code></noscript>';
		}
		return $html;
	} // function
	
	
	
	/**
	* [jsfiddle] Shortcode Callback
	*
	* Embed a jsFiddle into a webpage. 
	* Based on code by Willington Vega  http://wvega.com/
	*/
	function shortcode_jsfiddle($attrs, $content) {
		$tabs = array('result', 'js', 'css', 'html');

		extract(shortcode_atts(array(
			'url' => null,
			'height' => '300px',
			'include' => join(',', $tabs)
		), $attrs));
		
		if (!$url) {
			return;
		}
		$include = array_intersect(split(',', $include), $tabs);
		$url = trim($url, '/') . '/embedded/' . join(',', $include);

		$html = '<iframe style="width: 100%; height: ' . $height . '" src="' . $url . '"></iframe>';

		return $html;
	}

	/**
	* [odesk] Shortcode Callback
	*
	* 
	*/
	function shortcode_odesk($atts) {	
		// extract the defaults
		extract(shortcode_atts(array(		
			'user'     => null,
		), $atts));
		
		if (!$user) {
			return;
		}
		
		
		
		$data = $this->shortcode_odesk_fetch_profile($user);
		$doc = new SimpleXmlElement($data, LIBXML_NOCDATA);
		$output = $this->odesk_header($doc);
		$output .= $this->odesk_tests($doc);
		$output .= $this->odesk_education($doc);
		$output .= $this->odesk_portfolio($doc);
	
	
		return $output;	
	} // function
	
	
	
	function shortcode_odesk_fetch_profile($user) {
		$transient = 'odesk-' . $user;
		// Get any existing copy of our transient data
		if ( false === ( $response = get_transient( $transient ) ) ) {
			// It wasn't there, so regenerate the data and save the transient
			 $response = wp_remote_retrieve_body( wp_remote_get( 'http://www.odesk.com/api/profiles/v1/providers/'.$user.'?wsrc=tile_2/profile.xml' ) );
			 set_transient( $transient,  $response, 60 );
		}
		return $response;
	} // function
	
	function odesk_portfolio($profile) {
		
		$portfolio = "<div>";
		$portfolio .= "<table class=\"odesk_table\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
		$portfolio .= "<tr>";
		$portfolio .= "<td colspan=2><span><strong>Portfolio</strong></span></td>";
		$portfolio .= "</tr>";	
		$portfolio .= "<tbody>";
		
		foreach ($profile->profile->portfolio_items->portfolio_item as $hr) {
		$date = (int)$hr->pi_completed;
		$portfolio .= "<tr>";
		$portfolio .= "<td valign=top><img src=\"".$hr->pi_thumbnail."\" /></td>";
		$portfolio .= "<td><ul>";
		$portfolio .= "<li><strong>Project Title:</strong> ".$hr->pi_title."</li>";
		$portfolio .= "<li><strong>Completed:</strong> ".date('M d, Y',$date)."</li>";
		$portfolio .= "<li><strong>Category:</strong> ".$hr->pi_category->pi_category_level1.">".$hr->pi_category->pi_category_level2."</li>";
		$portfolio .= "<li><strong>URL:</strong> <a href=\"".$hr->pi_url."\" rel=\"nofollow\" target=\"_blank\">".$hr->pi_url."</a></li>";
		$portfolio .= "<li><strong>Description:</strong> ".$hr->pi_description."</li>";
		$portfolio .= "</ul></td>";
		$portfolio .= "</tr>";
		
		}
		
		$portfolio .= "</tbody>";
		$portfolio .= "</table></div>";		
		
		return $portfolio;
		
	}


function odesk_tests($profile) {
	
	$work = "<div>";
	$work .= "<table class=\"odesk_table\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
	$work .= "<tr>";
	$work .= "<td colspan=5><span><strong>oDesk Tests Taken</strong></span></td>";
	$work .= "</tr>";	
	$work .= "<tr>";
	$work .= "<th width=45%>Name of Test</th>";
	$work .= "<th width=10%>Score</th>";
	$work .= "<th width=15%>Percentile</th>";
	$work .= "<th width=20%>Date Taken</th>";
	$work .= "<th width=10%>Duration</th>";
	$work .= "</tr>";
	$work .= "<tbody>";
	
	foreach ($profile->profile->tsexams->tsexam as $hr) {

	$work .= "<tr>";
	$work .= "<td>".$hr->ts_name."</td>";
	$work .= "<td>".$hr->ts_score."</td>";
	$work .= "<td>".$hr->ts_percentile."</td>";
	$work .= "<td>".$hr->ts_when."</td>";
	$work .= "<td>".$hr->ts_duration." min</td>";	
	$work .= "</tr>";
	
	}
	
	$work .= "</tbody>";
	$work .= "</table></div>";		
	
	return $work;
	
}

	function odesk_certification($profile) {
		
		$certificate .= "<table class=\"odesk_table\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
		$certificate .= "<tr>";
		$certificate .= "<td colspan=4><span><strong>Certification</strong></span></td>";
		$certificate .= "</tr>";	
		$certificate .= "<tr>";
		$certificate .= "<th width=10%>Date</th>";
		$certificate .= "<th width=25%>Name</th>";
		$certificate .= "<th width=25%>Organization</th>";
		$certificate .= "<th width=40%>Description</th>";
		$certificate .= "</tr>";
		$certificate .= "<tbody>";
		
		foreach ($profile->profile->certification->certificate as $c) {
			
		$certificate .= "<tr>";
		$certificate .= "<td>".$c->cer_earned."</td>";
		$certificate .= "<td>".$c->cer_name."</td>";
		$certificate .= "<td>".$c->cer_organisation."</td>";
		$certificate .= "<td>".$c->cer_comment."</td>";
		$certificate .= "</tr>";
		
		}
		
		$certificate .= "</tbody>";
		$certificate .= "</table>";		
		
		return $certificate;
		
	}
	function odesk_education($profile) {
		
		$education .= "<table class=\"odesk_table\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
		$education .= "<tr>";
		$education .= "<td colspan=6><span><strong>Education</strong></span></td>";
		$education .= "</tr>";
		$education .= "<tr>";
		$education .= "<th width=10%>From</th>";
		$education .= "<th width=10%>To</th>";
		$education .= "<th width=15%>School</th>";
		$education .= "<th width=15%>Degree</th>";
		$education .= "<th width=20%>Major</th>";
		$education .= "<th width=30%>Description</th>";
		$education .= "</tr>";	
		$education .= "<tbody>";
		
		foreach ($profile->profile->education->institution as $c) {
			$education .= "<tr>";
			$education .= "<td>".$c->ed_from."</td>";
			$education .= "<td>".$c->ed_to."</td>";
			$education .= "<td>".$c->ed_school."</td>";
			$education .= "<td>".$c->ed_degree."</td>";
			$education .= "<td>".$c->ed_area."</td>";
			$education .= "<td>".$c->ed_comment."</td>";	
			$education .= "</tr>";
		}
		
		$education .= "</tbody>";
		$education .= "</table>";		
		return $education;	
	}

	function odesk_header($profile) {
		$header = "<div class=\"odesk_header\">";
		$header .= "<div class=\"img\"><img src=\"".$profile->profile->dev_portrait."\" /><br />";
		$header .= "<a target=\"_blank\" href=\"https://www.odesk.com/users/".get_option("odesk_profile_key")."?wsrc=tile_2&wlnk=btn&_scr=hireme_l\">";
		$header .= "<img src=\"".WP_CONTENT_URL.'/plugins/'.basename(dirname(__FILE__)) . '/'.'th_hire_me_button.jpg'."\" border=0 width=100 /></a></div>";
		$header .= "<div class=\"header_profilename\"><strong><a target=\"_blank\" href=\"https://www.odesk.com/users/".get_option("odesk_profile_key")."?wsrc=tile_2&wlnk=btn&_scr=hireme_l\">".$profile->profile->dev_short_name."</a></strong></h1>";
		$header .= "<div class=\"border_line\"></div>";
		$header .= "<span>".$profile->profile->profile_title_full."</span><br />";
		$header .= "<span>Current hourly rate: $<strong>".$profile->profile->dev_bill_rate."/hr</strong></span><br />";
		$header .= "<span>Member since ".$profile->profile->dev_member_since."</span>";
		$header .= "</div>";
		$header .= "</div>";
		
		return $header;
	}

	/**
	* [w3c-valid] Shortcode Callback
	*/
	function shortcode_w3c_valid($atts) {	
		// extract the defaults
		extract(shortcode_atts(array(		
			'url'     => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		), $atts));
	
		$data = $this->shortcode_w3c_check($url);
		if (empty($data)) {
			return 'error';
		}
		$doc = new DOMDocument();
		$doc->loadHTML($data);
		$res = $doc->getElementById('congrats');
		if (isset($res)) { $res = $doc->getElementById('congrats')->nodeValue; }
		  if($res == 'Congratulations') {
			return 'W3C Valid!';
		  } else {
			return 'Not W3C valid (<a href="http://validator.w3.org/check?uri=' . $url . '&amp;charset=%28detect+automatically%29&amp;doctype=Inline">errors</a>)';
		  }
		
		
	} // function
	
	
	
	function shortcode_w3c_check($url) {
		$transient = 'w3c-' . md5($url);
		// Get any existing copy of our transient data
		if ( false === ( $response = get_transient( $transient ) ) ) {
			// It wasn't there, so regenerate the data and save the transient
			 $response = wp_remote_retrieve_body( wp_remote_get('http://validator.w3.org/check?uri=' .$url. '&charset=%28detect+automatically%29&doctype=Inline') );
			 set_transient( $transient,  $response, 60*60*24 );
		}
		return $response;
	} // function
	
	
function shortcode_gravatar( $atts ) {
	extract( shortcode_atts( array(
		'size' => '80',
		'email' => '',
		'rating' => 'X',
		'default' => '',
		'alt' => '',
		'title' => '',
		'align' => '', 
		'style' => '', 
		'class' => '', 
		'id' => '', 
		'border' => '', 
		), $atts ) );
	if ( !$email ) return '';
	
	// Supported Gravatar parameters
	$rating  = $rating ? '&amp;r=' . $rating : '';
	$default = $default ? '&amp;d=' . urlencode( $default ) : '';
	
	// Supported HTML attributes for the IMG tag
	$alt    = $alt ? ' alt="' . $alt . '"' : '';
	$title  = $title ? ' title="' . $title . '"' : '';
	$align  = $align ? ' align="' . $align . '"' : '';
	$style  = $style ? ' style="' . $style . '"' : '';
	$class  = $class ? ' class="' . $class . '"' : '';
	$id     = $id ? ' id="' . $id . '"' : '';
	$border = $border ? ' border="' . $border . '"' : '';
	
	// Send back the completed tag
	return '<img src="http://www.gravatar.com/avatar/' . md5( trim( strtolower( $email ) ) ) . '.jpg?s=' . $size . $rating . $default . '" width="' . $size . '" height="' . $size . '"' . $alt . $title . $align . $style . $class . $id . $border . ' />';
}

	
	/**
	* [plugin-list] Shortcode Callback
	*/
	function shortcode_plugin_list($atts) {
	
	// extract the defaults
		extract(shortcode_atts(array(		
			'user'     => null,
			'url' => 'http://wordpress.org/extend/plugins/',
			'type' => 1,
		), $atts));
		
		if (!$user) {
		 return;
		}
		
		$data = $this->shortcode_plugin_fetch_list($user);
		// scrape the data
		preg_match_all( '/<a.*?href\s*=\s*["\']http:\/\/wordpress\.org\/extend\/plugins\/([^"\']+)[^>]*>.*?<\/a>/i', $data, $links );
		 foreach ($links[1] as $link) {
			if (strpos($link,'tags') === false) {
				$output .= "<li class='plugin-list-item'>" . apply_filters( 'shortcode_plugin_list_before_link', '',  str_replace('/','',$link),$type ). "<span class='plugin-list-item-link'><a href='" . $url . $link . "'>" . $this->plugin_nice_name($link) . "</a></span>" . apply_filters( 'shortcode_plugin_list_after_link', '' , str_replace('/','',$link),$type ). "</li>";
			}
		 }
		return '<ul class="plugin-list wp-' . $user. '">' . $output . '</ul>';
	}
	
	
	function plugin_nice_name($slug) {
		$output = str_replace('/','',$slug) ;
		$output = str_replace('-',' ',$output) ;
		return ucwords($output);
	
	}
	
	function shortcode_plugin_fetch_list($user) {
		$transient = 'wp-plugins-list-' . $user;
		// Get any existing copy of our transient data
		if ( false === ( $response = get_transient( $transient ) ) ) {
			// It wasn't there, so regenerate the data and save the transient
			 $response = wp_remote_retrieve_body( wp_remote_get( 'http://wordpress.org/extend/plugins/profile/' . $user) );
			 set_transient( $transient,  $response, 60 * 60 * 24  );
		}
		return $response;
	} // function
	
} // class

$wpthemeshortcodes = wpThemeShortcodes::getInstance();