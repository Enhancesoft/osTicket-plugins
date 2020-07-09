<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');
require_once('class.google2fa.php');

class GoogleAuth2FAPlugin extends Plugin {
    var $config_class = "Google2FAConfig";

    function bootstrap() {
        TwoFactorAuthenticationBackend::register('GoogleAuth2FABackend');
    }

    function enable() {
        return parent::enable();
    }

    function uninstall() {
        $errors = array();

        self::disable();

        return parent::uninstall($errors);
    }

    function disable() {
        $default2fas = GoogleAuth2FABackend::getConfigsByNamespace(false, 'default_2fa', '2fa-google');
        foreach($default2fas as $default2fa)
            $default2fa->delete();

        $tokens = GoogleAuth2FABackend::getConfigsByNamespace(false, '2fa-google');
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
