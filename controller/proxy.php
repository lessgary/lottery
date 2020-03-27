<?php 
    /**
     * @desc商户管理
     * @author rhovin
     * @date 2017-06-12
     */
    class controller_proxy extends pcontroller
    {
        
        function __construct()
        {
            parent::__construct();
        }
        /**
         * @desc 商户管理组别列表
         * @author rhovin
         * @2017-06-12
         */
        public function actionGroupList() {
            $aGetData = $this->get(array(
                "parentid"=> parent::VAR_TYPE_INT,
            ));
            if($this->getIsAjax()){
                $oProxyGroup = new model_proxygroup();
                $oProxyUser = new model_proxyuser();
                $aUserGroupInfo = $oProxyUser->getProxyUserGroup($this->loginProxyId);
                $parentid = $aGetData['parentid'] != 0 ? $aGetData['parentid'] : 1 ;
                $aGroupList = $oProxyGroup->getGroupList($aUserGroupInfo['parentid'],$this->lvtopid);
                $this->outPutJQgruidJson($aGroupList,count($aGroupList));
            }else{
                $GLOBALS['oView']->display('proxy_grouplist.html');
            }
        }
        /**
         * @desc 添加商户管理组
         * @author rhovin
         * @2017-06-12
         */
        public function actionAddGroup() {
            $oProxyGroup = A::singleton('model_proxygroup');
            if($this->getIsPost()){
                $aGetData = $this->post(array(
                    "groupid" => parent::VAR_TYPE_INT,
                    "groupname" => parent::VAR_TYPE_STR,
                    "description" => parent::VAR_TYPE_STR,
                ));
                if($aGetData['groupid']==0 || empty($aGetData['groupname']) || empty($aGetData['description'])) {
                    $this->ajaxMsg(0,"请将信息填写完整");
                }
                $aGetData['parentid'] = $aGetData['groupid'] ;
                $aGetData['lvtopid'] = $this->lvtopid;

                $mResult = $oProxyGroup->addProxyGroup($aGetData);
                if($mResult) {
                    $this->ajaxMsg(1,"添加成功");
                } else {
                    $this->ajaxMsg(0,"添加失败");
                }
            } else {
                if (isset($_GET['groupid'])) {
                    $groupid = intval($_GET['groupid']);
                    $aGroupInfo = $oProxyGroup->proxyGroup($groupid);
                    $GLOBALS['oView']->assign('groupinfo', $aGroupInfo);
                }
                $oProxyUser = new model_proxyuser();
                $aUserGroupInfo = $oProxyUser->getProxyUserGroup($this->loginProxyId);
                $aProxyGroup = $oProxyGroup->getProxyGroupSelect($this->lvtopid,$aUserGroupInfo['parentid'],$aUserGroupInfo['groupid'], TRUE);
                $GLOBALS['oView']->assign('proxygroup', $aProxyGroup);
                $GLOBALS['oView']->display('proxy_addgroup.html');
            }
        }
        /**
         * @desc 编辑商户组
         * @author rhovin
         * @date 2017-06-17
         */
        public function actionEditGroup() {
            $groupid = intval($_GET['groupid']); //当前编辑的组id
            $oProxyGroup = A::singleton('model_proxygroup');
            $oProxyGroup->lvtopid = $this->lvtopid; //定义商户模型id 只允许当前商户操作模型
            if($this->getIsPost()) {
                $aGetData = $this->post(array(
                    "parentid" => parent::VAR_TYPE_INT, //父级id
                    "groupname" => parent::VAR_TYPE_STR,
                    "description" => parent::VAR_TYPE_STR,
                ));
                if($aGetData['groupname'] == '') $this->ajaxMsg(0,"商户组名称不能为空");
                $mResult = $oProxyGroup->updateGroup($groupid,$aGetData);
                if ($mResult) {
                    $this->ajaxMsg(1,"修改成功");
                } else {
                    $this->ajaxMsg(0,$oProxyGroup->_errMsg);
                }
                exit;
            }else{
                $aGroupInfo = $oProxyGroup->proxyGroup($groupid);
                $GLOBALS['oView']->assign('groupinfo', $aGroupInfo);
                $oProxyUser = new model_proxyuser();
                $aUserGroupInfo = $oProxyUser->getProxyUserGroup($this->loginProxyId);
                $aProxyGroup = $oProxyGroup->getProxyGroupSelect($this->lvtopid,$aUserGroupInfo['parentid'],$aGroupInfo['parentid'], TRUE);
                $GLOBALS['oView']->assign('proxygroup', $aProxyGroup);
                $GLOBALS['oView']->display('proxy_editgroup.html');
            }
        }
        /**
         * @desc 分配商户组权限
         * @author rhovin
         * @date 2017-06-14
         */
        public function actionSetPermission() {
            $aGetData = $this->get(array(
                    "parentid" => parent::VAR_TYPE_INT,//父级ID
                    "groupid" => parent::VAR_TYPE_INT,//需要分配权限的组的ID
            ));
            $aGetData['parentid'] = $aGetData['parentid'] == 0 ? $aGetData['groupid'] : $aGetData['parentid'] ;
            $oProxyGroup = A::singleton('model_proxygroup');
            //当前用户
            $aCurrGroup = $oProxyGroup->proxygroup($aGetData['groupid']);
            if($aCurrGroup['lvtopid'] != $this->lvtopid) sysMessage("非法操作", 1);
            //根据父级ID读取父级权限
            $aParentGroup = $oProxyGroup->proxygroup($aGetData['parentid']);
            $aCurrGroupStr = array_unique(explode(",", $aCurrGroup["menustrs"]));
            $oProxyMenu = new model_proxymenu();
            $aProxyMenus = $oProxyMenu->getMenuListById($aParentGroup['menustrs']); // 获取所有菜单
            unset($oProxyMenu); // 就近释放
            $aMenus = array();
            foreach ($aProxyMenus as $v) {
                $aMenus[$v["parentid"]][$v["menuid"]]["menuid"] = $v["menuid"];
                $aMenus[$v["parentid"]][$v["menuid"]]["title"] = $v["title"];
                $aMenus[$v["parentid"]][$v["menuid"]]["check"] = in_array($v["menuid"], $aCurrGroupStr);
            }
            foreach ($aMenus as $key => $value) {
                $a[$key] = count($value);
            }
            if(!isset($a[0])) {
                $a[0] = 0;
            }
            unset($a[0]);
            $GLOBALS['oView']->assign('counts', $a);
            $GLOBALS['oView']->assign('form_action', 'savepermission');
            $GLOBALS['oView']->assign('groupid', $aGetData['groupid']);
               $GLOBALS['oView']->assign('menus', $aMenus);
            $GLOBALS['oView']->display('proxy_setpermission.html');
        }
        /**
         * @desc 保存商户组权限
         * @author rhovin
         * @date 2017-06-15
         */
        public function actionSavePermission() {
            $aMenustrs = (array)$_POST['menustrs']; //组权限
            $groupid = intval($_POST['groupid']);   //组id
            if (empty($aMenustrs)) {
                   sysMessage("'组别菜单权限'未分配, 请检查", 1);
               }
               $oProxyGroup = A::singleton('model_proxygroup');
               $oProxyGroup->lvtopid = $this->lvtopid;//设定商户id 只允许当前商户操作模型
               $sMenustr = implode(',', $aMenustrs);
               $mResult = $oProxyGroup->updateGroupPermission($groupid,$sMenustr);
               if($mResult){
                   sysMessage("分配权限成功",0,[0=>["href"=>"proxy_grouplist.shtml","text"=>"返回上一页"]]);
               } else {
                   sysMessage("分配权限失败",1,[0=>["href"=>"proxy_grouplist.shtml","text"=>"返回上一页"]]);
               }
        }
        /**
         * @desc 复制商户组
         * @author rhovin
         * @date 2017-06-20
         */
        public function actionCopyGroup() {
            $aGetData = $this->get(array(
                "groupid" => parent::VAR_TYPE_INT,
            ));
            $oProxyGroup = new model_proxygroup();
            $aProxyGroup = $oProxyGroup->proxygroup($aGetData['groupid']);
            if($aProxyGroup['lvtopid'] != $this->lvtopid) sysMessage("非法操作");
            $aProxyGroup['groupname'] = $aProxyGroup['groupname'].'(复制组)';
            unset($aProxyGroup['groupid']);
            $mResult = $oProxyGroup->copyGroup($aProxyGroup);
            if ($mResult) {
                sysMessage("复制成功");
            } else {
                sysMessage("复制失败", 1);
            }
            
        }
        /**
         * @desc 锁定/解锁商户管理组
         * @author rhovin
         * @date 2017-06-13
         */
        public function actionLockGroup() {
            $aGetData = $this->get(array(
                "groupid" => parent::VAR_TYPE_INT,
                "isdisabled" => parent::VAR_TYPE_INT,
            ));
            $oProxyGroup = new model_proxygroup();
            $oProxyGroup->lvtopid = $this->lvtopid; //设定商户id 只允许当前商户操作模型
            $aData['isdisabled'] = $aGetData['isdisabled'];
            $bResult = $oProxyGroup->lockGroup($aGetData['groupid'],$aData);
            if ($bResult) {
                sysMessage("操作成功");
            }else{
                sysMessage("操作失败", 1);
            }
        }
        /**
         * @desc 删除商户管理组
         * @author rhovin
         * @date 2017-06-13
         */
        public function actionDelGroup() {
            $aGetData = $this->get(array(
                "groupid" => parent::VAR_TYPE_INT,
            ));
            $oProxyGroup = new model_proxygroup();
            $oProxyGroup->lvtopid = $this->lvtopid;//设定商户id 只允许当前商户操作模型
            $bResult = $oProxyGroup->delete($aGetData['groupid']);
            if ($bResult == 1) {
                sysMessage("删除成功");
            }elseif($bResult == "-1"){
                sysMessage("删除失败，商户组别不存在", 1);
            }elseif($bResult == "-2"){
                sysMessage("删除失败，含有下级的管理员分组", 1);
            }elseif($bResult == "-3"){
                sysMessage("删除失败，组别中含有用户", 1);
            }
        }
        /**
         * @desc 商户独立权限分配
         * @author rhovin
         * @date 2017-06-16
         */
        public function actionUserPermission() {
            $aGetData = $this->get(array(
                    "proxyadminid" => parent::VAR_TYPE_INT,//父级ID
                    "groupid" => parent::VAR_TYPE_INT,//需要分配权限的组的ID
            ));
            //当前登录商户的ID
            $iLoginProxyId = $this->loginProxyId;
            $oProxyUser = A::singleton('model_proxyuser');
            //查询用户是否存在独立权限
            $aProxyUser = $oProxyUser->getAdminNameById($aGetData['proxyadminid']);
            if($aProxyUser['lvtopid'] != $this->lvtopid) sysMessage("非法操作", 1);
            //查询登录用户的信息
            $aLoginProxyUser = $oProxyUser->getProxyUserGroup($iLoginProxyId);
            //如果存在用户独立权限，将登录用的户组权限和用户独立权限合并
            $aUsermenus = [];
            if(!empty($aProxyUser['menustrs'])){
                $aUserOwnMenus = explode(',', $aProxyUser['menustrs']);
                $sAllMenuStr =     $aLoginProxyUser['gmenustrs'].','.$aProxyUser['menustrs'];
                foreach ($aUserOwnMenus as $key => $value) {
                    $aUsermenus[$value] = true;
                }
            } else {
                $sAllMenuStr = $aLoginProxyUser['gmenustrs'];
            }
            $oProxyGroup = A::singleton('model_proxygroup');
            //需分配权限的商户的组权限
            $aCurrGroup = $oProxyGroup->proxygroup($aGetData['groupid']);
            $aCurrGroupStr = array_unique(explode(",", $aCurrGroup["menustrs"]));
            $oProxyMenu = new model_proxymenu();
            $aProxyMenus = $oProxyMenu->getMenuListById($aLoginProxyUser['gmenustrs']); // 获取登录用户的组权限
            unset($oProxyMenu); // 就近释放
            $aMenus = array();
            foreach ($aProxyMenus as $v) {
                $aMenus[$v["parentid"]][$v["menuid"]]["menuid"] = $v["menuid"];
                $aMenus[$v["parentid"]][$v["menuid"]]["title"] = $v["title"];
                $aMenus[$v["parentid"]][$v["menuid"]]["check"] = in_array($v["menuid"], $aCurrGroupStr);
            }
            foreach ($aMenus as $key => $value) {
                $a[$key] = count($value);
            }
            if(!isset($a[0])) {
                $a[0] = 0;
            }
            unset($a[0]);
            $GLOBALS['oView']->assign('counts', $a);
            $GLOBALS['oView']->assign('form_action', 'saveuserpermission');
            $GLOBALS['oView']->assign('proxyadminid', $aGetData['proxyadminid']);
               $GLOBALS['oView']->assign('menus', $aMenus);
                $GLOBALS['oView']->assign('usermenus', $aUsermenus);
            $GLOBALS['oView']->display('proxy_userpermission.html');
        }
        /**
         * desc用户独立权限保存
         * @author rhovin
         * @date 2017-06-16
         */
        function actionSaveUserPermission() {
            $aMenustrs = (array)$_POST['menustrs']; //商户独立权限
            $iProxyAdminid = intval($_POST['proxyadminid']);   //商户id
            $sMenustr = implode(',', $aMenustrs);
            $oProxyUser = new model_proxyuser();
            $oProxyUser->lvtopid = $this->lvtopid; //验证是否是当前商户
            $mResult = $oProxyUser->updateUserPermission($iProxyAdminid,$sMenustr);
            if($mResult) {
                sysMessage("分配权限成功",0,[0=>["href"=>"proxy_memberlist.shtml","text"=>"返回上一页"]]);
            }else{
                sysMessage("分配权限失败",1,[0=>["href"=>"proxy_memberlist.shtml","text"=>"返回上一页"]]);
            }
        }
        /**
         * @desc 商户管理员列表
         * @author rhovin
         * @2017-06-12
         */
        public function actionMemberList() {
            $oProxyUser = new model_proxyuser();
            if($this->getIsAjax()) {
                //表单提交时条件
                $aGetData = $this->post(array(
                    "page" => parent::VAR_TYPE_INT,
                    "rows" => parent::VAR_TYPE_INT,
                    "adminname" => parent::VAR_TYPE_STR,
                    "groupid" => parent::VAR_TYPE_INT,
                ));
                //商户组别列表传过来的groupid
                $iGetGroupid = isset($_GET['groupid']) ? intval($_GET['groupid']) : 0 ;
                $sWhere = " a.lvtopid=".$this->lvtopid." AND is_del=0";
                $groupid = $aGetData['groupid'] == 0 ? $iGetGroupid : intval($aGetData['groupid']);
                if($groupid > 0){
                    $sWhere .= " AND a.groupid=$groupid";
                }
                //首次读取时条件
                $aGetData['page'] = isset($_GET['page']) ? intval($_GET['page']) : $aGetData['page'];
                $aGetData['rows'] = isset($_GET['rows']) ? intval($_GET['rows']) : $aGetData['rows'];
                
                
                if(!empty($aGetData['adminname'])) { 
                    $sWhere.= " AND a.adminname='".$aGetData['adminname']."'";
                }
                $aResult = $oProxyUser->getAdminList("",$sWhere,$aGetData['rows'],$aGetData['page']);
                //$aProxyUser = $oProxyUser->getAdminNameById($aGetData['proxyadminid']);
                $oPuser = new model_puser();
                $aPuser = $oPuser->getUsernameByUserId($this->lvtopid);
                foreach ($aResult['results'] as $key => &$value) {
                    if($value['adminname'] == $aPuser['username']) {
                        $value['isLvTop'] = TRUE; //总代
                    } else {
                        $value['isLvTop'] = FALSE; //非总代
                    }
                }
                if (!empty($aResult)) {
                    $this->outPutJQgruidJson($aResult['results'] , $aResult['affects'],$aGetData['page'],$aGetData['rows']);
                }
            } else {
                $oProxyGroup = new model_proxygroup();
                $aUserGroupInfo = $oProxyUser->getProxyUserGroup($this->loginProxyId);
                if(!empty($aUserGroupInfo)) {
                    $aProxyGroup = $oProxyGroup->getProxyGroupSelect($this->lvtopid,$aUserGroupInfo['parentid'],$aUserGroupInfo['groupid'], TRUE);
                    $GLOBALS['oView']->assign('proxygroup', $aProxyGroup);
                }
                $GLOBALS['oView']->assign('groupid', $aUserGroupInfo['groupid']);
                $GLOBALS['oView']->display('proxy_memberlist.html');
            }
        }
        /**
         * @desc 添加商户管理员
         * @author rhovin
         * @2017-06-12
         */
        public function actionAddMember() {
            if ($this->getIsPost()) {
                $aGetData = $this->post(array(
                    "adminteam" => parent::VAR_TYPE_INT,
                    "adminuser" => parent::VAR_TYPE_STR,
                    "adminnick" => parent::VAR_TYPE_STR,
                    "adminlang" => parent::VAR_TYPE_STR,
                    "adminpass" => parent::VAR_TYPE_STR,
                    "adminpass2" => parent::VAR_TYPE_STR,
                ));
                if($aGetData['adminpass2'] != $aGetData['adminpass']) $this->ajaxMsg(0,"两次输入密码不一致");
                $aGetData['lvtopid'] = $this->lvtopid;
                $oProxynuser = new model_proxyuser();
                $bResult = $oProxynuser->addProxyUser($aGetData);
                if($bResult){
                    $this->ajaxMsg(1,"操作成功");
                }else{
                    $this->ajaxMsg(0,$oProxynuser->_errMsg);
                }
            } else {
                $oProxyGroup = new model_proxygroup();
                $oProxyUser = new model_proxyuser();
                $aUserGroupInfo = $oProxyUser->getProxyUserGroup($this->loginProxyId);
                $aProxyGroup = $oProxyGroup->getProxyGroupSelect($this->lvtopid,$aUserGroupInfo['parentid'],$aUserGroupInfo['groupid'], TRUE);
                //$aProxyGroupList = $oProxyGroup->getProxyGroupList($iCurrentGroupId, 0, TRUE);
                $GLOBALS['oView']->assign('proxygroup', $aProxyGroup);
                $GLOBALS['oView']->display('proxy_addmember.html');
            
            }
        }
        /**
         * @desc 编辑商户管理员
         * @author rhovin
         * @date 2017-06-17
         */
        public function actionEditMember() {
            $proxyadminid = intval($_GET['proxyadminid']);
            $oProxyUser = new model_proxyuser();
            $aProxyUser = $oProxyUser->getProxyUserGroup($proxyadminid);
            if($aProxyUser['lvtopid'] != $this->lvtopid) $this->ajaxMsg(0,"非法操作");
            if($this->getIsPost()) {
                $aGetData = $this->post(array(
                    "groupid" => parent::VAR_TYPE_INT,
                    "adminname" => parent::VAR_TYPE_STR,
                    "adminnick" => parent::VAR_TYPE_STR,
                    "adminlang" => parent::VAR_TYPE_STR,
                    "adminpass" => parent::VAR_TYPE_STR,
                    "adminpass2" => parent::VAR_TYPE_STR,
                ));

                if($aGetData['adminpass'] != $aGetData['adminpass2']) $this->ajaxMsg(0,"两次输入密码不一致");
                if (!empty($aGetData['adminpass']) && !$oProxyUser->checkAdminPass($aGetData['adminpass'])) { //管理员密码不符合规则
                   $this->ajaxMsg(0,"管理员密码不符合规则");
                }
                $aGetData['adminpass'] = $aGetData['adminpass'] != "" ? md5($aGetData['adminpass']) : "";
                //需要更新的数据存在，并且提交的数据不为空并且有变化的数据才更新
                $aData = [] ;
                foreach ($aGetData as $key => $value) {
                    if( isset($aProxyUser[$key])) {
                         if($aProxyUser[$key] == $aGetData[$key] || $aGetData[$key] == "" ) {
                            continue ;
                         } else {
                            $aData[$key] = $value; 
                         }
                    }
                }
                $mResult = $oProxyUser->updateMember($aData,$proxyadminid);
                if($mResult){
                    $this->ajaxMsg(1,"保存成功");
                }else{
                    $this->ajaxMsg(0,$oProxyUser->_errMsg);
                }
            }
            $oProxyGroup = new model_proxygroup();
            $oProxyUser = new model_proxyuser();
            $iSelectUserId = isset($_GET['proxyadminid'])? intval($_GET['proxyadminid']) :$this->loginProxyId;
            $aUserGroupInfo = $oProxyUser->getProxyUserGroup($iSelectUserId);
            $aProxyGroup = $oProxyGroup->getProxyGroupSelect($this->lvtopid,$aUserGroupInfo['parentid'],$aUserGroupInfo['groupid'], TRUE);
            $GLOBALS['oView']->assign('proxygroup', $aProxyGroup);
            $GLOBALS['oView']->assign('proxyuser', $aProxyUser);
            $GLOBALS['oView']->display('proxy_editmember.html');
        }
        /**
         * @desc 删除商户管理成员
         * @author rhovin
         * @date 2017-06-13
         */
        public function actionDelMember() {
            $aGetData = $this->get(array(
                "proxyadminid" => parent::VAR_TYPE_INT,
            ));
            $oProxyUser = new model_proxyuser();
            $aProxyUser = $oProxyUser->getAdminNameById($aGetData['proxyadminid']);
            $oPuser = new model_puser();
            $aPuser = $oPuser->getUsernameByUserId($this->lvtopid);
            if($aProxyUser['adminname'] == $aPuser['username']) {
                sysMessage("不能删除总代", 1);
            }
            $oProxyUser->lvtopid = $this->lvtopid; //设定商户id 只允许当前商户操作模型
            $aData['is_del'] = 1;
            $bResult = $oProxyUser->deleteMember($aGetData['proxyadminid'],$aData);
            if ($bResult) {
                sysMessage("删除成功");
            }else{
                sysMessage("删除失败", 1);
            }
        }
        /**
         * @desc 锁定/解锁商户管理成员
         * @author rhovin
         * @date 2017-06-13
         */
        public function actionLockMember() {
            $aGetData = $this->get(array(
                "proxyadminid" => parent::VAR_TYPE_INT,
                "islocked" => parent::VAR_TYPE_INT,
            ));
            $oProxyUser = new model_proxyuser();
            $aProxyUser = $oProxyUser->getAdminNameById($aGetData['proxyadminid']);
            $oPuser = new model_puser();
            $aPuser = $oPuser->getUsernameByUserId($this->lvtopid);
             if($aProxyUser['adminname'] == $aPuser['username']) {
                 sysMessage("不能锁定总代", 1);
            }
            $oProxyUser->lvtopid = $this->lvtopid; //设定商户id 只允许当前商户操作模型
            $aData['islocked'] = $aGetData['islocked'];
            $bResult = $oProxyUser->lockMember($aGetData['proxyadminid'],$aData);
            if ($bResult) {
                sysMessage("操作成功");
            }else{
                sysMessage("操作失败", 1);
            }
        }
    }