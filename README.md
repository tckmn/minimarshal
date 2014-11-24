# MiniMarshal

A versatile tagging and categorization system, in a single PHP file.

For a live example, see http://keyboardfire.com/pages/. An example you can use on your own website is provided in the test.php file.

Usage instructions:

1. Upload minimarshal.php and test.php to a location of your choice.
2. Create a minimarshaldefs.php file in accordance to the example file provided, and upload that to the same directory. (You will also need to create a mySQL database in order to provide the correct definitions.)
3. Create a \_minimarshal\_session directory in the same directory. This is for PHP sessions with the admin version. TODO: this step will be removed soon and the directory will automatically be created.
4. Run the setup() PHP function somehow (you can create a PHP page with `<?php require_once('minimarshal.php'); new MiniMarshal()->setup();` (untested) or something like that in order to do this; TODO: make this easier).
5. Access MiniMarshal at `path/to/directory/test.php` (you can rename it to a name of your choice), and append `?admin` to the URL to access the admin page (you will need to update the password hash; TODO: make this easier than going into the test.php code itself).
