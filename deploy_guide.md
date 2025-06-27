# 🚀 Oto Asist Deployment Guide

## Hızlı Deployment Seçenekleri

### 1. Railway.app (En Hızlı - 5 dakika) ⭐
1. **GitHub Repository Oluştur**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin https://github.com/USERNAME/otoasist-backend.git
   git push -u origin main
   ```

2. **Railway.app Hesabı**
   - https://railway.app adresine git
   - GitHub ile giriş yap
   - "New Project" → "Deploy from GitHub repo"

3. **Environment Variables**
   ```
   DB_HOST=containers-us-west-1.railway.app
   DB_PORT=3306
   DB_NAME=railway
   DB_USERNAME=root
   DB_PASSWORD=[Railway tarafından otomatik]
   APP_URL=https://otoasist-backend-production.up.railway.app
   ```

### 2. DigitalOcean App Platform (10 dakika)
1. **GitHub Repository** (yukarıdaki gibi)
2. DigitalOcean hesabı oluştur
3. "Create App" → GitHub repo seç
4. MySQL database ekle
5. Environment variables ayarla

### 3. Hostinger (En Ekonomik - $2.99/ay)
1. Hostinger Premium plan satın al
2. cPanel'e git
3. File Manager ile dosyaları yükle
4. MySQL database oluştur
5. Database'i import et

## 📦 Deployment Dosyaları

### Gerekli Dosyalar
```
backend/
├── api/                    # API endpoints
├── config/                 # Configuration files
├── admin_modern.php        # Admin panel
├── database_schema_update.sql # Database schema
├── news_table.sql          # News table
├── config_production.php   # Production config
└── .htaccess              # URL rewriting
```

### Database Import Komutları
```sql
-- 1. Database oluştur
CREATE DATABASE otoasist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Schema'yı import et
mysql -u username -p otoasist < database_schema_update.sql
mysql -u username -p otoasist < news_table.sql

-- 3. Demo verileri ekle (opsiyonel)
-- Admin panel üzerinden eklenebilir
```

## 🔧 Configuration

### 1. Database Config Update
`config/database.php` dosyasını production için güncelle:
```php
<?php
class Database {
    private $host = "YOUR_DB_HOST";
    private $db_name = "YOUR_DB_NAME"; 
    private $username = "YOUR_DB_USER";
    private $password = "YOUR_DB_PASS";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                 $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
```

### 2. Flutter App Config
`lib/core/constants/network_constants.dart`:
```dart
class NetworkConstants {
  static const String baseUrl = 'https://yourdomain.com';
  // Diğer ayarlar...
}
```

## 🌐 Domain ve SSL

### Ücretsiz SSL
- Railway, DigitalOcean, Hostinger otomatik SSL sağlar
- Cloudflare ücretsiz SSL + CDN için

### Custom Domain
1. Domain satın al (Namecheap, GoDaddy)
2. DNS ayarlarını hosting provider'a yönlendir
3. SSL sertifikası otomatik aktif olur

## ✅ Deployment Checklist

### Önce Yapılacaklar
- [ ] GitHub repository oluştur
- [ ] Database export al
- [ ] Production config hazırla
- [ ] Flutter baseUrl güncelle

### Deployment Sırasında
- [ ] Hosting seç ve hesap oluştur
- [ ] Repository'yi bağla
- [ ] Environment variables ayarla
- [ ] Database oluştur ve import et
- [ ] Admin panele erişim test et

### Sonra Yapılacaklar
- [ ] Flutter app'i rebuild et
- [ ] API endpoints test et
- [ ] Admin panel test et
- [ ] Mobile app test et

## 💡 Pro Tips

### Hız için
- Railway.app en hızlı (5 dk)
- Otomatik deployment için GitHub Actions
- CDN için Cloudflare

### Güvenlik için
- Strong database passwords
- Admin token değiştir
- HTTPS zorunlu kıl
- Regular backups

### Monitoring için
- Hosting provider dashboard
- Google Analytics
- Error tracking (Sentry)

## 🆘 Troubleshooting

### Yaygın Hatalar
1. **Database Connection Error**
   - Host, username, password kontrol et
   - Port 3306 açık mı kontrol et

2. **CORS Errors**
   - API headers'ı kontrol et
   - Domain whitelist'i güncelle

3. **File Upload Issues**
   - PHP upload limits artır
   - Directory permissions 755

### Hızlı Test
```bash
# API test
curl https://yourdomain.com/api/v1/news/

# Admin panel test  
curl https://yourdomain.com/admin_modern.php
```

## 📞 Support

En hızlı çözüm için Railway.app öneriyorum:
1. 5 dakikada deploy
2. Otomatik SSL
3. MySQL dahil
4. $5/ay uygun fiyat
5. Kolay yönetim

Hangi seçeneği tercih ediyorsunuz? Size özel adım adım guide hazırlayabilirim. 