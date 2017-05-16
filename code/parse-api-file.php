<?php

// parse API dump files

require_once (dirname(__FILE__). '/couchsimple.php');

ini_set("auto_detect_line_endings", true); // vital because some files have Windows ending


$filenames=array(
'api-iBOL_phase_0.50_COI.tsv'
);


$bold_to_dc = array(

	'processid' => 'occurrenceID',
	'sampleid' => 'otherCatalogNumbers',
	'museumid' => 'catalogNumber',
	'fieldnum' => 'recordNumber',
	'bin_uri' => 'taxonID',
	'voucher_type' => 'basisOfRecord',
	'institution_storing' => 'institutionCode',
	
	
	'phylum_name'=>'phylum',
	'class_name'=>'class',
	'order_name'=>'order',
	'family_name'=>'family',
	'genus_name'=>'genus',
	'species_name'=>'scientificName',
	'identification_provided_by'=>'identifiedBy',

	'collectors'=>'recordedBy',
	'collectiondate'=>'eventDate',
	'lifestage'=>'lifestage',
	'lat'=>'decimalLatitude',
	'lon'=>'decimalLongitude',
	
	'exactsite'=>'locality',
	'province'=>'stateProvince',
	'country'=>'country',
	
	'genbank_accession' => 'associatedSequences'
);


$data_dir = dirname(dirname(__FILE__)) . '/data/api'; 

foreach ($filenames as $filename)
{
	$keys = array();
	
	$row_count = 0;
	
	
	$filename = $data_dir . '/' . $filename;
	
	$file = @fopen($filename, "r") or die("couldn't open $filename");
	
	$file_handle = fopen($filename, "r");
	while (!feof($file_handle)) 
	{
		$line = trim(fgets($file_handle));
		
		$row = explode("\t", $line);
		
		if ($row_count == 0)
		{
			$keys = $row;
		}
		else
		{
			//print_r($row);
			
			$obj = new stdclass;
			
			$n = count($row);
			for ($i = 0; $i < $n; $i++)
			{
				if (trim($row[$i]) != '')
				{
					switch ($keys[$i])
					{
						case 'copyright_licenses':
							break;
						case 'image_urls':
							$image_urls 		= explode("|", $row[$i]);
							$copyright_licenses = explode("|", $row[$i+1]);
			
							$n = count($image_urls);
							for ($j =0; $j < $n; $j++)
							{
								$media = new stdclass;
				
								// $media->occurrenceID = $id;
								//$media->occurrenceID = $obj->occurrenceID; // old-style, based on data downloads
								$media->title = $obj->occurrenceID;
			
								$media->identifier = $image_urls[$j];
								// some URLs have # symbol (why?)
								$media->identifier = str_replace('#', '%23', $media->identifier);
								// encode '+' otherwise GBIF breaks
								$media->identifier = str_replace('+', '%2B', $media->identifier);
				
								// URL of barcode page 
								$media->references =  'http://bins.boldsystems.org/index.php/Public_RecordView?processid=' . $obj->occurrenceID;
				
								$media->format = '';
								if (preg_match('/\.(?<extension>[a-z]{3,4})$/i', $image_urls[$j], $m))
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
								$media->license = $copyright_licenses[$j]; //  dcterms.license
				
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
								
								// store
								
								$obj->associatedMedia[] = $media;
							}
								
													
							break;
							
						default:
							if (isset($bold_to_dc[$keys[$i]]))
							{
								$obj->{$bold_to_dc[$keys[$i]]} = $row[$i];
							}
							break;
					}
					
				}
			}
			
			// clean
			
			print_r($obj);
			
			// id
			$obj->_id = $obj->occurrenceID;
			
			// fetch
			$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $obj->_id);
			
			//echo $resp;
			
			if ($resp != '')
			{
				$existing = json_decode($resp);
				
				if (isset($existing->images))
				{
					unset($existing->images);
				}
				if (isset($existing->media))
				{
					unset($existing->media);
				}
				
				if (isset($obj->associatedMedia))
				{
					$existing->associatedMedia = $obj->associatedMedia;
					
					//echo "---\n";
					//print_r($existing);
					$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . $existing->_id, json_encode($existing));
					var_dump($resp);
				}
			}
						
			
		}
		
		$row_count++;
		
		if ($row_count > 5000) 
		{
			break;
		}
	
	}

	
}

?>
