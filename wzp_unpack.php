<?php
/**
 * YFAPP.WZP Unpack
 *
 * @Author: Zhelneen Evgeniy
 * @Contacts: skype: zhelneen
 * @Date: 24 sep 2013
 *
 * @Notes: Compile using BamCompile: http://www.bambalam.se/bamcompile/
 */

// No time limits
set_time_limit(0);

// Usage info
echo "YFAPP.WZP file unpacker by Zhelneen Evgeniy.\n";
echo "usage: wzp_unpack [yfapp.wzp] [out_dir]\n\n";

// WZP file, yfapp.wzp
if ($argv[1] == "")
  $wzp_file = "yfapp.wzp";
else
  $wzp_file = $argv[1];

// Opening file
if (!file_exists($wzp_file)) {
  echo "File \"".$wzp_file."\" not found!\n";
  exit;
}
$f = file_get_contents($wzp_file);

// Output directory (default is "Unpacked")
if ($argv[2] == "")
  $out_dir = "Unpacked";
else
  $out_dir = $argv[2];

if (!file_exists($out_dir)) {
	mkdir($out_dir, true);
}
if (!is_dir($out_dir)) {
	echo "Error: Can't create directory ".$out_dir."\n";
}

// Analyze file information
echo "Analyze file...\n";
$header = substr($f, -23, 14); // Search is better, but now it works
list(,$magic) =     unpack('v', substr($header, 0, 2));
list(,$ver) =       unpack('v', substr($header, 0, 2));
list(,$files) =     unpack('v', substr($header, 4, 2));
list(,$size) =      unpack('V', substr($header, 6, 4));
list(,$offset2) =   unpack('V', substr($header, 10, 4));

// Something goes wrong...
if ($magic != 0xd9ff) {
	echo "Error: File signature unknown!\n";
	die();
}

echo "YFAPP.WZP archive found.\n";

echo "Files: ".$files."\n";
echo "\n";

$offset = 0;

for ($i = 0; $i < $files; $i++) {
	// First table
	list(,$magic) =     unpack('v', substr($f, $offset, 2)); $offset += 2;
	if ($magic != 0xd8ff) {
		echo "Error: Invalid block signature (table1)!\n";
		die();
	}
	list(,$type) =      unpack('v', substr($f, $offset, 2)); $offset += 2;
	list(,$ver) =       unpack('v', substr($f, $offset, 2)); $offset += 2;
	list(,$flag) =      unpack('v', substr($f, $offset, 2)); $offset += 2;
	list(,$method) =    unpack('v', substr($f, $offset, 2)); $offset += 2;
	list(,$modtime) =   unpack('v', substr($f, $offset, 2)); $offset += 2;
	list(,$moddate) =   unpack('v', substr($f, $offset, 2)); $offset += 2;
	list(,$crc32) =     unpack('V', substr($f, $offset, 4)); $offset += 4;
	list(,$name_len) =  unpack('v', substr($f, $offset, 2)); $offset += 2;
	$filename = substr($f, $offset, $name_len); $offset += $name_len;

	// Second table
	list(,$magic) =     unpack('v', substr($f, $offset2, 2)); $offset2 += 2;
	if ($magic != 0xd8ff) {
		echo "Error: Invalid block signature (table2)!\n";
		die();
	}
	list(,$type) =      unpack('v', substr($f, $offset2, 2)); $offset2 += 2;
	list(,$ver_made) =  unpack('v', substr($f, $offset2, 2)); $offset2 += 2;
	list(,$ver_need) =  unpack('v', substr($f, $offset2, 2)); $offset2 += 2;
	list(,$flag) =      unpack('v', substr($f, $offset2, 2)); $offset2 += 2;
	list(,$method) =    unpack('v', substr($f, $offset2, 2)); $offset2 += 2;
	list(,$modtime) =   unpack('v', substr($f, $offset2, 2)); $offset2 += 2;
	list(,$moddate) =   unpack('v', substr($f, $offset2, 2)); $offset2 += 2;
	list(,$crc32) =     unpack('V', substr($f, $offset2, 4)); $offset2 += 4;
	list(,$name_len) =  unpack('v', substr($f, $offset2, 2)); $offset2 += 2;
	list(,$chunks) =    unpack('V', substr($f, $offset2, 4)); $offset2 += 4;
	$filename = substr($f, $offset2, $name_len); $offset2 += $name_len;

	echo $filename." ";

	// Directory
	if (substr($filename, -1, 1) == '\\') {
		if (!file_exists($out_dir."\\".$filename)) {
			mkdir($out_dir."\\".$filename, true);
		}
		if (!is_dir($out_dir."\\".$filename)) {
			echo "Error: Can't create directory ".$oud_dir."\\".$filename."\n";
		}
	}
	// File
	else {
		// Chunk table
		$contents = "";
		for($j=0; $j<$chunks; $j++) {
			list(,$uncomp_size) =   unpack('V', substr($f, $offset2 + 0, 4));
			list(,$comp_size) =     unpack('V', substr($f, $offset2 + 4, 4));
			list(,$ext_attr) =      unpack('V', substr($f, $offset2 + 8, 4));
			$chunk = substr($f, $offset, $comp_size);
			$unpacked_cnunk = gzuncompress($chunk);
			$contents .= $unpacked_cnunk;
			$offset += $comp_size;
			$offset2 += 12;
		}
		echo "CRC: ";
		echo (crc32($contents) == $crc32) ? "Ok" : "Error";
		$file = fopen($out_dir."\\".$filename, "wb");
		fwrite($file, $contents);
		fclose($file);
	}
	echo "\n";
}
echo "Done.";
