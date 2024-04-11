<?php

namespace App\Provider\Cognito;

//use pmill\AwsCognito\CognitoClient;
use pmill\AwsCognito\Exception\ChallengeException;
use \PDO;
use TFCLog\TFCLogger;
use App\Provider\Cognito\RealCognitoClient;
use App\Domain\Base\CognitoAccount;
use App\Domain\Base\LoginCognitoAccount;
use App\Exception\InvalidObjectException;
use App\Exception\InvalidLogException;



class RealCognitoProvider implements CognitoProvider {

    private $adminpdo;
    private $logger;
    private $cognitoClient;
    private $fallback;

    private $cognitoFields = [
        'name' => 'Name',
        'email' => 'Email',
        'custom:company' => 'Company',
        'custom:sfaccountid' => 'SFAccountId',
        'custom:sfcontactid' => 'SFContactId',
        'status' => 'Status'
    ];

    public function __construct(PDO $adminpdo, TFCLogger $logger, RealCognitoClient $cognitoClient)
    {
        $this->adminpdo = $adminpdo;
        $this->logger = $logger;
        $this->cognitoClient = $cognitoClient;
    }

    public function getCognitoUsers(
        string $group = null
    ): Array {
        try {
            //ToDo: Cognito getList doesn't exist on the CognitoClient currently in use
            return [];
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function getCognitoUser(
        string $username
    ): CognitoAccount {
        try {
            //result coming from AWS Cognito
            $result = $this->cognitoClient->getUser($username);

            $userAttributes = [];
            foreach ($result['UserAttributes'] as $attribute) {
                $userAttributes[$attribute['Name']] = $attribute['Value'];
            }

            $account = new CognitoAccount(
                $result['Username'],
                new \PVO\EmailAddress($userAttributes['email']),
                $result['UserStatus']
            );

            return $account;
        } catch (\pmill\AwsCognito\Exception\CognitoResponseException $e) {
            $message = $e->getPrevious()->getMessage();
            if (strpos($message, 'UserNotFoundException') !== false) {
                throw new \pmill\AwsCognito\Exception\UserNotFoundException($e->getPrevious());
            }
        } catch (\Exception $e) {
//            dd("Exceotuib: ".$e->getMessage());
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function createCognitoAccount(
        Array $data
    ): CognitoAccount {
        try {
            $result = $this->cognitoClient->registerUser(
                $data['Username'],
                $data['Password'],
                [
                    'name' => $data['Name'],
                    'email' => $data['Email'],
                    'custom:company' => $data['Company'],
                    'custom:sfaccountid' => $data['SFAccountId'],
                    'custom:sfcontactid' => $data['SFContactId']
                ]
            );

            $this->cognitoClient->adminConfirmSignUp($data['Username']);

            $account = $this->getCognitoUser($data['Username']);
            return $account;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function confirmCognitoRegistration(
        Array $data
    ): Array {
        try {
            $result = $this->cognitoClient->confirmUserRegistration(
                $data['confirmationCode'],
                $data['username']
            );
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function forgotCognitoPassword(
        Array $data
    ): Array {
        try {
            $return = $this->cognitoClient->sendForgottenPasswordRequest(
                $data['username']
            );
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function updateCognitoPassword(
        Array $data
    ): CognitoAccount {
        try {
            $authResponse = $this->cognitoClient->authenticate(
                $data['username'],
                $data['password']
            );
            $accessToken = $authResponse['AccessToken'];
            return $this->cognitoClient->changePassword(
                    $accessToken,
                    $data['password'],
                    $data['newPassword']
            );
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function loginCognito(
        Array $data
    ): LoginCognitoAccount {
        
        try {
            $return = $this->cognitoClient->authenticate(
                $data['Username'],
                $data['Password']
            );

            $account = new LoginCognitoAccount(
                new \PVO\EmailAddress($data['Username']),
                $return
            );

            $username = $account->email->value();
            $status = $account->status;

            // Register cognito login into db
            $this->registerCognitoLogin($username, $status);

            return $account;                

        } catch (ChallengeException $e) {
            if ($e->getChallengeName() === CognitoClient::CHALLENGE_NEW_PASSWORD_REQUIRED) {
                return $this->cognitoClient->respondToNewPasswordRequiredChallenge(
                        $data['Username'],
                        'password_new',
                        $e->getSession()
                );
            }
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function logoutCognito(
        Array $data
    ): bool {
        d("Reached at logoutCognito");
        d($data);
        try {
            //ToDo
            return $data;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function updateCognitoUser(
        Array $data
    ): CognitoAccount {
        try {
            return $this->cognitoClient->updateUserAttributes(
                    $data['username'],
                    [
                        'name' => $data['Name'],
                        'email' => $data['Email'],
                        'custom:company' => $data['Company'],
                        'custom:sfaccountid' => $data['SFAccountId'],
                        'custom:sfcontactid' => $data['SFContactId']
                    ]
            );
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function deleteCognitoUser(
        Array $data
    ): Array {
        try {
            $authResponse = $this->cognitoClient->authenticate(
                $data['username'],
                $data['password']
            );
            $accessToken = $authResponse['AccessToken'];
            $return = $this->cognitoClient->deleteUser($accessToken);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    /**
     * Check if cognito account for this email exists or not
     * @param string $username
     * @return bool
     */
    public function userExists($username): bool {
        try {
//                    d('in user exists '.$username);
            $cognitoUser = $this->getCognitoUser($username);
//            dd($cognitoUser);
            if ($cognitoUser) {
                return true;
            }
        } catch (\pmill\AwsCognito\Exception\UserNotFoundException $e) {
//            d("User does not exists");
            return false;
        }

        return false;
    }

    /**
     * Generate Cognito username from email
     * @param string $email
     * @return string
     */
    public function generateUsername($email): string {
        return md5(strtolower(trim($email)));
    }

    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param CognitoProvider $fallback
     */
    public function attach(CognitoProvider $fallback) {
        $this->fallback = $fallback;
    }

    /**
     * Login into database cognito user login
     */

    public function registerCognitoLogin(string $username, array $status) : string
    {
        d("Reached at registerCognitoLogin");
        d($status);
        try {
            $sql = "CALL cognitoLogin(:username, :access_token, refresh_token, :id_token)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":username", $username, PDO::PARAM_STR);
            $stmt->bindValue(":access_token", $status['AccessToken'], PDO::PARAM_STR);
            $stmt->bindValue(":refresh_token", $status['RefreshToken'], PDO::PARAM_STR);
            $stmt->bindValue(":id_token", $status['IdToken'], PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            dd("Res: " . $res);

            if (count($res)!==1) {
                throw new InvalidLogException();
            }
            return $res[0]['id'];
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

}
