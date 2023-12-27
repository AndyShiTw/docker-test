# 使用官方的 PHP 映像
FROM php:7.3-fpm

# 安裝必要的軟體包
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libssl-dev \
    procps \
    vim \
    supervisor \
    # pear \
    && pecl install redis \
    && docker-php-ext-enable redis
    
# 安裝 PHP 擴展
RUN docker-php-ext-install zip pdo pdo_mysql bcmath 
RUN docker-php-ext-install json mbstring opcache pcntl
# RUN docker-php-ext-install recode soap xml posix 

# 安裝 Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 創建工作目錄
WORKDIR /var/www/html

# 複製 Laravel 專案到容器中
COPY . .

# 安裝 Laravel 的依賴
RUN composer install

# RUN chown -R www-data:www-data /var/www/html
# RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# 啟動websocket服務
# CMD ["php", "artisan", "WebSocketCommand", "start","--d"]

# 透過supervisord，同時管理php-fpm和websocket服務
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]