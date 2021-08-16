<?php
require_once "../includes/config.php";
// random 4 feeds
$feed_query = "select id,followers_id,word_id,news_feed_types_id,user_id,translation_id,comment_id from news_feed where common_newsfeed='yes' order by rand() limit 4";
$result     = mysqli_query($link, $feed_query);
if (mysqli_num_rows($result) > 0) {
    $rows = [];
    $i    = 0;
    while ($feed = mysqli_fetch_assoc($result)) {
        // $rows[$i]['newsfeed'] = $feed;
        $feed_word_id = $feed['word_id'];
        $query        = "select distinct wtt.*,users.resize_photo,users.id as userid,users.photo,users.fname,users.lname,users.language from word_table_translate wtt left join users on users.id = wtt.user_id
                    where wtt.id='$feed_word_id' order by wtt.id desc";
        // get words
        $exe_query = mysqli_query($link, $query);
        while ($profilePost = mysqli_fetch_assoc($exe_query)) {
            // $rows[$i]['wtt']      = $profilePost;
            $word_id              = $profilePost['id'];
            $rows[$i]['word']     = $profilePost['word'];
            $rows[$i]['asked_by'] = $profilePost['user_name'];
            $word_user_id         = $profilePost['user_id'];
            $is_user_logged_in    = $profilePost['user_logged_in'];
            if ($profilePost['word_image'] != '') {
                $rows[$i]['feed_image'] = SITEROOT . "/images/medium/" . 'medium_' . $profilePost['word_image'];
            }

            $rows[$i]['translate_to'] = get_lang_name($link, $profilePost['lang_id']);

            // get translations

            $trans_query_new                = mysqli_query($link, "select wt.translation,wt.id,wt.word_id,wt.user_id,wt.language,wt.image,u.usname from word_translation wt inner join users u on wt.user_id = u.id where word_id='$word_id' order by is_first desc limit 1");
            $total_translations             = mysqli_num_rows($trans_query_new);
            $rows[$i]['total_translations'] = $total_translations;
            if ($total_translations < 1 && $profilePost['have_translation'] == 0) {
                // $row[$i]['translate_to'] = ($profilePost['lang_id'] == 125) ? 'Any Language' : 'English';
            } else {
                while ($translation = mysqli_fetch_assoc($trans_query_new)) {
                    $rows[$i]['translation']          = $translation['translation'];
                    $rows[$i]['translation_image']    = SITEROOT . "/images/medium/" . 'medium_' . $translation['image'];
                    $rows[$i]['translator']           = $translation['usname'];
                    $rows[$i]['translation_language'] = get_lang_name($link, $translation['language']);
                }
            }

        }
        $i++;

    }
    echo json_encode($rows);
} else {
    echo "No newsfeed found..";
}

function get_lang_name($link, $id)
{
    // Selct translate to lang name -start
    $lang_id         = $id;
    $parent_langNAme = '';
    $sub_langNAme    = '';
    if ($lang_id == 0 || $lang_id == 1) {
        // select lang name by word_table_translate.lang_id
        $parent_langNAme = "Any language";
    } else {
        $lang_query      = mysqli_query($link, "select parent_id,name from language_cat where id='$lang_id' ");
        $tempLangRow     = mysqli_fetch_assoc($lang_query);
        $parent_langNAme = $tempLangRow['name'];

        if ($tempLangRow['parent_id'] != 0) {

            $parent_langid = $tempLangRow['parent_id'];
            // select parent lang name if above selected name is a sub lang
            $temp_sublang    = mysqli_query($link, "select name from language_cat where id='$parent_langid' ");
            $rowTemp_subLang = mysqli_fetch_assoc($temp_sublang);
            $sub_langNAme    = $parent_langNAme;
            $parent_langNAme = $rowTemp_subLang['name'];
        }
    }
    return $parent_langNAme;
    // Selct translate to lang name -end
}
