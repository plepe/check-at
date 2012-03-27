<?
// prefixes:
// sa_  Statistik Austria
$f=fopen("http://www.statistik.at/web_de/static/politische_bezirke_csv_ca._5kb_022948.csv", "r");
$bezirke_ref=array();
while($x=fgetcsv($f, null, ";")) {
  if($x[0]==0)
    continue;

  $is_statuar=false;
  $name=utf8_encode($x[3]);
  if(preg_match("/^(.*)\(Stadt\)/", $name, $m)) {
    $name=$m[1];
    $is_statuar=true;
  }

  $bezirke_ref[]=array(
    "sa_state"	=>utf8_encode($x[1]),
    "sa_ref"	=>$x[4],
    "sa_name"	=>$name,
    "sa_is_statuar"=>$is_statuar,
  );
}

$cols=array("sa_name"=>"Name", "sa_state"=>"Bundesland", "sa_ref"=>"Referenz", "sa_is_statuar"=>"Statuarstadt");

print "<table>\n";

foreach($cols as $col=>$title) {
  print "    <th class='{$col}'>{$title}</td>\n";
}

foreach($bezirke_ref as $bezirk_ref) {
  print "  <tr>\n";
  foreach($cols as $col=>$title) {
    print "    <td class='{$col}'>{$bezirk_ref[$col]}</td>\n";
  }
  print "  </tr>\n";
}
print "</table>\n";
