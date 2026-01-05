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
        return array_map(fn($row) => new self($row), $results);
    }
}