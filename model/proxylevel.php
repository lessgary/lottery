<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/19
 * Time: 16:48
 */
class model_proxylevel extends basemodel{
    public function __construct(){

        parent::__construct();
    }

    /**
     * @desc 代理佣金列表
     * @author price
     * @date 2017-06-19
     */
    public function actionCommissionList($lvtopid,$iPageRecord = 0,$iCurrentPage = 0){
        $lvtopid = intval($lvtopid);
        if ($iPageRecord == 0) {//不分页显示
            $sSql = "SELECT * FROM `proxy_accquota_set` WHERE lvtopid='" . $lvtopid . "' GROUP BY id";
            return $this->oDB->getAll($sSql);
        } else {
            $sCondition = "lvtopid='" . $lvtopid . "'";
            return $this->oDB->getPageResult("proxy_accquota_set", "*", $sCondition, $iPageRecord, $iCurrentPage,'', '');
        }
    }

    /**
     * @desc 代理账号与绑定列表
     * @author price
     * @date 2017-06-20
     */
    public function actionProxyList($sCondition = '',$iPageRecord = 0,$iCurrentPage = 0){
        $sFields = "url.*,u.username AS username";
        $sTable = " user_register_links AS url LEFT JOIN users AS u ON url.userid = u.userid";
        if ($iPageRecord == 0) {//不分页显示
            $sCondition = empty($sCondition) ? "" : " WHERE " . $sCondition;
            $sSql = "SELECT $sFields FROM $sTable $sCondition ";
            return $this->oDB->getAll($sSql);
        } else {
            $sCondition = empty($sCondition) ? "1" : $sCondition;
            $sCountSql = "SELECT count(*) AS TOMCOUNT FROM " . $sTable . " WHERE " . $sCondition . " GROUP BY url.`id`";
            $iAllCount = $this->oDB->getAll($sCountSql);
            $sCountSql = "SELECT " . count($iAllCount) . " AS TOMCOUNT";
            return $this->oDB->getPageResult($sTable, $sFields, $sCondition, $iPageRecord, $iCurrentPage,'', '', $sCountSql);
        }
    }

    /**
     * @desc 添加代理佣金
     * @author pierce
     * @date 2017-06-19
     */
    public function addProxyCommission($aData){
        $a = $this->oDB->insert('proxy_accquota_set', $aData);
        if($this->oDB->ar() < 1) {
            return false;
        }
        return true;
    }
    /**
     * @desc 根据id查询当前佣金
     * @author pierce
     * @date 2017-06-28
     */
    public function getLayerById($lvtopid ,$id){
        $sql = "SELECT * FROM `proxy_accquota_set` 
				WHERE lvtopid='".$lvtopid."'  AND id='".$id."' ";
        $aArr = $this->oDB->getOne($sql);
        if (0 == $this->oDB->ar()) {
            return false;
        } else {
            return $aArr;
        }
    }

    /**
     * @desc 根据条件查询默认代理配额个数
     * @author pierce
     * @date 2017-07-17
     * @param $lvtopid
     * @param $point
     * @param $proxy_level
     * @return bool|mysqli_result|null
     */
    public function getAccquotaCount($lvtopid ,$proxy_level,$point){
        $iCount =$this->oDB->getOne("SELECT count(*) AS icount FROM `proxy_accquota_set` 
				WHERE lvtopid='".$lvtopid."'  AND point='".$point."' AND proxy_level = '".$proxy_level."'");
        return $iCount;
    }
    /**
     * @desc 更新代理佣金
     * @author pierce
     * @date 2017-06-28
     */
    public function editProxyCommission($aParams,$id){
        $sTempWhereSql = " `id` = '" . intval($id) . "'";
        return $this->oDB->update('proxy_accquota_set', $aParams, $sTempWhereSql);
    }
    /**
     * @desc 删除代理佣金
     * @author pierce
     * @date 2017-06-28
     */
    public function delLayer($id){
        return $this->oDB->query("DELETE FROM `proxy_accquota_set` WHERE `id`='" . $id . "'");
    }

    /**
     * @desc 删除代理佣金
     * @author pierce
     * @date 2017-07-30
     * @param $id
     * @return bool|mysqli_result|null
     */
    public function delProxy($id){
        return $this->oDB->query("DELETE FROM `user_register_links` WHERE `id`='" . $id . "'");
    }

    /**
     * 根据总代获取是否有代理加盟
     * @param $iLvtopid
     * @return array
     */
    public function getHomeLink($iLvtopid){
        return $this->oDB->getAll("SELECT * FROM user_register_links WHERE lvtopid ='" . $iLvtopid . "' AND ishomelink = 1 ");
    }

    /**
     * 设置代理加盟
     * @param $aParams
     * @param $sTempWhereSql
     * @return bool|int
     */
    public function setHomeLink($aParams,$sTempWhereSql){
        return $this->oDB->update('user_register_links', $aParams,$sTempWhereSql);
    }
}