<?php


if (file_exists("new_wp_content.txt") && trim(file_get_contents("new_wp_content.txt")) != "1") {
    // no new changes were made, so no need to update
    exit();
}

$url = "http://blog.datawrapper.de/?post_type=datawrapper&custom_fields=dw_url,dw_lang&order=ASC&order_by=menu_order&json=1";

$data = json_decode(file_get_contents($url));

$autogen_note = "\tThis file was automatically generated by update_content_from_datastory.php.\n\tAny changes that you make will be overwritten on next run.";
$autogen_php = "\n/*\n" . $autogen_note . "\n*/\n";
$autogen_twig = "\n{#\n" . $autogen_note . "\n#}\n";

$header = "{% extends \"docs.twig\" %}\n{% block docscontent %}\n".$autogen_twig."\n";
$footer = "\n{% endblock %}";

$pages = array();

foreach ($data->posts as $post) {
    if ($post->status == "publish") {
        $lang = $post->custom_fields->dw_lang[0];
        $url = $post->custom_fields->dw_url[0];

        if (count($post->categories) == 0) {
            print "ignoring ".$post->title." (no category)\n";
            continue;
        }
        $category = $post->categories[0]->slug == "dw" && count($post->categories) > 1 ? $post->categories[1] : $post->categories[0];

        if ($category->slug == "docs") $url = "docs/" . $url;
        else if ($category->slug == "popups") $url = "popups/" . $url;

        $tpl = $header . "\n<article> <!-- begin wordpress content -->\n\n" . $post->content . "\n\n</article> <!-- end wordpress content -->\n" . $footer;
        $tpl_dir = "../templates/imported/" . $lang;
        $tpl_file = $tpl_dir . "/" . str_replace('/', '-', $url) . ".twig";
        if (!file_exists($tpl_dir)) mkdir($tpl_dir);
        file_put_contents($tpl_file, $tpl);

        $show_page = $category->slug == "docs";

        if (!isset($pages[$lang])) $pages[$lang] = array();
        $pages[$lang][$url] = array(
            'title' => $post->title,
            'show' => $show_page
        );
    }
}

file_put_contents("../templates/imported/pages.inc.php", "<?php\n$autogen_php\n\$docs_pages = " . var_export($pages, true) . ";\n");

if (file_exists("new_wp_content.txt")) {
    file_put_contents("new_wp_content.txt", "0");
}