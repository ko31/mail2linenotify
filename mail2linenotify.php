<?php
require( "vendor/autoload.php" );

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Header\HeaderConsts;

/**
 * Class MailToLineNotify
 */
class MailToLineNotify {
    /**
     * LINE Notify endpoint URL.
     */
    const ENDPOINT_URL = 'https://notify-api.line.me/api/notify';

    /**
     * LINE Notify maximum message length.
     */
    const MAX_MESSAGE_LENGTH = 1000;

    /**
     * Return path set in forwarded mail.
     */
    const TRANSFERD_RETURN_PATH = 'MAILER-DAEMON';

    /**
     * LINE Notify access token.
     *
     * @var string
     */
    private $access_token;

    /**
     * Detect whether to send a forwarded mail.
     *
     * @var bool
     */
    private $is_send_forwarded_email;

    /**
     * Constructor.
     *
     * @param $access_token
     * @param $is_send_forwarded_email
     */
    public function __construct( $access_token, $is_send_forwarded_email ) {
        $this->access_token            = $access_token;
        $this->is_send_forwarded_email = $is_send_forwarded_email;
        $this->run();
    }

    /**
     * Run proccess.
     */
    public function run() {
        $content = $this->parseMailContent();
        if ( empty( $content ) ) {
            return;
        }
        $this->send( $content );
    }

    /**
     * Get standard input.
     *
     * @return false|string
     */
    public function getStdin() {
        return file_get_contents( "php://stdin" );
    }

    /**
     * Parse email content.
     *
     * @return string
     */
    public function parseMailContent() {
        $stdin = $this->getStdin();
        if ( empty( $stdin ) ) {
            return '';
        }

        $message = Message::from( $stdin );
        if ( empty( $message ) ) {
            return '';
        }

        // Detect whether to send a forwarded mail.
        if ( ! $this->is_send_forwarded_email ) {
            if ( self::TRANSFERD_RETURN_PATH === $message->getHeaderValue( 'Return-Path' ) ) {
                return;
            }
        }

        return sprintf( "\n[メール件名]\n%s\n\n[メール本文]\n%s", $message->getHeaderValue( HeaderConsts::SUBJECT ), $message->getTextContent() );
    }

    /**
     * Send message to LINE Notify
     *
     * @param $content
     */
    public function send( $content ) {
        $messages = $this->splitMessage( $content );
        foreach ( $messages as $message ) {
            $this->notify( $message );
        }
    }

    /**
     * Split message text.
     *
     * @param $message
     *
     * @return array
     */
    public function splitMessage( $message ) {
        $length = self::MAX_MESSAGE_LENGTH;

        // Split message text
        $split = [];
        $i     = 0;
        while ( true ) {
            // If the text to be split is shorter than length, it breaks.
            if ( mb_strlen( mb_substr( $message, $i ) ) <= $length ) {
                $split[] = mb_substr( $message, $i );
                break;
            }

            // Get the position to split
            $substr    = mb_substr( $message, $i, $length );
            $split_pos = mb_strrpos( $substr, "\n" );
            if ( $split_pos === false ) {
                // If no line break code
                $split_pos = $length;
            } else if ( $split_pos === 0 ) {
                // If a leading character is line break code
                $split_pos = $length;
                // Skip a leading character
                $i ++;
            } else {
                $split_pos ++;
            }

            $split[] = mb_substr( $message, $i, $split_pos );

            $i += $split_pos;
        }

        return $split;
    }

    /**
     * Send LINE Notify
     *
     * @param $message
     *
     * @return bool|string
     */
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
$is_send_forwarded_email = ( isset( $argv[2] ) ? (bool) $argv[2] : 0 );
$mail2linenotify = new MailToLineNotify( $argv[1], $is_send_forwarded_email );
