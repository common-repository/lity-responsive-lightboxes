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

if ( ! class_exists( 'Lity_Options' ) ) {

	/**
	 * Lity Options Class.
	 *
	 * @since 1.0.0
	 */
	final class Lity_Options {

		/**
		 * Default options.
		 *
		 * @var array
		 */
		public $default_options;

		/**
		 * Lity options class constructor.
		 *
		 * @param array $default_options Default Lity options array.
		 *
		 * @since 1.0.0
		 */
		public function __construct( $default_options ) {

			$this->default_options = $default_options;

			add_filter( 'removable_query_args', array( $this, 'removable_query_args' ) );

			add_action( 'admin_init', array( $this, 'clear_lity_transient_button_clicked' ) );
			add_action( 'admin_init', array( $this, 'reset_lity_settings_button_clicked' ) );

			add_action( 'admin_init', array( $this, 'options_init' ) );

			add_action( 'admin_menu', array( $this, 'register_menu_item' ) );

		}

		/**
		 * Add custom query args to be removed.
		 *
		 * @param array $args Core removable query args array.
		 *
		 * @return array Filtered removable query args array.
		 */
		public function removable_query_args( $args ) {

			return array_merge( $args, array( 'lity-transient-cleared', 'lity-settings-reset' ) );

		}

		/**
		 * Catch the clear lity transient button click, and redirect.
		 */
		public function clear_lity_transient_button_clicked() {

			if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) ) {

				return;

			}

			$action = filter_var( $_GET['action'], FILTER_SANITIZE_STRING );
			$nonce  = filter_var( $_GET['_wpnonce'], FILTER_SANITIZE_STRING );

			if ( 'lity-regenerate-transient' !== $action || ! wp_verify_nonce( $nonce, 'lity-regenerate-transient' ) ) {

				return;

			}

			// If the transient is already being built, do nothing.
			if ( (bool) $this->get_lity_option( 'generating_transient' ) ) {

				return;

			}

			$lity = new Lity();
			$lity->clear_lity_media_transient();

			wp_safe_redirect(
				add_query_arg(
					'lity-transient-cleared',
					true,
					menu_page_url( 'lity-options', false )
				),
				302,
				'WordPress - Lity - Responsive Lightboxes Plugin'
			);

			exit;

		}

		/**
		 * Catch the reset lity plugin settings button click, and redirect.
		 */
		public function reset_lity_settings_button_clicked() {

			if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) ) {

				return;

			}

			$action = filter_var( $_GET['action'], FILTER_SANITIZE_STRING );
			$nonce  = filter_var( $_GET['_wpnonce'], FILTER_SANITIZE_STRING );

			if ( 'lity-reset-plugin-settings' !== $action || ! wp_verify_nonce( $nonce, 'lity-reset-plugin-settings' ) ) {

				return;

			}

			$lity = new Lity();
			$lity->clear_lity_media_transient();

			update_option( 'lity_options', $this->default_options );

			wp_safe_redirect(
				add_query_arg(
					'lity-settings-reset',
					true,
					menu_page_url( 'lity-options', false )
				),
				302,
				'WordPress - Lity - Responsive Lightboxes Plugin'
			);

			exit;

		}

		/**
		 * Add the admin menu.
		 */
		public function register_menu_item() {

			$submenu = add_submenu_page(
				'options-general.php',
				__( 'Lity - Responsive Lightboxes', 'lity' ),
				__( 'Lity - Responsive Lightboxes', 'lity' ),
				'manage_options',
				'lity-options',
				array( $this, 'lity_options_page' )
			);

			$suffix = SCRIPT_DEBUG ? '' : '.min';

			add_action(
				"admin_print_styles-${submenu}",
				function() use ( $suffix ) {
					wp_enqueue_style( 'slimselect', plugin_dir_url( __FILE__ ) . "../assets/css/slimselect/slimselect${suffix}.css", array(), LITY_SLIMSELECT_VERSION, 'all' );
					wp_enqueue_style( 'tagify', plugin_dir_url( __FILE__ ) . "../assets/css/tagify/tagify${suffix}.css", array(), LITY_TAGIFY_VERSION, 'all' );
				}
			);

			add_action(
				"admin_print_scripts-${submenu}",
				function() use ( $suffix ) {

					wp_enqueue_script( 'slimselect', plugin_dir_url( __FILE__ ) . "../assets/js/slimselect/slimselect${suffix}.js", array( 'jquery' ), LITY_SLIMSELECT_VERSION, true );
					wp_enqueue_script( 'tagify', plugin_dir_url( __FILE__ ) . '../assets/js/tagify/jQuery.tagify.min.js', array( 'jquery' ), LITY_TAGIFY_VERSION, true );

					$slimselect = "jQuery( document ).on( 'ready', function() {
						new SlimSelect( {
							select: '#disabled_on'
						} );
					} );";

					wp_add_inline_script( 'slimselect', $slimselect, 'after' );

					$tagify = "jQuery( document ).on( 'ready', function() {
						jQuery( '#element_selectors, #excluded_element_selectors' ).tagify();
					} );";

					wp_add_inline_script( 'tagify', $tagify, 'after' );

				}
			);

		}

		/**
		 * Return all lity options.
		 *
		 * @return array Lity options array.
		 */
		public function get_lity_options() {

			return get_option( 'lity_options', $this->default_options );

		}

		/**
		 * Return a specific lity option by name.
		 *
		 * @param string $name The name of the option to retrieve.
		 *
		 * @return string Lity option value, or empty if not found.
		 */
		public function get_lity_option( $name = '' ) {

			$options = $this->get_lity_options();

			if ( empty( $name ) || ! array_key_exists( $name, $options ) ) {

				return '';

			}

			return in_array( $name, array( 'element_selectors', 'excluded_element_selectors' ), true ) ? wp_list_pluck( json_decode( $options[ $name ], true ), 'value' ) : $options[ $name ];

		}

		/**
		 * Update options in the lity_options array.
		 *
		 * @param string $options Key value pair of options to update.
		 *
		 * @return string Lity option value, or empty if not found.
		 */
		public function update_lity_option( $options = array() ) {

			if ( empty( $options ) ) {

				return;

			}

			update_option(
				'lity_options',
				wp_parse_args(
					$options,
					$this->get_lity_options()
				)
			);

		}

		/**
		 * Register the Lity options.
		 */
		public function options_init() {

			register_setting(
				'lity',
				'lity_options',
				array(
					'sanitize_callback' => array( $this, 'sanitize_lity_options' ),
				)
			);

			add_settings_section(
				'lity_options',
				__( 'Responsive Lightbox Settings.', 'lity' ),
				array( $this, 'lity_section_developers_callback' ),
				'lity'
			);

			add_settings_field(
				'show_full_size',
				__( 'Show Full Size Image', 'lity' ),
				array( $this, 'lity_show_full_size_dropdown' ),
				'lity',
				'lity_options',
				array(
					'label_for'   => 'show_full_size',
					'description' => __( 'Should full size images be shown in the lightbox?', 'lity' ),
				)
			);

			add_settings_field(
				'use_background_image',
				__( 'Use Background Image', 'lity' ),
				array( $this, 'lity_use_background_image_dropdown' ),
				'lity',
				'lity_options',
				array(
					'label_for'   => 'use_background_image',
					'description' => __( 'Should the lightbox use the selected image as a background?', 'lity' ),
				)
			);

			add_settings_field(
				'show_image_info',
				__( 'Show Image Info', 'lity' ),
				array( $this, 'lity_show_image_info_dropdown' ),
				'lity',
				'lity_options',
				array(
					'label_for'   => 'show_image_info',
					'description' => __( 'Should the image title and description be shown in the lightbox?', 'lity' ),
				)
			);

			add_settings_field(
				'caption_type',
				__( 'Caption Image Metadata', 'lity' ),
				array( $this, 'lity_caption_type_dropdown' ),
				'lity',
				'lity_options',
				array(
					'label_for'   => 'caption_type',
					'description' => __( 'Lightbox captions should use which image metadata?', 'lity' ),
				)
			);

			add_settings_field(
				'disabled_on',
				__( 'Disabled on', 'lity' ),
				array( $this, 'lity_show_disabled_on_input' ),
				'lity',
				'lity_options',
				array(
					'label_for'   => 'disabled_on',
					'description' => sprintf(
						/* translators: %s is 'not' wrapped in a strong tag. */
						__( 'Select specific posts or pages that Lity should %s load on.', 'lity' ),
						'<strong>' . esc_html__( 'not', 'lity' ) . '</strong>'
					),
				)
			);

			add_settings_field(
				'element_selectors',
				__( 'Element Selectors', 'lity' ),
				array( $this, 'lity_show_element_selector_textarea' ),
				'lity',
				'lity_options',
				array(
					'label_for'   => 'element_selectors',
					'description' => __( 'Specify custom element selectors for images on your site. If left empty, this will just be <code>img</code> and target all images on your site.', 'lity' ),
				)
			);

			add_settings_field(
				'excluded_element_selectors',
				__( 'Excluded Element Selectors', 'lity' ),
				array( $this, 'lity_excluded_element_selector_textarea' ),
				'lity',
				'lity_options',
				array(
					'label_for'   => 'excluded_element_selectors',
					'description' => __( 'Specify element selectors that should be excluded from opening in a lightbox.', 'lity' ),
				)
			);

			add_settings_field(
				'delete_lity_transient',
				__( 'Clear Lity Transient Data', 'lity' ),
				array( $this, 'lity_clear_transient_button' ),
				'lity',
				'lity_options',
				array(
					'description' => __( "Clearing the transient data will generate new media data. This can be helpful if data isn't displaying properly.", 'lity' ),
				)
			);

			add_settings_field(
				'reset_plugin_settings',
				__( 'Reset Plugin Settings', 'lity' ),
				array( $this, 'lity_reset_plugin_settings_button' ),
				'lity',
				'lity_options',
				array(
					'description' => __( "Reset the plugin settings back to 'factory settings'. All options will be reset and cache will be cleared.", 'lity' ),
				)
			);

		}

		/**
		 * Sanitize lity_options before saving it to the database
		 *
		 * @param array $value The lity_options array.
		 *
		 * @since 1.0.0
		 */
		public function sanitize_lity_options( $value ) {

			if ( ! array_key_exists( 'disabled_on', $value ) ) {

				$value['disabled_on'] = array();

			}

			return $value;

		}

		/**
		 * Developers section callback function.
		 *
		 * @param array $args The settings array, defining title, id, callback.
		 */
		public function lity_section_developers_callback( $args ) {

			?>
				<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'General Settings', 'lity' ); ?></p>
			<?php

		}

		/**
		 * Show full size dropdown callback.
		 *
		 * @param array $args Field args.
		 */
		public function lity_show_full_size_dropdown( $args ) {

			$options = $this->get_lity_options();

			?>

			<select id="<?php echo esc_attr( $args['label_for'] ); ?>" name="lity_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
				<option value="yes" <?php selected( $options[ $args['label_for'] ], 'yes', true ); ?>>
					<?php esc_html_e( 'Yes', 'lity' ); ?>
				</option>

				<option value="no" <?php selected( $options[ $args['label_for'] ], 'no', true ); ?>>
					<?php esc_html_e( 'No', 'lity' ); ?>
				</option>
			</select>

			<p class="description">
				<?php echo esc_html( $args['description'] ); ?>
			</p>

			<?php

		}

		/**
		 * Show image info dropdown callback.
		 *
		 * @param array $args Field args.
		 */
		public function lity_show_image_info_dropdown( $args ) {

			$options = $this->get_lity_options();

			?>

			<select id="<?php echo esc_attr( $args['label_for'] ); ?>" name="lity_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
				<option value="yes" <?php selected( $options[ $args['label_for'] ], 'yes', true ); ?>>
					<?php esc_html_e( 'Yes', 'lity' ); ?>
				</option>

				<option value="no" <?php selected( $options[ $args['label_for'] ], 'no', true ); ?>>
					<?php esc_html_e( 'No', 'lity' ); ?>
				</option>
			</select>

			<p class="description">
				<?php echo esc_html( $args['description'] ); ?>
			</p>

			<?php

		}

		/**
		 * Show caption type dropdown callback.
		 *
		 * @param array $args Field args.
		 */
		public function lity_caption_type_dropdown( $args ) {

			$options = $this->get_lity_options();

			?>

			<select id="<?php echo esc_attr( $args['label_for'] ); ?>" name="lity_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
				<option value="caption" <?php selected( $options[ $args['label_for'] ], 'caption', true ); ?>>
					<?php esc_html_e( 'Caption', 'lity' ); ?>
				</option>

				<option value="description" <?php selected( $options[ $args['label_for'] ], 'description', true ); ?>>
					<?php esc_html_e( 'Description', 'lity' ); ?>
				</option>
			</select>

			<p class="description">
				<?php echo esc_html( $args['description'] ); ?>
			</p>

			<?php

		}

		/**
		 * Show use_image_background dropdown callback.
		 *
		 * @param array $args Field args.
		 */
		public function lity_use_background_image_dropdown( $args ) {

			$options = $this->get_lity_options();

			?>

			<select id="<?php echo esc_attr( $args['label_for'] ); ?>" name="lity_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
				<option value="yes" <?php selected( $options[ $args['label_for'] ], 'yes', true ); ?>>
					<?php esc_html_e( 'Yes', 'lity' ); ?>
				</option>

				<option value="no" <?php selected( $options[ $args['label_for'] ], 'no', true ); ?>>
					<?php esc_html_e( 'No', 'lity' ); ?>
				</option>
			</select>

			<p class="description">
				<?php echo esc_html( $args['description'] ); ?>
			</p>

			<?php

		}

		/**
		 * Show full size dropdown callback.
		 *
		 * @param array $args Field args.
		 */
		public function lity_show_disabled_on_input( $args ) {

			$options = $this->get_lity_options();

			$query = new WP_Query(
				array(
					'posts_per_page' => -1,
					'post_type'      => 'any',
				)
			);

			$posts = array();

			if ( $query->have_posts() ) {

				while ( $query->have_posts() ) {

					$query->the_post();

					$posts[ get_post_type() ][] = get_the_ID();

				}
			}

			// Strip post types with no posts.
			$posts = array_filter( $posts );

			?>

			<select id="<?php echo esc_attr( $args['label_for'] ); ?>" name="lity_options[<?php echo esc_attr( $args['label_for'] ); ?>][]" multiple>
				<?php
				foreach ( $posts as $post_type => $post_ids ) {

					$post_type_obj = get_post_type_object( $post_type );

					?>

					<optgroup label="<?php echo esc_attr( $post_type_obj->labels->name ); ?>">
						<?php
						foreach ( $post_ids as $post_id ) {

							$post_title = get_the_title( $post_id );
							$selected   = in_array( (string) $post_id, $options['disabled_on'], true ) ? ' selected="selected" ' : '';

							?>

							<option value="<?php echo esc_attr( $post_id ); ?>" <?php echo esc_attr( $selected ); ?>>
								<?php echo empty( $post_title ) ? esc_html__( '(no title)', 'lity' ) : esc_html( $post_title ); ?>
							</option>

							<?php

						}
						?>
					</optgroup>
					<?php
				}
				?>
			</select>

			<p class="description">
				<?php echo wp_kses_post( $args['description'] ); ?>
			</p>

			<?php

		}

		/**
		 * Show the element selector textarea.
		 *
		 * @param array $args Field args.
		 */
		public function lity_show_element_selector_textarea( $args ) {

			$options = $this->get_lity_options();

			?>

			<textarea id="<?php echo esc_attr( $args['label_for'] ); ?>" name="lity_options[<?php echo esc_attr( $args['label_for'] ); ?>]" cols="80" rows="10" style="resize: vertical; max-height: 300px;"><?php echo esc_html( $options['element_selectors'] ); ?></textarea>

			<p class="description">
				<?php echo wp_kses_post( $args['description'] ); ?>
			</p>

			<p class="description">
				<strong><?php esc_html_e( 'Note:', 'lity' ); ?></strong> <?php esc_html_e( 'After you are done adding element selectors hit enter or comma, to add additional selectors.', 'lity' ); ?>
			</p>

			<?php

		}

		/**
		 * Show the excluded element selector textarea.
		 *
		 * @param array $args Field args.
		 */
		public function lity_excluded_element_selector_textarea( $args ) {

			$options = $this->get_lity_options();

			?>

			<textarea id="<?php echo esc_attr( $args['label_for'] ); ?>" name="lity_options[<?php echo esc_attr( $args['label_for'] ); ?>]" cols="80" rows="10" style="resize: vertical; max-height: 300px;"><?php echo esc_html( $options['excluded_element_selectors'] ); ?></textarea>

			<p class="description">
				<?php echo wp_kses_post( $args['description'] ); ?>
			</p>

			<p class="description">
				<strong><?php esc_html_e( 'Example:', 'lity' ); ?></strong> <?php esc_html_e( '`.content-area img` will disable lightboxes for images in the post content area of your site.', 'lity' ); ?>
			</p>

			<p class="description">
				<strong><?php esc_html_e( 'Note:', 'lity' ); ?></strong> <?php esc_html_e( 'After you are done adding element selectors hit enter or comma, to add additional selectors.', 'lity' ); ?>
			</p>

			<?php

		}

		/**
		 * Button to regenerate the lity_media transient.
		 *
		 * @param array $args Field args.
		 */
		public function lity_clear_transient_button( $args ) {

			$url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'lity-regenerate-transient',
					),
					menu_page_url( 'lity-options', false )
				),
				'lity-regenerate-transient'
			);

			printf(
				'<a href="%1$s" class="button delete">%2$s</a>
				<p class="description">%3$s</p>',
				esc_url( $url ),
				esc_html__( 'Clear Lity Transient', 'lity' ),
				esc_html( $args['description'] )
			);

		}

		/**
		 * Button to reset the plugin back to "factory settings".
		 *
		 * @param array $args Field args.
		 */
		public function lity_reset_plugin_settings_button( $args ) {

			$url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'lity-reset-plugin-settings',
					),
					menu_page_url( 'lity-options', false )
				),
				'lity-reset-plugin-settings'
			);

			printf(
				'<a href="%1$s" id="lity-reset-plugin-settings" class="button delete" onclick="if (confirm(\'%2$s\')){return true;}else{event.stopPropagation(); event.preventDefault();};">%3$s</a>
				<p class="description">%4$s</p>',
				esc_url( $url ),
				esc_attr__( 'Are you sure you want to reset the plugin settings? This cannot be undone.', 'lity' ),
				esc_html__( 'Reset Plugin Settings', 'lity' ),
				esc_html( $args['description'] )
			);

		}

		/**
		 * Lity options page markup.
		 */
		public function lity_options_page() {

			if ( ! current_user_can( 'manage_options' ) ) {

				return;

			}

			if ( isset( $_GET['lity-transient-cleared'] ) ) { // phpcs:ignore

				printf(
					'<div id="lity-transient-data-cleared-notice" class="notice notice-success">
						<p>
							<strong>%1$s</strong>
						</p>
					</div>',
					esc_html__( 'Lity - Responsive Lightboxes transient data successfully cleared.', 'lity' )
				);

			}

			if ( isset( $_GET['lity-settings-reset'] ) ) { // phpcs:ignore

				printf(
					'<div id="lity-settings-reset-notice" class="notice notice-success">
						<p>
							<strong>%1$s</strong>
						</p>
					</div>',
					esc_html__( 'Lity - Responsive Lightboxes settings successfully reset, and the cache has been cleared.', 'lity' )
				);

			}

			?>

			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form action="options.php" method="post">
				<?php
					settings_fields( 'lity' );
					do_settings_sections( 'lity' );
					submit_button( __( 'Save Settings', 'lity' ) );
				?>
				</form>
			</div>

			<?php

		}

	}

}
