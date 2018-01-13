<?php
function betheme_content_replace($conn, $search, $replace) {
    $result1 = mysqli_query($conn, "SELECT * FROM wp_postmeta WHERE meta_key = 'mfn-page-items'");

    while($row = mysqli_fetch_assoc($result1)) {
        $base64 = base64_decode($row['meta_value']);
        replace($conn, $row['meta_id'], $base64, $search, $replace);
    }
}

function removeWidgets($conn) {
    $sql = "SELECT * FROM `wp_options` WHERE option_name = 'sidebars_widgets'";
    $optionID = null;
    $optionValue = null;
    $rs = mysqli_query($conn, $sql);
    while($row = mysqli_fetch_assoc($rs)) {
        $optionValue = $row['option_value'];
        $optionID = $row['option_id'];
    }

    if($optionID == null || $optionValue == null || empty($optionValue)) return;

    $extract = unserialize($optionValue);
    $search = ['search-2', 'recent-posts-2', 'recent-comments-2', 'archives-2', 'categories-2', 'meta-2'];

    foreach($extract as $key => &$val) {
        if(!is_array($val)) continue;
        array_walk($search, function($s) use (&$val) {
            $index = array_search($s, $val);
            if($index === FALSE) return;
            array_splice($val, $index, 1);
        });
    }

    mysqli_query($conn, "UPDATE wp_options SET option_value = '" . serialize($extract) . "' WHERE option_id = {$optionID}");
}

function replace($conn, $meta_id, $content, $search, $replace) {
    $jsonencode = json_encode(unserialize($content));

    $jsonencode = str_replace($search, $replace, $jsonencode);
    $base64 = base64_encode(serialize(json_decode($jsonencode, true)));

    mysqli_query($conn, "UPDATE wp_postmeta SET meta_value = '{$base64}' WHERE meta_id = {$meta_id}");
}

function replaceSlides($conn, $search, $replace) {
    try {
        $result = mysqli_query($conn, "SELECT * FROM wp_revslider_slides");

        while($row = mysqli_fetch_assoc($result)) {
            $params = $row['params'];
            $params = str_replace($search, $replace, $params);
            mysqli_query($conn, "UPDATE wp_revslider_slides SET params = '{$params}' WHERE id = {$row['id']}");
        }
    } catch (Exception $e) {

    }
}

function checkConfigSite($site) {

}

function readDBInfo($site) {
    $webRootDir = "/var/www";

    //Read Database Info
    $ee_config = file_get_contents("{$webRootDir}/{$site}/ee-config.php");

    //Parse Account
    preg_match("/\'DB_NAME\'\, \'([\w\d_-]+)\'/", $ee_config, $matches);
    $DB_NAME = $matches[1];

    preg_match("/\'DB_USER\'\, \'([\w\d_-]+)\'/", $ee_config, $matches);
    $DB_USER = $matches[1];

    preg_match("/\'DB_PASSWORD\'\, \'([\w\d_-]+)\'/", $ee_config, $matches);
    $DB_PASS = $matches[1];

    return [
        "db_name" => $DB_NAME,
        "db_user" => $DB_USER,
        "db_pass" => $DB_PASS
    ];
}