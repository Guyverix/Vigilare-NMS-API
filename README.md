# Vigilare

Welcome to Vigilare, an open-source fault management system designed to provide comprehensive monitoring and alerting capabilities. Vigilare is built to ensure high availability and reliability of your systems, offering real-time insights and proactive fault resolution.

Until I have an installer completed I am keeping the code in my personal gitlab repository.  I am currently sanatizing the code and getting the directory structures cleaned up.  Once the code is at least clean, I will be migrating the code base to here as well as the GUI repository.

The Poller repository is going to be the last to be built.  Currently the polling system is designed to run on the API server only.  This does not scale, so the pollers will end up being a discrete repository so the ability to have N+1 pollers will be supported.

Notes and information on the application do currently reside on **[wiki.iwillfearnoevil.com](https://wiki.iwillfearnoevil.com/mediawiki)**

## Features

- **Real-Time Monitoring:** Continuous surveillance of system performance and health.
- **Alerting Mechanism:** Immediate notifications for any system irregularities or failures.
- **Customizable Dashboards:** Tailor-made views to monitor vital metrics.
- **Historical Data Analysis:** In-depth analysis of past performance for better future planning.
- **User-Friendly Interface:** Easy to navigate UI for efficient management.
- **Template system:** Extend functionality beyond initial design with adding templates.
- **Independent GUI:** Use your own UI for in-house branding and looks.
- **Multiple Graph options available:** Support for rrd, and graphite are built in.
- **Database authentication:** Discrete access levels per user are supported.      

## Getting Started
- [ ] All testing done on Ubuntu / Debian systems
- [ ] Latest testing Ubuntu 20.04.6 LTS

### Prerequisites
- [ ] Installed Ubuntu packages for PHP
```
sudo apt install -y libapache2-mod-php8.2 php8.2 php8.2-bcmath php8.2-cli php8.2-common php8.2-curl php8.2-fpm php8.2-gd php8.2-gmp php8.2-intl php8.2-mbstring php8.2-mysql php8.2-opcache php8.2-phpdbg php8.2-readline php8.2-rrd php8.2-snmp php8.2-xml php8.2-zip php-snmp composer
```
- [ ] Installed packages for RRD
```
sudo apt install -y librrd8 rrdtool
```
- [ ] Installed packages for SNMP
```
sudo apt install python3-pysmi snmp snmp-mibs-downloader snmpd snmptrapd
```
- [ ] MySQL >= 5.7
```
sudo apt install mariadb
```


### Installation

# Step-by-step installation instructions
```
sudo su
cd /opt
git clone https://github.com/Guyverix/Vigilare-api.git
cd Vigilare-api
cd apps
```
### nano config.php
Anything defined with ??? is a maditory parameter
- [ ] ```$apiUrl ='https://FQDN';```
- [ ] ```$apiHost="https://FQDN of localhost"```
- [ ] ```$apiPort='PORT';```
- [ ] ```$apiKey ='random-guid';  //(uuidgen)```
- [ ] ```$frontendUrl='https://FQDN for GUI';```
- [ ] ```$dbHost='db IP';```
- [ ] ```$dbPort='3306';```
- [ ] ```$dbUser='dbUserid';```
- [ ] ```$dbPass='dbPassword';```
- [ ] ```$dbDatabase='event';```
- [ ] ```$emailSMTPAuth=true;```
- [ ] ```$emailSMTPAutoTLS=true;```
- [ ] ```$emailSMTPSecure=false;```
- [ ] ```$emailAuthType='PLAIN';```
- [ ] ```$emailFromAddress='nms@DOMAIN';```
- [ ] ```$emailFromName='Vigilare NMS Mailer';```
- [ ] ```$emailReplyToAddress='noreply@DOMAIN';```
- [ ] ```$emailReplyToName='Unmonitored Address';```
- [ ] ```$emailAdmin='admin@DOMAIN';  // basically unused currently```
- [ ] ```$emailLogin='emailUserId';```
- [ ] ```$emailPassword='emailPassword';```
- [ ] ```$emailSmtp='SMTP email gateway';```
- [ ] ```$emailPort='SMTP Email Port listener';```


There are additonal vars defined, which are stubs for future functionality.  Dont bother messing with them unless you are doing active development on the API.  For the most part they will not DO anything, but are in place as reminders of where they are going to live.

### nano settings.php
- [ ] ```'secret_key' => 'MAKE ME COMPLEX',  //(randomkeygen.com)```
- [ ] ```'api_auth_keys' => ["api-key-1", "api-key-2", 'api-key-3'], // key defined above in config.php for each API host being built```
- [ ] ```'passwordPepper' => 'pepperValue',   // (strings /dev/urandom | grep -o '[[:alnum:]]' | head -n 10 | tr -d '\n')```
- [ ] ```'frontendUrl' => 'https://FQDN for GUI'```

# Further installation steps
- [ ] Configure SSL certs for Apache or Nginx
- [ ] Configure Nginx / Apache to serve pages.  Basic configs to start are in /notes directory
- [ ] Both Frontend Gui and backend API are expecting SSL
- [ ] Import database from seeds in src/Seeds
- [ ] Test email values or you will get frustrated later :) ```testing/email/testSmtpServerSettings.php```
- [ ] Create initial admin user for auth with GUI ```testing/admin/addAdminUser.php```

# Basic usage instructions or commands
- [ ] Start Apache / Nginx
- [ ] Start polling daemons
- [ ] Current system uses manual start-stop scripts in api directory for daemon pollers
- [ ] End of API installation and configuration instructions

# Future tools
- [ ] bulk user add tool (CSV?)
- [ ] bulk host add tool (CSV?)

# Future support
- [ ] Discrete native support for AWS
- [ ] Ingestion of Cloud Watch events to local event database
- [ ] Adhoc Graphite URL additions outside of the nms.ABC pathing used by default
- [ ] LDAP / AD authentication
- [ ] Reporting templates

# Living with existing tools
- [ ] Forwarding from existing tools is possible via script (IE Nagios notification script) or snmptraps directly
- [ ] The system recognises if an event is passed as a proxy for a different host or came from the source directly

# Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are greatly appreciated.

    Fork the Project
    Create your Feature Branch (git checkout -b feature/AmazingFeature)
    Commit your Changes (git commit -m 'Add some AmazingFeature')
    Push to the Branch (git push origin feature/AmazingFeature)
    Open a Pull Request

# License

Distributed under the MIT License. See LICENSE for more information.
# Contact

Chris Hubbard – <chubbard@iwillfearnoevil.com>

Project Link: https://github.com/Guyverix/Vigilare-api
# Acknowledgements
This project has been built using the following frameworks and libraries
- [ ] https://github.com/slimphp/Slim-Skeleton

Composer addtions:
- [ ] slim/php-view
- [ ] slim/psr7
- [ ] slim/slim
- [ ] codes50/validation
- [ ] firebase/php-jwt
- [ ] freedsx/snmp
- [ ] gipfl/rrdtool
- [ ] monolog/monolog
- [ ] php-di/php-di
- [ ] phpmailer/phpmailer
- [ ] tuupola/slim-jwt-auth
- [ ] webmozart/assert

Testing tools loaded via compose:
- [ ] jangregor/phpstan-prophecy
- [ ] phpstan/extension-installer
- [ ] phpstan/phpstan
- [ ] phpunit/phpunit
 
