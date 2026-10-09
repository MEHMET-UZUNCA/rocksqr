# Sistem Gereksinimleri

Bu projeyi çalıştırmak için aşağıdaki yazılım ve PHP eklentilerinin kurulu olması gerekmektedir:

## Sunucu
- PHP 8.2 veya üzeri
- Laravel 12 çerçevesi (Composer ile kurulur)
- Composer
- Node.js ve npm (ön yüz derleme işlemleri için)
- MySQL 5.7+ / 8.0 veya MariaDB 10.3+ (uygulama veritabanı)
- Apache veya Nginx (XAMPP veya benzeri bir paket de kullanılabilir)

## PHP Eklentileri
- PDO
- PDO_MySQL (uygulama veritabanı için)
- PDO_SQLSRV (Microsoft SQL Server bağlantısı için)
- SQLSRV (Microsoft SQL Server sürücüsü için)
- GD (QR kodlarına logo eklemek için)
- Fileinfo
- Mbstring
- OpenSSL
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- ZipArchive (Toplu QR arşivleri için)

## Ek Gereksinimler
- MSSQL Symphony entegrasyonu (ürün senkronizasyonu ve `/kitchen-pos`, `/kitchen-ana`, `/bar` canlı akışları) için Microsoft ODBC Driver for SQL Server ve PHP SQLSRV sürücüleri kurulmalıdır.
- Bağlantı bilgileri Admin > MSSQL Ayarları üzerinden veritabanında saklanır; `.env` değişkeni gerekmez.

## Zamanlayıcı (Cron)
Uygulama, Laravel zamanlayıcısına kayıtlı iki arka plan görevini barındırır:
- **Gece ekran temizliği**: her ekran için ayrılan temizlik saatinde (Ayarlar > Genel) bekleyen kartların temizlenmesi ve yanıtsız garson çağrılarının otomatik kapanan olarak işaretlenmesi.
- **MSSQL otomatik fiyat senkronu**: Sistem sekmesinde seçilen moda göre (kapalı / her N dakikada / günlük saat) ürün fiyatı güncellemesi.

Bu görevlerin çalışması için sunucuda dakikada bir `schedule:run` tetikleyen bir cron kaydı olmalıdır:

```
* * * * * cd /proje/yolu && php artisan schedule:run >> /dev/null 2>&1
```

Kurulum ve yapılandırma adımlarını dikkatlice takip edin. Herhangi bir eksik eklenti veya yazılım, uygulamanın bazı özelliklerinin çalışmamasına neden olabilir.
