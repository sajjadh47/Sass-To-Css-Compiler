<?php
/*
Plugin Name: Sass To CSS Compiler 
Plugin URI : https://github.com/sajjadh47/sass-to-css-compiler
Description: Compile Your Theme-Plugin Sass (.scss) files to .css on the fly.
Version: 1.0.3
Author: Sajjad Hossain Sagor
Author URI: https://profiles.wordpress.org/sajjad67
Text Domain: sass-to-css-compiler
Domain Path: /languages

License: GPL2
This WordPress Plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

This free software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this software. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// ---------------------------------------------------------
// Define Plugin Folders Path
// ---------------------------------------------------------
define( "SASSTOCSS_PLUGIN_PATH", plugin_dir_path( __FILE__ ) );

define( "SASSTOCSS_PLUGIN_URL", plugin_dir_url( __FILE__ ) );

// Create cache directory on plugin activation
register_activation_hook( __FILE__, 'wp_scss_create_directory' );

function wp_scss_create_directory()
{
	$upload_dir = WP_SASS_CSS_TO_COMPILER::get_cache_dir( true );
	
	// check if upload directory is writable...
	if ( is_writable( $upload_dir ) )
	{
		// create cache dir if not already there
		if ( ! is_dir( $upload_dir . '/scss_cache' ) )
		{    
			mkdir( $upload_dir . '/scss_cache', 0700 );
		}
	}
	else
	{
		// is this plugin active?
		if ( is_plugin_active( plugin_basename( __FILE__ ) ) )
		{    
			// deactivate the plugin
			deactivate_plugins( plugin_basename( __FILE__ ) );
			
			// unset activation notice
			unset( $_GET[ 'activate' ] );
			
			// display notice
			add_action( 'admin_notices', function()
			{
				echo '<div class="error notice is-dismissible">';
					echo __( 'Upload Directory is not writable! Please make it writable to store cache files.', 'sass-to-css-compiler' );
				echo '</div>';
			} );
		}
	}
}

// clear cache if requested
add_action( 'admin_init', function()
{
	global $pagenow;
	
	// check if it is plugin option page & requested for clearing cache...
	if ( $pagenow == 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] == 'sass-to-css-compiler.php'  && isset( $_GET['clear_cache'] ) && $_GET['clear_cache'] == 'yes' )
	{
		WP_SASS_CSS_TO_COMPILER::clear();

		// display notice
		add_action( 'admin_notices', function()
		{
			echo '<div class="notice notice-success is-dismissible" style="padding: 10px 12px;">';
				echo __( 'Compiled Files Successfully Deleted! New Cache Files Will Be Generated Soon if Enabled!', 'sass-to-css-compiler' );
			echo '</div>';
		} );
	}
} );

/**
 * Add Go To Settings Page in Plugin List Table
 *
 */
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function( $links )
{    
	$plugin_actions = array();
		
	$plugin_actions[] = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=sass-to-css-compiler.php' ), __( 'Settings', 'sass-to-css-compiler' ) );

	return array_merge( $links, $plugin_actions );
} );

// add plugin settings to admin menu
require SASSTOCSS_PLUGIN_PATH . 'admin/admin_settings.php';

use ScssPhp\ScssPhp\Compiler;

/**
* Sass to CSS Compiler Class
*/
class WP_SASS_CSS_TO_COMPILER
{
	/**
	 * Compile the provided source code
	 *
	 * @return string source code
	 */
	public function compile( $source_code = '', $import_path )
	{
		// add the compiling library [https://github.com/scssphp/scssphp/]
		require SASSTOCSS_PLUGIN_PATH . 'includes/library/vendor/autoload.php';

		if ( $source_code == '' ) return '';

		$formatter = array(
			"ScssPhp\ScssPhp\Formatter\Expanded",
			"ScssPhp\ScssPhp\Formatter\Nested",
			"ScssPhp\ScssPhp\Formatter\Compact",
			"ScssPhp\ScssPhp\Formatter\Compressed",
			"ScssPhp\ScssPhp\Formatter\Crunched"
		);

		$setFormatter = "ScssPhp\ScssPhp\Formatter\Expanded";

		$compiling_mode = SASS_TO_CSS_COMPILER_SETTINGS::get_option( 'mode', 'sasstocss_basic_settings', '1' );

		if ( isset( $formatter[$compiling_mode] ) )
		{    
			$setFormatter = $formatter[$compiling_mode];
		}

		$compiler = new Compiler;
		
		$compiler->setFormatter( $setFormatter );
		
		$compiler->setImportPaths( $import_path );

		try
		{
			// Compile the SCSS to CSS
			$compiled_css = $compiler->compile( $source_code );

			return $compiled_css;
		}
		catch ( Exception $e )
		{
			error_log( $e->getMessage() );

			return false;
		}
	}

	/**
	 * Save compiled css to a file
	 *
	 * @return boolean
	 */
	public function save( $code, $filename )
	{
		// get cache folder
		$cache_folder = self::get_cache_dir();

		file_put_contents( $cache_folder . '/' . $filename , $code );
	}

	/**
	 * Clear Cache
	 *
	 * @return boolean
	 */
	static public function clear()
	{
		// get cache folder
		$cache_folder = self::get_cache_dir();

		// check if cache directory exists
		if ( is_dir( $cache_folder ) )
		{
			// get all files from that folder
			$files = glob( $cache_folder . '/*' ); // get all file names
		
			foreach( $files as $file )
			{
				// iterate files
				if( is_file( $file ) )
				
				unlink( $file ); // delete file
			}
		}
	}

	/**
	 * Get compiled file storing cache directory
	 *
	 * @return boolean
	 */
	static public function get_cache_dir( $base_dir_only = false, $dir = 'basedir' )
	{
		$upload = wp_upload_dir();
	
		$upload_dir = $upload[$dir];

		if ( $base_dir_only )
		{   
		   return $upload_dir;
		}

		return $upload_dir . '/scss_cache';
	}
}

// Compile on run time and cache it for next time use
add_filter( 'style_loader_src', function( $src, $handle )
{
	$compiler_enabled = SASS_TO_CSS_COMPILER_SETTINGS::get_option( 'enable', 'sasstocss_basic_settings', 'off' );

	// compiler is not enabled so nothing to do here...
	if ( $compiler_enabled !== 'on' ) return $src;

	$url = parse_url( $src );

	$pathinfo = pathinfo( $url['path'] );

	// Check if it's a scss file or not
	if ( isset( $pathinfo['extension'] ) && $pathinfo['extension'] !== 'scss' ) return $src;

	// if stylesheet is built in wp don't touch it
	$built_in_script = preg_match_all( '/(\/wp-includes\/)|(\/wp-admin\/)/', $src, $matches );

	if ( $built_in_script === 1 ) return $src;

	// Convert $src to relative paths
	$relative_path = preg_replace( '/^' . preg_quote( site_url(), '/' ) . '/i', '', $src );

	// Don't do anything if file is from CDN, external site or relative path
	if ( preg_match( '#^//#', $relative_path ) || strpos( $relative_path, '/' ) !== 0 ) return $src;

	$excluded_files = SASS_TO_CSS_COMPILER_SETTINGS::get_option( 'exclude', 'sasstocss_basic_settings', '' );

	$included_files = SASS_TO_CSS_COMPILER_SETTINGS::get_option( 'include', 'sasstocss_basic_settings', '' );

	$cache_dir = WP_SASS_CSS_TO_COMPILER::get_cache_dir();
	
	$cache_dir_url = WP_SASS_CSS_TO_COMPILER::get_cache_dir( false, 'baseurl' );

	// get script file name
	$filename = basename( $src );

	// check if include files is not empty
	if ( $included_files !== '' )
	{    
		// get all comma separated file list
		$included_files_list = explode( ',', $included_files );

		// check if any valid comma separated file exists
		if ( ! empty( $included_files_list ) )
		{    
			// if not included don't continue
			if ( ! in_array( $filename , $included_files_list ) ) return $src;
		}
	}
	else
	{
		if ( $excluded_files !== '' )
		{    
			// get all comma separated file list
			$excluded_files_list = explode( ',', $excluded_files );

			// check if any valid comma separated file exists
			if ( ! empty( $excluded_files_list ) )
			{    
				// if not excluded don't continue
				if ( in_array( $filename , $excluded_files_list ) ) return $src;
			}
		}
	}

	// check if file is already generated... if so load cache file
	if ( file_exists( $cache_dir .'/'. $filename ) )
	{
		$src = $cache_dir_url .'/'. $pathinfo['filename'] . '.css';
	}
	else
	{
		// get stylesheet file content
		$scss_code = file_get_contents( $src );

		$scss_compiler_obj = new WP_SASS_CSS_TO_COMPILER;

		// Create a complete path
		$import_path = rtrim( $_SERVER['DOCUMENT_ROOT'], '/') . $pathinfo['dirname'];

		$compiled_content = $scss_compiler_obj->compile( $scss_code, $import_path );

		if ( $compiled_content )
		{
			$src = $cache_dir_url .'/'. $pathinfo['filename'] . '.css';
			
			$scss_compiler_obj->save( $compiled_content, $pathinfo['filename'] . '.css' );
		}
	}

	// keep any stylesheets file args... like ver
	return empty( $url['query'] ) ? $src : $src . '?' . $url['query'];

}, 101, 2 );
