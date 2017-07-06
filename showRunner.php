<?php
$runnerId = (int)$_GET["runnerId"];
$sql = "SELECT cmp.name AS name,  org.name AS team, cmp.rt AS time, cmp.cls, cmp.bib FROM mopCompetitor cmp LEFT JOIN mopOrganization AS org ON cmp.org = org.id AND cmp.cid = org.cid ".
"WHERE cmp.cid='$cmpId' AND cmp.id='$runnerId'";
$res = $link->query($sql);
$r = $res->fetch_array();

$cls = $r["cls"];

if($r[bib]>0)
  print "<h3>$r[bib]. $r[name]</h3>\n";
else
  print "<h3>$r[name]</h3>\n";
print "<h4>$r[team]</h4>\n";

$finisht=$r['time'];

$sql = "SELECT cc.ctrl, c.name, mopRadio.rt AS time ".
"FROM mopClassControl cc ".
"LEFT JOIN mopRadio ON cc.ctrl=mopRadio.ctrl AND cc.cid = mopRadio.cid AND mopRadio.id = '$runnerId' ".
"LEFT JOIN mopControl c ON c.cid = cc.cid AND c.id = cc.ctrl ".
"WHERE cc.cid = '$cmpId' AND cc.id =  '$cls' ".
"ORDER BY cc.ord ";

$res = $link->query($sql);

$lengths = array(7.1, 13.5, 20.5, 29.6, 35.7, 45.7, 52.2, 57.0, 62.7, 64.0);

$out = array();

$count = 0;
$lastCtrl = -1;
while($r = $res->fetch_array()){
  $row = array();
  $row['name'] = $r['name'];

  if($r['time']){
    $t = $r['time']/10;
    $row['time'] = sprintf("%d:%02d:%02d", $t/3600, ($t/60)%60, $t%60);
      $k = $t + 3600*8;
      $row['clock'] = sprintf("%d:%02d:%02d", $k/3600, ($k/60)%60, $k%60);
    $sql = "SELECT COUNT(*)+1 as pl, MIN(mopRadio.rt) as time ".
      "FROM mopRadio LEFT JOIN mopCompetitor c ON c.cid=mopRadio.cid AND c.cls='$cls' AND c.id = mopRadio.id WHERE mopRadio.cid='$cmpId' AND mopRadio.ctrl='$r[ctrl]' AND mopRadio.rt < '$r[time]'";

    $res2 = $link->query($sql);
    $r2 = $res2->fetch_array();

    if($r2['pl']>1){
      $after = $t - $r2['time']/10;
      if ($after > 3600)
        $row['after'] = sprintf("+%d:%02d:%02d", $after/3600, ($after/60)%60, $after%60);
      elseif ($after > 0)
        $row['after'] = sprintf("+%d:%02d", ($after/60)%60, $after%60);
      else
        $row['after'] = "";
    } else $row['after'] = "";
    $row['pl']=$r2['pl'];
    $lastCtrl = $count;
    $row['t'] = $t;
    $row['est'] = false;
  }elseif($lastCtrl != -1){
    $t = $out[$lastCtrl]['t'] * $lengths[$count] / $lengths[$lastCtrl];
    $row['time'] = sprintf("%d:%02d:%02d", $t/3600, ($t/60)%60, $t%60);
    if($t > 3600*12)
      $row['clock'] = 'Över maxtid';
    else{
      $k = $t + 3600*8;
      $row['clock'] = sprintf("%d:%02d:%02d", $k/3600, ($k/60)%60, $k%60);
    }
    $row['pl'] = "";
    $row['after'] = "";
    $row['t'] = $t;
    $row['est'] = true;
  }
  $out[$count] = $row;
  $count++;
}



$row = array();
$row['name'] = 'Mål';

if($finisht){
  $t = $finisht/10;
  $row['time'] = sprintf("%d:%02d:%02d", $t/3600, ($t/60)%60, $t%60);
  $k = $t + 3600*8;
  $row['clock'] = sprintf("%d:%02d:%02d", $k/3600, ($k/60)%60, $k%60);
  $sql = "SELECT COUNT(*)+1 as pl, MIN(c.rt) as time ".
    "FROM mopCompetitor c WHERE c.cls='$cls' AND c.cid='$cmpId' AND c.rt < '$finisht' AND c.rt > 0";

  $res2 = $link->query($sql);
  $r2 = $res2->fetch_array();

  if($r2['pl']>1){
    $after = $t - $r2['time']/10;
    if ($after > 3600)
      $row['after'] = sprintf("+%d:%02d:%02d", $after/3600, ($after/60)%60, $after%60);
    elseif ($after > 0)
      $row['after'] = sprintf("+%d:%02d", ($after/60)%60, $after%60);
    else
      $row['after'] = "";
  } else $row['after'] = "";
  $row['pl']=$r2['pl'];
  $lastCtrl = $count;
  $row['t'] = $t;
  $row['est'] = false;
}elseif($lastCtrl != -1){
  $t = $out[$lastCtrl]['t'] * $lengths[$count] / $lengths[$lastCtrl];
  $row['time'] = sprintf("%d:%02d:%02d", $t/3600, ($t/60)%60, $t%60);
  if($t > 3600*12)
    $row['clock'] = 'Över maxtid';
  else{
    $k = $t + 3600*8;
    $row['clock'] = sprintf("%d:%02d:%02d", $k/3600, ($k/60)%60, $k%60);
  }
  $row['pl'] = "";
  $row['after'] = "";
  $row['t'] = $t;
  $row['est'] = true;
}
$out[$count] = $row;



/*
$estLengths = array(10,20,21.1,30,40,42.2,50,60);
$estCtrls = array(1,2,3,4,5,5,6,8);
$estNames = array("10 km", "20 km", "Halvmaraton", "30 km", "40 km", "Maraton", "50 km", "60 km");

$outEst = array();
for($count = 0; $count < count($estLengths) && isset($out[$estCtrls[$count]]) && !$out[$estCtrls[$count]]['est']; $count++){
  $row = array();
  $t = $out[$estCtrls[$count]-1]['t'];
  $tdif = $out[$estCtrls[$count]]['t'] - $t;
  $ddif = $lengths[$estCtrls[$count]]-$lengths[$estCtrls[$count]-1];
  $dratio = ($estLengths[$count]-$lengths[$estCtrls[$count]-1])/$ddif;
  $t = $t + $tdif * $dratio;
  $row['name'] = $estNames[$count];
  $row['time'] = sprintf("%d:%02d:%02d", $t/3600, ($t/60)%60, $t%60);
  $outEst[$count] = $row;
}*/

echo '<table class="table table-striped"><thead><tr><th>Kontroll</th><th>Tid</th><th></th><th>Placering</th><th>Klockslag</th></tr></thead>';
foreach ($out as $key => $value) {
  if($value['est'])
    echo '<tr class="warning" style="font-style: italic;"><td>'.$value['name'].' (Beräknad)</td>';
  else echo '<tr><td>'.$value['name'].'</td>';
  echo '<td>'.$value['time'].'</td><td>'.$value['after'].'</td><td>'.$value['pl'].'</td><td>'.$value['clock'].'</td></tr>';
}
echo '</table>';
/*if(!empty($outEst)){
  echo '<h4>Uppskattade milpasseringar m.m.</h4>';
  echo '<table class="table table-striped"><thead><tr><th>Kontroll</th><th>Tid</th></tr></thead>';
  foreach ($outEst as $key => $value) {
    echo '<tr><td>'.$value['name'].'</td><td>'.$value['time'].'</td></tr>';
  }
  echo '</table>';
}*/
