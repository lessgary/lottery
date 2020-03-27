<?php

/**
 * 文件 : /_app/controller/companymoney.php
 * 功能 : 控制器 - 公司入款记录
 *
 * @author    Ben
 * @version   1.0.0
 * @package   proxyweb
 *
 */
class controller_companymoney extends pcontroller {
    /**
     * 视图对象
     * @var $oViewer view
     */
    private $oViewer;

    public function __construct() {
        parent::__construct();
        $this->oViewer = $GLOBALS['oView'];
    }

    /**
     * 导出
     * @author Ben
     * @date 2017-08-08
     */
    public function actionExport() {
        if ($this->getIsPost()) {
            $aCondition = $this->post([
                'starttime' => parent::VAR_TYPE_DATETIME,
                'endtime' => parent::VAR_TYPE_DATETIME,
                'status' => parent::VAR_TYPE_INT,
                'company_payacc_id' => parent::VAR_TYPE_INT,
                'min' => parent::VAR_TYPE_FLOAT,
                'max' => parent::VAR_TYPE_FLOAT,
                'search_type' => parent::VAR_TYPE_INT,
                'key_word' => parent::VAR_TYPE_STR,
                'layerid' => parent::VAR_TYPE_STR
            ]);
            $aCondition['page'] = 1;
            $aCondition['rows'] = 10000;

            /* @var $oDepositCompany model_userdepositcompany */
            $oDepositCompany = a::singleton('model_userdepositcompany');
            $aList = $oDepositCompany->getList($this->lvtopid, $aCondition);

            $expCellName = [
                ['company_order_no', '订单号'],
                ['username', '会员账号'],
                ['apply_realname', '存款人姓名'],
                ['apply_amount', '存入金额'],
                ['favor_amount', '入款优惠'],
                ['notes', '附言码'],
                ['bank_info', '存入银行账号'],
                ['status_msg', '状态'],
                ['proxy_adminname', '操作者'],
                ['reject_remark', '拒绝备注'],
                ['time', '入款时间'],
            ];
            $expTableData = [];
            foreach ($aList['results'] as $k => $v) {
                $expTableData[$k]['company_order_no'] = $v['company_order_no'];
                $expTableData[$k]['username'] = $v['username'];
                $expTableData[$k]['apply_realname'] = $v['apply_realname'];
                $expTableData[$k]['apply_amount'] = $v['apply_amount'];
                $expTableData[$k]['favor_amount'] = $v['favor_amount'];
                $expTableData[$k]['notes'] = $v['notes'];
                $expTableData[$k]['bank_info'] = "账号别名：${v['nickname']}\n账号：${v['accout_no']}\n银行：${v['bankname']}";
                $expTableData[$k]['status_msg'] = array_key_exists($v['status'], model_userdepositcompany::$STATUS) ? model_userdepositcompany::$STATUS[$v['status']] : '';
                $expTableData[$k]['proxy_adminname'] = $v['proxy_adminname'];
                $expTableData[$k]['reject_remark'] = $v['reject_remark'];
                $expTableData[$k]['time'] = "提交时间：${v['inserttime']}\n操作时间：${v['updatetime']}";
            }
            ExportExcel('companymoney' . time(), $expCellName, $expTableData);
            die;
        }
    }

    /**
     * 获取列表信息
     * @author Ben
     * @date 2017-07-08
     */
    public function actionList() {
        static $aPageNumber = [100, 300, 500, 1000, 2000];//分页条数
        if ($this->getIsPost()) {
            $aCondition = $this->post([
                'page' => parent::VAR_TYPE_INT,
                'rows' => parent::VAR_TYPE_INT,
                'starttime' => parent::VAR_TYPE_DATETIME,
                'endtime' => parent::VAR_TYPE_DATETIME,
                'sfinishtime' => parent::VAR_TYPE_DATETIME,
                'efinishtime' => parent::VAR_TYPE_DATETIME,
                'status' => parent::VAR_TYPE_INT,
                'company_payacc_id' => parent::VAR_TYPE_STR,
                'min' => parent::VAR_TYPE_FLOAT,
                'max' => parent::VAR_TYPE_FLOAT,
                'search_type' => parent::VAR_TYPE_INT,
                'key_word' => parent::VAR_TYPE_STR,
                'layerid' => parent::VAR_TYPE_STR
            ]);
            //判断前端传过来的数据是否合法，不合法返回数组第一个值
            if (!in_array($aCondition['rows'],$aPageNumber)){
                $aCondition['rows'] = $aPageNumber[0];
            }
            /* @var $oDepositCompany model_userdepositcompany */
            $oDepositCompany = a::singleton('model_userdepositcompany');
            $aList = $oDepositCompany->getList($this->lvtopid, $aCondition);
            if ($aList) {
                $this->outPutJQgruidJson($aList['results'], $aList['affects'], $aCondition['page'], $aCondition['rows'], $aList['total']);
            } else {
                die(json_encode(['error' => 1, 'msg' => '请求失败！']));
            }
        } else {
            // 获取用户层级
            /* @var $oUserLayer model_userlayer */
//            $oUserLayer = a::singleton('model_userlayer');
//            $aLayerList = $oUserLayer->getLayerList($this->lvtopid);
//            $this->oViewer->assign('aLayerList', $aLayerList);
            // 日期开始时间
            $this->oViewer->assign('edate', date('Y-m-d 00:00:00', strtotime('+1 day')));
            // 日期结束时间
            $this->oViewer->assign('sdate', date('Y-m-d 00:00:00'));
            // 入款账号
            /* @var $oPayAcc model_proxypayacc */
            $oPayAcc = a::singleton('model_proxypayacc');
            $aList = $oPayAcc->getList($this->lvtopid, -1);
            $this->oViewer->assign('acc_list', $aList);
            $this->oViewer->assign('page_number', json_encode($aPageNumber));//分页条数
            $this->oViewer->display('companymoney_list.html');
        }
    }

    /**
     * 确认到账
     * @author Ben
     * @date 2017-07-08
     */
    public function actionConfirm() {
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton('memcachedb', $GLOBALS['aSysMemCacheServer']);

        if ($this->getIsGet()) {
            // 撤销取消
            $iId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            // 是否已经锁定
            if ($this->is_lock($oMemCache, $iId)) {
                die(json_encode(['error' => -1, 'msg' => '当前记录有人正在操作，请稍等！']));
            }

            // 加锁
            $this->add_lock($oMemCache, $iId);
            try {
                // 执行逻辑
                /* @var $oDepositCompany model_userdepositcompany */
                $oDepositCompany = a::singleton('model_userdepositcompany');
                $bResult = $oDepositCompany->backCancel($this->lvtopid, $iId, $this->loginProxyId, $this->adminname);
            } catch (Exception $e) {
                // 解锁
                $this->remove_lock($oMemCache, $iId);
            }

            // 解锁
            $this->remove_lock($oMemCache, $iId);

            if ($bResult) {
                die(json_encode([
                    'error' => 0,
                    'msg' => '操作成功！'
                ]));
            } else {
                die(json_encode([
                    'error' => -1,
                    'msg' => '更新失败！请联系管理员'
                ]));
            }
        } else if ($this->getIsPost()) {
            // 确定 & 拒绝
            $iId = isset($_POST['id']) ? intval($_POST['id']) : null;
            $isConfirm = isset($_POST['is_confirm']) ? intval($_POST['is_confirm']) : null;

            if(is_null($iId) || is_null($isConfirm)){
                die(json_encode([
                    'error' => -1,
                    'msg' => '服务器错误！'
                ]));
            }

            if ($isConfirm != 1) { // 确定
                $aCondition = $this->post([
                    'apply_amount' => parent::VAR_TYPE_FLOAT,
                    'favor_amount' => parent::VAR_TYPE_FLOAT
                ]);
            }else{ // 拒绝
                $aCondition = $this->post([
                    'reject_remark' => parent::VAR_TYPE_STR
                ]);
            }

            // 是否已经锁定
            if ($this->is_lock($oMemCache, $iId)) {
                die(json_encode(['error' => -1, 'msg' => '当前记录有人正在操作，请稍等！']));
            }

            // 加锁
            $this->add_lock($oMemCache, $iId);

            try {
                /* @var $oDepositCompany model_userdepositcompany */
                $oDepositCompany = a::singleton('model_userdepositcompany');
                if ($isConfirm == 1) {
                    $bResult = $oDepositCompany->cancel($this->lvtopid, $iId, $this->loginProxyId, $this->adminname, $aCondition['reject_remark']);
                } else {
                    $bResult = $oDepositCompany->confirm($this->lvtopid, $iId, $this->loginProxyId, $this->adminname,$aCondition['apply_amount'],$aCondition['favor_amount']);
                }

            } catch (Exception $e) {
                // 解锁
                $this->remove_lock($oMemCache, $iId);
            }
            // 解锁
            $this->remove_lock($oMemCache, $iId);

            if ($bResult) {
                die(json_encode([
                    'error' => 0,
                    'msg' => '操作成功！'
                ]));
            } else {
                die(json_encode([
                    'error' => -1,
                    'msg' => '服务器错误！'
                ]));
            }
        }
    }
    /**
     * desc 修改公司入款金额
     * @author rhovin 2017-08-28
     */
    public function actionChangeAmount() {
        if($this->getIsPost()){
            $aCondition = $this->post([
                'id' => parent::VAR_TYPE_INT,
                'apply_amount' => parent::VAR_TYPE_FLOAT,
            ]);
            $oDepositCompany = a::singleton('model_userdepositcompany');
            $favor_amount = $oDepositCompany->ChangeApplyAmount($this->lvtopid,$aCondition['id'],$aCondition['apply_amount']);
            if($favor_amount === FALSE) {
                $this->ajaxMsg(0,"修改失败");
            } else {
                $this->ajaxMsg(1,"修改成功",["favor_amount"=>$favor_amount]);
            }

        }
        
    }
    /**
     * 是否上锁
     * @param memcachedb $oMemCache
     * @param $iId
     * @return array|string
     */
    private function is_lock(memcachedb $oMemCache, $iId) {
        $sKey = 'company_money_lock_' . $iId;
        return $oMemCache->get($sKey);
    }

    /**
     * 加锁
     * @param memcachedb $oMemCache
     * @param $iId
     * @param int $expire
     */
    private function add_lock(memcachedb $oMemCache, $iId, $expire = 1800) {
        $sKey = 'company_money_lock_' . $iId;
        $oMemCache->set($sKey, 1, 0, 1800);
    }

    /**
     * 解锁
     * @param memcachedb $oMemCache
     * @param $iId
     */
    private function remove_lock(memcachedb $oMemCache, $iId) {
        $sKey = 'company_money_lock_' . $iId;
        $oMemCache->delete($sKey);
    }
}