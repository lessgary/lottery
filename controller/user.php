<?php

/**
 * @desc 功能: 控制器 - 用户管理
 * 
 * @author    rhovin
 * @package   proxyweb
 * @date 2017-05-27
 */
include "UserTrait.php";
class controller_user extends pcontroller {
    use UserTrait;
    public $user_point_gap=0.001 ;//点差

    //构造函数
    public function __construct() {
        parent::__construct();
    }


    /**
     * @param $aRes
     * @param $sWhere
     * 拼装获取在线时间以及在线状态查询SQL语句
     * @return string
     */
    private function setWhereSql($aRes,$sWhere){
        if(empty($aRes)){
            return $sWhere;
        }
        $aRes['lastonline_day']=intval($aRes['lastonline_day']);
        //如果搜索条件不为空,拼接SQL语句
        if($aRes['lastonline_day'] != '' || $aRes['lastonline_day'] != 0){
            switch ($aRes['lastonline'])
            {
                case 3: //如果条件等于不限
                    break;
                case '='://如果搜索条件是等于
                    $iToday=date('Y-m-d H:i:s',strtotime(date('Y-m-d',strtotime("-".$aRes['lastonline_day']."days"))));
                    $iEndToday = date('Y-m-d H:i:s',strtotime($iToday)+60*60*24);
                    $sWhere .= " AND u.`lasttime` between '".$iToday."' and '$iEndToday'";
                    break;
                default://如果搜索条件是大于或者小于
                    $iToday=date('Y-m-d H:i:s',strtotime(date('Y-m-d',strtotime("-".$aRes['lastonline_day']."days"))));
                    $sWhere .= " AND u.`lasttime` ".$aRes['lastonline']." '$iToday'";
                    break;
            }
        }
        if(isset($aRes['isonline']) && in_array($aRes['isonline'],[1,2])){//拼接是否在线查询语句 1在线 2离线 不在此范围内不做处理
            $iNowTime=date('Y-m-d H:i:s',time()-300);//
            $sWhere .= $aRes['isonline'] == 1?" AND u.`last_access_time` > '$iNowTime'":" AND u.`last_access_time` < '$iNowTime'";//根据条件拼接SQL
        }
        return $sWhere;
    }


    /**
     * @param $aArray
     *
     * @return mixed
     * 获取用户上次在线时间
     */
    private function getLastDay($aArray){
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb',$GLOBALS['aSysMemCacheServer']);
        $aResult = $oMemCache->get('userOnlineResult');
        foreach ($aArray as $k=>&$v){ //给数组添加一个最后登录时间
            if(strtotime($v['lasttime']) >0){   //如果曾经登陆过
                $iToday=strtotime(date('Y-m-d',strtotime($v['lasttime'])));
                $iNum=floor((time()-$iToday)/(60*60*24));
                $aArray[$k]['lastloginday'] = $iNum == 0?'今天':$iNum.'天前';
            }else{
                $aArray[$k]['lastloginday'] = '未登录过';

            }

            $aArray[$k]['onlineStatus'] = strtotime($v['last_access_time']) > time() - 300 ? '在线' : '离线';
        }

        return $aArray;
    }



    /**
     * @desc 用户列表
     * @author rhovin
     * @date 2017-05-27
     */
    public function actionMainList(){   
        $oPuserModel = new model_puser();  
        $oUserLayer = new model_userlayer();
        $aUserLayer = $oUserLayer->getOnliyLayerList($this->lvtopid);
        $aDataArray=array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "sidx" => parent::VAR_TYPE_STR,
                "sord" => parent::VAR_TYPE_STR,
                "username" => parent::VAR_TYPE_STR,
                "searchType" => parent::VAR_TYPE_STR,
                "team" => parent::VAR_TYPE_INT,
                "layerid" => parent::VAR_TYPE_INT, 
                "lvtopid" => parent::VAR_TYPE_INT, 
                "isfrozen" => parent::VAR_TYPE_INT, 
                "regstarttime" => parent::VAR_TYPE_DATETIME,
                "regendtime" => parent::VAR_TYPE_DATETIME,
                "moneytype" => parent::VAR_TYPE_STR,
                'ufather' => parent::VAR_TYPE_STR,
                'usernamea' => parent::VAR_TYPE_STR,
                "isonline" => parent::VAR_TYPE_INT,
                "lastonline" => parent::VAR_TYPE_STR,
                'lastonline_day' => parent::VAR_TYPE_INT,
            );
        if ($this->getIsPost()) {
            $aGetData = $this->post($aDataArray);
            $aRangeData = $this->post(array(
                "minmoney" => parent::VAR_TYPE_FLOAT, //最小账号余额
                "maxmoney" => parent::VAR_TYPE_FLOAT, //最大账号余额
            ));
            $aGetData["min" . $aGetData["moneytype"]] = $aRangeData["minmoney"];
            $aGetData["max" . $aGetData["moneytype"]] = $aRangeData["maxmoney"];
            unset($aRangeData);
            $aGetData['lvtopid'] = $this->lvtopid;
            //构造where条件
            $sWhere = $this->_getWhereStr($aGetData);
            $sWhere = $this->setWhereSql($aGetData,$sWhere);
            //order by
            $sOrderBy = $aGetData['sidx'] == '' ? "u.`userid` ${aGetData['sord']}" :"${aGetData['sidx']} ${aGetData['sord']}";
            $aUserData = $oPuserModel->getChildList($this->lvtopid, "", $sWhere, $sOrderBy, $aGetData['rows'], intval($aGetData['page']));
            $aUserData['results'] = $this->getLastDay( $aUserData['results']);
            $aLayer = [] ;
            foreach ($aUserLayer as $key => $value) {
                $aLayer[$value['layerid']] = $value['name'];
            }
            $aUserInfo = $oPuserModel->getUserTree($this->lvtopid);
            foreach ($aUserInfo as $v){
                $aUserStr[$v['userid']] = $v;
            }
            if (!empty($aUserData) && !empty($aGetData)) {
                //加入用户层级
                foreach ($aUserData['results'] as $key => &$value) {
                    $value['sTime'] = date('Y-m-01 00:00:00' ,strtotime('-2 month'));
                    $value['name'] = isset($aLayer[$value['layerid']]) ? $aLayer[$value['layerid']] : "";
                    $value['parentName'] = $aUserStr[$value['parentid']]['username'];
                    //格式化金额
                    $value['availablebalance'] = numberFormat2($value['availablebalance']);
                    $value['channelbalance'] = numberFormat2($value['channelbalance']);
                    $value['loadmoney'] = numberFormat2($value['loadmoney']);

                    //代理等级
                    if($value['usertype'] == 1) {
                        $value['proxylevel'] = $oPuserModel->getProxyLevel(substr_count($value['parenttree'],','));
                    }else{
                        $value['proxylevel'] = "普通会员";
                    }
                }
                $this->outPutJQgruidJson($aUserData['results'],$aUserData['affects'] , $aGetData['page'], $aGetData['rows']);
            }
        } else {
            $aGetDataArray = $this->get($aDataArray);//请求过来的get参数和$aDataArray里面下标对应传入前台
            $oUserGroup = new model_usergroup();
            $aUserGroups = $oUserGroup->getList(array('groupid', 'groupname')," groupid in(2,3,4)");
            $GLOBALS["oView"]->assign("getDataArray",$aGetDataArray);//把get过来的 参数传入到前台
            $GLOBALS['oView']->assign("usergroup", $aUserGroups);
            $GLOBALS['oView']->assign("userlayer", $aUserLayer);
            $GLOBALS['oView']->display('user_mainlist.html');
        }
    }

    public function actionChangParent() {
        if ($this->getIsGet()) {
            $iUserId = isset($_GET['userid']) ? intval($_GET['userid']) : 0;
            $oUser = new model_user();
            $aUserTree = $oUser->getUserTreeInfo($iUserId);
            $iParentId = $aUserTree['parentid'];
            $aParentInfo = $oUser->getUserInfo($iParentId, array('username', 'usertype', 'maxpoint'));
            if (!$aParentInfo) {
                sysMessage("不存在上级！", 1);
            }
            $GLOBALS['oView']->assign('parent', $aParentInfo);
            $GLOBALS['oView']->display('user_changparent.html');
        } elseif($this->getIsPost()) {
            $isSearch = isset($_POST['is_search']) ? $_POST['is_search'] : 0;
            if ($isSearch) {
                $keyword = isset($_POST['key_word']) ? trim($_POST['key_word']) : '';
                if (empty($keyword)) {
                    die(json_encode(['error' => -1, 'msg' => '参数错误！']));
                }
                $oUserTree = new model_usertree();
                if ($isSearch == 2){
                    $aUser = $oUserTree->searchUserByUserNameFuzzy($this->lvtopid, $keyword, 'parentid >0 and isdeleted=0 and usertype=1');
                    $aUserNew = array();
                    if ($aUser && !empty($aUser)) {
                        foreach ($aUser as $k=>$v){
                            if (!$aUser[$k]['istester']) {
                                $aUserNew[$v['userid']] = $aUser[$k];
                            }
                        }
                        $_SESSION['to-change-proxy'] = $aUserNew;
                        die(json_encode(['error' => 0, 'msg' => "已找到",'data' => $aUserNew]));
                    }
                }else{
                    $aUser = $oUserTree->searchUserByUserName($this->lvtopid, $keyword, 'parentid >0 and isdeleted=0 and usertype=1');
                    if ($aUser && !empty($aUser['userid'])) {
                        $oPuserModel = new model_puser();
                        $sLv = $oPuserModel->getProxyLevel(substr_count($aUser['parenttree'],','));
                        if ($aUser['istester']) {
                            die(json_encode(['error' => -2, 'msg' => "测试账号不支持修改上级"]));
                        }
                        $_SESSION['to-change-proxy'] = $aUser;
                        die(json_encode(['error' => 0, 'msg' => "已找到{$sLv}: {$aUser['username']}"]));
                    }
                }
                die(json_encode(['error' => -2, 'msg' => '代理账号不存在！']));
            } else {
                $aData = $this->assemblyData($_POST);
                $oPuser = new model_puser();
                $bResult = $oPuser->addReview($aData);
                if ($bResult) {
                    die(json_encode(['error' => 0, 'msg' => '提交成功，修改上级需要等待安全信息审核']));
                } else {
                    die(json_encode(['error' => -1, 'msg' => '添加失败']));
                }
            }
        } else {
            sysMessage('非法请求！', 1);
        }
    }

    private function assemblyData($post){
        if (empty($_SESSION['to-change-proxy'])) {
            die(json_encode(['error' => -2, 'msg' => '代理账号不存在！']));
        }
        $userid = isset($post['userid']) ? intval($post['userid']) : 0;
        $parentid = isset($post['parentid']) ? intval($post['parentid']) : 0;
        $parentname = isset($post['parent_name']) ? trim($post['parent_name']) : '';
        if (empty($userid) || empty($parentid)) {
            die(json_encode(['error' => -1, 'msg' => '参数错误！']));
        }
        if (isset($post['newuerid'])){
            $aSessionData = $_SESSION['to-change-proxy'][$post['newuerid']];
        }else{
            $aSessionData = $_SESSION['to-change-proxy'];
        }
        $aData = [
            'lvtopid' => $this->lvtopid,
            'userid' => $userid,
            'apply_adminid' => $_SESSION['proxyadminid'],
            'apply_ip' => getRealIP(),
            'apply_info' => "上级代理由【{$parentname}】修改为【{$aSessionData['username']}】",
            'apply_data' => serialize(['userid'=> $userid,'parent_id'=>$parentid, 'new'=>intval($aSessionData['userid'])]),
            'type' => 6,
            'inserttime' => date('Y-m-d H:i:s')
        ];
        return $aData;
    }

    /**
     * desc导出用户列表
     * 2017-08-07
     */
    public function actionExportUsers() {
//        $_GET = json_decode($_GET['getData'],TRUE);
        $oPuserModel = new model_puser();  
        $oUserLayer = new model_userlayer();
        $aGetData = $this->post(array(
            "page" => parent::VAR_TYPE_INT,
            "rows" => parent::VAR_TYPE_INT,
            "sidx" => parent::VAR_TYPE_STR,
            "sord" => parent::VAR_TYPE_STR,
            "username" => parent::VAR_TYPE_STR,
            "searchType" => parent::VAR_TYPE_STR,
            "team" => parent::VAR_TYPE_INT,
            "layerid" => parent::VAR_TYPE_INT,
            "lvtopid" => parent::VAR_TYPE_INT,
            "isfrozen" => parent::VAR_TYPE_INT,
            "regstarttime" => parent::VAR_TYPE_DATETIME,
            "regendtime" => parent::VAR_TYPE_DATETIME,
            "moneytype" => parent::VAR_TYPE_STR,
            'ufather' => parent::VAR_TYPE_STR,
            "isonline" => parent::VAR_TYPE_INT,
            "lastonline" => parent::VAR_TYPE_STR,
            'lastonline_day' => parent::VAR_TYPE_INT,
        ));
        $aRangeData = $this->post(array(
                "minmoney" => parent::VAR_TYPE_FLOAT, //最小账号余额
                "maxmoney" => parent::VAR_TYPE_FLOAT, //最大账号余额
            ));
        $aGetData["min" . $aGetData["moneytype"]] = $aRangeData["minmoney"];
        $aGetData["max" . $aGetData["moneytype"]] = $aRangeData["maxmoney"];
        $aGetData['lvtopid'] = $this->lvtopid;
        //构造where条件
        $sWhere = $this->_getWhereStr($aGetData);
        $sWhere = $this->setWhereSql($aGetData,$sWhere);
        //order by
        $sOrderBy = $aGetData['sidx'] == '' ? "u.`userid` ${aGetData['sord']}" :"${aGetData['sidx']} ${aGetData['sord']}";
        $aUserData = $oPuserModel->getChildList($this->lvtopid, "", $sWhere, $sOrderBy, 10000, intval($aGetData['page']));

        $aUserData['results'] = $this->getLastDay( $aUserData['results']);
        $aLayer = [] ;
        $aUserLayer = $oUserLayer->getOnliyLayerList($this->lvtopid);
        foreach ($aUserLayer as $key => $value) {
            $aLayer[$value['layerid']] = $value['name'];
        }
        $aStatus = [
            0 => '启用',
            1 => '完全冻结',
            2 => '可登录,查看帮助中心,不可投注,不可充提',
            3 => '不可投注,可充提,查看用户列表和报表,帮助中心'
        ];
        if (!empty($aUserData) && !empty($aGetData)) {
            //加入用户层级
            foreach ($aUserData['results'] as $key => &$value) {
                $value['sTime'] = date('Y-m-01 00:00:00' ,strtotime('-2 month'));
                $value['name'] = isset($aLayer[$value['layerid']]) ? $aLayer[$value['layerid']] : "";
                //代理等级
                if($value['usertype'] == 1) {
                    $value['proxylevel'] = $oPuserModel->getProxyLevel(substr_count($value['parenttree'],','));
                } else {
                    $value['proxylevel'] = "普通会员";
                }
                $value['isfrozen'] = array_key_exists($value['frozentype'], $aStatus) ? $aStatus[$value['frozentype']] : '';
            }
        }
        $expTitle  = "User";
        $expCellName  = [
            ['username','用户名'],
            ['proxylevel','所属组'],
            ['name','用户层级'],
            ['parentName','所属上级'],
            ['maxpoint','用户返点'],
            ['availablebalance','可用余额'],
            ['channelbalance','账号余额'],
            ['holdbalance','未结算余额'],
            ['loadmoney','累计充值'],
            ['totalbets','累计投注'],
            ['lastloginday','上次在线时间'],
            ['registertime','注册日期'],
            ['isfrozen','用户状态'],
        ];
       ExportExcel($expTitle, $expCellName , $aUserData['results']);
    }
    /** 
     * @desc 用户配额设置
     * @author rhovin
     * @date 2017-06-22
     */
    public function actionAccQuota() {
        $iUserId = isset($_GET['userid']) ? intval($_GET['userid']) : 0 ;
        $oUser = A::singleton("model_puser");
        if($this->getIsPost()) {
            if(is_array($_POST['quotaval'])) {
                $aQuoTa = $_POST['quotaval'];
                $aParams = [];
                $aData   = []; // 账变信息记录数组
                $sSt  = 8; // 8 系统扣减，9 系统增加
                $iNum = 0; // 增减幅度
                foreach ($aQuoTa as $key => $value) {
                    if($value === '' ) continue ;
                    $value = intval($value); // 放在if之后，不然为空的会编程 0 ，导致其他问题
                    $aUserQuota = '';
                    $aUserQuota = $oUser->getUserQuota(intval($_POST['userid']),$key/100,$this->lvtopid);
                    if($aUserQuota) {
                        if($value < $aUserQuota['left_quota']) {
                            $total_quota = $aUserQuota['total_quota'] - ($aUserQuota['left_quota']-$value);
                            $sSt = 8;
                            $iNum = $aUserQuota['left_quota'] - $value;
                        } else {
                            $total_quota =  $aUserQuota['total_quota'] + ($value - $aUserQuota['left_quota']);
                            $sSt = 9;
                            $iNum = $value - $aUserQuota['left_quota'];
                        }
                        $aParams[] = [
                            "userid"=>intval($_POST['userid']),
                            "lvtopid"=>$this->lvtopid,
                            "point"=>$key/100,
                            "total_quota"=>$total_quota,
                            "left_quota"=>$value,
                            "updatetime"=>date("Y-m-d H:i:s")
                        ];
                        $aData[] = [
                            'userid'    => intval($_POST['userid']),
                            'name'      => $key/100,
                            'original'  => $aUserQuota['left_quota'],
                            'current'   => $value,
                            'amount'    => $iNum,
                            'relativeid'=> 0,
                            'type'      => $sSt,
                            'adminid'   => $_SESSION['proxyadminid'],
                            'monthname' => date('Y-m'),
                            'times'     => date('Y-m-d H:i:s')
                        ];
                    }else {
                        $aParams[] = [
                            "userid"=>intval($_POST['userid']),
                            "lvtopid"=>$this->lvtopid,
                            "point"=>$key/100,
                            "total_quota"=>$value,
                            "left_quota"=>$value,
                            "updatetime"=>date("Y-m-d H:i:s")
                        ];
                        $aData[] = [
                            'userid'    => intval($_POST['userid']),
                            'name'      => $key/100,
                            'original'  => 0,
                            'current'   => $value,
                            'amount'    => $value,
                            'relativeid'=> 0,
                            'type'      => 9,
                            'adminid'   => $_SESSION['proxyadminid'],
                            'monthname' => date('Y-m'),
                            'times'     => date('Y-m-d H:i:s')
                        ];
                    }
                }
                /* @var $oRecord model_useraccquota */
                $oRecord = A::singleton('model_useraccquota');
                $mResult = $oRecord-> insertQuota($aParams);
                $bRe = true;
                foreach ($aData as $k => $v){
                    $re = $oRecord->insertRecord($v);
                    if ($re !== $bRe){
                        sysMessage("操作失败", 1);
                    }
                }
                if($mResult) {
                    sysMessage("操作成功");
                }else{
                    sysMessage("操作失败", 1);
                }
            }
        }
        $aUserTree = $oUser->getUserTreeInfo($iUserId,['username','lvtopid']);
        if($aUserTree['lvtopid'] != $this->lvtopid) sysMessage("非法操作", 1);
        $aUser = $oUser->getUserInfo($iUserId,['userid','username','maxpoint']);
        $oProxyConfig = new model_proxyconfig();
        $min = $oProxyConfig->getConfigs($this->lvtopid,"registered_promotion_point");//最小无需配额返点
        if(empty($min)) $min = 0;
        $dvalue="0.001";
        $iMax = $aUser['maxpoint'];
       /// $sSame = $oProxyConfig->getConfigs($_SESSION['lvtopid'], 'registered_samegrade'); // 客户允许上下级返点相同，点差为0
        $iMax = $aUser['maxpoint'] + $dvalue;
        $aPoint = $this->_adjustPoint($iMax * 1000, $min*1000 , $dvalue*1000);
        foreach ($aPoint as $key => &$value) {
            $value['val'] = bcdiv($value['val'],10,1);
            $aUserQuota = $oUser->getUserQuota($iUserId,$value['val']/100,$this->lvtopid);
            if(!empty($aUserQuota )){
               $value['total_quota'] = $aUserQuota['total_quota']; 
               $value['left_quota'] = $aUserQuota['left_quota']; 
            }else{
                $value['total_quota'] = 0;
                $value['left_quota'] = 0;
            }
        }
        $GLOBALS['oView']->assign("user", $aUser);
        $GLOBALS['oView']->assign("point", $aPoint);
        $GLOBALS['oView']->display('user_accquota.html');
    }
    /**
     * @desc 用户基本信息列表
     * @author rhovin
     * @date 2017-06-01
     */
    public function actionInfoList(){
        $oPuserModel = new model_puser();  
        $oUserLayer = new model_userlayer();
        $aUserLayer = $oUserLayer->getOnliyLayerList($this->lvtopid);
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "username" => parent::VAR_TYPE_STR,
                "searchType" => parent::VAR_TYPE_STR,
                "team" => parent::VAR_TYPE_INT,
                "layerid" => parent::VAR_TYPE_INT, 
                "regstarttime" => parent::VAR_TYPE_DATETIME,
                "regendtime" => parent::VAR_TYPE_DATETIME,
                'usernamea' => parent::VAR_TYPE_STR,
                'mobile' => parent::VAR_TYPE_STR,
                'ufather' => parent::VAR_TYPE_STR,
                'rname' => parent::VAR_TYPE_STR,
                "lastonline" => parent::VAR_TYPE_STR,
                'lastonline_day' => parent::VAR_TYPE_INT,
            ));
            $aLayer = [] ;
            foreach ($aUserLayer as $key => $value) {
                $aLayer[$value['layerid']] = $value['name'];
            }
            $sWhere = $this->_getWhereStr($aGetData);
            $sWhere = $this->setWhereSql($aGetData,$sWhere);
            $aUserData = $oPuserModel->getChildList($this->lvtopid, "", $sWhere, "", $aGetData['rows'], intval($aGetData['page']));
            $aUserData['results'] = $this->getLastDay( $aUserData['results']);
            if (!empty($aUserData) && !empty($aGetData)) {
                $aUserInfo = $oPuserModel->getUserTree($this->lvtopid);
                foreach ($aUserInfo as $v){
                    $aUserStr[$v['userid']] = $v;
                }
                foreach ($aUserData['results'] as $key => &$value) {
                    //最后登录时间
                    $value['lasttime'] = $value['lasttime'] != '1970-01-01 00:00:00' ? $value['lasttime'] : $value['registertime'];
                    //上级代理
                    $value['parentName'] = $aUserStr[$value['parentid']]['username'] ? $aUserStr[$value['parentid']]['username'] : "";
                    //离开天数
                    $time = time()-($value['lasttime']!='1970-01-01 00:00:00' ? strtotime($value['lasttime']) :time() );
                    $time = $time > 24*3600 ? $time : 0;
                    $value['awaydays'] = ceil($time/(24*3600));
                    //加入用户层级
                    $value['name'] = isset($aLayer[$value['layerid']]) ? $aLayer[$value['layerid']] : "";
                    $value['channelbalance'] = numberFormat2($value['channelbalance']);//格式化金额
                    //代理等级
                    if($value['usertype'] == 1) {
                        $value['proxylevel'] = $oPuserModel->getProxyLevel(substr_count($value['parenttree'],','));
                    } else {
                        $value['proxylevel'] = "普通会员";
                    }
                }
                $this->outPutJQgruidJson($aUserData['results'],$aUserData['affects'] , $aGetData['page'], $aGetData['rows']);
            }
        } else {
            $oUserGroup = new model_usergroup();
            $aUserGroups = $oUserGroup->getList(array('groupid', 'groupname')," groupid in(2,3,4)");
            $GLOBALS['oView']->assign("usergroup", $aUserGroups);
            $GLOBALS['oView']->assign("userlayer", $aUserLayer);
            $GLOBALS['oView']->display('user_infolist.html');
        }
    }
    /**
     * desc 导出用户资料
     * @author rhovin 2017-08-08
     */
    public function actionExportUserInfo() {
//        $_GET = json_decode($_GET['getData'],TRUE);
        $oPuserModel = new model_puser();  
        $oUserLayer = new model_userlayer();
        $aUserLayer = $oUserLayer->getOnliyLayerList($this->lvtopid);
        $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "username" => parent::VAR_TYPE_STR,
                "searchType" => parent::VAR_TYPE_STR,
                "team" => parent::VAR_TYPE_INT,
                "layerid" => parent::VAR_TYPE_INT, 
                "regstarttime" => parent::VAR_TYPE_DATETIME,
                "regendtime" => parent::VAR_TYPE_DATETIME,
                "lastonline" => parent::VAR_TYPE_STR,
                'lastonline_day' => parent::VAR_TYPE_INT,
            ));
        $aGetData[$aGetData['searchType']]=$_POST[$aGetData['searchType']];
        $aLayer = [] ;
        foreach ($aUserLayer as $key => $value) {
            $aLayer[$value['layerid']] = $value['name'];
        }
        $sWhere = $this->_getWhereStr($aGetData);
        $sWhere = $this->setWhereSql($aGetData,$sWhere);
        $aUserData = $oPuserModel->getChildList($this->lvtopid, "", $sWhere, "", 10000, intval($aGetData['page']));
        $aUserData['results'] = $this->getLastDay( $aUserData['results']);
        if (!empty($aUserData) && !empty($aGetData)) {
            $aUserInfo = $oPuserModel->getUserTree($this->lvtopid);
            foreach ($aUserInfo as $v){
                $aUserStr[$v['userid']] = $v;
            }
            foreach ($aUserData['results'] as $key => &$value) {
                //最后登录时间
                $value['lasttime'] = $value['lasttime'] != '1970-01-01 00:00:00' ? $value['lasttime'] : $value['registertime'];
                //上级代理
                $value['parentName'] = $aUserStr[$value['parentid']]['username'] ? $aUserStr[$value['parentid']]['username'] : "";
                //离开天数
                $time = time()-($value['lasttime']!='1970-01-01 00:00:00' ? strtotime($value['lasttime']) :time() );
                $time = $time > 24*3600 ? $time : 0;
                $value['awaydays'] = ceil($time/(24*3600));
                //加入用户层级
                $value['name'] = isset($aLayer[$value['layerid']]) ? $aLayer[$value['layerid']] : "";
                //代理等级
                if($value['usertype'] == 1) {
                    $value['proxylevel'] = $oPuserModel->getProxyLevel(substr_count($value['parenttree'],','));
                } else {
                    $value['proxylevel'] = "普通会员";
                }

                //导出总额默认显示后两位小数不需要四舍五入
                $value['loadmoney'] = intval($value['loadmoney'] * 100)/100;
                $value['totalwithdrawal'] = intval($value['totalwithdrawal'] * 100)/100;
            }
        }
        $expTitle  = "UserInfo";
        $expCellName  = [
            ['userid','用户ID'],
            ['username','账号名'],
            ['realname','真实姓名'],
            ['proxylevel','所属组'],
            ['parentName','上级代理'],
            ['name','用户层级'],
            ['nickname','昵称'],
            ['qq','QQ号'],
            ['mobile','电话号码'],
            ['email','邮箱'],
            ['channelbalance','账户余额'],
            ['rechargetimes', '存款次数'],
            ['withdrawaltimes', '提款次数'],
            ['loadmoney', '存款总额'],
            ['totalwithdrawal', '提款总额'],
            ['lastloginday','上次在线时间'],
            ['lasttime','最后登录日期'],
            ['registertime','注册日期']
        ];

        ExportExcel($expTitle,$expCellName , $aUserData['results']);
    }
    /**
     * @desc 查看客户银行卡列表
     * @author rhovin 
     * @date 2017-06-01
     */
    function actionUserCard() {
        if ($this->getIsPost()) {
            // 01, 整理搜索条件
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "isdel_bank" => parent::VAR_TYPE_INT,
                "isblack_bank" => parent::VAR_TYPE_INT,
                "searchType" => parent::VAR_TYPE_STR,
                "realname" => parent::VAR_TYPE_STR,
                "mintotaltransfer" => parent::VAR_TYPE_FLOAT,
                "maxtotaltransfer" => parent::VAR_TYPE_FLOAT,
            ));
            $aGetDataa = $this->post(array(
                "${aGetData['searchType']}" => parent::VAR_TYPE_STR, //用户名或银行卡查询
            ));
            $aGetData = array_merge($aGetData,$aGetDataa);
            //获取where条件
            $sWhere = 1;
            $sWhere .= $this->_getWhereStr($aGetData);
            $sWhere .= " AND ut.lvtopid='".$this->lvtopid."' AND ut.`isdeleted`='0'";
            if($_POST['mintotaltransfer'] != ""){
                $sWhere .= " AND cbi.totaltransfer >= '".$aGetData['mintotaltransfer']."'";
            }
            if($_POST['maxtotaltransfer'] != ""){
                $sWhere .= " AND cbi.totaltransfer <= '".$aGetData['maxtotaltransfer']."'";
            }
            $oBankCard = A::singleton('model_bankcard');
            // 获取数据结果集
            $aResult = $oBankCard->getUserBankCardList('*', $sWhere, $aGetData['rows'], $aGetData['page']); 
            //获取总代名称
            $oUser = new model_puser();
                foreach ($aResult['results'] as $k => $v) {
                    $aTopInfo = $oUser->getUserTreeInfo($v['lvproxyid'],['username']);
                    $aResult['results'][$k]['topproxy'] = $aTopInfo['username'];
            }
            //格式化金额
            foreach ($aResult['results'] as $k => &$v){
                    $v['totaltransfer'] = numberFormat2($v['totaltransfer']);
            }
            if (!empty($aResult) && !empty($aGetData)) {
                $this->outPutJQgruidJson($aResult['results'],$aResult['affects'] , $aGetData['page'], $aGetData['rows']);
            }
        } else {       
            $GLOBALS['oView']->display("user_usercard.html");
       }
    }
    /**
     * @desc 删除银行卡
     * @author rhovin
     * @date 2017-06-23
     */
    public function actionDelBank() {
        $aGetData = $this->get(array(
                "id" => parent::VAR_TYPE_INT,
        ));
        $iEntry = $aGetData['id'];
        $oBankCard = new model_bankcard();
        $aResult = $oBankCard->getUserBankCard(array('entry' => $iEntry, 'limits' => ' LIMIT 1 '));
        if (empty($aResult)) {
            sysMessage("操作失败, 银卡不存在", 1);
        }
        $aResult = $aResult[0];
        $oPuser = new model_puser();
        $aPuser = $oPuser->getUserTreeInfo($aResult['userid'],['lvtopid']);
        if($aPuser['lvtopid'] != $this->lvtopid) sysMessage("非法操作", 1);
        $oUserBankCard = new model_payment_bank();
        $mResult = $oUserBankCard->delCard($aResult['userid'], $aResult['entry']);
        if (-100 === intval($mResult)) {
            sysMessage("操作失败, 该银行卡存在未处理完成的提现申请", 1);
        } elseif ($mResult === TRUE) {
            sysMessage("操作成功");
        } else {
            sysMessage("操作失败", 1);
        }
    }
    /**
     * desc 修改银行卡
     * @author Rhovin 2017-10-12
     */
    public function actionUpdateBank() {
        $aGetData = $this->get(array(
                "id" => parent::VAR_TYPE_INT,
                "userid"=>parent::VAR_TYPE_INT,
        ));
        $iEntry = $aGetData['id'];
        $oBankCard = new model_bankcard();
        $aResult = $oBankCard->getUserBankCard(array('entry' => $iEntry,'userid'=>$aGetData['userid'], 'limits' => ' LIMIT 1 '));
        $aBanklist =  $oBankCard->getBankInfoList();
        $aRegion =  $oBankCard->getRegionList();
        $GLOBALS['oView']->assign("bankinfo", $aResult[0]);
        $GLOBALS['oView']->assign("banklist", $aBanklist);
        $GLOBALS['oView']->assign("regionlist", $aRegion);
        $GLOBALS['oView']->display("user_updatebank.html");
    }
    /**
     * @desc 将银行卡设为黑名单
     * @author rhovin
     * @date 2017-06-23
     */
    function actionblackUserCard() {
        $aGetData = $this->get(array(
                "type" => parent::VAR_TYPE_INT,
                "id" => parent::VAR_TYPE_INT,
        ));
        $iType = $aGetData['type'];
        $iEntry = $aGetData['id'];
        if ($iType != 0 && $iType != 1) {
            sysMessage("操作失败(001)", 1);
        }
        $oBankCard = new model_bankcard();
        $aResult = $oBankCard->getUserBankCard(array('entry' => $iEntry, 'limits' => ' LIMIT 1 '));
        if (empty($aResult)) {
            sysMessage("操作失败, 银卡不存在", 1);
        }
        $aResult = $aResult[0];
        $oPuser = new model_puser();
        $aPuser = $oPuser->getUserTreeInfo($aResult['userid'],['lvtopid']);
        if($aPuser['lvtopid'] != $this->lvtopid) sysMessage("非法操作", 1);
        $bFlag = $oBankCard->setUserBlankCard($iEntry, $iType);
        if ($bFlag == TRUE) {
            sysMessage("操作成功");
        } else {
            sysMessage("操作失败(003)", 1);
        }
    }
    /**
     * @desc 用户层级
     * @author rhovin
     * @date 2017-06-3
     */
    public function actionUserLayer(){
        $oUserLayer = A::singleton('model_userlayer');
        if ($this->getIsAjax()) {
            $aLayerList = $oUserLayer->getLayerList($this->lvtopid);
            $oPaySet = new model_payset();
            $oBetModel = new model_betlimit();
            $aPaySet = $oPaySet->paySetList($this->lvtopid);
            if(!empty($aPaySet) && $aPaySet>0) {
                foreach ($aPaySet as $key => $value) {
                    $aPaySetData[$value['id']] = $value['title'];
                }
                //支付设定
                foreach ($aLayerList as $key => &$value) {
                    $value['paysetlist'] = $aPaySetData;
                    //格式化金额
                    $value['loadmoney'] = numberFormat2($value['loadmoney']);
                    $value['loadmax'] = numberFormat2($value['loadmax']);
                    $value['totalwithdrawal'] = numberFormat2($value['totalwithdrawal']);
                }
            }
            $aBetGroup = $oBetModel->getBetGroup($this->lvtopid);
            if(!empty($aBetGroup)) {
                //投注限额
                foreach ($aBetGroup as $key => $val) {
                    $aBetData[$val['id']] = $val['name'];
                }
                foreach ($aLayerList as $key => &$value) {
                        $value['betlist'] = $aBetData;
                }
             }
            $this->outPutJQgruidJson($aLayerList,count($aLayerList));
        } else {
            $aLayerList = $oUserLayer->getLayerList($this->lvtopid);
            $GLOBALS['oView']->assign("aLayerList", $aLayerList);
            $GLOBALS['oView']->display("user_userlayer.html");
        }
    }
    /**
     * @desc 设置层级支付ID
     * @author rhovin 2017-07-07
     */
    public function actionSetLayerPayid() {
        $aGetData = $this->post(array(
                "layerid" => parent::VAR_TYPE_INT,
                "payid" => parent::VAR_TYPE_INT,
            ));
        $oUserLayer = new model_userlayer();
        $mResult = $oUserLayer->upLayerPayid($aGetData['layerid'],$aGetData['payid'],$this->lvtopid);
        if($mResult) {
            $this->ajaxMsg(1,"设定成功");
        } else {
            $this->ajaxMsg(0,"设定失败");
        }

    }
    /**
     * @desc 设置层级投注限额ID
     * @author rhovin 2017-07-07
     */
    public function actionSetLayerBetid() {
        $aGetData = $this->post(array(
                "layerid" => parent::VAR_TYPE_INT,
                "bet_group_id" => parent::VAR_TYPE_INT,
            ));
        $oUserLayer = new model_userlayer();
        $mResult = $oUserLayer->upLayerBetGroupId($aGetData['layerid'],$aGetData['bet_group_id'],$this->lvtopid);
        if($mResult) {
            $this->ajaxMsg(1,"设定成功");
        } else {
            $this->ajaxMsg(0,"设定失败");
        }
    }
    /**
     * @desc 添加会员层级
     * @author rhovin 
     * @date 2017-06-06
     */
    public function actionAddLayer() {
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "layername" => parent::VAR_TYPE_STR,
                "recharge_num" => parent::VAR_TYPE_INT,
                "paySet_id" => parent::VAR_TYPE_INT,
                "bet_group_id" => parent::VAR_TYPE_INT,
                "recharge_money" => parent::VAR_TYPE_FLOAT,
                "max_recharge_money" => parent::VAR_TYPE_FLOAT,
                "withdrawal_num" => parent::VAR_TYPE_INT,
                "withdrawal_money" => parent::VAR_TYPE_FLOAT,
                "remark" => parent::VAR_TYPE_STR,
            ));
            foreach ($aGetData as $key => $value) {
                if($key == "remark") continue;
                if($value === "") $this->ajaxMsg(0,"请将信息填写完整");
                if(strpos($value," ")!==false) $this->ajaxMsg(0,"填写的数据中不允许存在空格");
            }
            $oUserLayer = new model_userlayer();
            $layerid = $oUserLayer->getMaxLayerId($this->lvtopid);
            //入库数据
            $aData = [] ; 
            $aData['lvtopid'] = $this->lvtopid ;
            $aData['name'] = $aGetData['layername'] ;
            $aData['layerid'] = $layerid+1;
            $aData['rechargetimes'] = $aGetData['recharge_num'] ;
            $aData['p_paysetid'] = $aGetData['paySet_id'] ;
            $aData['bet_group_id'] = $aGetData['bet_group_id'] ;
            $aData['loadmoney'] = $aGetData['recharge_money'] ;
            $aData['loadmax'] = $aGetData['max_recharge_money'] ;
            $aData['withdrawaltimes'] = $aGetData['withdrawal_num'] ;
            $aData['totalwithdrawal'] = $aGetData['withdrawal_money'] ;
            $aData['remark'] = $aGetData['remark'] ;
            $aData['addtime'] = time();
            $result = $oUserLayer->addLayer($aData);
            if ($result) {
                $this->ajaxMsg(1,"添加成功");
            } else {
                $this->ajaxMsg(0,"添加失败");
            }
        } else {
            $oPaySet = new model_payset();
            $aPaySetList = $oPaySet->paySetList($this->lvtopid);
            $aPaySetOption = array();
            foreach ($aPaySetList as $k => $v) {
                $aPaySetOption[$k]['paySetId'] = $v['id'];
                $aPaySetOption[$k]['paySetName'] = $v['title'];
            }
            $oBetModel = new model_betlimit();
            $aBetGroup = $oBetModel->getBetGroup($this->lvtopid);
            $GLOBALS['oView']->assign("betlimit", $aBetGroup);
            $GLOBALS['oView']->assign("aPaySetOption", $aPaySetOption);
            $GLOBALS['oView']->display("user_addlayer.html");
        }
    }
    /**
     * @desc 编辑层级
     * @author rhovin
     * @date 2017-06-20
     */
    public function actionEditLayer() {
        $layerid = intval($_GET['layerid']);
        $oUserLayer = A::singleton("model_userlayer");
        $aUserLayer= $oUserLayer->getLayerById($this->lvtopid,$layerid);
        if($aUserLayer['lvtopid'] != $this->lvtopid) $this->ajaxMsg(0,"非法操作");
        if($this->getIsPost()) {
            $aGetData = $this->post(array(
                "layername" => parent::VAR_TYPE_STR,
                "recharge_num" => parent::VAR_TYPE_INT,
                "recharge_money" => parent::VAR_TYPE_FLOAT,
                "max_recharge_money" => parent::VAR_TYPE_FLOAT,
                "withdrawal_num" => parent::VAR_TYPE_INT,
                "withdrawal_money" => parent::VAR_TYPE_FLOAT,
                "remark" => parent::VAR_TYPE_STR,
            ));

            $aData['name'] = $aGetData['layername'] ;
            $aData['rechargetimes'] = $aGetData['recharge_num'] ;
            $aData['loadmoney'] = $aGetData['recharge_money'] ;
            $aData['loadmax'] = $aGetData['max_recharge_money'] ;
            $aData['withdrawaltimes'] = $aGetData['withdrawal_num'] ;
            $aData['totalwithdrawal'] = $aGetData['withdrawal_money'] ;
            $aData['remark'] = $aGetData['remark'] ;
            
            //需要更新的数据存在，并且提交的数据不为空并且有变化的数据才更新
            $aParams = [] ;
            foreach ($aData as $key => $value) {
                if( isset($aUserLayer[$key])) {
                     if($aUserLayer[$key] == $aData[$key] || $aData[$key] === "" ) {
                        continue ;
                     } else {
                        $aParams[$key] = $value; 
                     }
                }
            }
            if(empty($aParams)) $this->ajaxMsg(0,"没有需要更新的数据");
            $mResult = $oUserLayer->updateLayer($aParams,$layerid,$this->lvtopid);
            if($mResult) {
                $this->ajaxMsg(1,"更新成功");
            } else {
                $this->ajaxMsg(0,"更新失败");
            }
        }
        $GLOBALS['oView']->assign("userlayer",$aUserLayer);
        $GLOBALS['oView']->display("user_editlayer.html");
    }
    /**
     * @desc 删除层级
     * @author rhovin
     * @date 2017-06-20
     */
    public function actionDelLayer() {
        $aGetData = $this->get(array(
            "layerid"=> parent::VAR_TYPE_INT,
        ));
        $oUserLayer = new model_userlayer();  
        $aLockUsers = $oUserLayer->getLayerLockUser($this->lvtopid, $aGetData['layerid']);
        if(!empty($aLockUsers)) {
            sysMessage("当前层级有锁定用户，请先解锁", 1);
        }
        $aUsers = $oUserLayer->getUserListByLayerId($this->lvtopid, $aGetData['layerid']);
        $aUserId = [] ;
        if (!empty($aUsers)) {
            foreach ($aUsers as $key=>$userVal) {
                $aUserId[] = $userVal['userid'];
            }
            $oUserLayer->upAllUserlayerByUserid($aUserId);
        }
        $mResult = $oUserLayer->delLayer($this->lvtopid , $aGetData['layerid']);
        if($mResult) {
            sysMessage("删除成功");
        } else {
            sysMessage("删除失败", 1);
        }

    }
    /**
     * @desc 某层级下的用户列表
     * @author rhovin
     * @date 2017-06-21
     */
    public function actionBelongLayer() {
        static $aPageNumber = array(50,100,150,200,500);//分页条数
        //查询某层级下的会员
        $layerid = isset($_GET['layerid']) ? intval($_GET['layerid']) : "" ;
        if($this->getIsAjax()) {
            $aGetData = $this->post(array(
                "usernames" => parent::VAR_TYPE_STR,
                "page"=> parent::VAR_TYPE_INT,
                "rows"=> parent::VAR_TYPE_INT,
            ));
            //判断前端传过来的数据是否合法，不合法返回数组第一个值
            if (!in_array($aGetData['rows'],$aPageNumber)){
                $aGetData['rows'] = $aPageNumber[0];
            }
            $sWhere = '';
            if($aGetData['usernames'] != ''){
                $layerid = '';
                $sWhere .= " AND FIND_IN_SET(u.`username`,'". $aGetData['usernames'] ."')";
            }
            if($layerid !== ''){
                $sWhere .= " AND u.layerid='$layerid'";
            }
            $sWhere .= "  AND ut.lvtopid='".$this->lvtopid."'";
            $sWhere .= " AND ut.parentid <> 0 ";
            $oUserLayer = new model_userlayer();
            $aUserList = $oUserLayer->getLayerUserPageList($aGetData['page'],$aGetData['rows'],$sWhere);
            $aLayerList = $oUserLayer->getOnliyLayerList($this->lvtopid);
            $aLayerData = [];
            foreach ($aLayerList as $key => $value) {
                $aLayerData[$value['layerid']] = $value['name'];
            }
            //放入层级列表 用于前端下拉展示
            foreach ($aUserList['results'] as $key => &$value) {
                $value['layerlist'] = $aLayerData;
            }
            $this->outPutJQgruidJson($aUserList['results'],$aUserList['affects'] , $aGetData['page'], $aGetData['rows']);
        }
        //按用户名查询
        if($this->getIsPost()) {
             $aPostData = $this->post(array(
                "usernames"=> parent::VAR_TYPE_STR,
            ));
            $aPostData['usernames'] = preg_replace("/\s+/", ',', $aPostData['usernames']);
            $GLOBALS['oView']->assign('usernames', $aPostData['usernames']);
        }
        $GLOBALS['oView']->assign('page_number', json_encode($aPageNumber));//分页条数
        $GLOBALS['oView']->display("user_belonglayer.html");
    }
    /**
     * @desc 给单个用户分层
     * @author rhovin
     * @date 2017-06-21
     */
    public function actionEditUserLayer() {
        if($this->getIsAjax()) {
            $aGetData = $this->post(array(
                "layerid" => parent::VAR_TYPE_INT,
                "userid" => parent::VAR_TYPE_INT,
            ));
            $oUser= new model_user();
            $aUserTree= $oUser->getUserTreeInfo($aGetData['userid']);
            if($aUserTree['lvtopid'] != $this->lvtopid) $this->ajaxMsg(0,"非法操作");
            $aUser = $oUser->getUserInfo($aGetData['userid']);
            if($aUser['islocklayer'] == 1) $this->ajaxMsg(0,"用户层级被锁定,请先解锁");
            $oUserLayer = new model_userlayer();
            $aUserLayer = $oUserLayer->getLayerById($this->lvtopid,$aGetData['layerid']);
            if($aUserLayer['lvtopid'] != $this->lvtopid) $this->ajaxMsg(0,"非法操作");
            $mResult = $oUserLayer->upUserlayerByUserid($aGetData['userid'],$aGetData['layerid']);
            if($mResult) {
                $this->ajaxMsg(1,"分层成功");
            } else {
                $this->ajaxMsg(0,"分层失败");
            }
        }
    }
    /**
     * @desc 锁定用户层级
     * @author rhovin
     * @date 2017-06-22
     */
    public function actionLockUserLayer() {
        if($this->getIsAjax()) {
            $aGetData = $this->post(array(
                "islocklayer" => parent::VAR_TYPE_INT,
                "userid" => parent::VAR_TYPE_INT,
            ));
            $oPuser= new model_puser();
            $aUserTree = $oPuser->getUserTreeInfo($aGetData['userid']);
            if($aUserTree['lvtopid'] != $this->lvtopid) $this->ajaxMsg(0,"非法操作");
            $mResult = $oPuser->lockUserLayer($aGetData['userid'],$aGetData['islocklayer']);
            if($mResult) {
                $this->ajaxMsg(1,"操作成功");
            }else {
                $this->ajaxMsg(0,"操作成功");
            }
        }
    }
    /**
     * @desc 用户分层 层级到层级
     * @author rhovin
     * @date 2017-06-06
     */
    public function actionDispatchLayer() {
        if ($this->getIsPost()) {
            //@layerid 当前层级  @layerval 目标层级
            $aGetData = $this->post(array(
                "layerid" => parent::VAR_TYPE_INT,
                "layerval" => parent::VAR_TYPE_INT,
            )); 
            if($aGetData['layerid'] == $aGetData['layerval']) {
                $this->ajaxMsg(0,"目标层级不能和选择的层级相同");
            }
            $oUserLayer = new model_userlayer();
            //需要更新的用户
            $aResult = $oUserLayer->getUserListByLayerId($this->lvtopid,$aGetData['layerid']);

            if(!empty($aResult)) {
                $aUserId = [] ;
                foreach($aResult as $key=>$userVal){
                    $aUserId[] = $userVal['userid'];
                }
            }else {
               $this->ajaxMsg(0,"没有符合条件的用户"); 
            }
            //更新到目标层级
            $aResult = $oUserLayer->getLayerById($this->lvtopid,$aGetData['layerval']);
            if ($aResult && !empty($aUserId)) {
                $res = $oUserLayer->upUserlayer($aUserId,$aResult);
                if ($res) {
                    $this->ajaxMsg(1,"更新成功");
                } else {
                    $this->ajaxMsg(0,"更新失败");
                }
            } else {
                $this->ajaxMsg(0,"没有符合条件的用户");
            }
        }
    }
    /**
     * @desc 层级回归
     * @author rhovin 
     * @date 2017-06-07
     */
    public function actionResetLayer() {
        $oUserLayer = new model_userlayer();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "layerid" => parent::VAR_TYPE_INT,
            )); 
            $aResult = $oUserLayer->getUserListByLayerId($this->lvtopid,$aGetData['layerid']);
            $aUserId = [] ;
            if (!empty($aResult)) {
                foreach ($aResult as $key=>$userVal) {
                    $aUserId[] = $userVal['userid'];
                }
            }else{
                $this->ajaxMsg(0,"没有需要回归的用户");
            }
            if (!empty($aUserId)) {
                $bRes = $oUserLayer->upLayerByUserid($aUserId,$this->lvtopid);
                if($bRes){
                    $this->ajaxMsg(1,"更新成功");
                }else{
                    $this->ajaxMsg(0,"更新失败");
                }
            }else{
                $this->ajaxMsg(0,"没有需要回归的用户");
            }
        }
    }
    /**
     * @desc 新增用户页面
     * @author rhovin
     * @date 2017-06-03
     */
    public function actionAddUser() {
        $oUser = new model_puser();
        $iUserId = $this->lvtopid;
        //获取用户自身的属性
        $sFiled = " u.`createaccount`, ut.`istester`,ut.`parentid`,u.`maxpoint`,u.`pgtype`,ut.`parenttree`,ut.`username` ";
        $sAndWhere = " AND ut.`isdeleted`='0' AND ut.`userid`='" . $iUserId . "' AND ut.`usertype`='1' ";
        $aSelf = $oUser->getUsersProfile($sFiled, '', $sAndWhere, FALSE);  
        $fMaxPoint = $oUser->getCQSSCQSpoint($iUserId);
        $aAllowPoint = $this->_adjustPoint($fMaxPoint+0.001,0);
        if($this->getIsPost()) { 
            $aGetData = $this->post(array(
                "usertype" => parent::VAR_TYPE_INT,
                "username" => parent::VAR_TYPE_STR,
                "userpass" => parent::VAR_TYPE_STR,
                "nickname" => parent::VAR_TYPE_STR,
                "remark" => parent::VAR_TYPE_STR,
                "maxpoint" => parent::VAR_TYPE_FLOAT,
            ));
            //检查表格数据
            $bResult = $this->_checkForm($aGetData);
            if (!$bResult) {
                $this->ajaxMsg(0,$this->_errMsg);
            }          
            // 数据整理
            $aData = array();
            $aData['username'] = $aGetData['username'];
            $aData['loginpwd'] = $aGetData['userpass'];
            $aData['nickname'] = $aGetData['nickname'];
            $aData['usertype'] = $aGetData['usertype'];
            //最终返点值
            $fLastMaxPoint = $aGetData['maxpoint'];
            if($fLastMaxPoint > $fMaxPoint) {
                $this->ajaxMsg("0","用户返点设置错误");
            }
            // 获取用户组列--支持特殊组
            $oGroup = new model_usergroup();
            $aGroups = $oGroup->getGroupID($iUserId);
            if (empty($aGroups)) { // 获取组失败
                $this->ajaxMsg("0","系统错误: #users_1150"); // 分配用户级别操作失败
            }
            // 如果是开的代理，则判断是一级代理还是普通代理
            if ($aData['usertype'] == 1) {
                if ($oUser->isTopProxy($iUserId)) { // 如果为总代操作，并且开的是代理，则为一级代理
                    $iGroupId = $aGroups[1]; // 一代的组ID
                } else {
                    $iGroupId = $aGroups[2]; // 普代的组ID
                }
            } else {
                $iGroupId = $aGroups[3]; // 普通用户
            }
            // 调用模型方法, 执行增加用户
            $mResult = $oUser->pInsertUser($aData, $fLastMaxPoint, $iGroupId, $iUserId, TRUE, $aSelf['istester'],1,$iPgType = 1);
            if ($mResult == -1) {
                $this->ajaxMsg("0","用户名已经存在, 请重新输入");
            } elseif ($mResult == -7008) {
                $this->ajaxMsg("0","您的开户额不足，请联系您的上级获取开户额");
            } elseif (empty($mResult) || $mResult < 0) {
                $this->ajaxMsg("0","操作失败($mResult)");
            }
            $this->ajaxMsg("1","操作成功");
        } else {
            $GLOBALS['oView']->assign('allowpoint', $aAllowPoint);
            $GLOBALS['oView']->assign('userdata', $aSelf);
            //$oUser->assignSysInfo();
            $GLOBALS['oView']->display("user_adduser.html");
        }
    }
    /**
     * @desc 用户冻结
     * @author rhovin
     * @date 2017-06-07
     */
    public function actionFreeze() {
        $oUser = new model_puser();
        if($this->getIsPost()) {
            $aGetData = $this->post(array(
                "userid" => parent::VAR_TYPE_INT,
                "isallowcs" => parent::VAR_TYPE_INT,
                "free" => parent::VAR_TYPE_INT,
                "freetype" => parent::VAR_TYPE_INT,
                "reason" => parent::VAR_TYPE_STR,
            )); 
            $iUserId = $aGetData['userid'] ;
            $iIsallowcs = $aGetData['isallowcs'] ;
            $iFree = $aGetData['free'] ;
            $iFreeType = $aGetData['freetype'] ;
            $sFreeReason = $aGetData['reason'] ;
            if($sFreeReason == "") {
                $this->ajaxMsg(0,'请填写冻结原因');
            }
            $bResult = $oUser->frozenUser($iUserId, $this->lvtopid, 2, $iFreeType, ($iFree == 2), $sFreeReason, $iIsallowcs);
            if ($bResult) {
                $this->ajaxMsg(1,"冻结成功");
            }else{
                $this->ajaxMsg(0,"冻结失败");
            }
        } else {
            $iUserId = intval($_GET['userid']);
            $GLOBALS['oView']->assign('userid', $iUserId);
            $aUser = $oUser->getUserInfo($iUserId);
            $GLOBALS['oView']->assign('user', $aUser);
            $GLOBALS['oView']->display("user_freeze.html");
        }
    }
    /**
     * @desc 用户解冻
     * @author rhovin
     * @date 2017-06-07
     */
    public function actionUnFreeze() {
        $oUser = new model_puser();
        if($this->getIsPost()) {
            $aGetData = $this->post(array(
                "userid" => parent::VAR_TYPE_INT,
                "unfree" => parent::VAR_TYPE_INT,
            )); 
            $iUserId = $aGetData['userid'] ;
            $iUnFree = $aGetData['unfree'] ;
            $bResult = $oUser->unFrozenUser($iUserId, $this->lvtopid, 2, ($iUnFree == 2));
            if ($bResult) {
                $this->ajaxMsg(1,"解冻成功");
            } else {
                $this->ajaxMsg(0,"解冻失败");
            }
        } else {
            $iUserId = intval($_GET['userid']);
            $aUser = $oUser->getUserInfo($iUserId);
            $aUserTree = $oUser->getUserTreeInfo($iUserId);
            $GLOBALS['oView']->assign('userid', $iUserId);
            $GLOBALS['oView']->assign('user', $aUser);
            $GLOBALS['oView']->assign('usertree', $aUserTree);
            $GLOBALS['oView']->display("user_unfreeze.html");
        }
    }
    /**
     * @desc 锁卡/解锁
     * @author rhovin 2017-06-08
     * @date 2017-06-08
     */
    public function actionLockCard() {
        $oUser = new model_puser();
        $aGetData = $this->post(array(
                "status" => parent::VAR_TYPE_INT,
                "userid" => parent::VAR_TYPE_INT,
            )); 
        $bResult = $oUser->UserCardLock($aGetData['userid'], $aGetData['status']);
        if ($bResult) {
            $this->ajaxMsg(1,"操作成功");
        }else{
            $this->ajaxMsg(1,"操作失败");

        }
    }
    /**
     * @desc 解锁银行卡信息展示页面 实际解锁action : lockCard
     * @author rhovin 2017-06-08
     * @date 2017-06-08
     */
    public function actionUnLockCard() {
        $oUser = new model_puser();
         $aGetData = $this->get(array(
                "userid" => parent::VAR_TYPE_INT,
            )); 
        $iUserId = $aGetData['userid'] ;
        $aUser = $oUser->getUserInfo($iUserId,array("username"));
        $sUsername = $aUser['username'];
        //银行卡信息
        $oBankCard = A::singleton('model_bankcard');
        $sWhere = " cbi.userid='$iUserId' AND `isdel`=0 ";
        $aResult = $oBankCard->getUserBankCardList('*', $sWhere, 100, 0); // 获取数据结果集
        //账号资金
        $oUserFund = A::singleton('model_userfund');
        $fMoney = $oUserFund->getUserAvailableBalance($iUserId);

        $oWithDrawel = A::singleton('model_withdrawel');
        $aUserLoginStatusLately = $oWithDrawel->getUserLoginStatusLately($iUserId);
        //获取最近充值的银行卡信息
        $aUserSaveBank = $oWithDrawel->getAutosaveList("", " a.payer_userid='" . $iUserId . "' ", 3);
        $aUserSaveBank = $aUserSaveBank['results'];

        $GLOBALS['oView']->assign("aBankCardInfos", empty($aResult['results']) ? array() : $aResult['results'] );
        $GLOBALS['oView']->assign("aSaveBank", empty($aUserSaveBank) ? array() : $aUserSaveBank );
        $GLOBALS['oView']->assign("uname", $sUsername);
        $GLOBALS['oView']->assign("ufmoney", $fMoney);
        $GLOBALS['oView']->assign("uid", $iUserId);
        $GLOBALS['oView']->assign("ls", $aUserLoginStatusLately);
        $oBankCard->assignSysInfo();
        $GLOBALS['oView']->display("user_unlockcard.html");
    }
    /**
     * @desc 用户调点
     * @author rhovin
     * @date 2017-06-09
     */
    function actionAdjustPoint() {
        $oUser = new model_user();
        $iUserId = intval($_REQUEST['userid']);
        //获取自己的返点级别
        $aUserInfo = $oUser->getUserInfo($iUserId, array('userid', 'username', 'usertype', 'maxpoint'));
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "pointval" => parent::VAR_TYPE_FLOAT,
            ));
            $aGetData['pointval'] = $aGetData['pointval']/100;
            if($aGetData['pointval'] == $aUserInfo['maxpoint']) {
                $this->ajaxMsg("0","当前返点未发生变化");
            }
            $sType = $aGetData['pointval'] > $aUserInfo['maxpoint']  ? 'up' : 'down';
            //计算升点还是降点的差值
            if($sType == 'up'){
                $pointval = $aGetData['pointval'] - $aUserInfo['maxpoint'];
            }else{
                $pointval = $aUserInfo['maxpoint'] - $aGetData['pointval'];
            }
            /* @var $oUserMethodSet model_usermethodset */
            $oUserMethodSet = A::singleton("model_usermethodset");
            $mResult = $oUserMethodSet->AdjustUserPoint($this->lvtopid,$aUserInfo['maxpoint'],$aGetData['pointval'],$iUserId,$this->loginProxyId, $pointval, $sType, false, 2);
            if ($mResult === -11) {
               $this->ajaxMsg(0,"调点范围不符合规定，超出范围");
            } elseif ($mResult === -7008 || $mResult === -10010) {
               $this->ajaxMsg(0,"上级该返点配额不够,请检查上级配额");
            } elseif ($mResult === -13) {
               $this->ajaxMsg(0,"进行过靠量升点的用户30天内不能降点");
            } elseif ($mResult === TRUE) {
                $this->ajaxMsg(1,"调点成功");
            } else {
               $this->ajaxMsg(0,"调点失败(" . $mResult . ")");
            }
        }else{
            $iPid = $oUser->getParentId($iUserId);
            if ($iPid == 0) {
                $fWebMaxPoint = getConfigValue("runset_standardmaxpoint", 0.078);
                $aParentInfo  = array('username' => '无（当前账号为总代)', 'usertype' => 1, 'maxpoint' => $fWebMaxPoint);
            } else {
                //获取上级的返点级别
                $aParentInfo = $oUser->getUserInfo($iPid, array('username', 'usertype', 'maxpoint'));
            }
            //获取下级最大的返点级别
            $aChildMax = $oUser->getUsersProfile('u.`username`,u.`usertype`,u.`maxpoint`', '', " AND ut.`parentid`='" . $iUserId . "' AND ut.`isdeleted`=0 ORDER BY u.`maxpoint` DESC ");
            $sChildMax = isset($aChildMax['maxpoint']) && $aChildMax['maxpoint'] ? $aChildMax['maxpoint'] : 0;
            //获取调点范围
            $dvalue = "0.001";
            /* @var $oConfig model_proxyconfig */
            $oConfig = A::singleton('model_proxyconfig');
            /*$sSame = $oConfig->getConfigs($_SESSION['lvtopid'] ,'registered_samegrade');*/
            $iMax = $aParentInfo['maxpoint'] + $dvalue;
            $aAllowPoint = $this->_adjustPoint($iMax*100 , $sChildMax*100 , $dvalue*100);
            $aUserInfo['maxpoint'] = isset($aUserInfo['maxpoint']) ? $aUserInfo['maxpoint'] : 0.00;
            $aChildMax['maxpoint'] = isset($aChildMax['maxpoint']) ? $aChildMax['maxpoint'] : 0.00;
            $GLOBALS['oView']->assign("aAllowPoint", $aAllowPoint);
            $GLOBALS['oView']->assign("userid", $iUserId);
            $GLOBALS['oView']->assign("aSelf", $aUserInfo);
            $GLOBALS['oView']->assign("aParent", $aParentInfo);
            $GLOBALS['oView']->assign("aChild", $aChildMax);
            $oUser->assignSysInfo();
            $GLOBALS['oView']->display("user_adjustpoint.html");
        }
       
    }
    /**
     * @desc 编辑用户页面
     * @author rhovin
     * @date 2017-06-10
     */
    public function actionUserEdit() {
        $oUser = new model_puser();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "userid" => parent::VAR_TYPE_INT,
                "nickname" => parent::VAR_TYPE_STR,
                "email" => parent::VAR_TYPE_STR,
                "mobile" => parent::VAR_TYPE_STR,
                "qq" => parent::VAR_TYPE_STR,
                "realname" => parent::VAR_TYPE_STR,
                "remark" => parent::VAR_TYPE_STR,
                //"edit_action" => parent::VAR_TYPE_STR,
            )); 
            $aUserInfo = $oUser->getUserInfo(intval($aGetData['userid']),['nickname','username','realname','remark','qq','mobile','email','securitypwd','loginpwd']);
            $bResult = $this->_checkForm($aGetData);
            if ($bResult) {
                //需要更新的数据存在，并且提交的数据不为空并且有变化的数据才更新
                foreach ($aGetData as $key => $value) {
                    if( isset($aUserInfo[$key])) {
                         if($aUserInfo[$key] == $aGetData[$key] || $aGetData[$key] == "" ) {
                            continue ;
                         } else {
                            $aData[$key] = $value; 
                         }
                    }
                }
                if(empty($aData)){
                     $this->ajaxMsg(0,"没有需要更新的数据");
                }
                //基本资料修改
                $mResult = $oUser->updateUser($aGetData['userid'] , $aData);
                if ($mResult) {
                        $this->ajaxMsg(1,"更新成功");
                } else {
                        $this->ajaxMsg(0,"更新失败");
                }
             } else{
                 $this->ajaxMsg(0,$this->_errMsg);
            }
        } else {
            $iUserid = intval($_GET['userid']);
            //获取用户信息
            $aUserInfo = $oUser->getUserInfo($iUserid,['nickname','username','realname','remark','qq','mobile','email']);
            //获取上级ID
            $aUserTree = $oUser->getUserTreeInfo($iUserid,['parentid']);
            //如果上级ID为0 则为自身
            if($aUserTree['parentid'] == 0){
               $aParentInfo = $aUserInfo ; 
            }else{
                //获取上级信息
                $aParentInfo = $oUser->getUserInfo($aUserTree['parentid'],['username']);
            }
            $GLOBALS['oView']->assign("userid",$iUserid);
            $GLOBALS['oView']->assign("userinfo",$aUserInfo);
            $GLOBALS['oView']->assign("parentinfo",$aParentInfo);
            $GLOBALS['oView']->display("user_useredit.html");
        }
    }
    /**
     * @desc 用户资料修改审核列表
     * @author rhovin
     * @date 2017-06-24
     */
    public function actionReviewList() {
        $oPuser = new model_puser();
       if($this->getIsAjax() || $this->getIsPost()) {
            $aPostData = $this->post(array(
                "rows" => parent::VAR_TYPE_INT,
                "page" => parent::VAR_TYPE_INT,
                "type" => parent::VAR_TYPE_INT,
                "isconfirm" => parent::VAR_TYPE_INT,
                "username" => parent::VAR_TYPE_STR,
                "nametype" => parent::VAR_TYPE_STR,
                "starttime" => parent::VAR_TYPE_STR,
                "endtime" => parent::VAR_TYPE_STR,
            ));
            $aName = $this->post(array(
                "applyname" => parent::VAR_TYPE_STR,
                "confirmname" => parent::VAR_TYPE_STR,
            ));
            $aPostData[$aPostData['nametype']] = $aName[$aPostData['nametype']];
            $sWhere = $this->getReviewWhere($aPostData);
            $aData = $oPuser->getUserReviewList($this->lvtopid,$sWhere,$aPostData['page'],$aPostData['rows']," ORDER BY pac.`inserttime` DESC");
            //资金密码跟登陆密码修改，改备注
            foreach ($aData['results'] as $k => &$v){
                if ($v['type'] == '1'){
                    $v['apply_info'] = '资金密码修改';
                }elseif ($v['type'] == '0'){
                    $v['apply_info'] = '登陆密码修改';
                }
            }
            $this->outPutJQgruidJson($aData['results'],$aData['affects'] , $aPostData['page'], $aPostData['rows']);
       }
        $GLOBALS['oView']->display("user_reviewlist.html");
    }
    /** 
     * @desc 增加用户安全信息审核资料
     * @author rhovin
     * @date 2017-06-24
     */
    public function actionAddReview() {
        $aGetData = $this->post(array(
                "userid" => parent::VAR_TYPE_INT,
                "type" => parent::VAR_TYPE_INT,
                "loginpwd" => parent::VAR_TYPE_STR,
                "repeat_password" => parent::VAR_TYPE_STR,
                "securitypwd" => parent::VAR_TYPE_STR,
                "repeat_securitypwd" => parent::VAR_TYPE_STR,
                "realname" => parent::VAR_TYPE_STR,
                "entry"=>parent::VAR_TYPE_INT,
                "bankid"=>parent::VAR_TYPE_INT,
                "provinceid"=>parent::VAR_TYPE_INT,
                "cardno"=>parent::VAR_TYPE_STR,
                "branch"=>parent::VAR_TYPE_STR,
            ));
        $bResult = $this->_checkForm($aGetData);
        if(!$bResult) $this->ajaxMsg(0,$this->_errMsg);
        $oUser = new model_puser();
        $aUserInfo = $oUser->getUserInfo(intval($aGetData['userid']),['realname','securitypwd','loginpwd']);
        $aInsertData =  [];
        $aInsertData =[
                "lvtopid" => $this->lvtopid,
                "userid" => $aGetData['userid'],
                "apply_adminid" => $this->loginProxyId,
                "apply_ip" => getRealIP(),
                "inserttime" => date("Y-m-d H:i:s"),
                "type"=> $aGetData['type']
        ];
        //登录密码修改
        if($aGetData['type'] == 0) {
           if($aGetData['loginpwd'] != $aGetData['repeat_password']) $this->ajaxMsg(0,"两次输入密码不相同");
           if($aGetData['loginpwd'] === "") $this->ajaxMsg(0,"登录密码不允许为空");
           if(md5($aGetData['loginpwd']) == $aUserInfo['securitypwd']) $this->ajaxMsg(0,"登录密码不能资金密码相同");
           $aInsertData['apply_data'] =serialize(['loginpwd' => $aGetData['loginpwd'],"userid"=>$aGetData['userid']]); 
           $aInsertData['apply_info'] = "登录密码修改";
        }//资金密码
        else if($aGetData['type'] == 1) {
           if($aGetData['securitypwd'] != $aGetData['repeat_securitypwd']) $this->ajaxMsg(0,"两次输入密码不相同");
           if($aGetData['securitypwd'] === "") $this->ajaxMsg(0,"资金密码不允许为空");
           if(md5($aGetData['securitypwd']) == $aUserInfo['loginpwd']) $this->ajaxMsg(0,"登录密码不能资金密码相同");
           $aInsertData['apply_data'] =serialize(['securitypwd'=>$aGetData['securitypwd'],"userid"=>$aGetData['userid']]); 
           $aInsertData['apply_info'] = "资金密码修改";

        }//真实姓名修改
        else if($aGetData['type'] == 2) {
           if($aGetData['realname'] === "") $this->ajaxMsg(0,"真实姓名不允许为空");
           $aInsertData['apply_data'] =serialize(['realname'=>$aGetData['realname'],"userid"=>$aGetData['userid']]); 
           $aInsertData['apply_info'] = "真实姓名由【".$aUserInfo['realname']."】修改为【".$aGetData['realname']."】";
        } //银行卡信息修改 
        else if($aGetData['type'] == 5) {
            $oBankCard = new model_bankcard();
            $aResult = $oBankCard->getUserBankCard(array('entry' => $aGetData['entry'],'fields'=>'a.entry,a.bankid,a.provinceid,a.cardno,a.branch,c.bankname,d.title','userid'=>$aGetData['userid'], 'limits' => ' LIMIT 1 '));
            $aData = [];
            foreach ($aGetData as $key => $value) {
                if( isset($aResult[0][$key])) {
                     if($aResult[0][$key] == $aGetData[$key] || $aGetData[$key] == "" ) {
                        continue ;
                     } else {
                        $aData[$key] = $value; 
                     }
                }
            }
            $applyInfo = '';
            if(!empty($aData)) {
                foreach ($aData as $key => $value) {
                    switch ($key) {
                        case 'bankid':
                            $aBankCard = $oBankCard->getBankInfoById($value);
                            $applyInfo.="银行由:【".$aResult[0]['bankname']."】改为【".$aBankCard['bankname']."】</br>";
                            break;
                        case 'provinceid':
                            $aRegion = $oBankCard->getRegionById($value); 
                            $applyInfo.="银行省份由:【".$aResult[0]['title']."】改为【".$aRegion['title']."】</br>";
                            break;
                         case 'cardno':
                            $applyInfo.="银行卡号由:【".$aResult[0]['cardno']."】</br>改为【".$value."】</br>";
                            break;
                         case 'branch':
                            $applyInfo.="支行名称由:【".$aResult[0]['branch']."】改为【".$value."】</br>";
                            break;
                        default:
                            break;
                    }
                }
                $aData['userid'] = $aGetData['userid'];   
                $aData['entry'] = $aGetData['entry'];   
                $aInsertData['apply_data'] =  serialize($aData);  
                $aInsertData['apply_info'] = $applyInfo;
            } else {
                $this->ajaxMsg(0,"未做任何修改，无需提交");
            }
        }
        $mResult = $oUser->addReview($aInsertData);
        if($mResult) {
            $this->ajaxMsg(1,"提交成功，安全信息修改需审核");
        }else{
            $this->ajaxMsg(0,"提交失败");
        }
    }
    /**
     * @desc 安全信息审核通过
     * @author rhovin
     * @date 2017-06-26
     */
    public function actionPassReview() {
        $aGetData = $this->post(array(
                "id" => parent::VAR_TYPE_INT,
                "isconfirm" => parent::VAR_TYPE_INT,
                "type" => parent::VAR_TYPE_INT,
                "remark" => parent::VAR_TYPE_STR,
            ));
        $aUpData = [] ;
        foreach ($aGetData as $key => &$value) {
            if($value == "" || $key == 'id') continue;
            $aUpData[$key] = $value;
        }
        $aUpData ['confirm_adminid'] = $this->loginProxyId;
        $oUser = new model_puser();
        $aReview = $oUser->getOneReview($aGetData['id'], $this->lvtopid);
        $apply_data = @unserialize($aReview['apply_data']);
        if(empty($apply_data)) $this->ajaxMsg(0,"没有需要更新的数据");
        $mResult = $oUser->updateReview($aGetData['id'],$this->lvtopid,$aUpData, $apply_data);
        if($mResult) {
            $this->ajaxMsg(1,"审核通过");
        } else {
            $this->ajaxMsg(0,$oUser->_errMsg);
        }
    }
    /**
     * @desc 安全信息审核拒绝
     * @author rhovin
     * @date 2017-06-26
     */
    public function actionRefuseReview() {
        $aGetData = $this->post(array(
                "id" => parent::VAR_TYPE_INT,
                "isconfirm" => parent::VAR_TYPE_INT,
                "remark" => parent::VAR_TYPE_STR,
            ));
        $aUpData = [] ;
        foreach ($aGetData as $key => &$value) {
            if($value == "" || $key == 'id') continue;
            $aUpData[$key] = $value;
        }
        $aUpData ['confirm_adminid'] = $this->loginProxyId;
        $oUser = new model_puser();
        $mResult = $oUser->updateReview($aGetData['id'],$this->lvtopid,$aUpData);
        if($mResult) {
            $this->ajaxMsg(1,"操作成功");
        } else {
            $this->ajaxMsg(0,$oUser->_errMsg);

        }
    }
    /**
     * @desc 有效会员列表
     * @author rhovin
     * @date 2017-06-12
     */
    public function actionValidUser() {
        $aValidList = [];
        $aDataTime = array(
                "username" => parent::VAR_TYPE_STR,
                "starttime" => parent::VAR_TYPE_DATETIME,
                "endtime" => parent::VAR_TYPE_DATETIME,
            );
        $aGetData = $this->getIsPost() ? $this->post($aDataTime) : $this->get($aDataTime);
        if($aGetData["starttime"] == "" || $aGetData["endtime"] == ""){
            $aGetData["starttime"] = date('Y-m-d');
            $aGetData["endtime"] = date('Y-m-d');
        }
        $username = $aGetData['username'] == "" ? "" : "AND u.username='".$aGetData['username']."'";
        if($aGetData['starttime'] == "" && $aGetData['endtime'] == "") sysMessage("请输入查询时间", 1);
        if($aGetData['starttime'] >$aGetData['endtime'] ) sysMessage("开始时间不能大于结束时间", 1);
        $endtime = $aGetData['endtime'] == "" ? date("Y-m-d",time()) : $aGetData['endtime'] ;
        $oUser = new model_puser();
        $oProxyConfig = new model_proxyconfig();
        $other_member_comdition = $oProxyConfig->getConfigs($this->lvtopid,"other_member_comdition");//有效用户最低打码量
        if(empty($other_member_comdition)) $other_member_comdition = 0;
        $aValidList = $oUser->getValidUserList($this->lvtopid,$username, $other_member_comdition, $aGetData['starttime'], $endtime);
        $total['total_valid_user'] = 0;
        $total['total_new_user'] = 0;
        $total['total_charge'] = 0;
        $total['total_withdraw'] = 0;
        if($aValidList != null) {
            foreach ($aValidList as $key => &$value) {
               $total['total_valid_user'] +=$value['count_valid_user'] ;
               $total['total_new_user'] +=$value['count_new_user'] ;
               $total['total_charge'] += $value['count_charge'] ;
               $total['total_withdraw'] += $value['count_withdraw'] ;
               //格式化数据
               $value['count_charge'] = numberFormat2($value['count_charge']) ;
               $value['count_withdraw'] = numberFormat2($value['count_withdraw']);
            }
        }
        $username = isset($username) ? $aGetData['username'] :"";
        $GLOBALS['oView']->assign("username",$username);
        $GLOBALS['oView']->assign("starttime",$aGetData["starttime"]);
        $GLOBALS['oView']->assign("endtime",$aGetData["endtime"]);
        $GLOBALS['oView']->assign("total",$total);
        $GLOBALS['oView']->assign("userlist",$aValidList);
        $GLOBALS['oView']->display("user_validuser.html");
    }
    /**
     * @desc 用户出入款统计查询
     * @author rhovin
     * @date 2017-06-12
     */
    public function actionInOutCash() {
         $oPuserModel = new model_puser();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "pattern" => parent::VAR_TYPE_INT, //方式
                "usertype" => parent::VAR_TYPE_INT, //用户类型
                "searchType" => parent::VAR_TYPE_STR, //用户类型
                "username" => parent::VAR_TYPE_STR,
                "proxyname" => parent::VAR_TYPE_STR,
                "startlasttime" => parent::VAR_TYPE_DATETIME,
                "endlasttime" => parent::VAR_TYPE_DATETIME,
            ));
            //构造where条件
            $sWhere = "";
            if($aGetData['proxyname'] !== '' && $aGetData['searchType'] == 'proxyname') {
                $aIds = $oPuserModel->getIdByUsername($this->lvtopid,$aGetData['proxyname']);
                if (empty($aIds['lvproxyid'])){
                    $this->outPutJQgruidJson([],0 , $aGetData['page'], $aGetData['rows']);
                }
                $sWhere .= " AND ut.lvproxyid = '".$aIds['lvproxyid']."'";
            }
            //最后登录时间
            if (isset($aGetData['startlasttime']) && $aGetData['startlasttime'] != '') {
                $sWhere.=" AND u.`lasttime`>='" . $aGetData['startlasttime'] . "'";
            }
            if (isset($aGetData['endlasttime']) && $aGetData['endlasttime'] != '') {
                $sWhere.=" AND u.`lasttime`<='" . $aGetData['endlasttime'] . "'";
            }
            if($aGetData['pattern'] > -1) {
                  $sWhere .= $oPuserModel->getSelectStatisticsPattern($aGetData['pattern'],TRUE);
            }
            if ($aGetData['usertype'] > -1) {
                $sWhere .= " AND ut.usertype = '".$aGetData['usertype']."'";
            }
            if($aGetData['searchType'] == 'username' && $aGetData['username'] == '') {
                $sWhere .= " AND ut.lvproxyid = ut.userid";
            }
            if ($aGetData['username'] != '' && $aGetData['searchType'] == 'username') {
                $aUserInfo = $oPuserModel->getUser($aGetData['username'],$this->lvtopid);
                if (empty($aUserInfo)) {
                    $this->outPutJQgruidJson([],0 , $aGetData['page'], $aGetData['rows']);
                }
                $sWhere .= " AND (ut.parentid = '".$aUserInfo['userid']."' OR ut.userid = '".$aUserInfo['userid']."')";
            }
            $aUserData = $oPuserModel->getUserMoneyStatistics($this->lvtopid, $sWhere, intval($aGetData['page']),$aGetData['rows']);
            $aSumData = $oPuserModel->getUserSumStatistics($this->lvtopid,$sWhere);
            foreach ($aSumData as $key => &$value) {
                $value=bcadd($value, 0,4);
            }
            $aUserData['results'][0]['totals'] = $aSumData;
            if (!empty($aUserData) && !empty($aGetData)) {
                //格式化金额，保留后两位小数点
                foreach ($aUserData['results'] as $k => &$v){
                    $v['channelbalance'] = numberFormat2($v['channelbalance']);
                    $v['totalactivity'] = numberFormat2($v['totalactivity']);
                    $v['totalwithdrawal'] = numberFormat2($v['totalwithdrawal']);
                    $v['loadmoney'] = numberFormat2($v['loadmoney']);
                }

                $this->outPutJQgruidJson($aUserData['results'],$aUserData['affects'] , $aGetData['page'], $aGetData['rows']);
            }
        } else {
            if(isset($_GET['proxyname'])) {
                $GLOBALS['oView']->assign("proxyname", $_GET['proxyname']);
            }
            $pattern = $oPuserModel->getSelectStatisticsPattern();
           /* $GLOBALS['oView']->assign('sdate', date('Y-m-d ' . $this->sReportTime));
            $GLOBALS['oView']->assign('edate', date('Y-m-d ' . $this->sReportTime, strtotime('+1 d')));*/
            $GLOBALS['oView']->assign("pattern", $pattern);
            $GLOBALS['oView']->display("user_inoutcash.html");
        }
        
    }

}