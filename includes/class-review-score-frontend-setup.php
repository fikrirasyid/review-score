<?php
class Review_Score_Frontend_Setup{
	var $settings;
	var $review_score;

	function __construct(){
		$this->settings = new Review_Score_Settings;
		$this->review_score = new Review_Score;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_filter( 'the_content', array( $this, 'display' ) );
	}

	/**
	 * Adding scripts & stylesheet to front end page
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */	
	function enqueue_scripts_styles(){
		global $post;

		// If this isn't a single page, bail
		if( !is_singular() )
			return;

		wp_enqueue_style( 'review-score-frontend', REVIEW_SCORE_URL . '/css/review-score-frontend.css', array(), false, 'screen' );
	}

	/**
	 * Display review score on the_content
	 * 
	 * @param string of content
	 * 
	 * @return string of modified content
	 */
	function display( $content ){
		global $post;

		if( $this->review_score->is_display_review_score() ){
			$scores = $this->review_score->get_review_score( $post->ID );

			$review_score = '<div class="review-score-wrap">';
			$review_score .= '<h2 class="section-title review-score-title">'. apply_filters( "{$this->settings->prefix}title_label", __( "Review Score", "review-score" ) ) .'</h2>';
			$review_score .= '<div class="review-score-content">';
			if( !empty( $scores ) ){
				$review_score .= '<div class="review-score-average">';
				$review_score .= '<div class="review-score-average-label">' . apply_filters( "{$this->settings->prefix}average_score_label", __( "Average Score", "review-score" ) ) . '</div>';
				$review_score .= '<div class="review-score-average-score">' . round( get_post_meta( $post->ID, '_review_score_average', true ), 2 ) . '</div>';
				$review_score .= '</div>';

				// Print review score data
				foreach ( $scores as $key => $score ) {
					$review_score .= '<div class="review-score-item">
										<div class="review-score-item-label">'. $score["label"] .'</div>
										<div class="review-score-item-score">'. $score["value"] .'</div>
										<div class="review-score-item-bar" data-score="'. $score["value"] .'">'. $this->review_score->score_to_stars( $score["value"] ) .'</div>
									</div>';
				}
			}
			$review_score .= '</div>';
			$review_score .= '</div>';
			$content .= $review_score;			
		}

		return $content;
	}
}
new Review_Score_Frontend_Setup;