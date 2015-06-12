<?php 

/*
Plugin Name: BbPress Latest Topic Shortcode
Plugin URI: calvincanas.com
Description: Enhanced [bbp-forum-index]
Author: Calvin Canas
Author URI: http://calvincanas.com
Version: 1.0
*/


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'Bb_Pootle' ) ):

class Bb_Pootle {

	public $codes;

	public function __construct() {

		$this->setup_shortcodes();
		$this->add_shortcodes();
	}

	/**
	 * Setup the shortcodes along with their respective shortcode
	 * This is scalable just put it as array key($tag) and value($function)
	 * @return void
	 */
	private function setup_shortcodes() {

		$this->codes = apply_filters( 'bbpootle_shortcodes', array(

				/**
				 * Forums
				 */
				'bbpootle-forum-index'		=>		array( $this, 'display_chosen_forums' ),

				'bbpootle-topic-index'		=>		array( $this, 'display_chosen_topics' ),	
			)
		);
	}

	/**
	 * This will be the engine that will register shortcodes base on the $codes array 
	 * keys and values
	 */
	private function add_shortcodes() {
		foreach ($this->codes as $code => $function) {
			add_shortcode( $code, $function );
		}
	}

	/**
	 * Unset some globals in the $bbp object that hold query related info
	 * Will also restore $post
	 * @return void
	 */
	public function unset_globals() {
		$bbp = bbpress();

		// Unset global queries
		$bbp->forum_query  = new WP_Query();
		$bbp->topic_query  = new WP_Query();
		$bbp->reply_query  = new WP_Query();
		$bbp->search_query = new WP_Query();

		// Unset global ID's
		$bbp->current_view_id      = 0;
		$bbp->current_forum_id     = 0;
		$bbp->current_topic_id     = 0;
		$bbp->current_reply_id     = 0;
		$bbp->current_topic_tag_id = 0;

		// Reset the post data
		wp_reset_postdata();
	}

	/**
	 * This actually exist in bbPress and I don't know why they set this into
	 * private method since it is useful for plugin extension
	 */
	public function start( $query_name = '' ) {

		// Set query name
		bbp_set_query_name( $query_name );

		// Start output buffer
		ob_start();
	}

	/**
	 * This actually exist in bbPress and I don't know why they set this into
	 * private method since it is useful for plugin extension
	 */
	public function end() {

		// Unset globals
		$this->unset_globals();

		// Reset the query name
		bbp_reset_query_name();

		// Return and flush the output buffer
		return ob_get_clean();
	}

	
	/**
	 * The function behind shortcode [bbpootle]
	 * @param  array $attr     This will catch the param value we got from user
	 * @param  string $content [description]
	 * @return string
	 */
	public function display_chosen_forums( $attr, $content = '' ) {
		
		// Sanity check required info
		if ( !empty( $content ) )
			return $content;
		$this->unset_globals();

		//if no forum_id then show all
		if( empty( $attr['forum_id'] ) ) {
			$bbp_shortcodes = bbpress()->shortcodes;
			return $bbp_shortcodes->display_forum_index();
		}

		

		global $forum;
		$forum = $attr['forum_id'];

		if ( ! bbp_is_forum_archive() ) {
			add_filter( 'bbp_before_has_forums_parse_args', array( $this, 'display_forum_query_args' ) );
		}

		// Start output buffer
		$this->start( 'bbp_forum_archive' );
		

		// Output template
		bbp_get_template_part( 'content', 'archive-forum' );

		// Return contents of output buffer
		return $this->end();

	}

	
	function display_chosen_topics( $attr, $content = '' ) {

		//sanity check
		if( !empty( $content ) ) return $content;

		$this->unset_globals();

		//display all topic index when no id attribute pass
		if( empty( $attr['id'] ) ) {

			$bbp_shortcodes = bbpress()->shortcodes;
			return $bbp_shortcodes->display_topic_index();

		}

		global $forum;

		$forum = $attr['id'];


		// Filter the query
		if ( ! bbp_is_topic_archive() ) {
			add_filter( 'bbp_before_has_topics_parse_args', array( $this, 'display_topic_query_args' ) );
		}

		// Start output buffer
		$this->start( 'bbp_topic_archive' );

		// Output template
		bbp_get_template_part( 'content', 'archive-topic' );

		// Return contents of output buffer
		return $this->end();


	}

	/**
	 * Put the right value to our query args
	 * @param  array $args this will be the argument we will parse to query specific forum
	 * @return array       enhanced argument
	 */
	function display_forum_query_args( $args ) {
		global $forum ;
		
		
		// split the string into pieces
		$forums = explode(',', $forum);
				
		$args['post__in'] = $forums;
		$args['post_parent'] = '';
		$args['orderby'] = 'post__in';
		return $args;
	}

	function display_topic_query_args( $args ) {
		global $forum;
		// split the string into pieces
		$forums = explode(',', $forum);
		$args['author']        = 0;
		$args['show_stickies'] = true;
		$args['order']         = 'DESC';
		$args['post_parent']	= '';
		$args['post_parent__in'] = $forums;
		return $args;
	}	

}

//launch the engine
return new Bb_Pootle();

endif;




