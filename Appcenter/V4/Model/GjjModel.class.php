<?php

namespace V4\Model;

/**
 * 谷聚金模型
 *
 */
class GjjModel extends BaseModel
{

    /**
     * 获取谷聚金产品详情
     *
     * @param string $field 获取字段(默认*)
     */
    public function getProductDetails($field = 'pro.*,paf.*,ppr.*')
    {
        $where = [
            'pro.status' => ['eq', 0],
            'pro.manage_status' => ['eq', 1],
            'paf.block_id' => ['eq', C('GJJ_BLOCK_ID')],
        ];

        $data = M('Product')
            ->alias('pro')
            ->join('join __PRODUCT_AFFILIATE__ paf ON paf.product_id=pro.id')
            ->join('join __PRODUCT_PRICE__ ppr ON ppr.product_id=pro.id')
            ->where($where)
            ->order('pro.id desc')
            ->field($field)
            ->find();

        return $data;
    }

    /**
     * 获取用户谷聚金身份信息
     *
     * @param int $user_id 用户ID
     * @param boolean $is_show_area 是否显示所属省市区信息(true/false,默认true)
     * @param boolean $is_array 是否返回数据数组,若是,则$is_show_area将失效
     * @param array $extend_where 扩展筛选条件(默认false)
     *
     * @return boolean/array 失败返回false,成功返回数据
     */
    public function getGjjRoles($user_id, $is_show_area = true, $is_array = false, $extend_where=false)
    {
        if (!validateExtend($user_id, 'NUMBER')) {
            return false;
        }

        $map_roles = [
            'user_id' => ['eq', $user_id],
            'audit_status' => ['eq', 1],
            'enabled' => ['eq', 1]
        ];
        
        if ($extend_where) {
        	$map_roles = array_merge($map_roles, $extend_where);
        }
        
        $data = M('gjj_roles')->where($map_roles)->field('id,role,region,province,city,country,audit_status,remark')->order('role desc')->select();
        if (count($data) <= 0) {
            return false;
        }

        $roles = [];

        if ($is_array) {
            $roles = [
                'role_name' => '',
                'role_region' => '',
                'role_counties' => [],
                'audit_status' => 0,
                'remark' => '',
            ];
            foreach ($data as $k => $v) {
                if (empty($roles['role_name'])) {
                    switch ($v['role']) {
                        case '5':
                            $roles['role_name'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['role'][5];
                            $roles['role_region'] = $v['region'];
                            break;
                        case '4':
                            $roles['role_name'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['role'][4];
                            $roles['role_region'] = $v['province'];
                            break;
                        case '2':
                            $roles['role_name'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['role'][2];
                            $roles['role_region'] = $v['province'] . $v['city'] . $v['country'];
                            break;
                    }
                    
                    $roles['audit_status'] = $v['audit_status'];
                    $roles['remark'] = $v['remark'];
                }
                if ($v['role'] == 2 && $roles['role_name'] != C('GJJ_FIELD_CONFIG')['gjj_roles']['role'][2]) {
                    $roles['role_counties'][] = $v['province'] . $v['city'] . $v['country'];
                }
            }
        } else {
            foreach ($data as $k => $v) {
                switch ($v['role']) {
                    case '5':
                        $role = $v['region'] . '合伙人';
                        break;
                    case '4':
                        $role = $is_show_area ? $v['province'] : '';
                        $role = $role . C('GJJ_FIELD_CONFIG')['gjj_roles']['role'][4];
                        break;
                    case '2':
                        $role = $is_show_area ? $v['province'] . $v['city'] . $v['country'] : '';
                        $role = $role . C('GJJ_FIELD_CONFIG')['gjj_roles']['role'][2];
                        break;
                    default:
                        $role = '未知身份';
                }
                $roles[$k] = $role;
            }
        }

        return $roles;
    }

    /**
     * 获取数据列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     * @param boolean $use_join 是否启用关联查询(默认false)
     * @param string $join_section 关联查询扩展条件(需添加and)
     *
     * @return array
     */
    public function getList($fields = '*', $page = 1, $listRows = 10, $actionwhere = '', $use_join=false, $join_section)
    {	
    	if ($use_join) {
    		if ($fields == '*') {
    			$fields = 'g.*';
    		}
    	}
    	
        //当$page为false时不分页
        $_totalRows = 0;
        if ($page !== false) {
        	if ($use_join) {
        		$_totalRows = M('gjj_roles')
        			->alias('g')
        			->join('left join __GJJ_ROLES__ g1 ON g1.user_id=g.user_id '. $join_section)
	        		->where($actionwhere)
	        		->count(0);
        	} else {
           		$_totalRows = M('gjj_roles')->where($actionwhere)->count(0);
        	}
        }

        if ($use_join) {
        	$list = M('gjj_roles')
        		->alias('g')
        		->join('left join __GJJ_ROLES__ g1 ON g1.user_id=g.user_id '. $join_section)
	        	->field($fields)
	        	->where($actionwhere);
        } else {
       		$list = M('gjj_roles')->field($fields)->where($actionwhere);
        }

        if ($page !== false) {
            $list = $list->page($page, $listRows);
        }

        if ($use_join) {
        	$list = $list->order('g.id desc')->select();
        } else {
       		$list = $list->order('id desc')->select();
        }

        return [
            'paginator' => $this->paginator($_totalRows, $listRows),
            'list' => $list,
        ];
    }

    /**
     * 获取数据详情
     *
     * @param string $fields 获取字段
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getInfo($fields = '*', $actionwhere = '')
    {
        $info = M('gjj_roles')->field($fields);
        $info = empty($actionwhere) ? $info : $info->where($actionwhere);
        $info = $info->find();

        return $info ?: null;
    }

    /**
     * 获取大中华区选择项列表
     */
    public function getRegionsName()
    {
        $data = M('gjj_regions')->group('name')->order('id asc')->select();

        return $data;
    }

    /**
     * 获取指定大中华区的省市选择项列表
     *
     * @param string $regions_name 大中华区名称
     */
    public function getRegionsProvince($regions_name)
    {
        if (empty($regions_name)) {
            return false;
        }

        $data = M('gjj_regions')->where("`name`='{$regions_name}'")->order('id asc')->select();

        return $data;
    }

}
