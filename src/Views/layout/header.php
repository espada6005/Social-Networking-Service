<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <?php
    $current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($current_page === "") {
        echo "<link rel='stylesheet' href='/css/top.css'>";
    } else {
        echo "<link rel='stylesheet' href='/css/{$current_page}.css'>";
    }
    ?>
    <?php if ($user !== null): ?>
        <link rel="stylesheet" href="/css/sidebar.css">
    <?php endif; ?>
    <title>SNS</title>
</head>

<body>
    <?php if ($user !== null): ?>
        <div class="container-fluid">
        <div class="row flex-nowrap">
    <?php endif; ?>