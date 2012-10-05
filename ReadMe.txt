2012.10.04——TPCMS项目备忘录
########1.版本及项目架构
	（1）ThinkPHP版本，ThinkPHP3.1，http://thinkphp.cn/down/236.html
	（2）PHPCMS版本，Phpcms V9.2.00 Release 20120928 
	（3）约定：将ThinkPHP统一为分组模式，不存在单一模块，即：index.php?m=ModuleName&c=ControlerName&a=ActionName
	（3）初始目录架构：
	  ├─index.php
	  ├─admin.php
	  ├─api.php
	  ├─ThinkPHP //ThinkPHP核心框架
	  ├─Apps //TPCMS模块
	  │   ├─Lib
	  │   ├─Common
	  │   ├─Conf	//$Key全部小写以区别于常量【另外，所有对应C(parameter)方法其参数都变为小写】
	  │   ├─Lang
	  │   ├─Runtime
	  │   ├─Model
	  │   ├─Tpl
	  │   ├─Plugin
	  │   └─Modules
	  ├─Api  //TPCMS的API接口
	  ├─Statics
	  │   ├─CSS
	  │   ├─Js
	  │   └─Images
	  ├─Html //网站内容静态文件
	  ├─Uploadfiles  //网站用户上传目录
	  └─Install //程序安装引导目录
	（4）确定了基本架构后，则令此结构不再改变，后面创建Module的时候，动态生成Module内容
########2.重要替换说明
	（1）分组（Group）/模块（Module）/操作（Action）---->模块（Module）/控制器（Controler）/操作（Action）
	（2）以上操作，注意不能包括ThinkPHP/Extend/Vendor目录，因为Vendor其下命名规则与ThinkPHP不一致
	（3）命名规则的替换是在PowerGrep下完成，不能进行diff分析及提交
########3.增加模块相关常量
	（1）App.class.php 增加MODULE_PATH相关常量
########3.修改common.php
	（1）A()函数
	（2）import()函数