<?php
if (false == ($socket = socket_create(AF_INET, SOCK_STREAM, 0))) {
   die("socket_create() failed:". socket_strerror(socket_last_error())."\n");
} 
if(( $ret = socket_bind( $socket, "192.168.1.223", "9999")) < 0 ) {
	die( "could not bind socket: ".socket_strerror($ret)."\n");
}
if(( $ret = socket_listen( $socket, 5)) < 0) {
	die( "could not listen: ".socket_strerror($ret)."\n");
}
do {
        if (($msgsock = socket_accept($socket)) < 0) {
		echo "sockect_accept failed\n";
                break;
		
        }
	$buffer = "";
        do {
		if(0 >= socket_recv($msgsock,$buf, 2048,0)) {
			echo "socket_recv failed\n";
			$data = unserialize(base64_decode($buffer));
			print_r( $data );
			$strArr = selectFromSagitta( $data[1] );
			print_r($strArr);
			//sendBackData( $data, $strArr );
                        break ;
                }	
		echo "$buf\n";
                $buffer .= trim($buf) ;
	} while(true);
	unset($buf);
	unset($buffer);
} while(true);
function selectFromSagitta( $select ) {
	$db_obj = odbc_connect( 'sag','','');
	//$s = "select * from Um_Coverages_MiscPolicy";
	$res = odbc_exec( $db_obj, $select );
	return odbc_fetch_array( $res );
}
function sendBackData( $data, $strArr ) {
	$fd = fsockopen( $data[2], $data[0] );
	fwrite( $fd, base64_encode(serialize($strArr)) );
	fclose( $fd );
}
?>
