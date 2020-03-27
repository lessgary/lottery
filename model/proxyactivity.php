<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/26
 * Time: 17:12
 */

class model_proxyactivity extends basemodel
{
    /**
     * 根据id获取活动表数据
     * 复写父类方法 1
     * @param int $iActivityId
     * @param bool 是否是前台
     * @return array
     * @author by ken 2017
     * @rewrite true
     */
    function getItem($iActivityId = 0,$lvtopid,$isPassport = false) {
        if($iActivityId == 0){
            return -1;
        }
        $sSql = "SELECT * FROM `proxy_activity` WHERE `id`='".$iActivityId."' AND lvtopid = '{$lvtopid}' LIMIT 1";
        $aResult = $this->oDB->getOne($sSql);
        if ($aResult && $aResult['detail']) {
            $aResult['detail'] = $isPassport ? $aResult['detail'] : imageSrcAdd($aResult['detail']);
        }
        if ($aResult && $aResult['app_detail']) {
            $aResult['app_detail'] = $isPassport ? $aResult['app_detail'] : imageSrcAdd($aResult['app_detail']);
        }
        if ($this->oDB->errno > 0) {
            return array();
        }
        return $aResult;
    }
    /**
     * desc 获取是否存在相同排序的记录
     * @author rhovin 2017-12-01
     */
    public function getSortByid($lvtopid , $sort) {
        $sSql = "SELECT * FROM `proxy_activity` WHERE  lvtopid = '{$lvtopid}' AND sort = '{$sort}' LIMIT 1";
        return $this->oDB->getOne($sSql);
    }
    
    /**
     * 根据id 更新 活动表数据
     * 复写父类方法 2
     * @param int $iActivityId
     * @param array $aUpdateData
     * @return bool
     * @author by ken 2017
     * @rewrite true
     */
    function edit($iActivityId = 0, $aUpdateData =  [],$lvtopid) {
        if($iActivityId == 0){
            return FALSE;
        }
        if(empty($aUpdateData)){
            return FALSE;
        }
        $aData = array();
        
        if (isset($aUpdateData['image'])) {
            $aData['image'] = $aUpdateData['image'];
        }
        if (isset($aUpdateData['app_type'])) {
            $aData['app_type'] = $aUpdateData['app_type'];
        }
        if (isset($aUpdateData['title']) && $aUpdateData['title'] != '') {
            $aData['title'] = $aUpdateData['title'];
        }
        if (isset($aUpdateData['desc']) && $aUpdateData['desc'] != '') {
            $aData['desc'] = $aUpdateData['desc'];
        }
        if (isset($aUpdateData['detail']) && $aUpdateData['detail'] != '') {
            $aData['detail'] = $aUpdateData['detail'];
            if (!empty($aData['detail'])) {
                $aData['detail'] = imageSrcRemove($aData['detail']);
//                $aData['detail'] = $this->oDB->real_escape_string($aData['detail']);
            }
        }
         if (isset($aUpdateData['app_detail']) && $aUpdateData['app_detail'] != '') {
            $aData['app_detail'] = $aUpdateData['app_detail'];
            if (!empty($aData['app_detail'])) {
                $aData['app_detail'] = imageSrcRemove($aData['app_detail']);
//                $aData['detail'] = $this->oDB->real_escape_string($aData['detail']);
            }
        }
        if (isset($aUpdateData['starttime']) && $aUpdateData['starttime'] != '') {
            $aData['starttime'] = $aUpdateData['starttime'];
        }
        if (isset($aUpdateData['endtime']) && $aUpdateData['endtime'] != '') {
            $aData['endtime'] = $aUpdateData['endtime'];
        }
        if (isset($aUpdateData['disable']) && in_array($aUpdateData['disable'],array(0,1))) {
            $aData['disable'] = $aUpdateData['disable'];
        }
        if (isset($aUpdateData['sort'])) {
            $aData['sort'] = $aUpdateData['sort'];
        }

        $aData['web_banner'] = $aUpdateData['web_banner'];
        if (!empty($aUpdateData['web_banner_new'])) {
            $aData['web_banner'] = $aUpdateData['web_banner_new'];
        }
        //$aData['web_content'] = $aUpdateData['web_content'];
        if (!empty($aUpdateData['web_content_new'])) {
            $aData['web_content'] = $aUpdateData['web_content_new'];
        }
        $aData['app_banner'] = $aUpdateData['app_banner'];
        if (!empty($aUpdateData['app_banner_new'])) {
            $aData['app_banner'] = $aUpdateData['app_banner_new'];
        }
       // $aData['app_content'] = $aUpdateData['app_content'];
        if (!empty($aUpdateData['app_content_new'])) {
            $aData['app_content'] = $aUpdateData['app_content_new'];
        }

        $sCondition = " `id`='" . $iActivityId . "' AND lvtopid = '{$lvtopid}' ";
        $aOld =  $this->oDB->getOne("SELECT * FROM `proxy_activity` WHERE {$sCondition}");
        if ($aOld) {
            if ($aData['web_banner'] != daddslashes($aOld['web_banner'])) {
                $sWebBannerOld = $aOld['web_banner'];
            }
           /* if ($aData['web_content'] != daddslashes($aOld['web_content'])) {
                $sWebContentOld = $aOld['web_content'];
            }*/
            if ($aData['app_banner'] != daddslashes($aOld['app_banner'])) {
                $sAppBanner = $aOld['app_banner'];
            }
            /*if ($aData['app_content'] != daddslashes($aOld['app_content'])) {
                $sAppContent = $aOld['app_content'];
            }*/
        }

        $mResult = $this->oDB->update("proxy_activity", $aData, $sCondition);
        if ($mResult === FALSE) {
            return FALSE;
        }
        if (!empty($sWebBannerOld)) {
            @unlink(getPassportPath() . $sWebBannerOld);
        }
        if (!empty($sWebContentOld)) {
            @unlink(getPassportPath() . $sWebContentOld);
        }
        if (!empty($sAppBanner)) {
            @unlink(getPassportPath() . $sAppBanner);
        }
        if (!empty($sAppContent)) {
            @unlink(getPassportPath() . $sAppContent);
        }
        return TRUE;
    }

    /**
     * 选择性更新活动表信息
     * @date 2017-11-29
     * @author  james liang
     *
     * @param array   对应的字段和和值，在控制器做校验
     * @return boolean
     */
    public function partEdit($iActivityId, $lvtopid, $arr = [])
    {
        if (empty($iActivityId) || empty($lvtopid) || count($arr)<1){
            return false;
        }
        $sCondition = " `id`='" . $iActivityId . "' AND lvtopid = '{$lvtopid}' ";
        $mResult = $this->oDB->update("proxy_activity", $arr, $sCondition);
        return $mResult;
    }
    
    /**
     *  复写父类方法3
     * 插入一条新的活动
     * @param $aData
     * @return bool
     * @author ken 2017
     */
    function insert($aData) {
        if(empty($aData)){
            return FALSE;
        }
        $mResult = $this->oDB->insert("proxy_activity",$aData);
        if ($mResult === FALSE) {
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * 新方法 1
     * 获取活动列表
     * @rewrite false
     * @param $sFields
     * @param $sCondition
     * @param $iPageRecords
     * @param $iCurrPage
     * @param $sOrderBy
     * @return int
     * @author ken 2017
     */
    public function getActivityList($sFields = "*", $sCondition = "1", $iPageRecords = 25, $iCurrPage = 1,$iLvtopId = '',$sOrderBy )
    {
        $sTableName = ' `proxy_activity` ';
        $sCondition .= " AND `lvtopid` = '{$iLvtopId}' ";
        if (empty($sOrderBy)) {
        }else{
            $sOrderBy = " ORDER BY " . $sOrderBy;
        }
        return $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy);
    }
    
    /**
     * 根据管理员id获取管理员名
     * @param $sAdminId
     * @return array
     */
    public function getUsernameByadminId($sAdminId)
    {
        $sSql = "SELECT `adminname` FROM `proxyuser` WHERE proxyuser.proxyadminid = '{$sAdminId}'";
        $aResult = $this->oDB->getOne($sSql);
        return $aResult;
    }
    
    /**
     * 删除图片和活动
     * @param $aGetData
     * @param $lvtopid
     * @return int
     */
    public function deleteActivity($aGetData,$lvtopid)
    {
        $aData = [];
        $sTableName = "proxy_activity";
        $id = isset($aGetData['id']) ? $aGetData['id'] : '';
        if ($id == '') {
            return -1;
        }
        $sWhere = "  1  AND  id = '{$id}' AND lvtopid = '{$lvtopid}'  ";
        $delImg = isset($aGetData['delimg']) ? $aGetData['delimg'] : '';
        if ($delImg == 1) {
            $aData['image'] = isset($aGetData['image']) ? $aGetData['image'] : '' ;
            $this->oDB->update($sTableName, $aData, $sWhere);
            if ($this->oDB->ar() > 0 && ($this->oDB->errno() <= 0)) {
                return 1;
            }else{
                return -2;
            }
        }
        $mResult = $this->oDB->delete("proxy_activity"," `id`='".$id."' AND lvtopid='{$lvtopid}' ");
        if ($mResult === FALSE) {
            return -3;
        }else{
            return 2;
        }
    }
}