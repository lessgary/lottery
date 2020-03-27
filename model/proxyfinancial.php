<?php
/*
 * 账务报表数据管理
 * @author     ken
 * @version    1.0.0
 * @package    passport
 * *
 */
/* * ***************************[ 宏定义账变ID对应类型关系 ]********************* */
define("ORDER_TYPE_CDSXF", 14);   // 撤单手续费      pid=0   - 游戏币
define("ORDER_TYPE_TSJEQL", 19);  // 特殊金额清理   pid=0   - 游戏币
define("ORDER_TYPE_TSJEZL", 20);  // 特殊金额整理   pid=0   + 游戏币
define("ORDER_TYPE_LPCZ", 21);   // 理赔充值        pid=0   + 游戏币
define("ORDER_TYPE_XTKJ", 22);   // 系统扣减        pid=0   - 游戏币
define("ORDER_TYPE_HDHB", 32);   // 活动礼金        pid=0   + 游戏币

class model_proxyfinancial extends basemodel {
    
    // 构造函数
    function __construct($aDBO = array()) {
        parent::__construct($aDBO);
    }
    
    /**
     * 生成账务报表
     * @param string $sDate 报表日期
     * @return mixed
     */
    public function createReport($sDate = "") {
        //检查当天快照数据是否已生成
        $sNowDate = date('Ymd', strtotime($sDate));
        $aData = $this->oDB->getOne("SELECT COUNT(DISTINCT(`userid`)) AS TOMCOUNT FROM `snapshot` WHERE `days`='$sNowDate' LIMIT 1");
        if ($aData['TOMCOUNT'] == 0) { // 快照数据没有生成，不能继续
            return 'snapshot not done!';
        }
        unset($aData);
        //step 01 检测参数是否正确
        if ($sDate == "") {
            return "The date is not right,please input report date";
        }
        $sTableName = "financial";
        //检测对应时间的数据是否存在
        $sSql = "SELECT `id` FROM `" . $sTableName . "` WHERE `day`='" . $sDate . "' LIMIT 1";
        $aCheck = $this->oDB->getOne($sSql);
        if (!empty($aCheck)) {//不允许重复执行
            return TRUE;
        }
        $aResult = array(); //报表结果集
        $aResult['day'] = $sDate;
        //step 02 获取指定日期内的总销量、平台盈亏
        $aResult += $this->getTotalBets($sDate);
        //step 03 获取指定日期内的特殊金额信息,其中包括：理赔、礼金、特殊金额整理、特殊金额清理、系统扣减、撤单手续费
        $aResult += $this->getSpecicalOrder($sDate);
        //step 04 获取充值信息,充值金额、充值手续费、充值笔数
        $aResult += $this->getRecharge($sDate);
        //step 05 获取提现信息，提现金额、提现手续费、提现笔数
        $aResult += $this->getWithdraw($sDate);
        //step 06 获取用户信息，新注册用户，新充值用户
        $aResult += $this->getUserinfo($sDate);
        //step 07 获取用户信息，新注册用户，新充值用户
        $aResult += $this->getSystemBalance($sDate);
        //setp 08 平台实际利润:前日平台余额+当日充值金额-当日提现金额-当日提现手续费-当日平台余额
        $aResult['realprofit'] = $aResult['lastcashbalance'] + $aResult['recharge'] - $aResult['withdraw'] - $aResult['withdrawfee'] - $aResult['cashbalance'];
        //step09 检测当天是否有分红操作:如果有归类到上一个月的报表中，不影响当天的利润处理
        $aResult['realprofit'] += $this->getCurShare($sDate);
        if (empty($aCheck)) {
            $this->oDB->insert($sTableName, $aResult);
        } else {
            $this->oDB->update($sTableName, $aResult, "`id`='" . $aCheck['id'] . "'");
        }
        if ($this->oDB->errno > 0) {
            return $this->oDB->error;
        }
        return TRUE;
    }
    
    /**
     * 检测当天是否有总代的分红记录
     * @param type $sDate
     */
    private function getCurShare($sDate = "") {
        $fShare = 0;
        if ($sDate == "") {
            return $fShare;
        }
        $sReportTime = getConfigValue("tradeset_reporttime", "03:00:00");
        $sCodition = "(`times` BETWEEN '" . $sDate . " " . $sReportTime . "' AND '" . date("Y-m-d " . $sReportTime, strtotime($sDate) + 86400) . "') AND `istester`=0 ";
        //01.获取分红金额
        $sSql = "SELECT SUM(`amount`) AS `share` FROM `claims` WHERE " . $sCodition . " AND `type`=2 LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return $fShare;
        }
        $fShare = $aData['share'] == NULL ? 0.0000 : $aData['share'];
        return $fShare;
    }
    
    /**
     * 获取指定日期内总销量
     * @param string $sDate 报表日期
     */
    private function getTotalBets($sDate = "") {
        $aResult = array();
        if ($sDate == "") {
            return $aResult;
        }
        $sSql = "SELECT SUM(`realbets`) AS `bets`,SUM(`realbets`-`points`-`bonus`) AS `profit` FROM `user_report` AS ur LEFT JOIN `usertree` AS ut ON(`ur`.`userid`=ut.`userid`) WHERE ur.`day`='" . $sDate . "' AND `ut`.`istester`= 0 LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['bets'] = $aData['bets'] == NULL ? 0.0000 : $aData['bets'];
        $aResult['profit'] = $aData['profit'] == NULL ? 0.0000 : $aData['profit'];
        return $aResult;
    }
    
    /**
     * 获取特殊金额信息
     * 其中包括：理赔、礼金、特殊金额整理、特殊金额清理、系统扣减、撤单手续费
     * @param string $sDate 报表日期
     */
    private function getSpecicalOrder($sDate = "") {
        $aResult = array();
        if ($sDate == "") {
            return $aResult;
        }
        $sReportTime = getConfigValue("tradeset_reporttime", "03:00:00");
        $sCodition = "(`times` BETWEEN '" . $sDate . " " . $sReportTime . "' AND '" . date("Y-m-d " . $sReportTime, strtotime($sDate) + 86400) . "') AND `ut`.`istester`=0 ";
        //01.获取理赔金额
        $sSql = "SELECT SUM(`amount`) AS `claims` FROM `history_orders` AS o LEFT JOIN `usertree` AS ut ON(`o`.`fromuserid`=ut.`userid`) WHERE " . $sCodition . " AND `ordertypeid`='" . ORDER_TYPE_LPCZ . "' LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['claims'] = $aData['claims'] == NULL ? 0.0000 : $aData['claims'];
        //02.获取系统扣减金额
        $sSql = "SELECT SUM(`amount`) AS `systemreduce` FROM `history_orders` AS o LEFT JOIN `usertree` AS ut ON(`o`.`fromuserid`=ut.`userid`) WHERE " . $sCodition . " AND `ordertypeid`='" . ORDER_TYPE_XTKJ . "' LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['systemreduce'] = $aData['systemreduce'] == NULL ? 0.0000 : $aData['systemreduce'];
        //03.获取特殊金额清理金额
        $sSql = "SELECT SUM(`amount`) AS `specialclear` FROM `history_orders` AS o LEFT JOIN `usertree` AS ut ON(`o`.`fromuserid`=ut.`userid`) WHERE " . $sCodition . " AND `ordertypeid`='" . ORDER_TYPE_TSJEQL . "' LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['specialclear'] = $aData['specialclear'] == NULL ? 0.0000 : $aData['specialclear'];
        //04.获取特殊金额整理金额
        $sSql = "SELECT SUM(`amount`) AS `specialclaims` FROM `history_orders` AS o LEFT JOIN `usertree` AS ut ON(`o`.`fromuserid`=ut.`userid`) WHERE " . $sCodition . " AND `ordertypeid`='" . ORDER_TYPE_TSJEZL . "' LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['specialclaims'] = $aData['specialclaims'] == NULL ? 0.0000 : $aData['specialclaims'];
        //05.获取礼金金额
        $sSql = "SELECT SUM(`amount`) AS `gift` FROM `history_orders` AS o LEFT JOIN `usertree` AS ut ON(`o`.`fromuserid`=ut.`userid`) WHERE " . $sCodition . " AND `ordertypeid`='" . ORDER_TYPE_HDHB . "' LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['gift'] = $aData['gift'] == NULL ? 0 : $aData['gift'];
        //06.获取撤单手续费
        $sSql = "SELECT SUM(`amount`) AS `cancelfee` FROM `history_orders` AS o LEFT JOIN `usertree` AS ut ON(`o`.`fromuserid`=ut.`userid`) WHERE " . $sCodition . " AND `ordertypeid`='" . ORDER_TYPE_CDSXF . "' LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['cancelfee'] = $aData['cancelfee'] == NULL ? 0 : $aData['cancelfee'];
        return $aResult;
    }
    
    /**
     * 获取充值信息
     * 其中包括：充值金额、充值人数、充值手续费(特指平台赔付给用户的金额)
     * @param string $sDate 报表日期
     */
    private function getRecharge($sDate) {
        $aResult = array();
        if ($sDate == "") {
            return $aResult;
        }
        $sReportTime = getConfigValue("tradeset_reporttime", "03:00:00");
        //查询充值成功的信息
        $sCodition = "(`done_time` BETWEEN '" . $sDate . " " . $sReportTime . "' AND '" . date("Y-m-d " . $sReportTime, strtotime($sDate) + 86400) . "')";
        $sSql = "SELECT SUM(`payer_amount`) AS `recharge`,COUNT(distinct `payer_userid`) AS `rechargeuser`,SUM(`payer_realamount`-`payer_amount`) AS  `rechargefee` FROM `payrecords` WHERE " . $sCodition . " AND `isdone`=1 LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['recharge'] = $aData['recharge'] == NULL ? 0.0000 : $aData['recharge'];
        $aResult['rechargefee'] = $aData['rechargefee'] == NULL ? 0.0000 : $aData['rechargefee'];
        $aResult['rechargeuser'] = $aData['rechargeuser'];
        //加入三方充值数据
        $sCodition = "(`finished_time` BETWEEN '" . $sDate . " " . $sReportTime . "' AND '" . date("Y-m-d " . $sReportTime, strtotime($sDate) + 86400) . "')";
        $sSql = "SELECT SUM(`get_money`) AS `recharge`,COUNT(distinct `user_id`) AS `rechargeuser`,SUM(`return_shop_fee`) AS  `rechargefee` FROM `fastpay_request` WHERE `trade_status`=1 AND " . $sCodition . " LIMIT 1";
        $aYeeData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['recharge'] += $aYeeData['recharge'] == NULL ? 0.0000 : $aYeeData['recharge'];
        $aResult['rechargefee'] += $aYeeData['rechargefee'] == NULL ? 0.0000 : $aYeeData['rechargefee'];
        $aResult['rechargeuser'] += $aYeeData['rechargeuser'];
        return $aResult;
    }
    
    /**
     * 获取提现信息
     * 其中包括：提现金额、提现手续费(公司付出的实际金额)
     * @param string $sDate 报表日期
     */
    private function getWithdraw($sDate) {
        $aResult = array();
        if ($sDate == "") {
            return $aResult;
        }
        $sReportTime = getConfigValue("tradeset_withdrawtime", "05:00:00");
        $sCodition = "(`finishtime` BETWEEN '" . $sDate . " " . $sReportTime . "' AND '" . date("Y-m-d " . $sReportTime, strtotime($sDate) + 86400) . "')";
        $sSql = "SELECT SUM(`amount`) AS `withdraw`,SUM(`banktransferfee`-`transferfee`) AS  `withdrawfee` FROM `withdrawel` WHERE " . $sCodition . " AND `status`=2 LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['withdraw'] = $aData['withdraw'] == NULL ? 0.0000 : $aData['withdraw'];
        $aResult['withdrawfee'] = $aData['withdrawfee'] == NULL ? 0.0000 : $aData['withdrawfee'];
        return $aResult;
    }
    
    /**
     * 获取新增用户信息
     * 其中包括：新增充值、新注册的用户
     * @param string $sDate 报表日期
     */
    private function getUserinfo($sDate) {
        $aResult = array();
        if ($sDate == "") {
            return $aResult;
        }
        $sReportTime = getConfigValue("tradeset_reporttime", "03:00:00");
        //新注册用户
        $sCodition = "(`registertime` BETWEEN '" . $sDate . " " . $sReportTime . "' AND '" . date("Y-m-d " . $sReportTime, strtotime($sDate) + 86400) . "') AND `ut`.`istester`=0 ";
        $sSql = "SELECT COUNT(ut.`userid`) AS `newuser` FROM `users` AS u LEFT JOIN `usertree` AS ut ON(`u`.`userid`=ut.`userid`)  WHERE " . $sCodition . " LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['newuser'] = $aData['newuser'];
        //新充值用户
        $sSql = "SELECT `payer_userid`,min(`done_time`) AS `maxtime` FROM `payrecords` WHERE `isdone`=1 AND `done_time` >= '" . date("Y-m-d 03:00:00", time() - 86400 * 15) . "' GROUP BY `payer_userid` HAVING `maxtime` >= '" . $sDate . " " . $sReportTime . "' AND `maxtime` <= '" . date("Y-m-d " . $sReportTime, strtotime($sDate) + 86400) . "'";
        $aData = $this->oDB->getAll($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        if (empty($aData)) {
            $aResult['newrecharge'] = 0;
        } else {
            $aResult['newrecharge'] = count($aData);
        }
        return $aResult;
    }
    
    /**
     * 获取系统余额
     * @param string $sDate 报表日期
     */
    private function getSystemBalance($sDate) {
        $aResult = array();
        if ($sDate == "") {
            return $aResult;
        }
        //当日现金余额
        $sSql = "SELECT SUM(`todaycash`) AS `cashbalance` FROM `snapshot` WHERE `days`='" . $sDate . "' AND `istestuser`= 0 LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['cashbalance'] = $aData['cashbalance'] == NULL ? 0.0000 : $aData['cashbalance'];
        //获取前日现金余额
        $sSql = "SELECT `cashbalance` AS `lastcashbalance` FROM `financial` WHERE `day`='" . date("Y-m-d", strtotime($sDate) - 86400) . "' LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return array();
        }
        $aResult['lastcashbalance'] = $aData['lastcashbalance'] == NULL ? 0.0000 : $aData['lastcashbalance'];
        return $aResult;
    }
    
    /**
     * 获取指定月份报表
     * @param type $sMonth
     */
    public function getList($sMonth) {
        $sMonth = $sMonth == "" ? date("Y-m") : $sMonth;
        $sSql = "SELECT * FROM `financial` WHERE `day` LIKE '" . $sMonth . "%'";
        $aResult = $this->oDB->getAll($sSql);
        return $aResult;
    }
    
    /**
     * 获取指定日期报表详情
     * @param type $sMonth
     */
    public function getDetail($sMonth, $sDate) {
        $sMonth = $sMonth == "" ? date("Y-m") : $sMonth;
        $sLastMonth = date("Y-m", strtotime("-1 month", strtotime($sDate)));
        $sDate = $sDate == "" ? date("Y-m-d") : $sDate;
        $sLastDate = date("Y-m-" . date("d", strtotime($sDate)), strtotime("-1 month", strtotime($sDate)));
        if ($sMonth == $sLastMonth) {
            $sLastMonth = date("Y-m", strtotime("-1 month", strtotime($sDate) - 86400));
        }
        if ($sDate == $sLastDate) {
            $sLastDate = date("Y-m-" . date("d", strtotime($sDate) - 86400), strtotime("-1 month", strtotime($sDate) - 86400));
        }
        $aResult = array();
        //获取当日数据
        $sSql = "SELECT * FROM `financial` WHERE `day` = '" . $sDate . "' LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if (!empty($aData)) {
            $aResult['bets'] = $aData['bets']; //当日销量
            $aResult['profit'] = $aData['profit']; //当日平台盈亏
            $aResult['realprofit'] = $aData['realprofit']; //当日实际利润
            $aResult['recharge'] = $aData['recharge']; //当日充值金额
            $aResult['newrecharge'] = $aData['newrecharge']; //当日新充值客户
            $aResult['newuser'] = $aData['newuser']; //当日新注册用户
            $aResult['withdraw'] = $aData['withdraw']; //当日提现金额
            $aResult['rwrate'] = number_format($aData['withdraw'] / $aData['recharge'] * 100, 2) . "%"; //当日充提占比
        }
        //获取当月到今日累计数据
        $sSql = "SELECT SUM(`bets`) AS monthbets,SUM(`profit`) AS monthprofit,SUM(`realprofit`) AS monthrealprofit,SUM(`recharge`) AS monthrecharge,SUM(`withdraw`) AS monthwithdraw
            FROM `financial` WHERE `day` <= '" . $sDate . "' AND `day` LIKE '" . $sMonth . "%' LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if (!empty($aData)) {
            $aResult['monthbets'] = $aData['monthbets']; //当月累计销量
            $aResult['monthprofit'] = $aData['monthprofit']; //当月累计平台盈亏
            $aResult['monthrealprofit'] = $aData['monthrealprofit']; //当月累计实际利润
            $aResult['monthrecharge'] = $aData['monthrecharge']; //当月累计充值金额
            $aResult['monthwithdraw'] = $aData['monthwithdraw']; //当月累计提现金额
            $aResult['monthrwrate'] = number_format($aData['monthwithdraw'] / $aData['monthrecharge'] * 100, 2) . "%"; //当月累计充提占比
        }
        //获取上个月同期数据
        $sSql = "SELECT * FROM `financial` WHERE `day` <= '" . $sLastDate . "' ORDER BY `day` DESC LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if (!empty($aData)) {
            $aResult['lastnewrecharge'] = $aData['newrecharge']; //上月当日新充值客户
            $aResult['lastnewuser'] = $aData['newuser']; //上月当日新注册用户
            $aResult['lastrecharge'] = $aData['recharge']; //上月当日充值金额
            $aResult['lastrwrate'] = number_format($aData['withdraw'] / $aData['recharge'] * 100, 2) . "%"; //上月同期充提占比
        }
        //获取上个月同期累计数据
        $sSql = "SELECT SUM(`bets`) AS monthbets,SUM(`profit`) AS monthprofit,SUM(`realprofit`) AS monthrealprofit,SUM(`recharge`) AS monthrecharge,SUM(`withdraw`) AS monthwithdraw
            FROM `financial` WHERE `day` <= '" . $sLastDate . "' AND `day` LIKE '" . $sLastMonth . "%' LIMIT 1";
        $aData = $this->oDB->getOne($sSql);
        if (!empty($aData)) {
            $aResult['lastmonthbets'] = $aData['monthbets']; //上月累计销量
            $aResult['lastmonthprofit'] = $aData['monthprofit']; //上月累计平台盈亏
            $aResult['lastmonthrealprofit'] = $aData['monthrealprofit']; //上月累计实际利润
            $aResult['lastmonthrecharge'] = $aData['monthrecharge']; //上月累计充值金额
            $aResult['lastmonthwithdraw'] = $aData['monthwithdraw']; //上月累计提现金额
            $aResult['lastmonthrwrate'] = number_format($aData['monthwithdraw'] / $aData['monthrecharge'] * 100, 2) . "%"; //上月累计充提占比
        }
        //月销量同期比
        $aResult['monthsalerate'] = isset($aResult['lastmonthbets']) && $aResult['lastmonthbets'] > 0 ? number_format(($aResult['monthbets'] - $aResult['lastmonthbets']) / $aResult['lastmonthbets'] * 100, 2) . "%" : "0.00%";
        //月平台盈亏同期比
        $aResult['monthprofitrate'] = isset($aResult['lastmonthprofit']) && $aResult['lastmonthprofit'] > 0 ? number_format(($aResult['monthprofit'] - $aResult['lastmonthprofit']) / $aResult['lastmonthprofit'] * 100, 2) . "%" : "0.00%";
        //月实际利润同期比
        $aResult['monthrealprofitrate'] = isset($aResult['lastmonthrealprofit']) && $aResult['lastmonthrealprofit'] > 0 ? number_format(($aResult['monthrealprofit'] - $aResult['lastmonthrealprofit']) / $aResult['lastmonthrealprofit'] * 100, 2) . "%" : "0.00%";
        //日充值金额差异率
        $aResult['dayctrate'] = isset($aResult['lastrecharge']) && $aResult['lastrecharge'] > 0 ? number_format(($aResult['recharge'] - $aResult['lastrecharge']) / $aResult['lastrecharge'] * 100, 2) . "%" : "0.00%";
        //月充值金额差异率
        $aResult['monthctrate'] = isset($aResult['lastmonthrecharge']) && $aResult['lastmonthrecharge'] > 0 ? number_format(($aResult['monthrecharge'] - $aResult['lastmonthrecharge']) / $aResult['lastmonthrecharge'] * 100, 2) . "%" : "0.00%";
        //新客户差异率(注册)
        $aResult['newuserrate'] = isset($aResult['lastnewuser']) && $aResult['lastnewuser'] > 0 ? number_format(($aResult['newuser'] - $aResult['lastnewuser']) / $aResult['lastnewuser'] * 100, 2) . "%" : "0.00%";
        //新客户差异率(充值)
        $aResult['newrechargerate'] = isset($aResult['lastnewrecharge']) && $aResult['lastnewrecharge'] > 0 ? number_format(($aResult['newrecharge'] - $aResult['lastnewrecharge']) / $aResult['lastnewrecharge'] * 100, 2) . "%" : "0.00%";
        //日累计充提占比差异率
        $aResult['dayctdiff'] = isset($aResult['lastrwrate']) && $aResult['lastrwrate'] > 0 ? number_format($aResult['lastrwrate'] - $aResult['rwrate'], 2) : number_format($aResult['rwrate'], 2);
        //月累计充提占比差异率
        $aResult['monthctdiff'] = isset($aResult['lastmonthrwrate']) && $aResult['lastmonthrwrate'] > 0 ? number_format($aResult['lastmonthrwrate'] - $aResult['monthrwrate'], 2) : number_format($aResult['monthrwrate'], 2);
        return $aResult;
    }
    
    /**
     * 获取指定ID报表
     * @param type $iId
     */
    public function getItem($iId = 0) {
        if ($iId == 0) {
            return FALSE;
        }
        $sSql = "SELECT * FROM `financial` WHERE `id` = '" . $iId . "' LIMIT 1";
        $aResult = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0 || empty($aResult)) {
            return FALSE;
        }
        return $aResult;
    }
    
    /**
     * 增加充值退款
     * @param type $aData
     */
    public function backRecharge($aData = array()) {
        if (empty($aData)) {
            return -1001;
        }
        //报表对应的ID
        if (!isset($aData['reportid']) || $aData['reportid'] <= 0) {
            return -1002;
        }
        //退款金额
        if (!isset($aData['amount']) || $aData['amount'] <= 0) {
            return -1003;
        }
        //退款手续费
        if (!isset($aData['fee']) || $aData['fee'] < 0) {
            return -1004;
        }
        //退款真实用户姓名
        if (!isset($aData['username']) || $aData['username'] == "") {
            return -1005;
        }
        //退款银行流水号
        if (!isset($aData['serial']) || $aData['serial'] == "") {
            return -1006;
        }
        //退款备注
        if (!isset($aData['reason']) || $aData['reason'] == "") {
            return -1007;
        }
        $aData['adminid'] = $_SESSION['admin']; //退款管理员ID
        $aData['adminname'] = $_SESSION['adminname']; //退款管理员名称
        $this->oDB->doTransaction(); //开始事务
        //01添加退款记录
        $this->oDB->insert("backrecords", $aData);
        if ($this->oDB->errno > 0) {
            $this->oDB->doRollback(); //事务回退
            return -1008;
        }
        //02更新当天的充值金额
        $iReportId = intval($aData['reportid']); //报表对应的ID
        $aCheck = $this->getItem($iReportId); //获取当前记录
        $aFinancial = array();
        $aFinancial['rechargeback'] = $aCheck['rechargeback'] + $aData['amount'] + $aData['fee']; //退款金额
        $this->oDB->update("financial", $aFinancial, "`id`='" . $iReportId . "'");
        if ($this->oDB->errno > 0) {
            $this->oDB->doRollback(); //事务回退
            return -1009;
        }
        $this->oDB->doCommit();
        return TRUE;
    }
    
    /**
     * 获取指定ID报表退款列表
     * @param type $iId
     */
    public function getBackList($iId = 0) {
        if ($iId == 0) {
            return FALSE;
        }
        $sSql = "SELECT * FROM `backrecords` WHERE `reportid` = '" . $iId . "'";
        $aResult = $this->oDB->getAll($sSql);
        if ($this->oDB->errno > 0 || empty($aResult)) {
            return FALSE;
        }
        return $aResult;
    }
    
    /**
     * 统计每个总代每天的报表
     * @param type $sDate
     */
    public function createTopReport($sDate) {
        //step 01 检测参数是否正确
        if ($sDate == "") {
            return "The date is not right,please input report date";
        }
        $sSql = "SELECT ur.`lvtopid`,ut.`username`,SUM(ur.`realbets`) AS `bets`,SUM(ur.`points`) AS `points`,SUM(ur.`bonus`) AS `bonus`,SUM(ur.`realbets`-ur.`points`-ur.`bonus`) AS `profit`
            FROM `user_report` AS ur LEFT JOIN `usertree` AS ut ON(ut.`userid`=ur.`lvtopid`) WHERE ur.`day`='" . $sDate . "' AND ut.`istester`=0 AND ut.`issuperproxy`=0 GROUP BY ur.`lvtopid`";
        $aData = $this->oDB->getAll($sSql);
        if ($this->oDB->errno > 0) {
            return $this->oDB->error;
        }
        $aSql = array();
        foreach ($aData as $aTmp) {
            $aResult = array(); //报表结果集
            $aResult['day'] = "'".$sDate."'";
            $aResult['userid'] = $aTmp['lvtopid'];
            $aResult['username'] = "'".$aTmp['username']."'";
            $aResult['bets'] = $aTmp['bets'] == NULL ? 0.0000 : $aTmp['bets'];
            $aResult['points'] = $aTmp['points'] == NULL ? 0.0000 : $aTmp['points'];
            $aResult['bonus'] = $aTmp['bonus'] == NULL ? 0.0000 : $aTmp['bonus'];
            $aResult['profit'] = $aTmp['profit'] == NULL ? 0.0000 : $aTmp['profit'];
            $aSql[] = "(".implode(",", $aResult).")";
        }
        if (!empty($aSql)) {
            $sql = "INSERT IGNORE `topreport` (day,userid,username,bets,points,bonus,profit) VALUES " . implode(',', $aSql);
            $this->oDB->query($sql);
            if ($this->oDB->errno > 0) {
                return $this->oDB->error;
            }
        }
        return TRUE;
    }
    
    /**
     * 获取总代分红
     * @param type $sMonth
     */
    public function getShare($sStartDay, $sEndDay = '', $iChannelId = '') {
        $sConditon = "1";
        if ($iChannelId != '') {
            $sConditon .= " AND uf.`channelid`=" . $iChannelId;
        }
        $condition = " BETWEEN '$sStartDay' AND '$sEndDay'";
        //获取公司总代信息
        $sCompanyTopId = getConfigValue("topproxyset_companytopid", "1,2"); //公司总代ID
        $sConditon = "1";
        if ($sCompanyTopId != '') {
            $sConditon .= " AND tp.`userid` NOT IN(" . $sCompanyTopId . ") ";
        }
        $sSql = "SELECT tp.`userid`,uf.`channelid`,tp.`username`,SUM(tp.`bets`) AS `bets`,SUM(tp.`points`) AS `points`,SUM(tp.`bonus`) AS `bonus`,SUM(tp.`profit`) AS `profit` "
            . "FROM `topreport` AS tp LEFT JOIN `userfund` AS uf ON(tp.`userid`=uf.`userid`) "
            . "WHERE tp.`day` $condition AND " . $sConditon . " GROUP BY tp.`userid`";
        $aResult = $this->oDB->getAll($sSql);
        return $aResult;
    }
    /**
     * 获取铂金用户半月工资
    和分红计划运行日期有关，16号以后获取上半月的，16号之前获取上个月后半月的
     * @param type $iUserId
     * @param type $rewardType 1 铂金总代工资 5 铂金总代额外分红
     */
    public function getPlatinumUserWageOrBonus($iUserId,$rewardType=1) {
        if(date("d") <= 15){
            $startDay = date("Y-m-16", strtotime("-1 month"));
            $endDay = date("Y-m-31", strtotime("-1 month"));
        }else{
            $startDay = date("Y-m-01");
            $endDay = date("Y-m-15");
        }
        $condition = " BETWEEN '$startDay' AND '$endDay'";
        $sSql = "SELECT SUM(`reward`) AS `bets` FROM `activity_zhaoshang` WHERE userid=$iUserId AND `day` $condition AND rewardtype=$rewardType";
        $aResult = $this->oDB->getOne($sSql);
        if($aResult['bets']){
            return $aResult['bets'];
        }else{
            return 0;
        }
    }
    
    /**
     * 获取总代分红详情
     * @param type $sMonth
     * @param type $iUserId
     */
    public function getShareDetail($sMonth, $iUserId) {
        $sMonth = $sMonth == "" ? date("Y-m") : $sMonth;
        $sSql = "SELECT `userid`,`day`,`username`,SUM(`bets`) AS `bets`,SUM(`points`) AS `points`,SUM(`bonus`) AS `bonus`,SUM(`profit`) AS `profit` FROM `topreport` WHERE `day` LIKE '" . $sMonth . "%' AND `userid`='" . $iUserId . "' GROUP BY `day`";
        $aResult = $this->oDB->getAll($sSql);
        return $aResult;
    }
    
    /**
     * 获取平台理赔记录
     * @param type $sCondition
     */
    public function getClaimList($sCondition = "") {
        $sSql = "SELECT * FROM `claims` WHERE " . $sCondition;
        $aResult = $this->oDB->getAll($sSql);
        return $aResult;
    }
    
    /**
     * 检测当月的分红
     * @param type $sMonth
     * @return type
     */
    public function checkCurShare($sMonth) {
        $sMonth = $sMonth == "" ? date("Y-m") : $sMonth;
        $fShare = 0;
        $sSql = "SELECT SUM(`amount`) as share FROM `claims` WHERE `day` LIKE '" . $sMonth . "%' AND `type`=2";
        $aResult = $this->oDB->getOne($sSql);
        if ($this->oDB->errno > 0) {
            return $fShare;
        }
        $fShare = $aResult['share'] == NULL ? 0.0000 : $aResult['share'];
        return $fShare;
    }
    
    /**
     * 账务特殊金额整理
     * @param type $aData
     * @return boolean
     */
    public function adjustSpecial($aData = array()) {
        if (empty($aData)) {
            return -1001;
        }
        //报表对应的ID
        if (!isset($aData['reportid']) || $aData['reportid'] <= 0) {
            return -1002;
        }
        //整理金额
        if (!isset($aData['amount'])) {
            return -1003;
        }
        //整理备注
        if (!isset($aData['reason']) || $aData['reason'] == "") {
            return -1007;
        }
        $iIsBelongRecharge = 0; //是否计入充值
        if (isset($aData['belongrecharge'])) {
            $iIsBelongRecharge = $aData['belongrecharge'];
            unset($aData['belongrecharge']);
        }
        $aData['adminid'] = $_SESSION['admin']; //整理管理员ID
        $aData['adminname'] = $_SESSION['adminname']; //整理管理员名称
        $this->oDB->doTransaction(); //开始事务
        //01添加整理记录
        $this->oDB->insert("specialrecords", $aData);
        if ($this->oDB->errno > 0) {
            $this->oDB->doRollback(); //事务回退
            return -1008;
        }
        //02更新当天的充值金额
        $iReportId = intval($aData['reportid']); //报表对应的ID
        $aCheck = $this->getItem($iReportId); //获取当前记录
        $aFinancial = array();
        if ($iIsBelongRecharge == 1) {//计入充值
            $aFinancial['recharge'] = $aCheck['recharge'] + $aData['amount'];
        }
        $aFinancial['special'] = $aCheck['special'] + $aData['amount'];
        $aFinancial['realprofit'] = $aCheck['realprofit'] + $aData['amount'];
        $this->oDB->update("financial", $aFinancial, "`id`='" . $iReportId . "'");
        if ($this->oDB->errno > 0) {
            $this->oDB->doRollback(); //事务回退
            return -1009;
        }
        $this->oDB->doCommit();
        return TRUE;
    }
    
    /**
     * 特殊金额整理列列表
     * @param type $iId
     * @return boolean
     */
    function getSpecialList($iId = 0) {
        if ($iId == 0) {
            return FALSE;
        }
        $sSql = "SELECT * FROM `specialrecords` WHERE `reportid` = '" . $iId . "'";
        $aResult = $this->oDB->getAll($sSql);
        if ($this->oDB->errno > 0 || empty($aResult)) {
            return FALSE;
        }
        return $aResult;
    }
    
    /**
     * 获取超级总代信息
     */
    function getSuperProxy() {
        $sSql = "SELECT * FROM `usertree` WHERE `issuperproxy`=1";
        $aTopList = $this->oDB->getAll($sSql);
        return $aTopList;
    }
    
    /**
     * 自动分红检查
     * @param type $iUserId
     * @param type $fShare
     */
    function checkShare($iUserId = 0, $sLastDay = '') {
        $sSql = "SELECT * FROM `claims` WHERE `userid`=" . $iUserId . " AND `type`=2 AND `day`='" . $sLastDay . "' LIMIT 1";
        $aCheck = $this->oDB->getOne($sSql);
        if (empty($aCheck)) {
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * 统计每个公司一代（非测试）每天的报表
     * @param type $sDate
     */
    public function createProxyReport($sDate) {
        //step 01 检测参数是否正确
        if ($sDate == "") {
            return "The date is not right,please input report date";
        }
        //获取公司总代信息
        $sCompanyTopId = getConfigValue("topproxyset_companytopid", "1,2"); //公司总代ID
        $sConditon = " AND 1";
        if ($sCompanyTopId != '') {
            $sConditon .= " AND ut.`lvtopid` IN(" . $sCompanyTopId . ") AND ur.`lvproxyid`>0 ";
        }
        $sCompanyProxyId = getConfigValue("topproxyset_companyproxyid", "4100,258525"); //公司一代ID(用于测试)
        if ($sCompanyProxyId != '') {
            $sConditon .= " AND ut.`lvproxyid` NOT IN(" . $sCompanyProxyId . ") ";
        }
        $sSql = "SELECT ur.`lvproxyid`,SUM(ur.`realbets`) AS `bets`,SUM(ur.`points`) AS `points`,SUM(ur.`bonus`) AS `bonus`,SUM(ur.`realbets`-ur.`points`-ur.`bonus`) AS `profit`
            FROM `user_report` AS ur LEFT JOIN `usertree` AS ut ON(ut.`userid`=ur.`lvtopid`) WHERE ur.`day`='" . $sDate . "' AND ut.`istester`=0 AND ut.`issuperproxy`=0" . $sConditon . " GROUP BY ur.`lvproxyid`";
        $aData = $this->oDB->getAll($sSql);
        if ($this->oDB->errno > 0) {
            return $this->oDB->error;
        }
        /* @var $oUserTree model_user */
        $oUser = A::singleton('model_user', $GLOBALS['aSysDbServer']['report']);
        $aSql = array();
        foreach ($aData as $aTmp) {
            $aTmpUser = $oUser->getUserInfo($aTmp['lvproxyid']);
            $aResult = array(); //报表结果集
            $aResult['day'] = "'" . $sDate . "'";
            $aResult['userid'] = $aTmp['lvproxyid'];
            $aResult['username'] = "'" . $aTmpUser['username'] . "'";
            $aResult['bets'] = $aTmp['bets'] == NULL ? 0.0000 : $aTmp['bets'];
            $aResult['points'] = $aTmp['points'] == NULL ? 0.0000 : $aTmp['points'];
            $aResult['bonus'] = $aTmp['bonus'] == NULL ? 0.0000 : $aTmp['bonus'];
            $aResult['profit'] = $aTmp['profit'] == NULL ? 0.0000 : $aTmp['profit'];
            $aSql[] = "(" . implode(",", $aResult) . ")";
        }
        if (!empty($aSql)) {
            $sql = "INSERT IGNORE `proxyreport` (day,userid,username,bets,points,bonus,profit) VALUES " . implode(',', $aSql);
            $this->oDB->query($sql);
            if ($this->oDB->errno > 0) {
                return $this->oDB->error;
            }
        }
        return TRUE;
    }
    
    /**
     * 获取公司一代分红
     * @param type $sMonth
     */
    public function getProxyShare($sStartDay, $sEndDay = '', $iChannelId = '') {
        $sConditon = "1";
        if ($iChannelId != '') {
            $sConditon .= " AND uf.`channelid`=" . $iChannelId;
        }
        $condition = " BETWEEN '$sStartDay' AND '$sEndDay'";
        $sSql = "SELECT tp.`userid`,uf.`channelid`,tp.`username`,SUM(tp.`bets`) AS `bets`,SUM(tp.`points`) AS `points`,SUM(tp.`bonus`) AS `bonus`,SUM(tp.`profit`) AS `profit` "
            . "FROM `proxyreport` AS tp LEFT JOIN `userfund` AS uf ON(tp.`userid`=uf.`userid`) "
            . "WHERE tp.`day` $condition GROUP BY tp.`userid`";
        $aResult = $this->oDB->getAll($sSql);
        return $aResult;
    }
    
    /**
     * 获取一代分红详情
     * @param type $sMonth
     * @param type $iUserId
     */
    public function getProxyShareDetail($sMonth, $iUserId) {
        $sMonth = $sMonth == "" ? date("Y-m") : $sMonth;
        $sSql = "SELECT `userid`,`day`,`username`,SUM(`bets`) AS `bets`,SUM(`points`) AS `points`,SUM(`bonus`) AS `bonus`,SUM(`profit`) AS `profit` FROM `proxyreport` WHERE `day` LIKE '" . $sMonth . "%' AND `userid`='" . $iUserId . "' GROUP BY `day`";
        $aResult = $this->oDB->getAll($sSql);
        return $aResult;
    }
}

?>
