<?php

require_once (dirname(__FILE__). '/couchsimple.php');

ini_set("auto_detect_line_endings", true); // vital because some files have Windows ending


$filenames=array(
'iBOL_phase_0.50_COI.tsv'
);


$bold_to_dc = array(

	'processid' => 'occurrenceID',
	'sampleid' => 'otherCatalogNumbers',
	'museumid' => 'catalogNumber',
	'fieldid' => 'recordNumber',
	'bin_guid' => 'taxonID',
	'vouchertype' => 'basisOfRecord',
	'inst_reg' => 'institutionCode',
	
	
	'phylum_reg'=>'phylum',
	'class_reg'=>'class',
	'order_reg'=>'order',
	'family_reg'=>'family',
	'genus_reg'=>'genus',
	'species_reg'=>'scientificName',
	'taxonomist_reg'=>'identifiedBy',

	'collectors'=>'recordedBy',
	'collectiondate'=>'eventDate',
	'lifestage'=>'lifestage',
	'lat'=>'decimalLatitude',
	'lon'=>'decimalLongitude',
	
	'site'=>'locality',
	'province_reg'=>'stateProvince',
	'country_reg'=>'country',
	
	'accession' => 'associatedSequences'
);


$data_dir = dirname(dirname(__FILE__)) . '/data'; 

foreach ($filenames as $filename)
{
	$keys = array();
	
	$row_count = 0;
	
	// CouchDB
	$docs = new stdclass;
	$docs->docs = array();
	
	$bulk_size = 1000;
	$bulk_count = 0;
	$bulk_override = false;
	
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
				if ($row[$i] != '')
				{
					if (isset($bold_to_dc[$keys[$i]]))
					{
						$obj->{$bold_to_dc[$keys[$i]]} = $row[$i];
					}
				}
			}
			
			// clean
			$obj->occurrenceID = str_replace('.COI-5P', '', $obj->occurrenceID);
			
			print_r($obj);
			
			// id
			$obj->_id = $obj->occurrenceID;
			
			// Upload in bulk
			
			$docs->docs [] = $obj;
			echo ".";
			
			if (count($docs->docs ) == $bulk_size)
			{
				if ($bulk_override)
				{
					$docs->new_edits = false;
				}
			
				echo "CouchDB...";
				$resp = $couch->send("POST", "/" . $config['couchdb_options']['database'] . '/_bulk_docs', json_encode($docs));
				$bulk_count += $bulk_size;
				echo "\nUploaded... total=$bulk_count\n";
			
				$docs->docs  = array();
			}
			
			
		}
		
		$row_count++;
		
		if ($row_count > 5000) 
		{
			break;
		}
	
	}
	
	
	 // Make sure we load the last set of docs
	if (count($docs->docs ) != 0)
	{
		echo "CouchDB...\n";
		
		
		if ($bulk_override)
		{
			$docs->new_edits = false;
		}
		
		$resp = $couch->send("POST", "/" . $config['couchdb_options']['database'] . '/_bulk_docs', json_encode($docs));		
		echo $resp;
		
		
		$bulk_count += count($docs->docs);
		echo "\nUploaded... total=$bulk_count\n";
	
	
		$docs->docs  = array();
	}
	
}

?>
