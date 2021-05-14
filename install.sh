#!/usr/bin/env bash

set -euxo pipefail
TMP_DIR=$(mktemp -d)

run_composer() {
  # See https://hub.docker.com/_/composer for details around
  #   using a non-root user and mounting the SSH agent socket.
  docker run --rm \
    --volume "${COMPOSER_HOME:-$HOME/.composer}:/tmp" \
    --volume "${PWD}:/app" \
    --user "$(id -u):$(id -g)" \
    composer:1.8.0 --profile -vvv "$@" || (
      exit_code=$?
      echo "Exited with code: ${exit_code}"
      echo "If composer exited part way through without reason, it may have run out of memory"
      echo "Stop all other running containers and try again."
      echo "Composer needs about 1.7GB of memory to install Magento and 0.7GB to install other packages"
      exit 1
    )

}

MAGENTO_PATH="${MAGENTO_PATH:-magento}"
mkdir -p "${MAGENTO_PATH}"
cd "${MAGENTO_PATH}" || exit 1

if [ ! -f "composer.json" ]; then
  echo "Installing magento..."
  MAGENTO_VERSION="${MAGENTO_VERSION:-2.3.5}"
  if [ -z "${MAGENTO_VERSION}" ]; then
    MAGENTO_COMPOSER="magento/project-community-edition"
  else
    MAGENTO_COMPOSER="magento/project-community-edition=$MAGENTO_VERSION"
  fi

  echo "Downloading magento (${MAGENTO_COMPOSER})..."
  run_composer create-project --prefer-dist --ignore-platform-reqs --repository-url=https://repo.magento.com/ "${MAGENTO_COMPOSER}" .

  echo "Downloading required packages..."

  # Pin kiwicommerce/module-cron-scheduler to v1.0.7 to support Magento versions >= v2.3.5
  run_composer require --prefer-dist --ignore-platform-reqs \
    kiwicommerce/module-cron-scheduler=1.0.7 \
    kiwicommerce/module-admin-activity \
    kiwicommerce/module-login-as-customer \
    solvedata/plugins-magento2
fi
