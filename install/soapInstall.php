<?php
//install SOAP and dependencies

	function installSoapPackages()
	{
    	  InstallMail_Mime();
	    //install HTTP_Request and dependenies
    	  if( ! InstallHTTP_Request() )
	      {
    	      InstallNet_URL();
        	  InstallNet_Socket();
	          installHTTP_Request();
    	  }
	      InstallNet_DIME();
    	  InstallSoap();
		  echo "\n";
	}

  function InstallSoap()
  { 
     $ret = array();
	 echo "Installing soap...\n";
     $retStr = `pear install -f SOAP`;
     $ret = explode("\n", $retStr);
     if ( !installOK($retStr) )
     {
         echo "SOAP: Initial install failed. ";
         echo "Installing the following dependencies\n";
         for ( $i = 2; $i < sizeof($ret) - 1; $i++ ) 
             echo "\t" . getDependency($ret[$i]) . "\n";
         return false;
     }
     elseif( alreadyInstalled($retStr) )
         return true;
     else
     {
         echo "SOAP: Install completed\n";
         return true;
     }
  }

  function InstallMail_Mime()
  {
	 echo "Installing Mail_Mime...\n";
     $retStr = `pear install -f Mail_Mime`;
     if ( installOK($retStr) ){
         echo "Mail_Mime: Install completed\n";
         return true;
     }
     elseif( alreadyInstalled($retStr) )
         return true;
     else{
         echo "Mail_Mime: Install failed\n";
         return false;
     }
  }

  function InstallHTTP_Request()
  {
     $ret = array();
	 echo "Installing HTTP_Request...\n";
     $retStr = `pear install -f HTTP_Request`;
     $ret = explode("\n", $retStr);
     if ( ! installOK( $retStr ) )
     {
         echo "HTTP_Request: Initial install failed. ";
         echo "Installing the following dependencies\n";
         for ( $i = 2; $i < sizeof($ret) - 1; $i++ ) 
             echo "\t" . getDependency($ret[$i]) . "\n";
         return false;
     }
     elseif( alreadyInstalled($retStr) )
         return true;
     else
     {
         echo "HTTP_Request: Install completed\n";
         return true;
     }
  }

  function InstallNet_URL()
  {
	 echo "Installing Net_URL...\n";
     $retStr = `pear install -f Net_URL`;
     if ( installOK($retStr) ){
         echo "Net_URL: Install completed\n";
         return true;
     }
     elseif( alreadyInstalled($retStr) )
         return true;
     else{
         echo "Net_URL: Install failed\n";
         return false;
     }
  }

  function InstallNet_Socket()
  {
	 echo "Installing Net_Socket\n";
     $retStr = `pear install -f Net_Socket`;
     if ( installOK($retStr) ){
         echo "Net_Socket: Install completed\n";
         return true;
     }
     elseif( alreadyInstalled($retStr) )
         return true;
     else{
         echo "Net_Socket: Install failed\n";
         return false;
     }
  }

  function InstallNet_DIME()
  {
	 echo "Installing Net_Dime...\n";
     $retStr = `pear install -f Net_DIME`;
     if ( installOK($retStr) ){
         echo "Net_DIME: Install completed\n";
         return true;
     }
     elseif( alreadyInstalled($retStr) )
         return true;
     else{
         echo "Net_DIME: Install failed\n";
         return false;
     }
  }

  function getDependency( $depStr )
  {
      $tmp = preg_split("'`([^`]+)\''", $depStr, -1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	if(isset($tmp[1])) {
	      return $tmp[1];
	} else {
		return '';
	}
  }

  function installOK( $retStr )
  {
      if ( substr_count($retStr, "install ok") > 0 )
          return true;
      else
          return false;
  }
  function alreadyInstalled( $retStr )
  {
      if ( substr_count($retStr, "already installed") > 0 )
          return true;
      else
          return false;
  }
?>
