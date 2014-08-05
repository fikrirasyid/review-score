<?php
class Review_Score_Messages{
	var $prefix;

	function __construct(){
		$this->prefix = 'review_score_';
	}

	/**
	 * Message for non logged in user on vote section
	 * 
	 * @return string
	 */
	function non_logged_in_visitor(){
		return apply_filters( "{$this->prefix}message_for_non_logged_in_visitor", __( 'Please log in to vote for this item.', 'review-score' ) );
	}

	/**
	 * Message for voted user: user can only vote once
	 * 
	 * @return string
	 */
	function voted_user(){
		return apply_filters( "{$this->prefix}message_for_voted_user", __( 'You have voted for this item. Thank you.', 'review-score' ) );
	}
}