<?php

get('/login', ['as' => 'login', 'uses' => 'SteamController@login']);
get('/', ['as' => 'index', 'uses' => 'GameController@currentGame']);
get('/support', ['as' => 'support', 'uses' => 'PagesController@support']);
get('/fairplay/{game}', ['as' => 'fairplay', 'uses' => 'PagesController@fairplay']);
get('/fairplay', ['as' => 'fairplay_no', 'uses' => 'PagesController@fairplay_no']);
get('/top', ['as' => 'top', 'uses' => 'PagesController@top']);
get('/game/{game}', ['as' => 'game', 'uses' => 'PagesController@game']);
get('/user/{user}', ['as' => 'user', 'uses' => 'PagesController@user']);
get('/history', ['as' => 'history', 'uses' => 'PagesController@history']);
get('/escrow', ['as' => 'escrow', 'uses' => 'PagesController@escrow']);
post('ajax', ['as' => 'ajax', 'uses' => 'AjaxController@parseAction']);
get('/chat', ['as' => 'chat', 'uses' => 'ChatController@chat']);
get('/donate', 'DonateController@Donate');

get('/lol', 'PagesController@lol');

get('/auth', ['as' => 'auth', 'uses' => 'SteamController@auth']);

Route::group(['middleware' => 'auth'], function () {
	get('/havegame', 'GameController@havegame');
	get('/logout', ['as' => 'logout', 'uses' => 'SteamController@logout']);
	get('/dec', ['as' => 'dec', 'uses' => 'GameController@dec', 'middleware' => 'access:admin']);
	get('/ref', ['as' => 'ref', 'uses' => 'RefController@ref']);	
	get('/getcoupon', ['as' => 'getcoupon', 'uses' => 'RefController@getcoupon']);
	get('/setcoupon', ['as' => 'setcoupon', 'uses' => 'RefController@setcoupon']);
	get('/pay', ['as' => 'pay', 'uses' => 'PagesController@pay']);
    //get('/deposit', ['as' => 'deposit', 'uses' => 'GameController@deposit']);
    get('/dep', ['as' => 'deposit', 'uses' => 'GameController@deposit']);
    get('/my-inventory', ['as' => 'my-inventory', 'uses' => 'PagesController@myinventory']);
    post('/myinventory', ['as' => 'myinventory', 'uses' => 'PagesController@myinventory']);
	post('/inv', ['as' => 'getitemsF', 'uses' => 'GameController@getitemsF']);
    post('/getcoupon', ['as' => 'getcoupon', 'uses' => 'RefController@getcoupon']);
	post('/setcoupon', ['as' => 'setcoupon', 'uses' => 'RefController@setcoupon']);
	post('/settings/save', ['as' => 'settings.update', 'uses' => 'SteamController@updateSettings']);
    post('/addTicket', ['as' => 'add.ticket', 'uses' => 'GameController@addTicket']);
    post('/getBalance', ['as' => 'get.balance', 'uses' => 'GameController@getBalance']);
	post('/my_comission', 'GameController@curcomm');
	post('/updatepassword', ['as' => 'updatepassword', 'uses' => 'SteamController@updatepassword']);
});

/* GIVEOUT ROUTES */
Route::group(['middleware' => 'auth'], function () {
	get('/out', ['as' => 'out_index', 'uses' => 'GiveOutController@out_index']);
	post('/out/start', 'GiveOutController@startOut');
	post('/out/get', 'GiveOutController@getOut');
	post('/out/getMon', 'GiveOutController@getMon');
});
Route::group(['prefix' => 'api', 'middleware' => 'secretKey'], function () {
	post('/out/check', ['as' => 'checkUsers', 'uses' => 'GiveOutController@checkUsers']);
});

/* SCRIPT ROUTES */
Route::group(['prefix' => 'api', 'middleware' => 'secretKey'], function () {
	post('/chat', ['as' => 'chat', 'uses' => 'ChatController@chat']);
	post('/update', 'GameController@update');
    post('/checkOffer', 'GameController@checkOffer');
    post('/newBet', 'GameController@newBet');
    post('/setGameStatus', 'GameController@setGameStatus');
    post('/setPrizeStatus', 'GameController@setPrizeStatus');
    post('/getCurrentGame', 'GameController@getCurrentGame');
    post('/getWinners', 'GameController@getWinners');
    post('/getPreviousWinner', 'GameController@getPreviousWinner');
    post('/novigra', 'GameController@newGame');
	post('/checkBrokenGames', 'AdminController@checkBrokenGames');
    post('/getPriceItems', 'GameController@getPriceItems');
    post('/clearOnline', 'GameController@clearOnline');
    post('/updateOnline', 'GameController@updateOnline');
});

/* SHOP ROUTES */
Route::group(['middleware' => 'auth'], function () {
	get('/shop', ['as' => 'shop', 'uses' => 'ShopController@index']);
    get('/shop/deposit', ['as' => 'shop_deposit', 'uses' => 'ShopController@deposit']);
	get('/shop/history', ['as' => 'cards-history', 'uses' => 'ShopController@history']);
	post('/shop/buy', ['as' => 'settings.update', 'uses' => 'ShopController@buyItem']);
	post('/shop/items', ['as' => 'shop_items', 'uses' => 'ShopController@shop']);
	post('/shop/getcart', ['as' => 'getcart', 'uses' => 'ShopController@getcart']);
	post('/shop/sellitems', ['as' => 'sellitems', 'uses' => 'ShopController@sellitems']);
	post('/shop/myinventory', ['as' => 'cards-myinventory', 'uses' => 'ShopController@myinventory']);
	post('/shop/inv_update', ['as' => 'inv_update', 'uses' => 'ShopController@inv_update']);
	post('/shop/checkOffers', ['as' => 'shop_checkOffers', 'uses' => 'ShopController@checkOffers']);
});
Route::group(['prefix' => 'api', 'middleware' => 'secretKey'], function () {
	post('/shop/newItems', 'ShopController@addItemsToSale');
	post('/shop/checkShop', 'ShopController@checkShop');
    post('/shop/setItemStatus', 'ShopController@setItemStatus');
});

/* CHAT ROUTES */
Route::group(['middleware' => 'auth'], function () {
    post('/add_message', ['as' => 'chat', 'uses' => 'ChatController@add_message']);
    post('/delmsg', ['as' => 'chat', 'uses' => 'ChatController@delmsg']);
    post('/ban_user', ['as' => 'chat', 'uses' => 'ChatController@ban_user']);
	post('/chat', ['as' => 'chat', 'uses' => 'ChatController@chat']);
});

/* ADMIN ROUTES */
Route::group(['middleware' => 'auth'], function () {
	/* ADMIN BOT */
	get('/admin/bot', ['as' => 'give', 'uses' => 'BotController@index', 'middleware' => 'access:moderator']);
	get('/admin/bot/log', ['as' => 'give', 'uses' => 'BotController@log', 'middleware' => 'access:moderator']);
	post('/admin/bot/start', ['as' => 'give', 'uses' => 'BotController@start', 'middleware' => 'access:admin']);
	post('/admin/bot/stop', ['as' => 'give', 'uses' => 'BotController@stop', 'middleware' => 'access:admin']);
	post('/admin/bot/restart', ['as' => 'give', 'uses' => 'BotController@restart', 'middleware' => 'access:admin']);
	post('/admin/bot/reload', ['as' => 'give', 'uses' => 'BotController@reload', 'middleware' => 'access:admin']);
	post('/admin/bot/mysql', ['as' => 'give', 'uses' => 'BotController@mysql', 'middleware' => 'access:admin']);
	post('/admin/bot/status', ['as' => 'give', 'uses' => 'BotController@status', 'middleware' => 'access:moderator']);
	/* ADMIN AM */
	get('/admin/am', ['as' => 'give', 'uses' => 'AdminController@am', 'middleware' => 'access:moderator']);	
	post('/admin/am/getwords', ['as' => 'give', 'uses' => 'AdminController@getwords', 'middleware' => 'access:moderator']);
	post('/admin/am/add', ['as' => 'give', 'uses' => 'AdminController@addword', 'middleware' => 'access:moderator']);
	/* ADMIN USERS */
	get('/admin/users', ['as' => 'give', 'uses' => 'AdminController@users', 'middleware' => 'access:moderator']);	
	get('/admin/user/{user}', ['as' => 'give', 'uses' => 'AdminController@user', 'middleware' => 'access:moderator']);	
	post('/admin/userinfo', ['as' => 'give', 'uses' => 'AdminController@userinfo', 'middleware' => 'access:moderator']);
	post('/admin/users/updateMute', ['as' => 'give', 'uses' => 'AdminController@updateMute', 'middleware' => 'access:moderator']);
	post('/admin/users/updateBan', ['as' => 'give', 'uses' => 'AdminController@updateBan', 'middleware' => 'access:moderator']);
	post('/admin/users/updateBanSup', ['as' => 'give', 'uses' => 'AdminController@updateBanSup', 'middleware' => 'access:moderator']);
	post('/admin/users/updateMoney', ['as' => 'give', 'uses' => 'AdminController@updateMoney', 'middleware' => 'access:admin']);
	post('/admin/users/updateAdmin', ['as' => 'give', 'uses' => 'AdminController@updateAdmin', 'middleware' => 'access:admin']);
	post('/admin/users/updateModerator', ['as' => 'give', 'uses' => 'AdminController@updateModerator', 'middleware' => 'access:admin']);
	/* ADMIN FUSER*/
	post('/admin/fuser_add', ['as' => 'give', 'uses' => 'AdminController@fuser_add', 'middleware' => 'access:admin']);
	post('/admin/fuser_del', ['as' => 'give', 'uses' => 'AdminController@fuser_del', 'middleware' => 'access:admin']);
	/* ADMIN */
	get('/admin', ['as' => 'give', 'uses' => 'AdminController@admin', 'middleware' => 'access:moderator']);	
	post('/fixtic', ['as' => 'give', 'uses' => 'AdminController@fixGameTic', 'middleware' => 'access:admin']);	
	post('/ctime', ['as' => 'give', 'uses' => 'AdminController@ctime', 'middleware' => 'access:admin']);
	post('/updateNick', ['as' => 'give', 'uses' => 'AdminController@updateNick', 'middleware' => 'access:moderator']);
	get('/updatePrices', ['as' => 'give', 'uses' => 'GameController@updatePrices', 'middleware' => 'access:admin']);
	post('/updateShop', ['as' => 'give', 'uses' => 'AdminController@updateShop', 'middleware' => 'access:moderator']);
	post('/winner', ['as' => 'give', 'uses' => 'AdminController@winner', 'middleware' => 'access:admin']);
	post('/winnerr', ['as' => 'give', 'uses' => 'AdminController@winnerr', 'middleware' => 'access:admin']);
	post('/fixgame', ['as' => 'give', 'uses' => 'AdminController@fixgame', 'middleware' => 'access:admin']);
});

/* SEND ROUTES */
Route::group(['middleware' => 'auth'], function () {
	post('/send', ['as' => 'send', 'uses' => 'SendController@send']);
    post('/send/list', ['as' => 'sendlist', 'uses' => 'SendController@sendlist']);
});

/* DOUBLE ROUTES */
Route::group(['middleware' => 'auth'], function () {
	get('/dwinner', ['as' => 'dwinner', 'uses' => 'DoubleController@dwinner', 'middleware' => 'access:admin']);
	get('/double', ['as' => 'double_index', 'uses' => 'DoubleController@double_index']);	
	post('/double/bet', ['as' => 'bet', 'uses' => 'DoubleController@newBet']);	
});

Route::group(['prefix' => 'api', 'middleware' => 'secretKey'], function () {
    post('/double/newGame', 'DoubleController@newGame');
    post('/double/startGame', 'DoubleController@startGame');
	post('/double/setGameStatus', 'DoubleController@setGameStatus');
	post('/double/getCurrentGame', 'DoubleController@getCurrentGame');
});
/* COINFLIP ROUTES */
Route::group(['middleware' => 'auth'], function () {
	get('/coin', ['as' => 'coinflip', 'uses' => 'CoinFlipController@index']);
    post('/coin/bet', ['as' => 'coinflip_bet', 'uses' => 'CoinFlipController@bet']);
    post('/coin/nbet', ['as' => 'coinflip_new_bet', 'uses' => 'CoinFlipController@nbet']);
});
/* DICE ROUTES */
Route::group(['middleware' => 'auth'], function () {
	get('/dice', ['as' => 'dicegame', 'uses' => 'DiceController@index']);
    post('/dice/bet', ['as' => 'dicebet', 'uses' => 'DiceController@bet']);
});