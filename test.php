<!DOCTYPE html>
<?php require_once('minimarshal.php'); ?>
<?php
if (isset($_POST['create'])) {
    addPage($_POST['url'], $_POST['data']);
} else if (isset($_POST['createtag'])) {
    addTag($_POST['tag']);
} else if (isset($_POST['deltag'])) {
    list($pageid, $tagid) = explode('-', $_POST['deltag']);
    delPageTag($pageid, $tagid);
} else if (isset($_POST['addpagetag'])) {
    addPageTag($_POST['addpagetag'], tagIdFromName($_POST['pagetag']));
}
?>
<html lang='en'>
    <head>
        <title>MiniMarshal example</title>
        <style>
        .page { margin: 10px; padding-bottom: 15px;
            border-bottom: 1px dotted black; }
        .data { margin: 15px; }
        .tags span { padding: 5px; margin: 0px 2px; border-radius: 5px;
            background-color: #CCF; }
        .tags span button { background-color: #F00; padding: 0px 2px;
            color: #FFF; border-radius: 10px; }
        #tag-listing { margin: 30px 0px 10px; }
        </style>
    </head>
    <body>
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
                echo "
                <form class='page' method='post'>
                    <h2 class='url'><a href='$url'>$url</a></h2>
                    <div class='data'>$data</div>
                    <div class='tags'>
                        <input name='pagetag' type='text' />
                        <button name='addpagetag' type='submit'
                            value='$id'>Add tag</button>
                        <div>&nbsp;</div>";
                    foreach ($tags as $tagname => $tagid) {
                        echo "
                        <span class='tag'>$tagname <button name='deltag'
                            value='$id-$tagid'>&times;</button></span>";
                    }
                echo "
                    </div>
                </form>";
            }
        ?>
        <form method='post'>
            <label for='url'>URL</label> <input name='url' type='text' /><br>
            <label for='data'>Data</label><br>
            <textarea name='data'></textarea><br>
            <input name='create' type='submit' value='Create a new page' />
        </form>
        <div id='tag-listing' class='tags'>
            Tags: <?php
                foreach (getTags() as $tag) {
                    echo "<span class='tag'>$tag[name]</span>";
                }
            ?>
        </div>
        <form method='post'>
            <label for='tag'>Name</label> <input name='tag' type='text' /><br>
            <input name='createtag' type='submit' value='Create a new tag' />
        </form>
    </body>
</html>
