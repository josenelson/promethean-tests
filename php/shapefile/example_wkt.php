<?php
include("ShapeFile.inc.php");
$shp = new ShapeFile("us.shp"); // along this file the class will use file.shx and file.dbf

    $server="localhost";
    $user="root";
    $pwd="root";
    $db="geo";
    if(file_exists("dbc.php")){include("dbc.php");}
    $conn=@mysql_connect($server,$user,$pwd)
          or die('cannot connect to DB Server'.mysql_error().$server."/".$user."/".$pwd);
    @mysql_select_db($db,$conn)
          or die("cannot select database".mysql_error() )  ;


/*
      00 : FShapeTypeName :=          'Null Shape';
      01 : FShapeTypeName :=          'Point';
      03 : FShapeTypeName :=          'PolyLine';
      05 : FShapeTypeName :=          'Polygon';
      08 : FShapeTypeName :=          'MultiPoint';
      11 : FShapeTypeName :=          'PointZ';
      13 : FShapeTypeName :=          'PolyLineZ';
      15 : FShapeTypeName :=          'PolygonZ';
      18 : FShapeTypeName :=          'MultiPointZ';
      21 : FShapeTypeName :=          'PointM';
      23 : FShapeTypeName :=          'PolyLineM';
      25 : FShapeTypeName :=          'PolygonM';
      28 : FShapeTypeName :=          'MultiPointM';
      31 : FShapeTypeName :=          'MultiPatch';
*/
echo "loaded : ".$shp->dbf->dbf_num_rec." records available<br>";
// Let's see all the records:
    set_time_limit(160);
    $field_num = $shp->dbf->dbf_num_field;
    $tn = 'usmap';
    $sql = "DROP TABLE IF EXISTS $tn; " ;
        $rz = @mysql_query($sql)    or die("EMPTY RESULT. Process may have finished ".mysql_error()."<br>".$sql );
    $crtb = "CREATE TABLE $tn ( ID_AUTO bigint(20) NOT NULL auto_increment, ";
    for($j=0; $j<$field_num; $j++){
	    $crtb .= $shp->dbf->dbf_names[$j]['name'];
	    $tp="TEXT";
	    if($shp->dbf->dbf_names[$j]['type']=='C') $tp="VARCHAR(".$shp->dbf->dbf_names[$j]['len'].")";
	    if($shp->dbf->dbf_names[$j]['type']=='N') $tp="DOUBLE";
	    $crtb .= "  ".$tp."  NULL , ";
    }
    if($shp->shp_type==5){
	    $crtb .= "  FEATURE POLYGON , TRICK LONGTEXT  NULL , PRIMARY KEY  (ID_AUTO) ) ENGINE = MYISAM ";
    }
    echo"<br>$crtb<br>";
    $sql=$crtb;
        $rz = @mysql_query($sql)    or die("EMPTY RESULT. Process may have finished ".mysql_error()."<br>".$sql );

//foreach($shp->records as $record){
for($RECID=0;$RECID < $shp->dbf->dbf_num_rec;$RECID++){
     echo "<br>processing $RECID <br>"; // just to format
     $record = $shp->records[$RECID];
     $cshp = $record->shp_data;
    if($shp->shp_type==5){
     $wkt='POLYGON(';
     for ($r = 0; $r < $cshp["numparts"];$r++){//r comes from ring
       $wkt.='('; //start ring
       $kp = count($cshp["parts"][$r]["points"]);
           set_time_limit(160);
     echo "<br> ring $r has $kp points";
       for($p = 0; $p < $kp; $p++){
          $x = $cshp["parts"][$r]["points"][$p]["x"];
          $y = $cshp["parts"][$r]["points"][$p]["y"];
          $wkt.=$x.' '.$y;
          if($p<$kp-1)$wkt.=','; //enum vertices
          if($p%100==0)    set_time_limit(160);
       }
       $wkt.=')'; //close ring
       if($r < $cshp["numparts"]-1)$wkt.=','; //enum rings
     }
     $wkt.=')';
    }
    $sql="INSERT INTO $tn VALUES('',";
    for($j=0; $j<$field_num; $j++){
      $sql.="'".$record->dbf_data[$j]."',";
    }
    $sql.="'',NULL)";
    echo substr($sql,0,100)."...<br><br>";
    $rz = @mysql_query($sql)    or die("EMPTY RESULT. Process may have finished ".mysql_error()."<br>".$sql );
    $idauto = @mysql_insert_id();
    $baud=25000;//bytes
    for($z = 0; $z < strlen($wkt)/$baud;$z++){
      $akt = substr($wkt,$z*$baud,$baud);
      $sql = "UPDATE $tn SET TRICK = CONCAT(TRICK ,'$akt') WHERE ID_AUTO = $idauto";
      echo substr($sql,0,100)."...<br><br>";
      $rz = @mysql_query($sql)    or die("EMPTY RESULT. Process may have finished ".mysql_error()."<br>".$sql );
    }
    $sql = "UPDATE $tn SET FEATURE = GeomFromText(TRICK) WHERE ID_AUTO = $idauto";
    echo substr($sql,0,100)."...<br><br>";
    $rz = @mysql_query($sql)    or die("EMPTY RESULT. Process may have finished ".mysql_error()."<br>".$sql );

    $sql = "UPDATE $tn SET TRICK = NULL WHERE ID_AUTO = $idauto";
    echo substr($sql,0,100)."...<br><br>";
    $rz = @mysql_query($sql)    or die("EMPTY RESULT. Process may have finished ".mysql_error()."<br>".$sql );

    $shp->fetchNextRecord();

}
//GeomFromText('$wkt')
?>
