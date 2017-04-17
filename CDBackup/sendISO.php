<?php
include_once "../check_login.php";
include_once "../lib/mime.php";

downloadFile ($DEFS['TMP_DIR'] . '/docutron/' . $user->username . '/cd_backup', 'cd_backup.zip', true, true);
setSessionUser ($user);
?>
