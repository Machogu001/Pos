#!/usr/bin/env bash
set -euo pipefail

# Install a minimal sudoers policy so the web process can run non-interactive
# service reload/restart commands during update flows.

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root, e.g.: sudo bash scripts/setup_update_sudoers.sh [web_user]"
  exit 1
fi

WEB_USER="${1:-www-data}"
SUDOERS_FILE="/etc/sudoers.d/pos-update-web-reload"

cat > "${SUDOERS_FILE}" <<EOF
# Allow Laravel update flow to reload web services without interactive sudo prompt.
${WEB_USER} ALL=(root) NOPASSWD: \
    /usr/bin/systemctl reload apache2, \
    /usr/bin/systemctl restart apache2, \
    /usr/bin/systemctl reload nginx, \
    /usr/bin/systemctl restart nginx, \
    /usr/bin/systemctl reload httpd, \
    /usr/bin/systemctl restart httpd, \
    /usr/bin/systemctl reload php8.4-fpm, \
    /usr/bin/systemctl restart php8.4-fpm, \
    /usr/bin/systemctl reload php8.3-fpm, \
    /usr/bin/systemctl restart php8.3-fpm, \
    /usr/bin/systemctl reload php8.2-fpm, \
    /usr/bin/systemctl restart php8.2-fpm, \
    /usr/bin/systemctl reload php8.1-fpm, \
    /usr/bin/systemctl restart php8.1-fpm, \
    /usr/bin/systemctl reload php-fpm, \
    /usr/bin/systemctl restart php-fpm, \
    /usr/sbin/service apache2 reload, \
    /usr/sbin/service apache2 restart, \
    /usr/sbin/service nginx reload, \
    /usr/sbin/service nginx restart, \
    /usr/sbin/service httpd reload, \
    /usr/sbin/service httpd restart
EOF

chmod 0440 "${SUDOERS_FILE}"
visudo -cf "${SUDOERS_FILE}"

echo "Installed: ${SUDOERS_FILE}"
echo "Validated with visudo."
echo "Testing one non-interactive command as ${WEB_USER}..."

if id "${WEB_USER}" >/dev/null 2>&1; then
  set +e
  sudo -u "${WEB_USER}" sudo -n /usr/bin/systemctl reload apache2 >/dev/null 2>&1
  TEST_EXIT=$?
  set -e
  if [[ ${TEST_EXIT} -eq 0 ]]; then
    echo "Test passed: ${WEB_USER} can run sudo -n systemctl reload apache2"
  else
    echo "Test command failed with exit code ${TEST_EXIT}."
    echo "If this host does not use apache2, this may be expected; nginx/php-fpm commands can still be allowed."
  fi
else
  echo "User ${WEB_USER} not found. Skipped runtime test."
fi

echo "Done."