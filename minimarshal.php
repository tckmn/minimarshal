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

        // There needs to be at least one tag for posts to show up
        addTag('untagged');
    } catch (PDOException $e) {
        return $e;
    }
}

/**
 * Add a page.
 * @param url The URL of the page you are adding.
 * @param data Any extra data to store along with the URL; optional.
 */
function addPage($url, $data = NULL) {
    global $dbh;

    // add the page stub to the database
    $stmt = $dbh->prepare("INSERT INTO Pages (url, date, data) VALUES " .
        "(?, NOW(), ?)");
    $stmt->execute(array($url, $data));

    // tag it with untagged first
    $stmt = $dbh->prepare("INSERT INTO PageTags (page_id, tag_id) VALUES (?, 1)");
    $stmt->execute(array($dbh->lastInsertId('Pages')));
}

/**
 * Delete a page by its id.
 * @param id The id of the page to delete.
 */
function delPage($id) {
    global $dbh;

    // delete the actual page
    $stmt = $dbh->prepare("DELETE FROM Pages WHERE id = ?");
    $stmt->execute(array($id));

    // delete the references to that page in the page <=> tags table
    $stmt = $dbh->prepare("DELETE FROM PageTags WHERE page_id = ?");
    $stmt->execute(array($id));
}

/**
 * Get pages by certain criteria.
 * @param tags Get only pages that have all of these tags. Defaults to array().
 * @param excludeTags Get only pages that have none of these. Defaults to array().
 */
function getPages($tags = array(), $excludeTags = array()) {
    global $dbh;

    // for $tags and $excludeTags (" WHERE ... AND ... AND ...")
    $whereClause = '';
    foreach ($tags as $tag) {
        $whereClause .= ($whereClause ? ' WHERE' : ' AND') . " ? IN (t.name)";
    }
    foreach ($excludeTags as $excludeTag) {
        $whereClause .= ($whereClause ? ' WHERE' : ' AND') . " ? NOT IN (t.name)";
    }

    // this query is terrifying
    $stmt = $dbh->prepare("SELECT p.*, GROUP_CONCAT(t.id) AS tag_ids, " . "
        GROUP_CONCAT(t.name) AS tag_names FROM Pages p " .
        "INNER JOIN Tags t ON t.id IN (SELECT tag_id FROM PageTags " .
        "WHERE page_id = p.id)$whereClause GROUP BY p.id;");

    // execute without args if no $tags or $excludeTags
    if ($whereClause === '') $stmt->execute();
    else $stmt->execute(array_merge($tags, $excludeTags));

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Add a tag.
 * @param name The name of the tag you are adding.
 * @param parent Which tag should be this tag's parent; optional.
 */
function addTag($name, $parentId = NULL) {
    global $dbh;
    $stmt = $dbh->prepare("INSERT INTO Tags (name, parent_id) VALUES " .
        "(?, ?)");
    $stmt->execute(array($name, $parentId));
}

/**
 * Get all tags.
 */
function getTags() {
    global $dbh;
    $stmt = $dbh->prepare("SELECT t.*, (SELECT name FROM Tags p " .
        "WHERE p.id = t.parent_id) AS parent_name FROM Tags t");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Add a tag to a page.
 * @param pageid The id of the page to add the tag to.
 * @param tagid The id of the tag to add to the page.
 */
function addPageTag($pageid, $tagid) {
    global $dbh;
    $stmt = $dbh->prepare("INSERT INTO PageTags (page_id, tag_id) VALUES (?, ?)");
    $stmt->execute(array($pageid, $tagid));
}

/**
 * Delete a tag from a page.
 * @param pageid The id of the page to remove the tag from.
 * @param tagid The id of the tag to remove from the page.
 */
function delPageTag($pageid, $tagid) {
    global $dbh;
    $stmt = $dbh->prepare("DELETE FROM PageTags WHERE page_id = ? AND tag_id = ?");
    $stmt->execute(array($pageid, $tagid));
}

/**
 * Get a tag id from its name.
 * @param name The name of the tag to get the id of.
 * @return The id of the tag.
 */
function tagIdFromName($name) {
    global $dbh;
    $stmt = $dbh->prepare("SELECT id FROM Tags WHERE name = ?");
    $stmt->execute(array($name));
    $fetched = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fetched['id'];
}
