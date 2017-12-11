<!DOCTYPE html>
<html>
<head>
	<title>Today's Responses</title>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {

        var data = google.visualization.arrayToDataTable([
          ['answer', 'frequency']
          <?php
          	//print graph data	
          	for ($i = 0; $i < count($graph_data); $i++) {
          		$explode = explode("|", $graph_data[$i]);
          		if ($explode[0] === "") $explode[0] = "other";
          		echo ",['".$explode[0]."', ".$explode[1]."]";
          	}
          ?>
        ]);

        var options = {
          title: '<?php if(isset($question)) echo $question; ?>',
          backgroundColor: '#4286f4'
        };

        var chart = new google.visualization.PieChart(document.getElementById('piechart'));

        chart.draw(data, options);
      }

	</script>
</head>
<body bgcolor="#4286f4" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
	<table width="15%">
		<tr><td><FONT color = "#FFFFFF">Logged in as: <?php echo $user; ?></FONT></td></tr>
		<tr><td><a color = "#FFFFFF" href="index.php?action=signout">Sign Out</a></td></tr>
	</table>
	<table width="40%" cellspacing="5px" cellpadding="10px" align="center">
		<tr>
			<td/><td><FONT color="#FFFFFF"><STRONG>Today's Responses</STRONG></FONT></td><td/>
		</tr>
		<?php
			// print today's responses
			for ($i=0; $i < count($responses); $i++) { 
				$explode = explode("|", $responses[$i]);
				echo '<tr><td><FONT color = "#FFFFFF">Question '.($i+1).':</FONT></td>';
				echo '<td><FONT color = "#FFFFFF">'.$explode[0].'</FONT></td>';
				echo '<td><FONT color = "#FFFFFF">'.$explode[1].'</td></tr>';
			}
		?>
		<tr>
			<form method="post" action="index.php?action=results">
				<td><FONT color="#FFFFFF"><select name = "user">
					<option value="me">Me</option>
					<option value="all">Everyone</option>
				</select></FONT></td>
				<td><FONT color = "#FFFFFF">Question: <INPUT name="question" type="number" class="formfield" style="width: 40px"> Since <INPUT name="date" type="date" class="formfield" style="width: 130px"></FONT></td>
				<td><input name="submit" type="submit" value="View Responses" class="button"></td>
			</form>
		</tr>
	</table>
	<div id="piechart" style="width: 900px; height: 500px; margin:0 auto;"></div>
</body>
</html>