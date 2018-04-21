# auth
这是一个方便集成于任何PHP后台系统的auth权限控制系统
v2.0.0版本加入请求头类型判断，解决有些REST风格的api。加入机构字段，有些需求是机构后台，每个机构有不同的管理权限。

## 安装
> composer require "sujun/auth": "v2.0.0"

## 配置
### 公共配置
```
// auth配置
'auth' => [
    'auth_on'           => 1, // 权限开关
    'auth_type'         => 2, // 认证方式，1为实时认证；2为登录认证。
    'public_auth'       => [
       [
           'name'=>'admin/v1.Auth/getUserAccessLists',
           'method'=>'post'
       ],
       [
           'name'=>'api/admin/info',
           'method'=>'get'
       ],
    ],
    'auth_group'        => 'auth_group', // 用户组数据表名
    'auth_group_access' => 'auth_group_access', // 用户-用户组关系表
    'auth_rule'         => 'auth_rule', // 权限规则表
    'auth_user'         => 'auth_admin', // 用户信息表
],
```

### 导入数据表
> `` 为自定义的数据表前缀

```
-- ----------------------------
-- auth_rule，规则表，
-- id:主键，name：规则唯一标识, title：规则中文名称 status 状态：为0正常，为1禁用，condition：规则表达式，为空表示存在就验证，不为空表示按照条件验证
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule`;
CREATE TABLE `auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `open_oid` int(11) DEFAULT '0' COMMENT '对某些类型机构开放：暂定0表示所有都开放',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '规则唯一标识',
  `request_method` varchar(255) NOT NULL DEFAULT '' COMMENT '请求方法',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '规则中文名称',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '规则类型，1为需要用户信息',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：为0正常，为1禁用',
  `is_show` tinyint(1) DEFAULT '0' COMMENT '是否显示:0显示，1不显示',
  `is_public` tinyint(1) DEFAULT '0' COMMENT '是否是开放的权限，用于判断新增或更改都开放情况：0否，1是',
  `condition` text NOT NULL COMMENT '规则表达式，为空表示存在就验证，不为空表示按照条件验证# 规则附件条件,满足附加条件的规则,才认为是有效的规则',
  `pid` mediumint(8) unsigned NOT NULL COMMENT '用户把规则划分成组，方便分配权限',
  `update_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='规则表';
-- ----------------------------
-- auth_group 用户组表，
-- id：主键， title:用户组中文名称， rules：用户组拥有的规则id， 多个规则","隔开，status 状态：为0正常，为1禁用
-- ----------------------------
DROP TABLE IF EXISTS `auth_group`;
CREATE TABLE `auth_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '用户组中文名称',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：为0正常，为1禁用',
  `rules` text COMMENT '用户组拥有的规则id，多个规则","隔开',
  `is_del` tinyint(1) DEFAULT '0' COMMENT '删除状态：0正常，1删除',
  `update_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `remarks` text  DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户组表';
-- ----------------------------
-- auth_group_access 用户组明细表
-- uid:用户id，group_id：用户组id
-- ----------------------------
DROP TABLE IF EXISTS `auth_group_access`;
    CREATE TABLE `auth_group_access` (
    `uid` mediumint(8) unsigned NOT NULL,
    `group_id` mediumint(8) unsigned NOT NULL,
    UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
    KEY `uid` (`uid`),
    KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
```

## 原理
Auth权限认证是按规则进行认证。
在数据库中我们有 

- 规则表（auth_rule） 
- 用户组表(auth_group) 
- 用户组明显表（auth_group_access）

我们在规则表中定义权限规则， 在用户组表中定义每个用户组有哪些权限规则，在用户组明显表中定义用户所属的用户组。 

下面举例说明：

我们要判断用户是否有显示一个操作按钮的权限， 首先定义一个规则， 在规则表中添加一个名为 show_button 的规则。 然后在用户组表添加一个用户组，定义这个用户组有show_button 的权限规则（auth_group表中rules字段存得时规则ID，多个以逗号隔开）， 然后在用户组明细表定义 UID 为1 的用户 属于刚才这个的这个用户组。 

## 使用
判断权限方法
```
// 引入类库
use think\auth\Auth;

// 获取auth实例
$auth = Auth::instance();

// 检测权限
if($auth->check('show_button',1)){// 第一个参数是规则名称,第二个参数是用户UID
	//有显示操作按钮的权限
}else{
	//没有显示操作按钮的权限
}
```

Auth类也可以对节点进行认证，我们只要将规则名称，定义为节点名称就行了。 
可以在公共控制器Base中定义_initialize方法
```
<?php
use think\Controller;
use think\auth\Auth;
class Base extends Controller
{
    public function _initialize()
	{
		$controller = request()->controller();
		$action = request()->action();
		$auth = new Auth();
		if(!$auth->check($controller . '-' . $action, session('uid'))){
			$this->error('你没有权限访问');
		}
    }
 }
```
这时候我们可以在数据库中添加的节点规则， 格式为： “控制器名称-方法名称”

Auth 类 还可以多个规则一起认证 如： 
```
$auth->check('rule1,rule2',uid); 
```
表示 认证用户只要有rule1的权限或rule2的权限，只要有一个规则的权限，认证返回结果就为true 即认证通过。 默认多个权限的关系是 “or” 关系，也就是说多个权限中，只要有个权限通过则通过。 我们也可以定义为 “and” 关系
```
$auth->check('rule1,rule2',uid,'and'); 
```
第三个参数指定为"and" 表示多个规则以and关系进行认证， 这时候多个规则同时通过认证才有权限。只要一个规则没有权限则就会返回false。

Auth认证，一个用户可以属于多个用户组。 比如我们对 show_button这个规则进行认证， 用户A 同时属于 用户组1 和用户组2 两个用户组 ， 用户组1 没有show_button 规则权限， 但如果用户组2 有show_button 规则权限，则一样会权限认证通过。 
```
$auth->getGroups(uid)
```
通过上面代码，可以获得用户所属的所有用户组，方便我们在网站上面显示。

Auth类还可以按用户属性进行判断权限， 比如
按照用户积分进行判断， 假设我们的用户表 (members) 有字段 score 记录了用户积分。 
我在规则表添加规则时，定义规则表的condition 字段，condition字段是规则条件，默认为空 表示没有附加条件，用户组中只有规则 就通过认证。
如果定义了 condition字段，用户组中有规则不一定能通过认证，程序还会判断是否满足附加条件。
比如我们添加几条规则： 

> `name`字段：grade1 `condition`字段：{score}<100 <br/>
> `name`字段：grade2 `condition`字段：{score}>100 and {score}<200<br/>
> `name`字段：grade3 `condition`字段：{score}>200 and {score}<300

这里 `{score}` 表示 `members` 表 中字段 `score` 的值。 

那么这时候 

> $auth->check('grade1', uid) 是判断用户积分是不是0-100<br/>
> $auth->check('grade2', uid) 判断用户积分是不是在100-200<br/>
> $auth->check('grade3', uid) 判断用户积分是不是在200-300

