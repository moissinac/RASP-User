<html>
<?php
header('Content-type: text/html; charset=utf-8');
?>
<body>
<?php
function direction($u, $v)
{
$dirString = array(
     0 => "N",
     1 => "NNE",
     2 => "NNE",
     3 => "NE",
     4 => "NE",
     5 => "ENE",
     6 => "ENE",
     7 => "E",
     8 => "E",
     9 => "ESE",
    10 => "ESE",
    11 => "SE",
    12 => "SE",
    13 => "SSE",
    14 => "SSE",
    15 => "S",
    16 => "S",
    17 => "SSO",
    18 => "SSO",
    19 => "SO",
    20 => "SO",
    21 => "OSO",
    22 => "OSO",
    23 => "O",
    24 => "O",
    25 => "ONO",
    26 => "ONO",
    27 => "NO",
    28 => "NO",
    29 => "NNO",
    30 => "NNO",
    31 => "N",
    32 => "N"
);
  $pi = 3.14159;  
  $dir = (atan2($u, $v)/$pi + 1)*180;
  $dirSecteur = round($dir/11.25);
  print $dirString[$dirSecteur];
}

function vitesse($u, $v)
{
  print round(sqrt($u*$u+$v*$v)*3.6);
}
?>
<?php

$request = 'http://data2.rasp-france.org/status.php';
$response  = file_get_contents($request);
$jsonstatus  = json_decode($response, true);
foreach ( $jsonstatus['france'] as $run )
{
  if ($run['status']=='complete')
    break;
}
echo 'Previsions <a href="http://rasp-france.org/" title="Site de previsions meteo pour parapente">RASP-France</a> n=' . $run['run'] . ' du ' . $run['day'] . '<br/>';

// echo 'Prévision n°' . $jsonstatus['france'][0]['run'] . ' du ' . $jsonstatus['france'][0]['day'] . '<br/>';
$lat = $_GET['lat'];
$lon = $_GET['lon'];
//$place = '49.93,1.06';
$place = '' . $lat . ','. $lon;
//echo $place;
$request = 'http://data2.rasp-france.org/json.php?domain=france&run=' . $run['run'] . '&places=' . $place . '&dates=' . $run['day'] .'&heures=8-18&' .'params=umet;vmet;pbltop';
//echo $request . '<br/>';
$response  = file_get_contents($request);
$jsondata  = json_decode($response, true);
//var_dump($jsonobj);
?>
<table border="1">
<tr>
<td>UTC+2</td>
<td>10h</td>
<td>11h</td>
<td>12h</td>
<td>13h</td>
<td>14h</td>
<td>15h</td>
<td>16h</td>
<td>17h</td>
<td>18h</td>
<td>19h</td>
<td>20h</td>
</tr>
<tr>
<td>Plafond couche convective</td>
<td><?php
echo round($jsondata[$place][$run['day']]['8']['pbltop']);
?></td>
<td><?php
echo round($jsondata[$place][$run['day']]['9']['pbltop']);
?></td>
<td><?php
echo round($jsondata[$place][$run['day']]['10']['pbltop']);
?></td>
<td><?php
echo round($jsondata[$place][$run['day']]['11']['pbltop']);
?></td>
<td><?php
echo round($jsondata[$place][$run['day']]['12']['pbltop']);
?></td>
<td><?php
echo round($jsondata[$place][$run['day']]['13']['pbltop']);
?></td>
<td><?php
echo round($jsondata[$place][$run['day']]['14']['pbltop']);
?></td>
<td><?php
echo round($jsondata[$place][$run['day']]['15']['pbltop']);
?></td>
<td><?php
echo round($jsondata[$place][$run['day']]['16']['pbltop']);
?></td>
<td><?php
echo round($jsondata[$place][$run['day']]['17']['pbltop']);
?></td>
<td><?php
echo round($jsondata[$place][$run['day']]['18']['pbltop']);
?></td>
</tr>
<tr>
<td>Direction du vent</td>
<td><?php direction($jsondata[$place][$run['day']]['8']['umet'][0], $jsondata[$place][$run['day']]['8']['vmet'][0]); ?></td>
<td><?php direction($jsondata[$place][$run['day']]['9']['umet'][0], $jsondata[$place][$run['day']]['9']['vmet'][0]); ?></td>
<td><?php direction($jsondata[$place][$run['day']]['10']['umet'][0], $jsondata[$place][$run['day']]['10']['vmet'][0]); ?></td>
<td><?php direction($jsondata[$place][$run['day']]['11']['umet'][0], $jsondata[$place][$run['day']]['11']['vmet'][0]); ?></td>
<td><?php direction($jsondata[$place][$run['day']]['12']['umet'][0], $jsondata[$place][$run['day']]['12']['vmet'][0]); ?></td>
<td><?php direction($jsondata[$place][$run['day']]['13']['umet'][0], $jsondata[$place][$run['day']]['13']['vmet'][0]); ?></td>
<td><?php direction($jsondata[$place][$run['day']]['14']['umet'][0], $jsondata[$place][$run['day']]['14']['vmet'][0]); ?></td>
<td><?php direction($jsondata[$place][$run['day']]['15']['umet'][0], $jsondata[$place][$run['day']]['15']['vmet'][0]); ?></td>
<td><?php direction($jsondata[$place][$run['day']]['16']['umet'][0], $jsondata[$place][$run['day']]['16']['vmet'][0]); ?></td>
<td><?php direction($jsondata[$place][$run['day']]['17']['umet'][0], $jsondata[$place][$run['day']]['17']['vmet'][0]); ?></td>
<td><?php direction($jsondata[$place][$run['day']]['18']['umet'][0], $jsondata[$place][$run['day']]['18']['vmet'][0]); ?></td>
</tr>
<tr>
<td>Vitesse du vent (km/h)</td>
<td><?php vitesse($jsondata[$place][$run['day']]['8']['umet'][0], $jsondata[$place][$run['day']]['8']['vmet'][0]); ?></td>
<td><?php vitesse($jsondata[$place][$run['day']]['9']['umet'][0], $jsondata[$place][$run['day']]['9']['vmet'][0]); ?></td>
<td><?php vitesse($jsondata[$place][$run['day']]['10']['umet'][0], $jsondata[$place][$run['day']]['10']['vmet'][0]); ?></td>
<td><?php vitesse($jsondata[$place][$run['day']]['11']['umet'][0], $jsondata[$place][$run['day']]['11']['vmet'][0]); ?></td>
<td><?php vitesse($jsondata[$place][$run['day']]['12']['umet'][0], $jsondata[$place][$run['day']]['12']['vmet'][0]); ?></td>
<td><?php vitesse($jsondata[$place][$run['day']]['13']['umet'][0], $jsondata[$place][$run['day']]['13']['vmet'][0]); ?></td>
<td><?php vitesse($jsondata[$place][$run['day']]['14']['umet'][0], $jsondata[$place][$run['day']]['14']['vmet'][0]); ?></td>
<td><?php vitesse($jsondata[$place][$run['day']]['15']['umet'][0], $jsondata[$place][$run['day']]['15']['vmet'][0]); ?></td>
<td><?php vitesse($jsondata[$place][$run['day']]['16']['umet'][0], $jsondata[$place][$run['day']]['16']['vmet'][0]); ?></td>
<td><?php vitesse($jsondata[$place][$run['day']]['17']['umet'][0], $jsondata[$place][$run['day']]['17']['vmet'][0]); ?></td>
<td><?php vitesse($jsondata[$place][$run['day']]['18']['umet'][0], $jsondata[$place][$run['day']]['18']['vmet'][0]); ?></td>
</tr>
</table>

</body>
</html>