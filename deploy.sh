#!/usr/bin/env bash
#
# tariif.ee juurutus — korratav, mitte käsitsi scp.
#
# Eeldab, et muudatused on GitHubis. Serveris tehakse ainult pull + ehitus.
# Testid jooksevad ENNE vahemälude soojendamist: katkine kood ei jõua cache'i.

set -euo pipefail

APP="/data01/virt143084/domeenid/www.tariif.ee/tariif"
PHP="/opt/zone/bin/php84-cli"

echo "▸ tariif.ee juurutus $(date '+%Y-%m-%d %H:%M')"

ssh tariif "set -euo pipefail
  cd $APP

  echo '  · git pull'
  git pull --ff-only

  echo '  · composer'
  composer install --no-dev --optimize-autoloader --no-interaction --quiet

  echo '  · migratsioonid'
  $PHP artisan migrate --force

  echo '  · frontend'
  npm ci --silent && npm run build

  echo '  · testid'
  $PHP artisan test --compact

  echo '  · vahemälud'
  $PHP artisan config:cache
  $PHP artisan route:cache
  $PHP artisan view:cache
"

echo "▸ kontroll"
curl -fsS -o /dev/null -w '  / → %{http_code}\n' https://test.tariif.ee/
curl -fsS https://test.tariif.ee/api/v1/health | python3 -c "import sys,json; d=json.load(sys.stdin); print('  health →', d['status'], '·', d['rows_total'], 'rida')"

echo "▸ valmis"
