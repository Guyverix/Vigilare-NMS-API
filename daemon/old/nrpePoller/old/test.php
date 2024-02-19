<?php

//$raw="{"hostname":[\"guyver-office.iwillfearnoevil.com\",\"guyver-myth.iwillfearnoevil.com\"]}";
$raw="{\"hostname\": [\"guyver-office.iwillfearnoevil.com\",\"guyver-myth.iwillfearnoevil.com\"] }";

echo "RAW " . $raw . "\n";

$raw2=json_decode($raw, true);

echo "RAW2 " . $raw2 . "\n";

print_r($raw2);

?>



