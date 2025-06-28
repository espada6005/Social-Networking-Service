<?php

namespace WebSocket;

use Database\DataAccess\DAOFactory;
use Helpers\Settings;
use Helpers\Encryptor;
use Models\Message;
use Models\Notification;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

class Chat implements MessageComponentInterface {

    protected $clients;
    protected $clientIds;

    public function __construct() {
        $this->clients = [];
        $this->clientIds = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $params);
        $fun = $params["fun"] ?? null;
        $tun = $params["tun"] ?? null;
        $token = $params["t"] ?? null;

        $expectedToken = hash_hmac("sha256", $fun . $tun, Settings::env("SECRET_KEY"));

        if (!hash_equals($expectedToken, $token)) {
            error_log("Invalid token for connection {$conn->resourceId}");
            $conn->close();
            return;
        }

        $this->clientIds[$conn->resourceId]["fun"] = $fun;
        $this->clientIds[$conn->resourceId]["tun"] = $tun;
        $this->clients[$fun][$tun][$conn->resourceId] = $conn;
    }

    public function onMessage(ConnectionInterface $conn, $data) {
        // echo sprintf("Connection %d sending message '%s'" . "\n", $conn->resourceId, $data);

        $chatUsers = $this->clientIds[$conn->resourceId];
        $fun = $chatUsers["fun"];
        $tun = $chatUsers["tun"];
        $rawData = json_decode($data, true);

        $rawData["content"] = trim($rawData["content"]);
        if (!(mb_strlen($rawData["content"]) >= 0 && mb_strlen($rawData["content"]) <= 1000)) {
            throw new \Exception("リクエストデータが不適切です。文字数違反です。");
        };

        // メッセージをDBに登録
        $userDao = DAOFactory::getUserDao();
        $messageDao = DAOFactory::getMessageDao();

        $fromUser = $userDao->getByUsername($fun);
        $toUser = $userDao->getByUsername($tun);
        if ($fromUser === null || $toUser === null || $fromUser->getUserId() === $toUser->getUserId()) {
            throw new \Exception("リクエストデータが不適切です。");
        }

        $message = new Message(
            from_user_id: $fromUser->getUserId(),
            to_user_id: $toUser->getUserId(),
            content: Encryptor::encrypt($rawData["content"]),
        );
        $result = $messageDao->create($message);
        if (!$result) {
            throw new \Exception("メッセージの作成に失敗しました。");
        }

        // 送信者のコネクションに送信
        if (isset($this->clients[$fun]) && isset($this->clients[$fun][$tun]) && count($this->clients[$fun][$tun]) > 0) {
            $conns = $this->clients[$fun][$tun];
            $rawData["isMyMessage"] = 1;
            $data = json_encode($rawData);
            foreach ($conns as $sourceId => $conn) {
                $conn->send($data);
            }
        }

        // 受信者のコネクションに送信
        if (isset($this->clients[$tun]) && isset($this->clients[$tun][$fun]) && count($this->clients[$tun][$fun]) > 0) {
            $conns = $this->clients[$tun][$fun];
            $rawData["isMyMessage"] = 0;
            $data = json_encode($rawData);
            foreach ($conns as $sourceId => $conn) {
                $conn->send($data);
            }
        } else {
            // 受信者のコネクションがない場合は通知を作成する
            $notification = new Notification(
                from_user_id: $fromUser->getUserId(),
                to_user_id: $toUser->getUserId(),
                source_id: $message->getMessageId(),
                type: "MESSAGE",
            );
            $notificationDao = DAOFactory::getNotificationDAO();
            $result = $notificationDao->create($notification);
            if (!$result) {
                throw new \Exception("通知作成処理に失敗しました。");
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $chatUsers = $this->clientIds[$conn->resourceId];
        unset($this->clients[$chatUsers["fun"]][$chatUsers["tun"]][$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        error_log("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }

}
