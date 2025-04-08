<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShikuUser;
use Illuminate\Support\Facades\DB;
use DateTime;

class ShikuController extends Controller
{
    public function init(Request $request) {
        $userId = $request->userId;
        $userData = null;

        if ($userId == "") {
            //初期状態
            //適当なIDを振る
            //ユーザーIDを作成
            $randomStr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $userId = "";

            while ($userId == "") {
                for ($i = 0; $i < 5; $i++) {
                    $ch = substr($randomStr, mt_rand(0, strlen($randomStr)) - 1, 1);
                    $userId = $userId . $ch;
                }

                //重複チェック
                $checkData = DB::table('shiku_users')->where('user_id', $userId)->first();

                if ($checkData != null) {
                    $userId = "";
                } else {
                    $userData = new ShikuUser();
                    $userData->user_id = $userId;
                    $userData->info = "{}";
                    $userData->guest = 1;
                    $userData->save();
                }
            }
        } else {
            $userData = DB::table('shiku_users')->where('user_id', $userId)->first();
        }

        return response()->json([
            'userData' => $userData,
        ]);
    }

    public function regist(Request $request) {
        $userId = $request->userId;
        $isConfirm = $request->isConfirm;
        $curUserId = $request->currentUserId;

        //存在チェック
        $userData = ShikuUser::where('user_id', $userId)->first();
        if ($userData != null) {
            //同一のユーザーIDがある場合
            $info = $request->info;

            //上書きの同意があるか
            if ($isConfirm == 1) {
                //同意あり 上書き先とデータをマージして、元のユーザーを削除
                $mergeInfo = $this->mergeInfo($userData->info, $info);
                $userData->info = $mergeInfo;
                $userData->save();

                $delUserData = ShikuUser::where('user_id', $curUserId)->delete();
                
                return response()->json([
                    'status' => "merge",
                    'userData' => $userData,
                ]);
            } else {
                //同意を求めるために一度バック
                return response()->json([
                    'status' => "confirm",
                ]);
            }
        } else {
            //ない場合　ユーザーIDを更新して終了
            $userData = ShikuUser::where('user_id', $curUserId)->first();

            $userData->user_id = $userId;
            $userData->guest = 0;
            $userData->save();

            return response()->json([
                'status' => "update",
                'userData' => $userData,
            ]);
        }
    }

    public function clear(Request $request) {
        $userId = $request->userId;
        $clearInfo = $request->clearInfo;

        //存在チェック
        $userData = ShikuUser::where('user_id', $userId)->first();
        // return response()->json([
        //     'status' => $userData->info,
        //     'userData' => $clearInfo,
        // ]);

        $mergeInfo = $this->mergeInfo($userData->info, $clearInfo);
        $userData->info = $mergeInfo;
        $userData->save();

        return response()->json([
            'status' => "success",
            'userData' => $userData,
        ]);
    }

    /**
     * データ構造内から 'time' の値を探すヘルパー関数
     * 'time' が直下にあるか、'normal' のようなサブキーの下にあるかをチェック
     *
     * @param array $dataItem Prefecture or Test data item
     * @return int|float|null Time value if found, otherwise PHP_INT_MAX
     */
    function findTimeValue(array $dataItem): int|float|null {
        // Case 1: 'time' key exists directly under the item (like "Test1")
        if (isset($dataItem['time']) && is_numeric($dataItem['time'])) {
            return $dataItem['time'];
        }

        // Case 2: Check one level deeper for keys like 'normal', 'hard' etc.
        foreach ($dataItem as $subItem) {
            if (is_array($subItem) && isset($subItem['time']) && is_numeric($subItem['time'])) {
                // 最初に見つかった 'time' を返す (例: 'normal' の time)
                return $subItem['time'];
            }
        }

        // 'time' が見つからない場合は、比較で不利になるように非常に大きな値を返す
        return PHP_INT_MAX;
    }

    public function mergeInfo(String $info1, String $info2) {
        if ($info1 == "") {
            $info1 = "{}";
        }
        if ($info2 == "") {
            $info2 = "{}";
        }
        $data1 = json_decode($info1, true);
        $data2 = json_decode($info2, true);

        // マージ結果を格納する配列を初期化 (data1をベースにする)
        $mergedData = $data1;

        // data2 の内容を mergedData にマージしていく
        foreach ($data2 as $key => $value2) {
            // data1 (mergedData) に同じキーが存在するかチェック
            if (array_key_exists($key, $mergedData)) {
                // キーが存在する場合、time を比較する
                $value1 = $mergedData[$key];

                $time1 = $this->findTimeValue($value1);
                $time2 = $this->findTimeValue($value2);

                // data2 の time の方が小さい場合、data2 のデータで上書きする
                // どちらか一方または両方に time が存在しない場合も考慮 (PHP_INT_MAX で処理)
                if ($time2 < $time1) {
                    $mergedData[$key] = $value2;
                }
                // time1 <= time2 の場合、または time が見つからない場合は、
                // 既存のデータ (data1由来) を維持するので、何もしない
            } else {
                // キーが存在しない場合は、data2 のデータをそのまま追加する
                $mergedData[$key] = $value2;
            }
        }

        // マージされた結果をJSON文字列にエンコード
        // JSON_UNESCAPED_UNICODE: 日本語が文字化けしないようにする
        // JSON_PRETTY_PRINT: 人間が読みやすいように整形する (任意)
        $mergedJsonString = json_encode($mergedData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // 結果を出力
        return $mergedJsonString;
    }

}
