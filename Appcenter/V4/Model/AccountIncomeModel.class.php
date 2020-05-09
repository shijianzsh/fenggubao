<?php

namespace V4\Model;

/**
 * 用户财务统计
 */
class AccountIncomeModel extends BaseModel {


	private static $_instance;

	/**
	 * 单例-获取new对象
	 */
	public static function getInstance() {
		if ( ! ( self::$_instance instanceof self ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * 动态生成数据表
	 *
	 * @param \V4\Model\Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
	 *
	 * @return string
	 */
	private function getTableName( $tag = 0 ) {
		$_tableName = 'account_income';
		//if ($tag >= 20170600) {
		//    $_tableName .= '_' . substr($tag . '', 0, 6);
		//}
		return $_tableName;
	}

	/**
	 * 动态生成数据模块
	 *
	 * @param \V4\Model\Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
	 *
	 * @return type 返回兑换记录数据Model
	 */
	protected function M( $tag = 0 ) {
		return M( $this->getTableName( $tag ) );
	}

	/**
	 * 获取用户收益统计数据
	 *
	 * @param type $user_id 用户ID
	 * @param type $fields 读取字段
	 * @param \V4\Model\Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
	 *
	 * @return array | false 返回结果数据 或 false(无结果)
	 */
	public function getItemById( $user_id, $income_tag ) {
		$item = $this->M( $income_tag )
		             ->where( '`user_id`=%d AND `income_tag`=%d', [ $user_id, $income_tag ] )
		             ->order( 'income_id desc' )
		             ->find();

		return $item;
	}


	/**
	 * 获取用户收益统计数据
	 *
	 * @param type $user_id 用户ID
	 * @param type $fields 读取字段
	 * @param \V4\Model\Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
	 *
	 * @return array | false 返回结果数据 或 false(无结果)
	 */
	public function getItemByUserId( $user_id, $fields = '*', $tag = 0 ) {
		$item = $this->M( $tag )
		             ->where( '`user_id`=%d AND `income_tag`=%d', [ $user_id, $tag ] )
		             ->field( $fields )
		             ->order( 'income_id desc' )
		             ->find();

		return $item;
	}

	/**
	 * 获取每月列表
	 * Enter description here ...
	 *
	 * @param $user_id
	 * @param $fields
	 * @param $tag
	 */
	public function getListMonthByUserId( $user_id, $fields = '*' ) {
		$ww['user_id']    = $user_id;
		$ww['income_tag'] = array( array( 'elt', date( 'Ym' ) ), array( 'egt', 201703 ) );
		$list             = $this->M()->field( $fields )->where( $ww )->order( 'income_tag desc' )->select();
		foreach ( $list as $k => $v ) {
			$list[ $k ]['year'] = substr( $v['income_tag'], 0, 4 );
			$list[ $k ]['day']  = substr( $v['income_tag'], 4, 2 );
		}

		return $list;
	}


	/**
	 * 按月查询
	 *
	 * @param $user_id
	 * @param $fields
	 * @param $month
	 */
	public function getListByUserId( $user_id, $fields = '*', $month ) {
		$startday = $month . '01';
		$endday   = date( 'Ym', strtotime( '+1 month', strtotime( substr( $month, 0, 4 ) . '-' . substr( $month, 4, 2 ) ) ) ) . '01';

		$ww['user_id']    = $user_id;
		$ww['income_tag'] = array( array( 'lt', $endday ), array( 'egt', $startday ) );

		$list = $this->M( $month * 100 )->field( $fields )->where( $ww )->order( 'income_tag asc' )->select();
		foreach ( $list as $k => $v ) {
			$list[ $k ]['day'] = substr( $v['income_tag'], 0, 4 ) . '-' . substr( $v['income_tag'], 4, 2 ) . '-' . substr( $v['income_tag'], 6, 8 );

			$list[ $k ]['isclick'] = 1;
			unset( $list[ $k ]['income_uptime'] );
		}

		return $list;
	}

	/**
	 * 获取用户所有收益统计数据（有初始值）
	 *
	 * @param type $user_id 用户ID
	 * @param \V4\Model\Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
	 *
	 * @return array 返回结果数据
	 */
	public function getIncome( $user_id, $tag = 0 ) {
		$fields = '*';
		$item   = $this->getItemByUserId( $user_id, $fields, $tag );
		if ( ! $item ) {
			$item = [
				'user_id'                       => $user_id,
				'income_cash_merchant'          => 0,
				'income_cash_recommend'         => 0,
				'income_cash_consume'           => 0,
				'income_cash_partner_subsidy'   => 0,
				'income_cash_service_subsidy'   => 0,
				'income_cash_company_subsidy'   => 0,
				'income_cash_performance'       => 0,
				'income_cash_bonus'             => 0,
				'income_cash_total'             => 0,
				'income_goldcoin_register_give' => 0,
				'income_goldcoin_consume_give'  => 0,
				'income_goldcoin_checkin'       => 0,
				'income_goldcoin_total'         => 0,
				'income_points_register_give'   => 0,
				'income_points_consume_give'    => 0,
				'income_goldcoin_total'         => 0,
				'income_points_total'           => 0,
				'income_total'                  => 0,
				'income_tag'                    => $tag,
				'income_uptime'                 => 0,
			];
		}

		return $item;
	}

	/**
	 * 获取用户财务统计记录ID
	 *
	 * @param type $user_id
	 * @param \V4\Model\Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
	 */
	public function getIncomeIdByTag( $user_id, $tag = 0 ) {
		return $this->M( $tag )->where( [ 'user_id' => $user_id, 'income_tag' => $tag ] )->getField( 'income_id' );
	}

	public function getIncomeByTag( $user_id, $field, $tag = 0 ) {
		return $this->M( $tag )->where( [ 'user_id' => $user_id, 'income_tag' => $tag ] )->getField( $field );
	}

	/**
	 * 获取上周管理津贴通证汇总
	 * Enter description here ...
	 *
	 * @param int $user_id
	 * @param array $timearray
	 */
	public function getLastWeekSM( $user_id, $timearray ) {
		$_baseName = 'account_income';
		$table[0]  = $_baseName;
//        if($timearray[3] >= 20170600){
//            if($timearray[0] != $timearray[1]){
//                //跨表
//                $table[0] = $_baseName.'_'.$timearray[0];
//                $table[1] = $_baseName.'_'.$timearray[1];
//            }else{
//                //不跨表
//                $table[0] = $_baseName.'_'.$timearray[0];
//            }
//        }else{
//            //不分表
//            $table[0] = $_baseName;
//        }

		$where['user_id']    = $user_id;
		$where['income_tag'] = array( array( 'egt', $timearray[2] ), array( 'elt', $timearray[3] ) );
		$list1               = M( $table[0] )->field( $this->income_manage_fields )->where( $where )->select();
		if ( ! empty( $table[1] ) ) {
			$list2 = M( $table[1] )->field( $this->income_manage_fields )->where( $where )->select();
			$list1 = array_merge( $list1, $list2 );
		}
		$amount = 0;
		foreach ( $list1 as $row ) {
			$amount += array_sum( $row );
		}

		return $amount;
	}


	/**
	 * 按月/天查询所有用户收益数据
	 *
	 * @param string $fields
	 * @param int $month
	 * @param int $page 当前页码,当为false时不分页(此时$listRows参数无效)
	 * @param int $listRows 分页大小
	 * @param string $actionwhere 筛选条件
	 * @param string $group 分组条件(默认acf.income_tag)
	 *
	 * @internal 此方法所有字段均需附加别名(默认前缀为acf.)
	 */
	public function getListByAllUser( $fields = 'acf.*', $month, $page = 1, $listRows = 10, $actionwhere = '', $group = 'acf.income_tag' ) {
		$where = ' 1 ';
		$where .= $actionwhere;

		//当$page为false时不分页
		$_totalRows = 0;
		if ($page !== false) {
			$_totalRows = $this->M( $month * 100 )
			                   ->alias( 'acf' )
			                   ->join( 'JOIN __MEMBER__ mem ON mem.id=acf.user_id' )
			                   ->where( $where )
			                   ->group( $group )
			                   ->field( 'acf.income_id' )
			                   ->select();
			$_totalRows = count( $_totalRows );
		}

		$list = $this->M( $month * 100 )
		             ->alias( 'acf' )
		             ->join( 'JOIN __MEMBER__ mem ON mem.id=acf.user_id' )
		             ->field( $fields )
		             ->where( $where )
		             ->group( $group )
		             ->order( 'acf.income_tag desc' );

		if ($page !== false) {
			$list = $list->page( $page, $listRows );
		}

		$list = $list->select();

		return [
			'paginator' => $this->paginator( $_totalRows, $listRows ),
			'list'      => $list,
		];
	}


	/**
	 * 获取指定条件的指定字段的值
	 *
	 * @internal 主要用于获取字段累计数据，此方法使用的为find()查询
	 *
	 * @param string $fileds 读取字段,需要加alias前缀acf.
	 * @param string $actionwhere 筛选条件
	 * @param int $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
	 *
	 * @return mixed
	 */
	public function getFieldsValues( $fields = 'acf.*', $actionwhere = '', $tag ) {
		$info = $this->M( $tag )->alias( 'acf' )
		             ->join( 'join __MEMBER__ mem ON mem.id=acf.user_id' )//此处强制关联可能会导致特殊情况下比如某个用户被强制删除后出现此处查询sum金额与统计数据金额不符的情况
		             ->where( $actionwhere )
		             ->field( $fields )
		             ->find();

		return $info;
	}

}
