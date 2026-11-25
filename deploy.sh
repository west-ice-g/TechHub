#!/usr/bin/env sh
# TechHub Docker Compose deployment helper.
# Usage: ./deploy.sh [up|start|stop|down|restart|logs|migrate|init-admin|backup|status]
set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
cd "$ROOT_DIR"

ENV_FILE=${ENV_FILE:-.env}

if docker compose version >/dev/null 2>&1; then
    COMPOSE="docker compose --env-file $ENV_FILE"
elif command -v docker-compose >/dev/null 2>&1; then
    COMPOSE="docker-compose --env-file $ENV_FILE"
else
    echo "未找到 Docker Compose。请先安装 Docker Engine/Desktop 和 Compose v2。" >&2
    exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
    cp .env.example "$ENV_FILE"
    chmod 600 "$ENV_FILE" 2>/dev/null || true
    echo "已创建 $ENV_FILE。请先填写数据库密码和 INITIAL_ADMIN_PASSWORD，再重新执行 ./deploy.sh up。" >&2
    exit 1
fi

get_env() {
    key=$1
    value=$(awk -F= -v key="$key" '$1 == key {sub(/^[^=]*=/, ""); print; exit}' "$ENV_FILE")
    printf '%s' "$value" | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

check_required() {
    key=$1
    value=$(get_env "$key")
    if [ -z "$value" ] || printf '%s' "$value" | grep -qi 'change-me'; then
        echo ".env 中的 $key 未设置或仍为示例值。" >&2
        exit 1
    fi
}

check_required MYSQL_ROOT_PASSWORD
check_required MYSQL_PASSWORD
check_required INITIAL_ADMIN_PASSWORD

APP_DB_PASSWORD=$(get_env DB_PASSWORD)
if [ -n "$APP_DB_PASSWORD" ] && [ "$APP_DB_PASSWORD" != "$(get_env MYSQL_PASSWORD)" ]; then
    echo ".env 中 DB_PASSWORD 必须与 MYSQL_PASSWORD 保持一致。" >&2
    exit 1
fi

DB_NAME=$(get_env MYSQL_DATABASE)
DB_NAME=${DB_NAME:-techhub}
if [ "$DB_NAME" != "techhub" ]; then
    echo "当前 data/install.sql 使用 techhub 库名；请保持 MYSQL_DATABASE=techhub，或同步修改安装/迁移 SQL。" >&2
    exit 1
fi

prepare_dirs() {
    mkdir -p storage/sessions storage/logs public/uploads data/team data/personal data/resources backups
}

wait_for_db() {
    printf '等待 MySQL 初始化'
    i=0
    while [ "$i" -lt 90 ]; do
        if $COMPOSE exec -T db sh -c \
            'mysqladmin ping -h 127.0.0.1 -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --silent' \
            >/dev/null 2>&1; then
            printf '\nMySQL 已就绪。\n'
            return 0
        fi
        printf '.'
        sleep 2
        i=$((i + 1))
    done
    printf '\nMySQL 未在预期时间内就绪，请执行 ./deploy.sh logs 查看日志。\n' >&2
    return 1
}

run_migrations() {
    echo "执行可重复迁移..."
    for file in \
        data/migrations/2026-11-25_frontend.sql \
        data/migrations/2026-11-25_admin.sql \
        data/migrations/2026-11-25_admin_demo_dates.sql
    do
        if [ -f "$file" ]; then
            $COMPOSE exec -T db sh -c \
                'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < "$file"
        fi
    done
}

init_admin() {
    echo "初始化/确认管理员密码..."
    $COMPOSE exec -T app php /var/www/html/docker/init-admin.php
}

show_url() {
    url=$(get_env APP_URL)
    if [ -n "$url" ]; then
        echo "访问地址：$url"
    else
        port=$(get_env APP_PORT)
        port=${port:-8090}
        echo "访问地址：http://localhost:$port"
    fi
}

case "${1:-up}" in
    up)
        prepare_dirs
        $COMPOSE up -d --build
        wait_for_db
        run_migrations
        init_admin
        echo "Docker Compose 服务已启动。"
        show_url
        ;;
    start)
        prepare_dirs
        $COMPOSE start
        ;;
    stop)
        $COMPOSE stop
        ;;
    down)
        $COMPOSE down
        echo "容器已停止并移除；数据库和作业文件卷/目录保留。"
        ;;
    restart)
        $COMPOSE restart
        ;;
    logs)
        $COMPOSE logs -f --tail=200
        ;;
    migrate)
        wait_for_db
        run_migrations
        ;;
    init-admin)
        init_admin
        ;;
    backup)
        prepare_dirs
        stamp=$(date +%Y%m%d-%H%M%S)
        $COMPOSE exec -T db sh -c \
            'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --routines --triggers "$MYSQL_DATABASE"' \
            > "backups/techhub-$stamp.sql"
        tar -czf "backups/files-$stamp.tar.gz" data/team data/personal data/resources public/uploads
        echo "备份完成：backups/techhub-$stamp.sql、backups/files-$stamp.tar.gz"
        ;;
    status)
        $COMPOSE ps
        ;;
    help|-h|--help)
        cat <<'EOF'
用法：./deploy.sh [命令]
  up          构建并启动，等待数据库，执行迁移，初始化管理员
  start       启动已有容器
  stop        停止容器
  down        停止并移除容器（保留数据库卷和文件）
  restart     重启容器
  logs        查看容器日志
  migrate     对现有数据库执行可重复迁移
  init-admin  按 .env 初始化首次管理员密码
  backup      备份数据库与 data/uploads 文件
  status      查看容器状态
EOF
        ;;
    *)
        echo "未知命令：$1" >&2
        echo "执行 ./deploy.sh help 查看用法。" >&2
        exit 1
        ;;
esac
