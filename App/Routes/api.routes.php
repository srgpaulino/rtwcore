<?php
use App\Action;
use Slim\App;

(function (App $app) {
    $root = __DIR__ . '/../';
    $src = $root . 'App/';

    //SSO route
    $app->get('/v{version}/sso', Action\API\EndUser\SignOn::class)->setName('enduser.signon');
    $app->post('/v{version}/sso', Action\API\EndUser\SignOn::class)->setName('enduser.signon');
    $app->put('/v{version}/sso', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/v{version}/sso', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/v{version}/sso', Action\Unauthorized::class)->setName('unauthorized');

    $app->get('/sso', Action\API\EndUser\SignOn::class)->setName('enduser.signon');
    $app->post('/sso', Action\API\EndUser\SignOn::class)->setName('enduser.signon');
    $app->put('/sso', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/sso', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/sso', Action\Unauthorized::class)->setName('unauthorized');
    //end of SSO route

    //Voucher route
    $app->get('/v{version}/voucher', Action\API\Voucher\ListVouchers::class)->setName('voucher.list');
    $app->get('/v{version}/voucher/{code}', Action\API\Voucher\Valid::class)->setName('voucher.valid');
    $app->post('/v{version}/voucher', Action\API\Voucher\Create::class)->setName('voucher.create');
    $app->post('/v{version}/shopvoucher', Action\API\Voucher\ShopVoucher::class)->setName('voucher.shopvoucher');
    $app->put('/v{version}/voucher/{code}', Action\API\Voucher\Redeem::class)->setName('voucher.redeem');
    $app->put('/v{version}/voucher', Action\Unauthorized::class)->setName('unauthorized');
    $app->put('/v{version}/dfvoucher/{code}', Action\API\Voucher\DFRedeem::class)->setName('voucher.dfRedeem');
    $app->put('/v{version}/dfvoucher', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/v{version}/dfvoucher', Action\API\Voucher\DFActivate::class)->setName('voucher.dfActivate');
    $app->put('/v{version}/reactivate/{code}', Action\API\Voucher\Reactivate::class)->setName('voucher.reactivate');
    $app->put('/v{version}/reactivate', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/v{version}/voucher', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/v{version}/voucher/{code}', Action\API\Voucher\Invalidate::class)->setName('voucher.invalidate');

    $app->post('/v{version}/code', Action\API\Voucher\Code::class)->setName('voucher.code');
    $app->get('/v{version}/code/{code}/{format}', Action\API\Voucher\Image::class)->setName('voucher.image');

    $app->get('/v{version}/voucherwallet/{code}', Action\API\Voucher\GetVoucherWallet::class)->setName('voucherwallet.get');
    $app->get('/v{version}/listvoucherwallet/{user}', Action\API\Voucher\ListVoucherWallet::class)->setName('voucherwallet.list');

    $app->get('/voucher', Action\API\Voucher\ListVouchers::class)->setName('voucher.list');
    $app->get('/voucher/{code}', Action\API\Voucher\Valid::class)->setName('voucher.valid');
    $app->post('/voucher', Action\API\Voucher\Create::class)->setName('voucher.create');
    $app->post('/shopvoucher', Action\API\Voucher\ShopVoucher::class)->setName('voucher.shopvoucher');
    $app->put('/voucher/{code}', Action\API\Voucher\Redeem::class)->setName('voucher.redeem');
    $app->put('/voucher', Action\Unauthorized::class)->setName('unauthorized');
    $app->put('/dfvoucher/{code}', Action\API\Voucher\DFRedeem::class)->setName('voucher.dfRedeem');
    $app->put('/dfvoucher', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/dfvoucher', Action\API\Voucher\DFActivate::class)->setName('voucher.dfActivate');
    $app->put('/reactivate/{code}', Action\API\Voucher\Reactivate::class)->setName('voucher.reactivate');
    $app->put('/reactivate', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/voucher', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/voucher/{code}', Action\API\Voucher\Invalidate::class)->setName('voucher.invalidate');

    $app->post('/code', Action\API\Voucher\Code::class)->setName('voucher.code');
    $app->get('/code/{code}/{format}', Action\API\Voucher\Image::class)->setName('voucher.image');

    $app->get('/voucherwallet/{code}', Action\API\Voucher\GetVoucherWallet::class)->setName('voucherwallet.get');
    $app->get('/listvoucherwallet/{user}', Action\API\Voucher\ListVoucherWallet::class)->setName('voucherwallet.list');
    
    //end of Voucher route

    //Pass route
    $app->get('/v{version}/pass', Action\Unauthorized::class)->setName('unauthorized');
    $app->get('/v{version}/pass/{code}', Action\API\Pass\Redeem::class)->setName('pass.redeem');
    $app->post('/v{version}/pass', Action\API\Pass\Create::class)->setName('pass.create');
    $app->put('/v{version}/pass', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/v{version}/pass', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/v{version}/pass/{code}', Action\API\Pass\Invalidate::class)->setName('pass.invalidate');

    $app->get('/pass', Action\Unauthorized::class)->setName('unauthorized');
    $app->get('/pass/{code}', Action\API\Pass\Redeem::class)->setName('pass.redeem');
    $app->post('/pass', Action\API\Pass\Create::class)->setName('pass.create');
    $app->put('/pass', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/pass', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/pass/{code}', Action\API\Pass\Invalidate::class)->setName('pass.invalidate');
    //end of Pass route

    //Redeem Game Route
    $app->get('/v{version}/redeem/game', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/v{version}/redeem/game/{pid}', Action\API\Redemption\Games::class)->setName('game.redeem');
    $app->put('/v{version}/redeem/game', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/v{version}/redeem/game', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/v{version}/redeem/game', Action\Unauthorized::class)->setName('unauthorized');

    $app->get('/redeem/game', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/redeem/game/{pid}', Action\API\Redemption\Games::class)->setName('game.redeem');
    $app->put('/redeem/game', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/redeem/game', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/redeem/game', Action\Unauthorized::class)->setName('unauthorized');
    //end of Redeem Game Route

    //Redeem Game Route
    $app->get('/v{version}/redeem/software', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/v{version}/redeem/software/{pid}', Action\API\Redemption\Software::class)->setName('game.redeem');
    $app->put('/v{version}/redeem/software', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/v{version}/redeem/software', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/v{version}/redeem/software', Action\Unauthorized::class)->setName('unauthorized');

    $app->get('/redeem/software', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/redeem/software/{pid}', Action\API\Redemption\Software::class)->setName('game.redeem');
    $app->put('/redeem/software', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/redeem/software', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/redeem/software', Action\Unauthorized::class)->setName('unauthorized');
    //end of Redeem Game Route

    //Identity route
    $app->get('/v{version}/identity', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/v{version}/identity', Action\API\EndUser\Identity::class)->setName('enduser.identity');
    $app->put('/v{version}/identity', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/v{version}/identity', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/v{version}/identity', Action\Unauthorized::class)->setName('unauthorized');

    $app->get('/identity', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/identity', Action\API\EndUser\Identity::class)->setName('enduser.identity');
    $app->put('/identity', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/identity', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/identity', Action\Unauthorized::class)->setName('unauthorized');
    //end of Identity route

    //Wallet route
    $app->get('/v{version}/wallet', Action\API\EndUser\CheckWallet::class)->setName('enduser.wallet');
    $app->post('/v{version}/wallet', Action\Unauthorized::class)->setName('unauthorized');
    $app->put('/v{version}/wallet', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/v{version}/wallet', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/v{version}/wallet', Action\Unauthorized::class)->setName('unauthorized');

    $app->get('/wallet', Action\API\EndUser\CheckWallet::class)->setName('enduser.wallet');
    $app->post('/wallet', Action\Unauthorized::class)->setName('unauthorized');
    $app->put('/wallet', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/wallet', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/wallet', Action\Unauthorized::class)->setName('unauthorized');
    //end of Wallet route

    //Redeem route
    $app->get('/v{version}/redeem', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/v{version}/redeem', Action\API\EndUser\RecordRedemption::class)->setName('enduser.redeem');
    $app->put('/v{version}/redeem', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/v{version}/redeem', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/v{version}/redeem', Action\Unauthorized::class)->setName('unauthorized');

    $app->get('/redeem', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/redeem', Action\API\EndUser\RecordRedemption::class)->setName('enduser.redeem');
    $app->put('/redeem', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/redeem', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/redeem', Action\Unauthorized::class)->setName('unauthorized');
    //end of Redeem route

})($app);
