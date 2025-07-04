#!/bin/bash

# Fetch the versions of WooCommerce from the WordPress API
LATEST_VERSION=$(
  curl -s https://api.wordpress.org/plugins/info/1.0/woocommerce.json | \
  jq -r '.versions | keys_unsorted | .[]' | \
  grep -v 'trunk' | \
  sort -V | \
  tail -n 1
)

# Check if the latest version is a beta/RC version
if [[ $LATEST_VERSION != *'beta'* && $LATEST_VERSION != *'rc'* ]]; then
  echo "No WooCommerce beta/RC version found."
  echo "LATEST_BETA="
else
  echo "Latest WooCommerce beta/RC version: $LATEST_VERSION"
  echo "LATEST_BETA=$LATEST_VERSION"
fi
