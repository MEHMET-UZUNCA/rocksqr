<?php

namespace App\Support;

use App\Models\Setting;

class KitchenFilter
{
    // MSSQL KDS sorgusunun getirdiği gelir merkezleri: RVC kodu → ekrandaki ad
    public const RVCS = [44 => 'Pool Bar', 81 => 'Rocks Patisserie'];

    // BDS (bar) sorgusu yalnız RVC 44 getirir — bar tick'leri bu merkez için
    public const BAR_RVCS = [44 => 'Pool Bar'];

    // Ana Mutfak (AKDS) ekranı mutfakla aynı v1.4 sorguyu paylaşır; yalnız RVC
    // yazılım filtresi farklıdır (mssql_akds_rvc_filter). Tick anahtarları
    // kitchen_show_{rvc}_{mg} şemasını izler, mutfak kayıtlarına dokunmaz.
    public const ANA_RVCS = [
        43 => 'Cafe Rocks',
        45 => 'Minibar',
        46 => 'Room Service',
        63 => 'Sıralı Et',
    ];

    // Kart ekranında "Chk #... · İsim" için: bilinen RVC → ekran adı,
    // bilinmeyen RVC → sayısal olmayan ham değer olduğu gibi, aksi halde boş.
    public static function rvcName(int $rvcId, $raw = null): string
    {
        $names = self::RVCS + self::ANA_RVCS;
        if (isset($names[$rvcId])) {
            return $names[$rvcId];
        }
        $raw = trim((string) $raw);
        return $raw !== '' && !ctype_digit($raw) ? $raw : '';
    }

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
    // {$prefix}_{rvc} ayarı virgülle ayrılmış kod listesi tutar; kayıt yokken
    // FAMILY_HIDE_DEFAULT devrededir, boş kayıt ("") hiçbir grubu gizlemez.
    // $prefix: mutfak 'kitchen_fg_hide', bar 'bar_fg_hide' — ayar anahtarları ayrı settir.
    public static function familyHideMap(int $rvcId, string $prefix = 'kitchen_fg_hide'): array
    {
        $saved = Setting::get("{$prefix}_{$rvcId}");
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
    public static function familyHideList(int $rvcId, string $prefix = 'kitchen_fg_hide'): string
    {
        $saved = Setting::get("{$prefix}_{$rvcId}");
        if ($saved === null) {
            $saved = implode(',', self::FAMILY_HIDE_DEFAULT[$rvcId] ?? []);
        }
        return (string) $saved;
    }

    // ─────────────────────────────────────────────
    // Açık Yiyecek/İçecek/Diğer POS kodları — RVC bazlı ayrı ayar
    // (kitchen_open_{tur}_{rvc}). Varsayılanlar canlı veriden doğrulandı:
    // 1998001=Acik Yiyecek, 2998001=Acik Icecek, 3998001=Acik Alkollu Icecek,
    // 4998001=Acik Diger. Bu kodlu satırlar birleştirilmez; içerikleri
    // POS fişinden (POS_JOURNAL_LOG) çözülür. Boş kayıt sınıfı kapatır.
    // ─────────────────────────────────────────────
    public const OPEN_KINDS = ['food', 'drink', 'other'];

    public const OPEN_DEFAULTS = [
        'food'  => '1998001',
        'drink' => '2998001,3998001',
        'other' => '4998001',
    ];

    // RVC'nin tüm açık satır kodları (birleşik küme; kod => true)
    public static function openCodeMap(int $rvcId, string $prefix = 'kitchen_open'): array
    {
        $map = [];
        foreach (self::OPEN_KINDS as $kind) {
            foreach (self::openCodeSet($rvcId, $kind, $prefix) as $code => $_) {
                $map[$code] = true;
            }
        }
        return $map;
    }

    // Tek sınıfın kod kümesi
    public static function openCodeSet(int $rvcId, string $kind, string $prefix = 'kitchen_open'): array
    {
        $saved = Setting::get("{$prefix}_{$kind}_{$rvcId}");
        $raw   = $saved === null ? (self::OPEN_DEFAULTS[$kind] ?? '') : (string) $saved;
        $map = [];
        foreach (array_filter(array_map('intval', preg_split('/[+,]/', $raw) ?: [])) as $code) {
            if ($code > 0) $map[$code] = true;
        }
        return $map;
    }

    // Ayar sayfasındaki metin alanına yazılacak değer (virgüllü kod listesi).
    public static function openCodeList(int $rvcId, string $kind, string $prefix = 'kitchen_open'): string
    {
        $saved = Setting::get("{$prefix}_{$kind}_{$rvcId}");
        if ($saved === null) {
            $saved = self::OPEN_DEFAULTS[$kind] ?? '';
        }
        return (string) $saved;
    }
}
