<?php
    class DbConection
    {
        static private mysqli $conection;
        public static function instance()
        {
            if(!isset(DbConection::$conection))
            {
                DbConection::$conection = new mysqli("127.0.0.1", "root", "", "KindergartenDB");
                if(DbConection::$conection->connect_error != null)
                {
                    die("Ошибка подключения ".DbConection::$conection->connect_error);
                }
            }
            return DbConection::$conection;
        }
    }

    function check_parameters($parameters, ...$parameters2)
    {
        foreach ($parameters2 as $v)
        {
            if (!isset($parameters[$v]))
            {
                return false;
            }
        }
        return true;
    }

    interface IDBEntity
    {
        public function insert();
        public function update();
        public function remove();
    }
?>