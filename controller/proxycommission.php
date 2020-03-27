<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/19
 * Time: 16:36
 */
include "UserTrait.php";
class controller_proxycommission extends pcontroller{
    use UserTrait;
    /**
     * @desc 代理佣金列表
     * @author pierce
     * @date 2017-06-19
     */
    public function actionList(){
        $oProxyLevel = new model_proxylevel();

        if ($this->getIsAjax()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT
            ));
            $aCommissionList = $oProxyLevel->actionCommissionList($this->lvtopid,$aGetData['rows'],$aGetData['page']);
            foreach($aCommissionList['results'] as $k => $v){
                $aCommissionList['results'][$k]['point'] = ($v['point'] * 100)."%";
            }
            $this->outPutJQgruidJson($aCommissionList['results'],$aCommissionList['affects'],$aGetData['page'], $aGetData['rows']);
        }else{
            $GLOBALS['oView']->display("proxycommission_list.html");
        }

    }
    /**
     * @desc 添加代理佣金层级
     * @author price
     * @date 2017-06-19
     */
    public function actionAddCommission() {
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "proxy" => parent::VAR_TYPE_INT,
                "point" => parent::VAR_TYPE_FLOAT,
                "def_quota" => parent::VAR_TYPE_INT
            ));
            //入库数据
            $aData = [] ;
//            if ($aGetData['point'] == 0){
//                $this->ajaxMsg(1,"返点等级不能为空");
//                exit;
//            }
            $oProxyConfig = new model_proxyconfig();
            $aProyxConfig = $oProxyConfig->getConfigs($this->lvtopid,"registered_promotion_point");
            if ($aGetData['point'] < $aProyxConfig*100){
                $this->ajaxMsg(1,"该返点不受配额限制");
                exit;
            }
            if ($aGetData['def_quota'] < 0){
                $this->ajaxMsg(1,"默认配额不能为空");
                exit;
            }
            if ($aGetData['point'] > 10){
                $this->ajaxMsg(1,"不可超過系統反點最大值");
                exit;
            }
            $aData['lvtopid'] = $this->lvtopid ;
            $aData['proxy_level'] = $aGetData['proxy'];
            $aData['point'] = $aGetData['point']/100 ;
            $aData['def_quota'] = $aGetData['def_quota'];

            $oProxyLevel = new model_proxylevel();
            $iCount = $oProxyLevel->getAccquotaCount($this->lvtopid,$aData['proxy_level'],$aData['point']);
            if ($iCount['icount'] >= 1){
                $this->ajaxMsg(1,"该返点默认赔额已存在");
                exit;
            }
            $result = $oProxyLevel->addProxyCommission($aData);
            if ($result) {
                $this->ajaxMsg(0,"添加成功");
            } else {
                $this->ajaxMsg(1,"添加失败");
            }
        } else {
            $GLOBALS['oView']->display("proxycommission_addcommission.html");
        }
    }
    /**
     * @desc 修改代理佣金层级
     * @author price
     * @date 2017-06-20
     */
    public function actionEditCommission() {
        $id = intval($_GET['id']);
        $oProxyLevel = new model_proxylevel();
        $aProxyLayer= $oProxyLevel->getLayerById($this->lvtopid,$id);
        $sPoint = $aProxyLayer['point'] * 100;
        if($aProxyLayer['lvtopid'] != $this->lvtopid) $this->ajaxMsg(0,"非法操作");
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "proxy" => parent::VAR_TYPE_INT,
                "point" => parent::VAR_TYPE_FLOAT,
                "def_quota" => parent::VAR_TYPE_INT
            ));
            //入库数据
            if ($aGetData['point'] > 10){
                $this->ajaxMsg(1,"不可超過系統反點最大值");
                exit;
            }
            $oProxyConfig = new model_proxyconfig();
            $aProyxConfig = $oProxyConfig->getConfigs($this->lvtopid,"registered_promotion_point");
            if (empty($aProyxConfig)){
                $aProyxConfig = 0;
            }
            if ($aGetData['point']/100 < $aProyxConfig){
                $this->ajaxMsg(1,"该返点不受配额限制");
                exit;
            }
            $aData['proxy_level'] = $aGetData['proxy'];
            $aData['point'] = $aGetData['point']/100 ;
            $aData['def_quota'] = $aGetData['def_quota'];
            //需要更新的数据存在，并且提交的数据不为空并且有变化的数据才更新
            $aParams = [] ;
            if (isset($aData['proxy_level']) && $aData['proxy_level'] != $aProxyLayer['proxy_level']){
                $aParams['proxy_level'] = $aData['proxy_level'];
            }
            if (isset($aData['point']) && $aData['point'] != $aProxyLayer['point']){
                $aParams['point'] = $aData['point'];
            }
            if (isset($aData['def_quota']) && $aData['def_quota'] != $aProxyLayer['def_quota']){
                $aParams['def_quota'] = $aData['def_quota'];
            }
            if (empty($aParams)){
                $this->ajaxMsg(1,"修改失败");
            }
            $mResult = $oProxyLevel->editProxyCommission($aParams,$id);
            if ($mResult) {
                $this->ajaxMsg(0,"修改成功");
            } else {
                $this->ajaxMsg(1,"修改失败");
            }
        } else {
            $GLOBALS['oView']->assign("point",$sPoint);
            $GLOBALS['oView']->assign("proxyLayer",$aProxyLayer);
            $GLOBALS['oView']->display("proxycommission_editcommission.html");
        }
    }
    /**
     * @desc 删除默认佣金
     * @author pierce
     * @date 2017-06-20
     */
    public function actionDeleteCommission() {
        $aGetData = $this->get(array(
            "id"=> parent::VAR_TYPE_INT,
        ));
        $oProxyLevel = new model_proxylevel();
        $mResult = $oProxyLevel->delLayer($aGetData['id']);
        if($mResult) {
            sysMessage("删除成功");
        } else {
            sysMessage("删除失败", 1);
        }
    }
    /**
     * @desc 代理账号与绑定
     * @author pierce
     * @date 2017-06-20
     */
    public function actionProxyList(){
        $oProxyLevel = new model_proxylevel();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "minRegister" => parent::VAR_TYPE_INT,
                "maxRegister" => parent::VAR_TYPE_INT,
                "user_type" => parent::VAR_TYPE_INT,
                "minVisit" => parent::VAR_TYPE_INT,
                "maxVisit" => parent::VAR_TYPE_INT,
                "numtype" => parent::VAR_TYPE_STR,
                "nametype" => parent::VAR_TYPE_STR,
                "username" => parent::VAR_TYPE_STR,
                "code" => parent::VAR_TYPE_STR,
                "domain" => parent::VAR_TYPE_STR
            ));
            //默认查询此商户下的信息
            $sWhere = "url.`lvtopid`='" . intval($this->lvtopid) . "' AND url.`creator` = 0";
            //代理账号和绑定域名
            if (isset($aGetData['nametype']) && $aGetData['nametype'] == "username"){
                if (isset($aGetData['username']) && $aGetData['username'] != ""){
                    $sWhere .= " AND u.`username` = '".$aGetData['username']."'";
                }
            }else{
                if (isset($aGetData['domain']) && $aGetData['domain'] != ""){
                    $sWhere .= " AND url.`reg_domain` = '".$aGetData['domain']."'";
                }
            }
            if (isset($aGetData['nametype']) && $aGetData['nametype'] == "code" && $aGetData['code'] != ""){
                $sWhere .= " AND url.`reg_code` = '".$aGetData['code']."'";
            }
            if (isset($aGetData['user_type']) && $aGetData['user_type'] < 2){
                $sWhere .= " AND url.`user_type` = '".$aGetData['user_type']."'";
            }
            //访问量和注册人数
            if ($aGetData['numtype'] == "Register") {
                if (isset($aGetData["minRegister"]) && isset($aGetData["maxRegister"])){
                    if ($aGetData["maxRegister"] != "" && $aGetData["minRegister"] != ""){
                        $sWhere .=" AND url.`reg_users`>='" .  $aGetData["minRegister"] . "' AND url.`reg_users`<='" .  $aGetData["maxRegister"] . "'";
                    }elseif ($aGetData["maxRegister"] == "" && $aGetData["minRegister"] != ""){
                        $sWhere .=" AND url.`reg_users`>='" .  $aGetData["minRegister"] . "'";
                    }elseif ($aGetData["maxRegister"] != "" && $aGetData["minRegister"] == "") {
                        $sWhere .=" AND url.`reg_users`<='" .  $aGetData["maxRegister"] . "'";
                    }
                }
            }elseif ($aGetData['numtype'] == "Visit"){
                if (isset($aGetData["minVisit"]) && isset($aGetData["maxVisit"])){
                    if ($aGetData["maxVisit"] != "" && $aGetData["minVisit"] != ""){
                        $sWhere .=" AND url.`views`>='" .  $aGetData["minVisit"]. "' AND url.`views`<='" . $aGetData["maxVisit"] . "'";
                    }elseif ($aGetData["maxVisit"] == "" && $aGetData["minVisit"] != ""){
                        $sWhere .=" AND url.`views`>='" . $aGetData["minVisit"]. "'";
                    }elseif ($aGetData["maxVisit"] != "" && $aGetData["minVisit"] == "") {
                        $sWhere .=" AND url.`views`<='" . $aGetData["maxVisit"] . "'";
                    }
                }
            }
            $aProxyList = $oProxyLevel->actionProxyList($sWhere,$aGetData['rows'],$aGetData['page']);
            $aProxyhomelink = $oProxyLevel->getHomeLink($this->lvtopid);
            foreach($aProxyList['results'] as $k => &$v){
                $v['user_point'] = ($v['user_point'] * 100)."%";
                if($v['show_code']==0 && !empty($v['reg_domain'])){
                    $v['reg_domain'] =  $v['reg_domain']."(不显)";
                }
                if (empty($aProxyhomelink)){
                    $v['addHomeLink'] = 1;
                }else{
                    $v['addHomeLink'] = 0;
                }
            }
            $this->outPutJQgruidJson($aProxyList['results'],$aProxyList['affects'],$aGetData['page'], $aGetData['rows']);
        }else{
            $GLOBALS['oView']->assign('startDate', date('Y-m-d '));
            $GLOBALS['oView']->assign('endDate', date('Y-m-d ', strtotime('+1 days')));
            $GLOBALS['oView']->display("proxycommission_proxylist.html");
        }
    }
    /**
     * @desc 新增代理域名
     * @author pierce
     * @date 2017-06-28
     */
    public function actionAddProxy() {

        if(isset($_POST['getproxy']) && $_POST['getproxy'] == 1){//模糊查询系统代理人用户名
            $sUsername = (string)$_POST['username'];
            /* @var  $oUserTree model_usertree */
            $oUserTree = A::singleton('model_usertree');
            $sWhere = " And username like '%$sUsername%'";
            $aResult = $oUserTree->getAllProxy($_SESSION['lvtopid'],'userid,username',$sWhere);
            if($aResult){
                $this->ajaxMsg(1,$aResult);
            }else{
                $this->ajaxMsg(0,'fail');
            }
        }

        $oPuser = new model_puser();
        $oProxyDomain = new model_pdomain();
        $aDomains = $oProxyDomain->getDomainByLvTopId($this->lvtopid, true);
        $aUser = $oPuser->getProxyByLvTopId($this->lvtopid);
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "userId" => parent::VAR_TYPE_INT,
                "userType" => parent::VAR_TYPE_INT,
                "isShowCode" => parent::VAR_TYPE_INT,
                "domain" => parent::VAR_TYPE_STR,
                "point" => parent::VAR_TYPE_FLOAT,
                "remarks" => parent::VAR_TYPE_STR
            ));
            if ($aGetData['userId'] == 0){
                $this->ajaxMsg(1,"请搜索正确的代理账号");
                exit;
            }
            //入库数据
            $aData = [] ;
            $aData['lvtopid'] = $this->lvtopid ;
            $reg = substr(microtime(true) * 1000, 0, 13) ;
            $aData['reg_code'] = numConvert($reg);
            $aData['userid'] = $aGetData['userId'];
            $aData['creator'] = 0;
            $aData['user_type'] = $aGetData['userType'];
            $aData['show_code'] = $aGetData['isShowCode'];
            if ($aGetData['domain'] !== "" ){
                $aData['reg_domain'] = $aGetData['domain'];
                $aLinkInfo = $oProxyDomain->getDomainByDomain($this->lvtopid,$aData['reg_domain']);
                if (!empty($aLinkInfo)){
                    $this->ajaxMsg(1,"该域名已被绑定");
                }
            }
            $aData['user_point'] = $aGetData['point']/100;
            $aData['instertime'] =date('Y-m-d H:i:s');
            $aData['remark'] = $aGetData['remarks'];
            $result = $oProxyDomain->addProxyDomain($aData);
            if ($result) {
                /* @var $oMemCache memcachedb */
                $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
                $oMemCache->delete('user_register_links_' . $this->lvtopid);
                $this->ajaxMsg(0,"添加成功");
            } else {
                $this->ajaxMsg(1,"添加失败");
            }
        } else {
            $GLOBALS['oView']->assign("aUser",$aUser);
            $GLOBALS['oView']->assign("aDomains",$aDomains);
            $GLOBALS['oView']->display("proxycommission_addproxy.html");
        }
    }
    /**
     * @desc 修改代理域名
     * @author pierce
     * @date 2017-06-28
     */
    public function actionEditProxy(){
        $oPuser = new model_puser();
        $oProxyDomain = new model_pdomain();
        $oProxyConfig = new model_proxyconfig();
        $id = intval($_GET['id']);
        $userid = intval($_GET['userid']);
        $aProyxConfig = $oProxyConfig->getConfigs($this->lvtopid,"registered_promotion_point");//获取系统配置参数
        $aUserAccquota = $oPuser->getUserAccquotaByLvtopid($this->lvtopid,$userid);
        $aPoint = array_column($aUserAccquota,'point');
        $aAllowUserPoint = array();
        foreach ($aPoint as $v){
            if ($v > $aProyxConfig){
                $v = $v*1000;
                $aAllowUserPoint[] = ["val"=>number_format($v / 10, 1),"str"=>number_format($v / 10, 1).'%------'.( ($v / 10)*20+1800)];
            }
        }

        //获取此用户的代理域名信息
        $aProxy = $oProxyDomain->getProxyDomainById($id,$this->lvtopid);
        $aProxy['user_point'] = strval($aProxy['user_point']*100);

        //获取推广链接返点下拉框
        $iMinPoint = getConfigValue("topproxyset_mincommissiongap", 0.001);
        $iMinPoint = round(ceil($iMinPoint*1000)/10,1);
        if (empty($aPoint)){
            $iMaxPoint = 0;
        }else{
            if (max($aPoint) < $aProyxConfig) {
                $iMaxPoint = max($aPoint)*100;
            }else{
                $iMaxPoint = $aProyxConfig*100;
            }
        }
        $iStart = floatval(number_format($iMinPoint, 1)) * 10;
        $iEnd = floatval(number_format($iMaxPoint, 1)) * 10;
        $aAllowPoint = array();
        for ($i = $iStart; $i <= $iEnd; $i+=1) {
            $aAllowConfigPoint[] = ["val"=>number_format($i / 10, 1),"str"=>number_format($i / 10, 1).'%------'.( ($i / 10)*20+1800)];
        }
        $aAllowPoint = array_merge($aAllowConfigPoint,$aAllowUserPoint);
        $aDomains = $oProxyDomain->getDomainByLvTopId($this->lvtopid, true);

        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "userType" => parent::VAR_TYPE_INT,
                "isShowCode" => parent::VAR_TYPE_INT,
                "domain" => parent::VAR_TYPE_STR,
                "point" => parent::VAR_TYPE_FLOAT,
                "remarks" => parent::VAR_TYPE_STR
            ));
            //入库数据
            $aData = [] ;
            $aData['user_type'] = $aGetData['userType'];
            $aData['show_code'] = $aGetData['isShowCode'];
            $aData['reg_domain'] = $aGetData['domain'];
            $aData['user_point'] = $aGetData['point']/100;
            $aData['instertime'] =date('Y-m-d H:i:s');
            $aData['remark'] = $aGetData['remarks'];
            //需要更新的数据存在，并且提交的数据不为空并且有变化的数据才更新
            $aParams = [] ;
            if (isset($aData['user_type']) && $aData['user_type'] != $aProxy['user_type']){
                $aParams['user_type'] = $aData['user_type'];
            }
            if (isset($aData['show_code']) && $aData['show_code'] != $aProxy['show_code']){
                $aParams['show_code'] = $aData['show_code'];
            }
            if (isset($aData['user_point']) && $aData['user_point'] != $aProxy['user_point']/100){
                $aParams['user_point'] = $aData['user_point'];
            }
            if (isset($aData['remark']) && $aData['remark'] != $aProxy['remark']){
                $aParams['remark'] = $aData['remark'];
            }
            if (!empty($aData['reg_domain'])){
                $aLinkInfo = $oProxyDomain->getDomainByDomain($this->lvtopid,$aData['reg_domain']);
                if (!empty($aLinkInfo) && $aData['reg_domain'] != $aProxy['reg_domain']){
                    $this->ajaxMsg(1,"该域名已被绑定");
                }
                $aParams['reg_domain'] = $aData['reg_domain'];
            } else {
                $aParams['reg_domain'] = 'NULL';
            }
            if (!empty($aParams)){
                $aParams['instertime'] = $aData['instertime'];
            }
            $result = $oProxyDomain->editProxyDomain($aParams,$id,$this->lvtopid);
            if ($result !== false) {
                /* @var $oMemCache memcachedb */
                $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
                $oMemCache->delete('user_register_links_' . $this->lvtopid);
                $this->ajaxMsg(0,"修改成功");
            } else {
                $this->ajaxMsg(1,"修改失败");
            }
        } else {
            $GLOBALS['oView']->assign("proxy",$aProxy);
            $GLOBALS['oView']->assign("aDomains",$aDomains);
            $GLOBALS['oView']->assign("aAllowPoint",$aAllowPoint);
            $GLOBALS['oView']->display("proxycommission_editproxy.html");
        }
    }
    /**
     * @desc 删除代理域名
     * @author pierce
     * @date 2017-06-20
     */
    public function actionDeleteProxy(){
        $aGetData = $this->get(array(
            "id"=> parent::VAR_TYPE_INT,
        ));
        $oProxyLevel = new model_proxylevel();
        $mResult = $oProxyLevel->delProxy($aGetData['id']);
        if($mResult) {
            /* @var $oMemCache memcachedb */
            $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
            $oMemCache->delete('user_register_links_' . $this->lvtopid);
            $this->ajaxMsg(0,"删除成功");
        } else {
            $this->ajaxMsg(1,"删除失败");
        }
    }
    /**
     * @desc 设置加盟代理
     * @author pierce
     * @date 2017-06-20
     */
    public function actionSetHomeLink(){
        $aGetData = $this->get(array(
            "id"=> parent::VAR_TYPE_INT,
            "ishomelink"=> parent::VAR_TYPE_INT,
        ));
        $oProxyLevel = new model_proxylevel();
        $aParam['ishomelink'] = $aGetData['ishomelink'];
        $sWhere = " `lvtopid` = '" . intval($this->lvtopid) . "' AND  `id` = '" . $aGetData['id'] . "' AND  `user_type`=1";
        $mResult = $oProxyLevel->setHomeLink($aParam,$sWhere);
        if($mResult) {
            /* @var $oMemCache memcachedb */
            $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
            $oMemCache->delete('user_register_links_' . $this->lvtopid);
            $this->ajaxMsg(0,"设置成功");
        } else {
            $this->ajaxMsg(1,"设置失败");
        }
    }

    /**
     * @desc 获取推广链接
     * @author pierce
     * @date 2017-06-20
     */
    public function actionGetSortUrl(){
        $aGetData = $this->get(array(
            "code"=> parent::VAR_TYPE_STR,
        ));
        $oProxyDomain = new model_pdomain();
        $aDomains = $oProxyDomain->getDomainByLvTopId($this->lvtopid);
        $i = 0;
        foreach ($aDomains as $k => $v) {
            $aDomains[$k]['i'] = $i;
            $i++;
        }
        $GLOBALS['oView']->assign("code",$aGetData['code']);
        $GLOBALS['oView']->assign("aDomains",$aDomains);
        $GLOBALS['oView']->display("proxycommission_getsorturl.html");
    }

    /**
     * 根据代理获取返点
     * @author pierce
     * @date 2017-08-09
     */
    public function actionGetPoint(){
        $iUserid = intval($_POST['userid']);
        $oPuser = new model_puser();
        $oProxyConfig = new model_proxyconfig();
        /* @var $oConfig model_proxyconfig */
        $oConfig = A::singleton('model_proxyconfig');
        $sSame = $oConfig->getConfigs($this->lvtopid, 'registered_samegrade'); // 客户允许上下级返点相同，点差为0
        $aProyxConfig = $oProxyConfig->getConfigs($this->lvtopid,"registered_promotion_point");//获取系统配置参数
        $aUserAccquota = $oPuser->getUserAccquotaByLvtopid($this->lvtopid,$iUserid);
        // 清除配额为0的返点，不让其显示到候选列表
        foreach ($aUserAccquota as $kList => $vList){
            if ($vList['left_quota'] == 0) {
                unset($aUserAccquota[$kList]);
            }
        }
        $aPoint = array_column($aUserAccquota,'point');
        asort($aPoint);
        $aUser = $oPuser->getUserInfoByUserId($iUserid);
        $iUserMaxPoint = $aUser['maxpoint'];
        // 若不允许返点同级，则推广链接不显示同级返点
        foreach ($aPoint as $k => $i){
            if ($i > $iUserMaxPoint){
                unset($aPoint[$k]);
            }
            if ($sSame == 'no' && $i == $iUserMaxPoint){
                unset($aPoint[$k]);
            }
        }
        $aAllowUserPoint = array();
        foreach ($aPoint as $v){
            if ($v > $aProyxConfig){
                $v = $v*1000;
                $aAllowUserPoint[] = ["val"=>number_format($v / 10, 1),"str"=>number_format($v / 10, 1).'%------'.( ($v / 10)*20+1800)];
            }
        }
        $iMinPoint = getConfigValue("topproxyset_mincommissiongap", 0.001);
        $iMinPoint = round(ceil($iMinPoint*1000)/10,1);
        if (empty($aPoint)){
            $iMaxPoint = 0;
        }else{
            if (max($aPoint) < $aProyxConfig) {
                $iMaxPoint = max($aPoint)*100;
            }else{
                $iMaxPoint = $aProyxConfig*100;
            }
        }
        // 默认配额一下循环递减出来
        $iStart = floatval(number_format($iMinPoint, 1)) * 10;
        $iEnd = floatval(number_format($iMaxPoint, 1)) * 10;
        for ($i = $iStart; $i <= $iEnd; $i+=1) {
            $aAllowConfigPoint[] = ["val"=>number_format($i / 10, 1),"str"=>number_format($i / 10, 1).'%------'.( ($i / 10)*20+1800)];
        }
        $aAllowPoint = array_merge($aAllowConfigPoint,$aAllowUserPoint);
        echo json_encode($aAllowPoint);
    }
}