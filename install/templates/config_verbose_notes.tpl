<?php
/*
  This is configuration that is needed when building things such as response URL's etc.
  No need to get too tricky here, but do at least attempt to keep security in mind
  for a later release and move this to something that cannot be easily read or accessed.

  There are some assumptions made here.  All internal, SSL may or may not be present.
  Given the state of the world, only someone very trusting or stupid is going to allow stuff to
  be on the internet that may be sensitive info.  Pray this is ALWAYS behind firewalls

  The API server should be as anti-social as possible.  Give minimal info back in general
  and EXACTLY what is needed back when called.  Pay close attention to API args due to this.
  It may be redundant, but will help filter down the data returned.
*/

/*
  API VARIBLES
*/
$apiUrl ='https://API_FQDN';      // for responding with things like images
$apiHost="https://API_FQDN";      // Poller default and independent of $apiUrl
$apiPort='API_PORT';                                      // Used to craft URLs correctly when non-standart ports in play
$apiKey ='API_KEY';      // Create with uuidgen :) lives in settings.php as array.  This is for daemons or custom script auth

/*
  FRONTEND UI VARIABLES

  There are times when the API fashions URL's for end users.
  The API needs to know the root URL to hang data off of.
  If you use an abnormal port, append :### to the end
  of the variable
*/
$frontendUrl='https://GUI_PROXY';

/*
  DATABASE VARIABLES (WIP)
  Define where to talk to the MySQL/MariaDB (Postgres?) database
  User should have full privs against this specific database.

  Reporting if possible should go against a replica in larger
  installations.  Query by the foot really affects performance, but
  you know someone will do it and screw up writes with read locks.
*/
$dbHost='DB_IP';
$dbPort='DB_PORT';
$dbUser='DB_USER';
$dbPass='DB_PASS';
$dbDatabase='vigilare';

//(completely unused ATM, likely only useful for reporting in the future)
$dbReplica='255.255.255.255';
$dbReplicaPort='3306';
$dbReplicaUser='replica';
$dbReplicaPass='randomPass';
$dbReplicaDb='vigilare';

/*
  DAEMON VARIABLES (WIP)
  Daemon threading and scale tweaks can be done here.
  Hopefully if this ever needs to be adjusted another
  host will be brought online instead.  By the time it is necessary to
  adjust children, likely we will be looking at disk IO or perhaps even
  RAM issues as well.  A small remote host may be a better solution than
  tweaking children locally.

  Any changes here require daemon to be restarted or they will not be
  picked up.  This file is only read once at daemon start.

  pollerName should not be IP address, but a full hostname so graphs on perf
  are able to be found.
  Log Levels default to $daemonLogSeverity if not set/overridden
*/

$daemonLogSeverity=0;        // Log everything possible
$snmpLogSeverity=3;          // Log down to warning
$nrpeLogSeverity=2;          // Log down to info
$shellLogSeverity=1;         // Log down to debug
$housekeepingLogSeverity=1;  // Log down to debug

// How many children should live at once (max)
$snmpMaxChildren=40;
$nrpeMaxChildren=30;
$shellMaxChildren=20;

// MaxWork per child should always be 1. (or bad things will happen for now?)
$snmpMaxWork=1;
$nrpeMaxWork=1;
$shellMaxWork=1;
$nrpePath="/usr/lib/nagios/plugins/check_nrpe";      // Define this here even though it is specific to NRPE daemon
$pollerName='API_FQDN';                              // Define here, so remote collectors have one spot only to change (unsupported in V1)

$housekeepingDebugClean='86400';                     // how long to keep debugger files
$housekeepingGraphClean='3600';                      // how long to keep rrd files in public/static/???.jpg

/*
  EMAIL VARIABLES

                    EMAIL OUTBOUND
  SMTP leverages PHPMailer, since that seems to handle 99% of normal use cases
  once it is configured correctly.

                    EMAIL INBOUND (for events) (TODO)
  Email parsing to events is quite useful for smaller companies that are transitioning
  from email monitoring to more formal event monitoring.  Leverage the existing
  Parsers as a baseline, and templatize it so different people can get events
  no matter what is being sent.

  Remember in IMAP to delete message after parsing!
  This inbound email is intended to be exclusive for inbound events and if spam
  gets in and is not parsable it will be nuked.  There should never be a backlog
  of emails that this system will need to parse as long as email itself is functioning.

  Do NOT use a shared email address with something else.  Since this will delete messages
  after processing them.

*/

// Leverages the PHPMailer class for outbound emails
// https://github.com/PHPMailer/PHPMailer
// SSL cert MUST be configured correctly for this to work
// Appears self signed make it choke as well.  Use Lets Encrypt if you want SSL easily

// TEST YOUR VALUES IN ../testing/email/testSmtpServerSettings.php to find what works for you
$emailSMTPAuth=true;
$emailSMTPAutoTLS=true;
$emailSMTPSecure=false;
$emailAuthType='EMAIL_AUTH';
$emailFromAddress='EMAIL_FROM';
$emailFromName='NMS Mailer';
$emailReplyToAddress='noreply@DOMAIN';
$emailReplyToName='Unmonitored Address';

$emailAdmin='admin@DOMAIN';  // basically unused currently
$emailLogin='EMAIL_USER';
$emailPassword='EMAIL_PASS';
$emailSmtp='EMAIL_GATEWAY';
$emailPort='EMAIL_PORT';

// totally TODO.  Donno if it will ever be needed TBH
$emailRecieveType='imap';  // convert emails to events!
$emailRecievePort='119';
$emailRecieveUser='fakeUser';
$emailRecievePassword='fakePassword';
$emailRecieveHost='imap.DOMAIN';

/*
  AUTHENTICATION VARIABLES
                        USER AUTH (WIP)
  Authentication types.  Support a mix.
  local or db will be default, later LDAP, and finally if I build a local AD server. The
  SALT value will be random string at install.  API will NEVER send raw salt value in a response.
  This will only ever be loaded into memory when creating db/redis entries.  API will have no
  concept of this variable directly.

  Caution: if fallback is EVER used, this will fire off Events and Email.  Fallback
  implies primary authentication is OOS, or system is compromised.  Never use it except
  emergency and live with the noise.  At least you can see the NMS while the primary auth system
  is offline.  It does make a nice honeypot for hackers or bored techs for us to spank. :)

  TFA is going to have to be a consideration as well.  This is going to require more investigation
  and likely will be a future option.  Dont get tricky and miss the basics.

  SAML is also going to have to be kept on the back burner for now.  While nice, it has its own
  complexities that simply cant be addressed quickly.  This is also going to require additional SSL
  certificates and templates to support different SAML types.

  The 'none' type would likely be a home system or when writing code in a sandbox
  environment where it would slow down the dev work.  Normally even a small company
  would have some kind of auth even if it is only 'local' or 'database'.




 The current system as of 11-21-2023 is using database for User storage.  This leverages
 PEPPER for password encryption, and feels secure.  Users do get an unencrypted password
 for a brief period while they are registering, but it is replaced once they set the initial
 password.  The unencrypted one will always fail for a login, so not too concerned about it existing
 for the time it takes a user to set a legit password.

 Later I will write the initial fallback of "local" to account for when someone does bad things
 and screws up their admin password, but that is future, and may never really be needed.

*/

$authTypes=['local','database','ldap','ad','sso','other','none'];
$authActive='database';                   // Where are we going to auth today?  (This likely should be templated for different auth types)
$authFallback='local';                    // What happens when DB is OOS?
$authTfa='none';                          // none, option, manditory (future)
$authTimer=30600;                         // 8.5 hour login time (in seconds)

/*
  ACCESS CONTROL VARIABLES (TODO)

  Currently a basic API is defined and working for AUTH.  Support for groups and JWT are working at a basic level

  Access Levels (0 through 100).  0 == unauth, 100 = full admin
  Still at the spitball stage right now.
  Logic:
    no authentication (login page only)                        (0, or null)                               (NONE)
    minimal access to see events                               (1-19)       // Trainee, new user, kiosks  (RO)
    Reporting and processing existing data(db reports)         (20-39)      // Business users + trainees  (RO) + W (new report template only)
    more access to ack events and see devices                  (40-59)      // NOC + managers             (RO) + W (Ack + ticket events)
    advanced access to disable monitors or suppress stuff      (60-79)      // managers + SME             (RW)     (Basic Monitoring Control)
    advanced access to add monitors and test them (caution)    (80-99)      // senior SME                 (RW)     (Advanced Monitoring Control)
    god. Config system, security, bless hosts                  (100)        // poor sucker who fixes mistakes ;)

    Outliers:
      Use keys for specific access to specific things:
       maintenance events (no expire, but no other access)
       triggers (INTERNAL These are time based, as they can SEND information, so caution is advised)
         reporting? Pager Duty?  Other services? Ticketing?
       Keys for scripts from unblessed hosts.
        This is going to be a problem and may never be coded.  Even with a key, this looks like a security weakness.

    Default UI:
      Business:   red-light/green-light minimal details (KISS)  (Customer facing visibility)
      KIOSK:      Pre-configured options minimal access         (hard coded options only)
      Reporting:  Reporting pages + events + history
      Technician: Full UI + event + history + monitors

    Future:
      Access levels further isolated by Application owners.  User can only screw up their own stuff.
*/
$accessLevelDefault=1;  // What do you want to do today?  Look or touch?
$defaultUi='business';  // Less critical on the API side, but will resrict data returned

/*
  TICKETING SYSTEM VARIABLES (TODO)
  Ticketing and event tracking outside of NMS
  Focus on in-house stuff for smaller companies
  Complex solutions like Jira would require a BIG template or plugin of some kind.
  This is also going to be based on templates so different companies can leverage within
  their systems with different solutions.
  Template location:
    templates/ticketing/$ticketingSystemName.php
*/
$ticketingSystemType='external';                                           // Internal is future possibility, not now.
$ticketingSystemUrl='http://ticketing.DOMAIN:9001/tickets';   // Used for POST in creating ticket.  Leverage with templates
$ticketingSystemName='Request_Tracker';                                    // Use a template
$ticketingSystemUser='fakeUser';
$ticketingSystemPassword='fakePass';
$ticketingSystemKey='1234fakeKey567890';

/*
  GRAPH VARIABLES (WIP)
  Graphing support is at MVP / Alpha class right now.
  Flesh this out more and document how to create templates better
  for the poor ops teams.

  RRD is expected to only exist on the API server so if oddball ports
  are used, adjust in the URL directly.  RRD can and is a PITA
  however it gives the most bang for the buck in my opinion.
*/
$rrdUrl=$apiUrl . ':' . $apiPort . '/static/';

// If using graphite set values here
$graphiteUrl='https://graphite.DOMAIN';
$graphitePort=443;

/*
  SECURITY VARIABLES (TODO)
  What to do in the event of attack or un-blessed apps attempting scrapes
  UI will have to have a way to unblock IP addresses as this will catch oddball
  scripts which are legit.  However some people will likely have this app
  accessable to the outside world no matter how bad of an idea it is.  Try like hell
  to keep these idiots safe too.

  We are going to need custom fail2ban definitions defined for this.  Dont forget.
*/
$defense='fail2ban';  // support: none, fail2ban, email, lockout (timer)

/*
  DEFENSE VARIABLES (TODO)
  Still spitballing stage.  Looking at setting a response level to tie into
  the above defense type.  The lower the level, the less of a response given.
  I am thinkng longer lockouts at higher levels, along with more notifications
  and finally full IP banning.

  Within a "secured" isolated LAN, a lower level makes sense, but in highly sensitive
  LANs a higher standard would make more sense.  Need to be able to support both via
  configuration.

  Important to note that bringing up a remote collector that does not send its blessed
  id values will lock it out of the system!

  Looking at levels 1 - 10
    Level 1:  Lock acct for X minutes
    Level 5:  Lock acct for Y minutes, Event, Email
    Level 10: ban IP address, Event, Lock acct (manual release only + new password), Email

*/
$defenseLevel='1';

/*
  BACKUP VARIABLES (TODO)
  Spitball stage again currently.  Looking into incremental, but likely given sizes we will
  be working with initially, full backups would be reasonable.
  Full backup implies we are storing rrd as well
  This is simply a dump of the database and possbly rrd and graphite (if on the same host?) data.
  Likely a streaming tar + gzip is the most reasonable solution here.
  Default to backup offhost, but if $backupHost not defined or empty, $backupLocation is expected to be local.
  filename will likely be backup_YYYY_MM_DD_HH_MM.tgz for simplicity.
*/
$backupTimer=86400;
$backupType='full';
$backupIdentification="date";
$backupLocation='/mnt/nas/foo/bar/';
$backupTransfer='rsync';
$backupHost='255.255.255.255';
$backupUser='fakeUser';
$backupPass='fakePass';
$backupKey='/path/to/ssh/id_rsa';

?>
