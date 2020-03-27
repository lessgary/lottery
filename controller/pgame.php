<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/15
 * Time: 15:02
 */
class controller_pgame extends pcontroller{

    static $lotteryTypes = array(
        '0' => '数字型',
        '2' => '乐透型',
        '3' => '基诺型'
    );
    static $methodTypes = array(
        '0' => '（信）',
        '1' => '（官）'
    );

    /**
     * 注单记录
     */
    public function actionPlayList(){
        $oMethod = new model_method;
        if ($this->getIsAjax()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "lotteryid" => parent::VAR_TYPE_STR,
                "methodid" => parent::VAR_TYPE_INT,
                "issueid" => parent::VAR_TYPE_STR,
                "sort" => parent::VAR_TYPE_STR,
                "username" => parent::VAR_TYPE_STR,
                "periods" => parent::VAR_TYPE_STR,
                "moneytype" => parent::VAR_TYPE_STR,
                "nameType" => parent::VAR_TYPE_STR,
                "minmoney" => parent::VAR_TYPE_FLOAT,
                "maxmoney" => parent::VAR_TYPE_FLOAT,
                "minmultiple" => parent::VAR_TYPE_FLOAT,
                "maxmultiple" => parent::VAR_TYPE_FLOAT,
                "pStartTime" => parent::VAR_TYPE_DATETIME,
                "pEndTime" => parent::VAR_TYPE_DATETIME,
                "isCancel" => parent::VAR_TYPE_INT,
                'game_status' => parent::VAR_TYPE_INT,
                'game_way' => parent::VAR_TYPE_INT
            ));
            if ($aGetData['game_way']==2){
                $sSqlWhere="a.`pid`>0";
            }else{
                $sSqlWhere="a.`pid`>0 AND a.is_official=".$aGetData['game_way'];
            }
            $aMethod = $oMethod->methodGetList("a.`methodname`,a.`is_official`,a.`isrx`,a.`position`,a.`methodid`,b.`cnname`,b.`lotteryid`,b.`frequencytype`", $sSqlWhere, '', 0, 0, TRUE);
            $aLottery = array();
            $aMethods = array();
            $aHighLotteryId = array(); //高频彩种ID
            $aLowLotteryId = array(); //低频彩种ID
            $aMethodName =  array();//玩法名称
            $aMethodPosition = array();
            foreach ($aMethod as $k => $method) {
                $aLottery[$method["lotteryid"]] = $method["cnname"];
                if ($method['frequencytype'] == 0) {
                    $aHighLotteryId[] = $method['lotteryid'];
                } elseif ($method['frequencytype'] == 1) {
                    $aLowLotteryId[] = $method['lotteryid'];
                }
                $aMethods[$method["lotteryid"]][] = array(
                    "methodid" => $method["methodid"],
                    "methodname" => $method["methodname"]
                );
                $aMethodName[$method['methodid']] = $method['methodname'];
                if ($method['isrx'] == 1) {
                    $sPosition = decbin($method['position']);
                    $aPosition = str_split($sPosition);
                    $aMethodPosition[$method['methodid']] = array_pad($aPosition, -5, 0); // 不够5位的用0补齐
                }
            }
            $sWhere = "P.`lvtopid` = {$this->lvtopid}";
//开始时间
            if (isset($aGetData["pStartTime"]) && !empty($aGetData["pStartTime"])) {
                $sStartTime = getFilterDate($aGetData["pStartTime"]);
            } else {//默认为当天
                $sStartTime = time() < strtotime(date("Y-m-d " . $this->sReportTime)) ? date("Y-m-d " . $this->sReportTime, strtotime("-1 days")) : date("Y-m-d " . $this->sReportTime);
            }
            if (!empty($sStartTime)) {
                $sWhere .= " AND P.`writetime`>'" . $sStartTime . "'";
            }
            //结束时间
            if (isset($aGetData["pEndTime"]) && !empty($aGetData["pEndTime"])) {
                $sEndtime = getFilterDate($aGetData["pEndTime"]);
            } else {//默认为当天
                $sEndtime = time() < strtotime(date("Y-m-d " . $this->sReportTime)) ? date("Y-m-d " . $this->sReportTime) : date("Y-m-d " . $this->sReportTime, strtotime("+1 days"));
            }
            if (!empty($sEndtime)) {
                $sWhere .= " AND P.`writetime`<='" . $sEndtime . "'";
            }
            //彩种
            $iLotteryId = $aGetData["lotteryid"];
//            $iLotteryId = isset($aGetData["lotteryid"]) && is_numeric($aGetData["lotteryid"]) ? intval($aGetData["lotteryid"]) : 0;
            if ($iLotteryId > 0) {
                //拆分数组
                $iLottery = explode('.',$iLotteryId);//0是游戏id ，1是否是官方玩法
                $sWhere .=" AND P.`lotteryid`='" . $iLottery[0] . "'";
                $sWhere .= " AND ME.is_official = '".$iLottery[1]. "'";
                //玩法
                $iMethodid = isset($aGetData["methodid"]) && is_numeric($aGetData["methodid"]) ? intval($_POST["methodid"]) : 0;
                if ($iMethodid > 0) {
                    $sWhere .=" AND P.`methodid`='" . $iMethodid . "'";
                }
            }
            //期数
            if (isset($aGetData["issueid"]) && !empty($aGetData["issueid"])) {
                $sWhere .=" AND P.`issue`='" . $aGetData["issueid"] . "'";
            }
            if ($aGetData['nameType'] == "username"){
                //名字
                if (isset($aGetData["username"]) && !empty($aGetData["username"])) {
                    $sWhere .=" AND UT.`username` = '" .  $aGetData["username"] ."'";
                }
            }elseif ($aGetData['nameType'] == "periods"){
                //注单编号
                if (isset($aGetData["periods"]) && !empty($aGetData["periods"])) {
                    $sWhere .= " AND P.`projectid`='" . myHash($aGetData["periods"], "DECODE", 'P') . "'";
                }
            }
            //奖金和倍数的区间
            if ($aGetData['moneytype'] == "money") {
                if (isset($aGetData["minmoney"]) && isset($aGetData["maxmoney"])){
                    if ($aGetData["maxmoney"] !== "" && $aGetData["minmoney"] !== ""){
                        $sWhere .=" AND P.`bonus`>='" .  floatval($aGetData["minmoney"]) . "' AND P.`bonus`<='" .  floatval($aGetData["maxmoney"]) . "'";
                    }elseif ($aGetData["maxmoney"] === "" && $aGetData["minmoney"] !== ""){
                        $sWhere .=" AND P.`bonus`>='" .  floatval($aGetData["minmoney"]) . "'";
                    }elseif ($aGetData["maxmoney"] !== "" && $aGetData["minmoney"] === "") {
                        $sWhere .=" AND P.`bonus`<='" .  floatval($aGetData["maxmoney"]) . "'";
                    }
                }
            }elseif ($aGetData['moneytype'] == "multiple"){
                if (isset($aGetData["minmultiple"]) && isset($aGetData["maxmultiple"])){
                    if ($aGetData["maxmultiple"] !== "" && $aGetData["minmultiple"] !== ""){
                        $sWhere .=" AND P.`multiple`>='" .  floatval($aGetData["minmultiple"]) . "' AND P.`multiple`<='" .  floatval($aGetData["maxmultiple"]) . "'";
                    }elseif ($aGetData["maxmultiple"] === "" && $aGetData["minmultiple"] !== ""){
                        $sWhere .=" AND P.`multiple`>='" .  floatval($aGetData["minmultiple"]) . "'";
                    }elseif ($aGetData["maxmultiple"] !== "" && $aGetData["minmultiple"] === "") {
                        $sWhere .=" AND P.`multiple`<='" .  floatval($aGetData["maxmultiple"]) . "'";
                    }
                }
            }
            // 中奖状态
            if (isset($aGetData['game_status']) && '-1' !== strval($aGetData['game_status'])) {
                $sWhere .= " AND  P.`isgetprize`='{$aGetData['game_status']}'";
            }
            // 中奖状态
            if (isset($aGetData['isCancel']) && '-1' !== strval($aGetData['isCancel'])) {
                $sWhere .= " AND  P.`isCancel`='{$aGetData['isCancel']}'";
            }
            $aGetData["sort"] = isset($aGetData["sort"]) && $aGetData["sort"] ? $aGetData["sort"] : "DESC";
            $oProject = new model_pprojects;
            if ($aGetData['game_way']!=2){
                $sWhere.=" AND ME.`is_official`=".$aGetData['game_way'];
            }
            $aProject = $oProject->projectGetResult("", $sWhere, $aGetData['sort'], $aGetData['rows'], $aGetData['page']);
            foreach ($aProject["results"] as &$project) {
                $project['cnname'] = $aLottery[$project['lotteryid']].self::$methodTypes[$project['is_official']];
                $project['methodname'] = $aMethodName[$project['methodid']];
                $project['totolSum'] = $aProject['sumPrice'];
                $project['totolBonus'] = $aProject['sumBonus'];
                $project['positioncode'] = '';
//                $aCur_Position = $aMethodPosition[$project['methodid']];
                if (!empty($aCur_Position)) {
                    $aBonusCode = str_split($project['nocode']);
                    foreach ($aCur_Position as $iKey => $iData) {
                        if ($iData == 1) {
                            $project['positioncode'] .= "<font color=red>" . $aBonusCode[$iKey] . "</font>";
                        }else{
                            $project['positioncode'] .= "<font color=black>" . $aBonusCode[$iKey] . "</font>";
                        }
                    }
                }else{
                    $project['positioncode'] = $project['nocode'];
                }
                $project['code'] = model_projects::AddslasCode($project['code'], $project['methodid']);
                //对号码进行整理
                if (strlen($project["code"]) > 40) {
                    $str = "";
                    $sTempCode = "";
                    $sProjectCode = "";
                    $aCodeDetail = explode(",", $project["code"]);
                    $iCodeLen = strlen($aCodeDetail[0]) + 1; //单个号码长度
                    $iRowCodeLen = intval(40 / $iCodeLen) * $iCodeLen; //一行的号码最大长度
                    foreach ($aCodeDetail as $sCode) {
                        $sTempCode .= $sCode . ",";
                        $sProjectCode .= $sCode . ",";
                        if (strlen($sTempCode) >= $iRowCodeLen) {
                            $sProjectCode = substr($sProjectCode, 0, -1);
                            $sProjectCode .= "\r\n";
                            $sTempCode = "";
                        }
                    }
                    $sProjectCode = substr($sProjectCode, 0, -1);
                    $str .= $sProjectCode;
                    $project["code"] = $str;
                }
                $project["encodeprojectid"] = myHash($project['projectid'], "ENCODE", 'P');
                if(strlen($project["code"]) > 205) {
                    $project["code"] = substr($project["code"], 0, 204) . ' （点击注单编号查看号码详情...）';
                }
                if ($project['codetype'] == 'input' && strstr($project['methodname'], '混合') === FALSE) {
                    $project['methodname'] = $project['methodname'] . '[单式]';
                }
            }
            if (!empty($aProject)) {
                $this->outPutJQgruidJson($aProject['results'],$aProject['affects'] , $aGetData['page'], $aGetData['rows']);
            }
        }else{
            $aMethod = $oMethod->getProxyMethodList("a.`methodname`,a.`isclose`,a.`methodid`,if(a.isclose=0,b.cnname,concat(b.`cnname`,'(停售)')) as cnname,b.`lotteryid`,b.`frequencytype`", "a.`pid`>0 AND pls.lvtopid='".$this->lvtopid."'", '', 0, 0, TRUE);
           // $aLottery = array();
            $aMethods = array();
            $aHighLotteryId = array();//高频彩种ID
            $aLowLotteryId = array();//低频彩种ID
            foreach ($aMethod as $method) {
               // $aLottery[$method["lotteryid"]] = $method["cnname"];
                if ($method['frequencytype'] == 0) {
                    $aHighLotteryId[] = $method['lotteryid'];
                } elseif ($method['frequencytype'] == 1) {
                    $aLowLotteryId[] = $method['lotteryid'];
                }
                $aMethods[$method["lotteryid"]][] = array(
                    "methodid" => $method["methodid"],
                    "methodname" => $method["methodname"]
                );
            }
            $oLottery = new model_plottery();
            $aLottery = $oLottery->getLotteryMethodList($this->lvtopid);
            foreach ($aLottery as $key => &$value) {
                if($value['is_official']==1){
                    $value['cnname'] = $value['cnname'].'(官)';
                }
                if($value['lvtopid_isclose']==1) {
                     $value['cnname'] = $value['cnname'].'(停售)';
                }
            }
            $GLOBALS['oView']->assign("lottery", $aLottery);
            $GLOBALS['oView']->assign("data_method", json_encode($aMethods));
            $GLOBALS['oView']->assign('sTime', date('Y-m-d ' . $this->sReportTime));
            $GLOBALS['oView']->assign('eTime', date('Y-m-d ' . $this->sReportTime, strtotime('+1 days')));
            $GLOBALS['oView']->display("game_playlist.html");
        }
    }
    /**
     * 投注详情
     */
    public function actionPlayDetail(){
        $aLocation[0] = array("text" => '关闭', "href" => 'javascript:self.close();');
        if (isset($_GET["id"]) && !empty($_GET["id"])) {
            $iProjectId = myHash($_GET["id"], "DECODE", 'P');
            if ($iProjectId == 0) {
                sysMessage('方案不存在', 1, $aLocation);
            }
            /* @var $oProject model_projects */
            $oProject = new model_projects();
            $sFields = " P.*, L.`cnname`,L.`lotterytype`, M.`methodname`,M.`nocount`, UT.`username`, I.`canneldeadline`,I.`statuscode`";
            $aProject = $oProject->projectGetOne($iProjectId, $sFields);
//            print_rr($aProject);
            if (empty($aProject)) {
                sysMessage('方案不存在', 1, $aLocation);
            }
            //注单编号
            if (intval($aProject["taskid"]) > 0) {
                $aProject["taskid"] = myHash($aProject["taskid"], "ENCODE", 'T');
            }
            if ($aProject['codetype'] == 'input' && strstr($aProject['methodname'], '混合') === FALSE) {
                $aProject['methodname'] = $aProject['methodname'] . '[单式]';
            }
            $aProject['code'] = model_projects::AddslasCode($aProject['code'], $aProject['methodid']);
            $aProject["code"] = wordwrap($aProject["code"], 100, "<br />", TRUE);
            $aProject["projectid"] = myHash($aProject["projectid"], "ENCODE", 'P');
            $aProject["userpoint"] *= 100;
            $GLOBALS['oView']->assign("ur_here", "查看注单详情");
            $GLOBALS['oView']->assign("aProject", $aProject);
            $sCondition = " `projectid`='" . $iProjectId . "' ";
            $aPrizelevel = $oProject->getExtendCode("*", $sCondition, "`isspecial` ASC", 0);
            $aPrizelevelDesc = unserialize($aProject['nocount']);
            //获取中奖详情
            /* @var $oGetPrize model_getprize */
            $oGetPrize = new model_getprize();
            if ($aProject['isgetprize'] == 1 && $aProject['prizestatus'] == 1) {
                $aProjectPrize = $oGetPrize->getProjectPrize($iProjectId, $aProject['methodid'], $aPrizelevel, $aProject['nocode'], $aProject['codetype']);
                if (!empty($aProjectPrize) && $aProjectPrize['totalprize'] != 0.00) {
                    foreach ($aProjectPrize['detail'] as $iLevel => &$PrizeDetail) {
                        $PrizeDetail['multiple'] = $aProject['multiple'];
                        $aDesc = explode(":", $aPrizelevelDesc[$iLevel]['name']);
                        if (count($aDesc) == 2) {
                            $PrizeDetail["leveldesc"] = $aDesc[0];
                            $PrizeDetail["levelcodedesc"] = $aDesc[1];
                        } else {
                            $PrizeDetail["leveldesc"] = $aPrizelevelDesc[$iLevel]['name'];
                            $PrizeDetail["levelcodedesc"] = $aPrizelevelDesc[$iLevel]['name'];
                        }
                        $PrizeDetail["singleprize"] = $PrizeDetail['prize'] / $PrizeDetail['multiple'] / $PrizeDetail['times'];
                    }
                    if (!empty($aProjectPrize)) {
                        $GLOBALS['oView']->assign("projectprize", $aProjectPrize);
                    }
                }
            }
            //扩展号码整理
            foreach ($aPrizelevel as &$prizelevel) {
                $aDesc = explode(":", $aPrizelevelDesc[$prizelevel['level']]['name']);
                if (count($aDesc) == 2) {
                    $prizelevel["leveldesc"] = $aDesc[0];
                    $prizelevel["levelcodedesc"] = $aDesc[1];
                } else {
                    $prizelevel["leveldesc"] = $aPrizelevelDesc[$prizelevel['level']]['name'];
                    $prizelevel["levelcodedesc"] = $aPrizelevelDesc[$prizelevel['level']]['name'];
                }
                $prizelevel["singleprize"] = $prizelevel["prize"] / $prizelevel["codetimes"];
                $prizelevel["expandcode"] = model_projects::AddslasCode($prizelevel["expandcode"], $aProject['methodid']);
                $prizelevel["expandcode"] = wordwrap(str_replace(array("|", "#"), array(", ", "|"), $prizelevel["expandcode"]), 100, "<br>", TRUE);
            }
            $GLOBALS['oView']->assign("prizelevel", $aPrizelevel);
            $GLOBALS['oView']->assign("modes", $GLOBALS['config']['modes']);
            $oProject->assignSysInfo();
            $GLOBALS['oView']->display('game_detail.html');
            EXIT;
        } else {
            sysMessage('方案不存在', 1, $aLocation);
        }
    }
    /**
     * 后台撤单
     */
    function actionPlayCancel() {
        $sProjectNo = !empty($_GET["id"]) ? $_GET["id"] : "";
        $sRemark = !empty($_GET["remark"]) ? $_GET["remark"] : "";
        $aLocation[0] = array("text" => '查看方案详情', "href" => url('pgame', 'playdetail', array('id' => $sProjectNo)));
        $iProjectId = !empty($sProjectNo) ? myHash($sProjectNo, "DECODE", 'P') : 0;
        if ($iProjectId == 0) {
            sysMessage('操作错误', 1, $aLocation);
        }
        if (empty($_GET['uid']) || !is_numeric($_GET['uid'])) {
            sysMessage('操作错误', 1, $aLocation);
        }
        $iUserId = intval($_GET['uid']);
        /* @var $oGame model_gamemanage */
        $oGame = new model_gamemanage();
        $mResult = $oGame->cancelProject($iUserId, $iProjectId, $_SESSION['proxyadminid'],$sRemark);
        if ($mResult === TRUE) {
            sysMessage('撤单成功', 0, $aLocation);
        } else {
            sysMessage($mResult, 1, $aLocation);
        }
    }

    /**
     * 游戏列表
     */
    public function actionList(){
        if ($this->getIsAjax()){
            /* @var $oLottery model_plottery */
            $oLottery = new model_plottery();
            $aLottery = $oLottery->getLotteryMethodList($this->lvtopid);
//            $aLottery = $oLottery->lotteryMethodGetList('', '', ' ORDER BY pls.`sorts` ASC ', 0,'',$this->lvtopid);
            foreach ($aLottery as & $aLotteryDetail) {
                $aLotteryDetail['issueset'] = unserialize($aLotteryDetail['issueset']);
                $aLotteryDetail['starttime'] = ''; // 某阶段的 奖期开始时间
                $aLotteryDetail['endtime'] = ''; // 某阶段的 奖期结束时间
                $aLotteryDetail['cycle'] = ''; // 某阶段的 奖期周期
                $aLotteryDetail['inputcodetime'] = ''; // 录入号码的差异秒数
                $aLotteryDetail['endsale'] = ''; // 截止销售的差异描述
                foreach ($aLotteryDetail['issueset'] as $aIssueSet) {
                    $aLotteryDetail['cycle'] .= $aIssueSet['cycle'] . ',';
                    $aLotteryDetail['inputcodetime'] .= $aIssueSet['inputcodetime'] . ',';
                    $aLotteryDetail['endsale'] .= $aIssueSet['endsale'] . ',';
                }
                //获取第一段的开始时间和最后一段的结束时间
                reset($aLotteryDetail['issueset']);
                $aTemp = current($aLotteryDetail['issueset']);
                $aLotteryDetail['starttime'] = $aTemp['starttime'];
                $aTemp = end($aLotteryDetail['issueset']);
                $aLotteryDetail['endtime'] = $aTemp['endtime'];
                unset($aTemp);
                $aLotteryDetail['cycle'] = substr($aLotteryDetail['cycle'], 0, -1);
                $aLotteryDetail['inputcodetime'] = substr($aLotteryDetail['inputcodetime'], 0, -1);
                $aLotteryDetail['endsale'] = substr($aLotteryDetail['endsale'], 0, -1);
                if ($aLotteryDetail['lotterytype'] == 0){
                    $aLotteryDetail['lotteryTypeName'] = "数字型";
                }elseif ($aLotteryDetail['lotterytype'] == 1){
                    $aLotteryDetail['lotteryTypeName'] = "乐透分区型";
                }elseif ($aLotteryDetail['lotterytype'] == 2){
                    $aLotteryDetail['lotteryTypeName'] = "乐透同区型";
                }elseif ($aLotteryDetail['lotterytype'] == 3){
                    $aLotteryDetail['lotteryTypeName'] = "基诺型";
                }elseif ($aLotteryDetail['lotterytype'] == 1){
                    $aLotteryDetail['lotteryTypeName'] = "排列型";
                }else{
                    $aLotteryDetail['lotteryTypeName'] = "分组型";
                }
                if ($aLotteryDetail['weekcycle'] == 127){
                    $aLotteryDetail['weekCycleType'] = "每天";
                }else{
                    $aLotteryDetail['weekCycleType'] = "非每天";
                }
                if ($aLotteryDetail['lvtopid_isclose'] == 1){
                    $aLotteryDetail['status'] = "停止销售";
                    $aLotteryDetail['set_status'] = "开售";
                }else{
                    $aLotteryDetail['status'] = "销售中";
                    $aLotteryDetail['set_status'] = "停售";
                }
            }
            $aResult['result'] = $aLottery;
            $aResult['affects'] = count($aResult['result']);
//            print_rr($aResult);
            if (!empty($aResult) && !empty($_GET)) {
                $this->outPutJQgruidJson($aResult['result'],$aResult['affects'],1,1000);
            }
        }
        $GLOBALS['oView']->assign("ur_here", "游戏信息列表");
        $GLOBALS['oView']->display('pgame_list.html');
        exit;
    }
    /**
     * 奖期时间
     */
    public function actionTimeList(){
        $aLocation[0]   = array( "text"=>'游戏信息列表', "href"=>url("game","list") );
        /* @var $oLottery model_lottery */
        $oLottery = new model_lottery();
        $iLotteryId = isset($_GET['id']) && $_GET['id'] != '' ? intval($_GET['id']) : 0;
        if( $iLotteryId == 0 )
        {
            sysMessage( '请选择彩种', 1, $aLocation );
        }
        $aLocation[1]   = array( "text"=>'增加奖期时间', "href"=>url("issue","timeadd",array('lotteryid'=>$iLotteryId)) );
        $aLottery = $oLottery->getItem( $iLotteryId );
        //整理issueset数据,用于显示
        $aLottery['issueset'] = $aLottery['issueset'];
        $GLOBALS["oView"]->assign( "ur_here", "奖期时间规则管理" );
        $GLOBALS["oView"]->assign( "aLottery",   $aLottery );
        $GLOBALS["oView"]->assign( "actionlink", $aLocation[0] );
        $GLOBALS["oView"]->assign( "actionlink2", $aLocation[1] );
        $oLottery->assignSysinfo();
        $GLOBALS['oView']->display( "pgame_timelist.html" );
        EXIT;
    }
    /**
     * 设置销售状态
     */
    public function actionSetSaleStatus(){
        $aGetData = $this->get(array(
            "id" => parent::VAR_TYPE_INT,
            "isclose" => parent::VAR_TYPE_INT,
        ));
        $oPlottery = new model_plottery();
        $lvtopid = $oPlottery->getUserlvtopid($aGetData['id']);
        if($lvtopid['lvtopid'] != $this->lvtopid) $this->ajaxMsg(0,"非法操作");
        $aData['isclose'] = $aGetData['isclose'];
        $bResult = $oPlottery->locklottery($aGetData['id'],$aData,$this->lvtopid);
        if ($bResult) {
            sysMessage("操作成功");
        }else{
            sysMessage("操作失败", 1);
        }
    }
    /**
     * 追单记录
     */
    public function actionTaskList(){
        $oMethod = new model_method;
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "lotteryid" => parent::VAR_TYPE_INT,
                "methodid" => parent::VAR_TYPE_INT,
                "taskstatus" => parent::VAR_TYPE_INT,
                "issueid" => parent::VAR_TYPE_INT,
                "sort" => parent::VAR_TYPE_STR,
                "username" => parent::VAR_TYPE_STR,
                "periods" => parent::VAR_TYPE_STR,
                "searchType" => parent::VAR_TYPE_STR,
                "tStartTime" => parent::VAR_TYPE_DATETIME,
                "tEndTime" => parent::VAR_TYPE_DATETIME,
            ));
            $aMethod = $oMethod->methodGetList("a.`methodname`,a.`methodid`,b.`cnname`,b.`lotteryid`,b.`frequencytype`", "a.`pid`>0", '', 0, 0, TRUE);
            $aLottery = array();
            $aMethods = array();
            $aHighLotteryId = array(); //高频彩种ID
            $aLowLotteryId = array(); //低频彩种ID
            $aMethodName = array(); //玩法名称
            foreach ($aMethod as $method) {
                $aLottery[$method["lotteryid"]] = $method["cnname"];
                if ($method['frequencytype'] == 0) {
                    $aHighLotteryId[] = $method['lotteryid'];
                } elseif ($method['frequencytype'] == 1) {
                    $aLowLotteryId[] = $method['lotteryid'];
                }
                $aMethods[$method["lotteryid"]][] = array(
                    "methodid" => $method["methodid"],
                    "methodname" => $method["methodname"]
                );
                $aMethodName[$method['methodid']] = $method['methodname'];
            }
            //开始时间
            if (isset($aGetData["tStartTime"]) && !empty($aGetData["tStartTime"])) {
                $aGetData["tStartTime"] = getFilterDate($aGetData["tStartTime"]);
            } else {//默认为当天
                $aGetData["tStartTime"] = time() < strtotime(date("Y-m-d " . $this->sReportTime)) ? date("Y-m-d " . $this->sReportTime, strtotime("-1 days")) : date("Y-m-d " . $this->sReportTime);
            }
            //结束时间
            if (isset($aGetData["tEndTime"]) && !empty($aGetData["tEndTime"])) {
                $aGetData["tEndTime"] = getFilterDate($aGetData["tEndTime"]);
            } else {//默认为当天
                $aGetData["tEndTime"] = time() < strtotime(date("Y-m-d " . $this->sReportTime)) ? date("Y-m-d " . $this->sReportTime) : date("Y-m-d " . $this->sReportTime, strtotime("+1 days"));
            }
            $aGetData["lotteryid"] = isset($aGetData["lotteryid"]) && is_numeric($aGetData["lotteryid"]) ? intval($aGetData["lotteryid"]) : 0;
            $aGetData["issueid"] = isset($aGetData["issueid"]) ? daddslashes($aGetData["issueid"]) : "0";
            $aGetData["taskstatus"] = isset($aGetData["taskstatus"]) && is_numeric($aGetData["taskstatus"]) ? intval($aGetData["taskstatus"]) : -1;
            $oTask = new model_ptask;
            $sWhere = "T.`lvtopid` = {$this->lvtopid}";
            $sWhere .= $this->_getWhereStr($aGetData);
            $aTask = $oTask->taskgetList("", $sWhere, " T.`begintime` DESC", $aGetData['rows'], $aGetData['page']);
            foreach ($aTask["results"] as $iTaskid => $task) {
                $aTask["results"][$iTaskid]['methodname'] = $aMethodName[$task['methodid']];
                $aTask["results"][$iTaskid]['cnname'] = $aLottery[$task['lotteryid']];
                $aTask["results"][$iTaskid]["encodetaskid"] = myHash($task["taskid"], "ENCODE", 'T');
                $aTask["results"][$iTaskid]['codes'] = model_projects::AddslasCode($task['codes'], $task['methodid']);
                //对号码进行整理
                if (strlen($aTask["results"][$iTaskid]['codes']) > 40) {
                    $str = "";
                    $sTempCode = "";
                    $sProjectCode = "";
                    $aCodeDetail = explode(",", $aTask["results"][$iTaskid]['codes']);
                    $iCodeLen = strlen($aCodeDetail[0]) + 1; //单个号码长度
                    $iRowCodeLen = intval(40 / $iCodeLen) * $iCodeLen; //一行的号码最大长度
                    foreach ($aCodeDetail as $sCode) {
                        $sTempCode .= $sCode . ",";
                        $sProjectCode .= $sCode . ",";
                        if (strlen($sTempCode) >= $iRowCodeLen) {
                            $sProjectCode = substr($sProjectCode, 0, -1);
                            $sProjectCode .= "\r\n";
                            $sTempCode = "";
                        }
                    }
                    $sProjectCode = substr($sProjectCode, 0, -1);
                    $str .= $sProjectCode;
                    $aTask["results"][$iTaskid]['codes'] = $str;
                }
                if ($aTask["results"][$iTaskid]['codetype'] == 'input' && strstr($aTask["results"][$iTaskid]['methodname'], '混合') === FALSE) {
                    $aTask["results"][$iTaskid]['methodname'] = $aTask["results"][$iTaskid]['methodname'] . '[单式]';
                }
            }
            if (!empty($aTask) && !empty($aGetData)) {
                $this->outPutJQgruidJson($aTask['results'],$aTask['affects'] , $aGetData['page'], $aGetData['rows']);
            }
        }else{
            //修复彩种错误显示停售的问题
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
            $GLOBALS['oView']->assign("lottery", $aLottery);
            $GLOBALS['oView']->assign("data_method", json_encode($aMethod));
            $GLOBALS['oView']->assign('sTime', date('Y-m-d ' . $this->sReportTime));
            $GLOBALS['oView']->assign('eTime', date('Y-m-d ' . $this->sReportTime, strtotime('+1 days')));
            $GLOBALS['oView']->display("game_tasklist.html");
        }
    }
    /**
     * 追号详情
     */
    public function actionTaskDetail(){
//        print_rr($_REQUEST);
        $aLocation[0] = array("text" => "关闭", "href" => 'javascript:close();');
        $sTaskId = isset($_GET["id"]) && !empty($_GET["id"]) ? daddslashes($_GET["id"]) : "";
        if ($sTaskId == "") {
            sysMessage('参数错误', 1, $aLocation);
        }
        /* @var $oTask model_task */
        $oTask = new model_task();
        $iTaskId = myHash($sTaskId, "DECODE", 'T');
        if ($iTaskId == 0) {
            sysMessage('参数错误', 1, $aLocation);
        }
        $aTask = $oTask->taskgetOne($iTaskId);
        if (empty($aTask)) {
            sysMessage('追号单不存在', 1, $aLocation);
        }
        if ($aTask['codetype'] == 'input' && strstr($aTask['methodname'], '混合') === FALSE) {
            $aTask['methodname'] = $aTask['methodname'] . '[单式]';
        }
        $aTask['codes'] = model_projects::AddslasCode($aTask['codes'], $aTask['methodid']);
        $aTask["codes"] = wordwrap($aTask["codes"], 100, "<br/>", TRUE);
        $aTask["taskid"] = myHash($aTask["taskid"], "ENCODE", 'T');
        $aTaskDetail = $oTask->taskdetailGetList($iTaskId, $aTask["lotteryid"]);
        foreach ($aTaskDetail as &$aDetail) {
            if ($aDetail["projectid"] > 0) { //注单详情
                $aDetail["projectid"] = myHash($aDetail["projectid"], "ENCODE", 'P');
            }
        }
        $GLOBALS["oView"]->assign("task", $aTask);
        $GLOBALS['oView']->assign("aTaskdetail", $aTaskDetail);
        $GLOBALS['oView']->assign("modes", $GLOBALS['config']['modes']);
        $GLOBALS['oView']->assign("ur_here", "查看追号详情");
        $GLOBALS['oView']->display("game_taskdetail.html");
        EXIT;
    }
    /**
     * 追单撤单
     */
    function actionTaskCancel() {
        $sTaskNo = !empty($_POST["id"]) ? $_POST["id"] : "";
        $sRemark = !empty($_POST["remark"]) ? $_POST["remark"] : "";
        $aLocation[0] = array("text" => '查看追号详情', "href" => url('pgame', 'taskdetail', array('id' => $sTaskNo)));
        $iTaskId = !empty($sTaskNo) ? myHash($sTaskNo, "DECODE", 'T') : 0;
        if ($iTaskId == 0) {
            sysMessage('权限不足', 1, $aLocation);
        }
        $aId = !empty($_POST["taskid"]) ? $_POST["taskid"] : array();
        if (empty($_POST['uid']) || !is_numeric($_POST['uid'])) {
            sysMessage('操作错误', 1, $aLocation);
        }
        $iUserId = intval($_POST['uid']);
        /* @var $oGame model_gamemanage */
        $oGame = A::singleton("model_gamemanage");
        $mResult = $oGame->cancelTask($iUserId, $iTaskId, $aId, $_SESSION['proxyadminid'],$sRemark);
        if ($mResult === TRUE) {
            sysMessage('操作成功', 0, $aLocation);
        } else {
            sysMessage($mResult, 1, $aLocation);
        }
    }
    /**
     * 开奖记录
     */
    public function actionOpenLottery(){
        $oOpenLottery = new model_openlottery();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "lottery" => parent::VAR_TYPE_INT,
                "sTime" => parent::VAR_TYPE_DATETIME,
                "eTime" => parent::VAR_TYPE_DATETIME,
            ));
            $aGetData['lotteryid'] = isset($aGetData['lottery']) ? intval($aGetData['lottery']) : 0;
            if (time() < strtotime(date("Y-m-d " . $this->sReportTime))) {
                $aGetData['sTime'] = isset($aGetData['sTime']) ? $aGetData['sTime'] : date("Y-m-d " , time() - 86400);
                $aGetData['eTime'] = isset($aGetData['eTime']) ? $aGetData['eTime'] : date("Y-m-d " , time());
            } else {
                $aGetData['sTime'] = isset($aGetData['sTime']) ? $aGetData['sTime'] : date("Y-m-d " , time());
                $aGetData['eTime'] = isset($aGetData['eTime']) ? $aGetData['eTime'] : date("Y-m-d " , time() + 86400);
            }
            $sWhere = "pls.lvtopid = '".$this->lvtopid."' AND ii.`statuscode` >= 2";//开奖状态为为写入的不查询
            if (isset($aGetData['lotteryid']) && $aGetData['lotteryid'] != 0) {//根据彩种查询
                $sWhere .= " AND ii.`lotteryid` = '" . $aGetData['lotteryid'] . "'";
            }
            if (isset($aGetData['sTime'])) {//统计的开始时间
                $sWhere .= " AND ii.`saleend` >= '" . $aGetData['sTime'] . "'";
            }
            if (isset($aGetData['eTime'])) {//统计的结束时间
                $sWhere .= " AND ii.`saleend` <= '" . $aGetData['eTime'] . "'";
            }
            $aOpenLottery = $oOpenLottery->getOpenLottery("", $sWhere, " ORDER BY ii.`saleend` DESC", $aGetData['rows'], $aGetData['page']);
            foreach ($aOpenLottery['results'] as &$v) {
                if ($v["statuscode"] == 3) {
                    $v["code"] = "官方未开奖";
                }
            }
            // 不开奖号码
//            $aOpenLottery['results'] = $this->_setJustOpen($aOpenLottery['results']);
            if (!empty($aOpenLottery) && !empty($aGetData)) {
                $this->outPutJQgruidJson($aOpenLottery['results'],$aOpenLottery['affects'] , $aGetData['page'], $aGetData['rows']);
            }
        } else {
             $oMethod = new model_method;
            //获取彩种列表
           $aMethod = $oMethod->getProxyMethodList("SUM(a.`isclose`) AS `count_status`,COUNT(a.`methodid`) AS `count_method`,a.`methodname`,a.`isclose`,a.`methodid`,a.`isclose`,a.`methodid`,if(pls.isclose=0,b.cnname,concat(b.`cnname`,'(停售)')) as cnname,b.`lotteryid`,b.`frequencytype`", "a.`pid`>0 AND pls.lvtopid='".$this->lvtopid."' GROUP BY  b.`lotteryid` ", '', 0, 0, TRUE);
           foreach ($aMethod as &$v ) {
               if (strpos($v['cnname'],'停售') ==  false) {
                   if (($v['count_status'] == $v['count_method']) || $v['count_method'] == 0) {
                       $v['cnname'] = $v['cnname']."(停售)";
                   }
               }
           }
            $aLottery = array();
            $aMethods = array();
            $aHighLotteryId = array();//高频彩种ID
            $aLowLotteryId = array();//低频彩种ID
            foreach ($aMethod as $method) {
                $aLottery[$method["lotteryid"]] = $method["cnname"];
            }
            $GLOBALS['oView']->assign('ur_here', '开奖记录');
            $GLOBALS['oView']->assign('sTime', date('Y-m-d H:i:s'));
            $GLOBALS['oView']->assign('eTime', date('Y-m-d H:i:s',strtotime('+1 days')));
            $GLOBALS['oView']->assign('lottery', $aLottery);
            $GLOBALS['oView']->display("game_openlottery.html");
            EXIT;
        }
    }
    /**
     * desc 奖金设定
     * @author rhovin 2017-07-15
     *
     */
    public function actionPrizeLevel() {
        $aGetData = $this->get(array(
                "id" => parent::VAR_TYPE_INT,
            ));
        $iLvtopLottery = $aGetData['id'];

        // 获取商家设置彩种信息
        /* @var $oPlottery model_plottery */
        $oPlottery = A::singleton("model_plottery");
        $aProxySet = $oPlottery->getLvTopLotteries($this->lvtopid, $iLvtopLottery);
        if(empty($aProxySet)) {
            sysMessage("商户不存在该彩种", 1);
        }
        $iLotteryId = $aProxySet['lotteryid'];
        $iIsOfficial = $aProxySet['is_official'];

        $oUser = new model_puser();
        $aUser = $oUser->getUserInfo($this->lvtopid,['pgtype','username']);
        /* @var $oPrizeGroup model_prizegroup */
        $oPrizeGroup = A::singleton("model_prizegroup");
        //根据总代的奖金组类型和彩种ID找到当前奖金组ID
        $aPrizeGroup = $oPrizeGroup->pgGetOne("*", "`lotteryid`='" . $iLotteryId . "' AND `type`='" . $aUser['pgtype'] . "' ");
        if(empty($aPrizeGroup)){
            $aPrizeGroup = $oPrizeGroup->pgGetOne("*", "`lotteryid`='" . $iLotteryId . "' AND `type`=4");
        }
        if(empty($aPrizeGroup)) sysMessage("奖金组不存在", 1);
        $iPgId = intval($aPrizeGroup['prizegroupid']);
        /* @var $oLottery model_lottery */
        $oLottery = A::singleton("model_lottery");
        $aLottery = $oLottery->lotteryCache($aPrizeGroup['lotteryid']);
        /* @var $oMethod model_method */
        $oMethod = A::singleton("model_method");
        $sFiled = "M.`methodid`,M.`lotteryid`,M.`methodname`,M.`level`,M.`type`,"
                . "M.`nocount`,M.`totalmoney`,M.`is_official`,L.`cnname`,MC.`crowdname`,MC.`crowdid`";
        $sCondition = "M.`lotteryid`='" . $iLotteryId . "' and M.`pid`='0'";
        $aMethod = $oMethod->methodGetListByCrowd($sFiled, $sCondition);

        // 过滤官方/信用玩法
        foreach($aMethod as $k=>$method) {
            foreach ($method['method'] as $key => $value) {
                if($value['is_official'] != $iIsOfficial) {
                    unset($aMethod[$k]['method'][$key]);
                }
            }
            if (empty($aMethod[$k]['method'])) {
                unset($aMethod[$k]);
            }
        }


        //奖金设置详情
        /* @var $oPrizeLevel model_proxyprizelevel */
        $oPrizeLevel = A::singleton("model_proxyprizelevel");
        $sWhere = "A.`prizegroupid`='" . $iPgId . "' AND A.`lvtopid`='".$this->lvtopid."'";
        $aPrizeLevel = $oPrizeLevel->prizelevelGetList("",$sWhere ,"", 0);
        $aPrizeLevels = array();
        foreach ($aPrizeLevel as $prizelevel) {
            $iMethodid = $prizelevel["methodid"];
            $iLevel = $prizelevel["level"];
            $aPrizeLevels["description"][$iMethodid][$iLevel] = $prizelevel["description"];
            $aPrizeLevels["prize"][$iMethodid][$iLevel] = $prizelevel["prize"];
            $aPrizeLevels["userpoint"][$iMethodid][$iLevel] = $prizelevel["userpoint"];
            $aPrizeLevels["isclose"][$iMethodid] = $prizelevel["isclose"];
        }
        foreach ($aMethod as $key => &$value) {
            $value['count'] = 0;
            foreach ($value['method'] as $key => $val) {
                $value['count'] += count($val['nocount']);
            }
        }
        $oPlottery = new model_plottery();
//        $aLotteryList = $oPlottery->lotteryMethodGetList('', '', ' ORDER BY pls.`sorts` ASC ', 0,'',$this->lvtopid);
        $aLotteryList = $oPlottery->getLotteryMethodList($this->lvtopid);
        $GLOBALS['oView']->assign("prizelevel", $aPrizeLevels);
        $GLOBALS['oView']->assign("pgid", $iPgId);
        $GLOBALS['oView']->assign("alottery", $aLottery);
        $GLOBALS['oView']->assign("lotterylist", $aLotteryList);
        $GLOBALS['oView']->assign("lotteryid", $iLotteryId);
        $GLOBALS['oView']->assign("iLvtopLottery", $iLvtopLottery);
        $GLOBALS['oView']->assign("amethod", $aMethod);
        $oUser = new model_user();
        $aPrizeType = $oUser->getPrizeType(TRUE);
        $GLOBALS['oView']->assign("aPrizeType", $aPrizeType);
        $GLOBALS['oView']->assign("prizegroup", $aPrizeGroup);
        $GLOBALS['oView']->display("pgame_prizelevel.html");
    }
    /**
     * desc编辑奖金组
     * @author rhovin 2017-07-19
     */
    public function actionEditPrize() {
        if($this->getIsPost()) {
            if(isset($_POST['userpoint']) && is_array($_POST['userpoint'])){
                $iPgId = intval($_POST['pgid']);
                $oPrizeLevel = A::singleton("model_proxyprizelevel");
                $mResult = $oPrizeLevel->updateUserpoint($_POST, $iPgId , $this->lvtopid);
                if($mResult) {
                    $this->ajaxMsg(1,"更新成功");
                } else {
                    $this->ajaxMsg(0,$oPrizeLevel->_errMsg);
                }
            } else {
                $this->ajaxMsg(0,"请勾选需要更新的数据");
            }
        }

    }
    /**
     * @desc 统一构造where条件
     * @author pierce
     * @date 2017-06-16
     */
    private function _getWhereStr($aGetData)
    {
        $sWhere = "";
        if (!empty($aGetData["tStartTime"]) && !empty($aGetData["tEndTime"])) {
            $sWhere .= " AND T.`begintime` between '" . $aGetData["tStartTime"] . "' and '" . $aGetData["tEndTime"] . "'";
        } elseif (!empty($aGetData["tStartTime"])) {
            $sWhere .= " AND T.`begintime`>'" . $aGetData["tStartTime"] . "'";
        } elseif (!empty($aGetData["tEndTime"])) {
            $sWhere .= " AND T.`begintime`<'" . $aGetData["tEndTime"] . "'";
        }
        if ($aGetData["lotteryid"] > 0) {
            $sWhere .=" AND T.`lotteryid`='" . $aGetData["lotteryid"] . "'";
            $aGetData["methodid"] = isset($aGetData["methodid"]) && is_numeric($aGetData["methodid"]) ? intval($aGetData["methodid"]) : 0;
            if ($aGetData["methodid"] > 0) {
                $sWhere .=" AND T.`methodid`='" . $aGetData["methodid"] . "'";
            }
        }
        if ($aGetData['searchType'] == "issueid"){
            if ($aGetData["issueid"] != "0") {
                $sWhere .=" AND T.`beginissue`='" . $aGetData["issueid"] . "'";
            }
        }else if($aGetData['searchType'] == "username"){
            if (isset($aGetData["username"]) && !empty($aGetData["username"])) { // 对用户 中 “*”的支持
                $sWhere .=" AND UT.`username` = '" .  $aGetData["username"] ."'";
            }
        }else if ($aGetData['searchType'] == "periods"){
            if (isset($aGetData["periods"]) && !empty($aGetData["periods"])) {
                $sWhere .=" AND T.`taskid`='" . myHash($aGetData["periods"], "DECODE", 'T') . "'";
            }
        }
        if ($aGetData["taskstatus"] > -1) {
            $sWhere .= " AND T.`status`='" . $aGetData["taskstatus"] . "'";
        }

        //注单

        return $sWhere;
    }

    /**
     * 修改商户彩种排序
     *
     * @author left
     * @date 2017/10/05
     */
    public function actionChangeSorts(){
        $aGetData = $this->get(array(
            "id" => parent::VAR_TYPE_INT,
            "sorts" => parent::VAR_TYPE_INT,
        ));
        $oPlottery = new model_plottery();
        $lvtopid = $oPlottery->getUserlvtopid($aGetData['id']);
        if($lvtopid['lvtopid'] != $this->lvtopid) $this->ajaxMsg(0,"非法操作");
        $aData['sorts'] = $aGetData['sorts'];
        $bResult = $oPlottery->locklottery($aGetData['id'],$aData,$this->lvtopid);
        if ($bResult) {
            sysMessage("操作成功");
        }else{
            sysMessage("操作失败", 1);
        }
    }
    
//    /**
//     * 不开奖号码不显示
//     * @param $arr
//     * @return mixed
//     */
//    private function _setJustOpen($arr)
//    {
//        foreach ($arr as $key => $value)
//        {
//            if (empty($value['code'])) {
//                unset($arr[$key]);
//            }
//        }
//        return $arr;
//    }
}