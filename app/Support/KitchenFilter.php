<?php

namespace App\Support;

use App\Models\Setting;

class KitchenFilter
{
    // MSSQL KDS sorgusunun getirdiği gelir merkezleri: RVC kodu → ekrandaki ad
    public const RVCS = [44 => 'Pool Bar', 81 => 'Rocks Patisserie'];

    // BDS (bar) sorgusu yalnız RVC 44 getirir — bar tick'leri bu merkez için
    public const BAR_RVCS = [44 => 'Pool Bar'];

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

    // FamGrp (ürün grubu) kodları admin ayarlarında elle girilir (kitchen_fg_hide_{rvc}).
    // Bilinen kod: 1205 = RP MAYALI (Poğaça / Simit) — FamGrpObjNum canlı probe ile doğrulandı.

    // Hiç kayıt yokken devrede olan gizle varsayılanı: Patisserie'de yalnız poğaça/simit
    public const FAMILY_HIDE_DEFAULT = [81 => [1205]];

    // RVC + kategori başına görünür mü (tek sorgu).
    // $prefix: mutfak 'kitchen_show', bar 'bar_show' — ayar anahtarları ayrı settir.
    public static function visibleMap(int $rvcId, string $prefix = 'kitchen_show'): array
    {
        $keys = [];
        foreach (array_keys(self::GROUPS) as $mg) {
            $keys[] = "{$prefix}_{$rvcId}_{$mg}";
        }
        $saved = Setting::whereIn('key', $keys)->pluck('value', 'key');

        $map = [];
        foreach (array_keys(self::GROUPS) as $mg) {
            $default  = in_array($mg, self::DEFAULT_HIDDEN, true) ? '0' : '1';
            $map[$mg] = ($saved["{$prefix}_{$rvcId}_{$mg}"] ?? $default) === '1';
        }
        return $map;
    }

    // Mutfak ekranına yazdırilmayacak FamGrp kodları (ters mantık: girilen = gizli).
    // kitchen_fg_hide_{rvc} ayarı virgülle ayrılmış kod listesi tutar; kayıt yokken
    // FAMILY_HIDE_DEFAULT devrededir, boş kayıt ("") hiçbir grubu gizlemez.
    public static function familyHideMap(int $rvcId): array
    {
        $saved = Setting::get("kitchen_fg_hide_{$rvcId}");
        if ($saved === null) {
            $saved = implode(',', self::FAMILY_HIDE_DEFAULT[$rvcId] ?? []);
        }

        $map = [];
        foreach (array_filter(array_map('intval', explode(',', (string) $saved))) as $code) {
            $map[$code] = true;
        }
        return $map;
    }

    // Ayar sayfasındaki metin alanına yazılacak değer (virgüllü kod listesi).
    public static function familyHideList(int $rvcId): string
    {
        $saved = Setting::get("kitchen_fg_hide_{$rvcId}");
        if ($saved === null) {
            $saved = implode(',', self::FAMILY_HIDE_DEFAULT[$rvcId] ?? []);
        }
        return (string) $saved;
    }
}
