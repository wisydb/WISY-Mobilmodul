<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************
 WISY 2.0
 ******************************************************************************
 Einfacher Passwortschutz für ein Portal, z.B. für Testzwecke.
 
 Achtung: Geringe Sicherheit!
 
 Das Passwort wird im Klartext in der Datenbank gespeichert und im Cookie
 nur durch ein einfaches md5() ohne salt etc. geschützt!
 ******************************************************************************/



class MOBIL_AUTH_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}

	function check()
	{

		if( isset($_REQUEST['wisyauth1']) || isset($_REQUEST['wisyauth2']) )
		{
			$enteredHash = md5(stripslashes($_REQUEST['wisyauth1']) . stripslashes($_REQUEST['wisyauth2']));
			setcookie('mobilwisyauth', $enteredHash); // only use a session cookie to allow the user to remove the cookie by closing the window.
		}
		else
		{
			$enteredHash = stripslashes($_COOKIE['mobilwisyauth']);
		}
		
		$correctHash = md5($this->framework->iniRead('mobil.auth.user', '') . $this->framework->iniRead('mobil.auth.password', ''));

		$title_default = "Passwort erforderlich!";
		$title_custom = trim($this->framework->iniRead('mobil.auth.html_title'));
		$title = (strlen($title_custom) > 3 ? $title_custom : $title_default);

		$formtext_default = "Um fortzufahren, geben Sie bitte einen g&uuml;tigen Benutzernamen und ein g&uuml;ltiges Passwort ein:";
		$formtext_custom = trim($this->framework->iniRead('mobil.auth.html_formtext'));
		$formtext = (strlen($formtext_custom) > 3 ? $formtext_custom : $formtext_default);

		if( $enteredHash != $correctHash )
		{
			?>
				<!DOCTYPE html>
				<html lang="de">
					<head>
					<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />
						<title><?php echo($title); ?></title>
						<style type="text/css">
							form {
								text-align: center; margin: 3em;
							}
						</style>
					</head>
					<body>
						<form action="" method="post">
							<?php echo($formtext); ?><br />
							Benutzername: <input name="wisyauth1" type="text" value="<?php echo isohtmlspecialchars(stripslashes($_REQUEST['wisyauth1'])) ?>" /><br />
							Passwort: <input name="wisyauth2" type="password" value="" /><br />
							<input type="submit" value=" OK " />
						</form>
					</body>
				</html>
			<?php
			exit();
		}
	}

};
?>