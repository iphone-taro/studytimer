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
        
        //しょぼ
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

        $getStr = '/style="font-size:80%; margin: 0 0px 0 0px;">([^"]+).<span itemprop="author">/';

        //最新の番号を取得
        preg_match($getStr, $result, $matches);
        if (isset($matches[1])) {
            $latestNo = $matches[1];
        }

        $newData = new SkTimeStamp();
        $newData->kbn = 0;
        $newData->no = $latestNo;
        $res = $newData->save();

        //ふぁ
        $urlF = "https://suki-kira.com/people/result/%E3%83%95%E3%82%A1%E3%83%B3%E5%A4%AA";

        // cURLセッションの初期化
        $chF = curl_init();
        // データを抽出したいページのURLを指定
        curl_setopt($chF, CURLOPT_URL, $urlF);
        // 文字列で取得するように設定
        curl_setopt($chF, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($chF, CURLOPT_COOKIE, "sk_97215=1");

        // URLの情報を取得する指示
        $resultF = curl_exec($chF);
        // cURLセッションの終了
        curl_close($chF);
        
        $getStrF = '/style="font-size:80%; margin: 0 0px 0 0px;">([^"]+).<span itemprop="author">/';

        //最新の番号を取得
        preg_match($getStrF, $resultF, $matchesF);
        if (isset($matchesF[1])) {
            $latestNoF = $matchesF[1];
        }
        
        $newData = new SkTimeStamp();
        $newData->kbn = 1;
        $newData->no = $latestNoF;
        $res = $newData->save();

        //起動時が5時かどうか
        // $chStart = new DateTime('08:12:00');
        // $chEnd = new DateTime('08:14:00');
        $chStart1 = new DateTime('05:00:00');
        $chEnd1 = new DateTime('05:02:00');
        $chStart2 = new DateTime('05:30:00');
        $chEnd2 = new DateTime('05:32:00');
        
        $chMiddleStart1 = new DateTime('23:00:00');
        $chMiddleEnd1 = new DateTime('23:02:00');
        $chMiddleStart2 = new DateTime('23:14:00');
        $chMiddleEnd2 = new DateTime('23:16:00');
        if ($chStart1 <= $now && $now < $chEnd1) {
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
            ->where('kbn', 0)
            ->orderBy('created_at', 'desc')
            ->get();
            
            $curNo = 0;
            $maxCount = 0;
            $maxDate = null;
            // echo count($sukiList) . "\n";

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
            // echo "max " . $maxCount . "\n";
            // echo "time " . $maxDate . "\n";
            
            $month = $getStart->format('n');
            $day = $getStart->format('j');
            
            $maxDateDate = new DateTime($maxDate);
            $hour = $maxDateDate->format('H'); // 24時間形式の時
            $minute = $maxDateDate->format('i'); // 分

            //投稿文
            $tweetText =
            "❀✿  " . $month . "月" . $day . "日の ストグラフ  ✿❀\n" .
            "\n" . 
            "本日のストグラでイチバン「好き嫌い.com」（しょぼ板）が盛り上がったのは！？\n" .
            "\n" .
            "【 " . $hour . "時" . $minute . "分 】（" . $maxCount . "投稿/2分間）\n" . 
            "\n" .
            "でしたー！\n"
            //  .
            // "#ストグラ #ストグラフ\n" .
            // "\n" .
            // "詳しくはこちら\n" .
            // "https://sutograph.net"
            ;

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

        } else if ($chStart2 <= $now && $now < $chEnd2) {
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
            ->where('kbn', 1)
            ->orderBy('created_at', 'desc')
            ->get();
            
            $curNo = 0;
            $maxCount = 0;
            $maxDate = null;
            // echo count($sukiList) . "\n";

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
            // echo "max " . $maxCount . "\n";
            // echo "time " . $maxDate . "\n";
            
            $month = $getStart->format('n');
            $day = $getStart->format('j');
            
            $maxDateDate = new DateTime($maxDate);
            $hour = $maxDateDate->format('H'); // 24時間形式の時
            $minute = $maxDateDate->format('i'); // 分

            //投稿文
            $tweetText =
            "❀✿  " . $month . "月" . $day . "日の ストグラフ  ✿❀\n" .
            "\n" . 
            "本日のストグラでイチバン「好き嫌い.com」（ノーリミ、ファン太板）が盛り上がったのは！？\n" .
            "\n" .
            "【 " . $hour . "時" . $minute . "分 】（" . $maxCount . "投稿/2分間）\n" . 
            "\n" .
            "でしたー！\n"
            //  .
            // "#ストグラ #ストグラフ #ノーリミ\n" .
            // "\n" .
            // "詳しくはこちら\n" .
            // "https://sutograph.net/nolimit"
            ;

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

        } else if ($chMiddleStart1 <= $now && $now < $chMiddleEnd1) {
            echo "YES\n";

            $getStart = new DateTime();
            $getStart->setTime(17, 0, 0);

            $getEnd = new DateTime();
            $getEnd->setTime(23, 0, 0);

            //稼働時間の情報を取得
            $sukiList = DB::table('sk_time_stamps')
            ->where('created_at', '>=', $getStart)
            ->where('created_at', '<=', $getEnd)
            ->where('kbn', 0)
            ->orderBy('created_at', 'desc')
            ->get();
            
            $curNo = 0;
            $maxCount = 0;
            $maxDate = null;
            // echo count($sukiList) . "\n";

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
            // echo "max " . $maxCount . "\n";
            // echo "time " . $maxDate . "\n";
            
            $month = $getStart->format('n');
            $day = $getStart->format('j');
            
            $maxDateDate = new DateTime($maxDate);
            $hour = $maxDateDate->format('H'); // 24時間形式の時
            $minute = $maxDateDate->format('i'); // 分

            //投稿文
            $tweetText =
            "❀✿  " . $month . "月" . $day . "日の ストグラフ  ✿❀\n" .
            "\n" . 
            "現在までのストグラでイチバン「好き嫌い.com」（しょぼ板）が盛り上がったのは！？\n" .
            "\n" .
            "【 " . $hour . "時" . $minute . "分 】（" . $maxCount . "投稿/2分間）\n" . 
            "\n" .
            "でしたー！\n"
            //  .
            // "#ストグラ #ストグラフ\n" .
            // "\n" .
            // "詳しくはこちら\n" .
            // "https://sutograph.net"
            ;

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

        } else if ($chMiddleStart2 <= $now && $now < $chMiddleEnd2) {
            echo "YES\n";

            $getStart = new DateTime();
            $getStart->setTime(17, 0, 0);

            $getEnd = new DateTime();
            $getEnd->setTime(23, 14, 0);

            //稼働時間の情報を取得
            $sukiList = DB::table('sk_time_stamps')
            ->where('created_at', '>=', $getStart)
            ->where('created_at', '<=', $getEnd)
            ->where('kbn', 1)
            ->orderBy('created_at', 'desc')
            ->get();
            
            $curNo = 0;
            $maxCount = 0;
            $maxDate = null;
            // echo count($sukiList) . "\n";

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
            // echo "max " . $maxCount . "\n";
            // echo "time " . $maxDate . "\n";
            
            $month = $getStart->format('n');
            $day = $getStart->format('j');
            
            $maxDateDate = new DateTime($maxDate);
            $hour = $maxDateDate->format('H'); // 24時間形式の時
            $minute = $maxDateDate->format('i'); // 分

            //投稿文
            $tweetText =
            "❀✿  " . $month . "月" . $day . "日の ストグラフ  ✿❀\n" .
            "\n" . 
            "現在までのストグラでイチバン「好き嫌い.com」（ノーリミ、ファン太板）が盛り上がったのは！？\n" .
            "\n" .
            "【 " . $hour . "時" . $minute . "分 】（" . $maxCount . "投稿/2分間）\n" . 
            "\n" .
            "でしたー！\n"
            //  .
            // "#ストグラ #ストグラフ #ノーリミ\n" .
            // "\n" .
            // "詳しくはこちら\n" .
            // "https://sutograph.net/nolimit"
            ;

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

        } else {
            echo "NO\n";
        }
    }
}
