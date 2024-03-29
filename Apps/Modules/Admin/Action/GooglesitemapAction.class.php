<?php
defined('APP_NAME') or exit('No permission resources.');
import('Admin','',0);
vendor('Pc.Form');

class GooglesitemapAction extends BaseAction {
	function __construct() {
		parent::__construct();
		$this->header = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n\t<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
	    $this->charset = "UTF-8";
	    $this->footer = "\t</urlset>\n";
	    $this->baidunewsFooter = "</document>";
		$this->items = array();
		$this->baidunewItems = array();
		//生成栏目级别选项
		$this->siteid = Admin::getSiteid();
		$this->categorys = cache('category_content_'.$this->siteid,'Commons');
	}
	
	function addItem2($new_item) {
        $this->items[] = $new_item;
    }
    
	function build( $file_name = null ) {
        $map = $this->header . "\n";
        foreach ($this->items AS $item){
            $map .= "\t\t<url>\n\t\t\t<loc>$item[loc]</loc>\n";
            $map .= "\t\t\t<lastmod>$item[lastmod]</lastmod>\n";
            $map .= "\t\t\t<changefreq>$item[changefreq]</changefreq>\n";
            $map .= "\t\t\t<priority>$item[priority]</priority>\n";
            $map .= "\t\t</url>\n\n";
        }
        $map .= $this->footer . "\n";
        if (!is_null($file_name)){
            	return file_put_contents($file_name, $map);
        	} else {
            	return $map;
        }
    }
  
	function googleSitemapItem($loc, $lastmod = '', $changefreq = '', $priority = '') {
		$data = array();
		$data['loc'] =  $loc;
		$data['lastmod'] =  $lastmod;
		$data['changefreq'] =  $changefreq;
		$data['priority'] =  $priority;
		return $data;
    } 
    /**
     * 
     * 百度新闻数组 组成
     * @param $title
     * @param $link
     * @param $description
     * @param $text
     * @param $image
     * @param $keywords
     * @param $category
     * @param $author
     * @param $source
     * @param $pubDate
     */
	function baidunewsItem($title, $link = '', $description = '',$text = '',$image = '', $keywords = '',$category = '',$author = '',$source='',$pubDate='') {
		$data = array();
		$data['title'] =  $title;
		$data['link'] =  $link;
		$data['description'] =  $description;
		$data['text'] =  $text;
		$data['image'] =  $image;
		$data['keywords'] =  $keywords;
		$data['category'] =  $category;
		$data['author'] =  $author;
		$data['source'] =  $source;
		$data['pubDate'] =  $pubDate;
		return $data;
    }
    
    function addBaidunewsItem($new_item){
    	 $this->baidunewItems[] = $new_item;
    }
    
	function baidunewsBuild( $file_name = null ,$this_domain,$email,$time) {
		//百度头部
			$this->baidunews = '';
			$this->baidunews = "<?xml version=\"1.0\" encoding=\"".CHARSET."\" ?>\n";
			$this->baidunews .= "<document>\n";
			$this->baidunews .= "<webSite>".$this_domain."</webSite>\n";
			$this->baidunews .= "<webMaster>".$email."</webMaster>\n";
			$this->baidunews .= "<updatePeri>".$time."</updatePeri>\n";
         	foreach ($this->baidunewItems AS $item){ 
				$this->baidunews .= "<item>\n";
				$this->baidunews .= "<title>".$item['title']."</title>\n";
				$this->baidunews .= "<link>".$item['link']."</link>\n";
				$this->baidunews .= "<description>".$item['description'] ."</description>\n";
				$this->baidunews .= "<text>".$item['text']."</text>\n";
				$this->baidunews .= "<image>".$item['image']."</image>\n";
				$this->baidunews .= "<keywords>".$item['keywords']."</keywords>\n";
				$this->baidunews .= "<category>".$item['category']."</category>\n";
				$this->baidunews .= "<author>".$item['author']."</author>\n";
				$this->baidunews .= "<source>".$item['source']."</source>\n";
				$this->baidunews .= "<pubDate>".$item['pubDate']."</pubDate>\n";
				$this->baidunews .= "</item>\n";
       	    } 
         $this->baidunews .= $this->baidunewsFooter . "\n";
         if (!is_null($file_name)){
            	return file_put_contents($file_name, $this->baidunews);
        	} else {
            	return $this->baidunews;
        }
    }
	  
	/**
	 * 
	 * Enter 生成google sitemap, 百度新闻协议
	 */
	function set () {
		$hits_db = M('Hits');
		$dosubmit = isset($_POST['dosubmit']) ? $_POST['dosubmit'] : $_GET['dosubmit'];
		
		//读站点缓存
		$siteid = $this->siteid;
		$sitecache = cache('sitelist','Commons');
		//根据当前站点,取得文件存放路径  		
 		$html_root = substr(C('html_root'), 1);
 		//判断当前站点目录,是PHPCMS则把文件写到根目录下, 不是则写到分站目录下.(分站目录用由静态文件路经html_root和分站目录dirname组成)
 		if($siteid==1){
 			$dir = CMS_PATH;
 		}else {
 			$dir = CMS_PATH.$html_root.'/'.$sitecache[$siteid]['dirname'].'/';
 		}
 		//模型缓存
 		$modelcache = cache('model','Commons');
 		
 		//获取当前站点域名,下面生成URL时会用到.
 		$this_domain = substr($sitecache[$siteid]['domain'], 0,strlen($sitecache[$siteid]['domain'])-1);
   		if($dosubmit) {
				//生成百度新闻
				if($_POST['mark']) {
 					$baidunum = $_POST['baidunum'] ? intval($_POST['baidunum']) : 20;
  					if($_POST['catids']=="")showmessage(L('choose_category'), HTTP_REFERER);
  					$catids = $_POST['catids'];
 					$catid_cache = $this->categorys;//栏目缓存
					$this->contentDb = D('Content');
 					foreach ($catids as $catid) {
 						$modelid = $catid_cache[$catid]['modelid'];//根据栏目ID查出modelid 进而确定表名,并结合栏目ID:catid 检索出对应栏目下的新闻条数
 						$this->contentDb->setModel($modelid);
 						$result = $this->contentDb->where(array('catid'=>$catid,'status'=>99))->limit($limit = "0,$baidunum")->order('id desc')->select();
 						//重设表前缀,for循环时用来查,文章正文 
 						$this->contentDb->tableName = $this->contentDb->tableName.'_data';
 						foreach ($result as $arr){
 							//把每一条数据都装入数组中
 							extract($arr);
 	 						if(!preg_match('/^(http|https):\/\//', $url)){
 								$url = $this_domain.$url;
							}
							if($thumb != ""){
								if(!preg_match('/^(http|https):\/\//', $thumb)){
									$thumb = $this_domain.$thumb;
								}
							}
							//取当前新闻模型 附属表 取 新闻正文
							$url = htmlspecialchars($url);
							$description = htmlspecialchars(strip_tags($description));
							//根据本条ID,从对应tablename_data取出正文内容
   							$content_arr = $this->contentDb->where(array('id'=>$id))->field('content')->find();
   							$content = htmlspecialchars(strip_tags($content_arr['content']));
   							//组合数据
   	 						$smi = $this->baidunewsItem($title,$url,$description,$content,$thumb, $keywords,$category,$author,$source,date('Y-m-d', $inputtime));//推荐文件
							$this->addBaidunewsItem($smi);
  						} 
 					}
  					$baidunews_file = $dir.'baidunews.xml';
  					
   					@mkdir($dir,0777,true);
 					$this->baidunewsBuild($baidunews_file,$this_domain,$_POST['email'],$_POST['time']); 
 			    }
			    
				//生成网站地图
				$content_priority = $_POST['content_priority'];
				$content_changefreq = $_POST['content_changefreq']; 
				$num = $_POST['num'] ? intval($_POST['num']) : 100;
				
				$today = date('Y-m-d');
 			    $domain = $this_domain;
 			    //生成地图头部　－第一条
				$smi = $this->googleSitemapItem($domain, $today, 'daily', '1.0');
     			$this->addItem2($smi);
     			
			    $this->contentDb = D('Content');
			    //只提取该站点的模型.再循环取数据,生成站点地图.
				$modelcache = cache('model','Commons');
 				$new_model = array();
				foreach ($modelcache as $modelid => $mod){
					if($mod['siteid']==$siteid){
						$new_model[$modelid]['modelid'] = $modelid;
						$new_model[$modelid]['name'] = $mod['name'];						
					}
 				}
				foreach($new_model as $modelid=>$m) {//每个模块取出num条数据 
					$this->contentDb->setModel($modelid);// 或者 $this->conetntDb->setModel($modelid);
					$result = $this->contentDb->where(array('status'=>99))->limit($limit = "0,$num")->order($order = 'inputtime desc')->select();
					foreach ($result as $arr){
						if(substr($arr['url'],0,1)=='/'){
							$url = htmlspecialchars(strip_tags($domain.$arr['url']));
						}else {
							$url = htmlspecialchars(strip_tags($arr['url']));
						}
						$hit_r = $hits_db->where(array('hitsid'=>'c-'.$modelid.'-'.$arr['id']))->find();
						if($hit_r['views']>1000) $content_priority = 0.9;
						$smi    = $this->googleSitemapItem($url, $today, $content_changefreq, $content_priority);//推荐文件
						$this->addItem2($smi);
					}
				}
				
			     $sm_file = $dir.'sitemaps.xml';
				 if($this->build($sm_file)){
					showmessage(L('create_success'), HTTP_REFERER);
			     } 
			} else { 
				$tree = vendor('Pc.Tree','',1);
				$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
				$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
				$categorys = array();
				foreach($this->categorys as $catid=>$r) {
					if($this->siteid != $r['siteid']) continue;
					if($r['type'] && $r['child']=='0'){//如果是单网页并且，没有子类了
						continue;
 					}
					if($modelid && $modelid != $r['modelid']) continue;
					$r['disabled'] = $r['child'] ? 'disabled' : '';
					$categorys[$catid] = $r;
				}
				$str  = "<option value='\$catid' \$selected \$disabled>\$spacer \$catname</option>";
				$tree->init($categorys);
				$string .= $tree->getTree(0, $str);
 				include Admin::adminTpl('googlesitemap');
			}
	}
	
	 
	
} 

?>