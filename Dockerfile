FROM ubuntu:latest

RUN apt-get update && apt-get install -y \
    build-essential \
    pkg-config \
    wget tar

RUN apt install libxml2-dev -y
RUN apt install libsqlite3-dev -y
RUN apt install libssl-dev -y
RUN apt install libcurl4-openssl-dev -y
RUN apt install libonig-dev -y
RUN apt install libreadline-dev -y
RUN apt install zlib1g-dev -y
RUN apt install libpcre3-dev -y

RUN mkdir -p /usr/src/php && \
    wget -O php.tar.gz https://www.php.net/distributions/php-8.4.13.tar.gz && \
    tar -xf php.tar.gz -C /usr/src/php --strip-components=1 && \
    rm php.tar.gz && \
    cd /usr/src/php && \
    ./configure \
        --prefix=/usr/local \
        --with-config-file-path=/usr/local/etc/php \
        --with-config-file-scan-dir=/usr/local/etc/php/conf.d \
        --enable-fpm \
        --with-fpm-user=www-data \
        --with-fpm-group=www-data \
        --enable-mbstring \
        --enable-mysqlnd \
        --with-pdo-mysql=mysqlnd \
        --with-openssl \
        --with-curl \
        --with-libzip \
        --with-gd \
        --with-webp \
        --with-freetype \
        --enable-exif \
        --enable-bcmath \
        --enable-intl \
        --with-readline && \
    make -j$(nproc) && \
    make install

RUN mkdir -p /usr/src/nginx && \
    wget -O nginx.tar.gz https://nginx.org/download/nginx-1.29.2.tar.gz && \
    tar -xf nginx.tar.gz -C /usr/src/nginx --strip-components=1 && \
    rm nginx.tar.gz && \
    cd /usr/src/nginx && \
    ./configure \
        --sbin-path=/usr/local/nginx/nginx \
        --conf-path=/usr/local/nginx/nginx.conf \
        --pid-path=/usr/local/nginx/nginx.pid \
        --with-http_ssl_module && \
    make -j$(nproc) && \
    make install




#RUN groupadd -r www-data && useradd -r -g www-data www-data

RUN cp /usr/src/php/php.ini-production /usr/local/etc/php.ini
#RUN cp /usr/local/etc/php-fpm.conf.default /usr/local/etc/php-fpm.conf
#RUN cp /usr/local/etc/php-fpm.d/www.conf.default /usr/local/etc/php-fpm.d/www.conf

COPY ./conf/nginx.conf /usr/local/nginx/nginx.conf
COPY ./conf/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY ./conf/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./conf/php.ini /usr/local/etc/php/php.ini




#COPY --from=build_stage /usr/src/php/php.ini-production /usr/local/etc/php/php.ini
#COPY --from=build_stage /usr/src/php/sapi/fpm/php-fpm.conf /usr/local/etc/php/php-fpm.conf

EXPOSE 80

COPY ./startup.sh /usr/local/startup.sh
RUN chmod +x /usr/local/startup.sh
WORKDIR /usr/local/
CMD ["/usr/local/startup.sh"]