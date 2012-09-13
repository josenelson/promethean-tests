<?

$shp = new ShapeFile("file.shp"); // along this file the class will use file.shx and file.dbf

// Let's see all the records:
foreach($shp->records as $record){
     echo "<pre>"; // just to format
     print_r($record->shp_data);   // All the data related to the poligon
     print_r($record->dbf_data);   // The alphanumeric information related to the figure
     echo "</pre>";
}			