<?php

// Read database files and fetch same records from BOLD API (because)

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
function fetch_data($ids)
{
	$url = 'http://www.boldsystems.org/index.php/API_Public/combined';
	
	$url .= '?ids=' .  join('|', $ids);
	$url .= '&format=tsv';
	
	//echo $url . "\n";
	
	$data = get($url);
	
	echo $data;
	
	$values = explode("\n", $data);

	/*
	if ((count($values) - 2) != count($ids))
	{
		echo count($values) . ' ' . count($ids) . "\n";
		echo "Problem fetching data\n";
		exit();
	}
	*/
	
	return $values;
}

//----------------------------------------------------------------------------------------


$basedir = dirname(dirname(__FILE__));



// BOLD data files
$filenames=array(
'iBOL_phase_0.50_COI.tsv'
);


// Number of records to fetch in one API call
$page_size = 20;

foreach ($filenames as $filename)
{
	$ids = array();
	
	$row_count = 0;
	$chunk_count = 0;
	
	$data_filename = $basedir . '/data/' . $filename;
	
	$output_filename = $basedir . '/api-' . $filename;
	unlink($output_filename);
	
	$file = @fopen($data_filename, "r") or die("couldn't open $data_filename");
	
	$file_handle = fopen($data_filename, "r");
	while (!feof($file_handle)) 
	{
		$line = trim(fgets($file_handle));
		
		$row = explode("\t", $line);
		
		if ($row_count == 0)
		{
			// ignore
		}
		else
		{
			$id = $row[0];
			$id = preg_replace('/\.COI-5P$/', '', $id);
			
			$ids[] = $id;
			
			if ($row_count % $page_size == 0)
			{
				$values = fetch_data($ids);
				
				if ($chunk_count != 0)
				{
					array_shift($values);
				}
				
				file_put_contents($output_filename, join("\n", $values), FILE_APPEND);
			
				echo $row_count . "\n";
			
				$chunk_count++;
				$ids = array();				
				pause();
			}
		}
				
		$row_count++;
		
		
		// testing
		//if ($row_count > 65) break;
	}
	
	 // Make sure we load the last set of rows
	if (count($ids) != 0)
	{
		$values = fetch_data($ids);	
		array_shift($values);
		file_put_contents($output_filename, join("\n", $values), FILE_APPEND);
	}
}

?>
