<?php

use App\Core\Router;

$router = new Router();

$router->get('/', 'HomeController', 'index');

$router->get('/login', 'AuthController', 'showLogin');
$router->post('/login', 'AuthController', 'login');
$router->post('/auth/firebase', 'AuthController', 'firebaseAuth');
$router->get('/register', 'AuthController', 'showRegister');
$router->post('/register/send-code', 'AuthController', 'sendRegistrationCode');
$router->post('/register', 'AuthController', 'register');
$router->get('/logout', 'AuthController', 'logout');

$router->get('/forgot', 'ForgotPasswordController', 'index');
$router->post('/forgot', 'ForgotPasswordController', 'send');
$router->post('/forgot/verify', 'ForgotPasswordController', 'verifyCode');
$router->get('/forgot/reset', 'ForgotPasswordController', 'showResetForm');
$router->post('/forgot/reset', 'ForgotPasswordController', 'resetPassword');

$router->get('/product/:id', 'ProductController', 'detail');
$router->get('/products', 'ProductController', 'list');

$router->get('/search', 'SearchController', 'index');

$router->get('/cart', 'CartController', 'index');

$router->get('/wishlist', 'WishlistController', 'index');

$router->get('/account', 'AccountController', 'home');
$router->get('/account/orders', 'AccountController', 'orders');
$router->get('/account/order/:id', 'AccountController', 'orderDetail');
$router->get('/account/settings', 'AccountController', 'settings');
$router->get('/account/payment', 'AccountController', 'payment');
$router->get('/account/points', 'PointsController', 'center');
$router->post('/account/password', 'AccountController', 'updatePassword');
$router->post('/account/email', 'AccountController', 'updateEmail');
$router->post('/account/phone', 'AccountController', 'updatePhone');
$router->post('/account/review', 'AccountController', 'submitReview');
$router->get('/account/addresses', 'AddressController', 'index');

$router->get('/checkout', 'OrderController', 'checkout');

$router->get('/faq', 'StaticController', 'faq');
$router->get('/about', 'StaticController', 'about');
$router->get('/privacy', 'StaticController', 'privacy');
$router->get('/terms', 'StaticController', 'terms');

$router->get('/api/cart/count', 'CartController', 'getCount');
$router->get('/api/cart/items', 'CartController', 'getItems');
$router->post('/api/cart/add', 'CartController', 'add');
$router->post('/api/cart/update', 'CartController', 'update');
$router->post('/api/cart/remove', 'CartController', 'remove');

$router->get('/api/wishlist/items', 'WishlistController', 'getItems');
$router->get('/api/wishlist/check', 'WishlistController', 'check');
$router->post('/api/wishlist/toggle', 'WishlistController', 'toggle');
$router->post('/api/wishlist/remove', 'WishlistController', 'remove');

$router->get('/api/orders', 'AccountController', 'getOrders');
$router->get('/api/points/info', 'PointsController', 'apiInfo');
$router->get('/api/points/logs', 'PointsController', 'apiLogs');

$router->get('/api/address/list', 'AddressController', 'list');
$router->get('/api/address/get', 'AddressController', 'get');
$router->post('/api/address/create', 'AddressController', 'create');
$router->post('/api/address/update', 'AddressController', 'update');
$router->post('/api/address/delete', 'AddressController', 'delete');
$router->post('/api/address/set-default', 'AddressController', 'setDefault');

$router->get('/api/payment/publishable-key', 'PaymentController', 'getPublishableKey');
$router->post('/api/payment/create-intent', 'PaymentController', 'createIntent');
$router->post('/api/payment/create-paypal-order', 'PaymentController', 'createPaypalOrder');
$router->post('/api/payment/wallet-checkout', 'PaymentController', 'walletCheckout');
$router->post('/api/payment/confirm', 'PaymentController', 'confirm');

$router->get('/admin/login', 'Admin\LoginController', 'index');
$router->post('/admin/login', 'Admin\LoginController', 'login');
$router->get('/admin/logout', 'Admin\LoginController', 'logout');

$router->get('/admin/dashboard', 'Admin\DashboardController', 'index');

$router->get('/admin/products', 'Admin\ProductController', 'index');
$router->get('/admin/products/create', 'Admin\ProductController', 'create');
$router->post('/admin/products/save', 'Admin\ProductController', 'save');
$router->get('/admin/products/edit/:id', 'Admin\ProductController', 'edit');
$router->post('/admin/products/delete/:id', 'Admin\ProductController', 'delete');

$router->get('/admin/orders', 'Admin\OrderController', 'index');
$router->get('/admin/orders/:id', 'Admin\OrderController', 'detail');
$router->post('/admin/orders/:id/status', 'Admin\OrderController', 'updateStatus');

$router->get('/admin/users', 'Admin\UserController', 'index');
$router->get('/admin/users/:id', 'Admin\UserController', 'show');
$router->post('/admin/users/:id/toggle-status', 'Admin\UserController', 'toggleStatus');
$router->post('/admin/users/:id/vip-level', 'Admin\UserController', 'updateVipLevel');

return $router;
