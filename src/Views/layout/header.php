<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
        $current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if ($current_page === "") {
            echo "<link rel='stylesheet' href='css/home.css'>";
        } else {
            echo "<link rel='stylesheet' href='css/{$current_page}.css'>";
        }
    ?>
    <title>SNS</title>
</head>
<body>