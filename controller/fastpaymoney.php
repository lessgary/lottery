<?php

/**
 * 文件 : /_app/controller/fastpaymoney.php
 * 功能 : 控制器 - 三方入款记录
 *
 * @author    Ben
 * @version   1.0.0
 * @package   proxyweb
 *
 */
class controller_fastpaymoney extends pcontroller {
    /**
     * 视图对象
     * @var $oViewer view
     */
    private $oViewer;

    public function __construct() {
        parent::__construct();
        $this->oViewer = $GLOBALS['oView'];
    }

    public function actionExport() {
        if ($this->getIsPost()) {
            $aCondition = $this->post([
                'page' => parent::VAR_TYPE_INT,
                'rows' => parent::VAR_TYPE_INT,
                'starttime' => parent::VAR_TYPE_DATETIME,
                'endtime' => parent::VAR_TYPE_DATETIME,
                'status' => parent::VAR_TYPE_INT,
                'company_fastpayacc_id' => parent::VAR_TYPE_INT,
                'min' => parent::VAR_TYPE_FLOAT,
                'max' => parent::VAR_TYPE_FLOAT,
                'search_type' => parent::VAR_TYPE_INT,
                'key_word' => parent::VAR_TYPE_STR,
                'layerid' => parent::VAR_TYPE_STR,
                'paytypeid' => parent::VAR_TYPE_INT
            ]);
            $aCondition['page'] = 1;
            $aCondition['rows'] = 10000;
            /* @var $oDepositfastpay model_userdepositfastpay */
            $oDepositfastpay = A::singleton('model_userdepositfastpay');
            $aList = $oDepositfastpay->getList($this->lvtopid, $aCondition);
            $expCellName = [
                ['layer_name','层级'],
                    ['fastpay_acc_name','收款账号'],
                    ['username','账号'],
                    ['lvproxyname','一级代理'],
                    ['company_order_no','订单号'],
                    ['payname','充值方式'],
                    ['apply_amount','充值金额'],
                    ['real_amount','上分金额'],
                    ['charge','手续费'],
                    ['inserttime','日期'],
                    ['proxy_adminname','操作者'],
                    ['status_msg','状态'],
                    ['remark','备注']
            ];

            ExportExcel('companymoney' . time(), $expCellName, $aList['results']);
            die;
        }
    }

    /**
     * 列表
     * @author Ben
     * @date 2017-08-08
     */
    public function actionList() {
        static $aPageNumber = array(100,300,500,1000,2000);//分页条数
        if ($this->getIsPost()) {

            //异步查询三方账号
            $fastPay = isset($_POST['fast_pay_name']) ? $_POST['fast_pay_name'] : null;
            if(!is_null($fastPay)){
                $aCondition = $this->post(['fast_pay_name' => parent::VAR_TYPE_STR]);
                $fastModel = A::singleton('model_proxyfastpayacc');
                $result = $fastModel->searchAccByNickName($this->lvtopid, $aCondition);

                if(count($result)){
                    $this->ajaxMsg(1, $result);
                }
                $this->ajaxMsg(0, $result);
            }

            $aCondition = $this->post([
                'page' => parent::VAR_TYPE_INT,
                'rows' => parent::VAR_TYPE_INT,
                'starttime' => parent::VAR_TYPE_DATETIME,
                'endtime' => parent::VAR_TYPE_DATETIME,
                'sfinishtime' => parent::VAR_TYPE_DATETIME,
                'efinishtime' => parent::VAR_TYPE_DATETIME,
                'status' => parent::VAR_TYPE_INT,
                'company_fastpayacc_id' => parent::VAR_TYPE_INT,
                'min' => parent::VAR_TYPE_FLOAT,
                'max' => parent::VAR_TYPE_FLOAT,
                'search_type' => parent::VAR_TYPE_INT,
                'key_word' => parent::VAR_TYPE_STR,
                'layerid' => parent::VAR_TYPE_STR,
                'paytypeid' => parent::VAR_TYPE_INT
            ]);

            //判断前端传过来的数据是否合法，不合法返回数组第一个值
            if (!in_array($aCondition['rows'],$aPageNumber)){
                $aCondition['rows'] = $aPageNumber[0];
            }
            /* @var $oDepositfastpay model_userdepositfastpay */
            $oDepositfastpay = A::singleton('model_userdepositfastpay');
            $aList = $oDepositfastpay->getList($this->lvtopid, $aCondition);
            if ($aList) {
                $this->outPutJQgruidJson($aList['results'], $aList['affects'], $aCondition['page'], $aCondition['rows'],$aList['total']);
            } else {
                die(json_encode(['error' => 1, 'msg' => '请求失败！']));
            }
        } else {
            // 获取用户层级
            /* @var $oUserLayer model_userlayer */
//            $oUserLayer = A::singleton('model_userlayer');
//            $aLayerList = $oUserLayer->getLayerList($this->lvtopid);
//            $this->oViewer->assign('aLayerList', $aLayerList);
            // 日期开始时间
            $this->oViewer->assign('edate', date('Y-m-d 00:00:00', strtotime('+1 day')));
            // 日期结束时间
            $this->oViewer->assign('sdate', date('Y-m-d 00:00:00'));
            // 入款账号
            /* @var $oPayAcc model_proxyfastpayacc */
            $oPayAcc = A::singleton('model_proxyfastpayacc');
            $aList = $oPayAcc->getAllList($this->lvtopid, -1);
            $this->oViewer->assign('acc_list', $aList);
            // 充值方式
            /* @var $oPayType model_fastpaytype */
            $oPayType = A::singleton('model_fastpaytype');
            $aPayTypeList = $oPayType->getList();
            $this->oViewer->assign('pay_type_list', $aPayTypeList);
            $this->oViewer->assign('page_number', json_encode($aPageNumber));//分页条数
            $this->oViewer->display('fastpaymoney_list.html');
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
            // 取消
            $iId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $sRemark = isset($_GET['remark']) ? daddslashes(trim($_GET['remark'])) : '';

            if (empty($iId) || !is_numeric($iId) || empty($sRemark)) {
                die(json_encode(['error' => -1, 'msg' => '参数错误！']));
            }

            // 是否已经锁定
            if ($this->is_lock($oMemCache, $iId)) {
                die(json_encode(['error' => -1, 'msg' => '当前记录有人正在操作，请稍等！']));
            }

            // 加锁
            $this->add_lock($oMemCache, $iId);

            try {
                // 执行逻辑
                /* @var $oDepositFastPay model_userdepositfastpay */
                $oDepositFastPay = A::singleton('model_userdepositfastpay');
                $bResult = $oDepositFastPay->cancel($this->lvtopid, $iId, $this->loginProxyId, $this->adminname, $sRemark);
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
            // 确定
            $iId = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $sRemark = isset($_POST['remark']) ? daddslashes(trim($_POST['remark'])) : '';

            if (empty($iId) || !is_numeric($iId) || empty($sRemark)) {
                die(json_encode(['error' => -1, 'msg' => '参数错误！']));
            }

            // 是否已经锁定
            if ($this->is_lock($oMemCache, $iId)) {
                die(json_encode(['error' => -1, 'msg' => '当前记录有人正在操作，请稍等！']));
            }

            /* @var $oDepositFastPay model_userdepositfastpay */
            $oDepositFastPay = A::singleton('model_userdepositfastpay');

            //超过30天禁止补单
            $aDeposit = $oDepositFastPay->getUserDepositFastpayOne($this->lvtopid, $iId);
            if (empty($aDeposit)){
                die(json_encode(['error' => -1, 'msg' => '没找到当前记录！']));
            }

            $iDiffer = time() - strtotime($aDeposit['inserttime']);
            if ($iDiffer > (86400 * 30)){
                die(json_encode(['error' => -1, 'msg' => '补单时间为30天内！']));
            }
            // 加锁
            $this->add_lock($oMemCache, $iId);
            try {
                $bResult = $oDepositFastPay->confirm($this->lvtopid, $iId, $this->loginProxyId, $this->adminname, $sRemark);
            } catch (Exception $e) {
                // 解锁
                $this->remove_lock($oMemCache, $iId);
            }

            // 解锁
            $this->remove_lock($oMemCache, $iId);

            if (true === $bResult) {
                die(json_encode([
                    'error' => 0,
                    'msg' => '操作成功！'
                ]));
            } else {
                die(json_encode([
                    'error' => -1,
                    'msg' => $bResult
                ]));
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
        $sKey = 'fastpay_money_lock_' . $iId;
        return $oMemCache->get($sKey);
    }

    /**
     * 加锁
     * @param memcachedb $oMemCache
     * @param $iId
     * @param int $expire
     */
    private function add_lock(memcachedb $oMemCache, $iId, $expire = 1800) {
        $sKey = 'fastpay_money_lock_' . $iId;
        $oMemCache->set($sKey, 1, 0, 1800);
    }

    /**
     * 解锁
     * @param memcachedb $oMemCache
     * @param $iId
     */
    private function remove_lock(memcachedb $oMemCache, $iId) {
        $sKey = 'fastpay_money_lock_' . $iId;
        $oMemCache->delete($sKey);
    }
}

