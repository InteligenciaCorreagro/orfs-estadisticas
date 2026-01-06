<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class TraderAdicional extends Model
{
    protected string $table = 'trader_adicionales';
    
    protected array $fillable = [
        'trader_id',
        'nombre_adicional'
    ];
    
    protected array $casts = [
        'trader_id' => 'integer'
    ];
    
    public function trader(): ?Trader
    {
        return Trader::find($this->trader_id);
    }
    
    public static function porTrader(int $traderId): array
    {
        $sql = "SELECT * FROM trader_adicionales WHERE trader_id = :trader_id";
        $results = Database::fetchAll($sql, ['trader_id' => $traderId]);
        $models = [];
        foreach ($results as $result) {
            $model = new static();
            foreach ($result as $key => $value) {
                $model->attributes[$key] = $value;
                $model->original[$key] = $value;
            }
            $model->castAttributes();
            $models[] = $model;
        }

        return $models;
    }
}