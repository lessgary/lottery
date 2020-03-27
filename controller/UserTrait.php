<?php
/**
 * @desc user代码复用trait 包含用户相关逻辑处理
 * @author rhovin
 * @date 2017-06-08
 *
 */
trait UserTrait {
    private $_errMsg;
    /**
     * @desc 检查表单提交数据
     * @author rhovin
     * @date 2017-06-10
     */
    private function _checkForm($aData){
        if($this->_errMsg == NULL) {
            try {
                if(isset($aData['username']) && $aData['username'] != "") {
                    if (FALSE == model_user::checkUserName($aData['username'])) { 
                        throw new Exception("登录用户不符合规则，请重新输入6-16位数字字母组合");
                    }
                }
                if(isset($aData['loginpwd']) && $aData['loginpwd'] != "" ) {
                    if (FALSE == model_user::checkUserPass($aData['loginpwd'])) { 
                        throw new Exception("登录密码不符合规则，请重新输入6-16位数字字母组合");
                    }
                }
                if(isset($aData['securitypwd']) && $aData['securitypwd'] != "" ) {
                    if (FALSE == model_user::checkUserPass($aData['securitypwd'])) { 
                        throw new Exception("资金密码不符合规则，请重新输入6-16位数字字母组合");
                    }
                }
                if(isset($aData['nickname']) && $aData['nickname'] != ""){
                    if (FALSE == model_user::checkNickName($aData['nickname'])) { 
                        throw new Exception("呢称不符合规则，请重新输入2-15个任意字符");
                    }
                }
                if(isset($aData['realname']) && $aData['realname'] != "") {
                    if (FALSE == model_user::checkNickName($aData['realname'])) { 
                        throw new Exception("真实姓名不符合规则，请重新输入");
                    }
                }
                if(isset($aData['mobile']) && $aData['mobile'] != "") {
                    if (FALSE == model_user::checkMobilePhone($aData['mobile'])) { 
                        throw new Exception("手机号码不符合规则，请重新输入");
                    }
                }
                if(isset($aData['email']) && $aData['email'] != "") {
                    if (FALSE == model_user::checkEmail($aData['email'])) { 
                        throw new Exception("邮箱地址不符合规则，请重新输入");
                    }
                }
                if(isset($aData['qq']) && $aData['qq'] != "") {
                    if (FALSE == model_user::checkQQ($aData['qq'])) { 
                        throw new Exception("QQ号码不符合规则，请重新输入");
                    }
                }
                return TRUE;
            } catch (Exception $e) {
               $this->_errMsg = $e->getMessage();
               return FALSE ;
            }
        }
        return FALSE ;
        
    }
    /**
     * @desc 两数之间按点差计算范围
     * @author rhovin
     * @date 2017-06-10
     * @max 最大值 min 最小值 dvalue 点差
     */
	private function _adjustPoint($max, $min, $dvalue=0.001){
        $aValueRange = [] ;
        for($i = 1 ; $i<=floor($max/$dvalue) ; $i++) {
            //如果存val递减点差
            $val =isset($aValueRange[$i-1]['val']) ? $aValueRange[$i-1]['val']-$dvalue : $max-$dvalue;
            $aValueRange[$i] = [
                        "val"=>$val,
                        "str"=>number_format($val*100, 1).'%------'. ($val*2000+1800),
                        "extendStr"=>number_format($val, 1).'%------'.($val*20+1800)
                    ];
            //判断下级最大返点是否为0
            if (isset($min) && $min != 0 ) {
                if($aValueRange[$i]['val'] < ($min+$dvalue )) break ;
            } else {
                  if($aValueRange[$i]['val'] < 0) {
                       $aValueRange[$i] = ["val"=>0, "str"=>'0------1800'];
                       break;
                  }
            }
        }
        return $aValueRange;
    }

	/**
     * @desc 统一构造where条件
     * @author rhovin
     * @date 2017-06-01
     */
    private function _getWhereStr($aGetData) {
        $sWhere = "";
        //用户名
        if (count(explode(',',$aGetData[$aGetData['searchType']]))>1){
            $name='(';
            foreach (explode(',',$aGetData[$aGetData['searchType']]) as $V){
                $name.="'{$V}'".',';
            }
            $name=rtrim($name,',').')';
            $sWhere .= " AND u.username in ".$name;
            if($aGetData['searchType']!='username'){
                unset($aGetData[$aGetData['searchType']]);
            }
        }else if ((isset($aGetData['username']) && $aGetData['username'] != '' && $aGetData['searchType'] == 'username')
            || (isset($aGetData['usernamea']) && !empty($aGetData['usernamea']))) {
            if (!empty($aGetData['usernamea'])) {
                if (preg_match("/\\n/", $aGetData['usernamea'])) {
                    $names = explode("\n", $aGetData['usernamea']);
                    $names = array_map(function($n){
                        return "'{$n}'";
                    }, $names);
                    $name = implode(',', $names);
                } else {
                    $name = "'{$aGetData['usernamea']}'";
                }

                $sWhere .= " AND u.`username` in ({$name}) ";
                return $sWhere;
            }else {
                $sWhere .= " AND u.`username` = '" . $aGetData['username'] . "'";
            }
        }
        // 所属上级
        if (isset($aGetData['ufather']) && $aGetData['ufather'] != ''  && $aGetData['searchType'] == 'ufather') {
            $sWhere .= " AND ut2.`username`='{$aGetData['ufather']}'";
        }
        //手机号
        if (isset($aGetData['mobile']) && $aGetData['mobile'] != 0) {
            $sWhere .= " AND u.`mobile`='{$aGetData['mobile']}'";
        }

        //用户真实姓名
        if (isset($aGetData['rname']) && $aGetData['rname'] != '') {
            $sWhere .= " AND  u.`realname` = '" . $aGetData['rname'] . "'";
        }
        if (isset($aGetData['realname']) && $aGetData['realname'] != '') {
            $sWhere .= " AND  cbi.`realname` = '" . $aGetData['realname'] . "'";
        }
        //用户组       
        if (isset($aGetData['team']) && $aGetData['team']> 0) {
            if($aGetData['team'] == 2) {
                $sWhere .=" AND u.`usertype`=1 AND ut.`userid`=ut.`lvproxyid`";
            } elseif ($aGetData['team'] == 3) {
                $sWhere .=" AND u.`usertype`=1";
            } elseif($aGetData['team'] == 4) {
                $sWhere .=" AND u.`usertype`=0";
            }
            
        }
        //总代ID
        if (isset($aGetData['lvtopid']) && $aGetData['lvtopid']> 0) {
            $sWhere .=" AND ut.`lvtopid`='" . $aGetData['lvtopid'] . "'";
        }
         //身份
        if (isset($aGetData['identity']) && $aGetData['identity']> 0) {
            $sWhere .=" AND ut.`identity`='" . $aGetData['identity'] . "'";
        }
         //层级id
        if (isset($aGetData['layerid']) && $aGetData['layerid'] >= 0 ) {
            $sWhere .=" AND u.`layerid`='" . $aGetData['layerid'] . "'";
        }
        //是否冻结
        if (isset($aGetData['isfrozen']) && $aGetData['isfrozen'] == 1) {
            $sWhere .=" AND ut.`isfrozen`=0";
        }elseif (isset($aGetData['isfrozen']) && $aGetData['isfrozen'] == 2) {
            $sWhere .=" AND ut.`isfrozen`>0";
        }
        //账号余额
        if (isset($aGetData['minmoney']) && $aGetData['minmoney'] > 0.00) {
            $sWhere.=" AND uf.`channelbalance`>=" . $aGetData['minmoney'];
        }
        if (isset($aGetData['maxmoney']) && $aGetData['maxmoney'] > 0.00) {
            $sWhere.=" AND uf.`channelbalance`<" . $aGetData['maxmoney'];
        }
        //充值金额
        if (isset($aGetData['minrechage']) && $aGetData['minrechage'] > 0.00) {
            $sWhere.=" AND u.`loadmoney`>=" . $aGetData['minrechage'];
        }
        if(isset($aGetData['maxrechage']) && $aGetData['maxrechage'] > 0.00) {
            $sWhere.=" AND u.`loadmoney`<" . $aGetData['maxrechage'];
        }
        //可用余额
        if (isset($aGetData['minavailamoney']) && $aGetData['minavailamoney'] > 0.00) {
            $sWhere.=" AND uf.`availablebalance`>=" . $aGetData['minavailamoney'];
        }
        if (isset($aGetData['maxavailamoney']) && $aGetData['maxavailamoney'] > 0.00) {
            $sWhere.=" AND uf.`availablebalance`<" . $aGetData['maxavailamoney'];
        }
         //冻结余额
        if (isset($aGetData['minholdmoney']) && $aGetData['minholdmoney'] > 0.00) {
            $sWhere.=" AND uf.`holdbalance`>=" . $aGetData['minholdmoney'];
        }
        if (isset($aGetData['maxholdmoney']) && $aGetData['maxholdmoney'] > 0.00) {
            $sWhere.=" AND uf.`holdbalance`<=" . $aGetData['maxholdmoney'];
        }
        //注册时间
        if (isset($aGetData['regstarttime']) && $aGetData['regstarttime'] != '') {
            $sWhere.=" AND u.`registertime`>'" . $aGetData['regstarttime'] . "'";
        }
        if (isset($aGetData['regendtime']) && $aGetData['regendtime'] != '') { 
            $sWhere.=" AND u.`registertime`<'" . $aGetData['regendtime'] . "'";
        }
        //最后登录时间
        if (isset($aGetData['startlasttime']) && $aGetData['startlasttime'] != '') {
            $sWhere.=" AND u.`lasttime`>='" . $aGetData['startlasttime'] . "'";
        }
        if (isset($aGetData['endlasttime']) && $aGetData['endlasttime'] != '') { 
            $sWhere.=" AND u.`lasttime`<='" . $aGetData['endlasttime'] . "'";
        }
        //银行卡号
        if(isset($aGetData['cardno']) && $aGetData['cardno'] != '' ) {
            $sWhere .= " AND `cardno` LIKE '" . str_replace("*", "%", $aGetData['cardno']) . "'";    
        }
        //银行卡是否删除
        if(isset($aGetData['isdel_bank']) && $aGetData['isdel_bank'] >= 0 ) {
            $sWhere .= " AND `isdel` = '" . $aGetData['isdel_bank'] . "'";   
        }
        //是否黑名单
        if(isset($aGetData['isblack_bank']) && $aGetData['isblack_bank'] >= 0 ) {
            $sWhere .= " AND `isblack` = '" . $aGetData['isblack_bank'] . "'";   
        }
        return $sWhere;
    }
    /**
     * @desc proxy_apply_confirm 用户安全信息审核where条件构造
     * @author rhovin
     * @date 2017-06-26
     */
    private function getReviewWhere($aGetData) {
         $sWhere = "";
        //用户名
        if (isset($aGetData['username']) && $aGetData['username'] != '') {
            $sWhere .= " AND u.`username` = '" . $aGetData['username'] . "'";
        }
        //类型
        if (isset($aGetData['type']) && $aGetData['type'] > -1) {
            $sWhere .= " AND pac.`type` = '" . $aGetData['type'] . "'";
        }
        //审核状态
        if (isset($aGetData['isconfirm']) && $aGetData['isconfirm'] > -1) {
            $sWhere .= " AND pac.`isconfirm` = '" . $aGetData['isconfirm'] . "'";
        }
        //提交人
        if (isset($aGetData['applyname']) && $aGetData['applyname'] != "") {
            $sWhere .= " AND p.`adminname` = '" . $aGetData['applyname'] . "'";
        }
        //审核人
        if (isset($aGetData['confirmname']) && $aGetData['confirmname'] != "") {
            $sWhere .= " AND pu.`adminname` = '" . $aGetData['confirmname'] . "'";
        }
        //提交时间
        if (isset($aGetData['starttime']) && $aGetData['starttime'] != '') {
            $sWhere.=" AND pac.`inserttime`>='" . $aGetData['starttime'] . "'";
        }
        if (isset($aGetData['endtime']) && $aGetData['endtime'] != '') { 
            $sWhere.=" AND pac.`inserttime`<='" . $aGetData['endtime'] . "'";
        }
        return $sWhere;
    }
}