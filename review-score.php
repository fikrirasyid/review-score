<?php
/*
	Plugin Name: Review Score
	Version: 0.1
	Description: Easy to use plugin which enables author to add flexible rating system to his/her post.
	Author: Fikri Rasyid
	Author URI: http://fikrirasyid.com
*/
/*
	Copyright 2014 by Fikri Rasyid (fikrirasyid@gmail.com)
*/

/**	
 * Constants
 *
 * @since 0.1
*/	
if (!defined('REVIEW_SCORE_DIR'))
    define('REVIEW_SCORE_DIR', plugin_dir_path( __FILE__ ));

if (!defined('REVIEW_SCORE_URL'))
    define('REVIEW_SCORE_URL', plugin_dir_url( __FILE__ ));	

class Review_Score{
	public $post_type_support;
	public $review_scale;
	public $prefix_label;

	function __construct(){
		$this->post_type_support = $this->post_type_support();
		$this->review_scale = 10;
		$this->prefix_label = '_review_score_label_';
		$this->hook();
	}

	/**
	 * Define post type that will be supported by this plugin
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */	
	function post_type_support(){
		return "post";
	}

	function comment_vote_support(){
		return false;
	}

	function predefined_review_score_fields(){
		return false;
	}

	/**
	 * Hooking methods to WP environment
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */
	function hook(){
		add_action( 'admin_print_styles', array( &$this, 'styling_editor' ) );
		add_action( 'add_meta_boxes', array( &$this, 'meta_boxes_add' ) );
		add_action( 'save_post', array( &$this, 'meta_box_save' ) );
		add_action( 'wp_head', array( &$this, 'styling_frontend' ) );
		add_filter( 'the_content', array( &$this, 'display' ) );
		add_action( 'comment_form', array( &$this, 'display_comment_vote' ) );
		add_action( 'comment_post', array( &$this, 'vote_save' ) );
	}	

	/**
	 * Adding scripts & stylesheet to editor page
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */
	function styling_editor(){
		$screen = get_current_screen();

		if( isset( $screen->id ) && $screen->id == $this->post_type_support ){
			// register style and script
			wp_register_style( 'review-score-editor', REVIEW_SCORE_URL . '/css/review-score-editor.css', array(), false, 'screen' );
			wp_register_script( 'review-score-editor', REVIEW_SCORE_URL . '/js/review-score-editor.js', array( 'jquery' ), false, false );

			// call the style and script
		    wp_enqueue_style( 'review-score-editor' );
			wp_enqueue_script( 'review-score-editor' );			
		}
	}

	/**
	 * Adding scripts & stylesheet to front end page
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */	
	function styling_frontend(){
		global $post;
		wp_register_style( 'review-score-frontend', REVIEW_SCORE_URL . '/css/review-score-frontend.css', array(), false, 'screen' );
		wp_register_script( 'review-score-frontend', REVIEW_SCORE_URL . '/js/review-score-frontend.js', array( 'jquery' ), false, false );
	    wp_enqueue_style( 'review-score-frontend' );

	    // The front end js is only relevant for voting mechanism
	    $fields = $this->get_review_score( $post->ID );		
		if( $this->is_display_review_score() && $this->comment_vote_support() && !empty( $fields ) ){
			wp_enqueue_script( 'review-score-frontend' );					
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
		add_meta_box('review-score', __( 'Review Score', 'review_score'), array( &$this, 'meta_box' ), $this->post_type_support );		
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
		if( $this->is_use_review_score() ){
			$review_score_visibility = 'style="display: block;"';
			$review_score_use_check = 'checked="checked"';
		} else {
			$review_score_visibility = 'style="display: none;"';
			$review_score_use_check = '';
		}

		// Get stored value
		$review_score = $this->get_review_score( $post->ID );
		?>
			<p><label for="_review_score_use"><input type="checkbox" name="_review_score_use" value="yes" id="_review_score_use" <?php echo $review_score_use_check; ?>>Use Review Score for this content.</label></p>
			
			<div id="review-score-post-settings" <?php echo $review_score_visibility; ?>>
				<h3>Review Aspects</h3>
				<table cellspacing="0" class="review-aspects">
					<thead>
						<tr>
							<th>Aspects</th>
							<th>Score</th>

							<?php if( !$this->predefined_review_score_fields() ) : ?>
							<th></th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php 
							if( empty( $review_score ) ){
								echo '<tr id="no-review-score"><td colspan="3">no review score, yet.</td></tr>';
							} else {
								foreach ($review_score as $key => $aspect) {
									?>
									<tr>
										<td><?php echo $aspect['label']; ?></td>
										<td>
											<?php $this->select_score( $key, $aspect['value'] ); ?></span>
										</td>
										<?php if( !$this->predefined_review_score_fields() ) : ?>
										<td>
											<a href="#" class="remove-review-aspect">Remove</a>
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
								Average Score
							</td>
							<td colspan="2">
								<?php echo get_post_meta( $post->ID, '_review_score_average', true ); ?>
							</td>
						</tr>
					</tfoot> 					
				</table>

				<?php if( !$this->predefined_review_score_fields() ): ?>
				<h3>Add New Aspect</h3>
				<p>
					<input id="new-review-aspect" type="text" placeholder="Type New Aspect Here..">
					<button id="add-review-aspect" class="button">Add</button>
				</p>				
				<?php endif; ?>
			</div><!-- #review-score-post-settings -->

			<script type="text/template" id="template-aspect">
				<tr>
					<td>{0}</td>
					<td>
						<?php $this->select_score( '', false, true ); ?> / 10</span>
					</td>
					<td>
						<a href="#" class="remove-review-aspect">Remove</a>
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

		// If this isn't ticket editor, bail
		if ($screen != null && $screen->post_type != $this->post_type_support ) return;

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
		$review_score_to_be_deleted = $this->get_review_score( $post_id );

		// Collect all _review_score_label, calculate its average
		$review_score_total = array();

		// Find review score key and save it to the DB
		foreach ($_POST as $key => $value) {
			if( substr( $key, 0, strlen( $this->prefix_label ) ) === $this->prefix_label ){
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

	/**
	 * Hook vote-saving mechanism
	 */ 
	function vote_save( $comment_id ){
		global $wpdb;

		$post_id = $_POST['comment_post_ID'];
		$prefix_length = strlen( $this->prefix_label );

		// Is this vote?
		$is_vote = false;

		// Collect all _review_score_label, calculate its average
		$review_score_total = array();		

		// Save each metadata to the database
		foreach ($_POST as $key => $field) {
			if( substr( $key, 0, $prefix_length) == $this->prefix_label ){
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
		}

		return;
	}

	/**
	 * Select box for selecting score of review aspect
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	function select_score( $name = 'aspect_name', $selected = false, $js_template = false ){
		if( $js_template ){
			$name = '{1}';
		}

		for ($i = 1; $i <= $this->review_scale ; $i++) { 
			if( $i == intval( $selected ) ){
				echo "<label for='{$name}_{$i}'><input type='radio' id='{$name}_{$i}' name='$name' value='$i' checked='checked'> $i </label>";
			} else {
				echo "<label for='{$name}_{$i}'><input type='radio' id='{$name}_{$i}' name='$name' value='$i'> $i </label>";
			}
		}
	}

	/**
	 * Clean review score key from review score prefix
	 * 
	 * @since 0.1
	 * 
	 * @return string
	 */	
	function _prepare_review_score_key( $value ){
		$remove_prefix = str_replace( $this->prefix_label, '', $value );
		$replace_underscore = str_replace( '_', ' ', $remove_prefix );

		return $replace_underscore;
	}

	/**
	 * Get review score data of certain post
	 * 
	 * @since 0.1
	 * 
	 * @return array
	 */	
	function get_review_score( $post_id ){
		$review_score = array();
		$post_metas = get_post_custom( $post_id );
		foreach ($post_metas as $key => $post_meta) {
			if( $this->is_review_score_label( $key ) ){		
				$review_score[$key] = array( 'label' => $this->_prepare_review_score_key( $key ), 'value' => $post_meta[0] );
			}
		}

		return $this->_prepare_get_review_score( $review_score, $post_id );
	}

	/**
	 * Prepare get_review_score. Make it override-able from other methods
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */	
	function _prepare_get_review_score( $review_score, $post_id ){
		if( $this->predefined_review_score_fields() ){
			$fields = $this->predefined_review_score_fields();

			$predefined_review_score = array();

			foreach ( $fields as $key => $field ) {
				$meta_key = $this->prefix_label . str_replace(' ', '_', $field );

				$predefined_review_score[ $meta_key ] = array( 'label' => $field, 'value' => get_post_meta( $post_id, $meta_key, true ) );
			}
			return $predefined_review_score;			
		} else {
			return $review_score;
		}
	}

	/**
	 * Check if a post meta is review score's post meta
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */	
	function is_review_score_label( $value ){
		if( substr( $value, 0, strlen( $this->prefix_label ) ) === $this->prefix_label ){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Conditional tag for checking review_score_use status
	 *
	 * @return bool
	 */
	function is_use_review_score( $post_id = false ){
		global $post;

		if( !$post_id ){
			$post_id = $post->ID;
		}

		if( get_post_meta( $post_id, '_review_score_use', true ) == 'yes' ){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check whether we should display review score UI
	 *
	 * @return bool
	 */
	function is_display_review_score( $post_id = false ){
		if( $this->is_use_review_score( $post_id ) && is_singular( $this->post_type_support ) ){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display review score on the_content
	 */
	function display( $content ){
		global $post;

		if( $this->is_display_review_score() ){
			$scores = $this->get_review_score( $post->ID );

			$review_score = '<div class="review-score-wrap">';
			$review_score .= '<h2 class="section-title review-score-title">'. apply_filters( "review_score_title", __( "Review Score", "review_score" ) ) .'</h2>';
			$review_score .= '<div class="review-score-content">';
			if( !empty( $scores ) ){
				$review_score .= '<div class="review-score-average">';
				$review_score .= '<div class="review-score-average-label">' . apply_filters( "review_score_average_score_label", __( "Average Score", "review_score" ) ) . '</div>';
				$review_score .= '<div class="review-score-average-score">' . get_post_meta( $post->ID, '_review_score_average', true ) . '</div>';
				$review_score .= '</div>';

				// Print review score data
				foreach ( $scores as $key => $score ) {
					$review_score .= '<div class="review-score-item">
										<div class="review-score-item-label">'. $score["label"] .'</div>
										<div class="review-score-item-score">'. $score["value"] .'</div>
										<div class="review-score-item-bar" data-score="'. $score["value"] .'">'. $this->score_to_stars( $score["value"] ) .'</div>
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
	 *
	 */
	function display_comment_vote(){
		global $post;

    	$fields = $this->get_review_score( $post->ID );		
		if( $this->is_display_review_score() && $this->comment_vote_support() && !empty( $fields ) ):
		?>
            <div class="review-score-wrap">
                <!-- <h2 class="section-title review-score-title" style="">Review Score</h2> -->
                <div class="review-score-content" style="padding-left: 0; font-size: .7em;">
                <?php foreach ($fields as $key => $field) { ?>
                    <div class="review-score-item">
                        <div class="review-score-item-label"><?php echo $field['label']; ?></div>
                        <div class="review-score-item-score">-</div>
                        <div class="review-score-item-bar" data-score="0">
                        	<?php echo $this->score_to_stars( 0 ); ?>
                        </div>
                        <div class="review-score-item-select">
                        	<?php $this->select_score( $key ); ?>
                        </div>
                    </div>
        		<?php } ?>
                </div>
            </div>
		<?php
		endif;
	}

	/**
	 * Display stars for review score
	 *
	 * @return void
	 */
	function score_to_stars( $score = 10 ){
		$stars = '';
		for ($i=1; $i <= 10 ; $i++) { 
			if( $i <= $score ){
				$stars .= '<div class="review-score-star starred">'. $i .'</div>';
			} else {
				$stars .= '<div class="review-score-star">'. $i .'</div>';				
			}
		}

		return $stars;
	}
}
$review_score = new Review_Score();