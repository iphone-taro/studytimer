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
use App\Models\Examinee;
use App\Models\Soudan;
use App\Models\Nandemo;
use App\Models\Guchi;
use App\Models\Stamp;
use App\Models\Response;
use App\Models\Sequence;
use App\Models\Violation;
use App\Models\Invisible;
use App\Models\Access;
use DateTime;

use Imagick;
use ImagickDraw;
use ImagickPixel;

class MainController extends Controller
{    
    public function test() {
        $text = "あいうえお\n\n\n\n\n\n\n\n\nあいうえお\n\n\nあいうえお\n\n\n\n\nあいうえお\n\n\n\n\n\nあいうえお\n";

        $pattern = "/(\R{3,})/u";

        $aaa = preg_replace("/(\R{3,})/u", "\n\n", $text);
        dd($aaa);
    }

    //
    //広告情報取得
    //
    public function getAbs(Request $request): JsonResponse {
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

        //すでに画像がある場合
        if (file_exists(realpath("./") . '/storage/card/' . $code . '.jpg')) {
            return view('spa.app')->with(['card' => $code . "jpg"]);
        }

        //未使用にある場合
        if (file_exists(realpath("./") . '/storage/card/unused/' . $code . '.jpg')) {
            //ファイルを移動
            if (rename(realpath("./") . '/storage/card/unused/' . $code . '.jpg', realpath("./") . '/storage/card/' . $code . '.jpg'))
            return view('spa.app')->with(['card' => $code . ".jpg"]);
        }

        //ない場合
        return view('spa.app')->with(['card' => "base.jpg"]);
    }

    //
    //通報リスト取得
    //
    public function getViolationList (Request $request) {

        //勉強報告
        $reportList = \DB::table('violations as vi')
        ->select('vi.id', 'vi.message', 'vi.created_at', 'vi.user_id as report_id', 
        'ta.user_id', 'ta.name', 'ta.kbn', 'ta.degree', 'ta.title', 'ta.message', 'ta.time', 'ta.is_delete', 'ta.created_at')
        ->leftJoin('reports as ta', 'vi.post_id', '=', 'ta.id')
        ->where('vi.post_kbn', 'REPORT')
        ->where('vi.status', 0)->get();

        //受験生
        $examineeList = \DB::table('violations as vi')
        ->select('vi.id', 'vi.message', 'vi.created_at', 'vi.user_id as report_id',
        'ta.user_id', 'ta.name', 'ta.ken', 'ta.choice', 'ta.degree', 'ta.body', 'ta.is_delete', 'ta.created_at')
        ->leftJoin('examinees as ta', 'vi.post_id', '=', 'ta.id')
        ->where('vi.post_kbn', 'EXAMINEE')
        ->where('vi.status', 0)->get();

        //愚痴
        $guchiList = \DB::table('violations as vi')
        ->select('vi.id', 'vi.message', 'vi.created_at', 'vi.user_id as report_id',
        'ta.user_id', 'ta.name', 'ta.degree', 'ta.body', 'ta.is_delete', 'ta.created_at')
        ->leftJoin('guchis as ta', 'vi.post_id', '=', 'ta.id')
        ->where('vi.post_kbn', 'GUCHI')
        ->where('vi.status', 0)->get();

        //相談
        $soudanList = \DB::table('violations as vi')
        ->select('vi.id', 'vi.message', 'vi.created_at', 'vi.user_id as report_id',
        'ta.user_id', 'ta.name', 'ta.degree', 'ta.body', 'ta.is_delete', 'ta.created_at')
        ->leftJoin('soudans as ta', 'vi.post_id', '=', 'ta.id')
        ->where('vi.post_kbn', 'SOUDAN')
        ->where('vi.status', 0)->get();

        //相談返信
        $responseList = \DB::table('violations as vi')
        ->select('vi.id', 'vi.message', 'vi.created_at', 'vi.user_id as report_id',
        'ta.user_id', 'ta.name', 'ta.degree', 'ta.body', 'ta.is_delete', 'ta.created_at')
        ->leftJoin('responses as ta', 'vi.post_id', '=', 'ta.id')
        ->where('vi.post_kbn', 'RESPONSE')
        ->where('vi.status', 0)->get();

        //なんでも
        $nandemoList = \DB::table('violations as vi')
        ->select('vi.id', 'vi.message', 'vi.created_at', 'vi.user_id as report_id',
        'ta.user_id', 'ta.name', 'ta.degree', 'ta.body', 'ta.is_delete', 'ta.created_at')
        ->leftJoin('nandemos as ta', 'vi.post_id', '=', 'ta.id')
        ->where('vi.post_kbn', 'NANDEMO')
        ->where('vi.status', 0)->get();

        return response()->json(['status' => Consts::API_SUCCESS, 
            'reportList' => $reportList,
            'examineeList' => $examineeList,
            'guchiList' => $guchiList,
            'soudanList' => $soudanList,
            'responseList' => $responseList,
            'nandemoList' => $nandemoList,
        ]);
    }

    public function updateThrowMgr (Request $request) {
        $id = $request->id;

        $update = \DB::table('violations')->where('id', $id)->update(['status' => 2]);

        return response()->json(['status' => Consts::API_SUCCESS]);
    }

    public function updateBlackMgr (Request $request) {
        $id = $request->id;
        $userId = $request->userId;

        $update = \DB::table('violations')->where('id', $id)->update(['status' => 1]);

        $data = \DB::table('invisibles')->where('user_id', $userId)->first();

        if ($data == null) {
            $newData = new Invisible();
            $newData->user_id = $userId;
            $newData->save();    
        }

        return response()->json(['status' => Consts::API_SUCCESS]);
    }

    //
    //初期処理
    //
    public function initAction (Request $request) {
        // $reportList = Report::where('study_time', '>', 6000)->where('kbn', '1')
        // ->select('title', 'study_time as time', 'created_at as date')->get();

        // return response()->json(['reportList' => $reportList]);
    }

    //
    //
    //
    public function shareAction (Request $request) {
        $img = $request->baseFile;

        if ($img == null) {
            //失敗
            return response()->json(['status' => Consts::API_FAILED_NODATA, 'errMsg' => ""]);
        }

        $img = str_replace('data:image/jpeg;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $fileData = base64_decode($img);
        
        //画像IDを決定
        $imgId = "";
        $randomStr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        while ($imgId == "") {
            for ($i = 0; $i < 20; $i++) {
                $ch = substr($randomStr, mt_rand(0, strlen($randomStr)) - 1, 1);
                $imgId = $imgId . $ch;
            }
            //重複チェック
            if (file_exists("../storage/app/public/card/unused/" . $imgId . ".jpg")) {
                $imgId = "";
            }
            if (file_exists("../storage/app/public/card/" . $imgId . ".jpg")) {
                $imgId = "";
            }
        }

        if (file_put_contents("../storage/app/public/card/unused/" . $imgId . ".jpg", $fileData)) {
            //成功
            return response()->json(['status' => Consts::API_SUCCESS, 'code' => $imgId]);
        } else {
            //失敗
            return response()->json(['status' => Consts::API_FAILED_NODATA, 'errMsg' => ""]);
        }
    }


    //
    //
    //
    public function insertPost (Request $request) {

        //バリデート
        try {
            $validated = $request->validate([
                'userId' => 'string',
                'postKbn' => 'required|string',
                'nickName' => 'required|string',
                'secret' => 'string',
                'body' => 'string',

                'title' => 'string',
                'message' => 'string',
                'kbn' => 'string',
                'time' => 'string',

                'ken' => 'numeric',
                'choice' => 'string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => $e->getMessage()]);
        }

        $postKbn = $request->postKbn;
        $nickName = trim($request->nickName);
        $userId = $request->userId;
        $secret = $request->secret;
        $degree = $request->degree;

        //入力チェック
        if ($nickName == "" || mb_strlen($nickName) > 20) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "ニックネームエラー"]);
        }

        if (mb_strlen($secret) > 5) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "削除番号エラー"]);
        }
        $userId = $this->chkUserId($userId);
        
        //運営設定
        if ($nickName == "運営愛ふぉん太郎") {
            $nickName = "まなぴよ運営";
            $userId = "manapiyo";
            $degree = 99;
        }
        //ランダム設定
        $check = strpos($nickName, "ランダム愛ふぉん太郎");
        if ($check === true && $check == 0) {
            $nickName = str_replace('ランダム愛ふぉん太郎', '', $nickName);
            $userId = $this->chkUserId("");
        }

        if ($postKbn == "REPORT") {
            $title = trim($request->title);
            if ($title == "" || mb_strlen($title) > 50) {
                return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "勉強内容エラー"]);
            }
            
            $message = trim($request->message);
            $message = preg_replace("/\r\n/", "\n", $message);
            $message = preg_replace("/(\R{3,})/u", "\n\n", $message);
            
            if (mb_strlen($message) > 300) {
                return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "勉強内容エラー"]);
            }

            $newData = new Report();
            
            $newData->kbn = $request->kbn;
            $newData->time = $request->time;
            $newData->title = $title;
            $newData->message = $message;
        } else if ($postKbn == "EXAMINEE") {
            $choice = trim($request->choice);
            if (mb_strlen($choice) > 30) {
                return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "志望校エラー"]);
            }

            $newData = new Examinee();
            $newData->ken = $request->ken;
            $newData->choice = $choice;
        } else if ($postKbn == "GUCHI") {
            $newData = new Guchi();
        } else if ($postKbn == "SOUDAN") {
            $newData = new Soudan();
            $currentDateTime = new DateTime();
            $formattedDateTime = $currentDateTime->format('Y-m-d H:i:s');
            $newData->response_at = $formattedDateTime;
        } else if ($postKbn == "NANDEMO") {
            $newData = new Nandemo();
        } else if ($postKbn == "RESPONSE") {
            $newData = new Response();

            $soudanId = $request->soudanId;
            
            //投稿番号を取得
            $seqData = \DB::table('sequences')
            ->where('soudan_id', $soudanId)->first();

            $newData->soudan_id = $soudanId;
            $newData->no = $seqData->no;
        } else {
            //エラー
            $newData = null;
        }

        if ($postKbn != "REPORT") {
            $body = trim($request->body);
            $body = preg_replace("/\r\n/", "\n", $body);
            $body = preg_replace("/(\R{3,})/u", "\n\n", $body);
            
            $bodyMaxLength = 400;
            if ($postKbn == "SOUDAN") {
                $bodyMaxLength = 2000;
            } else if ($postKbn == "RESPONSE") {
                $bodyMaxLength = 1000;
            }
            if (mb_strlen($body) > $bodyMaxLength) {
                return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "本文エラー"]);
            }
            $newData->body = $body;
        }

        $newData->user_id = $userId;
        $newData->name = $nickName;
        $newData->secret = $secret;
        $newData->degree = $degree;
        $newData->info = $_SERVER['REMOTE_ADDR'] . "/" . $_SERVER['HTTP_USER_AGENT'];
        
        $result = $newData->save();

        if ($result) {
            // $postCount = $this->retPostList($request, $userId, false);
            // $postList = $this->retPostList($request, $userId, true);

            if ($postKbn == "SOUDAN") {
                $seqData = new Sequence();
                $seqData->soudan_id = $newData->id;
                $seqData->no = 1;
                $seqData->save();

            } else if ($postKbn == "RESPONSE") {
                $abc = \DB::table('sequences')
                ->where('soudan_id', $soudanId)
                ->increment('no');

                $soudanData = Soudan::where('id', $soudanId)->first();

                $currentDateTime = new DateTime();
                $formattedDateTime = $currentDateTime->format('Y-m-d H:i:s');
                $soudanData->response_at = $formattedDateTime;
                $soudanData->save();
            }

            return response()->json(['status' => Consts::API_SUCCESS, 'userId' => $userId]);
        } else {
            return response()->json(['status' => Consts::API_FAILED_EXEPTION]);
        }
    }

    //
    //削除
    //
    public function deletePost (Request $request) {
        //バリデート
        try {
            $validated = $request->validate([
                'postKbn' => 'required|string',
                'postId' => 'required|string',
                'deleteNo' => 'required|string',
                'userId' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => $e->getMessage()]);
        }

        $postKbn = $request->postKbn;
        $postId = $request->postId;
        $deleteNo = $request->deleteNo;
        $userId = $request->userId;

        $tableName = Consts::retTableName($postKbn);
        
        $postData = \DB::table($tableName)->where('id', $postId)->where('user_id', $userId)->where('is_delete', 0)->first();

        if ($postData == null) {
            //データなし
            return response()->json(['status' => Consts::API_FAILED_NODATA, 'errMsg' =>""]);
        }
        
        if ($postData->secret != $deleteNo) {
            //データなし
            return response()->json(['status' => Consts::API_FAILED_MISMATCH, 'errMsg' =>""]);
        }

        //削除
        $delete = \DB::table($tableName)->where('id', $postId)->where('user_id', $userId)->update(['is_delete' => 1]);

        return response()->json(['status' => Consts::API_SUCCESS]);
    }

    //
    //通報
    //
    public function reportPost (Request $request) {
        //バリデート
        try {
            $validated = $request->validate([
                'postKbn' => 'required|string',
                'postId' => 'required|string',
                'message' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => $e->getMessage()]);
        }

        $postKbn = $request->postKbn;
        $postId = $request->postId;
        $message = $request->message;
        $userId = $request->userId;
        if ($userId == "") {
            $userId = "no name";
        }

        $tableName = Consts::retTableName($postKbn);

        $postData = \DB::table($tableName)->where('id', $postId)->first();

        if ($postData == null) {
            //データなし
            return response()->json(['status' => Consts::API_FAILED_NODATA, 'errMsg' =>""]);
        }
        $violationData = new Violation();
        $violationData->post_kbn = $postKbn;
        $violationData->post_id = $postId;
        $violationData->message = $message;
        $violationData->user_id = $userId;
        $violationData->info = $_SERVER['REMOTE_ADDR'] . "/" . $_SERVER['HTTP_USER_AGENT'];
        $violationData->save();

        return response()->json(['status' => Consts::API_SUCCESS]);
    }

    public function retPostList(Request $request, $userId, $isList) {
        $postKbn = $request->postKbn;
        $page = $request->page;
        $keyword = $request->keyword;
        $degree = $request->degree;
        $degreeArray = json_decode($degree);

        $tableName = Consts::retTableName($postKbn);

        $stampCountSub = \DB::table('stamps')
        ->select('post_id')
        ->selectRaw('count(*) as stamp_count')
        ->groupBy('post_id', 'kbn')
        ->where('kbn', $postKbn);

        $stampMySub = \DB::table('stamps')
        ->select('post_id', 'user_id as my_flg')
        ->where('kbn', $postKbn)
        ->where('user_id', $userId);

        $invisibleSub = \DB::table('invisibles')
        ->where('user_id', '<>', $userId);

        $query = \DB::table($tableName)
        ->leftJoinSub($invisibleSub, 'invisibles', function ($join) use ($tableName) {
            $join->on('invisibles.user_id', '=', $tableName . '.user_id');
        })
        ->whereIn('degree', $degreeArray)
        ->where('is_delete', 0)
        ->where(function($query) use ($userId) {
            $query->whereNull('invisibles.user_id')
            ->orWhere('invisibles.user_id', "=", $userId);
        });
        
        //順番
        if ($postKbn == "SOUDAN") {
            $query->orderBy($tableName . '.response_at', 'desc');
            $query->orderBy($tableName . '.created_at', 'desc');
        } else {
            $query->orderBy($tableName . '.created_at', 'desc');
        }
        //セレクト句
        if ($postKbn == "REPORT") {
            $query->select($tableName . ".id", $tableName . ".user_id", $tableName . ".name", $tableName . ".degree", $tableName . ".secret", $tableName . ".created_at", "stamp_sub1.stamp_count", "stamp_sub2.my_flg", $tableName . ".kbn", $tableName . ".title", $tableName . ".message", $tableName . ".time");
        } else if ($postKbn == "EXAMINEE") {
            $query->select($tableName . ".id", $tableName . ".user_id", $tableName . ".name", $tableName . ".degree", $tableName . ".secret", $tableName . ".created_at", "stamp_sub1.stamp_count", "stamp_sub2.my_flg", $tableName . ".body", $tableName . ".ken", $tableName . ".choice");
        } else if ($postKbn == "GUCHI") {
            $query->select($tableName . ".id", $tableName . ".user_id", $tableName . ".name", $tableName . ".degree", $tableName . ".secret", $tableName . ".created_at", "stamp_sub1.stamp_count", "stamp_sub2.my_flg", $tableName . ".body");
        } else if ($postKbn == "SOUDAN") {
            $query->select($tableName . ".id", $tableName . ".user_id", $tableName . ".name", $tableName . ".degree", $tableName . ".secret", $tableName . ".created_at", $tableName . ".body", "res.res_count", "res.res_date");
        } else if ($postKbn == "RESPONSE") {
            $query->select($tableName . ".id", $tableName . ".user_id", $tableName . ".name", $tableName . ".degree", $tableName . ".secret", $tableName . ".created_at", "stamp_sub1.stamp_count", "stamp_sub2.my_flg", $tableName . ".body", $tableName . ".soudan_id");
        } else if ($postKbn == "NANDEMO") {
            $query->select($tableName . ".id", $tableName . ".user_id", $tableName . ".name", $tableName . ".degree", $tableName . ".secret", $tableName . ".created_at", "stamp_sub1.stamp_count", "stamp_sub2.my_flg", $tableName . ".body");
        }

        if ($postKbn != "SOUDAN") {
            $query->leftJoinSub($stampCountSub, 'stamp_sub1', function ($join) use ($tableName) {
                $join->on($tableName . '.id', '=', 'stamp_sub1.post_id');
            })
            ->leftJoinSub($stampMySub, 'stamp_sub2', function ($join) use ($tableName) {
                $join->on($tableName . '.id', '=', 'stamp_sub2.post_id');
            });
        }

        if ($postKbn == "RESPONSE") {
            $soudanId = $request->soudanId;
            $query->where($tableName . '.soudan_id', $soudanId);
        }

        if ($postKbn == "EXAMINEE") {
            //受験生
            $kenStr = $request->kens;
            $kenArray = explode(",", $kenStr);
            $query->whereIn('ken', $kenArray);

            $wordArray = explode(" ", $keyword);
            for ($i = 0; $i < count($wordArray); $i++) {
                if ($wordArray[$i] != "") {
                    $word = $wordArray[$i];
                    
                    //通常の検索
                    $query->where(function($query) use ($word) {
                        $query->where("examinees.name", 'like', "%" . $word . "%")
                        ->orWhere('examinees.body', "like", "%" . $word . "%");
                    });
                }
            }
        } else if ($postKbn == "SOUDAN") {
            //相談　レス数を取得
            $queryResSub = \DB::table('responses')
            ->select('soudan_id')
            ->selectRaw('count(*) as res_count')
            ->selectRaw('MAX(created_at) as res_date')
            ->groupBy('soudan_id');

            $query->leftJoinSub($queryResSub, 'res', function ($join) use ($tableName) {
                $join->on($tableName . '.id', '=', 'res.soudan_id');
            });

            if ($request->keyword != null) {
                //相談返信用の相談取得
                $query->where($tableName . '.id', $request->keyword);
            }
        }

        // dd(preg_replace_array('/\?/', $query->getBindings(), $query->toSql()));
        if ($isList) {
            //リストを返却
            if ($page == -1) {
                //トップ用の新着
                $postList = $query->take(5)->get();
            } else if ($page == -2) {
                //ポップアップ用のデータ（1件のみ）
                $query->where($tableName . '.id', $keyword);
                $postList = $query->get();
            } else {
                //各種ページ
                $postList = $query->skip(($page - 1) * 20)->take(20)->get();

                //削除コードの隠蔽
                foreach ($postList as $data) {
                    if ($data->secret != "") {
                        $data->secret = "del";
                    }
                }
            }

            //本文と一言の処理
            for ($i=0; $i < count($postList); $i++) { 
                if (property_exists($postList[$i], "name")) {
                    //変換 名前
                    $postList[$i]->name = str_replace("<", "&lt;", $postList[$i]->name);
                    // $profile = preg_replace("/\r\n/", "\n", $profile);
                }
                if (property_exists($postList[$i], "body")) {
                    //変換 本文
                    $postList[$i]->body = str_replace("<", "&lt;", $postList[$i]->body);
                    // $profile = preg_replace("/\r\n/", "\n", $profile);
                }
                if (property_exists($postList[$i], "message")) {
                    //変換 メッセージ
                    $postList[$i]->message = str_replace("<", "&lt;", $postList[$i]->message);
                }
            }
            
            //順序を入れ替える 相談と新着以外
            if ($postKbn != 'SOUDAN') {
                $postList = $postList->reverse()->values();
            }
            // dd(preg_replace_array('/\?/', $query->getBindings(), $query->toSql()));

            //フィルタリング
            for ($i=0; $i < count($postList); $i++) { 
                $data = $postList[$i];

                //名前
                $data->name = Consts::retFiltering($data->name);

                if ($postKbn == "REPORT") {
                    $data->title = Consts::retFiltering($data->title);
                    $data->message = Consts::retFiltering($data->message);
                } else {
                    $data->body = Consts::retFiltering($data->body);
                }

                if ($postKbn == "EXAMINEE") {
                    $data->choice = Consts::retFiltering($data->choice);
                }
            }

            return $postList;
        } else {
            //総件数を返却
            $postCount = $query->count();
            return $postCount;
        }
    }

    //
    //一覧取得
    //
    public function getPostList (Request $request) {
        $userId = $request->userId;
        
        $userId = $this->chkUserId($userId);

        $array = array();
        //相談返信の場合存在チェック
        if ($request->postKbn == "RESPONSE") {

            $requestS = new Request();
            $requestS->postKbn = "SOUDAN";
            $requestS->keyword = $request->soudanId;
            $requestS->page = 0;
            $requestS->degree = $request->degree;

            $soudanList = $this->retPostList($requestS, $userId, true);

            if (count($soudanList) != 1) {
                return response()->json(['status' => Consts::API_FAILED_NODATA, 'errMsg' => '']);
            }
            $array = $array + array('soudanData' => $soudanList[0]);
        }

        $postList = $this->retPostList($request, $userId, true);
        $postCount = $this->retPostList($request, $userId, false);

        $array = $array + array('status' => Consts::API_SUCCESS, 'postList' => $postList, 'postCount' => $postCount, 'userId' => $userId);
        return response()->json($array);

    }

    //
    //一覧取得
    //
    public function getListRowData (Request $request) {
        $userId = $request->userId;
        
        $userId = $this->chkUserId($userId);
        $postDataList = $this->retPostList($request, $userId, true);

        $postData = null;
        if (count($postDataList) == 1) {
            $postData = $postDataList[0];
        }

        return response()->json(['status' => Consts::API_SUCCESS, 'postData' => $postData]);
    }

    //
    //トップ画面用の全新着を取得
    //
    public function getLatestPostList (Request $request) {
        $userId = $request->userId;
       
        $userId = $this->chkUserId($userId);

        //アクセスカウント
        $currentDateTime = new DateTime();
        $formattedDateTime = $currentDateTime->format('Y-m-d');
        $dateData = Access::where('date', $formattedDateTime)->first();
        if ($dateData == null) {
            //新規
            $dateData = new Access();
            $dateData->date = $formattedDateTime;
            $dateData->save();
        } else {
            //1追加
            $dateData->count = $dateData->count() + 1;
            $dateData->save();
        }

        $request->postKbn = "REPORT";
        $reportList = $this->retPostList($request, $userId, true);
        $request->postKbn = "EXAMINEE";
        $examineeList = $this->retPostList($request, $userId, true);
        $request->postKbn = "GUCHI";
        $guchiList = $this->retPostList($request, $userId, true);
        $request->postKbn = "SOUDAN";
        $soudanList = $this->retPostList($request, $userId, true);
        $request->postKbn = "NANDEMO";
        $nandemoList = $this->retPostList($request, $userId, true);

        return response()->json(['status' => Consts::API_SUCCESS, 
        'reportList' => $reportList,
        'examineeList' => $examineeList,
        'guchiList' => $guchiList,
        'soudanList' => $soudanList,
        'nandemoList' => $nandemoList,
        'userId' => $userId]);
    }

    //
    // ユーザーIDチェック
    //
    public function chkUserId ($userId) {
        if ($userId == "") {
            //IDを生成
            $randomStr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $userId = "";
            while($userId == "") {
                for ($i = 0; $i < 30; $i++) {
                    $ch = substr($randomStr, mt_rand(0, strlen($randomStr)) - 1, 1);
                    $userId = $userId . $ch;
                }
                // //重複チェック
                // $checkData = DB::table('reports')->where('user_id', $userId)->first();
                // if ($checkData != null) {
                //     $userId = "";
                // }
            }
            return $userId;
        }

        return $userId;
        
    }

    //
    //スタンプ
    //
    public function addStamp (Request $request) {
        $postId = $request->postId;
        $postKbn = $request->postKbn;
        $userId = $request->userId;
        $stampId = $request->stampId;

        $userId = $this->chkUserId($userId);
        
        $tableName = Consts::retTableName($postKbn);

        $postData = \DB::table($tableName)->where('id', $postId)->first();

        if ($postData == null) {
            return response()->json(['status' => Consts::API_FAILED_NODATA, 'errMsg' => '']);
        }

        $stampData = new Stamp();
        $stampData->post_id = $postId;
        $stampData->kbn = $postKbn;
        $stampData->user_id = $userId;
        $stampData->stamp_id = $stampId;
        $stampData->save();

        return response()->json(['status' => Consts::API_SUCCESS, 'userId' => $userId]);
    }
}
