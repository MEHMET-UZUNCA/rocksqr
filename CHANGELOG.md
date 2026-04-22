# Changelog

## v1.0.9 - 2026-04-22

### Eklenenler
- MSSQL ayar ekranına RVC (gelir merkezi) odaklı alanlar eklendi
- Senkronizasyon için özel SQL sorgusu desteği eklendi (karmaşık Symphony şemaları için)
- Test altyapısı tamamlandı: `phpunit.xml`, `tests/TestCase.php`, `database/factories/UserFactory.php`
- Parola sıfırlama akışı için `password_reset_tokens` migration'ı eklendi

### Değişiklikler
- Sync ekranında MSSQL çekim sonucu için RVC ve özel sorgu bilgisi görünür hale getirildi
- Varsayılan gelir merkezi kolon yaklaşımı RVC terminolojisiyle uyumlu hale getirildi

### Doğrulama
- `vendor/bin/phpunit --configuration phpunit.xml` ile 23 test geçti

---

## v1.0.8 - 2026-04-21

### Eklenenler
- QR kartları ve indirilen SVG dosyaları için yazdırmaya uygun masa etiketi eklendi
- A4 baskı şablonu eklendi; QR setleri ayrı pencerede yazdırılabilir hale getirildi
- QR arşiv sistemi eklendi; oluşturulan setler kaydedilip daha sonra tekrar indirilebilir veya yazdırılabilir

### Değişiklikler
- QR yönetim ekranı önizleme, yazdırma, ZIP indirme ve arşivleme aksiyonlarını tek panelde topladı

---

## v1.0.7 - 2026-04-21

### Eklenenler
- Admin paneline toplu masa QR oluşturma alanı eklendi
- Masa aralığı veya özel masa listesi ile QR önizleme desteği eklendi
- Üretilen masa QR kodlarını ZIP olarak toplu indirme özelliği eklendi
- Admin menüsüne ve dashboard hızlı erişimine QR ekranı eklendi

### Teknik
- `endroid/qr-code` paketi eklendi
- QR kodları masa linki `/table/{masaNo}` için SVG olarak üretiliyor

---

## v1.0.6 - 2026-04-20

### Değişiklikler
- Oracle Veritabanı Ayarları ayrı sayfaya taşındı (Ayarlar altından çıkarıldı)
- Admin navbar'a "Oracle" butonu eklendi (database ikonu)
- SettingsController'a oracleIndex/oracleUpdate/oracleTest metodları eklendi

### Eklenenler
- **Oracle Bağlantı Testi**: "Bağlantıyı Test Et" butonu ile Oracle veritabanı bağlantısı kontrol edilebilir
  - Başarılı bağlantıda yeşil onay mesajı (host + service bilgisi)
  - Başarısız bağlantıda kırmızı hata mesajı (detaylı hata açıklaması)
  - AJAX tabanlı, sayfa yenilenmeden sonuç gösterimi

---

## v1.0.5 - 2026-04-20

### Eklenenler
- **Oracle Veritabanı Ayarları** (Ayarlar sayfası):
  - Host, port, service name, kullanıcı adı, şifre alanları
  - Tablo ve kolon eşleme yapılandırması (ID, isim, fiyat kolonları)
  - Şifre encrypt edilerek saklanır
- **Sync Sayfası - Oracle Entegrasyonu**:
  - "Oracle'dan Çek" butonu ile Oracle POS'tan canlı veri çekme
  - Eski/yeni karşılaştırma modalı (değişenler turuncu vurgulu)
  - Eşleşen, güncel, eşleşmeyen ürün istatistikleri
  - Tek tıkla tüm değişiklikleri onaylayıp uygulama
- **Sync Sayfası - Toplu Güncelle**:
  - Tüm satırlar inline düzenlenebilir (isim, fiyat, Oracle ID)
  - Değişen alanlar sarı ile vurgulanır
  - Önizleme modalı: eski değer (kırmızı üstü çizili) → yeni değer (yeşil)
  - Onay sonrası toplu güncelleme
- **Sync Sayfası - Tek Tek Oracle ID Düzenleme**:
  - Her satırda kalem ikonu ile Oracle ID düzenleme modalı
  - Eski/yeni değer gösterimi, anında kayıt
- SyncController: index, updateOracleId, previewBulk, bulkUpdate, fetchOracle, applyOracle
- **Dashboard** - "En Çok Garson Çağrılan Masalar" top 5 listesi eklendi

### Değişiklikler
- Sync sayfası closure yerine SyncController'a bağlandı
- Ayarlar sayfasına Oracle bölümü eklendi (host, port, service, credentials, kolon mapping)
- Oracle kolon eşlemeye Ana Kategori ve Alt Kategori kolonları eklendi
- Sync ürün tablosuna Kategori kolonu eklendi
- Oracle fetch sonuçlarında kategori/alt kategori bilgisi gösteriliyor
- KDS garson çağrıları en yeniden eskiye sıralanıyor (DESC)
- KDS garson çağrıları kart görünümüne geçirildi (masa, not, süre, İlgilendi butonu)

### Düzeltmeler
- Garson çağır modalında placeholder ("Size nasıl yardımcı olabiliriz?") görünmeme sorunu düzeltildi

---

## v1.0.4 - 2026-04-20

### Değişiklikler
- KDS Mutfak Ekranı route `/admin/kitchen` → `/kitchen` olarak taşındı (auth gerektirmez)
- Siparişler en yeniden eskiye sıralanıyor (DESC)
- Süre gösterimi `HH:MM:SS` formatına güncellendi
- Onaylanan siparişlerde süre duruyor (confirmed_seconds)
- Bekleyen siparişler yanıp sönen border, onaylananlar sabit yeşil border

### Düzeltmeler
- `menu.show` route hatası düzeltildi → `menu.table`
- `table_no` null olduğunda route hatası düzeltildi (koşullu kontrol)

### İyileştirmeler
- Sipariş onay sayfası yeniden tasarlandı:
  - Animasyonlu yeşil ✓ ikonu
  - "Siparişiniz Onaylandı!" Türkçe başlık
  - Büyük toplam tutar gösterimi
  - Fade-up animasyonlar
  - CDN Tailwind (kırık @vite kaldırıldı)
- KDS sipariş kartlarına "Onayla" butonu eklendi (onaylanınca yeşil "Onaylandı")

---

## v1.0.3 - 2026-04-20

### Düzeltmeler
- Sipariş veritabanına kaydedilmeme sorunu düzeltildi (items JSON parse, table_no NULL desteği)
- `orders.table_no` ve `waiter_calls.table_no` alanları NULL kabul edecek şekilde güncellendi
- `order.success` route eklendi
- Garson çağrı mesajı Türkçe'ye çevrildi

### Eklenenler
- **KDS Mutfak Ekranı** (`/admin/kitchen`) - Tam ekran canlı sipariş takibi
  - Siparişler kart görünümünde, sipariş sırasına göre listelenir
  - Durum yönetimi: Yeni → Hazırlanıyor → Hazır → Tamamlandı
  - Bekleme süresi renk kodlu gösterge (yeşil/sarı/kırmızı)
  - 5 saniyede bir otomatik yenileme
- **Garson çağrı paneli** - Üst barda bekleyen garson çağrıları, tek tıkla onaylama
- **Sesli bildirimler** (Web Audio API):
  - Yeni sipariş geldiğinde melodili ding-dong sesi
  - Garson çağrısında acil zil sesi (farklı ton)
- KitchenController: index, updateStatus, attendWaiterCall, apiOrders
- Admin dashboard ve navbar'a Mutfak Ekranı linki eklendi

---

## v1.0.2 - 2026-04-20

### Düzeltmeler
- Ürün ve kategori adlarındaki Türkçe karakter sorunu düzeltildi (ş, ç, ı, ğ, ö, ü, İ)
- Tüm ürün açıklamaları UTF-8 uyumlu hale getirildi

### Eklenenler
- Admin ürün listesine görsel önizleme kolonu eklendi (küçük resim veya placeholder ikon)
- Admin ürün listesine açıklama satırı eklendi (ürün adı altında)
- Ürün aktif/pasif toggle butonu eklendi (tek tıkla durum değiştirme)
- `products.toggle` route ve controller method eklendi

---

## v1.0.0 - 2026-04-20

### Eklenenler
- Admin paneli (`/admin`) - giriş yapan kullanıcılar için
- Admin Dashboard sayfası (sipariş, ürün, çağrı istatistikleri)
- Kategori yönetimi (CRUD) - ekleme, düzenleme, silme
- Alt kategori desteği (parent_id ile hiyerarşik yapı)
- Ürün yönetimi (CRUD) - ekleme, düzenleme, fiyat güncelleme, silme
- Ürün fotoğraf yükleme desteği (JPEG, PNG, JPG, GIF - maks 2MB)
- AdminCategoryController (resource controller)
- AdminProductController (resource controller)
- Admin rotaları auth middleware ile korumalı (`/admin/*`)
- Müşteri menü sayfası - Rocks Hotel teması (siyah/altın)
- Masa bazlı QR menü erişimi (`/table/{tableNo}`)
- Sepet sistemi ve sipariş verme
- Garson çağırma özelliği
- User modeli ve users tablosu migration'ı
- Admin kullanıcı: admin@rockshotel.com
- 20 adet test ürünü (resimli):
  - **Yiyecekler (8):** Hamburger (120₺), Pizza (95₺), Caesar Salata (85₺), Mercimek Çorbası (45₺), Adana Kebap (180₺), Tavuk Şiş (150₺), Lahmacun (65₺), Patates Kızartması (55₺)
  - **İçecekler (6):** Kola (25₺), Çay (15₺), Taze Limonata (35₺), Ayran (20₺), Türk Kahvesi (40₺), Mojito (60₺), Smoothie (50₺), Su (10₺)
  - **Tatlılar (4):** Baklava (85₺), Künefe (95₺), Sütlaç (55₺), Tiramisu (75₺)

### Değiştirilenler
- Para birimi € (Euro) yerine ₺ (TL) olarak güncellendi (tüm sayfalar)
- Tüm admin ve müşteri arayüzleri Türkçe'ye çevrildi
- Admin layout CDN Tailwind + FontAwesome kullanacak şekilde güncellendi (@vite kaldırıldı)
- Kategori formu alt kategori (üst kategori) seçimi eklendi
- Cache driver `CACHE_STORE=file` olarak düzeltildi

### Teknik Altyapı
- Laravel 11.51.0, PHP 8.2.12, XAMPP
- MySQL veritabanı: `qr_menu`
- Tailwind CSS (CDN), FontAwesome 6.4.0 (CDN), Google Fonts Poppins
- Apache VirtualHost port 81 (HTTP) ve 443 (HTTPS)
