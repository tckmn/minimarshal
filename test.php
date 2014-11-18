<!DOCTYPE html>
<?php require_once('minimarshal.php'); ?>
<html lang='en'>
    <head>
        <title>MiniMarshal example</title>
        <style>
        .err { background-color: #FEE; /*FI*/ color: #F00; /*FUM*/
            padding: 20px; border-radius: 20px; position: fixed;
            width: 90%; }
        .page { margin: 10px; padding-bottom: 15px;
            border-bottom: 1px dotted black; }
        .date { color: #777; font-size: 12px; }
        .data { margin: 15px; }
        .tags span { padding: 5px; margin: 0px 2px; border-radius: 5px;
            background-color: #CCF; }
        .tags span button { background-color: #F00; padding: 0px 2px;
            color: #FFF; border-radius: 10px; }
        #tag-listing { margin: 30px 0px 10px; }
        .delpage { margin-top: 15px; font-size: 10px; background-color: #FDD; }
        </style>
        <meta name='viewport' content='width=device-width' />
    </head>
    <body>
        <?php
        // to prevent abuse (long story)
        if (md5($_GET['x']) !== '1f6b3872491e0d2ecb0369792627e73f') {
            echo "<div class='err'>Not authenticated. No requests will be
                processed.</div>";
        } else {

        if (isset($_POST['addpage'])) {
            $err = addPage($_POST['url'], $_POST['data']);
        } else if (isset($_POST['delpage'])) {
            $err = delPage($_POST['delpage']);
        } else if (isset($_POST['addtag'])) {
            $err = addTag($_POST['tag']);
        } else if (isset($_POST['deltag'])) {
            $err = delTag($_POST['deltag']);
        } else if (isset($_POST['addpagetag'])) {
            $err = addPageTag($_POST['addpagetag'], tagIdFromName($_POST['pagetag']));
        } else if (isset($_POST['delpagetag'])) {
            list($pageid, $tagid) = explode('-', $_POST['delpagetag']);
            $err = delPageTag($pageid, $tagid);
        }

        if ($err) {
            $err = htmlspecialchars($err);
            // TODO add hash to below URL via JavaScript
            echo "<div class='err'>$err
                <a href='$_SERVER[REQUEST_URI]'>OK</a></div>";
        }

        }
        ?>
        <h1>My pages</h1>
        <?php
            foreach (getPages() as $page) {
                $url = htmlspecialchars($page['url'], ENT_QUOTES);
                $data = htmlspecialchars($page['data']);
                $tags = array_combine(
                    array_map('htmlspecialchars', explode(',', $page['tag_names'])),
                    explode(',', $page['tag_ids'])
                );
                $id = $page['id'];
                // TODO also include a permalink based on ID maybe?
                echo "
                <form class='page' method='post' action='#p$id' id='p$id'>
                    <h2 class='url'><a href='$url'>$url</a>
                        <span class='date'>- $page[date]</span></h2>
                    <div class='data'>$data</div>
                    <div class='tags'>
                        <input name='pagetag' type='text' />
                        <button name='addpagetag' type='submit'
                            value='$id'>Add tag</button>
                        <div>&nbsp;</div>";
                    foreach ($tags as $tagname => $tagid) {
                        echo "
                        <span class='tag'>$tagname <button name='delpagetag'
                            value='$id-$tagid'>&times;</button></span>";
                    }
                echo "
                    </div>
                    <button class='delpage' name='delpage'
                        value='$id'>delete this page</button>
                </form>";
            }
        ?>
        <form method='post' action='#cp' id='cp'>
            <label for='url'>URL</label> <input name='url' type='text' /><br>
            <label for='data'>Data</label><br>
            <textarea name='data'></textarea><br>
            <input name='addpage' type='submit' value='Create a new page' />
        </form>
        <form method='post' action='#tag-listing' id='tag-listing' class='tags'>
            Tags: <?php
                foreach (getTags() as $tag) {
                    $name = htmlspecialchars($tag['name']);
                    echo "<span class='tag'>$name <button name='deltag'
                        value='$tag[id]'>&times;</button></span>";
                }
            ?>
        </form>
        <form method='post' action='#ct' id='ct'>
            <label for='tag'>Name</label> <input name='tag' type='text' /><br>
            <input name='addtag' type='submit' value='Create a new tag' />
        </form>
    </body>
</html>
