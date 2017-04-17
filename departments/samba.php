<?php
//NOT WINDOWS-SAFE
chdir(dirname(__FILE__));
require_once '../lib/fileFuncs.php';
require_once '../lib/settings.php';

$db_name = $argv[1];//department name
if( $db_name=="" ) {
	die();
} else {
}
copy('/etc/samba/smb.conf', '/etc/samba/smb.conf.old');
$fp = fopen( '/etc/samba/smb.conf', 'a+' );
//creates samba share for inbox
fwrite( $fp, "\n\n[$db_name-inbox]");
fwrite( $fp, "\n\tcomment = this share is designated for $db_name");
fwrite( $fp, "\n\tpath = {$DEFS['DATA_DIR']}/$db_name/inbox");
fwrite( $fp, "\n\tpublic = no");
fwrite( $fp, "\n\twritable = yes");
fwrite( $fp, "\n\tvalid users = @$db_name");
fwrite( $fp, "\n\tcreate mask = 0775");
fwrite( $fp, "\n\tguest ok = no");
//creates samba share for indexing
fwrite( $fp, "\n\n[$db_name-indexing]");
fwrite( $fp, "\n\tcomment = this share is designated for $db_name");
fwrite( $fp, "\n\tpath = {$DEFS['DATA_DIR']}/$db_name/indexing");
fwrite( $fp, "\n\tpublic = no");
fwrite( $fp, "\n\twritable = yes");
fwrite( $fp, "\n\tvalid users = @$db_name");
fwrite( $fp, "\n\tcreate mask = 0775");
fwrite( $fp, "\n\tguest ok = no");
//creates samba share for backup
fwrite( $fp, "\n\n[$db_name-backup]");
fwrite( $fp, "\n\tcomment = this share is designated for $db_name");
fwrite( $fp, "\n\tpath = {$DEFS['DATA_DIR']}/$db_name/");
fwrite( $fp, "\n\tpublic = no");
fwrite( $fp, "\n\twritable = no");
fwrite( $fp, "\n\tvalid users = @$db_name");
fwrite( $fp, "\n\tcreate mask = 0765");
fwrite( $fp, "\n\tguest ok = no");
fclose( $fp );

shell_exec("/usr/sbin/groupadd -f $db_name");
shell_exec("/usr/sbin/useradd -g $db_name $db_name");
shell_exec("(echo $db_name;echo $db_name) | /usr/bin/smbpasswd -a $db_name -s");
shell_exec("/etc/init.d/smb restart");

chownDir($DEFS['DATA_DIR'].'/'.$db_name, 'apache', $db_name);
chmodDir($DEFS['DATA_DIR'].'/'.$db_name, 0775);

?>
