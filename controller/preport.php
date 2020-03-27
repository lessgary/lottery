<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/5/31
 * Time: 16:59
 */

class controller_preport extends pcontroller {

    //构造函数
    public function __construct() {
        parent::__construct();
    }

    /**
     * 盈亏报表:用户输赢情况,不按游戏,玩法统计
     * @author pierce
     */
    function actionProfit(){
        if ($this->getIsAjax()){
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "userid" => parent::VAR_TYPE_INT,
                "isgetdata" => parent::VAR_TYPE_INT,
                "username" => parent::VAR_TYPE_STR,
                "sTime" => parent::VAR_TYPE_DATETIME,
                "eTime" => parent::VAR_TYPE_DATETIME,
                "sidx" => parent::VAR_TYPE_STR,
                "sord" => parent::VAR_TYPE_STR
            ));
            if (isset($_GET['username']) && $_GET['username'] != ""){
                $aGetData['username'] = $_GET['username'];
                $aGetData['page'] = $_GET['page'];
                $aGetData['rows'] = $_GET['rows'];
            }
            $oUserReport = new model_preport();
            $oUser = new model_puser();
            if (time() < strtotime(date("Y-m-d " . $this->sReportTime))) {
                $aGetData['sTime'] = isset($aGetData['sTime']) ? $aGetData['sTime'] : date("Y-m-d " . $this->sReportTime, time() - 86400);
                $aGetData['eTime'] = isset($aGetData['eTime']) ? $aGetData['eTime'] : date("Y-m-d " . $this->sReportTime, time());
            } else {
                $aGetData['sTime'] = isset($aGetData['sTime']) ? $aGetData['sTime'] : date("Y-m-d " . $this->sReportTime, time());
                $aGetData['eTime'] = isset($aGetData['eTime']) ? $aGetData['eTime'] : date("Y-m-d " . $this->sReportTime, time() + 86400);
            }
            //构造where条件
            $sWhere = "  AND istester = 0";
            if (isset($aGetData["username"]) && $aGetData["username"] != "") {
                $aUserInfo = $oUser->getUser($aGetData["username"],$this->lvtopid);
                if (empty($aUserInfo)){
                    $this->outPutJQgruidJson([],0, $aGetData['page'], $aGetData['rows']);
                }else{
                    $iUserId = $aUserInfo['userid'];
                }
            }else{
                $iUserId = $this->lvtopid;
            }
            //时间条件
            if (isset($aGetData['sTime']) && $aGetData['sTime'] != '') {
                $sWhere .= " AND `day`>='" . $aGetData['sTime'] . "'";
            }
            if (isset($aGetData['eTime']) && $aGetData['eTime'] != '') {
                $sWhere .= " AND `day`<='" . $aGetData['eTime'] . "'";
            }
            $aResult = $oUserReport->getAdminReport($sWhere,$aGetData['rows'],$aGetData['page'],$iUserId);

            //格式化金额
            foreach ($aResult as $k => &$v){
                $v['payment'] = !empty($v['payment']) ? numberFormat2($v['payment'],4) : '0.0000';
                $v['activity'] = !empty($v['activity']) ? numberFormat2($v['activity'],4) : '0.0000';
                $v['withdraw'] = !empty($v['withdraw']) ? numberFormat2($v['withdraw'],4) : '0.0000';
                $v['bets'] = !empty($v['bets'])? numberFormat2($v['bets'],4) : '0.0000';
                $v['realbets'] = !empty($v['realbets']) ? numberFormat2($v['realbets'],4) : '0.0000';
                $v['points'] = !empty($v['points']) ? numberFormat2($v['points'],4) :'0.0000';
                $v['bonus'] = !empty($v['bonus']) ?  numberFormat2($v['bonus'],4) : '0.0000';
                $v['profit'] = !empty($v['profit']) ? numberFormat2($v['profit'],4) : '0.0000';
            }
            $this->outPutJQgruidJson($aResult,count($aResult), $aGetData['page'], $aGetData['rows']);        }else{
            if (isset($_GET['username']) && $_GET['username'] != ""){
                $GLOBALS['oView']->assign('username',$_GET['username']);
            }
            $GLOBALS['oView']->assign('startDate', date('Y-m-d '));
            $GLOBALS['oView']->assign('endDate', date('Y-m-d ', strtotime('+1 days')));
            $GLOBALS['oView']->assign("ur_here", "盈亏报表");
            $GLOBALS['oView']->display('report_profit.html');
        }
    }
    /**
     * @desc 单期盈亏报表
     * 查询单期盈亏报表
     * 派奖时生成每相应彩种相应奖期盈亏数据
     * 可以根据彩种，时间进行查询
     * 可以分元角模式进行查询
     * @author pierce
     * @date 2017-06-02
     */
    function actionSingleSale(){
        $oSale = new model_psale();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "modes" => parent::VAR_TYPE_INT,
                "issue" => parent::VAR_TYPE_INT,
                "sidx" => parent::VAR_TYPE_STR,
                "sord" => parent::VAR_TYPE_STR,
                "lottery" => parent::VAR_TYPE_INT,
                "sTime" => parent::VAR_TYPE_DATETIME,
                "eTime" => parent::VAR_TYPE_DATETIME,
            ));
            $aGetData['modes'] = isset($aGetData['modes']) ? intval($aGetData['modes']) : -1;
            $aGetData['lotteryid'] = isset($aGetData['lottery']) ? intval($aGetData['lottery']) : 0;
            if (time() < strtotime(date("Y-m-d " . $this->sReportTime))) {
                $aGetData['sTime'] = isset($aGetData['sTime']) ? $aGetData['sTime'] : date("Y-m-d " . $this->sReportTime, time() - 86400);
                $aGetData['eTime'] = isset($aGetData['eTime']) ? $aGetData['eTime'] : date("Y-m-d " . $this->sReportTime, time());
            } else {
                $aGetData['sTime'] = isset($aGetData['sTime']) ? $aGetData['sTime'] : date("Y-m-d " . $this->sReportTime, time());
                $aGetData['eTime'] = isset($aGetData['eTime']) ? $aGetData['eTime'] : date("Y-m-d " . $this->sReportTime, time() + 86400);
            }
            $aGetData['rows'] = isset($aGetData['rows']) ? intval($aGetData['rows']) : 500; // 分页用1
//            $iPn = $GLOBALS['SysPageSizeMax'] > 1 && $pn > $GLOBALS['SysPageSizeMax'] ? $GLOBALS['SysPageSizeMax'] : $pn = $pn > 5 ? $pn : 500;
            $aGetData['page'] = isset($aGetData['page']) ? intval($aGetData['page']) : 0; // 分页用2
            $sWhere = "sl.`lvtopid` =  '".$this->lvtopid."'";
            $sWhere .= $this->_getWhereStr($aGetData);
            $sFiled = '';
            $aGetData['sidx'] = !empty($aGetData['sidx']) ? $aGetData['sidx'] : 'joindate';
            $sOrderBy = " ORDER BY `" . $aGetData['sidx'] ."` ". $aGetData['sord']." ";
            $aSaleList = $oSale->getSingleSales($sFiled, $sWhere, $sOrderBy, $aGetData['rows'], $aGetData['page'], $aGetData['modes']);
            if (!empty($aSaleList) && !empty($aGetData)) {

                $this->outPutJQgruidJson($aSaleList['results'],$aSaleList['affects'] , $aGetData['page'], $aGetData['rows']);
            }
            //        $oPager = new pages($aSaleList['affects'], $iPn, 10);   // 分页用3
        } else {
            /* @var $oMethod model_method */
            $oMethod = A::singleton('model_method', $GLOBALS['aSysDbServer']['report']);
            //获取彩种列表
            $aMethod = $oMethod->methodGetInfo('DISTINCT `lotteryid`', '`isclose`=0 ');
            $aActiveLotterise = array_column($aMethod, 'lotteryid');
            /* @var $oLottery model_lottery */
            $oLottery = A::singleton('model_lottery', $GLOBALS['aSysDbServer']['report']);
            $aLotteryList = $oLottery->lotteryGetList('', '', '', 0);
            $aLottery = array();
            foreach ($aLotteryList as $aValue) {
                $aLottery[$aValue['lotteryid']] = $aValue['cnname'] . (in_array($aValue['lotteryid'],$aActiveLotterise) ? '' : '(停售)') ;
            }

            $GLOBALS['oView']->assign('ur_here', '查询单期盈亏报表');
            $GLOBALS['oView']->assign('sTime', date('Y-m-d '));
            $GLOBALS['oView']->assign('eTime', date('Y-m-d ', strtotime('+1 days')));
            $GLOBALS['oView']->assign('lottery', $aLottery);
            $GLOBALS['oView']->assign('modelist', $GLOBALS['config']['modes']);
//            $GLOBALS['oView']->assign('pageinfo', $oPager->show()); // 分页用4
            $GLOBALS['oView']->display("report_psinglesale.html");
            EXIT;
        }
    }
    /**
     * 用户彩种分类报表
     */
    public function actionUserCategory(){
        $oUser = new model_puser();
        $oReport = new model_preport();
        $aGetData = $this->get(array(
            "username" => parent::VAR_TYPE_STR,
            "tStartTime" => parent::VAR_TYPE_DATETIME,
            "tEndTime" => parent::VAR_TYPE_DATETIME,
        ));

        if ($aGetData['username'] != ""){
            $aGetData['userid'] = $oUser->getUser($aGetData['username'],$this->lvtopid)['userid'];
        }
        $aGetData['userid'] = isset($aGetData['userid']) ? intval($aGetData['userid']) : 0;
        $aGetData['page'] = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $aGetData['rows'] = isset($_POST['rows']) ? intval($_POST['rows']) : 50;
        $sWhere = "p.`lvtopid` = {$this->lvtopid}";
        if (isset($aGetData['tStartTime']) && $aGetData['tStartTime'] != '') {
            $sWhere .= " AND p.`writetime`>='" . $aGetData['tStartTime'] . "'";
        }
        if (isset($aGetData['tEndTime']) && $aGetData['tEndTime'] != '') {
            $sWhere .= " AND p.`writetime`<='" . $aGetData['tEndTime'] . "'";
        }
        if ($aGetData['userid'] > 0){
            $usertype = $oUser->getUserTypeByUserId($aGetData['userid'],$this->lvtopid);
            if ($usertype['usertype'] == 1){
                $sWhere .= " AND (p.userid = {$aGetData['userid']} OR FIND_IN_SET({$aGetData['userid']}, ut.parenttree))";
            }else{
                $sWhere .= " AND p.`userid` = {$aGetData['userid']}";
            }
        }
        $aUserCategory = $oReport->getUserCategory($sWhere,$aGetData['rows'],$aGetData['page']);
        $GLOBALS['oView']->assign('outputData', $aUserCategory);
        $GLOBALS['oView']->display("preport_usercategory.html");
//        if (!empty($aUserCategory) && !empty($aGetData)) {
//            $this->outPutJQgruidJson($aUserCategory,count($aUserCategory));
//        }
    }
    /**
     * 代理统计
     * @author pierce
     * @date 2017-06-05
     */
    function actionTeamSale(){
        $oUserReport = new model_userreport;
        $oUser = new model_puser;
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "minPoint" => parent::VAR_TYPE_FLOAT,
                "maxPoint" => parent::VAR_TYPE_FLOAT,
                "minAmount" => parent::VAR_TYPE_FLOAT,
                "maxAmount" => parent::VAR_TYPE_FLOAT,
                "childName" => parent::VAR_TYPE_STR,
                "searchname" => parent::VAR_TYPE_STR,
               // "proxylevel" => parent::VAR_TYPE_INT,
                "selectType" => parent::VAR_TYPE_STR,
                "tStartTime" => parent::VAR_TYPE_DATETIME,
                "tEndTime" => parent::VAR_TYPE_DATETIME,
                "sidx" => parent::VAR_TYPE_STR,
                "sord" => parent::VAR_TYPE_STR
            ));
            if ($aGetData['searchname'] != '' && $aGetData['childName'] != '') {
                $aGetData['childName'] = '';
            }
            $sPointWhere = $this->_getWhereStr($aGetData);
            $aGetData['sPointWhere'] = $sPointWhere;
            if(ceil((time()-strtotime($aGetData['tStartTime']))/86400) >31) {
                $this->ajaxMsg(0,"最多查询近30天内的数据");
            }
            if(strtotime($aGetData['tStartTime'])>strtotime($aGetData['tEndTime'])) {
                $this->ajaxMsg(0,"开始时间不能大于结束时间");
            }
            $aGetData['sTime'] = $aGetData['tStartTime'];
            $aGetData['eTime'] = $aGetData['tEndTime'];
            $aGetData['minAmount']  = $fMinAmount = isset($aGetData['minAmount']) ? floatval(str_replace(",", "", $aGetData['minAmount'])) : 0;
            $aGetData['maxAmount']  = $fMaxAmount = isset($aGetData['maxAmount']) ? floatval(str_replace(",", "", $aGetData['maxAmount'])) : 0;
            $aGetData['lvtopid'] = $this->lvtopid;
            $aResult = $oUserReport->getTeamDataList(1, $aGetData,$aGetData['page'],$aGetData['rows']);
            foreach($aResult['results'] as $k => $v) {
                $oUserInfo = new model_userfund();
                if ($aResult['results'][$k]['lvproxyid'] > 0) {
                    $aUserInfo = $oUserInfo->getFundByUser($aResult['results'][$k]['lvproxyid']);
                    $aResult['results'][$k]['lvproxyname'] = $aUserInfo['username'];
                }
                $aResult['results'][$k]['maxpoint'] = $v['maxpoint'] * 100;
            }
            if (!empty($aResult['results']) && !empty($aGetData)) {
                if (!empty($aGetData['searchname'])) {
                    $aResult['affects'] = $aResult['affects']+(ceil($aResult['affects']/($aGetData['rows']-1)));
                }
                $this->outPutJQgruidJson($aResult['results'],$aResult['affects'] , $aGetData['page'], $aGetData['rows']);
            } else {
                $this->ajaxMsg(0,"未查到相关数据");
            }
        }else{
            $aUserReport = $oUserReport->getSelect();
            $aHtml['ssstarttime'] = date("Y-m-d", strtotime("-30 days"));
            $aHtml['endtime'] = date('Y-m-d', strtotime('-1 days'));
            $GLOBALS['oView']->assign('s', $aHtml);
            $GLOBALS['oView']->assign('sTime', date('Y-m-d', strtotime('-2 days')));
            $GLOBALS['oView']->assign('eTime', date('Y-m-d', strtotime('-1 days')));
            $GLOBALS['oView']->assign('ur_here', '统计报表');
            $GLOBALS['oView']->assign('select', $aUserReport);
            $GLOBALS['oView']->display("report_pteamsale.html");
            }
    }

    /**
     * 用户输赢排行
     * @author pierce
     * @date 2017-06-05
     */
    public function actionUserWinOrder() {
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "lottery" => parent::VAR_TYPE_INT,
                "userCount" => parent::VAR_TYPE_INT,
                "sidx" => parent::VAR_TYPE_STR,//排序字段
                "sord" => parent::VAR_TYPE_STR,//排序类型（desc or acs）
                "uStartTime" => parent::VAR_TYPE_DATETIME,
                "uEndTime" => parent::VAR_TYPE_DATETIME,
            ));
            $sSidx = isset($aGetData['sidx']) && $aGetData['sidx'] != '' ? $aGetData['sidx'] : "totallose";
            $sSord = isset($aGetData['sord']) && $aGetData['sord'] != '' ? $aGetData['sord'] : "ASC";
            $aGetData['userCount'] = isset($aGetData['userCount']) && $aGetData['userCount'] != '' ? intval($aGetData['userCount']) : 25;
            $sWhere = "ur.`lvtopid` = {$this->lvtopid}";
            $sStartDay = date("Y-m-d", strtotime($aGetData['uStartTime']));
            $sEndDay = date("Y-m-d", strtotime($aGetData['uEndTime']) - 86400);
            if ($sStartDay == $sEndDay) {
                $sWhere .= " AND ur.`day` = '" . $sStartDay . "'";
            } else {
                $sWhere .= " AND ur.`day` BETWEEN '" . $sStartDay . "' AND '" . $sEndDay . "' ";
            }
            if (isset($aGetData['lottery']) && $aGetData['lottery'] != 0) {//根据彩种查询报表
                $sWhere .= " AND ur.`lotteryid` = '" . $aGetData['lotteryid'] . "'";
            }
            $oMarketmgr = new model_preport;
            $aUserWinOrder = $oMarketmgr->getUserWinOrder($sWhere, $sSidx, $sSord, $aGetData['userCount'], $aGetData['page']);
            foreach ($aUserWinOrder['results'] as $iKey => & $aUserList) {
                if ($aUserList['totalprice'] != 0){
                    $aUserList['companyrate'] = $aUserList['totallose'] / $aUserList['totalprice'] * 100;
                    $totallose = $aUserList['totallose'] / $aUserList['totalprice'] * 100;
                }
                $aUserList['companyrate'] = number_format(isset($totallose) ? $totallose : 0,2)."%";
                $aUserList['order'] = $iKey + 1;
            }
            if (!empty($aUserWinOrder) && !empty($aGetData)) {
                $this->outPutJQgruidJson($aUserWinOrder['results'],count($aUserWinOrder['results']) , $aGetData['page'], $aGetData['rows']);
            }
        }else{
            $GLOBALS['oView']->assign('ur_here', '用户输赢排名');
            $GLOBALS['oView']->assign('sTime', date('Y-m-d '));
            $GLOBALS['oView']->assign('eTime', date('Y-m-d ', strtotime('+1 days')));
            /* @var $oLottery model_lottery */
            $oLottery = new model_lottery();
            $aLotteryList = $oLottery->lotteryGetList('', '', '', 0);
            $aLottery = array();
            foreach ($aLotteryList as $aValue) {
                $aLottery[$aValue['lotteryid']] = $aValue['cnname'];
            }
            $GLOBALS['oView']->assign('lottery', $aLottery);
            $iOrderNum = isset($_REQUEST['ordernum']) && $_REQUEST['ordernum'] != '' ? intval($_REQUEST['ordernum']) : 25;
            $GLOBALS['oView']->assign('orderNum', $iOrderNum);
            $GLOBALS['oView']->display("report_userwinorder.html");
            EXIT;
        }
    }

    /**
     * @desc 统一构造where条件
     * @author pierce
     * @date 2017-06-01
     */
    private function _getWhereStr($aGetData) {
        $sWhere = " ";
        //彩种
        if (isset($aGetData['startDate']) && $aGetData['startDate'] != '') {
            $sWhere .= " AND us.`joindate`>='" . $aGetData['startDate'] . "'";
        }
        if (isset($aGetData['endDate']) && $aGetData['endDate'] != '') {
            $sWhere .= " AND us.`joindate`<='" . $aGetData['endDate'] . "'";
        }
        //单期盈亏
        if (isset($aGetData['lotteryid']) && $aGetData['lotteryid'] != 0) {//根据彩种查询报表
            $sWhere .= " AND sl.`lotteryid` = '" . $aGetData['lotteryid'] . "'";
        }
        if (isset($aGetData['modes']) && $aGetData['modes'] != -1) {//根据元角模式查询报表
            $sWhere .= " AND sl.`modes` = '" . $aGetData['modes'] . "'";
        }
        if (isset($aGetData['sTime'])) {//统计的开始时间
            $sWhere .= " AND sl.`joindate` >= '" . $aGetData['sTime'] . "'";
        }
        if (isset($aGetData['eTime'])) {//统计的结束时间
            $sWhere .= " AND sl.`joindate` <= '" . $aGetData['eTime'] . "'";
        }
        //奖期
        if (isset($aGetData['issue']) && $aGetData['issue'] != '') {
            $sWhere .= " AND  sl.`issue` = '" . $aGetData['issue'] . "'";
        }
        //返点级别
        if (isset($aGetData['minPoint'])){
            if ($aGetData['minPoint'] == $aGetData['maxPoint'] && $aGetData['minPoint'] > 0) {
                $sWhere .= " AND `maxpoint` =  " . $aGetData['minPoint'] / 100;
            } elseif ($aGetData['minPoint'] == $aGetData['maxPoint'] && $aGetData['minPoint'] <= 6.4 && $aGetData['minPoint'] > 0) {
                $sWhere .= " AND `maxpoint` <=  " . $aGetData['minPoint'] / 100;
            } elseif($aGetData['minPoint'] <= 6.4 && $aGetData['maxPoint'] >= 6.5){
                $sWhere .= " AND `maxpoint` <=  " . $aGetData['maxPoint'] / 100;
            }elseif ($aGetData['minPoint'] > 0 && $aGetData['maxPoint'] == 0){
                $sWhere .= " AND `maxpoint` >= " . $aGetData['minPoint']  / 100;
            } elseif ($aGetData['minPoint'] == 0 && $aGetData['maxPoint'] > 0){
                $sWhere .= " AND `maxpoint` <=  " . $aGetData['maxPoint'] / 100;
            } elseif ($aGetData['minPoint'] > 0 && $aGetData['maxPoint'] > 0){
                $sWhere .= " AND `maxpoint` >= ".$aGetData['minPoint'] / 100 . " AND `maxpoint` <= " . $aGetData['maxPoint'] / 100;
            }
        }
        return $sWhere;
    }
    /**
     * 彩种盈亏报表
     */
    function actionLotteryProfit(){
        $oSale = new model_psale();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "startDate" => parent::VAR_TYPE_DATETIME,
                "endDate" => parent::VAR_TYPE_DATETIME,
            ));
            if (time() < strtotime(date("Y-m-d " . $this->sReportTime))) {
                $aGetData['startDate'] = isset($aGetData['startDate']) ? $aGetData['startDate'] : date("Y-m-d " . $this->sReportTime, time() - 86400);
                $aGetData['endDate'] = isset($aGetData['endDate']) ? $aGetData['endDate'] : date("Y-m-d " . $this->sReportTime, time());
            } else {
                $aGetData['startDate'] = isset($aGetData['startDate']) ? $aGetData['startDate'] : date("Y-m-d " . $this->sReportTime, time());
                $aGetData['endDate'] = isset($aGetData['endDate']) ? $aGetData['endDate'] : date("Y-m-d " . $this->sReportTime, time() + 86400);
            }
            $sWhere = "`lvtopid` = {$this->lvtopid}";
            $sWhere .= $this->_getWhereStr($aGetData);
            $aSaleList = $oSale->getSingleSale($sWhere, $sOrderBy=" GROUP BY us.lotteryid");
            if (!empty($aGetData)) {
                $this->outPutJQgruidJson($aSaleList,count($aSaleList));
            }
        } else {
            $GLOBALS['oView']->assign('ur_here', '查询彩种盈亏报表');
            $GLOBALS['oView']->assign('sTime', date('Y-m-d '));
            $GLOBALS['oView']->assign('eTime', date('Y-m-d ', strtotime('+1 days')));
            $GLOBALS['oView']->display("report_plotteyprofit.html");
            EXIT;
        }
    }

    /**
     * 彩种盈亏报表详情
     */
    function  actionLotteryProfitInfo() {
        $oSale = new model_psale();
        if ($this->getIsPost()) {
            $aGetData = $this->get(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "startDate" => parent::VAR_TYPE_DATETIME,
                "lottery_id" => parent::VAR_TYPE_INT,
                "endDate" => parent::VAR_TYPE_DATETIME,
            ));
            if (time() < strtotime(date("Y-m-d " . $this->sReportTime))) {
                $aGetData['startDate'] = isset($aGetData['startDate']) ? $aGetData['startDate'] : date("Y-m-d " . $this->sReportTime, time() - 86400);
                $aGetData['endDate'] = isset($aGetData['endDate']) ? $aGetData['endDate'] : date("Y-m-d " . $this->sReportTime, time());
            } else {
                $aGetData['startDate'] = isset($aGetData['startDate']) ? $aGetData['startDate'] : date("Y-m-d " . $this->sReportTime, time());
                $aGetData['endDate'] = isset($aGetData['endDate']) ? $aGetData['endDate'] : date("Y-m-d " . $this->sReportTime, time() + 86400);
            }
            $sWhere = "`lvtopid` = {$this->lvtopid}";
            $sWhere .= $this->_getWhereStr($aGetData);
            if($aGetData['lottery_id'] > 0) {
                $sWhere.= " AND l.lotteryid = '".$aGetData['lottery_id']."'";
            }
            $aSaleList = $oSale->getSingleSale($sWhere, $sOrderBy=" GROUP BY us.lotteryid,us.methodid");
            if (!empty($aSaleList) && !empty($aGetData)) {
                $this->outPutJQgruidJson($aSaleList,count($aSaleList));
            }
        } else {
            $GLOBALS['oView']->assign('ur_here', '查询彩种盈亏报表');
            $GLOBALS['oView']->display("report_plotteyprofitinfo.html");
            EXIT;
        }
    }

    /**
     * 单期盈亏报表导出
     */
    function actionExportReport(){
        $_GET = json_decode($_GET['getData'],TRUE);
        $oSale = new model_sale();
        $aGetData = $this->get(array(
            "page" => parent::VAR_TYPE_INT,
            "rows" => parent::VAR_TYPE_INT,
            "modes" => parent::VAR_TYPE_INT,
            "issue" => parent::VAR_TYPE_INT,
            "sidx" => parent::VAR_TYPE_STR,
            "sord" => parent::VAR_TYPE_STR,
            "lottery" => parent::VAR_TYPE_INT,
            "sTime" => parent::VAR_TYPE_DATETIME,
            "eTime" => parent::VAR_TYPE_DATETIME,
        ));
        $aGetData['modes'] = isset($aGetData['modes']) ? intval($aGetData['modes']) : -1;
        $aGetData['lotteryid'] = isset($aGetData['lottery']) ? intval($aGetData['lottery']) : 0;
        if (time() < strtotime(date("Y-m-d " . $this->sReportTime))) {
            $aGetData['sTime'] = isset($aGetData['sTime']) ? $aGetData['sTime'] : date("Y-m-d " . $this->sReportTime, time() - 86400);
            $aGetData['eTime'] = isset($aGetData['eTime']) ? $aGetData['eTime'] : date("Y-m-d " . $this->sReportTime, time());
        } else {
            $aGetData['sTime'] = isset($aGetData['sTime']) ? $aGetData['sTime'] : date("Y-m-d " . $this->sReportTime, time());
            $aGetData['eTime'] = isset($aGetData['eTime']) ? $aGetData['eTime'] : date("Y-m-d " . $this->sReportTime, time() + 86400);
        }
        $aGetData['rows'] = isset($aGetData['rows']) ? intval($aGetData['rows']) : 500; // 分页用1
        $aGetData['page'] = isset($aGetData['page']) ? intval($aGetData['page']) : 0; // 分页用2
        $sWhere = "sl.`lvtopid` =  '".$this->lvtopid."'";
        $sWhere .= $this->_getWhereStr($aGetData);
        $sFiled = '';
        $aGetData['sidx'] = !empty($aGetData['sidx']) ? $aGetData['sidx'] : 'joindate';
        $sOrderBy = " ORDER BY `" . $aGetData['sidx'] ."` ". $aGetData['sord']." ";
        $aSaleList = $oSale->getSingleSale($sFiled, $sWhere, $sOrderBy, '', '', $aGetData['modes']);
        $expTitle  = "Report";
        $expCellName  = [
            ['joindate','日期'],
            ['cnname','游戏'],
            ['issue','奖期'],
            ['code','开奖号码'],
            ['saleend','截止投注时间'],
            ['writetime','开奖时间'],
            ['usercount','投注人数'],
            ['sell','下注总额'],
            ['bonus','返奖总额'],
            ['totalpoints','返点总额'],
            ['saleresult','盈亏值'],
        ];
        ExportExcel($expTitle,$expCellName , $aSaleList);
    }

    /**
     * 用户输赢排行导出
     */
    function actionExportUserWinOrder(){
        $_GET = json_decode($_GET['getData'],TRUE);
        $oMarketmgr = new model_preport;
        $aGetData = $this->get(array(
            "page" => parent::VAR_TYPE_INT,
            "rows" => parent::VAR_TYPE_INT,
            "lottery" => parent::VAR_TYPE_INT,
            "sidx" => parent::VAR_TYPE_STR,//排序字段
            "sord" => parent::VAR_TYPE_STR,//排序类型（desc or acs）
            "uStartTime" => parent::VAR_TYPE_DATETIME,
            "uEndTime" => parent::VAR_TYPE_DATETIME,
        ));
        $sSidx = isset($aGetData['sidx']) && $aGetData['sidx'] != '' ? $aGetData['sidx'] : "totallose";
        $sSord = isset($aGetData['sord']) && $aGetData['sord'] != '' ? $aGetData['sord'] : "ASC";
        $sWhere = "ur.`lvtopid` = {$this->lvtopid}";
        $sStartDay = date("Y-m-d", strtotime($aGetData['uStartTime']));
        $sEndDay = date("Y-m-d", strtotime($aGetData['uEndTime']) - 86400);
        if ($sStartDay == $sEndDay) {
            $sWhere .= " AND ur.`day` = '" . $sStartDay . "'";
        } else {
            $sWhere .= " AND ur.`day` BETWEEN '" . $sStartDay . "' AND '" . $sEndDay . "' ";
        }
        if (isset($aGetData['lottery']) && $aGetData['lottery'] != 0) {//根据彩种查询报表
            $sWhere .= " AND ur.`lotteryid` = '" . $aGetData['lotteryid'] . "'";
        }
        $aUserWinOrder = $oMarketmgr->getUserWinOrder($sWhere, $sSidx, $sSord, '', '');
        foreach ($aUserWinOrder as $iKey => & $aUserList) {
            if ($aUserList['totalprice'] != 0){
                $aUserList['companyrate'] = $aUserList['totallose'] / $aUserList['totalprice'] * 100;
                $totallose = $aUserList['totallose'] / $aUserList['totalprice'] * 100;
            }
            $aUserList['companyrate'] = number_format(isset($totallose) ? $totallose : 0,2)."%";
            $aUserList['order'] = $iKey + 1;
        }
        $expTitle  = "UserWinOrder";
        $expCellName  = [
            ['order','排名'],
            ['username','用户名'],
            ['totalprice','总投注金额'],
            ['totalreturn','投注返点'],
            ['totalbonus','总中奖金额'],
            ['totallose','总结算'],
            ['companyrate','公司利润'],
            ['lastip','投注IP']
        ];
        ExportExcel($expTitle,$expCellName , $aUserWinOrder);
    }
}