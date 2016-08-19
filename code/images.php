<?php

// Parse dump of images and export in Darwin Core Archive format

$basedir = dirname(dirname(__FILE__));

$data_filename = $basedir . '/images.txt';

$media_filename 			= $basedir . '/media.txt';


if (file_exists($media_filename))
{	
	unlink($media_filename);
}

	
$file = @fopen($data_filename, "r") or die("couldn't open $data_filename");

$media_count = 0;

$file_handle = fopen($data_filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
	
	$row = explode("\t", $line);
	
	
	$image_urls = $row[2];
	
	$image_urls 		= explode("|", $row[2]);
	$copyright_licenses = explode("|", $row[3]);
	
	$n = count($image_urls);
	for ($i =0; $i < $n; $i++)
	{
		$media = new stdclass;
		
		// $media->occurrenceID = $id;
		$media->occurrenceID = $row[0]; // old-style, based on data downloads
		$media->title = $row[1];
	
		$media->identifier = $image_urls[$i];
		// some URLs have # symbol (why?)
		$media->identifier = str_replace('#', '%23', $media->identifier);
		// encode '+' otherwise GBIF breaks
		$media->identifier = str_replace('+', '%2B', $media->identifier);
		
		// URL of barcode page 
		$media->references =  'http://bins.boldsystems.org/index.php/Public_RecordView?processid=' . $row[1];
		
		$media->format = '';
		if (preg_match('/\.(?<extension>[a-z]{3,4})$/i', $image_urls[$i], $m))
		{
			switch (strtolower($m['extension']))
			{
				case 'gif':
					$media->format = 'image/gif';
					break;
				case 'jpg':
				case 'jpeg':
					$media->format = 'image/jpeg';
					break;
				case 'png':
					$media->format = 'image/png';
					break;
				case 'tif':
				case 'tiff':
					$media->format = 'image/tiff';
					break;
				default:
					break;
			}
		}
		$media->license = $copyright_licenses[$i]; //  dcterms.license
		
		// Convert to URL if possible
		switch ($media->license)
		{
			case 'CreativeCommons - Attribution':
				$media->license = 'https://creativecommons.org/licenses/by/3.0/';
				break;
				
			case 'CreativeCommons - Attribution Share-Alike':
				$media->license = 'https://creativecommons.org/licenses/by-sa/3.0/';
				break;
				
			case 'CreativeCommons by-nc-sa':
			case 'CreativeCommons - Attribution Non-Commercial Share-Alike':
			case 'CreativeCommons ñ Attribution Non-Commercial Share-Alike':
			case 'CreativeCommons-Attribution Non-Commercial Share-Alike':
				$media->license = 	'https://creativecommons.org/licenses/by-nc-sa/3.0/';
				break;
				
			case 'CreativeCommons - Attribution by Laurence Packer':
				$media->license = 'https://creativecommons.org/licenses/by/3.0/';
				break;
				
			case 'CreativeCommons - Attribution Non-Commercial No Derivatives':
				$media->license = 'https://creativecommons.org/licenses/by-nc/3.0/';
				break;
				
			case 'CreativeCommons - Attribution Non-Commercial':
				$media->license = 'https://creativecommons.org/licenses/by-nc/3.0/';
				break;
				
			case 'No Rights Reserved':
			case 'No Rights Reserved (nrr)':
				$media->license = 	'http://creativecommons.org/publicdomain/mark/1.0/';
				break;
				
			case 'CreativeCommons': // ?
			default:
				break;
		}
		
		$header = array();
		$values = array();
		foreach ($media as $k => $v)
		{
			$header[] = $k;
			$values[] = $v;
		}
		
		if ($media_count == 0)
		{
			//echo join("\t", $header) . "\n";
			file_put_contents($media_filename, join("\t", $header) . "\n");
		}
		//echo join("\t", $values) . "\n";
		file_put_contents($media_filename, join("\t", $values) . "\n", FILE_APPEND);
		
		//print_r($media);

		$media_count++;
	}
	
	
}

?>
