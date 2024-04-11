<?php

namespace App\Provider\Cognito;

use pmill\AwsCognito\CognitoClient;

class RealCognitoClient extends CognitoClient {

    public function adminConfirmSignUp($username) {
        try {
            $this->client->adminConfirmSignUp([
                'UserPoolId' => $this->userPoolId,
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            throw CognitoResponseException::createFromCognitoException($e);
        }
    }

}
