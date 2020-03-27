<?php
/**
 * Class notice
 * @author ken 2017
 *
 */
class controller_notice extends pcontroller
{
    /**
     *  公告列表
     *
     * @author ken 2017
     */
    public function actionList()
    {
        //公告列表
        $oProxyNotice = new model_proxynotice();
        $tingri = date('Y-m-d',strtotime('+1 days'));
        if ($this->getIsAjax()) {
            // 01, 搜索条件整理
            $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "status" => parent::VAR_TYPE_INT, // 状态 1 启用 2停用 模型中已经写定初始创建格式为停用状态
                "subject" => parent::VAR_TYPE_STR, //标题内容
                "sendtime" => parent::VAR_TYPE_DATETIME, //发送时间
                "isdel" => parent::VAR_TYPE_INT, // 0:未删除;1:删除
                "adminname" => parent::VAR_TYPE_STR, // 管理员名称
                "sdate" => parent::VAR_TYPE_DATETIME, //公告开始时间
                "edate" => parent::VAR_TYPE_DATETIME, //公告结束时间
                "sidx" => parent::VAR_TYPE_STR,
                "sord" => parent::VAR_TYPE_STR,
            ));
            $sOrderBy = $aGetData['sidx'] == '' ? "a.`id` ${aGetData['sord']}" :"${aGetData['sidx']} ${aGetData['sord']}";
            $aResult = $oProxyNotice->getNoticeList('*', $this->_search($aGetData), $aGetData['rows'], $aGetData['page'], $aGetData,$sOrderBy); // 获取数据结果集
            $this->outPutJQgruidJson($aResult['results'], $aResult['affects'], $aGetData['page'], $aGetData['rows']);
        }
        $GLOBALS['oView']->assign("mingtian", $tingri);
        $GLOBALS['oView']->assign("today", $this->sToday);
        $GLOBALS['oView']->display("notice_list.html");
        EXIT;
    }

    /**
     * 发布新公告
     * 功能
     * 1 新建公告 文件内容为空
     * 2 有内容的公告 文件填充内容
     * 3 用于处理文字
     *
     * @author ken 2017
     */
    public function actionAdd()
    {
        $oProxyNo = new model_proxynotice();
        $aLayer = $oProxyNo->getLayerByadminId($this->lvtopid);
        $GLOBALS['oView']->assign("layer", $aLayer);
        $GLOBALS['oView']->assign("today", $this->sToday);
        $GLOBALS['oView']->display("notice_add.html"); // * 共用
    }

    /**
     * 发送公告 @TODO 只接受新增
     * 分别接受新发送的公告和修改的公告
     * 即插入数据库中一条新的公告
     * 类型1 文字
     * 类型2 图片
     *
     * @author ken 2017
     */
    public function actionSend()
    {
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $oMemCache->delete('proxynotice_' . $_SESSION['lvtopid']);

        $oProxyNotice = new model_proxynotice();
        $aGetData = $this->post(array(
            "subject"   => parent::VAR_TYPE_STR, //消息标题
            "all_level" =>  parent::VAR_TYPE_INT,
            "content"   => parent::VAR_TYPE_STR,// 正文
            "type"      =>  parent::VAR_TYPE_INT,//公告类型 1 文字 2 图片
            "ismartop"  => parent::VAR_TYPE_INT, // 首页置顶
            "sorts"     =>  parent::VAR_TYPE_INT,// 排序
//            "sdate"     => parent::VAR_TYPE_DATETIME,
//            "edate"     => parent::VAR_TYPE_DATETIME,
            "allUser" => parent::VAR_TYPE_INT,
            "iswindow" => parent::VAR_TYPE_INT,
        ));
        $iNtype = isset($aGetData['type']) ? $aGetData['type'] : '';
        if (is_numeric($iNtype) && $iNtype == 2 ) {
            if ($_FILES['image']['name'] == '') {
                $this->ajaxMsg(9,'请上传图片');
            }
        }
        $aNoticeInfo = $oProxyNotice->getNoticeInfo($aGetData['sorts'],$this->lvtopid);
        if (!empty($aNoticeInfo)) {
            $this->ajaxMsg(3,'该排序已存在');
        }
        $iData = $this->_checkData($aGetData,$_FILES);
        if ($iData > 0) {
            $aData = $iData;
        }
        switch ($iData) {
            case -100:
                $this->ajaxMsg(3,'排序不能为空');
                 break;
            case -102:
                $this->ajaxMsg(5,'公告类型不能为空');
                break;
            case -103:
                $this->ajaxMsg(16,'文字公告不能为空');
                break;
            case -104:
                $this->ajaxMsg(7,'时间格式不正确');
                break;
            case -105:
                $this->ajaxMsg(8,'公告标题不能为空且不能为数字');
                break;
            case -106:
            $this->ajaxMsg(8,'发送版本不能为空');
                break;
            case -107:
                $this->ajaxMsg(10,'图片上传失败');
                break;
        }
        $aData['sorts'] = $aGetData['sorts'];
        $iFlag = $oProxyNotice->NoticeInsert($aData);
        if ($iFlag > 0) {
            $this->ajaxMsg(1,"公告发布成功");
        } else if (!empty($sFailedFile)) {
            $this->ajaxMsg(12,'图片上传失败');
        } else if ($iFlag == -1) {
            $this->ajaxMsg(7,'时间格式不正确');
        } else {
            $this->ajaxMsg(14,'未知错误，请联系管理员');
        }
        EXIT;
    }

    /**
     * 修改公告(前台)
     *
     * @author ken 2017
     */
    function actionEdit() {
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $oMemCache->delete('proxynotice_' . $_SESSION['lvtopid']);

        $aGetData = $this->get(array(
            "id"    => parent::VAR_TYPE_INT,
        ));
        $oProxyNotice = new model_proxynotice();
        if (isset($_POST['change_sticky']) && $this->getIsAjax()) {
            // 修改置顶
            if (false !== $oProxyNotice->changeStatus($this->lvtopid, intval($_POST['id']))) {
                die(json_encode(['result' => 1, 'msg' => '修改成功！']));
            } else {
                die(json_encode(['result' => 0, 'msg' => '修改失败，请联系管理员']));
            }
        }
        $aProxynotice = $oProxyNotice->notice($aGetData['id']);

        if(!empty($aProxynotice['version'])) {
            $version = [];
            $versionName = ['全部'=>0,'WEB'=>1,'APP'=>2];
            foreach ($versionName as $key => $value) {
                if(in_array($value, $aProxynotice['version'])) {
                    $version[$key]=$value;
                }
            }
            $GLOBALS['oView']->assign("version", $version);
        }
        if ($aProxynotice == -1) {
            $GLOBALS['oView']->assign("tclose", 1);
        }
        $aLayer = $oProxyNotice->getLayerByadminId($this->lvtopid);
        $noticeLayer = isset($aProxynotice['layerids']) ? $aProxynotice['layerids'] : null;
        // 根据重叠部分 选出已有部分
        if ($noticeLayer !== null && is_array($noticeLayer)) {
            foreach ($aLayer as $key => &$value) {
                if (in_array($value['layerid'], $noticeLayer)) {
                    $value['checked'] = true;
                }
            }
        }
        $GLOBALS['oView']->assign("layer", $aLayer);// 用户层级
        /**
         * 如果有图片上传 则走这里
         * 1 处理图片
         */
        if ($aProxynotice['type'] == 2) {
            $this->_uploadImage($_FILES);
        }
        $imaPath = getImageLoadUrl().$aProxynotice['image'];
        $GLOBALS['oView']->assign("imgpath", $imaPath);
        $GLOBALS['oView']->assign("nvalue", $aProxynotice);
        $GLOBALS['oView']->assign("ur_here", "修改公告");
        $GLOBALS['oView']->assign('actionlink', array('href' => url("notice", "edit"), 'text' => '编辑公告'));
        $GLOBALS['oView']->display("notice_edit.html");
        EXIT;//@TODO KEN 2017
    }

    /**
     * 公告查看
     *
     * @author Tom 100916 17:00 (最后效验)
     */
    function actionView() {
        /* @var $oNotice model_notices */
        $oProxyNotice = new model_proxynotice();
        $aGetData = $this->get(array(
            'id'    => parent::VAR_TYPE_INT,
            'type'  => parent::VAR_TYPE_INT,
        ));
        $iNoticeId = isset($aGetData['id']) ? $aGetData['id'] : '';//ken
        $iType = isset($aGetData['type']) ? $aGetData['type'] : '';
        if (empty($iNoticeId) || empty($iType)) {
            $GLOBALS['oView']->assign("close", 1);
        }
        $aNotice = $oProxyNotice->notice($aGetData['id'],$this->lvtopid,1);
        if ($aNotice == -1) {
            $GLOBALS['oView']->assign("close", 1);
        }
        $GLOBALS['oView']->assign('notice', $aNotice);
        if (intval($aNotice['type']) === 2) {//图片公告
           $imaPath = getImageLoadUrl().$aNotice['image'];
            $GLOBALS['oView']->assign("imgpath", $imaPath);
        }
        $sRecUser = isset($aNotice['userid']) ? $aNotice['userid'] : 0;
        $GLOBALS['oView']->assign("recuser", $sRecUser);
        $GLOBALS['oView']->assign("ur_here", "查看公告");
        $GLOBALS['oView']->display("notice_view.html");
        EXIT;
    }

    /**
     * 更新公告
     *
     * @author ken 2017
     */
    public function actionUpdate()
    {
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $oMemCache->delete('proxynotice_' . $_SESSION['lvtopid']);

        $oProxyNotice = new model_proxynotice();
        $aGetData = $this->post(array(
            "id" => parent::VAR_TYPE_INT,
            "subject"   => parent::VAR_TYPE_STR, //消息标题
            "all_level" =>  parent::VAR_TYPE_INT,
            "content"   => parent::VAR_TYPE_STR,// 正文
            "type"      =>  parent::VAR_TYPE_INT,//公告类型 1 文字 2 图片
            "ismartop"  => parent::VAR_TYPE_INT, // 首页置顶
//            "sorts"     =>  parent::VAR_TYPE_INT,// 排序
            "sdate"     => parent::VAR_TYPE_DATETIME,
            "edate"     => parent::VAR_TYPE_DATETIME,
            "allUser"   => parent::VAR_TYPE_INT,
            "iswindow"   => parent::VAR_TYPE_INT,
        ));
        $sOldImg = isset($_POST['oldImg']) ? $_POST['oldImg'] : '';
        $iNtype = isset($aGetData['type']) ? $aGetData['type'] : '';
        if (is_numeric($iNtype) && $iNtype == 2 ) {
            if ($_FILES['image']['name'] == '' && empty($sOldImg)) {
                $this->ajaxMsg(9,'请上传图片');
            }
        }
        if (empty($aGetData['id'])) {
            $this->ajaxMsg(15,'未知错误，请联系管理员');
        }
        $iData = $this->_checkData($aGetData, $_FILES);
        if ($iData > 0) {
            $aData = $iData;
        }
        switch ($iData) {
//            case -100:
//                $this->ajaxMsg(3,'排序不能为空');
//                break;
            case -101:
                $this->ajaxMsg(4,'发送的用户层级不能为空');
                break;
            case -102:
                $this->ajaxMsg(5,'公告类型不能为空');
                break;
            case -103:
                $this->ajaxMsg(6,'公告类型不能为空');
                break;
//            case -104:
//                $this->ajaxMsg(7,'时间格式不正确');
//                break;
            case -105:
                $this->ajaxMsg(8,'公告标题不能为空且不能为数字');
                break;
            case -106:
                $this->ajaxMsg(8,'发送版本不能为空');
                break;
            case -107:
                $this->ajaxMsg(10,'图片上传失败');
                break;
        }
        if (empty($sOldImg)) {
                $iResult = $this->_uploadImage($_FILES);//处理图片
                if ($iResult < 0) {
                    $this->ajaxMsg(11,'图片不能为空');
                }
                $aData['image'] = $iResult;
            }
        $aData['id'] = isset($aGetData['id']) ? $aGetData['id'] : '';
        $iFlag = $oProxyNotice->NoticeUpdate($aData);
        if ($iFlag > 0) {
            $this->ajaxMsg(1,'公告修改成功');
        } else if (!empty($sFailedFile)) {
            $this->ajaxMsg(12,'图片上传失败');
        } else if ($iFlag == -1) {
            $this->ajaxMsg(13,'未知用户名错误');
        } else {
            $this->ajaxMsg(14,'未知错误，请联系管理员');
        }
        EXIT;
    }

    /**
     * 修改公告排序
     */
    public function actionEditSort() {
        /* @var $oNotice model_notices */
        $oProxyNotice = new model_proxynotice();
        $aGetData = $this->post(array(
            'id'    => parent::VAR_TYPE_INT,
            'sorts' => parent::VAR_TYPE_INT,
        ));
        $aNoticeInfo = $oProxyNotice->getNoticeInfo($aGetData['sorts'],$this->lvtopid);
        if (!empty($aNoticeInfo)) {
            exit("该排序已存在");
        }
        $iFlag = $oProxyNotice->editSorts($aGetData['id'],$aGetData['sorts'],$this->lvtopid);
        if ($iFlag > 0) {
            exit("修改排序成功");
        }else{
            exit("修改排序失败");
        }
    }
    /**
     * @desc 启用或者停用公告
     *
     * @author ken
     * @date 2017
     */
    function actionIsUsed()
    {
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $oMemCache->delete('proxynotice_' . $_SESSION['lvtopid']);

        /* @var $oNotice model_notices */
        $oProxyNotice = new model_proxynotice();
        $aGetData = $this->post(array(
            'id'    => parent::VAR_TYPE_INT,
            'status' => parent::VAR_TYPE_INT,
        ));
        $iFlag = $oProxyNotice->upNoticeStatus($aGetData['id'],$aGetData['status']);
        if ($iFlag > 0) {
            $this->ajaxMsg(1,'公告状态修改成功');
        }
    }

    /**
     * @desc 删除公告
     *
     * @author ken
     * @date 2017
     */
    function actionDel() {
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $oMemCache->delete('proxynotice_' . $_SESSION['lvtopid']);

        /* @var $oNotice model_notices */
        $oProxyNotice = new model_proxynotice();
        $aGetData = $this->post(array(
            'id'    => parent::VAR_TYPE_INT,
            'delimg' => parent::VAR_TYPE_INT,
            'redo'  => parent::VAR_TYPE_INT,
        ));
        $iDelImg = isset($aGetData['delimg']) ? $aGetData['delimg'] : 0;
        if ($iDelImg !== '' && $iDelImg == 1) {//删除图片
            $iFFlag = $oProxyNotice->delImg($aGetData);
            if ($iFFlag > 0) {
                $this->ajaxMsg(2,'图片删除成功');
            }else{
                $this->ajaxMsg(4,'图片删除失败');
            }
        }
        $iRedo = isset($aGetData['redo']) ? $aGetData['redo'] : '';
        if (!empty($iRedo) && $iRedo == 1) {
            $iFFFlag = $oProxyNotice->redoDel($aGetData);
            if ($iFFFlag > 0) {
                $this->ajaxMsg(3,'公告恢复成功');
            }else{
                $this->ajaxMsg(5,'公告恢复失败');
            }
        }

        $iFlag = $oProxyNotice->delNoitce($aGetData['id'],$this->lvtopid);
        if ($iFlag > 0) {
            $this->ajaxMsg(1,'删除公告成功！');
        }else{
            $this->ajaxMsg(6,'删除公告失败');
        }
    }

    /**
     * 搜索条件
     *
     * @param $aGetData
     * @return string
     */
    private function _search($aGetData)
    {
        $sWhere = ' 1 '; // WHERE 条件变量声明
        $iLvtopid = $this->lvtopid;
        $aGetData['isdel'] = isset($aGetData['isdel']) ? $aGetData['isdel'] : '';

        if ($aGetData['isdel'] != '') {
            if ($aGetData['isdel'] == 1) { // 已删标记
                $sWhere .= " AND `isdel` = '" . intval($aGetData['isdel']) . "' ";
            }
            if ($aGetData['isdel'] == 2) {
//                $sWhere .= " AND `isdel` = '" . intval($aGetData['isdel']) . "' ";
                $sWhere .= " AND `isdel` = '0 ' ";
            }
        }

        if ($aGetData['status'] != '') {
        if ($aGetData['status'] == 1) { //启用
            $sWhere .= " AND `status` = '" . intval($aGetData['status']) . "' ";
        }
        if ($aGetData['status'] == 2) { //2停用
            $sWhere .= " AND `status` = '" . intval($aGetData['status']) . "' ";
        }
        }
        if (!empty($aGetData['subject'])) {

            if (strstr($aGetData['subject'], '*')) {
                $sWhere .= " AND `subject` LIKE '" . str_replace('*', '%', $aGetData['subject']) . "' ";
            } else {
                $sWhere .= " AND `subject` = '" . $aGetData['subject'] . "' ";
            }
        }
        if ($aGetData['sdate'] !== '') {
            $sWhere .= " AND ( `sendtime` >= '" . daddslashes($aGetData['sdate']) . "' ) ";
            $aGetData['sdate'] = stripslashes_deep($aGetData['sdate']);
        }
        if ($aGetData['edate'] !== '') {
            $sWhere .= " AND ( `sendtime` <= '" . daddslashes($aGetData['edate']) . "' ) ";
            $aGetData['edate'] = stripslashes_deep($aGetData['edate']);
        }
        $sWhere .= " AND a.lvtopid = '{$iLvtopid}' ";
        return $sWhere;
    }

    /**
     * 检查条件
     *
     * @param $aGetData
     * @param $fFile
     * @return mixed
     */
    private function _checkData($aGetData,$fFile = '')
    {
        $aData = [];//init
//        $aStime = isset($aGetData['sdate']) ? $aGetData['sdate'] : '';
//        $aEtime = isset($aGetData['edate']) ? $aGetData['edate'] : '';
        $iswindow = isset($aGetData['iswindow']) ? $aGetData['iswindow'] : '';
        $aLayerid = isset($_POST['layerid'])?$_POST['layerid']: null;
        $version= isset($_POST['version'])?$_POST['version']: null;
        $sContent = isset($aGetData['content']) ? $aGetData['content'] : '';
//        $iSorts = isset($aGetData['sorts']) ? $aGetData['sorts'] : '';
        $sSubject = isset($aGetData['subject']) ? $aGetData['subject'] : '';
        $iAllLevel = isset($aGetData['all_level']) ? $aGetData['all_level'] : '';
        $iIsmartop = isset($aGetData['ismartop']) ? $aGetData['ismartop'] : '';
        $sType = isset($aGetData['type']) ? $aGetData['type'] : '';
        $iId = isset($aGetData['id']) ? $aGetData['id'] : '';
//        if ($iSorts === '') {
//            return -100;
//        }
        if (empty($sType)) {
            return -102;
        }
        if (empty($sContent) && $sType == 1) {
            return -103;
        }
//        if ($aStime ==='' || $aEtime === '' || ($aStime == $aEtime) || ($aStime > $aEtime))
//        {
//            return -104;
//        }
        if ($sSubject == '' || empty($sSubject) || is_numeric($sSubject) ) {
            return -105;
        }
        if (empty($version) ) {
            return -106;
        }
//        if (strlen($sSubject) < 8 || strlen($sSubject) > 40) {
//            return -108;
//        }
        if ($sType == 1) { // 文字公告

        } else { // 图片公告
            if ($iId !== '') {

            }else {
                $iResult = $this->_uploadImage($fFile);//处理图片
                if ($iResult < 0) {
                    return -107;
                }
                $aData['image'] = $iResult;
            }
        }
        // 合并数据
        $aData['allUser'] = isset($aGetData['allUser']) ? $aGetData['allUser'] : '';
        $aData['type'] = $sType;
//        $aData['sdate'] = $aStime;
//        $aData['edate'] = $aEtime;
        $aData['layerids'] = $aLayerid;
        $aData['versions'] = $version;
        $aData['iswindow'] = $iswindow;
        $aData['content'] = $sContent;
//        $aData['sorts'] = $iSorts;
        $aData['lvtopid'] = $this->lvtopid;
        $aData['sendid'] = $_SESSION['proxyadminid'];
        $aData['sendtime'] = date('Y-m-d H:i:s');
        $aData['subject'] = $sSubject;
        $aData['all_level'] = $iAllLevel;
        $aData['subject'] = $sSubject;
        $aData['ismartop'] = $iIsmartop;
        return $aData;
    }

    /**
     * 处理上传文件
     *
     * @param $fFile
     * @return  mixed
     */
    private function _uploadImage($fFile)
    {
        if (!empty($fFile)) {
            a::loadFile('filefunc.php', A_DIR . DS . 'includes' . DS . 'plugin');
            $filePath = DS . 'upload' . DS . date('Ymd');
            $aResult = saveUploadFile(
                $fFile['image'],
                $this->getPassportPath() . $filePath,
                'image',
                'gif|jpeg|jpg|png',
                0,
                2097152 // 1024*1024*2
            );
            if (is_array($aResult)) {
                if (!empty($aResult['err_msg']) && 0 > $aResult['code']) {
//                    $this->error($aResult['err_msg']);
                    return -1;
                } else {
                    // 图片上传成功，记录图片路径
                    $aData['image'] = daddslashes($filePath . DS . basename($aResult['name']));//避免数据库吃掉路径
                    return $aData['image'];
                }
            } else {
                return -2;
            }
        }
    }

}