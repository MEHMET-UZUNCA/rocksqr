# Rocksqr — Tanıtım Videosu Senaryosu

**Hedef:** Restaurant/otel yöneticileri, KDS/BDS sistemine bakan teknik ekip
**Süre:** ~12–18 dakika
**Format:** Ekran kaydı + sesli anlatım, gerçek sistem üzerinde canlı demo

---

## BÖLÜM 1 — Giriş ve Genel Bakış (1–2 dk)

**Anlatım:**
> "Merhaba. Bu videoda Rocksqr sistemini tanıtacağız. Rocksqr; QR menü, mutfak ekranı (KDS) ve bar ekranı (BDS) içeren bir restoran yönetim platformu. Hem kendi QR sipariş akışını hem de varsa Symphony POS sisteminizi destekliyor."

**Ekranda göster:**
- Admin dashboard ana sayfası
- Kart sayaçlarını göster (siparişler, ürünler, garson çağrıları)
- Sağ üstteki hızlı erişim linkleri (KDS, BDS, AKDS)

---

## BÖLÜM 2 — QR Menü: Müşteri Deneyimi (2–3 dk)

**Anlatım:**
> "Müşteri masadaki QR kodu tarar, listeye doğrudan girer."

**Adımlar:**
1. `/table/5` gibi bir bağlantıyı tara ya da tarayıcıdan aç
2. Mobil ekranda kategori sekmeleri göster — yapışkan üst bar
3. Bir ürün kartına tıkla → sepete ekle
4. Sepeti aç → sipariş ver
5. Sipariş onay ekranını göster (yeşil animasyonlu tik)
6. Ayrıca **Garson Çağır** butonunu göster (sepet kapalıyken de kullanılabiliyor)

**Vurgu:**
- "Mobil öncelikli tasarım, hiçbir uygulama indirimi gerekmez."

---

## BÖLÜM 3 — KDS: QR Mutfak Ekranı (2–3 dk)

**Anlatım:**
> "Sipariş geldikten sonra mutfak ekranına düşer. `/kitchen` adresini mutfaktaki ekrana tam ekran açabilirsiniz."

**Adımlar:**
1. `/kitchen` ekranını aç → tam ekran butonu bas
2. Gelen siparişi göster — masa no, ürünler, geçen süre (renk kodları: yeşil/sarı/kırmızı)
3. **"Hazırlanıyor"** butonuna bas → kart durumu değişti
4. **"Hazır"** yap → kart yeşile döner
5. **"Tamamlandı"** ile karta süreli Geri Al penceresini göster (30 sn)
6. Alt barda **"İptal Edilenler (son 5 dk)"** bölümünü göster

**Vurgu:**
- "SSE (Server-Sent Events) ile anlık güncelleme — polling yok, sayfa yenilemek gerekmez."

---

## BÖLÜM 4 — KDS: Symphony POS Ekranı (3–4 dk)

**Anlatım:**
> "Eğer restoranda Symphony POS kullanılıyorsa, POS'tan gelen siparişleri doğrudan `/kitchen-pos` ekranında görebilirsiniz."

**Adımlar:**
1. `/kitchen-pos` ekranını aç
2. Header'daki sayaçları göster: Aktif hesap / Checksiz mesaj / Bugün tamamlanan
3. Bir **Checksiz Mesaj** kartını göster (sarı, üstte flash bölüm)
4. Normal bir **Masa / Hesap kartını** göster:
   - Kart başlığı: Masa + SYM rozeti / Chk No + sipariş saati / garson adı
   - Ürünler: condiment girintili, combo zinciri
5. **"Onayla → Servis"** butonuna bas → kart tamamlananlar bölümüne iner
6. **"Geri Al"** penceresini göster
7. **EK SİPARİŞ** senaryosunu göster: Tamamlanmış hesaba Symphony'den yeni ürün eklendi → kart turuncu border ile tekrar açıldı, "EK SİPARİŞ" badge yanıp söner

**Vurgu:**
- "Mutfak tamamladı ama garson ek sipariş aldı — sistem eski ürünleri tekrar çıkarmaz, sadece yeni eklenenler görünür."

---

## BÖLÜM 5 — AKDS: Ana Mutfak Ekranı (1–2 dk)

**Anlatım:**
> "Büyük tesislerde merkezi bir mutfak ekranı gerekebilir. `/kitchen-ana` ekranı ayrı bir MSSQL bağlantısıyla çalışır ve salt-görüntüleme modundadır — aksiyon butonu yoktur."

**Adımlar:**
1. `/kitchen-ana` ekranını aç
2. Teal temalı grid kartları göster
3. Süre renk eşiklerini göster: <5 dk teal → 5–10 dk teal-700 → 10–15 dk sarı → 15 dk+ kırmızı
4. **RVC filtresi** nasıl çalışır kısaca açıkla (admin ayarlarından yapılandırılıyor)

---

## BÖLÜM 6 — BDS: Bar Ekranı (2–3 dk)

**Anlatım:**
> "Bar ekranı, hem QR siparişleri hem de Symphony'den gelen siparişleri takip eder. Bar personeli buradan servis akışını yönetir."

**Adımlar:**
1. `/bar` ekranını aç → header sayaçlarını göster (SYM / QR / Hazır / Garson çağrısı)
2. **YENİ QR siparişi** kartını göster:
   - "POS bekleniyor..." durumu (Symphony'de henüz adisyon açılmamış)
   - Garson POS'a girdikten sonra kart altın renge döner → "Onayla (POS'ta var)" aktifleşir
3. **Onayla** bas → kart "MUTFAKTA" rozetiyle grid'de kalır
4. Mutfak hazır deyince yeşil **"SİPARİŞ HAZIR — SERVİSE GÖTÜR"** şeridine geçişi göster
5. **"Servis Edildi"** bas → Tamamlananlar bölümüne düşer
6. **Garson Çağrısı** kartını göster → "İlgilendi" butonuna bas

**Vurgu:**
- "QR ve Symphony siparişleri tek ekranda birleşik takip; ayrı panel yok."

---

## BÖLÜM 7 — Admin: Ayarlar ve Yapılandırma (2–3 dk)

**Anlatım:**
> "Tüm bu ekranları admin panelinden yapılandırabilirsiniz."

**Gösterilecekler:**

**A) Ekran Ayarları sekmesi:**
- Mutfak başlığı, tamamlanan sipariş sayısı, geri alma süresi (saniye)
- Bar başlığı, hazır gösterim adedi

**B) Sayaç Renk Eşikleri:**
- QR / SYM / Hazır / Garson çağrısı için sarı/turuncu/kırmızı dakika eşikleri

**C) MSSQL Ayarları (sekmeli yapı):**
- KDS sekmesi: bağlantı bilgileri + özel SQL sorgusu + RVC filtresi + Test + Önizle butonları
- BDS sekmesi: bağımsız bağlantı (KDS'den farklı olabilir)
- AKDS sekmesi: teal tema, `{{RVC}}` placeholder nasıl kullanılır

**D) Subdomain Ayarları:**
- `bar.domain.com`, `kitchen.domain.com`, `ana.domain.com` alias yapılandırması

---

## BÖLÜM 8 — Mutfak Performans Raporu (1 dk)

**Anlatım:**
> "Mutfak performansını raporlar bölümünden analiz edebilirsiniz."

**Adımlar:**
1. Admin → Raporlar → Mutfak Raporu aç
2. Göster:
   - Bugün tamamlanan sipariş sayısı
   - Ortalama hazırlık süresi
   - En yavaş 50 sipariş
   - Son 30 günlük trend grafiği
3. Tarih aralığı filtresi uygula

---

## BÖLÜM 9 — Kapanış (30 sn)

**Anlatım:**
> "Rocksqr; QR menü, Symphony POS entegrasyonu, canlı mutfak ve bar ekranları ile restoranınızın tüm sipariş akışını tek çatı altında toplar. Teşekkürler."

---

## Çekim Öncesi Kontrol Listesi

Canlı demoya başlamadan önce hazırlamanız gerekenler:

- [ ] Symphony MSSQL bağlantısı yapılandırılmış ve canlı veri geliyor
- [ ] En az 1-2 test siparişi QR üzerinden verilmiş (önceden)
- [ ] Bar ve Kitchen ekranları farklı tarayıcı/monitörde açık
- [ ] Admin ayarlarında geri alma süresi 60 sn'ye çekilmiş (demo için)
- [ ] Ses açık (yeni sipariş / garson çağrısı sesi)
- [ ] Tam ekran butonları hazır
