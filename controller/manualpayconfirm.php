<?php

/**
 * Class controller_artificialreview
 */
class controller_manualpayconfirm extends pcontroller
{
    /**
     * 1 获取审核表用户列表
     * @author ken 2017
     */
    public function actionList ()
    {
        static $aPageNumber = array(100,300,500,1000,2000);
        if ($this->getIsAjax()) {//接收常规数据
            $aGetData = $this->post(array(
                'page' => parent::VAR_TYPE_INT,
                'rows' => parent::VAR_TYPE_INT,
                'sdate' => parent::VAR_TYPE_DATETIME,
                'edate' => parent::VAR_TYPE_DATETIME,
                'input_type' => parent::VAR_TYPE_STR,
                'review_status' => parent::VAR_TYPE_INT,
                'start_money' => parent::VAR_TYPE_INT,
                'end_money' => parent::VAR_TYPE_INT,
                'isconfirm' => parent::VAR_TYPE_INT,
                'order_type' => parent::VAR_TYPE_INT,
                'sidx' => parent::VAR_TYPE_STR,
                'sord' => parent::VAR_TYPE_STR,
            ));
            $aOther = $this->post(array(// 接收动态操作人
                'admin_type' => parent::VAR_TYPE_STR,
                'admin_type_value' => parent::VAR_TYPE_STR,
            ));
            //验证分页条数是否合法
            if (!in_array($aGetData['rows'],$aPageNumber)){
                $this->ajaxMsg(0,"参数错误请重试");
            }
            $aGetData[$aOther['admin_type']] = $aOther['admin_type_value']; //
            $sWhere = $this->_getSearch($aGetData);// 拼接搜索条件
            $oProxyMan = new model_proxymanualpay();
            $sOrderBy = $aGetData['sidx'] == '' ? "a.`inserttime` ${aGetData['sord']}" :"${aGetData['sidx']} ${aGetData['sord']}";
            $aResult = $oProxyMan->getConfirmList('*', $sWhere, $aGetData['rows'], $aGetData['page'],$sOrderBy);
            // 剥离出入款
            $aRstNew = $this->_setInOut($aResult['results']);
            if ($aRstNew != '' && is_array($aRstNew)) {
                //格式化金额
                foreach ($aRstNew as $k => &$v){
                    $v['intmoney'] = isset($v['intmoney']) ? numberFormat2($v['intmoney']) : 0;
                    $v['outmoney'] = isset($v['outmoney']) ? numberFormat2($v['outmoney']) : 0 ;
                    $v['ext_amount'] = numberFormat2($v['ext_amount']);
                    $v['ext_bets'] = numberFormat2($v['ext_bets']);
                    $v['audit_bets'] = numberFormat2($v['audit_bets']);
                }
                $this->outPutJQgruidJson($aRstNew, $aResult['affects'], $aGetData['page'], $aGetData['rows']);
            }
        }
        $sYestoday = date('Y-m-d 00:00:00');
        $sToday = date('Y-m-d 00:00:00',strtotime('+1 days'));;
        $GLOBALS['oView']->assign('ytday', $sYestoday);
        $GLOBALS['oView']->assign('today', $sToday);
        $GLOBALS['oView']->assign('pageNumber', json_encode($aPageNumber));
        $GLOBALS['oView']->display("manualpayconfirm_list.html");
    }


    /**
     * 1 批量审核
     * @author robert 2018
     */
    public function actionBatchToExamine()
    {
        if ($this->getIsPost()) {
            if (!isset($_POST['ids']) || !is_array($_POST['ids'])){
                $this->ajaxMsg(0, "参数错误请重试");
            }
            $aIds = $_POST['ids'];//前台传过来的id
            if (!is_array($aIds)) {
                $this->ajaxMsg(0, "参数错误请重试");
            }
            $states = $_POST['states'];//状态来判断是通过还是拒绝
            foreach ($aIds as $k => $v) {
                $this->BatchModificationState($v,$states);
            }
            $this->ajaxMsg(1, '审批成功，已完成所有审批！');
        }
    }
    /**
     * 1 批量修改状态
     * @author robert 2018
     * @param $v array
     * @param $sStates string 状态：2拒绝，1通过
     */
    function BatchModificationState($v, $sStates)
    {
        //拒绝
        if ($sStates == '2') {
            $_POST = array('isconfirm' => 2, 'id' => $v, 'confirm_remark' => $_POST['confirm_remark'], 'states' => $sStates);
            $this->actionUpdateConfirm();
            return;
        }
        //同意
        $oProxyMan = new model_proxymanualpay();
        $aData = $oProxyMan->getOneConfirmById($v);
        $aRstNew = $this->_setInOut(array($aData));
        $aRstNew[0]['confirm_remark'] = $_POST['confirm_remark'];
        $_POST = $aRstNew[0];
        $_POST['states'] = $sStates;//这个状态是后面判断是否是批量处理用的
        $this->actionUptoConfirm();
        return;
    }

    /**
     * 2 管理员审核（执行）审批拒绝
     *
     * @author ken 2017
     */
    public function actionUpdateConfirm ()
    {
        $aData['confirm_admin'] = $this->loginProxyId;//当前登录商户id
        $aData['confirmadmin'] = $this->adminname;
        $aData['finishtime'] = date( "Y-m-d H:i:s", time());
        $aGetData = $this->post(array(
            'id' => parent::VAR_TYPE_INT,
            'confirm_admin' => parent::VAR_TYPE_STR,
            'confirm_remark' => parent::VAR_TYPE_STR,
            'isconfirm' => parent::VAR_TYPE_INT,
        ));
        $oProxyMan = new model_proxymanualpay();
        /*1 审核拒绝接口  直接点击拒绝*/
        $aData['isconfirm'] = $aGetData['isconfirm'];
        $aData['confirm_remark'] = $aGetData['confirm_remark'];
        if (empty($aData['confirm_remark'])) {
            sysMessage('提交的数据不完整，请重试', 1);
        }
        $aData['id'] = $aGetData['id'];
        $sResult = $oProxyMan->UpdateOneConfirmById($aData);//审批拒绝
        //如果是批量拒绝，直接返回true，不然后面会直接断掉
        if (isset($_POST['states'])){
            return true;
        }
        if ($sResult) {
            $this->ajaxMsg(1, '审核已拒绝');
        }
        EXIT;
    }

    /**
     * 3 审批通过 走事务
     * 4 审批通过 使用独立的model pmanualpaychecked 模型
     *
     * @author ken 2017
     */
    public function actionUptoConfirm()
    {
        /*
         * 1 调取审批表中的数据
         * 2 将数据传送到方法
         * 3 需要的数据为 用户id 管理员id 资金 审批通过利亚欧
         */
        $aGetData = $this->post(array(
            'id' => parent::VAR_TYPE_INT,
            'optype' => parent::VAR_TYPE_INT,
            'usernames' => parent::VAR_TYPE_STR,
            'ext_amount' => parent::VAR_TYPE_FLOAT,
            'ext_bets' => parent::VAR_TYPE_FLOAT,
            'checkbets' => parent::VAR_TYPE_INT,
            'order_type' => parent::VAR_TYPE_INT,
            'apply_adminid' => parent::VAR_TYPE_INT,
            'apply_remark' => parent::VAR_TYPE_STR,
            'isconfirm' => parent::VAR_TYPE_INT,
            'adminname' => parent::VAR_TYPE_STR,
            'user_ids' => parent::VAR_TYPE_STR,// 注意用字符串接收
            'amount' => parent::VAR_TYPE_FLOAT,
            'confirm_remark' => parent::VAR_TYPE_STR,// 审核者备注
            'audit_bets' => parent::VAR_TYPE_FLOAT // 常态稽核
        ));
        if ($aGetData['amount'] == 0 && $aGetData['ext_amount'] > 0 ) {
            $aGetData['order_type'] = 3;
        }
        $iOptype = isset($aGetData['optype']) ? $aGetData['optype'] : '';
        if ($iOptype === '' || ($iOptype != 1 && $iOptype != 0 )) { // 0 加款 1 扣款
            $this->ajaxMsg(-1,"加减款出错，请重试");
        }
        $aData = $this->_arrData($aGetData); //整理数据
        if (0 == $iOptype) {// 加款 执行
            // 核对数据
            switch ($this->_checkData($aGetData)) {
                case -601:
                    $this->ajaxMsg(-2,"审核状态错误");
                    break;
                case -602:
                    $this->ajaxMsg(-3,"管理员名称错误");
                    break;
                case -603:
                    $this->ajaxMsg(-4,"金额错误");
                    break;
                case -604:
                    $this->ajaxMsg(-5,"用户名或id不能为空");
                    break;
                case -605:
                    $this->ajaxMsg(-6,"审批理由不能为空");
                    break;
                case -606:
                    $this->ajaxMsg(-7,"操作类型不能为空");
                    break;
            }
            $oConfirmChecked = new model_pmanualpaychecked();
            $iFlag = $oConfirmChecked->adminPayMentProxy($aData);
            //如果是批量通过，直接返回true，不然后面会直接断掉
            if (isset($_POST['states'])){
                return true;
            }
            if (1 == $iFlag || is_array($iFlag)) {
                if (is_array($iFlag)) {
                     $aCode = $this->_beforSendData($iFlag);
                     if (1 == $aCode[0]) {
                         $this->ajaxMsg(1,"完成审核,成功人数(".$aCode[1].")人,失败人数(".$aCode[2].")人,不通过用户(".$aCode[3]."),错误码:".$aCode[4]."");
                     }
                     if (2 == $aCode[0]) {
                         if (0 == $aCode[2]) {
                             $this->ajaxMsg(1, '审核已通过');
                         }
                         $this->ajaxMsg(1,"完成审核,成功人数(".$aCode[1].")人,失败人数(".$aCode[2].")人,不通过用户(".$aCode[3]."),错误码:".$aCode[4]."");
                     }
                }
                $this->ajaxMsg(1, '审核已通过');
            } else {
                switch ($iFlag) {
                    case -2:
                        $this->ajaxMsg(-9,"账户被锁");
                        break;
                    case -3:
                        $this->ajaxMsg(-10,"用户理赔充值成功,但账号被锁");
                        break;
                    case -11:
                        $this->ajaxMsg(-11,"存入类型不能为空");
                        break;
                    case -12:
                        $this->ajaxMsg(-12,"批量存入错误");
                        break;
                    case -13:
                        $this->ajaxMsg(-13,"批量存入时用户ID错误");
                        break;
                    case -1001:
                        $this->ajaxMsg(-14,"用户ID错误");
                        break;
                    case -1002:
                        $this->ajaxMsg(-15,"账变类型ID错误");
                        break;
                    case -1003:
                        $this->ajaxMsg(-16,"账变金额错误, 不允许负数");
                        break;
                    case -1004:
                        $this->ajaxMsg(-17,"获取用户频道资金数据失败");
                        break;
                    case -1005:
                        $this->ajaxMsg(-18,"用户资金账户未被锁");
                        break;
                    case -1006:
                        $this->ajaxMsg(-19,"账变类型错误,未被程序枚举处理");
                        break;
                    case -1007:
                        $this->ajaxMsg(-20,"账变记录插入失败");
                        break;
                    case -1008:
                        $this->ajaxMsg(-21,"账户金额更新失败");
                        break;
                    case -1009:
                        $this->ajaxMsg(-22,"金额不正确");
                        break;
                    case -2001:
                        $this->ajaxMsg(-23,"该条审核已被其他管理员审核,请勿重复提交");
                        break;
                    case -2002:
                        $this->ajaxMsg(-24,"更新打码表失败");
                        break;
                    default:
                        $this->ajaxMsg(-25,"系统错误002");
                }
            }
            EXIT;
        } elseif ($iOptype == 1) { // 扣款
            $iUserId = isset($aGetData['user_ids']) ? $aGetData['user_ids'] : '';
            // 1 整理接收数据
            $iAdmin = $this->loginProxyId;
            $iLvtopid = $this->lvtopid;
            $sUsername = isset($aGetData['usernames']) ? $aGetData['usernames'] : '';
            $fMoney = isset($aGetData['amount']) ? $aGetData['amount'] : '';
            $iPayMent = isset($_POST["payment"]) && is_numeric($_POST["payment"]) ? intval($_POST["payment"]) : 0;
            $iChannel = isset($_POST["channel"]) && is_numeric($_POST["channel"]) ? intval($_POST["channel"]) : 0;
            $sDescription = isset($aGetData['apply_remark']) ? $aGetData['apply_remark'] : '';
            // 2 核查数据
            if ($iUserId == '' || $sUsername == '') {
                $this->ajaxMsg(-26,"用户信息错误");
            }
            if ($iAdmin == '' || $iLvtopid == '') { // 系统错误
                $this->ajaxMsg(-27,"系统错误003");
            }
            if ($fMoney == '' || $fMoney < 0) {// 金额错误
                $this->ajaxMsg(-28,"系统金额错误，请重试");
            }
            if ($sDescription == '') {
                $this->ajaxMsg(-29,"备注不能为空");
            }
            if ($iPayMent == 0) {
                $iChannel = 0; //只向银行频道加钱
            }
            $oConfirmChecked = new model_pmanualpaychecked();
            $iFlag = $oConfirmChecked->admintoUserPayWithDraw($iAdmin, $iUserId, $fMoney, $sDescription, $iChannel, $aData);
            switch ($iFlag) {
                case 1:
                    $this->ajaxMsg(1, '已经审核通过');
                    break;
                case -2:
                    $this->ajaxMsg(-9,"账户被锁");
                    break;
                case -3:
                    $this->ajaxMsg(-10,"用户可用余额不足,请重新查看");
                    break;
                case -11:
                    $this->ajaxMsg(-11,"存入类型不能为空");
                    break;
                case -12:
                    $this->ajaxMsg(-12,"批量存入错误");
                    break;
                case -13:
                    $this->ajaxMsg(-13,"批量存入时用户ID错误");
                    break;
                case -1001:
                    $this->ajaxMsg(-14,"用户ID错误");
                    break;
                case -1002:
                    $this->ajaxMsg(-15,"账变类型ID错误");
                    break;
                case -1003:
                    $this->ajaxMsg(-16,"账变金额错误, 不允许负数");
                    break;
                case -1004:
                    $this->ajaxMsg(-17,"获取用户频道资金数据失败");
                    break;
                case -1005:
                    $this->ajaxMsg(-18,"用户资金账户未被锁");
                    break;
                case -1006:
                    $this->ajaxMsg(-19,"账变类型错误,未被程序枚举处理");
                    break;
                case -1007:
                    $this->ajaxMsg(-20,"账变记录插入失败");
                    break;
                case -1008:
                    $this->ajaxMsg(-21,"账户金额更新失败");
                    break;
                case -1009:
                    $this->ajaxMsg(-22,"金额不正确");
                    break;
                case -2001:
                    $this->ajaxMsg(-23,"该条审核已被其他管理员审核,请勿重复提交");
                    break;
                case -2002:
                    $this->ajaxMsg(-24,"更新打码表失败");
                    break;
                default:
                    $this->ajaxMsg(-30,"系统错误004");
            }
        }else {
            $this->ajaxMsg(-31,"系统错误005");
        }
    }
    /**
     * 3 拼接搜索方法
     *
     * @param $aGetData
     * @return string
     * @author ken 2017
     */
    private function _getSearch ($aGetData)
    {
        $iLvtopId = $this->lvtopid;
        // 1 整理搜索条件
        $sWhere = ' 1 '; //init
        if ($aGetData['sdate'] != '') {
            $sWhere .= " AND ( `inserttime` >= '" . daddslashes($aGetData['sdate']) . "' ) ";
            $aGetData['sdate'] = stripslashes_deep($aGetData['sdate']);
        }
        if ($aGetData['edate'] != '') {
            $sWhere .= " AND ( `inserttime` <= '" . daddslashes($aGetData['edate']) . "' ) ";
            $aGetData['edate'] = stripslashes_deep($aGetData['edate']);
        }
        //存提类型
        $iOrderType = isset($aGetData['order_type']) ? $aGetData['order_type'] : '';
        if ($iOrderType != '') {
            switch ($iOrderType) {
                case 1:
                    $sWhere .= "  AND  `order_type` = 0 ";
                    break;
                case 2:
                    $sWhere .= "  AND  `order_type` = 1 ";
                    break;
                case 3:
                    $sWhere .= "  AND  `order_type` = 3 ";
                    break;
                case 4:
                    $sWhere .= "  AND  `order_type` = 4 ";
                    break;
                case 5:
                    $sWhere .= "  AND  `order_type` = 5 ";
                    break;
                case 6:
                    $sWhere .= "  AND  `order_type` = 6 ";
                    break;
                case 7:
                    $sWhere .= "  AND  `order_type` = 7 ";
                    break;
                case 8:
                    $sWhere .= "  AND  `order_type` = 8 ";
                    break;
            }
        }
        if ($aGetData['isconfirm'] != "") {
            if ($aGetData['isconfirm'] == 3) {
                $sWhere .= "  AND  `isconfirm` = 0 ";
            }
            if ($aGetData['isconfirm'] == 1) {
                $sWhere .= "  AND  `isconfirm` = 1 ";
            }
            if ($aGetData['isconfirm'] == 2) {
                $sWhere .= "  AND  `isconfirm` = 2 ";
            }
        }
        if ($aGetData['start_money'] != '') {
            $sWhere .= " AND ( `amount` >= '" . doubleval($aGetData['start_money']) . "' ) ";
        }
        if ($aGetData['end_money'] != '') {
            $sWhere .= " AND ( `amount` <= '" . doubleval($aGetData['end_money']) . "' ) ";
        }

        $sMember = isset($aGetData['member']) ? $aGetData['member'] : '';
        $sCommiter = isset($aGetData['commiter']) ? $aGetData['commiter'] : '';
        $sOperator = isset($aGetData['operator']) ? $aGetData['operator'] : '';
        if ($sMember !== '') {//根据会员搜索
//            $sWhere .= " AND  a.usernames IN ('{$sMember}') ";
            $sWhere .= " AND  a.usernames LIKE '%{$sMember}%' ";
        }
        if ($sCommiter !== '') {//根据提交者搜索
            $sWhere .= " AND  a.adminname = '{$sCommiter}' ";
        }
        if ($sOperator !== '') {//根据审核者搜索
            $sWhere .= " AND  a.confirmadmin = '{$sOperator}' ";
        }
        $sWhere .= "  AND b.lvtopid = $iLvtopId  ";
        return $sWhere;
    }

    /**
     * 检查数据一致性
     *
     * @param $aData
     * @return int
     * @author ken 2017
     */
    private function _checkData($aData)
    {
        if (!isset($aData['isconfirm']) && $aData['isconfirm'] != 0) {
            return -601;// 状态不正确
        }

        if (empty($aData['adminname'])) {
            return -602; // 管理员错误
        }

        if (empty($aData['amount']) && empty($aData['ext_amount']) && empty($aData['ext_bets'])) {
            return -603; // 金额错误
        }

        if ($aData['user_ids'] === '' || $aData['user_ids'] === 0) {
            return -604;
        }
        if ($aData['usernames'] === '' || $aData['usernames'] === 0) {
            return -604;
        }

//        if (empty($aData['user_ids']) || empty($aData['usernames'])) {
//            return -604; // 用户id或用户名错误
//        }

        if (empty($aData['confirm_remark'])) {
            return -605; // 审批理由错误
        }

        if ($aData['order_type'] === '' || !isset($aData['order_type'])) {
            return -606;
        }
    }

    /**
     * 整理数据
     *
     * @param $aGetData
     * @return array
     * @author ken 2017
     */
    private function _arrData($aGetData)
    {
        $aData = array();//init
//        $aData['optype'] = isset($aGetData['optype']) ? $aGetData['optype'] : '';
        $aData['id'] = isset($aGetData['id']) ? $aGetData['id'] : 0;//该条审核的id
        $aData['lvtopid'] = $this->lvtopid;
//        $aData['adminname'] = isset($aGetData['adminname']) ? $aGetData['adminname'] : 0;// 审核者管理员名
        $aData['confirm_admin'] = $this->adminname; // 审核者管理员名
        $aData['confirm_admin_id'] = $this->loginProxyId; // 审核者管理员id
        $aData['usernames'] = isset($aGetData['usernames']) ? $aGetData['usernames'] : 0;// 需要充值用户名
        $aData['user_ids'] = isset($aGetData['user_ids']) ? $aGetData['user_ids'] : 0;// 待充值用户id
        $aData['confirm_remark'] = isset($aGetData['confirm_remark']) ? $aGetData['confirm_remark'] : 0;// 审核理由
        $aData['amount'] = isset($aGetData['amount']) ? $aGetData['amount'] : 0;// 充值金额
        $aData['ext_bets'] = isset($aGetData['ext_bets']) ? $aGetData['ext_bets'] : 0;// 综合打码量
        $aData['ext_amount'] = isset($aGetData['ext_amount']) ? $aGetData['ext_amount'] : 0; // 额外金额
        $aData['isconfirm'] = isset($aGetData['isconfirm']) ? $aGetData['isconfirm'] : 0; // 如何已经审核 则不需要再次审核
        // 原默认数据整理
        $aData['channelid'] = isset($aGetData['channelid']) ? $aGetData['channelid'] : 0;// 已弃用
        $aData['claimtype'] = isset($aGetData['order_type']) ? $aGetData['order_type'] : '';// 存入类型
        /*审核通过使用*/
        $aData['isconfirm'] = 1;//审核通过
        $aData['checkbets'] = $aGetData['checkbets'];
        if ($aData['checkbets'] == 1) { // 写入常态稽核
//        $aData['need_bets'] = (($aGetData['amount'] + $aGetData['ext_amount']) * 2); //常态打码量
//        $aData['need_bets'] = ($aGetData['amount'] * 2); //常态打码量

            $aData['need_bets'] = $aGetData['audit_bets']; // 常态打码量
        }

        return $aData;
    }

    /**
     * 剥离出入款
     *
     * @param $aData
     * @author ken 2017
     */
    private function _setInOut($aData)
    {
        foreach ($aData as $k => $v) {
            if ($aData[$k]['optype'] == 1) {
                $aData[$k]['outmoney'] = $aData[$k]['amount'];
            }
            if ($aData[$k]['optype'] == 0) {
                if ($aData[$k]['order_type'] == 3) {
                    $aData[$k]['ext_amount'] = $aData[$k]['amount'];
                    $aData[$k]['amount'] = null;
                }
                $aData[$k]['intmoney'] = $aData[$k]['amount'];
            }
        }
        return $aData;
    }
    
    /**
     * 对批量用户执行的操作
     * @param $aCode
     * @return array
     */
    private function _beforSendData($aCode)
    {
        $aUsername = [];
        $aCode[0] = isset($aCode[0]) ? $aCode[0] : [];
        $aCode[1] = isset($aCode[1]) ? $aCode[1] : [];
        $aCode[2] = isset($aCode[2]) ? $aCode[2] : [];
        $oUser = new model_puser();
        if (!empty($aCode[1])) {
            if (1 == count($aCode[1])) {
                return [1,count($aCode[2]), count($aCode[1]),$oUser->getUsernameByUserId($aCode[1][0])['username'], $aCode[0][0]];// 成功用户数\失败用户\用户名\错误码
            }
            foreach ($aCode[1] as $val) {
                $aTemp = $oUser->getUsernameByUserId($val);
                $aUsername[] = $aTemp['username'];
            }
            $aCode[0] = array_unique($aCode[0]);
        }
        return [2,count($aCode[2]),count($aCode[1]),implode(',',$aUsername),implode(',',$aCode[0])];
    }
}
