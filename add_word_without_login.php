<?php
require_once "../includes/config.php";
require_once "../includes/functions.php";

$email_notification = 'n';
if (isset($_POST['email_notification']) && $_POST['email_notification'] != '') {
    $email_notification = $_POST['email_notification'];
}

/*Word Image start */
$word_image = '';
if (!empty($_FILES['imgInp_word'])) {

    $image       = $_FILES['imgInp_word'];
    $allowedExts = array("gif", "jpeg", "jpg", "png", "GIF", "JPEG", "JPG", "PNG");

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    //create directory if not exists
    if (!file_exists('../images')) {
        mkdir('../images', 0777, true);
    }
    $image_name = $image['name'];
    //get image extension
    $ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
    //assign unique name to image
    $name = time() . '.' . $ext;
    //$name = $image_name;
    //image size calcuation in KB
    $image_size = $image["size"] / 1024;
    $image_flag = true;
    //max image size
    //$max_size = 512;
    $max_size = 2048;

    if (in_array($ext, $allowedExts) && $image_size < $max_size) {
        $image_flag = true;
    } else {
        $image_flag    = false;
        $data['error'] = 'Maybe ' . $image_name . ' exceeds max ' . $max_size . ' KB size or incorrect file extension';
    }

    if ($image["error"] > 0) {
        $image_flag    = false;
        $data['error'] = '';
        $data['error'] .= '<br/> ' . $image_name . ' Image contains error - Error Code : ' . $image["error"];
        $image = '';
    }

    if ($image_flag) {
        move_uploaded_file($image["tmp_name"], "../images/original/" . $name);
        $src             = "../images/original/" . $name;
        $dist            = "../images/thumbnail/thumbnail_" . $name;
        $data['success'] = $thumbnail = 'thumbnail_' . $name;
        thumbnail($src, $dist, 382, 75, 75);

        $dist1 = "../images/medium/medium_" . $name;
        thumbnail($src, $dist1, 382, 382, 215);

        $dist2 = "../images/large/large_" . $name;
        thumbnail($src, $dist2, 382, 660, 363);

        $word_image = addslashes($name);

        /*$sql="INSERT INTO images (`id`, `original_image`, `thumbnail_image`, `ip_address`) VALUES (NULL, '$name', '$thumbnail', '$ip');";
    if (!mysqli_query($link,$sql)) {
    die('Error: ' . mysqli_error($link));
    } */

    }

}
/*Word image end */

$word = addslashes(html_entity_decode($_POST['words']));
$word = removeEvilTags($word);

$word = trim($word);

if ($word == '') {
    echo 'word is required';
    mysqli_close($link);
    return 1;
}

$word = substr($word, 0, 500);

$word_slug = hyphenize($word);
$word_slug = strip_tags($word_slug);

$word_slug = slugify($word_slug);

$word_slug = substr($word_slug, 0, 200);

$temp_name = $word_slug;
$loop_name = $temp_name;
$i         = 0;
do {

    $exists = checkWordSlug($temp_name);
    if ($exists) {
        $i++;
        $temp_name = $loop_name . $i;
    }
} while ($exists);

$new_word_slug = $temp_name;
$new_word_slug = clean($new_word_slug);

$user_name  = addslashes($_POST['name']);
$user_email = addslashes($_POST['email']);

$country_code = addslashes($_POST['country_code']);

$langauge_id = addslashes($_POST['language']);
$todays_date = strtotime(date('Y-m-d'));

$dom = new domDocument;
$dom->loadHTML($word);
$dom->preserveWhiteSpace = false;
$imgs                    = $dom->getElementsByTagName("iframe");
$links                   = array();
for ($i = 0; $i < $imgs->length; $i++) {
    $links[] = $imgs->item($i)->getAttribute("src");
}

$word = preg_replace("/<iframe[^>]+\>/i", "", $word);
$word = str_replace('</iframe>', '', $word);

$youtube_video = '';
if (isset($links['0'])) {

    $youtube_video = $links['0'];
}
$word = trim($word);
if ($word == '') {

    echo 'empty_with_youtube';

    mysqli_close($link);

    return false;

}

$id_add = $_SERVER['REMOTE_ADDR'];

//         if( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) )
//         {
$query = mysqli_query($link, "insert into word_table_translate(ip_address,youtube_video,word_image,phone,country_code,added_date_var,slug,lang_id,word,user_name,email_id,email_notification,user_logged_in,added_date)
		values('$id_add','$youtube_video','$word_image','$user_email','$country_code','$todays_date','$new_word_slug','$langauge_id','$word','$user_name','$user_email','$email_notification','n',now())");
//         }
$word_id = mysqli_insert_id($link);

$insert_news_feed = mysqli_query($link, "insert into news_feed(news_feed_types_id,word_id,new_feed_text1,added_date,common_newsfeed)values('1','$word_id','Added New Word',now(),'yes')");

$admin_subject = "$user_name has submitted a post for translation - NigerianDictionary.com";

$email_text = file_get_contents('../email_notification.html');

$temp_q         = mysqli_query($link, "select slug from word_table_translate where id='$word_id'");
$temp_row       = mysqli_fetch_assoc($temp_q);
$temp_word_slug = $temp_row['slug'];

$word_url = SITEROOT . "/" . $temp_word_slug;

$message = "Hello Admin,
						    		<br><br><b>$user_name</b> has submitted a post for translation.
                                                                <br> To view the post, click here " . "<a href='$word_url'>" . $word_url . "</a><br><br>";

$message .= "<b>Post</b>: " . $word . "<br><br>";

$message .= "<b><i>Add notify@nigeriandictionary.com to your contacts to ensure that you receive your NigerianDictionary.com e-mail.</i></b>";

$image_src  = "<img src='" . SITEROOT . "/img/logo.PNG'>";
$email_text = str_replace("[[imagelink]]", $image_src, $email_text);

$email_text = str_replace("[[subject]]", $admin_subject, $email_text);
$email_text = str_replace("[[message]]", $message, $email_text);
$email_text = str_replace("[[sitetitle]]", "<i><b>Nigerian Dictionary</b></i>", $email_text);

$headers = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= "From: Nigerian Dictionary <notify@nigeriandictionary.com>" . "\r\n";

/*mail("rajendramjadhav@gmail.com", $admin_subject , $email_text, $headers);  */
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) {
    mail("admin@nigeriandictionary.com", $admin_subject, $email_text, $headers);
}
/*echo $word_id;
 */

?>
		<?php

$word_query   = mysqli_query($link, "select * from word_table_translate where id='$word_id'");
$word_data    = mysqli_fetch_assoc($word_query);
$word_lang_id = $word_data['lang_id'];

$lang_id      = $word_lang_id;
$word_lang    = '';
$sub_langNAme = '';
if ($lang_id != 0) {
    $lang_query  = mysqli_query($link, "select parent_id,name from language_cat where id='$lang_id' ");
    $tempLangRow = mysqli_fetch_assoc($lang_query);
    $word_lang   = $tempLangRow['name'];

    if ($tempLangRow['parent_id'] != 0) {

        $parent_langid   = $tempLangRow['parent_id'];
        $temp_sublang    = mysqli_query($link, "select name from language_cat where id='$parent_langid' ");
        $rowTemp_subLang = mysqli_fetch_assoc($temp_sublang);
        $sub_langNAme    = $word_lang;
        $word_lang       = $rowTemp_subLang['name'];
    }
}

$temp_word = str_replace('"', "", $word_data['word']);
$temp_word = str_replace("'", "", $temp_word);

$share_url = SITEROOT . '/share-word.php?wordid=' . $word_id;
$share_url = urlencode($share_url);

$res['fbshare']  = "https://www.facebook.com/sharer/sharer.php?u=$share_url&amp;src=sdkpreparse', 'newwindow', 'width=400, height=250,left=300,top=200";
$res['twtshare'] = "https://twitter.com/intent/tweet?url=$share_url, 'newwindow', 'width=400, height=250,left=300,top=200";
$res['msg']      = "Word has been submitted successfully";

echo json_encode($res);

mysqli_close($link);

function thumbnail($src, $dist, $dis_width = 100, $maxwidth, $maxheight)
{

    $img       = '';
    $extension = strtolower(strrchr($src, '.'));
    switch ($extension) {
        case '.jpg':
        case '.jpeg':
            $img = @imagecreatefromjpeg($src);
            break;
        case '.gif':
            $img = @imagecreatefromgif($src);
            break;
        case '.png':
            $img = @imagecreatefrompng($src);
            break;
    }

    //$maxwidth = 660;
    //$maxheight = 363;

    //$maxwidth = 382;
    //$maxheight = 215;

    $width  = imagesx($img);
    $height = imagesy($img);

    if ($width > $maxwidth || $height > $maxheight) {
        if ($height > $width) {
            $ratio      = $maxheight / $height;
            $dis_height = $maxheight;
            $dis_width  = $width * $ratio;
        } else {
            $ratio      = $maxwidth / $width;
            $dis_width  = $maxwidth;
            $dis_height = $height * $ratio;
        }
    } else {
        $dis_height = $height;
        $dis_width  = $height;

    }

    //$dis_height = $dis_width * ($height / $width);
    //$dis_height=215;

    $new_image = imagecreatetruecolor($dis_width, $dis_height);
    imagecopyresampled($new_image, $img, 0, 0, 0, 0, $dis_width, $dis_height, $width, $height);

    $imageQuality = 100;

    switch ($extension) {
        case '.jpg':
        case '.jpeg':
            if (imagetypes() & IMG_JPG) {
                imagejpeg($new_image, $dist, $imageQuality);
            }
            break;

        case '.gif':
            if (imagetypes() & IMG_GIF) {
                imagegif($new_image, $dist);
            }
            break;

        case '.png':
            $scaleQuality       = round(($imageQuality / 100) * 9);
            $invertScaleQuality = 9 - $scaleQuality;

            if (imagetypes() & IMG_PNG) {
                imagepng($new_image, $dist, $invertScaleQuality);
            }
            break;
    }
    imagedestroy($new_image);
}

?>