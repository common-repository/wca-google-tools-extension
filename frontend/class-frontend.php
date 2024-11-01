<?php

/**
 * The frontend-specific functionality of the plugin.
 *
 * @link       https://www.wecodeart.com/
 * @since      1.0.0
 *
 * @package    WCA\EXT\Google
 * @subpackage WCA\EXT\Google\Frontend
 */

namespace WCA\EXT\Google;

use WCA\EXT\Google;
use function WeCodeArt\Functions\get_prop;

/**
 * The frontend-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the frontend-specific stylesheet and JavaScript.
 *
 * @package    WCA\EXT\Google
 * @subpackage WCA\EXT\Google\Frontend
 * @author     Bican Marian Valeriu <marianvaleriubican@gmail.com>
 */
class Frontend {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The config of this plugin.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		mixed    $config    The config of this plugin.
	 */
	private $config;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since	1.0.0
	 * @param	string    $plugin_name	The name of this plugin.
	 * @param	string    $version		The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $config ) {
		$this->plugin_name	= $plugin_name;
		$this->version 		= $version;
		$this->config 		= $config;
	}

	/**
	 * Generate Output
	 *
	 * @since 	2.0.0
	 * @version	2.0.0
	 *
	 * @return 	void
	 */
	public function generate_output() {
		$defaults = [
			[
				'id'		=> 'google_webmasters',
				'hook' 		=> 'wp_head',
				'content' 	=> '<meta name="google-site-verification" content="%s" />'
			],
			[
				'id'		=> 'google_publisher',
				'hook' 		=> 'wp_enqueue_scripts',
				'content' 	=> [ $this, 'adsense_callback' ],
			],
			[
				'id'		=> 'google_analytics',
				'hook' 		=> 'wp_enqueue_scripts',
				'content' 	=> [ $this, 'analytics_callback' ]
			]
		];

		// Merge config with defaults
		$config = wp_parse_args( get_prop( $this->config, 'fields', [] ), $defaults );

		// Get only required data.
		$config = array_map( function( $item ) {
			return wp_array_slice_assoc( $item, [ 'id', 'hook', 'content' ] );
		}, $config );

		// Generate output
		foreach( $config as $hook ) {
			if( ! isset( $hook['id'] ) || ! isset( $hook['hook'] ) || ! isset( $hook['content'] ) ) continue;

			$content	= $hook['content'];
			$value		= trim( wecodeart_option( $hook['id'] ) );

			if( empty( $value ) ) continue;
			
			add_action( $hook['hook'], function() use ( $content, $value ) {
				if( is_callable( $content ) ) {
					return $content( $value );
				}

				return printf( $content . "\n", esc_attr( $value ) );
			} );
		}
	}

	/**
	 * Adsense callback.
	 *
	 * @since 	2.0.0
	 *
	 * @param 	string 	$value The vaue.
	 *
	 * @return 	void
	 */
	public function adsense_callback( $value ) {
		$callback = function( $tag, $handle ) use ( $value ) {
			if ( $handle !== 'google-adsense' ) return $tag;
			
			$tag = str_replace( ' src', ' async data-ad-client="' . esc_attr( $value ) . '" src', $tag );
			$tag = str_replace( ' type=\'text/javascript\'', '', $tag );
	
			return $tag;
		};

		add_filter( 'script_loader_tag', $callback, 10, 2 );

		wp_register_script( 'google-adsense', 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', [], null );
		wp_enqueue_script( 'google-adsense' );
	}
	
	/**
	 * Analytics callback.
	 *
	 * @since 	2.0.0
	 *
	 * @param 	string 	$value The vaue.
	 *
	 * @return 	void
	 */
	public function analytics_callback( $value ) {
		$script = '';
		$script .= 'window.dataLayer = window.dataLayer || [];';
		$script .= 'function gtag(){dataLayer.push(arguments);}';
		$script .= "gtag('js',new Date());";
		$script .= sprintf( "gtag('config','%s');", esc_attr( $value ) );
	
		$google = add_query_arg( [ 'id' => esc_attr( $value ) ], 'https://www.googletagmanager.com/gtag/js' );

		wp_register_script( 'google-tag', $google, [], null, true );
		wp_enqueue_script( 'google-tag' );

		wp_add_inline_script( 'google-tag', $script, 'after' );
	}
}
