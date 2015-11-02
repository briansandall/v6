REQUIREMENTS
- Cubecart v6 or higher: http://www.cubecart.com
- Database supporting 'length', 'height', and 'width' fields for products
	See https://github.com/briansandall/v6/commits/product_dimensions
- AWSP Shipping Library:  https://bitbucket.org/briansandall/awsp-ship

INSTALLATION
0.a. Make sure you meet all of the requirements
  b. Update shippable inventory products with appropriate dimensions if not already done
     Shippable items with no dimensions provided will still use weight-based shipping.
1. Remove the standard UPS shipping module if it is installed
	(see below for co-installation instructions)
2.a. Place the AWSP shipping library in CubeCart's /includes/lib/ directory
  b. (Optional) Delete /Awsp/config.php - settings are handled by the module via CubeCart's admin panel
  c. (Optional) Delete example files in the root /Awsp directory
3.a. Place this folder and its contents in CubeCart's /modules/shipping/ directory
  b. Replace the /Awsp/includes/autoloader.php class with the autoloader.php file included with the module
4. Rename the 'Awsp_UPS' folder to 'UPS' (otherwise it appears to customers as 'Awsp UPS')
5. Enable the plugin and edit its settings from the Manage Plugins page of the CubeCart admin panel

DUAL INSTALLATION
If you would like to have both the standard UPS and Awsp UPS modules installed at the
same time, e.g. for comparing rate results, you can do so by changing the following
prior to installing the Awsp UPS module:
	1. Open shipping.class.php in a text editor; change the class name to 'Awsp_UPS'
	2. Open admin/index.tpl in a text editor; change <div id="UPS"> to <div id="Awsp_UPS">
	3. Follow the regular installation instructions, but skip steps 1 and 4.
