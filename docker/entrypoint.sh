#!/bin/bash
set -euo pipefail

PORT="${PORT:-80}"
export APACHE_PORT="$PORT"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
envsubst '${APACHE_PORT}' < /etc/apache2/sites-available/000-default.conf.template > /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
