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

class MainController extends Controller
{

    public function abc () {
        $title = "あいうえおあいうえおあいうえおあいうえおあいうえおあいうえおあいうえお";
        $cardBase = new Imagick();
        $draw = new ImagickDraw();
        $draw->setFont(realpath("./") . "/storage/KosugiMaru-Regular.ttf");
        $draw->setFillColor("rgb(136, 136, 136)");
        $draw->setTextInterlineSpacing(5);
        $draw->setGravity(Imagick::GRAVITY_CENTER);

        $titleWidth = 600 * 0.75;
        $array = array();
        $fontVal = 54;
        $flg = true;
        while ($flg) {
            $draw->setFontSize($fontVal);
            $metrics = $cardBase->queryFontMetrics($draw, $title);
            $array[] = $fontVal . " " . $metrics['textWidth'];
            
            if ($metrics["textWidth"] > $titleWidth) {
                $fontVal--;
                if ($fontVal == 29) {
                    $flg = false;
                }
            } else {
                $flg = false;
            }
        }

        if ($fontVal == 29) {
            //折り返しが必要
            $draw->setFontSize(30);

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
        dd($fontVal);
    }

    //
    //投稿ページ初期処理
    //
    public function test ($code) {
        //DBから情報を取得
        $reportData = Report::where("code", $code)->first();

        if ($reportData == null) {
            //存在しないコードの場合
            return view('spa.app')->with(['title' => "共通です", 'card' => 'card_common']);
        }

        // //すでに画像がある場合
        // if (file_exists(realpath("./") . '/storage/card/card_' . $code . '.jpg')) {
        //     return view('spa.app')->with(['title' => "使いまわし" . $code, 'card' => 'card_' . $code]);
        // }

        $cardFrameIndex = $reportData->frame_index;
        $cardKbn = $reportData->kbn; //0:開始 1:終了
        $title = $reportData->title;
        $studyTime = (int)$reportData->study_time;

        //カードを作成
        //フレーム
        $cardBase = new Imagick(realpath("./") . '/app/img/frame/card_base_' . $cardFrameIndex . '.png');
        $draw = new ImagickDraw();
        //文字
        //目的
        $draw->setFont(realpath("./") . "/storage/KosugiMaru-Regular.ttf");
        $draw->setFillColor("rgb(136, 136, 136)");
        $draw->setTextInterlineSpacing(5);
        $draw->setGravity(Imagick::GRAVITY_CENTER);

        $title = "あいうえおあいうえおあいうえおあいうえおあ";

        $titleWidth = 600 * 0.75;
        $array = array();
        $fontVal = 54;
        $flg = true;
        while ($flg) {
            $draw->setFontSize($fontVal);
            $metrics = $cardBase->queryFontMetrics($draw, $title);
            $array[] = $fontVal . " " . $metrics['textWidth'];
            
            if ($metrics["textWidth"] > $titleWidth) {
                $fontVal--;
                if ($fontVal == 29) {
                    $flg = false;
                }
            } else {
                $flg = false;
            }
        }

        if ($fontVal == 29) {
            //折り返しが必要
            $draw->setFontSize(30);

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

        $draw->setFontSize(30);
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
            $cardBase->annotateImage($draw, 70, 60, 0, $timeStr);
        }

        $cardBase->writeImage(realpath("./") . '/storage/card/card_' . $code . '.jpg');

        $reportData->is_access = 1;
        $reportData->save();

        return view('spa.app')->with(['title' => "新規作成" . $code, 'card' => 'card_' . $code]);
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
