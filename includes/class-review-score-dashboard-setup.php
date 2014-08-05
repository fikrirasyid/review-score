<?php
class Review_Score_Dashboard_Setup{
	var $settings;
	var $review_score;

	function __construct(){
		$this->settings = new Review_Score_Settings;
		$this->review_score = new Review_Score;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_scripts_styles' ) );		
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes_add' ) );
		add_action( 'save_post', array( $this, 'meta_box_save' ) );		
	}

	/**
	 * Adding scripts & stylesheet to editor page
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */
	function enqueue_dashboard_scripts_styles(){
		$screen = get_current_screen();

		if( isset( $screen->id ) && in_array( $screen->id, $this->settings->post_types() ) ){

			// register style and script
			wp_enqueue_style( 'review-score-editor', REVIEW_SCORE_URL . '/css/review-score-editor.css', array(), false, 'screen' );
			wp_enqueue_script( 'review-score-editor', REVIEW_SCORE_URL . '/js/review-score-editor.js', array( 'jquery' ), false, false );

		}
	}

	/**
	 * Add Meta Box
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */	
	function meta_boxes_add(){
		foreach ( $this->settings->post_types() as $post_type ) {
			add_meta_box('review-score', __( 'Review Score', 'review-score'), array( $this, 'meta_box' ), $post_type );
		}
	}

	/**
	 * Meta Box
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */	
	function meta_box(){	
		global $post;

		// Adding Nonce
		wp_nonce_field( 'review_score_nonce', '_wpnonce_review_score_nonce' );		

		// Check review score usage
		if( $this->review_score->is_use_review_score() ){
			$review_score_visibility = 'style="display: block;"';
			$review_score_use_check = 'checked="checked"';
		} else {
			$review_score_visibility = 'style="display: none;"';
			$review_score_use_check = '';
		}

		// Get stored value
		$review_score = $this->review_score->get_review_score( $post->ID );
		?>
			<p><label for="_review_score_use"><input type="checkbox" name="_review_score_use" value="yes" id="_review_score_use" <?php echo $review_score_use_check; ?>><?php _e( 'Use Review Score for this content.', 'review-score' ); ?></label></p>
			
			<div id="review-score-post-settings" <?php echo $review_score_visibility; ?>>
				<h3><?php _e( 'Review Aspects', 'review-score' ); ?></h3>
				<table cellspacing="0" class="review-aspects">
					<thead>
						<tr>
							<th><?php _e( 'Aspects', 'review-score' ); ?></th>
							<th><?php _e( 'Score', 'review-score' ); ?></th>

							<?php if( !$this->settings->predefined_fields() ) : ?>
							<th></th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php 
							if( empty( $review_score ) ){
								echo '<tr id="no-review-score"><td colspan="3">'. __( 'no review score, yet.', 'review-score' ) .'</td></tr>';
							} else {
								foreach ($review_score as $key => $aspect) {
									?>
									<tr>
										<td><?php echo $aspect['label']; ?></td>
										<td>
											<?php $this->review_score->select_score( $key, $aspect['value'] ); ?></span>
										</td>
										<?php if( !$this->settings->predefined_fields() ) : ?>
										<td>
											<a href="#" class="remove-review-aspect"><?php _e( 'Remove', 'review-score' ); ?></a>
										</td>
										<?php endif; ?>
									</tr>
									<?php
								}
							}
						?>					
					</tbody>					 
					<tfoot>
						<tr>
							<td>
								<?php _e( 'Average Score', 'review-score' ); ?>
							</td>
							<td colspan="2">
								<?php echo round( get_post_meta( $post->ID, '_review_score_average', true ), 2 ); ?>
							</td>
						</tr>
					</tfoot> 					
				</table>

				<?php if( !$this->settings->predefined_fields() ): ?>
				<h3><?php _e( 'Add New Aspect', 'review-score' ); ?></h3>
				<p>
					<input id="new-review-aspect" type="text" placeholder="Type New Aspect Here..">
					<button id="add-review-aspect" class="button"><?php _e( 'Add', 'review-score' ); ?></button>
				</p>				
				<?php endif; ?>
			</div><!-- #review-score-post-settings -->

			<script type="text/template" id="template-aspect">
				<tr>
					<td>{0}</td>
					<td>
						<?php $this->review_score->select_score( '', false, true ); ?> / 10</span>
					</td>
					<td>
						<a href="#" class="remove-review-aspect"><?php _e( 'Remove', 'review-score' ); ?></a>
					</td>
				</tr>
			</script>
		<?php
	}

	/**
	 * Saving Meta Box value to post_meta
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */	
	function meta_box_save( $post_id ){
		$screen = get_current_screen();

		// If this isn't review score editor, bail
		if ($screen != null && !in_array( $screen->post_type, $this->settings->post_types() ) ) return;

		// Bail if we're doing an auto save
		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

		// if our nonce isn't there, or we can't verify it, bail
		if( !isset( $_POST['_wpnonce_review_score_nonce'] ) || !wp_verify_nonce( $_POST['_wpnonce_review_score_nonce'], 'review_score_nonce' ) ) return;

		// if our current user can't edit this post, bail
		if( !current_user_can( 'edit_posts' ) ) return;

		// Updating process		

		// Update review score usage status
		if( isset( $_POST['_review_score_use'] ) && $_POST['_review_score_use'] == 'yes' ){
			update_post_meta( $post_id, '_review_score_use', 'yes' );
		} else {
			update_post_meta( $post_id, '_review_score_use', 'no' );
			return;
		}

		// Get current value. We'll match it later for deleting purpose
		$review_score_to_be_deleted = $this->review_score->get_review_score( $post_id );

		// Collect all _review_score_label, calculate its average
		$review_score_total = array();

		// Find review score key and save it to the DB
		foreach ($_POST as $key => $value) {
			if( substr( $key, 0, strlen( $this->settings->prefix_label ) ) === $this->settings->prefix_label ){
				unset( $review_score_to_be_deleted[$key] );
				update_post_meta( $post_id, $key, intval( $value ) );				

				// push value to review score total
				array_push( $review_score_total, intval( $value ) );
			}
		} 

		// Delete "removed" review score
		if( !empty( $review_score_to_be_deleted ) ){
			foreach ($review_score_to_be_deleted as $key => $post_meta) {
				delete_post_meta( $post_id, $key, $post_meta['value'] );
			}
		}

		// Calculate the average then save it
		$review_score_sum = array_sum( $review_score_total );
		$review_score_count = count( $review_score_total );
		$review_score_avg = $review_score_sum / $review_score_count;
		update_post_meta( $post_id, '_review_score_average', $review_score_avg );
	}
}
new Review_Score_Dashboard_Setup;