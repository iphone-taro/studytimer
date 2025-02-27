<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SkTimeStamp;

class SukiController extends Controller
{
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
}
