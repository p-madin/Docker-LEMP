FROM ubuntu:latest

RUN apt-get update && apt-get install -y \
    build-essential \
    pkg-config \
    curl wget tar \
    git \
    tzdata \
    sudo

RUN echo "retry = 5" >> /root/.curlrc && \
    echo "retry-delay = 3" >> /root/.curlrc && \
    echo "retry-all-errors" >> /root/.curlrc && \
    echo "connect-timeout = 10" >> /root/.curlrc && \
    echo "location" >> /root/.curlrc

ENV TZ=Australia/Sydney

RUN apt update
RUN apt install libxml2-dev -y
RUN apt install libsqlite3-dev -y
RUN apt install libssl-dev -y
RUN apt install libcurl4-openssl-dev -y
RUN apt install libonig-dev -y
RUN apt install libreadline-dev -y
RUN apt install zlib1g-dev -y
RUN apt install libpcre2-dev -y
RUN apt install libpq-dev -y
RUN apt install cron -y
RUN apt install libicu-dev -y


RUN mkdir -p /usr/src/php && \
    curl -o php.tar.gz https://www.php.net/distributions/php-8.4.13.tar.gz && \
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
        --with-pdo-pgsql \
        --with-pgsql \
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
    git clone https://github.com/openresty/echo-nginx-module.git /usr/src/echo-nginx-module && \
    curl -o nginx.tar.gz https://nginx.org/download/nginx-1.29.2.tar.gz && \
    tar -xf nginx.tar.gz -C /usr/src/nginx --strip-components=1 && \
    rm nginx.tar.gz && \
    cd /usr/src/nginx && \
    ./configure \
        --sbin-path=/usr/local/nginx/nginx \
        --conf-path=/usr/local/nginx/nginx.conf \
        --pid-path=/usr/local/nginx/nginx.pid \
        --with-http_ssl_module \
        --with-http_v2_module \
        --add-module=/usr/src/echo-nginx-module && \
    make -j$(nproc) && \
    make install

#Get the docker-cli
RUN curl -o docker-27.3.1.tgz https://download.docker.com/linux/static/stable/x86_64/docker-27.3.1.tgz && \
    tar -xzvf docker-27.3.1.tgz && \
    mv docker/* /usr/local/bin/ && \
    rm -rf docker docker-27.3.1.tgz


RUN curl -o /usr/local/bin/docker-compose https://github.com/docker/compose/releases/download/v2.33.1/docker-compose-linux-x86_64 && \
    chmod +x /usr/local/bin/docker-compose

#RUN mkdir /home/ubunut/Workspace
COPY ./ /home/ubuntu/Workspace/


RUN chmod -R 777 /home/ubuntu/
RUN chmod -R 777 /home/ubuntu/Workspace/

RUN mkdir -p /var/www/html
COPY ./app /var/www/html

RUN openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
    -keyout /var/www/html/nginx-selfsigned.key \
    -out /var/www/html/nginx-selfsigned.crt \
    -subj "/CN=localhost" && \
    chmod 640 /var/www/html/nginx-selfsigned.key && \
    chown root:www-data /var/www/html/nginx-selfsigned.key && \
    chmod 644 /var/www/html/nginx-selfsigned.crt && \
    chown root:www-data /var/www/html/nginx-selfsigned.crt 

RUN chmod 755 /usr/local/nginx/logs && \
    touch /usr/local/nginx/logs/error.log && \
    chown www-data:www-data /usr/local/nginx/logs/error.log

RUN echo "www-data ALL=(root) NOPASSWD: /usr/local/nginx/nginx, /usr/local/bin/docker, /usr/local/bin/docker-compose" >> /etc/sudoers

RUN cp /usr/src/php/php.ini-production /usr/local/etc/php.ini

COPY ./conf/nginx.conf /usr/local/nginx/nginx.conf
COPY ./conf/nginx.live.conf /usr/local/nginx/nginx.live.conf
COPY ./conf/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY ./conf/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./conf/php.ini /usr/local/etc/php/php.ini
RUN printenv > /etc/cron.d/environment.env

COPY ./conf/crontab /etc/cron.d/worker-source

RUN mkdir /usr/local/nginx/conf.d
RUN chmod -R 777 /usr/local/nginx/conf.d

EXPOSE 80

COPY ./startup.sh /usr/local/startup.sh
RUN chmod +x /usr/local/startup.sh
WORKDIR /usr/local/
CMD ["/usr/local/startup.sh"]