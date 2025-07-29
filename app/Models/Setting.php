<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'group',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Obtenir une valeur de paramètre avec cache
     */
    public static function get($key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return static::castValue($setting->value, $setting->type);
        });
    }
    
    /**
     * Définir une valeur de paramètre
     */
    public static function set($key, $value, $type = 'string')
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => static::prepareValue($value, $type),
                'type' => $type,
                'updated_at' => now()
            ]
        );
        
        // Vider le cache
        Cache::forget("setting.{$key}");
        
        return $setting;
    }
    
    /**
     * Obtenir tous les paramètres d'un groupe
     */
    public static function getGroup($group)
    {
        return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            $settings = static::where('group', $group)->get();
            
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = static::castValue($setting->value, $setting->type);
            }
            
            return $result;
        });
    }
    
    /**
     * Obtenir tous les paramètres publics
     */
    public static function getPublicSettings()
    {
        return Cache::remember('settings.public', 3600, function () {
            $settings = static::where('is_public', true)->get();
            
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = static::castValue($setting->value, $setting->type);
            }
            
            return $result;
        });
    }
    
    /**
     * Sauvegarder plusieurs paramètres en une fois
     */
    public static function setMany(array $settings)
    {
        foreach ($settings as $key => $data) {
            if (is_array($data)) {
                static::set($key, $data['value'], $data['type'] ?? 'string');
            } else {
                static::set($key, $data);
            }
        }
        
        // Vider tout le cache des settings
        static::clearCache();
    }
    
    /**
     * Vider le cache des paramètres
     */
    public static function clearCache()
    {
        $keys = static::pluck('key');
        
        foreach ($keys as $key) {
            Cache::forget("setting.{$key}");
        }
        
        // Vider aussi les groupes courants
        Cache::forget('settings.public');
        Cache::forget('settings.group.general');
        Cache::forget('settings.group.system');
        Cache::forget('settings.group.notifications');
        Cache::forget('settings.group.financial');
    }
    
    /**
     * Convertir la valeur selon le type
     */
    private static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return (bool) $value || $value === '1' || $value === 'true';
            case 'integer':
                return (int) $value;
            case 'decimal':
            case 'float':
                return (float) $value;
            case 'array':
            case 'json':
                return json_decode($value, true) ?: [];
            default:
                return $value;
        }
    }
    
    /**
     * Préparer la valeur pour la base de données
     */
    private static function prepareValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'array':
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }
    
    /**
     * Helper pour les vues
     */
    public static function helper($key, $default = null)
    {
        return static::get($key, $default);
    }
}