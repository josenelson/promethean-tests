<?php
include("ShapeFile.inc.php");
function strhex($string)
{
   $hex="";
   for ($i=0;$i<strlen($string);$i++)
       $hex.=(strlen(dechex(ord($string[$i])))<2)? "0".dechex(ord($string[$i])): dechex(ord($string[$i]));
   return $hex;
}

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
	    $crtb .= " FEATURE POLYGON, PRIMARY KEY  (ID_AUTO) ) ENGINE = MYISAM ";
    }
    echo"<br>$crtb<br>";
    $sql=$crtb;
    $rz = @mysql_query($sql)    or die("EMPTY RESULT. Process may have finished ".mysql_error()."<br>".$sql );


$handle = fopen($shp->file_name.".wkb", "wb");

$byteorder = 1;

for($RECID=0;$RECID < $shp->dbf->dbf_num_rec;$RECID++){
     echo "<pre>"; // just to format
     echo $RECID.", ";
     $record = $shp->records[$RECID];
     $cshp = $record->shp_data;
    if($shp->shp_type==5){
     $t=3;//OpenGis Poly
     $rings = $cshp["numparts"];
     $bin = pack("LcLL",0,$byteorder,$t,$rings);

     for ($r = 0; $r < $rings ;$r++){//r comes from ring
       $kp = count($cshp["parts"][$r]["points"]);
       $bin .= pack("L",$kp);
       set_time_limit(160);
       for($p = 0; $p < $kp; $p++){
          $x = $cshp["parts"][$r]["points"][$p]["x"];
          $y = $cshp["parts"][$r]["points"][$p]["y"];
          $bin .= pack("dd",$x,$y);
          if($p%100==0)    set_time_limit(160);
       }
     }
    }
    echo strhex( $bin ).'...';
    $bin = mysql_escape_string($bin);
    $bin .= pack("C",0x0A);
//    $sql="INSERT INTO $tn VALUES('',"; pune 0x09 între câmpuri
    $s = (string)$RECID;
    $s .= chr(9);
if ($RECID==5){
    fwrite($handle,$s,strlen($s));
    for($j=0; $j<$field_num; $j++){
      $s = (string)$record->dbf_data[$j];
      $s .= chr(9);
      fwrite($handle,$s,strlen($s));
    }
    fwrite($handle,$bin,strlen($bin));
break;
}
    $shp->fetchNextRecord();
}
fclose($handle);
//LOAD DATA LOCAL INFILE '/phpdev/www/shp/us.shp.wkb' INTO TABLE usmap(FEATURE)

?>
