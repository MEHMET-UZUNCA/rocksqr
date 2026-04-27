

## v1.0.54 - 2026-04-27

### Ana Mutfak (AKDS): ek sipariş tespiti ve EK SİPARİŞ badge
- **Controller** (`kitchenAnaApi()`): Check tamamlandıktan sonra Symphony'den yeni ürün eklenmesi durumunda
  sadece `completed_at` tarihinden SONRA eklenen ürünler gösterilir; eski ürünler tekrar çıkmaz.
- **Blade** (`kitchen-ana.blade.php`): Ek sipariş kartı turuncu border ile açılır;
  başlıkta **EK SİPARİŞ** badge'i (animate-pulse) görünür.

---

## v1.0.53 - 2026-04-27

### KDS: ek sipariş tespiti ve EK SİPARİŞ badge
- **Controller** (`kitchenPosApi()`): Check "Onayla → Servis" yapıldıktan sonra aynı check numarasına
  yeni ürün eklenirse kart yeniden açılır; ancak yalnızca `completed_at`'ten SONRA eklenen
  ürünler gösterilir. Eski ürünler (mutfak zaten hazırladı) tekrar listelenmez.
  - `item_time > completed_at` karşılaştırmasında Istanbul/Berlin timezone farkı Carbon ile düzgün hesaplanıyor.
- **Blade** (`kitchen-pos.blade.php`): Ek sipariş kartı turuncu border (`border-orange-500`) ile açılır;
  başlıkta **🔶 EK SİPARİŞ** badge'i (animate-pulse) görünür.

---

## v1.0.52 - 2026-04-27

### Ana Mutfak (AKDS): timer + item_time düzeltmeleri
- **Blade** (`kitchen-ana.blade.php`): Elapsed-counter span’lara `data-order-time` attribute eklendi;
  `setInterval(tickElapsed, 1000)` ile sayıç saniye saniye ilerler (5sn polling beklemiyor).
  Tekrarlayan sabit saat span’ı kaldırıldı.
- **Controller** (`kitchenAnaApi()`): Her ürün kendi `ItemTime`’ını gösterir (`OrderTime` fallback).
  `order_time` = gruptaki en erken `item_time`. `order_time` ISO8601 formatında JS’e iletilir.

---

## v1.0.51 - 2026-04-27

### KDS: item_time düzeltmesi + ek sipariş altyapısı + tekrarlayan saat kaldırıldı
- **Controller** (`kitchenPosApi()`): Her ürün kendi `ItemTime`’ını gösterir (`OrderTime` fallback).
  Kart başlığındaki `order_time` = gruptaki en erken ürün zamanı (artık tüm ürünlere aynı saat yazmaz).
  Completion filter altyapısı eklendi (`kitchen_pos_completions` tablosundan `completed_at` okunuyor).
- **Blade** (`kitchen-pos.blade.php`): Sayacın yanındaki tekrarlayan sabit saat span’ı kaldırıldı.

---

## v1.0.50 - 2026-04-27

### Bar: tamamlanan garson çağrıları kart olarak birleştirildi
- **Blade** (`bar.blade.php`): “Son İlgilenilen Garson Çağrıları” ayrı satır yerine Son Tamamlananlar
  grid’ine kart olarak eklendi. Kart yapısı: yeşil border, `bell-slash` ikonu, **ÇAĞRI** badge.

---

## v1.0.49 - 2026-04-26

### Timer + garson çağrısı “İlgilendi” aktarımı
- **Blade** (`kitchen-pos.blade.php`): Elapsed-counter span’lara `data-order-time` attribute eklendi;
  `setInterval(tickElapsed, 1000)` ile sayıç saniye saniye ilerler.
- **Controller** (`barApiOrders()`): Son 10 dk içinde `attended` olan garson çağrıları
  `attended_calls` alanıyla API’ye eklendi.

---


### AKDS: `{{RVC}}` placeholder desteği ve admin panel RVC alanı
- **Controller** (`kitchenAnaApi()`): SQL sorgusu çalıştırılmadan önce `{{RVC}}` placeholder'ı, admin panelindeki `mssql_akds_rvc_filter` ayarından gelen sayısal değerle değiştiriliyor.
  - Placeholder varken alan boşsa açıklayıcı hata mesajı döner.
  - Güvenlik: yalnızca `^\d+$` (tam sayı) kabul edilir — SQL injection engeli.
- **View** (`mssql-settings.blade.php`): AKDS sekmesine `rvcLabel`, `rvcPlaceholder`, `rvcHint` parametreleri eklendi; queryHint `{{RVC}}` kullanımını açıklar.
- **Partial** (`mssql-section.blade.php`): `$rvcLabel`, `$rvcPlaceholder`, `$rvcHint` parametreleri desteklendi. AKDS için sağ bilgi kutusu `{{RVC}}` akışını anlatır, diğer sekmeler eski metni korur.
- **Kullanım**: SQL'de `WHERE chk.Rvc = {{RVC}}` yaz → RVC Filtresi alanına yalnızca `43` gir → Kaydet. RVC değişince sadece filtre alanını güncellemek yeterli, SQL'e dokunulmaz.

---

## v1.0.43 - 2026-04-26

### Ana Mutfak KDS (AKDS) — sadece görüntüleme ekranı
- **Yeni ekran** `/kitchen-ana`: Ayrı MSSQL veritabanından canlı açık siparişleri gösteren, aksiyon butonu olmayan salt-görüntüleme KDS ekranı.
  - Slate-900 arka plan, teal-400 aksanlar; grid kart düzeni.
  - Her kart: masa no, hesap no, RVC / gelir merkezi, ürünler (adet + ad + not), geçen süre.
  - Süre renk eşikleri: **<5dk teal** → **5–10dk teal-700** → **10–15dk yellow** → **15dk+ kırmızı**.
  - 5 saniyede bir otomatik yenileme, yeni sipariş ses uyarısı, tam ekran butonu.
- **Yeni API** `/kitchen-ana/api` (`kitchenAnaApi()`): AKDS ayarlarından bağlantı + sorgu okur, ham SQL çalıştırır, `CheckNumber`'a göre gruplar.
- **MSSQL Ayarları** — 4. sekme **"Ana Mutfak (AKDS)"** eklendi (teal tema):
  - Bağımsız host/port/database/username/password alanları.
  - Özel SQL sorgusu + RVC Filtresi alanı.
  - Test Bağlantısı ve Önizle butonları.
- **Dashboard**: "Ana Mutfak (AKDS)" hızlı erişim kartı eklendi (teal, `route('kitchen.ana')`).
- **KDS başlık**: `/kitchen-pos` ekranının üst çubuğuna "Ana Mutfak" bağlantısı eklendi.
- Route'lar auth'suz public display screen grubuna eklendi.

---

## v1.0.42 - 2026-04-26

### Ayarlar: Ekran Ayarları Bar ve Kitchen olarak ayrıldı
- **Bar Ekran Ayarları** bölümü: Bar ekranı başlığı, tamamlanan sipariş sayısı, sipariş hazır alanı adedi, sipariş karı adedi — ayrı "Bar Ekran Ayarlarını Kaydet" butonu.
- **Kitchen Ekran Ayarları** bölümü: Mutfak ekranı başlığı, tamamlanan sipariş sayısı, garson çağrıları adedi, geri alma süresi — ayrı "Kitchen Ekran Ayarlarını Kaydet" butonu.
- Controller: `_display_only=bar` ve `_display_only=kitchen` ayrı validation + save branch'i.

---

## v1.0.41 - 2026-04-26

### MSSQL Ayarlar: KDS ve BDS Symphony için bağımsız RVC filtresi
- **Symphony Mutfak (KDS)** sekmesi: Bağımsız `mssql_kds_rvc_filter` alanı eklendi, tab başlığı güncellendi.
- **Symphony Bar (BDS)** sekmesi: Bağımsız `mssql_bds_rvc_filter` alanı eklendi, tab başlığı güncellendi. KDS'den fallback değer alma kaldırıldı.
- Partial `mssql-section.blade.php`: RVC filtre field adı `$rvcField` parametresiyle dinamik hale getirildi.

---

## v1.0.40 - 2026-04-26

### Ayarlar: Görüntülenecek adet limitleri eklendi
- **Garson Çağrıları: Görüntülenecek Adet** (`waiter_call_display`, varsayılan: 10)
- **Sipariş Hazır Alanı: Görüntülenecek Adet** (`order_ready_display`, varsayılan: 10)
- **Sipariş Karı: Görüntülenecek Adet** (`order_profit_display`, varsayılan: 20)
- Controller'a validation ve kayıt eklendi.

---

## v1.0.39 - 2026-04-26

### MSSQL Sync: Lokal product code'lu ürün bazlı karşılaştırma
- **Eski davranış**: MSSQL'deki tüm ürünler çekilir, local'e eşleştirilirdi. Eşleşmeyenler ayrı liste.
- **Yeni davranış**: Lokal'de `mssql_id` (product code) atanmış ürünler alınır; her biri MSSQL'de aranır.
- **Durum badge'leri**: 🟡 Değişti (seçilebilir, güncellenebilir) · 🟢 Güncel · 🔴 MSSQL'de Yok
- Tablo: Checkbox | Product Code | Yerel Ürün Adı | Durum | Ad Değişimi | Fiyat Değişimi | MSSQL Grup
- Sıralama: Değişti → MSSQL'de Yok → Güncel

---

## v1.0.38 - 2026-04-26

### MSSQL Sync modal açılmıyordu — düzeltildi
- Önceki commit'te (`v1.0.37`) modal HTML eklendi fakat eski inline panel HTML silinmemişti. İki ayrı `id="mssql-panel"` oluştu; JS ilkini (inline, `fixed` değil) buluyordu.
- Eski `hidden bg-white rounded-xl...` inline paneli kaldırıldı, `hidden fixed inset-0 bg-black/60 z-50...` modal yapısı kaldı.

---

## v1.0.37 - 2026-04-26

### MSSQL Sync paneli → fixed modal
- MSSQL karşılaştırma paneli, Symphony Import ile aynı fixed overlay modal yapısına geçirildi.
- Tümünü Seç artık hem eşleşen (`mssql-check`) hem eşleşmeyen (`mssql-unmatched-check`) checkboxları seçiyor.

---

## v1.0.36 - 2026-04-26

### MSSQL Sync: grup bazlı collapse + üst güncelle butonu
- "Tüm ürünler güncel" mesajı eşleşmeyen varsa bunu belirtecek şekilde düzeltildi.
- Eşleşmeyen ürünler grup bazlı collapse (accordion) yapısına geçirildi.
- "Seçilenleri Güncelle" butonu tablonun üstüne de eklendi.

---

## v1.0.35 - 2026-04-26

### MSSQL Sync: eşleşmeyen ürünler tablo + checkbox
- Eşleşmeyen MSSQL ürünleri grid yerine tablo formatında listeleniyor.
- Her satırda checkbox var; "Tümünü Seç / Seçimi Kaldır" desteği.

---

## v1.0.34 - 2026-04-26

### Product Code inline edit + MSSQL karşılaştırma tablosu
- Product Code düzenleme modal kaldırıldı; tablo hücresinde kalem butonu ile açılan inline edit.
- MSSQL diff tablosu yenilendi: Yerel Ad / MSSQL Ad / Yerel Fiyat / MSSQL Fiyat / Grup kolonları.

---

## v1.0.33 - 2026-04-26

### JS syntax hatası düzeltildi
- `cancelBulk()` fonksiyonunda orphan satırlar tüm sayfa JS'ini çökertiyordu. Fazla kapanışlar kaldırıldı.

---

## v1.0.32 - 2026-04-26

### `$symphonyConfigured` koşulu düzeltildi
- `$symphonyConfigured` artık `mssql_custom_query` gerektirmiyor; sadece `mssql_host` + `mssql_database` kontrolü yapıyor.
- `$mssqlConfigured` hâlâ üç koşul gerektiriyor (host + database + custom_query).

---


### Sync Sayfası Komple Yeniden Tasarımı
- **Kolon sırası düzeltildi**: ID → Product Code → Ürün Adı → Kategori → Fiyat → Durum → İşlem (tüm tablolarda tutarlı).
- **Stats kartları**: Toplam ürün / Product Code Var / Product Code Yok / Eşleşme Oranı (%).
- **Arama + filtre**: Canlı ad araması ve "Kodu Var / Kodu Yok" filtresi.
- **Satır checkbox'ları**: Her ürüne tick ekle; "Tümünü Seç" header checkbox; seçim bağlamsal aksiyon çubuğu (mavi bant).
- **"Seçilenleri Düzenle"** ile sadece seçili satırlarda edit-mode açılır; bağlamsal turuncu düzenleme çubuğu gösterilir.
- **MSSQL Sync inline panel**: "MSSQL Sync" tıklayınca karşılaştırma sonuçları modal değil, sayfada inline açılır; değişiklik olan satırlar otomatik seçili gelir.
- Product Code "Yok" olan ürünler tur-turuncu uyarı ikonuyla belirtilir.
- Tüm butonlar rounded-lg ve daha belirgin; çalışma alanı card bazlı layout.

---
## v1.0.28 - 2026-04-25

### Toplu Güncelle Ürünler Sayfasına Taşındı
- **Admin → Ürünler** sayfasındaki **Toplu Güncelle** butonu artık başka bir sayfaya yönlendirmez. Aynı sayfada inline düzenleme moduna geçer.
- Toplu modda düzenlenebilir alanlar: **Ürün Adı**, **Kategori** (ana/alt birleşik dropdown), **Fiyat**.
- **Product Code (MSSQL ID)** kolonu salt-okunur olarak görünür (sky-100 rozet).
- "Değişiklikleri Önizle" → modal'da eski/yeni karşılaştırması → "Onayla ve Güncelle".
- Backend `admin.sync.preview` ve `admin.sync.bulk` endpoint'leri yeniden kullanılır.
- `?bulk=1` query parametresi ile sayfa açılır açılmaz toplu mod aktive olur (eski Sync linki uyumlu).

---

## v1.0.27 - 2026-04-25

### Toplu Güncelle Geliştirmeleri
- **Kategori artık toplu modda düzenlenebilir**: Her satırda ana/alt kategori seçimi için açılır liste gelir.
- **Ürün adı ve fiyat** düzenlemesi mevcut (değişiklik yok).
- **Product Code (MSSQL ID) artık salt-okunur** olarak gösterilir; toplu güncelleme akışında değiştirilemez (tek tek "MSSQL ID Düzenle" sunucu butonu mevcut).
- Önizleme ve onaylama akışında kategori değişiklikleri "Eski Kategori → Yeni Kategori" olarak gösterilir.
- Backend: `SyncController::previewBulk` ve `bulkUpdate` artık `category_id` kabul eder.

---

## v1.0.26 - 2026-04-25

### Değişenler
- **"Toplu Güncelle" butonu Sync sayfasından Ürünler sayfasına taşındı**: Admin → Ürünler ekranında **"Yeni Ürün"** butonunun yanına eklendi. Tıklandığında Sync sayfası `?bulk=1` parametresiyle açılır ve otomatik olarak toplu düzenleme moduna geçer.

## v1.0.25 - 2026-04-25

### Değişenler
- **Symphony İmport modalı genişletildi** (`max-w-3xl` → `max-w-7xl`, yükseklik %95). Üstüne **Tümünü Seç / Seçimi Kaldır** butonları eklendi.
- **"MSSQL'den Çek" butonu → `Sync`** olarak yeniden adlandırıldı (ikon: `fa-rotate`).
- **Sync modalı tamamen yeniden tasarlandı**: Eski/Yeni karşılaştırma artık tablo formatında (MSSQL ID • Yerel ürün • Eski Ad/Fiyat • Yeni Ad/Fiyat • Grup/RVC). Her satırda **checkbox** var; **Tümünü Seç / Seçimi Kaldır** butonları ve başlıktaki üçlü-durum (checked/indeterminate) checkbox'ı ile sadece seçilen satırlar güncellenir. Modal `max-w-7xl` ile genişletildi.

## v1.0.24 - 2026-04-25

### Eklenenler
- **Bar ekranına tam ekran butonu**: Üst bar'a `⛶` ikonu eklendi (mutfak ekranıyla aynı). Tıklayınca tarayıcı tam ekrana geçer, ikon `⤓` (compress) olur. PWA olarak yüklendiğinde de aynı kısayol çalışır.

### Değişenler
- **Mutfak/Bar son sipariş limiti artık serbest sayı**: Admin → Ekran Ayarları'ndaki "Mutfak Ekranı: Tamamlanan Son Sipariş Sayısı" ve "Bar Ekranı: Sipariş Hazır Son Sipariş Sayısı" alanları sabit `3/6/12/24` listeden çıkarılıp **1–100 arası serbest sayı girişi** olarak değiştirildi.

## v1.0.23 - 2026-04-25

### Değişenler
- **Bar ekranı: "Geri Al" butonu kaldırıldı**: Yeşil "SİPARİŞ HAZIR — SERVİSE GÖTÜR" şeridindeki kartlardaki sarı **`Geri Al`** butonu kaldırıldı. Geri alma sadece mutfak (KDS) ekranından yapılır; bar yalnızca **`Servis Edildi`** ile akışı sonlandırır.

## v1.0.22 - 2026-04-25

### Düzeltildi
- **Bar ekranı: Symphony "Servis Edildi" sonrası Tamamlananlara düşmüyordu**: Yeşil ready şeridindeki Symphony mesaj/sipariş kartı `Servis Edildi`'ye basılınca `delivered_at` set ediliyordu ama **Tamamlanan Siparişler** bölümü sadece yerel `orders.kitchen_status='completed'` kayıtlarını gösteriyordu, Symphony tarafı görünmüyordu.
- Artık `kitchen_pos_completions` tablosunda `delivered_at IS NOT NULL` olan kayıtlar da Tamamlananlara karışıyor (mavi `SYMPHONY` rozetiyle), QR/Symphony en yeni servis sırasıyla birleşik gösterilir, `bar_completed_display` sınırı uygulanır.

## v1.0.21 - 2026-04-25

### Eklenenler
- **QR siparişlerin "Servis Edildi" butonu**: Bar ekranındaki yeşil **"SİPARİŞ HAZIR — SERVİSE GÖTÜR"** şeridindeki QR kartlarına da artık **`Servis Edildi`** butonu eklendi (Symphony kartlarında zaten vardı).
  - Tıklanınca sipariş `kitchen_status='completed'` olur ve **Tamamlanan Siparişler** bölümüne düşer.

## v1.0.20 - 2026-04-25

### Eklenenler
- **Hibrit Symphony doğrulaması (bar ekranı)**: QR siparişi geldiğinde Onayla butonu, ilgili masanın Symphony POS'ta açık adisyonu olana kadar **pasif** durumda bekler:
  - Yeni QR siparişi → kart turuncu kenarlıkla **`POS BEKLENIYOR`** etiketiyle görünür, buton "POS bekleniyor..." (kum saati animasyonu, tıklanamaz)
  - Garson Symphony'ye girince (≤5 sn içinde algılanır) → kart altın renge döner, buton aktifleşir: **"Onayla (POS'ta var)"**
  - Onayla'ya basılınca QR kartı kaybolur ve aynı masanın **mavi `SYMPHONY` kartı** akışı devralır (zaten POS'ta olduğu için)

### Mantık
- Eşleşme `table_no` üzerinden yapılır (Symphony BDS sorgusundan dönen masalar ile QR siparişlerin `table_no`'su karşılaştırılır)
- Ekstra MSSQL yükü yok — mevcut bar/symphony API yanıtları frontend'de eşleştiriliyor

## v1.0.19 - 2026-04-25

### Düzeltildi
- **Bar onayladıktan sonra sipariş kayboluyordu**: QR siparişi `Onayla`'ya basıldığında `bar_status='approved'` oluyordu ama API sadece `new` olanları getirdiği için kart anında ekrandan siliniyor, mutfak `ready` deyene kadar ortada görünmüyordu.
- Artık onaylanmış siparişler grid'de **mavi `MUTFAKTA` rozeti** ve **"Mutfakta hazırlanıyor"** alt bilgisi ile kalmaya devam eder; mutfak siparişi hazır olarak işaretleyince yukarıdaki yeşil **"SERVİSE GÖTÜR"** şeridine geçer.

## v1.0.18 - 2026-04-25

### Düzeltildi
- Bar ekranındaki Symphony sipariş kartlarında saat alanı **`YYYY-MM-DD HH:MM:SS`** formatında görünüyordu — artık QR kartlarıyla aynı şekilde sadece **`HH:MM:SS`** gösteriliyor.

## v1.0.17 - 2026-04-25

### Değişenler
- **Bar ekranı tek tip kart**: Symphony ve QR siparişleri artık tek bir grid'de aynı kart şablonuyla render ediliyor (mutfak ekranıyla tutarlı görünüm).
  - Üstte küçük rozet: mavi **SYMPHONY** (POS) / mor **QR** (QR menü)
  - Rozet konumu mutfak ekranıyla birebir aynı: **Masa numarasından sonra**, hesap etiketinden önce
  - Hesap numarası önüne **`CHK #`** kısaltması eklendi (örn. `CHK #3626`)
  - Symphony kartlarında **Onayla** butonu yerine **"POS'ta"** bilgi rozeti
  - Tüm kartlar `seconds_ago` artan sıraya göre — **en yeni sol üstte**
- Eski ayrı **"Symphony POS Siparişleri"** bölümü kaldırıldı.

## v1.0.16 - 2026-04-25

### Eklenenler
- **BDS (Bar Display System) — yeni MSSQL ayarı sekmesi**:
  - Admin → MSSQL Ayarları altında **BDS (Bar)** sekmesi eklendi (Ürün ve KDS yanında 3. sekme)
  - Kendi host/port/db/user/pwd alanları (boş bırakılırsa otomatik KDS bağlantısını kullanır)
  - Kendi özel SQL sorgusu (Symphony'den canlı bar siparişlerini çekmek için)
  - **Test Bağlantısı** ve **Önizleme** butonları çalışır
- **Bar ekranında Symphony POS canlı siparişleri**:
  - Üstte ayrı **"Symphony POS Siparişleri"** bölümü (mavi tema, `SYMPHONY` rozetli kartlar)
  - Onayla butonu YOK — POS'a zaten girilmiş, sadece görsel takip
  - Renk eşikleri: **<5dk yeşil**, **5–10dk sarı**, **10dk+ kırmızı**
  - Her 5 saniyede otomatik güncellenir
- Beklenen kolon adları (case-insensitive): `TableNo`, `ItemName`, `Qty`, `OrderTime`, `CheckNumber`, `Note`
- Yeni route: `GET /bar/api/symphony`

## v1.0.15 - 2026-04-25

### Eklenenler
- **Symphony onayları artık bar ekranına da düşüyor** — QR siparişleriyle simetrik akış:
  - KDS'de Symphony hesap kartı veya mutfak mesajı **Onayla → Servis** ile onaylandığında, bar ekranındaki **"SİPARİŞ HAZIR — SERVİSE GÖTÜR"** şeridine otomatik eklenir
  - Bar ekranındaki kartta kaynak rozeti gösterilir: mavi **SYMPHONY** veya mor **QR**
  - **Geri Al** butonu (admin panelindeki `ready_undo_seconds` süresince) — Symphony kayıtları için KDS uncomplete endpoint'ini çağırır, kayıt tamamen kalkar
  - **Servis Edildi** butonu (sadece Symphony girdileri için) — bar'dan kaldırır ama KDS Tamamlananlar listesinde rapor için kalır
- `kitchen_pos_completions` tablosuna `delivered_at` kolonu eklendi (null = bar'da görünür)
- Yeni route: `POST /bar/symphony/delivered`

## v1.0.14 - 2026-04-25

### Eklenenler
- **KDS Mutfak Mesajları onayla/tamamla akışı**:
  - Symphony hesap kartlarındaki her mutfak mesajının yanına büyük yeşil **"Onayla → Servis"** butonu (QR/hesap kartlarıyla aynı stil)
  - Onaylanan mesaj aktif listeden kalkar, alt **"Son Tamamlananlar"** bölümüne sarı kartla düşer
  - Sadece mesajdan oluşan boş Symphony kartı otomatik gizlenir
  - Symphony bazı mesajlar için `ItemID` döndürmediğinde fallback olarak `tableNo + checkNum + dtlSeq + name + note + itemTime` md5'inden benzersiz `m-xxx` üretilir (toplu onay bug fix)
- **Symphony hesap kartlarına da "Onayla → Servis" butonu** eklendi (eski "sadece görüntüleme" yazısı kaldırıldı). Onaylanan hesap alt panelde mavi **SYMPHONY** rozetli kart olarak görünür.
- **Tamamlananlar bölümü** kaynak rozetleri:
  - **QR MENU** (mor) → QR siparişler
  - **SYMPHONY** (mavi) → Symphony hesapları & mesajları
- **Sağ üst toast bildirim sistemi** (slide-in animasyonlu, hata/başarı/info renkleri, 3.5sn sonra otomatik kaybolur) — eski `alert()` popup'ları kaldırıldı

### Düzeltmeler
- "Geri Al" butonunun çalışmama bug'ı: `JSON.stringify` çıktısının HTML attribute içindeki çift tırnaklarla çakışması — `data-uncomplete-key` + `dataset` ile çözüldü
- "Geri alma süresi doldu" mesajı artık inline toast olarak görünür (alert popup yerine)

### Değişiklikler
- **`ready_undo_seconds` ayarı** artık hem QR siparişlerin hem de mutfak mesajlarının/Symphony hesaplarının Geri Al süresini kontrol eder (Admin → Ayarlar → "Geri Alma Süresi (saniye)")
- Mutfak mesajı/hesap onaylamaları DB'den **silinmez** (raporlama için kalıcı tutulur); UI sadece son N tanesini (kitchen_completed_display) gösterir
- 12 saatlik zaman penceresi filtresi kaldırıldı

### Veritabanı
- `kitchen_pos_completions` tablosuna `name`, `note`, `qty` kolonları eklendi (yeni migration: `2026_04_25_160000_add_message_fields_to_kitchen_pos_completions_table`) — onaylanan mesajların alt panelde gösterilebilmesi için

---

## v1.0.13 - 2026-04-25

### Eklenenler
- Symphony POS hesap kartlarındaki **Mutfak Mesajları** satırlarının yanına yeşil **"Onayla"** butonu eklendi; tıklanan mesaj listeden kalkar ve 12 saat boyunca tekrar gösterilmez (`kitchen_pos_completions` üzerinden filtrelenir)

### Kaldırılanlar
- KDS QR kartlarındaki **"Symphony'e işlendi"** butonu, **SYMPHONY?/SYMPHONY OK** rozetleri ve `toggleSymphony()` JS fonksiyonu kaldırıldı
- `kitchenPosToggleSymphony` controller methodu ve `PATCH /kitchen-pos/qr/{order}/symphony` route'u kaldırıldı
- `Order` modelinden `symphony_processed_at` fillable/cast kayıtları kaldırıldı

### Veritabanı
- `orders.symphony_processed_at` kolonu drop edildi (yeni migration: `2026_04_25_150000_drop_symphony_processed_at_from_orders_table`)

---

## v1.0.12 - 2026-04-25

### Eklenenler
- **Symphony POS Mutfak Ekranı (`/kitchen-pos`)** artık QR menü siparişlerini de gösteriyor; Symphony hesapları read-only kalıyor, QR kartları **mor "QR" şeritli** ayrı stille basılıyor
- QR kartında **"Onayla → Servis"** butonu: tıklayınca yerel sipariş `kitchen_status=ready` olur ve **Bar ekranındaki "SİPARİŞ HAZIR — SERVİSE GÖTÜR"** şeridine düşer
- QR kartında **"Symphony'e işlendi"** geçişi: garson Symphony POS'a manuel girdikten sonra işaretler; kart üst kısmı yanıp sönen kırmızı **"SYMPHONY?"** rozetinden sabit yeşil **"SYMPHONY OK"** rozetine döner (yerel `orders.symphony_processed_at` alanında saklanır)
- Bar ekranına **"Son Tamamlananlar"** alt bölümü eklendi (mutfak `completed` siparişler, `bar_completed_display` ayarı ile sınırlı)
- Süre/yaş rengi (yeşil/sarı/kırmızı, 10/15 dk eşiği) Symphony hesaplarıyla aynı şekilde QR kartlarında da çalışıyor
- Yeni Settings sekmesi gerekmeden, ürün bazında **mutfak/bar yönlendirme** kontrolü:
  - Ürün form ekranına `Mutfak ekranında görünsün` ve `Bar ekranında görünsün` checkbox'ları eklendi
  - Ürün listesinde her satırın yanında **KDS** / **BAR** rozetleri görünür hale geldi
  - Kitchen-POS'daki QR kartında yalnızca `show_in_kitchen=true` ürünler listelenir; tek bir bar ürünü olan sipariş kitchen ekranında hiç gösterilmez

### Değişiklikler
- `kitchen_pos_completions` tablosu artık ana akışta kullanılmıyor (legacy endpoint geriye uyumluluk için duruyor); QR siparişler doğrudan `orders` tablosu üzerinden tamamlanır
- `mapOrder` ve bar API yanıtı `completed_orders` + `completed_orders_limit` alanlarıyla genişletildi

### Veritabanı
- `products.show_in_kitchen` (boolean, default `true`) eklendi
- `products.show_in_bar` (boolean, default `false`) eklendi
- `orders.symphony_processed_at` (nullable timestamp) eklendi

### Notlar
- Symphony tarafına yazma yapılmaz; "Symphony'e işlendi" sadece operasyonel takip içindir
- Mutfak mesajları (Symphony `MajGrp=99`) bu değişikliklerden etkilenmez

---

## v1.0.11 - 2026-04-25

### Eklenenler
- Symphony Import: **ProductCode (mssql_id) sabit anahtar** olarak kullanılıyor — tekrar senkronizasyonda mevcut ürünler bulunup güncelleniyor, yeni olanlar ekleniyor (silinmiyor)
- Symphony çoklu fiyat seviyesi (HierStrucID) için otomatik **deduplication**: ürün başına tek satır, en yüksek seviye önceli (RVC > Property > Enterprise)
- Kullanıcının PascalCase alias'ları desteklendi: `ProductCode`, `ProductName`, `FamilyGroup`, `Price`, `PriceLevel`, `PriceLevelID`
- MSSQL Ayarları sayfasına **Sorguyu Önizle** butonu eklendi: özel SQL sorgusunu doğrudan çalıştırıp ilk 100 satırı tablo halinde modal'da gösterir (yalnızca SELECT/WITH)
- MSSQL Ayarları sayfası **sekmeli** yapıya kavuştu: **Ürün (Symphony)** ve **KDS (Mutfak)** için ayrı bağlantı + ayrı SQL sorgusu yönetimi
- KDS için kendi host/port/db/kullanıcı/şifre/sorgu alanları; her sekme için bağımsız Test ve Önizle butonları
- **Symphony POS Mutfak Ekranı** (`/kitchen-pos`): KDS sorgusunu canlı çalıştırıp hesapları ve mutfak mesajlarını gösteren read-only ekran
  - Hesaplar `CheckNumber`'a göre gruplanıyor; her kart masa, hesap, gelir merkezi, kişi sayısı ve geçen süre gösterir
  - **Mutfak mesajları (MajGrp=99)** iki türlü işleniyor: hesap içindekiler kartın üstünde sarı banner; **checksiz** olanlar sayfanın en üstünde ayrı flash bölüm
  - 5 saniyede bir otomatik yenileme, yeni hesaplarda ses uyarısı
  - **Onayla / Tamamla butonu**: hesap veya checksiz mesaj tamamlandığında alttaki "Son Tamamlananlar" bölümüne taşınır (24 saat saklanır), tek tıkla "Geri Al"
  - Admin menüsüne **Kitchen-Symphony** linki eklendi
  - Yerel ↔ Symphony KDS ekranları arası çapraz linkler eklendi

### Düzeltmeler
- Aynı ürünün birden fazla fiyat satırıyla gelmesi durumunda son satır yerine en spesifik fiyatın seçilmesi sağlandı

---

## v1.0.10 - 2026-04-24

### Eklenenler
- Mutfak ekranında sipariş kartlarında **Masa Numarası** gösterimi eklendi
- Mobil müşteri menüsünde **alışveriş sepeti altına Garson Çağır butonu** eklendi (sepet kapalıyken de açılabilir)

### İyileştirmeler
- Kitchen display screen'de sipariş başında masa bilgisi artık görünür ("Siparis #123 Masa 5" formatında)
- Mobil kullanıcılar sepeti açmadan garson çağırabilir
- Responsive design mobile-first yaklaşımla optimize edildi

---

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
