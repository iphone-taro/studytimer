<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SkTimeStamp;
use Illuminate\Support\Facades\DB;
use DateTime;

class SukiController extends Controller {
    public function getGoodsList(Request $request) {
        $goodsList = DB::table('sk_goods')->where('deleted', 1)->where('kbn', 1)->orderBy('place', 'desc')->get();

        return response()->json([
            'goodsList' => $goodsList,
        ]);
    }

    public function getList(Request $request) {
        //パラメータの取得
        $date = $request->date;
        $kbn = $request->kbn;

        //初期取得 現在日付から2日間取得
        $targetDate = new DateTime($request->date);
        
        //17より前
        $startOfYesterday = clone $targetDate;
        $startOfYesterday->modify('-1 day');
        $startOfYesterday->setTime(17, 0, 0);

        $endOfDay = clone $targetDate;
        $endOfDay->modify('+1 day');
        $endOfDay->setTime(16, 59, 59);
        
        // 文字列に変換
        $endOfDayString = $endOfDay->format('Y-m-d H:i:s');
        $startOfYesterdayString = $startOfYesterday->format('Y-m-d H:i:s');

        $sukiList = DB::table('sk_time_stamps')
            ->where('kbn', $kbn)
            ->where('created_at', '>=', $startOfYesterdayString)
            ->where('created_at', '<', $endOfDayString)
            ->orderBy('created_at', 'desc')
            ->get();

        $goodsList = null;
        if ($request->isInit == 1) {
            $goodsKbn = 0;
            if ($kbn == 0) {
                $goodsKbn = 2;
            } else {
                $goodsKbn = 3;
            }

            $goodsList = DB::table('sk_goods')->where('deleted', 1)->where('kbn', $goodsKbn)->orderBy('place', 'desc')->get();
        }

        return response()->json([
            'sukiList' => $sukiList,
            'st' => $startOfYesterday,
            'ed' => $endOfDay,
            'goodsList' => $goodsList,
        ]);
    }

    public function suki (Request $request) {
        $url = "https://suki-kira.com/people/result/%E3%81%97%E3%82%87%E3%81%BC%E3%81%99%E3%81%91";

        // cURLセッションの初期化
        $ch = curl_init();
        // データを抽出したいページのURLを指定
        curl_setopt($ch, CURLOPT_URL, $url);
        // 文字列で取得するように設定
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIE, "sk_92355=1");

        // URLの情報を取得する指示
        $result = curl_exec($ch);
        // cURLセッションの終了
        curl_close($ch);

        $getStr = '/<div class="text-muted comment_info text-primary" style="font-size:80%; margin: 0 0px 0 0px;">([^"]+).<span itemprop="author">/';

        //最新の番号を取得
        preg_match($getStr, $result, $matches);
        if (isset($matches[1])) {
            $latestNo = $matches[1];
        }

        $newData = new SkTimeStamp();
        $newData->no = $latestNo;
        $res = $newData->save();

        return response()->json([
            "result" => $result,
            "latest" => $latestNo,
            "res" => $res,
        ]);

        // //本文中から該当箇所を抽出
        // $id = "";
        // $auth1 = "";
        // $auth2 = "";
        // $authR = "";
        // preg_match('/<input type="hidden" name="id" value="([^"]+)">/', $result, $matches);
        // if (isset($matches[1])) {
        //     $id = $matches[1];
        // }
        // preg_match('/<input type="hidden" class="auth1" name="auth1" value="([^"]+)">/', $result, $matches);
        // if (isset($matches[1])) {
        //     $auth1 = $matches[1];
        // }
        // preg_match('/<input type="hidden" class="auth1" name="auth2" value="([^"]+)">/', $result, $matches);
        // if (isset($matches[1])) {
        //     $auth2 = $matches[1];
        // }
        // preg_match('/<input type="hidden" class="auth-r" name="auth-r" value="([^"]+)">/', $result, $matches);
        // if (isset($matches[1])) {
        //     $authR = $matches[1];
        // }
        // // dd($id . " " . $auth1 . " " . $auth2 . " " . $authR);

        // //送信
        // $url = "https://suki-kira.com/people/result/k4sen";
        // $post_data = array(
        //     'vote' => '1',
        //     'ok' => 'ok',
        //     'id' => $id,
        //     'auth1' => $auth1,
        //     'auth2' => $auth2,
        //     'authR' => $authR,
        // );

        // $ch = curl_init($url);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // $response = curl_exec($ch);

        // if (curl_errno($ch)) {
        //     echo 'cURLエラー: ' . curl_error($ch);
        // }

        // curl_close($ch);
        // dd($response);

        // return "AAA";
    }

    public function getMonitorInfo(Request $request) {
        // 現在時刻を取得
        $currentTime = new DateTime();
        $hour = (int)$currentTime->format('H');

        // 開始時刻と終了時刻の初期化
        $startTime = clone $currentTime;
        $endTime = clone $currentTime;

        // 17時前後の条件分岐
        if ($hour < 17) {
            // 17時前の場合、前日の17時から当日の17時まで
            $startTime->modify('-1 day')->setTime(17, 0, 0);
            $endTime->setTime(17, 0, 0);
        } else {
            // 17時以降の場合、当日の17時から現在まで
            $startTime->setTime(17, 0, 0);
        }

        // sk_time_stampsテーブルからデータを取得
        $shoboList = DB::table('sk_time_stamps')
        ->where('kbn', 0)
        ->where('created_at', '>=', $startTime)
        ->where('created_at', '<', $endTime)
        ->orderBy('created_at', 'desc')
        ->get();

        $fantaList = DB::table('sk_time_stamps')
        ->where('kbn', 1)
        ->where('created_at', '>=', $startTime)
        ->where('created_at', '<', $endTime)
        ->orderBy('created_at', 'desc')
        ->get();

        // 結果をJSON形式で返す
        return response()->json([
            'shoboList' => $shoboList,
            'fantaList' => $fantaList
        ]);
    }
}
