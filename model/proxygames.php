<?php

/**
 * 商户游戏模型
 * Class model_proxygames
 */
class model_proxygames extends basemodel
{
    private $sTableName = "proxy_games";
    private $aType = array(
        "1" => "彩票",
        "2" => "真人娱乐",
        "3" => "电子游戏",
        "4" => "体育",
        "5" => "棋牌");

    /**
     * 获取商户游戏基本信息
     * @param $filed
     * @param $data
     * @return array
     */
    function getProxyGames($filed, $data)
    {
        $sSql = "SELECT * FROM " . $this->sTableName . "  WHERE {$filed}='" . $data . "'";
        $result = $this->oDB->getOne($sSql);
        return $result;
    }

    /**
     * 通过id获取商户游戏名字
     * @param $id
     * @return array
     */
    function getProxyGamesNameById($id)
    {
        $sSql = "SELECT name FROM " . $this->sTableName . "  WHERE id = '" . $id . "'";
        $result = $this->oDB->getOne($sSql);
        return $result;
    }

    /**
     * 新建商户游戏信息
     * @param $data
     * @return bool
     */
    function insertProxyGames($data)
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
     * 修改商户游戏基本信息
     * @param $aData
     * @param $sCondition
     * @return bool
     */
    function changeProxyGames($aData, $sCondition)
    {
        return $this->update($aData, $sCondition);
    }


    /**
     * 获取全部的商户游戏
     * @param $filed
     * @return array
     */
    function getAllVendor($sWhere = 1)
    {
        $result = $this->oDB->getAll("SELECT * FROM " . $this->sTableName . "  WHERE {$sWhere}");
        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $result[$k]["create_time"] = date("Y-m-d", strtotime($v['create_time']));
                if ($v['type'] == 0) {
                    $result[$k]['type'] = "";
                } else {
                    $typeArray = explode(',', $v['type']);
                    if ($typeArray[0] != "") {
                        $sType = "";
                        foreach ($typeArray as $v) {
                            $sType = $sType . " " . $this->aType[$v];
                        }
                    }
                    $result[$k]['type'] = $sType;
                }
            }
        }
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