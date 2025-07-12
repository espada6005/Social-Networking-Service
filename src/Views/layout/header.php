<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.9.4/dist/css/tempus-dominus.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php
    $current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($current_page === "") {
        echo "<link rel='stylesheet' href='/css/top.css'>";
    } 
    
    ?>
    <script src="/js/common.js"></script>
    <?php if ($user !== null): ?>
        <link rel="stylesheet" href="/css/sidebar.css">
        <script src="/js/post_modal.js"></script>
        <script src="/js/reply_modal.js"></script>
    <?php endif; ?>
    <title>SNS</title>

</head>

<body>
    <?php if ($user !== null): ?>
        <div class="container-fluid">
        <div class="row flex-nowrap">
    <?php endif; ?>