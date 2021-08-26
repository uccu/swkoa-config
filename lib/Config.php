<?php

namespace Uccu\SwKoaConfig;

use stdClass;

class Config
{

    private static $confMap = [];

    private $conf;

    public function __construct($name)
    {
        $this->conf = new stdClass;
        self::$confMap[$name] = $this;
    }

    public function importEnv(?string $path = null)
    {
        if (!$path) {
            $path = getcwd() . "/.env";
        }

        $file = @fopen($path, "r");
        if (!$file) return;

        while (!feof($file)) {
            $line = fgets($file);
            $line = trim(preg_replace('/#.*$/', '', $line));
            if (!$line) continue;
            if (!preg_match('#^[a-z_]#i', $line)) continue;
            if (!preg_match('#^([a-z_][a-z_0-9]*)[ \t]*=[ \t]*(.*)$#i', $line, $match)) continue;

            list(, $key, $value) = $match;
            $key = strtoupper($key);
            $value = $this->stringToVal($value);
            $this->conf->$key = $value;
        }
        fclose($file);
    }

    public function importJson(?string $path = null)
    {
        if (!$path) {
            $path = getcwd() . "/config.json";
        }

        $file = @file_get_contents($path);
        if (!$file) return;

        $json = json_decode($file);

        foreach ($json as $k => $v) {
            $this->conf->$k = $v;
        }
    }

    public function importPhp(?string $path = null)
    {
        if (!$path) {
            $path = getcwd() . "/config.php";
        }

        $file = @include($path);
        if (!$file) return;

        $json = json_decode(json_encode($file));

        foreach ($json as $k => $v) {
            $this->conf->$k = $v;
        }
    }


    private function stringToVal(string $value)
    {
        if (
            (substr($value, 0, 1) == "'" && substr($value, -1, 1) == "'")
            ||
            (substr($value, 0, 1) == '"' && substr($value, -1, 1) == '"')
        ) {
            $value = substr($value, 1, -1);
        }

        if ($value === "false") {
            $value = false;
        }

        if ($value === "true") {
            $value = true;
        }

        if (is_numeric($value)) {
            if (is_float($value + 0)) {
                $value = floatval($value);
            } else {
                $value = intval($value);
            }
        }
        return $value;
    }

    public function getConfig(string $key)
    {

        $keys = explode('.', $key);

        $conf = $this->conf;
        foreach ($keys as $k) {
            if (!isset($conf->$k)) {
                return null;
            }
            $conf = $conf->$k;
        }

        return $conf;
    }

    public static function get(string $key)
    {

        $keys = explode('.', $key);
        $name = array_shift($keys);
        if (empty(self::$confMap[$name])) {
            $self = new static($name);
            $method = 'get' . ucfirst($name);
            if (method_exists($self, $method)) {
                $self->$method();
            }
        }
        return self::$confMap[$name]->getConfig(implode(',', $keys));
    }
}
