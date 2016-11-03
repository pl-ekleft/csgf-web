<?php

namespace App\Http\Controllers;

use App\Item;
use App\Services\SteamItem;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Cache;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Storage;

class CoinFlipController extends Controller {
	public function index(){
        parent::setTitle('МОНЕТКА | ');
        $games = \DB::table('coin')->where('status', 0)->get();
        $coingames = [];
        foreach ($games as $game){
            $creator = User::find($game->creator);
            $coingames[] = [
                'ava' => $creator->avatar,
                'name' => $creator->username,
                'id' => $game->id,
                'sum' => $game->money
            ];
        }
		return view('pages.coin', compact('coingames'));
	}
    private function _responseMessageToSite($message, $userid)
    {
        return $this->redis->publish(GameController::INFO_CHANNEL, json_encode([
            'steamid' => $userid,
            'message' => $message
        ]));
    }
    
    public function bet(Request $request){
        if (\Cache::has('coin.user.' . $this->user->id)) return response()->json(['text' => 'Подождите...', 'type' => 'error']);
		\Cache::put('coin.user.' . $this->user->id, '', 1);
        if ($this->user->ban != 0) return response()->json(['text' => 'Вы забанены на сайте', 'type' => 'error']);
        $game = \DB::table('coin')->where('id', $request->get('id'))->first();
        if(is_null($game)) return response()->json(['text' => 'Игра не найдена', 'type' => 'error']);
        if($game->status != 0) return response()->json(['text' => 'Игра завершена', 'type' => 'error']);
        $creator = User::find($game->creator);
        if($creator->id == $this->user->id) return response()->json(['text' => 'Вы не можете играть сам с собой', 'type' => 'error']);
        if($this->user->money < $game->money) return response()->json(['text' => 'У вас недостаточно средств.', 'type' => 'error']);
        $this->user->money -= $game->money;
        $this->user->save();
        if(rand(0, 1) == 1){
            $winner = $this->user;
            $looser = $creator;
        } else {
            $looser = $this->user;
            $winner = $creator;
        }
        \DB::table('users')->where('id', $winner->id)->update(['money' => ($winner->money + $game->money * 2)]);
        \DB::table('coin')->where('id', $game->id)->update(['money' => ($game->money + $game->money), 'status' => 1, 'player' => $this->user->id, 'winner'=> $winner->id]);
        $returnValue = [
            'ava' => $this->user->avatar,
            'id' => $game->id,
            'name' => $this->user->username,
            'wava' => $winner->avatar,
            'lava' => $looser->avatar
        ];
        $this->redis->publish('coin_scroll', json_encode($returnValue));
        return response()->json(['text' => 'Действие выполнено.', 'type' => 'success']);
	}
    public function nbet(Request $request){
        if (\Cache::has('coin.user.' . $this->user->id)) return response()->json(['text' => 'Подождите...', 'type' => 'error']);
		\Cache::put('coin.user.' . $this->user->id, '', 1);
        if ($this->user->ban != 0) return response()->json(['text' => 'Вы забанены на сайте', 'type' => 'error']);
        $sum = floor($request->get('sum')*100)/100;
        if($sum=='') return response()->json(['text' => 'Укажите сумму.', 'type' => 'error']);
        if($sum < 0.01) return response()->json(['text' => 'Минимальная ставка 0.01р.', 'type' => 'error']);

        if($this->user->money < $sum) return response()->json(['text' => 'У вас недостаточно средств.', 'type' => 'error']);

        $this->user->money -= $sum;
        $this->user->save();
        
        $id = \DB::table('coin')->insertGetId([
            'money' => $sum,
            'creator' => $this->user->id,
            'status' => 0,
        ]);
        
        $returnValue = [
            'ava' => $this->user->avatar,
            'name' => $this->user->username,
            'id' => $id,
            'sum' => $sum
        ];
        $this->redis->publish('coin_new', json_encode($returnValue));
        return response()->json(['text' => 'Действие выполнено.', 'type' => 'success']);
	}
}