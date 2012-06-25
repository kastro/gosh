<?php
/*************************************
 *           - gosh -                *
 * GNU Open Softwaresuite for Hotels *
 ************************************/
final class ApplicationContext
{
    // Singleton
    private static $instance;

    // Systemattributes
    private $database;
    private $config;
    private $session;
    private $pageAccessLevel;

    public function __construct($pageAccessLevel) {
        //start session
        @session_name("sid");
        session_start();

        self::$instance = $this;
        $this->database = Config::getDbConnection();

        self::setAccessLevel($pageAccessLevel);
        self::loadConfig();
        self::loadSession();

        // Execute logout if requested
        if(isset($_GET["session"]) &&
            $_GET["session"] === "logout")
            $this->session->logout();
    }

    public function __destruct() {
        // Suspend state
        $_SESSION["Session"] = serialize($this->session);
        $_SESSION["Config"] = serialize($this->config);
    }

    private static function getInstance() {
        if(isset(self::$instance)) {
            return self::$instance;
        } else {
            throw new Exception("ApplicationContext has not been initialized, yet!");
        }
    }

    private function setPageAccessLevel($pageAccessLevel)
    {
        // Check and set accesslevel
        $options = array();
        $options["options"]["min_range"] = 0;
        $options["options"]["max_range"] = 999;
        if(filter_var($pageAccessLevel, FILTER_VALIDATE_INT, $options) !== false) {
            $this->pageAccessLevel = $pageAccessLevel;
        } else {
            throw new Exception("Invalid value for $pageAccessLevel.<br>Value has to be integer and between 0 - 999.");
        }
    }

    private function loadSession() {
        if(isset($_SESSION["Session"])) {
            $this->session = unserialize($_SESSION["Session"]);
        } else {
            $this->session = new Session();
        }
        // Validate session
        $this->session->validate($this->pageAccessLevel);
    }

    private function loadConfig() {
        if(isset($_SESSION["Config"])) {
            $this->config = unserialize($_SESSION["Config"]);
        } else {
            $this->config = new Config();
        }
    }

    public static function getDatabaseConnection() {
        return ApplicationContext::getInstance()->database;
    }

    public static function getProperty($key) {
        return ApplicationContext::getInstance()->config->getProperty($key);
    }

    public static function setProperty($key, $value) {
        ApplicationContext::getInstance()->config->setProperty($key, $value);
    }

    public static function getCurrentUser() {
        return ApplicationContext::getInstance()->session->getCurrentUser();
    }

    public static function login($username, $password) {
        return ApplicationContext::getInstance()->session->login($username, $password);
    }
}