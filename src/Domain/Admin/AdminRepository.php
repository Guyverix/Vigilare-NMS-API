<?php
declare(strict_types=1);

namespace App\Domain\Admin;

require __DIR__ . '/../../../app/Database.php';


interface AdminRepository {
    /**
     * @return User[]
     */
    public function findAll(): array;

    /**
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserOfId(int $id): Admin;
    /**
     * @param string $username
     * @param string $password
     * @param string $pepper
     * @return User | null
    */
    public function findUserOfUsernamePassword(string $username, string $password, string $pepper);
    /**
     * @param string $username
     * @param string $password
     * @param string $pepper
     * @return array
    */
    public function resetPassword(string $username);
    /**
     * @param string $username
     * @return array
    */
    public function updatePassword(string $username, string $password, string $pepper);
    /**
     * @param string $username
     * @param string $password
     * @param string $pepper
     * @return array
    */
    public function setPassword(int $id, string $tid, string $password, string $pepper);  // called after email validation completed.
    /**
     * @param int $id
     * @param string $tid
     * @param string $password
     * @param string $pepper
     * @return array
    */
    public function resetPasswordConfirm(int $id, string $tid, string $password, string $pepper);  // called after email validation completed.
    /**
     * @param int $id
     * @param string $tid
     * @param string $password
     * @param string $pepper
     * @return array
    */
    public function updatePasswordUsers(string $username, string $password, string $oldPassword, string $pepper);
    /**
     * @param string $username
     * @param string $password
     * @param string $pepper
     * @return array
    */
    public function registerUser(array $registerUser);
    /**
     * @param array $createUser
     * @return array
    */
    public function updateUser(array $updateUser);
    /**
     * @param array $createUser
     * @return array
    */
    public function createUser(array $username, string $pepper);
    /**
     * @param string $username
     * @param string $pepper
     * @return array
    */
    public function activateAccount(string $username);
    /**
     * @param string $username
     * @param string $password
     * @param string $pepper
     * @return array
    */
    public function deactivateAccount(string $username);
    /**
     * @param string $username
     * @param string $password
     * @return array
    */
    public function validateAccount(string $username, string $password);
    /**
     * @param int $id
     * @return array
    */
    public function deleteUser(int $id);

//    private function findEncrypt($pwd, $pepper);
//    public function findHmac($pwd, $pepper);
}
