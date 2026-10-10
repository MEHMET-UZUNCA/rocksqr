

## v1.0.103 - 2026-10-10

### Grafikli Dashboard v2 + raporlarda ekran filtresi (KPOS / AKDS)
- **Dashboard yenilendi (grafikli)**: 8 istatistik kartı korundu; altına iki yeni grafik eklendi — "Son 14 Gün" (günlük sipariş adedi sütun + ciro çizgisi, çift eksen) ve "Saatlik Yoğunluk" (son 30 gün, saat bazlı sipariş dağılımı). Grafikler Chart.js ile sayfaya özel yüklenir; veriler mevcut sipariş tablosundan türetilir, yeni veri kaynağı yoktur. Başlık çubuğuna bugünün tarihi eklendi; Hızlı Erişim, En Çok Satılan Ürünler, En Çok Garson Çağrılan Masalar ve Son Ayar Hareketleri panelleri aynen korundu (çağrılan masalar + ayar hareketleri artık yan yana).
- **Raporlarda ekran filtresi**: Mutfak Hazırlık ve Süre Raporu sayfalarına "Ekran" seçici eklendi — Tümü / Mutfak (KPOS: RVC 44+81) / Ana Mutfak (AKDS: RVC 43,45,46,63). Gelir merkezi sınırları ekran sabitlerinden (KitchenFilter) okunur.
- **Ürün geçmişinde gelir merkezi**: kitchen_item_logs tablosuna rvc_id kolonu eklendi (Symphony onaylarında hangi gelir merkezinden geldiği yazılır; QR satırları 0). Böylece "Ürüne Göre Hazırlık", "En Geç Hazırlanan Ürünler" ve "Masaya Göre" panelleri de ekran filtresine uyar. KPOS filtresi QR onaylarını da içerir (QR yalnız mutfak ekranında onaylanır); AKDS filtresi yalnız kendi gelir merkezlerinin satırlarını gösterir.
- **Süre Raporu'nda AKDS görünümü**: Ana Mutfak seçildiğinde yalnız Symphony'e ait bölümler (aşamalar, ürün/masa panelleri, son teslimler) gösterilir; QR'ya özel paneller gizlenir. Satış Raporu bu değişiklikten etkilenmez.
- **Veri temizliğine hazır**: Test dönemi verilerinin (siparişler, çağrılar, mutfak kayıtları) canlıya açılış öncesi sıfırlanması bu sürümle birlikte yapılır; ürünler, kategoriler, kullanıcılar ve ayarlar korunur.

---

## v1.0.102 - 2026-10-10

### Roller, profil, ad soyad + panel geneli cila
- **Ad Soyad ayrı alanlar**: users tablosuna surname (Soyad) kolonu eklendi; Ad ve Soyad artık ayrı ayrı girilir. Panelde ad soyad birleşik gösterilir (sadece ad girilmişse ad, hiçbiri yoksa e-posta görünür). Mevcut kullanıcılar etkilenmez.
- **Rol ve aktiflik alanları**: users tablosuna role (admin/personel, varsayılan personel) ve is_active (aktif/pasif) kolonları eklendi. Kurulumda var olan kullanıcılar yönetici (admin) olarak işlendi.
- **Personel rolü kısıtı**: Personel rolündeki kullanıcılar panelde yalnızca Dashboard, Ekranlar ve Raporlar bölümlerini görür; Kategoriler, Ürünler, Masa QR, Entegrasyon, Kullanıcılar ve Ayarlar bölümlerine girişi engellenir (yönlendirme + Türkçe uyarı). Menüde de yalnızca erişebildiği bağlantılar görünür.
- **Pasif kullanıcı giremez**: Pasif işaretlenen kullanıcı oturum açamaz; açık oturumu bir sonraki istekte güvenle kapatılır ve "Hesabınız devre dışı bırakılmış." uyarısı gösterilir.
- **Son yönetici koruması**: Son admin'in rolü düşürülemez, hesabı pasif yapılamaz ve silinemez. Kullanıcı kendi rolünü ve aktiflik durumunu değiştiremez (formda alanlar kilitli, sunucu tarafında da zorunlu).
- **Profilim sayfası**: Her kullanıcı (personel dahil) ad, soyad, e-posta ve şifresini Profilim sayfasından güncelleyebilir. Şifre değişimi isteğe bağlıdır; değiştirilecekse mevcut şifre zorunludur. Üst çubuktaki profil düğmesi ve sol menü altındaki kullanıcı kutusu bu sayfaya gider.
- **Açık kayıt kaldırıldı**: /register sayfası ve kayıt yolu kapatıldı — panel hesapları artık yalnızca yönetici tarafından Kullanıcılar sayfasından açılır. Eski Breeze profil sayfası ve içindeki kendi hesabını silme formu da kaldırıldı (yerine korumalı Profilim geçti); e-posta doğrulama ve şifre sıfırlama sayfaları durur.
- **Dashboard'a Son Ayar Hareketleri**: Ayar denetim kaydındaki (settings_audit_logs) son 8 değişiklik Dashboard'da listelenir (anahtar, yeni değer, kullanıcı, zaman).
- **İçerik sayfalarına cila**: Tüm yönetim sayfaları (Kategoriler, Ürünler, Masa QR, MSSQL Ayarları, Ayarlar, Raporlar, Kullanıcılar, Sync) tek tip başlık düzenine alındı: altın ikon rozetli başlık çubuğu + sayfa adı. Üst çubuk başlığı ve tarayıcı sekmesi artık sayfa adını gösterir.
- **Dağıtım öncesi tam yedek**: v1.0.102 canlıya alınmadan önce tam yedek alındı (veritabanı dump + public/images + .env kopyası; sunucuda /root/rocksqr-backup-2026-10-10-2 ve geliştirme klasöründe backups/ altında).

---

## v1.0.101 - 2026-10-10

### AdminLTE görünümlü sidebar yerleşimi + Kullanıcılar modülü
- **Admin paneli sol menülü (sidebar) yerleşime geçti**: Klasik yönetim paneli görünümünde, solda sabit lacivert (marka #1A1A2E) menü çubuğu + üstte ince başlık çubuğu. Tüm mevcut menü öğeleri korundu: Dashboard/Kategoriler/Ürünler üst bölümde; Ekranlar, Entegrasyon, Yönetim başlıklı gruplar altında. Aktif sayfa altın vurgu ile işaretlenir. Mobilde menü hamburger butonuyla açılır (karartma katmanı ve ESC ile kapanır). İçerik sayfalarına dokunulmadı; yalnızca yerleşim çerçevesi (layouts/admin.blade.php) değişti. KDS/QR ekranları bu değişikliğin dışındadır.
- **Kullanıcılar modülü**: Yeni "Kullanıcılar" sayfası ile yönetim paneli kullanıcıları listelenir, eklenir, düzenlenir ve silinir. E-posta benzersizliği doğrulanır; şifreler en az 8 karakter + tekrar alanı ile bcrypt kullanılarak saklanır (düzenlemede şifre boş bırakılırsa değişmez). Kendi hesabınız ve sistemdeki son kullanıcı silinemez. Mevcut kullanıcılar tablosu kullanıldı; veritabanı değişikliği yoktur.
- **Oturum hata bildirimleri**: Yerleşim, oturum bazlı hata mesajlarını (ör. "Son kullanıcı silinemez.") artık kırmızı bilgi kutusunda gösterir.
- **Dağıtım öncesi tam yedek**: v1.0.101 canlıya alınmadan önce tam yedek alındı (veritabanı dump + public/images + .env kopyası; sunucuda /root/rocksqr-backup-2026-10-10 ve geliştirme klasöründe backups/ altında).

---

## v1.0.100 - 2026-10-09

### Bar SON şeridi mutfak stili + mesaj çapası + gelir merkezi adı + garson çağrısı otomatik kapanma
- **Bar SON şeridi mutfak (kitchen-pos) stiline çevrildi**: Tamamlananlar, dokunmatik kayan tek şerit + mini kart çipleri olarak gösterilir — SYM (mavi) / QR (turuncu) / İPT (kırmızı üstü çizikli) / ÇAĞRI çipleri, başlıkta Chk #, geri al butonu yok. BarController, Symphony'den tamamlananlara check_number taşır. Şeritteki çift kopya ve kaymama hatası giderildi: şerit her durumda akar, grup kopyaları maskeyi dolduracak kadar JS ile ölçeklenir, kayma yüzde tabanlıdır (yazı tipi yüklendikten sonra genişlik değişse de döngü kenarı kaymaz).
- **SON şeridi ürün içerikleri kayan yazı**: Çip içi pencere + çift metin döngüsüyle ürün adları ve notlar kısaltılmadan gösterilir; genişlik sınırı ve kırpma kaldırıldı.
- **SON şeridi çağrı çipleri kronolojik**: Çağrı çipleri sabit başta değil, onay (İlgilendi) / tamamlanma zamanına göre kronolojik sıraya girer — şerit doluyken yeni çip görünürlüğü garanti altına alındı; odalı çağrı çipleri "Gen" yerine ROOM numarası gösterir.
- **Bar mesaj çapası**: MESAJ/MARS satırları DtlSeq ile kendinden önceki en yakın ANA ürün satırına bağlanır; çapa ürün mutfaktan servis edilince (mavi karttan hazır düşme) mesaj da karttan düşer. MODIFIER satırları çapa olamaz (isim bazlı servis düşmesine girmediklerinden mesajı asılı bırakır). _seq/_par çıktıya sızmaz.
- **Bar hazırlanan check kartı**: Mutfak onaylı içerik (kitchen_pos_completions db_items) feed çözümlemesine önceliklidir — açık fiş çözümünden güvenilir, gerçek ürün listesi.
- **Mutfak kartlarında gelir merkezi adı**: Chk numarasının yanına gelir merkezi adı eklendi (KPOS + AKDS). Bilinen gelir merkezleri sabit haritadan isim olarak çözülür (Pool Bar, Rocks Patisserie, Cafe Rocks, Minibar, Room Service, Sıralı Et); bilinmeyen RVC'de sayısal olmayan ham değer yazılır, boşsa ek yazılmaz. CHECKSİZ kartlar dahil.
- **KDS tamamlama çipi sıkıştırıldı ve kalıcı içerik**: Çip 77→57px (line-height:0 + ticker flex-start); çip içeriği artık kalıcıdır — kitchen_pos_completions tablosuna items_list kolonu eklendi (ürün adları + açıklama notları; check tam liste / item kümülatif). ÜRÜN çipleri de kalıcı içeriği tercih eder; canlı feed yedeği notları da içerir.
- **MBB-20 klavye gezinmesi**: SELECT PREVIOUS/NEXT yalnız hazırlanmamış ürün satırlarında yapılır (hazırlanmış ve iade satırları atlanır) — gezinme, vurgu ve DONE 1-4 aynı satır evrenini kullandığı için ürün hazırlanınca seçim bir sonraki hazırlanmamış ürüne kayar (KPOS + AKDS).
- **Garson çağrısı otomatik kapanma işareti**: waiter_calls tablosuna auto_closed_at kolonu eklendi. Gece ekran temizliğinde (ScreenCleaner) yanıtsız kalan çağrılar otomatik kapanan olarak işaretlenir; Satış/Süre raporlarındaki çağrı istatistikleri otomatik kapananları dışarıda bırakır. Süre Raporu ibaresinde garson çağrısı sayısı gösterilir.
- **AKDS 'TBL -' düzeltmesi**: Ana SQL sorgusuna dokunulmadan, CheckPostingDB toplu sorgusuyla eksik masa numaraları doldurulur (batch anahtarlı kartlar dahil).
- **Carbon 3 geri alma süreleri**: diffInSeconds'in işaretli dönüşü nedeniyle çalışmayan geri alma süreleri KPOS + AKDS ekranlarında operand çevrimiyle düzeltildi.
- **Bar QR kartı**: "POS bekleniyor" satırındaki iptal (X) butonu büyütüldü — ikon 9→12px, genişlik px-1→px-1.5, leading-none ile satır yüksekliği değişmeden dokunmatik kullanım kolaylaştı.

---

## v1.0.99 - 2026-10-08

### Mutfak alanından garson çağrıları kaldırıldı (Ana Mutfak KDS)
- **Ana Mutfak (AKDS) ekranı artık bekleyen garson çağrısı kartı göstermez**: Mutfak alanında garson çağrılarına ihtiyaç olmadığından, Ana Mutfak ekranındaki kırmızı garson çağrı kartları ve ilgili altyapı tamamen kaldırıldı. Garson çağrıları bar ekranında görünmeye ve bar tarafından karşılanmaya devam eder; QR garson çağrısı akışı değişmedi.
- **Admin paneli**: Ekran sekmesindeki "Ana Mutfak Ekran Ayarlari" bölümünden "Garson Çagrilari: Görüntülenecek Adet" alanı kaldırıldı; `ana_waiter_call_display` ayar anahtarı artık okunmaz/yazılmaz (DB'deki eski kayıt zararsız şekilde durur).
- **/kitchen-ana/api**: Yanıttan `waiter_calls` anahtarı çıkarıldı; ekran poll ile otomatik güncellenir, kart kendiliğinden düşer.

---

## v1.0.98 - 2026-10-08

### QR garson çağrısında oda numarası + bar sayaç ve SON şeridi düzeltmeleri
- **QR garson çağrısı oda numarası sorar**: Garson çağrı modalına, sepettekiyle aynı oda numarası alanı eklendi (kayıtlı oda varsa "Oda X / Değiştir" görünümü, yoksa giriş alanı). Sepet ve garson modalı aynı oda seçimini paylaşır.
- **Zorunlu Alanlar ayarı (Sistem sekmesi)**: Oda numarasının siparişte ve garson çağrısında zorunlu olup olmadığı admin panelinden ayrı ayrı seçilebilir. Varsayılan: siparişte zorunlu, garson çağrısında isteğe bağlı. Zorunlu olmayan akışta oda numarası yine sorulur ama "(isteğe bağlı)" etiketiyle boş bırakılabilir. Sunucu boş değeri kabul eder; listede olmayan bir değer her durumda reddedilir. Ayar yalnızca Oda Numaraları sekmesinde liste tanımlıysa geçerlidir.
- **Bar garson çağrı sayacı**: Sayaç artık sunucudan gelen başlangıç süresi + tarayıcı çapasıyla ilerletilir; bar PC'sinin saat kaymasından bağımsız olarak doğru sayar (eski yöntemde saat kayması olan bilgisayarda sayaç kayıyordu). Aynı düzeltme QR sipariş kartı sayacına da uygulandı; Symphony kartları ve hazır sayacı değişmedi.
- **Bar SON şeridi tam içerik**: Son tamamlanan çiplerindeki 210px/170px genişlik sınırı ve kırpma kaldırıldı; ürün adları ve notlar kısaltılmadan tek sıra kayar şeritte gösterilir.

---

## v1.0.97 - 2026-10-08

### Geri alma olayları + ayar denetim kaydı (kds_events + settings_audit_logs)
- **kds_events tablosu (yeni)**: Mutfak ekranlarındaki geri alma işlemleri — Symphony kartı geri al (`undo_qr`), hesabı tamamlanmadan geri al (`uncomplete`), ürünü servisten düşür (`unserve`) — artık silinen kayıtların anlık görüntüsüyle kalıcı olarak kaydedilir: hangi grup anahtarı / check / masa / RVC, ne zaman ve hangi kayıtların silindiği. Ekran akışı etkilenmez; kayıt yazılamazsa sessizce geçilir.
- **settings_audit_logs tablosu (yeni)**: Admin panelinden yapılan ayar değişiklikleri kalıcı denetim kaydına yazılır (kim / ne zaman / hangi anahtar / eski → yeni değer). Oturumsuz bağlamlarda (cron, gece ekran temizliği) kayıt tutulmaz.

---

## v1.0.96 - 2026-10-08

### Mutfak batch kartlarında condiment/mesaj eşleştirme düzeltmesi + bar saati ve SON şeridi
- **Batch kart condiment/mesaj eşleştirme (KPOS + AKDS)**: Birleşik satırın sonradan giren unit'leri artık taban karta sızmaz; batch evreni görünür tüm unit'lerin ilk görülme zamanlarından kurulur ve her unit yalnız kendi batch kartında görünür. Mesajlar kendi unit zamanına göre ait olduğu batch kartına atanır (zaman yoksa mesajın POS konumundan geriye doğru eşleştirme). Böylece 1. kartın condiment/mesajı 2. (EK) kartta görünmez.
- **Bar saati üst-ortaya taşındı**: Saat + tarih, GELEN SİPARİŞLER ve HAZIRLANAN SİPARİŞLER başlıkları arasında ortalanmış olarak gösterilir.
- **Bar alt SON şeridi**: "Adisyon #..." yer tutucusu yerine her tamamlanan checkin gerçek ürün içerikleri (1x Ürün · 1x Ürün) tek sıra kayar şeritte gösterilir.

---

## v1.0.95 - 2026-10-08

### Mavi karttan hazır ürün düşme + ek siparişte yeni kart (KPOS + AKDS)
- **Mavi kart (bar GELEN kolonu)**: Mutfak (KPOS) bir ürünü "Hazır" işaretlediğinde o ürün adediyle birlikte bar ekranındaki mavi Symphony kartından anında düşer; hesabın tüm ürünleri hazırlanınca mavi kart bar ekranından tamamen kalkar (mesaj satırları da kartla birlikte düşer). Kısmi onayda satır adedi hazır işaretlenen miktar kadar azalır. Düşme yalnız KPOS onaylarıyla çalışır; Ana Mutfak (AKDS) onayları bar mavi kartını etkilemez. Yalnız mesaj içeren hesaplar etkilenmez.
- **Aynı adisyona ek sipariş → yeni kart**: Sonradan giren ürünler mevcut kartın içine yazılmaz; her iki mutfak ekranında ayrı bir YENİ kart olarak en üstte açılır — aynı check numarası / masa / garson bilgisi, yalnız ek ürünler, başlıkta küçük turuncu "EK" rozeti. Eski "turuncu kenarlık + yanıp sönen EK SİPARİŞ" görünümü kaldırıldı.
- **Sıralama**: En yeni sipariş en üstte; ek kart açıldığında eski kart kendi sırasında kalır.
- **Bar yeşil kart sayacı**: Hazır adet / toplam adet sayacı, kısmi onaylarda da doğru toplamı gösterir (hazır toplamı + feed kalanı).

---

## v1.0.94 - 2026-10-08

### İade ürünün açıklama ve mesaj satırları da iade görünümü alır (KPOS + AKDS)
- **Ürün açıklaması (note)**: iade/iptal edilmiş ürünün açıklama satırı artık kırmızı + üstü çizili + yanıp sönen arka planla gösterilir (önceden sarı görünüyordu).
- **MESAJ satırları**: POS'ta iade edilen ürünün altına serpiştirilmiş mesaj satırları (ürünle konum eşleşmesi üzerinden) üst ürün iade ise aynı iade görünümünü alır — kırmızı, üstü çizili, yanıp söner.
- **Alt satırlar (condiment/combo)**: üst ürün iade edildiğinde alt satırlar da iade görünümünü devralır; "İade" rozeti yalnızca kendi iade bayrağı olan satırda görünür.
- Her iki mutfak ekranına uygulandı: Mutfak (KPOS) + Ana Mutfak (AKDS). Bar (BDS) ekranı kapsam dışıdır.

---

## v1.0.93 - 2026-10-08

### RECALL alanı kaldırıldı + MSSQL otomatik fiyat senkronu + Bar HAZIRLANAN filtreleri
- **RECALL alanı kaldırıldı**: "Diğer Ayarlar" sekmesindeki "RECALL Geri Çağırma Süresi (saniye)" alanı silindi. KDS geri alma penceresi artık ekran ayarlarındaki geri alma süresine bağlı: Mutfak (KPOS) → `ready_undo_seconds`, Ana Mutfak (AKDS) → `ana_ready_undo_seconds`.
- **MSSQL Otomatik Fiyat Senkronu (Sistem sekmesi)**: çalışma modu Kapalı / Her N dakikada bir (5-1440) / Her gün belirlenen saatte. Yeni `sync:mssql-prices` komutu dakikalık zamanlayıcıya eklendi (bootstrap/app.php withSchedule); sunucu cron'una `schedule:run` (dakikada bir) görevi kuruldu. Yalnızca ürün kodu (mssql_id) eşleşen yerel ürünlerin **yalnız fiyatı** güncellenir; yeni ürün oluşturulmaz, ürün adları değişmez. Son çalışma zamanı + istatistik (toplam / güncellenen / aynı / eşleşmeyen) Sistem sekmesinde gösterilir; hata durumunda istatistikte hata metni görünür ve bir dakika sonra yeniden denenir.
- **Bar HAZIRLANAN filtreleri (mutfaktan bağımsız ayrı set)**: Bar Ekran Ayarları'na RVC bazlı "Hariç Tutulacak Family Grupları" (`bar_fg_hide_44`) ve "Açık Sipariş Kodları" (`bar_open_food_44` / `bar_open_drink_44` / `bar_open_other_44`; varsayılan 1998001 / 2998001,3998001 / 4998001) alanları eklendi. Seçilen family grupları bar HAZIRLANAN kartlarından gizlenir; açık sipariş satırlarında satır adı + POS fişinden çözülen içerik gösterilir (mutfak paritesi). GELEN (sol) kolonu ve bar yerleşimi değişmedi.
- benioku.txt güncellendi: sunucu cron notu, yeni ayar anahtarları, AKDS ekran ayarları.

---

## v1.0.92 - 2026-10-08

### Admin panel elden geçirme + Satış Raporu
- **Dashboard yeniden düzenlendi**: 8 istatistik kartı — Bugün Sipariş, Günlük Satış, Aylık Satış, Bekleyen Çağrılar / Yeni Siparişler, Bugün Tamamlanan, Bugün Ort. Hazırlık, Toplam Ürün. Toplam sipariş/tutar kartları kaldırıldı; Günlük ve Aylık Satış artık iptal edilen siparişleri hariç tutar. "Bugün Tamamlanan" = QR (kitchen_ready_at) + Symphony (kitchen_pos_completions) toplamı; "Bugün Ort. Hazırlık" = kitchen_item_logs ortalama hazırlık süresi.
- **Hızlı Erişim 8 buton**: Bar, Symphony Mutfak, Ana Mutfak, Raporlar, Kategoriler, Ürünler, Masa QR, Ayarlar — "Yeni Kategori/Ürün Ekle" kısayolları kaldırıldı (her buton farklı hedefe gider).
- **Dashboard listeleri son 30 günle sınırlandı**: "En Çok Satılanlar" ve "En Çok Çağrılan Masalar" yalnızca son 30 günü tarar (iptal siparişler hariç) — sorgu yükü azaldı.
- **Yeni: Satış Raporu sayfası** (`/admin/reports/sales`): dönem özeti (sipariş, ciro, ort. sepet, iptal adedi), günlük ciro grafiği, saatlik yoğunluk (00–23), en çok satılan 30 ürün (adet + ciro, sipariş anı fiyatıyla), konum (masa/oda) bazlı ciro, garson çağrıları (toplam, ort. karşılama süresi, en çok çağrılan 10 masa). Rapor sayfaları artık 3 sekmeli: Mutfak Hazırlık · Süre Raporu · Satış Raporu; varsayılan dönem son 30 gün.
- **Sipariş içeriği görünürlüğü**: Mutfak Hazırlık raporunda "En Yavaş Tamamlanan 50 Hesap" ve "Bugün En Uzun Süren" tablolarına **İçerik** kolonu eklendi (kitchen_item_logs üzerinden ürün dökümü) — gecikmenin hangi siparişte/hangi ürünlerle yaşandığı görülebilir.
- **Süre Raporu'na "En Geç Hazırlanan Ürünler" paneli eklendi**: ortalama hazırlık süresine göre en yavaş 20 ürün + en uzun kaydın hesap/masa bilgisi.

---

## v1.0.91 - 2026-10-08

### Check kapanma ayarı Sistem sekmesine taşındı + Bar (BDS) desteği
- "Check kapanınca sipariş ekrandan silinsin" ayarı Ekran sekmesinden **Sistem** sekmesine taşındı; artık her ekran için ayrı tick: **Bar Ekranı (BDS)** → `bar_check_close_wait`, **Mutfak KPOS (Symphony)** → `kitchen_check_close_wait`, **Ana Mutfak (AKDS)** → `ana_check_close_wait`. Yeni kaydetme dalı `_checkclose_only`.
- Ekran sekmesindeki kitchen/ana formlarından eski checkbox'lar kaldırıldı; bu formlardaki `Setting::set('...check_close_wait')` satırları da silindi (checkbox'lar formdan çıkınca her Ekran kaydında '0' yazılmasın diye).
- **Bar (BDS) bekleme modu (yeni)**: işaretsizken POS'ta kapanan check bar ekranından düşmez; kart HAZIRLANAN şeridinde "KAPANDI" rozetiyle bekler. Otomatik "servis edildi" işaretleme (SON'a geçiş) yalnız sil modunda çalışır. Varsayılan '1' mevcut bar davranışını birebir korur.
- `barApiSymphony` payload'ına `status` alanı eklendi; bar kartı imzasına (`orderSig`) status dahil edildi — check kapanınca kart rozetle yeniden render edilir.
- benioku.txt: "Check Kapanma Davranışı" bloğu 3 anahtar (bar/kitchen/ana) olarak güncellendi.

---

## v1.0.90 - 2026-10-07

### Check kapanma ters koşul düzeltmesi (repoya alındı)
- `SymphonyKdsController::kdsPayload`'da check-kapanma ayarı ters çalışıyordu: "Kapanan hesap ekrandan silinsin" işaretliyken kapanan hesap ekranda KALIYOR, işaretsizken SİLİNİYORDU. Değişken `$checkCloseWait` → `$deleteOnCheckClose` olarak yeniden adlandırılıp koşul `if ($status === 'C' && $deleteOnCheckClose) continue;` olarak düzeltildi. (Canlıya önceki deploy ile uygulanmıştı; bu sürümle repoya alındı.)

### Bar: hazır şeridi limiti admin ayarı oldu
- `bar_ready_display` ayarı BarController'da okunuyordu ama panelde alanı yoktu (hep 12 kullanılıyordu). Ayarlar → Ekran → Bar Ekran Ayarları'na **"Hazirlanan (Servise Gotur) Sayisi"** alanı eklendi (1-100, varsayılan 12). Bar HAZIRLANAN (servise götür) şeridindeki QR ve Symphony kartlarının adedini panelden sınırlar.

### Denetim temizliği (A-Z kod denetimi bulguları)
- Ölü controller'lar silindi: `Admin/OrderController`, `Admin/CategoryController`, `Admin/ProductController` — hiçbir route kullanmıyordu; gerçek route'lar kök controller'ları kullanıyor.
- Kullanılmayan view'lar silindi: `welcome.blade.php`, `dashboard.blade.php`.
- `SyncController`: hiç çağrılmayan `quoteSqlServerIdentifier`/`quoteSqlServerTable` metotları ve kullanılmayan `$table` okuması kaldırıldı.
- Hayalet ayarlar temizlendi: `order_ready_display` + `order_profit_display` (formda vardı, hiçbir ekranda okunmuyordu), `mssql_table` (form alanı yoktu, her kayıtta boş yazılıyordu), `screen_pin_kitchen` (kitchen ekranı kaldırılmıştı; PIN döngüleri bar/kpos/ana'ya indirildi).
- `SymphonyKdsController`: hiçbir yerde set edilmeyen `dtl_seq` fallback'i kaldırıldı (davranış değişmedi).
- `AdminCategoryController`: `(int) $request->sort_order ?? 0` operatör önceliği düzeltildi → `(int) ($request->sort_order ?? 0)`.
- Bar: bekleyen garson çağrısı sorgusuna güvenlik limiti (50) eklendi (yanıtlananlar zaten 8 ile sınırlıydı).
- `benioku.txt` çalışma senaryosu güncellendi (Bar/Kitchen ayar anahtarları + check-kapanma davranışı).

---


## v1.0.89 - 2026-10-05

### Yeni: Ekran PIN kilidi (BDS / KDS / Kitchen POS / AKDS)
- Admin → Ayarlar'a **Ekran PIN** sekmesi eklendi: her ekran için ayrı "PIN istensin" anahtarı + 4-6 haneli PIN tanımı (hash olarak saklanır, panelde görünmez).
- `EnsureScreenPin` middleware: yalnızca ekran açılış GET route'larını (`/bar`, `/kitchen`, `/kitchen-pos`, `/kitchen-ana`) korur; API/SSE endpoint'leri ve admin paneli etkilenmez.
- PIN doğrulama ekranı (`/screen-pin/{screen}`): dokunmatik numerik tuş takımı, fiziksel klavye desteği, hatalı PIN'de titreme animasyonu.
- Doğru PIN sonrası 30 gün geçerli APP_KEY şifreli çerez verilir — kiosk tarayıcı her açılışta PIN sormaz.
- PIN POST'u `throttle:5,1` ile korunur (dakikada 5 deneme); CSRF korumalı.
- Kilitlenme koruması: PIN atanmamış bir ekran için "PIN istensin" açılamaz — önce PIN atanmalıdır.

---

## Ara Dönem Özeti - 2026-05 → 2026-10 (v1.0.88 ile v1.0.89 arası)

Bu dönemde sürüm numaraları yazılmadığı için aşağıda dönem boyunca v1.0.89–v1.0.100 girdilerinde hâlâ geçmeyen büyük işler özetlenir:

- **Bar ekranı görsel yeniden tasarımı**: tek üst şerit, GELEN / HAZIRLANAN iki kolon, masa bazlı birleşik kartlar, filigran ve saat üst-ortada; TBL (masa) / RM (oda) etiketli kart kimlikleri.
- **Symphony KDS/BDS gerçek zamanlı akış (v1.4 sorgu)**: adisyon POS'ta açıldığı an ekranda görünür, ödeme/kapanış beklemez; KDS ve BDS aynı ortak sorguyu kullanır, RVC filtresi yazılımda uygulanır.
- **Süre Raporu + kitchen_item_logs**: ürün bazlı hazır/onay kayıtları ve aşama süreleri; en geç hazırlanan ürünler, en yavaş hesaplar, 30 günlük trend.
- **Gece ekran temizliği**: ekran başına temizlik saati (screen_clear_time_*), ScreenCleaner komutu dakikalık zamanlayıcıyla kontrol eder.
- **Ekran arka plan logoları**: her ekran için ayrı arka plan görseli (Ayarlar > Ekran üzerinden yönetilir).
- **Saat kaynağı seçimi + NTP senkronu**: sayaçlar sunucu saati / DB saati / tarayıcı saati seçeneklerinden beslenir; bar PC saat kaymalarına dayanıklı.
- **MBB-20 mutfak klavyesi**: SELECT PREVIOUS/NEXT gezinmesi, DONE 1-4 ürün tamamlama kısayolları, vurgu yönetimi (KPOS + AKDS).
- **RVC bazlı family/açık sipariş filtreleri**: mutfak (kitchen_fg_hide_{rvc} / kitchen_open_*_{rvc}) ve bar (bar_fg_hide_44 / bar_open_*_44) için ayrı setler.
- **kitchen_pos_completions.rvc_id + çapraz ekran sızıntısı kapatıldı**: her onay kendi gelir merkeziyle kaydedilir, SON şeridi sorguları ekran kapsamıyla filtrelenir.
- **Çoklu fiş içerik çözümü**: POS_JOURNAL_LOG fişlerinden açık sipariş satırı içerikleri çözülür; '2x ÜRÜN' adet biçimi; MARS kurs ayırıcıları ekranda gizlenir.
- **kitchen_item_times kalıcı sayaç**: kartın ekranda ilk görülme zamanı kalıcı tabloda tutulur; tamamlanan kart geri gelse bile sayaç sıfırlanmaz.

---

## v1.0.88 - 2026-05-04

### Admin: Ürünler ve Kategoriler sayfa iyileştirmeleri
- **Ürünler** sayfasına filtre bar eklendi: metin arama (ad / product code), kategori dropdown (otomatik filtrele), sayfa başına seçici (20/50/100/200/500/Tümü — otomatik filtrele).
- Tüm kolon başlıkları tıklanabilir sıralama desteği kazandı (N, #, Product Code, Ürün Adı, Kategori, Fiyat, Aktif/Pasif).
- Global satır numarası sütunu (N) ve kategori içi sıra numarası sütunu (#, amber badge) eklendi.
- **Sıralama Düzenle** modalı eklendi: SortableJS sürükle-bırak, kategoriye göre gruplu, pozisyon numaraları anlık güncelleniyor.
- **Toplu Seç / Sil**: checkbox, seçim çubuğu, onay modalı — `sync/bulk-delete` endpoint kullanıyor.
- **Toplu Güncelle**: satır içi düzenleme (ad, kategori, fiyat), önizleme modalı, `sync/bulk-update` endpoint.
- **Kategoriler** sayfasına filtre bar eklendi: metin arama, sayfa başına seçici, tıklanabilir kolon başlıkları (Ad, Ürün Sayısı, Sıra, Durum), global satır numarası.
- Her iki sayfada da seçici elemanlar (`per_page`, `category`) değişince form otomatik gönderiliyor.

### Symphony Import düzeltmesi
- `Product::withTrashed()` eksikliği: soft-delete ürünü yeniden içe aktarırken `UNIQUE` kısıtlaması patlıyordu — restore akışı eklendi.
- `Accept: application/json` header eksikliği: sunucu hatalarında JSON yerine HTML geliyordu, `.json()` parse hatası modal yerine konsola düşüyordu — modal içi hata çubuğu eklendi.
- `showSymphonyError()` / `hideSymphonyError()` fonksiyonları eklendi.

### Soft-delete + UNIQUE kısıtlaması düzeltmesi
- `AdminCategoryController::store()`: `Rule::unique()->whereNull('deleted_at')` ile doğrulama; aynı isimli soft-deleted kategori varsa otomatik restore + güncelle.
- `AdminCategoryController::update()`: `Rule::unique()->ignore()->whereNull('deleted_at')` + slug üretiminde `withTrashed()` kontrolü.
- `AdminProductController::store/update()`: `mssql_id` için `Rule::unique()->whereNull('deleted_at')` (store) ve `->ignore()` (update).
- `SyncController::symphonyImport()`: kategori oluşturmada `withTrashed()` + `try/catch QueryException 1062` fallback.

### Müşteri QR menü yeniden tasarımı
- Mobil öncelikli tam sayfa yeniden yazım: yapışkan kategori sekmeleri, ürün kartları, kompakt header.
- `lang="tr"`, `maximum-scale=1.0`, alt navigation bar, kaydırma gizleme, placeholder görsel desteği.

### Diğer
- `SettingsController::resolvePasswordKey()`: `akds` şifre anahtarı eklendi.
- MSSQL ayarlar sayfası AKDS sekmesi renk paleti (teal) eklendi.
- `routes/web.php`: `products/for-reorder`, `products/reorder`, `sync/bulk-delete` route'ları eklendi.

---

## v1.0.87 - 2026-05-02

### Refactor: KitchenController 3 ayrı controller + MssqlService + MapsOrders trait
- **KitchenController** yalnızca QR mutfak ekranını yönetiyor (kitchen, kitchenApiOrders, kitchenSse, kitchenUpdateStatus, kitchenAckCancel) — 1382 satırdan 130 satıra indi.
- **BarController** (yeni): bar ekranına ait tüm metodlar (bar, barApiOrders, barApiSymphony, barUpdateStatus, cancelOrder, attendWaiterCall, barSymphonyDelivered).
- **SymphonyKdsController** (yeni): Symphony KDS ve AKDS metodları (kitchenPos\*, kitchenAna\*) — 9 metod.
- **MssqlService** (yeni): PDO bağlantı, SQL temizleme ve case-insensitive alan okuma — 4 yerde tekrar eden ~30 satır kod tek sınıfa çıkarıldı.
- **MapsOrders trait** (yeni): `mapOrder()` KitchenController ve BarController arasında ortaklandı.
- Exception catch bloklarına `Log::error()` eklendi; kullanıcıya artık generic hata mesajı dönüyor (DB şema bilgisi ifşa edilmiyor).
- `artisan route:list` ile 21 route doğrulandı.

---

## v1.0.86 - 2026-05-02

### Bar + Kitchen: kompakt header yenileme
- **Bar header**: `h-screen flex-col overflow:hidden` layout; SYM / QR / hazır / çağrı adet sayaçları; canlı saat + tarih.
- **Kitchen header**: Kompakt tek satır; saat + tarih; tam ekran butonu eklendi; `auto-fill minmax(260px)` grid.
- **Bar combined-grid**: Garson çağrıları ve hazır siparişler tek `combined-grid`'e alındı.
- `kitchen_cards_per_page` / `bar_cards_per_page` ayarları kaldırıldı.

---

## v1.0.85 - 2026-05-02

### Ayarlar: Sayaç renk eşikleri
- Admin → Ayarlar'a **Sayaç Renk Eşikleri** bölümü eklendi: QR sipariş, SYM sipariş, hazır sipariş ve garson çağrısı sayaçları için bağımsız sarı / turuncu / kırmızı dakika eşikleri.
- 12 adet yeni ayar anahtarı: `timer_qr_yellow/orange/red`, `timer_sym_*`, `timer_ready_*`, `timer_waiter_*`.
- SettingsController: `_timer_only` form dalı, validation + kayıt.

---

## v1.0.84 - 2026-05-02

### CSRF muafiyeti — display ekranlar
- Bar ve Kitchen display endpoint'leri `bootstrap/app.php`'de CSRF doğrulama dışına alındı.
- Uzun süre açık kalan ekranlarda token expire sorununu çözüyor.

---

## v1.0.83 - 2026-05-02

### Sipariş İptal
- **BarController::cancelOrder()**: Bar ekranından yalnızca `bar_status=new` olan QR siparişler iptal edilebilir.
- **KitchenController::kitchenAckCancel()**: Kitchen ekranı iptal bildirimini onaylar.
- Kitchen ekranında **"İptal Edilenler (son 5 dk)"** bölümü eklendi (kırmızı border, ban ikonu).
- `orders.status` enum'una `cancelled` değeri eklendi (migration).
- Yeni route'lar: `PATCH /bar/orders/{order}/cancel`, `PATCH /kitchen/orders/{order}/ack-cancel`.

---

## v1.0.82 - 2026-05-02

### Subdomain alias ayarları
- **SubdomainRedirect middleware**: `bar.*`, `kitchen.*`, `ana.*` subdomainleri admin'den yapılandırılabilir alias'lara yönlendiriyor.
- **Admin → Ayarlar → Subdomain** sekmesi: bar / kitchen / ana mutfak için özel subdomain alias girişi.
- Settings sekmeli yapıya kavuştu: **Genel / Ekran / Subdomain** sekmeleri.

---

## v1.0.81 - 2026-04-27

### Bar KDS: MUTFAKTA badge kaldırıldı
- QR siparişler bar'dan mutfağa gönderilmediği için MUTFAKTA badge'i gereksizdi; sadece YENİ badge'i gösteriliyor.

---

## v1.0.80 - 2026-04-27

### Bar KDS: POS BEKLENİYOR badge kaldırıldı
- Durum alttaki butondan takip ediliyor; kart başlığındaki POS BEKLENİYOR badge'i kaldırıldı.

---

## v1.0.79 - 2026-04-27

### Bar KDS: SYM kartlarında POS ibaresi kaldırıldı
- SYM rozetinde ayrıca "POS'ta" badge'i gösterilmiyordu; temizlendi.

---

## v1.0.78 - 2026-04-27

### Bar: escapeHtml tanımsız hatası düzeltildi
- `escapeHtml()` bar.blade'de tanımlı olmadığı için bazı kartlar görünmüyordu. `data-order-time` attribute'unda inline `.replace(/['"<>&]/g, '')` ile değiştirildi.

---

## v1.0.77 - 2026-04-27

### Bar: Symphony siparişleri kaybolma sorunu düzeltildi
- `lastSymOrders` cache: API geçici hata verince son bilinen siparişler korunur.
- Stabil render key: yalnızca gerçek içerik değişince `innerHTML` güncellenir, `seconds_ago` render tetiklemez.
- Client-side elapsed ticker: `data-order-time` ile 1 sn'de bir zaman sayacı (polling beklemez).

---

## v1.0.76 - 2026-04-27

### Bar: srcBadge ternary syntax hatası düzeltildi
- `renderCompletedOrders` içindeki `srcBadge` ternary sözdizimi hatası blank screen yapıyordu; düzeltildi.

---

## v1.0.75 - 2026-04-27

### "SYMPHONY" etiketi → "SYM" olarak kısaltıldı
- Bar ve kitchen-pos ekranlarında SYM rozeti; daha kompakt kart görünümü.

---

## v1.0.74 - 2026-04-27

### Bar: btn syntax hatası düzeltildi
- `renderOrders` içinde `btn = \`` syntax hatası blank screen yapıyordu; düzeltildi.

---

## v1.0.73 - 2026-04-27

### Bar: tüm grid'lere +1 sütun
- `waiter-calls-list`, `ready-orders-list`, `completed-grid` aynı breakpoint şemasına getirildi.

---

## v1.0.72 - 2026-04-27

### KDS + Bar: kart genişliği daraltıldı, Onayla butonu küçültüldü
- Kartlar daha dar: her breakpoint'e +1 sütun eklendi.
- "Onayla → Servis" butonu `py-0.5 text-[11px]` ile küçültüldü.

---

## v1.0.71 - 2026-04-27

### KDS + Bar: sayfa başına kart sayısı + sayfalama sistemi
- Admin ayarı: "sayfa başına kart sayısı" (`kitchen_cards_per_page` / `bar_cards_per_page`, varsayılan 8).
- Kartlar sayfaya sığmazsa sol altta sabit sayfalama kontrolü belirir (altın renk aktif sayfa).

---

## v1.0.70 - 2026-04-27

### KDS + Bar: kart sütun sayısı admin ayarı
- `kitchen_card_columns` / `bar_card_columns` admin ayarı eklendi.

---

## v1.0.69 - 2026-04-27

### KDS + Bar: kart 3 satırlı başlık formatı
- Kart başlığı: Masa + SYM rozeti / Chk + sipariş zamanı / garson adı.
- `WaiterName` controller'a eklendi; bar ve mutfak aynı formata getirildi.

---

## v1.0.68 - 2026-04-27

### Kitchen-POS: Onayla butonu daha kompakt

---

## v1.0.67 - 2026-04-27

### Kitchen-POS: RVC (POOL BAR) etiketi kart başlığından kaldırıldı

---

## v1.0.66 - 2026-04-27

### Kitchen-POS: kompakt kart düzeni
- `2xl:grid-cols-5`, padding azaltıldı.

---

## v1.0.65 - 2026-04-27

### Kitchen-POS: QR siparişler ekrandan kaldırıldı
- `/kitchen-pos` yalnızca Symphony POS siparişlerini gösterir.

---

## v1.0.64 - 2026-04-27

### Kitchen-POS: Chk label, büyük saat, tamamlanan sayacı
- `Hesap #` → `Chk #`; saat `text-2xl font-bold`; `fetched-at` gizlendi; header'a `completed_today` sayacı eklendi.

---

## v1.0.63 - 2026-04-27

### Bar: items-start hizalaması, ISO 8601 saat parse, gereksiz span kaldırıldı

---

## v1.0.62 - 2026-04-27

### KDS: orders-grid items-start, kartlar kendi yüksekliğini alır

---

## v1.0.61 - 2026-04-27

### KDS: condiment bir önceki URUN altına girer, combo kendi zincirini kurar

---

## v1.0.60 - 2026-04-27

### KDS: combo/condiment parent zinciri düzeltmesi

---

## v1.0.59 - 2026-04-27

### KDS: condiment sub_items — combo ile aynı görsel (girintili, aynı ikon/renk)

---

## v1.0.58 - 2026-04-27

### Bar KDS: gereksiz badge'lerin temizlenmesi
- SYM kartlarında "POS" durum badge'i kaldırıldı (zaten SYM badge'i mevcut).
- "POS BEKLENİYOR" badge'i kart başlığından kaldırıldı; durum alttaki buton üzerinden takip ediliyor.
- "MUTFAKTA" badge'i kaldırıldı; yalnızca "YENİ" badge'i gösteriliyor (QR siparişler bar'dan mutfağa gönderilmiyor).

---

## v1.0.57 - 2026-04-27

### Bar KDS: blank screen, Symphony kaybolma ve escapeHtml düzeltmeleri
- `renderOrders` içinde `btn = \`` ve `renderCompletedOrders` içinde `srcBadge` ternary syntax hataları giderildi (her ikisi de blank screen yapıyordu).
- Symphony siparişleri arada kaybolma sorunu üç katmanlı çözümle giderildi: `lastSymOrders` cache (API geçici hata verince son bilinen siparişler korunur), stabil render key (yalnızca gerçek içerik değişince `innerHTML` güncellenir, `seconds_ago` render tetiklemez), client-side elapsed ticker (`data-order-time` ile 1 saniyede bir zaman sayacı — polling beklemiyor).
- `escapeHtml()` bar.blade'de tanımsız olduğu için kart görünmüyordu; `data-order-time` attribute'unda inline `.replace(/['"<>&]/g, '')` ile değiştirildi.
- "SYMPHONY" etiketleri bar ve kitchen-pos ekranlarında "SYM" olarak kısaltıldı.

---

## v1.0.56 - 2026-04-27

### KDS + Bar: sayfalama sistemi ve kompakt grid
- Admin ayarı olarak "sayfa başına kart sayısı" eklendi (`kitchen_cards_per_page` / `bar_cards_per_page`, varsayılan 8, max 50). `SettingsController` ve `settings.blade` güncellendi.
- Kartlar sayfaya sığmazsa sol altta sabit sayfalama kontrolü belirir (`< [1] [2] ... >`); aktif sayfa altın renkte. Tamamlananlar her sayfada görünür.
- Her breakpoint'e +1 sütun eklendi (kartlar daha dar): `sm:2 → md:3 → lg:4 → xl:5 → 2xl:6`.
- "Onayla → Servis" butonu `py-0.5 text-[11px]` ile küçültüldü.
- Bar ekranındaki tüm grid'ler (`waiter-calls-list`, `ready-orders-list`, `completed-grid`) aynı breakpoint şemasına getirildi.

---

## v1.0.55 - 2026-04-27

### Kitchen KDS + Bar: UI iyileştirmeleri
- Bar ekranına `items-start` hizalaması eklendi; ISO 8601 saat parse düzeltildi; gereksiz `created_at` span'ı kaldırıldı.
- `Hesap #` etiketi → `Chk #`; saat büyütüldü (`text-2xl font-bold`); `fetched-at` gizlendi; header'a `completed_today` sayacı eklendi.
- QR menü siparişleri Kitchen POS ekranından kaldırıldı (yalnızca Symphony POS siparişleri gösterilir).
- Kartlar daha kompakt hale getirildi: `gap-2`, azaltılmış padding. RVC (POOL BAR) etiketi kart başlığından kaldırıldı.
- Kart başlığı 3 satırlı formata geçirildi: Masa + SYM rozeti / Chk + sipariş zamanı / garson adı. `WaiterName` controller'a eklendi; bar ve mutfak aynı formata getirildi.

---

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
