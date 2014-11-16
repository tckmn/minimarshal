<form method='get'>
    <input name='setup' type='submit' value='Setup' />

    <hr />

    <label for='url'>URL<label> <input name='url' type='text' /><br />
    <label for='data'>Data<label> <input name='data' type='text' /><br />
    <input name='add' type='submit' value='Add page' />

    <hr />

    <input name='get' type='submit' value='Get pages' />
</form>

<?php

require_once('minimarshal.php');

echo "<hr />Result:<pre>";
if (isset($_GET['setup'])) {
    var_dump(setup());
} else if (isset($_GET['add'])) {
    var_dump(addPage($_GET['url'], $_GET['data']));
} else if (isset($_GET['get'])) {
    var_dump(getPages());
} else {
    echo "Please choose an action and fill out the form to continue.";
}
echo "</pre>";
