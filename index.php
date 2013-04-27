<?
ini_set('memory_limit', '256M');
Header("content-type: text/html; charset=utf-8");
include "../../conf.php";
include "../inc/hooks.php";
include "../inc/sql.php";
include "functions.php";
function debug($text) { }

function osm_link($id) {
  $object_types=array('N'=>'node', 'W'=>'way', 'R'=>'relation');
  return "http://www.openstreetmap.org/browse/".$object_types[substr($id, 0, 1)]."/".substr($id, 1);
}

function coalesce() {
  foreach(func_get_args() as $arg)
    if($arg)
      return $arg;
}

if(isset($_COOKIE['check-at-conf']))
  $conf=json_decode($_COOKIE['check-at-conf'], true);
else
  $conf=array("sort"=>"name", "tags"=>array("population", "wikipedia:de"));

if($_REQUEST['add_tag']) {
  $conf['tags'][]=$_REQUEST['add_tag'];
}
if($_REQUEST['del_tag']) {
  $tmp=array_combine($tags, $conf['tags']);
  unset($tmp[$_REQUEST['del_tag']]);
  $conf['tags']=array_keys($tmp);
}
if($_REQUEST['sort'])
  $conf['sort']=$_REQUEST['sort'];

setcookie('check-at-conf', json_encode($conf));

?>
<html>
<head>
<link href='style.css' rel='stylesheet' type='text/css' />
</head>
<body>
<?
$list_nodes=unserialize(file_get_contents("nodes.ser"));
$list_boundaries=unserialize(file_get_contents("boundaries.ser"));
$general=unserialize(file_get_contents("general.ser"));
$list_sa=unserialize(file_get_contents("sa.ser"));

if(!isset($_REQUEST['what'])) {
  $title="OpenStreetMap, Checker für Österreich";
}
elseif($_REQUEST['what']=="node") {
  $title="Place Nodes, place={$_REQUEST['value']}";
}
elseif($_REQUEST['what']=="boundary") {
  $title="Boundary Relations, admin_level={$_REQUEST['value']}";
}
elseif($_REQUEST['what']=="sa") {
  $title="Statistik Austria, {$_REQUEST['value']}";
}
print "<h1>$title</h1>\n";
print "Stand: ".$general['timestamp']."<br>\n";
if(isset($_REQUEST['what']))
  print "<a href='.'>Zurück zur Übersicht</a><br>\n";

if(($_REQUEST['what']=="sa")&&($_REQUEST['value']=="bezirk")) {
  $check=array(array("6"), "ref:at:gkz");
}
if(($_REQUEST['what']=="sa")&&($_REQUEST['value']=="gemeinde")) {
  $check=array(array("8"), "ref:at:gkz");
}
if(($_REQUEST['what']=="sa")&&($_REQUEST['value']=="ortschaft")) {
  $check=array(array("8", "9", "10", "11"), "ref:at:okz");
}

if(isset($check)) {
  $check_list=array();
  foreach($list_boundaries as $ob) {
    if(in_array($ob['admin_level'], $check[0]))
      $check_list[$ob[$check[1]]][]=$ob;
  }
}

$list=array(
  'node'=>array(
    'country'=>array(),
    'state'=>array(),
    'city'=>array(),
    'region'=>array(),
    'town'=>array(),
    'village'=>array(),
    'hamlet'=>array(),
  ),
  'sa'=>array(
    'bezirk'=>array(),
  ),
);
foreach($list_nodes as $elem) {
  $list['node'][$elem['place']][]=$elem;
}
foreach($list_boundaries as $i=>$elem) {
  if(!isset($elem['admin_level']))
    $elem['admin_level']="";
  $list['boundary'][$elem['admin_level']][]=$elem;
}
$list['sa']=$list_sa;

if(!$_REQUEST['what']) {
  print "<h2>Nodes</h2>\n";
  print "<ul>\n";
  foreach($list['node'] as $place=>$l) {
    print "  <li><a href='?what=node&value=$place'>place=$place</a> (".sizeof($l).")</li>\n";
  }
  print "</ul>\n";

  print "<h2>Boundaries</h2>";
  print "<i>Achtung! Es kann sein, dass Objekte fehlen, weil sie vom OpenStreetBrowser falsch interpretiert wurden. Im Zweifelsfall bitte nachfragen.</i>\n";
  print "<ul>\n";
  ksort($list['boundary']);
  foreach($list['boundary'] as $lev=>$l) {
    print "  <li><a href='?what=boundary&value=$lev'>admin_level=$lev (".sizeof($l).")</a></li>\n";
  }
  print "</ul>\n";

  print "<h2>Statistik Austria</h2>";
  print "<ul>\n";
  foreach($list['sa'] as $lev=>$l) {
    print "  <li><a href='?what=sa&value=$lev'>$lev (".sizeof($l).")</li>\n";
  }
  print "</ul>\n";
}
else {
  if(!$conf['tags'])
    $conf['tags']=array();

  if($_REQUEST['what']=='node')
    $fields=array_merge(array("OSM ID", "name", "ref:at:gkz", "ref:at:okz"), $conf['tags']);
  elseif($_REQUEST['what']=='boundary')
    $fields=array_merge(array("OSM ID", "name", "ref:at:gkz", "ref:at:okz"), $conf['tags']);
  else {
    $fields=array("name", "ref:at:gkz", "ref:at:okz", "status", "plz");
  }

  if(isset($check)) {
    $fields[]="check";
    $fields_desc['check']="Check OSM (via {$check[1]})";
  }

  if($_REQUEST['what']!="sa") {
    $link=".?what={$_REQUEST['what']}&value={$_REQUEST['value']}&del_tag=".urlencode($f);
    print "Tag hinzufügen: <form action='.' method='get'>";
    print "<input type='hidden' name='what' value='{$_REQUEST['what']}'>";
    print "<input type='hidden' name='value' value='{$_REQUEST['value']}'>";
    print "<input name='add_tag' value=''>";
    print "</form>\n";
  }

  print "<table>\n";
  foreach($fields as $f) {
    print "    <th>";
    if(isset($fields_desc[$f]))
      print $fields_desc[$f];
    else
      print $f;
    if($conf['sort']==$f) {
      print "<b>&darr;</b>";
    }
    else {
      $link=".?what={$_REQUEST['what']}&value={$_REQUEST['value']}&sort=".urlencode($f);
      print " <a href='$link'>&darr;</a>";
    }
    if(in_array($f, $conf['tags'])) {
      $link=".?what={$_REQUEST['what']}&value={$_REQUEST['value']}&del_tag=".urlencode($f);
      print " <a href='$link'>&#x2715;</a>";
    }
    print "</th>\n";
  }
  print "  </tr>\n";

  $l=$list[$_REQUEST['what']][$_REQUEST['value']];
  $ret=array();
  foreach($l as $elem) {
    $link=osm_link($elem['id']);
    $link_osb="http://www.openstreetbrowser.org/#{$elem['id']}";
    $ret1 ="  <tr>\n";
    foreach($fields as $f) {
      $text="";
      switch($f) {
        case "OSM ID":
	  $text="<a href='$link_osb'><img src='osb16.png'></a> <a href='$link'>{$elem['id']}</a>\n";
	  break;
        case "check":
          $check_id=$elem[$check[1]];
          if($check_id&&isset($check_list[$check_id])) {
            $check_ob=$check_list[$check_id];
            foreach($check_ob as $check_ob1)
              $text="<a href='".osm_link($check_ob1['id'])."'>".coalesce($check_ob1['name'], "unnamed")."</a><br>";
          }
            //$text=print_r($check_list[$elem[$check[1]], 1);
          break;
	default:
	  $text=$elem[$f];
      }

      $ret1.="    <td>{$text}</td>\n";
    }
    $ret1.="  </tr>\n";

    $sort_value=$elem['name'];
    if($conf['sort']=="OSM ID")
      $sort_value=$elem['id'];
    elseif($conf['sort']=="name")
      $sort_value=$elem['name'];
    elseif($conf['sort'])
      $sort_value="{$elem[$conf['sort']]}-{$elem['name']}";

    $ret[$sort_value][]=$ret1;
  }
  $ret=keyNatSort($ret, true);
  foreach($ret as $r)
    print implode("\n", $r);

  print "</table>\n";
}
?>
</body>
</html>
