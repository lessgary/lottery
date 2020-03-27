<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/9/29
 * Time: 13:08
 */
class model_marquee extends basemodel{
    /**
     * 跑马灯列表
     * @param $lvtopid
     * @param $bAll 是否取出全部（默认是）
     * @param $version 0:全部（默认），1：移动端，2：pc端
     * @return array
     */
    public function getList($iLvtopid, $version = 0, $bAll=true) {
        $sSql = "SELECT * FROM marquee WHERE lvtopid = '{$iLvtopid}'";
        if ($version == 1){
            $sSql .= " AND `version`<>2 ";
        }
        if($version == 2){
            $sSql .= " AND `version`<>1";
        }
        if (!$bAll){
            $sSql .= " AND `isshow`='0' ";
        }

        $sSql .= " ORDER BY `sorts` ASC";
        $aArr = $this->oDB->getAll($sSql);
        if (0 == $this->oDB->ar()) {
            return [];
        } else {
            return $aArr;
        }
    }

    /**
     * 添加跑马灯
     * @param $aData
     * @return bool
     */
    public function add($aData){
        $a = $this->oDB->insert('marquee', $aData);
        if($this->oDB->ar() < 1) {
            return false;
        }
        return true;
    }

    /**
     * 根据id获取跑马灯信息
     * @param $iLvtopid
     * @param $iId
     * @return array
     */
    public function getMarqueeById($iLvtopid,$iId) {
        $sSql = "SELECT * FROM marquee WHERE lvtopid = '".$iLvtopid."' AND id = '".$iId."'";
        $aArr = $this->oDB->getOne($sSql);
        if (0 == $this->oDB->ar()) {
            return [];
        } else {
            return $aArr;
        }
    }

    /**
     * 根据id删除跑马灯
     * @param $iLvtopid
     * @param $iId
     * @return bool|mysqli_result|null
     */
    public function deleteById($iLvtopid,$iId) {
        return $this->oDB->query("DELETE FROM `marquee` WHERE `id`='" . $iId . "' AND lvtopid = '".$iLvtopid."'");
    }

    /**
     * 根据id修改跑马灯
     * @param $aParams
     * @param $iId
     * @return bool|int
     */
    public function editById($aParams,$iId) {
        $sTempWhereSql = " `id` = '" . intval($iId) . "'";
        $aArr = $this->oDB->update('marquee', $aParams, $sTempWhereSql);
        if (0 == $this->oDB->ar()) {
            return [];
        } else {
            return $aArr;
        }
    }

    /**
     * 设置是否展示跑马灯
     * @param $iLvtopid
     * @param $aParams
     * @return array|bool|int
     */
    public function setShow($iLvtopid,$aParams) {
        $sTempWhereSql = " `lvtopid` = '" . intval($iLvtopid) . "'";
        $aArr = $this->oDB->update('marquee', $aParams, $sTempWhereSql);
        if (0 == $this->oDB->ar()) {
            return [];
        } else {
            return $aArr;
        }
    }
}