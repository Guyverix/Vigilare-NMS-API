<?php

$arr["foo"]["bar"] = "baz";

$hostname="foobar.com";

// This must be defined in the database with the {} enclosing the variable
$var='-h {$hostname} -a {$address} -f {$arr["foo"]["bar"]}';

echo "VAR " . $var . "\n";

$var2 = eval( 'return "' . $var . '";') ;

echo "RESULT " . $var2 . "\n";


?>



