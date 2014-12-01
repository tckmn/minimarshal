# MiniMarshal

A versatile tagging and categorization system, in a single PHP file.

For a live example, see http://keyboardfire.com/pages/. An example you can use on your own website is provided in the test.php file.

Usage instructions:

1. Upload minimarshal.php and test.php to a location of your choice.
2. Create a minimarshaldefs.php file in accordance to the example file provided, and upload that to the same directory. (You will also need to create a mySQL database in order to provide the correct definitions.)
3. Visit test.php and click the "admin" link in the upper right. Enter the password for which you provided a whirlpool hash in minimarshaldefs.php (TODO make this easier).
4. Click the "setup" button **once** at the very bottom for the initial database creation and whatnot, and you can now start editing your page listing!
5. (Optional) Upload Parsedown.php to the same location you uploaded the previous files. This will automatically add Markdown support.
