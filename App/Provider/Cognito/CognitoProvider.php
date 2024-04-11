<?php

namespace App\Provider\Cognito;

use App\Domain\Base\CognitoAccount;
use App\Domain\Base\LoginCognitoAccount;

interface CognitoProvider {

    public function getCognitoUsers(string $group = null): Array;

    public function getCognitoUser(string $username): CognitoAccount;

    public function createCognitoAccount(Array $data): CognitoAccount;

    public function confirmCognitoRegistration(Array $data): Array;

    public function forgotCognitoPassword(Array $data): Array;

    public function updateCognitoPassword(Array $data): CognitoAccount;

    public function loginCognito(Array $data): LoginCognitoAccount;

    public function logoutCognito(Array $data): bool;

    public function updateCognitoUser(Array $data): CognitoAccount;

    public function deleteCognitoUser(Array $data): Array;

    public function userExists(string $username): bool;

    public function generateUsername(string $email): string;

    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param CognitoProvider $fallback
     */
    public function attach(CognitoProvider $fallback);
}
