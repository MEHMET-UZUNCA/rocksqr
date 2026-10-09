# RocksQR

QR menü, mutfak ve bar ekranları (KDS/BDS) ile Symphony POS entegrasyonunu
tek çatı altında toplayan restoran/otel sipariş yönetim sistemi.

**Laravel 12 · PHP 8.2 · MySQL · Tailwind CSS**

- Müşteriler masadaki QR kodu ile sipariş verir, garson çağırır.
- Garsonlar Symphony POS'a girdiği siparişler POS'un canlı işlem tablosundan
  (CheckPostingDB) gerçek zamanlı okunur — adisyon açıldığı an ekranda görünür,
  ödeme/kapanış beklemez.
- Mutfak ve bar ekranları tarayıcı üzerinden 5 saniyelik polling ile çalışır.

Ayrıntılı çalışma senaryosu, ayar anahtarları ve MSSQL sorgu kuralları için
[`benioku.txt`](benioku.txt) dosyasına bakın. Yazılım ön koşulları için
[`SYSTEM_REQUIREMENTS.md`](SYSTEM_REQUIREMENTS.md) dosyasına bakın.

---

## Ekranlar

| Ekran | Adres | Açıklama |
|---|---|---|
| Müşteri QR Menü | `/table/{masa}` | Masaya özgü mobil menü; sepet, oda numarası, garson çağrısı |
| Bar (BDS) | `/bar` | QR + Symphony birleşik akış: gelen siparişler, POS onayı, hazırlananlar, SON şeridi, garson çağrıları |
| Mutfak (KPOS) | `/kitchen-pos` | Symphony canlı akış; ürün bazlı "Hazır", tamamlama çipleri, geri alma, MBB-20 klavye kısayolları |
| Ana Mutfak (AKDS) | `/kitchen-ana` | Çoklu gelir merkezini (RVC) tek ekranda birleştiren salt-görüntüleme KDS |
| Admin Paneli | `/admin` | Kategori/ürün yönetimi, senkronizasyon, raporlar, ayarlar |

### Ekran PIN kilidi

Bar ve mutfak ekranları istenirse açılışta PIN ile kilitlenir
(Admin → Ayarlar → Ekran PIN sekmesi). Yalnız ekran açılış sayfaları korunur;
API uçları etkilenmez. Doğru PIN sonrası verilen çerez 30 gün geçerlidir,
kiosk tarayıcı her açılışta PIN sormaz. Hatalı denemeler hız sınırına tabidir.

---

## Sipariş Akışı (özet)

1. Müşteri QR'dan sipariş verir → bar ekranında "POS BEKLENİYOR" kartı belirir.
2. Garson aynı ürünleri Symphony POS'a girer → kart altın renge döner,
   "Onayla (POS'ta var)" aktifleşir; çift giriş engeli masa eşleşmesiyle çalışır.
3. Bar onaylar → mutfak ekranında ürünler tek tek "Hazır" işaretlenir
   (istenirse "Komple Hazır").
4. Tüm ürünler hazırlanınca kart bar ekranındaki yeşil
   "SİPARİŞ HAZIR — SERVİSE GÖTÜR" şeridine düşer; "Servis Edildi" ile kapanır.
5. Symphony'den ödenen/kapanan adisyonlar otomatik olarak SON şeridine iner.

Garsonun doğrudan Symphony POS'a girdiği siparişler de aynı bar/mutfak
ekranlarında canlı görünür. Aynı adisyona sonradan eklenen ürünler için
mutfakta ayrı bir "EK" rozetli yeni kart açılır.

Tam senaryo (AKDS çoklu RVC kurulumu, iade gösterimi, renk/süre eşikleri,
kapanan hesap davranışları): [`benioku.txt`](benioku.txt)

---

## Kurulum

```bash
# 1) Bağımlılıklar
composer install
npm install && npm run build

# 2) Yapılandırma
cp .env.example .env
php artisan key:generate
# .env içindeki DB_* değerlerini kendi MySQL sunucunuza göre düzenleyin

# 3) Veritabanı ve depolama
php artisan migrate
php artisan storage:link
```

MSSQL bağlantıları (Symphony) `.env` gerektirmez; kurulumdan sonra
Admin → MSSQL Ayarları üzerinden yapılır.

### Cron (üretim)

Sunucuya dakikada bir zamanlayıcıyı tetikleyen cron kaydı ekleyin —
gece ekran temizliği ve MSSQL otomatik fiyat senkronu bu yolla çalışır:

```
* * * * * cd /proje/yolu && php artisan schedule:run >> /dev/null 2>&1
```

---

## Admin Paneli

- **Kategoriler / Ürünler**: CRUD, sıralama (sürükle-bırak), toplu güncelleme/silme,
  fotoğraf yükleme, Symphony ürün kodu (mssql_id) eşleştirme.
- **Senkronizasyon**: MSSQL'den ürün çekme/aktarma, otomatik fiyat senkronu
  (kapalı / her N dakikada / günlük saat).
- **Raporlar** (3 sekmeli):
  - *Mutfak Hazırlık*: Symphony süreleri, sipariş içerikleri, en yavaş hesaplar.
  - *Süre Raporu*: aşama süreleri, ürüne/masaya göre kırılım, en geç hazırlanan ürünler.
  - *Satış Raporu*: ciro, günlük/saatlik yoğunluk, ürün ve konum kırılımı,
    garson çağrı istatistikleri.
- **Masa QR**: toplu QR üretimi, A4 baskı, ZIP indirme, arşiv.
- **Ayarlar**: ekran başlıkları/gösterim adetleri, sayaç renk eşikleri, ekran
  arka plan logoları, ekran temizleme saatleri, PIN tanımları, saat kaynağı
  seçimi, Zorunlu Alanlar (oda numarası), Oda Numaraları listesi.
- **MSSQL Ayarları** (4 bağımsız sekme): Ürün (mssql_), Symphony Mutfak/KDS
  (mssql_kds_), Symphony Bar/BDS (mssql_bds_), Ana Mutfak/AKDS (mssql_akds_)
  — her sekme kendi bağlantısı, RVC filtresi ve özel SQL sorgusuyla yönetilir;
  bağlantı testi ve sorgu önizleme içerir.

---

## Teknoloji

- Laravel 12 (PHP 8.2+), MySQL, Laravel Breeze kimlik doğrulama
- Tailwind CSS, Font Awesome
- Laravel Storage (fotoğraflar, QR arşivleri)
- PDO SQLSRV + Microsoft ODBC Driver (Symphony MSSQL bağlantısı)
- endroid/qr-code (QR üretimi), smalot/pdfparser (fiş çözümleme)

## Sürüm Geçmişi

Değişikliklerin tam listesi için [`CHANGELOG.md`](CHANGELOG.md) dosyasına bakın.
