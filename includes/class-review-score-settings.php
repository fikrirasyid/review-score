<?php
class Review_Score_Settings{
	var $prefix;
	var $prefix_label;

	function __construct(){
		$this->prefix 			= 'review_score_';
		$this->prefix_label 	= '_review_score_label_';
	}

	/**
	 * Define post type that will be supported by this plugin
	 * 
	 * @since 0.1
	 * 
	 * @return array
	 */	
	function post_types(){
		return apply_filters( "{$this->prefix}post_types", array( 'post' ) );
	}

	/**
	 * Define support for comment based vote
	 * 
	 * @return bool
	 */
	function comment_vote_support(){
		return apply_filters( "{$this->prefix}comment_vote_support", false );
	}

	/**
	 * Setup predefined fields
	 * 
	 * @return array|bool
	 */
	function predefined_fields(){
		return apply_filters( "{$this->prefix}predefined_fields", false );
	}

	/**
	 * Limit vote to logged in user only
	 * 
	 * @return bool
	 */
	function enable_guest_to_vote(){
		return apply_filters( "{$this->prefix}enable_guest_to_vote", false );
	}

	/**
	 * Limit vote to once for each user
	 * 
	 * @return bool
	 */
	function only_vote_once(){
		return apply_filters( "{$this->prefix}only_vote_once", true );
	}

	/**
	 * Setting review scale
	 * 
	 * @return int
	 */
	function review_scale(){
		return apply_filters( "{$this->prefix}review_scale", 10 );
	}
}