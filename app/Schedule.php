<?php
namespace App;

// require__DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use DateTime;
use App\Models\SkTimeStamp;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Consts\Consts;

class Schedule {
    // cd /home/iphone-taro/Laravel/studytimerpj && /usr/local/bin/php artisan schedule:run 1> /dev/null

    public function __invoke() {
        // 現在時刻を取得
        $now = new DateTime();

        // // 17時と翌日5時のDateTimeオブジェクトを作成
        // $start = new DateTime('17:00');
        // $end = new DateTime('05:00 tomorrow');

        // // 現在時刻が17時から翌日5時の間にあるか確認
        // if ($now >= $start || $now < $end) {
        // }
        
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

        //起動時が5時かどうか
        $chStart = new DateTime('16:32:00');
        $chEnd = new DateTime('16:34:00');

        if ($chStart <= $now && $now < $chEnd) {
            echo "YES\n";

            $getStart = new DateTime();
            $getStart->modify("-1 day");
            $getStart->setTime(17, 0, 0);

            $getEnd = new DateTime();
            $getEnd->setTime(5, 0, 0);

            //稼働時間の情報を取得
            $sukiList = DB::table('sk_time_stamps')
            ->where('created_at', '>=', $getStart)
            ->where('created_at', '<=', $getEnd)
            ->orderBy('created_at', 'desc')
            ->get();
            
            $curNo = 0;
            $maxCount = 0;
            $maxDate = null;
            echo count($sukiList) . "\n";

            foreach ($sukiList as $data) {
                if ($curNo != 0) {
                    $count = $curNo - $data->no;

                    if ($maxCount < $count) {
                        $maxCount = $count;
                        $maxDate = $data->created_at;
                    }
                }
                $curNo = $data->no;
            }
            echo "max " . $maxCount . "\n";
            echo "time " . $maxDate . "\n";
            
            $maxDateDate = new DateTime($maxDate);
            $month = $maxDateDate->format('n');
            $day = $maxDateDate->format('j');
            $hour = $maxDateDate->format('H'); // 24時間形式の時
            $minute = $maxDateDate->format('i'); // 分

            //投稿文
            $tweetText =
            "꙳✧˖°⌖꙳✧˖°⌖꙳✧˖°⌖꙳✧˖°⌖꙳✧˖°\n" . 
            "  " . $month . "月" . $day . "日の ストグラフ  \n" .
            "꙳✧˖°⌖꙳✧˖°⌖꙳✧˖°⌖꙳✧˖°⌖꙳✧˖°\n" .
            "\n" . 
            "本日のストグラでイチバン「好き嫌い.com」が盛り上がったのは！？\n" .
            "\n" .
            "    【 " . $hour . "時" . $minute . "分 】（" . $maxCount . "投稿/2分間）\n" . 
            "\n" .
            "でしたー！\n" .
            "#ストグラ #ストグラフ\n" .
            "\n" .
            "詳しくはこちら\n" .
            "https://sutograph.net";

            echo $tweetText;
            //投稿処理
            $connection = new TwitterOAuth(
            Consts::API_KEY,
            Consts::API_KEY_SECRET,
            Consts::ACCESS_TOKEN,
            Consts::ACCESS_TOKEN_SECRET
            );

            $connection->setApiVersion('2');

            // $text = "Twitter APIテストです。";

            $result = $connection->post("tweets", ["text"=>$tweetText], ['jsonPayload'=>true]);

            $httpCode = $connection->getLastHttpCode();

            if ($httpCode == 201) { // 201は作成成功を示すステータスコード
                echo "success";
                $this->info("ツイートが送信されました！");
            } else {
                $errorMessage=isset($result->errors) ?json_encode($result->errors, JSON_UNESCAPED_UNICODE) :'不明なエラー';
                echo "ツイートの送信に失敗しました。HTTPコード:" . $httpCode . ", エラーメッセージ:" .$errorMessage;
            }
        } else {
            echo "NO\n";
        }
    }
}
