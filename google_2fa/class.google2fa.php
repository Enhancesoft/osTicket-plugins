<?php
require('vendor/autoload.php');
require_once INCLUDE_DIR . 'class.export.php';

class GoogleAuth2FABackend extends TwoFactorAuthenticationBackend {
    static $id = /* @trans */ "2fa-google";
    static $name = "Google Authenticator";

    static $desc = /* @trans */ 'Verification codes are located in the Google Authenticator app on your phone';

    var $secretKey;

    protected function getSetupOptions() {
        global $thisstaff;

        $googleAuth = new GoogleAuth2FABackend;
        $qrCodeURL = $googleAuth->getQRCode($thisstaff);
        if ($googleAuth->validateQRCode($thisstaff)) {
            return array(
                '' => new FreeTextField(array(
                    'configuration' => array(
                        'content' => sprintf(
                            '<input type="hidden" name="email" value="%s" />
                            <em>Use the Google Authenticator application on your phone to scan and
                                the QR Code below. If you lose the QR Code
                                on the app, you will need to have your 2FA configurations reset by
                                a helpdesk Administrator.</em>
                            </br>
                            <tr>
                                <td>
                                <img src="%s" alt="QR Code" />
                                </td>
                            </tr>',
                            $thisstaff->getEmail(), $qrCodeURL),
                    )
                )),
            );
        }
    }

    protected function getInputOptions() {
        return array(
            'token' => new TextboxField(array(
                'id'=>1, 'label'=>__('Verification Code'), 'required'=>true, 'default'=>'',
                'validator'=>'number',
                'hint'=>__('Please enter the code from your Google Authenticator app'),
                'configuration'=>array(
                    'size'=>40, 'length'=>40,
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                    'validator-error' => __('Invalid Code format'),
                    ),
            )),
        );
    }

    function validate($form, $user) {
        // Make sure form is valid and token exists
        if (!($form->isValid()
                    && ($clean=$form->getClean())
                    && $clean['token']))
            return false;

        if (!$this->validateLoginCode($clean['token']))
            return false;

        // upstream validation might throw an exception due to expired token
        // or too many attempts (timeout). It's the responsibility of the
        // caller to catch and handle such exceptions.
        $secretKey = self::getSecretKey();
        if (!$this->_validate($secretKey))
            return false;

        // Validator doesn't do house cleaning - it's our responsibility
        $this->onValidate($user);

        return true;
    }

    function send($user) {
        global $cfg;

        // Get backend configuration for this user
        if (!$cfg || !($info = $user->get2FAConfig($this->getId())))
            return false;

        // get configuration
        $config = $info['config'];

        // Generate Secret Key
        if (!$this->secretKey)
            $this->secretKey = self::getSecretKey($user);

        $this->store($this->secretKey);

        return true;
    }

    function store($secretKey) {
       global $thisstaff;

       $store =  &$_SESSION['_2fa'][$this->getId()];
       $store = ['otp' => $secretKey, 'time' => time(), 'strikes' => 0];

       if ($thisstaff) {
           $config = array('config' => array('key' => $secretKey, 'external2fa' => true));
           $_config = new Config('staff.'.$thisstaff->getId());
           $_config->set($this->getId(), JsonDataEncoder::encode($config));
           $thisstaff->_config = $_config->getInfo();
           $errors['err'] = '';
       }

       return $store;
    }

    function validateLoginCode($code) {
        $googleAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
        $secretKey = self::getSecretKey();

        return $googleAuth->checkCode($secretKey, $code);
    }

    function getSecretKey($staff=false) {
        if (!$staff) {
            $s = StaffAuthenticationBackend::getUser();
            $staff = Staff::lookup($s->getId());
        }

        if (!$token = ConfigItem::getConfigsByNamespace('staff.'.$staff->getId(), '2fa-google')) {
            $googleAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
            $this->secretKey = $googleAuth->generateSecret();
            $this->store($this->secretKey);
        }

        $key = $token->value ?: $this->secretKey;
        if (strpos($key, 'config')) {
            $key = json_decode($key, true);
            $key = $key['config']['key'];
        }

        return $key;
    }

    function getQRCode($staff=false) {
        $staffEmail = $staff->getEmail();
        $secretKey = self::getSecretKey($staff);

        return Sonata\GoogleAuthenticator\GoogleQrUrl::generate($staffEmail, $secretKey, 'osTicket');
    }

    function validateQRCode($staff=false) {
        $googleAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
        $secretKey = self::getSecretKey($staff);
        $code = $googleAuth->getCode($secretKey);

        return $googleAuth->checkCode($secretKey, $code);
    }

    function getCode() {
        $googleAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
        $secretKey = self::getSecretKey();

        return $googleAuth->getCode($secretKey);
    }
}
