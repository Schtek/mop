<?php
  /*
  Copyright 2013 Melin Software HB

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
  */

	include_once('functions.php');
	session_start();
  header('Content-type: text/html;charset=utf-8');

  $PHP_SELF = $_SERVER['PHP_SELF'];
	ConnectToDB();

  if (isset($_GET['cmp']))
    $_SESSION['competition'] = 1 * (int)$_GET['cmp'];

  $cmpId = $_SESSION['competition'];

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Silverleden Online Results</title>

<style type="text/css">
body {
  font-family: verdana, arial, sans-serif;
  font-size: 9pt;
  background-color: #FFFFFF;
}

a.button {
  border-style: ridge;
  border-color: #b0c4de;
  background-color:#b0c4de;
  color: #900000;
  text-decoration: none;
  padding: 0.1em 0.3em;
  margin: 1em;
}

h1 {text-shadow: 3px 3px 3px #AAAAAA;}
th {text-align:left;}
td {padding-right:1em;}
</style>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

</head>
<body>
<div class="container">
<?php
  if ($_GET['select'] == 1 || $cmpId == 0) {
    print "<h1>$lang[selectcmp]</h1>";
    $sql = "SELECT name, date, cid FROM mopCompetition ORDER BY date DESC";
    $res = $link->query($sql);

    while ($r = $res->fetch_array()) {
      print '<a href="'."$PHP_SELF?cmp=$r[cid]".'">'."$r[name] ($r[date])</a><br/>\n";
    }
    die('</body></html>');
  }

  $sql = "SELECT * FROM mopCompetition WHERE cid = '$cmpId'";
  $res = $link->query($sql);

  if ($r = $res->fetch_array()) {
    print "<h1>$r[name] &ndash; $r[date]</h1>\n";

    /*if (strlen($r['organizer']) > 0) {
      if (strlen($r['homepage'])>0)
        print '<a href="'.$r['homepage'].'">'.$r['organizer'].'</a><br>';
      else
        print $r['organizer'].'<br>';
    }*/
  }

  //print '<br><div style="clear:both;"><a href="'.$PHP_SELF.'?select=1" class="button">'.$lang['selectcmp'].'</a></div>';

  print '<ul class="nav nav-pills">';
  $sql = "SELECT name, id FROM mopClass WHERE cid = '$cmpId' ORDER BY ord";
  $res = $link->query($sql);
  if (isset($_GET['cls'])) $cls = (int)$_GET['cls'];

  while ($r = $res->fetch_array()) {
    print '<li role="presentation" ';
    if($r[id] == $cls) print ' class="active"';
    print '><a href="'."$PHP_SELF?cls=$r[id]".'">'.$r['name']."</a></li>\n";
  }

  print '</ul>';

  if (isset($_GET['cls'])) {
    $cls = (int)$_GET['cls'];
    $sql = "SELECT name FROM mopClass WHERE cid='$cmpId' AND id='$cls'";
    $res = $link->query($sql);
    $cinfo = $res->fetch_array();
    $cname = $cinfo['name'];

    $sql = "SELECT max(leg) FROM mopTeamMember tm, mopTeam t WHERE tm.cid = '$cmpId' AND t.cid = '$cmpId' AND tm.id = t.id AND t.cls = $cls";
    $res = $link->query($sql);
    $r = $res->fetch_array();
    $numlegs = $r[0];
    print "<h2>$cname</h2>\n";

    if ($numlegs > 1) {
      //Multiple legs, relay etc.
      if (isset($_GET['leg'])) {
        $leg = (int)$_GET['leg'];
      }
      if (isset($_GET['ord'])) {
        $ord = (int)$_GET['ord'];
      }
      if (isset($_GET['radio'])) {
        $radio = $_GET['radio'];
      }
      for ($k = 1; $k <= $numlegs; $k++) {
        $sql = "SELECT max(ord) FROM mopTeamMember tm, mopTeam t WHERE t.cls = '$cls' AND tm.leg=$k AND ".
                "tm.cid = '$cmpId' AND t.cid = '$cmpId' AND tm.id = t.id";
        $res = $link->query($sql);
        $r = $res->fetch_array();
        $numparallel = $r[0];

        if ($numparallel == 0) {
          print "$k: ";
          selectLegRadio($cls, $k, 0);
        }
      }

      if ($radio!='') {
        if ($radio == 'finish') {
          $sql = "SELECT t.id AS id, cmp.name AS name, t.name AS team, cmp.rt AS time, cmp.stat AS status, ".
                 "cmp.it+cmp.rt AS tottime, cmp.tstat AS totstat ".
                 "FROM mopTeamMember tm, mopCompetitor cmp, mopTeam t ".
                 "WHERE t.cls = '$cls' AND t.id = tm.id AND tm.rid = cmp.id ".
                 "AND t.cid = '$cmpId' AND tm.cid = '$cmpId' AND cmp.cid = '$cmpId' AND t.stat>0 ".
                 "AND tm.leg='$leg' AND tm.ord='$ord' ORDER BY cmp.stat, cmp.rt ASC, t.id";
          $rname = $lang["finish"];
        }
        else {
          $rid = (int)$radio;
          $sql = "SELECT name FROM mopControl WHERE cid='$cmpId' AND id='$rid'";
          $res = $link->query($sql);
          $rinfo = $res->fetch_array();
          $rname = $rinfo['name'];

          $sql = "SELECT team.id AS id, cmp.name AS name, team.name AS team, radio.rt AS time, 1 AS status, ".
                   "cmp.it+radio.rt AS tottime, cmp.tstat AS totstat ".
                   "FROM mopRadio AS radio, mopTeamMember AS m, mopTeam AS team, mopCompetitor AS cmp ".
                   "WHERE radio.ctrl='$rid' ".
                   "AND radio.id=cmp.id ".
                   "AND m.rid = radio.id ".
                   "AND m.id = team.id ".
                   "AND cmp.stat<=1 ".
                   "AND m.leg='$leg' AND m.ord='$ord' ".
                   "AND cmp.cls='$cls' ".
                   "AND team.cid = '$cmpId' AND m.cid = '$cmpId' AND cmp.cid = '$cmpId' ".
                   "ORDER BY radio.rt ASC ";
        }

        $res = $link->query($sql);
        $results = calculateResult($res);
        print "<h3>Leg $leg, $rname</h3>\n";
        formatResult($results);
      }
    }
    else {

      if (is_null($numlegs)) {
        //No teams;
        $radio = selectRadio($cls);
        if ($radio!='') {
          if ($radio == 'finish') {
            /*$sql = "SELECT cmp.id AS id, cmp.name AS name, org.name AS team, cmp.rt AS time, cmp.stat AS status ".
                   "FROM mopCompetitor cmp LEFT JOIN mopOrganization AS org ON cmp.org = org.id AND cmp.cid = org.cid ".
                   "WHERE cmp.cls = '$cls' ".
                   "AND cmp.cid = '$cmpId' AND cmp.stat>0 ORDER BY cmp.stat, cmp.rt ASC, cmp.id";*/
            $rname = "Totalställning";
            $sql = " SELECT cmp.id AS id, cmp.name AS name, org.name AS team, IF(cmp.rt>0, cmp.rt, mopRadioMax.rt) AS time, IF(cmp.rt>0, '".$lang['finish']."', mopControl.name) AS ctrlName, IF(cmp.rt>0, 999, cc.ord) AS sortOrd, cmp.stat AS status ".
             "FROM mopCompetitor cmp LEFT JOIN mopOrganization AS org ON cmp.org = org.id AND cmp.cid = org.cid ".
             "INNER JOIN (SELECT id, MAX(rt) as rt FROM mopRadio WHERE cid=1 GROUP BY id) mopRadioMax ON cmp.id=mopRadioMax.id ".
             "INNER JOIN mopRadio ON cmp.id = mopRadio.id AND mopRadio.rt = mopRadioMax.rt AND mopRadio.cid=cmp.cid ".
             "INNER JOIN mopControl ON mopRadio.ctrl = mopControl.id AND mopControl.cid=cmp.cid ".
             "LEFT JOIN mopClassControl cc ON cc.cid=cmp.cid AND cc.id=cmp.cls AND mopRadio.ctrl=cc.ctrl ".
             "WHERE cmp.cid='$cmpId' AND cmp.cls = '$cls' ".
             "ORDER BY sortOrd DESC, time";
          }
          else {
            $rid = (int)$radio;
            $sql = "SELECT name FROM mopControl WHERE cid='$cmpId' AND id='$rid'";
            $res = $link->query($sql);
            $rinfo = $res->fetch_array();
            $rname = $rinfo['name'];

            $sql = "SELECT cmp.id AS id, cmp.name AS name, org.name AS team, radio.rt AS time, 1 AS status ".
                   "FROM mopRadio AS radio, mopCompetitor AS cmp ".
                   "LEFT JOIN mopOrganization AS org ON cmp.org = org.id AND cmp.cid = org.cid ".
                   "WHERE radio.ctrl='$rid' ".
                   "AND radio.id=cmp.id ".
                   //"AND cmp.stat<=1 ".
                   "AND cmp.cls='$cls' ".
                   "AND cmp.cid = '$cmpId' AND radio.cid = '$cmpId' ".
                   "ORDER BY radio.rt ASC ";
          }
          $res = $link->query($sql);
          $results = calculateResult($res);
          print "<h3>$rname</h3>\n";
          formatResult($results);
        }
      }
      else {
        // Single leg (patrol etc)
        $radio = selectRadio($cls);

       if ($radio!='') {
         if ($radio == 'finish') {
             $sql = "SELECT t.id AS id, cmp.name AS name, t.name AS team, t.rt AS time, t.stat AS status ".
                    "FROM mopTeamMember tm, mopCompetitor cmp, mopTeam t ".
                    "WHERE t.cls = '$cls' AND t.id = tm.id AND tm.rid = cmp.id AND tm.leg=1 ".
                    "AND t.cid = '$cmpId' AND tm.cid = '$cmpId' AND cmp.cid = '$cmpId' AND t.stat>0 ORDER BY t.stat, t.rt ASC, t.id";
             $rname = "Totalställning";
           }
         else {
           $rid = (int)$radio;
           $sql = "SELECT name FROM mopControl WHERE cid='$cmpId' AND id='$rid'";
           $res = $link->query($sql);
           $rinfo = $res->fetch_array();
           $rname = $rinfo['name'];

           $sql = "SELECT team.id AS id, cmp.name AS name, team.name AS team, radio.rt AS time, 1 AS status ".
                   "FROM mopRadio AS radio, mopTeamMember AS m, mopTeam AS team, mopCompetitor AS cmp ".
                   "WHERE radio.ctrl='$rid' ".
                   "AND radio.id=cmp.id ".
                   "AND m.rid = radio.id ".
                   "AND m.id = team.id ".
                   "AND cmp.stat<=1 ".
                   "AND m.leg=1 ".
                   "AND cmp.cls='$cls' ".
                   "AND radio.cid = '$cmpId' AND m.cid = '$cmpId' AND team.cid = '$cmpId' AND cmp.cid = '$cmpId' ".
                   "ORDER BY radio.rt ASC ";
         }

         $res = $link->query($sql);
         $results = calculateResult($res);
         print "<h3>$rname</h3>\n";
         formatResult($results);
        }
      }
    }
  }elseif (isset($_GET["runnerId"])) {
    include("showRunner.php");
  }
?>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</body></html>
