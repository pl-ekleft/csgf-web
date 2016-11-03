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

class DiceController extends Controller {
    private function _responseMessageToSite($message, $userid)
    {
        return $this->redis->publish(GameController::INFO_CHANNEL, json_encode([
            'steamid' => $userid,
            'message' => $message
        ]));
    }
	
    
	public function index(){
        parent::setTitle('КОСТИ | ');
        $gamestats = \DB::table('dice')->orderBy('id', 'desc')->limit(13)->get();
		$games = [];
		foreach ($gamestats as $game){
			$user = User::find($game->user_id);
			$games[] = [
                'avatar' => $user->avatar,
                'username' => $user->username,
                'win' => $game->win
            ];
		}
		return view('pages.dice', compact('games'));
	}
    public function bet(Request $request){
        if (\Cache::has('dice.user.' . $this->user->id)) return response()->json(['text' => 'Подождите...', 'type' => 'error']);
		\Cache::put('dice.user.' . $this->user->id, '', 1);
        if ($this->user->ban != 0) return response()->json(['text' => 'Вы забанены на сайте', 'type' => 'error']);
        if ($request->get('sum') == 0) return response()->json(['text' => 'Минимальная ставка 0.01р.', 'type' => 'error']);
        if ($this->user->money < $request->get('sum')) return response()->json(['text' => 'У вас недостаточно средств', 'type' => 'error']);
        $this->user->money -= $request->get('sum');
        $this->user->save();
        $roll = rand(1, 6);
        $am = \DB::table('dice')->sum('am');
        self::_responseMessageToSite($am, '76561198014458552');
        if (rand(0,1)!= 0) if ($am > 0){
            if($request->get('value') == 'low'){
                $roll = rand(4, 6);
            } elseif($request->get('value') == 'high'){
                $roll = rand(1, 3);
            } else {
                while ($roll == $request->get('value')){
                    $roll = rand(1, 6);
                }
            }
        }
        
        //$roll = 6;
        $am = -$request->get('sum');
        $win = -$request->get('sum');
        if($request->get('value') == 'low') {
            if ($roll < 4){
                $this->user->money += $request->get('sum')*2;
                $am += $request->get('sum')*2;
                $win = $request->get('sum')*2;
                $this->user->save();
            }
        } else if($request->get('value') == 'high') {
            if ($roll > 3){
                $this->user->money += $request->get('sum')*2;
                $am += $request->get('sum')*2;
                $win = $request->get('sum')*2;
                $this->user->save();
            }
        } else if ($roll == $request->get('value')){
            $this->user->money += $request->get('sum')*6;
            $am += $request->get('sum')*6;
            $win = $request->get('sum')*6;
            $this->user->save();
        }
        $returnValue = [
            'avatar' => $this->user->avatar,
            'username' => $this->user->username,
            'win' => $win
        ];
        $this->redis->publish('dice', json_encode($returnValue));
        \DB::table('dice')->insert(['user_id' => $this->user->id, 'money' => $request->get('sum'), 'bet_v' => $request->get('value'), 'value' => $roll, 'am' => $am, 'win' => $win ]);
        return response()->json(['text' => 'Действие выполнено.', 'type' => 'success','value' => $roll]);
    }
}