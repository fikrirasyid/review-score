<?php
class Review_Score{
	public $settings;
	public $messages;

	function __construct(){
		$this->settings = new Review_Score_Settings;
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

		for ($i = 1; $i <= $this->settings->review_scale() ; $i++) { 
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
		$remove_prefix = str_replace( $this->settings->prefix_label, '', $value );
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

		if( !$post_metas ){
			return array();
		}

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
		if( $this->settings->predefined_fields() ){
			$fields = $this->settings->predefined_fields();

			$predefined_review_score = array();

			foreach ( $fields as $key => $field ) {
				$meta_key = $this->settings->prefix_label . str_replace(' ', '_', $field );

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
		if( substr( $value, 0, strlen( $this->settings->prefix_label ) ) === $this->settings->prefix_label ){
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

		// If this is a newly created post
		if( !isset( $post->ID ) ){
			return false;
		}

		// If this is saved post
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
		if( $this->is_use_review_score( $post_id ) && is_singular( $this->settings->post_types() ) ){
			return true;
		} else {
			return false;
		}
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