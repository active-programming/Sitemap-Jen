<?php
/**
 * Sitemap Jen
 * @author Konstantin@Kutsevalov.name
 */
class SPDO
{
    private static $prefix = 'jos_';

    /**
     * @var PDO
     */
    private static $instance = null;

    private static $error = '';

    /**
     * @param JConfig $jc
     * @return bool|string Returns TRUE on success or error message
     */
    public static function connect($jc)
    {
        self::$instance = null;
        $dsn = 'mysql:dbname=' . $jc->db . ';host=' . $jc->host . ';charset=UTF8';
        try {
            self::$instance = new PDO($dsn, $jc->user, $jc->password);
        } catch (PDOException $e) {
            self::$error = $e->getMessage();
            return self::$error;
        }
        self::$prefix = $jc->dbprefix;
        return true;
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * @param string $query "Select * from table where id = :id"
     * @param array $params [':id' => 1]
     * @return array|bool
     */
    public static function query($query, $params = null)
    {
        if (!empty(self::$instance)) {
            $query = str_replace('#__', self::$prefix, $query);
            $st = self::$instance->prepare($query);
            $st->execute($params);
            return ($st->columnCount() ? $st->fetchAll(PDO::FETCH_ASSOC) : true);
        }
        self::$error = 'No connection!';
        return [];
    }

    public static function lastInsertId()
    {
        if (!empty(self::$instance)) {
            return self::$instance->lastInsertId();
        }
        return 0;
    }


    public static function getError()
    {
        return self::$error;
    }

    public static function loadSettings($reload = false)
    {
        if (empty($_SESSION['smj_options']) || $reload) {
            $res = SPDO::query("SELECT * FROM `#__sitemapjen_options`");
            $_SESSION['smj_options'] = [];
            foreach ($res as $row) {
                $opt[$row['param']] = $row['value'];
            }
            // проверяем список исключаемых адресов, если он пуст, сканируем robots.txt на наличие disallow
            if (empty($_SESSION['smj_options']['ignore_list'])) {
                $_SESSION['smj_options']['ignore_list'] = parseRobotstxt();
            }
        }
    }

    public static function getSetting($name, $default = null)
    {
        if (empty($_SESSION['smj_options'])) {
            self::loadOptions();
        }
        return isset($_SESSION['smj_options'][$name]) ? $_SESSION['smj_options'][$name] : $default;
    }

    public static function setSetting($name, $value)
    {
        SPDO::query(
            "UPDATE `#__sitemapjen_options` SET `value` = :val WHERE `param` = :param",
            [':val' => $value, ':param' => $name]
        );
        $_SESSION['smj_options'][$name] = $value;
    }

    public static function close()
    {
        self::$instance = null;
    }

}