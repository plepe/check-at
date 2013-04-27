#!/usr/bin/php
<?
include "../../conf.php";
include "../inc/hooks.php";
include "../inc/sql.php";
function debug($text) { }

$list_nodes=array();
$date=Date("Ymd");
print "Nodes ...\n";
$res=sql_query("select osm_id, osm_tags from osm_point join (select load_geo('rel_16239') as boundary offset 0) x on x.boundary && osm_way and CollectionIntersects(x.boundary, osm_way) and osm_tags ? 'place'");
while($elem=pg_fetch_assoc($res)) {
  $x=parse_hstore($elem['osm_tags']);
  $x['id']=$elem['osm_id'];
  $stats['node'][$x['place']]++;

  $list_nodes[]=$x;
}

print "Boundaries ...\n";
$list_boundaries=array();
$res=sql_query("select osm_id, osm_tags from osm_polygon_extract join (select load_geo('rel_16239') as boundary offset 0) x on x.boundary && osm_way and CollectionWithin(osm_way, x.boundary) and osm_tags @> 'boundary=>administrative'");
while($elem=pg_fetch_assoc($res)) {
  $x=parse_hstore($elem['osm_tags']);
  $x['id']=$elem['osm_id'];
  if(!isset($x['admin_level']))
    $x['admin_level']="";
  $stats['boundary'][$x['admin_level']]++;

  $list_boundaries[]=$x;
}

print "Writing files ...\n";
file_put_contents("nodes.ser", serialize($list_nodes));
file_put_contents("boundaries.ser", serialize($list_boundaries));
file_put_contents("stats-$date.ser", serialize($stats));
