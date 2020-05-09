<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 16:18
 */

namespace V4\Controller;

use Think\Controller;
use V4\Model\ProcedureModel;

class BonusbackController extends Controller {

    public function __construct() {
        parent::__construct();
        header('Content-Type:text/html; charset=utf-8');
    }

    public function index() {
        set_time_limit(0);
        M()->startTrans();
        $procedureM = new ProcedureModel();
        $result = $procedureM->execute('BonusBack', '', '@msg');
        if($result[0]['@msg'] == ''){
            M()->commit();
            echo '回购成功';
        }else{
            M()->rollback();
            echo $result[0]['@msg'];
        }
        set_time_limit(30);
    }

    

}
