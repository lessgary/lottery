<?php

/**
 * 供应商模型
 * Class model_vendor_vendor
 */
class model_vendor_pvendors extends basemodel
{
    private $sTableName = "vendor";
    private $aType = array(
        "1" => "彩票",
        "2" => "真人娱乐",
        "3" => "电子游戏",
        "4" => "体育",
        "5" => "棋牌");

    /**
     * 获取供应商基本信息
     * @param $filed
     * @param $data
     * @return array
     */
    function getVendor($filed, $data)
    {
        $sSql = "SELECT * FROM " . $this->sTableName . "  WHERE {$filed}='" . $data . "'";
        $result = $this->oDB->getOne($sSql);
        return $result;
    }

    /**
     * 获取全部的游戏厂商
     * @param $filed
     * @return array
     */
    function getAllVendor($sWhere = 1)
    {
        $result = $this->oDB->getAll("SELECT * FROM " . $this->sTableName . "  WHERE {$sWhere}");
        if (!empty($result)) {
            foreach ($result as $k => $v) {
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



}