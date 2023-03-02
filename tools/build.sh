#!/usr/bin/env sh

dst="${1:?Destination required}"
branch="${2:-main}"

dst="$(realpath "${dst}")"

[ -d "${dst}" ] || {
    echo "Destination directory not exists: ${dst}"
    exit 1
} >&2

[ "$(ls -A "${dst}")" ] && {
    echo "Destination directory is not empty: ${dst}"
    exit 1
} >&2

git --work-tree="${dst}" checkout -f "${branch}" && \
    cd "${dst}" && tools/build_tasks.sh
