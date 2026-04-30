FROM php:8.4-cli-bookworm

# System deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    libexif-dev libicu-dev libcurl4-openssl-dev \
    && docker-php-ext-install \
        pdo pdo_mysql mbstring xml bcmath gd exif \
        tokenizer fileinfo curl zip ctype intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Node 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP deps
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Install Node deps
COPY package.json package-lock.json ./
RUN npm ci

# Copy app source
COPY . .

# Build frontend assets
RUN npm run build

# Composer post-install scripts (autoload dump etc.)
RUN composer run-script post-autoload-dump --no-interaction || true

RUN chmod +x startup.sh

EXPOSE 8000

CMD ["bash", "startup.sh"]
