<?php
require_once "../includes/config.php";

require_once "../includes/functions.php";
if (isset($_SESSION['user_id'])) {
    $session_id = $_SESSION['user_id'];
} else {
    $session_id = 0;
}
$q = addslashes($_GET['q']);

$exp_q = explode(' ', $q);

$query_str = '';
if (count($exp_q) > 1) {
    $name1     = $exp_q['0'];
    $name2     = $exp_q['1'];
    $query_str = "(usname LIKE '%$q%' OR fname = '$name1' OR lname= '$name2')";
} else {
    $query_str = "(usname LIKE '%$q%' OR fname LIKE '%$q%' OR lname LIKE '%$q%')";
}

$q = str_replace(' ', '', $q);
if (isset($q) && !empty($q)) {
    $query  = "SELECT city,state,country,id, usname, fname, lname,resize_photo,facebook_id FROM users WHERE $query_str limit 20";
    $result = mysqli_query($link, $query);
    $res    = array();
    while ($resultSet = mysqli_fetch_assoc($result)) {
        $img_src = '';

        if ($resultSet['resize_photo'] != '' && !empty($resultSet['resize_photo'])) {
            $img_src = SITEROOT . '/photo/39/' . '39x39_' . $resultSet['resize_photo'];

        } elseif ($resultSet['facebook_id'] != '' && $resultSet['facebook_id'] != 0) {
            $img_src = "https://graph.facebook.com/" . $resultSet['facebook_id'] . "/picture?width=40&height=40";

        } else {
            $img_src = SITEROOT . "/img/default-user.png";
        }

        $address = '';

        $city = $resultSet['city'];
        if ($city != '') {
            $address .= substr($city, 0, 8);
        }

        $state = $resultSet['state'];
        if ($state != '') {
            $address .= ', ' . substr($state, 0, 8);
        }

        $country = $resultSet['country'];
        if ($country != '') {
            $address .= ', ' . substr($country, 0, 8);
        }

        $json = array();

        $json['value']   = $resultSet['usname'];
        $json['label']   = substr($resultSet['fname'], 0, 10) . " " . substr($resultSet['lname'], 0, 10);
        $json['image']   = $img_src;
        $json['address'] = $address;

        $res[] = $json;
    }
    if (!$res) {
        $json['value']   = '';
        $json['label']   = 'Not Found';
        $json['image']   = SITEROOT . "/img/default-user.png";
        $json['address'] = "";
        $res[]           = $json;
    }
    echo json_encode($res);
}
mysqli_close($link);
