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

        //すでに画像がある場合
        if (file_exists(realpath("./") . '/storage/card/card_' . $code . '.jpg')) {
            return view('spa.app')->with(['title' => "使いまわし" . $code, 'card' => 'card_' . $code]);
        }

        $cardFrameIndex = $reportData->frame_index;
        $cardKbn = $reportData->kbn; //0:開始 1:終了
        $title = $reportData->title;
        $studyTime = $reportData->study_time;

        //カードを作成
        //フレーム
        $cardBase = new Imagick(realpath("./") . '/app/img/frame/card_base_' . $cardFrameIndex . '.png');
        $draw = new ImagickDraw();
        //文字
        //目的
        $draw->setFont(realpath("./") . "/app/GenEiKoburiMin6-R.ttf");
        $draw->setFillColor("black");
        $draw->setTextInterlineSpacing(5);
        $draw->setGravity(Imagick::GRAVITY_CENTER);

        $draw->setFontSize(70);
        $cardBase->annotateImage($draw, 0, -60, 0, $title);

        $draw->setFontSize(40);
        if ($cardKbn == 0) {
            //開始
            // $cardBase->annotateImage($draw, 0, 50, 0, "の勉強を開始しました");
            $cardBase->annotateImage($draw, 0, 50, 0, "を開始しました");
        } else if ($cardKbn == 1) {
            //終了
            // $cardBase->annotateImage($draw, 0, 50, 0, "の勉強を終了しました");
            $cardBase->annotateImage($draw, 0, 50, 0, "を終了しました");

            //時間の整形
            $timeStr = $studyTime;
            $draw->setFontSize(30);
            $draw->setGravity(Imagick::GRAVITY_SOUTHEAST );
            $cardBase->annotateImage($draw, 60, 60, 0, $timeStr);
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
