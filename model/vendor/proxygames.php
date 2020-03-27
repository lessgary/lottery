<?php

/**
 * 供应商模型
 * Class model_vendor_vendor
 */
class model_vendor_proxygames extends basemodel
{
    private $sTableName = "proxy_games";

    /**
     * 修改供应商基本信息
     * @param $aData
     * @param $sCondition
     * @return bool
     */
    function changeProxyGame($aData, $sCondition)
    {
        return $this->update($aData, $sCondition);
    }


    /**
     * 获取全部的游戏厂商
     * @param $filed
     * @return array
     */
    function getAllProxyGame($sWhere = 1)
    {
        $result = $this->oDB->getAll("SELECT * FROM " . $this->sTableName . "  WHERE {$sWhere}");
        return $result;
    }

    private function update($aData, $sCondition)
    {
        $aData['update_time'] = date("Y-m-d H:i:s");
        $this->oDB->update($this->sTableName, $aData, $sCondition);
        if ($this->oDB->errno > 0 || $this->oDB->ar() != 1) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 获取全部厂商名字和支持的游戏类型
     * @author Howie
     * @return array
     */
    function getProxyGameById($id)
    {
        $sSql = 'SELECT *  FROM  proxy_games  WHERE id = '.$id;
        $result = $this->oDB->getOne($sSql);
        return $result;
    }


    /**
     * 获取全部厂商名字和支持的游戏类型
     * @author Howie
     * @return array
     */
    function getAllVendorNameAndType()
    {
        $sSql = 'SELECT id,name FROM  vendor  GROUP BY name';
        $result = $this->oDB->getAll($sSql);
        return $result;
    }


    function getVendorGame($id)
    {
        $sSql = 'SELECT * FROM  vendor_games WHERE id =' . $id;
        $result = $this->oDB->getAll($sSql);
        return $result;
    }


    /**
     * 通过id获取供应商名字
     * @param $id
     * @return array
     */
    function getVendorNameById($id)
    {
        $sSql = "SELECT name FROM vendor  WHERE id = '" . $id . "'";
        $result = $this->oDB->getOne($sSql);
        return $result;
    }

}