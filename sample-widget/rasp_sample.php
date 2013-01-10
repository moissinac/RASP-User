<?php

/******************************************
* RASP : www.mete-parapente.com           *
* *************************************** *
* récupération des prévisions météo des   *
* sites de parapente                      *
* Ce code est diffusé gratuitement et     *
* les volontés du site RASP sous licence  *
* GPL.                                    *
*                                         *
* Ce code est repris depuis :             *
* auteur : Alain Broussy alain@broussy.net*
* site: paraveyron.free.fr                *
*******************************************
* Auteur : herve.charlot<at>gmail.com     *
* Site : www.lesailesdebourgogne.fr       *
******************************************/


/* Definition des constantes */

  $utcStart = 7;//heure de départ (UTC)) des prévis // pas 6 h, le serveur bloque dessus
  $utcStop = 18;//heure de fin (UTC)) des prévis
  /*$fuseauHoraire = 2;*/ //en vue d'automatiser l'heure d'hiver et d'été..........
  $plage_horaire=sprintf('%d-%d',$utcStart,$utcStop);
  $param_lim_alti=4000; // altitude maxi de restitution des informations (en m /mer)
  //$param_lim_alti="pbltop"; // limite automatique au plaf

/* Informations dispo : http://rasp-france.org/utiliser-les-donnees
/**********************************************************************************
/*Nom   	Description 						Dims 	Unité
/**********************************************************************************
p 		pression 						3D 	hPa
z 		altitude des points du modèle (axe des points 3D) 	3D 	m (réf mer)
ter 		altitude du relief 					2D 	m (réf mer)
tc 		température de l'air 					3D 	°C
td 		température du point de rosée 				3D 	°C
umet 		Vent, vecteur U (voir annexe) 				3D 	m/s
vmet 		Vent, vecteur V (voir annexe) 				3D 	m/s
ublavg 		Vent moyen couche conv, vecteur U (voir annexe) 	2D 	m/s
vblavg 		Vent moyen couche conv, vecteur V (voir annexe) 	2D 	m/s
ubltop 		Vent sommet couche conv, vecteur U (voir annexe) 	2D 	m/s
vbltop 		Vent sommet couche conv, vecteur V (voir annexe) 	2D 	m/s
blwindshear	Cisaillement 						2D 	m/s
wblmaxmin 	Mouvements Verticaux Max (Convergence) 		2D 	cm/s
wstar 		Echelle Vitesse Moyenne Thermiques 			2D 	m/s
bsratio 	Rapport Flotabilité / Cisaillement 			2D 	sans
pblh 		Epaisseur couche convective 				2D 	m (réf sol)
pbltop 		Plafond couche convective 				2D 	m (réf mer)
tc2 		Température à 2m 					2D 	°C
cfraqh 		Couverture nuageuse - Haute 				2D 	%
cfraqm 		Couverture nuageuse - Moyenne 				2D 	%
cfracl 		Couverture nuageuse - Basse 				2D 	%
raintot		Proba de précipitaion 					2D	%
*/

  $params_rasp='usfc;vsfc;pbltop;wstar;ubltop;vbltop;umet;vmet;ter;z;p;tc;td;tc2;cfracl;cfracm;cfrach;raintot;blwindshear'; // liste des indications restituées
  $params_words = array('dir', 'vitesse(km/h)', 'alt plafm', 'vitesse thermique(m/s)', 'dir sommet', 'vitesse sommet(km/s)', 'dir', 'vitesse(km/h)',
                        'altitude site',  'altitudes modele (m)', 'pression hpa','Temp air °C','Point Rosée','Temp à 2m °C','Nébul basse','Nébul moyenne','Nébul haute','proba pluie','Cisaillement');


  $place="";

//adresses d'interrogation du site rasp-france.org
  $request_status = 'http://data2.rasp-france.org/status.php';
  $request_meteo = 'http://data2.rasp-france.org/json.php?';
  $contact=''; // Merci de remplacer cette constante par votre propre adresse (elle permet à rasp de contacter les webmasters en cas de besoin)

  $msg=""; /* Variable contenant les infos de debug du traitement*/

  /* fonction direction 
  ***********************
  donne une couleur verte à la direction du vent si celui-ci est compatible avec le site
  */
  function direction($u=0, $v=0, $good_orient=''){//$good est là pour la colorisation des cases de vent optimales
    $stringNum = array(     
	  "N" => 0,     "NNE" => 2,     
	  "NE" => 4,     "ENE" => 6,     
	  "E" => 8,    "ESE" => 10,    
	  "SE" => 12,    "SSE" => 14,    
	  "S" => 16,    "SSO" => 18,    
	  "SO" => 20,    "OSO" => 22,    
	  "O" => 24,    "ONO" => 26,    
	  "NO" => 28,    "NNO" => 30
    );
    $col="";

    $pi = 3.14159;    
    $dir = (atan2($u, $v)/$pi + 1)*180;  // directon en degrés
    $dirSecteur = round($dir/11.25);
    $goods = str_getcsv($good_orient);  //on récupère la bonne orientation
    
    foreach ($goods as $good)  
    {       
	    if ($good!='')
	    {      //ici on peut jouer sur la valeur d'écart des directions acceptables dans ce cas +/- 4'
		    $num = $stringNum[$good];         
		    if ((($num+4)>=$dirSecteur)and(($num-4)<=$dirSecteur))      
			    $col=" ok";  //coloration bonne orientation    
		    if ($num==0)      
		    {        
			    if (($dirSecteur==31)or($dirSecteur==30)or($dirSecteur==29))      
			    $col=" ok";      
		    }      
		    if ($num==1)      
		    {        
			    if ($dirSecteur==31)      
			    $col=" ok";      
		    }    
	    }  
    }
    //print "<td bgcolor='". $col . "'>" . $dirString[$dirSecteur] . "</td>";
    return round($dir).$col;
  }

  /* fonction vitesse 
  ***********************
  donne la vitesse du vent en km/h à partir des composantes de vitesse Nord et Est (u et v)
  */
  function vitesse($u=0, $v=0)//variable 3D donc $u et $v (voir sur le site meteo parapente 'utilisation des données')
  {  
    return round(sqrt($u*$u+$v*$v)*3.6);
  }

  /* fonction jour_fra 
  ***********************
  ecrit le jour en français à partir d'une date
  */
  function jour_fra($var_day){//petite fonction maison pour passer du english au french!! on pourrait faire mieux avec des arrays
    $eng_words = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
                     'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
 
    $french_words = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche',
                         'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
 
    $date_str = date('l d/m/Y',strtotime($var_day));
 
    return str_replace($eng_words, $french_words, $date_str);
}


/**************************************
*  DEBUT DU TRAITEMENT                *
**************************************/

  // Récupération des latitudes et longitudes du site si passé en paramètre
  if(isset($_GET['long']) && isset($_GET['lat']))
  {
    $places=array("site" => array("lat" => $lat,"long" => $long));
  }
  elseif(!isset($places))
  {
    // Définition des sites des ailes de bourgogne Notre dame d'Etang, Savigny, Lac Kir, Baulme La Roche
    $places=array(
      "NDE" => array( "Nom"=>"Velars - Site de la Madone "       ,"lat"=>'47.302',"long"=>'4.900',"Orientation"=>"E,O"),
      "SAV" => array( "Nom"=>"Savigny - Site de Chambeau"        ,"lat"=>'47.337',"long"=>'4.751',"Orientation"=>"S"),
      "LAC" => array( "Nom"=>"Dijon, Lac Kir"                    ,"lat"=>'47.326',"long"=>'4.985',"Orientation"=>"N"),
      "BAU" => array( "Nom"=>"Baulme la roche - Sites des Roches","lat"=>'47.350',"long"=>'4.800',"Orientation"=>"SSO"));
  }

  foreach( $places as $place_temp )
  {
    $place.=sprintf('%.3f,%.3f;', $place_temp['lat'], $place_temp['long']);//petite manip sur les variables pour les formatter à 3 décimales, pas obligatoire bien sur
  }

  // Récupération des statuts des calculs
  $response  = file_get_contents($request_status);
  $jsonstatus  = json_decode($response, true);

  foreach ( $jsonstatus['france'] as $run )
  {  
    if ($run['status']=='complete')   //ok le run est prêt les données sont prêtes  
    break;
  }
  // Décodage de la date au format français
  $jour = substr($run['day'], 6, 2);//run['day'] donne la date du run dans la requete là j'extrait le jour
  $mois = substr($run['day'], 4, 2);//le mois
  $an = substr($run['day'], 0, 4);//l'annnée'
  $jour_fr=$an.'/'.$mois.'/'.$jour;//classique

  // Vérification du paramètre contact
  if($contact=="") die("La constante $ contact doit être renseignée");

  // Gestion d'un Cache
  if(file_exists('results_'.$run['run'].'.json'))
  {
    $msg.='Depuis le cache';
    $response  = file_get_contents('results_'.$run['run'].'.json');
  }
  // si pas de fichier du "run" en cache, il faut interroger le serveur
  else
  {
    //Suppression des runs précédents
    array_map('unlink', glob("*.json"));
    $msg.='Depuis www.rasp-france.org';
    //construction de la requête

    //déclaration des arguments de la requête qui sont figés, on peut s'en passer en les déclarant directement dans $request'
    $meteo_args=array('domain'=>'france',  
    'run'=>$run['run'],  
    'places'=>$place,  
    'dates'=>$run['day'],  
    'heures'=>$plage_horaire,  
    'params'=>$params_rasp, //paramétres qui m'interessent il y en a d'autre, voir le script rasp-ffvl''
    'contact'=>$contact);

    // La requête avec $query qui prends donc les arguments et les paramétres dans le http_build
    $query = http_build_query($meteo_args);
    $response  = file_get_contents($request_meteo.$query);
    // on écrit le résultat en cache
    file_put_contents('results_'.$run['run'].'.json', $response);
  }
  // Fin Gestion d'un Cache (les données sont arrivées

  $jsondata  = json_decode($response, true);//on met en tampon les résultats

  // Traitement des informations pour tous les sites
  $params=explode(';',$params_rasp);

  // envoi du header et du début html
  header('Content-Type: text/html; charset=UTF-8');
  echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
  echo '<html xmlns="http?://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
  echo '	<head>'."\n";
  echo '		<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />'."\n";
  echo '		<title>Pr&#233;visions Meteo RASP</title>'."\n";
  echo '	</head>'."\n";
  echo '	<body>'."\n";
  $msg.='<br/>Pr&eacute;vision pour le <b>'.jour_fra($jour_fr).'</b> n&#176;&#13;&#10;=' . $run['run']. '&nbsp;<a href="http://rasp-france.org/" title="Site de pr&#233;visions meteo pour parapente">RASP-France</a></font>';
  echo $msg;

  foreach( $places as $place )
  {
    $ref=sprintf('%.3f,%.3f', $place['lat'], $place['long']);
    $titre='Emplacement : '.$place['Nom'].'( '.$ref.' ) alt='.round($jsondata[$ref][$run['day']]['7']['ter']) ."m";
    echo "<br/>".$titre."<br/>\n";

    // recherche de l'altitude max de la couche de convection
    if($param_lim_alti=="pbltop")
    {
      $lim_alti=0;
      // Gestion de l'altitude maxi :
      for($heure=$utcStart;$heure<=$utcStop;$heure++)
	$lim_alti=($lim_alti > round($jsondata[$ref][$run['day']][$heure]['pbltop'] + 1) ? $lim_alti : round($jsondata[$ref][$run['day']][$heure]['pbltop']+1) );
    }
    else
      $lim_alti=$param_lim_alti;

    echo "plaf_max = ".$lim_alti."m<br/>";      

    echo "<table border=1>\n";
    echo "\t<tr><th>info</th>";
    for($heure=$utcStart;$heure<=$utcStop;$heure++)
    echo "<th>".$heure."</th>";
    echo "</tr>\n";
    foreach($params as $param)
    {
      if($param!='z')
      {
	echo "\t<tr><td>".$param."=".str_replace($params,$params_words,$param)."</td>";
	for($heure=$utcStart;$heure<=$utcStop;$heure++)
	{
	  switch ($param) {
	    case "usfc":
	      echo "<td>".direction($jsondata[$ref][$run['day']][$heure]['usfc'], $jsondata[$ref][$run['day']][$heure]['vsfc'], $place['Orientation'])."</td>";
	      break;
	    case "vsfc":
	      echo "<td>".vitesse($jsondata[$ref][$run['day']][$heure]['usfc'], $jsondata[$ref][$run['day']][$heure]['vsfc'])."</td>";
	      break;
	    case "ubltop":
	      echo "<td>".direction($jsondata[$ref][$run['day']][$heure]['ubltop'], $jsondata[$ref][$run['day']][$heure]['vbltop'])."</td>";
	      break;
	    case "vbltop":
	      echo "<td>".vitesse($jsondata[$ref][$run['day']][$heure]['ubltop'], $jsondata[$ref][$run['day']][$heure]['vbltop'])."</td>";
	      break;
	    case "umet";
	      echo "<td>";
	      foreach($jsondata[$ref][$run['day']][$heure]['z'] as $key => $alt)
		  if($alt<$lim_alti)echo round($alt)."m=".direction($jsondata[$ref][$run['day']][$heure]['umet'][$key], $jsondata[$ref][$run['day']][$heure]['vmet'][$key])."<br/>";
	      echo "</td>";
	      break;
	    case "vmet";
	      echo "<td>";
	      foreach($jsondata[$ref][$run['day']][$heure]['z'] as $key => $alt)
		  if($alt<$lim_alti)echo round($alt)."m=".vitesse($jsondata[$ref][$run['day']][$heure]['umet'][$key], $jsondata[$ref][$run['day']][$heure]['vmet'][$key])."<br/>";
	      echo "</td>";
	      break;
	    case "ter":
	      if($heure!=$utcStart)
	      {
		break;
	      }
	    default :
	      if(is_Array($jsondata[$ref][$run['day']][$heure][$param]))
	      {
		echo "<td>";
		foreach($jsondata[$ref][$run['day']][$heure]['z'] as $key => $alt)
		  if($alt<$lim_alti)echo round($alt)."m=".$jsondata[$ref][$run['day']][$heure][$param][$key]."<br/>";
		echo "</td>";
	      }
	      else echo "<td>".$jsondata[$ref][$run['day']][$heure][$param]."</td>";
	  }
	}
	echo "</tr>\n";
      }
    }
    echo "</table>\n";
  }
//die('Fin</html>');
//  die('<pre>'.print_r($jsondata[$ref],1).'</pre><br/>'.$msg.'<br/>Fin</html>');
echo '	</body>'."\n";
echo '</html>'."\n";
