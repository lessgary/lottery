<?php

/**
 * @desc 首页
 * @author    Rhovin
 * @date    2017-05-25
 * @package   proxyweb
 */
class controller_pindex extends pcontroller
{
    protected $everyDay;
    public function __construct ()
    {
        parent::__construct();
        $arr = [];
        $oUserModel = new model_puser();
        $eachDay = $oUserModel->getEachDay();
        foreach ($eachDay as $value) {
            $arr[] = $value;
        }
        $this->everyDay = $arr;
        unset($arr);
    }
    
    /**
     * @desc 后台框架页
     * @author rhovin
     * @date 2017-05-25
     */
    public function actionIndex ()
    {
        $oProxyMenu = A::singleton('model_proxymenu');
        $aProxyMenu = $oProxyMenu->getUserMenu($_SESSION['proxyadminid']);
        /*if ($aProxyMenu == FALSE) {
            unset($_SESSION);
            session_destroy();
            redirect(url('default', 'index'));
            EXIT;
        }*/
        $aMenus = array();
        foreach ($aProxyMenu as $v) {
            $aMenus[$v["parentid"]][$v["menuid"]]["title"] = $v["title"];
            $aMenus[$v["parentid"]][$v["menuid"]]["controller"] = $v["controller"];
            $aMenus[$v["parentid"]][$v["menuid"]]["action"] = $v["actioner"];
            $aMenus[$v["parentid"]][$v["menuid"]]["icon"] = $v["icon"];
            if (strpos($v["actioner"], "_") !== FALSE && $v["controller"] == 'lock') {
                $aActioner = explode("_", $v['actioner']);
                $aMenus[$v["parentid"]][$v["menuid"]]["action"] = 'lockdetail';
                $aMenus[$v["parentid"]][$v["menuid"]]["param"]['lotteryid'] = intval($aActioner[1]);
                $aMenus[$v["parentid"]][$v["menuid"]]["param"]['lotteryname'] = strtoupper($aActioner[0]);
            }
        }
        unset($aProxyMenu);
        $GLOBALS['oView']->assign('lang', $GLOBALS['_LANG']);
        $GLOBALS['oView']->assign('menus', $aMenus);
        $GLOBALS['oView']->display('pindex_index.html');
        EXIT;
    }
    
    
    public function actionUpdateInfo ()
    {
        $GLOBALS['oView']->display('pindex_updateinfo.html');
    }
    
    
    /**
     * 首页展示
     *
     * @author ken 2017
     */
    public function actionHome ()
    {
        $iLvtopid = isset($_SESSION['lvtopid']) ? $_SESSION['lvtopid'] : '';
        if (empty($iLvtopid)) {
            unset($_SESSION);
            session_destroy();
            redirect(url("default", "index"));// 重新登录
        }
        $oUserModel = new model_puser();
        //获取每天新注册用户数量    代码已弃用
//        $aNewUser = $oUserModel->getNewUser($iLvtopid);//注册
//        $aBetsUser = $oUserModel->getWithBetsUser($iLvtopid);//投注
//        $aPayUser = $oUserModel->getUserPayment($iLvtopid);// 充值
//        $totalMoney = $oUserModel->getUserCount($iLvtopid);
//        $aOrdinaryAmount = $oUserModel->getOrdinaryAmount($iLvtopid);
//        $totalOnline = $oUserModel->getOnlineUser($iLvtopid); //活跃用户
//        $total_withdraw = $oUserModel->getUserWithdraw($iLvtopid); //活跃用户
//        $aTotalUser['total_onlinecount'] = $totalOnline['onlinecount'];
//        $aTotalUser['total_new_user'] = $aNewUser['new_user'];
//        $aTotalUser['total_valid_user'] = $aBetsUser['bet_user'];
//        $aTotalUser['total_in'] = $totalMoney[0]['total_payment']+$aOrdinaryAmount['manuaordinarypay_money'];
//        $aTotalUser['total_out'] = $total_withdraw['total_withdraw'];
//        $aTotalUser['total_win'] = $totalMoney[0]['total_payment'] - $total_withdraw['total_withdraw'];
//        $aTotalUser['total_payment_user'] = $aPayUser['userpay'];
        //上面代码已弃用 已换此最新的
        $totalUser = $oUserModel->getAllToday($iLvtopid);
        $aTotalUser['total_onlinecount'] = $totalUser['hyUser']; //现在人数
        $aTotalUser['total_new_user'] = $totalUser['newUser']; //新注册用户
        $aTotalUser['total_valid_user'] = $totalUser['yxUser'];// 有效用户
        $aTotalUser['total_in'] = $totalUser['todayCr'];//今日存入
        $aTotalUser['total_out'] = $totalUser['todayQk'];//今日取款
        $aTotalUser['total_win'] = $totalUser['win'];//盈利
        $aTotalUser['total_payment_user'] = $totalUser['todayRs'];//今日存入人数
        $GLOBALS['oView']->assign('userinfo', $aTotalUser);
        $GLOBALS['oView']->display('pindex_home.html');
    }
    
    /**
     * 返回前台数据-金钱
     *
     * @author ken 2017
     *
     */
    public function actionGetMapMoney ()
    {
        $iLvtopid = isset($_SESSION['lvtopid']) ? $_SESSION['lvtopid'] : '';
        $oUserModel = new model_puser();
        if ($_POST) {
            $format = isset($_POST['month']) ? $_POST['month'] : 'day';
            if ($format == 'day') {
                $aSum = $oUserModel->getUserMoneyInfoOutIn($iLvtopid,$format);
                $dayData = [];
                foreach ($aSum as $ka => $va){
                    $day = date('md',strtotime($aSum[$ka]['dt']));
                    $dayData[$day]['total_money_in'] = $aSum[$ka]['total_money_in'];
                    $dayData[$day]['total_money_out'] = $aSum[$ka]['total_money_out'];
                    $dayData[$day]['total_money_win'] = $aSum[$ka]['total_money_in'] - $aSum[$ka]['total_money_out'];
                }
                echo json_encode($dayData);
            }
            if ($format == 'month') {
                $monthData = [];
                $aSumb = $oUserModel->getUserMoneyInfoOutIn($iLvtopid,$format);
                foreach ($aSumb as $kb => $vb) {
                    $month = $aSumb[$kb]['month'];
                    $monthData[$month]['total_money_in'] = $aSumb[$kb]['total_money_in'];
                    $monthData[$month]['total_money_out'] = $aSumb[$kb]['total_money_out'];
                    $monthData[$month]['total_money_win'] = $aSumb[$kb]['total_money_in'] - $aSumb[$kb]['total_money_out'];
                }
                echo json_encode($monthData);
            }
        }
    }
    
    /**
     * 返回前台用户数据-用户
     *
     * @author ken 2017
     */
    public function actionGetMapUser ()
    {
        $iLvtopid = $this->lvtopid;
        $oUserModel = new model_puser();
        if ($_POST) {
            $format = isset($_POST['month']) ? $_POST['month'] : 'day';
            // 获取月数据
            if ($format == 'month') {
                $monthData = $oUserModel->getUserRegAndBetAndPayInfo($iLvtopid,'',$format);
                $newMonthData = [];
                foreach ($monthData as $ka => $va) {
                    $month = $monthData[$ka]['month'];
                    $newMonthData[$month]['reg_user'] = $monthData[$ka]['reg_user'];
                    $newMonthData[$month]['real_user'] = $monthData[$ka]['real_user'];
                    $newMonthData[$month]['pay_user'] = $monthData[$ka]['pay_user'];
                }
                echo json_encode($newMonthData);
            } else if ($format == 'day') {
                $dayData = $oUserModel->getUserRegAndBetAndPayInfo($iLvtopid,'',$format);
                $newDayData = [];
                foreach ($dayData as $k => $v)
                {
                    $day = date('md',strtotime($dayData[$k]['dt']));
                    $newDayData[$day]['reg_user'] = $dayData[$k]['reg_user'];
                    $newDayData[$day]['real_user'] = $dayData[$k]['bet_user'];
                    $newDayData[$day]['pay_user'] = $dayData[$k]['real_user'];
                }
                echo json_encode($newDayData);
            }
           
        }
    }
    
    /**
     * 中国地图
     */
    public function actionChinaMap ()
    {
        $oUserModel = new model_puser();
        $aChina = $oUserModel->getChinaMap($this->lvtopid);
        $aLocation =[];
        foreach ($aChina as $k => $v){
            $sCity = IpInfo($v["lastip"]); //根据IP 获取 客户端 的具体地址返回的是 数组
            $aResult = array_filter($aLocation,function($arr)use($sCity){ //循环二维数组
                if($arr["place"] == $sCity[1]){  //判断是否有相同地址的会员
                    return $arr;  //如果有相同地址的会员返回当前的key value
                }
            });
            $aKeys = array_keys($aResult); //获取 key值
            if(empty($aKeys)){ //如果为空也就是没有相同地址的会员就加入到数组里
                $aLocation[$k]["place"] = $sCity[1];
                $aLocation[$k]["usernum"] = 1;
            }else{ // 如果有相同地址会员的 就把人数加1
                $aLocation[$aKeys[0]]["usernum"] += 1;
            }
        }
        echo json_encode($aLocation);
    }
    /**
     * desc 网页右下角弹窗提示
     * @author rhovin 2017-10-30
     */
    public function actionShowCake() {
        $iLvtopId = $this->lvtopid;
        $iLoginProxyId = $this->loginProxyId;
        $oWithDraw = new model_withdraw();
        //$oFast= new model_fastpaycompany();
        $oProxyUser = new model_proxyuser();
        //$oRiskModel = new model_lotteryrisk();
        $aProxyUser = $oProxyUser->getProxyUserGroup($iLoginProxyId);
        $menustr = array_merge(explode(',',$aProxyUser['gmenustrs']),explode(',', $aProxyUser['menustrs']));
        $aMenustr = array_filter($menustr);
        //下列注释代码已弃用
//        $count_withdraw = $oWithDraw->getCountWithDrawByTime($iLvtopId)['count_withdraw']; //获取有提款的最大ID
//        $count_fastpay = $oFast->getCountFastByTime($iLvtopId)['count_fastpay']; //获取是否有第三方充值
//        $count_companypay = $oFast->getCountFastCompanyByTime($iLvtopId)['count_companypay']; //获取是否有公司入款
//        $count_risk = $oRiskModel->getCountRiskDataByLvtopId($iLvtopId)['count_risk']; //获取总代风控数据

//        $count_withdraw = $count_withdraw == null ? 0 : $count_withdraw;
//        $count_fastpay = $count_fastpay == null ? 0 : $count_fastpay;
//        $count_companypay = $count_companypay == null ? 0 : $count_companypay;
//        $count_risk = $count_risk == null ? 0 : $count_risk;

        $aCountData = $oWithDraw->getMoneyData($iLvtopId); //获取是否有入款 出款 风控数据

       /* $count_withdraw_session = isset($_COOKIE['count_withdraw']) ? $_COOKIE['count_withdraw'] : '';
        $count_fastpay_session = isset($_COOKIE['count_fastpay']) ? $_COOKIE['count_fastpay'] : '';
        $count_companypay_session = isset($_COOKIE['count_companypay']) ? $_COOKIE['count_companypay'] : '';
        $sNewSessionId = session_id();
        if ($count_withdraw_session != $sNewSessionId . '_' . $count_withdraw) {
            setcookie('count_withdraw', $sNewSessionId . '_' . $count_withdraw);
        } else {
             $count_withdraw = 0;
        }
        if ($count_fastpay_session != $sNewSessionId . '_' . $count_fastpay) {
            setcookie('count_fastpay', $sNewSessionId . '_' . $count_fastpay);
        } else {
            $count_fastpay = 0;
        }
        if ($count_companypay_session != $sNewSessionId . '_' . $count_companypay) {
            setcookie('count_companypay', $sNewSessionId . '_' . $count_companypay);
        } else {
            $count_companypay = 0;
        }*/
        $oProxyMenu = new model_proxymenu();
        $aCashMenuId = $oProxyMenu->getMenuIdByConrotroller("cash","withdrawlist");
        $aFastPayMenuId = $oProxyMenu->getMenuIdByConrotroller("fastpaymoney","list");
        $aCompanyPayMenuId = $oProxyMenu->getMenuIdByConrotroller("companymoney","list");
        $aLotteryRiskMenuId = $oProxyMenu->getMenuIdByConrotroller("lotteryrisk","list");
        $aTripartiteMenuId = $oProxyMenu->getMenuIdByConrotroller("tripartite","list");
        if(!in_array($aFastPayMenuId['menuid'], $aMenustr)) { //是否有三方入款权限
            $count_fastpay = 0;
        }
        if(!in_array($aCompanyPayMenuId['menuid'], $aMenustr)) { //是否有公司入款权限
            $aCountData["gsrk"] = 0;
        }
        if(!in_array($aCashMenuId['menuid'], $aMenustr)) { //是否有出款权限
            $aCountData["yhtk"] = 0;
        }
        if(!in_array($aLotteryRiskMenuId['menuid'], $aMenustr)) { //是否有风控权限
            $aCountData["fk"] = 0;
        }
        if(!in_array($aTripartiteMenuId['menuid'], $aMenustr)) { //是否有风控权限
            $aCountData["sf"] = 0;
        }
        $this->ajaxMsg("1","获取成功", ['data'=>[
            'count_withdraw'=>$aCountData["yhtk"],
            'count_fastpay'=>$count_fastpay=0,
            'count_companypay'=>$aCountData["gsrk"],
            'count_tripartite'=>$aCountData["sf"],
            'count_risk'=>$aCountData["fk"]
            ]]
        );
    }
    
}