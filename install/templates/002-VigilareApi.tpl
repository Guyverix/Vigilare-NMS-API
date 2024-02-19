<IfModule mod_ssl.c>
<VirtualHost \*:${API_PORT}>
        ServerName API_FQDN
	ServerAdmin webmaster@${DOMAIN}
        # Note that docRoot ends in public, NOT your main repo root
        DocumentRoot INSTALL_PATH/public

        SSLEngine on
        SSLCertificateFile ${SSL_PATH}/cert.pem
        SSLCertificateKeyFile ${SSL_PATH/}key.pem

        # Directory is the main path to the repository itself
        <Directory ${INSTALL_PATH}>
          AllowOverride All
          Options  +Indexes +FollowSymLinks 
          Require all granted
        </Directory>

	# Available loglevels: trace8, ..., trace1, debug, info, notice, warn,
	# error, crit, alert, emerg.
	# It is also possible to configure the loglevel for particular
	# modules, e.g.
	#LogLevel info ssl:warn
        # https://httpd.apache.org/docs/2.4/mod/core.html#loglevel

        LogLevel debug
	ErrorLog \${APACHE_LOG_DIR}/VigilareApi_error.log
	CustomLog \${APACHE_LOG_DIR}/VigilareApi_access.log combined

</VirtualHost>
</IfModule>
