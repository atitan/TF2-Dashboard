<!DOCTYPE html>
<html>
<head>
	<title>Dashboard</title>
	<meta charset="utf-8">
</head>
<body>
	<h1>Dashboard</h1>
	<table border="1">
  		<tr>
    		<th>玩家名稱</th>
    		<th>玩家ID</th>
    		<th>事件原因</th>
    		<th>處置</th>
    		<th>起始時間</th>
    		<th>截止時間</th>
    		<th>管理員名稱</th>
  		</tr>
  		<?php
  			$action = array(
  				'class' => '限制職業',
  				'ban' => '水桶',
  				'mute' => '靜音',
  				'spray' => '禁止噴漆',
  				'disarm' => '解除武裝'
  			);

  			for($o=0; $o < count($banData); $o++) { 
  				echo '<tr>';
    			for ($i=0; $i < count($banData[$o]); $i++) { 
    				if ($i == 0) {
    					echo '<td><a class="nofollow" href="'.$banData[$o][$i+2].'" target="_blank">'.$banData[$o][$i].'</a></td>';
    				} else if ($i == 4) {
    					if ($banData[$o][$i] == 'class') {
    						echo '<td>'.$action[$banData[$o][$i]].': '.$banData[$o][$i+1].'</td>';
    					} else {
    						echo '<td>'.$action[$banData[$o][$i]].'</td>';
    					}		
    				} else if($i == 6) {
    					echo '<td>'.date("Y-m-t H:i", $banData[$o][$i]).'</td>';
    				} else if($i == 7) {
    					if ($banData[$o][$i] == 0) {
    						echo '<td>永久</td>';
    					} else {
    						echo '<td>'.date("Y-m-t H:i", $banData[$o][$i]).'</td>';
    					}
    				} else if($i != 5 && $i != 2) {
    					echo '<td>'.$banData[$o][$i].'</td>';
    				} 				
    			}
  				echo '</tr>';
  			}
  		?>
	</table>
</body>
</html>