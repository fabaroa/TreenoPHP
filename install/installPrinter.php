<?php
require '../lib/settings.php';

function installPrinter() {
	global $DEFS;
	$t = date("D-M-j-G-i-s-Y");

	echo " Creating new smb.conf\n";
	# This is the editing needed for the smb.conf file
	$oldfile = fopen("/etc/samba/smb.conf", "r");
	$newfile = fopen("conf/printsmb.conf", "w");

	// Here we will read a line from the old and look for sections we need
	// to edit. Once there, we will look for lines we must change in that
	// section.

	// Look for printing and set it to cups
	$gsearch[] = "printing =";
	$greplace[] = "\tprinting = BSD\n";
	// Look for printcap name and set it to cups
	$gsearch[] = "printcap name =";
	$greplace[] = "\tprintcap name = lpstat\n";
	// Look for guest account and set to nobody
	$gsearch[] = "guest account =";
	$greplace[] = "\tguest account = apache\n";
	$gsearch[] = "load printers =";
	$greplace[] = "\tload printers = No\n";
	$gsearch[] = "print command =";
	$greplace[] = "\tprint command = \n";
	$gsearch[] = "lpq command =";
	$greplace[] = "\tlpq command = \n";
	$gsearch[] = "lprm command =";
	$greplace[] = "\tlprm command = \n";

	$line = fgets($oldfile);

	while (!feof($oldfile)) {

		// look for the global section
		if (strstr($line, "[global]")) {
			fwrite($newfile, $line);
			$line = fgets($oldfile);

			while (!strstr($line, "[") && !feof($oldfile)) {
				// cycle through all possible searches
				for ($i = 0; $i < sizeof($gsearch); $i ++) {
					if (strstr($line, $gsearch[$i])) {
						$line = $greplace[$i];
						$greplace[$i] = "done"; // mark this has been printed
						break;
					}
				}

				// Dont print out empty line yet, need to still do stuff			
				if ($line != "\n")
					fwrite($newfile, $line);
				$line = fgets($oldfile);
			}

			// now print out lines that havent yet
			for ($i = 0; $i < sizeof($gsearch); $i ++) {
				if ($greplace[$i] != "done") {
					fwrite($newfile, $greplace[$i]);
				}
			}

			// Write out printer info, dont print it out later
			$printerinfo = "\n[Docutron]\n\t" .
					"path = /tmp\n\t" .
					"browseable = yes\n\t" .
					"public = yes\n\t" .
					"use client driver = yes\n\t" .
					"guest ok = yes\n\t" .
					"writeable = yes\n\t" .
					"printable = yes\n\t" .
					"print command = /usr/bin/php -q {$DEFS['DOC_DIR']}/tools/pdfPrint.php %s\n\t" .
					"guest only = yes\n\n";
			fwrite($newfile, $printerinfo);
		}

		if (strstr($line, "[printers]") && $line[0] != "#") {
			fwrite($newfile, "#This is the old printers section\n");
			fwrite($newfile, "#".$line);
			$line = fgets($oldfile);
			while (!strstr($line, "[") && !feof($oldfile)) {
				fwrite($newfile, "#".$line);
				$line = fgets($oldfile);
			}
		}

		fwrite($newfile, $line);
		$line = fgets($oldfile);
	}

	if (file_exists("/etc/samba/smb.conf")) {
		rename('/etc/samba/smb.conf', '/etc/samba/smb-'.$t.'.conf');
	}
	copy('conf/printsmb.conf', '/etc/samba/smb.conf');

	echo "Restarting Samba\n";
	shell_exec('/etc/init.d/smb restart');

	echo "\n Printer installed\n\n";
}
?>
