<?php

namespace App;

use App\Feature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bet extends Model {
    
    use SoftDeletes;

    const FAVORITE = 'FAVORITE';
    const NORMAL = 'NORMAL';

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function choices() {
        return $this->hasOne('App\CategoryFeature', 'id', 'category_features_id');
    }

    public static function deleteAllBetsIdByCategoryAndUserId ($categoryId, $userId) {
        $ids = self::getAllBetsIdByCategoryAndUserId($categoryId, $userId);

        return self::whereIn('id', $ids)->delete();
    }

    public static function getAllBetsIdByCategoryAndUserId ($categoryId, $userId) {
        $betsIds = self::select('bets.id')
        ->join('category_feature', 'bets.category_features_id', '=', 'category_feature.id')
        ->where('category_feature.category_id', $categoryId)
        ->where('bets.user_id', $userId)
        ->whereNull('bets.deleted_at')
        ->get()
        ->toArray();

        if (count($betsIds) == 0) {
            return [];
        }

        $ids = array_map( function($item) {
            return $item['id'];
        }, $betsIds);

        return $ids;
    }

    public static function getAllBetsByUser ($userId) {
        $bets = self::select(
            [
                'type',
                'features.name as feature_name',
                'categories.name as category_name',
                'pictures.path',
                'features.feature_id',
            ])
            ->join('category_feature', 'bets.category_features_id', '=', 'category_feature.id')
            ->join('features', 'category_feature.feature_id', '=', 'features.id')
            ->join('categories', 'category_feature.category_id', '=', 'categories.id')
            ->join('pictures', 'features.picture_id', '=', 'pictures.id')
            ->where('bets.user_id', $userId)
            ->whereNull('bets.deleted_at')
            ->orderBy('categories.id', 'asc')
            ->get()
            ->toArray();

        $result = [];
        foreach ($bets as $key => $bet) {
            $userChoice['name'] = $bet['category_name'];
            $userChoice['feature'] = [
                'name' => $bet['feature_name'],
                'path' => $bet['path'],
                'feature' => Feature::find($bet['feature_id']),
                'favorite' => $bet['type'] == self::FAVORITE,
            ];

            $result[] = $userChoice;
        }

        return $result;
    }

    public static function getAllBetsIdByUser ($userId) {
        $betsIds = self::select('bets.category_features_id')
            ->where('bets.user_id', $userId)
            ->whereNull('bets.deleted_at')
            ->orderBy('bets.category_features_id', 'desc')
            ->get()
            ->toArray();

        $ids = array_map( function($item) {
            return $item['category_features_id'];
        }, $betsIds);

        return $ids;
    }

    public static function returnType ($type) {
        return ($type != null) ? self::FAVORITE : self::NORMAL;
    }

    public static function removeAllFavoriteBetsByUser ($userId) {
        return self::where('user_id', $userId)
            ->update(['type' => self::NORMAL]);
    }

    public static function getFavoriteBetByUser ($userId) {
        $bet = self::select('category_features_id')
            ->where('user_id', $userId)
            ->where('type', self::FAVORITE)
            ->whereNull('deleted_at')
            ->first();
            
        return ($bet) ? $bet->category_features_id : $bet;
    }
}
