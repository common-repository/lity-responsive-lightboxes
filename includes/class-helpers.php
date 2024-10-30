<?php
/**
 * Lity Helpers Class
 *
 * @package Lity
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

if ( ! class_exists( 'Lity_Helpers' ) ) {

	/**
	 * Lity Helpers Class.
	 *
	 * @since 1.0.0
	 */
	final class Lity_Helpers {

		/**
		 * Retreive the number of images on the site.
		 *
		 * @return integer Number of images on the site.
		 */
		public function get_site_image_count() {

			$attachment_count = wp_count_attachments( 'image' );

			unset( $attachment_count->trash );

			return (int) array_sum( (array) $attachment_count );

		}

	}

}
