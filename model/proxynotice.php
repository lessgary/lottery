<?php

/**
 * Class model_proxynotice
 * @func 排序 1 复写方法 rewrite true
 * 2 新方法 newfunc true
 * 3 私有方法  selffunc true
 */
class model_proxynotice extends basemodel {
    
    /**
     * @todo rewrite 1
     * @param string $sFields
     * @param string $sCondition
     * @param int $iPageRecords
     * @param int $iCurrPage
     * @param $sOrderBy
     * @return mixed
     * @author ken 2017
     * @rewrite true
     * @change notices -> proxynotice
     */
    public function & getNoticeList($sFields = "*", $sCondition = "1", $iPageRecords = 25, $iCurrPage = 1, $aData = [],$sOrderBy = '') {
        $sAdminName = isset($aData['adminname']) ? $aData['adminname'] : '';
        if ($sAdminName !== '') {
            $sCondition .= " AND  c.adminname = '{$sAdminName}' ";
        }
        $sTableName = ' `proxynotice` a LEFT JOIN `proxyuser` c ON (a.`sendid`=c.`proxyadminid`) ';
        $sFields = " a.*,c.adminname,c.proxyadminid ";
        if (empty($sOrderBy)) {
        
        }else {
            $sOrderBy = " ORDER BY " .$sOrderBy;
        }
        $aResult = $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy);
        $aTemp = $aResult['results'];
        foreach ($aTemp as $kk => $vv) {
            $sLayerid = $aTemp[$kk]['layerids'];
            $aTemp[$kk]['layerids'] = explode(',', $sLayerid);
        }
        $aResult['results'] = $aTemp;
        return $aResult;
    }
    
    /**
     * @todo rewrite 2
     * @param $aArr
     * @return bool|mixed
     * @rewrite true
     * @author ken 2017
     */
    public function NoticeInsert($aArr) {
        // 数据整理
        unset($aArr['all_level']);//不插入数据库
        $aArr['status'] = 1;//默认关闭
        $aLayerids = isset($aArr['layerids']) ? $aArr['layerids'] : null ;
        if (is_array($aLayerids) && $aLayerids !== '') {
            $aArr['layerids'] = implode(',',$aLayerids);
        }
        $versions = isset($aArr['versions']) ? $aArr['versions'] : null ;
         if (is_array($versions) && $versions !== '') {
                $aArr['version'] = implode(',',$versions);
            }
        if (1 == $aArr['allUser']) {
            $aArr['layerids'] = '';
        }
        unset($aArr['allUser']);
        unset($aArr['versions']);
            //去除图片头
        if (!empty($aArr['content'])) {
            $aArr['content'] = imageSrcRemove($aArr['content']);
        }
        $iRst = $this->oDB->insert('proxynotice', $aArr);
        if ($iRst > 0 && ($this->oDB->errno() <= 0)) {
            return 1;
        }else {
            return -1;
        }
    }
    
    /**
     * 更新公告
     * @param $aData
     * @return int
     */
    public function NoticeUpdate($aData)
    {
        unset($aData['all_level']);//不插入数据库
        $id = isset($aData['id'])?intval($aData['id']):0;
        if (empty($id)) {
            return -3;
        }
        unset($aData['id']);
        $aLayerids = isset($aData['layerids']) ? $aData['layerids'] : null ;
        if (is_array($aLayerids) && $aLayerids !== '') {
            $aData['layerids'] = implode(',',$aLayerids);
        }
        $versions = isset($aData['versions']) ? $aData['versions'] : null ;
        if (is_array($versions) && $versions !== '') {
                $aData['version'] = implode(',',$versions);
        }
        if (1 == $aData['allUser']) {
            $aData['layerids'] = '';
        }
        unset($aData['allUser']);
        unset($aData['versions']);
        $sWhere = " id = '{$id}' ";
        //去除图片头
        if (!empty($aData['content'])) {
            $aData['content'] = imageSrcRemove($aData['content']);
        }
        $iRst = $this->oDB->update('proxynotice', $aData, $sWhere);
        if ($iRst > 0 && ($this->oDB->errno() <= 0)) {
            return 1;
        }else {
            return -4;
        }
    }
    
    /**
     * @todo rewrite 3
     * 获取一个公告 更换表名
     * @rewrite true
     * @author ken 2017
     * @access public
     * @param  $iNoticeId
     * @param $iLvtopid
     * @param $iIsView
     * @return [mixd] -1:不存在的ID
     *              Array:公告
     */
    public function notice($iNoticeId,$iLvtopid = '',$iIsView = '') {
        $sSql = " SELECT a.*,b.adminname AS sendername FROM `proxynotice` AS a LEFT JOIN `proxyuser` AS b ON (a.sendid=b.proxyadminid)
        WHERE a.id = '{$iNoticeId}'  ";
        $aResult = $this->oDB->getOne($sSql);
        $aLayerid = isset($aResult['layerids']) ? $aResult['layerids'] :null;
        $sVersion = isset($aResult['version']) ? $aResult['version'] :null;
        $aResult['version'] = explode(',', $sVersion);
        if ($aLayerid != null || $aLayerid !='') {
            $aLayerid = explode(',',$aResult['layerids']);
        }
        if (is_array($aLayerid) && $iIsView== 1)
        {
            foreach ($aLayerid as $value){
                $aRst[] = $this->oDB->getOne(" SELECT user_layer.name FROM `user_layer` WHERE user_layer.layerid = '{$value}' AND user_layer.lvtopid = '{$iLvtopid}' ");
            }
            if (is_array($aRst)) {
                foreach ($aRst as $kk => $vv) {
                    $layerName[] = $aRst[$kk]['name'];
                }
                if (is_array($layerName)) {
                    $sLayerName = implode(',',$layerName);
                }
            }
        }
        if (is_array($aLayerid)) {
            $aResult['layerids'] = $aLayerid;
        }
        if (is_array($aLayerid) && $iIsView == 1) {
            $aResult['layerids'] = $aLayerid;
            $aResult['layernames'] = $sLayerName;
        }
        if (!empty($aResult['content'])) {// 有图片增加路径
            $aResult['content'] = imageSrcAdd($aResult['content']);
        }
        if ($this->oDB->ar() < 1) {
            return -1;
        } else {
            return $aResult;
        }
    }
    
    /**
     * @todo rewrite 4
     * @desc 停用或者启用公告
     * @author Rhovin
     * @date 2017-05-18
     * @param int $iNoticeId    公告编号
     * @param  $iStatus
     * @author ken
     */
    public function upNoticeStatus($iNoticeId,$iStatus) {
        $aNotice = $this->notice($iNoticeId);
        if ($aNotice == -1) {
            return -1;
        }
        $this->oDB->query("UPDATE `proxynotice` SET `status`='" . $iStatus. "' WHERE `id`='" . $iNoticeId . "'");
        return $this->oDB->ar();
    }
    
    /**
     * @return bool
     * @author ken 2017
     */
    public function delNoitce($iNoticeId,$iLvtopid) {
        $this->oDB->query("DELETE FROM `proxynotice` WHERE `id`='" . $iNoticeId . "' AND lvtopid = '".$iLvtopid."'");
        return $this->oDB->ar();
    }
    
    /**
     * 获取层级用于展示
     * @param $iAdminId
     * @return array
     */
    public function getLayerByadminId($iAdminId)
    {
        $sSql = " SELECT user_layer.name,user_layer.layerid FROM `user_layer` WHERE user_layer.lvtopid = '{$iAdminId}' ";
        return $this->oDB->getAll($sSql);
    }
    
    /**
     * 根据id获取层级
     * @param $aData
     * @return array
     */
    public function getUserIdByLayerId($aData)
    {
        $sLvtopid = $aData['lvtopid'];
        $aLayerid = $aData['layerid'];
        $sLayerid = implode(',',$aLayerid);
        $sSql = " SELECT users.userid FROM users WHERE  users.layerid IN  ('{$sLayerid}') AND users.lvtopid = '{$sLvtopid}' ";
        return $this->oDB->getAll($sSql);
    }
    
    /**
     * 删除图片
     *
     * @param $aData
     * @return int
     */
    public function delImg($aData)
    {
        $id = isset($aData['id']) ? $aData['id'] : 0;
        if (empty($id)) {
            return -1;
        }
        unset($aData['id']);
        $sSql = " UPDATE `proxynotice` SET image = ''  WHERE id = $id ";
        $rst = $this->oDB->query($sSql);
        if ($rst > 0 && ($this->oDB->errno() <= 0)) {
            return 1;
        }else {
            return -2;
        }
    }
    
    /**
     * 恢复已删除公告
     *
     * @param $aData
     * @return int
     */
    public function redoDel($aData)
    {
        $id = isset($aData['id']) ? $aData['id'] : 0;
        if (empty($id)) {
            return -1;
        }
        unset($aData['id']);
        $sSql = " UPDATE `proxynotice` SET isdel = '0'  WHERE id = $id ";
        $rst = $this->oDB->query($sSql);
        if ($rst > 0 && ($this->oDB->errno() <= 0)) {
            return 1;
        }else {
            return -2;
        }
    }

    /**
     * 修改公告排序
     * @param $iId
     * @param $iSorts
     * @param $iLvtopid
     * @return int
     */
    public function editSorts($iId,$iSorts,$iLvtopid) {
        $sSql = " UPDATE `proxynotice` SET sorts = '".$iSorts."'  WHERE id = '".$iId."' AND lvtopid = '".$iLvtopid."'";
        $rst = $this->oDB->query($sSql);
        if ($rst > 0 && ($this->oDB->errno() <= 0)) {
            return 1;
        }else {
            return -2;
        }
    }

    /**
     * 根据排序查询公告
     * @param $iSort
     * @param $iLvtopid
     * @return array
     */
    public function getNoticeInfo($iSort,$iLvtopid) {
        return $this->oDB->getOne("SELECT * FROM proxynotice WHERE lvtopid = '".$iLvtopid."' AND sorts = '".$iSort."'");
    }
    /**
     * 修改公告置顶状态
     *
     * @author ken
     * @date 2017-06-23
     * @param $iLvtopid
     * @param $iInformationid
     * @return bool
     */
    public function changeStatus($iLvtopid, $iInformationid){
        $sWhere = " id='${iInformationid}' AND lvtopid='${iLvtopid}'";
        $aInfo = $this->oDB->getOne("SELECT `istop` FROM `proxynotice` WHERE ${sWhere}");
        if (!$aInfo) {
            return false;
        }
        if (0 == $aInfo['istop']) {
            // 开启事务
            $this->oDB->doTransaction();
            try {
                $this->oDB->update('proxynotice',['istop' => 1], $sWhere);
                $this->oDB->update('proxynotice',['istop' => 0], "id<>'${iInformationid}' and lvtopid='${iLvtopid}'");
                $this->oDB->doCommit();
                return 1;
            } catch (Exception $oException) {
                // 回滚，返回失败
                $this->oDB->doRollback();
                return false;
            }
        } else {
            return $this->oDB->update('proxynotice',['istop' => 0], $sWhere);
        }
    }
}















