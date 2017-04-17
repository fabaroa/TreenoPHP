<?php
// --------------------------------------------------------------------
// Send PHP files as application/xhtml+xml based on HTTP_ACCEPT, the
// Accept: header sent by the browser
//
// Public domain code, but credit would be nice: http://jan.moesen.nu/
// "Released" 2004/06/16
// --------------------------------------------------------------------

// Prefer HTML over XHTML by default
$acceptedMimeHTML = 0; $acceptedMimeXHTML = -1;
if (!empty($_SERVER['HTTP_ACCEPT']))
{
    $acceptedMimeTypes = preg_split('/\s*,\s*/', $_SERVER['HTTP_ACCEPT']);
    foreach ($acceptedMimeTypes as $type)
    {
        @list($media, $params) = preg_split('/\s*;\s*/', $type, 2);
        $params = preg_split('/\s*;\s*/', $params);
        if ($media == 'text/html')
        {
            $acceptedMimeHTML = 1;
        }
        else if ($media == 'application/xhtml+xml')
        {
            $acceptedMimeXHTML = 1;
        }
        foreach($params as $param)
        {
            @list($name, $value) = preg_split('/=/', $param, 2);
            if ($name == 'q' && $media == 'text/html')
            {
                $acceptedMimeHTML = floatval($value);
            }
            else if ($name == 'q' && $media == 'application/xhtml+xml')
            {
                $acceptedMimeXHTML = floatval($value);
            }
        }
    }
}
// If you'd rather send HTML when there is no preference for
// either HTML or XHTML, change the following ">=" into ">"
$shouldSendXHTML = $acceptedMimeXHTML >= $acceptedMimeHTML;


?>
