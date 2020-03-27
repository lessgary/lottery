<?php

/**
 * 商户管理平台(仅总代用户使用) 单入口文件
 *
 * SESSION 数组使用情况:
 * ~~~~~~~~~~~~~~~~~~~~~
 * 系统相关:
 * $_SESSION['validateCode']    验证码
 * $_SESSION['issuper']         {'yes',''} 是否为公司内部测试账号,也查看页脚程序执行效率
 *
 * 用户相关:
 * $_SESSION['proxyadminid']          用户ID
 * $_SESSION['adminname']        用户名(登录账号)
 * $_SESSION['lvtopid']         ?? 当前账户的,总代的ID编号
 *
 * TODO:
 *    将更多模型,控制器, 注册至 $GLOBALS['G_CLASS_FILES'], 实现快速载入.
 *
 * 最后效验于 2010-09-17 18:09
 */
@header("content-type:text/html; charset=UTF-8");
@header("Cache-Control: no-cache, must-revalidate");
define('IN_APPLE', TRUE);
define('PAPPNAME', 'proxyweb'); // 定义应用程序名称(不可重复). 核心类 filecaches.php 的基础目录名
define('PUSERNAME', 'passport'); // 定义前台用户应用程序名
define('PADMINNAME', 'passportadmin'); // 定义管理后台应用程序名
define('PDIR', dirname(__FILE__)); // 项目入口路径: aframe/_example/proxyweb/wwwroot
require realpath(PDIR . "/../../../library/a.php");
define('PAPPDIR', PDIR . DS . '..' . DS . '_app' . DS); // 当前项目应用目录
// add by shawn 2011-04-28, anti-ddos only for web service not for CLI.
if (defined('OPEN_COOKIE_FIREWALL') && OPEN_COOKIE_FIREWALL === TRUE
        && !defined('DONT_USE_APPLE_FRAME_MVC')) {
    if (checkCookieVerify('check') == TRUE) {
        // do nothing ... Just lets this pass ^_^
    } else {
        checkCookieVerify('set');
    }
}
define('PDIR_USER',  realpath(PDIR . DS . '..' . DS . '..' . DS . PUSERNAME)); // PASSPORT 应用目录
define('PDIR_ADMIN', realpath(PDIR . DS . '..' . DS . '..' . DS . PADMINNAME)); // PASSPORTADMIN 应用目录
define('PDIR_USER_DATA', PDIR_USER . DS . '_data'); // 前台数据目录
define('PDIR_USER_MODEL', PDIR_USER . DS . '_app' . DS . 'model' . DS); // PASSPORT 模型目录
define('PDIR_ADMIN_MODEL', PDIR_ADMIN . DS . '_app' . DS . 'model' . DS); // PASSPORTADMIN 模型目录

define('PRJ_CONTROLLER_PATH', PAPPDIR . 'controller' . DS); // 控制器默认路径
define('PRJ_MODEL_PATH', PAPPDIR . 'model' . DS);           // 模型层默认路径
// 定义版本号
// 格式:               发行版本号,   SVN 版本号,  发布日期
define('PRJ_VERSION', '2.0.0,         12,         2010-07-08 10:43');

// 多语言配置文件 LANG 路径, PDIR . \_app\language\
define('PRJ_LANG_PATH', PAPPDIR . 'language' . DS);
require_once( realpath(PRJ_LANG_PATH . 'utf8_zhcn' . DS . 'common.php') );


$GLOBALS['G_CLASS_FILES']['pcontroller'] = PRJ_CONTROLLER_PATH . DS . 'pcontroller.php';
// proxyweb模型
$GLOBALS['G_CLASS_FILES']['model_proxymenu'] = PAPPDIR . 'model' . DS . 'proxymenu.php';
$GLOBALS['G_CLASS_FILES']['model_proxyuser'] = PAPPDIR . 'model' . DS . 'proxyuser.php';
$GLOBALS['G_CLASS_FILES']['model_proxygroup'] = PAPPDIR . 'model' . DS . 'proxygroup.php';
$GLOBALS['G_CLASS_FILES']['model_puser'] = PAPPDIR . 'model' . DS . 'puser.php';
$GLOBALS['G_CLASS_FILES']['model_preport'] = PAPPDIR . 'model' . DS . 'preport.php';
$GLOBALS['G_CLASS_FILES']['model_openlottery'] = PAPPDIR . 'model' . DS . 'popenlottery.php';
$GLOBALS['G_CLASS_FILES']['model_userlayer'] = PAPPDIR . 'model' . DS . 'userlayer.php';
$GLOBALS['G_CLASS_FILES']['model_proxylevel'] = PAPPDIR . 'model' . DS . 'proxylevel.php';
$GLOBALS['G_CLASS_FILES']['model_porders'] = PAPPDIR . 'model' . DS . 'porders.php';
$GLOBALS['G_CLASS_FILES']['model_proxynotice'] = PAPPDIR . 'model' . DS . 'proxynotice.php';
$GLOBALS['G_CLASS_FILES']['model_userbetscheck'] = PAPPDIR . 'model' . DS . 'userbetscheck.php';

// PDIR_USER_MODEL 用户模型
$GLOBALS['G_CLASS_FILES']['model_notices'] = PDIR_USER_MODEL . 'notices.php';
$GLOBALS['G_CLASS_FILES']['model_user'] = PDIR_USER_MODEL . 'user.php';
$GLOBALS['G_CLASS_FILES']['model_userlog'] = PDIR_USER_MODEL . 'userlog.php';
$GLOBALS['G_CLASS_FILES']['model_config'] = PDIR_USER_MODEL . 'config.php';
$GLOBALS['G_CLASS_FILES']['model_payment_bank'] = PDIR_USER_MODEL . 'payment' .DS. 'bank.php';
$GLOBALS['G_CLASS_FILES']['model_usergroup'] = PDIR_USER_MODEL . 'usergroup.php';
$GLOBALS['G_CLASS_FILES']['model_accountorders'] = PDIR_USER_MODEL . 'accountorders.php';
$GLOBALS['G_CLASS_FILES']['model_userfund'] = PDIR_USER_MODEL . 'userfund.php';
$GLOBALS['G_CLASS_FILES']['model_withdrawel'] = PDIR_USER_MODEL . 'withdrawel.php';
$GLOBALS['G_CLASS_FILES']['model_rechargemoney'] = PDIR_USER_MODEL . 'rechargemoney.php';
$GLOBALS['G_CLASS_FILES']['model_gamebase'] = PDIR_USER_MODEL . 'gamebase.php';
$GLOBALS['G_CLASS_FILES']['model_gamemanage'] = PDIR_USER_MODEL . 'gamemanage.php';
$GLOBALS['G_CLASS_FILES']['model_orders'] = PDIR_USER_MODEL . 'orders.php';
$GLOBALS['G_CLASS_FILES']['model_userreport'] = PDIR_USER_MODEL . 'userreport.php';
$GLOBALS['G_CLASS_FILES']['model_projects'] = PDIR_USER_MODEL . 'projects.php';
$GLOBALS['G_CLASS_FILES']['model_usermethodset'] = PDIR_USER_MODEL . 'usermethodset.php';
$GLOBALS['G_CLASS_FILES']['model_task'] = PDIR_USER_MODEL . 'task.php';
$GLOBALS['G_CLASS_FILES']['model_getprize'] = PDIR_USER_MODEL . 'getprize.php';
$GLOBALS['G_CLASS_FILES']['model_usertree'] = PDIR_USER_MODEL . 'usertree.php';
$GLOBALS['G_CLASS_FILES']['model_domains'] = PDIR_USER_MODEL. 'domains.php';
$GLOBALS['G_CLASS_FILES']['model_venaccessconfig'] = PDIR_USER_MODEL."venaccessconfig.php";
$GLOBALS['G_CLASS_FILES']['model_vendor_base'] = PDIR_USER_MODEL. 'vendor'.DS."base.php";
$GLOBALS['G_CLASS_FILES']['model_vendor_ky'] = PDIR_USER_MODEL. 'vendor'.DS."ky.php";
$GLOBALS['G_CLASS_FILES']['vendorobject'] = PDIR_USER_MODEL.'vendor'.DS . 'vendorobject.php';
$GLOBALS['G_CLASS_FILES']['model_vendor_vendorpubfun'] = PDIR_USER_MODEL.'vendor'.DS . 'vendorpubfun.php';
$GLOBALS['G_CLASS_FILES']['vendorgamemain'] = PDIR_USER_MODEL.'vendor'.DS . 'vendorgamemain.php';
$GLOBALS['G_CLASS_FILES']['model_transferorder'] = PDIR_USER_MODEL . 'transferorder.php';
$GLOBALS['G_CLASS_FILES']['model_vendorgame'] = PDIR_USER_MODEL . 'vendorgame.php';
$GLOBALS['G_CLASS_FILES']['model_gamelisttransfer'] = PDIR_USER_MODEL . 'gamelisttransfer.php';
$GLOBALS['G_CLASS_FILES']['model_userbetreport'] = PDIR_USER_MODEL . 'userbetreport.php';
// 后台模型
$GLOBALS['G_CLASS_FILES']['model_prizegroup'] = PDIR_ADMIN_MODEL . 'prizegroup.php';
$GLOBALS['G_CLASS_FILES']['model_activity'] = PDIR_ADMIN_MODEL . 'activity.php';
$GLOBALS['G_CLASS_FILES']['model_lottery'] = PDIR_ADMIN_MODEL . 'lottery.php';
$GLOBALS['G_CLASS_FILES']['model_method'] = PDIR_ADMIN_MODEL . 'method.php';
$GLOBALS['G_CLASS_FILES']['model_issueinfo'] = PDIR_ADMIN_MODEL . 'issueinfo.php';
$GLOBALS['G_CLASS_FILES']['model_bankcard'] = PDIR_ADMIN_MODEL . 'bankcard.php';
$GLOBALS['G_CLASS_FILES']['model_adminmenu'] = PDIR_ADMIN_MODEL . 'adminmenu.php';
$GLOBALS['G_CLASS_FILES']['model_sale'] = PDIR_ADMIN_MODEL . 'sale.php';
$GLOBALS['G_CLASS_FILES']['model_lottery'] = PDIR_ADMIN_MODEL. 'lottery.php';
$GLOBALS['G_CLASS_FILES']['model_hklhc'] = PDIR_ADMIN_MODEL. 'hklhc.php';
// PASSPORTADMIN 模型共用
$GLOBALS['G_CLASS_FILES']['model_message'] = PDIR_ADMIN_MODEL . 'message.php';
// add ken 2017-06-15
$GLOBALS['G_CLASS_FILES']['model_adminuser'] = PDIR_ADMIN_MODEL . 'adminuser.php';

// ... more ...
// 类自动搜索路径
A::import(PAPPDIR);
A::setDispatcher('authdispatcher');


//载入网站配置文件 (位于passport项目下)
//if (!defined("DONT_TRY_LOAD_SYSCONFIG_FILE")) {
//    if (!file_exists(PRJ_DSN_PATH . 'global_config.php')) {
//        $oConfig = A::singleton("model_config");
//        if (TRUE !== $oConfig->getConfigFile(PRJ_DSN_PATH)) { // 重定向
//            @header("Location: /");
//            exit;
//        }
//    }
//    include_once( realpath(PRJ_DSN_PATH . 'global_config.php') );
//}


// 01, 设置属于项目的全局核心参数
A::replaceIni(
    array(
        /* 全局设置 */
        'class.bDevelopMode' => FALSE,
        'class.db.bRecordProcessTime' => TRUE, // 是否记录执行 SQL 的总计时间
        /* 调度器 & 控制器 */
        'apple.default.controller' => '_controllers',
        'apple.default.action' => '_actions',
        /* 日志类 */
        'class.logs.sBasePath' => PDIR . DS . '..' . DS . '_tmp' . DS . 'logs' . DS, // 默认日志路径 A_DIR.DS.'tmp'.DS.'logs'.DS,
        'class.logs.iMaxLogFileSize' => 5242880, // 日志最大尺寸. 1024*1024*5
        /* 错误处理 */
        'error' => array(
            'trigger_error' => 0 /* 日志全开 =117 */
//           APPLE_ON_ERROR_CONTINUE
//           | APPLE_ON_ERROR_REPORT
//           | APPLE_ON_ERROR_TRACE
//           | APPLE_ON_ERROR_LOG
//           | APPLE_LOGS_SQL_TO_FILE, 
        ),
    )
);

// 数据库配置文件 DSN 路径[和前台一个文件], PDIR_USER . \_app\config\
define('PRJ_DSN_PATH', PDIR_USER . DS . '_app' . DS . 'config' . DS);
require ( realpath(PRJ_DSN_PATH . 'dsn.php') );

//print_rr( A::getIni('/') );exit;
// 02, 根据需要初始化 LOGS 对象
if ((bool) (intval(A::getIni('error/trigger_error')) & APPLE_ON_ERROR_LOG)) {
    $GLOBALS['oLogs'] = A::singleton('logs'); //new logs( /*array('iLogType'=>0)*/ );
}

checkCache();//检查是否需要更新缓存
// 03, 初始化视图对象 $oView, 初始化SESSION, runMVC !
if (!defined('DONT_USE_APPLE_FRAME_MVC')) {
    
    //$oSession = new sessiondb(array('aDBO' => $GLOBALS['aSysDbServer']['master']));
    @session_start();
    // 首次打开,域名验证
    if(!isset($_SESSION['lvtopid'])) {
        /* @var $oDomains model_domains */
        $oDomains = A::singleton('model_domains');
        $sDomain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] ;
        $aResult = $oDomains->checkDomainChannel($sDomain, TRUE);
        $sDefaultRedirectSite = 'http://www.baidu.com/';
        if(empty($aResult)){ //不是平台域名
            @header("Location: {$sDefaultRedirectSite}");
            exit;
        }
        $_SESSION['lvtopid'] = $aResult['lvtopid']; // 识别商户
    }
    
    $oView = new view(
            array(
                'template_dir' => PAPPDIR . 'views' . DS . 'default' . DS, // 结尾不需斜线
                'compile_dir' => PDIR . DS . '..' . DS . '_tmp' . DS . 'views_compiled',
                'cache_dir' => PDIR . DS . '..' . DS . '_tmp' . DS . 'views_cached',
                //'caching' => TRUE,
                //'cache_lifetime' => 5,
                'direct_output' => FALSE, // 直接输出
                'force_compile' => FALSE, // 强制编译
            )
    );

    $oView->assign("webtitle", getConfigValue("webset_webtitle", "商户管理平台"));
    /*     * ********************************验证码*********************** */
    if (isset($_GET['useValid']) && (bool) $_GET['useValid'] == TRUE) {
        require PAPPDIR . 'validate.php';
        exit;
    };
    A::runMVC();
}

/**
 * 系统提示信息
 * @param  string  $sMsgDetail 消息内容
 * @param  int     $sMsgType   消息类型， 0消息，1错误，2询问
 * @param  array   $aLinks     可选的链接
 */
function sysMessage($sMsgDetail, $sMsgType = 0, $aLinks = array()) {
    $iSeconds = 8;
    if (count($aLinks) == 0) {
        $aLinks[0]['text'] = '返回上一页';
        $aLinks[0]['href'] = 'javascript:self.location=document.referrer;';
    }
    $GLOBALS['oView']->assign('ur_here', '系统信息');
    $GLOBALS['oView']->assign('auto_redirection', '如果您不做出选择，将在 <span id="spanSeconds">' . $iSeconds . '</span> 秒后跳转到第一个链接地址。');
    $GLOBALS['oView']->assign('msg_detail', $sMsgDetail);
    $GLOBALS['oView']->assign('msg_type', $sMsgType);
    $GLOBALS['oView']->assign('links', $aLinks);
    $GLOBALS['oView']->assign('default_url', $aLinks[0]['href']);
    $GLOBALS['oView']->display('message.html');
    exit;
}

// 用于 CLI 的消息显示函数, 用于替代 sysMessage, 见  admin/model/issueinfo.php 
// drawNumber( ... , $sErrorFunc='sysMessage')
function sysEcho($sMsgDetail, $sMsgType = 0, $aLinks = array()) {
    echo $sMsgDetail . "\n";
}
/**
 * 系统提示信息
 * @author         James        09/05/17
 * @param         string        $sMsg                //消息内容
 * @param         int                $iMsgType        //消息类型：0-弹出框，1-系统消息，2-错误消息，3-询问消息,
 * @param         string        $sTarget        //默认目标窗口        self:本窗口，parent:父窗口，top:顶窗口
 * @param         array        $aArray                //可选的连接地址，键/值：url=>连接地址，title=>连接标题,target:目标窗口(没有_)
 */
function sysMsg($sMsg, $iMsgType = 0, $aLinks = array(), $sTarget = 'self') {
    switch ($sTarget) {
        case 'top': $sTarget = 'top';
            break;
        case 'parent': $sTarget = 'parent';
            break;
        default: $sTarget = 'self';
            break;
    }
    if (empty($aLinks)) {
        $aLinks[0]['title'] = '返回上一页';
        $aLinks[0]['url'] = 'javascript:history.back()';
        $aLinks[0]['target'] = $sTarget;
    } else {
        foreach ($aLinks as &$v) {
            if (empty($v['url'])) {
                $v['url'] = "javascript:history.back()";
            }
            if (empty($v['title'])) {
                $v['title'] = "返回上一页";
            }
            if (empty($v['target'])) {
                $v['target'] = $sTarget;
            }
        }
    }
    if ($iMsgType == 0) { //JS弹出框
        if (empty($sMsg)) { //直接跳转，不弹出信息
            $sStr = "<script>" . $aLinks[0]['target'] . ".location='" . $aLinks[0]['url'] . "';</script>";
        } else {
            if ($aLinks[0]['url'] == 'close') {
                //<script language=JavaScript> self.opener.location.reload(); </script>
                $sStr = "<script>alert('" . $sMsg . "');</script><script>
if (self.opener.opener){self.opener.opener.location.href = '" . $aLinks[1]['url'] . "';}
if(self.opener){self.opener.location.href = '" . $aLinks[1]['url'] . "';}</script>
<p align=center><span onClick='window.opener=null;window.close();' style='cursor:pointer'>请关闭窗口</span></p>";
            } else {
                $sStr = "<script>alert('" . $sMsg . "');" . $aLinks[0]['target'] . ".location='" . $aLinks[0]['url'] . "';</script>";
            }
        }
        echo $sStr;
        exit;
    }

    $seconds = 9; //倒计时
    $sStr = '如果您不做出选择，将在 <span id="spanSeconds">' . $seconds . '</span> 秒后跳转到第一个链接地址';
    $GLOBALS['oView']->assign('auto_redirection', $sStr);
    $GLOBALS['oView']->assign('msg_detail', $sMsg);
    $GLOBALS['oView']->assign('msg_type', $iMsgType);
    $GLOBALS['oView']->assign('links', $aLinks);
    $GLOBALS['oView']->assign('target', $sTarget);
    $GLOBALS['oView']->assign('default_url', $aLinks[0]['url']);
    $GLOBALS['oView']->display('sys_message.html');
    exit;
}

/**
 * 返回图片加载路径
 * @author Ben
 * @date 2017-06-29
 * @return string
 */
function getImageLoadUrl() {
    return '/default_loadimage.shtml?path=';
}

/**
 * 文件上传二级文件夹名字
 * @author Ben
 * @date 2017-07-31
 * @return string
 */
function getUploadPath() {
    if (!empty($_SESSION['lvtopid'])) {
        return md5($_SESSION['lvtopid']);
    }
    return date('Ymd');
}

/**
 * 获取passport项目上传文件路径
 * @return string
 */
function getPassportPath() {
    $sSeparator = DS;
    return __DIR__ . "${sSeparator}..${sSeparator}..${sSeparator}passport${sSeparator}wwwroot${sSeparator}";
}
?>