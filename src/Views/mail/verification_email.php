<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>メールアドレス検証</title>
</head>
<body>
    <p><?php echo htmlspecialchars($toName) . " さま"; ?></p>
    <p>ご登録いただきありがとうございます。</p>
    <p>アカウント保護のため下のURLをクリックして、メールアドレスを確認してください。</p>
    <p><a href="<?php echo htmlspecialchars($signedURL); ?>" style="color: #1a73e8;"><?php echo htmlspecialchars($signedURL); ?></a></p>
    <p>このURLの有効期間は30分です。</p>
    <p>このメールに心当たりがない場合は、お手数ですがこのメールを削除してください。</p>
</body>
</html>