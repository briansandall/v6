# Lineman's Equipment (powered by CubeCart)

This branch of this CubeCart fork is solely for developing Lineman's Equipment.

## Contributing

1. Follow the installation instructions (see below) to get a working local copy

2. Create and checkout a new branch* to begin working on a feature

	> git checkout -b feature_branch

* Create a new branch based off of the 'master' branch for each feature
  unless it depends on an earlier feature not yet merged

3. Make changes to the feature_branch and commit them

	> git add --all .
	
	> git commit

4. When you are ready to share your changes, push the branch to the Bitbucket repository

	> git push origin feature_branch

5. After review, your changes will be merged into the main branch ('master') and you can delete your branch

## Installation

1. Clone the installation repository to /webroot/git/cc (or a directory of your choosing within the web root directory)

	> Open git shell or command prompt in /webroot/git/ directory (parent of where cc will install)
	
	> git clone https://bitbucket.org/nldeveloper/cc.git --branch master --single-branch
	
	> /webroot/path/to/cc will now contain the branch 'master'

2. Set up a database and user for CubeCart in your local MySQL instance

	> Open the MySQL console
	
	> CREATE DATABASE `cubecart`; // or whatever name you wish

3. Create a new database user (skip this step if using the root user)

	> CREATE USER 'cc_user'@'localhost' IDENTIFIED BY 'password';

4. Grant database permissions to whichever user you used in step 3

	> GRANT ALL PRIVILEGES ON cubecart.* TO 'cc_user'@'localhost';
	
	> FLUSH PRIVILEGES;

5. Navigate to 127.0.0.1/path/to/cc and complete the setup process using whatever db credentials you have set up

6. Install required modules: (TODO can these be downloaded automatically as git submodules?)

	a. PayPal Pro
		
		> Download version 1.0.8 from https://www.cubecart.com/extensions/plugins/paypal-pro-express-checkout
		
		> Place in /path/to/cc/modules/plugins/
		
		> Place 'PayPal_acceptance.js' in /path/to/cc/includes/extra/
	
	b. Awsp UPS Shipping
	
		> Should automatically be included with the repository

7. Import data

	> Ask the site admin to send you the cc_data.sql file
	
	> mysql -u root -p db_name < c:/path/to/cc_data.sql

8. Verify/Update Store Settings

	> Log in to 127.0.0.1/path/to/cc/admin as 'admin', password 'Welcome1'
	
	> Click on 'Store Settings' -> 'SSL' tab and update the Store URL to match your directory
	
	> Click on 'Store Settings' -> 'Logos' and update the logo to '/path/to/cc/images/logos/logo.png'

## Troubleshooting

Q: Page 404 errors when navigating away from the home page

A: Check that the Apache rewrite_module is enabled

Q: Can't log in to admin panel

A: Are you using Chrome?

	> Yes
	
		Make sure the settings under Store Settings -> SSL all use 127.0.0.1 instead of localhost.
		This will already be the case if you imported the test database data, or can be done using a different browser.
		See https://forums.cubecart.com/topic/50319-resolved-issues-getting-started
	
	> No
	
		Make sure you are using the correct username and password 
