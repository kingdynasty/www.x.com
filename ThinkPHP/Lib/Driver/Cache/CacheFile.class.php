<?php
defined('THINK_PATH') or exit();
/**
 * 文件类型缓存类
 * @category   Think
 * @package  Think
 * @subpackage  Driver.Cache
 * @author    liu21st <liu21st@gmail.com>
 */
class CacheFile extends Cache {
    /**
     * 架构函数
     * @access public
     */
    public function __construct($options=array()) {
        if(!empty($options)) {
            $this->options =  $options;
        }
        $this->options['cache']      =   !empty($options['cache'])?   $options['cache']    :   C('data_cache_path');
        $this->options['prefix']    =   isset($options['prefix'])?  $options['prefix']  :   C('data_cache_prefix');
        $this->options['expire']    =   isset($options['expire'])?  $options['expire']  :   C('data_cache_time');
        $this->options['datatype']    =   isset($options['datatype'])?  $options['datatype']  :   C('data_cache_datatype');
        $this->options['length']    =   isset($options['length'])?  $options['length']  :   0;
        if(substr($this->options['cache'], -1) != '/')    $this->options['cache'] .= '/';
        $this->connected = is_dir($this->options['cache']) && is_writeable($this->options['cache']);
        $this->init();
    }

    /**
     * 初始化检查
     * @access private
     * @return boolen
     */
    private function init() {
        $stat = stat($this->options['cache']);
        $dir_perms = $stat['mode'] & 0007777; // Get the permission bits.
        $file_perms = $dir_perms & 0000666; // Remove execute bits for files.
        // 创建项目缓存目录
        if (!is_dir($this->options['cache'])) {
            if (!  mkdir($this->options['cache']))
                return false;            
             chmod($this->options['cache'], $dir_perms);
        }
    }

    /**
     * 是否连接
     * @access public
     * @return boolen
     */
    private function isConnected() {
        return $this->connected;
    }

    /**
     * 取得变量的存储文件名
     * @access private
     * @param string $name 缓存变量名
     * @return string
     */
    private function filename($name,$module=MODULE_NAME) {
    	if(empty($module) || is_null($module)) $module = ucfirst(MODULE_NAME);
        return $this->options['cache'].$module.'/'.$name.'.php';
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name,$module) {
        $filename   =   $this->filename($name,$module);
        if (!$this->isConnected() || !is_file($filename)) {
           return false;
        }
        N('cache_read',1);
        $content    =   file_get_contents($filename);
        if( false !== $content) {
            $expire  =  (int)substr($content,8, 12);//从第一个“0”开始算起000000000000
            if($expire != 0 && time() > filemtime($filename) + $expire) {
                //缓存过期删除缓存文件
                unlink($filename);
                return false;
            }
            if(C('data_cache_check')) {//开启数据校验
                $check  =  substr($content,20, 32);
                $content   =  substr($content,52, -3);
                if($check != md5($content)) {//校验错误
                    return false;
                }
            }else {
            	$content   =  substr($content,20, -3);
            }
            if(C('data_cache_compress') && function_exists('gzcompress')) {
                //启用数据压缩
                $content   =   gzuncompress($content);
            }
            if($this->options['datatype'] == 'array') {
		    	$content = @require($filename);
		    } elseif($this->_setting['type'] == 'serialize') {
            	$content = unserialize($content);
            }
            return $content;
        }
        else {
            return false;
        }
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param int $expire  有效时间 0为永久
     * @return boolen
     */
    public function set($name,$value,$expire=null,$module) {
        N('cache_write',1);
        if(is_null($expire)) {
            $expire =  $this->options['expire'];
        }
        //确定文件名
        $filename   =   $this->filename($name,$module);    
        //将值赋值给$data
        if ($this->options['datatype'] == 'array') {
        	$data    = "<?php\n//".sprintf('%012d',$expire)."\nreturn ".var_export($value, true).";";
        }
        elseif ($this->options['datatype'] == 'serialize'){
	        $data   =   serialize($value);
	        if( C('data_cache_compress') && function_exists('gzcompress')) {
	            //数据压缩
	            $data   =   gzcompress($data,3);
	        }
	        if(C('data_cache_check')) {//开启数据校验
	            $check  =  md5($data);
	        }else {
	            $check  =  '';
	        }
	        $data    = "<?php\n//".sprintf('%012d',$expire).$check.$data."\n?>";
        }
        //将缓存数据存入数据库
        if ($module == 'Commons' || ($module == 'Commons' && substr($name, 0, 11) != 'cat_content')) {
        	$db = M('Cache');
        	$datas = new_addslashes($data);
        	if ($db->where(array('filename'=>$filename))->find()) {
        		$db->where(array('filename'=>$filename))->data(array('data'=>$datas))->save();
        	} else {
        		$db->data(array('filename'=>$filename,'data'=>$datas))->add();
        	}
        }        
        //是否开启互斥锁
        if(C('lock_ex'))  {
        	$result = file_put_contents($filename, $data, LOCK_EX);
        } else {
        	$result  =   file_put_contents($filename,$data);
        }        
        if($result) {
            if($this->options['length']>0) {
                // 记录缓存队列
                $this->queue($name);
            }
            clearstatcache();
            return true;
        }else {
            return false;
        }
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolen
     */
    public function rm($name,$module) {
    	$filename = $this->filename($name,$module);
    	if(file_exists($filename)) {
    		if ($module == 'Commons' && substr($name, 0, 11) != 'cat_content') {
    			$db = M('Cache');
    			$db->where(array('filename'=>$filename))->delete();
    		}
    		return unlink($filename) ? true : false;
    	} else {
    		return false;
    	}
    }

    /**
     * 清除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolen
     */
    public function clear() {
        $path   =  $this->options['cache'];
        if ( $dir = opendir( $path ) ) {
            while ( $file = readdir( $dir ) ) {
                $check = is_dir( $file );
                if ( !$check )
                    unlink( $path . $file );
            }
            closedir( $dir );
            return true;
        }
    }
}