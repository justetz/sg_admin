FROM alpine:latest

RUN apk update && apk upgrade
RUN apk add php7 php7-zip php7-curl php7-session php7-dom composer

WORKDIR /var/www

COPY composer* ./

RUN composer install

COPY . ./
