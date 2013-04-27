<?
// prefixes:
// sa_  Statistik Austria
include "../../conf.php";
include "../inc/hooks.php";
include "../inc/sql.php";
function debug($text) { }
setlocale(LC_CTYPE, "POSIX");

function read_file($file, $type) {
  $ret=array();
  if($type=="gemeinde") {
    $stati=array("SS"=>"Statuarstadt", "M"=>"Markt", "ST"=>"Stadt", ""=>"");
  }

  $f=fopen($file, "r");
  while($x=fgetcsv($f, null, ";")) {
    if($x[0]==0)
      continue;

    if($type=="bezirk") {
      unset($status);
      $name=utf8_encode($x[3]);
      $gkz=$x[4];
      if(preg_match("/^(.*)\(Stadt\)/", $name, $m)) {
	$name=$m[1];
	$status="Statuarstadt";
      }
    }
    elseif($type=="gemeinde") {
      $gkz=$x[2];
      $status=$stati[$x[3]];
      $name=utf8_encode($x[1]);
//      print_r($x);
      $plz=$x[4];
      if($x[5])
	$plz.=" und {$x[5]}";
    }
    elseif($type=="ortschaft") {
      $gkz=$x[0];
      $okz=$x[2];
      $name=utf8_encode($x[3]);
      $plz=$x[4];
    }

    $ret1=array(
      "ref:at:gkz"=>$gkz,
      "name"	=>$name,
    );
    if(isset($okz))
      $ret1["ref:at:okz"]=$okz;
    if(isset($plz))
      $ret1["plz"]=$plz;
    if(isset($status))
      $ret1["status"]=$status;

    $ret[]=$ret1;
  }
  fclose($f);

  return $ret;
}

$list=array();
$list['bezirk']=read_file("http://www.statistik.at/web_de/static/politische_bezirke_csv_ca._5kb_022948.csv", "bezirk");
$list['gemeinde']=read_file("http://www.statistik.at/web_de/static/gemeinden_sortiert_nach_gemeindekennziffer_mit_status_und_postleitzahlen_c_022953.csv", "gemeinde");
$list['ortschaft']=read_file("http://www.statistik.at/web_de/static/ortschaften_ohne_wien_sortiert_nach_gemeindekennziffer_mit_postleitzahlen__022963.csv", "ortschaft");

file_put_contents("sa.ser", serialize($list));
