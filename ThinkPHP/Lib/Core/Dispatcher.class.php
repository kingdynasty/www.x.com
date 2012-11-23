<?php
//TODO 此文件未更改
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/**
 * ThinkPHP内置的Dispatcher类
 * 完成URL解析、路由和调度
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 */
class Dispatcher {

    /**
     * URL映射到控制器
     * @access public
     * @return void
     */
    static public function dispatch() {
        $urlMode  =  C('url_model');
        if(!empty($_GET[C('var_pathinfo')])) { // 判断URL里面是否有兼容模式参数
            $_SERVER['PATH_INFO']   = $_GET[C('var_pathinfo')];
            unset($_GET[C('var_pathinfo')]);
        }
        if($urlMode == URL_COMPAT ){
            // 兼容模式判断
            define('PHP_FILE',_PHP_FILE_.'?'.C('var_pathinfo').'=');
        }elseif($urlMode == URL_REWRITE ) {
            //当前项目地址
            $url    =   dirname(_PHP_FILE_);
            if($url == '/' || $url == '\\')
                $url    =   '';
            define('PHP_FILE',$url);
        }else {
            //当前项目地址
            define('PHP_FILE',_PHP_FILE_);
        }

        // 开启子域名部署
        if(C('app_sub_domain_deploy')) {
            $rules      = C('app_sub_domain_rules');
            $subDomain  = strtolower(substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],'.')));
            define('SUB_DOMAIN',$subDomain); // 二级域名定义
            if($subDomain && isset($rules[$subDomain])) {
                $rule =  $rules[$subDomain];
            }elseif(isset($rules['*'])){ // 泛域名支持
                if('www' != $subDomain && !in_array($subDomain,C('app_sub_domain_deny'))) {
                    $rule =  $rules['*'];
                }
            }
            if(!empty($rule)) {
                // 子域名部署规则 '子域名'=>array('模块名/[控制器名]','var1=a&var2=b');
                $array  =   explode('/',$rule[0]);
                $controler =   array_pop($array);
                if(!empty($controler)) {
                    $_GET[C('var_controler')]  =   $controler;
                    $domainControler           =   true;
                }
                if(!empty($array)) {
                    $_GET[C('var_module')]   =   array_pop($array);
                    $domainModule            =   true;
                }
                if(isset($rule[1])) { // 传入参数
                    parse_str($rule[1],$parms);
                    $_GET   =  array_merge($_GET,$parms);
                }
            }
        }
        // 分析PATHINFO信息
        if(empty($_SERVER['PATH_INFO'])) {
            $types   =  explode(',',C('url_pathinfo_fetch'));
            foreach ($types as $type){
                if(0===strpos($type,':')) {// 支持函数判断
                    $_SERVER['PATH_INFO'] =   call_user_func(substr($type,1));
                    break;
                }elseif(!empty($_SERVER[$type])) {
                    $_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type],$_SERVER['SCRIPT_NAME']))?
                        substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME']))   :  $_SERVER[$type];
                    break;
                }
            }
        }
        $depr = C('url_pathinfo_depr');
        if(!empty($_SERVER['PATH_INFO'])) {
            tag('path_info');
            $part =  pathinfo($_SERVER['PATH_INFO']);
            define('__EXT__', isset($part['extension'])?strtolower($part['extension']):'');
            if(C('url_html_suffix')) {
                $_SERVER['PATH_INFO'] = preg_replace('/\.('.trim(C('url_html_suffix'),'.').')$/i', '', $_SERVER['PATH_INFO']);
            }elseif(__EXT__) {
                $_SERVER['PATH_INFO'] = preg_replace('/.'.__EXT__.'$/i','',$_SERVER['PATH_INFO']);
            }
            if(!self::routerCheck()){   // 检测路由规则 如果没有则按默认规则调度URL
                $paths = explode($depr,trim($_SERVER['PATH_INFO'],'/'));
                if(C('var_url_params')) {
                    // 直接通过$_GET['_URL_'][1] $_GET['_URL_'][2] 获取URL参数 方便不用路由时参数获取
                    $_GET[C('var_url_params')]   =  $paths;
                }
                $var  =  array();
                if (C('app_module_list') && !isset($_GET[C('var_module')])){
                    $var[C('var_module')] = in_array(strtolower($paths[0]),explode(',',strtolower(C('app_module_list'))))? array_shift($paths) : '';
                    if(C('app_module_deny') && in_array(strtolower($var[C('var_module')]),explode(',',strtolower(C('app_module_deny'))))) {
                        // 禁止直接访问模块
                        exit;
                    }
                }
                if(!isset($_GET[C('var_controler')])) {// 还没有定义控制器名称
                    $var[C('var_controler')]  =   array_shift($paths);
                }
                $var[C('var_action')]  =   array_shift($paths);
                // 解析剩余的URL参数
                preg_replace('@(\w+)\/([^\/]+)@e', '$var[\'\\1\']=strip_tags(\'\\2\');', implode('/',$paths));
                $_GET   =  array_merge($var,$_GET);
            }
            define('__INFO__',$_SERVER['PATH_INFO']);
        }

        // 获取模块 控制器和操作名称
        if (C('app_module_list')) {
            define('MODULE_NAME', self::getModule(C('var_module')));
        }
        define('CONTROLER_NAME',self::getControler(C('var_controler')));
        define('ACTION_NAME',self::getAction(C('var_action')));
        // URL常量
        define('__SELF__',strip_tags($_SERVER['REQUEST_URI']));
        // 当前项目地址
        define('__APP__',strip_tags(PHP_FILE));
        // 当前控制器和模块地址
        if(defined('MODULE_NAME')) {
            define('__MODULE__',(!empty($domainModule) || strtolower(MODULE_NAME) == strtolower(C('default_module')) )?__APP__ : __APP__.'/'.MODULE_NAME);
            define('__URL__',!empty($domainControler)?__MODULE__.$depr : __MODULE__.$depr.CONTROLER_NAME);
        }else{
            define('__URL__',!empty($domainControler)?__APP__.'/' : __APP__.'/'.CONTROLER_NAME);
        }
        // 当前操作地址
        define('__ACTION__',__URL__.$depr.ACTION_NAME);
        //保证$_REQUEST正常取值
        $_REQUEST = array_merge($_POST,$_GET);
    }

    /**
     * 路由检测
     * @access public
     * @return void
     */
    static public function routerCheck() {
        $return   =  false;
        // 路由检测标签
        tag('route_check',$return);
        return $return;
    }

    /**
     * 获得实际的控制器名称
     * @access private
     * @return string
     */
    static private function getControler($var) {
        $controler = (!empty($_GET[$var])? $_GET[$var]:C('default_controler'));
        unset($_GET[$var]);
        if($maps = C('URL_MODULE_MAP')) {
            if(isset($maps[strtolower($module)])) {
                // 记录当前别名
                define('MODULE_ALIAS',strtolower($module));
                // 获取实际的模块名
                return   $maps[MODULE_ALIAS];
            }elseif(array_search(strtolower($module),$maps)){
                // 禁止访问原始模块
                return   '';
            }
        }        
        if(C('url_case_insensitive')) {
            // URL地址不区分大小写
            // 智能识别方式 index.php/user_type/index/ 识别到 UserTypeAction 控制器
            $controler = ucfirst(parse_name($controler,1));
        }
        return strip_tags($controler);
    }

    /**
     * 获得实际的操作名称
     * @access private
     * @return string
     */
    static private function getAction($var) {
        $action   = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var])?$_GET[$var]:C('default_action'));
        unset($_POST[$var],$_GET[$var]);
        if($maps = C('URL_ACTION_MAP')) {
            if(isset($maps[strtolower(MODULE_NAME)])) {
                $maps =   $maps[strtolower(MODULE_NAME)];
                if(isset($maps[strtolower($action)])) {
                    // 记录当前别名
                    define('ACTION_ALIAS',strtolower($action));
                    // 获取实际的操作名
                    return   $maps[ACTION_ALIAS];
                }elseif(array_search(strtolower($action),$maps)){
                    // 禁止访问原始操作
                    return   '';
                }
            }
        }        
        return strip_tags(C('url_case_insensitive')?strtolower($action):$action);
    }

    /**
     * 获得实际的模块名称
     * @access private
     * @return string
     */
    static private function getModule($var) {
        $module   = (!empty($_GET[$var])?$_GET[$var]:C('default_module'));
        unset($_GET[$var]);
        return strip_tags(C('url_case_insensitive') ?ucfirst(strtolower($module)):$module);
    }

}