<?php
/**
 * @Author     SuJun (351699382@qq.com)
 * @time       2017-12-04
 * @link       https://github.com/351699382(http://www.yikaipu.com)
 * @copyright  Copyright (c) 2017
 */
namespace Auth;

use PDO;

class Db
{

    /**
     * 数据库配置
     * @var [type]
     */
    protected $dbConfig;

    /**
     * 数据库连接
     */
    protected $db;

    /**
     * 连接指定数据库
     * @param string $name
     * @param bool $change 当前db是否指向这个连接的DB
     * @return bool|PDO
     */
    protected function connectDB()
    {
        $db = null;
        try {
            $dbOptions = [
                PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_AUTOCOMMIT => 1,
            ];
            $dsn = "mysql:host={$this->dbConfig['hostname']};port={$this->dbConfig['hostport']};dbname={$this->dbConfig['database']};charset={$this->dbConfig['charset']}";
            $db  = new PDO($dsn, $this->dbConfig['username'], $this->dbConfig['password'], $dbOptions);
        } catch (PDOException $e) {
            throw new \Exception("连接数据库失败,请检查数据库配置或是否开启MYSQL_PDO", 1);
        }
        return $db;
    }

}
