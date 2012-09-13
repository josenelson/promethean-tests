<?php 

	include("php/shapefile/ShapeFile.inc.php");
	
	$shp = new ShapeFile("data/9868/electricity_generating_plants_florida_2007.shp"); 
	
	for($RECID=0;$RECID < $shp->dbf->dbf_num_rec;$RECID++){
		$record = $shp->records[$RECID];
        $cshp = $record->shp_data;
        $shp->fetchNextRecord();
    }
	
	
	
	
