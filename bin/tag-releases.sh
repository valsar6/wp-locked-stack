#!/bin/sh
set -eu

for directory in packages/*/; do
    slug=$(basename "$directory")
    version=$(jq -r .version "${directory}composer.json")
    tag="$slug/v$version"
    if git rev-parse -q --verify "refs/tags/$tag" >/dev/null; then
        continue
    fi
    git tag "$tag"
    git push origin "refs/tags/$tag"
    printf 'tagged %s\n' "$tag"
done
