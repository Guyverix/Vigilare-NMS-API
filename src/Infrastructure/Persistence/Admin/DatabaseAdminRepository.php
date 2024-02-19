<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Admin;

use App\Domain\Admin\Admin;
use App\Domain\Admin\AdminNotFoundException;
use App\Domain\Admin\AdminRepository;
use Database;

/*
  This is almost a lift-and-shift from User, however
  we are going to only be using certain parts and this
  will get cleaned up once we iron out things like access
  a little better.  As an admin, you are god, and are likely
  not going to need to use the email function unless signup
  is not allowed.  This should be a good solution to have
  a human get the new user built out, and set initial
  access levels except the password itself (email style), or
  setting the initial password for the user.  This does not
  support a change on first login currently.  This is not a
  commercial/public site, so not sure that is needed.
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


class DatabaseAdminRepository implements AdminRepository {
  private $db;
  private $storedHash;
  private $mail;

  public function __construct() {
    $this->db = new Database();
    //    $this->mail = new PHPMailer(true); // DEBUG MODE
    $this->mail = new PHPMailer();
  }

  // encrypt our passwords using pepper not salt
  // https://www.php.net/manual/en/function.password-hash.php
  public function findEncrypt($pwd, $pepper) {
    $pwd_peppered = hash_hmac("sha256", $pwd, $pepper);
    $pwd_hashed = password_hash($pwd_peppered, PASSWORD_BCRYPT);
    return $pwd_hashed;
  }

  // for the password_verify we only need HMAC, not the encrypted password
  // for validation of correctness
  public function findHmac($pwd, $pepper) {
    $pwd_peppered = hash_hmac("sha256", $pwd, $pepper);
    return $pwd_peppered;
  }

  function generateRandomString($length = 10) {
    $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $res = substr(str_shuffle(str_repeat($x, (int) ceil($length/strlen($x)) )),1,$length);
    return $res;
  }

  // register account email
  function sendMail($data) {
    try {
    if ( ! isset($data['userName']) && isset($data['userId'])) {
      $data['userName'] = $data['userId'];
    }
    // Sucks to do this, but dont need users and passwords everywhere.
    require __DIR__ . ("/../../../../app/config.php");
    // Server settings
    // $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;    //Enable verbose debug output
    $this->mail->isSMTP();                             //Send using SMTP
    $this->mail->Host        = $emailSmtp;             //Set the SMTP server to send through
    $this->mail->SMTPAuth    = $emailSMTPAuth;         //Enable SMTP authentication
    $this->mail->Username    = $emailLogin;            //SMTP username
    $this->mail->Password    = $emailPassword;         //SMTP password
    $this->mail->SMTPAutoTLS = $emailSMTPAutoTLS;      //StartTLS option (check EHLO to see if needed)
    $this->mail->SMTPSecure  = $emailSMTPSecure;       //Secure mode, AFAIK plain and secure cannot both be set
    $this->mail->AuthType    = $emailAuthType;         //Set explcit authentication type
    $this->mail->Port        = $emailPort;             //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    // $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         //Enable implicit TLS encryption causes heartburn in tests

    // Recipients
    $this->mail->setFrom($emailFromAddress, $emailFromName);
    $this->mail->addAddress($data['email'], $data['realName']);     //Add a recipient
    $this->mail->addReplyTo($emailReplyToAddress, $emailReplyToName);

    // Content
    $this->mail->isHTML(true);                                      //Set email format to HTML
    $this->mail->Subject  = 'New user account creation activation link';
    $this->mail->Body     = 'Welcome, ' . $data['realName'] . '!<br>An account was just created for you with the login name of "' . $data['userName']. '".<br><br>Please follow the provided link to set your login password.<br><br>';
    $this->mail->Body    .= '<a href="' . $data['frontendUrl'] . '/user/registerActivate.php?id=' . $data['id'] . '&tpw=' . $data['tpw'] . '">Account Activation Link </a><br>';
    $this->mail->AltBody  = 'Welcome, ' . $data['realName'] . '!  Please use the provided link to set your login password.\n';
    $this->mail->AltBody .= 'Paste this link in a browser: ' . $data['frontendUrl'] . "/user/registerActivate.php?id=" . $data['id'] . "&tpw=" . $data['tpw'] . "\n";

    $this->mail->send();
    return 0;
    }
    catch (Exception $e) {
      return "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
    }
  }

  // Internal only function to retrieve the temp password that we have set for the user
  function getTpw($arr) {
    $this->db->prepare("SELECT userPass FROM users WHERE id= :id LIMIT 1");
    $this->db->bind('id', $id);
    $data = $this->db->resultset();
    return $data;
  }

  // reset password email
  function sendMailReset($data) {
    try {
    // Sucks to do this, but dont need users and passwords everywhere.
    require __DIR__ . ("/../../../../app/config.php");
    //Server settings
    // $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;    //Enable verbose debug output
    $this->mail->isSMTP();                             //Send using SMTP
    $this->mail->Host        = $emailSmtp;             //Set the SMTP server to send through
    $this->mail->SMTPAuth    = $emailSMTPAuth;         //Enable SMTP authentication
    $this->mail->Username    = $emailLogin;            //SMTP username
    $this->mail->Password    = $emailPassword;         //SMTP password
    $this->mail->SMTPAutoTLS = $emailSMTPAutoTLS;      //StartTLS option (check EHLO to see if needed)
    $this->mail->SMTPSecure  = $emailSMTPSecure;       //Secure mode, AFAIK plain and secure cannot both be set
    $this->mail->AuthType    = $emailAuthType;         //Set explcit authentication type
    $this->mail->Port        = $emailPort;             //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    // $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         //Enable implicit TLS encryption causes heartburn in tests

    //Recipients
    $this->mail->setFrom($emailFromAddress, $emailFromName);
    $this->mail->addAddress($data['email'], $data['realName']);     //Add a recipient
    $this->mail->addReplyTo($emailReplyToAddress, $emailReplyToName);

    //Content
    $this->mail->isHTML(true);                                      //Set email format to HTML
    $this->mail->Subject  = 'Existing user password reset requested';
    $this->mail->Body     = '!!! WARNING !!!!<br>An account password reset request created for your account.  If you did not request this, please ignore this email.<br><br>Please follow the provided link to reset your login password if you requested a reset.<br><br>';
    $this->mail->Body    .= 'This link will expire in 24 hours<br>';
    $this->mail->Body    .= '<a href="' . $frontendUrl . '/login/passwordReset.php?id=' . $data['id'] . '&tpw=' . $data['tpw'] . '">Password Reset Link </a><br>';
    $this->mail->AltBody  = '!!! WARNING !!!!\n\nAn account password reset request created for your account.  If you did not request this, please ignore this email.\n\nPlease follow the provided link to reset your login password if you requested a reset.\n\n';
    $this->mail->AltBody .= "This link will expire in 24 hours\n\n";
    $this->mail->AltBody .= 'Paste this link in a browser: ' . $frontendUrl . "/login/passwordReset.php?id=" . $data['id'] . "&tpw=" . $data['tpw'] . "\n";

    $this->mail->send();
    if ( $this->mail->ErrorInfo !== '' || ! empty($this->mail->ErrorInfo) ) {  // The try catch does not always catch the damn thing
      return "Find Mailer Error: {$this->mail->ErrorInfo}";
    }
    //return var_dump($this->mail);
    return 0;
    }
    catch (Exception $e) {
      return "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
    }
  }

  // OPER access or better
  public function findUserOfId(int $id): Admin {
    $this->db->prepare("SELECT id, userId, email, realName, createdAt, accessList, enable FROM users WHERE id= :id LIMIT 1");
    $this->db->bind('id', $id);
    $data = $this->db->resultset();
    return $data;
  }

  // ADMIN ONLY
  public function findUsersAll(): array {
    $this->db->prepare("SELECT id, userId, email, realName, createdAt, timer, accessList, enable FROM users");
    $data = $this->db->resultset();
    return $data;
  }

  public function findAll(): array {
    $this->db->prepare("SELECT id, userId, email, realName, createdAt, timer, accessList, enable FROM users");
    $data = $this->db->resultset();
    return $data;
  }

  // check if pass is correct.  We use password_verify against HMAC, not the password itself
  public function findUserOfUsernamePassword(string $username, string $password, string $pepper) {
    $hashPass = $this->findHmac($password, $pepper);               // HMAC not encrypted value
    $this->db->prepare("SELECT userPass FROM users WHERE userId= :userId1 AND enable= 1 LIMIT 1");
    $this->db->bind('userId1', $username);
    $storedHash = $this->db->resultset();
    $row = $this->db->rowCount();
    if ( $row > 0 ) {
      $storedHash = json_decode(json_encode($storedHash,1),true);    // from an object to an array
      $cleanHash = array_values($storedHash);
    }
    else {
      return null;
    }
    // If we have a valid password for the userid, continue
    if ( password_verify($hashPass, $cleanHash[0]['userPass'])) {
      $this->db->prepare("SELECT id, userId, email, realName, createdAt, accessList, timer FROM users WHERE userId= :userId LIMIT 1");
      $this->db->bind('userId', $username);
      $data = $this->db->resultset();
      $row = $this->db->rowCount();
      if ( $row > 0 ) {
        return $data;
      }
      else {
        return null;
      }
    }
    return null;
  }


  // Use caution, nice vector for attack
  // Never screw with a user account directly.  Create a userId_reset name, and go from there
  public function resetPassword(string $username) {
    $this->db->prepare("SELECT * FROM users WHERE userId= :userId LIMIT 1");
    $this->db->bind('userId', $username);
    $userData = $this->db->resultset();
    $userData = json_decode(json_encode($userData,1),true);
    $row = $this->db->rowCount();
    if ( $row == 0 ) {
      return ['FAILURE - No email address associated with user name ' . $username];
    }
    $tpw = $this->generateRandomString(40);
    $this->db->prepare("INSERT INTO users VALUES('', :userId2, :email, :realName, :userPass, now(), 0, :accessList , 0)");
    $this->db->bind('userId2', $userData[0]['userId'] . '_reset');
    $this->db->bind('accessList', $userData[0]['userId']);  // This is how we know what userId is really being reset
    $this->db->bind('email', $userData[0]['email']);
    $this->db->bind('realName', $userData[0]['realName']);
    $this->db->bind('userPass', $tpw);
    $this->db->execute();
    if (! empty($this->db->error)) {
      return ['FAILURE - Unable to set user password.  Contact admin.', $this->db->error];
    }
    else {
      // Reetrieve the "fake" user info we created for a valid id number
      $this->db->prepare("SELECT id, userId, email, realName, userPass AS tpw, accessList FROM users WHERE accessList= :accessList2 AND email= :email2");
      $this->db->bind('accessList2', $userData[0]['userId']);
      $this->db->bind('email2', $userData[0]['email']);
      $this->db->execute();
      $userData2 = $this->db->resultset();
      $userData2 = json_decode(json_encode($userData2,1),true);

      $mailResult = $this->sendMailReset($userData2[0]);
      if ($mailResult == 0) {
        return ["Reset password email has been sent", $mailResult ];
      }
      else {
        // If we cannot send the email, then nuke the row we just created
        $this->db->prepare("DELETE FROM users WHERE id= :id2");
        $this->db->bind('id2', $userData2[0]['id']);
        $this->db->execute();

        return ['FAILURE - Email was unable to be sent for '. $userData[0]['userId'] . '.  Inform admin to repair SMTP email services.', $userData2, $mailResult];
      }
    }
  }

  // Initial query against the "fake" userid, for confirmation
  // THEN we update the real userId that we stored in the accessList column
  // finally, delete the temporary account that was created
  public function resetPasswordConfirm(int $id, string $tpw, string $password, string $pepper) {
    $this->db->prepare("SELECT id FROM users WHERE userId=(SELECT accessList FROM users WHERE id= :id AND userPass= :userPass)");  //will return the real userId
    $this->db->bind('id', $id);
    $this->db->bind('userPass', $tpw);
    $realId = $this->db->resultset();
    $realId = json_decode(json_encode($realId,1),true);
    $row = $this->db->rowCount();
    if ( $row == 0 ) {
      return ['FAILURE - Unable to find validation values to allow the password reset', $this->db->error];
    }
    // We have a real result to change.  Set the new password values here
    $hashPass = $this->findEncrypt($password, $pepper);
    $this->db->prepare("UPDATE users SET userPass= :userPass2 WHERE id= :id2");
    $this->db->bind('userPass2', $hashPass);
    $this->db->bind('id2', $realId[0]['id']);
    $this->db->execute();
    if (! empty($this->db->error)) {
      return ['FAILURE - Unable to set user password.  Contact admin.', $this->db->error];
    }
    // New password has been set, now delete the old temp account created
    $this->db->prepare("DELETE from users WHERE id= :id3");
    $this->db->bind('id3', $id);  // the initial FAKE account
    $this->db->execute();
    if (! empty($this->db->error)) {
      return ['FAILURE - Unable to remove temporary validation settings.  Contact admin.', $this->db->error];
    }
    return ["Password has been reset"];
  }

  public function setPassword(int $id, string $tpw, string $password, string $pepper) {
    $this->db->prepare("SELECT userPass FROM users WHERE id= :id AND userPass= :tpw");
    $this->db->bind('id', $id);
    $this->db->bind('tpw', $tpw);
    $this->db->execute();
    $row = $this->db->rowCount();
    if ( $row == 0 ) {
      return ['FAILURE - Userid does not contain correct tpw value', $this->db->error];
    }
    else {
      // We confirmed that they know the tid value and want to set their password.
      $hashPass = $this->findEncrypt($password, $pepper);
      $this->db->prepare("UPDATE users SET userPass= :userPass2 WHERE id= :id2");
      $this->db->bind('id2', $id);
      $this->db->bind('userPass2', $hashPass);
      $this->db->execute();
      if (! empty($this->db->error)) {
        return ['FAILURE - Unable to set user password.  Contact admin.', $this->db->error];
      }
      else {
        return ['Password set for id ' . $id];
      }
    }
  }

  // single user password change.  access level admin.
  // only admins should be able to do this.  Think about the logic needed and security
  public function updatePassword(string $username, string $password, string $pepper) {
    $hashPass = $this->findEncrypt($password, $pepper);
    $this->db->prepare("UPDATE users SET userPass= :userPass WHERE userId= :userId");
    $this->db->bind('userId', $username);
    $this->db->bind('userPass', $hashPass);
    $this->db->execute();
    if ( ! empty($this->db->error)) {
      return ['FAILURE - Unable to change user password at admin request.', $this->db->error];
    }
    return ["User " . $username . " password was changed at admin request"];
  }

  // only self.  Must know old password to change to new one, this needs more security
  // bad people could mess with this.
  // option?  a uuid in database that is added to user cookie to validate?
  // value should change on login so invaidating value would invalidate jwt when checked?
  public function updatePasswordUsers(string $username, string $password, string $oldPass, string $pepper) {
    $hashPass = $this->findEncrypt($password, $pepper);            // new pass hash created
    $hashPassOld = $this->findHmac($oldPass, $pepper);            // verify against this one before changes
    $this->db->prepare("SELECT userPass FROM users WHERE userId= :userId1 LIMIT 1");
    $this->db->bind('userId1', $username);
    $storedHash = $this->db->resultset();
    $row = $this->db->rowCount();
    if ( $row == 0 ) {
      return ['FAILURE - failed to find user ' . $username, $storedHash];
    }
    $storedHash = json_decode(json_encode($storedHash,1),true);    // from an object to an array
    $cleanHash = array_values($storedHash);
    // If we have a valid password for the userid, continue
    if ( password_verify($hashPassOld, $cleanHash[0]['userPass'])) {
      $this->db->prepare("UPDATE users SET userPass= :userPass WHERE userId= :userId");
      $this->db->bind('userId', $username);
      $this->db->bind('userPass', $hashPass);
      $this->db->execute();
      if ( ! empty($this->db->error)) {
        return ['FAILURE - Unable to update password for user.', $this->db->error];
      }
      return ["User " . $username . " password was changed at user request."];
    }
    else {
      return ["FAILURE - Failed to update password for " . $username . " old password is incorrect.", 'no we are not going to show the passwords attempted, duh'];
    }
  }

  // Caution, default can be active at creation time if enable is set
  // This is an admin only function.  This bypasses temp passwords and sets the legit value
  public function createUser($newUser, $pepper) {
    $hashPass = $this->findEncrypt($newUser['userPass'], $pepper);
    if ( ! isset($newUser['enable'])) { $newUser['enable'] = 0; }  // Default disable if not defined, silly admin
    if ( ! isset($newUser['timer'])) { $newUser['timer'] = 8; }    // Give ability to set different login times
    $this->db->prepare("INSERT INTO users VALUES( null, :userId, :email, :realName, :userPass, now(), :timer, :accessList, :enable)");
    $this->db->bind('userId', $newUser['userId']);
    $this->db->bind('email', $newUser['email']);
    $this->db->bind('realName', $newUser['realName']);
    $this->db->bind('userPass', $hashPass);
    $this->db->bind('accessList', $newUser['accessList']);
    $this->db->bind('enable', $newUser['enable']);
    $this->db->bind('timer', $newUser['timer']);
    $this->db->execute();
    if ( ! empty($this->db->error)) {
      return ['FAILURE - Unable to create user.  Confirm user does not already exist.', $this->db->error];
    }
    return ["User " . $newUser['userId'] . " created."];
  }

  // This is used in tandem with an email so an account can be setup
  public function registerUser($newUser) {
    if ( ! isset($newUser['enable'])) { $newUser['enable'] = 0; }
    if ( ! isset($newUser['timer']))  { $newUser['timer'] = 8;  }
    if ( ! isset($newUser['accessList'])) { $newUser['accessList'] = 'user'; }
    if ( ! isset($newUser['userName']) && isset($newUser['userId'])) { $newUser['userName'] = $newUser['userId']; }
    $hashPass = $this->generateRandomString(40);      // This is not usable for login!  This is kind of a temp password to validate email
    $this->db->prepare("INSERT INTO users VALUES( null, :userId, :email, :realName, :userPass, now(), :timer, :accessList, :enable)");
    $this->db->bind('userId', $newUser['userName']);
    $this->db->bind('email', $newUser['email']);
    $this->db->bind('realName', $newUser['realName']);
    $this->db->bind('userPass', $hashPass);
    $this->db->bind('accessList', $newUser['accessList']);
    $this->db->bind('enable', $newUser['enable']);
    $this->db->bind('timer', $newUser['timer']);
    $this->db->execute();
    if ( ! empty($this->db->error)) {
      return ['FAILURE - Unable to register user name ' . $newUser['userName'] . '.  Check if it has already been created', $this->db->error];
    }
    // Get new ID value to pass to email
    $this->db->prepare("SELECT * FROM users WHERE userPass= :userPass2 LIMIT 1");
    $this->db->bind('userPass2', $hashPass);
    $data = $this->db->resultset();
    $data = json_decode(json_encode($data,1), true);
    $newUser['id'] = $data[0]['id'];
    $newUser['tpw'] = $hashPass;
    $mailResult = $this->sendMail($newUser);
    if ($mailResult == 0) {
      return ["User " . $newUser['userName'] . " registered.  Password will still have to be set, and possible enable of account by admin."];
    }
    else {
      return ['FAILURE - Email was unable to be sent, however account was created for '. $newUser['userName'], $newUser, $mailResult];
    }
  }

  // Cannot update password filtered by id
  public function updateUser($updateUser) {
    $this->db->prepare("UPDATE users SET email= :email, realName= :realName, userId= :userId, accessList= :accessList, enable= :enable  WHERE id= :id");
    $this->db->bind('userId', $updateUser['userId']);
    $this->db->bind('email', $updateUser['email']);
    $this->db->bind('realName', $updateUser['realName']);
    $this->db->bind('accessList', $updateUser['accessList']);
    $this->db->bind('id', $updateUser['id']);
    $this->db->bind('enable', $updateUser['enable']);
    $this->db->execute();
    if ( ! empty($this->db->error)) {
      return ['FAILURE - Unable to update user values.', $this->db->error];
    }
    return ["User id " . $updateUser['id'] . " values were updated"];
  }

  // ADMIN
  public function deleteUser(int $id) {
    $this->db->prepare("DELETE FROM users WHERE id= :id");
    $this->db->bind('id', $id);
    $this->db->execute();
    if ( ! empty($this->db->error)) {
      return ['FAILURE - Unable to delete user.', $this->db->error];
    }
    else {
      $this->db->prepare("SELECT * FROM users WHERE id= :id2");
      $this->db->bind('id2', $id);
      $data = $this->db->execute();
      $row2 = $this->db->rowCount();
      if ( $row2 > 0 ) {
       return ['FAILURE - Delete command failed.  Unable to delete user.', $data];
      }
    }
    return ["User id " . $id . " was deleted"];
  }

  // User account allow login
  public function activateAccount(string $username) {
    $this->db->prepare("UPDATE users SET enable=1 WHERE userId= :userId");
    $this->db->bind('userId', $username);
    $this->db->execute();
    if ( ! empty($this->db->error)) {
      return ['FAILURE - Unable to activate account.'];
    }
    else {
      $this->db->prepare("SELECT * FROM users WHERE userId= :userId2 AND enable=1");
      $this->db->bind('userId2', $username);
      $data = $this->db->execute();
      $row2 = $this->db->rowCount();
      if ( $row2 == 0 ) {
        return ['FAILURE - UserId ' . $username . ' is not enabled', $data];
      }
    }
    return ["User id " . $username . " account enabled"];
  }

  // keep account but no login allowed
  public function deactivateAccount(string $username) {
    $this->db->prepare("UPDATE users SET enable=0 WHERE userId= :userId");
    $this->db->bind('userId', $username);
    $this->db->execute();
    if ( ! empty($this->db->error)) {
      return ['FAILURE - Unable to deactivate account.', $this->db->error];

    }
    else {
      $this->db->prepare("SELECT * FROM users WHERE userId= :userId2 AND enable=0");
      $this->db->bind('userId2', $username);
      $data = $this->db->execute();
      $row2 = $this->db->rowCount();
      if ( $row2 == 0 ) {
        return ['FAILURE - UserId ' . $username . ' cannot confirm username was disabled correctly', $data];
      }
    }
    return ["User id " . $username . " account disabled"];
  }

  // confirm user knows info we provided for registering
  public function validateAccount(string $username, string $password) {
  }
} // end class

