<?php

namespace Helpers;

use PHPMailer\PHPMailer\PHPMailer;

class MailSender {

    public static function sendVerificationEmail(string $signedURL, string $toAddress, string $toName): bool {
        $mail = new PHPMailer(true);

        try {
            // サーバーの設定
            $mail->isSMTP();
            $mail->Host = Settings::env("SMTP_HOST");
            $mail->SMTPAuth = true;
            $mail->Username = Settings::env("SMTP_USER");
            $mail->Password = Settings::env("SMTP_PASSWORD");
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port = 587;
            $mail->CharSet = "UTF-8";

            // 送信者を設定
            $mail->setFrom(Settings::env("FROM_ADDRESS"), Settings::env("FROM_NAME"));

            // 受信者を設定
            $mail->addAddress($toAddress, $toName);

            $mail->Subject = "メールアドレスの確認";

            $mail->isHTML(true);
            
            ob_start();
            extract([
                "signedURL" => $signedURL,
                "toName" => $toName,
            ]);
            include("../src/Views/mail/verification_email.php");
            $mail->Body = ob_get_clean();

            $textBody = file_get_contents("../src/Views/mail/verification_email.txt");
            $textBody = str_replace("[signedURL]", $signedURL, $textBody);
            $textBody = str_replace("[toName]", $toName, $textBody);
            $mail->AltBody = $textBody;
            
            $mail->send();

            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public static function sendPasswordResetEmail(string $signedURL, string $toAddress, string $toName): bool {
        $mail = new PHPMailer(true);

        try {
            // サーバーの設定
            $mail->isSMTP();
            $mail->Host = Settings::env("SMTP_HOST");
            $mail->SMTPAuth = true;
            $mail->Username = Settings::env("SMTP_USER");
            $mail->Password = Settings::env("SMTP_PASSWORD");
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port = 587;
            $mail->CharSet = "UTF-8";
            $mail->isHTML(true);

            // 送信者を設定
            $mail->setFrom(Settings::env("FROM_ADDRESS"), Settings::env("FROM_NAME"));

            // 受信者を設定
            $mail->addAddress($toAddress, $toName);

            ob_start();
            extract([
                "signedURL" => $signedURL,
                "toName" => $toName,
            ]);
            include("../src/Views/mail/password_reset.php");
            $mail->Body = ob_get_clean();

            $textBody = file_get_contents("../src/Views/mail/password_reset.txt");
            $textBody = str_replace("[signedURL]", $signedURL, $textBody);
            $textBody = str_replace("[toName]", $toName, $textBody);
            $mail->AltBody = $textBody;
            
            $mail->send();

            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

}