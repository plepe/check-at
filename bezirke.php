<?
// prefixes:
// sa_  Statistik Austria
include "../../conf.php";
include "../inc/hooks.php";
include "../inc/sql.php";
function debug($text) { }

$list=array();
$f=fopen("http://www.statistik.at/web_de/static/politische_bezirke_csv_ca._5kb_022948.csv", "r");
while($x=fgetcsv($f, null, ";")) {
  if($x[0]==0)
    continue;

  $is_statuar=false;
  $name=utf8_encode($x[3]);
  if(preg_match("/^(.*)\(Stadt\)/", $name, $m)) {
    $name=$m[1];
    $is_statuar=true;
  }

  $bezirke_ref_idx[$name]=sizeof($bezirke_ref);
  $bezirke_ref[]=array(
    "ref:at:gkz"=>$x[4],
    "name"	=>$name,
    "is_statuar"=>$is_statuar,
  );
}

$res=sql_query("select osm_id, osm_tags from osm_point_extract join (select load_geo('rel_16239') as boundary offset 0) x on x.boundary && osm_way and CollectionIntersects(x.boundary, osm_way) and (osm_tags @> 'place=>city' or osm_tags @> 'place=>region' or osm_tags @> 'place=>town')");
while($elem=pg_fetch_assoc($res)) {
  $x=parse_hstore($elem['osm_tags']);
  $x['id']=$elem['osm_id'];

  $bezirke_osmn_idx[$x['name']]=sizeof($bezirke_osmn);
  $bezirke_osmn[]=$x;
}

$list=array_unique(array_merge(array_keys($bezirke_ref_idx), array_keys($bezirke_osmn_idx)));

$ref_cols=array("sa_name"=>"Name", "sa_state"=>"Bundesland", "sa_ref"=>"Referenz", "sa_is_statuar"=>"Statuarstadt");
$osmn_cols=array("name"=>"Name", "place"=>"place=", "ref"=>"ref=", "population"=>"population=");

print "<table>\n";

print "  <tr>\n";
foreach($ref_cols as $col=>$title) {
  print "    <th class='{$col}'>{$title}</td>\n";
}
foreach($osmn_cols as $col=>$title) {
  print "    <th class='{$col}'>{$title}</td>\n";
}
print "  </tr>\n";

foreach($list as $bezirk_name) {
  print "  <tr>\n";
  $bezirk_ref=$bezirke_ref[$bezirke_ref_idx[$bezirk_name]];
  foreach($ref_cols as $col=>$title) {
    print "    <td class='{$col}'>{$bezirk_ref[$col]}</td>\n";
  }

  $bezirk_osmn=$bezirke_osmn[$bezirke_osmn_idx[$bezirk_name]];
  foreach($osmn_cols as $col=>$title) {
    print "    <td class='{$col}'>{$bezirk_osmn[$col]}</td>\n";
  }
  print "  </tr>\n";
}
print "</table>\n";
