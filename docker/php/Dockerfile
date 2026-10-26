# PHP 8.4 FPM imajı (Debian bookworm). FPM, nginx'ten gelen istekleri PHP ile çalıştırır.
# Eklentiler ve Composer bu dosyada kurulur.
FROM php:8.4-fpm-bookworm

# Komut satırı araçları (git, unzip, curl; Composer için gerekli) ve PHP eklentilerinin derleme bağımlılıkları: libpq (PostgreSQL), libzip, icu (dil/biçim), png/jpeg/freetype (resim işleme).
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    # GD (resim) eklentisini freetype ve jpeg desteğiyle derlemek için ayar.
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    # PHP eklentileri kurulur: pdo_pgsql ve pgsql (PostgreSQL), zip, intl (dil ve biçim), gd (resim), bcmath (hassas sayı), opcache (kod önbelleği, hız için). -j: CPU sayısı kadar paralel derleme.
    && docker-php-ext-install -j$(nproc) \
       pdo_pgsql pgsql \
       zip intl gd \
       bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# Composer (PHP paket yöneticisi) resmi imajdan kopyalanır; ayrıca kurulum gerekmez.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Komutların çalışacağı klasör: compose dosyasındaki kod bağlama yeriyle aynı (/var/www/html).
WORKDIR /var/www/html
