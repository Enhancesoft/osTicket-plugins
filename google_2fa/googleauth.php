<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');
require_once('class.google2fa.php');

class GoogleAuth2FAPlugin extends Plugin {
    var $config_class = "Google2FAConfig";

    function bootstrap() {
        GoogleAuth2FA::bootstrap();

        Signal::connect('agent.google2fa.register', function($staff, &$extras) {
            StaffAuthenticationBackend::register('GoogleAuth2FA');
        });

        //Agent 2FA Configuration
        Signal::connect('agent.account.auth', function($staff, &$extras) {
            global $thisstaff;

            if (!$thisstaff || !$thisstaff->isAdmin())
                return;

            require_once('class.google2fa.php');

            include 'templates/agent-save.tmpl.php';
        });
    }

    function uninstall() {
        $errors = array();

        self::disable();

        return parent::uninstall($errors);
    }

    function disable() {
        if($backend2FA = ConfigItem::getConfigsByNamespace(false, 'Google2FA'))
            $backend2FA->delete();

        $tokens = ConfigItem::getConfigsByNamespace(false, 'backend2fa', 'Google2FA');
        foreach($tokens as $token)
            $token->delete();

        return parent::disable();
    }
}

require_once(INCLUDE_DIR.'UniversalClassLoader.php');
use Symfony\Component\ClassLoader\UniversalClassLoader_osTicket;
$loader = new UniversalClassLoader_osTicket();
$loader->registerNamespaceFallbacks(array(
    dirname(__file__).'/lib'));
$loader->register();
