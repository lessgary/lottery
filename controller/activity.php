<?php

/**
 * 文件 : /_app/controller/activity.php
 * 功能 : 控制器 - 活动管理
 *
 * @author    ken 2017
 * @version   3.0.0
 * @package   passportadmin
 */
class controller_activity extends pcontroller {
    /**
     * 活动列表
     * @author ken 2017
     */
    function actionList()
    {
        if ($this->getIsAjax()) {
            $aGetData = $this->get(array(
                "rows" => parent::VAR_TYPE_INT,
                "page" => parent::VAR_TYPE_INT,
            ));
        
            if (empty($aGetData['rows'])) {
                $aGetData['rows'] = 25;
            }
            if (empty($aGetData['page'])) {
                $aGetData['page'] = 1;
            }
            //拼接where 条件语句
            $sWhere = ' 1 '; //init
            if ($this->getIsPost()) {
                $aGetData = $this->post(array(
                    "rows" => parent::VAR_TYPE_INT,
                    "page" => parent::VAR_TYPE_INT,
                    'title' => parent::VAR_TYPE_STR,
                    'disable' => parent::VAR_TYPE_INT,
                    "sidx" => parent::VAR_TYPE_STR,
                    "sord" => parent::VAR_TYPE_STR,
                ));
                if (isset($aGetData['title']) && !empty($aGetData['title'])) {
                    $sWhere .= " AND `title` = '{$aGetData['title']}' ";
                }
                if ($aGetData['disable'] == 0) {
                    $sWhere .= " AND `disable` = 0 ";
                }
                if ($aGetData['disable'] == 1) {
                    $sWhere .= " AND `disable` = 1 ";
                }
                if ($aGetData['disable'] == 2) {
                    $sWhere .= " AND `disable` = 2 ";
                }
                $oProxyActivity = new model_proxyactivity();
                $sOrderBy = $aGetData['sidx'] == '' ? "`sort` ${aGetData['sord']}" :"${aGetData['sidx']} ${aGetData['sord']}";
                $aResult = $oProxyActivity->getActivityList('*', $sWhere, $aGetData['rows'], $aGetData['page'],$this->lvtopid,$sOrderBy);
                foreach ($aResult['results'] as $kk => $vv) {
                    $arr = $aResult['results'][$kk]['proxyadminid'];
                    $rst = $oProxyActivity->getUsernameByadminId($arr);
                    $aResult['results'][$kk]['adminname'] = $rst['adminname'];
                    if (!empty($aResult['results'][$kk]['image'])){
                    $aResult['results'][$kk]['image'] = getImageLoadUrl().$aResult['results'][$kk]['image'];
                    }
                }
                if (!empty($aResult) && !empty($aGetData)) {
                    $this->outPutJQgruidJson($aResult['results'], $aResult['affects'], $aGetData['page'], $aGetData['rows']);
                }
            }
        }
        $GLOBALS['oView']->display("activity_list.html");
        EXIT;
    }
    
    /**
     * 查看活动
     * @author ken 2017
     */
    function actionView() {
        $aLocation = array();
        $aLocation[0] = array("text" => '增加活动', 'href' => url('activity', 'add'));
        $aLocation[1] = array("text" => '活动列表', 'href' => url('activity', 'list'));
        $iActivityId = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
        if ($iActivityId == 0) {
            sysMessage('请指定活动ID', 1);
        }
        /* @var $oActivity model_activity */
        $oActivity = A::singleton('model_activity');
        $aResult = $oActivity->getItem($iActivityId);
        $aResult['channelid'] = explode(",", $aResult['channelid']);
        if (in_array(0, $aResult['channelid'])) {
                $GLOBALS['oView']->assign('public', 1);
            }
        $GLOBALS['oView']->assign('result', $aResult);
        $GLOBALS['oView']->assign('action', "view");
        $GLOBALS['oView']->assign('ur_here', '查看活动 ');
        $GLOBALS['oView']->assign("actionlink", $aLocation[0]);
        $GLOBALS['oView']->assign("actionlink2", $aLocation[1]);
        $oActivity->assignSysInfo();
        $GLOBALS['oView']->display("activity_info.html");
        EXIT;
    }

    /**
     *
     * 编辑活动
     *
     * @author by ken 2017
     */
    function actionEdit() {
        $oProxyactivity = new model_proxyactivity();
        if ($this->getIsGet()) {
            $aGetData = $this->get(array(
                'id' => parent::VAR_TYPE_INT,
            ));
            // 判断id 是否存在
            $aResult = $oProxyactivity->getItem($aGetData['id'],$this->lvtopid);
            if ($aResult == -1) {
                $GLOBALS['oView']->assign("tclose", 1);
            }else{
                $GLOBALS['oView']->assign("tclose", 2);
            }
            if (isset($aResult['image']) && $aResult['image'] !== '') {
                $GLOBALS['oView']->assign('isImg', 1);
            }
            $dToday = $this->sToday;
            $GLOBALS['oView']->assign('today', $dToday);
            $GLOBALS['oView']->assign('result', $aResult);
            $GLOBALS['oView']->assign('ur_here', '优惠活动页面 ');//URL
            $GLOBALS['oView']->assign('action', "edit");
            $GLOBALS['oView']->assign('image_load_url', getImageLoadUrl());
            $GLOBALS['oView']->display("activity_edit.html");
            EXIT;
        }
        
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "id"    => parent::VAR_TYPE_INT,
                "title" => parent::VAR_TYPE_STR,
                "proxyadminid" => parent::VAR_TYPE_INT,
                "lvtopid" => parent::VAR_TYPE_INT,
                "app_type" => parent::VAR_TYPE_INT,
                "desc" => parent::VAR_TYPE_STR,
                "detail" => parent::VAR_TYPE_STR,
                "starttime" => parent::VAR_TYPE_DATETIME,
                "endtime" => parent::VAR_TYPE_DATETIME,
                //"sort" => parent::VAR_TYPE_INT,
                "disable" => parent::VAR_TYPE_INT,
                "lasttime" => parent::VAR_TYPE_DATETIME,
                'web_banner' => parent::VAR_TYPE_STR,
                'app_detail' => parent::VAR_TYPE_STR,
                'app_banner' => parent::VAR_TYPE_STR,
            ));
            if (isset($_POST['flag']) == 'sol'){
                $bResult = $oProxyactivity->partEdit($aGetData['id'], $this->lvtopid, ['disable'=>$aGetData['disable']]);
                if ($bResult){
                    $this->ajaxMsg(1,"活动修改成功");
                    return ['data' => 1];
                }else{
                    $this->ajaxMsg(2,"活动修改成功");
                    return ['data' => 2];
                }
            }
            if (empty($aGetData['detail'])) {
                $this->ajaxMsg(-109,"活动详情不能为空");
            }
            $aGetData['lvtopid'] = $this->lvtopid ; //总代ID
            $aGetData['proxyadminid'] = $this->loginProxyId; //当前登录的商户ID
            //更新活动图片
            $iActivityId = $aGetData['id'];
            $iFlag = $this->_updateData($aGetData);
            if ($iFlag > 0) {
                $aUpdateData = $iFlag;
            }
//             $aSortData = $oProxyactivity->getSortByid($this->lvtopid , $aGetData['sort']);
//             if(!empty($aSortData)) {
//                 $iFlag = '-109';
//             }
            switch ($iFlag) {
                case -100:
                    $this->ajaxMsg(-100,"活动标题不能为空");
                    break;
                case -101:
                    $this->ajaxMsg(-101,"活动内容不能为空");
                    break;
                case -102:
                    $this->ajaxMsg(-102,"活动开始时间不能为空");
                    break;
                case -103:
                    $this->ajaxMsg(-103,"活动结束时间不能为空");
                    break;
                case -104:
                    $this->ajaxMsg(-104,"活动结束时间不能早于开始时间");
                    break;
                case -105:
                    $this->ajaxMsg(-105,"请上传活动图片");
                    break;
                case -106:
                    $this->ajaxMsg(-106,"活动图片上传错误");
                    break;
                case -107:
                    $this->ajaxMsg(-107,"活动开始时间和结束时间不能相同");
                    break;
                case -108:
                    $this->ajaxMsg(-108,"活动结束时间不能小于今天({$this->sToday})");
                    break;
                case -109:
                    $this->ajaxMsg(-109,"排序值不能重复");
                    break;
            }
            if (!empty($_FILES['web_banner_new']) && !empty($_FILES['web_banner_new']['name'])) {
                $iResult = $this->_uploadImage($_FILES['web_banner_new']);//处理图片
                if (is_numeric($iResult)) {
                    $this->ajaxMsg(-106,"活动图片上传错误");
                }
                $aUpdateData['web_banner_new'] = isset($iResult) ? $iResult : '';
            }
           /* if (!empty($_FILES['web_content_new']) && !empty($_FILES['web_content_new']['name'])) {
                $iResult = $this->_uploadImage($_FILES['web_content_new']);//处理图片
                if (is_numeric($iResult)) {
                    $this->ajaxMsg(-106,"活动图片上传错误");
                }
                $aUpdateData['web_content_new'] = isset($iResult) ? $iResult : '';
            }*/
            if (!empty($_FILES['app_banner_new']) && !empty($_FILES['app_banner_new']['name'])) {
                $iResult = $this->_uploadImage($_FILES['app_banner_new']);//处理图片
                if (is_numeric($iResult)) {
                    $this->ajaxMsg(-106,"活动图片上传错误");
                }
                $aUpdateData['app_banner_new'] = isset($iResult) ? $iResult : '';
            }
            if (!empty($_FILES['app_content_new']) && !empty($_FILES['app_content_new']['name']) ) {
                $iResult = $this->_uploadImage($_FILES['app_content_new']);//处理图片
                if (is_numeric($iResult)) {
                    $this->ajaxMsg(-106,"活动图片上传错误");
                }
                $aUpdateData['app_content_new'] = isset($iResult) ? $iResult : '';
            }
            $lvtopid = $this->lvtopid;
            $bResult = $oProxyactivity->edit($iActivityId, $aUpdateData, $lvtopid);
            if ($bResult === TRUE) {
                $this->ajaxMsg(1,"活动修改成功");
                EXIT;
            }
        }
    }

    /**
     * 增加活动
     * @review by ken 2017
     * inuse
     */
    function actionAdd() {
        $aData =[];//init
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "id" => parent::VAR_TYPE_INT,
                "app_type" => parent::VAR_TYPE_INT,
                "title" => parent::VAR_TYPE_STR,
                "desc" => parent::VAR_TYPE_STR,
                "detail" => parent::VAR_TYPE_STR,
                "app_detail" => parent::VAR_TYPE_STR,
                "starttime" => parent::VAR_TYPE_DATETIME,
                "endtime" => parent::VAR_TYPE_DATETIME,
                "sort" => parent::VAR_TYPE_INT,
               // "disable" => parent::VAR_TYPE_INT,
                "lasttime" => parent::VAR_TYPE_DATETIME,
            ));

            if($aGetData['sort']<=0){
                $this->ajaxMsg(-109,"排序值不能重复");
            }
            $oProxyactivity = new model_proxyactivity();
            $iFlag = $this->_checkData($aGetData,$_FILES);
            $aSortData = $oProxyactivity->getSortByid($this->lvtopid , $aGetData['sort']);
            if(!empty($aSortData)) {
                $iFlag = '-109';
            }
            if ($iFlag > 0) {
                $aData = $iFlag;
            }
            switch ($iFlag) {
                case -100:
                    $this->ajaxMsg(-100,"活动标题不能为空");
                    break;
                case -101:
                    $this->ajaxMsg(-101,"活动内容不能为空");
                    break;
                case -102:
                    $this->ajaxMsg(-102,"活动开始时间不能为空");
                    break;
                case -103:
                    $this->ajaxMsg(-103,"活动结束时间不能为空");
                    break;
                case -104:
                    $this->ajaxMsg(-104,"活动结束时间不能早于开始时间");
                    break;
                case -105:
                    $this->ajaxMsg(-105,"请上传活动图片");
                    break;
                case -106:
                    $this->ajaxMsg(-106,"活动图片上传错误");
                    break;
                case -107:
                    $this->ajaxMsg(-107,"活动开始时间和结束时间不能相同");
                    break;
                case -108:
                    $this->ajaxMsg(-108,"活动结束时间不能小于今天({$this->sToday})");
                    break;
                case -109:
                    $this->ajaxMsg(-109,"排序值不能重复");
                    break;
            }
            if (empty($aGetData['detail'])) {
                $this->ajaxMsg(-109,"活动详情不能为空");
            }
            $bResult = $oProxyactivity->insert($aData);
            if (TRUE === $bResult ) {
                $this->ajaxMsg(1,"活动发布成功");
            } else {
                $this->ajaxMsg(-107,"活动发布失败");
            }
            EXIT;
        }
        $dToday = $this->sToday;
        $GLOBALS['oView']->assign('today', $dToday);
        $GLOBALS['oView']->display("activity_add.html");
        EXIT;
    }
    
    /**
     * 启用/禁用活动
     */
    function actionEnable() {
        $aLocation = array();
        $aLocation[0] = array("text" => '活动列表', 'href' => url('activity', 'list'));
        /* @var $oActivity model_activity */
        $oActivity = A::singleton('model_activity');
        if (isset($_GET) && !empty($_GET)) {
            $iActivityId = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
            if ($iActivityId == 0) {
                sysMessage('请指定活动ID', 1, $aLocation);
            }
            $iDisable = isset($_GET['disable']) && is_numeric($_GET['disable']) ? intval($_GET['disable']) : 0;
            $bResult = $oActivity->enable($iActivityId, $iDisable);
            if ($bResult === TRUE) {
                sysMessage('更新成功', 0, $aLocation);
            } else {
                sysMessage('更新失败', 1, $aLocation);
            }
        }
    }

    /**
     * 重新设置活动：清空活动的历史数据，保存到备份表中
     */
    function actionReset() {
        $aLocation = array();
        $aLocation[0] = array("text" => '活动列表', 'href' => url('activity', 'list'));
        /* @var $oActivity model_activity */
        $oActivity = A::singleton('model_activity');
        if (isset($_GET) && !empty($_GET)) {
            $iActivityId = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
            if ($iActivityId == 0) {
                sysMessage('请指定活动ID', 1, $aLocation);
            }
            $bResult = $oActivity->reset($iActivityId);
            if ($bResult === TRUE) {
                sysMessage('更新成功', 0, $aLocation);
            } else {
                sysMessage('更新失败', 1, $aLocation);
            }
        }
    }

    /**
     * 删除活动
     * @author ken 2017
     */
    function actionDelete() {
        $aGetData = $this->post(array(
            'id' => parent::VAR_TYPE_INT,
            'delimg' => parent::VAR_TYPE_INT
        ));
        $oProxyAct = new model_proxyactivity();
        $delImg = isset($aGetData['delimg']) ? $aGetData['delimg'] : '';
        if ($delImg == 1) {
            $iFlag = $oProxyAct->deleteActivity($aGetData,$this->lvtopid);
            if ($iFlag > 0) {
                $this->ajaxMsg(1,'图片删除成功');
            }else{
                $this->ajaxMsg(2,'图片删除失败');
            }
        }
        unset($iFlag);
        $iFlag = $oProxyAct->deleteActivity($aGetData,$this->lvtopid);
        if ($iFlag > 0) {
            $this->ajaxMsg(1,'活动删除成功');
        }else{
            $this->ajaxMsg(2,'活动删除失败');
        }

    }
    /**
     *
     * desc 编辑排序
     * @rhovin 2017-12-01
     *
     */
    public function actionEditSort() {
         $aGetData = $this->post(array(
            'id' => parent::VAR_TYPE_INT,
            'sort' => parent::VAR_TYPE_INT,
            'newsort' => parent::VAR_TYPE_INT
        ));
         $oProxyAct = new model_proxyactivity();
         $aResult = $oProxyAct->getSortByid($this->lvtopid , $aGetData['newsort']);
         if ($aGetData['newsort']<=0){
             $this->ajaxMsg(0,'排序值不能为0');
         }
         if(!empty($aResult)){
            $this->ajaxMsg(0,'排序值不能重复');
         } else {
             $bResult = $oProxyAct->partEdit($aGetData['id'], $this->lvtopid, ['sort'=>$aGetData['newsort']]);
                if ($bResult){
                    $this->ajaxMsg(1,"已修改");
                }else{
                    $this->ajaxMsg(0,"修改失败");
                   
                }
         }
    }


    /**
     * 管理所有活动图片
     */
    function actionManageImage() {
        $aLocation[0] = array("text" => '管理活动图片', 'href' => url('activity', 'manageimage'));
        /* @var $oActivity model_activity */
        $oActivity = A::singleton('model_activity');
        $sBaseDir = $oActivity->getImageBasePath(); //活动图片根目录
        if (isset($_GET['dir'])) {//指定目录文件
            $sBaseDir.=$_GET['dir'] . DS;
        }
        require A_DIR . DS . 'includes' . DS . 'plugin' . DS . 'filefunc.php';
        if (isset($_POST) && !empty($_POST)) {
            $iAction = isset($_POST['action']) ? intval($_POST['action']) : 0;
            if ($iAction == 0) {
                sysMessage('请选择操作', 1, $aLocation);
            } elseif ($iAction == 1) {//删除文件或者目录
                if (empty($_POST['checkboxes'])) {
                    sysMessage('请选择删除项', 1, $aLocation);
                }
                foreach ($_POST['checkboxes'] as $sName) {
                    $sFullName = $sBaseDir . $sName;
                    if (!is_dir($sFullName)) {
                        @unlink($sFullName);
                    } else {
                        $aTmpData = scandir($sFullName);
                        foreach ($aTmpData AS $k => $v) {
                            if (preg_match("/^\./", $v)) {
                                unset($aTmpData[$k]);
                                continue;
                            }
                        }
                        if (count($aTmpData) == 0) {
                            @rmdir($sFullName);
                        } else {
                            removeDir($sFullName);
                        }
                    }
                }
            } elseif ($iAction == 2) {//创建新目录
                $sNewDirName = isset($_POST['newdir']) ? daddslashes($_POST['newdir']) : "";
                if ($sNewDirName == "") {
                    sysMessage('请填写新目录名称', 1, $aLocation);
                } else {
                    $sPath = $sBaseDir . $sNewDirName;
                    if (!file_exists($sPath)) {
                        @mkdir($sPath, 0777, true);
                        @chdir($sPath, 0777);
                    } else {
                        sysMessage('操作失败：目录已经存在', 1, $aLocation);
                    }
                }
            } elseif ($iAction == 3) {//上传新文件
                if ($_FILES["imagefile"]['name'] != "") {
                    $sAllowedExtension = "jpg|png|gif|swf";        // 接受的文件类型
                    $iAllowedMinSize = 10;         // 上传文件的最小字节
                    $iAllowedMaxSize = 204800 * 200;        // 上传文件的最大字节
                    $_FILES["imagefile"]['changename'] = FALSE;
                    $aFile = saveUploadFile($_FILES["imagefile"], $sBaseDir, $sAllowedMime, $sAllowedExtension, $iAllowedMinSize, $iAllowedMaxSize);
                    if ($aFile['code'] != 0) {
                        sysMessage('操作失败', 1, $aLocation);
                    }
                } else {
                    sysMessage('请选择上传的文件', 1, $aLocation);
                }
            }
            sysMessage('操作成功', 0, $aLocation);
            exit;
        }
        $aDirFileList = scandir($sBaseDir);
        $aFileName = array(); // 最终文件
        $sBaseUrl = $oActivity->getImageBaseUrl(); //活动图片根目录
        foreach ($aDirFileList AS $k => $v) {
            if (preg_match("/^\./", $v)) {
                unset($aDirFileList[$k]);
                continue;
            }
            $aTmp = array();
            if (is_dir($sBaseDir . $v)) {
                $aTmp['type'] = 0;
                if (isset($_GET['dir'])) {//指定目录文件
                    $aTmp['url'] = getUrl(TRUE, FALSE) . "/" . $v . "";
                } else {
                    $aTmp['url'] = getUrl(TRUE, FALSE) . "?dir=" . $v;
                }
            } else {
                $aTmp['type'] = 1;
                if (isset($_GET['dir'])) {//指定目录文件
                    $aTmp['url'] = $sBaseUrl . $_GET['dir'] . "/" . $v;
                } else {
                    $aTmp['url'] = $sBaseUrl . $v;
                }
            }
            $aTmp['name'] = $v; // 临时数组
            $aTmp['dirlasttime'] = date("Y-m-d H:i:s", filemtime($sBaseDir . $v));
            array_push($aFileName, $aTmp);
        }
        $oActivity->assignSysInfo();
        $GLOBALS['oView']->assign("basedir", $sBaseDir);
        $GLOBALS['oView']->assign("ur_here", "活动图片管理");
        $GLOBALS['oView']->assign("aFileList", $aFileName);
        $GLOBALS['oView']->display("activity_manageimage.html");
        exit;
    }
    
    /**
     * 将图片转换成64位
     */
    private function _setImgToBase64(&$aResult)
    {
        foreach ($aResult['results'] as $k => $v) {
            $aPath[] = $aResult['results'][$k]['image']; //最后得出值 一维数组 所有图片路径的一维数组
        }
        $aPath = isset($aPath) ? $aPath : [];
        $sPath = $this->getPassportPath();
        //@TODO 后期修改
        $sItem['base64_content'] = '';
        foreach ($aPath as $v) {//遍历出相关的项目图片
            if (empty($v)) continue;
            $sFilePath = $sPath.$v;
            @$aFile = getimagesize($sFilePath);
            $sItem['base64_content'] = $aFile ? "data:{$aFile['mime']};base64," . chunk_split(base64_encode(file_get_contents($sFilePath))) : '';
        }
        foreach ($aResult['results'] as $k1 => $v1) {
            if (empty($aResult['results'][$k1]['image'])) {
                $aResult['results'][$k1]['base64_content'] = 0;
                continue;
            }
        
            $aResult['results'][$k1]['base64_content'] = $sItem['base64_content'];
        }
    }
    
    /**
     * 私有方法 传入一维数组
     * @param $aData
     * @return mixed
     * @author ken 2017
     */
    private function _getContentImage($aData)
    {
        if ($aData && !empty($aData['detail'])) {
            $aData['detail'] = imageSrcAdd($aData['detail']);
        }
        return $aData;
    }
    
    /**
     * 检查数据
     * @author ken 2017
     * @param $aGetData
     * @param $fFile
     * @return array|int
     */
    private function _checkData($aGetData, $fFile)
    {
        $dToday = $this->sToday;
        $aData = [];
        $aData['lvtopid'] = $this->lvtopid; //总代ID
        $aData['proxyadminid'] = $_SESSION['proxyadminid']; //当前登录的商户ID
        $aData['title'] = isset($aGetData['title']) ? $aGetData['title'] : "";

        if ($aData['title'] == '') {
            return -100;
        }
        $aData['starttime'] = isset($aGetData['starttime']) ?$aGetData['starttime'] : "";
        if ($aData['starttime'] == '') {
            return -102;
        }
        $aData['endtime'] = isset($aGetData['endtime']) ? $aGetData['endtime'] : "";
        if ($aData['endtime'] == '') {
            return -103;
        }
        if ($aData['starttime'] == $aData['endtime']) {
            return -107;
        }
        if ($aGetData['endtime'] < $dToday) {
            return -108;
        }
        if (strtotime($aData['starttime']) > strtotime($aData['endtime'])) {
            return -104;
        }
        if (!empty($fFile['web_banner']) && !empty($fFile['web_banner']['name'])) {
            $iResult = $this->_uploadImage($fFile['web_banner']);//处理图片
            if (is_numeric($iResult)) {
                return $iResult;
            }
            $aData['web_banner'] = $iResult;
        }
       /* if (!empty($fFile['web_content']) && !empty($fFile['web_content']['name'])) {
            $iResult = $this->_uploadImage($fFile['web_content']);//处理图片
            if (is_numeric($iResult)) {
                return $iResult;
            }
            $aData['web_content'] = $iResult;
        }*/
        if (!empty($fFile['app_banner']) && !empty($fFile['app_banner']['name'])) {
            $iResult = $this->_uploadImage($fFile['app_banner']);//处理图片
            if (is_numeric($iResult)) {
                return $iResult;
            }
            $aData['app_banner'] = $iResult;
        }
        if (!empty($fFile['app_content']) && !empty($fFile['app_content']['name']) ) {
            $iResult = $this->_uploadImage($fFile['app_content']);//处理图片
            if (is_numeric($iResult)) {
                return $iResult;
            }
            $aData['app_content'] = $iResult;
        }
        $aData['disable'] = isset($_POST['disable']) && is_numeric($_POST['disable']) ? intval($_POST['disable']) : 0;
        $aData['sort'] = isset($_POST['sort']) && is_numeric($_POST['sort']) ? intval($_POST['sort']) : 255;//活动排序
        $aData['detail'] = $aGetData['detail'];
        $aData['app_detail'] = $aGetData['app_detail'];
        $aData['app_type'] = $aGetData['app_type'];
        return $aData;
    }
    
    /**
     * 处理上传文件
     * @param $fFile
     * @return  mixed
     */
    private function _uploadImage($fFile)
    {
        if (!empty($fFile)) {
            a::loadFile('filefunc.php', A_DIR . DS . 'includes' . DS . 'plugin');
            $filePath = DS . 'upload' . DS . date('Ymd');
            $aResult = saveUploadFile(
                $fFile,
                $this->getPassportPath() . $filePath,
                'image',
                'gif|jpeg|jpg|png',
                0,
                2097152 //1024*1024*2
            );
            if (is_array($aResult)) {
                if (!empty($aResult['err_msg']) && 0 > $aResult['code']) {
                    return -106;
                } else {
                    $aData['image'] = daddslashes($filePath . DS . basename($aResult['name']));//避免数据库吃掉路径
                    return $aData['image'];
                }
            } else {
                return -106;
            }
        }
    }
    
    /**
     * 拼凑更新数据
     * @param $aGetData
     * @return array|int
     */
    private function _updateData($aGetData)
    {
        $dToday = $this->sToday;
        $iActivityId = $aGetData['id'];
        $aUpdateData = []; //init
        if ($iActivityId == null) {
           return -1000;
        }
        $aUpdateData['title'] = isset($aGetData['title']) ? $aGetData['title'] : ""; //活动标题
        if ($aUpdateData['title'] == '') {
            return -100;
        }

        $aUpdateData['starttime'] = isset($aGetData['starttime']) ? $aGetData['starttime'] : "";
        if ($aUpdateData['starttime'] == '') {
            return -102;
        }
    
        $aUpdateData['endtime'] = isset($aGetData['endtime']) ? $aGetData['endtime'] : "";
        if ($aUpdateData['endtime'] == '') {
            return -103;
        }
    
        if (strtotime($aUpdateData['starttime']) > strtotime($aUpdateData['endtime'])) {
            return -104;
        }
        if ($aGetData['starttime'] == $aGetData['endtime']) {
            return -107;
        }
        if ($aGetData['endtime'] < $dToday) {
            return -108;
        }
        $aUpdateData['disable'] = isset($aGetData['disable']) ? $aGetData['disable'] : 0;
    
        //$aUpdateData['sort'] = isset($aGetData['sort']) ? $aGetData['sort'] : 255;//活动排序

        $aUpdateData['web_banner'] = $aGetData['web_banner'];
        $aUpdateData['detail'] = $aGetData['detail'];
        $aUpdateData['app_banner'] = $aGetData['app_banner'];
        $aUpdateData['app_detail'] = $aGetData['app_detail'];
        $aUpdateData['app_type'] = $aGetData['app_type'];

        return $aUpdateData;
    }
    
    
}

