<?php
namespace V4\Model;

/**
 * 摇一摇
 * Enter description here ...
 * @author Administrator
 *
 */
class ShakeModel extends BaseModel {
    
	/**
	 * 发布摇一摇
	 * 
	 * @param array $post 待发布摇一摇数据
	 * @param int $user_id 发布者用户ID
	 * @param decimal $shake_amount 摇一摇金额
	 * @param string $shake_ranges 摇一摇距离
	 * @param int $shake_times 摇一摇次数
	 * @param string $pic 摇中提示图片
	 * @param string $currency 货币类型
	 * @param string $currencyaction 操作类型
	 * 
	 * @return boolean
	 */
    public function publish_shake($post, $user_id, $shake_amount, $shake_ranges, $shake_times, $pic, $currency, $currencyaction){
        
        //1.一摇记录
        $vo['user_id'] = $user_id; 
        $vo['shake_amount'] = $shake_amount; 
        $vo['shake_ranges'] = $shake_ranges; 
        $vo['shake_times'] = $shake_times; 
        $vo['shake_img'] = $pic; 
        $vo['shake_lng'] = $post['store']['longitude']; 
        $vo['shake_lat'] = $post['store']['latitude']; 
        $vo['shake_addtime'] = time(); 
        $vo['shake_status'] = 2;
        //回本设置
        $params = M('g_parameter', null)->find();
        $vo['shake_refund_rate'] = $params['shake_return_bei'];
        $vo['shake_refund_amount'] = $shake_amount*$params['shake_return_bei']/$params['shake_return_day'];
        $vo['shake_refund_times'] = $params['shake_return_day'];
        
        $res1 = M('shake')->add($vo);
        
        //2.增加明细
        $arm = new AccountRecordModel();
        $res2 = $arm->add($user_id, $currency, $currencyaction, -$shake_amount, $arm->getRecordAttach(1, '系统'), '发布摇一摇');
        
        //吊起存储过程
    	$pm = new ProcedureModel();
    	$res5 = $pm->execute('V51_Bonus_uniondelivery', $res1, '@error');
        if($res1 !== false && $res2 !== false && $res5){
            return true;
        }else{
            return false;
        }
    } 
    
    /**
     * 获取摇一摇列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getShakeList($fields='*', $page=1, $listRows=10, $actionwhere='') {
    	//当$page为false时不分页
    	$_totalRows = 0;
    	if ($page !== false) {
    		$_totalRows = M('shake')->where($actionwhere)->count(0);
    	}
    		
    	$list = M('shake')->field($fields)->where($actionwhere);
    		
    	if ($page !== false) {
    		$list = $list->page($page, $listRows);
    	}
    
    	$list = $list->order('shake_id desc')->select();
    
    	return [
	    	'paginator' => $this->paginator($_totalRows, $listRows),
	    	'list' => $list,
    	];
    
    }
    
    /**
     * 获取摇一摇记录列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getShakeRecordsList($fields='*', $page=1, $listRows=10, $actionwhere='') {
    	//当$page为false时不分页
    	$_totalRows = 0;
    	if ($page !== false) {
    		$_totalRows = M('shake_records')->where($actionwhere)->count(0);
    	}
    
    	$list = M('shake_records')->field($fields)->where($actionwhere);
    
    	if ($page !== false) {
    		$list = $list->page($page, $listRows);
    	}
    
    	$list = $list->order('records_id desc')->select();
    
    	return [
	    	'paginator' => $this->paginator($_totalRows, $listRows),
	    	'list' => $list,
    	];
    
    }
    
    /**
     * 获取摇一摇回本记录列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getShakeRefundList($fields='*', $page=1, $listRows=10, $actionwhere='') {
    	//当$page为false时不分页
    	$_totalRows = 0;
    	if ($page !== false) {
    		$_totalRows = M('shake_refund')->where($actionwhere)->count(0);
    	}
    
    	$list = M('shake_refund')->field($fields)->where($actionwhere);
    
    	if ($page !== false) {
    		$list = $list->page($page, $listRows);
    	}
    
    	$list = $list->order('refund_id desc')->select();
    
    	return [
	    	'paginator' => $this->paginator($_totalRows, $listRows),
	    	'list' => $list,
    	];
    
    }
    
}