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

	function __construct( $init = false ){
		$this->post_type_support = $this->post_type_support();
		$this->review_scale = 10;
		$this->prefix_label = '_review_score_label_';

		if( $init ){
			$this->hook();
		}
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

		// Get stored value
		$review_score = $this->get_review_score( $post_id );
		?>
			<h3>Review Aspects</h3>
			<table cellspacing="0" class="review-aspects">
				<thead>
					<tr>
						<th>Aspects</th>
						<th>Score</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php 
						if( empty( $review_score ) ){
							echo '<tr><td colspan="3">no review score, yet.</td></tr>';
						} else {
							foreach ($review_score as $key => $aspect) {
								?>
								<tr>
									<td><?php echo $aspect['label']; ?></td>
									<td>
										<?php $this->select_score( $key, $aspect['value'] ); ?> / 10</span>
									</td>
									<td>
										<a href="#" class="remove-review-aspect">Remove</a>
									</td>
								</tr>
								<?php
							}
						}
					?>					
				</tbody>
				<!-- 
				<tfoot>
					<tr>
						<td>
							Total Score
						</td>
						<td>
							9
						</td>
						<td>
							
						</td>
					</tr>
				</tfoot> 
				-->
			</table>


			<h3>Add New Aspect</h3>
			<p>
				<input id="new-review-aspect" type="text" placeholder="Type New Aspect Here..">
				<button id="add-review-aspect" class="button">Add</button>
			</p>

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
		// Get current value. We'll match it later for deleting purpose
		$review_score_to_be_deleted = $this->get_review_score( $post_id );

		// Find review score key and save it to the DB
		foreach ($_POST as $key => $value) {
			if( substr( $key, 0, strlen( $this->prefix_label ) ) === $this->prefix_label ){
				unset( $review_score_to_be_deleted[$key] );
				update_post_meta( $post_id, $key, intval( $value ) );				
			}
		} 

		// Delete "removed" review score
		if( !empty( $review_score_to_be_deleted ) ){
			foreach ($review_score_to_be_deleted as $key => $post_meta) {
				delete_post_meta( $post_id, $key, $post_meta['value'] );
			}
		}
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
		echo '<select name="{1}" class="">';
		} else {
		echo '<select name="'. $name .'" class="">';			
		}

		for ($i = 1; $i <= $this->review_scale ; $i++) { 
			if( $i == intval( $selected ) ){
				echo '<option value="'. $i .'" selected="selected">'. $i .'</option>';				
			} else {
				echo '<option value="'. $i .'">'. $i .'</option>';
			}
		}
		echo '<select>';
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
		$post_metas = get_post_custom( $post->ID );
		foreach ($post_metas as $key => $post_meta) {
			if( $this->is_review_score_label( $key ) ){		
				$review_score[$key] = array( 'label' => $this->_prepare_review_score_key( $key ), 'value' => $post_meta[0] );
			}
		}		

		return $review_score;
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
}
$review_score = new Review_Score( true );