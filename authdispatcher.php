<?php

/**
 * 路径: /_app/authdispatcher.php
 * 功能: MVC调度器 + 身份验证 
 * 
 *     1, 根据当前 URL $_REQUEST 来初始化控制器名,与动作方法名.
 *        例:  http://www.xxx.com/?controller=default&action=list
 *             控制器名为: default   ( $_controller_name )
 *             动作方法为: list      ( $_action_name )
 *     
 *     2, 进行 控制器/动作方法 名称检查
 *     3, 进行 控制器/动作方法 的使用权限判断
 *     4, 程序交付给指定控制器的制定方法 
 * @author     Tom  090523
 * @version    1.2.0
 * @package    proxyweb
 */
if (!defined('IN_APPLE') || IN_APPLE !== TRUE)
    die('Error code: 0x1000');

class authDispatcher extends dispatcher {

    /**
     * 通过多态性, 重写调度器验证方法
     * @author Tom 090511
     * @return bool
     */
    protected function authCheck() {
        $sCurrentController = self::getControllerName();
        $sCurrentAction = self::getActionName();
        /* @var $oProxyAdmin model_proxyuser */
        $oProxyAdmin = A::singleton('model_proxyuser');
        $iProxyAdminId = isset($_SESSION["proxyadminid"]) ? intval($_SESSION["proxyadminid"]) : 0;
        $iLvtopId = isset($_SESSION["lvtopid"]) ? intval($_SESSION["lvtopid"]) : 0;
        $iFlag = $oProxyAdmin->adminAccess($iProxyAdminId, $iLvtopId, $sCurrentController, $sCurrentAction);
        if ($iFlag == -100) {
            $this->halt(-100, '访问的管理员菜单不存在, 或未启用');
        } elseif ($iFlag == -101) {
            //redirect(url('default', 'index'));
            if (isAjax()) {
                echo "<-101>";
            } else {
                echo "<script>alert('您的账号长时间没有进行操作已被登出！');top.location='./';</script>";
            }
            exit;
        } elseif ($iFlag == -102) {
            session_destroy();
            if (isAjax()) {
                echo "<-102>";
            } else {
                echo "<script>alert('您的账户已从另外一个地方登录了！');top.location='./';</script>";
            }
            exit;
        } elseif ($iFlag == -103) {
            session_destroy();
            $this->halt(-101, '管理员不存在, 或管理员被锁定, 或所属组不存在, 或所属组被禁用');
        } elseif ($iFlag == -104) {
            $this->halt(-102, '权限不足');
        } else {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 多态的 halt 方法
     * @author Tom 090511
     */
    protected static function halt($iCode, $sMessage = '') {
        switch ($iCode) {
            case -4 :
                sysMessage("控制器&动作方法名无效", 1);
            case -1 :
                sysMessage("控制器文件名不符合规则", 1);
            case -2 :
                sysMessage("控制器类文件中, 不包含制定的类定义", 1);
            case -3 :
                sysMessage('控制器中指定的方法不存在 : ( ' . self::$_actionName . ' ) ', 1);
            case -100 :
            case -101 :
            case -102 :
                sysMessage($sMessage, 1);
                break;
            default :
                break;
        }
        exit;
    }

}

?>