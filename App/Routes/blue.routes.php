<?php
use App\Action;
use Slim\App;

(function (App $app) {

    $root = __DIR__ . '/../';
    $src = $root . 'App/';

    //TFC blue functions

    //registration
    $app->get('/b{version}/register', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/b{version}/register', Action\TFCBlue\Register::class)->setName('tfcblue.register');
    $app->put('/b{version}/register', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/b{version}/register', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/b{version}/register', Action\Unauthorized::class)->setName('unauthorized');

    $app->get('/register', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/register',Action\TFCBlue\Register::class)->setName('tfcblue.register');
    $app->put('/register', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/register', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/register', Action\Unauthorized::class)->setName('unauthorized');
    
    //login
    $app->get('/b{version}/login', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/b{version}/login', Action\TFCBlue\Login::class)->setName('tfcblue.login');
    $app->put('/b{version}/login', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/b{version}/login', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/b{version}/login', Action\Unauthorized::class)->setName('unauthorized');

    $app->get('/login', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/login', Action\TFCBlue\Login::class)->setName('tfcblue.login');
    $app->put('/login', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/login', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/login', Action\Unauthorized::class)->setName('unauthorized');
    //logout
    $app->get('/b{version}/logout', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/b{version}/logout', Action\TFCBlue\Logout::class)->setName('tfcblue.logout');
    $app->put('/b{version}/logout', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/b{version}/logout', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/b{version}/logout', Action\Unauthorized::class)->setName('unauthorized');

    $app->get('/logout', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/logout', Action\TFCBlue\Logout::class)->setName('tfcblue.logout');
    $app->put('/logout', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/logout', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/logout', Action\Unauthorized::class)->setName('unauthorized');
    //forgotpassword
    $app->get('/b{version}/forgotpassword', Action\Unauthorized::class)->setName('unauthorized');
    $app->post('/b{version}/forgotpassword', Action\TFCBlue\ForgotPassword::class)->setName('tfcblue.forgotpassword');
    $app->put('/b{version}/forgotpassword', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/b{version}/forgotpassword', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/b{version}/forgotpassword', Action\Unauthorized::class)->setName('unauthorized');
    //order
    $app->get('/b{version}/order', Action\TFCBlue\ListOrders::class)->setName('tfcblue.listorders'); //list orders
    $app->get('/b{version}/order/{id}', Action\TFCBlue\OrderDetails::class)->setName('tfcblue.orderdetails'); //order details
    $app->post('/b{version}/order', Action\TFCBlue\CreateOrder::class)->setName('tfcblue.createorder'); // create order
    $app->put('/b{version}/order', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/b{version}/order', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/b{version}/order', Action\Unauthorized::class)->setName('unauthorized');
    //voucher download
    $app->get('/b{version}/voucher/{id}', Action\TFCBlue\DownloadVoucher::class)->setName('tfcblue.dlvoucher');
    $app->post('/b{version}/voucher/{id}', Action\TFCBlue\SendVoucher::class)->setName('tfcblue.sendvoucher');
    $app->put('/b{version}/voucher', Action\Unauthorized::class)->setName('unauthorized');
    $app->patch('/b{version}/voucher', Action\Unauthorized::class)->setName('unauthorized');
    $app->delete('/b{version}/voucher', Action\Unauthorized::class)->setName('unauthorized');
    //end of tfc blue functions

})($app);
