<?php
require( "vendor/autoload.php" );

use ZBateson\MailMimeParser\Message;

class MailToLineNotify {
    const ENDPOINT_URL = 'https://notify-api.line.me/api/notify';

    private $access_token;

    public function __construct( $access_token ) {
        $this->access_token = $access_token;
        $this->run();
    }

    public function run() {
        $content = $this->parseMailContent();
        if ( empty( $content ) ) {
            return;
        }
        $this->notify( $content );
    }

    public function getStdin() {
        return file_get_contents( "php://stdin" );
    }

    public function parseMailContent() {
        $stdin = $this->getStdin();
        if ( empty( $stdin ) ) {
            return '';
        }
        $message = Message::from( $stdin );
        if ( empty( $message ) ) {
            return '';
        }

        return $message->getTextContent();
    }

    public function notify( $message ) {
        $headers   = [
            'Authorization: Bearer ' . $this->access_token,
        ];
        $post_data = [
            'message' => $message,
        ];
        $ch        = curl_init( self::ENDPOINT_URL );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post_data ) );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        $result = curl_exec( $ch );
        curl_close( $ch );

        return $result;
    }
}

// Check parameters.
if ( empty( $argv[1] ) ) {
    exit( 'Invalid parameter.' );
}

// Send message from mail to LINE Nofify.
$mail2linenotify = new MailToLineNotify( $argv[1] );

echo 'done';

