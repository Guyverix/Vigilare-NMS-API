<?php
\$apiUrl =\"https://${API_FQDN}\";      // for responding with things like images
\$apiHost=\"https://${API_FQDN}\";      // Poller default and independent of \$apiUrl
\$apiPort=\"${API_PORT}\";              // Used to craft URLs correctly when non-standart ports in play
\$apiKey =\"${API_KEY}\";               // Create with uuidgen :) lives in settings.php as array.  This is for daemons or custom script auth

\$frontendUrl=\"https://${GUI_PROXY}\";

\$daemonLogSeverity=0;        // Log everything possible
\$snmpLogSeverity=3;          // Log down to warning
\$nrpeLogSeverity=2;          // Log down to info
\$shellLogSeverity=1;         // Log down to debug
\$housekeepingLogSeverity=1;  // Log down to debug

\$snmpMaxChildren=40;
\$nrpeMaxChildren=30;
\$shellMaxChildren=20;

\$snmpMaxWork=1;
\$nrpeMaxWork=1;
\$shellMaxWork=1;
\$nrpePath=\"/usr/lib/nagios/plugins/check_nrpe\";      // Define this here even though it is specific to NRPE daemon
\$pollerName=\"${API_FQDN}\";                           // Define here, so remote collectors have one spot only to change (unsupported not even alpha level 02-04-24)

\$housekeepingDebugClean=\"86400\";                     // how long to keep debugger files
\$housekeepingGraphClean=\"3600\";                      // how long to keep rrd files in public/static/???.jpg

\$emailSMTPAuth=true;
\$emailSMTPAutoTLS=true;
\$emailSMTPSecure=false;
\$emailAuthType=\"${EMAIL_AUTH}\";
\$emailFromAddress=\"${EMAIL_FROM}\";
\$emailFromName=\"NMS Mailer\";
\$emailReplyToAddress=\"noreply@${DOMAIN}\";
\$emailReplyToName=\"Unmonitored Address\";

\$emailLogin=\"${EMAIL_USER}\";
\$emailPassword=\"${EMAIL_PASS}\";
\$emailSmtp=\"${EMAIL_GATEWAY}\";
\$emailPort=\"${EMAIL_PORT}\";

\$rrdUrl=\$apiUrl . \":\" . \$apiPort . \"/static/\";
\$graphiteUrl=\"https://graphite.${DOMAIN}\";
\$graphitePort=443;

?>
