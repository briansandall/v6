<?php
/**
 * AWSP UPS Module for CubeCart v6
 * ========================================
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @license GPL-3.0 http://opensource.org/licenses/GPL-3.0
 *
 * Adapted from the original Awsp library autoloader.php by Alex Fraundorf.
 * INSTALLATION: Replace the original Awsp library autoloader.php with this one.
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2014. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@devellion.com
 * License:  GPL-2.0 http://opensource.org/licenses/GPL-2.0
 */
namespace Awsp\Ship;

/**
 * A simple autoloader for the Awsp\Ship classes based on PSR-0
 * 
 * @param string $class_name the class being loaded
 * @return void
 * @version updated 10/30/2015
 * @since 12/28/2012
 */
function awsp_ship_autoloader($class_name) {
	// remove any leading backslash
	$class_name = ltrim($class_name, '\\');
	// explode the class name into an array
	$class_name_array = explode('\\', $class_name);
	// extract the last element (file name) from the array
	$file_name = array_pop($class_name_array);
	// begin building the file path, replacing any backslashes with forward slashes for conformity
	$file_path = str_replace('\\', '/', CC_INCLUDES_DIR) . 'lib/Awsp/libs/';
	// append the namespace pieces to the file path
	$file_path .= implode('/', $class_name_array) . '/';
	// complete the file path
	$file = $file_path . $file_name . '.php';
	// see if the file exists and is readable in this directory
	if(is_readable($file)) {
		// require the file if it exists
		require($file);
	}
}

// register the autoloader
spl_autoload_register('\Awsp\Ship\awsp_ship_autoloader');
