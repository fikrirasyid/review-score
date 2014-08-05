<?php
class Review_Score_Frontend_Setup{
	var $settings;
	var $review_score;

	function __construct(){
		$this->settings = new Review_Score_Settings;
		$this->review_score = new Review_Score;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_filter( 'the_content', array( $this, 'display' ) );
		add_action( 'comment_form', array( $this, 'display_comment_vote' ) );
		add_action( 'comment_post', array( $this, 'vote_save' ) );		
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

		wp_register_style( 'review-score-frontend', REVIEW_SCORE_URL . '/css/review-score-frontend.css', array(), false, 'screen' );
		wp_register_script( 'review-score-frontend', REVIEW_SCORE_URL . '/js/review-score-frontend.js', array( 'jquery' ), false, false );
	    wp_enqueue_style( 'review-score-frontend' );

	    // The front end js is only relevant for voting mechanism
	    $fields = $this->review_score->get_review_score( $post->ID );		
		if( $this->review_score->is_display_review_score() && $this->settings->comment_vote_support() && !empty( $fields ) ){
			wp_enqueue_script( 'review-score-frontend' );					
		}
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
				$review_score .= '<div class="review-score-average-score">' . get_post_meta( $post->ID, '_review_score_average', true ) . '</div>';
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

	/**
	 * Dispkat comment vote
	 * 
	 * @return void
	 */
	function display_comment_vote(){
		global $post, $current_user;

    	$fields = $this->review_score->get_review_score( $post->ID );		
		if( $this->review_score->is_display_review_score() && $this->settings->comment_vote_support() && !empty( $fields ) ):		

		// Only display comment field to logged in user
		if( is_user_logged_in() == $this->settings->enable_guest_to_vote() ){
			?>
			<div id="review-score-vote-message" class="review-score-message not-logged-in">
				<p><?php echo $this->messages->non_logged_in_visitor(); ?></p>
			</div>
			<?php
			return;
		}

		// If user has voted and we're setting it so
		$voters = get_post_meta( $post->ID, '_review_score_voters', true );
		if( !is_array( $voters ) ){
			$voters = array();
		}

		if( is_user_logged_in() && in_array( $current_user->data->ID, $voters ) ){
			?>
			<div id="review-score-voted-message" class="review-score-message not-logged-in">
				<p><?php echo $this->messages->voted_user(); ?></p>
			</div>
			<?php
			return;
		}

		?>
            <div class="review-score-wrap">
                <!-- <h2 class="section-title review-score-title" style="">Review Score</h2> -->
                <div class="review-score-content" style="padding-left: 0; font-size: .7em;">
                <?php foreach ($fields as $key => $field) { ?>
                    <div class="review-score-item">
                        <div class="review-score-item-label"><?php echo $field['label']; ?></div>
                        <div class="review-score-item-score">-</div>
                        <div class="review-score-item-bar" data-score="0">
                        	<?php echo $this->review_score->score_to_stars( 0 ); ?>
                        </div>
                        <div class="review-score-item-select">
                        	<?php $this->review_score->select_score( $key ); ?>
                        </div>
                    </div>
        		<?php } ?>
                </div>
            </div>
		<?php
		endif;
	}

	/**
	 * Hook vote-saving mechanism
	 * 
	 * @param comment ID
	 * 
	 * @return void
	 */ 
	function vote_save( $comment_id ){
		global $wpdb, $current_user;

		$post_id = $_POST['comment_post_ID'];
		$prefix_length = strlen( $this->settings->prefix_label );

		// Is this vote?
		$is_vote = false;

		// Collect all _review_score_label, calculate its average
		$review_score_total = array();		

		// Save each metadata to the database
		foreach ($_POST as $key => $field) {
			if( substr( $key, 0, $prefix_length) == $this->settings->prefix_label ){
				// Prevent double meta submission
				$existing_comment_meta = get_comment_meta( $comment_id, $key, true );
				if( $existing_comment_meta ){
					break;
				}

				// Yes, this is vote
				$is_vote = true;				

				// Save comment metadata
				$add_comment_meta = add_comment_meta( $comment_id, $key, intval( $field ) );

				// Get all value of existing key for given post
				$sql = $wpdb->prepare( "SELECT m.meta_value FROM $wpdb->comments c LEFT JOIN $wpdb->commentmeta m ON (m.comment_id = c.comment_ID) WHERE c.comment_post_id = %d AND m.meta_key = %s", $_POST['comment_post_ID'], $key );
				$sql_result = $wpdb->get_results( $sql );

				// Convert sql result into simple array
				$scores = array();
				foreach ($sql_result as $row) {
					array_push( $scores, $row->meta_value);
				}

				// Calculate value
				$scores_sum = array_sum( $scores );
				$scores_count = count( $scores );
				$scores_avg = round( $scores_sum / $scores_count );

				// Push the avg value
				array_push( $review_score_total, $scores_avg );

				// Save the value
				$update_post_meta = update_post_meta( $_POST['comment_post_ID'], $key, $scores_avg );
			}
		}

		if( $is_vote ){
			// Calculate the average then save it
			$review_score_sum = array_sum( $review_score_total );
			$review_score_count = count( $review_score_total );
			$review_score_avg = $review_score_sum / $review_score_count;
			update_post_meta( $_POST['comment_post_ID'], '_review_score_average', $review_score_avg );		

			// Mark current user ID
			if( is_user_logged_in() ){
				$current_user_id = $current_user->data->ID;
				$voters = get_post_meta( $_POST['comment_post_ID'], '_review_score_voters', true );
				if( empty( $voters ) || !isset( $voters ) ){
					$voters = array();
				}
				array_push( $voters, $current_user_id );

				// Save recently added voters information
				update_post_meta( $_POST['comment_post_ID'], '_review_score_voters', $voters );
			}
		}

		return;
	}
}
new Review_Score_Frontend_Setup;