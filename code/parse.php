<?php

// Read  files and fetch same records from BOLD API (because)

require_once (dirname(__FILE__) . '/lib.php');

ini_set("auto_detect_line_endings", true); // vital because some files have Windows ending

//----------------------------------------------------------------------------------------
function pause()
{
	$rand = rand(500000, 1000000);
	echo "Sleep for " . ($rand/1000000) . " seconds\n";
	usleep($rand); 
}

//----------------------------------------------------------------------------------------
function fetch_data($id)
{
	$obj = null;
	
	$url = 'http://www.boldsystems.org/index.php/API_Public/combined';
	
	$url .= '?ids=' .  $id;
	$url .= '&format=tsv';
	
	//echo $url . "\n";
	
	$data = get($url);
	
	//echo $data;
	
	if ($data != '')
	{
		$rows = explode("\n", $data);
		
		//print_r($rows);
		
		$keys = explode("\t", $rows[0]);
		$values = explode("\t", $rows[1]);

		$obj = new stdclass;
		
		$n = count($values);
		for ($i = 0; $i < $n; $i++)
		{
			//if (trim($values[$i]) != '')
			{
				$obj->{$keys[$i]} = trim($values[$i]);
			}
		}
		
		//print_r($obj);
		//exit();
	}
	
	return $obj;
		
}

//----------------------------------------------------------------------------------------

$basedir = dirname(dirname(__FILE__));

$data_filename = $basedir . '/occurrences.tsv';

$media_filename 			= $basedir . '/media.txt';
$identifications_filename 	= $basedir . '/identifications.txt';


if (file_exists($media_filename))
{	
	unlink($media_filename);
}
if (file_exists($identifications_filename))
{	
	unlink($identifications_filename);
}
	
$file = @fopen($data_filename, "r") or die("couldn't open $data_filename");

$count = 0;

$occurrence_count 		= 0;
$media_count 			= 0;
$identification_count 	= 0;

$keys = array();

$file_handle = fopen($data_filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
	
	//echo $line;
	
	$row = explode("\t", $line);
	
	if ($count == 0)
	{
		$keys = $row;
	}
	else
	{
		$obj = new stdclass;
		
		$n = count($row);
		for ($i = 0; $i < $n; $i++)
		{
			//if (trim($row[$i]) != '')
			{
				$obj->{$keys[$i]} = trim($row[$i]);
			}
		}
		
		//print_r($obj);
		
		//exit();
		
		$id = $obj->processid;
		$id = preg_replace('/\.COI-5P$/', '', $id);
		
		$api_obj = fetch_data($id);
		
		print_r($api_obj);
		
		// occurrence
		$occurrence = new stdclass;
		
		$occurrence->occurenceID = $id;
		
		$occurrence->otherCatalogNumbers 	= $obj->sampleid;
		$occurrence->catalogNumber			= $obj->museumid;
		$occurrence->recordNumber 			= $obj->fieldid;
		
		$occurrence->institutionCode 		= $obj->inst_reg;
		
		$occurrence->taxonID 				= $obj->bin_guid;
		
		// taxonomy
		$occurrence->phylum					= $obj->phylum_reg;
		$occurrence->class					= $obj->class_reg;
		$occurrence->order					= $obj->order_reg;
		$occurrence->family					= $obj->family_reg;
		$occurrence->genus					= $obj->genus_reg;
		$occurrence->scientificName			= $obj->species_reg;
		$occurrence->identifiedBy			= $obj->taxonomist_reg;
		
		// event
		$occurrence->recordedBy				= $obj->collectors;
		$occurrence->verbatimEventDate		= $obj->collectiondate;
		$occurrence->lifestage				= $obj->lifestage;
		
		// Locality
		$occurrence->decimalLatitude 		= $obj->lat;
		$occurrence->decimalLongitude 		= $obj->lon;
		
		$occurrence->locality				= $obj->site;
		$occurrence->stateProvince			= $obj->province_reg;
		$occurrence->country				= $obj->country_reg;
		
		// GenBank
		$occurrence->associatedSequences	= $obj->accession;

 		// OK, if we have data from API can we update?
		if ($api_obj)
		{
			// Taxonomy
			/*
			$occurrence->phylum					= $api_obj->phylum_name;
			$occurrence->class					= $api_obj->class_name;
			$occurrence->order					= $api_obj->order_name;
			$occurrence->family					= $api_obj->family_name;
			$occurrence->genus					= $api_obj->genus_name;
			$occurrence->scientificName			= $api_obj->species_name;
			$occurrence->identifiedBy			= $api_obj->identification_provided_by; */
			
			if (($api_obj->species_name != '') && ($api_obj->species_name != $occurrence->scientificName))
			{
				// Treat updated info we get from API as an identification
				$identification = new stdclass;
				$identification->occurrenceID = $id;
				$identification->identificationID = $identification_count + 1;
				
				$identification->scientificName = $api_obj->species_name;
				$identification->identifiedBy = $api_obj->identification_provided_by;
				
				$header = array();
				$values = array();
				foreach ($identification as $k => $v)
				{
					$header[] = $k;
					$values[] = $v;
				}
		
				if ($identification_count == 0)
				{
					file_put_contents($identifications_filename, join("\t", $header) . "\n");
				}
				file_put_contents($identifications_filename, join("\t", $values) . "\n", FILE_APPEND);
				
				$identification_count++;
			}
			
			// locality
			//$occurrence->stateProvince			= $api_obj->province;
			//$occurrence->country				= $api_obj->country;
			
			
			// GenBank
			$occurrence->associatedSequences	= $api_obj->genbank_accession;
		
		}
		
		//print_r($occurrence);
		
		$header = array();
		$values = array();
		foreach ($occurrence as $k => $v)
		{
			$header[] = $k;
			$values[] = $v;
		}
		
		/*
		if ($occurrence_count == 0)
		{
			//echo join("\t", $header) . "\n";
			file_put_contents($occurrences_filename, join("\t", $header) . "\n");
		}
		//echo join("\t", $values) . "\n";
		file_put_contents($occurrences_filename, join("\t", $values) . "\n", FILE_APPEND);
		
		$occurrence_count++;
		*/
		
		// Do we have any associated media? (specimen images)
		if ($api_obj->image_urls != '')
		{
			$image_urls 		= explode("|", $api_obj->image_urls);
			$copyright_licenses = explode("|", $api_obj->copyright_licenses);
			
			$n = count($image_urls);
			for ($i =0; $i < $n; $i++)
			{
				$media = new stdclass;
				
				$media->occurrenceID = $id;
				$media->title = $id;
			
				$media->identifier = $image_urls[$i];
				// some URLs have # symbol (why?)
				$media->identifier = str_replace('#', '%23', $media->identifier);
				
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
				
				print_r($media);

				$media_count++;
				
		
				
			}
		}
		
		
	}
			

			
	$count++;
	
	if ($count % 100 == 0)
	{
		pause();
	}
	
	
	// testing
	//if ($count > 10) break;
}

?>
