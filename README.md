# typecho-accessBmob
AccessBmob 基于 Access 且使用 Bmob后端云 作数据库的访问统计插件

想法
====

我们都知道，typecho有一款很不错的访问插件access，类似于百度。
access 仓库是 `https://github.com/kokororin/typecho-plugin-Access`
Typecho Access 插件，提供简易的访客记录查看。获取访客信息，生成统计图表。

但是，当访客的数据很大时，会增加服务器响应时间，虽然前端异步可以解决。但是越来越多，是不利于轻量级博客的。

解决
====

然后，权那他，基于这个`access`进行了二开，使用 Bmob后端云 作数据库。这样随便产生多少数据，都不会影响博客。

昨天，权那他发布一一款`bmob`数据库插件，今天这个访问插件是基于`bmob`和`access` 的 `AccessBmob`插件。
`AccessBmob`，利用bmob，把数据丢在一边。

使用
====

 1. 到 https://www.bmob.cn/ 注册bmob账号
 2. 创建一个应用
 3. 在该应用后面，点击设置
 4. 进入页面后，在点击应用密匙，获取`Application ID`和`REST API Key`
 5. 然后安装`bmob`插件 https://github.com/kraity/typecho-bmob/archive/v1.0.zip
 6. 进行配置
 7. 然后安装`AccessBmob`插件 https://github.com/kraity/typecho-accessBmob/archive/v1.2.zip
 8. 注意，先配置好bmob插件，再启用accessbmob插件

然后，就可以使用了。
accessbmob基本保留了access的功能，去掉了和重构了部分。


再次声明
====

**再次声明**，先启用`Bmob`插件，配置后，再启用`AccessBmob`


归档
====

`Bmob` 插件: https://github.com/kraity/typecho-bmob
`AccessBmob` 插件： https://github.com/kraity/typecho-accessBmob

关于`Bmob`的很多基于开发，请到  Bmob后端云-改造成typecho数据库插件 https://krait.cn/major/2018.html
