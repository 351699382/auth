<?php
/**
 * @Author     SuJun (351699382@qq.com)
 * @time       2017-12-04
 * @link       https://github.com/351699382(http://www.yikaipu.com)
 * @copyright  Copyright (c) 2017
 */
namespace Auth;

use Auth\Db;
use PDO;

class Custom extends Db
{

    //默认配置
    protected $config;

    /**
     * 类架构函数
     * Auth constructor.
     */
    public function __construct(array $config)
    {
        if (empty($config)) {
            throw new \Exception("数据库配置不能为空", 1);
        }
        $this->dbConfig = $config['db_config'];
        $this->config   = $config['auth'];
        //获取DB连接
        $this->db = $this->connectDB();
    }

    /**
     * 根据用户id获取用户组,返回值为数组
     * @param  $uid int     用户id
     * @return array       用户所属的用户组
     * array(
     *     array(
     *      'uid'=>'用户id',
     *      'group_id'=>'用户组id',
     *      'title'=>'用户组名称',
     *      'rules'=>'用户组拥有的规则id,多个,号隔开'
     *     ),
     * )
     */
    protected function getGroups($uid)
    {
        $sql = "SELECT aga.uid,ag.title,ag.status,ag.rules FROM {$this->config['auth_group_access']} AS aga LEFT JOIN {$this->config['auth_group']} AS ag ON aga.group_id = ag.id  WHERE aga.uid = {$uid} AND ag.status= 0 ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获得用户资料
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    public function getUserInfo($uid)
    {
        $sql    = "SELECT * FROM {$this->config['auth_user']} WHERE id = $uid ";
        $stmt   = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result[0];
    }

    /**
     * 获取指定规则
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getRules($uid)
    {

        //读取用户所属用户组即获取其所有的规则ID
        $groups = $this->getGroups($uid);
        $ids    = []; //保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
    
        $ids = array_unique($ids);
        if (empty($ids)) {
            return [];
        }

        $ids = implode(',', $ids);
        $sql = "SELECT `id`,`condition`,`name`,`type`,`request_method`,`pid` FROM {$this->config['auth_rule']}  WHERE id in ($ids) AND status= 0 ";

        $stmt = $this->db->query($sql);
        $userRules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
        //获取公开的规则，is_public：1
        $sql = "SELECT `id`,`condition`,`name`,`type`,`request_method`,`pid` FROM {$this->config['auth_rule']}  WHERE is_public=1 AND status= 0 ";
        $stmt = $this->db->query($sql);
        $publicRules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //合并
        $ret = array_column(array_merge((array)$userRules,(array)$publicRules), null,'id');
        return $ret;
    }

    /**
     * 设置缓存
     * @param [type] $key [description]
     * @param [type] $val [description]
     */
    public function setCache($key, $val)
    {
        if (PHP_SESSION_ACTIVE != session_status()) {
            session_start();
        }
        $_SESSION[$key] = $val;
    }

    /**
     * 获取缓存
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function getCache($key)
    {
        if (PHP_SESSION_ACTIVE != session_status()) {
            session_start();
        }
        return $_SESSION[$key];
    }

    /**
     * 判断是否存在该缓存
     * @param  [type]  $key [description]
     * @return boolean      [description]
     */
    public function hasCache($key)
    {
        if (PHP_SESSION_ACTIVE != session_status()) {
            session_start();
        }
        return isset($_SESSION[$key]);
    }
}
