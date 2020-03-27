<?php
/**
 * 文件 : /_app/controller/default.php
 * 功能 : 默认的控制器处理(无需权限访问)
 * @edit by Ben 2017-06-29 1、修改父类为 pcontroller 2、删除结尾 ?> 标签
 * @author    --
 * @version   2.0.0
 * @package   proxyweb
 */
class controller_default extends pcontroller {

    /**
     * @desc 登录页面及登录请求处理
     * @author rhovin
     * @date 2017-05-26
     */
    function actionIndex() {
        if (empty($_SESSION['proxyadminid'])) { // 如果不存在 sessionid，则执行登录
            if(!isset($_POST['flag']) || ($_POST['flag'] != 'login' && $_POST['flag'] != 'login2')) { // 显示登录界面
                $GLOBALS['oView']->display("default_index.html");
                exit;
            }
            //  执行登录过程
            if(isset($_POST['flag']) && $_POST['flag'] == 'login') { // 当有 Post 数据时, 检测用户合法性
                $aLocation = array(0 => array("text" => "登录页面", "href" => url("default", "index")));
                $sValidateCode = isset($_SESSION["validateCode"]) ? md5(strtoupper($_SESSION["validateCode"])) : '';
                if ($sValidateCode == '' || $sValidateCode != strtolower(trim($_POST["captcha"]))) {
                    sysMessage('验证码错误', 1, $aLocation);
                }
                /* @var $oProxyAdmin model_proxyuser */
                $oProxyAdmin = A::singleton('model_proxyuser');
                $iAdminLogin = $oProxyAdmin->adminlogin($_POST["username"], $_POST["loginpass"], $_SESSION['lvtopid']);
                unset($oProxyAdmin);
                /* @var $oProxyAdminLog model_proxylog */
                $oProxyAdminLog = A::singleton('model_proxylog');
                if ($iAdminLogin == -1) {
                    sysMessage('缺少参数', 1, $aLocation);
                } elseif ($iAdminLogin == -2) {
                    $oProxyAdminLog->insert('用户登录失败', '用户登录失败, 用户不存在', 'default', 'index');
                    sysMessage('用户不存在或密码错误'.' | '.$_SESSION['lvtopid'], 1, $aLocation);
                } elseif ($iAdminLogin == -3) {
                    $oProxyAdminLog->insert('用户登录失败', '用户登录失败，用户被锁定', 'default', 'index');
                    sysMessage('用户已被锁定', 1, $aLocation);
                } elseif ($iAdminLogin == -4) {
                    $oProxyAdminLog->insert('用户登录失败', '用户登录失败，用户组不存在', 'default', 'index');
                    sysMessage('用户组不存在', 1, $aLocation);
                } elseif ($iAdminLogin == -5) {
                    $oProxyAdminLog->insert('用户登录失败', '用户登录失败，用户组被锁定', 'default', 'index');
                    sysMessage('用户组被锁定', 1, $aLocation);
                } elseif ($iAdminLogin == -6) {
                    $oProxyAdminLog->insert('用户登录失败', '用户登录失败，更新session key 失败', 'default', 'index');
                    sysMessage('更新资料失败', 1, $aLocation);
                } else {
                    unset($_SESSION["validateCode"]);
                    $_SESSION["proxyadminid"] = $iAdminLogin["proxyadminid"];
                    $_SESSION["adminname"] = $iAdminLogin["adminname"];
                    $_SESSION["lvtopid"] = $iAdminLogin["lvtopid"];
                    $oProxyAdminLog->insert('登录成功', '成功登录平台', 'default', 'index', 0, $iAdminLogin["proxyadminid"], $iAdminLogin["lvtopid"]);
                    redirect(url("pindex", "index"), 0, TRUE);
                }
            }
        }
        // 否则转向至已登录的首页
        redirect(url("pindex", "index"), 0, TRUE);
    }

    /**
     * 管理员退出
     * @author Tom 100726 17:19 (最后效验)
     */
    function actionExit() {
        unset($_SESSION);
        session_destroy();
        redirect(url("default", "index"));
    }

    /**
     * 编辑器图片空间(返回全空记录，不做相应功能)
     * @author Ben
     * @date 2017-06-28
     */
    public function actionKindEditorImageManager() {
        $aReturn = [
            'moveup_dir_path' => '',
            "current_dir_path" => '',
            "current_url" => '',
            "total_count" => 0,
            "file_list" => []
        ];
        die(json_encode($aReturn));
   }

    /**
     * 编辑器图片上传
     * @author Ben
     * @date 2017-06-28
     */
    public function actionKindEditorUploadImage() {
        // 上传文件
        if (!empty($_FILES) && !empty($_FILES['imgFile']) && empty($_FILES['imgFile']['error'])) {
            a::loadFile('filefunc.php', A_DIR . DS . 'includes' . DS . 'plugin');
            $sFilePath = DS . 'upload' . DS . getUploadPath();
            $sType = $_FILES['imgFile']['type'];
            $sfType = $sType === 'application/octet-stream' ? 'application/octet-stream' : 'image';
            $aResult = saveUploadFile(
                $_FILES['imgFile'],
                $this->getPassportPath() . $sFilePath,
                $sfType,
                [
                    'gif',
                    'jpeg',
                    'jpg',
                    'png'
                ],
                0,
                2097152 // 1024*1024*2
            );
            if (is_array($aResult)) {
                if (!empty($aResult['err_msg']) && 0 > $aResult['code']) {
                    die(json_encode(['error' => 1,'message' => $aResult['err_msg']]));
                } else {
                    die(json_encode([
                        'error' => 0,
                        'url' => 'default_loadimage.shtml?path=' . $sFilePath . DS . basename($aResult['name'])
                    ]));
                }
            } else {
                die(json_encode(['error' => 1,'message' => 'Sorry, 上传错误！']));
            }
        }

        die(json_encode(['error' => 1, 'message' => '没有上传文件！']));
   }

    /**
     * 展示图片
     * @author Ben
     * @date 2017-06-29
     */
    public function actionLoadImage() {
        if (empty($_GET['path'])) {
            die(json_encode(['error_msg' => '参数错误！']));
        }

        $sPath = trim(daddslashes(ltrim($_GET['path'], '/\\')));
        $sFilePath = $this->getPassportPath() . $sPath;

        if (!file_exists($sFilePath)) {
            die(json_encode(['error_msg' => '参数错误！']));
        }
        @$aFile = getimagesize($sFilePath);

        $aMime = ['image/gif', 'image/png', 'image/jpeg', 'image/bmp', 'image/webp', 'image/vnd.microsoft.icon'];

        if (!$aFile || !is_array($aFile) || !in_array($aFile['mime'], $aMime)) {
//            echo $aFile['mime'];
            die(json_encode(['error_msg' => '参数错误！']));
        }

        @header("Content-Type:{$aFile['mime']}");
        echo file_get_contents($sFilePath);
    }
}
