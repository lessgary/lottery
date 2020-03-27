<?php
/**
 * Date: 2018/5/28/028
 * Time: 14:17
 */

class controller_proxygames extends pcontroller
{

    /**
     * 获取商户游戏列表
     */
    public function actionList()
    {
        $oProxyGames = new model_vendor_proxygames();
        $GLOBALS['oView']->assign("aData", $oProxyGames->getAllVendorNameAndType());
        $GLOBALS['oView']->display('proxygames_tasklist.html');
    }


    /**
     * 查找游戏厂家游戏
     */
    public function actionFind()
    {
        $iType = isset($_POST["type"]) && is_numeric($_POST["type"]) ? intval($_POST["type"]) : 0;
        $iGameId = isset($_POST["game_id"]) && is_numeric($_POST["game_id"]) ? intval($_POST["game_id"]) : 0;
        $iVendorId = isset($_POST["vendor_id"]) && is_numeric($_POST["vendor_id"]) ? intval($_POST["vendor_id"]) : 0;
        $iStatus = isset($_POST["status"]) && is_numeric($_POST["status"]) ? intval($_POST["status"]) : 0;
        $sName = isset($_POST["game_name"]) ? daddslashes($_POST["game_name"]) : "";
        $sWhere = "lvtopid = '" . $this->lvtopid . "'";
        if ($iStatus > 0) {
            $sWhere .= " AND status ='" . $iStatus . "'";
        }
        if ($iGameId > 0) {
            $sWhere .= " AND vendor_game_id ='" . $iGameId . "'";
        }
        $oProxyGames = new model_vendor_proxygames();
        $aData = $oProxyGames->getAllProxyGame($sWhere);
        foreach ($aData as $k => $v) {
            $aGame = $oProxyGames->getVendorGame($v['vendor_game_id']);
            if (!empty($iType)) {
                if ($aGame[0]['type'] != $iType) {
                    unset($aData[$k]);
                    continue;
                }
            }
            if (!empty($iVendorId)) {
                if ($aGame[0]['vendor_id'] != $iVendorId) {
                    unset($aData[$k]);
                    continue;
                }
            }
            $sVendorName = $oProxyGames->getVendorNameById($aGame[0]['vendor_id'])['name'];
            if ($sName != "") {
                if ($sName != $sVendorName) {
                    unset($aData[$k]);
                    continue;
                }
            }
            $aData[$k]["type"] = $this->aType[$aGame[0]['type']];
            $aData[$k]["game_name"] = $aGame[0]['name'];
            $aData[$k]["vendor_name"] = $sVendorName;
            $aData[$k]["create_time"] = date("Y-m-d", strtotime($v['create_time']));
        }
        $this->outPutJQgruidJson($aData, count($aData));
    }


    /**
     * 修改厂家游戏状态
     */
    public function actionChange()
    {
        $aPostArr = array();
        $iId = isset($_GET["id"]) && is_numeric($_GET["id"]) ? intval($_GET["id"]) : 0;
        $sAttributes = isset($_GET["attributes"]) ? daddslashes($_GET["attributes"]) : "";
        $iStatus = isset($_GET["status"]) && is_numeric($_GET["status"]) ? intval($_GET["status"]) : 0;
        $oProxyGames = new model_vendor_proxygames();

        if ($iStatus > 0) {
            $aPostArr['status'] = $iStatus;
            $aGame = $oProxyGames->getProxyGameById($iId);
            if($aGame['control_status']==2){
                $this->ajaxMsg(0, "后台关闭了该游戏,不能修改状态");
            }

        }
        if ($sAttributes != "") {
            if ($sAttributes == "empty") {
                $aPostArr['attributes'] = "";
            } else {
                $aPostArr['attributes'] = $sAttributes;
            }
        }
        if ($oProxyGames->changeProxyGame($aPostArr, "id = '" . $iId . "'")) {
            $this->ajaxMsg(1, "修改成功");
        } else {
            $this->ajaxMsg(0, "修改失败");
        }

    }

    private $aType = array(
        "1" => "彩票",
        "2" => "真人娱乐",
        "3" => "电子游戏",
        "4" => "体育",
        "5" => "棋牌");

    private $aAttributes = array(
        "1" => "热门",
        "2" => "推荐",
        "3" => "精品",
        "4" => "新上",
    );
}