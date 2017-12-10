<?php if (isset($_SESSION['user'])){ // skip login
	header("Location: main.php?action=next");
} ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="charset=utf-8">
		<title>AbleTo Challenge</title>
	</head>
	<body bgcolor="#4286f4" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
		<?php echo $message ?>
		<table width="10%" border="0" cellspacing="5px" cellpadding="10px" align="center" height="10%">
			<form method="post" action="main.php?action=auth">
				<TR align = "center">
					<TD/>
					<TD>
						<FONT color = "#FFFFFF">
							<STRONG>AbleTo Challenge</STRONG>
						</FONT>
					</TD>
				</TR>
				<TR align = "center">
					<TD align = "center">
						<FONT color="#FFFFFF">
							<STRONG>Username:</STRONG>
						</FONT>					
					</TD>
					<TD>
						<INPUT name="user" type="text" class="formfield" size="20">
					</TD>
				</TR>
				<TR align = "center">
					<TD align = "center">
						<FONT color="#FFFFFF">
							<STRONG>Password:</STRONG>
						</FONT>					
					</TD>
					<TD>
						<INPUT name="pass" type="password" class="formfield" size="20">
					</TD>
				</TR>
				<TR>
					<TD>
						<INPUT type="submit" name="signup" value="SIGN-UP" class="button">
					</TD>
					<TD>
						<INPUT type="submit" name="login" value="LOGIN" class="button">
					</TD>
				</TR>
			</form>
		</table>
	</body>
</html>
