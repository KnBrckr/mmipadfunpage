<?php

/*
  Plugin Name: mmipadfunpage
  Plugin URI:  http://action-a-day.com/
  Description: mmipadfunpage Site Customizations
  Version:     0.1
  Author:      Kenneth J. Brucker
  Author URI:  http://action-a-day.com
  License:     GPL2
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Domain Path: /i18n/languages
  Text Domain: aad-plugin-domain

  Copyright 2017 Kenneth J. Brucker  (email : ken.brucker@action-a-day.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * @package AAD\mmipadfunpage
 * 
 * Uses the Pimple framework defined at https://pimple.sensiolabs.org
 * 
 * Review files in classes directory
 *  - Change mmipadfunpage
 *  - Confirm Namespace usage
 */

/**
 *  Protect from direct execution
 */
if ( !defined( 'WP_PLUGIN_DIR' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	die( 'I don\'t think you should be here.' );
}

/*
 * Define classes that will be used
 */

use AAD\mmipadfunpage\Plugin;
use AAD\mmipadfunpage\ClassName;

/**
 * Define autoloader for plugin
 */
spl_autoload_register( function ( $class_name ) {
	if ( false !== strpos( $class_name, 'AAD\mmipadfunpage' ) ) {
		$classes_dir = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
		$class_file	 = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name ) . '.php';
		require $classes_dir . $class_file;
	}
} );

/**
 * Hook plugin loaded to execute setup
 */
add_action( 'plugins_loaded', function () {
	$plugin = new Plugin();

	$plugin[ 'version' ]	 = '0.1';
	$plugin[ 'path' ]		 = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR;
	$plugin[ 'url' ]		 = plugin_dir_url( __FILE__ );

	/*
	 * 
	 * Instantiate needed plugin classes

	  $variationTableService = function ($product) {
	  $varTable = new VariationTable( $product );
	  return $varTable;
	  };
	  $plugin['VariationScreen'] = function ($p) use ($variationTableService) {
	  $varScreen = new VariationScreen( $p['version'], $p['url'], $variationTableService );
	  return $varScreen;
	  };

	  $variationScreen = $plugin[ 'VariationScreen' ];

	 */

	$plugin->run();
} );
