<?php
class Themesara_Toolset_Hooks {

    private $hook_suffix;

    private $theme_author = 'themesara';

    public static function instance() {

        static $instance = null;

        if ( null === $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    public function __construct() {}

    public function import_menu() {

        if( !class_exists('Advanced_Import')){
            $this->hook_suffix[] = add_theme_page( esc_html__( 'Theme Demo Import ','themesara-toolset' ), esc_html__( 'One Click Demo Import','themesara-toolset'  ), 'manage_options', 'advanced-import', array( $this, 'demo_import_screen' ) );
        }
    }

  
    public function enqueue_styles( $hook_suffix ) {

        if ( !is_array($this->hook_suffix) || !in_array( $hook_suffix, $this->hook_suffix )){
            return;
        }

        wp_enqueue_style( THEMESARA_TOOLSET_PLUGIN_NAME, THEMESARA_TOOLSET_URL . 'assets/themesara-toolset.css',array( 'wp-admin', 'dashicons' ), THEMESARA_TOOLSET_VERSION, 'all' );
    }

    public function enqueue_scripts( $hook_suffix ) {

        if ( !is_array($this->hook_suffix) || !in_array( $hook_suffix, $this->hook_suffix )){
            return;
        }

        wp_enqueue_script( THEMESARA_TOOLSET_PLUGIN_NAME, THEMESARA_TOOLSET_URL . 'assets/themesara-toolset.js', array( 'jquery'), THEMESARA_TOOLSET_VERSION, true );

        wp_localize_script( THEMESARA_TOOLSET_PLUGIN_NAME, 'themesara_toolset', array(
            'btn_text' => esc_html__( 'Processing...', 'themesara_toolset' ),
            'nonce'    => wp_create_nonce( 'themesara_toolset_nonce' )
        ) );
    }

    public function demo_import_screen() {
        ?>
        <div id="ads-notice">
            <div class="ads-container">
                <img class="ads-screenshot" src="<?php echo esc_url(themesara_toolset_get_theme_screenshot() )?>" />
                <div class="ads-notice">
                    <h2>
                        <?php
                        printf(
                            esc_html__( 'Welcome! Thank you for choosing %1$s! To get started with ready-made starter site templates. Install the Advanced Import plugin and install Demo Starter Site within a single click', 'themesara-toolset' ), '<strong>'. wp_get_theme()->get('Name'). '</strong>');
                        ?>
                    </h2>

                    <p class="plugin-install-notice"><?php esc_html_e( 'Clicking the button below will install and activate the Advanced Import plugin.', 'themesara-toolset' ); ?></p>

                    <a class="ads-gsm-btn button button-primary button-hero" href="#" data-name="" data-slug="" aria-label="<?php esc_html_e( 'Get started with the Theme', 'themesara-toolset' ); ?>">
                        <?php esc_html_e( 'Get Started', 'themesara-toolset' );?>
                    </a>
                </div>
            </div>
        </div>
        <?php

    }

    public function install_advanced_import() {

        check_ajax_referer( 'themesara_toolset_nonce', 'security' );

        $slug   = 'advanced-import';
        $plugin = 'advanced-import/advanced-import.php';

        $status = array(
            'install' => 'plugin',
            'slug'    => sanitize_key( wp_unslash( $slug ) ),
        );
        $status['redirect'] = admin_url( '/themes.php?page=advanced-import&browse=all&at-gsm-hide-notice=welcome' );

        if ( is_plugin_active_for_network( $plugin ) || is_plugin_active( $plugin ) ) {
            // Plugin is activated
            wp_send_json_success($status);
        }


        if ( ! current_user_can( 'install_plugins' ) ) {
            $status['errorMessage'] = __( 'Sorry, you are not allowed to install plugins on this site.', 'themesara-toolset' );
            wp_send_json_error( $status );
        }

        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        // Looks like a plugin is installed, but not active.
        if ( file_exists( WP_PLUGIN_DIR . '/' . $slug ) ) {
            $plugin_data          = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
            $status['plugin']     = $plugin;
            $status['pluginName'] = $plugin_data['Name'];

            if ( current_user_can( 'activate_plugin', $plugin ) && is_plugin_inactive( $plugin ) ) {
                $result = activate_plugin( $plugin );

                if ( is_wp_error( $result ) ) {
                    $status['errorCode']    = $result->get_error_code();
                    $status['errorMessage'] = $result->get_error_message();
                    wp_send_json_error( $status );
                }

                wp_send_json_success( $status );
            }
        }

        $api = plugins_api(
            'plugin_information',
            array(
                'slug'   => sanitize_key( wp_unslash( $slug ) ),
                'fields' => array(
                    'sections' => false,
                ),
            )
        );

        if ( is_wp_error( $api ) ) {
            $status['errorMessage'] = $api->get_error_message();
            wp_send_json_error( $status );
        }

        $status['pluginName'] = $api->name;

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );
        $result   = $upgrader->install( $api->download_link );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $status['debug'] = $skin->get_upgrade_messages();
        }

        if ( is_wp_error( $result ) ) {
            $status['errorCode']    = $result->get_error_code();
            $status['errorMessage'] = $result->get_error_message();
            wp_send_json_error( $status );
        } elseif ( is_wp_error( $skin->result ) ) {
            $status['errorCode']    = $skin->result->get_error_code();
            $status['errorMessage'] = $skin->result->get_error_message();
            wp_send_json_error( $status );
        } elseif ( $skin->get_errors()->get_error_code() ) {
            $status['errorMessage'] = $skin->get_error_messages();
            wp_send_json_error( $status );
        } elseif ( is_null( $result ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            WP_Filesystem();
            global $wp_filesystem;

            $status['errorCode']    = 'unable_to_connect_to_filesystem';
            $status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'themesara-toolset' );

            // Pass through the error from WP_Filesystem if one was raised.
            if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
                $status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
            }

            wp_send_json_error( $status );
        }

        $install_status = install_plugin_install_status( $api );

        if ( current_user_can( 'activate_plugin', $install_status['file'] ) && is_plugin_inactive( $install_status['file'] ) ) {
            $result = activate_plugin( $install_status['file'] );

            if ( is_wp_error( $result ) ) {
                $status['errorCode']    = $result->get_error_code();
                $status['errorMessage'] = $result->get_error_message();
                wp_send_json_error( $status );
            }
        }

        wp_send_json_success( $status );

    }

    public function add_demo_lists( $current_demo_list ) {

        if( themesara_toolset_get_current_theme_author() != $this->theme_author ){
            return  $current_demo_list;
        }

        $theme_slug = themesara_toolset_get_current_theme_slug();

        switch ($theme_slug):
            case "web-wave-pro":
                $templates = array(
                    array(
                        'title' => __( 'Main Demo', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/web-wave-pro/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'Easy FancyBox',
                                'slug'      => 'easy-fancybox',

                            ),

                            array(
                                'name'      => 'Getwid – Gutenberg Blocks',
                                'slug'      => 'getwid',
                            ),

                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),

                    array(
                        'title' => __( 'Grid Demo', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/grid/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/grid/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/grid/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/grid/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/web-wave-grid/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'Easy FancyBox',
                                'slug'      => 'easy-fancybox',

                            ),

                            array(
                                'name'      => 'Getwid – Gutenberg Blocks',
                                'slug'      => 'getwid',
                            ),

                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),

                    array(
                        'title' => __( ' List Demo', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/list/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/list/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/list/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/list/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/web-wave-list/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'Easy FancyBox',
                                'slug'      => 'easy-fancybox',

                            ),

                            array(
                                'name'      => 'Getwid – Gutenberg Blocks',
                                'slug'      => 'getwid',
                            ),

                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),

                     array(
                        'title' => __( ' Dark Mode Demo', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/dark/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/dark/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/dark/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/dark/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/web-wave-dark/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'Easy FancyBox',
                                'slug'      => 'easy-fancybox',

                            ),

                            array(
                                'name'      => 'Getwid – Gutenberg Blocks',
                                'slug'      => 'getwid',
                            ),

                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),
                );
                break;
            case "web-wave":
                $templates = array(
                    array(
                        'title' => __( 'Default Demo', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/web-wave/',/*Demo Url*/
                        'plugins' => array(

                            array(
                                'name'      => 'Blockspare  – Beautiful Page Building Gutenberg Blocks for WordPress',
                                'slug'      => 'blockspare',
                            ),

                           array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Contact Form by WPForms',
                                'slug'      => 'wpforms-lite',
                            ),
                            
                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            
                        )
                    ),
                );
                break;


                case "sara-log":
                $templates = array(
                    array(
                        'title' => __( 'Default Demo', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/sara-demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/sara-log/',/*Demo Url*/
                        'plugins' => array(

                           array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Contact Form by WPForms',
                                'slug'      => 'wpforms-lite',
                            ), 
                        )
                    ),
                );
                break;


                case "sara-log-pro":
                $templates = array(
                    array(
                        'title' => __( 'Main Demo', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/default/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/sara-log-pro/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),

                    array(
                        'title' => __( 'Sara Food', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/food/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/food/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/food/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/food/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/sara-food/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'Easy FancyBox',
                                'slug'      => 'easy-fancybox',

                            ),

                            array(
                                'name'      => 'Getwid – Gutenberg Blocks',
                                'slug'      => 'getwid',
                            ),

                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),

                    array(
                        'title' => __( ' Sara Fitness', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/fitness/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/fitness/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/fitness/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/fitness/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/sara-fitness/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),

                     array(
                        'title' => __( ' Sara Lifestyle', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/lifestyle/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/lifestyle/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/lifestyle/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/lifestyle/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/sara-lifestyle/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'Getwid – Gutenberg Blocks',
                                'slug'      => 'getwid',
                            ),

                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),

                     array(
                        'title' => __( ' Sara Travel', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/travel/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/travel/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/travel/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/travel/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/sara-travel/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'Getwid – Gutenberg Blocks',
                                'slug'      => 'getwid',
                            ),

                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),
                     array(
                        'title' => __( ' Sara Travel', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/full-width/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/full-width/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/full-width/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/full-width/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/sara-full-width/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'Getwid – Gutenberg Blocks',
                                'slug'      => 'getwid',
                            ),

                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),

                     array(
                        'title' => __( ' Sara Sports', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/sports/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/sports/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/sports/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/sports/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/sara-sports/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'Getwid – Gutenberg Blocks',
                                'slug'      => 'getwid',
                            ),

                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),

                     array(
                        'title' => __( ' Sara Dark Version', 'themesara-toolset' ),/*Title*/
                        'is_premium' => false,/*Premium*/
                        'type' => 'normal',
                        'author' => __( 'Themesara', 'themesara-toolset' ),/*Author Name*/
                        'keywords' => array( 'main', 'demo' ),/*Search keyword*/
                        'categories' => array( 'blog' ),/*Categories*/
                        'template_url' => array(
                            'content' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/dark/content.json',
                            'options' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/dark/options.json',
                            'widgets' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/dark/widgets.json'
                        ),
                        'screenshot_url' => THEMESARA_TOOLSET_TEMPLATE_URL.$theme_slug.'/dark/demo.png',/*Screenshot of block*/
                        'demo_url' => 'https://demo.themesara.com/sara-dark/',/*Demo Url*/
                        'plugins' => array(
                            array(
                                'name'      => 'Everest Forms',
                                'slug'      => 'everest-forms',

                            ),
                            array(
                                'name'      => 'Getwid – Gutenberg Blocks',
                                'slug'      => 'getwid',
                            ),

                            array(
                                'name'      => 'WooCommerce',
                                'slug'      => 'woocommerce',
                            ),

                            array(
                                'name'      => 'Elementor',
                                'slug'      => 'elementor',
                            ),

                            array(
                                'name'      => 'Mailchimp',
                                'slug'      => 'mailchimp-for-wp',
                            ),

                            array(
                                'name'      => 'Instagram Feed',
                                'slug'      => 'instagram-feed',
                            ),

                          
                        )
                    ),
                );
                break;

                
                 
                 

                 
            default:
                $templates = array();
        endswitch;

        return array_merge( $current_demo_list, $templates );

    }
    

	

 
}

/**
 * Begins execution of the hooks.
 *
 * @since    1.0.0
 */
function themesara_toolset_hooks( ) {
    return Themesara_Toolset_Hooks::instance();
}