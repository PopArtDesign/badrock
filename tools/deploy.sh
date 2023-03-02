#!/usr/bin/env sh

src="${1:?Source required}"
dst="${2:?Destination required}"

dst_host="${dst%%:*}"
dst_dir="${dst##*:}"

rsync -chav --delete \
    --include='.env.local.php' \
    --exclude='/.*' \
    --exclude='/node_modules/' \
    --exclude='/public/uploads/*' \
    --exclude='/var/log/*' \
    --exclude='/tests/' \
    "${src}/" "${dst}" && \
    ssh "${dst_host}" "cd '${dst_dir}' && tools/deploy_tasks.sh"
