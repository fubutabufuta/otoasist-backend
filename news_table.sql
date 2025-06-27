-- Haberler tablosu
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    content TEXT,
    image_url VARCHAR(500),
    category VARCHAR(100),
    is_sponsored BOOLEAN DEFAULT FALSE,
    author VARCHAR(100),
    publish_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    view_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Örnek haberler ekle
INSERT INTO
    news (
        title,
        description,
        content,
        image_url,
        category,
        is_sponsored,
        author
    )
VALUES (
        'Yeni Araç Bakım Teknolojileri',
        'Akıllı sensörlerle araç bakımında devrim...',
        'Otomotiv sektöründe akıllı sensör teknolojileri sayesinde araç bakımı artık daha kolay ve etkili hale geliyor. Bu yeni teknolojiler sayesinde araç sahipleri, bakım zamanlarını daha doğru tahmin edebiliyor ve beklenmedik arızaların önüne geçebiliyor.',
        'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=200&h=120&fit=crop',
        'Teknoloji',
        TRUE,
        'Ahmet Yılmaz'
    ),
    (
        'Kış Lastiği Değişim Zamanı',
        'Güvenli sürüş için doğru lastik seçimi...',
        'Kış mevsiminin yaklaşmasıyla birlikte araç sahiplerinin en önemli görevlerinden biri kış lastiklerine geçiş yapmak. Doğru lastik seçimi, güvenli sürüş için kritik öneme sahip.',
        'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=200&h=120&fit=crop',
        'Güvenlik',
        FALSE,
        'Mehmet Demir'
    ),
    (
        'Elektrikli Araçlarda Bakım İpuçları',
        'Elektrikli araçlar için özel bakım önerileri...',
        'Elektrikli araçların bakımı geleneksel araçlardan farklılık gösteriyor. Bu rehberde elektrikli aracınızı en iyi şekilde nasıl bakacağınızı öğreneceksiniz.',
        'https://images.unsplash.com/photo-1593941707882-a5bac6861d75?w=200&h=120&fit=crop',
        'Elektrikli',
        TRUE,
        'Ayşe Kaya'
    ),
    (
        'Motor Yağı Seçim Rehberi',
        'Aracınız için en uygun motor yağını nasıl seçersiniz?',
        'Motor yağı, aracınızın kalbinin sağlıklı çalışması için en önemli unsurlardan biridir. Doğru motor yağı seçimi, motorunuzun ömrünü uzatır.',
        'https://images.unsplash.com/photo-1486754735734-325b5831c3ad?w=200&h=120&fit=crop',
        'Bakım',
        FALSE,
        'Can Özkan'
    ),
    (
        'Araç Sigortasında Dikkat Edilmesi Gerekenler',
        'Sigorta seçerken hangi kriterlere odaklanmalısınız?',
        'Araç sigortası seçimi önemli bir karardır. Bu yazıda sigorta seçerken dikkat etmeniz gereken temel kriterleri bulacaksınız.',
        'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=200&h=120&fit=crop',
        'Sigorta',
        TRUE,
        'Fatma Şen'
    );