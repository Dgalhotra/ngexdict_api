<?php

require_once "../includes/config.php";

require_once "../includes/functions.php";

$most_followed_member = addslashes($_POST['most_followed_member']);
$most_followed_member = htmlspecialchars($most_followed_member, ENT_QUOTES, 'UTF-8');

// if (isset($_SESSION['user_id'])) {
//     $user_id = $_SESSION['user_id'];
// } else {
//     $user_id = $_SESSION['user_id'];
// }

?>

<?php

if ($most_followed_member == 'most_popular') {

    $user_query = "SELECT count(fua.id) as total_follwer,u.state,u.city,u.country,u.registerd_phone,u.usname,u.fname,u.lname,u.id,u.email,u.resize_photo,u.facebook_id,u.occupation,u.profile_status FROM `follow_and_unfollow_activity` as fua inner join users u on u.id=fua.page_owners_emails where fua.`accepted`='1' group by fua.page_owners_emails order by total_follwer desc limit 8";

} elseif ($most_followed_member == 'most_recent') {

    $user_query = "select * from users order by id desc limit 8";

}

$run_query = mysqli_query($link, $user_query);

$temp_total_rec = mysqli_num_rows($run_query);

if ($temp_total_rec < 1) {

    echo "Record(s) not found";

}
$rows = [];
while ($temp_row = mysqli_fetch_assoc($run_query)) {
    // add image
    if ($temp_row['resize_photo'] != '' && !empty($temp_row['resize_photo'])) {

        $temp_row['img'] = SITEROOT . "/photo/166/" . '166x166_' . $temp_row['resize_photo'];

    } elseif ($temp_row['facebook_id'] != '' && $temp_row['facebook_id'] != 0) {
        $temp_row['img'] = "https://graph.facebook.com/" . $temp_row['facebook_id'] . "/picture?width=200&height=200";
    } else {
        $temp_row['img'] = SITEROOT . "/img/default.png";
    }
    // add username
    if ($temp_row['usname'] != '') {
        $temp_row['usname'] = substr($temp_row['usname'], 0, 25);
        if (strlen($temp_row['usname']) > 25) {
            $temp_row['usname'] .= '...';
        }

    } else {
        $temp_row['usname'] = substr($temp_row['fname'] . ' ' . $temp_row['lname'], 0, 25);
        if (strlen($temp_row['fname'] . ' ' . $temp_row['lname']) > 25) {
            $temp_row['usname'] .= '...';
        }

    }
    $rows[] = $temp_row;
}
print json_encode($rows);

?>


<?php mysqli_close($link);?>



