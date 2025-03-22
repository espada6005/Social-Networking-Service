<?php
$css_file = "login";
include("../src/Views/layout/header.php");
?>

<div class="login-container">
    <h1>ログイン</h1>
    <form id="loginForm">
        <input type="text" id="username" name="username" placeholder="ユーザー名">
        <input type="password" id="password" name="password" placeholder="パスワード">
        <button type="submit">ログイン</button>
    </form>
</div>