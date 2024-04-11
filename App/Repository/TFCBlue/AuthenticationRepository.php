<?php

namespace App\Repository\TFCBlue;

use App\Provider\Cognito\CognitoProvider;
use App\Provider\Account\AccountProvider;
use App\Provider\Contact\ContactProvider;
use App\Provider\Id\IdProvider;
use App\Domain\TFCBlue\Authentication\ForgotPasswordRequest;
use App\Domain\TFCBlue\Authentication\LoginRequest;
use App\Domain\TFCBlue\Authentication\LogoutRequest;
use App\Domain\TFCBlue\Authentication\RegistrationRequest;
use App\Domain\TFCBlue\Authentication\AuthorizationResponse;


class AuthenticationRepository
{
    private $cognitoProvider;
    private $accountProvider;
    private $contactProvider;
//    private $clientProvider;
    private $idProvider;

    private $samlStructure = [
        'name' => 'name',
        'company' => 'company',
        'email' => 'email',
        'password' => 'password',
        'repeatpassword' => 'repeatpassword'

    ];
    
    public function __construct(
        CognitoProvider $cognitoProvider, 
        AccountProvider $accountProvider, 
        ContactProvider $contactProvider, 
        IdProvider $idProvider)
    {
        $this->cognitoProvider = $cognitoProvider;
        $this->accountProvider = $accountProvider;
        $this->contactProvider = $contactProvider;
        $this->idProvider = $idProvider;
        
     }

    public function forgotPassword(ForgotPasswordRequest $request) : void
    {
        //should send an email
    } 

    public function login(LoginRequest $request) : \App\Domain\Base\LoginCognitoAccount
    {
        try {
            $requestData = $request->getData();

            $cognitoData = [
                'Username' => $requestData['email'],
                'Password' => $requestData['password']
            ];

            // Check if cognito user exits.
            if (empty($this->cognitoProvider->userExists($request->email))) {
                throw new \App\Domain\TFCBlue\Authentication\Exception\UserDoesNotExist();
            }

            // Login to cognito
            $account = $this->cognitoProvider->loginCognito($cognitoData);

            return $account;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
        
    }

    public function logout(LogoutRequest $request) : void
    {
        try {
            $requestData = $request->getData();

            $cognitoData = [
                'cognitoId' => $requestData['cognitoId']
            ];

            d($cognitoData);
            //logout from cognito
            $this->cognitoProvider->logoutCognito($cognitoData);
        } catch (\Eception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
        
    }

    public function register(RegistrationRequest $request): \App\Domain\Base\CognitoAccount {

        try {
            $cognitoUserName = $this->cognitoProvider->generateUsername($request->email);
//            $cognitoUserName = 'yolanda.fernandez@thefirstclub.com';
//            $cognitoUserName = 'wasimasif@gmail18.com';

            /**
             * Check if cognito user exits.
             * For email verified we get it by email
             * For those who have not confirmed email we get it by username
             */
            if ($this->cognitoProvider->userExists($request->email) || $this->cognitoProvider->userExists($cognitoUserName)) {
                throw new \App\Domain\TFCBlue\Authentication\Exception\UserAlreadyExists();
            }

            $contactDetails = [
                'name' => $request->name,
                'email' => $request->email,
                'company' => $request->company,
            ];

            $sfData = $this->createSFDetails($contactDetails);

            $cognitoData = [
                'Username' => $cognitoUserName,
                'Password' => $request->password,
                'Name' => $request->name,
                'Email' => $request->email,
                'Company' => $request->company,
                'SFAccountId' => $sfData['account'],
                'SFContactId' => $sfData['contact'],
            ];

            $account = $this->cognitoProvider->createCognitoAccount($cognitoData);

            return $account;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    /**
     * returns the SF account and contact details.
     * if account does not exist it will create SF account/contact
     * @param type $data
     */
    private function createSFDetails($data): Array {

        $contact = $this->contactProvider->getSFContact($data['email']);
        // contact found so we have the account id and contact id both
        if (count($contact) > 0) {
            $accountId = $contact['owner'];
            $contactId = $contact['id'];
            $return['account'] = $accountId;
            $return['contact'] = $contactId;
            return $return;
        }

        $accountId = $contactId = null;
        try {
            $account = $this->accountProvider->getSFAccount($data['company']);
            $accountId = $account['id'];
        } catch (\App\Domain\TFCBlue\Authentication\Exception\SFNotFound $e) {
            // do nothing account not found
        }

        if (null == $accountId) {
            // create account
            $accountData = [
                'Name' => $data['company'],
            ];
            try {
                $accountId = $this->accountProvider->createSFAccount($accountData);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        $name = explode(' ', $data['name']);
        $firstName = $name[0];
        unset($name[0]);
        $lastName = implode(' ', $name);
        $contactData = [
            'AccountId' => $accountId,
            'Email' => $data['email'],
            'FirstName' => $firstName,
            'LastName' => $lastName,
        ];

        $contactId = $this->contactProvider->createSFContact($contactData);

        return [
            'account' => $accountId,
            'contact' => $contactId,
        ];
    }

}