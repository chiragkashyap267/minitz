<?php
// Pure diagnostic classification: no bootstrap, SMTP connections or messages.
define('MINITZ_SMTP_DIAGNOSTICS_LIBRARY_ONLY',true);require __DIR__.'/../deployment/hostinger-mail-check.php';
$secret='private-password-42';
foreach([['535 Authentication failed for '.$secret,'AUTH_REJECTED'],['Connection timed out '.$secret,'CONNECTION_TIMEOUT'],['getaddrinfo failed '.$secret,'DNS_FAILURE'],['SSL certificate verify failed '.$secret,'TLS_FAILURE'],['Connection refused '.$secret,'CONNECTION_REJECTED'],['Sending quota exceeded '.$secret,'SENDING_LIMIT'],['550 sender not allowed '.$secret,'SENDER_REJECTED'],['Unknown error '.$secret,'OTHER_SMTP_ERROR']] as [$message,$code]){$result=minitz_smtp_diagnosis(new RuntimeException($message));$json=json_encode($result);if(!str_contains($result['result'],$code)||str_contains($json,$secret))throw new RuntimeException('Diagnostic classification or redaction failed.');}
echo "PASS safe SMTP failure categories; raw errors and passwords never returned.\n";
