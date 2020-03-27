<?php

/**
 * Class controller_manualpay
 * 1 充值理赔类型为 如果充值理赔类型为1 则对应修改报表中的真实充值金额 realpayment 字段
 * 2 充值理赔类型为 3 则对应修改报表中的活动理赔金额 activity 字段
 * 3 其他理赔类型 写入报表中的充值总额 payment字段
 * 4 输入值都为fmoney  表示为最终入账金额以  $fMoney 字段为依据
 * 5 无论何种类型的写入 均将写入字段拼接入 $fMoney  order_model只接受一个字段作为变化
 * 6 在确认审核之后写入
 *
 * @author ken 2017
 * @for 人工存提
 */
class controller_manualpay extends pcontroller
{
    /**
     * 序号 1
     * 接收表单提交的数据 对应layer弹出层 人工存款
     *
     * @author ken 2017
     */
    public function actionSave()
    {
        $oProxyArt = new model_proxymanualpay();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "username" => parent::VAR_TYPE_STR,
                "getuser" => parent::VAR_TYPE_STR,
            ));

            $sUsername = isset($aGetData['username']) ? $aGetData['username'] : 0;
            $sGetuser = isset($aGetData['getuser']) ? $aGetData['getuser'] : 0;
            if (empty($sUsername) || empty($sGetuser)) {
                $this->ajaxMsg(2, '查询失败');
            }
            //是否是模糊查询
            if (isset($_POST['fuzzy']) && $_POST['fuzzy'] == 1) {
                $iFlag = $oProxyArt->getFuzzyByName(trim($aGetData['username']), $this->lvtopid);
                if ($iFlag < 0) {
                    $this->ajaxMsg(2, '查询失败');
                }
                $this->ajaxMsg(1, $iFlag );
            } else {
                $iFlag = $oProxyArt->getUseridByNames(trim($aGetData['username']), $this->lvtopid);
            }
            if ($iFlag < 0) {
                $this->ajaxMsg(2, '查询失败');
            } else {
                $sUserid = isset($iFlag['userid']) ? trim($iFlag['userid']) : 0;
                if ($sUserid == 0 || empty($sUserid)) {
                    $this->ajaxMsg(2, '查询失败');
                }
                $aResult = $oProxyArt->getUserfundByUserid($sUserid);// 数据为10秒内缓存数据
                if (!empty($aResult['userid']) && !empty($aResult['username'])) {
                    $aUserinfo['identity'] = empty($iFlag['usertype']) ? '会员' : '代理';
                    $this->ajaxMsg(1, '查询成功！', [
                        'username' => $aResult['username'],
                        'identity' => $aUserinfo['identity'], 'nickname' => $iFlag['nickname'],
                        'channelbalance' => $aResult['availablebalance'], 'userid' => $sUserid
                    ]);
                }
                $this->ajaxMsg(2, '查询失败');
            }
        }
        $GLOBALS['oView']->display("manualpay_save.html");
    }

    /**
     * 序号 2
     * 用户提款
     *
     * @author ken 2017
     */
    public function actionOut()
    {
        $oProxyArt = new model_proxymanualpay();
        /*1 获取会员资料*/
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "username" => parent::VAR_TYPE_STR,
                "getuser" => parent::VAR_TYPE_STR,
            ));
            if (isset($aGetData['getuser']) && $aGetData['getuser'] == 'out') {
                if (empty($aGetData['username'])) {
                    $this->ajaxMsg(2, '查询失败');
                }
                $iFlag = $oProxyArt->getUseridByNames($aGetData['username'], $this->lvtopid);
                if ($iFlag < 0) {
                    $this->ajaxMsg(2, '查询失败');
                } else {
                    $sUserid = isset($iFlag['userid']) ? $iFlag['userid'] : '';
                    if ($sUserid == '') {
                        $this->ajaxMsg(2, '查询失败');
                    }
                    $aResult = $oProxyArt->getUserfundByUserid($sUserid);// 数据为10秒内缓存数据
                    $aUserBankInfo = $oProxyArt->getUserBankInfoByUserId($sUserid);
                    if (!empty($aResult['userid']) && !empty($aResult['username'])) {
                        $aUserinfo['identity'] = empty($iFlag['usertype']) ? '会员' : '代理';
                        $this->ajaxMsg(1, '查询成功！', [
                            'username' => $aResult['username'],
                            'identity' => $aUserinfo['identity'], 'nickname' => $iFlag['nickname'],
                            'channelbalance' => $aResult['availablebalance'], 'userid' => $sUserid,
                            'userBankInfo' => $aUserBankInfo
                        ]);
                    }
                }

            }
        }
        $GLOBALS['oView']->display("manualpay_out.html");
    }

    /**
     * 序号 3
     * 批量存取款
     *
     * @author ken 2017
     */
    public function actionBatch()
    {
        $sLvtopid = $this->lvtopid;//总代id
        $oProxyMan = new model_proxymanualpay();
        $aResult = $oProxyMan->getUserLayer($sLvtopid);
        $aLayerName = $this->_getLayerName($aResult);
        $GLOBALS["oView"]->assign("layername", $aLayerName);
        $GLOBALS['oView']->display("manualpay_batch.html");
    }

    /**
     * 序号 4
     * 提交审核 -- 入款\扣款审核
     *
     * @author ken 2017
     */
    public function actionConfirm()
    {
        $aGetData = [];//init
        if ($this->getIsPost()) {
            switch ($this->_filterData($_POST)) {
                case -1000:
                    $this->ajaxMsg(0, '输入金额类型不正确');
                    break;
                case -1001:
                    $this->ajaxMsg(0, '输入优惠金额类型不正确');
                    break;
                case -1002:
                    $this->ajaxMsg(0, '综合打码输入类型不正确');
                    break;
                case -1003:
                    $this->ajaxMsg(0, '数据丢失，请重试');
                    break;
                case -1004:
                    $this->ajaxMsg(0, '出入款备注含有非法字符');
                case -1005:
                    $this->ajaxMsg(0, '常态性稽核倍数输入类型不正确');
                    break;
            }
            $aGetData = $this->post(array(
                'amount' => parent::VAR_TYPE_FLOAT,
                'ext_amount' => parent::VAR_TYPE_FLOAT,
                'ext_bets' => parent::VAR_TYPE_FLOAT,// 综合打码量
                'apply_remark' => parent::VAR_TYPE_STR,//提交者备注
                'order_type' => parent::VAR_TYPE_INT,//类型
                'username' => parent::VAR_TYPE_STR,
                'optype' => parent::VAR_TYPE_INT,
                'bankNumber' => parent::VAR_TYPE_STR,
                'single' => parent::VAR_TYPE_STR,
                'userid' => parent::VAR_TYPE_INT,
                'seleceM' => parent::VAR_TYPE_INT,
                'member' => parent::VAR_TYPE_STR,//用户名
                'layerid' => parent::VAR_TYPE_INT,
                'audit_bets' => parent::VAR_TYPE_FLOAT // 常态稽核
            ));
        }
        $oProxyManualpay = new model_proxymanualpay();
        if (empty($aGetData['order_type']) || 8 != $aGetData['order_type']) {//防伪造表单
            $teemp = $oProxyManualpay->getUseridByNames($aGetData['username'], $this->lvtopid);
            if (empty($teemp)) {
                $this->ajaxMsg(0, '用户名不正确');
            }
            if ($aGetData['username'] != $teemp['username']) {
                $this->ajaxMsg(0, '用户名不正确');
            }
        }
        $aGetData['apply_adminid'] = $this->loginProxyId;
        $aGetData['adminname'] = $this->adminname;
        // 根据用户的lvtopid 读取系统配置参数
        $oProxyConf = new model_proxyconfig();
        $configKey = ['money_manualpay_max_out', 'money_manualpay_max_in'];
        $aConfigValue = $oProxyConf->getConfigs($this->lvtopid, $configKey);
        $sMoney_manualpay_max_out = isset($aConfigValue['money_manualpay_max_out']) ? $aConfigValue['money_manualpay_max_out'] : '';
        $sMoney_manualpay_max_in = isset($aConfigValue['money_manualpay_max_in']) ? $aConfigValue['money_manualpay_max_in'] : '';
        $aMoneyType = isset($aGetData['optype']) ? $aGetData['optype'] : '';
        $fInMoney = isset($aGetData['amount']) ? $aGetData['amount'] : '';
        if ($aMoneyType == 0 && $fInMoney > 0 && !empty($sMoney_manualpay_max_in)) { // 入款
            if ($fInMoney > $sMoney_manualpay_max_in) {
                $this->ajaxMsg(0, "单次入款金额不能超过 {$sMoney_manualpay_max_in}");
            }
        }
        if ($aMoneyType == 1 && $fInMoney > 0 && !empty($sMoney_manualpay_max_out)) { // 出款
            if ($fInMoney > $sMoney_manualpay_max_out) {
                $this->ajaxMsg(0, "单次出款金额不能超过 {$sMoney_manualpay_max_out}");

            }
        }
        $sFlag = $oProxyManualpay->Insert($aGetData, $this->lvtopid);
        if ($sFlag >= 0) {
            $this->ajaxMsg(1,'操作成功');
        }
        switch ($sFlag) {
            case -1:
                $this->ajaxMsg(0, '备注信息不能为空');
                break;
            case -2:
                $this->ajaxMsg(0, '存入类型不能为空');
                break;
            case -3:
                $this->ajaxMsg(0, '用户名不能为空');
                break;
            case -4:
                $this->ajaxMsg(0, '变化资金不正确');
                break;
            case -5:
                $this->ajaxMsg(0, '用户名填写有错误');
                break;
            case -6:
                $this->ajaxMsg(0, '非法用户名');

                break;
            case -7:
                $this->ajaxMsg(0, '用户层级错误');
                break;
            case -8:
                $this->ajaxMsg(0, '该层级下的用户为空，请联系管理员');
                break;
            case -9:
                $this->ajaxMsg(0, '提交管理员不能为空');
                break;
            case -10:
                $this->ajaxMsg(0, '待充值用户错误，请重新核对');
                break;
            default:
                $this->ajaxMsg(0, '未知错误，请联系管理员');

                break;
        }
    }

    /**
     * 获取层级名称
     *
     * @param $aResult
     * @return array
     * @author ken 2017-07-06
     */
    private function _getLayerName($aResult)
    {
        foreach ($aResult as $kkkk => $vvvv) {
            $aLayerName[] = $aResult[$kkkk]['name'];
        }
        return $aLayerName;
    }

    /**
     * 显示用户信息
     *
     * @param $aUinfo
     * @return string
     */
    private function _setUserType($aUinfo)
    {
        if ($aUinfo == 0) {
            $aUinfo = "普通用户";
        } elseif ($aUinfo == 1) {
            $aUinfo = "VIP用户";
        } else {
            $aUinfo = "黑名单用户";
        }
        return $aUinfo;
    }

    /**
     * 增加过滤函数
     *
     * @param $aPost
     * @return int
     */
    private function _filterData($aPost)
    {
        $fAmout = isset($aPost['amount']) ? $aPost['amount'] : '';
        $fExtAmout = isset($aPost['ext_amount']) ? $aPost['ext_amount'] : '';
        $fExtBet = isset($aPost['ext_bets']) ? $aPost['ext_bets'] : '';
//        $fCheckBet = isset($aPost['checkbets']) ? $aPost['checkbets'] : '';
        $iOderType = isset($aPost['order_type']) ? $aPost['order_type'] : '';
        $iOptype = isset($aPost['optype']) ? $aPost['optype'] : '';
        $sApplayRemark = isset($aPost['apply_remark']) ? $aPost['apply_remark'] : '';
        $auditBets = isset($aPost['audit_bets']) ? trim($aPost['audit_bets']): null; //稽核倍数
//        if (empty($fAmout) || (strlen(trim($fAmout)) > 10) || !is_numeric($fAmout)) {
//            return -1000;
//        }
//        echo $iOderType;die;
        if ($iOptype == 0) {
            if(!is_numeric($fAmout)){
                return -1000;
            }
            if ($fExtAmout != '') {
                if (strlen(trim($fExtAmout)) > 10 || !is_numeric($fExtAmout)) {
                    return -1001;
                }
            }
            if (strlen(trim($fExtBet)) > 10 || !is_numeric($fExtBet)) {
                return -1002;
            }
            if (!is_numeric($iOderType) || !is_numeric($iOptype)) {
                return -1003;
            }

            //写入常态稽核时验证提交倍数
            if(!is_numeric($auditBets) || $auditBets < 0){
                return -1005;
            }

        }

//        if (!preg_match("/^[\x{4E00}-\x{9FA5}_a-zA-Z0-9]+$/iu", $sApplayRemark) || strlen($sApplayRemark) <= 8 || strlen($sApplayRemark) >= 200 || is_numeric($sApplayRemark)) {
//            return -1004;
//        }
    }


    /**
     * 增加Excel导入过滤函数
     *
     * @param $aData
     * @return bool
     */
    private function _filterExce($aData)
    {
        $fAmout = floatval($aData['amount']) ? $aData['amount'] : '';
        $fExtAmout = isset($aData['ext_amount']) ? $aData['ext_amount'] : '';
        $auditBets = isset($aData['audit_bets']) ? $aData['audit_bets'] : '';
        $fExtBet = isset($aData['ext_bets']) ? $aData['ext_bets'] : '';
        $fCheckBet = isset($aData['checkbets']) ? $aData['checkbets'] : '';
        $iOderType = isset($aData['order_type']) ? $aData['order_type'] : '';
        $iOptype = isset($aData['optype']) ? $aData['optype'] : '';
        $sApplayRemark = isset($aData['apply_remark']) ? $aData['apply_remark'] : '';
        //验证金额是否小于0
        if ($fAmout < 0) {
            return false;
        }
        if ($iOptype == 0) {
            if ($fExtAmout != '') {
                if (strlen(trim($fExtAmout)) > 10 || !is_numeric($fExtAmout)) {
                    return false;
                }
            }
            if (strlen(trim($fExtBet)) > 10 || !is_numeric($fExtBet)) {
                return false;
            }

            if (!is_numeric($fCheckBet) || !is_numeric($iOderType) || !is_numeric($iOptype)) {
                return false;
            }

            if(!is_numeric($auditBets) || $auditBets < 0){
                return false;
            }
        }
        if (empty($sApplayRemark)) {
            return false;
        }
        return true;
    }


    /**
     * 序号 5
     * excel 导入
     *
     * @author robert 2018
     */
    public function actionExcelupload()
    {
        if (!empty($_FILES['Exce']['tmp_name'])) {
            //上传文件
            $oProxyArt = new model_proxymanualpay();
            $sFileName = $_FILES['Exce']['name'];
            //验证文件格式
            $sExtend = strrchr($sFileName, '.');
            $sExtendLower = strtolower($sExtend);
            /*判别是不是.xls和.xlsx文件，判别是不是excel文件*/
            if (($sExtendLower != ".xls") && ($sExtendLower != ".xlsx")) {
                $this->error('文件格式不正确');
            }
            $upfile = $_FILES["Exce"];
            $sTmp_name = $upfile["tmp_name"];//上传文件的临时存放路径
            $sRandom = time() . rand(1, 99);//生成1到99的随机数
            move_uploaded_file($sTmp_name, dirname(__FILE__) . '/../' . $sRandom . $sExtendLower);//将上传到服务器临时文件夹的文件重新移动到新位置
            $sFile_name = dirname(__FILE__) . '/../' . $sRandom . $sExtendLower;
            $error = $upfile["error"];//上传后系统返回的值
            if ($error != 0) {
                $this->error('文件上传失败');
            }

            $aData = ParseExecl($sFile_name);//实例化excel类
            $aList = array();
            //组装输出数组
            foreach ($aData as $k => $v) {
                $aList[$k]['username'] = $v['A'];
                $aList[$k]['order_type'] = $v['B'];
                $aList[$k]['amount'] = $v['C'];
                $aList[$k]['ext_amount'] = $this->_isFigure($v['D']);
                $aList[$k]['ext_bets'] = $v['E'] !== null ? $v['E'] : $v['C'];
                $aList[$k]['audit_bets'] = $v['F'];
                $aList[$k]['checkbets'] = $v['F'] > 0 ? 1 : 0;
                $aList[$k]['apply_remark'] = !empty(trim($v['G'])) ? $v['G'] : '提交审核';
                $iFlag = $oProxyArt->getUseridByNames($aList[$k]['username'], $this->lvtopid);
                $aList[$k]['order_type'] = $this->_filterItems($aList[$k]['order_type']);//过滤前台传过来的指
//                $aList[$k]['checkbets'] = $this->_filterAudit($aList[$k]['checkbets']);//过滤前台传过来的指
                //如果是普通存入，或者活动优惠的话把优惠金额改为0
                if ($aList[$k]['order_type'] == '普通存入' || $aList[$k]['order_type'] == '活动优惠') {
                    $aList[$k]['ext_amount'] = 0;
                }
                //如果没查到直接跳出这次循环，如果没有跳出这次循环赋一个默认值
                if ($iFlag < 0) {
                    $aList[$k]['nickname'] = '查无此用户';
                    continue;
                }
                //在次验证是否查到用户，如果没有跳出这次循环赋一个默认值
                $sUserid = isset($iFlag['userid']) ? $iFlag['userid'] : '';
                if ($sUserid == '') {
                    $aList[$k]['nickname'] = '查无此用户';
                    continue;
                }
                //验证金额
                $aList[$k]['amount'] = $this->_isFigure($v['C']);
                //验证打码量稽核
                $aList[$k]['ext_bets'] = $this->_isFigure($aList[$k]['ext_bets']);
                $aResult = $oProxyArt->getUserfundByUserid($sUserid);// 数据为10秒内缓存数据
                $aList[$k]['nickname'] = $iFlag['nickname'] ? $iFlag['nickname'] : '填写错误';
                $aList[$k]['channelbalance'] = $aResult['availablebalance'] ? $aResult['availablebalance'] : '填写错误';
                $aList[$k]['identity'] = empty($iFlag['usertype']) ? '会员' : '代理';
                if (!empty($aResult['userid']) && !empty($aResult['username']) && $aList[$k]['amount'] != "-1" && $aList[$k]['ext_bets'] != "-1" && $aList[$k]['ext_amount'] != "-1") {
                    $aList[$k]['userid'] = $sUserid;
                }
            }
            unlink($sFile_name);//删除文件
            $GLOBALS['oView']->assign('list', json_encode(array_values($aList)));//把组装的数组传输到前台
        } elseif ($this->getIsPost()) {
            //提交数据
            $aData = json_decode($_POST['json_data'], true);
            if (empty($aData)) {
                $this->error('数据错误');
            }
            $iSucceed = 0;//统计成功条数
            $ifail = 0;//统计失败条数

            foreach ($aData as $k => $v) {
                //判断是否有uid有id的话表示数据是正确的
                if (!isset($v['userid'])) {
                    continue;
                }
                //常态性稽核文字转数字
//                $v['checkbets'] = $this->_filterAudit($v['checkbets'], true);
//                if ($v['checkbets'] == 0) {
//                    $ifail++;
//                    continue;
//                }
                //存入项目文字转数字
                $v['order_type'] = $this->_filterItems($v['order_type'], true);
                if ($v['order_type'] == "-1") {
                    $ifail++;
                    continue;
                }
                //如果是普通存入，或者活动优惠的话把优惠金额改为0
                if ($v['order_type'] == 0 || $v['order_type'] == 3) {
                    $v['ext_amount'] = 0;
                }
                $v['optype'] = 0;//加款
                $v['single'] = "single";//单条执行
                $bInsert = $this->batch_deposit($v);//插入
                //如果失败跳出这次循环$ifail+1，否则$iSucceed+1
                if (!$bInsert) {
                    $ifail++;
                    continue;
                }
                $iSucceed++;
            }
            $GLOBALS['oView']->assign('iSucceed', $iSucceed);//成功条数返回前台
            $GLOBALS['oView']->assign('ifail', $ifail);//失败条数返回前台
        }
        $GLOBALS['oView']->display("manualpay_excelupload.html");
    }

    /**
     * 增加过滤函数,验证是否为数字
     *
     * @param $sData
     * @return string
     */
    private function _isFigure($sData)
    {
        $s=floatval($sData);
        if (($s&&$sData==0)||($s>0)||!$sData) {
            return number_format($s, 4, '.', '');
        }
        return -1;
    }


    /**
     * 增加过滤函数,验证常态稽核参数是否合法
     *
     * @param $sData
     * @param $type
     * @return string
     */
    private function _filterAudit($sData, $type = false)
    {
        if ($type == true) {
            switch ($sData) {
                case '写入':
                    return "1";
                    break;
                case '不写入':
                    return "2";
                    break;
                default:
                    return "0";
            }
        }
        switch ($sData) {
            case 'y':
                return "写入";
                break;
            case 'n':
                return "不写入";
                break;
            default:
                return "填写错误";
        }
    }


    /**
     * 增加过滤函数,验证存入项目参数是否合法
     *
     * @param $sData
     * @return string
     */
    private function _filterItems($sData, $type = false)
    {
        if ($type == true) {
            switch ($sData) {
                case '普通存入':
                    return "0";
                    break;
                case '存款存入':
                    return "1";
                    break;
                case '活动优惠':
                    return "3";
                    break;
                default:
                    return "-1";
            }
        }
        switch ($sData) {
            case '普通存入':
                return "普通存入";
                break;
            case '存款存入':
                return "存款存入";
                break;
            case '活动优惠':
                return "活动优惠";
                break;
            default:
                return "填写错误";
        }
    }

    /**
     * 人工存入
     *
     * @param $aData
     * @return string
     */
    public function batch_deposit($aData)
    {
        if (!is_array($aData)) {
            return false;
        }
        //验证参数是否合法
        $bFilter = $this->_filterExce($aData);
        if ($bFilter == false) {
            return false;
        }
        $aGetData = $this->filtrationPost($aData, array(
            'amount' => parent::VAR_TYPE_FLOAT,
            'ext_amount' => parent::VAR_TYPE_FLOAT,
            'ext_bets' => parent::VAR_TYPE_FLOAT,// 综合打码量
            'apply_remark' => parent::VAR_TYPE_STR,//提交者备注
            'checkbets' => parent::VAR_TYPE_INT, // 常态稽核
            'order_type' => parent::VAR_TYPE_INT,//类型
            'username' => parent::VAR_TYPE_STR,
            'optype' => parent::VAR_TYPE_INT,
            'bankNumber' => parent::VAR_TYPE_STR,
            'single' => parent::VAR_TYPE_STR,
            'userid' => parent::VAR_TYPE_INT,
            'seleceM' => parent::VAR_TYPE_INT,
            'member' => parent::VAR_TYPE_STR,//用户名
            'layerid' => parent::VAR_TYPE_INT,
            'audit_bets' => parent::VAR_TYPE_FLOAT //常态稽核
        ));

        $aGetData['optype'] = isset($aData['optype']) ? $aData['optype'] : '';
        $aGetData['apply_adminid'] = $this->loginProxyId;
        $aGetData['adminname'] = $this->adminname;
        $oProxyManualpay = new model_proxymanualpay();
        $sFlag = $oProxyManualpay->Insert($aGetData, $this->lvtopid);
        if ($sFlag < 0) {
            return false;
        }
        return true;
    }

}
