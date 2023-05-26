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

class ManagementController extends Controller
{
    //
    //初期情報取得
    //
    public function getManagementData (Request $request) {
        $secret = (int)$request->secret;
        $code = (int)$request->code;
        
        if ($secret + $code != 11223344) {
            return response()->json(['status' => Consts::API_FAILED_LOGIN]);
        }
        
        //通報一覧取得
        $reportList = DB::table('reports')->select(
            'reports.no', 'reports.user_id as report_user_id', 'reports.post_id', 'reports.comment', 'reports.created_at as reported_at',
            'posts.game_id', 'posts.user_id as post_user_id', 'posts.type', 'posts.main_id_type', 'posts.main_id_label', 'posts.main_id', 'posts.sub_id_type', 'posts.sub_id_label', 'posts.sub_id', 'posts.comment', 'posts.created_at as posted_at')
            ->leftJoin('posts', 'reports.post_id', '=', 'posts.post_id')
            ->where('reports.through', 0)
            ->orderBy('reports.created_at')
            ->get();

        //ユーザー追加ゲーム一覧取得
        $addGameList = DB::table('games')
        ->select('game_id', 'game_name', 'game_name as new_game_name', 'game_name_sub as new_game_name_sub', 'created_at')
        ->where('add_flg', '=', '1')->orderBy('created_at')->get();

        //既存ゲームの一覧取得
        $gameList = DB::table('games')->where('add_flg', '!=', '1')->orderBy('game_name')->get();

        return response()->json(['status' => Consts::API_SUCCESS, 'reportList' => $reportList, 'addGameList' => $addGameList, 'gameList' => $gameList]);
    }

    //
    //追加ゲーム変更処理
    //
    public function updateAddGame (Request $request) {
        $kbn = $request->kbn;
        $gameId = $request->gameId;

        $targetData = Game::where('game_id', $gameId)->first();
        
        if ($kbn == "CHANGE") {
            $newGameName = $request->newGameName;
            $newGameNameSub = $request->newGameNameSub;

            $targetData->game_name = $newGameName;
            $targetData->game_name_sub = $newGameNameSub;
            $targetData->add_flg = 2;
            $targetData->save();
        } else if ($kbn == "INTEGRATION") {
            $integration = $request->integration;
            $postUpdate = DB::table('posts')->where('game_id', $gameId)->update(['game_id' => $integration]);
            $targetData->delete();
        } else if ($kbn == "DELETE") {
            $postUpdate = DB::table('posts')->where('game_id', $gameId)->delete();
            $targetData->delete();
        }

        //ユーザー追加ゲーム一覧取得
        $addGameList = DB::table('games')
        ->select('game_id', 'game_name', 'game_name as new_game_name', 'game_name_sub as new_game_name_sub')
        ->where('add_flg', '=', '1')->orderBy('created_at')->get();

        //既存ゲームの一覧取得
        $gameList = DB::table('games')->where('add_flg', '!=', '1')->orderBy('game_name')->get();

        return response()->json(['status' => Consts::API_SUCCESS, 'addGameList' => $addGameList, 'gameList' => $gameList]);
    }

    //
    //追加ゲーム変更処理
    //
    public function updateReport (Request $request) {
        $kbn = $request->kbn;
        $reportNo = $request->reportNo;
        $userId = $request->userId;

        $targetData = Report::where('no', $reportNo)->first();
        if ($kbn == "THROUGH") {
            $targetData->through = 1;
            $targetData->save();
        } else if ($kbn == "DELETE") {
            User::where('user_id', $userId)->update(['is_mute' => 1]);
            $targetData->through = 2;
            $targetData->save();
        }

        //通報一覧取得
        $reportList = DB::table('reports')->select(
            'reports.no', 'reports.user_id as report_user_id', 'reports.post_id', 'reports.comment', 'reports.created_at as reported_at',
            'posts.game_id', 'posts.user_id as post_user_id', 'posts.type', 'posts.main_id_type', 'posts.main_id_label', 'posts.main_id', 'posts.sub_id_type', 'posts.sub_id_label', 'posts.sub_id', 'posts.comment', 'posts.created_at as posted_at')
            ->leftJoin('posts', 'reports.post_id', '=', 'posts.post_id')
            ->where('reports.through', 0)
            ->orderBy('reports.created_at')
            ->get();

        return response()->json(['status' => Consts::API_SUCCESS, 'reportList' => $reportList]);
    }

    //
    //新規ゲーム処理
    //
    public function updateNew (Request $request) {
        $gameName = $request->gameName;
        $gameNameSub = $request->gameNameSub;

        $game = new Game();
        $game->game_name = $gameName;
        $game->game_name_sub = $gameNameSub;
        $game->add_flg = 1;
        $game->save();

        //ユーザー追加ゲーム一覧取得
        $addGameList = DB::table('games')
        ->select('game_id', 'game_name', 'game_name as new_game_name', 'game_name_sub as new_game_name_sub')
        ->where('add_flg', '=', '1')->orderBy('created_at')->get();

        //既存ゲームの一覧取得
        $gameList = DB::table('games')->where('add_flg', '!=', '1')->orderBy('game_name')->get();

        return response()->json(['status' => Consts::API_SUCCESS, 'addGameList' => $addGameList, 'gameList' => $gameList]);
    }
}
