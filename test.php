<?php require_once('minimarshal.php');
$mm = new MiniMarshal();
$admin = isset($_GET['admin']); ?><?php
// TODO verify tags exist
// TODO fix ugly thing in which both buttons do the same thing
// TODO tag autocomplete
if ($_POST['txtti']) {
    header("Location: ?" . addTagToQstr($_POST['txtti'], 'ti'));
} else if ($_POST['txtte']) {
    header("Location: ?" . addTagToQstr($_POST['txtte'], 'te'));
}
?>
<!DOCTYPE html>
<html lang='en'>
    <head>
        <title>Keyboard Fire page listing</title>
        <style>
        #err { background-color: #FEE; /*FI*/ color: #F00; /*FUM*/
            padding: 20px; border-radius: 20px; position: fixed; }
        #container { max-width: 800px; margin: 0px auto; }
        #filter { border: 1px dotted black; border-radius: 15px; padding: 5px; }
        .page { margin: 10px; padding-bottom: 15px;
            border-bottom: 1px dotted black; }
        .date { color: #777; font-size: 12px; }
        .data { margin: 15px; }
        .tag { color: black; padding: 5px; margin: 0px 2px; border-radius: 5px;
            background-color: #CCF; line-height: 200%; text-decoration: none;  }
        .tag button { background-color: #F00; padding: 0px 2px;
            color: #FFF; border-radius: 10px; }
        .children { position: relative; top: 2px; padding-top: 7px; /* 5px tag padding + 2px extra */
            border-top: 3px solid #F00; border-radius: 20px; }
        .children .children { border-color: #0F0; }
        .children .children .children { border-color: #00F; }
        .children .children .children .children { border-color: #F00; }
        .children .children .children .children .children { border-color: #0F0; }
        .children .children .children .children .children .children { border-color: #00F; }
        .nobr { white-space: nowrap; }
        #tag-listing { margin: 30px 0px 10px; }
        .delpage { margin-top: 15px; font-size: 10px; background-color: #FDD; }
        </style>
        <meta name='viewport' content='width=device-width' />
    </head>
    <body><div id='container'>
        <?php
        function addTagToQstr($tag, $qname, $toggle = FALSE) {
            parse_str($_SERVER['QUERY_STRING'], $qstr);
            if (isset($qstr[$qname])) {
                $qvals = explode('-', $qstr[$qname]);
                $qsearch = array_search($tag, $qvals);

                if ($qsearch !== FALSE) { if ($toggle) unset($qvals[$qsearch]); }
                else $qvals[] = $tag;

                $qstr[$qname] = implode('-', $qvals);

                // if last tag removed, it becomes "ti=" which borks the thing
                if (!$qstr[$qname]) unset($qstr[$qname]);
            } else {
                $qstr[$qname] = $tag;
            }
            return http_build_query($qstr);
        }

        function taghtml($tag, $adminextra = '', $qname = 'ti', $toggle = FALSE) {
            global $admin; // TODO gah ugly global, remove
            $qstr = addTagToQstr($tag, $qname, $toggle);
            if (!$admin) $adminextra = '';
            return "<a href='?$qstr' class='tag'>$tag$adminextra</a>";
        }

        if ($admin) {
            if (!isset($_SESSION['admin'])) {
                if (isset($_POST['adminpass']) && hash('whirlpool',
                    $_POST['adminpass']) == 'bb327808a3caf17b56229e59a0851f1' .
                    '0d3e0b8c97b3b66af4fb3ecb6e657a6839fd0dc73ac9aba12355dbb' .
                    '82dcae4537c2eabc8d9da85ae91a7fe84c5a726cea') {
                    $_SESSION['admin'] = true;
                    echo "<div id='err'>Please click
                        <a href='$_SERVER[REQUEST_URI]'>here</a> to complete
                        authentication.</div>";
                } else {
                    echo "<form method='post' id='err'>Admin password
                        required for admin requests to be processed:
                        <input name='adminpass' type='password' />
                        <input type='submit' value='Go' /></form>";
                }
            } else {
                $editpage = FALSE;
                if (isset($_POST['addpage'])) {
                    $err = $mm->addPage($_POST['url'], $_POST['data']);
                } else if (isset($_POST['delpage'])) {
                    $err = $mm->delPage($_POST['delpage']);
                } else if (isset($_POST['editpage'])) {
                    $editpage = $_POST['editpage'];
                } else if (isset($_POST['saveeditpage'])) {
                    $err = $mm->setPageData($_POST['saveeditpage'],
                        $_POST['editpagedata']);
                } else if (isset($_POST['addtag'])) {
                    $err = $mm->addTag($_POST['tag'], ($_POST['parenttag'] == "" ?
                        NULL : $mm->tagIdFromName($_POST['parenttag'])));
                } else if (isset($_POST['deltag'])) {
                    $err = $mm->delTag($_POST['deltag']);
                } else if (isset($_POST['addpagetag'])) {
                    $err = $mm->addPageTag($_POST['addpagetag'],
                        $mm->tagIdFromName($_POST['pagetag']));
                } else if (isset($_POST['delpagetag'])) {
                    list($pageid, $tagid) = explode('-', $_POST['delpagetag']);
                    $err = $mm->delPageTag($pageid, $tagid);
                } else if (isset($_POST['setup'])) {
                    $err = $mm->setup();
                } else if (isset($_POST['clean'])) {
                    // TODO
                }

                if ($err) {
                    $err = htmlspecialchars($err);
                    echo "<div id='err'>$err <a id='errlink'
                        href='$_SERVER[REQUEST_URI]'>OK</a></div>";
                    // some simple JS to make the OK button just hide the err
                    echo "<script type='text/javascript'>
                        var el = document.getElementById('errlink');
                        el.href = window.location.hash || '#';
                        el.onclick = function(e) {
                            e.preventDefault();
                            document.getElementById('err').style.display = 'none';
                        }
                    </script>";
                }
            }
        }
        ?>
        <div style='float: right; font-size: 12px'><a href='?admin'>admin</a></div>
        <h1>Keyboard Fire page listing</h1>
        <?php
            $ti = isset($_GET['ti']) ? array_map('urldecode', explode('-',
                $_GET['ti'])) : array();
            $te = isset($_GET['te']) ? array_map('urldecode', explode('-',
                $_GET['te'])) : array();
            echo "<p>Hello! This is a listing of all pages
                on the site, along with tags to make them easy to find for you.
                </p><p>You can filter the pages by tags that you want them to
                include or exclude by typing in the text boxes, or by clicking
                any tag in the page listing below. Click any tag you are
                filtering by to remove it.</p>";
            echo "<form id='filter' method='post'><p>Tags to include: ";
            foreach ($ti as $tix) { echo taghtml($tix, '', 'ti', TRUE); }
            echo " <input name='txtti' type='text' />
                <input name='ati' type='submit' value='Add' /></p><p>Tags to exclude: ";
            foreach ($te as $tex) { echo taghtml($tex, '', 'te', TRUE); }
            echo " <input name='txtte' type='text' />
                <input name='ate' type='submit' value='Add' /></p></form>";
            foreach ($mm->getPages($ti, $te) as $page) {
                $url = htmlspecialchars($page['url'], ENT_QUOTES);
                $data = htmlspecialchars($page['data']);
                $tags = $page['tags'];
                $id = $page['id'];
                $datadiv = ($id == $editpage) ? 'textarea' : 'div';
                // TODO also include a permalink based on ID maybe?
                echo "
                <form class='page' method='post' action='#p$id' id='p$id'>
                    <h2 class='url'><a href='$url'>$url</a>
                        <span class='date'>- #$id at $page[date]</span></h2>";
                if ($id == $editpage) echo "
                    <textarea name='editpagedata'>$data</textarea><br/>
                    <button name='saveeditpage' value='$id'>save edits</button>";
                else echo "
                    <div class='data'>$data</div>";
                echo "
                    <div class='tags'>";
                if ($admin) echo "
                        <input name='pagetag' type='text' />
                        <button name='addpagetag' type='submit' value='$id'>
                            Add tag</button>
                        <div>&nbsp;</div>";
                    $hier = $mm->buildTagHierarchy($tags, function($tag) {
                        $tagname = htmlspecialchars($tag['name']);
                        return taghtml($tagname, "<span
                            class='nobr'>&nbsp;<button name='delpagetag'
                            value='$id-$tagid'>&times;</button></span>") .
                            ($tag['children'] ? "<span class='children'>" . 
                            implode($tag['children']) . '</span>' : '');
                    });
                    echo implode($hier);
                echo "
                    </div>";
                if ($admin) echo "
                    <button name='editpage' value='$id'>edit page description</button>
                    <button class='delpage' name='delpage' value='$id'>delete page</button>";
                echo "
                </form>";
            }
        ?>
        <?php if ($admin) { ?>
        <form method='post' action='#cp' id='cp'>
            <label for='url'>URL</label> <input name='url' type='text' /><br>
            <label for='data'>Data</label><br>
            <textarea name='data'></textarea><br>
            <input name='addpage' type='submit' value='Create a new page' />
        </form>
        <?php } ?>
        <form method='post' action='#tag-listing' id='tag-listing' class='tags'>
            Tags: <?php
                $hier = $mm->buildTagHierarchy($mm->getTags(), function($tag) {
                    $tagname = htmlspecialchars($tag['name']);
                    return "<li>" . taghtml($tagname, "<span
                        class='nobr'>&nbsp;<button name='deltag'
                        value='$tag[id]'>&times;</button></span>") .
                        ($tag['children'] ? '<ul>' . implode($tag['children'])
                        . '</ul>' : '') . "</li>";
                });
                echo '<ul>' . implode($hier) . '</ul>';
            ?>
        </form>
        <?php if ($admin) { ?>
        <form method='post' action='#ct' id='ct'>
            <label for='tag'>Name</label> <input name='tag' type='text' /><br>
            <label for='parenttag'>Parent</label> <input name='parenttag' type='text' /><br>
            <input name='addtag' type='submit' value='Create a new tag' />
        </form><br />
        <form method='post' action='#at' id='at'>
            Admin tools:
            <input type='submit' name='setup' value='Setup' />
            <input type='submit' name='clean' value='Clean database' />
        </form>
        <?php } ?>
    </div></body>
</html>
