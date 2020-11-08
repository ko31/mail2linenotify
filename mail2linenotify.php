<?php
require("vendor/autoload.php");

use ZBateson\MailMimeParser\Message;

// Check parameters.
if (empty($argv[1])) {
    exit('Invalid parameter.');
}
$access_token = $argv[1];

$stdin = file_get_contents("php://stdin");
$message = Message::from($stdin);

// Send line notify
$url = 'https://notify-api.line.me/api/notify';
$headers = [
    'Authorization: Bearer '.$access_token
];
$post_data = [];
$post_data['message'] = $message->getTextContent();
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
curl_close($ch);

echo 'done';
