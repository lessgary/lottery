<?php
if (!defined('IN_APPLE') || IN_APPLE!==TRUE) die( 'error.frame.noAccess' );

/**
 * PassPort 前台+后台 公用语言文件
*/



/* 用户登录语言项 */
$_LANG['tom_out_put01'] = '对不起，遇到错误中断';
$_LANG['tom_out_put02'] = "对不起，[%s] 遇到错误中断";


/* 管理员登录认证*/
$_LANG['admin_user'] = "管理员账号";
$_LANG['admin_no_empty'] = "管理员用户名不能为空!";
$_LANG["need_more_params"] = "缺少参数";
$_LANG['admin_pass'] = "管理员密码";
$_LANG['admin_vifity'] = "后台验证码";
$_LANG['user_no_exist'] = "用户不存在或者用户密码错误";
$_LANG['user_is_lock'] = "用户已被锁定";
$_LANG["team_no_exist"] = "用户组不存在";
$_LANG['vifity_no_right'] = "验证码不正确";
$_LANG['admin_login'] = "进入管理中心";


/* 管理员修改密码 */
$_LANG['old_pass'] = "原始密码";
$_LANG['new_pass'] = "新的密码";
$_LANG['newpass2'] = "确认密码";
$_LANG['change_succ'] = "修改密码成功";
$_LANG['change_fail'] = "修改密码失败";
$_LANG['change_pass'] = "修改密码";
$_LANG['reset_pass'] = "重新输入";

/* 表单语言部分 */



/* 左侧菜单部分 */
$_LANG['collapse_all'] = "折叠全部";
$_LANG['expand_all'] = "展开全部";
$_LANG['leftmenu'] = "功能菜单";
$_LANG['lefthelp'] = "帮助";

?>