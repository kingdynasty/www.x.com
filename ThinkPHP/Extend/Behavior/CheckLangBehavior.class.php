<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();
/**
 * 语言检测 并自动加载语言包
 * @category   Extend
 * @package  Extend
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class CheckLangBehavior extends Behavior {
    // 行为参数定义（默认值） 可在项目配置中覆盖
    protected $options   =  array(
            'LANG_SWITCH_ON'        => false,   // 默认关闭语言包功能
            'LANG_AUTO_DETECT'      => true,   // 自动侦测语言 开启多语言功能后有效
            'LANG_LIST'             => 'zh-cn', // 允许切换的语言列表 用逗号分隔
            'VAR_LANGUAGE'          => 'l',		// 默认语言切换变量
        );

    // 行为扩展的执行入口必须是run
    public function run(&$params){
        // 开启静态缓存
        $this->checkLanguage();
    }

    /**
     * 语言检查
     * 检查浏览器支持语言，并自动加载语言包
     * @access private
     * @return void
     */
    private function checkLanguage() {
        // 不开启语言包功能，仅仅加载框架语言文件直接返回
        if (!C('lang_switch_on')){
            return;
        }
        $langSet = C('default_lang');
        // 启用了语言包功能
        // 根据是否启用自动侦测设置获取语言选择
        if (C('lang_auto_detect')){
            if(isset($_GET[C('var_language')])){
                $langSet = $_GET[C('var_language')];// url中设置了语言变量
                cookie('think_language',$langSet,3600);
            }elseif(cookie('think_language')){// 获取上次用户的选择
                $langSet = cookie('think_language');
            }elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){// 自动侦测浏览器语言
                preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                $langSet = $matches[1];
                cookie('think_language',$langSet,3600);
            }
            if(false === stripos(C('lang_list'),$langSet)) { // 非法语言参数
                $langSet = C('default_lang');
            }
        }
        // 定义当前语言
        define('LANG_SET',strtolower($langSet));
        // 读取项目公共语言包
        if (is_file(LANG_PATH.LANG_SET.'/common.php'))
            L(include LANG_PATH.LANG_SET.'/common.php');
        $module = '';
        $lang_path    =   C('app_module_mode')==1 ? BASE_LIB_PATH.'Lang/'.LANG_SET.'/' : LANG_PATH.LANG_SET.'/';        
        // 读取当前模块公共语言包
        if (defined('MODULE_NAME')){
            if (is_file($lang_path.MODULE_NAME.'.php'))
                L(include $lang_path.MODULE_NAME.'.php');
            $module = MODULE_NAME.C('tmpl_file_depr');
        }
        // 读取当前控制器语言包
        if (is_file($lang_path.$module.strtolower(CONTROLER_NAME).'.php'))
            L(include $lang_path.$module.strtolower(CONTROLER_NAME).'.php');
    }
}