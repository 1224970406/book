<?php
// +----------------------------------------------------------------------
// | KyxsCMS [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018~2019 http://www.kyxscms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kyxscms
// +----------------------------------------------------------------------

namespace app\api\controller;
use think\Controller;
use think\facade\Config;
use think\Db;
use net\Gather;

class Source extends Controller{

	public function index($id){
		ignore_user_abort(true);
		$chapter=Db::name('novel_chapter')->field('id,novel_id,collect_id,reurl,chapter')->where([['novel_id','=',$id],['collect_id','<>',0]])->whereTime('run_time', '<=', time())->lock(true)->select();
		if($chapter){
			$novel_update=false;
			foreach ($chapter as $key => $value) {
				$chapter_update=false;
				$data_link=[];
				Db::name('novel_chapter')->where(['id'=>$value['id']])->update(['run_time'=>time()+Config::get('web.chapter_time_interval')*60]);
				$chapter_collect=Gather::get_chapter($value['collect_id'],$value['reurl']);
				if($chapter_collect){
					if(!empty($value['chapter'])){
						$chapter_db=[];
						$value['chapter']=model('common/api')->decompress_chapter($value['chapter']);
		        		$value['chapter']=json_decode($value['chapter'],true);
						$chapter_db=array_column($value['chapter'],'title','reurl');
					}
					foreach ($chapter_collect as $k => $v){
						if(isset($chapter_db[$v['url']])){
							continue;
						}
						$chapter_data=[];
		                $keys=uniqidReal();
		                $chapter_data['title']=$v["title"];
		                $chapter_data['intro']='';
		                $chapter_data['update_time']=time();
		                $chapter_data['issued']=1;
		                $chapter_data['word']=2000;
		                $chapter_data['reurl']=$v["url"];
		                $chapter_data['auto']=2;
		                $chapter_data['path']=$value['novel_id'].DIRECTORY_SEPARATOR.$keys.'.txt';
		                $value['chapter'][$keys]=$chapter_data;
		                $chapter_update=true;
		                $novel_update=true;
		                $data_link[]=url('home/chapter/index',['id'=>$value['id'],'key'=>$keys]);
					}
					if($chapter_update==true){
						Db::name('novel_chapter')->where(['id'=>$value['id']])->update(['run_time'=>time()+Config::get('web.chapter_time_interval_over')*60]);
						$value['chapter']=json_encode($value['chapter']);
		        		$value['chapter']=model('common/api')->compress_chapter($value['chapter']);
						Db::name('novel_chapter')->update($value);
						model('common/DataOperation')->after('add','chapter',$data_link);
					}
				}
			}
			if($novel_update==true){
				Db::name('novel')->where(['id'=>$id])->update(['update_time'=>time()]);
				rm_cache($id,'novel',false);
			}
			return $novel_update;
		}
		return false;
	}

}