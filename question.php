<!DOCTYPE html>
<html>
<head>
	<title>Question <?php echo $question_number; ?></title>
</head>
<body bgcolor="#4286f4" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
	<table width="15%">
		<tr><td><FONT color = "#FFFFFF">Logged in as: <?php echo $user; ?></FONT></td></tr>
		<tr><td><a color = "#FFFFFF" href="index.php?action=signout">Sign Out</a></td></tr>
	</table>
	<table width="30%" border="0" cellspacing="5px" cellpadding="10px" align="center" height="50%">
		<tr>
			<td><FONT color = "#FFFFFF"><STRONG>Question <?php echo $question_number; ?></STRONG></FONT></td>
		</tr>
		<tr>
			<td><FONT color = "#FFFFFF"><?php echo $question;?></FONT>
		</tr>
		<form method="post" <?php echo 'action="index.php?action=next&question='.$question_number.'"';?>>
		<?php
			//print the possible answers
			for ($i=0; $i < sizeof($answers); $i++) { 
				echo '<tr><td><input type="radio" name="answer" value="'.$answers[$i].'"><FONT color = "#FFFFFF">'.$answers[$i].'</FONT></td></tr>';
			}
		?>
			<tr>
				<td>
					<input type="submit" name="submit" value="Next Question">
				</td>
			</tr>
		</form>
	</table>
</body>
</html>