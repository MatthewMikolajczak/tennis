<?php
$matchesFrom = 5000;// matches from database to analyze
$matchesTo = 5010;

include 'show.php';// contains function to display data in html table

function resultToValue($result)// function converts accurate result from string to one value
{
  $wgems = 0;
  $lgems = 0;
  $length = strlen($result);
  $i = 0;
  do {
    if($result[$i] == "R" OR $result[$i] == "W") break;
    $wgems += $result[$i];
    $i+=2;
    $lgems += $result[$i];
    $i+=1;
    if($result[$i] == '(') $i+=3;
    if($result[$i] == ' ') $i+=1;
  } while ($i < $length);
  if(($wgems + $lgems) != 0) $value = $wgems / ($wgems + $lgems);
  else $value = 0.5;
  return $value;
}

function isIDInArray($var, $arr)// function checks if there is player_id in comOpp array
{
  ini_set('display_errors', 0);
    for($i=0; $i<count($arr['id']); $i++)
      if($arr['id'][$i] == $var) return 1;
    return 0;
}

function played($player_id)// function counts how many matches player played
{
  $mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");
  $filter = array('$or' => array(
  array('winner_id' => $player_id),
  array('loser_id' => $player_id)
  ));
  $options = [];
  $query = new \MongoDB\Driver\Query($filter, $options);
  $rows   = $mongo->executeQuery('tenis.matches', $query);
  $counter = 0;
  foreach($rows as $aaa) { $counter++; }
  return $counter;
}

function commonOpponents($p1_id, $p2_id)// function searches for common opponnents
{
  $comOpp = array();

  $mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");
  $filter = array('$or' => array(
  array('winner_id' => $p1_id),
  array('loser_id' => $p1_id)
  ));
  $options = [];
  $query = new \MongoDB\Driver\Query($filter, $options);
  $rows   = $mongo->executeQuery('tenis.matches', $query);

  $counter = 0;
  foreach($rows as $result)
  {
    if($result->winner_id == $p1_id)
    {
      if(isIDInArray((int)$result->loser_id, $comOpp)) continue;
      $comOpp['id'][$counter] = $result->loser_id;
      $comOpp['name'][$counter++] = $result->loser_name;
    }
    else
    {
      if(isIDInArray((int)$result->winner_id, $comOpp)) continue;
      $comOpp['id'][$counter] = $result->winner_id;
      $comOpp['name'][$counter++] = $result->winner_name;
    }
  }
  for($i = 0; $i < count($comOpp['id']);)
  {
    $filter = array('$or' => array(
      array('$and' => array(
      array('winner_id' => $p2_id),
      array('loser_id' => $comOpp['id'][$i])
      )),
      array('$and' => array(
      array('winner_id' => $comOpp['id'][$i]),
      array('loser_id' => $p2_id)
      ))
    ));
    $options = [];
    $query = new \MongoDB\Driver\Query($filter, $options);
    $rows   = $mongo->executeQuery('tenis.matches', $query);
    if(count($rows->toArray()) > 0)
    {
      $i++;
    }
    else
    {
      unset($comOpp['id'][$i]);
      unset($comOpp['name'][$i]);
      $comOpp['id'] = array_values($comOpp['id']);
      $comOpp['name'] = array_values($comOpp['name']);
    }
  }
  return $comOpp;
}

// main script starts here

$mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");
$filter = [];
$options = [];

$query = new \MongoDB\Driver\Query($filter, $options);
$rows   = $mongo->executeQuery('tenis.matches', $query);

$counter = 0;
$realCounter = 0;
foreach ($rows as $document)
{
  $counter++;
  if($counter < $matchesFrom) continue;
  if($counter > $matchesTo) break;
  $realCounter++;
  $comOpp = commonOpponents($document->winner_id,$document->loser_id);
  if(count($comOpp['id'])>0)
  {
    if(isset($comOppResults)) unset($comOppResults);
    if(isset($comOppRates)) unset($comOppRates);
    if(isset($comOppRates2)) unset($comOppRates2);
    if(isset($comOppRates3)) unset($comOppRates3);
    $players = array($document->winner_id, $document->loser_id);
    foreach($players as $player_id)
    {
      for($i=0; $i<count($comOpp['id']); $i++)
      {
        //dla kazdego przeciwnika policz:
        //wygrane z nim
        $filter = array('$and' => array(
        array('winner_id' => $player_id),
        array('loser_id' => $comOpp['id'][$i])
        ));
        $options = [];
        $query = new \MongoDB\Driver\Query($filter, $options);
        $rows   = $mongo->executeQuery('tenis.matches', $query);
        $wins = 0;
        $gems = 0;
        foreach($rows as $aaa)
        {
          $wins++;
          $gems += resultToValue($aaa->score);
        }
        //przegrane z nim
        $filter = array('$and' => array(
        array('winner_id' => $comOpp['id'][$i]),
        array('loser_id' => $player_id)
        ));
        $options = [];
        $query = new \MongoDB\Driver\Query($filter, $options);
        $rows   = $mongo->executeQuery('tenis.matches', $query);
        $loses = 0;
        foreach($rows as $aaa)
        {
          $loses++;
          $gems += (1 - resultToValue($aaa->score));
        }
        //wylicz RATE
        $rate = $wins / ($wins + $loses);
        $comOppResults[$player_id][$i]['wins'] = $wins;
        $comOppResults[$player_id][$i]['loses'] = $loses;
        $comOppRates[$player_id][$i] = $rate;
        $comOppRates2[$player_id][$i] = $gems / ($wins + $loses);
        $comOppRates3[$player_id][$i] = 0.1 * ($gems / ($wins + $loses)) + 0.9 * $rate;
      }
    }
    //wylicz PREDICTION
    $sum_rate_w = 0;
    $sum_rate_l = 0;
    $sum_rate_w2 = 0;
    $sum_rate_l2 = 0;
    $sum_rate_w3 = 0;
    $sum_rate_l3 = 0;
    for($i=0; $i<count($comOppRates[$document->winner_id]); $i++)
    {
      $sum_rate_w += $comOppRates[$document->winner_id][$i];
      $sum_rate_l += $comOppRates[$document->loser_id][$i];
      $sum_rate_w2 += $comOppRates2[$document->winner_id][$i];
      $sum_rate_l2 += $comOppRates2[$document->loser_id][$i];
      $sum_rate_w3 += $comOppRates3[$document->winner_id][$i];
      $sum_rate_l3 += $comOppRates3[$document->loser_id][$i];
    }
    if($sum_rate_w + $sum_rate_l)
    {
        $prediction['winner'] = $sum_rate_w / ($sum_rate_w + $sum_rate_l);
        $prediction['loser'] = $sum_rate_l / ($sum_rate_w + $sum_rate_l);
        $prediction2['winner'] = $sum_rate_w2 / ($sum_rate_w2 + $sum_rate_l2);
        $prediction2['loser'] = $sum_rate_l2 / ($sum_rate_w2 + $sum_rate_l2);
        $prediction3['winner'] = $sum_rate_w3 / ($sum_rate_w3 + $sum_rate_l3);
        $prediction3['loser'] = $sum_rate_l3 / ($sum_rate_w3 + $sum_rate_l3);
    }
    else
    {
        $prediction['winner'] = 0.5;
        $prediction['loser'] = 0.5;
        $prediction2['winner'] = 0.5;
        $prediction2['loser'] = 0.5;
        $prediction3['winner'] = 0.5;
        $prediction3['loser'] = 0.5;
    }
  }
  else
  {
    $prediction = null;
    $prediction2 = null;
    $prediction3 = null;
    $comOppResults = null;
    $comOppRates = null;
  }

  show($counter,
    $document->winner_name,
    $document->winner_id,
    $document->loser_name,
    $document->loser_id,
    $document->score,
    played($document->winner_id),
    played($document->loser_id),
    $comOpp,
    $comOppResults,
    $comOppRates,
    $comOppRates2,
    $comOppRates3,
    $prediction,
    $prediction2,
    $prediction3);

  // statistics of predictions
  $predictionStats['comOpp'][$realCounter-1] = count($comOpp['id']);
  if($prediction != null AND $prediction['winner'] != 0.5) $predictionStats['prediction'][$realCounter-1] = $prediction['winner'];
  else $predictionStats['prediction'][$realCounter-1] = null;

  // statistics of predictions second method
  $predictionStats2['comOpp'][$realCounter-1] = count($comOpp['id']);
  if($prediction2 != null AND $prediction2['winner'] != 0.5) $predictionStats2['prediction'][$realCounter-1] = $prediction2['winner'];
  else $predictionStats2['prediction'][$realCounter-1] = null;

  // statistics of predictions third method
  $predictionStats3['comOpp'][$realCounter-1] = count($comOpp['id']);
  if($prediction3 != null AND $prediction3['winner'] != 0.5) $predictionStats3['prediction'][$realCounter-1] = $prediction3['winner'];
  else $predictionStats3['prediction'][$realCounter-1] = null;
  }
// counting statistics of all predictions
?>
<table>
  <tr>
    <td colspan="2" class="heading">
      SUMMARY
    </td>
  </tr>
  <tr>
    <td class="half">
      Prediction usage
    </td>
    <td class="half">
      <?php
        $use = 0;
        for($i=0; $i<count($predictionStats['prediction']); $i++)
          if($predictionStats['prediction'][$i] !== null) $use++;
        $percent = $use / count($predictionStats['prediction']);
        $percent = number_format(round((float)$percent * 100 , 2), 2, '.', '') . '%';
        echo($percent);
      ?>
    </td>
  </tr>
  <tr>
    <td>
      General prediction efficiency
    </td>
    <td>
      <?php
      $true = 0;
      for($i=0; $i<count($predictionStats['prediction']); $i++)
        if($predictionStats['prediction'][$i] > 0.5) $true++;
      $percent = $true / $use;
      $percent = number_format(round((float)$percent * 100 , 2), 2, '.', '') . '%';
      if($use > 0) echo($percent);
      else echo('undefined');
      ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="heading">
      PREDICTION EFFICIENCY DEPENDING OF COMMON OPPONENTS NUMBER
    </td>
  </tr>
  <?php
    $max = max($predictionStats['comOpp']);
    for($i=1; $i <= $max; $i++)
    {
      if(!in_array($i,$predictionStats['comOpp'])) continue;
      $sum = 0;
      $size = 0;
      for($j=0; $j<count($predictionStats['comOpp']); $j++)
      {
        if($predictionStats['comOpp'][$j] == $i AND $predictionStats['prediction'][$j] !== null AND $predictionStats['prediction'][$j] != 0.5)
        {
          if($predictionStats['prediction'][$j] > 0.5) $sum++;
          $size++;
        }
      }
      if($size == 0) continue;
  ?>
  <tr>
    <td>
      Common opponents:
      <?php
       echo($i);
       echo(' (occurs: ');
       echo($size);
       echo(')');
      ?>
    </td>
    <td>
      <?php
        if($size != 0) $percent = $sum / $size;
        else $percent = 0;
        $percent = $sum / $size;
        $percent = number_format(round((float)$percent * 100 , 2), 2, '.', '') . '%';
        echo($percent);
      ?>
    </td>
  </tr>
  <?php
    }
  ?>
</table>


<table>
  <tr>
    <td colspan="2" class="heading">
      SUMMARY - SECOND METHOD
    </td>
  </tr>
  <tr>
    <td class="half">
      Prediction usage
    </td>
    <td class="half">
      <?php
        $use = 0;
        for($i=0; $i<count($predictionStats2['prediction']); $i++)
          if($predictionStats2['prediction'][$i] !== null) $use++;
        $percent = $use / count($predictionStats2['prediction']);
        $percent = number_format(round((float)$percent * 100 , 2), 2, '.', '') . '%';
        echo($percent);
      ?>
    </td>
  </tr>
  <tr>
    <td>
      General prediction efficiency
    </td>
    <td>
      <?php
      $true = 0;
      for($i=0; $i<count($predictionStats2['prediction']); $i++)
        if($predictionStats2['prediction'][$i] > 0.5) $true++;
      $percent = $true / $use;
      $percent = number_format(round((float)$percent * 100 , 2), 2, '.', '') . '%';
      if($use > 0) echo($percent);
      else echo('undefined');
      ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="heading">
      PREDICTION EFFICIENCY DEPENDING OF COMMON OPPONENTS NUMBER
    </td>
  </tr>
  <?php
    $max = max($predictionStats2['comOpp']);
    for($i=1; $i <= $max; $i++)
    {
      if(!in_array($i,$predictionStats2['comOpp'])) continue;
      $sum = 0;
      $size = 0;
      for($j=0; $j<count($predictionStats2['comOpp']); $j++)
      {
        if($predictionStats2['comOpp'][$j] == $i AND $predictionStats2['prediction'][$j] !== null AND $predictionStats2['prediction'][$j] != 0.5)
        {
          if($predictionStats2['prediction'][$j] > 0.5) $sum++;
          $size++;
        }
      }
      if($size == 0) continue;
  ?>
  <tr>
    <td>
      Common opponents:
      <?php
       echo($i);
       echo(' (occurs: ');
       echo($size);
       echo(')');
      ?>
    </td>
    <td>
      <?php
        if($size != 0) $percent = $sum / $size;
        else $percent = 0;
        $percent = $sum / $size;
        $percent = number_format(round((float)$percent * 100 , 2), 2, '.', '') . '%';
        echo($percent);
      ?>
    </td>
  </tr>
  <?php
    }
  ?>
</table>


<table>
  <tr>
    <td colspan="2" class="heading">
      SUMMARY - THIRD METHOD
    </td>
  </tr>
  <tr>
    <td class="half">
      Prediction usage
    </td>
    <td class="half">
      <?php
        $use = 0;
        for($i=0; $i<count($predictionStats3['prediction']); $i++)
          if($predictionStats3['prediction'][$i] !== null) $use++;
        $percent = $use / count($predictionStats3['prediction']);
        $percent = number_format(round((float)$percent * 100 , 2), 2, '.', '') . '%';
        echo($percent);
      ?>
    </td>
  </tr>
  <tr>
    <td>
      General prediction efficiency
    </td>
    <td>
      <?php
      $true = 0;
      for($i=0; $i<count($predictionStats3['prediction']); $i++)
        if($predictionStats3['prediction'][$i] > 0.5) $true++;
      $percent = $true / $use;
      $percent = number_format(round((float)$percent * 100 , 2), 2, '.', '') . '%';
      if($use > 0) echo($percent);
      else echo('undefined');
      ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="heading">
      PREDICTION EFFICIENCY DEPENDING OF COMMON OPPONENTS NUMBER
    </td>
  </tr>
  <?php
    $max = max($predictionStats3['comOpp']);
    for($i=1; $i <= $max; $i++)
    {
      if(!in_array($i,$predictionStats3['comOpp'])) continue;
      $sum = 0;
      $size = 0;
      for($j=0; $j<count($predictionStats3['comOpp']); $j++)
      {
        if($predictionStats3['comOpp'][$j] == $i AND $predictionStats3['prediction'][$j] !== null AND $predictionStats3['prediction'][$j] != 0.5)
        {
          if($predictionStats3['prediction'][$j] > 0.5) $sum++;
          $size++;
        }
      }
      if($size == 0) continue;
  ?>
  <tr>
    <td>
      Common opponents:
      <?php
       echo($i);
       echo(' (occurs: ');
       echo($size);
       echo(')');
      ?>
    </td>
    <td>
      <?php
        if($size != 0) $percent = $sum / $size;
        else $percent = 0;
        $percent = $sum / $size;
        $percent = number_format(round((float)$percent * 100 , 2), 2, '.', '') . '%';
        echo($percent);
      ?>
    </td>
  </tr>
  <?php
    }
  ?>
</table>
