<?php
use App\Action;
use Psr\Container\ContainerInterface as c;
use Slim\App;

// Defining action creation here to better specify dependencies
(function (App $app) {
    $c = $app->getContainer();

    //Test Action
    $c[Action\Test::class] = function (c $c) {
        return new Action\Test($c->get('pdologger'), $c->get('settings'));
    };

    //Version Action
    $c[Action\Version::class] = function (c $c) {
        return new Action\Version($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('uuid'));
    };

    //EndUser Actions
    $c[Action\API\EndUser\Get::class] = function (c $c) {
        return new Action\API\EndUser\Get($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('endUserRepository'));
    };
    $c[Action\API\EndUser\ListUsers::class] = function (c $c) {
        return new Action\API\EndUser\ListUsers($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('endUserRepository'));
    };
    $c[Action\API\EndUser\SignOn::class] = function (c $c) {
        return new Action\API\EndUser\SignOn($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('endUserRepository'));
    };
    
    $c[Action\TFCBlue\Register::class] = function (c $c) {
        return new Action\TFCBlue\Register($c->get('pdologger'), $c->get('settings'), $c['tfcBlueAuthenticationRepository']);
    };

    $c[Action\TFCBlue\Login::class] = function (c $c) {
        return new Action\TFCBlue\Login($c->get('pdologger'), $c->get('settings'), $c->get('tfcBlueAuthenticationRepository'));
    };

    $c[Action\TFCBlue\Logout::class] = function (c $c) {
        return new Action\TFCBlue\Logout($c->get('pdologger'), $c->get('settings'), $c->get('tfcBlueAuthenticationRepository'));
    };

    $c[Action\API\EndUser\Identity::class] = function (c $c) {
        return new Action\API\EndUser\Identity($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('endUserRepository'));
    };

    $c[Action\API\EndUser\CheckWallet::class] = function (c $c) {
        return new Action\API\EndUser\CheckWallet($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('endUserRepository'));
    };

    $c[Action\API\EndUser\RecordRedemption::class] = function (c $c) {
        return new Action\API\EndUser\RecordRedemption($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('endUserRepository'));
    };
    //End of EndUser Actions

    //Voucher Actions
    $c[Action\API\Voucher\ListVouchers::class] = function (c $c) {
        return new Action\API\Voucher\ListVouchers($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\Redeem::class] = function (c $c) {
        return new Action\API\Voucher\Redeem($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\DFRedeem::class] = function (c $c) {
        return new Action\API\Voucher\DFRedeem($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\DFActivate::class] = function (c $c) {
        return new Action\API\Voucher\DFActivate($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\Reactivate::class] = function (c $c) {
        return new Action\API\Voucher\Reactivate($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\Create::class] = function (c $c) {
        return new Action\API\Voucher\Create($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\ShopVoucher::class] = function (c $c) {
        return new Action\API\Voucher\ShopVoucher($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\Invalidate::class] = function (c $c) {
        return new Action\API\Voucher\Invalidate($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\Reactivate::class] = function (c $c) {
        return new Action\API\Voucher\Reactivate($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\Valid::class] = function (c $c) {
        return new Action\API\Voucher\Valid($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\Code::class] = function (c $c) {
        return new Action\API\Voucher\Code($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\Image::class] = function (c $c) {
        return new Action\API\Voucher\Image($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\GetVoucherWallet::class] = function (c $c) {
        return new Action\API\Voucher\GetVoucherWallet($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    $c[Action\API\Voucher\ListVoucherWallet::class] = function (c $c) {
        return new Action\API\Voucher\ListVoucherWallet($c->get('pdologger'), $c->get('settings'), $c->get('voucherRepository'));
    };
    //End of Voucher Actions

    //Pass Actions
    $c[Action\API\Pass\ListPass::class] = function (c $c) {
        return new Action\API\Pass\ListPass($c->get('pdologger'), $c->get('settings'), $c->get('passRepository'));
    };
    $c[Action\API\Pass\Redeem::class] = function (c $c) {
        return new Action\API\Pass\Redeem($c->get('pdologger'), $c->get('settings'), $c->get('passRepository'));
    };
    $c[Action\API\Pass\Create::class] = function (c $c) {
        return new Action\API\Pass\Create($c->get('pdologger'), $c->get('settings'), $c->get('passRepository'));
    };
    $c[Action\API\Pass\Invalidate::class] = function (c $c) {
        return new Action\API\Pass\Invalidate($c->get('pdologger'), $c->get('settings'), $c->get('passRepository'));
    };
    //End of Pass Actions

    //Redemption Actions
    $c[Action\API\Redemption\Games::class] = function (c $c) {
        return new Action\API\Redemption\Games($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('ProductRepository'));
    };
    $c[Action\API\Redemption\Software::class] = function (c $c) {
        return new Action\API\Redemption\Software($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('ProductRepository'));
    };
    //End of Redemption Actions

    //TFC Blue Actions

    $c[Action\TFCBlue\Login::class] = function (c $c) {
        return new Action\TFCBlue\Login($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('authenticationRepository'));
    };
    $c[Action\TFCBlue\Logout::class] = function (c $c) {
        return new Action\TFCBlue\Logout($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('authenticationRepository'));
    };
    $c[Action\TFCBlue\ForgotPassword::class] = function (c $c) {
        return new Action\TFCBlue\ForgotPassword($c->get('pdologger'), $c->get('settings'), $c->get('cache'), $c->get('authenticationRepository'));
    };
    //End of TFC Blue Actions

    //NoFunction Action
    $c[Action\NoFunction::class] = function (c $c) {
        return new Action\NoFunction($c->get('pdologger'), $c->get('settings'));
    };

    //Unauthorized Action
    $c[Action\Unauthorized::class] = function (c $c) {
        return new Action\Unauthorized($c->get('pdologger'), $c->get('settings'));
    };
})($app);
