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

print '

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
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

</head><body>';
print '<div class="container-fluid">';


    $cls = (int)$_GET['cls'];
    $sql = "SELECT name FROM mopClass WHERE cid='$cmpId' AND id='$cls'";
    $res = $link->query($sql);
    $cinfo = $res->fetch_array();
    $cname = $cinfo['name'];

    $sql = "SELECT max(leg) FROM mopTeamMember tm, mopTeam t WHERE tm.cid = '$cmpId' AND t.cid = '$cmpId' AND tm.id = t.id AND t.cls = $cls";
    $res = $link->query($sql);
    $r = $res->fetch_array();
    $numlegs = $r[0];
    print "<h2>$cname - Totalställning</h2>\n";
            $sql = " SELECT cmp.id AS id, cmp.name AS name, org.name AS team, IF(cmp.rt>0, cmp.rt, mopRadioMax.rt) AS time, IF(cmp.rt>0, '".$lang['finish']."', mopControl.name) AS ctrlName, IF(cmp.rt>0, 999, cc.ord) AS sortOrd, cmp.stat AS status ".
             "FROM mopCompetitor cmp LEFT JOIN mopOrganization AS org ON cmp.org = org.id AND cmp.cid = org.cid ".
             "INNER JOIN (SELECT id, MAX(rt) as rt FROM mopRadio WHERE cid=1 GROUP BY id) mopRadioMax ON cmp.id=mopRadioMax.id ".
             "INNER JOIN mopRadio ON cmp.id = mopRadio.id AND mopRadio.rt = mopRadioMax.rt AND mopRadio.cid=cmp.cid ".
             "INNER JOIN mopControl ON mopRadio.ctrl = mopControl.id AND mopControl.cid=cmp.cid ".
             "LEFT JOIN mopClassControl cc ON cc.cid=cmp.cid AND cc.id=cmp.cls AND mopRadio.ctrl=cc.ctrl ".
             "WHERE cmp.cid='$cmpId' AND cmp.cls = '$cls' ".
             "ORDER BY sortOrd DESC, time";
          $res = $link->query($sql);
          $results = calculateResult($res);
          formatResult($results);

print '</div>';
print '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>';
print '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>';
print '<script type="text/javascript">
$(document).ready(function(){
  $("body,html").delay(5000).animate({scrollTop: window.scrollMaxY}, window.scrollMaxY*20, "linear", function(){
    window.setTimeout(function(){ location.reload(true); } ,10000);
  });
});
</script>';
print '</body></html>';

?>
