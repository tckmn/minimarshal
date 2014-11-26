<?php

/*
 * minimarshal.php
 * @author Keyboard Fire <andy@keyboardfire.com>
 * @license MIT
 */

// CHANGE THIS DEFINITION AND THOSE IN THE FILE IT POINTS TO BEFORE USING
// MINIMARSHAL!!!
define('_MINIMARSHAL_DEFS_PATH', 'minimarshaldefs.php');

// TODO autocreate this dir if does not exist
// TODO handle admin login here, not in test.php
session_save_path(__DIR__ . '/_minimarshal_session'); session_start();

require_once(_MINIMARSHAL_DEFS_PATH);

class MiniMarshal {

    /**
     * The database handle. Initialized in constructor.
     * @var PDO
     */
    private $dbh;

    /**
     * Constructor. (This doc-comment is absolutely worthless. I should make
     * it better.)
     */
    function __construct() {
        $this->dbh = new PDO("mysql:host=" . _MINIMARSHAL_DB_HOST . ";dbname=" .
            _MINIMARSHAL_DB_NAME, _MINIMARSHAL_DB_USER, _MINIMARSHAL_DB_PASS);
    }

    /**
     * Run this function once and only once to initialize the MiniMarshal database
     * @return PDOException exception object if an error occurred, NULL otherwise
     */
    function setup() {
        try {
            $this->dbh->exec("CREATE TABLE Pages (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                url VARCHAR(255) NOT NULL,
                date DATETIME NOT NULL,
                data TEXT
            );");
            $this->dbh->exec("CREATE TABLE Tags (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                parent_id INT(11)
            );");
            $this->dbh->exec("CREATE TABLE PageTags (
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
     * @return An error message as a string if there was bad input; NULL otherwise.
     */
    function addPage($url, $data = NULL) {
        if (!$url) return "You must provide a URL for your page!";

        $stmt = $this->dbh->prepare("SELECT COUNT(*) FROM Pages WHERE url = ?");
        $stmt->execute(array($url));
        $fetched = $stmt->fetch();
        if ($fetched[0] > 0) return "That page already exists!";

        // add the page stub to the database
        $stmt = $this->dbh->prepare("INSERT INTO Pages (url, date, data) VALUES " .
            "(?, UTC_TIMESTAMP(), ?)");
        $stmt->execute(array($url, $data));

        // tag it with untagged first
        $stmt = $this->dbh->prepare("INSERT INTO PageTags (page_id, tag_id) VALUES (?, 1)");
        $stmt->execute(array($this->dbh->lastInsertId('Pages')));
    }

    /**
     * Delete a page by its id.
     * @param id The id of the page to delete.
     * @return An error message as a string if there was bad input; NULL otherwise.
     */
    function delPage($id) {
        // delete the actual page
        $stmt = $this->dbh->prepare("DELETE FROM Pages WHERE id = ?");
        $stmt->execute(array($id));

        // delete the references to that page in the page <=> tags table
        $stmt = $this->dbh->prepare("DELETE FROM PageTags WHERE page_id = ?");
        $stmt->execute(array($id));
    }

    /**
     * Get pages by certain criteria.
     * @param tags Get only pages that have all of these tags. Defaults to array().
     * @param excludeTags Get only pages that have none of these. Defaults to array().
     * @return An array of associative arrays which contain keys id, url, data,
     *     tag_names, tag_ids, parent_ids, and tags (a combination of the last three).
     */
    function getPages($tags = array(), $excludeTags = array()) {
        // for $tags and $excludeTags
        $filterClauses = '';
        $i = 1;
        foreach ($tags as $tag) {
            $filterClauses .= "
                INNER JOIN Tags t$i
                    ON EXISTS
                        (SELECT * FROM PageTags
                            WHERE page_id = p.id
                            AND tag_id = t$i.id)
                    AND ? = t$i.name
            ";
            ++$i;
        }
        foreach ($excludeTags as $excludeTag) {
            $filterClauses .= "
                LEFT OUTER JOIN Tags t$i
                    ON EXISTS
                        (SELECT * FROM PageTags
                            WHERE page_id = p.id
                            AND tag_id = t$i.id)
                    AND ? = t$i.name
                    WHERE t$i.id IS NULL
            ";
            ++$i;
        }

        // this query is terrifying (when you add the $filterClauses part)
        $stmt = $this->dbh->prepare("
            SELECT
                p.*,
                GROUP_CONCAT(pt.tag_id) AS tag_ids,
                GROUP_CONCAT(
                    (SELECT name FROM Tags t WHERE t.id IN (pt.tag_id))
                ) AS tag_names,
                GROUP_CONCAT(
                    (SELECT IFNULL(parent_id, 'NULL') FROM Tags t WHERE t.id IN (pt.tag_id))
                ) AS parent_ids
            FROM Pages p
            INNER JOIN PageTags pt
                ON pt.page_id = p.id
            $filterClauses
            GROUP BY p.id
        ");

        // execute without args if no $tags or $excludeTags
        if ($filterClauses === '') $stmt->execute();
        else $stmt->execute(array_merge($tags, $excludeTags));

        $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fetched as &$page) {
            $page['tags'] = array();
            $page['tag_names'] = explode(',', $page['tag_names']);
            $page['tag_ids'] = explode(',', $page['tag_ids']);
            $page['parent_ids'] = explode(',', $page['parent_ids']);
            for ($i = 0; $i < count($page['tag_ids']); ++$i) {
                // an ugly hack:
                if ($page['parent_ids'][$i] === 'NULL') $page['parent_ids'][$i] = NULL;

                array_push($page['tags'], array(
                    'name' => $page['tag_names'][$i],
                    'id' => $page['tag_ids'][$i],
                    'parent_id' => $page['parent_ids'][$i]
                ));
            }
        }

        return $fetched;
    }

    /**
     * Set the data of a page by its id.
     * @param id The id of the page you are setting the data of.
     * @param data The data you wish to set this page to have.
     */
    function setPageData($id, $data) {
        $stmt = $this->dbh->prepare("UPDATE Pages SET data = ? WHERE id = ?");
        $stmt->execute(array($data, $id));
    }

    /**
     * Add a tag.
     * @param name The name of the tag you are adding.
     * @param parent Which tag should be this tag's parent; optional.
     * @return An error message as a string if there was bad input; NULL otherwise.
     */
    function addTag($name, $parentId = NULL) {
        // sanity check
        if (!$name) return "You can't create an empty tag!";

        // no duplicate tags
        $stmt = $this->dbh->prepare("SELECT COUNT(*) FROM Tags WHERE name = ?");
        $stmt->execute(array($name));
        $fetched = $stmt->fetch();
        if ($fetched[0] > 0) return "That tag already exists!";

        $stmt = $this->dbh->prepare("INSERT INTO Tags (name, parent_id) VALUES " .
            "(?, ?)");
        $stmt->execute(array($name, $parentId));
    }

    /**
     * Delete a tag by its id.
     * @param id The id of the tag to delete.
     * @return An error message as a string if there was bad input; NULL otherwise.
     */
    function delTag($id) {
        if ($id == 1) return "You can't delete the \"untagged\" tag!";

        // first we need to fix parent references; have children of this tag
        // have new parents of whatever this tag used to have
        $stmt = $this->dbh->prepare("SELECT @pid := parent_id FROM Tags WHERE id = ?;
            UPDATE Tags SET parent_id = @pid WHERE parent_id = ?");
        $stmt->execute(array($id));

        // delete the actual tag
        $stmt = $this->dbh->prepare("DELETE FROM Tags WHERE id = ?");
        $stmt->execute(array($id));

        // delete the references to that tag in the page <=> tags table
        $stmt = $this->dbh->prepare("DELETE FROM PageTags WHERE tag_id = ?");
        $stmt->execute(array($id));

        // retag anything that now has no tags with [untagged]
        $stmt = $this->dbh->prepare("INSERT INTO PageTags (page_id, tag_id) " .
            "SELECT id, 1 FROM Pages " .
            "LEFT OUTER JOIN PageTags ON page_id = id WHERE page_id IS NULL");
        $stmt->execute();
    }

    /**
     * Get all tags.
     * @return An array of associative arrays which contain keys name, id,
     *     and parent_id.
     */
    function getTags() {
        $stmt = $this->dbh->prepare("SELECT t.* FROM Tags t");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add a tag to a page.
     * @param pageid The id of the page to add the tag to.
     * @param tagid The id of the tag to add to the page.
     * @return An error message as a string if there was bad input; NULL otherwise.
     */
    function addPageTag($pageid, $tagid) {
        if (!$tagid) return "That tag doesn't exist!";

        // avoid duplicate db entries (and/or confusing the user)
        $dupstmt = $this->dbh->prepare("SELECT COUNT(*) FROM PageTags WHERE page_id = ?
            AND tag_id = ?");
        $dupstmt->execute(array($pageid, $tagid));
        $fetched = $dupstmt->fetch();
        if ($fetched[0] > 0) return "This page already has that tag!";

        $putstmt = $this->dbh->prepare("INSERT INTO PageTags (page_id, tag_id) VALUES (?, ?)");
        $putstmt->execute(array($pageid, $tagid));

        // is there a parent (are there parents) to also add?
        while (TRUE) {
            $stmt = $this->dbh->prepare("SELECT parent_id FROM Tags WHERE id = ?");
            $stmt->execute(array($tagid));
            $fetched = $stmt->fetch();
            if (!$fetched[0]) break;
            $tagid = $fetched[0];

            $dupstmt->execute(array($pageid, $tagid));
            $fetched = $dupstmt->fetch();
            if ($fetched[0] == 0) $putstmt->execute(array($pageid, $tagid));
        }
    }

    /**
     * Delete a tag from a page.
     * @param pageid The id of the page to remove the tag from.
     * @param tagid The id of the tag to remove from the page.
     * @return An error message as a string if there was bad input; NULL otherwise.
     */
    function delPageTag($pageid, $tagid) {
        // don't let user delete last tag
        $laststmt = $this->dbh->prepare("SELECT COUNT(*) FROM PageTags WHERE page_id = ?");
        $laststmt->execute(array($pageid));
        $fetched = $laststmt->fetch();
        if ($fetched[0] <= 1) return "Pages must have at least one tag!";

        $delstmt = $this->dbh->prepare("DELETE FROM PageTags WHERE page_id = ? AND tag_id = ?");
        $delstmt->execute(array($pageid, $tagid));

        // any child tags to delete?
        while (TRUE) {
            $stmt = $this->dbh->prepare("SELECT id FROM Tags WHERE parent_id = ?");
            $stmt->execute(array($tagid));
            $fetched = $stmt->fetch();
            if (!$fetched[0]) break;
            $tagid = $fetched[0];

            $laststmt->execute(array($pageid, $tagid));
            $fetched = $laststmt->fetch();
            if ($fetched[0] == 0) $delstmt->execute(array($pageid, $tagid));
        }
    }

    /**
     * Get a tag id from its name.
     * @param name The name of the tag to get the id of.
     * @return The id of the tag.
     */
    function tagIdFromName($name) {
        $stmt = $this->dbh->prepare("SELECT id FROM Tags WHERE name = ?");
        $stmt->execute(array($name));
        $fetched = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fetched['id'];
    }

    /**
     * Create a hierarchy of tags and their children from associative arrays.
     * @param tags An array of associative arrays with keys id, name, and parent_id.
     * @return An array of associative arrays with keys id, name, parent_id, and
     *     children, which is also an array of associative arrays with the same keys.
     */
    function buildTagHierarchy($tags, $callback = NULL, $parentId = NULL) {
        // just use identity function if no callback provided
        if ($callback === NULL) $callback = function($tag) { return $tag; };

        $hier = array();
        foreach ($tags as $tag) {
            if ($tag['parent_id'] == $parentId) {
                $branch = $this->buildTagHierarchy($tags, $callback, $tag['id']);
                $tag['children'] = $branch;
                array_push($hier, $callback($tag));
            }
        }
        return $hier;
    }
}
