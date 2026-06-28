#!/bin/bash

# ========================================================
#   CLOUD CONFIG MANAGER PRO - ULTIMATE MASTER EDITION
#   [UNIVERSAL OS + SMART PHP + HEALTH MONITOR + BACKUP]
# ========================================================

CYAN='\033[0;36m'
LIGHT_CYAN='\033[1;36m'
GREEN='\033[0;32m'
LIGHT_GREEN='\033[1;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
WHITE='\033[1;37m'
DARK_GRAY='\033[1;30m'
NC='\033[0m'
BOLD='\033[1m'

WEB_ROOT="/var/www/netcloud"
DEFAULT_DOMAIN="cloud.maxssh.site"
DEFAULT_PORT=80
DEFAULT_TZ="Africa/Tunis"
LOG_FILE="/var/log/netcloud_manager.log"

LINK_INDEX="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/index.php"
LINK_ADMIN="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/admin.php"

if [ "$EUID" -ne 0 ]; then 
    echo -e "  ${RED}${BOLD}ROOT PRIVILEGES REQUIRED! PLEASE RUN WITH SUDO.${NC}"
    exit 1
fi

touch "$LOG_FILE" 2>/dev/null
chmod 666 "$LOG_FILE" 2>/dev/null

log_action() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] - $1" >> "$LOG_FILE"
}

# ==========================================
# 1. OS DETECTION ENGINE (دعم جميع الأنظمة)
# ==========================================
if [ -f /etc/os-release ]; then
    source /etc/os-release
    OS_ID=$ID
    OS_LIKE=$ID_LIKE
fi

if [[ "$OS_ID" == "ubuntu" || "$OS_ID" == "debian" || "$OS_LIKE" == *"ubuntu"* || "$OS_LIKE" == *"debian"* ]]; then
    OS_FAMILY="debian"
    WEB_USER="www-data"
    NGINX_CONF="/etc/nginx/sites-available/netcloud"
elif [[ "$OS_ID" == "centos" || "$OS_ID" == "almalinux" || "$OS_ID" == "rocky" || "$OS_ID" == "fedora" || "$OS_LIKE" == *"rhel"* || "$OS_LIKE" == *"fedora"* ]]; then
    OS_FAMILY="rhel"
    WEB_USER="nginx"
    NGINX_CONF="/etc/nginx/conf.d/netcloud.conf"
else
    echo -e "${RED}UNSUPPORTED OS! SYSTEM ABORTED.${NC}"
    exit 1
fi

check_port() {
    local port=$1
    local port_busy=false
    while true; do
        if (ss -tuln | grep -q ":$port ") || (netstat -tuln | grep -q ":$port ") 2>/dev/null; then
            port_busy=true
        else
            port_busy=false
        fi
        if [ "$port_busy" = true ]; then
            port=$((port+1))
        else
            break
        fi
    done
    echo $port
}

install_netcloud() {
    clear
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${WHITE}${BOLD}INITIATING CLOUD CONFIG SECURE DEPLOYMENT (MASTER EDITION)...${NC}"
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n${NC}"
    
    echo -ne "  ${CYAN}ENTER DOMAIN  : ${WHITE}"
    read DOMAIN
    DOMAIN=${DOMAIN:-$DEFAULT_DOMAIN}

    echo -ne "  ${CYAN}ENTER PORT  : ${WHITE}"
    read PORT_INPUT
    PORT_INPUT=${PORT_INPUT:-$DEFAULT_PORT}
    PORT=$(check_port $PORT_INPUT)

    echo -ne "  ${CYAN}ENTER TIMEZONE  : ${WHITE}"
    read TZ_INPUT
    TZ_INPUT=${TZ_INPUT:-$DEFAULT_TZ}

    if [ "$PORT" == "443" ]; then
        echo -ne "  ${CYAN}ACTIVATE SSL HTTPS CERTIFICATE? (Y/N)  : ${WHITE}"
        read SSL_CHOICE
    else
        SSL_CHOICE="N"
    fi

    echo -e "\n  ${DARK_GRAY}[1/6]${NC} ${CYAN}UPDATING PACKAGES & INSTALLING CORE SERVICES...${NC}"
    
    # تثبيت الحزم المناسبة للنظام بذكاء
    if [ "$OS_FAMILY" == "debian" ]; then
        export DEBIAN_FRONTEND=noninteractive
        apt-get update -yq > /dev/null 2>&1
        apt-get install nginx php-fpm php-curl ufw iproute2 certbot python3-certbot-nginx -yq > /dev/null 2>&1
        if [ ! -d "/etc/nginx/sites-available" ]; then
            mkdir -p /etc/nginx/sites-available
            mkdir -p /etc/nginx/sites-enabled
        fi
    elif [ "$OS_FAMILY" == "rhel" ]; then
        if command -v dnf >/dev/null 2>&1; then PKGMGR="dnf"; else PKGMGR="yum"; fi
        $PKGMGR install epel-release -yq > /dev/null 2>&1
        $PKGMGR install nginx php-fpm php-cli php-curl firewalld iproute certbot python3-certbot-nginx wget nano net-tools procps-ng -yq > /dev/null 2>&1
        systemctl enable firewalld --now > /dev/null 2>&1
    fi

    # اكتشاف مسار PHP الذكي لتجنب أخطاء النسخ
    if [ "$OS_FAMILY" == "debian" ]; then
        PHP_SOCK_PATH=$(find /run/php/ -name "*.sock" | head -n 1)
        PHP_SVC=$(systemctl list-units --type=service | grep -o 'php[0-9.]*-fpm.service' | head -n 1)
    elif [ "$OS_FAMILY" == "rhel" ]; then
        PHP_SOCK_PATH=$(find /run/php-fpm/ -name "*.sock" | head -n 1)
        PHP_SVC="php-fpm.service"
    fi

    echo -e "  ${DARK_GRAY}[2/6]${NC} ${CYAN}BUILDING SYSTEM DIRECTORIES...${NC}"
    mkdir -p $WEB_ROOT/uploads
    chown -R $WEB_USER:$WEB_USER $WEB_ROOT

    echo -e "  ${DARK_GRAY}[3/6]${NC} ${CYAN}FETCHING CORE ENGINE FROM REPOSITORY...${NC}"
    wget -q --show-progress $LINK_INDEX -O $WEB_ROOT/index.php
    wget -q --show-progress $LINK_ADMIN -O $WEB_ROOT/admin.php

    echo -e "  ${DARK_GRAY}[4/6]${NC} ${CYAN}INJECTING TIMEZONE (${WHITE}$TZ_INPUT${CYAN}) INTO CORE...${NC}"
    sed -i "/date_default_timezone_set/d" $WEB_ROOT/index.php
    sed -i "/date_default_timezone_set/d" $WEB_ROOT/admin.php
    sed -i "s|session_start();|session_start();\ndate_default_timezone_set('$TZ_INPUT');|" $WEB_ROOT/index.php
    sed -i "s|session_start();|session_start();\ndate_default_timezone_set('$TZ_INPUT');|" $WEB_ROOT/admin.php
    chown $WEB_USER:$WEB_USER $WEB_ROOT/index.php $WEB_ROOT/admin.php

    echo -e "  ${DARK_GRAY}[5/6]${NC} ${CYAN}COMPILING NGINX SMART-FIREWALL CONFIG...${NC}"
    
    if [ "$PORT" == "443" ] && [[ "$SSL_CHOICE" == "y" || "$SSL_CHOICE" == "Y" ]]; then
        echo -e "\n  ${YELLOW}* ATTENTION: PORT 80 MUST BE FREE FOR 10 SECONDS TO VERIFY SSL *${NC}"
        echo -e "  ${DARK_GRAY}Please pause any WebSocket or Payload service running on port 80 temporarily.${NC}"
        echo -ne "  ${CYAN}PRESS [ENTER] WHEN PORT 80 IS READY >${NC} "
        read
        
        systemctl stop nginx
        certbot certonly --standalone -d $DOMAIN --non-interactive --agree-tos --register-unsafely-without-email
        
        cat <<EOF > $NGINX_CONF
server {
    listen 443 ssl http2;
    server_name $DOMAIN;
    root $WEB_ROOT;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / { try_files \$uri \$uri/ \$uri.php?\$query_string; }

    location ~* ^/([a-zA-Z0-9_-]+)\.hc\$ {
        if (\$http_user_agent ~* (curl|wget|python|Scrapy|libwww|HttpClient|Termux|WhatsApp|TelegramBot|facebookexternalhit|Slackbot|postman)) { return 403 "ACCESS DENIED"; }
        rewrite ^/([a-zA-Z0-9_-]+)\.hc\$ /index.php?c=\$1 last;
    }
    
    location ~ \.php\$ {
        fastcgi_pass unix:$PHP_SOCK_PATH;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ ^/(uploads|db\.json) { deny all; return 404; }
}
EOF
    else
        cat <<EOF > $NGINX_CONF
server {
    listen $PORT;
    server_name $DOMAIN;
    root $WEB_ROOT;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / { try_files \$uri \$uri/ \$uri.php?\$query_string; }

    location ~* ^/([a-zA-Z0-9_-]+)\.hc\$ {
        if (\$http_user_agent ~* (curl|wget|python|Scrapy|libwww|HttpClient|Termux|WhatsApp|TelegramBot|facebookexternalhit|Slackbot|postman)) { return 403 "ACCESS DENIED"; }
        rewrite ^/([a-zA-Z0-9_-]+)\.hc\$ /index.php?c=\$1 last;
    }
    
    location ~ \.php\$ {
        fastcgi_pass unix:$PHP_SOCK_PATH;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ ^/(uploads|db\.json) { deny all; return 404; }
}
EOF
    fi

    echo -e "  ${DARK_GRAY}[6/6]${NC} ${CYAN}APPLYING RULES & RESTARTING SERVICES...${NC}"
    if [ "$OS_FAMILY" == "debian" ]; then
        ln -sf $NGINX_CONF /etc/nginx/sites-enabled/ 2>/dev/null
        rm -f /etc/nginx/sites-enabled/default 2>/dev/null
        ufw allow $PORT/tcp > /dev/null 2>&1
    elif [ "$OS_FAMILY" == "rhel" ]; then
        firewall-cmd --add-port=$PORT/tcp --permanent > /dev/null 2>&1
        firewall-cmd --reload > /dev/null 2>&1
    fi
    
    systemctl restart nginx
    systemctl restart $PHP_SVC

    log_action "System installed on OS_FAMILY: $OS_FAMILY | Domain: $DOMAIN | Port: $PORT | PHP: $PHP_SVC"

    echo -e "\n${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${LIGHT_GREEN}SYSTEM DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -ne "\n  ${DARK_GRAY}PRESS [ENTER] TO RETURN TO THE MENU...${NC}"
    read
}

show_menu() {
    clear
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "                      ${WHITE}${BOLD}CLOUD CONFIG MANAGER PRO${NC}"
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
    
    echo -e "  ${DARK_GRAY}[${WHITE}01${DARK_GRAY}]${NC} ${CYAN}INSTALL SYSTEM CORE & SSL${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}02${DARK_GRAY}]${NC} ${CYAN}RECONFIGURE DOMAIN NAME${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}03${DARK_GRAY}]${NC} ${CYAN}RECONFIGURE PORT (AUTO-CHECK)${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}04${DARK_GRAY}]${NC} ${CYAN}START WEB SERVICE${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}05${DARK_GRAY}]${NC} ${CYAN}STOP WEB SERVICE${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}06${DARK_GRAY}]${NC} ${CYAN}RESTART ALL SERVICES${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}07${DARK_GRAY}]${NC} ${CYAN}EDIT CONFIGURATION FILES${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}08${DARK_GRAY}]${NC} ${CYAN}UPDATE SYSTEM TIMEZONE${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}09${DARK_GRAY}]${NC} ${RED}WIPE AND DESTROY SYSTEM${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}10${DARK_GRAY}]${NC} ${LIGHT_GREEN}SYSTEM HEALTH MONITOR${NC} ${YELLOW}[NEW]${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}11${DARK_GRAY}]${NC} ${LIGHT_GREEN}BACKUP DATABASE (db.json)${NC} ${YELLOW}[NEW]${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}00${DARK_GRAY}]${NC} ${CYAN}EXIT MANAGER\n${NC}"
    
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

read_choice() {
    echo -ne "\n  ${CYAN}SELECT MODULE [00-11] >${NC} ${WHITE}"
    read choice
    echo -e "${NC}"
    case $choice in
        1|01) install_netcloud ;;
        2|02) 
            echo -ne "  ${CYAN}ENTER NEW DOMAIN  : ${WHITE}"
            read NEW_DOMAIN
            if [ -f "$NGINX_CONF" ]; then
                sed -i "s/server_name .*/server_name $NEW_DOMAIN;/" $NGINX_CONF
                systemctl restart nginx
                log_action "Domain updated to: $NEW_DOMAIN"
                echo -e "  ${GREEN}DOMAIN UPDATED TO: ${WHITE}${NEW_DOMAIN}${NC}"
            else
                echo -e "  ${RED}SYSTEM IS NOT INSTALLED!${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}PRESS [ENTER] TO CONTINUE...${NC}"; read
            ;;
        3|03) 
            echo -ne "  ${CYAN}ENTER NEW PORT  : ${WHITE}"
            read NEW_PORT_INPUT
            NEW_PORT=$(check_port $NEW_PORT_INPUT)
            if [ -f "$NGINX_CONF" ]; then
                sed -i -E "s/listen [0-9]+;/listen $NEW_PORT;/" $NGINX_CONF
                if [ "$OS_FAMILY" == "debian" ] || [ -z "$OS_FAMILY" ]; then
                    ufw allow $NEW_PORT/tcp > /dev/null 2>&1
                else
                    firewall-cmd --add-port=$NEW_PORT/tcp --permanent > /dev/null 2>&1
                    firewall-cmd --reload > /dev/null 2>&1
                fi
                systemctl restart nginx
                log_action "Port updated to: $NEW_PORT"
                echo -e "  ${GREEN}PORT UPDATED TO: ${WHITE}${NEW_PORT}${NC}"
            else
                echo -e "  ${RED}SYSTEM IS NOT INSTALLED!${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}PRESS [ENTER] TO CONTINUE...${NC}"; read
            ;;
        4|04) systemctl start nginx; log_action "Nginx started."; echo -e "  ${GREEN}NGINX STARTED.${NC}"; echo -ne "\n  ${DARK_GRAY}PRESS [ENTER]...${NC}"; read ;;
        5|05) systemctl stop nginx; log_action "Nginx stopped."; echo -e "  ${YELLOW}NGINX STOPPED.${NC}"; echo -ne "\n  ${DARK_GRAY}PRESS [ENTER]...${NC}"; read ;;
        6|06) 
            systemctl restart nginx
            if [ "$OS_FAMILY" == "rhel" ]; then SVC="php-fpm.service"; else SVC=$(systemctl list-units --type=service | grep -o 'php[0-9.]*-fpm.service' | head -n 1); fi
            systemctl restart $SVC
            log_action "Services restarted."
            echo -e "  ${CYAN}SERVICES RESTARTED.${NC}"; echo -ne "\n  ${DARK_GRAY}PRESS [ENTER]...${NC}"; read ;;
        7|07) 
            if [ -d "$WEB_ROOT" ]; then nano $WEB_ROOT/admin.php; nano $WEB_ROOT/index.php; nano $NGINX_CONF; 
            else echo -e "  ${RED}SYSTEM IS NOT INSTALLED.${NC}"; echo -ne "\n  ${DARK_GRAY}PRESS [ENTER]...${NC}"; read; fi ;;
        8|08)
            echo -ne "  ${CYAN}ENTER TIMEZONE  : ${WHITE}"
            read TZ_INPUT
            TZ_INPUT=${TZ_INPUT:-Africa/Tunis}
            if [ -f "$WEB_ROOT/index.php" ]; then
                sed -i "/date_default_timezone_set/d" $WEB_ROOT/index.php
                sed -i "/date_default_timezone_set/d" $WEB_ROOT/admin.php
                sed -i "s|session_start();|session_start();\ndate_default_timezone_set('$TZ_INPUT');|" $WEB_ROOT/index.php
                sed -i "s|session_start();|session_start();\ndate_default_timezone_set('$TZ_INPUT');|" $WEB_ROOT/admin.php
                log_action "Timezone updated to: $TZ_INPUT"
                echo -e "  ${GREEN}TIMEZONE UPDATED TO ${WHITE}${TZ_INPUT}${NC}"
            else
                echo -e "  ${RED}SYSTEM IS NOT INSTALLED.${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}PRESS [ENTER] TO CONTINUE...${NC}"; read
            ;;
        9|09) 
            echo -ne "  ${RED}WIPE ENTIRE SYSTEM? (Y/N): ${WHITE}"
            read confirm
            if [[ "$confirm" == "y" || "$confirm" == "Y" ]]; then
                rm -rf $WEB_ROOT; rm -f $NGINX_CONF; rm -f /etc/nginx/sites-enabled/netcloud 2>/dev/null; systemctl restart nginx
                log_action "System wiped completely."
                echo -e "  ${GREEN}SYSTEM COMPLETELY REMOVED.${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}PRESS [ENTER]...${NC}"; read ;;
        10)
            echo -e "  ${LIGHT_CYAN}--- SYSTEM HEALTH MONITOR ---${NC}"
            echo -ne "  ${CYAN}CPU USAGE: ${WHITE}"
            top -bn1 | grep load | awk '{printf "%.2f%%\n", $(NF-2)}'
            echo -ne "  ${CYAN}RAM USAGE: ${WHITE}"
            free -m | awk 'NR==2{printf "%sMB / %sMB (%.2f%%)\n", $3,$2,$3*100/$2 }'
            echo -ne "  ${CYAN}DISK USAGE:${WHITE} "
            df -h | awk '$NF=="/"{printf "%s / %s (%s)\n", $3,$2,$5}'
            echo -e "  ${CYAN}SERVICES:${NC}"
            if systemctl is-active --quiet nginx; then echo -e "    - NGINX: ${GREEN}RUNNING${NC}"; else echo -e "    - NGINX: ${RED}STOPPED${NC}"; fi
            if [ "$OS_FAMILY" == "rhel" ]; then SVC="php-fpm.service"; else SVC=$(systemctl list-units --type=service | grep -o 'php[0-9.]*-fpm.service' | head -n 1); fi
            if systemctl is-active --quiet $SVC; then echo -e "    - PHP ($SVC): ${GREEN}RUNNING${NC}"; else echo -e "    - PHP: ${RED}STOPPED${NC}"; fi
            echo -ne "\n  ${DARK_GRAY}PRESS [ENTER] TO CONTINUE...${NC}"; read
            ;;
        11)
            if [ -f "$WEB_ROOT/db.json" ]; then
                BACKUP_NAME="/root/db_backup_$(date '+%Y%m%d_%H%M%S').json"
                cp "$WEB_ROOT/db.json" "$BACKUP_NAME"
                log_action "Database backup created: $BACKUP_NAME"
                echo -e "  ${GREEN}BACKUP SUCCESSFUL!${NC}"
                echo -e "  ${CYAN}SAVED TO: ${WHITE}$BACKUP_NAME${NC}"
            else
                echo -e "  ${RED}DATABASE (db.json) NOT FOUND!${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}PRESS [ENTER] TO CONTINUE...${NC}"; read
            ;;
        0|00) echo -e "  ${CYAN}TERMINATING SESSION. GOODBYE!${NC}\n"; exit 0 ;;
        *) echo -e "  ${RED}INVALID SELECTION!${NC}"; sleep 1 ;;
    esac
}

while true; do
    show_menu
    read_choice
done
