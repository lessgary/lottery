<?php

/**
 * 文件 : /_app/controller/tripartite.php
 * 功能 : 控制器 - 三方管理
 *
 * @author    Ben
 * @version   1.0.0
 * @package   proxyweb
 *
 */
class controller_tripartite extends pcontroller {
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
     * 列表
     * @author Ben
     * @date 2017-06-26
     */
    public function actionList() {
        static $aPageNumber = array(10,50,100,150,200,500);//分页条数
        if ($this->getIsPost()) {
            $aCondition = $this->post([
                'page' => parent::VAR_TYPE_INT,
                'rows' => parent::VAR_TYPE_INT,
                'status' => parent::VAR_TYPE_INT,
                'account_type' => parent::VAR_TYPE_INT,
                'company_name' => parent::VAR_TYPE_STR,
                'paytypeid' => parent::VAR_TYPE_INT
            ]);
            /* @var $aProxyfastPayAcc model_proxyfastpayacc */
            $aProxyfastPayAcc = a::singleton('model_proxyfastpayacc');
            if (!isset($_POST['status'])) {
                $aCondition['status'] = -1;
            }
            if (!isset($_POST['paytypeid'])) {
                $aCondition['paytypeid'] = -1;
            }
            if (!isset($_POST['account_type'])) {
                $aCondition['account_type'] = -1;
            }
            if(isset($aCondition['company_name']) && $aCondition['company_name'] != '') {
                $aFastPayCompany = $aProxyfastPayAcc->getFastPayCompany($aCondition['company_name']);
            }
            //判断前端传过来的数据是否合法，不合法返回数组第一个值
            if (!in_array($aCondition['rows'],$aPageNumber)){
                $aCondition['rows'] = $aPageNumber[0];
            }
            $sWhere = " `lvtopid`='$this->lvtopid' AND `status` > 0 ";
            if (-1 != $aCondition['status']) {
                $sWhere .= " AND  `status` = '".$aCondition['status']."'";
            }
            if (-1 != $aCondition['paytypeid']) {
                $sWhere .= " AND `paytypeid` = '".$aCondition['paytypeid']."'";
            }
            if (-1 != $aCondition['account_type']) {
                $sWhere .= " AND `account_type` = '".$aCondition['account_type']."'";
            }
            if (!empty($aFastPayCompany)) {
                $aCompanyId = array_column($aFastPayCompany,'id');
                $sCompanyId = implode(",",$aCompanyId);
                $sWhere .= " AND `companyid` IN (".$sCompanyId.")";
            }
            $aList = $aProxyfastPayAcc->getList($sWhere, $aCondition['rows'], $aCondition['page']);
            $aWithdrawAcc = $aProxyfastPayAcc->getWithdrawAcc($this->lvtopid);
            foreach($aList['results'] as $k => &$v){
                if (empty($aWithdrawAcc)){
                    $v['withdrawAcc'] = 1;
                }else{
                    $v['withdrawAcc'] = 0;
                }
            }
            if (!empty($aList) && !empty($aCondition)) {
                $this->outPutJQgruidJson($aList['results'], $aList['affects'], $aCondition['page'], $aCondition['rows']);
            }
        } else {
            // 支付公司列表
            /** @var  $oFastPayCompany model_fastpaycompany */
            $oFastPayCompany = a::singleton('model_fastpaycompany');
            $aPayCompany = $oFastPayCompany->getList();
            $this->oViewer->assign('pay_company', $aPayCompany);

            // 支付类型列表
            /* @var $oFastPayType model_fastpaytype */
            $oFastPayType = a::singleton('model_fastpaytype');
            $aPayType = $oFastPayType->getList();
            $this->oViewer->assign('pay_type', $aPayType);

            // 可选支付类型列表
            $aAvailablePayType = $oFastPayCompany->getFastPayType();
            $this->oViewer->assign('available_pay_type', json_encode($aAvailablePayType));

            // 用户层级列表
            $aLayer = $this->getLayerList();
            $this->oViewer->assign('layer_list', $aLayer);
            $this->oViewer->assign('page_number', json_encode($aPageNumber));//分页条数
            $this->oViewer->display('tripartite_list.html');
        }
    }

    /**
     * 获取用户层级
     * @author Ben
     * @date 2017-06-27
     * @return array
     */
    private function getLayerList() {
        /* @var $oUserLayer model_userlayer */
        $oUserLayer = a::singleton('model_userlayer');
        $aLayer = $oUserLayer->getLayerList($this->lvtopid);
        if ($aLayer) {
            $aReturn = [];
            foreach ($aLayer as $item) {
                $aReturn[$item['layerid']] = $item;
            }
        }
        return !empty($aReturn) ? $aReturn : [];
    }

    /**
     * 添加支付
     * @author Ben
     * @date 2017-06-26
     */
    public function actionAdd() {
        if ($this->getIsAjax() && isset($_GET['check_unique'])) {
            // 检验数据唯一性，重定向
            $this->checkUnique();
        }

        if ($this->getIsPost()) {
            $aData = $this->post([
                "paytypeid" => parent::VAR_TYPE_INT,
                "companyid" => parent::VAR_TYPE_INT,
                "isshowmobile" => parent::VAR_TYPE_INT,
                "nickname" => parent::VAR_TYPE_STR,
                "merchantid" => parent::VAR_TYPE_STR,
                "key" => parent::VAR_TYPE_STR,
                "pubkey" => parent::VAR_TYPE_STR,
                "redirecturl" => parent::VAR_TYPE_STR,
                "status" => parent::VAR_TYPE_STR,
                "ispoint" => parent::VAR_TYPE_STR,
                "use_set" => parent::VAR_TYPE_STR,
                "quota" => parent::VAR_TYPE_FLOAT,
                "min_deposit" => parent::VAR_TYPE_FLOAT,
                "max_deposit" => parent::VAR_TYPE_FLOAT,
                "seq" => parent::VAR_TYPE_INT
            ]);
            $pattern = "/^http(s)?:\/\/([\w-]+\.)+\w+/i";
            if(!preg_match($pattern, $aData['redirecturl'])) {
                $this->ajaxMsg(0,"请填写带有协议的url，支持协议：http://或者https://");
            }
            //层级必须选一个
            if (isset($_POST['user_layerids']) && !empty($_POST['user_layerids']) && is_array($_POST['user_layerids'])) {
                $aData['user_layerids'] = implode(',', $_POST['user_layerids']);
            }else{
                $this->ajaxMsg(0,"请至少选择一个层级");
            }
            if($aData['user_layerids'] < 0){
                $aData['account_type'] = 1;
            }
            //固定金额组装
            if (isset($_POST['amount_set']) && !empty($_POST['amount_set']) && is_array($_POST['amount_set'])) {
                $aData['amount_set'] = implode(',', $_POST['amount_set']);
            }
            if ($aData['use_set'] != 1) {
                if ($aData['min_deposit'] > $aData['max_deposit']) {
                    $this->ajaxMsg(0,"单笔最小限额不能大于单笔最大限额");
                }
            } else {
                //固定金额不能为空
                if (empty($aData['amount_set'])) {
                    $this->ajaxMsg(0,"固定金额不能为空");
                }
                //固定金额不能重复
                if($this->FetchRepeatMemberInArray($_POST['amount_set'])){
                    $this->ajaxMsg(0,"固定金额不能重复");
                }
                //数据是否合法
                if(!$this->FixedAmountJudgment($_POST['amount_set'])){
                    $this->ajaxMsg(0,"固定金额格式错误请重新填写");
                }
            }
            //数据是否合法
            if($this->checkUnique(1) != 1){
                $this->ajaxMsg(0,"商户代称已经存在");
            }
            $aData['lvtopid'] = $this->lvtopid;
            $aData['adminname'] = $this->adminname;
            $aData['adminid'] = $this->loginProxyId;
            $aData['key'] = authcode($aData['key'], 'ENCODE', $GLOBALS['config']['AesKeys']);
            $oProxyFastPayAcc = new model_proxyfastpayacc();
            if ($aData['account_type'] == 1) {
                $aWithdrawAcc = $oProxyFastPayAcc->checkUnique($this->lvtopid,"account_type = 1 AND `status` = 1");
                if ($aWithdrawAcc > 0 && $aData['status'] == 1) {
                    $this->ajaxMsg(0,"出款三方同时只可开启一个");
                }
            }
            if ($oProxyFastPayAcc->add($aData)) {
//                sysMessage('添加成功');
                $this->ajaxMsg(1,"添加成功");
            } else {
                $this->ajaxMsg(0,"添加失败，请联系管理员");
            }
        } else {
            $this->ajaxMsg(0,"非法请求!");
        }
    }

    /**
     * 编辑支付
     * @author Ben
     * @date 2017-06-27
     */
    public function actionEdit() {
        if ($this->getIsPost()) {
            $aData = $this->post([
                "paytypeid" => parent::VAR_TYPE_INT,
                "companyid" => parent::VAR_TYPE_INT,
                "isshowmobile" => parent::VAR_TYPE_INT,
                "nickname" => parent::VAR_TYPE_STR,
                "merchantid" => parent::VAR_TYPE_STR,
                "key" => parent::VAR_TYPE_STR,
                "pubkey" => parent::VAR_TYPE_STR,
                "redirecturl" => parent::VAR_TYPE_STR,
                "status" => parent::VAR_TYPE_STR,
                "ispoint" => parent::VAR_TYPE_STR,
                "use_set" => parent::VAR_TYPE_STR,
                "seq" => parent::VAR_TYPE_INT,
                "quota" => parent::VAR_TYPE_FLOAT,
                "current_quota" => parent::VAR_TYPE_FLOAT,
                "min_deposit" => parent::VAR_TYPE_FLOAT,
                "max_deposit" => parent::VAR_TYPE_FLOAT,
                "id" => parent::VAR_TYPE_STR
            ]);
            if (empty($aData['id'])) {
                $this->ajaxMsg(0,"记录不存在!");
            }
            $pattern = "/^http(s)?:\/\/([\w-]+\.)+\w+/i";
            if(!preg_match($pattern, $aData['redirecturl'])) {
                $this->ajaxMsg(0,"请填写带有协议的url，支持协议：http://或者https://");
            }
            if (isset($_POST['user_layerids']) && !empty($_POST['user_layerids']) && is_array($_POST['user_layerids'])) {
                $aData['user_layerids'] = implode(',', $_POST['user_layerids']);
            } else {
                $this->ajaxMsg(0,"请至少选择一个层级");
            }
            //组装固定金额
            if (isset($_POST['amount_set']) && !empty($_POST['amount_set']) && is_array($_POST['amount_set']) && ($_POST['use_set']==1)) {
                $aData['amount_set'] = implode(',', $_POST['amount_set']);
                //如果选择固定金额入款，充值最大金额，和最小金额
                $aData['min_deposit']=0;
                $aData['max_deposit']=0;
            } else {
                $Data['amount_set'] = '';
            }
            //判断入款类型
            if ($aData['use_set'] != 1) {
                if ($aData['min_deposit'] > $aData['max_deposit']) {
                    $this->ajaxMsg(0,"单笔最小限额不能大于单笔最大限额");
                }
            } else {
                if (empty($_POST['amount_set'])) {
                    $this->ajaxMsg(0,"固定金额不能为空");
                }
                //固定金额不能重复
                if($this->FetchRepeatMemberInArray($_POST['amount_set'])){
                    $this->ajaxMsg(0,"固定金额不能重复");
                }
                //数据是否合法

                if(!$this->FixedAmountJudgment($_POST['amount_set'])){
                    $this->ajaxMsg(0,"固定金额格式错误请重新填写");
                }

            }
            $iLvtopid = $this->lvtopid;
            $aData['adminname'] = $this->adminname;
            $aData['adminid'] = $this->loginProxyId;
//            $aData['quota'] = $aData['quota'] ? $aData['quota'] : 0;
            if (empty($aData['key'])) {
                unset($aData['key']);
            } else {
                $aData['key'] = authcode($aData['key'], 'ENCODE', $GLOBALS['config']['AesKeys']);
            }
            /* @var $oProxyFastPayAcc model_proxyfastpayacc */
            $oProxyFastPayAcc = a::singleton('model_proxyfastpayacc');
            if (false !== $oProxyFastPayAcc->edit($aData, "`id`='${aData['id']}' AND `lvtopid`='${iLvtopid}'")) {
//                sysMessage('修改成功');
                $this->ajaxMsg(1,"修改成功");exit();

            } else {
                $this->ajaxMsg(0,"修改失败，请联系管理员");
            }
        } else {
            $this->ajaxMsg(0,"非法请求！");
        }
    }

    //验证固定金额
    function FixedAmountJudgment($aData){
        foreach ($aData as $k=>$v){
            if(!preg_match("/^[1-9]\d*$/",$v)){
                return false;
            }
        }
        return true;
    }


    function FetchRepeatMemberInArray($array) {
        // 获取去掉重复数据的数组
        $unique_arr = array_unique ( $array );
        // 获取重复数据的数组
        $repeat_arr = array_diff_assoc ( $array, $unique_arr );
        return $repeat_arr;
    }

    /**
     * 删除支付
     * @author Ben
     * @date 2017-06-27
     */
    public function actionDelete() {
        if ($this->getIsAjax() && $this->getIsPost() && !empty($_POST['id']) && is_numeric($_POST['id'])) {
            /* @var $oProxyFastPayAcc model_proxyfastpayacc */
            $oProxyFastPayAcc = a::singleton('model_proxyfastpayacc');
            $iLvtopid = $this->lvtopid;
            $iId = intval($_POST['id']);
            $aData = [
                'status' => 0,
                'adminname' => $this->adminname,
                'adminid' => $this->loginProxyId
            ];
            if (false !== $oProxyFastPayAcc->edit(
                    $aData,
                    "`lvtopid`='${iLvtopid}' AND `id`='${iId}'")
            ) {
                die(json_encode(['result' => 1, 'msg' => '删除成功！']));
            } else {
                die(json_encode(['result' => 0, 'msg' => '操作失败，请联系管理员！']));
            }
        } else {
            $this->error('非法请求');
        }
    }

    /**
     * 异步验证数据唯一性
     * @author Ben
     * @date 2017-07-13
     */
    private function checkUnique($sBreak = 0) {
        if ($this->getIsPost() && $this->getIsAjax()) {
            $aData = $this->post([
                'nickname' => parent::VAR_TYPE_STR,
                'merchantid' => parent::VAR_TYPE_STR,
                'id' => parent::VAR_TYPE_INT
            ]);
            /* @var $oProxyFastPayAcc model_proxyfastpayacc */
            $oProxyFastPayAcc = a::singleton('model_proxyfastpayacc');
            if (!empty($aData['nickname'])) {
                $sCondition = "nickname='${aData['nickname']}'";
            } else if (!empty($aData['merchantid'])) {
                $sCondition = "merchantid='${aData['merchantid']}'";
            } else {
                die(json_encode(['error' => -1, 'msg' => '非法请求！']));
            }
            $sCondition .= ' AND `status` > 0';

            if (!empty($aData['id'])) {
                $sCondition .= " AND `id` <> '${aData['id']}'";
            }

            $iResult = $oProxyFastPayAcc->checkUnique($this->lvtopid, $sCondition);
            if (false === $iResult) {
                die(json_encode(['error' => -1, 'msg' => '服务器忙，请稍后再试！']));
            }
            if ($sBreak == 0){
                die(json_encode(['error' => $iResult > 0 ? 0 : 1, 'msg' => '请求成功!']));
            }else{
                return $iResult > 0 ? 0 : 1;
            }
        } else {
            die(json_encode(['error' => -1, 'msg' => '非法请求！']));
        }
    }

    /**
     * 清零三方账号额度
     */
    public function actionClearQuota(){
        $aGetData = $this->post(array(
            "id" => parent::VAR_TYPE_INT,
        ));
        $iLvtopid = $this->lvtopid;
        $aData['current_quota']=0;
        /* @var $aProxyfastPayAcc model_proxyfastpayacc */
        $aProxyfastPayAcc = a::singleton('model_proxyfastpayacc');
        $bResult = $aProxyfastPayAcc->edit($aData, "`id`='${aGetData['id']}' AND `lvtopid`='${iLvtopid}'");
        if ($bResult !== false) {
            die(json_encode(['result' => 1, 'msg' => '已清零！']));
        }else{
            die(json_encode(['result' => 0, 'msg' => '操作失败！']));
        }
    }

    /**
     * 三方账号置顶
     */
    public function actionIsUp(){
        $aGetData = $this->post(array(
            "id" => parent::VAR_TYPE_INT,
            "paytypeid" => parent::VAR_TYPE_INT,
            "seq" => parent::VAR_TYPE_INT,
        ));
        $aData['seq']=$aGetData['seq'];
        $aData['id']=$aGetData['id'];
        $aData['paytypeid']=$aGetData['paytypeid'];
        $aData['lvtopid']=$this->lvtopid;
        /* @var $aProxyfastPayAcc model_proxyfastpayacc */
        $aProxyfastPayAcc = a::singleton('model_proxyfastpayacc');
        $bResult = $aProxyfastPayAcc->doUp($aData);
        if ($bResult !== false) {
            die(json_encode(['result' => 1, 'msg' => '操作成功！']));
        }else{
            die(json_encode(['result' => 0, 'msg' => '操作失败！']));
        }
    }
}