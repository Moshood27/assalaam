# Minimal Laravel Sail Dockerfile for PHP 8.5
# This fallback lets you build the Sail image even if you haven't published
# Sail's Dockerfiles yet (./vendor/bin/sail artisan sail:publish).
# You can keep using this minimal base, or publish Sail to customize further.

ARG WWWGROUP=1000
FROM ghcr.io/laravel/sail:8.5

# Optional: align container user/group with host to avoid permission issues
# ARG WWWUSER=1000
# RUN groupmod -g ${WWWGROUP} www-data \
#     && usermod -u ${WWWUSER} -g ${WWWGROUP} www-data

# Optional: mitigate npm timeouts on slow networks if you add Node builds here
# RUN npm config set fetch-retry-maxtimeout 600000 && npm install -g npm@latest

WORKDIR /var/www/html
