<?php

require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/simplehtmldom_1_5/simple_html_dom.php');

// get data on BIN from BOLD

$bin = 'BOLD:AAA1296';

$bins = array('BOLD:AAA0002','BOLD:AAC9075','BOLD:AAC9112');

$bins=array('BOLD:AAB4119'); // merged

$bins=array('BOLD:ACV4245');

$bins=array('BOLD:AAE3237');

$bins = array('BOLD:AAC2757');

foreach ($bins as $bin)
{
	$url = 'http://bins.boldsystems.org/index.php/Public_BarcodeCluster?clusteruri=' . $bin;

	$html = get($url);
	
	//$html = file_get_contents('ACF4541.html');

	$dom = str_get_html($html);
	
	$merged = '';

	$taxonomy = array();
	$publications = array();
	
	// check that bin hasn't been merged..
	$ps = $dom->find('p strong');
	foreach ($ps as $p)
	{
		//echo $p->plaintext . "\n";
		if (preg_match('/(?<bin>BOLD:[A-Z]+\d+) has been synonymized. This BIN was merged into the BIN (?<merged>BOLD:[A-Z]+\d+)/', $p->plaintext, $m))
		{
			$merged = $m['merged'];
		}
	}
	
	if ($merged)
	{
		$sql = 'REPLACE INTO `bins`(bin, merged_with_bin) VALUES ("'. $bin . '", "' .  $merged . '");';
		echo $sql . "\n";	
	}
	else
	{
		$tables = $dom->find('table[class=binDataTable]');
		foreach ($tables as $table)
		{
			$mode = 0;
		
			$trs = $table->find('thead tr th strong');
			foreach ($trs as $tr)
			{
				if (preg_match('/\s*TAXONOMY:/', $tr->plaintext))
				{
					$mode = 1;
				}
		
				if (preg_match('/\s*PUBLICATIONS:/', $tr->plaintext))
				{
					$mode = 2;
				}
			
				if (preg_match('/\s*BIN DETAILS:/', $tr->plaintext))
				{
					$mode = 3;
				}
			
			}
		
			$trs = $table->find('tbody tr');
			foreach ($trs as $tr)
			{
				$label = '';
		
				$tds = $tr->find('td[class=label]');
				foreach ($tds as $td)
				{
					$label = $td->plaintext;
					$label = preg_replace('/:$/', '', $label);
					$label = strtolower($label);
				}
		
				$tds = $tr->find('td[colspan=3] table tbody tr td span[class=taxName]');
				foreach ($tds as $td)
				{
					$taxonomy[$label][] = $td->plaintext;
				}
			
				/*
				$tds = $tr->find('td');
				foreach ($tds as $td)
				{
					if ($mode == 3)
					{
						echo $label . '=' . $td->plaintext . "\n";
					}
					if (($label == 'doi') && preg_match('/http:\/\/dx.doi.org\/(?<doi>.*)/', $td->plaintext, $m))
					{
						$taxonomy[$label][] = $m['doi'];
					}
				}
				*/
			
				$tds = $tr->find('td[colspan=3]');
				foreach ($tds as $td)
				{
					if ($mode == 2)
					{					
						$publication = array();
						$publication['bin'] = $bin;
					
						$reference = $td->plaintext;
						$reference = preg_replace('/\s+\(PDF\)/u', '', $reference);
						$reference = preg_replace('/\s+$/u', '', $reference);
					
					
						$publication['reference'] = $reference;
					
						$publication['guid'] = md5($reference);
					
						$as = $td->find('a');
						foreach ($as as $a)
						{
							$publication['url'] = $a->href;
						
							$publication['guid'] = $a->href;
						
							if (preg_match('/http:\/\/(dx\.)?doi.org\/(?<doi>.*)/', $a->href, $m))
							{
								$publication['doi'] = $m['doi'];
								$publication['guid'] = $m['doi'];
							}
							// http://onlinelibrary.wiley.com/doi/10.1111/j.1755-0998.2011.03000.x/pdf
							if (preg_match('/http:\/\/onlinelibrary.wiley.com\/doi\/(?<doi>.*)\/(abstract|epdf|full|pdf)/', $a->href, $m))
							{
								$publication['doi'] = $m['doi'];
								$publication['guid'] = $m['doi'];
							}
							// info%3Adoi%2F10.1371%2Fjournal.pone.0028987&representation=PDF
							if (preg_match('/info:doi\/(?<doi>.*)(&representation=PDF)?/', urldecode($a->href), $m))
							{
								$doi = $m['doi'];
								$doi = preg_replace('/#\w\d+/', '', $doi);
								$doi = preg_replace('/&representation=PDF/', '', $doi);
								$publication['doi'] = $doi;
								$publication['guid'] = $doi;
							}
							
							// http://journals.plos.org/plosone/article?id=10.1371/journal.pone.0115774
							if (preg_match('/plosone\/article\?id=(?<doi>.*)/', urldecode($a->href), $m))
							{
								$doi = $m['doi'];
								$publication['doi'] = $doi;
								$publication['guid'] = $doi;
							}
							
							// http://link.springer.com/article/10.1007%2Fs00227-009-1284-0
							if (preg_match('/link.springer.com\/article\/(?<doi>.*)/', urldecode($a->href), $m))
							{
								$doi = $m['doi'];
								$publication['doi'] = $doi;
								$publication['guid'] = $doi;
							}
							
							// http://informahealthcare.com/doi/full/10.3109/19401736.2012.748041
							if (preg_match('/informahealthcare.com\/doi\/(abstract|full|pdf)\/(?<doi>.*)/', urldecode($a->href), $m))
							{
								$doi = $m['doi'];
								$publication['doi'] = $doi;
								$publication['guid'] = $doi;
							}
							
							// http://pubs.acs.org/doi/abs/10.1021/jf901618z
							if (preg_match('/pubs.acs.org\/doi\/abs\/(?<doi>.*)/', urldecode($a->href), $m))
							{
								$doi = $m['doi'];
								$publication['doi'] = $doi;
								$publication['guid'] = $doi;
							}
							
						
						}
						
					
						$publications[] = $publication;
					}
				}
			
			}
	
	
		}
		//print_r($taxonomy);
	
		//print_r($publications);
	
		$keys = array();
		$values = array();
	
		$keys[] = '`bin`';
		$values[] = '"' . $bin . '"';
	
		foreach ($taxonomy as $k => $v)
		{
			$keys[] = "`" . $k . "`";
			$values[] = '"' . addcslashes(join(";", $v), '"') . '"';
		}
	
		foreach ($taxonomy as $k => $v)
		{
			$keys[] = "`" . $k . "_count`";
			$values[] = count($v);
		}
	
		// BIN
		$sql = 'REPLACE INTO bins(' . join(",", $keys) . ') VALUES (' . join(",", $values) . ');';
		echo $sql . "\n";
	
		// Publications
		foreach ($publications as $publication)
		{
			$keys = array();
			$values = array();
		
			foreach ($publication as $k => $v)
			{
				$keys[] = "`" . $k . "`";
				$values[] = '"' . addcslashes($v, '"') . '"';
			}
		
			$sql = 'REPLACE INTO `references`(' . join(",", $keys) . ') VALUES (' . join(",", $values) . ');';
			echo $sql . "\n";
		}
	}
	
}

?>
