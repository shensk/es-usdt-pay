# USDT支付系统 - Linux部署文档

## 系统要求

### 基础环境
- **操作系统**: Ubuntu 18.04+ / CentOS 7+ / Debian 9+
- **PHP版本**: PHP 8.0+ (推荐 PHP 8.1)
- **数据库**: MySQL 5.7+ / MariaDB 10.3+
- **Redis**: Redis 5.0+
- **内存**: 最低 2GB RAM (推荐 4GB+)
- **存储**: 最低 20GB 可用空间

### PHP扩展要求
```bash
php-fpm
php-cli
php-mysql
php-redis
php-curl
php-json
php-mbstring
php-xml
php-zip
php-gd
php-bcmath
php-opcache
```

## 1. 环境准备

### 1.1 更新系统包
```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
# 或者 (CentOS 8+)
sudo dnf update -y
```

### 1.2 安装PHP 8.1
```bash
# Ubuntu/Debian
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.1 php8.1-fpm php8.1-cli php8.1-mysql php8.1-redis \
    php8.1-curl php8.1-json php8.1-mbstring php8.1-xml php8.1-zip \
    php8.1-gd php8.1-bcmath php8.1-opcache -y

# CentOS/RHEL
sudo yum install epel-release -y
sudo yum install https://rpms.remirepo.net/enterprise/remi-release-7.rpm -y
sudo yum-config-manager --enable remi-php81
sudo yum install php php-fpm php-cli php-mysql php-redis php-curl \
    php-json php-mbstring php-xml php-zip php-gd php-bcmath php-opcache -y
```

### 1.3 安装Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# 验证安装
composer --version
```

### 1.4 安装MySQL
```bash
# Ubuntu/Debian
sudo apt install mysql-server -y

# CentOS/RHEL
sudo yum install mysql-server -y
# 或者
sudo dnf install mysql-server -y

# 启动MySQL服务
sudo systemctl start mysql
sudo systemctl enable mysql

# 安全配置
sudo mysql_secure_installation
```

### 1.5 安装Redis
```bash
# Ubuntu/Debian
sudo apt install redis-server -y

# CentOS/RHEL
sudo yum install redis -y

# 启动Redis服务
sudo systemctl start redis
sudo systemctl enable redis

# 验证Redis
redis-cli ping
```

### 1.6 安装Nginx
```bash
# Ubuntu/Debian
sudo apt install nginx -y

# CentOS/RHEL
sudo yum install nginx -y

# 启动Nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

## 2. 项目部署

### 2.1 创建项目目录
```bash
# 创建网站根目录
sudo mkdir -p /var/www/usdt-payment
sudo chown -R www-data:www-data /var/www/usdt-payment  # Ubuntu/Debian
# sudo chown -R nginx:nginx /var/www/usdt-payment      # CentOS/RHEL

# 切换到项目目录
cd /var/www/usdt-payment
```

### 2.2 下载项目代码
```bash
# 方式1: 使用Git克隆
git clone https://github.com/your-repo/webman-usdt-payment.git .

# 方式2: 上传代码包并解压
# 将项目代码上传到服务器后解压
# unzip webman-usdt-payment.zip
# mv webman-usdt-payment/* .
```

### 2.3 安装依赖
```bash
# 安装Composer依赖
composer install --no-dev --optimize-autoloader

# 设置权限
sudo chown -R www-data:www-data /var/www/usdt-payment
sudo chmod -R 755 /var/www/usdt-payment
sudo chmod -R 777 /var/www/usdt-payment/runtime
sudo chmod -R 777 /var/www/usdt-payment/public
```

### 2.4 配置环境文件
```bash
# 复制环境配置文件
cp .env.example .env

# 编辑配置文件
nano .env
```

配置内容示例：
```env
# 应用配置
APP_DEBUG=false
APP_ENV=production

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=usdt_payment
DB_USERNAME=usdt_user
DB_PASSWORD=your_strong_password

# Redis配置
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

# 支付配置
PAYMENT_TIMEOUT=1800
API_AUTH_TOKEN=your_api_secret_key_here
```

## 3. 数据库配置

### 3.1 创建数据库和用户
```sql
-- 登录MySQL
mysql -u root -p

-- 创建数据库
CREATE DATABASE usdt_payment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建用户并授权
CREATE USER 'usdt_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON usdt_payment.* TO 'usdt_user'@'localhost';
FLUSH PRIVILEGES;

-- 退出MySQL
EXIT;
```

### 3.2 运行数据库迁移
```bash
# 进入项目目录
cd /var/www/usdt-payment

# 运行Phinx迁移
./vendor/bin/phinx migrate -e production

# 运行数据填充
./vendor/bin/phinx seed:run -e production
```

## 4. Nginx配置

### 4.1 创建Nginx配置文件
```bash
sudo nano /etc/nginx/sites-available/usdt-payment
```

配置内容：
```nginx
server {
    listen 80;
    server_name your-domain.com;  # 替换为您的域名
    root /var/www/usdt-payment/public;
    index index.php index.html;

    # 日志配置
    access_log /var/log/nginx/usdt-payment.access.log;
    error_log /var/log/nginx/usdt-payment.error.log;

    # 静态文件处理
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # PHP处理
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;  # Ubuntu/Debian
        # fastcgi_pass 127.0.0.1:9000;                   # CentOS/RHEL
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # 安全配置
        fastcgi_param PHP_VALUE "open_basedir=$document_root:/tmp/:/var/tmp/";
        fastcgi_read_timeout 300;
    }

    # 默认路由处理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 安全配置
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ /(config|runtime|vendor|database)/ {
        deny all;
        access_log off;
        log_not_found off;
    }
}
```

### 4.2 启用站点配置
```bash
# 启用站点
sudo ln -s /etc/nginx/sites-available/usdt-payment /etc/nginx/sites-enabled/

# 测试Nginx配置
sudo nginx -t

# 重载Nginx配置
sudo systemctl reload nginx
```

## 5. PHP-FPM配置优化

### 5.1 编辑PHP-FPM池配置
```bash
sudo nano /etc/php/8.1/fpm/pool.d/usdt-payment.conf
```

配置内容：
```ini
[usdt-payment]
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm-usdt.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000

; 环境变量
env[PATH] = /usr/local/bin:/usr/bin:/bin
env[TMP] = /tmp
env[TMPDIR] = /tmp
env[TEMP] = /tmp

; PHP配置
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 300
php_admin_value[upload_max_filesize] = 50M
php_admin_value[post_max_size] = 50M
```

### 5.2 重启PHP-FPM
```bash
sudo systemctl restart php8.1-fpm
```

## 6. 启动Webman服务

### 6.1 创建系统服务
```bash
sudo nano /etc/systemd/system/webman-usdt.service
```

服务配置：
```ini
[Unit]
Description=Webman USDT Payment Service
After=network.target mysql.service redis.service
Wants=network.target

[Service]
Type=forking
User=www-data
Group=www-data
WorkingDirectory=/var/www/usdt-payment
ExecStart=/usr/bin/php /var/www/usdt-payment/start.php start -d
ExecReload=/usr/bin/php /var/www/usdt-payment/start.php reload
ExecStop=/usr/bin/php /var/www/usdt-payment/start.php stop
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### 6.2 启动服务
```bash
# 重载systemd配置
sudo systemctl daemon-reload

# 启动服务
sudo systemctl start webman-usdt
sudo systemctl enable webman-usdt

# 检查服务状态
sudo systemctl status webman-usdt
```

## 7. SSL证书配置（推荐）

### 7.1 安装Certbot
```bash
# Ubuntu/Debian
sudo apt install certbot python3-certbot-nginx -y

# CentOS/RHEL
sudo yum install certbot python3-certbot-nginx -y
```

### 7.2 获取SSL证书
```bash
sudo certbot --nginx -d your-domain.com
```

## 8. 防火墙配置

### 8.1 配置UFW (Ubuntu/Debian)
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8787/tcp  # Webman端口
sudo ufw --force enable
```

### 8.2 配置Firewalld (CentOS/RHEL)
```bash
sudo firewall-cmd --permanent --add-port=22/tcp
sudo firewall-cmd --permanent --add-port=80/tcp
sudo firewall-cmd --permanent --add-port=443/tcp
sudo firewall-cmd --permanent --add-port=8787/tcp
sudo firewall-cmd --reload
```

## 9. 监控和日志

### 9.1 设置日志轮转
```bash
sudo nano /etc/logrotate.d/usdt-payment
```

配置内容：
```
/var/www/usdt-payment/runtime/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload webman-usdt
    endscript
}
```

### 9.2 设置监控脚本
```bash
nano /usr/local/bin/check-usdt-payment.sh
```

脚本内容：
```bash
#!/bin/bash

# 检查Webman服务
if ! systemctl is-active --quiet webman-usdt; then
    echo "$(date): Webman service is down, restarting..." >> /var/log/usdt-payment-monitor.log
    systemctl restart webman-usdt
fi

# 检查MySQL连接
if ! mysqladmin ping -h localhost --silent; then
    echo "$(date): MySQL is down!" >> /var/log/usdt-payment-monitor.log
fi

# 检查Redis连接
if ! redis-cli ping > /dev/null 2>&1; then
    echo "$(date): Redis is down!" >> /var/log/usdt-payment-monitor.log
fi
```

```bash
chmod +x /usr/local/bin/check-usdt-payment.sh

# 添加到crontab
echo "*/5 * * * * /usr/local/bin/check-usdt-payment.sh" | sudo crontab -
```

## 10. 性能优化

### 10.1 MySQL优化
```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

添加配置：
```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 128M
query_cache_type = 1
max_connections = 200
```

### 10.2 Redis优化
```bash
sudo nano /etc/redis/redis.conf
```

修改配置：
```
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### 10.3 重启服务
```bash
sudo systemctl restart mysql
sudo systemctl restart redis
sudo systemctl restart webman-usdt
```

## 11. 验证部署

### 11.1 检查服务状态
```bash
# 检查所有服务
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
sudo systemctl status mysql
sudo systemctl status redis
sudo systemctl status webman-usdt

# 检查端口监听
sudo netstat -tlnp | grep -E ':(80|443|3306|6379|8787)'
```

### 11.2 访问测试
```bash
# 测试网站访问
curl -I http://your-domain.com

# 测试API接口
curl -X POST http://your-domain.com/api/v1/order/create-transaction \
  -H "Content-Type: application/json" \
  -d '{"order_id":"test123","amount":100,"notify_url":"http://example.com/callback"}'
```

## 12. 常见问题排查

### 12.1 权限问题
```bash
# 重新设置权限
sudo chown -R www-data:www-data /var/www/usdt-payment
sudo chmod -R 755 /var/www/usdt-payment
sudo chmod -R 777 /var/www/usdt-payment/runtime
```

### 12.2 日志查看
```bash
# 查看Webman日志
tail -f /var/www/usdt-payment/runtime/logs/workerman.log

# 查看Nginx日志
tail -f /var/log/nginx/usdt-payment.error.log

# 查看PHP-FPM日志
tail -f /var/log/php8.1-fpm.log
```

### 12.3 重启所有服务
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo systemctl restart mysql
sudo systemctl restart redis
sudo systemctl restart webman-usdt
```

## 13. 备份策略

### 13.1 数据库备份脚本
```bash
nano /usr/local/bin/backup-usdt-db.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/backup/usdt-payment"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

mysqldump -u usdt_user -p'your_password' usdt_payment > $BACKUP_DIR/usdt_payment_$DATE.sql

# 保留最近30天的备份
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
```

### 13.2 设置自动备份
```bash
chmod +x /usr/local/bin/backup-usdt-db.sh
echo "0 2 * * * /usr/local/bin/backup-usdt-db.sh" | sudo crontab -
```

部署完成后，您的USDT支付系统应该可以正常运行了！
