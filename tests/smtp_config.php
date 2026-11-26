<?php
require __DIR__.'/../application/vendor/autoload.php';
// These are synthetic encoding fixtures, never real mailbox credentials.
$values=['Simple-Password-123','spaces # and quotes " and apostrophe '."'",'dollar $KEY and ${APP_KEY}', 'backslash \\ plus $ and "', 'end\\', 'literal\\n-not-a-newline'];
foreach($values as $value){$line='MAIL_PASSWORD="'.str_replace(['\\','"','$'],['\\\\','\\"','\\$'],$value).'"';$parsed=Dotenv\Dotenv::parse($line);if(($parsed['MAIL_PASSWORD']??null)!==$value)throw new RuntimeException('Credential encoding mismatch.');}
echo "PASS SMTP password encoding preserves quotes, spaces, dollar signs and backslashes without interpolation.\n";
