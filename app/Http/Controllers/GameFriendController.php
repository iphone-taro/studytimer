<?php

namespace App\Http\Controllers;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Game;
use App\Models\Post;
use App\Models\Report;
use App\Models\Mute;
use App\Models\User;
use App\Consts\Consts;
use Carbon\Carbon;

class GameFriendController extends Controller
{
    public function test (Request $request) {
        dd($request->ip());
        // $str = "
        // ";

        // // $str = preg_replace("/【.*?】/", "", $str);
        // // // $str = preg_replace("/\(.*?\)/", "", $str);
        // // $str = str_replace("(", "", $str);
        // // $str = str_replace(")", "", $str);
        // $str = str_replace("１", "1", $str);
        // $str = str_replace("２", "2", $str);
        // $str = str_replace("３", "3", $str);
        // $str = str_replace("４", "4", $str);
        // $str = str_replace("５", "5", $str);
        // $str = str_replace("６", "6", $str);
        // $str = str_replace("７", "7", $str);
        // $str = str_replace("８", "8", $str);
        // $str = str_replace("９", "9", $str);
        // $str = str_replace("０", "0", $str);
        
        // dd($str);

    }

    //
    //投稿ページ初期処理
    //
    public function viewPost ($gameId) {
        $title = Consts::BASE_TITLE;
        $description = Consts::BASE_DESCRIPTION;
        $seo = Consts::BASE_SEO;

        if ($gameId != "") {
            //ゲーム情報を取得
            $gameInfo = DB::table('games')->where('game_id', $gameId)->first();

            if ($gameInfo != null) {
                //ゲームがある場合
                $title = $gameInfo->game_name . " " . $title;
                $description = $gameInfo->game_name . "のフレンドコード交換掲示板です。" . $description;
                $seo = $gameInfo->game_name . "," . $gameInfo->game_name_sub . "," . $seo;
            }
        }
        return view('spa.app')->with(['title' => $title, 'description' => $description, 'seo' => $seo]);
    }

    //
    //NGワード置換
    //
    public function replaceNg ($str) {
        for ($i=0; $i < count(Consts::NG_WORDS); $i++) { 
            $word = Consts::NG_WORDS[$i];
            $str = str_replace($word, '***', $str);
        }
        $str = preg_replace("/(\r\n){3,}|\r{3,}|\n{3,}/", "\n\n", $str);
        // $str = str_replace("\r\n\r\n\r\n", "\r\n\r\n", $str);
        // $str = str_replace("\r\r\r", "\r\r", $str);
        // $str = str_replace("\n\n\n", "\n\n", $str);
        return $str;
    }

    //
    //ゲームの一覧取得
    //
    public function getGameList (Request $request) {
        $userId = $request->userId;

        $retUserId = "";
        if ($userId == "") {
            $retUserId = $this->makeUserId();
        }

        //自分の履歴
        DB::enableQueryLog();
        $currentList = DB::table('posts')
        ->select('posts.game_id', 'games.game_name', 'games.game_name_sub', DB::raw('max(posts.created_at)'))
        ->leftjoin('games', 'posts.game_id', '=', 'games.game_id')
        ->where('posts.user_id', $userId)
        ->groupBy('posts.game_id', 'games.game_name', 'games.game_name_sub')
        ->orderBy(DB::raw('max(posts.created_at)'), 'desc')
        ->take(10)->get();
        
        $subSql = '
        select 
        posts.game_id, count(*) as post_count
        from posts 
        left join users on posts.user_id = users.user_id 
        where users.is_mute = 0 
        group by posts.game_id';
        
        $gameList = DB::table('games as game')
        ->select('game.game_id', 'game.game_name', 'game.game_name_sub', DB::Raw('IFNULL(post.post_count, 0) as count'))
        ->leftJoinSub($subSql, 'post', 'game.game_id', 'post.game_id')
        ->get();

        foreach ($gameList as $data) {
            $data->game_name = $this->replaceNg($data->game_name);
            $data->game_name_sub = $this->replaceNg($data->game_name_sub);
        }

        return response()->json(['status' => Consts::API_SUCCESS, 'gameList' => $gameList, 'currentList' => $currentList, 'userId' => $retUserId]);
    }

    //
    //投稿の一覧取得
    //
    public function getPostList (Request $request) {
        //バリデート
        try {
            $validated = $request->validate([
                'userId' => 'string',
                'gameId' => 'required|string',
                'keyword' => 'string',
                'page' => 'required|string',
                'filModel' => 'string',
                'filType' => 'string',
                'filId' => 'string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => $e->getMessage()]);
        }

        $page = $request->page;
        $gameId = $request->gameId;
        $userId = $request->userId;
        $keyword = trim(str_replace("　", " ", $request->keyword));
        $filModel = $request->filModel;
        $filType = $request->filType;
        $filId = $request->filId;

        //ゲーム情報
        $gameInfo = DB::table('games')->select('games.game_name', 'games.game_name_sub')->where('games.game_id', $gameId)->first();
        if ($gameInfo == null) {
            return response()->json(['status' => Consts::API_FAILED_NODATA]);
        }

        $retUserId = "";
        if ($userId == "") {
            $retUserId = $this->makeUserId();
        } else if (mb_strlen($userId) != 20) {
            return response()->json(['status' => Consts::API_FAILED_NODATA]);
        } else {
            //ユーザーテーブルに存在するか
            $checkData = DB::table('users')->where('user_id', $userId)->first();
            if ($checkData == null) {
                $userData = new User();
                $userData->user_id = $userId;
                $userData->is_mute = 0;
                $userData->save();

                $retUserId = $userId;
            }
        }

        //投稿情報
        $query = DB::table('posts as post')
        ->select('post.post_id', 'post.user_id', 'post.model', 'post.type', 
        DB::raw('case when post.main_id_type != "99" then post.main_id_type else main_id_label end as main_id_type'), 
        DB::raw('case when post.limit_date > CURRENT_TIMESTAMP then post.main_id else "" end as main_id'), 
        DB::raw('case when post.sub_id_type != "99" then post.sub_id_type else sub_id_label end as sub_id_type'), 
        DB::raw('case when post.limit_date > CURRENT_TIMESTAMP then post.sub_id else "" end as sub_id'), 
        'post.comment', 'post.created_at', 
        DB::raw('case when post.delete_no = "" then 0 else 1 end as delete_flg'),
        DB::raw('case when post.limit_date > CURRENT_TIMESTAMP then 0 else 1 end as is_limit')
        )
        ->leftJoin('users', 'users.user_id', '=', 'post.user_id')
        ->where(function ($q) use ($userId) {
            $q->where('users.is_mute', 0)->orWhere('users.user_id', $userId)->orWhereNull('users.user_id');
        })
        ->where('post.game_id', $gameId)
        ->orderBy('post.created_at', 'desc');

        //キーワード設定
        if ($keyword != "") {
            $wordArray = explode(" ", $keyword);
            for ($i = 0; $i < count($wordArray); $i++) {
                if ($wordArray[$i] != "") {
                    $word = $wordArray[$i];
                    if (mb_substr($word, 0, 1) == "@" && $word != "@") {
                        //ユーザーかどうか
                        $word = mb_substr($word, 1);
                        $query->where('post.user_id', $word);
                    } else {
                        //ふつうの検索
                        $query->where('post.comment', 'like', '%' . $word . '%');
                    }
                }
            }
        }

        //フィルター
        $filModelArray = explode(",", $filModel);
        $query->where(function ($q) use ($filModelArray) {
            $q->where('post.model', -1);
            for ($i = 0; $i < count($filModelArray); $i++) {
                if ($filModelArray[$i] != "") {
                    $q->orWhere('post.model', $filModelArray[$i]);
                }
            }    
        });
        $filTypeArray = explode(",", $filType);
        $query->where(function ($q) use ($filTypeArray) {
            $q->where('post.type', -1);
            for ($i = 0; $i < count($filTypeArray); $i++) {
                if ($filTypeArray[$i] != "") {
                    $q->orWhere('post.type', $filTypeArray[$i]);
                }
            }    
        });
        $filIdArray = explode(",", $filId);
        $query->where(function ($q) use ($filIdArray) {
            $q->where('post.main_id_type', -1);
            for ($i = 0; $i < count($filIdArray); $i++) {
                if ($filIdArray[$i] != "") {
                    $q->orWhere('post.main_id_type', $filIdArray[$i]);
                    $q->orWhere('post.sub_id_type', $filIdArray[$i]);
                }
            }    
        });

        $perPage = 5;
        $postCount = $query->count();
        // $postCount = 4;
        $postList = $query->skip(($page - 1) * $perPage)->take($perPage)->get();
        
        foreach ($postList as $data) {
            //NGワード置換
            $data->main_id = $this->replaceNg($data->main_id);
            $data->main_id_type = $this->replaceNg($data->main_id_type);
            $data->sub_id = $this->replaceNg($data->sub_id);
            $data->sub_id_type = $this->replaceNg($data->sub_id_type);
            $data->comment = $this->replaceNg($data->comment);
        }

        return response()->json(['status' => Consts::API_SUCCESS, 'postList' => $postList, 'gameInfo' => $gameInfo, 'postCount' => $postCount, 'userId' => $retUserId]);
    }

    //
    //ユーザーID取得
    //
    public function makeUserId () {
        //ユーザーIDを作成
        $randomStr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $userId = "";

        while ($userId == "") {
            for ($i = 0; $i < 20; $i++) {
                $ch = substr($randomStr, mt_rand(0, strlen($randomStr)) - 1, 1);
                $userId = $userId . $ch;
            }

            //重複チェック
            $checkData = DB::table('users')->where('user_id', $userId)->first();

            if ($checkData != null) {
                $userId = "";
            } else {
                $userData = new User();
                $userData->user_id = $userId;
                $userData->is_mute = 0;
                $userData->save();
            }
        }
        return $userId;
    }

    //
    //書き込み
    //
    public function insertPost (Request $request) {
        //バリデート
        try {
            $validated = $request->validate([
                'userId' => 'string',
                'gameId' => 'string',
                'gameName' => 'string|max:50',
                'model' => 'string|max:50',
                'type' => 'string|max:50',
                'mainType' => 'required|string',
                'mainLabel' => 'string|max:20',
                'mainId' => 'required|string|max:50',
                'subType' => 'string',
                'subLabel' => 'string|max:20',
                'subId' => 'string|max:50',
                'comment' => 'string|max:200',
                'deleteNo' => 'string',
                'limit' => 'string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => $e->getMessage()]);
        }
        
        $lenUserId = 20;
        $lenGameName = 50;
        $lenIdName = 20;
        $lenId = 50;
        $lenComment = 200;
        $lenDeleteNo = 5;
        $modelArray = array("0","1","2","3","4","5","6","7","99");
        $typeArray = array("0","1","2","3","4","5","6");
        $idArray = array("0","1","2","3","4","5","6","7","8","9","10","11","12","99");

        
        $gameId = $request->gameId;
        $userId = $request->userId;
        $gameName = $request->gameName;
        $model = $request->model;
        $type = $request->type;
        $mainType = $request->mainType;
        $mainLabel = $request->mainLabel;
        $mainId = $request->mainId;
        $subType = $request->subType;
        $subLabel = $request->subLabel;
        $subId = $request->subId;
        $comment = $request->comment;
        $deleteNo = $request->deleteNo;
        $limit = $request->limit;
        $ipAddress = $request->ip();

        //入力チェック
        if ($userId == "" || mb_strlen($userId) > $lenUserId) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "1"]);
        }
        if ($gameId == "") {
            if ($gameName == "" || mb_strlen($gameName) > $lenGameName) {
                return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "2"]);
            }
        }
        $flg = false;
        //機種
        for ($i=0; $i < count($modelArray); $i++) { 
            if ($model == $modelArray[$i]) {
                $flg = true;
                break;
            }
        }
        if (!$flg) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "3"]);
        }

        //タイプ
        for ($i=0; $i < count($typeArray); $i++) { 
            if ($type == $typeArray[$i]) {
                $flg = true;
                break;
            }
        }
        if (!$flg) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "4"]);
        }

        //メインID
        $flg = false;
        for ($i=0; $i < count($idArray); $i++) { 
            if ($mainType == $idArray[$i]) {
                $flg = true;
                break;
            }
        }
        if (!$flg) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "5"]);
        }
        if ($mainType == "99") {
            if ($mainLabel == "" || mb_strlen($mainLabel) > $lenIdName) {
                return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "6"]);
            }
        }
        if ($mainId == "" || mb_strlen($mainId) > $lenId) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "7"]);
        }

        //サブID
        if ($subId == "") {
            $subType = "";
            $subLabel = "";
        } else {
            $flg = false;
            for ($i=0; $i < count($idArray); $i++) { 
                if ($subType == $idArray[$i]) {
                    $flg = true;
                    break;
                }
            }
            if (!$flg) {
                return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "8"]);
            }
            if (mb_strlen($subId) > $lenId) {
                return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "9"]);
            }
            if ($subType == "99") {
                if ($subLabel == "" || mb_strlen($subLabel) > $lenIdName) {
                    return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "10"]);
                }
            }
        }

        if ($comment == "" || mb_strlen($comment) > $lenComment) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "11"]);
        }
        
        if (mb_strlen($deleteNo) > $lenDeleteNo) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "12"]);
        } else if ($deleteNo != "") {
            if (preg_match("/^[0-9]+$/", $deleteNo) == 0) {
                return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "13"]);
            }
        }

        if ($limit != "0" && $limit != "1" && $limit != "2" && $limit != "3" && $limit != "4" && $limit != "5") {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => "14"]);
        }



        if ($gameName != "") {
            //重複がないか
            $checkGame = DB::table('games')->where('game_name', $gameName)->first();
            if ($checkGame != null) {
                //同じゲームがある場合
                $gameId = $checkGame->game_id;
            } else {
                //新規ゲーム作成
                $gameId = DB::table('games')->insertGetId([
                    'game_name' => $gameName,
                    'game_name_sub' => "",
                    'add_flg' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        //指定時間より前の投稿があるか
        $limitDate = date("Y/m/d H:i:s", strtotime("-1 minute"));
        $curPostData = DB::table('posts')->where('game_id', $gameId)->where('user_id', $userId)->where('created_at', '>', $limitDate)->first();

        if ($curPostData != null) {
            return response()->json(['status' => Consts::API_FAILED_DUPLICATE, 'errMsg' => "15"]);
        }
        
        $newPost = new Post();
        $newPost->game_id = $gameId;
        $newPost->user_id = $userId;
        $newPost->model = $model;
        $newPost->type = $type;
        $newPost->main_id_type = $mainType;
        $newPost->main_id_label = $mainLabel;
        $newPost->main_id = $mainId;
        $newPost->sub_id_type = $subType;
        $newPost->sub_id_label = $subLabel;
        $newPost->sub_id = $subId;
        $newPost->comment = $comment;
        $newPost->delete_no = $deleteNo;

        //期限の設定
        $limitDate = date("Y/m/d H:i:s", strtotime("10 year"));
        if ($limit == 1) {
            $limitDate = date("Y/m/d H:i:s", strtotime("1 hour"));
        } else if ($limit == 2) {
            $limitDate = date("Y/m/d H:i:s", strtotime("6 hours"));
        } else if ($limit == 3) {
            $limitDate = date("Y/m/d H:i:s", strtotime("12 hours"));
        } else if ($limit == 4) {
            $limitDate = date("Y/m/d H:i:s", strtotime("1 day"));
        } else if ($limit == 5) {
            $limitDate = date("Y/m/d H:i:s", strtotime("3 days"));
        }
        $newPost->limit_date = $limitDate;
        $newPost->ip_address = $ipAddress;
        $newPost->save();

        return response()->json(['status' => Consts::API_SUCCESS, 'gameId' => $gameId]);
    }

    //
    //削除
    //
    public function deletePost (Request $request) {
        //バリデート
        try {
            $validated = $request->validate([
                'postId' => 'required|string',
                'deleteNo' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => $e->getMessage()]);
        }

        $postData = Post::where('post_id', $request->postId)->where('delete_no', $request->deleteNo)->first();

        if ($postData == null) {
            //データなし
            return response()->json(['status' => Consts::API_FAILED_MISMATCH, 'errMsg' =>""]);
        }

        $delete = Post::where('post_id', $request->postId)->where('delete_no', $request->deleteNo)->delete();

        return response()->json(['status' => Consts::API_SUCCESS]);
    }

    //
    //通報
    //
    public function reportPost (Request $request) {
        //バリデート
        try {
            $validated = $request->validate([
                'postId' => 'required|string',
                'userId' => 'required|string',
                'message' => 'string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => Consts::API_FAILED_PARAM, 'errMsg' => $e->getMessage()]);
        }

        $postData = Post::where('post_id', $request->postId)->first();
        
        if ($postData == null) {
            //データなし
        }

        $reportData = Report::where('user_id', $request->userId)->where('post_id', $request->postId)->first();

        if ($reportData == null) {
            $reportData = new Report();
            $reportData->post_id = $request->postId;
            $reportData->user_id = $request->userId;
            $reportData->comment = $request->message;
            $reportData->save();
        } else {
            $reportData->comment = $request->message;
            $reportData->save();
        }

        return response()->json(['status' => Consts::API_SUCCESS]);
    }
}
