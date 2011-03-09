<?php
/**
 * 2011-03-07 10:45
 */
include_once('class.ronda.php');
$ronda = new ronda();
$ronda->rc($_GET);
$ret = $ronda->html();

$TITLE = 'Ronda';
$CONTENT .= sprintf('<h1>%1$s</h1>', $TITLE);
$CONTENT .= $ret;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo($TITLE); ?></title>
<link type="text/css" rel="stylesheet" href="style.css" />
<script type="text/javascript" src="script.js"></script>
<?php echo($HEADER); ?>
</head>
<body>
<?php echo($CONTENT); ?>
<div id="footer"><a href="http://code.google.com/p/ronda/"><strong>ronda</strong></a> | <a href="README.TXT">README</a></div>
</body>
</html>