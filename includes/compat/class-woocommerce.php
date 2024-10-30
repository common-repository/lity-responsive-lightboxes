<?php
/**
 * Lity Options Class
 *
 * @package Lity
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

if ( ! class_exists( 'Lity_WooCommerce' ) ) {

	/**
	 * Lity Options Class.
	 *
	 * @since 1.0.0
	 */
	final class Lity_WooCommerce {

		/**
		 * Lity options class constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			add_filter( 'lity_excluded_element_selectors', array( $this, 'woocommerce_exclusions' ), PHP_INT_MAX );

			add_filter( 'lity_excluded_element_selectors', array( $this, 'storefront_exclusions' ), PHP_INT_MAX );

		}

		/**
		 * Remove specific WooCommerce elements from opening in a lightbox.
		 *
		 * @param array $excluded_selectors Excluded selectors array.
		 *
		 * @return array Filtered excluded_selectors value.
		 */
		public function woocommerce_exclusions( $excluded_selectors ) {

			if ( ! function_exists( 'is_plugin_active' ) ) {

				include_once ABSPATH . 'wp-admin/includes/plugin.php';

			}

			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

				return $excluded_selectors;

			}

			array_push( $excluded_selectors, 'li.type-product .attachment-woocommerce_thumbnail' );

			return $excluded_selectors;

		}

		/**
		 * Remove specific WooCommerce Storefront theme elements.
		 *
		 * @param array $excluded_selectors Excluded selectors array.
		 *
		 * @return array Filtered excluded_selectors value.
		 */
		public function storefront_exclusions( $excluded_selectors ) {

			$theme = wp_get_theme( 'storefront' );

			if ( ! $theme->exists() ) {

				return $excluded_selectors;

			}

			array_push( $excluded_selectors, '.storefront-product-pagination img' );

			return $excluded_selectors;

		}

	}

}

new Lity_WooCommerce();
