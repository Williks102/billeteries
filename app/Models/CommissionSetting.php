<?php
// app/Models/CommissionSetting.php - VERSION HARMONISÉE

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'promoter_id',  // ✅ CHANGÉ: promoteur_id → promoter_id
        'commission_rate',
        'platform_fee_fixed',
        'min_commission',
        'is_active',
        'valid_from',
        'valid_until'
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'platform_fee_fixed' => 'integer',
        'min_commission' => 'integer',
    ];

    /**
     * Relations
     */
    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    public function promoter()  // ✅ CHANGÉ: Nouvelle relation principale
    {
        return $this->belongsTo(User::class, 'promoter_id');
    }
    
    public function promoteur()  // ✅ ALIAS: Pour compatibilité
    {
        return $this->promoter();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query, $date = null)
    {
        $date = $date ?: now();
        
        return $query->where('valid_from', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>=', $date);
                    });
    }

    public function scopeDefault($query)
    {
        return $query->whereNull('category_id')->whereNull('promoter_id');
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId)->whereNull('promoter_id');
    }

    public function scopeForPromoter($query, $promoterId)  // ✅ CHANGÉ: promoteur → promoter
    {
        return $query->where('promoter_id', $promoterId);
    }

    /**
     * Accessors
     */
    public function getFormattedCommissionRateAttribute()
    {
        return $this->commission_rate . '%';
    }

    public function getFormattedPlatformFeeAttribute()
    {
        return number_format($this->platform_fee_fixed, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedMinCommissionAttribute()
    {
        return number_format($this->min_commission, 0, ',', ' ') . ' FCFA';
    }

    public function getTypeAttribute()
    {
        if ($this->promoter_id) {
            return 'Promoteur spécifique';
        } elseif ($this->category_id) {
            return 'Catégorie';
        } else {
            return 'Par défaut';
        }
    }

    public function getDescriptionAttribute()
    {
        if ($this->promoter_id) {
            return "Commission pour {$this->promoter->name}";
        } elseif ($this->category_id) {
            return "Commission pour la catégorie {$this->category->name}";
        } else {
            return 'Commission par défaut';
        }
    }

    /**
     * Méthodes statiques pour récupérer les commissions
     */
    public static function getCommissionForEvent($event, $promoterId)  // ✅ CHANGÉ: promoteur → promoter
    {
        // Priorité 1: Commission spécifique promoteur
        $commission = self::active()
            ->valid()
            ->forPromoter($promoterId)
            ->first();
        
        if ($commission) {
            return $commission;
        }
        
        // Priorité 2: Commission par catégorie
        $commission = self::active()
            ->valid()
            ->forCategory($event->category_id)
            ->first();
        
        if ($commission) {
            return $commission;
        }
        
        // Priorité 3: Commission par défaut
        return self::active()
            ->valid()
            ->default()
            ->first();
    }
}