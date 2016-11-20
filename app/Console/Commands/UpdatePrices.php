<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Services\SteamItem;
use App\Item;
use Cache;

class UpdatePrices extends Command
{
    const COURSE = 65;
    const ITEMS_BY_ONCE = 100;
    const BANK_URL = 'http://www.cbr.ru/scripts/XML_daily.asp';
    const BP_URL = 'http://backpack.tf/api/IGetMarketPrices/v1/?key=';
    const FAST_URL = 'https://api.csgofast.com/price/all';
    
    protected $signature = 'prices:update {--force} {--info} {--db}';

    protected $description = '{--force} {--full} {--db} Updates Prices from steam, bp and fast';

    public function __construct()
    {
        parent::__construct();
    }
    public function handle()
    {
        $this->log('Start price loading');
        $force = $this->option('force');
        $info = $this->option('info');
        $updatedb = $this->option('db');
        if($force){
            $this->log('Getting prices from BP');
            $dataBP = self::curl(self::BP_URL . config('mod_game.backpack_key') . '&compress=1&appid=' . config('mod_game.appid'));
            $response = json_decode($dataBP);
            if(isset($response->response->items)){
                $BPitems = $response->response->items;
                $this->log('BP prices parsed');
                Cache::forever('bp_market_prices', $BPitems);
            }
            
            $this->log('Getting prices from CSGOFAST');
            $jsonItemsFast = self::curl(self::FAST_URL);
            $FastItems = json_decode($jsonItemsFast);
            if(isset(json_decode($jsonItemsFast,true)[0])){
                $this->log('FAST prices parsed');
                Cache::forever('fast_market_prices', $FastItems);
            }
        
        
            $this->log('Getting prices from STEAM');
            $parsedPages = $this->parseMarket($tmpPrices);
            Cache::forever('steam_market_prices', $tmpPrices);
            $this->log('STEAM prices parsed');
        } else {
            $BPitems = Cache::get('bp_market_prices');
            $FastItems = Cache::get('fast_market_prices');
            $tmpPrices = Cache::get('steam_market_prices');
            $parsedPages = count($tmpPrices);
        }
        
        $updated = 0; $created = 0; $usd = $this->getActualCurs();
        if($updatedb){
            foreach (array_keys($tmpPrices) as $itemname) {
                $fprice = 0; $bprice = 0;
                $sprice = round($tmpPrices[$itemname] * $usd, 2);
                if(isset($FastItems->$itemname)) $fprice = round($FastItems->$itemname * $usd, 2);
                if(isset($BPitems->$itemname)) $bprice = round($BPitems->$itemname->value * $usd / 100, 2);
                /*$this->log('SteamPrice: ' . $sprice);
                $this->log('FastPrice: ' . $fprice);
                $this->log('BPPrice: ' . $bprice);*/
                
                $price = $sprice;
                if($fprice > 0) $price = round(($price + $fprice)/2, 2);
                if($bprice > 0) $price = round(($price + $bprice)/2, 2);
                if($price < 30){
                    $price = $sprice;
                } else if($price > 20000){
                    if($fprice > 0){
                        if($bprice > 0){
                            $price = round(($fprice + $bprice)/2, 2);
                        } else {
                            $price = round($fprice, 2);
                        }
                    } else if($bprice > 0){
                        $price = round($bprice, 2);
                    } else {
                        $price = $sprice;
                    }
                }
                
                $item = Item::where('market_hash_name', $itemname)->first();
                if (is_null($item)) {
                    Item::create(array(
                        "name"=>$itemname,
                        "market_hash_name"=>$itemname,
                        "price"=>$price
                    ));
                    $this->log("Price for {$itemname} ~{$price} added [NEW!]");
                    $created++;
                } elseif ($item->price != $price && $price>0) {
                    $item->price = $price;
                    $item->save();
                    $this->log("Price for {$itemname} ~{$price} updated");
                    $updated++;
                }
                sleep(1);
            }
        }
        $this->log("Market parsed. Total updated items: {$updated}. New items: {$created}. Market pages parsed: {$parsedPages}");
        if($info){
            $this->log('Start info loading');
            $items = Item::where('classid',0)->get();
            foreach ($items as $item) {
                $newItem = $this->getInfo($item->market_hash_name);
                if ($newItem) {
                    $item->name = $newItem['name'];
                    $item->rarity = $newItem['rarity'];
                    $item->classid = $newItem['classid'];
                    $item->save();
                    $this->log("[INFO] {$item->market_hash_name} parsed");
                } else {
                    $this->log("Can't get info for {$item->market_hash_name}");
                }
                sleep(15);
            }
            $this->log('Update info finished');
        }
    }
    private function curl($url) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 

        
		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}
    private function getActualCurs() {
        if(!Cache::has('ActualCurs')) {
            $link = self::BANK_URL;
            $str  = self::curl($link);
            preg_match('#<Valute ID="R01235">.*?.<Value>(.*?)</Value>.*?</Valute>#is', $str, $value);
            $usd = $value[1];
            Cache::put('ActualCurs', $usd, 6 * 60 * 60);
        } else {
			$usd = Cache::get('ActualCurs');
        }
        if(!$usd) $usd = self::COURSE;
        return $usd;
    }
    private function parseMarket(&$items, $from=0,$to=50000) {
        $page = 0;
        for ($k = (int)$from; $k <= (int)$to; $k++) {
            if ($k == 0) {
                $first = 0;
            } else {
                $first = ((int)$k * self::ITEMS_BY_ONCE) + 1;
            }
            $link = "http://steamcommunity.com/market/search/render/?l=english&start=".$first."&count=".self::ITEMS_BY_ONCE."&search_descriptions=0&sort_column=price&sort_dir=asc&appid=".config('mod_game.appid');
            $strpage = self::curl($link);
            $json = json_decode($strpage);

            $sdata = $json->results_html;
            $total_count = $json->total_count;
            $totalPages = floor($total_count / self::ITEMS_BY_ONCE)+$from+1;
            $this->log("Parsing market list page ".++$page." of ".$totalPages);

            preg_match_all('%<a class="market_listing_row_link" href="(.+?)" id="resultlink.*?<span class="normal_price">(.+?) .+?</span>.+?<span class="sale_price">(.+?) .+?</span>.*?class="market_listing_item_name" style=".*?">(.+?)</span>%s', $sdata, $result, PREG_PATTERN_ORDER);
            for ($i = 0; $i < count($result[0]); $i++) {
                $steam_price_sale = substr($result[3][$i],1);
                $steam_market_name = $result[4][$i];
                //$this->log("PRICE: ". $steam_price_sale);
                $steam_price_sale = str_replace(",", ".", $steam_price_sale);
                $items[$steam_market_name] = $steam_price_sale;
            }
            sleep(15);
            if (!is_int($total_count)) {
                if (preg_match('/total_count":(.+?),"/', $strpage, $regs)) {
                    $total_count = $regs[1];
                }
            }
            if ($total_count < $first) {
                return $page;
            }
        }
    }
    public static function getItemRarity($info) {
        $type = $info['type'];
        $rarity = '';

        $types = array("StatTrak™ "," Pistol", " SMG", " Rifle", " Shotgun", " Sniper Rifle", " Machinegun", " Container", " Knife", " Sticker", " Music Kit", " Key", " Pass", " Gift", " Tag", " Tool");
        $typesrep = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "");
        $type = str_replace($types, $typesrep, $type);

        switch ($type) {
            case 'Mil-Spec Grade':
            case 'High Grade':
                $rarity = 'milspec'; break;
            case 'Restricted':
            case 'Remarkable':
                $rarity = 'restricted'; break;
            case 'Classified':
            case 'Exotic':
                $rarity = 'classified'; break;
            case 'Covert':
                $rarity = 'covert'; break;
            case 'Consumer Grade':
            case 'Base':
            case 'Industrial Grade':
                $rarity = 'common'; break;
            case '★':
            case '★ Covert':
                $rarity = 'rare'; break;
        }

        return $rarity;
    }
    private function getInfo($marketName) {
        $link = sprintf('http://steamcommunity.com/market/listings/730/%s',rawurlencode($marketName));
        $strpage = self::curl($link);
        $item = false;
        if (preg_match('%"\d+":({"currency".*?"descriptions":\[.*?\].*?"owner":\d+})%s',$strpage,$result)) {
            $info = json_decode($result[1]);
            $item = [];
            $item['name']=$info->market_name;
            $item['market_hash_name']=$info->market_hash_name;
            $item['classid']=$info->classid;
            $item['type']=$info->type;
            $item['rarity']=$this->getItemRarity($item);
        }
        return $item;
    }
    private function log($text) {
        echo sprintf("[%s] %s\r\n", Carbon::now()->toDayDateTimeString(), $text);
    }
}