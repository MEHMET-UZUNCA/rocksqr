<?php

namespace App\Support;

use App\Models\Setting;

class KitchenFilter
{
    // MSSQL KDS sorgusunun getirdiği gelir merkezleri: RVC kodu → ekrandaki ad
    public const RVCS = [44 => 'Pool Bar', 81 => 'Rocks Patisserie'];

    // Symphony MajorGroup kodu → [etiket, açıklama, ikon]
    // Yalnızca KDS akışında gerçekten görünen kodlar (canlı 7 günlük veri)
    public const GROUPS = [
        1  => ['Yiyecek', 'Symphony kodu 1 — YIYECEK', 'fa-utensils'],
        2  => ['İçecek', 'Symphony kodu 2 — ICECEK', 'fa-glass-water'],
        3  => ['Alkollü İçecek', 'Symphony kodu 3 — ALKOLLU ICECEK', 'fa-wine-glass'],
        4  => ['Diğer', 'Symphony kodu 4 — DIGER', 'fa-ellipsis'],
        99 => ['Mutfak Mesajı', 'Symphony kodu 99 — "Mutfak Mesaj" satırları ve mars', 'fa-comment-dots'],
        98 => ['İçecek Mesajı', 'MajGrp 99 — adı "Bar Mesaj" olan satırlar', 'fa-martini-glass'],
        0  => ['Etiketsiz Satırlar', 'Symphony kodu 0 — etiketsiz ürünler (garnitür, condiment)', 'fa-tag'],
    ];

    // Varsayılan kapalı kategoriler — eski kitchen_show_drinks=0 davranışının devamı
    public const DEFAULT_HIDDEN = [2, 3];

    // RVC + kategori başına görünür mü (tek sorgu)
    public static function visibleMap(int $rvcId): array
    {
        $keys = [];
        foreach (array_keys(self::GROUPS) as $mg) {
            $keys[] = "kitchen_show_{$rvcId}_{$mg}";
        }
        $saved = Setting::whereIn('key', $keys)->pluck('value', 'key');

        $map = [];
        foreach (array_keys(self::GROUPS) as $mg) {
            $default  = in_array($mg, self::DEFAULT_HIDDEN, true) ? '0' : '1';
            $map[$mg] = ($saved["kitchen_show_{$rvcId}_{$mg}"] ?? $default) === '1';
        }
        return $map;
    }
}
