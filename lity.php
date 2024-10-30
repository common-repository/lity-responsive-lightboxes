<?php
/**
 * Plugin Name: Lity - Responsive Lightboxes
 * Description: Enables beautiful and responsive lightboxes on every image on your site.
 * Author: Evan Herman
 * Author URI: https://www.evan-herman.com
 * Version: 1.0.0
 * Text Domain: lity
 * Domain Path: /languages
 * Tested up to: 6.0
 *
 * @package Lity
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

define( 'LITY_PLUGIN_VERSION', '1.0.0' );
define( 'LITY_VERSION', '2.4.1' );
define( 'LITY_SLIMSELECT_VERSION', '1.27.1' );
define( 'LITY_TAGIFY_VERSION', '4.12.0' );

if ( ! class_exists( 'Lity' ) ) {

	/**
	 * Main Lity Class.
	 *
	 * @since 1.0.0
	 */
	final class Lity {

		/**
		 * Default options array.
		 *
		 * @var array
		 */
		public $default_options;

		/**
		 * Options class instance.
		 *
		 * @var Object
		 */
		private $lity_options;

		/**
		 * Helpers class instance.
		 *
		 * @var Object
		 */
		private $helpers;

		/**
		 * Number of attachments to query for.
		 * Note: The higher the number, the longer building new transients will take.
		 *
		 * @var int
		 */
		private $posts_per_page;

		/**
		 * Lity plugin constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			require_once plugin_dir_path( __FILE__ ) . 'includes/action-scheduler/action-scheduler.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-helpers.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';

			// Compatibility files.
			require_once plugin_dir_path( __FILE__ ) . 'includes/compat/class-woocommerce.php';

			$this->default_options = array(
				'show_full_size'             => 'yes',
				'use_background_image'       => 'yes',
				'show_image_info'            => 'no',
				'caption_type'               => 'caption',
				'disabled_on'                => array(),
				'element_selectors'          => '[{"value":"img"}]',
				'excluded_element_selectors' => '[]',
				'generating_transient'       => false,
			);

			$this->lity_options = new Lity_Options( $this->default_options );

			$this->helpers = new Lity_Helpers();

			/**
			 * Filter the number of posts_per_page in the WP_Query when building the transient.
			 *
			 * @var int
			 */
			$this->posts_per_page = (int) apply_filters( 'lity_transient_image_query_count', 30 );

			register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );

			add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'custom_plugin_action_links' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_lity' ), PHP_INT_MAX );

			add_action( 'admin_init', array( $this, 'schedule_lity_media' ), PHP_INT_MAX );

			add_action( 'lity_generate_media', array( $this, 'set_media_transient' ) );

			add_action( 'attachment_updated', array( $this, 'update_lity_media_transient' ), PHP_INT_MAX, 3 );

			add_filter( 'wp_generate_attachment_metadata', array( $this, 'lity_handle_new_image' ), PHP_INT_MAX, 3 );

			add_action( 'deleted_post', array( $this, 'lity_remove_image_from_transient' ), PHP_INT_MAX, 2 );

			add_action( 'admin_notices', array( $this, 'display_generating_transient_notice' ), PHP_INT_MAX );

			add_filter( 'lity_excluded_element_selectors', array( $this, 'always_excluded_selectors' ), PHP_INT_MAX, 2 );

		}

		/**
		 * Actions to take on plugin activation.
		 */
		public function plugin_activation() {

			// Set defaults when the option doesn't exist yet.
			if ( ! get_option( 'lity_options', false ) ) {

				update_option( 'lity_options', $this->default_options );

			}

		}

		/**
		 * Display a custom 'Settings' link on the plugins.php table for Lity.
		 *
		 * @param array $links Original array of links.
		 *
		 * @return array Filtered array of links to display.
		 */
		public function custom_plugin_action_links( $links ) {

			return array_merge(
				$links,
				array(
					sprintf(
						'<a href="%1$s" aria-label="%2$s">%3$s</a>',
						menu_page_url( 'lity-options', false ),
						esc_html__( 'Lity - Responsive Lightboxes Settings', 'lity' ),
						esc_html__( 'Settings', 'lity' )
					),
				)
			);

		}

		/**
		 * Enqueue Lity assets and create lightbox with inline script.
		 */
		public function enqueue_lity() {

			$options    = $this->lity_options->get_lity_options();
			$media_data = get_transient( 'lity_media' );

			if ( false === $media_data || in_array( (string) get_the_ID(), $options['disabled_on'], true ) ) {

				return;

			}

			/**
			 * Allow users to disable lity via a filter.
			 *
			 * @var boolean
			 */
			if ( (bool) apply_filters( 'lity_is_disabled', false ) ) {

				return;

			}

			$suffix = SCRIPT_DEBUG ? '' : '.min';

			// Styles.
			wp_enqueue_style( 'lity', plugin_dir_url( __FILE__ ) . "assets/css/lity/lity${suffix}.css", array(), LITY_VERSION, 'all' );
			wp_enqueue_style( 'lity-styles', plugin_dir_url( __FILE__ ) . "assets/css/lity-styles${suffix}.css", array( 'lity' ), LITY_PLUGIN_VERSION, 'all' );

			// Scripts.
			wp_enqueue_script( 'lity', plugin_dir_url( __FILE__ ) . "assets/js/lity/lity${suffix}.js", array( 'jquery' ), LITY_VERSION, true );
			wp_enqueue_script( 'lity-script', plugin_dir_url( __FILE__ ) . "assets/js/lity-script${suffix}.js", array( 'lity' ), LITY_PLUGIN_VERSION, true );

			/**
			 * Filter the array of exlcuded element selectors.
			 *
			 * @var array
			 */
			$excluded_element_selectors = (array) apply_filters(
				'lity_excluded_element_selectors',
				$this->lity_options->get_lity_option( 'excluded_element_selectors' )
			);

			wp_localize_script(
				'lity-script',
				'lityScriptData',
				array(
					'options'                    => $options,
					'element_selectors'          => empty( $this->lity_options->get_lity_option( 'element_selectors' ) ) ? 'img' : implode( ',', $this->lity_options->get_lity_option( 'element_selectors' ) ),
					'excluded_element_selectors' => implode( ',', array_unique( $excluded_element_selectors ) ),
					'mediaData'                  => get_transient( 'lity_media' ),
					'a11y_aria_label'            => /* translators: %s is the title of the image, if it exists. eg: View Image: Beautiful Tree */ __( 'View Image: %s', 'lity' ),
				)
			);

		}

		/**
		 * Schedule the actions to retrieve image meta in the background.
		 */
		public function schedule_lity_media() {

			// If the task has been scheduled, do not re-schedule it.
			if ( $this->lity_options->get_lity_option( 'generating_transient' ) ) {

				return;

			}

			$media = get_transient( 'lity_media' );

			if ( false !== $media ) {

				return;

			}

			$image_count = $this->helpers->get_site_image_count();

			if ( 0 === $image_count ) {

				return;

			}

			$this->lity_options->update_lity_option( array( 'generating_transient' => true ) );

			// Divide $image_count by $this->posts_per_page to give us the number of pages we have to query.
			$total_pages = ceil( $image_count / $this->posts_per_page );

			$i = 1;

			while ( $i <= $total_pages ) {

				as_schedule_single_action( strtotime( 'now' ), 'lity_generate_media', array( 'page' => (int) $i ) );

				$i++;

			}

		}

		/**
		 * Create a transient to track media data.
		 *
		 * @param integer $page Page number to query in the REST API attachment endpoint.
		 *
		 * @since 1.0.0
		 */
		public function set_media_transient( int $page ) {

			// We've reached the final page.
			if ( (int) ceil( $this->helpers->get_site_image_count() / $this->posts_per_page ) === $page ) {

				$this->lity_options->update_lity_option( array( 'generating_transient' => false ) );

			}

			$media_query = new WP_Query(
				array(
					'post_type'      => 'attachment',
					'posts_per_page' => $this->posts_per_page,
					'post_status'    => 'inherit',
					'paged'          => $page,
				)
			);

			if ( ! $media_query->have_posts() ) {

				return;

			}

			$media = array();

			while ( $media_query->have_posts() ) {

				$media_query->the_post();

				$image_id  = get_the_ID();
				$image_src = wp_get_attachment_image_src( $image_id, 'full' );

				if ( false === $image_src || count( $image_src ) < 1 ) {

					continue;

				}

				$image_urls  = array();
				$image_sizes = get_intermediate_image_sizes();

				foreach ( $image_sizes as $image_size ) {

					$image_urls[] = wp_get_attachment_image_url( $image_id, $image_size );

				}

				// Ensure 'full' image size is first in the array.
				array_unshift( $image_urls, wp_get_attachment_image_url( $image_id, 'full' ) );

				/**
				 * Filter lity_image_info to allow alterations to the image info before the transient is set.
				 *
				 * @var array
				 */
				$image_info = (array) apply_filters(
					'lity_image_info',
					array(
						'id'          => $image_id,
						'urls'        => array_values( array_unique( $image_urls ) ),
						'title'       => get_the_title( $image_id ),
						'caption'     => get_the_excerpt( $image_id ),
						'description' => get_the_content(),
					),
					$image_id
				);

				/**
				 * Filter lity_image_info_custom_data to allow users to set custom data before the transient is set.
				 *
				 * Expected custom_data format:
				 * array(
				 *   'class-name' => array(
				 *     'element_wrap' => 'p',
				 *     'content'      => 'Custom Content'
				 *   ),
				 * );
				 *
				 * @var array
				 */
				$image_info['custom_data'] = (array) apply_filters( 'lity_image_info_custom_data', array(), $image_id );

				// Filter out any custom_data that contains no content.
				foreach ( $image_info['custom_data'] as $class => &$custom_data ) {

					if ( ! empty( $custom_data['content'] ) ) {

						continue;

					}

					unset( $image_info['custom_data'][ $class ] );

				}

				$media[] = $image_info;

			}

			$existing_transient = get_transient( 'lity_media' );

			if ( false === $existing_transient ) {

				set_transient( 'lity_media', json_encode( $media ) );

				return;

			}

			$existing_transient = json_decode( $existing_transient, true );

			set_transient( 'lity_media', json_encode( array_merge( $existing_transient, $media ) ) );

		}

		/**
		 * Update the image in the lity_media transient, to avoid having to regenerate the transient each time.
		 *
		 * @param int $attachment_id The attachment ID that was updated.
		 */
		public function update_lity_media_transient( $attachment_id ) {

			$media_data = get_transient( 'lity_media' );

			if ( false === $media_data ) {

				return;

			}

			$media_data = json_decode( $media_data, true );

			$attachment_index = array_search( $attachment_id, array_column( $media_data, 'id' ), true );

			if ( false === $attachment_index ) {

				return;

			}

			$media_data[ $attachment_index ]['title']       = get_the_title( $attachment_id );
			$media_data[ $attachment_index ]['caption']     = get_the_excerpt( $attachment_id );
			$media_data[ $attachment_index ]['description'] = get_post_field( 'post_content', $attachment_id );

			set_transient( 'lity_media', json_encode( $media_data ) );

		}

		/**
		 * Handle new image uploads. Update our transient with the new image meta.
		 *
		 * @param array $image_meta    Array of attachment meta.
		 * @param int   $attachment_id Current attachment ID.
		 *
		 * @return array Original image_meta array.
		 */
		public function lity_handle_new_image( $image_meta, $attachment_id ) {

			// Do not add attachments that are not images to the transient.
			if ( ! wp_attachment_is_image( $attachment_id ) ) {

				return;

			}

			$image_urls  = array();
			$image_sizes = array_keys( $image_meta['sizes'] );

			if ( ! empty( $image_sizes ) ) {

				foreach ( $image_sizes as $image_size ) {

					$image_urls[] = wp_get_attachment_image_url( $attachment_id, $image_size );

				}
			}

			// Ensure 'full' image size is first in the array.
			array_unshift( $image_urls, wp_get_attachment_image_url( $attachment_id, 'full' ) );

			$new_image_info = array(
				array(
					'id'          => $attachment_id,
					'urls'        => array_values( array_unique( $image_urls ) ),
					'title'       => basename( $image_meta['file'] ),
					'caption'     => '',
					'description' => '',
				),
			);

			$existing_transient = get_transient( 'lity_media' );

			if ( false === $existing_transient ) {

				set_transient( 'lity_media', json_encode( $new_image_info ) );

				return $image_meta;

			}

			$existing_transient = json_decode( $existing_transient, true );

			set_transient( 'lity_media', json_encode( array_merge( $new_image_info, $existing_transient ) ) );

			return $image_meta;

		}

		/**
		 * Remove the image from the lity_transient when it is deleted from the site.
		 *
		 * @param int $post_id The post_id that was deleted.
		 */
		public function lity_remove_image_from_transient( $post_id ) {

			$post_type = get_post_type( $post_id );

			if ( 'attachment' !== $post_type ) {

				return;

			}

			$lity_media = get_transient( 'lity_media' );

			if ( false === $lity_media ) {

				return;

			}

			$lity_media = json_decode( $lity_media, true );

			/**
			 * The $post_id must be typecast as an integer value. This is a bug in core when multiple
			 * images are deleted at once using the bulk action dropdown.
			 * https://core.trac.wordpress.org/ticket/56170
			 */
			$attachment_index = array_search( (int) $post_id, array_column( $lity_media, 'id' ), true );

			if ( false === $attachment_index ) {

				return;

			}

			unset( $lity_media[ $attachment_index ] );

			// Reset array keys.
			$lity_media = array_values( $lity_media );

			set_transient( 'lity_media', json_encode( $lity_media ) );

		}

		/**
		 * Display an admin notice about the transient being rebuilt in the background.
		 */
		public function display_generating_transient_notice() {

			if ( ! $this->lity_options->get_lity_option( 'generating_transient' ) ) {

				return;

			}

			$message = __( 'Lity - Responsive Lightboxes is fetching your image metadata and caching a few things to improve performance. This all happens in the background. This notice will disappear when the process is complete.', 'lity' );

			printf(
				'<div id="lity-cache-rebuilding-notice" class="notice notice-info">
					<p>%1$s</p>
				</div>',
				esc_html( $message )
			);

		}

		/**
		 * Clear the lity media transient when a new image is uploaded.
		 *
		 * @since 1.0.0
		 */
		public function clear_lity_media_transient() {

			delete_transient( 'lity_media' );

		}

		/**
		 * Remove certain elements from ever opening in a lightbox.
		 *
		 * @param array $excluded_selectors Excluded selectors array.
		 *
		 * @return array Filtered excluded_selectors value.
		 */
		public function always_excluded_selectors( $excluded_selectors ) {

			$exclusions = array(
				'#wpadminbar img',
			);

			return array_merge( $excluded_selectors, $exclusions );

		}

	}

}

new Lity();
