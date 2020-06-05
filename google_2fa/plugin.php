<?php

return array(
    'id' =>             'auth:google2fa', # notrans
    'version' =>        '0.1',
    'name' =>           /* trans */ 'Google Authenticator 2FA',
    'author' =>         'Adriane Alexander',
    'description' =>    /* trans */ 'Provides 2 Factor Authentication
                        using the Google Authenticator App',
    'url' =>            'https://www.osticket.com/download',
    'plugin' =>         'googleauth.php:GoogleAuth2FAPlugin',
);
?>
