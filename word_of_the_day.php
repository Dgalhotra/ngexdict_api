<?php
require_once "../includes/config.php";

$todays_date = date("m/d/Y");

$today_date_str = strtotime($todays_date);

$query = "select wt.image as trans_image,wtt.lang_id as word_lang_id,wt.language as translation_lang_id,wt.id as trans_id,wt.translation,lc.id as lang_id,lc.name as lang_name,wtt.id as word_id,wtt.word as word,u.id as user_id,u.usname,u.fname,u.lname,u.photo,u.resize_photo,u.facebook_id,wtt.slug as word_slug from word_table_translate wtt
inner join word_of_day wod on wtt.id=wod.word_id
inner join users u on wtt.user_id=u.id
inner join language_cat lc on wtt.lang_id=lc.id
inner join word_translation wt on wtt.id=wt.word_id
where wod.submitted_date='$today_date_str' and wt.translation!='' and wtt.word!='' and wtt.user_logged_in='y' limit 1";

$exe_query = mysqli_query($link, $query);

$total_rows = @mysqli_num_rows($exe_query);

if ($total_rows > 0) {

    $word_of_the_day = mysqli_fetch_assoc($exe_query);
    echo json_encode($word_of_the_day);
} else {
    $sql   = "select wtt.word, wt.translation,wt.user_id,wt.image, (select name from language_cat where language_cat.id=wt.language) as language from word_translation wt inner join word_table_translate wtt on wtt.id=wt.word_id where wt.is_first='0' and wt.translation <> '' order by rand() limit 1";
    $query = mysqli_query($link, $sql);
    if (mysqli_num_rows($query) > 0) {
        $wod = mysqli_fetch_assoc($query);
        if ($wod['image'] != '' || $wod['image'] != null) {
            $wod['image'] = SITEROOT . "/images/medium/" . 'medium_' . $wod['image'];
        }
        echo json_encode($wod);
    } else {
        echo json_encode("No word Found");
    }

}
