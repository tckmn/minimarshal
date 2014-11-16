<?php

/*
 * minimarshal.php
 * @author Keyboard Fire <andy@keyboardfire.com>
 * @license MIT
 */

// CHANGE THIS DEFINITION AND THOSE IN THE FILE IT POINTS TO BEFORE USING
// MINIMARSHAL!!!
define('_MINIMARSHAL_DEFS_PATH', 'minimarshaldefs.php');

require_once(_MINIMARSHAL_DEFS_PATH);
$dbh = new PDO("mysql:host=" . _MINIMARSHAL_DB_HOST . ";dbname=" .
    _MINIMARSHAL_DB_NAME, _MINIMARSHAL_DB_USER, _MINIMARSHAL_DB_PASS);

/**
 * Run this function once and only once to initialize the MiniMarshal database
 * @return PDOException exception object if an error occurred, NULL otherwise
 */
function setup() {
    global $dbh;
    try {
        $dbh->exec("CREATE TABLE Pages (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            url VARCHAR(255) NOT NULL,
            date DATETIME NOT NULL,
            data TEXT
        );");
        $dbh->exec("CREATE TABLE Tags (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            parent_id INT(11)
        );");
        $dbh->exec("CREATE TABLE PageTags (
            page_id INT(11) NOT NULL,
            tag_id INT(11) NOT NULL
        );");
    } catch (PDOException $e) {
        return $e;
    }
}

/**
 * Add a page.
 * @param url The URL of the page you are adding.
 * @param data Any extra data to store along with the URL; optional.
 * @return PDOException exception object if an error occurred, NULL otherwise
 */
function addPage($url, $data = NULL) {
    global $dbh;
    try {
        $stmt = $dbh->prepare("INSERT INTO Pages (url, date, data) VALUES " .
            "(?, NOW(), ?)");
        $stmt->execute(array($url, $data));
    } catch (PDOException $e) {
        return $e;
    }
}
