<?php
$matches = 1000;// number of matches from database to analyze

include 'show.php';// contains function to display data in html table

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
foreach ($rows as $document)
{
  if($counter++ > $matches-1) break;
  $comOpp = commonOpponents($document->winner_id,$document->loser_id);
  if(count($comOpp['id'])>0)
  {
    if(isset($comOppResults)) unset($comOppResults);
    if(isset($comOppRates)) unset($comOppRates);
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
        foreach($rows as $aaa) { $wins++; }
        //przegrane z nim
        $filter = array('$and' => array(
        array('winner_id' => $comOpp['id'][$i]),
        array('loser_id' => $player_id)
        ));
        $options = [];
        $query = new \MongoDB\Driver\Query($filter, $options);
        $rows   = $mongo->executeQuery('tenis.matches', $query);
        $loses = 0;
        foreach($rows as $aaa) { $loses++; }
        //wylicz RATE
        $rate = $wins / ($wins + $loses);
        $comOppResults[$player_id][$i]['wins'] = $wins;
        $comOppResults[$player_id][$i]['loses'] = $loses;
        $comOppRates[$player_id][$i] = $rate;
      }
    }
    //wylicz PREDICTION
    $sum_rate_w = 0;
    $sum_rate_l = 0;
    for($i=0; $i<count($comOppRates[$document->winner_id]); $i++)
    {
      $sum_rate_w += $comOppRates[$document->winner_id][$i];
      $sum_rate_l += $comOppRates[$document->loser_id][$i];
    }
    if($sum_rate_w + $sum_rate_l)
    {
        $prediction['winner'] = $sum_rate_w / ($sum_rate_w + $sum_rate_l);
        $prediction['loser'] = $sum_rate_l / ($sum_rate_w + $sum_rate_l);
    }
    else
    {
        $prediction['winner'] = 0.5;
        $prediction['loser'] = 0.5;
    }
  }
  else
  {
    $prediction = null;
    $comOppResults = null;
    $comOppRates = null;
  }

  show($counter,
    $document->winner_name,
    $document->winner_id,
    $document->loser_name,
    $document->loser_id,
    $document->score,
    $prediction,
    played($document->winner_id),
    played($document->loser_id),
    $comOpp,
    $comOppResults,
    $comOppRates);

  // statistics of predictions
  $predictionStats['comOpp'][$counter-1] = count($comOpp['id']);
  if($prediction != null AND $prediction['winner'] != 0.5) $predictionStats['prediction'][$counter-1] = $prediction['winner'];
  else $predictionStats['prediction'][$counter-1] = null;
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
        $percent = round((float)$percent * 100 ) . '%';
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
      $percent = round((float)$percent * 100 ) . '%';
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
    $correlation1 = array();
    $correlation2 = array();
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
  ?>
  <tr>
    <td>
      Common opponents:
      <?php
       echo($i);
       echo(' (occurs: ');
       echo($size);
       echo(')');
       array_push($correlation1, $i);
      ?>
    </td>
    <td>
      <?php
        if($size != 0) $percent = $sum / $size;
        else $percent = 0;
        $percent = $sum / $size;
        $percent = round((float)$percent * 100 );
        array_push($correlation2, $percent);
        $percent = $percent . '%';
        echo($percent);
      ?>
    </td>
  </tr>
  <?php
    }
  ?>
</table>
