<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 通用模型继承类
// +----------------------------------------------------------------------
namespace Common\Model;
use Think\Model;

class CommonModel extends Model {
	
	/**
	 * 获取最大ID
	 */
    public function maxId() {
		$list = $this->lock(true)->field('id')->order('id desc')->limit(1)->find();
		$maxid = count($list)>0 ? $list['id']+1 : 1;
		return $maxid;
	}
	
	/**
	 * 获取当前插入值的ID(用于无主键ID)
	 */
	public function getCurrentId() {
		$list = $this->lock(true)->field('id')->order('id desc')->limit(1)->find();
		$current_id = count($list)>0 ? $list['id'] : 1;
		return $current_id;
	}
	
	/**
	 * 搭配后台管理权限进行扩展条件筛选  只适用于后台,不适用于接口
	 * @override where
	 * @param $where mixed 默认where筛选(默认不启用)
	 * @param $parse mixed 默认预处理参数
	 * @param $alias string 数据表别名
	 * @param $field string 筛选条件字段
	 * @param $model string 模型名(主要针对member会员表中扩展字段匹配使用,如:school_id,dept_id,...)
	 */
	public function where($where=false, $parse=null, $alias='', $field, $model='school') {
		if ($where) {
			parent::where($where, $parse);
		}
		
		//判断是否为Admin模块,如果是,并且$where为false时,则执行扩展条件筛选
		if (MODULE_NAME == 'Admin' && !$where) {
			$sess_auth = session(C('AUTH_SESSION'));
			if ($sess_auth['account'] != 'admin') {
				$alias = empty($alias) ? '' : $alias.'.';
				switch ($model) {
					case 'school':
						if ($sess_auth['school_id'] > 0) {
							$map[$alias.$field] = array('eq', $sess_auth['school_id']);
							parent::where($map);
						}
						break;
					case 'department':
						if ($sess_auth['dept_id'] > 0) {
							$map[$alias.$field] = array('eq', $sess_auth['dept_id']);
							parent::where($map);
						}
						break;
				}
			}
		}
		
		return $this;
	}
	
}
?>