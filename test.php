<form method='get'>
    <input name='setup' type='submit' value='Setup' /><br />
    <input name='add' type='submit' value='Add page' />
</form>

<?php

require_once('minimarshal.php');

if (isset($_GET['setup'])) {
    echo "Result of setup(): ";
    var_dump(setup());
} else if (isset($_GET['add'])) {
    echo "Result of addPage(): ";
    var_dump(addPage('http://google.com', 'this is some data'));
}
