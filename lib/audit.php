<?PHP
include_once '../lib/utility.php';
include_once '../db/db_common.php';

function getFolderAuditStr($db_dept, $cab, $doc_id)
{
    $res = getCabIndexArr($doc_id, $cab, $db_dept);
    $realpath = "(".implode(", ", $res).")";
    return $realpath;
}
?>
