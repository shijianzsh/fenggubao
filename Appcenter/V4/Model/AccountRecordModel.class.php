<?php

namespace V4\Model;

use V4\Model\Currency;
use V4\Model\CurrencyAction;

/**
 * 用户兑换记录
 */
class AccountRecordModel extends BaseModel
{

    private static $_instance;

    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 动态生成数据表
     *
     * @param \V4\Model\Currency $currency 货币类型， 请使用\V4\Model\Currency提供的常量
     * @param string $suffix 表后缀， 规则： date('ym'), eg.: 1707， 缺省值：当前月
     *
     * @return string
     */
    private function getTableName($currency, $suffix = '')
    {
        $suffix = ($suffix != null && $suffix != '') ? $suffix : date('Ym');
        $_tableName = 'account_' . $currency . '_' . $suffix;

        return $_tableName;
    }

    /**
     * 动态生成数据模块
     *
     * @param \V4\Model\Currency $currency 货币类型， 请使用\V4\Model\Currency提供的常量
     * @param string $suffix 表后缀， 规则： date('Ym'), eg.: 201707， 缺省值：当前月
     *
     * @return type 返回兑换记录数据Model
     */
    protected function M($currency, $suffix = '')
    {
        return M($this->getTableName($currency, $suffix));
    }

    /**
     * 添加兑换记录-同时更新账户余额
     *
     * @param type $user_id 用户ID
     * @param string $currency 货币类型，请使用\V4\Model\Currency 提供的常量
     * @param string $record_action 操作类型，请使用\V4\Model\CurrencyAction 提供的常量
     * @param $record_amount 操作金额
     * @param $record_attach 附加参数
     * @param $record_remark 备注说明
     *
     * @return boolean 添加结果
     */
    public function add($user_id, $record_currency, $record_action, $record_amount, $record_attach = '', $record_remark = '', $record_exchange = 1)
    {
        if ($record_amount == 0) {
            return true;
        }

        $record_attach = $record_attach ?: $this->getRecordAttach(1, '系统');

        $AccountM = new AccountModel();
        //先锁表
        $AccountM->lockUserItem($user_id);
        $allbalence = $AccountM->getAllBalance($user_id, 0, false, false);
        $balence_field = $AccountM->getBalanceField($record_currency);
        $item = [
            'user_id' => $user_id,
            'record_currency' => $record_currency,
            'record_action' => $record_action,
            'record_amount' => $record_amount,
            'record_balance' => $allbalence[$balence_field] + $record_amount,
            'record_attach' => $record_attach,
            'record_remark' => $record_remark,
            'record_addtime' => time(),
            'record_exchange' => $record_exchange,
        ];
        //余额不足
        if ($allbalence[$balence_field] + $record_amount < 0) {
            return false;
        }
        $res = $this->M($item['record_currency'])->add($item);
        if ($res) {
            //实时统计用户帐户数据\
            unset($allbalence[$balence_field]);
            $status = $AccountM->save($user_id, $item['record_currency'], $record_amount, $item['record_balance'], $allbalence);
            if ($status) {
                return $res;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * 根据id获取记录
     * Enter description here ...
     *
     * @param unknown_type $record_id
     * @param unknown_type $currency
     * @param unknown_type $suffix
     */
    public function getById($record_id, $currency, $suffix)
    {
        return $this->M($currency, $suffix)->where('record_id=' . $record_id)->find();
    }

    /**
     * 根据uid获取记录
     * Enter description here ...
     *
     * @param unknown_type $record_id
     * @param unknown_type $currency
     * @param unknown_type $suffix
     */
    public function getByUserId($user_id, $currency, $suffix)
    {
        return $this->M($currency, $suffix)->where('user_id=' . $user_id)->order('record_id desc')->find();
    }

    /**
     * 获取当前月记录明细分页列表
     *
     * @param type $user_id 用户ID
     * @param \V4\Model\Currency $currency 货币类型，请使用\V4\Model\Currency 提供的常量
     * @param string $suffix 表后缀， 规则： date('ym'), eg.: 1707， 缺省值(留空)：当前月
     * @param int $page 当前页码 ,当为false时不分页(此时$listRows参数无效)
     * @param int $type 类型，1收入，0支出, 2全部
     * @param int $listRows 分页大小
     * @param string $actionwhere where筛选条件
     * @param string $getPrevMonth 是否获取并拼装前一月的数据(默认true)
     *
     * @return array 返回数据
     */
    public function getPageList($user_id, $currency, $suffix = '', $page = 1, $type = 2, $listRows = 10, $actionwhere = '', $getPrevMonth = true)
    {
        try {
            $query = $this->M($currency, $suffix);
        } catch (\Exception $e) {
            ajax_return('暂无该月数据');
        }
        $where = 'user_id=' . $user_id;
        if ($type == 1) {
            $where .= ' and record_amount > 0';
        }
        if ($type == 0) {
            $where .= ' and record_amount < 0';
        }
        if ($actionwhere != '') {
            $where .= $actionwhere;
        }
        //>>>8.11限制只能查当月最近3天<<
        if (!preg_match('/record_addtime/', $where)) {
            //$where .= ' and record_addtime >= '.(time()-259200);
        }

        //过滤微信支付充值
//         $where .= ' and record_action != ' . CurrencyAction::CashChongzhiWeixin;

        //过滤奖金退回的数据
        $where .= " and record_action != 999 ";

        $_totalRows = 0;
        if ($page !== false) {
            $_totalRows = $query->where($where)->count(0); //记录总条数
        }

        $list = $query->where($where)->order('record_id desc');

        if ($page !== false) {
            $list = $list->limit($listRows)->page($page);
        }

        $list = $list->select();

        //拼装前一月的数据
//         if (count($list) == 0 && $page == 1 && $getPrevMonth) {
//             $last = $this->getLastMonth($currency, $user_id, $type, $actionwhere);
//             if ($last) {
//                 $list = array_merge($list, $last);
//             }
//         }

        return [
            'paginator' => $this->paginator($_totalRows, $listRows),
            'list' => $list,
        ];
    }

    private function getLastMonth($currency, $user_id, $type, $actionwhere)
    {
        if (date('d') < 7) {
//            $lm = (date('m') - 1);
//            $lm = $lm < 10 ? '0' . $lm : $lm;
            $suffix = date("Ym", strtotime(sprintf("%s -1 month", date('Y-m'))));

            $query = $this->M($currency, $suffix);
            $where = 'user_id=' . $user_id;
            if ($type == 1) {
                $where .= ' and record_amount > 0';
            }
            if ($type == 0) {
                $where .= ' and record_amount < 0';
            }
            if ($actionwhere != '') {
                $where .= $actionwhere;
            }

            //过滤微信支付充值
            $where .= ' and record_action != ' . CurrencyAction::CashXiaofeiChongzhiWeixin;

            $list = $query->where($where)->order('record_id desc');
            $list = $list->limit(0, 100);

            $list = $list->select();

            return $list;
        }

        return [];
    }

    /**
     * 生成附件信息
     *
     * @param int $from_uid
     * @param string $from_name  来源名称：1兑换商品显示商品名称/2买单显示店铺名/3奖励显示根据from_uid来，1显示平台反之显示用户名
     * @param string $pic
     * @param string $orderNo
     * @param string $third_account
     * @param string $loginname
     * @param string $nickname
     * 
     * @param array $data_extend 扩展自定义信息
     *
     * @return string
     */
    public function getRecordAttach($from_uid, $from_name = '', $pic = '', $orderNo = '', $third_account = '', $loginname = '', $nickname = '', $data_extend='')
    {
        $data['from_uid'] = $from_uid;
        if ($from_name) {
            $data['from_name'] = $from_name;
        }
        if ($pic) {
            $data['pic'] = $pic;
        }
        if ($orderNo) {
            $data['serial_num'] = $orderNo;
        }
        if ($third_account) {
            $data['third_account'] = $third_account;
        }
        if ($loginname) {
            $data['loginname'] = $loginname;
        }
        if ($nickname) {
            $data['nickname'] = $nickname;
        }
        
        if (is_array($data_extend)) {
        	$data = array_merge(data, $data_extend);
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 查明细初始化附件信息，目的：兼容迁移的数据
     * Enter description here ...
     *
     * @param array $atach
     */
    public function initAtach($atach, $currency, $month_suffix, $recordid, $action)
    {
        //如果有直接返回
        if (isset($atach['from_name']) && isset($atach['pic']) && isset($atach['loginname']) && isset($atach['nickname'])) {
            return $atach;
        }
        if (in_array($action, array(35, 41, 45, 393, 394, 37))) {
            return $this->getStoreObject($atach['serial_num'], $currency, $month_suffix, $recordid);
        }
        if ($atach['from_uid'] > 1) {
            return $this->getUserObject($atach['from_uid'], $atach['serial_num'], $currency, $month_suffix, $recordid);
        }

        return $this->getDefaultAttach();
    }

    /**
     * 根据订单号查询店铺
     * Enter description here ...
     *
     * @param $order_no
     * @param $currency
     * @param $month_suffix
     * @param $recordid
     */
    private function getStoreObject($order_no, $currency, $month_suffix, $recordid)
    {
        $w['o.order_number'] = $order_no;
        $res = M('orders o')->field('s.id, s.store_name as from_name, s.store_img as pic, m.loginname, m.nickname')
            ->join('left join zc_store as s on s.id = o.storeid')
            ->join('left join __MEMBER__ m on m.id=s.uid')
            ->where($w)
            ->find();
        if (!$res) {
            $res = $this->getDefaultAttach($order_no);
        } else {
            $res['serial_num'] = $order_no;
        }
        $this->M($currency, $month_suffix)->where('record_id=' . $recordid)->save(array('record_attach' => json_encode($res, JSON_UNESCAPED_UNICODE)));

        return $res;
    }

    private function getUserObject($userid, $order_no, $currency, $month_suffix, $recordid)
    {
        $w['id'] = $userid;
        $res = M('member')->field('nickname as from_name,img as pic,id as from_uid, loginname, nickname')
            ->where($w)
            ->find();
        if (!$res) {
            $res = $this->getDefaultAttach($order_no);
        } else {
            $res['serial_num'] = $order_no;
        }
        $this->M($currency, $month_suffix)->where('record_id=' . $recordid)->save(array('record_attach' => json_encode($res, JSON_UNESCAPED_UNICODE)));

        return $res;
    }

    private function getDefaultAttach($order_no = '')
    {
        $res['from_uid'] = 1;
        $res['from_name'] = '管理员';
        $res['pic'] = C('LOCAL_HOST') . '/Public/images/default-avatar.png';
        $res['serial_num'] = $order_no;

        return $res;
    }

    /**
     * 按月/天查询所有用户转账记录
     *
     * @param \V4\Model\Currency $currency 货币类型，请使用\V4\Model\Currency 提供的常量
     * @param string $month 指定月份,格式:date('Ym'),留空默认当前月
     * @param int $page 当前页码,当为false时不分页(此时$listRows参数无效)
     * @param int $listRows 分页大小
     * @param string $actionwhere where筛选条件
     *
     * @return array 返回数据
     */
    public function getListByAllUser($currency, $month = '', $page = 1, $listRows = 10, $actionwhere = '')
    {
        $query = $this->M($currency, $month);

        $where = ' 1=1 ';
        $where .= $actionwhere;

        //当$page为false时不分页
        $_totalRows = 0;
        if ($page !== false) {
            $_totalRows = $query->where($where)->count(0); //记录总条数
        }

        $list = $query->where($where)->order('record_id desc');

        if ($page !== false) {
            $list = $list->page($page, $listRows);
        }

        $list = $list->select();

        return [
            'paginator' => $this->paginator($_totalRows, $listRows),
            'list' => $list,
        ];
    }

    /**
     * f返回显示给用户看的名称-用于明细列表
     * Enter description here ...
     *
     * @param $from_uid
     * @param $from_name
     */
    public function getFinalName($from_uid = 1, $from_name)
    {
        if ($from_uid == 1) {
            if ($from_name == '系统' || $from_name == '管理员' || $from_name == '平台') {
                //return $from_name;
                return '';
            }
        }

        return mb_substr($from_name, 0, 1, 'utf-8') . '**';
    }

    /**
     * 获取指定条件的指定字段的值
     *
     * @internal 主要用于获取字段累计数据，此方法使用的为find()查询
     *
     * @param \V4\Model\Currency $currency 货币类型，请使用\V4\Model\Currency 提供的常量
     * @param string $month 指定月份,格式:date('Ym'),留空默认当前月
     * @param string $fileds 读取字段
     * @param string $actionwhere 筛选条件
     *
     * @return mixed
     */
    public function getFieldsValues($currency, $month = '', $fields = '*', $actionwhere = '')
    {
        $info = $this->M($currency, $month)->where($actionwhere)->field($fields)->find();

        return $info;
    }


    /**
     * 交个税
     *
     * @param unknown $user_id
     * @param unknown $amount
     * @param unknown $pro 0=不交个税
     * @param unknown $currency
     * @param unknown $currencyAction
     *
     * @return boolean
     */
    public function personalTax($user_id, $amount, $pro, $currency, $currencyAction, $record_id = 0, $remark = '扣税')
    {
        if ($pro == 0) {
            return true;
        }
        //计算个税金额
        $tax = ($amount * $pro) / 100;
        if ($tax < 0.0001) {
            $tax = 0;
        }
        //记录
        $attach = $this->getDefaultAttach('');
        $attach['record_id'] = $record_id;

        return $this->add($user_id, $currency, $currencyAction, -$tax, json_encode($attach, JSON_UNESCAPED_UNICODE), $remark);
    }
}
