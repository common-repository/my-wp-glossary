<?php
/*
 * Plugin Name: My WP Glossary
 * Plugin URI: https://whodunit.fr/my-wp-glossary
 * Description: A glossary block for your WordPress website, with structured data and powered by a Gutenberg block or a shortcode.
 * Version: 0.6.4
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 6.1
 * Author: Whodunit
 * Author URI: https://whodunit.fr
 * Contributors: whodunitagency, alexischenal, audrasjb, leprincenoir, virginienacci, bmartinent
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: my-wp-glossary
 */

//----initialization and dependency-------------------------------------------------------------------------------------

if ( ! defined( 'ABSPATH' ) ) { die( 'Invalid request.' ); }
include_once( 'classes/simple_html_dom.php' );

//----plugin-constants--------------------------------------------------------------------------------------------------

define( 'MYWPGLOSSARY_TERMS_TRANSIENT_KEY', apply_filters( 'mywpglossary_term_transient_key', 'transient_glossary' ) );
define( 'MYWPGLOSSARY_TERMS_TRANSIENT_EXPIRATION', ( int ) apply_filters( 'mywpglossary_term_transient_expiration', 21600 ) ); // 6 hours


//----functions---------------------------------------------------------------------------------------------------------

/**
 * mywpglossary_get_terms_by_post
 * Utility function return a array of term contained by the post for a given WP_Post object, post_id or current post if null
 * this function need terms indexation for working correctly.
 * @param WP_Post|int|null $post, Take a WP_Post object a post ID or a null
 * @return array|string[], Return a empty array if nothing is find else return a array of string
 */
function mywpglossary_get_terms_by_post( $post = null ){
	$post = get_post( $post );
	if( ! is_a( $post, 'WP_Post' ) ){ return []; }
	$term_slugs = get_post_meta( $post-ID, 'mywpglossary_post_matching_terms', true );
	if( $term_slugs ){
		return explode( '][', substr( $term_slugs, 1, -1 ) );
	}
	return [];
}

/**
 * mywpglossary_get_posts_by_term
 * Utility function return an array of WP_Post containing the term for a given term slug ( mywpglossary cpt slug )
 * @param string $term_slug, Take a mywpglossary WP_Post slug
 * @return array|WP_Posts[], Return a empty array if nothing is find else return a array of WP_Post
 */
function mywpglossary_get_posts_by_term( $term_slug ){
	$args = [
		'ignore_sticky_posts' => 1,
		'post_type'           => 'any',
		'post_status'         => [ 'publish' ],
		'posts_per_page'      => 5,
		'meta_query'          => [ [
			'key'     => 'mywpglossary_post_matching_terms',
			'value'   => '['.$term_slug.']',
			'compare' => 'LIKE',
		] ]
	];
	$query = new WP_Query( $args );
	return $query->posts;
}

/**
 * mywpglossary_find_tag_in_parents
 * Utility function find a parent with a given tag for a given node
 * @param simple_html_dom_node $node, The node from where to start search.
 * @param string $tags, The tag to search for, take a tag without sign bracket like "h1", "button" or "a".
 * @param boolean $recursive, If this flag is true the function is recursive and will search down the tree to the root node else it will just check the closet parent.
 * @return simple_html_dom_node|boolean, Return a false if nothing is find else return a simple_html_dom_node object
 */
function mywpglossary_find_tag_in_parents( $node, $tags, $recursive = true ){
	if( ! is_array( $tags ) ){ $tags = [ $tags ]; }
	if( in_array( $node->tag, $tags ) ){ return true; }
	if( ! is_null( $node->parent ) && $recursive ){
		return mywpglossary_find_tag_in_parents( $node->parent, $tags );
	}
	return false;
}

/**
 * mywpglossary_recursive_match
 * Utility function find and replace brut text element from a simple_html_dom_node object and this offsprings
 * @param simple_html_dom_node $node, The node from where to start search.
 * @param string $exclude_tags, a array of parent tag which you want to prevent the text to by modified.
 * @param string $pattern, the regex pattern to apply.
 * @param string $replacement, the replacement string.
 * @param string $replaced, a counter for the number of match.
 * @return void
 */
function mywpglossary_recursive_match( &$node, $exclude_tags, $pattern, $replacement, &$replaced, $term = null ){
	if( ! $node->has_child() && $node->text() && ! mywpglossary_find_tag_in_parents( $node, $exclude_tags ) ){
		$r = preg_replace( $pattern, $replacement, $node->innertext, -1, $replaced );
		$node->innertext = $r;
		return;
	}
	$r = 0;
	foreach( $node->nodes as $child ){
		mywpglossary_recursive_match( $child, $exclude_tags, $pattern, $replacement, $r, $term );
		$replaced += $r;
	}
}

function mywpglossary_get_patern( $term ){
	$accepted_encapsulation_chars = apply_filters( 'mywpglossary_encapsulation_chars', [
		'pre'  => [ '\s', ':' , ';' , '*' ,'"' ,'\'', '.', ',', '!', '?', '¡', '¿', '«', '(', '[', '{', '\'', '"' ],
		'post' => [ '\s', ':' , ';' , '*' ,'"' ,'\'', '.', ',', '!', '?', '¡', '¿', '»', ')', ']', '}', '\'', '"' ],
		'html' => [ '&nbsp;', '&#8221;', '&#8220;', '&#8217;', '&#8216;' ],
	] );
	foreach ( $accepted_encapsulation_chars as $key => &$pos ){
		if( ! is_array( $pos ) ){ unset( $pos ); continue; }
		$pos = array_map( function( $char ){
			$regex_special_char_to_protect = [ '\\', '\'', '^', '$', '|', '?', '*', '+', '(', ')', '[', ']', '{', '}' ];
			return ( in_array( $char, $regex_special_char_to_protect ) ) ? '\\'.$char : $char;
		}, $pos );

		$pos = implode( ( ( 'html' === $key )  ? '|' : '' ), $pos );
	}
	$pattern = '/(^|['
		.( ( isset( $accepted_encapsulation_chars[ 'pre' ] ) ) ? $accepted_encapsulation_chars[ 'pre' ] : '' )
		.']|'.$accepted_encapsulation_chars[ 'html' ].')('.$term.')(['
		.( ( isset( $accepted_encapsulation_chars[ 'post' ] ) ) ? $accepted_encapsulation_chars[ 'post' ] : '' )
		.']|'.$accepted_encapsulation_chars[ 'html' ].'|$)/i';
	//if pattern do not work use the old one.
	if( false === preg_match( $pattern, '' ) ){
		$pattern = '/(^|[\s.,!?])('.$term.')([\s.,!?]|$)/i';
	}

	return $pattern;
}

/**
 * mywpglossary_matching_terms
 * Search and replace all glossary terms from a given content, it will try to index the terms if a post id is given.
 * @param string $content, the html content form which you want to perform a term search. if replace flag is true it will be modified.
 * @param string|null $post_id, if the post_id is given the function will add the term found into a index.
 * @param boolean $replace, replace flag if false, the content will not be changed, but they will be indexed.
 * @return array|string[] return a empty array if no term is present in the content, else return a array of all terms find;
 */
function mywpglossary_matching_terms( &$content, $post_id = null, $replace = true ) {
	$data_glossary = get_transient_glossary();
	$html          = str_get_html( $content, null, null, null, false );
	$terms         = [];
	$exclude_tags  = apply_filters( 'mywpglossary_exclude_tags', [ 'script','h1','h2','h3','h4','a' ] );
	if( false === $html ) { return $terms;  }
	foreach( $data_glossary as $key => $value ){
		$replaced    = 0;
		$term        = preg_quote( $value['term'], '/' );
		$pattern     = mywpglossary_get_patern( $term );
		$replacement = '$1'.$value['begin'].'$2'.$value['end'].'$3';
		mywpglossary_recursive_match($html->root, $exclude_tags, $pattern, $replacement, $replaced, $term );
		if ( $replaced && ! isset( $terms[$key] ) ) {
			$terms[$key] = [ 'term' => $value['term'], 'content' => $value['content'], 'count' => $replaced ];
		}elseif( $replaced && isset( $terms[$key] ) ){
			$terms[ $key ][ 'count' ] += $replaced;
		}
		if( ! empty( $terms ) && $replaced ) {
			$html = str_get_html( $html->save(), null, null, null, false );
		}
	}
	if( ! empty( $terms ) && $post_id ){
		$parced_terms = '['.implode( '][', array_keys( $terms ) ).']';
		update_post_meta( $post_id, 'mywpglossary_post_matching_terms', $parced_terms );
	}

	$content = ( string ) $html;
	return $terms;
}

/**
 * get_transient_glossary
 * Prepare a list of all valid glossary terms and associated data from data base, set the result in transient for performances issues
 * @param boolan $forceUpdate Reprocess flag, if true force to reprocess all publish glossary terms else try to retrieve a transient from db, false by default
 * @return array|array[] return an empty array if there is no glossary term published else return a 2d array of all terms with opening and closing tag, content, trim term name and sanitize term name as key.
 */
function get_transient_glossary( $forceUpdate = false ) {
	$transient_key = MYWPGLOSSARY_TERMS_TRANSIENT_KEY.( ( function_exists( 'pll_current_language' ) ) ? '_'.pll_current_language( 'slug' ) : '' );
	$result = [];
	$cache = json_decode( get_transient( $transient_key ), true );
	if( $forceUpdate || false === $cache || is_null( $cache ) ){
		global $wpdb;
		$use_single        = apply_filters( 'mywpglossary_use_single', false );
		$query_shortcode   = "SELECT ID, post_title FROM " . $wpdb->posts . " WHERE ( post_content LIKE '%<!-- wp:mywpglossary/glossary /-->%' OR post_content LIKE '%[glossary]%' ) AND post_status = 'publish' ORDER BY CHAR_LENGTH( post_title ) ASC";
		$results_shortcode = $wpdb->get_row( $wpdb->prepare( $query_shortcode, [] ) );
		$link              = apply_filters( 'override_glossary_link', get_the_permalink( $results_shortcode->ID ) ); //Deprecated filter
		$link              = apply_filters( 'mywpglossary_override_glossary_link', $link );
		$args              = [
			'post_type'      => 'mywpglossary',
			'posts_per_page' => - 1,
		];
		$query_glossary    = new WP_Query( $args );
		$glossary          = [];
		if ( $query_glossary->have_posts() ) {
			while ( $query_glossary->have_posts() ) {
				$query_glossary->the_post();
				$term = get_post();
				$sanitized_title = sanitize_title( trim( $term->post_title,chr(0xC2).chr(0xA0) ) );
				$glossary[ $sanitized_title ] = mywpglossary_process_term( $use_single, $link, $term );
			}
		}
		$keys = array_map('strlen', array_keys( $glossary ) );
		array_multisort($keys, SORT_DESC, $glossary );

		wp_reset_postdata();
		set_transient( $transient_key, json_encode( $glossary ), MYWPGLOSSARY_TERMS_TRANSIENT_EXPIRATION );
		$result = $glossary;
	}elseif( is_array( $cache ) ){
		$result = $cache;
	}
	return $result;
}

function mywpglossary_process_term( $use_single, $link, $post ){
	$sanitized_title = sanitize_title( trim( $post->post_title,chr(0xC2).chr(0xA0) ) );
	$term_link       = ( $use_single ) ? get_permalink( $post ) : $link.'#'.$sanitized_title;
	if( $use_single ){
		//cant call excerpt filter here, this create a infinit loop.
		$term_content = '<a href="'.$term_link.'">'.__( 'read more', 'my-wp-glossary' ).'</a>';
		$term_content = ( has_excerpt( $post ) ) ? '<p>'.$post->post_excerpt.'</p>'.$term_content : $term_content;
	}else{
		$term_content = $post->post_content;
	}
	return [
		'term'    => trim( $post->post_title,chr(0xC2).chr(0xA0) ),
		'content' => apply_filters( 'mywpglossary_display_term_content', $term_content, $post, $sanitized_title, $link.'#'.$sanitized_title ),
		'begin'   => '<span class="mywpglossary-term-def" data-title="'.$sanitized_title.'" data-url="'.$term_link.'">',
		'end'     => '</span>'
	];
}

//----Hooks-------------------------------------------------------------------------------------------------------------

//Create mywpglossary custom post type
function mywpglossary_register_post_type() {
	$use_single = apply_filters( 'mywpglossary_use_single', false );

	$labels = [
		'name'          => esc_html__( 'Definitions', 'my-wp-glossary' ),
		'singular_name' => esc_html__( 'Definition', 'my-wp-glossary' ),
		'menu_name'     => esc_html__( 'Glossary', 'my-wp-glossary' ),
		'add_new'       => esc_html__( 'Add new', 'my-wp-glossary' ),
		'add_new_item'  => esc_html__( 'Add new definition', 'my-wp-glossary' ),
		'new_item'      => esc_html__( 'New definition', 'my-wp-glossary' ),
		'edit_item'     => esc_html__( 'Edit definition', 'my-wp-glossary' ),
		'view_item'     => esc_html__( 'View definition', 'my-wp-glossary' ),
		'all_items'     => esc_html__( 'All definitions', 'my-wp-glossary' ),
		'search_items'  => esc_html__( 'Search definitions', 'my-wp-glossary' ),
	];
	$args = [
		'labels'              => $labels,
		'public'              => ( $use_single ) ? true : false,
		'publicly_queryable'  => ( $use_single ) ? true : false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menu'    => true,
		'show_in_rest'        => true,
		'query_var'           => true,
		'rewrite'             => [ 'slug' => 'glossary' ],
		'capability_type'     => 'page',
		'has_archive'         => false,
		'exclude_from_search' => true,
		'hierarchical'        => false,
		'menu_icon'           => 'dashicons-editor-textcolor',
		'supports'            => ( $use_single ) ? [ 'title', 'editor', 'custom-fields', 'excerpt' ] : [ 'title', 'editor', 'custom-fields' ]
	];
	register_post_type( 'mywpglossary', $args );

	register_post_meta( 'mywpglossary','mywpglossary_letter', [ 'show_in_rest' => true, 'single' => true, 'type' => 'string' ] );
}
add_action( 'init', 'mywpglossary_register_post_type' );

//Glossary terms list screen ordering behavior
function mywpglossary_list_screen_posts_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'mywpglossary' !== $query->get( 'post_type' ) ) {
		return;
	}
	$orderby = $query->get( 'orderby' );
	switch ( $orderby ) {
		case 'mywpglossary_sort_date' :
			$query->set( 'orderby', 'modified' );
			break;
		case '' : //empty default
			$query->set( 'order', 'ASC' );
		case 'mywpglossary_sort_letter' :
			$query->set( 'meta_key', 'mywpglossary_letter' );
			$query->set( 'orderby', 'meta_value title' );
		default :
			break;
	}

}
add_action( 'pre_get_posts', 'mywpglossary_list_screen_posts_order' );

//Add column on the glossary terms list screen
function mywpglossary_list_screen_add_column( $columns ) {
	$columns = [
		'cb'                         => '<input type="checkbox" />',
		'title'                      => esc_html__( 'Glossary definitions', 'my-wp-glossary' ),
		'mywpglossary-letter'        => esc_html__( 'Letter', 'my-wp-glossary' ),
		'mywpglossary-date-modified' => esc_html__( 'Last modified', 'my-wp-glossary' ),
	];

	return $columns;
}
add_filter( 'manage_mywpglossary_posts_columns', 'mywpglossary_list_screen_add_column' );

//add sort on the glossary terms list screen
function mywpglossary_list_screen_sortable_columns( $columns ) {
	$columns[ 'mywpglossary-letter' ]        = 'mywpglossary_sort_letter';
	$columns[ 'mywpglossary-date-modified' ] = 'mywpglossary_sort_date';

	return $columns;
}
add_filter( 'manage_edit-mywpglossary_sortable_columns', 'mywpglossary_list_screen_sortable_columns' );

//Column content for the glossary terms list screen
function mywpglossary_list_screen_fill_column( $column, $ID ) {
	global $post;
	switch ( $column ) {
		case 'mywpglossary-letter' :
			if ( get_post_meta( $post->ID, 'mywpglossary_letter', true ) ) {
				echo get_post_meta( $post->ID, 'mywpglossary_letter', true );
			} else {
				esc_html_e( 'Yet undefined.', 'my-wp-glossary' );
			}
			break;
		case 'mywpglossary-date-modified' :
			$d = get_date_from_gmt( $post->post_modified, 'Y-m-d H:i:s' );
			echo sprintf(
				/* translators: %1$s: Date the definition was last modified. %2$s: Time the definition was last modified. */
				esc_html__( '%1$s at %2$s', 'my-wp-glossary' ),
				date_i18n( get_option( 'date_format' ), strtotime( $d ) ),
				date_i18n( get_option( 'time_format' ), strtotime( $d ) )
			);
			if ( get_post_meta( $ID, '_edit_last', true ) ) {
				$last_user = get_userdata( get_post_meta( $ID, '_edit_last', true ) );
				echo ' ' . esc_html__( 'by', 'my-wp-glossary' ) . ' ' . $last_user->display_name;
			}
			break;
		default :
			break;
	}
}
add_action( 'manage_mywpglossary_posts_custom_column', 'mywpglossary_list_screen_fill_column', 10, 2 );

//Add an indexation tool on the glossary terms list screen
function mywpglossary_display_index_button( $views ){
	if ( current_user_can( 'edit_posts' ) ) {
		wp_register_script(
			'mywpglossary-list',
			plugin_dir_url( __FILE__ ) . 'js/mywpglossary_list_indexation.min.js',
			[ 'jquery' ],
			'',
			true
		);
		wp_localize_script( 'mywpglossary-list', 'mywpglossary_admin', [
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'rest_url' => get_rest_url(),
		] );
		wp_enqueue_script( 'mywpglossary-list' );

		$post_types = get_post_types( [ 'public'   => true, ], 'object' );
		echo' <button id="mywpglossary-index" style="float:right" class="button" name="mywpglossary-index" value="index-terms" >'.__( 're-index glossary terms', 'my-wp-glossary').'</button>';
	}
	return $views;
}
add_filter( 'views_edit-mywpglossary', 'mywpglossary_display_index_button' );

//Add letter meta box on term glossary edit screen
function mywpglossary_add_meta_box() {
	add_meta_box(
		'mywpglossary_section',
		esc_html__( 'Definition’s letter', 'my-wp-glossary' ),
		'mywpglossary_meta_box_render',
		'mywpglossary',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'mywpglossary_add_meta_box' );

//Meta-box behavior on term glossary edit screen
function mywpglossary_save_metaboxes( $post_id, $data ) {
	if( ! isset( $_POST[ 'meta-box-nonce' ] ) || ! wp_verify_nonce( $_POST[ 'meta-box-nonce' ], basename( __FILE__ ) ) ){
		return $post_id;
	}
	if( ! current_user_can( 'edit_post', $post_id ) ){
		return $post_id;
	}
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return $post_id;
	}
	if( 'mywpglossary' !== $data[ 'post_type' ] ){
		return $post_id;
	}
	$letter = '';
	if ( isset( $_POST[ 'meta_box_mywpglossary_letter' ] ) && in_array( $_POST[ 'meta_box_mywpglossary_letter' ], myglossary_get_alpha() ) ) {
		$letter = esc_html( $_POST[ 'meta_box_mywpglossary_letter' ] );
	}else{
		$letter = strtoupper( trim( substr( $data[ 'post_title' ], 0, 1 ) ) );
		if( ! in_array( $letter, range( 'A', 'Z' ) ) ){
			$letter = ( in_array( '#', myglossary_get_alpha() ) ) ? '#' : '';
		}
	}
	update_post_meta( $post_id, 'mywpglossary_letter', $letter );
}
add_action( 'pre_post_update', 'mywpglossary_save_metaboxes', 10, 3 );

/**
 * mywpglossary_update_term
 * update cache when term is insert or updated
 *
 *
 * @param $postid
 * @param WP_Post $post
 * @return void
 */
function mywpglossary_update_term( $post_id, WP_Post $post, $updated, WP_Post $post_before = null ){
	if( 'mywpglossary' === $post->post_type ){
		global $wpdb;
		$transient_key     = MYWPGLOSSARY_TERMS_TRANSIENT_KEY.( ( function_exists( 'pll_current_language' ) ) ? '_'.pll_current_language( 'slug' ) : '' );
		$glossary          = get_transient_glossary();
		$use_single        = apply_filters( 'mywpglossary_use_single', false );
		$query_shortcode   = "SELECT ID, post_title FROM " . $wpdb->posts . " WHERE ( post_content LIKE '%<!-- wp:mywpglossary/glossary /-->%' OR post_content LIKE '%[glossary]%' ) AND post_status = 'publish' ORDER BY CHAR_LENGTH( post_title ) ASC";
		$results_shortcode = $wpdb->get_row( $wpdb->prepare( $query_shortcode, [] ) );
		$link              = apply_filters( 'override_glossary_link', get_the_permalink( $results_shortcode->ID ) ); //Deprecated filter
		$link              = apply_filters( 'mywpglossary_override_glossary_link', $link );

		$sanitized_title   = sanitize_title( trim( $post->post_title,chr(0xC2).chr(0xA0) ) );
		//term title has changed so remove cache entry
		if( ! is_null( $post_before ) ){
			if( $updated && $post->title !== $post_before->title ){
				$sanitized_old_title   = sanitize_title( trim( $post_before->post_title,chr(0xC2).chr(0xA0) ) );
				unset( $glossary[ $sanitized_old_title ] );
			}
		}
		//check post status before update if is not publish remove entry from cache if it exists.
		if( 'publish' === $post->post_status ){
			$glossary[ $sanitized_title ] = mywpglossary_process_term( $use_single, $link, $post );
		}elseif( isset( $glossary[ $sanitized_title ] ) ){
			unset( $glossary[ $sanitized_title ] );
		}
		$keys = array_map('strlen', array_keys( $glossary ) );
		array_multisort($keys, SORT_DESC, $glossary );
		set_transient( $transient_key, json_encode( $glossary ), MYWPGLOSSARY_TERMS_TRANSIENT_EXPIRATION );
	}
}
add_action( 'wp_after_insert_post', 'mywpglossary_update_term', 10, 4 );

/**
 * mywpglossary_delete_term
 * update cache when a term is deleted
 *
 * @param $postid
 * @param WP_Post $post
 * @return void
 */
function mywpglossary_delete_term( $postid, WP_Post $post ){
	if( 'mywpglossary' === $post->post_type ){
		$transient_key   = MYWPGLOSSARY_TERMS_TRANSIENT_KEY.( ( function_exists( 'pll_current_language' ) ) ? '_'.pll_current_language( 'slug' ) : '' );
		$glossary        = get_transient_glossary();
		$sanitized_title = sanitize_title( trim( $post->post_title,chr(0xC2).chr(0xA0) ) );
		if( isset( $glossary[ $sanitized_title ]  ) ){
			unset( $glossary[ $sanitized_title ] );
			set_transient( $transient_key, json_encode( $glossary ), MYWPGLOSSARY_TERMS_TRANSIENT_EXPIRATION );
		}
	}
}
add_action( 'delete_post', 'mywpglossary_delete_term', 10, 2 );

//Shortcode declaration
function mywpglossary_init_shortcode() {
	function mywpglossary_shortcode() {
		$html = mywpglossary_template_render();
		return $html;
	}
	add_shortcode( 'glossary', 'mywpglossary_shortcode' );
}
add_action( 'init', 'mywpglossary_init_shortcode' );

//Glossary block declaration
function mywpglossary_init_block() {
	if ( function_exists( 'register_block_type' ) ) {
		register_block_type(
			'mywpglossary/glossary',
			[
				'editor_script'   => 'mywpglossary-block',
				'render_callback' => 'mywpglossary_block_render',
				'attributes'      => [],
			]
		);
	}
}
add_action( 'init', 'mywpglossary_init_block', 11 );

function mywpglossary_register_styles(){
	wp_register_style(
		'mywpglossary-front-style',
		plugin_dir_url( __FILE__ ).'css/style.min.css',
		[],
		'',
		'all'
	);
}
add_action( 'init', 'mywpglossary_register_styles' );

function mywpglossary_register_scripts(){
	wp_register_script(
		'mywpglossary-block',
		plugin_dir_url( __FILE__ ) . 'js/mywpglossary_block.min.js',
		[ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components' ]
	);

	wp_register_script( 'popper.js', 'https://unpkg.com/@popperjs/core@2' );
	wp_register_script( 'tippy.js', 'https://unpkg.com/tippy.js@6', [ 'popper.js' ]);

	wp_register_script( 'mywpglossary-modal',
		plugin_dir_url( __FILE__ ) . 'js/mywpglossary_modal.min.js',
		[ 'jquery', 'tippy.js' ],
		'',
		true
	);

	wp_localize_script( 'mywpglossary-modal', 'mywpglossary', [
		'tag_limit'    => apply_filters( 'mywpglossary_override_tag_limit', - 1 ),
		'display_mode' => apply_filters( 'mywpglossary_insertion_style', 'link' ),
		'tippy_theme'  => apply_filters( 'mywpglossary_tippy_theme', 'light' ),
	] );

	wp_register_script( 'mywpglossary-glossary',
		plugin_dir_url( __FILE__ ) . 'js/mywpglossary_glossary.min.js',
		[ 'jquery' ],
		'',
		true
	);
}
add_action( 'init', 'mywpglossary_register_scripts', 20 );

//Enqueue front script and style
function mywpglossary_public_enqueue() {
	wp_enqueue_style( 'mywpglossary-front-style' );
	wp_enqueue_script( 'mywpglossary-modal' );
	wp_enqueue_script( 'mywpglossary-glossary' );
}

//Perform terms matching on get_the_content and the_content core function
function mywpglossary_matching( $content ) {
	$default = ( ( is_singular() || in_the_loop() ) && is_main_query() && ! has_block( 'mywpglossary/glossary' ) && ! has_shortcode( $content, 'glossary' ) );
	if ( apply_filters( 'mywpglossary_matching', $default ) ) {
		$use_single = apply_filters( 'mywpglossary_use_single', false );
		$terms      = mywpglossary_matching_terms( $content, get_the_id() );
		if( ! empty( $terms ) ){
			$sanitized_title = sanitize_title( trim( get_post()->post_title,chr(0xC2).chr(0xA0) ) );

			if( 'mywpglossary' === get_post_type() && $use_single && isset( $terms[ $sanitized_title ] ) ){
				unset( $terms[ $sanitized_title ] );
			}

			mywpglossary_public_enqueue();
			wp_localize_script( 'mywpglossary-modal', 'mywpglossary_terms', apply_filters( 'mywpglossary_override_term', $terms ) );
		}
	}
	return $content;
}
add_filter( 'the_content', 'mywpglossary_matching', 10 );

//----Render------------------------------------------------------------------------------------------------------------

function myglossary_get_alpha(){
	return apply_filters( 'mywpglossary_alpha', array_merge( [ '#' ], range( 'A', 'Z' ) ) );
}

//Display letter meta-box into term glossary edit screen
function mywpglossary_meta_box_render( $object ) {
	wp_nonce_field( basename( __FILE__ ), 'meta-box-nonce' );

	$letter        = ( get_post_meta( $object->ID, 'mywpglossary_letter', true ) ) ? get_post_meta( $object->ID, 'mywpglossary_letter', true ) : '';
	$alphas_option = array_map( function( $a )use( $letter ){
		$s = ( $a === $letter ) ? ' selected="selected"' : '';
		return '<option value="'.$a.'"'.$s.'>'.$a.'</option>';
	}, myglossary_get_alpha() );
?>
	<p><label for="meta-box-departement"><?php esc_html_e( 'Select a letter', 'my-wp-glossary' ); ?></label></p>
	<p>
		<select name="meta_box_mywpglossary_letter" id="meta_box_mywpglossary_letter">
			<option value=""><?php esc_html_e( '— Select —', 'my-wp-glossary' ); ?></option>
			<?php echo implode( '', $alphas_option ); ?>
		</select>
	</p>
	<script>
		( function ( $ ){
			'use strict';
			$( window ).load( function (){
				let dispatch = wp.data.dispatch( 'core/edit-post' );
				let callback = dispatch.metaBoxUpdatesSuccess;
				dispatch.metaBoxUpdatesSuccess = function( ...args ) {
					let node = $( "#meta_box_mywpglossary_letter" )
					if( 1 !== node.val().length ){
						let post_title = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'title' )
						let letter     = post_title.trim().charAt(0).toUpperCase()
						let range      = <?php echo json_encode( myglossary_get_alpha() ).PHP_EOL; ?>
						letter         = ( range.includes( letter ) ) ? letter : ( ( range.includes( '#' ) ) ? '#' : '' )
						node.children( 'option[value="'+letter+'"]').prop( 'selected', true )
					}
					return callback.apply( this, args )
				}
			} )
		} )( jQuery )
	</script>
<?php
}

//Display the glossary
function mywpglossary_template_render() {
	$args           = [
		'post_type'      => 'mywpglossary',
		'posts_per_page' => apply_filters( 'mywpglossary_glossary_term_limit', 200 ),
	];
	$query_glossary = new WP_Query( $args );
	if( $query_glossary->have_posts() ){
		$glossary   = [];
		$alphas     = myglossary_get_alpha();
		$use_single = apply_filters( 'mywpglossary_use_single', false );

		while( $query_glossary->have_posts() ){
			$query_glossary->the_post();
			$post        = get_post();
			$title       = get_the_title();
			$content     = ( $use_single ) ? get_the_excerpt() : get_the_content();
			$url         = ( $use_single ) ? get_the_permalink() : false;
			$letter_meta = get_post_meta( $post->ID, 'mywpglossary_letter', true );
			$letter      = ( $letter_meta ) ? $letter_meta : strtoupper( $title[ 0 ] );
			if( empty( $content ) || ! in_array( $letter, $alphas ) ){ continue; }
			$glossary[ $letter ][ sanitize_title( get_the_title() ) ] = [
				'post'       => $post,
				'title'      => $title,
				'content'    => $content,
				'url'        => $url,
			];
			if( $use_single ){
				$glossary[ $letter ][ sanitize_title( get_the_title() ) ][ 'url' ] = get_the_permalink();
			}

		}
		ksort( $glossary );

		// Wrapper
		$html   = '<div class="mywpglossary">';
		// Navigation
		$html   .= '<div class="mywpglossary-letters">';
		foreach ( $alphas as $alpha ) {
			if ( isset( $glossary[ $alpha ] ) ) {
				$html .= '<a href="#mywpglossary-letter-' . $alpha . '" class="mywpglossary-letter">' . $alpha . '</a>';
			} else {
				$html .= '<span class="mywpglossary-letter inactive">' . $alpha . '</span>';
			}
		}
		$html .= '</div>';
		// Navigation end
		// List
		$html .= '<div itemscope itemtype="https://schema.org/DefinedTermSet" class="mywpglossary-list">';
		foreach ( $glossary as $letter => $entry ) {
			ksort( $entry );
			$html .= '<div id="mywpglossary-letter-' . $letter . '" class="mywpglossary-list-letter">' . $letter . '</div>';
			$html .= '<div class="mywpglossary-letter-content">';
			foreach ( $entry as $slug => $entry_data ) {
				if( empty( $entry_data['content'] ) ){ continue; }
				$html .= '<div itemscope itemtype="https://schema.org/DefinedTerm" class="mywpglossary-list-entry">';
				$html .= '<div itemprop="name" id="mywpglossary-term-' . $slug . '" class="mywpglossary-list-entry-title">' . $entry_data[ 'title' ] . '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>' . '</div>';
				$html .= '<div itemprop="description" class="mywpglossary-list-entry-description" data-glossary="mywpglossary-term-' . $slug . '">';
				$html .= '<p>'.apply_filters( 'mywpglossary_glossary_term_content', $entry_data[ 'content' ], $letter, $slug, $entry_data ).'</p>';
				$html .= ( $entry_data[ 'url' ] ) ? '<a href="'.$entry_data[ 'url' ].'">'.__( 'read more ...', 'my-wp-glossary' ).'</a>' : '';
				$html .= '</div>';
				$html .= '</div>';
			}
			$html .= '</div>';
		}
		// List end
		$html .= '</div>';
		// Wrapper end
		$html .= '</div>';
		wp_reset_postdata();
		$terms = mywpglossary_matching_terms( $content, get_the_id() );
		mywpglossary_public_enqueue();
		wp_localize_script( 'mywpglossary-modal', 'mywpglossary_terms', apply_filters( 'mywpglossary_override_term', $terms ) );
		return apply_filters( 'mywpglossary_glossary_term_archive', $html, $glossary );
	}
	return '';
	wp_reset_postdata();
}

//Display the glossary
function mywpglossary_block_render( $attributes, $content ) {
	$content = mywpglossary_template_render();
	return $content;
}

//----REST--------------------------------------------------------------------------------------------------------------

//rest term indexation route
//use this only if you need mywpglossary_get_terms_by_post()
//TODO redo cant handle large amount of post, add a pagination support
add_action( 'rest_api_init', function(){
	register_rest_route( 'mywpglossary/v1', 'index', [
		'methods' => [ 'GET' ],
		'callback' => function( WP_REST_Request $request ) {
			$post_types     = get_post_types( [ 'public'   => true, ], 'names' );
			$current_page   = $request->get_param( 'p' );
			$posts_per_page = 50;

			$query = new WP_Query( [
				'post_type'       => $post_types,
				'post_status'     => 'publish',
				'posts_per_page'  => $posts_per_page,
				'paged'           => $current_page,
			] );

			$posts         = $query->get_posts();
			$total_posts   = $query->found_posts;
			$max_pages     = ceil( $total_posts / $posts_per_page );
			$current_page  = ( $current_page > $max_pages ) ? $max_pages : $current_page;
			$next_page_url = ( $current_page < $max_pages ) ? get_rest_url( null, "mywpglossary/v1/index/?p=".( $current_page+1 ) ):'';

			$post_count  = 0;
			$terms_count = 0;
			if( ! empty( $posts ) ){
				foreach ( $posts as $post ){
					$terms = mywpglossary_matching_terms( $post->post_content, $post->ID, false );
					$logs [ $post->ID ] = [
						'time'       => date("M,d,Y h:i:s A" ),
						'post_title' => $post->post_title,
						'term_found' => sizeof( $terms ),
						'terms' => $terms,
					];
					$terms_count += sizeof( $terms );
					$post_count++;
				}
			}

			if( 0 < $post_count ){

				$message = ( is_null( $next_page_url ) )
					? esc_html__( 'Terms indexation completed.', 'my-wp-glossary' )
					: esc_html__( 'Terms indexation continue.', 'my-wp-glossary' );

				return new WP_REST_Response( [
					'status'     => 'success',
					'message'    => $message,
					'stats' => [
						'total_posts'     => $total_posts,
						'posts_processed' => $post_count,
						'terms_processed' => $terms_count,
					],
					'pagination' => [
						'current' => $current_page,
						'max'     => $max_pages,
						'next'    => $next_page_url,
					]
				], 200);
			}
			return new WP_REST_Response( [
				'status'  => 'error',
				'message' => esc_html__( 'An error occurred. No indexed post.', 'my-wp-glossary' ) ,
			], 200);
		},
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
		'args' => [
			'p' => [
				'default'           => 1,
				'required'          => false,
				'sanitize_callback' => function( $v ){ return  ceil( ( int ) $v ); },
				'validate_callback' => function( $v ){ return is_numeric( $v ); },
			],
		]
	] );
} );
