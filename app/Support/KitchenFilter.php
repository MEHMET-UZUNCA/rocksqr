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

    // FamGrp (ürün grubu) kodu → [etiket, açıklama] — mutfak ekranına yazdırma
    // gizle listesi için aday gruplar. Poğaça/simit ayrı MajorGroup değildir (hepsi
    // mg=1 YİYECEK içinde); ayırt eden kod FamGrpObjNum'dır (canlı probe: 1205=RP MAYALI).
    public const FOOD_FAMILIES = [
        1018 => ['Cafe Dondurma', 'Symphony ürün grubu 1018 — CAFE DONDURMA'],
        1025 => ['Açık Yiyecek', 'Symphony ürün grubu 1025 — ACIK YIYECEK'],
        1201 => ['RP Pasta', 'Symphony ürün grubu 1201 — RP PASTA'],
        1202 => ['RP Donut', 'Symphony ürün grubu 1202 — RP DONUT'],
        1203 => ['RP Ekler', 'Symphony ürün grubu 1203 — RP EKLER'],
        1204 => ['RP Ekmek', 'Symphony ürün grubu 1204 — RP EKMEK'],
        1205 => ['RP Mayalı (Poğaça / Simit)', 'Symphony ürün grubu 1205 — RP MAYALI (POGACA*, SIMIT*)'],
        1206 => ['RP Kruvasan', 'Symphony ürün grubu 1206 — RP KRUVASAN'],
        1207 => ['RP Tatlı', 'Symphony ürün grubu 1207 — RP TATLI'],
        1208 => ['RP Cheesecake', 'Symphony ürün grubu 1208 — RP CHEESECAKE'],
        1209 => ['RP Kurabiye', 'Symphony ürün grubu 1209 — RP KURABIYE'],
        1210 => ['RP KG Ürünler', 'Symphony ürün grubu 1210 — RP KG URUNLER'],
        1211 => ['RP Yaş Pasta', 'Symphony ürün grubu 1211 — RP YAS PASTA'],
        1212 => ['RP Börek', 'Symphony ürün grubu 1212 — RP BOREK'],
        1213 => ['RP Çörek & Çıtır', 'Symphony ürün grubu 1213 — RP COREK&CITIR'],
        1215 => ['RP Çikolata', 'Symphony ürün grubu 1215 — RP CIKOLATA'],
        1216 => ['RP Kuru Meyve', 'Symphony ürün grubu 1216 — RP KURU MEYVE'],
        1402 => ['RS Başlangıçlar', 'Symphony ürün grubu 1402 — RS BASLANGICLAR'],
        1405 => ['RS Kahvaltılar', 'Symphony ürün grubu 1405 — RS KAHVALTILAR (POGACA SOSISLI buradadır)'],
        1503 => ['Kahvaltılar', 'Symphony ürün grubu 1503 — KAHVALTILAR'],
        1901 => ['Pool Pizzalar', 'Symphony ürün grubu 1901 — POOL PIZZALAR'],
        1902 => ['Pool Pideler', 'Symphony ürün grubu 1902 — POOL PIDELER'],
        1903 => ['Pool Makarnalar', 'Symphony ürün grubu 1903 — POOL MAKARNALAR'],
        1904 => ['Pool Ana Yemekler', 'Symphony ürün grubu 1904 — POOL ANA YEMEKLER'],
        1905 => ['Pool Atıştırmalıklar', 'Symphony ürün grubu 1905 — POOL ATISTIRMALIKLAR'],
        1906 => ['Pool Sandviç & Burgerler', 'Symphony ürün grubu 1906 — POOL SANDVC VE BURGERLER'],
        1907 => ['Pool Salatalar', 'Symphony ürün grubu 1907 — POOL SALATALAR'],
        1908 => ['Pool Tatlılar', 'Symphony ürün grubu 1908 — POOL TATLILAR'],
        1909 => ['Pool Dondurma', 'Symphony ürün grubu 1909 — POOL DONDURMA'],
        1910 => ['Pool Tostlar', 'Symphony ürün grubu 1910 — POOL TOSTLAR'],
        4001 => ['Diğer (Pasta Mumu vb.)', 'Symphony ürün grubu 4001 — DIGER'],
    ];

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

    // Mutfak ekranına yazdırilmayacak FamGrp kodları (ters mantık: işaretli = gizli).
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
}
