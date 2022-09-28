<?php

/**
 * Sass To CSS Compiling Admin Settings Page
 *
 * @author Sajjad Hossain Sagor
 */
class SASS_TO_CSS_COMPILER_SETTINGS
{
    private $settings_api;

    function __construct()
    {
    	// add settings api wrapper
		require SASSTOCSS_PLUGIN_PATH . 'includes/class.settings-api.php';
        
        $this->settings_api = new SASS_TO_CSS_COMPILER_SETTINGS_API;

        add_action( 'admin_init', array( $this, 'admin_init') );
        
        add_action( 'admin_menu', array( $this, 'admin_menu') );
    }

    public function admin_init()
    {
        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    public function admin_menu()
    {
        add_options_page( 'Sass to CSS Compiler', 'Sass to CSS Compiler', 'manage_options', 'sass-to-css-compiler.php', array( $this, 'render_settings_page' ) );
    }

    public function get_settings_sections()
    {
        $sections = array(
            array(
                'id'    => 'sasstocss_basic_settings',
                'title' => __( 'General Settings', 'sass-to-css-compiler' )
            )
        );
        
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public function get_settings_fields()
    {
        // get compiling mode
        $mode = self::get_option( 'mode', 'sasstocss_basic_settings' );

        // if mode is set
        if ( $mode )
        {
            $preview_img = intval( $mode );
            
            $preview_src = SASSTOCSS_PLUGIN_URL . "admin/assets/images/$preview_img.png";
            // if not mode set default to Expanded
        }
        else
        {
            $preview_src = SASSTOCSS_PLUGIN_URL . "admin/assets/images/1.png";
        }

		$settings_fields = array(
            'sasstocss_basic_settings' => array(
                array(
                    'name'    => 'enable',
                    'label'   => __( 'Enable Compiler', 'sass-to-css-compiler' ),
                    'type'    => 'checkbox',
                    'desc'    => __( 'Checking this box will enable compiling .scss files from themes & plugins folders', 'sass-to-css-compiler' )
                ),
                array(
                    'name'    => 'skip_external',
                    'label'   => __( 'Ignore External Files', 'sass-to-css-compiler' ),
                    'type'    => 'checkbox',
                    'always_checked' => 'on',
                    'disabled'=> 'disabled',
                    'desc'    => __( 'Always Ignore .scss files from CDNs, other domains (external files) and relative paths (built in files)', 'sass-to-css-compiler' )
                ),
                array(
                    'name'    => 'exclude',
                    'label'   => __( 'Exclude Files From Compiling', 'sass-to-css-compiler' ),
                    'type'    => 'text',
                    'desc'    => __( 'Add comma separated scss files name to exclude it from Compiling', 'sass-to-css-compiler' ),
                    'placeholder' => __( 'admin.scss, plugins.scss, backend.scss', 'sass-to-css-compiler' )
                ),
                array(
                    'name'    => 'include',
                    'label'   => __( 'Include Files From Compiling', 'sass-to-css-compiler' ),
                    'type'    => 'text',
                    'desc'    => __( 'Add comma separated scss files name to include it while Compiling... Note if added any! only those files will be compiled', 'sass-to-css-compiler' ),
                    'placeholder' => __( 'menu.scss, footer.scss', 'sass-to-css-compiler' )
                ),
                array(
                    'name'    => 'mode',
                    'label'   => __( 'Compiling Mode', 'sass-to-css-compiler' ),
                    'type'    => 'select',
                    'options' => array(
                    	'0' => 'Expanded (Default : Recommended)',
                    	'1' => 'Nested',
                        '2' => 'Compact (Each Rule in a New Line)',
                        '3' => 'Compressed (Minified but Comments Are kept )',
                    	'4' => 'Crunched (Super Minified)'
                    ),
                    'default' => '0',
                    'desc'    => __( 'See below Screenshots To Understand Formatting Output<img class="formatting_preview" style="display: block;margin-top: 10px;" src="'.$preview_src.'">', 'sass-to-css-compiler' ),
                )
            )
        );

        return $settings_fields;
    }

    /**
     * Render settings fields
     *
     */

    public function render_settings_page()
    {    
        echo '<div class="wrap">';

	        $this->settings_api->show_navigation();
	       
	        $this->settings_api->show_forms();

        echo '</div>';
    }

    /**
	 * Returns option value
	 *
	 * @return string|array option value
	 */

	static public function get_option( $option, $section, $default = '' )
    {
	    $options = get_option( $section );

	    if ( isset( $options[$option] ) )
        {
	        return $options[$option];
	    }

	    return $default;
	}
}

$sass_to_css_compiler_settings = new SASS_TO_CSS_COMPILER_SETTINGS();
