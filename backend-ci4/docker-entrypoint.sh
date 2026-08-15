#!/bin/sh
set -eu

cat > .env <<EOF
CI_ENVIRONMENT = production
app.baseURL = '${APP_BASE_URL}'
database.default.hostname = '${DB_HOST}'
database.default.database = '${DB_DATABASE}'
database.default.username = '${DB_USERNAME}'
database.default.password = '${DB_PASSWORD}'
database.default.DBDriver = MySQLi
database.default.port = 3306
JWT_SECRET = '${JWT_SECRET}'
JWT_TTL = ${JWT_TTL:-3600}
CORS_ALLOWED_ORIGIN = '${CORS_ALLOWED_ORIGIN}'
EOF

php spark migrate --all --no-interaction
exec apache2-foreground
