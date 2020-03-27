<?php
/**
 * Created by PhpStorm.
 * User: robert
 * Date: 2018/5/25
 * Time: 13:28
 */

class controller_userbetting extends pcontroller
{

    //构造函数
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 平台报表
     * @author robert
     */
    function actionProfit()
    {
        if ($this->getIsAjax()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "userid" => parent::VAR_TYPE_INT,
                "modeType" => parent::VAR_TYPE_INT,
                "vendorId" => parent::VAR_TYPE_INT,
                "username" => parent::VAR_TYPE_STR,
                "gameType" => parent::VAR_TYPE_INT,
                "sTime" => parent::VAR_TYPE_DATETIME,
                "eTime" => parent::VAR_TYPE_DATETIME,
            ));
            if (isset($_GET['username']) && $_GET['username'] != "") {
                $aGetData['username'] = $_GET['username'];
                $aGetData['page'] = $_GET['page'];
                $aGetData['rows'] = $_GET['rows'];
            }

            //相差不能大于3个月
//            if (strtotime($aGetData['eTime']) > strtotime("+3 month", strtotime($aGetData['sTime']))) {
//                $this->ajaxMsg(0, '日期不能大于3个月');
//            }

            //供应商
            if (!isset($aGetData['vendorId']) || empty($aGetData['vendorId'])) {
                $this->ajaxMsg(0, '非法请求');
            }


            //展现表方式
            if ($aGetData['modeType'] == 1) {
                //日表模式
                $sGroupBy = "day"; //日表
            } elseif ($aGetData['modeType'] == 2) {
                //月表模式
                $sGroupBy = "DATE_FORMAT(day,'%Y-%m')"; //月表
            } else {
                //总表模式
                $sGroupBy = "userid"; //总表
            }

            //查询条件
            $sWhere = $this->ProfitWhere($aGetData);
            $oUser = new model_puser();
            //根据用户名搜索
            if (isset($aGetData["username"]) && $aGetData["username"] != "") {
                $aUserInfo = $oUser->getUser($aGetData["username"], $this->lvtopid);
                if (empty($aUserInfo)) {
                    $this->outPutJQgruidJson([], 0, $aGetData['page'], $aGetData['rows']);
                } else {
                    $iUserId = $aUserInfo['userid'];
                }
            } else {
                $iUserId = $this->lvtopid;
            }
            $oUserbettingreport = new model_userbettingreport;
            $aResult = $oUserbettingreport->BettingReportList($sWhere,$iUserId, "day", "DESC", $sGroupBy);
            foreach ($aResult as $k => &$v) {
                $v['winning'] = '-';   //中奖额三方未提供
                $v['profitability'] = intval(($v['bonus'] / $v['bets']) * 100) . "%";  //盈利率
                $v['active'] = $v['count'];  //活跃数
                //时间格式化月
                if ($aGetData['modeType'] == 2) {
                    $v['day'] = date("Y-m", strtotime($v['day']));
                }
            }

            $this->outPutJQgruidJson($aResult,count($aResult));

        } else {

            if (isset($_GET['username']) && $_GET['username'] != "") {
                $GLOBALS['oView']->assign('username', $_GET['username']);
            }
            //获取开元棋牌信息
            $ovendor_pvendors = new model_vendor_pvendors;
            $aVendors=$ovendor_pvendors->getVendor('name','开元棋牌');

            if ($_GET['modeType'] == 1){
                //日表模式
                $sStartDate = date('Y-m')."-01";
            }elseif ($_GET['modeType'] == 2){
                //月表模式
                $sStartDate = date('Y')."-01-01";
            }else{
                $sStartDate = date('Y-m-d ');
            }

            $sEndDate =  date('Y-m-d ', strtotime('+1 days'));
            $GLOBALS['oView']->assign('startDate', $sStartDate);
            $GLOBALS['oView']->assign('endDate',$sEndDate);
            $GLOBALS['oView']->assign("ur_here", "平台报表");
            $GLOBALS['oView']->assign("vendor",$aVendors);            //获取开元棋牌信息
            if (isset($_GET['modeType'])) {
                $GLOBALS['oView']->assign('modeType', $_GET['modeType']); //1日表，2月表
                $GLOBALS['oView']->display('userbtting_profit_day.html');
            } else {
                $GLOBALS['oView']->display('userbtting_profit.html');
            }

        }
    }

    /**
     * 平台报表统计
     * @author robert
     */
    function actionProfitTotal()
    {
        $aGetData = $this->post(array(
            "page" => parent::VAR_TYPE_INT,
            "rows" => parent::VAR_TYPE_INT,
            "userid" => parent::VAR_TYPE_INT,
            "modeType" => parent::VAR_TYPE_INT,
            "vendorId" => parent::VAR_TYPE_INT,
            "username" => parent::VAR_TYPE_STR,
            "gameType" => parent::VAR_TYPE_INT,
            "sTime" => parent::VAR_TYPE_DATETIME,
            "eTime" => parent::VAR_TYPE_DATETIME,
        ));
        //相差不能大于3个月
//        if (strtotime($aGetData['eTime']) > strtotime("+3 month", strtotime($aGetData['sTime']))) {
//            $this->ajaxMsg(0, '日期不能大于3个月');
//        }
        //供应商
        if (!isset($aGetData['vendorId']) || empty($aGetData['vendorId'])) {
            $this->ajaxMsg(0, '非法请求');
        }
        $sWhere = $this->ProfitWhere($aGetData);
        $oUser = new model_puser();
        //根据用户名搜索
        if (isset($aGetData["username"]) && $aGetData["username"] != "") {
            $aUserInfo = $oUser->getUser($aGetData["username"], $this->lvtopid);
            if (empty($aUserInfo)) {
                $this->outPutJQgruidJson([], 0, $aGetData['page'], $aGetData['rows']);
            } else {
                $iUserId = $aUserInfo['userid'];
            }
        } else {
            $iUserId = $this->lvtopid;
        }
        $oUserbettingreport = new model_userbettingreport;
        $aResult = $oUserbettingreport->BettingReporStatistics($sWhere,$iUserId);
        $aResult['sTime'] = date('Y-m-d', strtotime($aGetData['sTime']));
        $aResult['eTime'] = date('Y-m-d', strtotime($aGetData['eTime']));
        $this->ajaxMsg(0, '请求成功', $aResult);
    }



    /**
     * 拼接平台报表查询条件
     * @author robert
     */
    function ProfitWhere($aData)
    {

        //拼接查询条件
        $sWhere = '1 ';
        //拼接sql语句，注单时间不能大于开始时间
        $sWhere .= " AND `day` >= '" . $aData['sTime'] . "'";

        //拼接sql语句，注单时间不能小于开始时间
        $sWhere .= " AND `day` < '" . $aData['eTime'] . "'";

        //供应商
        $sWhere .= " AND `vendor_id` = '" . $aData['vendorId'] . "'";

        //游戏类型
        if (isset($aData['gameType']) && !empty($aData['gameType'])) {
            $sWhere .= " AND `game_type` = " . $aData['gameType'];
        }

        return $sWhere;
    }


    /**
     * 注单记录列表
     * @author robert
     */
    function actionBettingRecord()
    {
        if ($this->getIsAjax()) {
            $aGetData = $this->post(array(
                "username" => parent::VAR_TYPE_STR,//用户名
                "gameName" => parent::VAR_TYPE_STR, //游戏名称
                "gamePeriod" => parent::VAR_TYPE_STR,//注单号
                "gameType" => parent::VAR_TYPE_INT,//游戏类型（1:彩票 2:真人娱乐 3:电子游戏 4:体育 5:棋牌）
                "vendorId" => parent::VAR_TYPE_INT, //游戏厂商id
                "startStime" => parent::VAR_TYPE_DATETIME,//投注开始时间
                "startEtime" => parent::VAR_TYPE_DATETIME,//投注结束时间
                "endStime" => parent::VAR_TYPE_DATETIME,//开奖时间
                "endEtime" => parent::VAR_TYPE_DATETIME,//开奖结束时间
                "bettingSmall" => parent::VAR_TYPE_FLOAT,//投注额小
                "bettingBig" => parent::VAR_TYPE_FLOAT,//投注额大
//                "WinningSmall" => parent::VAR_TYPE_FLOAT,//中奖额小
//                "WinningBig" => parent::VAR_TYPE_FLOAT,//中奖额大
                "ProfitSmall" => parent::VAR_TYPE_FLOAT,//盈亏额小
                "ProfitBig" => parent::VAR_TYPE_FLOAT,//盈亏额大
                "terminal" => parent::VAR_TYPE_INT,//来源终端来源终端（0全部，1pc，2手机端）',
                "result" => parent::VAR_TYPE_INT, //中奖结果（0全部，1赢，2亏，3和）',
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
            ));

            //如果用户名跟订单号为空的话默认查询一当前一个小时的数据
            if (empty($aGetData['username']) && empty($aGetData['gamePeriod']) && empty($aGetData['endStime']) && empty($aGetData['endEtime'])) {
                //如果所有数据都为空
                $aGetData['endStime'] = date("Y-m-d H:i:s", strtotime("-1 hour"));
                $aGetData['endEtime'] = date("Y-m-d H:i:s", time());
            } elseif (!empty($aGetData['endStime']) && !empty($aGetData['endEtime']) && empty($aGetData['username']) && empty($aGetData['gamePeriod'])) {
                //判断时间间隔不能大于一个小时
                if ((strtotime($aGetData['endEtime']) - strtotime($aGetData['endStime']) > 3600)) {
                    $this->ajaxMsg(0, '查询区间不能超过一个小时');
                }
            }
            //用户名或者主单号不能为空
            if (empty($aGetData['username']) && empty($aGetData['gamePeriod']) && (empty($aGetData['endStime']) || empty($aGetData['endEtime']))) {
                $this->ajaxMsg(0, '用户名或注单号不能为空');
            }
            //游戏厂商不能为空
            if (isset($aGetData['vendord']) && $aGetData['vendord'] < 0) {
                $this->ajaxMsg(0, '非法请求');
            }
            $sWhere = $this->BettingRecordSqlWhere($aGetData);
            $oProjectGame = new model_projectgame();
            $aResult = $oProjectGame->getList($sWhere, $aGetData['page'], $aGetData['rows']);
            $this->outPutJQgruidJson($aResult['results'], $aResult['affects'], $aGetData['page'], $aGetData['rows']);
        } else {
            //如果开始时间跟结束时间没选
            if (empty($_GET['endStime']) && empty($_GET['endEtime'])){
                $_GET['endStime'] = date('Y-m-d H:i:s',strtotime('-1 hour'));
                $_GET['endEtime'] = date('Y-m-d H:i:s');
            }
            $GLOBALS['oView']->display('userbtting_bettingrecord.html');
        }
    }


    /**
     * 注单记录列表拼接sql
     * @author robert
     */
    function BettingRecordSqlWhere($aGetData)
    {
        $sWhere = " ut.lvtopid = ".$_SESSION['lvtopid'];
        //用户名查询
        if (!empty($aGetData['username'])) {
            $oUser = new model_puser();
            //根据用户名搜索
            $aUserInfo = $oUser->getUser($aGetData["username"], $this->lvtopid);
            if (empty($aUserInfo)) {
                $this->outPutJQgruidJson([], 0, $aGetData['page'], $aGetData['rows']);
            } else {
                $iUserId = $aUserInfo['userid'];
            }
            $sWhere .= " AND p.userid = '" . $iUserId . "'";
        }
        //供应商
        $sWhere .= " AND p.`vendor_id` = '" . $aGetData['vendorId'] . "'";

        //注单号查询
        if (!empty($aGetData['gamePeriod'])) {
            $sWhere .= " AND p.game_period = '" . $aGetData['gamePeriod'] . "'";
        }

        //游戏类型查询
        if (!empty($aGetData['gameType'])) {
            $sWhere .= " AND p.game_type = '" . $aGetData['gameType'] . "'";
        }

        //注单查询投注开始时间
        if (!empty($aGetData['startStime'])) {
            $sWhere .= " AND p.start_time >= '" . $aGetData['startStime'] . "'";
        }

        //注单查询投注结束时间
        if (!empty($aGetData['startEtime'])) {
            $sWhere .= " AND p.start_time < '" . $aGetData['startEtime'] . "'";
        }

        //注单查询开奖开始时间
        if (!empty($aGetData['endStime'])) {
            $sWhere .= " AND p.end_time >= '" . $aGetData['endStime'] . "'";
        }

        //注单查询开奖结束时间
        if (!empty($aGetData['endEtime'])) {
            $sWhere .= " AND p.end_time < '" . $aGetData['endEtime'] . "'";
        }

        //注单查询 投注金额开始金额
        if (!empty($aGetData['bettingBig'])) {
            $sWhere .= " AND p.bets <= '" . $aGetData['bettingBig'] . "'";
        }

        //注单查询 投注金额结束金额
        if (!empty($aGetData['bettingSmall'])) {
            $sWhere .= " AND p.bets >= '" . $aGetData['bettingSmall'] . "'";
        }

        //注单查询 中奖开始金额
        if (!empty($aGetData['WinningSmall'])) {
            $sWhere .= " AND p.winning_amount >= '" . $aGetData['bettingSmall'] . "'";
        }

        //注单查询 中奖结束金额
        if (!empty($aGetData['WinningBig'])) {
            $sWhere .= " AND p.winning_amount <= '" . $aGetData['WinningBig'] . "'";
        }

        //注单查询 盈亏开始金额
        if (!empty($aGetData['ProfitSmall'])) {
            $sWhere .= " AND p.profit >= '" . $aGetData['ProfitSmall'] . "'";
        }

        //注单查询 盈亏结束金额
        if (!empty($aGetData['ProfitBig'])) {
            $sWhere .= " AND p.profit <= '" . $aGetData['ProfitBig'] . "'";
        }

        //注单查询 来源终端
        if (!empty($aGetData['terminal'])) {
            $sWhere .= " AND p.terminal = '" . $aGetData['terminal'] . "'";
        }

        //注单查询 结果
        if (!empty($aGetData['result']) && $aGetData['result'] == "1") {
            //查询赢的结果
            $sWhere .= " AND p.profit > '0'";
        } elseif (!empty($aGetData['result']) && $aGetData['result'] == "2") {
            //查询亏
            $sWhere .= " AND p.profit < '0'";

        } elseif (!empty($aGetData['result']) && $aGetData['result'] == "3") {
            //查询和
            $sWhere .= " AND p.draw  = '1'";
        }

        //游戏名称查询
        if (!empty($aGetData['gameName'])) {
            $sWhere .= " AND g.vendor_id = '" . $aGetData['vendorId'] . "' and g.name='" . $aGetData['gameName'] . "'";
        }

        return $sWhere;
    }


    /**
     * 注单记录统计
     * @author robert
     */
    function actionBettingRecordTotal()
    {
        $aGetData = $this->post(array(
            "username" => parent::VAR_TYPE_STR,//用户名
            "gameName" => parent::VAR_TYPE_STR, //游戏名称
            "gamePeriod" => parent::VAR_TYPE_STR,//注单号
            "gameType" => parent::VAR_TYPE_INT,//游戏类型（1:彩票 2:真人娱乐 3:电子游戏 4:体育 5:棋牌）
            "vendorId" => parent::VAR_TYPE_INT, //游戏厂商id
            "startStime" => parent::VAR_TYPE_DATETIME,//投注开始时间
            "startEtime" => parent::VAR_TYPE_DATETIME,//投注结束时间
            "endStime" => parent::VAR_TYPE_DATETIME,//开奖时间
            "endEtime" => parent::VAR_TYPE_DATETIME,//开奖结束时间
            "bettingSmall" => parent::VAR_TYPE_FLOAT,//投注额小
            "bettingBig" => parent::VAR_TYPE_FLOAT,//投注额大
//            "WinningSmall" => parent::VAR_TYPE_FLOAT,//中奖额小
//            "WinningBig" => parent::VAR_TYPE_FLOAT,//中奖额大
            "ProfitSmall" => parent::VAR_TYPE_FLOAT,//盈亏额小
            "ProfitBig" => parent::VAR_TYPE_FLOAT,//盈亏额大
            "terminal" => parent::VAR_TYPE_INT,//来源终端来源终端（0全部，1pc，2手机端）',
            "result" => parent::VAR_TYPE_INT, //中奖结果（0全部，1赢，2亏，3和）',
            "mode" => parent::VAR_TYPE_INT, //中奖结果（0全部，1赢，2亏，3和）',
            "page" => parent::VAR_TYPE_INT,
            "rows" => parent::VAR_TYPE_INT,
        ));

        //如果用户名跟订单号为空的话默认查询一当前一个小时的数据
        if (empty($aGetData['username']) && empty($aGetData['gamePeriod']) && empty($aGetData['endStime']) && empty($aGetData['endEtime'])) {
            //如果所有数据都为空
            $aGetData['endStime'] = date("Y-m-d H:i:s", strtotime("-1 hour"));
            $aGetData['endEtime'] = date("Y-m-d H:i:s", time());
        } elseif (!empty($aGetData['endStime']) && !empty($aGetData['endEtime']) && empty($aGetData['username']) && empty($aGetData['gamePeriod'])) {
            //判断时间间隔不能大于一个小时
            if ((strtotime($aGetData['endEtime']) - strtotime($aGetData['endStime']) > 3600)) {
                $this->ajaxMsg(0, '查询区间不能超过一个小时');
            }
        }

        //用户名或者主单号不能为空
        if (empty($aGetData['username']) && empty($aGetData['gamePeriod']) && (empty($aGetData['endStime']) || empty($aGetData['endEtime']))) {
            $this->ajaxMsg(0, '用户名或注单号不能为空');
        }
        //供应商
        if (isset($aGetData['vendord']) && $aGetData['vendord'] < 0) {
            $this->ajaxMsg(0, '非法请求');
        }
        $sWhere = $this->BettingRecordSqlWhere($aGetData);
        $oProjectGame = new model_projectgame;
        $aResult = $oProjectGame->BettingRecordStatistics($sWhere);

        //开奖时间
        $aResult['sTime'] = $aGetData['endStime'];
        $aResult['eTime'] = $aGetData['endEtime'];
        $this->ajaxMsg(0, '请求成功', $aResult);
    }


    /**
     * 平台报表导出
     */
    function actionExportReport(){

        $_GET = json_decode($_GET['getData'],TRUE);
        $aGetData = $this->get(array(
            "page" => parent::VAR_TYPE_INT,
            "rows" => parent::VAR_TYPE_INT,
            "userid" => parent::VAR_TYPE_INT,
            "modeType" => parent::VAR_TYPE_INT,
            "vendorId" => parent::VAR_TYPE_INT,
            "username" => parent::VAR_TYPE_STR,
            "gameType" => parent::VAR_TYPE_INT,
            "sTime" => parent::VAR_TYPE_DATETIME,
            "eTime" => parent::VAR_TYPE_DATETIME,
        ));


        $sWhere = $this->ProfitWhere($aGetData);
        $oUser = new model_puser();
        //根据用户名搜索
        if (isset($aGetData["username"]) && $aGetData["username"] != "") {
            $aUserInfo = $oUser->getUser($aGetData["username"], $this->lvtopid);
            if (empty($aUserInfo)) {
                $this->outPutJQgruidJson([], 0, $aGetData['page'], $aGetData['rows']);
            } else {
                $iUserId = $aUserInfo['userid'];
            }
        } else {
            $iUserId = $this->lvtopid;
        }
        $oUserbettingreport = new model_userbettingreport;
        $aResult = $oUserbettingreport->BettingReportList($sWhere,$iUserId, "day", "DESC", 'userid');
        foreach ($aResult as $k => &$v) {
            $v['winning'] = '-';   //中奖额三方未提供
            $v['profitability'] = intval(($v['bonus'] / $v['bets']) * 100) . "%";  //盈利率
            $v['active'] = $v['count'];  //活跃数
            //时间格式化月
            if ($aGetData['modeType'] == 2) {
                $v['day'] = date("Y-m", strtotime($v['day']));
            }
        }

        $sTitle = "查询时间范围：". $aGetData['sTime'] ."到". $aGetData['eTime'] ."  查询游戏类型：". $this->getGameTypeName($aGetData['gameType']) ."  查询账号：".$aGetData['username'];
        $expTitle  = "Userbetting_Profit";
        $expCellName  = [
            ['username','用户名'],
            ['groupname','用户组'],
            ['bets','投注额'],
            ['points','返点额'],
            ['realbets','有效投注'],
            ['count_bonuss','中奖额'],
            ['bonus','盈亏'],
            ['profitability','盈利率'],
            ['bettimes','注单量'],
            ['active','活跃数']
        ];
        $aTableData = [];
        foreach ($aResult as $key => $value) {
            $aTableData[$key]['username'] = $value['username'];
            $aTableData[$key]['groupname'] = $value['groupname'];
            $aTableData[$key]['bets'] = $value['bets'];
            $aTableData[$key]['points'] = $value['points'];
            $aTableData[$key]['realbets'] = $value['realbets'];
            $aTableData[$key]['count_bonuss'] = "-";
            $aTableData[$key]['bonus'] = $value['bonus'];
            $aTableData[$key]['profitability'] = $value['profitability'];
            $aTableData[$key]['bettimes'] = $value['bettimes'];
            $aTableData[$key]['active'] = $value['active'];
        }
        ExportExcel($expTitle,$expCellName,$aTableData,$sTitle);
    }

    //获取游戏类型type
    private function getGameTypeName($iType){
        $sTypeName = "";
        switch ($iType){
            case 0:
                $sTypeName = "全部";
                break;
            case 1:
                $sTypeName = "彩票";
                break;
            case 2:
                $sTypeName ="真人娱乐";
                break;
            case 3:
                $sTypeName = "电子游戏";
                break;
            case 4:
                $sTypeName = "体育";
                break;
            case 5:
                $sTypeName = "棋牌";
                break;
        }
        return $sTypeName;
    }




}