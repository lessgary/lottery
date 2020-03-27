<?php
/**
 * User: Howie.
 * Date: 2018/5/24/024
 * Time: 15:05
 */

/**
 * 供应商游戏模型
 * Class model_vendor_vendor
 */
class model_vendorgames extends basemodel
{

    private $sTableName = "vendor_games";

    /**
     * 获取供应商游戏基本信息
     * @param $filed
     * @param $data
     * @return array
     */
    function getVendorGames($filed, $data)
    {
        $sSql = "SELECT * FROM " . $this->sTableName . "  WHERE {$filed}='" . $data . "'";
        $result = $this->oDB->getOne($sSql);
        return $result;
    }

    /**
     * 获取供应商游戏基本信息
     * @param $filed
     * @return array
     */
    function getVendorGamesOne($where)
    {
        $sSql = "SELECT * FROM " . $this->sTableName . "  WHERE " . $where ;
        $result = $this->oDB->getOne($sSql);
        return $result;
    }


    /**
     * 新建供应商游戏信息
     * @param $data
     * @return bool
     */
    function insertVendorGames($data)
    {
        $data['update_time'] = date("Y-m-d H:i:s");
        $data['create_time'] = date("Y-m-d H:i:s");
        $this->oDB->insert($this->sTableName, $data);
        if ($this->oDB->errno > 0 || $this->oDB->ar() != 1) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 修改供应商游戏基本信息
     * @param $aData
     * @param $sCondition
     * @return bool
     */
    function changeVendorGames($aData, $sCondition)
    {
        return $this->update($aData, $sCondition);
    }

    /**
     * 修改供应商游戏的状态
     * @param $id
     * @param $status 状态(0:未选择 1:启用 2:关闭)
     * @return bool
     */
    function changeVendorGamesStatus($id, $status)
    {
        $aData = array();
        $aData['status'] = $status;
        return $this->update($aData, " id=" . $id);
    }

    /**
     * 查询供应商游戏列表
     * @param string $sWhere
     * @return array
     */
    function getChartsResult($sWhere = 1)
    {
        $sql=" SELECT * FROM " . $this->sTableName . " WHERE " . $sWhere;
        $result = $this->oDB->getAll($sql);
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

}