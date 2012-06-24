<?php
/*************************************
 *           - gosh -                *
 * GNU Open Softwaresuite for Hotels *
 ************************************/

class Config
{
    private $settings = array();
    private static $CONFIGFILE = "/sys/settings";

    public function __construct() {
        self::loadSettings();
    }

    public function sleep() {
        return array("settings");
    }

    private static function getRootPath() {
        $included_files = get_included_files();
        foreach($included_files as $file) {
            $pos = strrpos($file, "/sys/class/class.config.php");
            if($pos) return substr($file, 0, $pos);
        }
        return null;
    }

    private static function readConfigFile($file) {
        $content = file($file);
        $result = array();

        if($content === false) {
            throw new Exception("Config-File could not be read!");
        }

        foreach($content as $line) {
            $line = trim($line);
            $lineparts = explode("=", $line);

            if(count($lineparts) === 2) {
                $key = strtoupper(trim($lineparts[0]));

                // DB_Type should be lowerCase e.g. mysql, pgsql
                if($key === "DB_TYPE") {
                    $value = strtolower(trim($lineparts[1]));
                } else {
                    $value = trim($lineparts[1]);
                }
                $result[$key] = $value;
            }
        }

        if(self::isValidConfig($result))
            return $result;
        else throw new Exception("The configuration settings are invalid!");
    }

    // This method simply tests whether necessary keys are present
    private static function isValidConfig($array) {
        if(!array_key_exists("DB_TYPE", $array)) {
            throw new InvalidArgumentException("Could not find \"DB_TYPE\" in Config-File!");
        }

        switch($array["DB_TYPE"]) {
            case "mysql":
            case "mssql":
            case "pgsql":
                $requiredKeys = array("DB_HOST", "DB_NAME", "DB_USER", "DB_PASS", "DB_PORT");
                break;
            case "sqlite":
            case "sqlite2":
                $requiredKeys = array("DB_PATH");
                break;
            default:
                throw new InvalidArgumentException("DB_Type \"" . $array["DB_TYPE"] . "\" is unknown.");
        }

        foreach ($requiredKeys as $key) {
            if(!array_key_exists($key, $array) || empty($result[$key]))
                throw new InvalidArgumentException("Config-File does not specify necessary parameter \""
                    . $key . "\" for DB_TYPE " . $result["DB_TYPE"]);
        }

        if(!is_numeric($array["DB_PORT"])) {
            throw new InvalidArgumentException("The value of \"DB_PORT\" defined in Config-File is invalid!");
        }

        return true;
    }

    public static function getDbConnection() {
        $result = null;
        $rootpath = self::getRootPath();

        if(!file_exists($rootpath . Config::$CONFIGFILE)) {
            throw new Exception("Config-File \"" . Config::$CONFIGFILE . "\" does not exists!");
        } elseif (!is_readable($rootpath . Config::$CONFIGFILE)) {
            throw new Exception("Config-File \"" . Config::$CONFIGFILE . "\" is not readable!");
        }

        $configuration = self::readConfigFile($rootpath . Config::$CONFIGFILE);
        switch($configuration["DB_TYPE"]) {
            case "mssql":
            case "mysql":
            case "pgsql":
                $result = new PDO($configuration["DB_TYPE"] . ":host=" . $configuration["DB_HOST"] .
                        ";dbname=" . $configuration["DB_NAME"], $configuration["DB_USER"],
                    $configuration["DB_PASS"], array(PDO::ATTR_PERSISTENT => true));
                break;

            case "sqlite":
            case "sqlite2":
                $result = new PDO($configuration["DB_TYPE"] . ":" . $configuration["DB_PATH"], null, null,
                    array(PDO::ATTR_PERSISTENT => true));
                break;
        }
        return $result;
    }

    private function loadSettings() {
        $this->settings["rootpath"] = self::getRootPath();
        $this->settings["configFile"] =
            $this->settings["rootpath"] . Config::$CONFIGFILE;

        $database = ApplicationContext::getDatabaseConnection();
        $query = "SELECT property, value FROM Configuration";
        $result = $database->query($query)->fetchAll(PDO::FETCH_ASSOC);
        foreach($result as $kvp) {
            $this->settings[$kvp["property"]] = $kvp["value"];
        }
    }

    public function getProperty($key) {
        if(array_key_exists($key, $this->settings))
            return $this->settings[$key];
        else return false;
    }

    public function setProperty($key, $value) {
        $database = ApplicationContext::getDatabaseConnection();
        // If this setting exists, update DB entry,
        // else insert it
        if(array_key_exists($key, $this->settings)) {
            $query = "UPDATE Configuration SET value = :value WHERE property = :property";
        } else {
            $query = "INSERT INTO Configuration (property, value) " .
                "VALUES (:property, :value)";
        }

        $statement = $database->prepare($query);
        $statement->bindValue(":value", $value, PDO::PARAM_STR);
        $statement->bindValue(":property", $key, PDO::PARAM_STR);
        $statement->execute();

        $this->settings[$key] = $value;
    }
}
?>