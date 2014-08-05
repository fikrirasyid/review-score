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
	Courtesy of Hijapedia
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

/**
 * Requiring external files
 */
require_once( REVIEW_SCORE_DIR . '/includes/class-review-score-settings.php' );
require_once( REVIEW_SCORE_DIR . '/includes/class-review-score.php' );
require_once( REVIEW_SCORE_DIR . '/includes/class-review-score-dashboard-setup.php' );
require_once( REVIEW_SCORE_DIR . '/includes/class-review-score-frontend-setup.php' );