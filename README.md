
> 本仓库存放服务端API源码

## 所有API文档如下
> 具体也可以查看doc仓库中的API文档

- [系统API格式](https://note.youdao.com/share/?token=17BF530888AD4843BEE2682B272169C3&gid=46417183
)

### APP

- [用户相关](https://note.youdao.com/share/?token=065C88C877244DABA953AC391A2D0991&gid=46417183
)

- [图片相关](http://note.youdao.com/groupshare/?token=FEC804AA084D4E04A7B4213DF95DA4B1&gid=46417183
)


### 后台

- [管理员](http://note.youdao.com/groupshare/?token=7B020E9F41514FC6813259A0AEF733FA&gid=46417183
)
- [用户](http://note.youdao.com/groupshare/?token=8576DA60F048415D893300173A67025B&gid=46417183
)
- [任务](http://note.youdao.com/groupshare/?token=5A975691D01E4BFCA1682181F60C5EE1&gid=46417183
)
- [标签](http://note.youdao.com/groupshare/?token=EB1247AFFE1741909451A0ACC84EC780&gid=46417183
)
- [审核](https://note.youdao.com/share/?token=239C7FDD8D034BAE972E3535D58D1402&gid=46417183
)

---

ThinkPHP 5.0
===============

> ThinkPHP5的运行环境要求PHP5.4以上。

详细开发文档参考 [ThinkPHP5完全开发手册](http://www.kancloud.cn/manual/thinkphp5)

## 目录结构

~~~
www  WEB部署目录（或者子目录）
├─application           应用目录
│  ├─common             公共模块目录（可以更改）
│  ├─module_name        模块目录
│  │  ├─config.php      模块配置文件
│  │  ├─common.php      模块函数文件
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  ├─view            视图目录
│  │  └─ ...            更多类库目录
│  │
│  ├─command.php        命令行工具配置文件
│  ├─common.php         公共函数文件
│  ├─config.php         公共配置文件
│  ├─route.php          路由配置文件
│  ├─tags.php           应用行为扩展定义文件
│  └─database.php       数据库配置文件
│
├─public                WEB目录（对外访问目录）
│  ├─index.php          入口文件
│  ├─router.php         快速测试文件
│  └─.htaccess          用于apache的重写
│
├─thinkphp              框架系统目录
│  ├─lang               语言文件目录
│  ├─library            框架类库目录
│  │  ├─think           Think类库包目录
│  │  └─traits          系统Trait目录
│  │
│  ├─tpl                系统模板目录
│  ├─base.php           基础定义文件
│  ├─console.php        控制台入口文件
│  ├─convention.php     框架惯例配置文件
│  ├─helper.php         助手函数文件
│  ├─phpunit.xml        phpunit配置文件
│  └─start.php          框架入口文件
│
├─extend                扩展类库目录
├─runtime               应用的运行时目录（可写，可定制）
├─vendor                第三方类库目录（Composer依赖库）
├─build.php             自动生成定义文件（参考）
├─composer.json         composer 定义文件
├─LICENSE.txt           授权说明文件
├─README.md             README 文件
├─think                 命令行入口文件
~~~
