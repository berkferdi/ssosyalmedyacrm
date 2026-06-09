# SocialPilot AI

Sosyal medya otomasyon ve yönetim sistemi. PHP 8.3, MySQL 8, Bootstrap 5.3 ile geliştirilmiştir.

## Özellikler

- **Çoklu Platform:** Instagram Business, Facebook Sayfaları, LinkedIn, WordPress
- **AI İçerik Üretimi:** OpenAI ile otomatik başlık, açıklama, hashtag, SEO metni
- **Otomatik Paylaşım:** Cron tabanlı zamanlanmış paylaşım motoru
- **Tam Otomatik Mod:** Toplu görsel yükleme, AI içerik, otomatik planlama
- **İçerik Takvimi:** Sürükle-bırak ile tarih değiştirme
- **Ajans Yönetimi:** Çoklu müşteri ve proje desteği
- **Analitik:** Platform bazlı istatistikler ve grafikler
- **Bildirimler:** E-posta, Telegram, WhatsApp

## Gereksinimler

- PHP 8.3+
- MySQL 8.0+
- Apache/Nginx (mod_rewrite)
- PHP Extensions: PDO, curl, json, mbstring, gd

## Kurulum

1. Dosyaları web sunucunuza yükleyin
2. `uploads/` klasörüne yazma izni verin: `chmod -R 755 uploads/`
3. Tarayıcıda `/install/` adresine gidin
4. Kurulum sihirbazını takip edin:
   - Veritabanı bağlantısı
   - Tablo oluşturma
   - Admin hesabı
   - Uygulama ayarları
5. Kurulum sonrası `/install/` klasörünü silin

## Cron Job

Dakikada bir çalışacak şekilde ayarlayın:

```bash
* * * * * php /path/to/cron/cron_scheduler.php
```

Alternatif (HTTP):

```bash
* * * * * curl -s "https://yourdomain.com/cron/cron_scheduler.php?key=CRON_SECRET_KEY"
```

## Dizin Yapısı

```
/admin          Yönetim paneli
/api            REST API endpoints
/assets         CSS, JS, görseller
/cron           Zamanlanmış görevler
/database       SQL şema dosyaları
/includes       Çekirdek PHP sınıfları
/install        Kurulum sihirbazı
/templates      HTML şablonları
/uploads        Medya dosyaları
```

## Kullanıcı Rolleri

| Rol | Yetki |
|-----|-------|
| Süper Admin | Tam erişim |
| Ajans Yöneticisi | Müşteri ve proje yönetimi |
| Editör | İçerik oluşturma ve planlama |
| Müşteri | Kendi paneline erişim |

## API Entegrasyonları

`includes/config.php` dosyasında yapılandırın:

- **OpenAI:** `OPENAI_API_KEY`
- **Facebook/Instagram:** `FACEBOOK_APP_ID`, `FACEBOOK_APP_SECRET`
- **LinkedIn:** `LINKEDIN_CLIENT_ID`, `LINKEDIN_CLIENT_SECRET`
- **SMTP:** `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`
- **Telegram:** `TELEGRAM_BOT_TOKEN`
- **WhatsApp:** `WHATSAPP_API_URL`, `WHATSAPP_API_TOKEN`

## Güvenlik

- PDO prepared statements (SQL Injection koruması)
- CSRF token doğrulama
- XSS escaping (htmlspecialchars)
- Bcrypt şifre hashleme
- 2FA (TOTP) desteği
- Oturum ve IP logları
- Upload dizininde PHP çalıştırma engeli

## Lisans

Özel lisans. Tüm hakları saklıdır.
