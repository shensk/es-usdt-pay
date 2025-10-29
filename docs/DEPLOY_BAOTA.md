# USDT支付系统 - 宝塔面板部署文档

## 系统要求

### 服务器配置
- **操作系统**: CentOS 7+ / Ubuntu 18.04+ / Debian 9+
- **内存**: 最低 2GB RAM (推荐 4GB+)
- **存储**: 最低 20GB 可用空间
- **带宽**: 建议 5Mbps+

### 宝塔面板版本
- **宝塔Linux面板**: 7.7.0+
- **推荐版本**: 最新正式版

## 1. 安装宝塔面板

### 1.1 一键安装脚本

**CentOS安装命令：**
```bash
yum install -y wget && wget -O install.sh http://download.bt.cn/install/install_6.0.sh && sh install.sh ed8484bec
```

**Ubuntu/Debian安装命令：**
```bash
wget -O install.sh http://download.bt.cn/install/install-ubuntu_6.0.sh && sudo bash install.sh ed8484bec
```

### 1.2 安装完成后
安装完成后会显示：
- 宝塔面板地址：http://your-server-ip:8888
- 用户名：随机生成
- 密码：随机生成

**首次登录后请立即修改默认用户名和密码！**

## 2. 安装运行环境

### 2.1 一键部署LNMP环境

登录宝塔面板后，点击"软件商店"，选择"一键部署"：

**推荐配置：**
- **Nginx**: 1.20+ (选择最新稳定版)
- **MySQL**: 5.7 或 8.0 (推荐8.0)
- **PHP**: 8.1 (必须选择8.1版本)
- **Redis**: 最新版本
- **phpMyAdmin**: 最新版本 (可选)

### 2.2 PHP扩展安装

点击"软件商店" → "PHP 8.1" → "设置" → "安装扩展"

**必须安装的扩展：**
- ✅ opcache (性能优化)
- ✅ redis (缓存支持)
- ✅ mysqli (数据库连接)
- ✅ pdo_mysql (数据库PDO)
- ✅ curl (HTTP请求)
- ✅ json (JSON处理)
- ✅ mbstring (多字节字符串)
- ✅ xml (XML处理)
- ✅ zip (压缩文件)
- ✅ gd (图像处理)
- ✅ bcmath (高精度数学)
- ✅ fileinfo (文件信息)

### 2.3 PHP配置优化

点击"PHP 8.1" → "设置" → "配置修改"，修改以下参数：

```ini
; 基础配置
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 50M
upload_max_filesize = 50M

; 错误报告 (生产环境)
display_errors = Off
log_errors = On
error_log = /www/wwwlogs/php_errors.log

; 会话配置
session.gc_maxlifetime = 7200
session.cookie_lifetime = 7200

; OPcache配置
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
```

## 3. 创建网站

### 3.1 添加站点

1. 点击"网站" → "添加站点"
2. 填写配置信息：
   - **域名**: your-domain.com (替换为您的域名)
   - **根目录**: /www/wwwroot/usdt-payment
   - **FTP**: 不创建
   - **数据库**: MySQL (记住数据库名、用户名、密码)
   - **PHP版本**: PHP-81

### 3.2 SSL证书配置（推荐）

1. 点击站点名称 → "SSL"
2. 选择"Let's Encrypt"免费证书
3. 填写域名，点击"申请"
4. 申请成功后，开启"强制HTTPS"

### 3.3 网站目录权限设置

1. 点击站点名称 → "网站目录"
2. 设置运行目录为：`/public` (重要！)
3. 开启"防跨站攻击"
4. 关闭"目录浏览"

## 4. 上传项目代码

### 4.1 方式一：在线文件管理器

1. 点击"文件" → 进入 `/www/wwwroot/usdt-payment`
2. 删除默认的 `index.html` 等文件
3. 上传项目压缩包
4. 右键解压到当前目录

### 4.2 方式二：FTP上传

1. 点击"FTP" → "添加FTP"
2. 创建FTP账户
3. 使用FTP客户端上传代码到网站根目录

### 4.3 方式三：Git部署（推荐）

在宝塔终端中执行：
```bash
cd /www/wwwroot/usdt-payment
git clone https://github.com/your-repo/webman-usdt-payment.git .
```

## 5. 安装项目依赖

### 5.1 安装Composer

在宝塔面板"终端"中执行：
```bash
# 下载Composer
curl -sS https://getcomposer.org/installer | php

# 移动到全局目录
mv composer.phar /usr/local/bin/composer

# 设置中国镜像（加速下载）
composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
```

### 5.2 安装项目依赖

```bash
cd /www/wwwroot/usdt-payment
composer install --no-dev --optimize-autoloader
```

### 5.3 设置目录权限

```bash
# 设置所有者
chown -R www:www /www/wwwroot/usdt-payment

# 设置基础权限
chmod -R 755 /www/wwwroot/usdt-payment

# 设置可写目录权限
chmod -R 777 /www/wwwroot/usdt-payment/runtime
chmod -R 777 /www/wwwroot/usdt-payment/public
```

## 6. 数据库配置

### 6.1 创建数据库

1. 点击"数据库" → "添加数据库"
2. 填写信息：
   - **数据库名**: usdt_payment
   - **用户名**: usdt_user (或使用已有的)
   - **密码**: 设置强密码
   - **访问权限**: 本地服务器

### 6.2 配置环境文件

在文件管理器中编辑 `.env` 文件：

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
DB_PASSWORD=your_database_password

# Redis配置
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

# 支付配置
PAYMENT_TIMEOUT=1800
API_AUTH_TOKEN=your_api_secret_key_here_change_this
COINMARKETCAP_API_KEY=your_coinmarketcap_api_key_optional
```

### 6.3 运行数据库迁移

在宝塔终端中执行：
```bash
cd /www/wwwroot/usdt-payment

# 运行数据库迁移
./vendor/bin/phinx migrate -e production

# 运行数据填充
./vendor/bin/phinx seed:run -e production
```

## 7. Nginx配置优化

### 7.1 修改Nginx配置

1. 点击站点名称 → "配置文件"
2. 替换为以下配置：

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name your-domain.com;  # 替换为您的域名
    index index.php index.html index.htm default.php default.htm default.html;
    root /www/wwwroot/usdt-payment/public;

    # SSL证书配置 (如果启用了SSL)
    ssl_certificate /www/server/panel/vhost/cert/your-domain.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.1 TLSv1.2 TLSv1.3;
    ssl_ciphers EECDH+CHACHA20:EECDH+CHACHA20-draft:EECDH+AES128:RSA+AES128:EECDH+AES256:RSA+AES256:EECDH+3DES:RSA+3DES:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    add_header Strict-Transport-Security "max-age=31536000";

    # 防止访问隐藏文件
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # 防止访问敏感目录
    location ~ /(config|runtime|vendor|database|docs)/ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # 静态文件缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # PHP处理
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/tmp/php-cgi-81.sock;
        fastcgi_index index.php;
        include fastcgi.conf;
        include pathinfo.conf;
        
        # 安全配置
        fastcgi_param PHP_VALUE "open_basedir=$document_root:/tmp/:/proc/:/www/server/php/";
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }

    # 默认路由处理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 访问日志
    access_log /www/wwwlogs/usdt-payment.log;
    error_log /www/wwwlogs/usdt-payment.error.log;
}
```

## 8. 启动Webman服务

### 8.1 创建启动脚本

在文件管理器中创建 `/www/wwwroot/usdt-payment/start.sh`：

```bash
#!/bin/bash
cd /www/wwwroot/usdt-payment
php start.php start -d
```

设置执行权限：
```bash
chmod +x /www/wwwroot/usdt-payment/start.sh
```

### 8.2 使用宝塔进程守护

1. 点击"软件商店" → 搜索"进程守护器" → 安装
2. 安装完成后，点击"设置"
3. 添加守护进程：
   - **名称**: webman-usdt
   - **启动文件**: `/www/wwwroot/usdt-payment/start.sh`
   - **运行目录**: `/www/wwwroot/usdt-payment`
   - **运行用户**: www

### 8.3 手动启动服务

也可以在终端中手动启动：
```bash
cd /www/wwwroot/usdt-payment
php start.php start -d

# 查看状态
php start.php status

# 重启服务
php start.php restart

# 停止服务
php start.php stop
```

## 9. 安全配置

### 9.1 宝塔面板安全设置

1. **修改面板端口**：
   - 点击"面板设置" → "安全设置"
   - 修改面板端口（默认8888）
   - 设置授权IP（仅允许特定IP访问）

2. **开启BasicAuth**：
   - 设置访问用户名和密码
   - 增加一层安全防护

3. **开启面板SSL**：
   - 申请面板SSL证书
   - 启用HTTPS访问

### 9.2 服务器安全设置

1. **防火墙配置**：
   - 点击"安全" → "防火墙"
   - 开放端口：22, 80, 443, 8787
   - 关闭不必要的端口

2. **SSH安全**：
   - 修改SSH默认端口22
   - 禁用root登录
   - 使用密钥登录

### 9.3 网站安全设置

1. **开启网站防火墙**：
   - 点击站点名称 → "安全"
   - 开启"网站防火墙"
   - 配置CC防护、SQL注入防护等

2. **设置IP黑白名单**：
   - 根据需要设置访问限制
   - 可以限制管理后台访问IP

## 10. 性能优化

### 10.1 MySQL优化

1. 点击"数据库" → "MySQL" → "性能调整"
2. 根据服务器配置选择优化方案
3. 或手动编辑 `/etc/my.cnf`：

```ini
[mysqld]
# 基础配置
max_connections = 200
max_connect_errors = 6000
open_files_limit = 65535
table_open_cache = 128

# InnoDB配置
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2
innodb_lock_wait_timeout = 120

# 查询缓存
query_cache_size = 128M
query_cache_type = 1
```

### 10.2 Redis优化

1. 点击"数据库" → "Redis" → "配置修改"
2. 修改配置：

```
# 内存配置
maxmemory 512mb
maxmemory-policy allkeys-lru

# 持久化配置
save 900 1
save 300 10
save 60 10000

# 网络配置
timeout 300
tcp-keepalive 300
```

### 10.3 PHP性能优化

在PHP配置中启用OPcache：
```ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

## 11. 监控和维护

### 11.1 宝塔监控

1. **系统监控**：
   - 点击"监控" → 查看CPU、内存、磁盘使用情况
   - 设置告警阈值

2. **网站监控**：
   - 监控网站访问状态
   - 设置异常告警

### 11.2 日志管理

1. **网站日志**：
   - 位置：`/www/wwwlogs/`
   - 定期清理旧日志

2. **应用日志**：
   - 位置：`/www/wwwroot/usdt-payment/runtime/logs/`
   - 监控错误日志

### 11.3 定时任务

点击"计划任务" → "添加任务"：

**1. 数据库备份**：
- 任务类型：备份数据库
- 执行周期：每天凌晨2点
- 备份到：本地备份

**2. 网站文件备份**：
- 任务类型：备份网站
- 执行周期：每周一次
- 备份目录：`/www/wwwroot/usdt-payment`

**3. 日志清理**：
- 任务类型：Shell脚本
- 执行周期：每天
- 脚本内容：
```bash
find /www/wwwlogs/ -name "*.log" -mtime +30 -delete
find /www/wwwroot/usdt-payment/runtime/logs/ -name "*.log" -mtime +7 -delete
```

## 12. 故障排查

### 12.1 常见问题

**1. 网站无法访问**：
- 检查Nginx是否启动
- 检查域名解析是否正确
- 查看Nginx错误日志

**2. PHP报错**：
- 检查PHP版本是否为8.1
- 检查必要扩展是否安装
- 查看PHP错误日志

**3. 数据库连接失败**：
- 检查MySQL是否启动
- 验证数据库配置信息
- 检查数据库用户权限

**4. Webman服务异常**：
- 检查进程守护器状态
- 查看应用日志
- 重启Webman服务

### 12.2 日志查看

**宝塔面板日志**：
```bash
tail -f /www/server/panel/logs/error.log
```

**Nginx日志**：
```bash
tail -f /www/wwwlogs/usdt-payment.error.log
```

**PHP日志**：
```bash
tail -f /www/wwwlogs/php_errors.log
```

**应用日志**：
```bash
tail -f /www/wwwroot/usdt-payment/runtime/logs/workerman.log
```

## 13. 验证部署

### 13.1 访问测试

1. **前台访问**：
   - 访问：`https://your-domain.com`
   - 应该看到支付页面或API文档

2. **管理后台**：
   - 访问：`https://your-domain.com/app/admin`
   - 使用默认账号登录：admin/123456

3. **API测试**：
   - 访问：`https://your-domain.com/demo`
   - 测试创建订单等功能

### 13.2 功能验证

1. **创建测试订单**
2. **检查数据库记录**
3. **验证支付页面显示**
4. **测试管理后台功能**

## 14. 备份和恢复

### 14.1 完整备份

使用宝塔面板的一键备份功能：
1. 点击"计划任务" → "添加任务"
2. 选择"备份网站"和"备份数据库"
3. 设置备份到云存储（推荐）

### 14.2 手动备份

**备份数据库**：
```bash
mysqldump -u usdt_user -p usdt_payment > /www/backup/usdt_payment_$(date +%Y%m%d).sql
```

**备份网站文件**：
```bash
tar -czf /www/backup/usdt_payment_$(date +%Y%m%d).tar.gz /www/wwwroot/usdt-payment
```

### 14.3 恢复数据

**恢复数据库**：
```bash
mysql -u usdt_user -p usdt_payment < /www/backup/usdt_payment_20231201.sql
```

**恢复网站文件**：
```bash
tar -xzf /www/backup/usdt_payment_20231201.tar.gz -C /
```

## 15. 升级维护

### 15.1 系统更新

定期更新系统和软件：
1. 更新宝塔面板到最新版本
2. 更新PHP、MySQL、Nginx等组件
3. 更新项目代码

### 15.2 代码更新

```bash
cd /www/wwwroot/usdt-payment

# 备份当前版本
cp -r . ../usdt-payment-backup-$(date +%Y%m%d)

# 拉取最新代码
git pull origin main

# 更新依赖
composer install --no-dev --optimize-autoloader

# 运行数据库迁移
./vendor/bin/phinx migrate -e production

# 重启服务
php start.php restart
```

部署完成后，您的USDT支付系统就可以在宝塔环境中稳定运行了！

如有问题，请查看相关日志文件或联系技术支持。
