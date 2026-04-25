# Sistem Gereksinimleri

Bu projeyi çalıştırmak için aşağıdaki yazılım ve PHP eklentilerinin kurulu olması gerekmektedir:

## Sunucu
- PHP 8.2 veya üzeri
- Composer
- Node.js ve npm (ön yüz derleme işlemleri için)
- XAMPP veya benzeri bir sunucu (Apache, MySQL, PHP)

## PHP Eklentileri
- PDO
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
- MSSQL Symphony entegrasyonu (ürün senkronizasyonu ve `/kitchen-pos` ekranı) için Microsoft ODBC Driver for SQL Server ve PHP SQLSRV sürücüleri kurulmalıdır.
- Bağlantı bilgileri Admin > MSSQL Ayarları üzerinden veritabanında saklanır; `.env` değişkeni gerekmez.

Kurulum ve yapılandırma adımlarını dikkatlice takip edin. Herhangi bir eksik eklenti veya yazılım, uygulamanın bazı özelliklerinin çalışmamasına neden olabilir.