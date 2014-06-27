<?php
require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."application".DIRECTORY_SEPARATOR."models". DIRECTORY_SEPARATOR."content.php";

function imgOut()
{
	$c = new Content;
	echo "w2";
print_r($c->to_array_custom("Brilon-1.JPG"));
echo "danach";
}
error_reporting (E_ALL );
imgOut();
 ?>