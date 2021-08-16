<?php

require_once "../includes/config.php";

$user_id = null;
$user    = null;

if (isset($_GET['id'])) {
    $id     = addslashes($_GET['id']);
    $temp_q = mysqli_query($link, "SELECT u.id,u.usname,u.fname,u.lname,u.language, (select count(id) from word_table_translate wtt WHERE wtt.user_id=u.id ) words, (select count(id) from word_translation wt WHERE wt.user_id=u.id) translations, (select count(id) from follow_and_unfollow_activity f where (f.page_owners_emails=u.id OR f.followers_emails=u.id) and f.accepted='1') followers,(select count(id) from favourite where user_id=u.id) favourites from users u WHERE u.id='$id'");
    $user   = mysqli_fetch_assoc($temp_q);
    $lang   = $user['language'];
    if ($lang != '' || $lang != null) {
        $sql           = mysqli_query($link, "select GROUP_CONCAT(l.name SEPARATOR ',') from language_cat l where l.id in($lang)"); //need to improve
        $langs         = mysqli_fetch_assoc($sql);
        $user['langs'] = array_values($langs)[0];
    }
    $user_id = $user['id'];
} else {
    echo "id field is required";
}

if ($user_id) {

    echo json_encode($user);
} else {
    echo "No user found";
}
