<?php

namespace App\Http\Controllers;
use App\Bet;
use App\Game;
use App\Item;
use App\Services\SteamItem;
use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class PagesController extends Controller
{
    public function support()
    {
        parent::setTitle('Поддержка | ');

        return view('pages.support');
    }

    public function fairplay($gameID)
    {
        parent::setTitle('Честная игра | ');

        $game = Game::with(['winner'])->where('status', Game::STATUS_FINISHED)->where('id', $gameID)->first();
		if(!is_null($game)){
			$betid = \DB::table('bets')->where('game_id', $game->id)->max('id');
			$bet = Bet::where('id', $betid)->first();
			return view('pages.fairplay', compact('game', 'bet'));
		}
		return view('pages.fairplay');
    }

    public function fairplay_no()
    {
        parent::setTitle('Честная игра | ');

        return view('pages.fairplay');
    }

    public function about()
    {
        parent::setTitle('О сайте | ');

        return view('pages.about');
    }

    public function top()
    {
        parent::setTitle('Топ | ');
		$userst = \DB::table('users')
			->select('users.id',
				'users.username',
				'users.avatar',
				'users.steamid64',
				\DB::raw('SUM(games.price) as top_value'),
				\DB::raw('COUNT(games.id) as wins_count')
			)
			->join('games', 'games.winner_id', '=', 'users.id')
			->groupBy('users.id')
			->orderBy('top_value', 'desc')
			->limit(10)
			->get();
		
        $tplace = 1;
        $i = 0;
		foreach($userst as $u){
			$userst[$i]->wins_count = count(\DB::table('games')
				->where('chance', '<', 100)
				->where('winner_id', $u->id)
				->get());
			$userst[$i]->games_played = count(\DB::table('games')
				->where('chance', '<', 100)
				->join('bets', 'games.id', '=', 'bets.game_id')
				->where('bets.user_id', $u->id)
				->groupBy('bets.game_id')
				->select('bets.id')->get());
			$userst[$i]->win_rate = 0;
			if($userst[$i]->games_played > 0) $userst[$i]->win_rate = round( $userst[$i]->wins_count / $userst[$i]->games_played, 3) * 100;
			$i++;
		}
		
		$users = \DB::table('users')
			->select('users.id',
				'users.username',
				'users.avatar',
				'users.steamid64',
				\DB::raw('SUM(games.price) as top_value'),
				\DB::raw('COUNT(games.id) as wins_count')
			)
			->join('games', 'games.winner_id', '=', 'users.id')
			->where('games.created_at', '>=', Carbon::today()->subWeek())
			->groupBy('users.id')
			->orderBy('top_value', 'desc')
			->limit(10)
			->get();
		
        $place = 1;
        $i = 0;
		foreach($users as $u){
			$users[$i]->wins_count = count(\DB::table('games')
				->where('created_at', '>=', Carbon::today()->subWeek())
				->where('chance', '<', 100)
				->where('winner_id', $u->id)
				->get());
			$users[$i]->games_played = count(\DB::table('games')
				->where('chance', '<', 100)
				->where('games.created_at', '>=', Carbon::today()->subWeek())
				->join('bets', 'games.id', '=', 'bets.game_id')
				->where('bets.user_id', $u->id)
				->groupBy('bets.game_id')
				->select('bets.id')->get());
			$users[$i]->win_rate = 0;
			if($users[$i]->games_played > 0) $users[$i]->win_rate = round( $users[$i]->wins_count / $users[$i]->games_played, 3) * 100;
			$i++;
		}
		$referals = \DB::table('users')
			->groupBy('users.id')
			->orderBy('refcount', 'desc')
			->limit(10)
			->get();
		$refplace = 1;
		
		$gouts = \DB::table('users')
			->select('users.id',
				'users.username',
				'users.avatar',
				'users.steamid64',
				\DB::raw('SUM(giveouts.price) as top_value'),
				\DB::raw('COUNT(giveouts.id) as count')
			)
			->join('giveouts', 'giveouts.user_id', '=', 'users.id')
			->groupBy('users.id')
			->orderBy('top_value', 'desc')
			->limit(10)
			->get();
		$outplace = 1;
		
        return view('pages.top', compact('users', 'place', 'userst', 'tplace', 'refplace', 'referals', 'outplace', 'gouts'));
    }

    public function user($userId)
    {
        $user = User::where('steamid64', $userId)->first();
        if(!is_null($user)) {
            $games = Game::where('winner_id', $user->id)->get();
            $winz = $games->count();
			$gamesList = [];
			$gamecount = 0;
			$wins = 0;
			if($user->steamid64 != '76561197960265728'){
				$gamesPlayed = \DB::table('games')
					->join('bets', 'games.id', '=', 'bets.game_id')
					->where('bets.user_id', $user->id)
					->where('games.status', 3)
					->groupBy('bets.game_id')
					->orderBy('games.created_at', 'desc')
					->select('games.*', \DB::raw('SUM(bets.price) as betValue'))->get();
            
				$i = 0;
				foreach ($gamesPlayed as $game) {
					$gamesList[$i] = (object)[];
					$gamesList[$i]->id = $game->id;
					$gamesList[$i]->win = false;
					$gamesList[$i]->bank = $game->price;
					if ($game->status != Game::STATUS_FINISHED) $gamesList[$i]->win = -1;
					if ($game->winner_id == $user->id) $gamesList[$i]->win = true;
					if ($userId != GameController::BONUS_ID){
						$gamesList[$i]->chance = round($game->betValue / $game->price, 3) * 100;
						//if ($gamesList[$i]->chance < 100){
							if ($gamesList[$i]->win) $wins++;
							$gamecount++;
						//}
					} else {
						$gamesList[$i]->chance = 0;
					}
					$i++;
				}
			} else {
				$gamesPlayed = NULL;
			}
			$betssum = \DB::table('bets')->where('user_id', $user->id)->orderBy('id')->sum('price');
			$betssum = round($betssum / 1000 , 2); 
			if($betssum > 50) $betssum = 50.00;
			
			$username = $user->username;
			$avatar = $user->avatar;
			$totalBank = $games->sum('price');
			$votes = $user->votes;
			
			
			$url = 'https://steamcommunity.com/profiles/' . $user->steamid64 . '/';
			if ( $gamecount > 0 ){
				$winrate = count($gamesPlayed) ? round($wins / $gamecount, 3) * 100 : 0;
			} else {
				$winrate = 0;
			}
			$wins = $winz;
			$games = $gamecount;
			$list = $gamesList;
			$tradeurl = $user->trade_link;

			
			parent::setTitle($username.' | ');
        }
        else
        {
            return redirect()->route('index');
        }

        return view('pages.user', compact('username', 'avatar', 'votes', 'wins', 'url' , 'winrate' , 'totalBank' , 'games', 'list', 'userId', 'betssum', 'tradeurl'));
    }

    public function settings()
    {
		parent::setTitle('Настройки | ');
		
        return view('pages.settings');
    }

    public function history()
    {
        parent::setTitle('История игр | ');

        $games = Game::with(['bets', 'winner'])->where('status', Game::STATUS_FINISHED)->orderBy('created_at', 'desc')->simplePaginate(20);
        return view('pages.history', compact('games'));
    }
	
	public function dep()
    {
        parent::setTitle('Депозит | ');
		return view('pages.deposit', compact('games'));
    }

    public function escrow()
    {
        parent::setTitle('ESCROW | ');

        return view('pages.escrow');
    }

    public function game($gameId){
        if(isset($gameId) && Game::where('status', Game::STATUS_FINISHED)->where('id', $gameId)->count()){
            $game = Game::with(['winner'])->where('status', Game::STATUS_FINISHED)->where('id', $gameId)->first();
			$betid = \DB::table('bets')->where('game_id', $game->id)->max('id');
            $game->ticket = ceil($game->rand_number * ($game->price * 100));
            $bets = $game->bets()->with(['user','game'])->get()->sortByDesc('created_at');
            $chances = json_encode(GameController::_getChancesOfGame($game));
			$citems = array_values(json_decode($game->comission, true));
			$bedz = [];
			foreach ($bets as $bet){
				$items = array_values(json_decode($bet->items, true));
				foreach ($citems as $ckey => $i ) {
					if (in_array($i, $items)){
						$key = array_search($i, $items);
						$citems[$ckey] = '';
						$items[$key]['com'] = true;
					}
				}
				$bet->items = json_encode($items);
				$bedz[] = $bet;
				if (count($citems) == 0) continue;
			}
			$bets = $bedz;

            parent::setTitle('Игра #'.$gameId.' | ');

            return view('pages.game', compact('game', 'bets', 'chances'));
        }
        return redirect()->route('index');
    }
    private function curl($url) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 

		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}
    private function get_real_price($price, $mhn){
		if ($price < 5){
            $tprice = self::curl('http://steamcommunity.com/market/priceoverview/?currency=5&country=ru&appid=730&market_hash_name=' . urlencode($mhn) . '&format=json');
            $tprice = json_decode($tprice);
            if (isset($tprice->success)){
                $lowest = floatval(str_ireplace(array(','),'.',str_ireplace(array('pуб.'),'',$tprice->lowest_price)));
                $median = floatval(str_ireplace(array(','),'.',str_ireplace(array('pуб.'),'',$tprice->median_price)));
                if($lowest<$median) $price = $lowest; else $price = $median;
            }
            sleep(1);
		}
		return $price;
	}
    public function myinventory(Request $request)
    {
        parent::setTitle('Мой инвентарь | ');

        if($request->getMethod() == 'GET'){
            return view('pages.myinventory', compact('title'));
        } else {
			if(!\Cache::has('inventory_' . $this->user->steamid64)) {
                $jsonInventory = file_get_contents('http://steamcommunity.com/profiles/' . $this->user->steamid64 . '/inventory/json/730/2?l=russian');
                $items = json_decode($jsonInventory, true);
                if(isset($items['rgDescriptions']) && isset($items['rgInventory'])){
                    if ($items['success']) {
                        foreach ($items['rgInventory'] as $id => $value) {
                            $class_instance = $value['classid'].'_'.$value['instanceid'];
                            $item = $items['rgDescriptions'][$class_instance];
                            $info = Item::where('market_hash_name', $item['market_hash_name'])->first();
                            if (is_null($info)) {
                                $info = new SteamItem($item);
                                if (!$info->price){
                                    $info->price = 0;
                                } else {
                                    $info->price = self::get_real_price($info->price, $item['market_hash_name']);
                                    $info = Item::create((array)$info);
                                }
                            }
                            $items['rgDescriptions'][$class_instance]['price'] = $info->price;
                            $items['rgInventory'][$id]['price'] = $info->price;
                        }
                    }
                    $arr = (array)$items['rgDescriptions'];
                    uasort($arr,function($f1,$f2){
                        if($f1['price'] < $f2['price']) return 1;
                        elseif($f1['price'] > $f2['price']) return -1;
                        else return 0;
                    });
                    $items['rgDescriptions'] = (object)$arr;
                    $arr = (array)$items['rgInventory'];
                    uasort($arr,function($f1,$f2){
                        if($f1['price'] < $f2['price']) return 1;
                        elseif($f1['price'] > $f2['price']) return -1;
                        else return 0;
                    });
                    $items['rgInventory'] = (object)$arr;
                    \Cache::put('inventory_' . $this->user->steamid64, $items, 60);
                } else {
                    return [];
                }
			} else {
				$items = \Cache::get('inventory_' . $this->user->steamid64);
			}
			return $items;
        }
    }

    public function pay(Request $request)
    {
        $amount = $request->get('sum');
		if (!$amount) $amount = 1;
		if ($amount<1) $amount = 1;
        $user = $this->user;
		if(\App\Http\Controllers\GameController::PayType == 0){
			header('Location: https://api.gdonate.ru/pay?public_key='.\App\Http\Controllers\GameController::GDonateKeyPublic.'&sum='.$amount.'&account='.$user->id.'&desc=Пополнение_баланса_на_CSGF.RU');
        }
		if(\App\Http\Controllers\GameController::PayType == 1){
			$id = \DB::table('freekassa_payments')->insertGetId(['account' => $user->id, 'AMOUNT' => $amount, 'dateCreate' => Carbon::now()->toDateTimeString(), 'status' => 0 ]);
			$hash = md5(\App\Http\Controllers\GameController::FreeKassaID.':'.$amount.':'.\App\Http\Controllers\GameController::FreeKassaSecret1.':'.$id);
			header('Location: https://www.free-kassa.ru/merchant/cash.php?m='.\App\Http\Controllers\GameController::FreeKassaID.'&oa='.$amount.'&o='.$id.'&s='.$hash);
        }
		exit();
    }
}//&paySystem=robokassa