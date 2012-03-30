<?
ini_set('memory_limit', '256M');
Header("content-type: text/html; charset=utf-8");
include "../../conf.php";
include "../inc/hooks.php";
include "../inc/sql.php";
function debug($text) { }

if(isset($_COOKIE['check-at-tags']))
  $tags=json_decode($_COOKIE['check-at-tags'], true);
else
  $tags=array("population", "wikipedia:de");

if($_REQUEST['add_tag']) {
  $tags[]=$_REQUEST['add_tag'];
}
if($_REQUEST['del_tag']) {
  $tmp=array_combine($tags, $tags);
  unset($tmp[$_REQUEST['del_tag']]);
  $tags=array_keys($tmp);
}

setcookie('check-at-tags', json_encode($tags));

?>
<html>
<head>
<link href='style.css' rel='stylesheet' type='text/css' />
</head>
<body>
<?
$list_nodes=unserialize(file_get_contents("nodes.ser"));
$list_boundaries=unserialize(file_get_contents("boundaries.ser"));
$list_sa=unserialize(file_get_contents("sa.ser"));

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
foreach($list_boundaries as $elem) {
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
  print "<i>Achtung! Es kann sein, dass das Objekte fehlen, weil sie vom OpenStreetBrowser falsch interpretiert wurden. Im Zweifelsfall bitte nachfragen.</i>\n";
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
  if($_REQUEST['what']=='node')
    $fields=array_merge(array("OSM ID", "name", "ref:at:gkz", "ref:at:okz"), $tags);
  elseif($_REQUEST['what']=='boundary')
    $fields=array_merge(array("OSM ID", "name", "ref:at:gkz", "ref:at:okz"), $tags);
  else
    $fields=array("name", "ref:at:gkz", "ref:at:okz", "status", "plz");

  $link=".?what={$_REQUEST['what']}&value={$_REQUEST['value']}&del_tag=".urlencode($f);
  print "Tag hinzufügen: <form action='.' method='get'>";
  print "<input type='hidden' name='what' value='{$_REQUEST['what']}'>";
  print "<input type='hidden' name='value' value='{$_REQUEST['value']}'>";
  print "<input name='add_tag' value=''>";
  print "</form>\n";

  print "<table>\n";
  foreach($fields as $f) {
    print "    <th>$f";
    if(in_array($f, $tags)) {
      $link=".?what={$_REQUEST['what']}&value={$_REQUEST['value']}&del_tag=".urlencode($f);
      print " <a href='$link'>&#x2715;</a>";
    }
    print "</th>\n";
  }
  print "  </tr>\n";

  $l=$list[$_REQUEST['what']][$_REQUEST['value']];
  $ret=array();
  foreach($l as $elem) {
    $link="http://www.openstreetmap.org/browse/".strtr($elem['id'], array("_"=>"/", "rel"=>"relation"));
    $link_osb="http://www.openstreetbrowser.org/#{$elem['id']}";
    $ret1 ="  <tr>\n";
    foreach($fields as $f) {
      $text="";
      switch($f) {
        case "OSM ID":
	  $text="<a href='$link_osb'><img src='osb16.png'></a> <a href='$link'>{$elem['id']}</a>\n";
	  break;
	default:
	  $text=$elem[$f];
      }

      $ret1.="    <td>{$text}</td>\n";
    }
    $ret1.="  </tr>\n";
    $ret[$elem['name']][]=$ret1;
  }
  ksort($ret);
  foreach($ret as $r)
    print implode("\n", $r);

  print "</table>\n";
}
?>
</body>
</html>
