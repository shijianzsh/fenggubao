<?php

namespace V4\Model;

/**
 * 用户实时统计数据模块
 */
class AccountModel
{

    private static $_instance;

    /**
     * 单例-获取new对象
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 动态生成数据表
     * @param Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return string
     */
    private function getTableName($tag = 0)
    {
        $_tableName = 'account';
//        if (strlen($tag . '') > 6) {
//            $_tableName .= '_' . substr($tag . '', 0, 6);
//        }
        return $_tableName;
    }

    /**
     * 动态生成数据模块
     * @param Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return type 返回兑换记录数据Model
     */
    protected function M($tag = 0)
    {
        return M($this->getTableName($tag));
    }

    /**
     * 获取用户实时统计数据
     *
     * @param $user_id 用户ID
     * @param string $fields 所需字段
     * @param Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return mixed
     */
    public function getItemByUserId($user_id, $fields = '*', $tag = 0, $forzentag = true)
    {
        $balance = $this->M($tag)
            ->where('`user_id`=%d AND `account_tag`=%d', [$user_id, $tag])
            ->field($fields)
            ->order('account_id desc')
            ->find();
        if (!$balance) {
            if ($fields == '*') {
                $balance = [
                    'user_id' => $user_id,
                    'account_goldcoin_expenditure' => 0,
                    'account_goldcoin_income' => 0,
                    'account_goldcoin_balance' => 0,
                   'account_colorcoin_expenditure' => 0,
                   'account_colorcoin_income' => 0,
                   'account_colorcoin_balance' => 0,
                    'account_cash_expenditure' => 0,
                    'account_cash_income' => 0,
                    'account_cash_balance' => 0,
                   'account_points_expenditure' => 0,
                   'account_points_income' => 0,
                   'account_points_balance' => 0,
                   'account_bonus_expenditure' => 0,
                   'account_bonus_income' => 0,
                   'account_bonus_balance' => 0,
                   'account_enroll_expenditure' => 0,
                   'account_enroll_income' => 0,
                   'account_enroll_balance' => 0,
                   'account_supply_expenditure' => 0,
                   'account_supply_income' => 0,
                   'account_supply_balance' => 0,
                   'account_credits_expenditure' => 0,
                   'account_credits_income' => 0,
                   'account_credits_balance' => 0,
                   'account_enjoy_expenditure' => 0,
                   'account_enjoy_income' => 0,
                   'account_enjoy_balance' => 0,
//                    'account_redelivery_expenditure' => 0,
//                    'account_redelivery_income' => 0,
//                    'account_redelivery_balance' => 0,
                    'account_tag' => $tag,
                    'account_uptime' => 0,
                ];
            } else {
                $balance = [
                    'user_id' => $user_id,
                ];
                $fieldlist = explode(',', $fields);
                foreach ($fieldlist as $k => $v) {
                    $balance[$v] = 0;
                }
            }
        }

        //获取冻结资金
        $where['frozen_status'] = 1;
        $where['user_id'] = $user_id;
       $frozen = M('frozen_fund')->field('sum(frozen_credits) frozen_credits, sum(frozen_supply) frozen_supply, sum(frozen_goldcoin) frozen_goldcoin, sum(frozen_colorcoin) frozen_colorcoin, sum(frozen_cash) as frozen_cash')->where($where)->find();
       if ($frozen && $forzentag) {
           if ($frozen['frozen_goldcoin'] > 0) {
               $balance['account_goldcoin_balance'] = ($balance['account_goldcoin_balance'] - $frozen['frozen_goldcoin']) . '';
           }
           if ($frozen['frozen_colorcoin'] > 0) {
               $balance['account_colorcoin_balance'] = ($balance['account_colorcoin_balance'] - $frozen['frozen_colorcoin']) . '';
           }
           if ($frozen['frozen_supply'] > 0) {
               $balance['account_supply_balance'] = ($balance['account_supply_balance'] - $frozen['frozen_supply']) . '';
           }
           if ($frozen['frozen_credits'] > 0) {
               $balance['account_credits_balance'] = ($balance['account_credits_balance'] - $frozen['frozen_credits']) . '';
           }
           if ($frozen['frozen_cash'] > 0) {
               $balance['account_cash_balance'] = ($balance['account_cash_balance'] - $frozen['frozen_cash']) . '';
           }
       }
       
        return $balance;
    }

    /**
     * 锁行
     * Enter description here ...
     * @param $user_id
     * @param $fields
     * @param $tag
     */
    public function lockUserItem($user_id, $fields = '*', $tag = 0)
    {
        return $this->M($tag)->lock(true)
            ->where('`user_id`=%d AND `account_tag`=%d', [$user_id, $tag])
            ->field($fields)
            ->order('account_id desc')
            ->find();
    }

    /**
     * 获取用户所有统计数据 (有初始值)
     * @param type $user_id 用户ID
     * @param $fields
     * @param Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return type 返回数组
     */
    public function getAccount($user_id, $fields = '*', $tag = 0)
    {
        $item = $this->getItemByUserId($user_id, $fields, $tag);
        if (!$item) {
            if ($fields == '*') {
                $item = [
                    'user_id' => $user_id,
                    'account_goldcoin_expenditure' => 0,
                    'account_goldcoin_income' => 0,
                    'account_goldcoin_balance' => 0,
                    'account_colorcoin_expenditure' => 0,
                    'account_colorcoin_income' => 0,
                    'account_colorcoin_balance' => 0,
                    'account_cash_expenditure' => 0,
                    'account_cash_income' => 0,
                    'account_cash_balance' => 0,
                    'account_points_expenditure' => 0,
                    'account_points_income' => 0,
                    'account_points_balance' => 0,
                    'account_bonus_expenditure' => 0,
                    'account_bonus_income' => 0,
                    'account_bonus_balance' => 0,
                    'account_enroll_expenditure' => 0,
                    'account_enroll_income' => 0,
                    'account_enroll_balance' => 0,
                    'account_supply_expenditure' => 0,
                    'account_supply_income' => 0,
                    'account_supply_balance' => 0,
                    'account_credits_expenditure' => 0,
                    'account_credits_income' => 0,
                    'account_credits_balance' => 0,
                    'account_enjoy_expenditure' => 0,
                    'account_enjoy_income' => 0,
                    'account_enjoy_balance' => 0,
                    'account_redelivery_expenditure' => 0,
                    'account_redelivery_income' => 0,
                    'account_redelivery_balance' => 0,
                    'account_tag' => $tag,
                    'account_uptime' => 0,
                ];
            } else {
                $item = [
                    'user_id' => $user_id,
                ];
                $fieldlist = explode(',', $fields);
                foreach ($fieldlist as $k => $v) {
                    $item[$v] = 0;
                }
            }
        }
        return $item;
    }

    /**
     * 获取用户指定货币实时余额
     * @param type $user_id
     * @param Currency $currency 货币类型， 请使用\V4\Model\Currency提供的常量
     * @return int
     */
    public function getBalance($user_id, $currency)
    {
        $_field = 'account_' . $currency . '_balance';
        $item = $this->getItemByUserId($user_id, $_field);
        if ($item) {
            return $item[$_field];
        }
        return 0;
    }

    /**
     * 获取6种余额， 默认扣除冻结部分
     * Enter description here ...
     * @param int $user_id
     * @param int $tag
     * @param int $gs 是否格式化
     */
    public function getAllBalance($user_id, $tag = 0, $gs = false, $forzentag = true)
    {
        $_field = 'account_goldcoin_balance,account_cash_balance,account_bonus_balance,account_colorcoin_balance,account_points_balance,account_enroll_balance,account_supply_balance,account_enjoy_balance,account_credits_balance';
        $item = $this->getItemByUserId($user_id, $_field, $tag, $forzentag);
        if ($gs) {
            foreach ($item as $k => $v) {
                $item[$k] = sprintf('%.2f', floor($v * 100) / 100);
            }
        }
        return $item;
    }

    /**
     * 判断用户指定标签统计记录ID
     * @param type $user_id
     * @param Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return int account_id 统计记录ID
     */
    public function getUserAccountIdByTag($user_id, $tag = 0)
    {
        return $this->M($tag)->where(['user_id' => $user_id, 'account_tag' => $tag])->getField('account_id');
    }

    /**
     * 动态生成余额字段名
     *
     * @param Currency $currency 货币类型， 请使用\V4\Model\Currency提供的常量
     * @return string
     */
    public function getBalanceField($currency)
    {
        return 'account_' . $currency . '_balance';
    }

    /**
     * 动态生成支出字段名
     *
     * @param Currency $currency 货币类型， 请使用\V4\Model\Currency提供的常量
     * @return string
     */
    private function getExpenditureField($currency)
    {
        return 'account_' . $currency . '_expenditure';
    }

    /**
     * 动态生成收入字段名
     *
     * @param Currency $currency 货币类型， 请使用\V4\Model\Currency提供的常量
     * @return string
     */
    private function getIncomeField($currency)
    {
        return 'account_' . $currency . '_income';
    }

    /**
     * 新增用户统计数据
     *
     * @param int $user_id 用户ID
     * @param Currency $currency 货币类型， 请使用\V4\Model\Currency提供的常量
     * @param float $amount 操作金额
     * @param Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return type 返回新增记录ID
     */
    public function add($user_id, $currency, $amount, $record_balance, $tag = 0, $allbalence)
    {

        $_balanceField = $this->getBalanceField($currency); // 余额字段名
        $_expenditureField = $this->getExpenditureField($currency); // 支出字段名
        $_incomeField = $this->getIncomeField($currency); // 收入字段名

        $item = [
            'user_id' => $user_id,
            $_balanceField => $record_balance,
            'account_tag' => $tag,
            'account_uptime' => time()
        ];
        foreach ($allbalence as $k => $v) {
            $item[$k] = $v;
        }

        if ($amount < 0) {
            $item[$_expenditureField] = $amount;
            $item[$_incomeField] = 0;
        } else if ($amount > 0) {
            $item[$_expenditureField] = 0;
            $item[$_incomeField] = $amount;
        }
        return $this->M($tag)->add($item);
    }

    /**
     * 一次获得3个余额字段
     * Enter description here ...
     * @param $currency
     */
    public function get3BalanceFields()
    {
        $cash = $this->getBalanceField(Currency::Cash);
        $goldcoin = $this->getBalanceField(Currency::GoldCoin);
        $colorcoin = $this->getBalanceField(Currency::ColorCoin);
        return $cash . ',' . $goldcoin . ',' . $colorcoin;
    }

    public function get5BalanceFields()
    {
        $str = $this->get3BalanceFields();
        $points = $this->getBalanceField(Currency::Points);
        $bonus = $this->getBalanceField(Currency::Bonus);
        $enroll = $this->getBalanceField(Currency::Enroll);
        $credits = $this->getBalanceField(Currency::Credits);
        $supply = $this->getBalanceField(Currency::Supply);
        $enjoy = $this->getBalanceField(Currency::Enjoy);
        $redelivery = $this->getBalanceField(Currency::Redelivery);
        return $str . ',' . $points . ',' . $bonus . ',' . $enroll . ',' . $credits . ',' . $supply . ',' . $enjoy . ',' . $redelivery;
    }

    /**
     * 更新用户统计数据
     *
     * @param int $account_id 记录ID
     * @param Currency $currency 货币类型， 请使用\V4\Model\Currency提供的常量
     * @param float $amount 操作金额
     * @return bool 更新结果
     */
    public function update($account_id, $currency, $amount, $record_balance, $tag = 0)
    {
        $_balanceField = $this->getBalanceField($currency); // 余额字段名
        $_expenditureField = $this->getExpenditureField($currency); // 支出字段名
        $_incomeField = $this->getIncomeField($currency); // 收入字段名

        $item = [
            'account_id' => $account_id,
            $_balanceField => $record_balance
        ];

        if ($amount < 0) {
            $item[$_expenditureField] = ['exp', $_expenditureField . '+' . $amount];
        } else if ($amount > 0) {
            $item[$_incomeField] = ['exp', $_incomeField . '+' . $amount];
        }
        return $this->M($tag)->save($item);
    }

    /**
     * 实时统计用户帐户数据
     *
     * @param $user_id 用户ID
     * @param Currency $currency 货币类型， 请使用\V4\Model\Currency提供的常量
     * @param float $amount 操作金额
     * @return bool 操作结果
     */
    public function save($user_id, $currency, $amount, $record_balance, $allbalence)
    {
        if ($amount == 0) {
            return false;
        }

        // 更新用户帐户总额数据
        $_totalAccountId = $this->getUserAccountIdByTag($user_id, Tag::get());
        if ($_totalAccountId > 0) {
            if (!$this->update($_totalAccountId, $currency, $amount, $record_balance, Tag::get())) {
                return false;
            }
        } else {
            if (!$this->add($user_id, $currency, $amount, $record_balance, Tag::get())) {
                return false;
            }
        }

        // 更新用户帐户当日数据
        $_dayAccountId = $this->getUserAccountIdByTag($user_id, Tag::getDay());
        if ($_dayAccountId > 0) {
            if (!$this->update($_dayAccountId, $currency, $amount, $record_balance, Tag::getDay())) {
                return false;
            }
        } else {
            if (!$this->add($user_id, $currency, $amount, $record_balance, Tag::getDay(), $allbalence)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取用户现金余额
     *
     * @param $user_id 用户ID
     * @return float 返回余额
     */
    public function getCashBalance($user_id)
    {
        return $this->getBalance($user_id, Currency::Cash);
    }

    /**
     * 获取用户公让宝余额
     *
     * @param $user_id 用户ID
     * @return float 返回余额
     */
    public function getGoldCoinBalance($user_id)
    {
        return $this->getBalance($user_id, Currency::GoldCoin);
    }

    /**
     * 获取用户商超券余额
     *
     * @param $user_id 用户ID
     * @return float 返回余额
     */
    public function getColorCoinBalance($user_id)
    {
        return $this->getBalance($user_id, Currency::ColorCoin);
    }

    /**
     * 获取用户积分余额
     *
     * @param $user_id 用户ID
     * @return float 返回余额
     */
    public function getPointsBalance($user_id)
    {
        return $this->getBalance($user_id, Currency::Points);
    }

    /**
     * 获取用户丰收点余额
     *
     * @param $user_id 用户ID
     * @return float 返回余额
     */
    public function getBonusBalance($user_id)
    {
        return $this->getBalance($user_id, Currency::Bonus);
    }

    /**
     * 获取用户注册币余额
     *
     * @param $user_id 用户ID
     * @return float 返回余额
     */
    public function getEnrollBalance($user_id)
    {
        return $this->getBalance($user_id, Currency::Enroll);
    }

    /**
     * 获取用户特供券余额
     *
     * @param $user_id 用户ID
     * @return float 返回余额
     */
    public function getSupplyBalance($user_id)
    {
        return $this->getBalance($user_id, Currency::Supply);
    }

    /**
     * 获取用户商城积分余额
     *
     * @param $user_id 用户ID
     * @return float 返回余额
     */
    public function getCreditsBalance($user_id)
    {
        return $this->getBalance($user_id, Currency::Credits);
    }

    /**
     * 获取用户乐享币余额
     *
     * @param $user_id 用户ID
     * @return float 返回余额
     */
    public function getEnjoyBalance($user_id)
    {
        return $this->getBalance($user_id, Currency::Enjoy);
    }

    /**
     * 获取用户复投币余额
     *
     * @param $user_id 用户ID
     * @return float 返回余额
     */
    public function getRedeliveryBalance($user_id)
    {
        return $this->getBalance($user_id, Currency::Redelivery);
    }

    /**
     * 随机获取3个人的记录
     * Enter description here ...
     */
    public function getRandomRecord()
    {
        $endid = $this->M()->order('account_id desc')->getField('account_id');
        $rand = 1; //rand(3000, $endid);
        return $this->M()->where('`account_tag`=0 and account_id > ' . $rand)->order('account_id asc')->limit(3)->select();
    }

    /**
     * 获取指定条件的指定字段的值
     *
     * @param string $fileds 读取字段
     * @param string $actionwhere 筛选条件
     * @param Tag $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     *
     * @return mixed
     *@internal 主要用于获取字段累计数据，此方法使用的为find()查询
     *
     */
    public function getFieldsValues($fields = '*', $actionwhere = '', $tag = 0)
    {
        $info = $this->M($tag)->where($actionwhere)->field($fields)->find();

        return $info;
    }

    /**
     * 今日总丰收点数(股)
     */
    public function getTotalBonus()
    {
        return $this->M(0)->alias('a')->join('__MEMBER__ AS m  ON a.user_id = m.id')->where('a.account_tag =0 AND m.is_lock = 0')->sum('a.account_bonus_balance');

    }

    /**
     * 今日封顶点数(股)
     * @param type $max_bonus
     */
    public function getExceedMaxBonus($member_bonus_max)
    {
        return $this->M(0)->alias('a')->join('__MEMBER__ AS m  ON a.user_id = m.id')->where("a.account_tag =0 AND m.is_lock = 0 AND a.account_bonus_balance >={$member_bonus_max}")->sum('a.account_bonus_balance');
    }

    /**
     * 冻结货币
     * @param unknown $user_id
     * @param unknown $data
     */
    public function frozenRefund($user_id, $order_id, $data, $remark = '')
    {
        if ($data['domiciled_credits'] > 0 || $data['domiciled_supply'] > 0 || $data['domiciled_goldcoin'] > 0 || $data['domiciled_points'] > 0) {
        	//判断订单冻结数据是否已存在,已存在则直接更新数据
        	$frozen_info = M('frozen_fund')->where('order_id='.$order_id)->find();
        	if ($frozen_info) {
                $vo['frozen_goldcoin'] = $data['domiciled_goldcoin'] ?: 0;
        		$vo['frozen_uptime'] = time();
        		$vo['frozen_remark'] = $remark;
        		
        		$res = M('frozen_fund')->where('order_id='.$order_id)->save($vo);
        	} else {
	            $vo['order_id'] = $order_id;
	            $vo['user_id'] = $user_id;
	            $vo['frozen_status'] = 1;
	            $vo['frozen_addtime'] = time();
	            $vo['frozen_uptime'] = time();
                $vo['frozen_credits'] = $data['domiciled_credits'] ?: 0;
                $vo['frozen_supply'] = $data['domiciled_supply'] ?: 0;
                $vo['frozen_goldcoin'] = $data['domiciled_goldcoin'] ?: 0;
	            $vo['frozen_remark'] = $remark;
	            $res = M('frozen_fund')->add($vo);
        	}
            return $res;
        } else {
            return true;
        }
    }


}
