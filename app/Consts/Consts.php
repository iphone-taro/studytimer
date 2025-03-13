<?php

namespace App\Consts;

class Consts
{
    public const TEST = "1";
    public const API_SUCCESS = "200";

    public const API_FAILED_LOGIN = "99";
    public const API_FAILED_AUTH = "100";

    public const API_FAILED_PARAM = "300";

    public const API_FAILED_NODATA = "400";
    public const API_FAILED_DUPLICATE = "401";
    public const API_FAILED_MISMATCH = "402";

    public const API_FAILED_PRIVATE = "500";
    public const API_FAILED_FILE = "600";

    public const API_FAILED_EXEPTION = "900";

    public const BASE_TITLE = "『まなんだー』おしらせつき勉強タイマー";
    public const BASE_DESCRIPTION = "受験や資格、学校の課題などの勉強タイマー。開始と終了をみんなにおしらせ！";
    public const BASE_SEO = "勉強,受験,課題,宿題,資格,高校,大学,中学,タイマー,学校,共有,JC,JK,study,timer";

    //
    //テーブル名
    //
    public static function retTableName($postKbn) {
        $tableName = "";
        if ($postKbn == "REPORT") {
            //勉強報告
            $tableName = "reports";
        } else if ($postKbn == "EXAMINEE") {
            //受験生
            $tableName = "examinees";
        } else if ($postKbn == "GUCHI") {
            //相談・質問
            $tableName = "guchis";
        } else if ($postKbn == "SOUDAN") {
            //相談・質問
            $tableName = "soudans";
        } else if ($postKbn == "RESPONSE") {
            //相談・質問 レスポンス
            $tableName = "responses";
        } else if ($postKbn == "NANDEMO") {
            //なんでも
            $tableName = "nandemos";
        }
        return $tableName;
    }

    //
    //テーブル名
    //
    public static function retFiltering($str) {
        for ($i=0; $i < count(Consts::NG_WORDS); $i++) { 
            $word = Consts::NG_WORDS[$i];
            $str = str_replace($word, '***', $str);
        }
        return $str;
    }

























    public const NG_WORDS = ["えっち", "エッチ", "エロ", "下着", "援助", "援交", "P活", "パパ活", "ちんこ", "チンコ", "まんこ", "せっくす", "セックス", "せふれ", "セフレ", "うんこ", "ウンコ", "変態", "SEX", "円光", "アダルト", "勃起", "おちんちん", "あなる", "せっくす", "ヤリマン", "ザーメン", "おっぱい", "オッパイ", "チンポ", "巨乳", "巨根", "性欲", "ホ別", "穂別", "エロイプ", "えろいぷ", "見せ合い", "見せあい", "不倫", "浮気", "チン凸", "マン凸", "おなにー", "オナニー", "おほ声", "オホ声", "オナ電", "おな電", "会い", "ホテル", "ドM", "ドS", "アソコ", "あそこ", "ムラムラ", "フェラ", "カカオ", "乳首"];


    































    public const API_KEY = "LWALRPsGjhDe02wP7E6D0GxBv";
    public const API_KEY_SECRET = "XpCejTMpZnkzC37ak8MqCJMUZePRvkBTdqYSjhaH47M21DkoRq";
    public const ACCESS_TOKEN = "1900058157218734080-e2MWchwjlky7jyhmucikhCeXOmYJS2";
    public const ACCESS_TOKEN_SECRET = "idYHTR1So9CcF6zySCx7phwK6u4PEaaumM5kXJxy011o8";
    public const CLIENT_ID = "TTlrQVpuZ2F5RGlPRUpxeVJSRFE6MTpjaQ";
    public const CLIENT_SECRET = "hD88OVNnaPKj4bbMuokTV70eKG9xrmBtilrn8KnAu24vh98oJL";
}
