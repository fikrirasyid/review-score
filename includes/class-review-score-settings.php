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
	 * Setup predefined fields
	 * 
	 * @return array|bool
	 */
	function predefined_fields(){
		return apply_filters( "{$this->prefix}predefined_fields", false );
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