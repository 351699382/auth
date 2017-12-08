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

class DataAccess extends Db
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
        $this->dbConfig = $config;
        //获取DB连接
        $this->db = $this->connectDB();
    }

    /**
     * 创建组权限
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public function saveGroup(array $params)
    {

        // $d    = new DateTime();
        // $time = $d->format('Y-m-d H:i:s');
        $insertFlag = true;
        if (isset($params['id']) && !empty($params['id'])) {
            //更新
            $insertFlag = false;
            //获取原数据
            $data   = $this->getGroupById($params['id']);
            $params = array_filter($params, function ($v) {
                return !empty($v) ? true : false;
            });
            $params = array_merge($data, $params);
            $sql    = "UPDATE auth_group SET title = ?,rules = ?,status = ? , remarks = ? WHERE id = ?";
            $stmt   = $this->db->prepare($sql);
            $stmt->bindValue(5, isset($params['id']) ? $params['id'] : null, PDO::PARAM_INT);
        } else {
            //新增
            $sql  = "INSERT INTO auth_group(title, rules ,status,remarks) VALUES (?, ?, ? ,? )";
            $stmt = $this->db->prepare($sql);
        }

        $stmt->bindValue(1, isset($params['title']) ? $params['title'] : null, PDO::PARAM_STR);
        $stmt->bindValue(2, isset($params['rules']) ? $params['rules'] : null, PDO::PARAM_STR);
        $stmt->bindValue(3, isset($params['status']) ? $params['status'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(4, isset($params['remarks']) ? $params['remarks'] : null, PDO::PARAM_STR);
        $stmt->execute();

        if ($insertFlag) {
            $insertId = (int) $this->db->lastInsertId();
            return $insertId;
        } else {
            return true;
        }
    }

    /**
     * 删除某条规则
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function delGroup($id)
    {
        if (empty($id)) {
            throw new \Exception("id不能为空", 1);
        }
        $sql = "DELETE FROM `auth_group` WHERE id = $id ";
        //定时执行日志
        $total = $this->db->exec($sql);
        return $total;
    }

    /**
     * 获取一条
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getGroupById($id)
    {
        $sql  = "SELECT * FROM auth_group WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 创建组权限
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public function saveRule(array $params)
    {
        // $d    = new DateTime();
        // $time = $d->format('Y-m-d H:i:s');
        $insertFlag = true;
        if (isset($params['id']) && !empty($params['id'])) {
            //更新
            $insertFlag = false;
            //获取原数据
            $data   = $this->getRuleById($params['id']);
            $params = array_merge($data, $params);
            $sql    = "UPDATE auth_rule SET `name` = ?,`title` = ?,`type` = ?, `status` = ? , `condition` = ? , `pid` = ?  WHERE id = ?";
            $stmt   = $this->db->prepare($sql);
            $stmt->bindValue(7, $params['id'], PDO::PARAM_INT);
        } else {
            //新增
            $sql  = "INSERT INTO auth_rule(`name`, `title` ,`type`,`status`,`condition`,`pid`) VALUES (?, ?, ? , ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
        }

        $stmt->bindValue(1, isset($params['name']) ? $params['name'] : null, PDO::PARAM_STR);
        $stmt->bindValue(2, isset($params['title']) ? $params['title'] : null, PDO::PARAM_STR);
        $stmt->bindValue(3, isset($params['type']) ? $params['type'] : 0, PDO::PARAM_INT);
        $stmt->bindValue(4, isset($params['status']) ? $params['status'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(5, isset($params['condition']) ? $params['condition'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(6, isset($params['pid']) ? $params['pid'] : 1, PDO::PARAM_INT);

        $stmt->execute();
        if ($insertFlag) {
            $insertId = (int) $this->db->lastInsertId();
            return $insertId;
        } else {
            return true;
        }
    }

    /**
     * 获取一条
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getRuleById($id)
    {
        $sql  = "SELECT * FROM auth_rule WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 删除某条规则
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function delRule($id)
    {
        if (empty($id)) {
            throw new \Exception("id不能为空", 1);
        }
        $sql = "DELETE FROM `auth_rule` WHERE id = $id ";
        //定时执行日志
        $total = $this->db->exec($sql);
        return $total;
    }

}
