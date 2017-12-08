<?php
/**
 *           佛祖保佑       永无BUG
 *
 *                   _ooOoo_
 *                  o8888888o
 *                  88" . "88
 *                  (| -_- |)
 *                  O\  =  /O
 *               ____/`---'\____
 *             .'  \\|     |//  `.
 *            /  \\|||  :  |||//  \
 *           /  _||||| -:- |||||-  \
 *           |   | \\\  -  /// |   |
 *           | \_|  ''\---/''  |   |
 *           \  .-\__  `-`  ___/-. /
 *         ___`. .'  /--.--\  `. . __
 *      ."" '<  `.___\_<|>_/___.'  >'"".
 *     | | :  `- \`.;`\ _ /`;.`/ - ` : | |
 *     \  \ `-.   \_ __\ /__ _/   .-` /  /
 * ======`-.____`-.___\_____/___.-`____.-'======
 *                   `=---='
 * @Author     SuJun (351699382@qq.com)
 * @time       2017-12-04
 * @link       https://github.com/351699382(http://www.yikaipu.com)
 * @copyright  Copyright (c) 2017
 */
namespace Auth;

use Auth\Custom;

/*
//数据库
-- ----------------------------
-- auth_rule，规则表，
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule`;
CREATE TABLE `auth_rule` (
`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '规则唯一标识',
`title` varchar(255) NOT NULL DEFAULT '' COMMENT '规则中文名称',
`type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:正则验证规则',
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：为1正常，为0禁用',
`condition` text NOT NULL DEFAULT '' COMMENT '规则表达式，为空表示存在就验证，不为空表示按照条件验证',  # 规则附件条件,满足附加条件的规则,才认为是有效的规则
`pid` mediumint(8) unsigned NOT NULL COMMENT '用户把规则划分成组，方便分配权限',
PRIMARY KEY (`id`),
UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='规则表';

-- ----------------------------
-- auth_group 用户组表
-- ----------------------------
DROP TABLE IF EXISTS `auth_group`;
CREATE TABLE `auth_group` (
`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
`title` varchar(255) NOT NULL DEFAULT '' COMMENT '用户组中文名称',
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：为1正常，为0禁用',
`rules` text  DEFAULT '' COMMENT '用户组拥有的规则id，多个规则","隔开',
`remarks` text  DEFAULT '' COMMENT '备注',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户组表';

-- ----------------------------
-- auth_group_access 用户组明细表
-- ----------------------------
DROP TABLE IF EXISTS `auth_group_access`;
CREATE TABLE `auth_group_access` (
`uid` mediumint(8) unsigned NOT NULL COMMENT '用户id',
`group_id` mediumint(8) unsigned NOT NULL COMMENT '用户组id',
UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
KEY `uid` (`uid`),
KEY `group_id` (`group_id`)
) ENGINE=InnoDB  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户组明细表';

 */

/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth=new Auth();  $auth->check('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth=new Auth();  $auth->check('规则1,规则2','用户id','and')
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个用户组(auth_group_access表 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(auth_group 定义了用户组权限)
 *
 * 4，支持规则表达式。
 *      在auth_rule 表中定义一条规则时，如果type为1， condition字段就可以定义规则表达式。 如定义{score}>5 and {score}<100  表示用户的分数在5-100之间时这条规则才会通过。
 */

class Auth
{
    /**
     * @var object 对象实例
     */
    protected static $instance;

    /**
     * 数据对象
     * @var [type]
     */
    protected static $custom;

    //默认配置
    protected $config = [
        'auth_on'           => 1, // 权限开关
        'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group'        => 'auth_group', // 用户组数据表名
        'auth_group_access' => 'auth_group_access', // 用户-用户组关系表
        'auth_rule'         => 'auth_rule', // 权限规则表
        'auth_user'         => 'member', // 用户信息表
    ];

    /**
     * 类架构函数
     * Auth constructor.
     */
    public function __construct(array $config)
    {
        //可设置配置项 auth, 此配置项为数组。
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 初始化
     * @access public
     * @param array $options 参数
     * @return \think\Request
     */
    public static function instance($options = [], $obj = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options['auth']);
        }
        if (empty($obj)) {
            self::$custom = new Custom($options);
        } else {
            if ($obj instanceof Custom) {
                self::$custom = $obj;
            } else {
                throw new \Exception("自定义类型请继承'Auth\Custom'类", 1);
            }
        }
        return self::$instance;
    }

    /**
     * 检查权限
     * @param $name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param $uid  int           认证用户的id
     * @param string $relation 如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @param int $type 认证类型
     * @param string $mode 执行check的模式
     * @return bool               通过验证返回true;失败返回false
     */
    public function check($name, $uid, $relation = 'or', $type = 1, $mode = 'url')
    {
        //检测是否启用权限开关
        if (!$this->config['auth_on']) {
            return true;
        }

        // 获取用户需要验证的所有有效规则列表
        $authList = $this->getAuthList($uid, $type);

        //把规则转成数组
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = [$name];
            }
        }

        $list = []; //保存验证通过的规则名
        if ('url' == $mode) {
            $REQUEST = $_GET;
        }

        foreach ($authList as $auth) {
            //用于url模式,如:www.yikaipu.com/index.php?a=12&b=234,即获取a=12&b=234,没有?则获取全部
            $query = preg_replace('/^.+\?/U', '', $auth);
            //如果URL存在变量即有?后面带值情况要区分开判断，即除了变量值可以不一样外，其它需要一样
            if ('url' == $mode && $query != $auth) {
                parse_str($query, $param); //解析规则中的param
                $intersect = array_intersect_assoc($REQUEST, $param);
                $auth      = preg_replace('/\?.*$/U', '', $auth); //剔除?后面的字串
                //如果规则存在且参数一样则符合
                if (in_array($auth, $name) && $intersect == $param) {
                    //如果节点相符且url参数满足
                    $list[] = $auth;
                }

            } else {
                if (in_array($auth, $name)) {
                    $list[] = $auth;
                }
            }
        }

        if ('or' == $relation && !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ('and' == $relation && empty($diff)) {
            return true;
        }

        return false;
    }

    /**
     * 获得权限列表
     * @param integer $uid 用户id
     * @param integer $type 1为实时认证；2为登录认证。
     * @return array
     */
    protected function getAuthList($uid, $type)
    {
        static $_authList = []; //保存用户验证通过的权限列表
        $t                = implode(',', (array) $type);
        if (isset($_authList[$uid . $t])) {
            return $_authList[$uid . $t];
        }

        //判断是否是登录认证，从缓存中获取权限列表
        if (
            2 == $this->config['auth_type'] &&
            self::$custom->hasCache('_auth_list_' . $uid . $t)
        ) {
            return self::$custom->getCache('_auth_list_' . $uid . $t);
        }

        //读取用户所属用户组即获取其所有的规则ID
        $rules = self::$custom->getRules($uid);

        //循环判断规则，即获取所有符合条件的规则
        $authList = [];
        //把公共规则加上
        if (strpos($this->config['public_auth'], ',') !== false) {
            $this->config['public_auth'] = explode(',', $this->config['public_auth']);
        } else {
            $this->config['public_auth'] = [$this->config['public_auth']];
        }
        $authList = $this->config['public_auth'];

        foreach ($rules as $rule) {
            //跳过权限 todo
            if ($rule['type'] == 1 && !empty($rule['condition'])) {
                //根据condition进行验证
                $user    = $this->getUserInfo($uid); //获取用户信息,一维数组
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = strtolower($rule['name']);
                }
            } else {
                //只要存在就记录
                $authList[] = strtolower($rule['name']);
            }

        }
        $authList             = array_unique($authList);
        $_authList[$uid . $t] = $authList;

        //如果为登录认证，存起来
        if (2 == $this->config['auth_type']) {
            //规则列表结果保存到缓存
            self::$custom->setCache('_auth_list_' . $uid . $t, $authList);
        }

        return $authList;
    }

    /**
     * 获得用户资料,根据自己的情况读取数据库
     */
    protected function getUserInfo($uid)
    {
        static $userinfo = [];
        $user            = self::$custom->getUserInfo($uid);
        if (!empty($user)) {
            $userinfo[$uid] = $user;
        }
        return $userinfo[$uid];
    }
}
