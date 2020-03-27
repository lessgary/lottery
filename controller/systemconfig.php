<?php

/**
 * 文件 : /_app/controller/systemconfig.php
 * 功能 : 控制器 - 系统参数设定
 *
 * @author    Ben
 * @version   1.0.0
 * @package   proxyweb
 *
 */
class controller_systemconfig extends pcontroller {
    /**
     * 视图对象
     * @var $oViewer view
     */
    private $oViewer;

    public function __construct() {
        parent::__construct();
        $this->oViewer = $GLOBALS['oView'];
    }

    /**
     * 网站logo，icon显示
     * @author Ben
     * @date 2017-08-12
     */
    public function actionImagelist() {
        /* @var $aInfo model_proxysmallimage */
        $oProxySmallImage = A::singleton('model_proxysmallimage');
        $aInfo = $oProxySmallImage->getImages($this->lvtopid);
        $this->oViewer->assign('info', empty($aInfo) ? [] : $aInfo);
        $this->oViewer->display('systemconfig_imagelist.html');
    }

    /**
     * 网站icon
     * @author Ben
     * @date 2017-08-12
     */
    public function actionUpload() {
        if ($this->getIsPost()) {
            if (empty($_POST['image_type']) || !in_array(trim($_POST['image_type']), ['logo', 'icon', 'slogo'])) {
                $this->error('参数错误！');
            }
            $sImageType = $_POST['image_type'];
            if (empty($_FILES) || empty($_FILES['croppedImage']) || empty($_FILES['croppedImage']['size'])) {
                $this->error('请选择图片');
            }

            if (!empty($_FILES['croppedImage']['error'])) {
                $this->error('图片上传错误');
            }

            if (204800 < $_FILES['croppedImage']['size']) {
                $this->error('图片必须小于 2 M');
            }
            if (!empty($_FILES['croppedImage2']) && 204800 < $_FILES['croppedImage2']['size']) {
                $this->error('图片必须小于 2 M');
            }

            if ('icon' === $sImageType) {
                A::loadFile('icon.php', A_DIR . DS . 'includes' . DS . 'plugin');
                if ($rIm = @imagecreatefrompng($_FILES['croppedImage']['tmp_name'])) {
                    $imginfo = @getimagesize($_FILES['croppedImage']['tmp_name']);
                    if (!is_array($imginfo)) {
                        $errors[] = "图像格式错误！";
                    }
                    imagesavealpha($rIm, true);
                    $rResizeIm = @imagecreatetruecolor(30, 30);
                    $iSize = 30;
                    imagealphablending($rResizeIm, false);//不合并颜色,直接用$im图像颜色替换,包括透明色
                    imagesavealpha($rResizeIm, true);//不要丢了$resize_im图像的透明色,解决生成黑色背景的问题
                    imagecopyresampled($rResizeIm, $rIm, 0, 0, 0, 0, $iSize, $iSize, $imginfo[0], $imginfo[1]);
                    $icon = new phpthumb_ico();
                    $aGdImageArray = array($rResizeIm);
                    $sIconData = $icon->GD2ICOstring($aGdImageArray);
                    $sBasNewPath = $this->getPassportPath() . 'upload' . DS . 'icon' . DS . getUploadPath() . DS;

                    if (!is_dir($sBasNewPath)) {
                        mkdir($sBasNewPath, 0777, true);
                    }
                    $filePath = $sBasNewPath . md5(mt_rand() . uniqid()) . ".ico";
                    if (file_put_contents($filePath, $sIconData)) {
                        $output = str_replace($this->getPassportPath(), '', $filePath);
                        $oProxySmallImage = new model_proxysmallimage();
                        if ($oProxySmallImage->setImages($this->lvtopid, ['lvtopid' => $this->lvtopid, $sImageType => DS . $output])) {
                            die(json_encode(['error' => 0, 'msg' => '添加成功']));
                        } else {
                            $this->error('添加失败，请重试！');
                        }
                    }
                } else {
                    $this->error('图片生成错误，请重试！');
                }
            }

            if ('logo' === $sImageType || 'slogo' === $sImageType) {
                A::loadFile('filefunc.php', A_DIR . DS . 'includes' . DS . 'plugin');
                $sFilePath = DS. 'upload' . DS . $sImageType . DS . getUploadPath();
                $_FILES['croppedImage']['name'] = $_FILES['croppedImage']['name'] . '.png';
                $aResult = saveUploadFile(
                    $_FILES['croppedImage'],
                    $this->getPassportPath() . $sFilePath,
                    'image',
                    [
                        'gif',
                        'jpeg',
                        'jpg',
                        'png'
                    ]
                );
                if (is_array($aResult)) {
                    if (!empty($aResult['err_msg']) && 0 > $aResult['code']) {
                        $this->error($aResult['err_msg']);
                    } else {
                        // 图片上传成功，记录图片路径
                        $output = $sFilePath . DS . basename($aResult['name']);
                        $oProxySmallImage = new model_proxysmallimage();
                        if ($oProxySmallImage->setImages($this->lvtopid, ['lvtopid' => $this->lvtopid, $sImageType => $output])) {
                            if ('logo' === $sImageType && !empty($_FILES['croppedImage2']) && 0 === $_FILES['croppedImage2']['error']) {
                                move_uploaded_file($_FILES['croppedImage2']['tmp_name'], $this->getPassportPath() . $sFilePath . DS . model_proxysmallimage::$SMLOGO . basename($aResult['name']));
                            }
                            die(json_encode(['error' => 0, 'msg' => '添加成功']));
                        } else {
                            $this->error('添加失败，请重试！');
                        }
                    }
                } else {
                    $this->error('Sorry, 图片上传错误');
                }
            }
            die;
        }
        $iType = isset($_GET['image_type']) ? trim($_GET['image_type']) : 'logo';
        $this->oViewer->assign('imageType', $iType);
        $this->oViewer->assign('icon', "{'width' : 30, 'height' : 30, aspectRatio : 1}");
        $this->oViewer->assign('logo', '{width :230, height : 100, aspectRatio : 23/10}');
        $this->oViewer->assign('mlogo', '{width : 460, height : 200, aspectRatio : 23/10}');
        $this->oViewer->assign('slogo', '{width : 300, height : 100, aspectRatio : 3}');
        $this->oViewer->display('systemconfig_upload.html');
    }
    /**
     * desc 清除icon/logo/slogan图片
     * @author rhovin 20170917
     */
    public function actionClearImage() {
        if($this->getIsPost()) {
            $postData = $this->post(array(
                "imageType"=>parent::VAR_TYPE_STR,
            ));
            $imageType = $postData['imageType'];
            $oProxySmallImage = new model_proxysmallimage();
            $bResult = $oProxySmallImage->clearImages($this->lvtopid,$imageType);
            if($bResult) {
                 $this->ajaxMsg(1,"删除成功");
            } else {
                 $this->ajaxMsg(0,"删除失败");
            }
        }
    }
    /**
     * 用户配置页
     * @author Ben
     * @date 2017-06-19
     */
    public function actionSet() {
        /* @var $oProxyConfig model_proxyconfig */
        $oProxyConfig = A::singleton('model_proxyconfig');
        if ($this->getIsAjax()) {
            // 检查是否一级代理
            $sUserName = $this->post([
                'name' => parent::VAR_TYPE_STR
            ]);
            $bResult = (new model_usertree())->isFirstPoxy($this->lvtopid, $sUserName['name']);
            if ($bResult && !empty($bResult['count'])) {
                die(json_encode(['error' => 0]));
            } else {
                die(json_encode(['error' => 1]));
            }
        } elseif ($this->getIsPost()) {
            // 表单提交
            $aList = $oProxyConfig->getSystemConfigChild($this->lvtopid);
            $oUser = new model_puser();
            $fMaxPoint = $oUser->getCQSSCQSpoint($this->lvtopid);
            $oUserTree = new model_usertree();
            foreach ($aList as $item) {
                $sKey = 'config_' . $item['configid'];
                // 检查是否一级代理
                if (preg_match('/_proxy$/', $item['configkey']) && 'input' == $item['forminputtype']) {
                    if (!$oUserTree->isFirstPoxy($this->lvtopid, $_POST[$sKey])) {
                        $this->error($item['title'] . '必须是有效的一级代理');
                    }
                }
                if (array_key_exists($sKey, $_POST) && 'input' == $item['forminputtype']) {
                    if ('string' == $item['configvaluetype'] && (!empty($_POST[$sKey]) && !is_string($_POST[$sKey]))) {
                        $this->error($item['title'] . '值必须是字符串');
                    } elseif ('num' == $item['configvaluetype'] && (!empty($_POST[$sKey]) && !is_numeric($_POST[$sKey]))) {
                        $this->error($item['title'] . '值必须是数字');
                    }
                }
                if ($item['configkey'] == 'registered_pc_proxy_point' && $_POST[$sKey] > $fMaxPoint) {
                    $this->error($item['title'] . '不能高于重庆时时彩前三直选最高返点');
                }
                if ($item['configkey'] == 'registered_app_proxy_point' && $_POST[$sKey] > $fMaxPoint) {
                    $this->error($item['title'] . '不能高于重庆时时彩前三直选最高返点');
                }

            }

            // 数据持久化
            if (!$oProxyConfig->setSystemConfig($this->lvtopid, $aList, $_POST)) {
                $this->error('保存失败！');
            }
            sysMessage('保存成功！');
            die;
        } else {
            // 显示页面
            $aOptions = $oProxyConfig->option();
            $aList = $oProxyConfig->getSystemConfig($this->lvtopid);
            $this->oViewer->assign('aConfig', $aList);
            $this->oViewer->assign('aOptions', $aOptions);
            $this->oViewer->display('systemconfig_set.html');
        }
    }
}