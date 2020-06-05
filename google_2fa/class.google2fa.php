<?php
require('vendor/autoload.php');
require_once INCLUDE_DIR . 'class.export.php';

class GoogleAuth2FA extends StaffAuthenticationBackend {
    static $name = "Google Authentication";
    static $id = "google2fa";

    var $secretKey;

    static function bootstrap() {
        Signal::connect('agent.google2fa', array('GoogleAuth2FA', 'setSecretKey'));
        Signal::connect('google2fa.login', array('GoogleAuth2FA', 'google2faLogin'));
    }

    function supportsTwoFactorAuthentication() {
        return true;
    }

    function google2faLogin($vars) {
        $code = is_array($vars) ? $vars['code'] : $vars;

        if (is_null($vars))
            $_SESSION['_staff']['auth']['msg'] = '';
        if($code) {
            $googleAuth = new GoogleAuth2FA;

            if ($isValid = $googleAuth->validateLoginCode($code)) {
                $staffId = $_SESSION['staff'];
                $staff = Staff::lookup($staffId);
                $_SESSION['_staff']['google2fa'] = 'true';

                return header('Location: index.php');
            }

        }
        $_SESSION['_staff']['google2fa'] = 'false';
        $_SESSION['_staff']['auth']['msg'] = __('Invalid code entered. Please try again.');
    }

    function setSecretKey($staff, $vars) {
        $token = ConfigItem::getTokenByNamespace('google2fa', $staff->getId());
        if (!empty($vars['backend2fa']) && $vars['backend2fa'] == 'google2fa' && !$token) {
            $googleAuth = new GoogleAuth2FA;
            $googleKey = $googleAuth->getSecretKey($staff);

            $_config = new Config('google2fa');
            $_config->set($googleKey, $staff->getId());
        }
    }

    function getSecretKey($staff=false) {
        $googleAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();

        if (!$staff) {
            $thisstaff = StaffAuthenticationBackend::getUser();
            $staff = Staff::lookup($thisstaff->getId());
        }

        $token = ConfigItem::getTokenByNamespace('google2fa', $staff->getId());
        if (!$token)
            $this->secretKey = $googleAuth->generateSecret();

        return $token->key ?: $this->secretKey;
    }

    function getQRCode($staff=false) {
        $staffEmail = $staff->getEmail();
        $secretKey = self::getSecretKey($staff);

        return Sonata\GoogleAuthenticator\GoogleQrUrl::generate($staffEmail, $secretKey, 'SupportSystem');
    }

    function validateQRCode($staff=false) {
        $googleAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
        $secretKey = self::getSecretKey($staff);
        $code = $googleAuth->getCode($secretKey);

        return $googleAuth->checkCode($secretKey, $code);
    }

    function validateLoginCode($code) {
        $googleAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
        $secretKey = self::getSecretKey();

        return $googleAuth->checkCode($secretKey, $code);
    }

    function getCode() {
        $googleAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
        $secretKey = self::getSecretKey();

        return $googleAuth->getCode($secretKey);
    }
}
