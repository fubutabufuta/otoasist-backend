# 🚗 Oto Asist Backend API

Modern otomotiv hizmetleri ve bakım takip platformu - Backend API

## 🚀 Live Demo
- **Admin Panel**: [https://otoasist-backend-production.up.railway.app/admin_modern.php](URL)
- **API Documentation**: [https://otoasist-backend-production.up.railway.app/api/v1/](URL)

## 🔑 Admin Access
- **Token**: `+905551234567`
- **User**: Ahmet Yılmaz (Admin)

## 📱 Features

### 🔐 Authentication
- Token-based API authentication
- Session-based admin panel
- Role-based access control

### 📊 Admin Panel
- Modern AdminMart themed interface
- Real-time filtering for all tables
- Complete CRUD operations
- Statistics dashboard

### 🌐 API Endpoints
- **Auth**: Login, register, verification
- **Vehicles**: Vehicle management
- **Quotes**: Quote requests and management
- **News**: News articles with categories
- **Sliders**: Homepage sliders with click tracking
- **Campaigns**: Marketing campaigns
- **Reminders**: Maintenance reminders

### 🎯 Advanced Features
- Smart filtering system
- Click tracking for sliders
- Automatic table creation
- MySQL with SQLite fallback
- CORS enabled
- SSL ready

## 🛠 Tech Stack
- **Backend**: PHP 8.2+ with PDO
- **Database**: MySQL / SQLite
- **Admin UI**: Bootstrap 5 + FontAwesome
- **API**: RESTful JSON API
- **Deployment**: Railway.app ready

## 🚀 Quick Deploy on Railway

1. **Fork this repository**
2. **Railway.app** → New Project → Deploy from GitHub
3. **Add MySQL Plugin** (automatic)
4. **Import Database**:
   - Copy content from `database_schema_update.sql`
   - Paste in Railway Database Query tab
   - Copy content from `news_table.sql`
   - Paste and execute

5. **Done!** Your API is live with HTTPS

## 🔧 Local Development

```bash
# Start local server
php -S localhost:8000

# Admin Panel
http://localhost:8000/admin_modern.php

# API Base
http://localhost:8000/api/v1/
```

## 📊 Database Schema

### Core Tables
- `users` - User management
- `vehicles` - Vehicle information
- `quote_requests` - Service quotes
- `news` - News articles
- `sliders` - Homepage sliders
- `campaigns` - Marketing campaigns
- `reminders` - Maintenance reminders

### Admin Tables
- `user_roles` - Role definitions
- `service_providers` - Service companies
- `subscription_packages` - Service packages
- `app_settings` - Application settings

## 🔒 Security Features
- SQL injection protection (PDO)
- XSS protection
- CSRF protection
- Secure headers
- Input validation
- Password hashing

## 📈 Performance
- Optimized queries
- Caching headers
- Gzip compression
- CDN ready
- Database indexing

## 🌍 Production Ready
- Environment configuration
- Error handling
- Logging system
- Monitoring ready
- Auto-scaling compatible

## 📞 Support
Built with ❤️ for modern automotive businesses

---
**Powered by Railway.app** 🚂 