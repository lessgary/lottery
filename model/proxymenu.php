<?php

/**
 * 文件 : /_app/model/proxymenu.php
 * 功能 : 模型 - 管理员菜单
 * 
 * @author     Tom
 * @version    2.0.0
 * @package    passportadmin
 * 
 * 已对每一行代码进行规范化处理. 最后效验于 2010-09-14 16:03
 */
class model_proxymenu extends basemodel {

    /**
     * 增加管理员菜单
     * @author Tom 100914 16:08 (最后效验)
     */
    public function proxyMenuAdd($aPostArr = array()) {
        if (empty($aPostArr) || empty($aPostArr['sMenuName']) || empty($aPostArr['sController'])) {
            return -1;
        }
        if (($aPostArr['iParentId'] > 0) && empty($aPostArr['sActioner'])) {
            return -1;
        }
        $sAdminParentStr = '';
        if ($aPostArr['iParentId'] > 0) {
            // 检测proxymenu 是否存在
            $aProxymenu = $this->proxymenu($aPostArr['iParentId']);
            if ($aProxymenu == -1) {
                return -2;
            }
            $sAdminParentStr = $aProxymenu["parentstr"] . (empty($aProxymenu["parentstr"]) ? "" : ",") . $aProxymenu["menuid"];
        } else {
            $aPostArr['iIsMenu'] = 1;
            $aPostArr['iIsLink'] = 0;
        }
        if (empty($aPostArr['sActioner']) && ( $aPostArr['iParentId'] > 0)) {
            return -3;
        }
        if (empty($aPostArr['sDescription'])) {
            $aPostArr['sDescription'] = "";
        }
        $this->oDB->query("INSERT INTO `proxymenu` (`parentid`, `parentstr`, `title`, `description`, " .
                " `controller`, `actioner`, `ismenu`, `islink`, `sort`, `isdisabled` ) VALUES " .
                " ('" . $aPostArr['iParentId'] . "','" . $sAdminParentStr . "','" . $aPostArr['sMenuName'] . "','" . $aPostArr['sDescription'] . "', " .
                " '" . $aPostArr['sController'] . "','" . $aPostArr['sActioner'] . "','" .
                $aPostArr['iIsMenu'] . "','" . $aPostArr['iIsLink'] . "','" . $aPostArr['iSort'] . "','0')");
        if ($this->oDB->errno() > 0) {
            return -4;
        } else {
            return $this->oDB->insertid();
        }
    }

    /**
     * 通过ID 实例化一个菜单
     * @author Tom 100914 16:08 (最后效验)
     */
    public function proxymenu($iMenuId = 0) {
        $iMenuId = intval($iMenuId);
        if ($iMenuId == 0) {
            return -1;
        }
        $aArr = $this->oDB->getOne("SELECT * FROM `proxymenu` WHERE `menuid`='" . $iMenuId . "'");
        if (0 == $this->oDB->ar()) {
            return -1;
        } else {
            return $aArr;
        }
    }

    /**
     * 更改菜单状态
     * @author Tom 100914 16:08 (最后效验)
     */
    public function proxymenuEnable($iMenuId, $iStatus) {
        $iMenuId = intval($iMenuId);
        $iStatus = intval($iStatus);
        $mproxymenu = $this->proxymenu($iMenuId);
        if ($mproxymenu == -1) {
            return FALSE;
        }
        if ($mproxymenu['isdisabled'] == $iStatus) {
            return TRUE;
        }
        if ($iStatus == 1) { //禁用
            $this->oDB->query("UPDATE `proxymenu` SET `isdisabled`='1' WHERE `menuid`='" . $iMenuId . "' " .
                    " OR FIND_IN_SET('" . $iMenuId . "',`parentstr`) "); //自身ID 以及所有下级
            if ($this->oDB->errno() > 0) {
                return FALSE;
            }
            return TRUE;
        } elseif ($iStatus == 0) {
            if ($mproxymenu['parentstr'] != '') {
                $this->oDB->query("SELECT * FROM `proxymenu` WHERE `menuid` " .
                        " IN (" . $mproxymenu["parentstr"] . ") AND `isdisabled`='1'"); //查询所有上级的状态
                if ($this->oDB->errno() > 0) {
                    return FALSE;
                }
                if ($this->oDB->numRows() > 0) {
                    return FALSE;
                }
            }
            $this->oDB->query("UPDATE `proxymenu` SET `isdisabled`='0' WHERE `menuid`='" . $iMenuId . "'");
            if ($this->oDB->errno() > 0) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * 删除某个菜单
     * @author Tom 100914 16:08 (最后效验)
     */
    public function proxymenuDel($iMenuId) {
        $iMenuId = intval($iMenuId);
        $aMenuChild = $this->proxymenuChild($iMenuId, true);
        $iCount = count($aMenuChild);
        unset($aMenuChild);
        if ($iCount > 0) {
            return FALSE;
        } else { //对管理员特殊权限 进行菜单剔除遍历
            $this->oDB->doTransaction();
            $aAdmin = $this->oDB->getAll("SELECT `adminid`,`menustrs` FROM `proxyuser` WHERE FIND_IN_SET(" . $iMenuId . ",`menustrs`)");
            foreach ($aAdmin as $admin) {
                $aProxymenus = explode(",", $admin["menustrs"]);
                foreach ($aProxymenus as $key => $value) {
                    if ($value == $iMenuId) {
                        unset($aProxymenus[$key]);
                    }
                }
                $sproxymenu = join(",", $aProxymenus);
                unset($aProxymenus);
                $this->oDB->query("UPDATE `proxyuser` SET `menustrs`='" . $sproxymenu . "' WHERE `adminid`='" . $admin["adminid"] . "'");
                if ($this->oDB->errno() > 0) {
                    $this->oDB->doRollback();
                    return false;
                }
            }
            //对管理员组进行菜单剔除遍历
            $aTeam = $this->oDB->getAll("SELECT `groupid`,`menustrs` FROM `proxygroup` WHERE FIND_IN_SET(" . $iMenuId . ",`menustrs`)");
            foreach ($aTeam as $team) {
                $aProxymenus = explode(",", $team["menustrs"]);
                foreach ($aProxymenus as $key => $value) {
                    if ($value == $iMenuId) {
                        unset($aProxymenus[$key]);
                    }
                }
                $sproxymenu = join(",", $aProxymenus);
                unset($aProxymenus);
                $this->oDB->query("UPDATE `proxygroup` SET `menustrs`='" . $sproxymenu . "' WHERE `groupid`='" . $team["groupid"] . "'");
                if ($this->oDB->errno() > 0) {
                    $this->oDB->doRollback();
                    return FALSE;
                }
            }
            $this->oDB->query("DELETE FROM `proxymenu` WHERE `menuid`='" . $iMenuId . "'");
            if ($this->oDB->errno() > 0) {
                $this->oDB->doRollback();
                return FALSE;
            }
            $this->oDB->doCommit();
            return TRUE;
        }
    }

    /**
     * 查询某个菜单的下级
     * @author Tom 100914 16:08 (最后效验)
     */
    public function proxyMenuChild($iMenuId, $bAll = TRUE) {
        $iMenuId = intval($iMenuId);
        if ($bAll) {
            if ($iMenuId == 0) {
                return $this->oDB->getAll("SELECT * FROM `proxymenu` ORDER BY `parentid`,`sort`");
            } else {
                return $this->oDB->getAll("SELECT * FROM `proxymenu` WHERE FIND_IN_SET('" . $iMenuId . "',`parentstr`) ORDER BY `parentid`,`sort`");
            }
        } else {
            return $this->oDB->getAll("SELECT * FROM `proxymenu` WHERE `parentid`='" . $iMenuId . "' ORDER BY `parentid`,`sort`");
        }
    }
    /**
     * 
     * @author Tom 100914 16:08 (最后效验)
     */
    public function getMenuListById($aMenuId) {
        if($aMenuId == "") return [] ; 
        $sSql = "SELECT * FROM `proxymenu` WHERE menuid in (${aMenuId}) AND isdisabled=0 ORDER BY `parentid`,`sort` ";
       return $this->oDB->getAll($sSql);
    }

    /**
     * 获取带层级关系的菜单数组
     * @author Tom 100726 18:16 (最后效验)
     */
    public function getproxymenuOptions($iSelectId = '') {
        $sSql = 'SELECT `menuid`,`parentstr`,`title` FROM `usermenu` ' .
                ' WHERE `isdisabled`=0 ORDER BY `parentid` ';
        $aTmpArray = $this->oDB->getAll($sSql);
        $aReturn = array();
        foreach ($aTmpArray AS $v) {
            $sTmpArrayKeyString = '';
            $aKeysArray = explode(',', $v['parentstr']);
            foreach ($aKeysArray as $pid) {
                if (is_numeric($pid)) {
                    $sTmpArrayKeyString .= "[$pid]";
                }
            }
            $sTmpArrayKeyString = '$aReturn' . $sTmpArrayKeyString . '[] = array( \'menuid\' => '
                    . $v['menuid'] . ', \'title\' => "' . $v['title'] . '");';
            eval($sTmpArrayKeyString);
        }
        $aResult = array();
        foreach ($aTmpArray AS $k => $v) {
            $aResult[$v['menuid']]['title'] = $v['title'];
        }
        unset($aTmpArray);
        $sRetrunOptions = '';
        foreach ($aReturn AS $k => $v) {
            if (!isset($v['menuid']) || !isset($v['title'])) { // 生成顶级
                $sRetrunOptions .= "<OPTION " . ($iSelectId == $k ? 'SELECTED' : '') . " VALUE='$k'>+---" . $aResult[$k]['title'] . "</option>";
                foreach ($v as $v1) {
                    if (!isset($v1['menuid']) || !isset($v1['title'])) {
                        foreach ($v1 as $v2) {
                            if (isset($v2['menuid']) && isset($v2['title'])) {
                                $sRetrunOptions .= "<OPTION " . ($iSelectId == $v2['menuid'] ? 'SELECTED' : '') . " VALUE='" . $v2['menuid'] .
                                        "'>| |---" . $v2['title'] . "</option>";
                            }
                        }
                    } else { // 生成2级
                        $sRetrunOptions .= "<OPTION " . ($iSelectId == $v1['menuid'] ? 'SELECTED' : '') . " VALUE='" . $v1['menuid'] .
                                "'>|----" . $v1['title'] . "</option>";
                    }
                }
            }
        }
        unset($aResult, $aReturn);
        return $sRetrunOptions;
    }

    /**
     * 更新某个菜单的名称以及描述
     * @author Tom 100914 15:44 (最后效验)
     */
    public function proxymenuUpdate($aPostArr = array()) {
        if (empty($aPostArr)) {
            return FALSE;
        }
        $iMenuId = intval($aPostArr['iMenuId']);
        $mMenu = $this->proxymenu($iMenuId);
        if ($mMenu == -1) {
            return FALSE;
        }
        //检测当前菜单是否修改组ID
        $iCurParentId = intval($mMenu['parentid']);
        $iParentId = intval($aPostArr['parentid']);
        $sCondition = "`menuid`='" . $iMenuId . "'";
        //说明：1、如果当前菜单没有下级，可以任意修改；2、如果有下级，只能从修改一级到新的一级，并同时修改下级；3、不可修改根目录
        if ($iCurParentId != $iParentId && $iCurParentId > 0) {//上级有所修改,所有下级跟着一起走
            //检测新的自身菜单是几级
            $iLevel = 2; //二级菜单
            if (intval($mMenu['parentid']) == intval($mMenu['parentstr'])) {
                $iLevel = 1; //一级菜单
            }
            $aNewParentMenu = $this->proxymenu($iParentId);
            if ($iLevel == 2) {//只修改自己
                if ($aNewParentMenu['parentstr'] != "") {
                    $aPostArr['parentstr'] = $aNewParentMenu['parentstr'] . "," . $iParentId;
                } else {
                    $aPostArr['parentstr'] = $iParentId;
                }
            } elseif ($iLevel == 1) {//看情况修改下级
                  //检测当前菜单是否有下级
                $ChildMenu = $this->proxymenuChild($iMenuId, FALSE);
                if (!empty($ChildMenu)) {//当前菜单有下级，只能转到新的一级菜单下面
                    //检测新的上级菜单是几级
                    $iNewLevel = 2; //二级菜单
                    if (intval($aNewParentMenu['parentid']) == intval($aNewParentMenu['parentstr'])) {
                        $iNewLevel = 1; //一级菜单
                    }
                    if ($iNewLevel == 1) {//整个下级及自身转移
                        $aPostArr['parentstr'] = $iParentId;
                        //更新所有下级
                        $aUpdataChild = array();
                        $aUpdataChild['parentstr'] = $iParentId . "," . $iMenuId;
                        $this->oDB->update("proxymenu", $aUpdataChild, "`parentid`='" . $iMenuId . "'");
                    } elseif ($iNewLevel == 2) {//不可转移
                        unset($aPostArr['parentid']);
                    }
                } else {//没有下级
                    if ($aNewParentMenu['parentstr'] != "") {
                        $aPostArr['parentstr'] = $aNewParentMenu['parentstr'] . "," . $iParentId;
                    } else {
                        $aPostArr['parentstr'] = $iParentId;
                    }
                }
            }
        } else {
            unset($aPostArr['parentid']);
        }
        unset($aPostArr['iMenuId']);
        $this->oDB->update("proxymenu", $aPostArr, $sCondition);
        return ( $this->oDB->errno() == 0 );
    }

    /**
     * 菜单排序
     * @author Tom 100914 15:54 (最后效验)
     */
    public function proxymenuSort($iMenuId, $aMenu) {
        $iMenuId = intval($iMenuId);
        $aMenuChild = $this->proxymenuChild($iMenuId, FALSE);
        $this->oDB->doTransaction();
        foreach ($aMenuChild as $menu) {
            if ($aMenu[$menu["menuid"]] != $menu["sort"]) {
                if (empty($aMenu[$menu["menuid"]]))
                    $aMenu[$menu["menuid"]] = 0;
                $this->oDB->query("UPDATE `proxymenu` SET `sort`='"
                        . $aMenu[$menu["menuid"]] . "' WHERE `menuid`='" . $menu["menuid"] . "'");
                if ($this->oDB->errno() > 0) {
                    $this->oDB->doRollback();
                    return FALSE;
                }
            }
        }
        $this->oDB->doCommit();
        return TRUE;
    }

    /**
     * 获取管理员用户菜单
     * @author Tom 100914 15:55 (最后效验)
     * @edited by Ben 添加参数$sAndWhere 添加其他过滤条件
     */
    public function getUserMenu($iUserId, $sAndWhere = null) {
        $iUserId = intval($iUserId);
        $aUser = $this->oDB->getOne("SELECT `menustrs`,`groupid`,`islocked` FROM `proxyuser` WHERE `proxyadminid`='" . $iUserId . "'");
        if (!$aUser) {
            return false;
        }
        $aGroup = $this->oDB->getOne("SELECT `menustrs`,`isdisabled` FROM `proxygroup` WHERE `groupid`='" . $aUser["groupid"] . "'");
        if (!$aGroup) {
            return false;
        }
        if ($aUser['islocked']) {
            return FALSE;
        }
        if (trim($aUser["menustrs"]) == "") {
            $aUserMenu = array();
        } else {
            $aUserMenu = array_filter(explode(",", $aUser["menustrs"]));
        }
        if ($aGroup['isdisabled']) {
            return FALSE;
        } else {
            $aGroupMenu = array_filter(explode(",", $aGroup["menustrs"]));
        }
        foreach ($aGroupMenu as $value) {
            $aUserMenu[] = $value;
        }
        $aMenu = array_unique($aUserMenu);
        $sMenu = implode(",", $aMenu);
        if ($sMenu == "") {
            return NULL;
        } else {
            //edit by Ben where添加其他过滤条件 2017-6-14
            if (!is_null($sAndWhere)) {
                $sSql = "SELECT * FROM `proxymenu` WHERE `isdisabled`='0' AND `ismenu`='1' AND  `menuid` IN(" . $sMenu . ") AND {$sAndWhere} ORDER BY `parentid` ASC,`sort` ASC";
            } else {
                $sSql = "SELECT * FROM `proxymenu` WHERE `isdisabled`='0' AND `ismenu`='1' AND  `menuid` IN(" . $sMenu . ") ORDER BY `parentid` ASC,`sort` ASC";
            }
            return $this->oDB->getAll($sSql);
        }
    }

    /**
     * 全部更改控制器行为器是否记录日志
     * @author Tom 100914 15:55 (最后效验)
     */
    function setLogStatus($iStatus, $iParentId = 0) {
        $iStatus = intval($iStatus);
        $iParentId = intval($iParentId);
        if ($iParentId > 0) {
            $this->oDB->query("UPDATE `proxymenu` SET `actionlog`='$iStatus' WHERE `menuid`='" . $iParentId . "' OR FIND_IN_SET('" . $iParentId . "',`parentstr`)");
        } else {
            $this->oDB->query("UPDATE `proxymenu` SET `actionlog`='$iStatus' ");
        }
        return ($this->oDB->errno() == 0);
    }

    /**
     * 全面启用菜单
     * @author Tom 100726 18:16 (最后效验)
     */
    function enableAll() {
        $this->oDB->query("UPDATE `proxymenu` SET `isdisabled`='0'");
        return ($this->oDB->errno() == 0);
    }
    /**
     * 根据控制器和方法获取菜单id
     * @author rhovin 2017-11-02
     */
    public function getMenuIdByConrotroller($controller,$action) {
        $sSql = "SELECT menuid FROM proxymenu WHERE controller='".$controller."' AND actioner='".$action."'";
        return $this->oDB->getOne($sSql);
    }

}

?>