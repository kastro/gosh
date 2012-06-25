<?php
/*************************************
 *           - gosh -                *
 * GNU Open Softwaresuite for Hotels *
 ************************************/

class Session
{
    private $user;
    private $fingerprint;
    private $lastActive;
    private $location;
    private $secretSalt;
    private static $TIMEOUT = 900; // Timeout (15min)
    private static $REASON_INVALIDSESSION = 1;
    private static $REASON_NOTLOGGEDIN = 2;
    private static $REASON_TIMEOUT = 3;
    private static $REASON_LOGOUT = 4;

    public function __construct() {
        self::setSecretSalt();
        self::setFingerprint();
        self::setLastActive();
    }

    public function __sleep() {
        return array("user", "fingerprint", "lastActive", "location", "secretSalt");
    }

    public function logout() {
        // If user requests a session logout
        $this->user = null;
        self::requestLogin(self::$REASON_LOGOUT);
    }

    private function setSecretSalt() {
        $this->secretSalt = sha1_file(ApplicationContext::getProperty("configFile"));
    }

    private function getFingerPrint() {
        return sha1($this->secretSalt . $_SERVER["HTTP_USER_AGENT"] .
            $_SERVER["REMOTE_ADDR"]);
    }

    private function setFingerprint() {
        $this->fingerprint = self::getFingerPrint();
    }

    private function setLastActive() {
        $this->lastActive = time();
    }

    private function requestLogin($reason) {
        $urlSuffix = "?reason=";
        switch($reason) {
            case self::$REASON_INVALIDSESSION:
                $urlSuffix .= "invalidSession";
                break;
            case self::$REASON_NOTLOGGEDIN:
                $urlSuffix .= "notLoggedIn";
                break;
            case self::$REASON_TIMEOUT:
                $urlSuffix .= "timeout";
                break;
            case self::$REASON_LOGOUT:
                $urlSuffix .= "logout";
        }
        $this->location = $_SERVER["SCRIPT_URI"];
        $loginQuery = ApplicationContext::getProperty("loginPage") . $urlSuffix;
        header("Location: " . $loginQuery);
        exit();
    }

    private function isLoggedIn() {
        return isset($this->user);
    }

    // If fingerprint does not match, reset usersession and request login
    private function validateFingerprint() {
        if($this->fingerprint !== self::getFingerPrint()) {
            $this->user = null;
            self::requestLogin(self::$REASON_INVALIDSESSION);
        }
    }

    private function checkTimeout() {
        if((time() - $this->lastActive) > self::$TIMEOUT) {
            $this->user = null;
            self::requestLogin(self::$REASON_TIMEOUT);
        }
    }

    private function denyAccess() {
        throw new Exception("Insufficient Rights");
    }

    public function validate($pageAccessLevel) {
        if($pageAccessLevel === 0) {
            return true;
        } elseif(self::isLoggedIn()) {
            if($this->user->getPermissionLevel() >= $pageAccessLevel) {
                self::validateFingerprint();
                self::checkTimeout();
            } else {
                self::denyAccess();
            }
        } else {
            self::requestLogin(self::$REASON_NOTLOGGEDIN);
        }
    }

    public function login($username, $password) {
        $this->user = User::login($username, $password);

        // If login failed, return false
        if(!self::isLoggedIn()) {
            return false;
        } else {
            if(isset($this->location)) {
                $location = $this->location;
                $this->location = null;
                header("Location: " . $location);
                exit();
            } else {
                // Standard redirect
                Login::redirect();
            }
        }
    }

    public function getCurrentUser() {
        return $this->user;
    }
}
