<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\CurrencyHelper;

class CommissionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'promoteur_id',
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

    public function promoteur()
    {
        return $this->belongsTo(User::class, 'promoteur_id');
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
        return $query->whereNull('category_id')->whereNull('promoteur_id');
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId)->whereNull('promoteur_id');
    }

    public function scopeForPromoteur($query, $promoteurId)
    {
        return $query->where('promoteur_id', $promoteurId);
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
        return CurrencyHelper::formatFCFA($this->platform_fee_fixed);
    }

    public function getFormattedMinCommissionAttribute()
    {
        return CurrencyHelper::formatFCFA($this->min_commission);
    }

    public function getTypeAttribute()
    {
        if ($this->promoteur_id) {
            return 'Promoteur spécifique';
        } elseif ($this->category_id) {
            return 'Catégorie';
        } else {
            return 'Par défaut';
        }
    }

    public function getDescriptionAttribute()
    {
        if ($this->promoteur_id) {
            return "Commission pour {$this->promoteur->name}";
        } elseif ($this->category_id) {
            return "Commission pour la catégorie {$this->category->name}";
        } else {
            return 'Commission par défaut';
        }
    }

    /**
     * Vérifications
     */
    public function isValid($date = null)
    {
        $date = $date ?: now();
        
        return $this->is_active &&
               $this->valid_from <= $date &&
               ($this->valid_until === null || $this->valid_until >= $date);
    }

    public function isExpired()
    {
        return $this->valid_until && $this->valid_until < now();
    }

    public function isDefault()
    {
        return is_null($this->category_id) && is_null($this->promoteur_id);
    }

    public function isForCategory()
    {
        return !is_null($this->category_id) && is_null($this->promoteur_id);
    }

    public function isForPromoteur()
    {
        return !is_null($this->promoteur_id);
    }

    /**
     * Obtenir la commission applicable pour un événement et un promoteur
     */
    public static function getCommissionForEvent($event, $promoteurId)
    {
        // Priorité 1: Commission spécifique promoteur
        $commission = self::active()
            ->valid()
            ->forPromoteur($promoteurId)
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

    /**
     * Calculer la commission pour un montant donné
     */
    public function calculateCommission($amount)
    {
        $commission = CurrencyHelper::calculateCommission(
            $amount,
            $this->commission_rate,
            $this->platform_fee_fixed
        );
        
        // Appliquer la commission minimale si définie
        if ($this->min_commission > 0 && $commission['commission'] < $this->min_commission) {
            $commission['commission'] = $this->min_commission;
            $commission['net'] = $amount - $this->min_commission;
        }
        
        return $commission;
    }

    /**
     * Créer les paramètres de commission par défaut
     */
    public static function createDefaults()
    {
        // Commission par défaut
        self::updateOrCreate(
            [
                'category_id' => null,
                'promoteur_id' => null,
            ],
            [
                'commission_rate' => 10.00,
                'platform_fee_fixed' => 500,
                'min_commission' => 1000,
                'is_active' => true,
                'valid_from' => now()->subYear(),
                'valid_until' => null,
            ]
        );
        
        // Commission réduite pour les concerts (volume plus élevé)
        $concertCategory = EventCategory::where('slug', 'concert')->first();
        if ($concertCategory) {
            self::updateOrCreate(
                [
                    'category_id' => $concertCategory->id,
                    'promoteur_id' => null,
                ],
                [
                    'commission_rate' => 8.00,
                    'platform_fee_fixed' => 300,
                    'min_commission' => 800,
                    'is_active' => true,
                    'valid_from' => now()->subYear(),
                    'valid_until' => null,
                ]
            );
        }
        
        // Commission majorée pour le théâtre (événements plus petits)
        $theatreCategory = EventCategory::where('slug', 'theatre')->first();
        if ($theatreCategory) {
            self::updateOrCreate(
                [
                    'category_id' => $theatreCategory->id,
                    'promoteur_id' => null,
                ],
                [
                    'commission_rate' => 12.00,
                    'platform_fee_fixed' => 700,
                    'min_commission' => 1200,
                    'is_active' => true,
                    'valid_from' => now()->subYear(),
                    'valid_until' => null,
                ]
            );
        }
    }

    /**
     * Désactiver une commission
     */
    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * Activer une commission
     */
    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    /**
     * Expirer une commission
     */
    public function expire($date = null)
    {
        $this->valid_until = $date ?: now();
        $this->save();
    }
}