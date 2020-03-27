<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/24
 * Time: 15:38
 */
class controller_cash extends pcontroller{
    //构造函数
    public function __construct() {
        parent::__construct();
    }

    //账变列表数据初始化
    private function setData($aArray){
        if(!empty($aArray)){
            /* @var $oVendors model_vendor_pvendors */
            $oVendors = A::singleton('model_vendor_pvendors');
            $aVendors = $oVendors->getAllVendor();
            foreach ($aArray as $k=>&$v){
                if(in_array($v['ordertypeid'],[33,34])){
                    foreach ($aVendors as $key=>$val){
                        if($val['id'] == $v['touserid']){
                            $v['terrace'] = $val['name'];
                        }
                    }
                }elseif(in_array($v['ordertypeid'],[1,2])){
                    $v['terrace'] = '彩票(主账户)';
                }else{
                    $v['terrace'] = $v['cnname']?'彩票('.$v['cnname'].')':'彩票(主账户)';
                }
            }
        }
        return $aArray;
    }

    /**
     * 账变管理
     */
    public function actionOrderList(){
        set_time_limit(240); // 限制4min
        $oOrder = new model_porders();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "isSubordinate" => parent::VAR_TYPE_INT,
                "orderNum" => parent::VAR_TYPE_STR,
                "otid" => parent::VAR_TYPE_STR,
                "searchType" => parent::VAR_TYPE_STR,
                "username" => parent::VAR_TYPE_STR,
                "sort" => parent::VAR_TYPE_STR,
                "clientIp" => parent::VAR_TYPE_STR,
                "minmoney" => parent::VAR_TYPE_FLOAT,
                "maxmoney" => parent::VAR_TYPE_FLOAT,
                "sTime" => parent::VAR_TYPE_DATETIME,
                "eTime" => parent::VAR_TYPE_DATETIME,
            ));
            $aGetData['otid'] = isset($aGetData['otid']) ? daddslashes(trim($aGetData['otid'])) : "";
            $aGetData['clientIp'] = isset($aGetData['clientIp']) ? daddslashes(trim($aGetData['clientIp'])) : "";
            $aGetData['orderNum'] = isset($aGetData['orderNum']) ? daddslashes(trim($aGetData['orderNum'])) : "";
            if (time() <= strtotime(date('Y-m-d ' . $this->sReportTime))) {//3点之前,显示前一天的数据
                $aGetData['sTime'] = isset($aGetData['sTime']) ? trim($aGetData['sTime']) : date('Y-m-d ' . $this->sReportTime, time() - 86400); // 当天账变
                $aGetData['eTime'] = isset($aGetData['eTime']) ? trim($aGetData['eTime']) : date('Y-m-d ' . $this->sReportTime, time());
            } else {
                $aGetData['sTime'] = isset($aGetData['sTime']) ? trim($aGetData['sTime']) : date('Y-m-d ' . $this->sReportTime, time()); // 当天账变
                $aGetData['eTime'] = isset($aGetData['eTime']) ? trim($aGetData['eTime']) : date('Y-m-d ' . $this->sReportTime, time() + 86400);
            }
            $aGetData['username'] = isset($aGetData['username']) ? daddslashes(trim($aGetData['username'])) : "";
            $aGetData['sTime'] = getFilterDate($aGetData['sTime']);
            $aGetData['eTime'] = getFilterDate($aGetData['eTime']);
            $aGetData["sort"] = isset($aGetData["sort"]) && $aGetData["sort"] ? $aGetData["sort"] : "DESC";
            $sWhere =  "ut.`lvtopid` = '$this->lvtopid' AND ut.`isdeleted`='0' AND ut.`userid` != '".$this->lvtopid."'";
            // WHERE 条件变量声明
            // 0001, 索引: 账变类型索引
            /* @var $oOrder model_orders */
            if ($aGetData['otid'] != '') {
                $sWhere .= " AND o.`ordertypeid` IN ( ".$aGetData['otid']." ) ";
            }
            // 0002, 索引 times
            if ($aGetData['sTime'] != '') { // 账变时间 起始于...
                $sWhere .= " AND o.`times` >= '" . daddslashes($aGetData['sTime']) . "' ";
            }
            if ($aGetData['eTime'] != '') { // 账变时间 截止于...
                $sWhere .= " AND o.`times` <= '" . daddslashes($aGetData['eTime']) . "' ";
            }
            // 0003, 索引 amount
            if ($aGetData['minmoney'] != '') {
                $sWhere .= " AND o.`availablebalance` > '" . $aGetData['minmoney'] . "' ";
            }
            if ($aGetData['maxmoney'] != '') {
                $sWhere .= " AND o.`availablebalance` < '" . $aGetData['maxmoney'] . "' ";
            }
            // 0004, 用户订单号ip 部分
            if ($aGetData['searchType'] == "orderNum"){// 订单号搜索
                if ($aGetData['orderNum'] != "" ) {
                    $aOrderNo = myHash($aGetData['orderNum'], 'DECODE', 'O');
                    if ($aOrderNo != 0) {
                        $sWhere .= " AND o.`entry` = '" . intval($aOrderNo) . "' ";
                    } else {
                        $sWhere .= " AND o.`entry` = 0 ";
                    }
                }
            }else if ($aGetData['searchType'] == "username"){//用户名搜索
                if ($aGetData['username'] != '') {
                    // 获取用户ID
                    /* @var $oUser model_user */
                    $oUser = new model_user();
                    $aUserInfo = $oUser->getUser($aGetData['username'],$this->lvtopid);
                    if (empty($aUserInfo)){
                        $iUserId = -1;
                    }else{
                        $iUserId = $aUserInfo['userid'];
                    }

                    if ($iUserId == 0 || is_array($iUserId)) { // 搜索的用户名未找到, 并且不允许通配符搜索
                        $sWhere .= " AND 0 ";
                    } else {
                        if ($aGetData['isSubordinate'] == 1) { // 包含下级
                            $sWhere .= " AND ( ut.`userid`='" . $iUserId . "' OR FIND_IN_SET('" . $iUserId . "',ut.`parenttree`) ) ";
                        } else {
                            $sWhere .= " AND ut.`userid` = '$iUserId' ";
                        }
                    }
                }
            }else if ($aGetData['searchType'] == "clientIp"){// 操作地址模糊搜索
                if ($aGetData['clientIp'] != '') {
                    if (strstr($aGetData['clientIp'], '*')) {
                        $sWhere .= " AND o.`clientip` LIKE '" . str_replace('*', '%', $aGetData['clientIp']) . "' ";
                    } else {
                        $sWhere .= " AND o.`clientip` = '" . $aGetData['clientIp'] . "' ";
                    }
                }
            }
            $aResult =  $oOrder->getAdminOrderList('*', $sWhere,$aGetData['sort'], $aGetData['rows'], $aGetData['page'], ""); // 获取数据结果集
            if (!empty($aResult['affects']) && !empty($aResult['results']) && is_array($aResult['results'])) { // 进行数据整理(更新订单号), 对当页数据进行小结
                foreach ($aResult['results'] as &$v) {
                    $v['signamount'] = $oOrder->getOrdersTypeInOut($v['ordertypeid']);
                    $v['orderno'] = myHash($v['entry'], "ENCODE", 'O');
                    if ($v["projectid"] > 0) {
                        $v["projectid"] = myHash($v["projectid"], "ENCODE", 'P');
                    }
                    if ($v['signamount'] == 1){
                        $v['riseAmount'] = "+".$v['amount'];
                    }else if ($v['signamount'] == 0){
                        $v['decreaseAmount'] = "-".$v['amount'];
                    }
                    if ($v['ordertypeid'] == 21){
                        $v['title'] = $v['description'];
                    }
                    if ($v['description'] != ''){
                        $v['remark'] = $v['description'];
                    }else{
                        $v['remark'] = "-----";
                    }
                }
            }

            if (!empty($aResult) && !empty($aGetData)) {
                $aResult['results'] = $this->setData($aResult['results']);//初始化数据 加入平台信息等
                $this->outPutJQgruidJson($aResult['results'],$aResult['affects'] , $aGetData['page'], $aGetData['rows']);
            }
        }else{
            $aOrderType = $oOrder->getOrderType('arr');
            $GLOBALS['oView']->assign('orderType', $aOrderType);
            $GLOBALS['oView']->assign('ur_here', '账变列表');
            $GLOBALS['oView']->assign('startDate', date('Y-m-d '. $this->sReportTime));
            $GLOBALS['oView']->assign('sevenDate', date('Y-m-d '. $this->sReportTime,strtotime('-6 days')));
            $GLOBALS['oView']->assign('endDate', date('Y-m-d '. $this->sReportTime, strtotime('+1 days')));
            $GLOBALS['oView']->display("cash_orderlist.html");
        }
    }

    /**
     * 支付设定列表
     */
    public function actionPaySetting(){
        $oPaySet = new model_payset();
        if ($this->getIsAjax()) {
            $aPaySetList = $oPaySet->paySetList($this->lvtopid);
            $this->outPutJQgruidJson($aPaySetList,count($aPaySetList));
        }else{
            $GLOBALS['oView']->display("cash_paysetting.html");
        }
    }

    /**
     * 新增支付设定
     */
    public function actionAddPaySet(){
        $oPaySet = new model_payset();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "title" => parent::VAR_TYPE_STR,
                "withdraw_isallowfree" => parent::VAR_TYPE_INT,
                "withdraw_freetimes" => parent::VAR_TYPE_INT,
                "withdraw_charge" => parent::VAR_TYPE_FLOAT,
                "withdraw_chargetype" => parent::VAR_TYPE_INT,
                "withdraw_chargemax" => parent::VAR_TYPE_FLOAT,
                "withdraw_times" => parent::VAR_TYPE_INT,
                "withdraw_max" => parent::VAR_TYPE_FLOAT,
                "withdraw_min" => parent::VAR_TYPE_FLOAT,
                "fastpay_favortime" => parent::VAR_TYPE_INT,
                "deposit_favortime" => parent::VAR_TYPE_INT,
                "fastpay_favorbase" => parent::VAR_TYPE_FLOAT,
                "deposit_favorbase" => parent::VAR_TYPE_FLOAT,
                "fastpay_favorrate" => parent::VAR_TYPE_FLOAT,
                "deposit_favorrate" => parent::VAR_TYPE_FLOAT,
                "fastpay_favormax" => parent::VAR_TYPE_FLOAT,
                "deposit_favormax" => parent::VAR_TYPE_FLOAT,
                "fastpay_max" => parent::VAR_TYPE_FLOAT,
                "deposit_max" => parent::VAR_TYPE_FLOAT,
                "fastpay_min" => parent::VAR_TYPE_FLOAT,
                "deposit_min" => parent::VAR_TYPE_FLOAT,
                "fastpay_extbets" => parent::VAR_TYPE_FLOAT,
                "fastpay_isextbets" => parent::VAR_TYPE_INT,
                "deposit_extbets" => parent::VAR_TYPE_FLOAT,
                "deposit_isextbets" => parent::VAR_TYPE_INT,
                "fastpay_betsrate" => parent::VAR_TYPE_FLOAT,
                "fastpay_isbets" => parent::VAR_TYPE_INT,
                "deposit_betsrate" => parent::VAR_TYPE_FLOAT,
                "deposit_isbets" => parent::VAR_TYPE_INT,
                "fastpay_betsreducerate" => parent::VAR_TYPE_FLOAT,
                "deposit_betsreducerate" => parent::VAR_TYPE_FLOAT,
                "fastpay_betschargerate" => parent::VAR_TYPE_FLOAT,
                "deposit_betschargerate" => parent::VAR_TYPE_FLOAT
            ));
            $aLocation = array(0=>array('text'=>'返回支付设定','href'=>url('cash','paysetting')));
            if ($aGetData['title'] == ""){
                sysMessage( '名称不可以为空', 1, $aLocation );
                exit;
            }
            //入库数据
            $aData = [] ;
            $aData['lvtopid'] = $this->lvtopid ;
            $aData['title'] = $aGetData['title'];
            $aData['withdraw_isallowfree'] = $aGetData['withdraw_isallowfree'];
            $aData['withdraw_freetimes'] = $aGetData['withdraw_freetimes'];
            $aData['withdraw_charge'] = $aGetData['withdraw_charge'];
            $aData['withdraw_chargetype'] = $aGetData['withdraw_chargetype'];
            $aData['withdraw_chargemax'] = $aGetData['withdraw_chargemax'];
            $aData['withdraw_times'] = $aGetData['withdraw_times'];
            $aData['withdraw_max'] = $aGetData['withdraw_max'];
            $aData['withdraw_min'] = $aGetData['withdraw_min'];
            $aData['fastpay_favortime'] = $aGetData['fastpay_favortime'];
            $aData['deposit_favortime'] = $aGetData['deposit_favortime'];
            $aData['fastpay_favorbase'] = $aGetData['fastpay_favorbase'];
            $aData['deposit_favorbase'] = $aGetData['deposit_favorbase'];
            $aData['fastpay_favorrate'] = $aGetData['fastpay_favorrate'];
            $aData['deposit_favorrate'] = $aGetData['deposit_favorrate'];
            $aData['fastpay_favormax'] = $aGetData['fastpay_favormax'];
            $aData['deposit_favormax'] = $aGetData['deposit_favormax'];
            $aData['fastpay_max'] = $aGetData['fastpay_max'];
            $aData['deposit_max'] = $aGetData['deposit_max'];
            $aData['fastpay_min'] = $aGetData['fastpay_min'];
            $aData['deposit_min'] = $aGetData['deposit_min'];
            $aData['fastpay_extbets'] = $aGetData['fastpay_extbets'];
            $aData['fastpay_isextbets'] = $aGetData['fastpay_isextbets'];
            $aData['deposit_extbets'] = $aGetData['deposit_extbets'];
            $aData['deposit_isextbets'] = $aGetData['deposit_isextbets'];
            $aData['fastpay_betsrate'] = $aGetData['fastpay_betsrate'];
            $aData['fastpay_isbets'] = $aGetData['fastpay_isbets'];
            $aData['deposit_betsrate'] = $aGetData['deposit_betsrate'];
            $aData['deposit_isbets'] = $aGetData['deposit_isbets'];
            $aData['fastpay_betsreducerate'] = $aGetData['fastpay_betsreducerate'];
            $aData['deposit_betsreducerate'] = $aGetData['deposit_betsreducerate'];
            $aData['fastpay_betschargerate'] = $aGetData['fastpay_betschargerate'];
            $aData['deposit_betschargerate'] = $aGetData['deposit_betschargerate'];
            $result = $oPaySet->addPaySet($aData,$this->lvtopid);
            if ($result === true) {
                sysMessage( '添加成功', 0, $aLocation );
            } else {
                sysMessage( $result, 1, $aLocation );
            }
        } else {
            $GLOBALS['oView']->display("cash_addpayset.html");
        }
    }

    /**
     * 修改支付设定
     */
    public function actionEditPaySet(){
        $id = intval($_GET['id']);
        $oPaySet = new model_payset();
        $aPaySetList = $oPaySet->paySetList($this->lvtopid,$id);
        $aLocation = array(0=>array('text'=>'返回支付设定','href'=>url('cash','paysetting')));
        if($aPaySetList['lvtopid'] != $this->lvtopid){
            sysMessage( '非法操作', 1, $aLocation );
        }
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "title" => parent::VAR_TYPE_STR,
                "withdraw_isallowfree" => parent::VAR_TYPE_INT,
                "withdraw_freetimes" => parent::VAR_TYPE_INT,
                "withdraw_charge" => parent::VAR_TYPE_FLOAT,
                "withdraw_chargetype" => parent::VAR_TYPE_INT,
                "withdraw_chargemax" => parent::VAR_TYPE_FLOAT,
                "withdraw_times" => parent::VAR_TYPE_INT,
                "withdraw_max" => parent::VAR_TYPE_FLOAT,
                "withdraw_min" => parent::VAR_TYPE_FLOAT,
                "fastpay_favortime" => parent::VAR_TYPE_INT,
                "deposit_favortime" => parent::VAR_TYPE_INT,
                "fastpay_favorbase" => parent::VAR_TYPE_FLOAT,
                "deposit_favorbase" => parent::VAR_TYPE_FLOAT,
                "fastpay_favorrate" => parent::VAR_TYPE_FLOAT,
                "deposit_favorrate" => parent::VAR_TYPE_FLOAT,
                "fastpay_favormax" => parent::VAR_TYPE_FLOAT,
                "deposit_favormax" => parent::VAR_TYPE_FLOAT,
                "fastpay_max" => parent::VAR_TYPE_FLOAT,
                "deposit_max" => parent::VAR_TYPE_FLOAT,
                "fastpay_min" => parent::VAR_TYPE_FLOAT,
                "deposit_min" => parent::VAR_TYPE_FLOAT,
                "fastpay_extbets" => parent::VAR_TYPE_FLOAT,
                "fastpay_isextbets" => parent::VAR_TYPE_INT,
                "deposit_extbets" => parent::VAR_TYPE_FLOAT,
                "deposit_isextbets" => parent::VAR_TYPE_INT,
                "fastpay_betsrate" => parent::VAR_TYPE_FLOAT,
                "fastpay_isbets" => parent::VAR_TYPE_INT,
                "deposit_betsrate" => parent::VAR_TYPE_FLOAT,
                "deposit_isbets" => parent::VAR_TYPE_INT,
                "fastpay_betsreducerate" => parent::VAR_TYPE_FLOAT,
                "deposit_betsreducerate" => parent::VAR_TYPE_FLOAT,
                "fastpay_betschargerate" => parent::VAR_TYPE_FLOAT,
                "deposit_betschargerate" => parent::VAR_TYPE_FLOAT
            ));
            foreach ($aGetData as $key => $value) {
                if( isset($aPaySetList[$key])) {
                    if($aPaySetList[$key] == $aGetData[$key]) {
                        continue ;
                    } else {
                        $aData[$key] = $value;
                    }
                }
            }
            if(empty($aData)){
                sysMessage("没有需要更新的数据",1, $aLocation);
            }
            //基本资料修改
            $mResult = $oPaySet->editPaySet($aData,$id);
            if ($mResult) {
                sysMessage("更新成功",0, $aLocation);
            } else {
                sysMessage("更新失败,参数不合法",1, $aLocation);
            }
        } else {
            $GLOBALS['oView']->assign("paysetlist",$aPaySetList);
            $GLOBALS['oView']->display("cash_editpayset.html");
        }
    }

    /**
     * 删除支付设定
     */
    public function actionDelPaySet(){
        $aGetData = $this->get(array(
            "id"=> parent::VAR_TYPE_INT,
        ));
        $oPaySet = new model_payset();
        $mResult = $oPaySet->delPaySet($this->lvtopid,$aGetData['id']);
        if($mResult) {
            sysMessage("删除成功");
        } else {
            sysMessage("此支付设定正被使用", 1);
        }
    }

    /**
     * 出款记录列表
     */
    public function actionWithdrawList(){
        $oUserLayer = new model_userlayer();
        $aUserLevel = $oUserLayer->getLayerList($this->lvtopid);
        if ($this->getIsAjax()) {
            $aPostData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT
            ));
            $aWithdrawList = $this->getAllWithdrawList();
            if (!empty($aPostData) && !empty($aWithdrawList)){
                //格式化金额
                foreach ($aWithdrawList['results'] as  $k => &$v){
                    $v['availablebalance'] = numberFormat2($v['availablebalance']);
                }
                $this->outPutJQgruidJson($aWithdrawList['results'],$aWithdrawList['affects'],$aPostData['page'], $aPostData['rows'],$aWithdrawList['total']);
            }
        }else {
            $sTimes = array("sTime" => parent::VAR_TYPE_DATE , "eTime" => parent::VAR_TYPE_DATE);
            $sSdate = !empty($this->get($sTimes)["sTime"]) ? date("Y-m-d 00:00:00") : date('Y-m-d ' . $this->sReportTime);//判断是用get参数还是给默认的
            $sEdate = !empty($this->get($sTimes)["eTime"]) ? date("Y-m-d 00:00:00",strtotime("+1 days")) : date('Y-m-d ' . $this->sReportTime, strtotime('+1 days')); //判断是用get参数还是给默认的   
            $GLOBALS['oView']->assign('userLevel', $aUserLevel);
            $GLOBALS['oView']->assign('sTime', $sSdate);
            $GLOBALS['oView']->assign('eTime', $sEdate);
            $GLOBALS['oView']->display("cash_withdrawlist.html");
        }
    }
    /**
     * 用户银行卡信息
     */
    public function actionUserBankInfo(){
        $userid = intval($_GET['userid']);
        $userbankcardid = intval($_GET['userbankcardid']);
        $apply_amount = $_GET['apply_amount'];
        $totalCharge = $_GET['totalCharge'];
        $real_amount = $_GET['real_amount'];
        $oBankeInfo = new model_bankinfo();
        $aUserBankInfo = $oBankeInfo->getUserBankInfo($userid,$this->lvtopid,$userbankcardid);
        $GLOBALS['oView']->assign('userbankinfo', $aUserBankInfo);
        $GLOBALS['oView']->assign('apply_amount', $apply_amount);
        $GLOBALS['oView']->assign('totalCharge', $totalCharge);
        $GLOBALS['oView']->assign('real_amount', $real_amount);
        $GLOBALS['oView']->display("cash_userbankinfo.html");
    }
    /**
     * 用户出款详情
     */
    public function actionWithdrawDetail(){
        $aGetData = $this->get(array(
            "userid" => parent::VAR_TYPE_INT,
            "id" => parent::VAR_TYPE_INT,
            "userbankcardid" => parent::VAR_TYPE_INT,
            "levelName" => parent::VAR_TYPE_STR,
            "applytime" => parent::VAR_TYPE_DATETIME,
            "verify_status" => parent::VAR_TYPE_INT,
        ));
        $iUserid = intval($aGetData['userid']);
        $iVerify_status = intval($aGetData['verify_status']);
        $iUserbankcardid = intval($aGetData['userbankcardid']);
        $iId = intval($aGetData['id']);
        $oBankInfo = new model_bankinfo();
        $oUserDepositFastPay = new model_userdepositfastpay();
        $oPuser = new model_puser();
        $oOrders = new model_porders();
        $oBankCard = new model_bankcard();
        $oPmanualpaychecked = new model_pmanualpaychecked();
        $aBankCard = $oBankCard->getBlackCard($aGetData['userid']);
        if (empty($aBankCard)){
            $iBankCard = "否";
        }else{
            $iBankCard = "是";
        }
        $aArtificial = $oPmanualpaychecked->getAllMoneyByUserId($iUserid,$aGetData['applytime']);
        $aOrderRecharge = $oOrders->getAdminOrderList('*', "`fromuserid` = '".$iUserid."' AND ordertypeid = 1","DESC", "", "", "");
        $aOrderWithdraw = $oOrders->getAdminOrderList('*', "`fromuserid` = '".$iUserid."' AND ordertypeid = 4","DESC", "", "", "");
        $sLastRecharge = isset($aOrderRecharge['results'][0]['times']) ? $aOrderRecharge['results'][0]['times'] : "0000-00-00 00:00:00";
        $sLastWithdraw = isset($aOrderWithdraw['results'][0]['times']) ? $aOrderWithdraw['results'][0]['times'] : "0000-00-00 00:00:00";
        $aReal_amount = $oUserDepositFastPay->getAllArtificialMoneyById($iUserid,$this->lvtopid,$aGetData['applytime']);
        $fReal_amount = isset($aReal_amount['real_amount']) ? $aReal_amount['real_amount'] : 0;
        $fArtificial = isset($aArtificial['allmoney']) ? $aArtificial['allmoney'] : 0;
        $aUserFund = $oPmanualpaychecked->getUserFundInfoByUserId($iUserid);
        $aUserInfo = $oPuser->getUserInfo($iUserid);
        $oProject = new model_pprojects;
        $aProject = $oProject->projectGetResult("", "P.`lvtopid` = '" .  $this->lvtopid ."' AND UT.`username` = '" .  $aUserInfo["username"] ."'", "", 2000, 1);
        $res = [];
        foreach ($aProject['results'] as $v){
            if(empty($res[$v['methodid']])) {
                $res[$v['methodid']] = [
                    'times' => 1,
                    'totalprices' => $v['totalprice'],
                    'totalbonus' => $v['bonus'],
                    'profit' => $v['bonus']-$v['totalprice'],
                ];
            } else {
                $res[$v['methodid']]['times'] 	   += 1;
                $res[$v['methodid']]['totalprices'] += $v['totalprice'];
                $res[$v['methodid']]['totalbonus'] += $v['bonus'];
                $res[$v['methodid']]['profit'] 	   += $v['bonus']-$v['totalprice'];
            }
            $oMethod = new model_method();
            $aMethod = $oMethod->methodGetList("a.`methodname`,a.`methodid`,b.`cnname`,b.`lotteryid`", "a.`pid`>0 AND a.methodid = '".$v['methodid']."'", '', 0, 0, TRUE);
            foreach ($aMethod as $method) {
                $aLottery[$method["lotteryid"]] = $method["cnname"];
                $aMethodName[$method['methodid']] = $method['methodname'];
            }
            $res[$v['methodid']]['methodname'] = $aMethodName[$v['methodid']];
            $res[$v['methodid']]['ccname'] = $aLottery[$v['lotteryid']];
        }

        $res = array_slice($res,0,10);
        $aUserBankInfo = $oBankInfo->getBankInfoByUserId($iUserid,$iUserbankcardid);
        if ($aUserBankInfo[0]['isblack'] == 0){
            $isThisBlack = "否";
        }else{
            $isThisBlack = "是";
        }
        if ($aUserBankInfo[0]['isupdate'] == 0){
            $isupdate = "否";
        }else{
            $isupdate = "是";
        }
        /* @var $oLoginLog model_userlog */
        $oLoginLog = a::singleton("model_userlog");
        $sTime = date('Y-m-d H:i:s', strtotime("-100 day"));
        $eTime = date('Y-m-d H:i:s');
        $aList = $oLoginLog->getCountIp(
            $this->lvtopid,
            $sTime,
            $eTime,
            $aUserInfo['username']
        );
        $iSumIp = array_sum(array_column($aList,'clientiptimes'));
        foreach($aList as &$v){
            $aCountUser = $oLoginLog->getCountUserByIp($this->lvtopid,$v['clientip']);
            $aMaxUser = $oLoginLog->getMaxUserByIP($this->lvtopid,$v['clientip'],$sTime,$eTime);
            $aMaxUsers = array_column($aMaxUser,'username');
            $v['maxUsername'] = implode(',',$aMaxUsers);
            $v['countUser'] = $aCountUser['countUser'];
            $v['bili'] = sprintf("%.4f",$v['clientiptimes']/$iSumIp) * 100;
        }
        $sLevelName = $aGetData['levelName'];
        $oWithdraw = new model_withdraw();
        $aRealBets = $oWithdraw->getRealBets($iUserid,$this->lvtopid);
        $iRealBets = sprintf("%.2f",$aRealBets['realbets']) ? : 0;
        if ($iRealBets == 0){
            $iBetsMultiple = 0;
        }else{
            if ($aUserInfo['totalwithdrawal'] == 0){
                $iBetsMultiple = 0;
            }else{
                $iBetsMultiple = sprintf("%.4f",$iRealBets/$aUserInfo['totalwithdrawal']);
            }
        }
        $aLastTimesInfo = $oWithdraw->getLastTimesInfo($iUserid,$this->lvtopid);
        //格式化金额
        $aLastTimesInfo['deposit'] = numberFormat2($aLastTimesInfo['deposit']);
        $aLastTimesInfo['favor'] = numberFormat2($aLastTimesInfo['favor']);
        $aUserFund['availablebalance'] = numberFormat2($aUserFund['availablebalance']);
        $aUserInfo['totalwithdrawal'] = numberFormat2($aUserInfo['totalwithdrawal']);
        $aUserInfo['loadmoney'] = numberFormat2($aUserInfo['loadmoney']);
        $aUserInfo['totalactivity'] = numberFormat2($aUserInfo['totalactivity']);


        $GLOBALS['oView']->assign('aLastTimesInfo', $aLastTimesInfo);
        $GLOBALS['oView']->assign('levelName', $sLevelName);
        $GLOBALS['oView']->assign('userInfo', $aUserInfo);
        $GLOBALS['oView']->assign('list', $aList);
        $GLOBALS['oView']->assign('iId', $iId);
        $GLOBALS['oView']->assign('iRealBets', $iRealBets);
        $GLOBALS['oView']->assign('iBankCard', $iBankCard);
        $GLOBALS['oView']->assign('iBetsMultiple', $iBetsMultiple);
        $GLOBALS['oView']->assign('iVerify_status', $iVerify_status);
        $GLOBALS['oView']->assign('lastRecharge', $sLastRecharge);
        $GLOBALS['oView']->assign('lastWithdraw', $sLastWithdraw);
        $GLOBALS['oView']->assign('isThisBlack', $isThisBlack);
        $GLOBALS['oView']->assign('isupdate', $isupdate);
        $GLOBALS['oView']->assign('userFund', $aUserFund);
        $GLOBALS['oView']->assign('res', $res);
        $GLOBALS['oView']->assign('real_amount', number_format($fReal_amount,2));
        $GLOBALS['oView']->assign('artificial', number_format($fArtificial,2));
        $GLOBALS['oView']->assign('userBankInfo', $aUserBankInfo[0]);
        $GLOBALS['oView']->display("cash_withdrawdetail.html");
    }

    /**
     * 风控审核
     */
    public function actionReview(){
        $oWithdraw = new model_withdraw();
        $aGetData = $this->post(array(
            "id" => parent::VAR_TYPE_INT,
            "status" => parent::VAR_TYPE_INT,
            "verify_status" => parent::VAR_TYPE_INT,
            "userid" => parent::VAR_TYPE_INT,
            "user_remark" => parent::VAR_TYPE_STR,
            "admin_remark" => parent::VAR_TYPE_STR,
        ));
        $id = intval($aGetData['id']);
        $userid = intval($aGetData['userid']);
        $aData['status'] = $aGetData['status'];
        $aData['verify_status'] = $aGetData['verify_status'];
        $aData['verify_adminid'] = $this->loginProxyId;
        $aData['verify_adminname'] = $this->adminname;
        $aData['user_remark'] = $aGetData['user_remark'];
        $aData['admin_remark'] = $aGetData['admin_remark'];
        $bResult = $oWithdraw->setReview($id,$aData,$this->lvtopid,$userid);
        if($aData['verify_status'] ==2 || $aData['status'] == 3) {
            // 发送站内信
            $oUser = new model_puser();
            $aUser = $oUser->getUsernameByUserId($userid);
            $aMsg['proxyadminid'] = $this->loginProxyId;//发送者
            $aMsg['lvtopid']      = $this->lvtopid; //发送者
            $aMsg['sendername']   = $this->adminname; //发送者
            //$aMsg['level']        = isset($_POST['level']) ? $_POST['level'] : '';
            $aMsg['send_range'] = "ismember"; //给用户发送
            $aMsg['receivename'] = $aUser['username']; // 接收人
            $aMsg['mt'] = 1; //消息类型，用户充提
            $aMsg['subject'] = "很抱歉，您的提现申请未通过"; //消息标题
            $aMsg['content'] = "很抱歉通知您，您的提现申请未通过，原因为:".$aData['user_remark']."。如果有任何疑问请联系在线客服";
            $oProxyMessage = new model_proxymessage();
            $a = $oProxyMessage->InsertMessageFromAdmin($aMsg);
        }
        if (true === $bResult) {
            die(json_encode([
                'error' => 0,
                'msg' => '操作成功！'
            ]));
        } else {
            die(json_encode([
                'error' => -1,
                'msg' => '当前状态已被修改,请刷新重试!'
            ]));
        }
    }
    /**
     * 风控拒绝
     */
    public function actionRefuse(){
        $aGetData = $this->get(array(
            "id" => parent::VAR_TYPE_INT,
            "userid" => parent::VAR_TYPE_INT,
            "verify_status" => parent::VAR_TYPE_INT,
            "status" => parent::VAR_TYPE_INT
        ));
        $id = intval($aGetData['id']);
        $userid = intval($aGetData['userid']);
        $aData['id'] = $id;
        $aData['userid'] = $userid;
        $aData['verify_status'] = $aGetData['verify_status'];
        $aData['status'] = $aGetData['status'];
        $GLOBALS['oView']->assign('data', $aData);
        $GLOBALS['oView']->display("cash_refuse.html");
    }

    /**
     * 前台备注
     */
    public function actionBeforeRemark(){
        $aGetData = $this->get(array(
            "id" => parent::VAR_TYPE_INT,
            "userid" => parent::VAR_TYPE_INT,
            "user_remark" => parent::VAR_TYPE_STR
        ));
        $id = intval($aGetData['id']);
        $userid = intval($aGetData['userid']);
        $aData['id'] = $id;
        $aData['userid'] = $userid;
        $aData['user_remark'] = $aGetData['user_remark'];
        $GLOBALS['oView']->assign('data', $aData);
        $GLOBALS['oView']->display("cash_beforeremark.html");
    }
    public function actionAfterRemark(){
        $aGetData = $this->get(array(
            "id" => parent::VAR_TYPE_INT,
            "userid" => parent::VAR_TYPE_INT,
            "admin_remark" => parent::VAR_TYPE_STR
        ));
        $id = intval($aGetData['id']);
        $userid = intval($aGetData['userid']);
        $aData['id'] = $id;
        $aData['userid'] = $userid;
        $aData['admin_remark'] = $aGetData['admin_remark'];
        $GLOBALS['oView']->assign('data', $aData);
        $GLOBALS['oView']->display("cash_afterremark.html");
    }
    /**
     * 添加备注
     */
    public function actionAddRemark(){
        $aGetData = $this->post(array(
            "id" => parent::VAR_TYPE_INT,
            "userid" => parent::VAR_TYPE_INT,
            "user_remark" => parent::VAR_TYPE_STR,
            "admin_remark" => parent::VAR_TYPE_STR
        ));
        $iId = $aGetData['id'];
        $iUserid = $aGetData['userid'];
        if (!empty($aGetData['user_remark'])){
            $aData['user_remark'] = $aGetData['user_remark']."/";
        }
        if (!empty($aGetData['admin_remark'])){
            $aData['admin_remark'] = $aGetData['admin_remark']."/";
        }
        $oWithdraw = new model_withdraw();
        $result = $oWithdraw->addRemark($aData,$iId,$iUserid,$this->lvtopid);
        if ($result) {
            $this->ajaxMsg(0,"修改成功");
        } else {
            $this->ajaxMsg(1,"修改失败");
        }
    }
    /**
     * 设置预备出款(人工预出款status=1，自动预出款status=4)
     */
    public function actionSetIsReady(){
        $oWithdraw = new model_withdraw();
        $aGetData = $this->post(array(
            "id" => parent::VAR_TYPE_INT,
            "status" => parent::VAR_TYPE_INT,
            "userid" => parent::VAR_TYPE_INT,
            "username" => parent::VAR_TYPE_STR,
            "apply_amount" => parent::VAR_TYPE_FLOAT,
            "withdraw_type" => parent::VAR_TYPE_INT,
        ));
        $id = intval($aGetData['id']);
        $userid = intval($aGetData['userid']);
        if ($aGetData['status'] == 1){
            //有效投注
            $aRealbet = $oWithdraw->getRealBets($userid,$this->lvtopid);
            if ($aRealbet['realbets'] == ''){
                $aRealbet['realbets'] = 0;
            }
            //行政费
            $oUserBetsCheck = new model_userbetscheck();
            $aFee = $oUserBetsCheck->findAll($userid,$aGetData['username'],$aRealbet['realbets'],$this->lvtopid," AND `isclear` = 0");
            if (!isset($aFee[0]['total_reduce']) || $aFee[0]['total_reduce'] == ''){
                $aFee[0]['total_reduce'] = 0.00;
            }
            //手续费
            $oPaySet = new model_payset();
            $iFee = $oPaySet->getFeeById($this->lvtopid,$userid,$aGetData['apply_amount'],$oUserBetsCheck->betsStatus);
            $aData['status'] = $aGetData['status'];
            $aData['withdraw_type'] = $aGetData['withdraw_type'];
            $aData['admin_fee'] = sprintf("%.2f",$aFee[0]['total_reduce']);//行政费
            $aData['charge'] = sprintf("%.2f",$iFee);//手续费
            $aData['real_amount'] = $aGetData['apply_amount'] - $aData['charge']-$aData['admin_fee'];//真是出款
            $aData['adminid'] = $this->loginProxyId;
            $aData['adminname'] = $this->adminname;
            if ($aData['real_amount'] <= 0) {
                $bResult = '对不起，你出款的金额必须大于0！';
            }else{
                if ($aGetData['withdraw_type'] == 1 ) {
                    $bResult = $oWithdraw->autoWithdraw($id,$userid,$aData,$this->lvtopid);
                }else{
                    $bResult = $oWithdraw->setReadyStatus($id,$userid,$aData,$this->lvtopid);
                }
            }
        }elseif ($aGetData['status'] == 0 ){
            $aData['status'] = 0;
            $aData['charge'] = 0;
            $aData['admin_fee'] = 0;
            $aData['real_amount'] = 0;
            $aData['adminid'] = 0;
            $aData['adminname'] = "";
            $aData['withdrawtime'] = '0000-00-00 00:00:00';
            $bResult = $oWithdraw->consoleReadyStatus($id,$userid,$aData,$this->lvtopid);
        }
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
    /**
     * 出款操作
     */
    public function actionConfirmWithdraw(){
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton('memcachedb', $GLOBALS['aSysMemCacheServer']);

        if ($this->getIsGet()) {
            //以下逻辑已改到拒绝出款中处理
           /* // 取消
            $iId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $sRemark = isset($_GET['remark']) ? $_GET['remark'] : "";
            // 是否已经锁定
            if ($this->is_lock($oMemCache, $iId)) {
                die(json_encode(['error' => -1, 'msg' => '当前记录有人正在操作，请稍等！']));
            }

            // 加锁
            $this->add_lock($oMemCache, $iId);

            try {
                // 执行逻辑
                $oWithdraw = new model_withdraw();
                $bResult = $oWithdraw->cancel($this->lvtopid, $iId, $this->loginProxyId, $this->adminname,$sRemark);
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
                    'msg' => '当前状态已被修改,请刷新重试!'
                ]));
            }*/
        } else if ($this->getIsPost()) {
            // 确定
            $iId = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $sRemark = isset($_POST['remark']) ? $_POST['remark'] : "";

            // 是否已经锁定
            if ($this->is_lock($oMemCache, $iId)) {
                die(json_encode(['error' => -1, 'msg' => '当前记录有人正在操作，请稍等！']));
            }

            // 加锁
            $this->add_lock($oMemCache, $iId);

            try {
                $oWithdraw = new model_withdraw();
                $bResult = $oWithdraw->confirm($this->lvtopid, $iId, $this->loginProxyId, $this->adminname,$sRemark);
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

    /*
     * 将出款记录的ajax数据查询移动到此处，导出表格也是用该方法，增加代码复用和逻辑统一
     */
    protected function getAllWithdrawList(){
        $oWithdraw = new model_withdraw();
        $aPostData = $this->post(array(
            "page" => parent::VAR_TYPE_INT,
            "rows" => parent::VAR_TYPE_INT,
            "withdrawMin" => parent::VAR_TYPE_FLOAT,
            "withdrawMax" => parent::VAR_TYPE_FLOAT,
            "username" => parent::VAR_TYPE_STR,
            "order" => parent::VAR_TYPE_STR,
            "level" => parent::VAR_TYPE_STR,
            "verifyCategory" => parent::VAR_TYPE_INT,
            "withdrawType" => parent::VAR_TYPE_INT,
            "withdrawCategory" => parent::VAR_TYPE_INT,
            "sTime" => parent::VAR_TYPE_DATETIME,
            "eTime" => parent::VAR_TYPE_DATETIME,
            "sFinishTime" => parent::VAR_TYPE_DATETIME,
            "eFinishTime" => parent::VAR_TYPE_DATETIME
        ));
        $aPostData["withdrawMin"] = isset($aPostData["withdrawMin"]) && is_numeric($aPostData["withdrawMin"]) ? floatval($aPostData["withdrawMin"]) : 0;
        $aPostData["withdrawMax"] = isset($aPostData["withdrawMax"]) && is_numeric($aPostData["withdrawMax"]) ? floatval($aPostData["withdrawMax"]) : 0;
        $sWhere = "uw.lvtopid = {$this->lvtopid} AND ut.`isdeleted`='0'";
        if (isset($aPostData["username"]) && !empty($aPostData["username"])) {
            $sWhere .=" AND uw.`username` = '" .  $aPostData["username"] ."'";
        }
        if ($aPostData["withdrawMin"] != "" && $aPostData["withdrawMax"] != ""){
            $sWhere .=" AND uw.`apply_amount`>='" .  $aPostData["withdrawMin"] . "' AND uw.`apply_amount`<='" .  $aPostData["withdrawMax"] . "'";
        }elseif ($aPostData["withdrawMax"] == "" && $aPostData["withdrawMin"] != ""){
            $sWhere .=" AND uw.`apply_amount`>='" .  $aPostData["withdrawMin"] . "'";
        }elseif ($aPostData["withdrawMax"] != "" && $aPostData["withdrawMin"] == "") {
            $sWhere .=" AND uw.`apply_amount`<='" .  $aPostData["withdrawMax"] . "'";
        }
        if (isset($aPostData['sTime']) && $aPostData['sTime'] != '') {
            $sWhere .= " AND uw.`applytime`>='" . $aPostData['sTime'] . "'";
        }
        if (isset($aPostData['sFinishTime']) && $aPostData['sFinishTime'] != '') {
            $sWhere .= " AND uw.`finishtime`>='" . $aPostData['sFinishTime'] . "'";
        }
        if (isset($aPostData['order']) && $aPostData['order'] != '' && strlen($aPostData['order']) == 6) {
            $sWhere .= " AND uw.`id`='" . myHash($aPostData['order'],"DECODE") . "'";
        }else if(strlen($aPostData['order']) == 19){
            $sWhere .= " AND uw.`order_no`='" . $aPostData['order'] . "'";
        }
        if (isset($aPostData['eTime']) && $aPostData['eTime'] != '') {
            $sWhere .= " AND uw.`applytime`<='" . $aPostData['eTime'] . "'";
        }
        if (isset($aPostData['eFinishTime']) && $aPostData['eFinishTime'] != '') {
            $sWhere .= " AND uw.`finishtime`<='" . $aPostData['eFinishTime'] . "'";
        }
        if (isset($aPostData['level']) && $aPostData['level'] != '') {
            $sWhere .= " AND uw.`layerid` in (" . $aPostData['level'] . ")";
        }
        if (isset($aPostData['verifyCategory']) && $aPostData['verifyCategory'] != -1) {
            $sWhere .= " AND uw.`verify_status` in (" . $aPostData['verifyCategory'] . ")";
        }
        if (isset($aPostData['withdrawCategory']) && $aPostData['withdrawCategory'] != -1) {
            $sWhere .= " AND uw.`status` in (" . $aPostData['withdrawCategory'] . ")";
        }
        if (isset($aPostData['withdrawType']) && $aPostData['withdrawType'] != -1) {
            $sWhere .= " AND uw.`withdraw_type` = " . $aPostData['withdrawType'] . "";
        }
        $aWithdrawList = $oWithdraw->getWithdrawList($sWhere,$aPostData['rows'], $aPostData['page']);
        foreach($aWithdrawList['results'] as $k => $v){
            if (empty($v['order_no'])) {
                $aWithdrawList['results'][$k]['order_no'] = myHash($v["id"], "ENCODE");
            }
            if ($aWithdrawList['results'][$k]['withdrawaltimes'] > 0){
                $aWithdrawList['results'][$k]['is_first'] = "否";
            }else{
                $aWithdrawList['results'][$k]['is_first'] = "是";
            }
            $aWithdrawList['results'][$k]['proxyadminid'] = $_SESSION['proxyadminid'];
            $aWithdrawList['results'][$k]['totalCharge'] = sprintf("%.2f",$aWithdrawList['results'][$k]['admin_fee']+$aWithdrawList['results'][$k]['charge']);
            if ($aWithdrawList['results'][$k]['status'] == 0){
                //有效投注
                $aRealbet = $oWithdraw->getRealBets($aWithdrawList['results'][$k]['userid'],$this->lvtopid);
                if (!isset($aRealbet['realbets']) || $aRealbet['realbets'] == ''){
                    $aRealbet['realbets'] = 0;
                }
                $iId = $aWithdrawList['results'][$k]['id'];
                //行政费
                $oUserBetsCheck = new model_userbetscheck();
                $aFee = $oUserBetsCheck->findAll($aWithdrawList['results'][$k]['userid'],$aWithdrawList['results'][$k]['username'],$aRealbet['realbets'],$this->lvtopid," AND isclear = 0");
                if (!isset($aFee[0]['total_reduce']) || $aFee[0]['total_reduce'] == ''){
                    $aFee[0]['total_reduce'] = 0.00;
                }
                $aWithdrawList['results'][$k]['admin_fee'] = $aFee[0]['total_reduce'];
                //手续费
                $oPaySet = new model_payset();
                $iFee = $oPaySet->getFeeById($this->lvtopid,$aWithdrawList['results'][$k]['userid'],$aWithdrawList['results'][$k]['apply_amount'],$oUserBetsCheck->betsStatus);
                $aWithdrawList['results'][$k]['charge'] = $iFee;
                //总扣款费用
                $aWithdrawList['results'][$k]['totalCharge'] = sprintf("%.2f",$aFee[0]['total_reduce']+$iFee);
                $ireal_amount =$aWithdrawList['results'][$k]['apply_amount']-$aFee[0]['total_reduce']-$iFee;
                $aWithdrawList['results'][$k]['real_amount'] = sprintf("%.2f",$ireal_amount);
            }
        }
        return $aWithdrawList;
    }

    public function actionMakeWithdrawExcel()
    {
        $_POST['rows'] = 0; // 强行置零，不分页
        if (!empty($_POST['level'])){
            $_POST['level'] = implode(',', $_POST['level']); // 强行置零，不分页
        }
        $aData = $this->getAllWithdrawList();
        $expCellName = [
            ['name', '层级'],
            ['order', '提现编号'],
            ['username', '会员账号'],
            ['realname', '姓名'],
            ['apply_amount', '提出额度'],
            ['totalCharge', '费用'],
            ['real_amount', '出款额度'],
            ['is_first', '首次'],
            ['availablebalance', '余额'],
            ['status', '出款状态'],
            ['applytime', '申请时间'],
            ['finishtime', '确认时间'],
            ['verify_status', '风控'],
            ['verify_adminname', '风控人'],
            ['operate', '操作'],
            ['adminname', '出款人'],
            ['user_remark', '前台备注'],
            ['admin_remark', '后台备注'],
        ];
        $expTableData = [];
        foreach ($aData['results'] as $k => $v) {
            $expTableData[$k]['name'] = $v['name'];
            $expTableData[$k]['order'] = $v['order'];
            $expTableData[$k]['username'] = $v['username'];
            $expTableData[$k]['realname'] = $v['realname'];
            $expTableData[$k]['apply_amount'] = $v['apply_amount'];
            $expTableData[$k]['totalCharge'] = $v['totalCharge'];
            $expTableData[$k]['real_amount'] = $v['real_amount'];
            $expTableData[$k]['is_first'] = $v['is_first'];
            $expTableData[$k]['availablebalance'] = $v['availablebalance'];
            switch ($v['status']){
                case 0:
                    $expTableData[$k]['status'] = '未处理';
                    break;
                case 1:
                    $expTableData[$k]['status'] = '预备/锁定';
                    break;
                case 2:
                    $expTableData[$k]['status'] = '已出款';
                    break;
                case 3:
                    $expTableData[$k]['status'] = '取消出款';
                    break;
                default:
                    $expTableData[$k]['status'] = '未处理';
                    break;
            }
            $expTableData[$k]['applytime'] = $v['applytime'];
            $expTableData[$k]['finishtime'] = $v['finishtime'];
            switch ($v['verify_status']){
                case 1:
                    $expTableData[$k]['verify_status'] = '已通过';
                    switch ($v['status']){
                        case 0:
                            $expTableData[$k]['operate'] = '预备出款';
                            break;
                        case 1:
                            $expTableData[$k]['operate'] = '正在处理';
                            break;
                        case 2:
                            $expTableData[$k]['operate'] = '已出款';
                            break;
                        case 3:
                            $expTableData[$k]['operate'] = '取消出款';
                            break;
                        default:
                            $expTableData[$k]['operate'] = null;
                            break;
                    }
                    break;
                case 2:
                    $expTableData[$k]['verify_status'] = '已拒绝';
                    $expTableData[$k]['operate'] = null;
                    break;
                default:
                    $expTableData[$k]['verify_status'] = null;
                    $expTableData[$k]['operate'] = null;
                    break;
            }
            $expTableData[$k]['verify_adminname'] = $v['verify_adminname'];
            $expTableData[$k]['adminname'] = $v['adminname'];
            $expTableData[$k]['user_remark'] = strip_tags($v['user_remark']);
            $expTableData[$k]['admin_remark'] = strip_tags($v['admin_remark']);
        }
        $sCondition = '查询条件：申请时间 ' . (string)$_POST['sTime'] . '-' . (string)$_POST['eTime'] . '；';
        $sCondition .= '确认时间： ' . (string)$_POST['sFinishTime'] . '-' . (string)$_POST['eFinishTime'] . '；';
        switch ($_POST['withdrawCategory']){
            case 0:
                $sCondition .= '出款状态：未处理；';
                break;
            case 1:
                $sCondition .= '出款状态：预备出款；';
                break;
            case 2:
                $sCondition .= '出款状态：已出款；';
                break;
            case 3:
                $sCondition .= '出款状态：已取消；';
                break;
            default:
                $sCondition .= '出款状态：全部；';
                break;
        }
        switch ($_POST['verifyCategory']){
            case 0:
                $sCondition .= '审核状态：待审核；';
                break;
            case 1:
                $sCondition .= '审核状态：已通过；';
                break;
            case 2:
                $sCondition .= '审核状态：已拒绝；';
                break;
            default:
                $sCondition .= '审核状态：全部；';
                break;
        }
        if (empty($_POST['withdrawMin'])){
            $_POST['withdrawMin'] = 0;
        }
        if (empty($_POST['withdrawMax'])){
            $_POST['withdrawMax'] = '最大';
        }
        $sCondition .= '提款金额：'.$_POST['withdrawMin'] . ' - ' . $_POST['withdrawMax'] . '；';
        if (empty($_POST['order'])){
            $sCondition .= '订单编号：无；';
        }else{
            $sCondition .= '订单编号：'.$_POST['order'] . '；';
        }
        if (empty($_POST['username'])){
            $sCondition .= '用户名：无；';
        }else{
            $sCondition .= '用户名：'.$_POST['username'] . '；';
        }
        if (!isset($_POST['level'])){
            $sCondition .= '用户层级：无；';
        }else{
            /* @var $oUserLayer model_userlayer */
            $oUserLayer = A::singleton('model_userlayer');
            $aUserLevel = $oUserLayer->getLayerList($this->lvtopid); // 用户层级信息
            $aLevel = null;
            $inputLevel = explode(',', $_POST['level']);
            foreach ($aUserLevel as $ul){
                foreach ($inputLevel as $level){
                    if ($ul['layerid'] == $level)
                        $aLevel[] = $ul['name'];
                }
            }
            $sLevel = implode('、', $aLevel);
            $sCondition .= '用户层级：' . $sLevel . '；';
        }
        $fileName = 'WithdrawList';
        $sTotal = '总笔数： ' . $aData['total']['count'] . '   出款金额： ' . $aData['total']['sumApply'] . '   实际出款： ' . $aData['total']['sumReal'];
        $sTitle = $sCondition . PHP_EOL . $sTotal;
        ExportExcel($fileName, $expCellName, $expTableData, $sTitle);
        exit;
    }

}