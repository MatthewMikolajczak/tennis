<?php
function randomImages()// function to draw players images
{
  $names = array("user01.png","user02.png","user03.png","user04.png","user05.png","user06.png","user07.png",);
  $res = array_rand($names,2);
  $array = array($names[$res[0]],$names[$res[1]]);
  return $array;
}
function show($counter,// function to display data from one match in html table
  $db_winner_name,
  $db_winner_id,
  $db_loser_name,
  $db_loser_id,
  $db_score,
  $p1_m,
  $p2_m,
  $comOpp,
  $comOppResults,
  $comOppRates,
  $comOppRates2,
  $comOppRates3,
  $prediction,
  $prediction2,
  $prediction3)
{
  $img = randomImages();
  ?>
  <table>
    <tr>
      <td rowspan="1000" class="id">
        <?php
            echo("$counter.");
        ?>
      </td>
      <td colspan="2" class="player">
        <img src="images/<?php echo("$img[0]"); ?>">
        <span class="name">
        <?php
            print_r ($db_winner_name);
            echo('</span>matches: ');
            print_r ($p1_m);
        ?>
      </td>
      <td class="vs">VS.</td>
      <td colspan="2" class="player">
        <img src="images/<?php echo("$img[1]"); ?>">
        <span class="name">
        <?php
              print_r ($db_loser_name);
              echo('</span>matches: ');
              print_r ($p2_m);
        ?>
      </td>
    </tr>
    <tr>
      <td colspan="5">COMMON OPPONENTS (<?php echo(count($comOpp['id'])); ?>)</td>
    </tr>
    <?php
      if(count($comOpp['id'])>0)
      {
    ?>
    <tr>
      <td>results</td>
      <td>rate</td>
      <td>name</td>
      <td>rate</td>
      <td>results</td>
    </tr>
    <?php
      for($i=0; $i<count($comOpp['id']); $i++)
      {
        ?>
        <tr>
          <td>
            <?php
            // $comOppResults[$player_id][$i]['wins'] = $wins;
            // $comOppResults[$player_id][$i]['loses'] = $loses;
            // $comOppRates[$player_id][$i] = $rate;
            print_r ($comOppResults[$db_winner_id][$i]['wins']);
            echo('-');
            print_r ($comOppResults[$db_winner_id][$i]['loses']);
            ?>
          </td>
          <td>
            <?php
              print_r (number_format(round((float)$comOppRates[$db_winner_id][$i] , 2), 2, '.', ''));
            ?>
          </td>
          <td><?php print_r ($comOpp['name'][$i]); echo(' ('); print_r ($comOpp['id'][$i]); echo(')'); ?></td>
          <td>
            <?php
              print_r (number_format(round((float)$comOppRates[$db_loser_id][$i] , 2), 2, '.', ''));
            ?>
          </td>
          <td>
            <?php
            print_r ($comOppResults[$db_loser_id][$i]['wins']);
            echo('-');
            print_r ($comOppResults[$db_loser_id][$i]['loses']);
            ?>
          </td>
        </tr>
        <?php
      }
    ?>
    <tr>
      <td colspan="5">PREDICTION RESULT</td>
    </tr>
    <tr>
      <td>
        <?php
            if($prediction['winner'] > 0.5) echo('winner');
            else if($prediction['winner'] < 0.5) echo('loser');
            else echo('unknown');
        ?>
      </td>
      <td>
        <?php
            $percent = number_format(round((float)$prediction['winner'] * 100 , 2), 2, '.', '') . '%';
            echo($percent);
        ?>
      </td>
      <td></td>
      <td>
        <?php
            $percent = number_format(round((float)$prediction['loser'] * 100 , 2), 2, '.', '') . '%';
            echo($percent);
        ?>
      </td>
      <td>
        <?php
            if($prediction['loser'] > 0.5) echo('winner');
            else if($prediction['loser'] < 0.5) echo('loser');
            else echo('unknown');
        ?>
      </td>
    </tr>
    <tr>
      <td colspan="5">REAL RESULT</td>
    </tr>
    <tr>
      <td colspan="2">winner</td>
      <td>
        <?php
          print_r ($db_score);
        ?>
      </td>
      <td colspan="2">loser</td>
    </tr>
    <?php
      }
    ?>
    <tr>
      <td colspan="5" class="<?php
      if($prediction != false && $prediction['winner'] > 0.5) $prediction_result = true;
      else if($prediction != false && $prediction['winner'] < 0.5) $prediction_result = false;
      if(isset($prediction_result))
      {
        if($prediction_result) echo("true");
        else echo("false");
      }
      else echo("undefined");
      ?>">FIRST METHOD PREDICTION IS: <?php
      if(isset($prediction_result))
      {
        if($prediction_result) echo("TRUE");
        else echo("FALSE");
      } else echo("UNDEFINED");
      ?></td>
    </tr>
    <?php
      if(count($comOpp['id'])>0)
      {
    ?>
    <tr>
      <td colspan="5">SECOND METHOD</td>
    </tr>
    <tr>
      <td colspan="5">COMMON OPPONENTS (<?php echo(count($comOpp['id'])); ?>)</td>
    </tr>
    <tr>
      <td>results</td>
      <td>rate</td>
      <td>name</td>
      <td>rate</td>
      <td>results</td>
    </tr>
    <?php
      for($i=0; $i<count($comOpp['id']); $i++)
      {
        ?>
        <tr>
          <td>
            <?php
            // $comOppResults[$player_id][$i]['wins'] = $wins;
            // $comOppResults[$player_id][$i]['loses'] = $loses;
            // $comOppRates[$player_id][$i] = $rate;
            print_r ($comOppResults[$db_winner_id][$i]['wins']);
            echo('-');
            print_r ($comOppResults[$db_winner_id][$i]['loses']);
            ?>
          </td>
          <td>
            <?php
              print_r (number_format(round((float)$comOppRates2[$db_winner_id][$i] , 2), 2, '.', ''));
            ?>
          </td>
          <td><?php print_r ($comOpp['name'][$i]); echo(' ('); print_r ($comOpp['id'][$i]); echo(')'); ?></td>
          <td>
            <?php
              print_r (number_format(round((float)$comOppRates2[$db_loser_id][$i] , 2), 2, '.', ''));
            ?>
          </td>
          <td>
            <?php
            print_r ($comOppResults[$db_loser_id][$i]['wins']);
            echo('-');
            print_r ($comOppResults[$db_loser_id][$i]['loses']);
            ?>
          </td>
        </tr>
        <?php
      }
    ?>
    <tr>
      <td colspan="5">PREDICTION RESULT</td>
    </tr>
    <tr>
      <td>
        <?php
            if($prediction2['winner'] > 0.5) echo('winner');
            else if($prediction2['winner'] < 0.5) echo('loser');
            else echo('unknown');
        ?>
      </td>
      <td>
        <?php
            $percent = number_format(round((float)$prediction2['winner'] * 100 , 2), 2, '.', '') . '%';
            echo($percent);
        ?>
      </td>
      <td></td>
      <td>
        <?php
            $percent = number_format(round((float)$prediction2['loser'] * 100 , 2), 2, '.', '') . '%';
            echo($percent);
        ?>
      </td>
      <td>
        <?php
            if($prediction2['loser'] > 0.5) echo('winner');
            else if($prediction2['loser'] < 0.5) echo('loser');
            else echo('unknown');
        ?>
      </td>
    </tr>
    <tr>
      <td colspan="5">REAL RESULT</td>
    </tr>
    <tr>
      <td colspan="2">winner</td>
      <td>
        <?php
          print_r ($db_score);
        ?>
      </td>
      <td colspan="2">loser</td>
    </tr>
    <?php
      }
    ?>
    <tr>
      <td colspan="5" class="<?php
      if($prediction2 != false && $prediction2['winner'] > 0.5) $prediction_result2 = true;
      else if($prediction2 != false && $prediction2['winner'] < 0.5) $prediction_result2 = false;
      if(isset($prediction_result2))
      {
        if($prediction_result2) echo("true");
        else echo("false");
      }
      else echo("undefined");
      ?>"> SECOND METHOD PREDICTION IS: <?php
      if(isset($prediction_result2))
      {
        if($prediction_result2) echo("TRUE");
        else echo("FALSE");
      } else echo("UNDEFINED");
      ?></td>
    </tr>

    <?php
      if(count($comOpp['id'])>0)
      {
    ?>
    <tr>
      <td colspan="5">THIRD METHOD</td>
    </tr>
    <tr>
      <td colspan="5">COMMON OPPONENTS (<?php echo(count($comOpp['id'])); ?>)</td>
    </tr>
    <tr>
      <td>results</td>
      <td>rate</td>
      <td>name</td>
      <td>rate</td>
      <td>results</td>
    </tr>
    <?php
      for($i=0; $i<count($comOpp['id']); $i++)
      {
        ?>
        <tr>
          <td>
            <?php
            // $comOppResults[$player_id][$i]['wins'] = $wins;
            // $comOppResults[$player_id][$i]['loses'] = $loses;
            // $comOppRates[$player_id][$i] = $rate;
            print_r ($comOppResults[$db_winner_id][$i]['wins']);
            echo('-');
            print_r ($comOppResults[$db_winner_id][$i]['loses']);
            ?>
          </td>
          <td>
            <?php
              print_r (number_format(round((float)$comOppRates3[$db_winner_id][$i] , 2), 2, '.', ''));
            ?>
          </td>
          <td><?php print_r ($comOpp['name'][$i]); echo(' ('); print_r ($comOpp['id'][$i]); echo(')'); ?></td>
          <td>
            <?php
              print_r (number_format(round((float)$comOppRates3[$db_loser_id][$i] , 2), 2, '.', ''));
            ?>
          </td>
          <td>
            <?php
            print_r ($comOppResults[$db_loser_id][$i]['wins']);
            echo('-');
            print_r ($comOppResults[$db_loser_id][$i]['loses']);
            ?>
          </td>
        </tr>
        <?php
      }
    ?>
    <tr>
      <td colspan="5">PREDICTION RESULT</td>
    </tr>
    <tr>
      <td>
        <?php
            if($prediction3['winner'] > 0.5) echo('winner');
            else if($prediction3['winner'] < 0.5) echo('loser');
            else echo('unknown');
        ?>
      </td>
      <td>
        <?php
            $percent = number_format(round((float)$prediction3['winner'] * 100 , 2), 2, '.', '') . '%';
            echo($percent);
        ?>
      </td>
      <td></td>
      <td>
        <?php
            $percent = number_format(round((float)$prediction3['loser'] * 100 , 2), 2, '.', '') . '%';
            echo($percent);
        ?>
      </td>
      <td>
        <?php
            if($prediction3['loser'] > 0.5) echo('winner');
            else if($prediction3['loser'] < 0.5) echo('loser');
            else echo('unknown');
        ?>
      </td>
    </tr>
    <tr>
      <td colspan="5">REAL RESULT</td>
    </tr>
    <tr>
      <td colspan="2">winner</td>
      <td>
        <?php
          print_r ($db_score);
        ?>
      </td>
      <td colspan="2">loser</td>
    </tr>
    <?php
      }
    ?>
    <tr>
      <td colspan="5" class="<?php
      if($prediction3 != false && $prediction3['winner'] > 0.5) $prediction_result3 = true;
      else if($prediction3 != false && $prediction3['winner'] < 0.5) $prediction_result3 = false;
      if(isset($prediction_result3))
      {
        if($prediction_result3) echo("true");
        else echo("false");
      }
      else echo("undefined");
      ?>"> THIRD METHOD PREDICTION IS: <?php
      if(isset($prediction_result3))
      {
        if($prediction_result3) echo("TRUE");
        else echo("FALSE");
      } else echo("UNDEFINED");
      ?></td>
    </tr>
  </table>
  <?php
}
?>
