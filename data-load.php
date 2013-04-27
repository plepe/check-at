#!/usr/bin/php
<?
include "../conf.php";
include "../modules/base/modules/hooks/hooks.php";
include "../modules/base/modules/pg_sql/sql.php";
function debug($text) { }

$list_nodes=array();
$date=Date("Ymd");
print "Nodes ...\n";
$res=sql_query("select id, tags from osm_point(load_geo('R16239'), $\$tags ? 'place'$$) where CollectionIntersects((select load_geo('R16239') offset 0), way)");
while($elem=pg_fetch_assoc($res)) {
  $x=parse_hstore($elem['tags']);
  $x['id']=$elem['id'];
  $stats['node'][$x['place']]++;

  $list_nodes[]=$x;
}

print "Boundaries ...\n";
$list_boundaries=array();
$res=sql_query("select id, tags from osm_polygon(load_geo('R16239'), $\$tags @> 'boundary=>administrative'$$) where CollectionIntersects((select load_geo('R16239') offset 0), way)");
while($elem=pg_fetch_assoc($res)) {
  $x=parse_hstore($elem['tags']);
  $x['id']=$elem['id'];
  if(!isset($x['admin_level']))
    $x['admin_level']="";
  $stats['boundary'][$x['admin_level']]++;

  $list_boundaries[]=$x;
}

print "Writing files ...\n";
file_put_contents("nodes.ser", serialize($list_nodes));
file_put_contents("boundaries.ser", serialize($list_boundaries));
file_put_contents("stats-$date.ser", serialize($stats));
