<?php
// configuration ------------------------------
$utcStart = 7;
$utcStop = 18;

$fuseauHoraire = 2; // utc + 2

// fin configuration -----------------------------

// -- fonction utiles -------------------------------------

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
  $dir = (atan2($u, $v)/pi() + 1)*180;
  $dirSecteur = round($dir/11.25);
  return $dirString[$dirSecteur];
}

function vitesse($u, $v)
{
  return round(sqrt($u*$u+$v*$v)*3.6);
}

$request = 'http://data2.rasp-france.org/status.php';
$response  = file_get_contents($request);
$jsonstatus  = json_decode($response, true);
foreach ( $jsonstatus['france'] as $run )
{
  if ($run['status']=='complete')
    break;
}

// -- boulot -------------------------------------
$place = sprintf('%.4f,%.4f', $_GET['lat'], $_GET['lon']);

$args = array(
  'domain'=>'france',
  'run'=>$run['run'],
  'places'=>$place,
  'dates'=>$run['day'],
  'heures'=>sprintf('%d-%d',$utcStart,$utcStop),
  'params'=>'usfc;vsfc;pbltop'  // usfc = umet[0]
);

$query = http_build_query($args);

//echo $place;
$request = 'http://data2.rasp-france.org/json.php?'.$query;
//echo $request . '<br/>';
$response  = file_get_contents($request);
$jsondata  = json_decode($response, true);
//var_dump($jsondata); exit();



header('Content-type: text/html; charset=utf-8');
?>
<html>
<body>
Previsions <a href="http://rasp-france.org/" title="Site de previsions meteo pour parapente">RASP-France</a> n=<?php echo $run['run'] ?> du <?php echo $run['day'] ?> <br/>
<table border="1">
<tr>
<td>UTC+<?php echo $fuseauHoraire ?></td>
<?php
  $max = $utcStop+1;
  for ($h=$utcStart; $h<$max; $h++) {
    $heureLocale = str_pad($h+$fuseauHoraire, 2, '0', STR_PAD_LEFT);
    echo "<td>${heureLocale}h</td>\n";
  }
?>
</tr>
<tr>
<td>Plafond couche convective</td>
<?php
  $max = $utcStop+1;
  for ($h=$utcStart; $h<$max; $h++) {
    $pbltop = round($jsondata[$place][$run['day']][$h]['pbltop']);
    echo "<td>${pbltop}</td>\n";
  }
?>
<?php

?></td>
</tr>
<tr>
<td>Direction du vent</td>
<?php
  for ($h=$utcStart; $h<$max; $h++) {
    $dir = direction($jsondata[$place][$run['day']][$h]['usfc'], $jsondata[$place][$run['day']][$h]['vsfc']);
    echo "<td>${dir}</td>\n";
  }
?>
</tr>
<tr>
<td>Vitesse du vent (km/h)</td>
<?php
  for ($h=$utcStart; $h<$max; $h++) {
    $vit = vitesse($jsondata[$place][$run['day']][$h]['usfc'], $jsondata[$place][$run['day']][$h]['vsfc']);
    echo "<td>${vit}</td>\n";
  }
?>
</tr>
</table>

</body>
</html>