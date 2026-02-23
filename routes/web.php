<?php

use App\Core\Router;

$router = new Router();

// 首頁
$router->get('/', 'HomeController', 'index');

// 認證
$router->get('/login', 'AuthController', 'showLogin');
$router->post('/login', 'AuthController', 'login');
$router->get('/register', 'AuthController', 'showRegister');
$router->post('/register', 'AuthController', 'register');
$router->get('/logout', 'AuthController', 'logout');
$router->get('/resetpw', 'AuthController', 'showForgotPassword');
$router->post('/resetpw', 'AuthController', 'forgotPasswordSubmit');

// 商品
$router->get('/product/:id', 'ProductController', 'detail');
$router->get('/products', 'ProductController', 'list');

// 搜尋
$router->get('/search', 'SearchController', 'index');

// 購物車頁面
$router->get('/cart', 'CartController', 'index');

// 喜愛清單頁面
$router->get('/wishlist', 'WishlistController', 'index');

// 帳戶
$router->get('/account', 'AccountController', 'home');
$router->get('/account/orders', 'AccountController', 'orders');
$router->get('/account/order/:id', 'AccountController', 'orderDetail');
$router->get('/account/settings', 'AccountController', 'settings');
$router->post('/account/password', 'AccountController', 'updatePassword');
$router->get('/account/addresses', 'AddressController', 'index');

// 結帳頁面
$router->get('/checkout', 'OrderController', 'checkout');
$router->post('/order/process', 'OrderController', 'process');

// 靜態頁面
$router->get('/faq', 'StaticController', 'faq');
$router->get('/about', 'StaticController', 'about');
$router->get('/privacy', 'StaticController', 'privacy');
$router->get('/terms', 'StaticController', 'terms');

// 購物車功能 API
$router->get('/api/cart/count', 'CartController', 'getCount');
$router->get('/api/cart/items', 'CartController', 'getItems');
$router->post('/api/cart/add', 'CartController', 'add');
$router->post('/api/cart/update', 'CartController', 'update');
$router->post('/api/cart/remove', 'CartController', 'remove');

// 喜愛清單功能 API
$router->get('/api/wishlist/items', 'WishlistController', 'getItems');
$router->get('/api/wishlist/check', 'WishlistController', 'check');
$router->post('/api/wishlist/toggle', 'WishlistController', 'toggle');
$router->post('/api/wishlist/remove', 'WishlistController', 'remove');

// 訂單功能 API
$router->get('/api/orders', 'AccountController', 'getOrders');

// 地址功能 API
$router->get('/api/address/list', 'AddressController', 'list');
$router->get('/api/address/get', 'AddressController', 'get');
$router->post('/api/address/create', 'AddressController', 'create');
$router->post('/api/address/update', 'AddressController', 'update');
$router->post('/api/address/delete', 'AddressController', 'delete');
$router->post('/api/address/set-default', 'AddressController', 'setDefault');

// 支付功能 API
$router->get('/api/payment/publishable-key', 'PaymentController', 'getPublishableKey');
$router->post('/api/payment/create-intent', 'PaymentController', 'createIntent');
$router->post('/api/payment/create-paypal-order', 'PaymentController', 'createPaypalOrder');
$router->post('/api/payment/confirm', 'PaymentController', 'confirm');

return $router;
