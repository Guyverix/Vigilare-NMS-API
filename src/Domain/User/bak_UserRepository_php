<?php
declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository {
    /**
     * @return User[]
     */
    public function findAll(): array;

    /**
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserOfId(int $id): User;
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
    public function updatePassword(string $username, string $password, string $pepper);
    /**
     * @param string $username
     * @param string $password
     * @param string $pepper
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
    public function activateAccount(string $username, string $password, $string $pepper);
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

    public function findEncrypt($pwd, $pepper);
    public function findHmac($pwd, $pepper);



}
