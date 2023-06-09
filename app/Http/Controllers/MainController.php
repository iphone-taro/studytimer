<?php

namespace App\Http\Controllers;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Consts\Consts;
use Carbon\Carbon;
use App\Models\Report;

use Imagick;
use ImagickDraw;
use ImagickPixel;

class MainController extends Controller
{    
    //
    //広告情報取得
    //
    public function getAbs(): JsonResponse {
        $json = file_get_contents("../resources/abs.json");
        $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
        $arr = json_decode($json,true);
        return response()->json(['abs' => $arr]);
    }
    
    //
    //投稿ページ初期処理
    //
    public function access ($code) {
        //DBから情報を取得
        $reportData = Report::where("code", $code)->first();

        if ($reportData == null) {
            //存在しないコードの場合
            return view('spa.app')->with(['title' => Consts::BASE_TITLE, 'card' => 'card_common']);
        }

        Report::where("code", $code)->increment('view_count');

        //すでに画像がある場合
        // if (file_exists(realpath("./") . '/storage/card/card_' . $code . '.jpg')) {
        //     return view('spa.app')->with(['title' => "使いまわし" . $code, 'card' => 'card_' . $code]);
        // }

        $cardFrameIndex = $reportData->frame_index;
        $cardKbn = $reportData->kbn; //0:開始 1:終了
        $title = $reportData->title;
        $studyTime = (int)$reportData->study_time;

        //カードを作成
        //フレーム
        if (strpos($cardFrameIndex, "#") === false) {
            //画像指定
            $cardBase = new Imagick(realpath("./") . '/app/img/frame/card_base_' . $cardFrameIndex . '.png');
        } else {
            //色指定
            $cardBase = new Imagick();
            $cardBase->newImage(600, 314, new ImagickPixel($cardFrameIndex));
        }

        $cardBase2 = new Imagick(realpath("./") . '/app/img/frame/card_base_front.png');
        $cardBase->compositeImage($cardBase2, $cardBase2->getImageCompose(), 0, 0);

        $draw = new ImagickDraw();
        //文字
        //目的
        $draw->setFont(realpath("./") . "/storage/ZenMaruGothic-Regular.ttf");
        $draw->setFillColor("rgb(58, 58, 58)");
        $draw->setTextInterlineSpacing(2);
        $draw->setGravity(Imagick::GRAVITY_CENTER); 

        $titleWidth = 600 * 0.75;
        $array = array();
        $fontVal = 40;
        $flg = true;
        while ($flg) {
            $draw->setFontSize($fontVal);
            $metrics = $cardBase->queryFontMetrics($draw, $title);
            $array[] = $fontVal . " " . $metrics['textWidth'];
            
            if ($metrics["textWidth"] > $titleWidth) {
                $fontVal--;
                if ($fontVal == 24) {
                    $flg = false;
                }
            } else {
                $flg = false;
            }
        }

        if ($fontVal == 24) {
            //折り返しが必要
            $draw->setFontSize(25);

            //1文字ずつ追加して、サイズを超えたら改行を入れる？
            $newStr = "";
            for ($i=0; $i < mb_strlen($title); $i++) { 
                $str = $newStr . mb_substr($title, $i, 1);
                $metrics = $cardBase->queryFontMetrics($draw, $str);

                if ($metrics["textWidth"] > $titleWidth) {
                    //超えたら前に改行を入れる
                    $str = $newStr . "\n" . mb_substr($title, $i, 1);
                }
                $newStr = $str;
            }
            $title = $newStr;
        }
        $cardBase->annotateImage($draw, 0, -60, 0, $title);

        $draw->setFontSize(20);
        if ($cardKbn == 0) {
            //開始
            $cardBase->annotateImage($draw, 0, 18, 0, "の勉強を開始しました");
        } else if ($cardKbn == 1) {
            //終了
            $cardBase->annotateImage($draw, 0, 18, 0, "の勉強を終了しました");

            //時間の整形
            $timeStr = "";
            $hours = floor($studyTime / 3600); // 時間
            $minutes = floor(($studyTime % 3600) / 60); // 分
            $seconds = $studyTime % 60; // 秒

            if ($hours != 0) {
                $timeStr = $timeStr . $hours . "時間";
            }
            if ($minutes != 0) {
                $timeStr = $timeStr . $minutes . "分";
            }
            if ($seconds != 0) {
                $timeStr = $timeStr . $seconds . "秒";
            }

            $draw->setFontSize(20);
            $draw->setGravity(Imagick::GRAVITY_SOUTHEAST );
            $cardBase->annotateImage($draw, 30, 55, 0, $timeStr);
        }

        $cardBase->writeImage(realpath("./") . '/storage/card/card_' . $code . '.jpg');

        $reportData->is_access = 1;
        $reportData->save();

        //アクセス前に作成された画像のデータを削除
        // $otherReportList = Report::where("code", '<>', $code)->where("is_access", "1")->get();
        // foreach ($otherReportList as $data) {
        //     //ファイルが存在する場合は削除
        //     $oCode = $data->code;
        //     if (file_exists(realpath("./") . '/storage/card/card_' . $oCode . '.jpg')) {
        //         //削除
        //         $delFlg = unlink(realpath("./") . '/storage/card/card_' . $oCode . '.jpg');
        //         if ($delFlg) {
        //             $data->is_access = 2;
        //             $data->save();
        //         }
        //     }
        // }

        return view('spa.app')->with(['title' => "新規作成" . $code, 'card' => 'card_' . $code]);
    }

    //
    //初期処理
    //
    public function initAction (Request $request) {
        $reportList = Report::where('study_time', '>', 6000)->where('kbn', '1')
        ->select('title', 'study_time as time', 'created_at as date')->get();

        return response()->json(['reportList' => $reportList]);
    }

    //
    //報告追加
    //
    public function reportStudy (Request $request) {
        //バリデート
        try {
            $validated = $request->validate([
                'titleName' => 'required|string',
                'kbn' => 'required|string',
                'frameIndex' => 'required|string',
                'studyTime' => 'string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => $e->getMessage()]);
        }

        $titleName = $request->titleName;
        $kbn = $request->kbn;
        $frameIndex = $request->frameIndex;

        //IDを生成
        $randomStr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $codeStr = "";
        while($codeStr == "") {
            for ($i = 0; $i < 15; $i++) {
                $ch = substr($randomStr, mt_rand(0, strlen($randomStr)) - 1, 1);
                $codeStr = $codeStr . $ch;
            }
            //重複チェック
            $checkData = DB::table('reports')->where('code', $codeStr)->first();
            if ($checkData != null) {
                $codeStr = "";
            }
        } 

        //データを保存
        $newData = new Report();
        $newData->code = $codeStr;
        $newData->title = $titleName;
        $newData->kbn = $kbn;
        $newData->frame_index = $frameIndex;

        if ($kbn == "1") {
            //勉強時間を設定
            $newData->study_time = $request->studyTime;
        }

        $result = $newData->save();

        if ($result) {
            return response()->json(['status' => Consts::API_SUCCESS, 'code' => $codeStr]);
        } else {
            return response()->json(['status' => Consts::API_FAILED_EXEPTION]);
        }
    }
}
