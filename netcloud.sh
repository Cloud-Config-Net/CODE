#!/bin/bash

# ========================================================
#   CLOUD CONFIG MANAGER PRO - SYSTEM INSTALLER (ULTIMATE)
#   [UNIVERSAL EDITION - DEBUG MODE]
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

LINK_INDEX="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/index.php"
LINK_ADMIN="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/admin.php"

# ==========================================
# --- OS DETECTION & VARIABLE ENGINE ---
# ==========================================
if [ -f /etc/os-release ]; then
    source /etc/os-release
    OS_ID=$ID
    OS_LIKE=$ID_LIKE
fi

if [[ "$OS_ID" == "ubuntu" || "$OS_ID" == "debian" || "$OS_LIKE" == *"ubuntu"* || "$OS_LIKE" == *"debian"* ]]; then
    OS_FAMILY="debian"
    WEB_USER="www-data"
    PHP_SVC="php8.2-fpm"
    PHP_SOCK="unix:/var/run/php/php8.2-fpm.sock"
    NGINX_CONF="/etc/nginx/sites-available/netcloud"
elif [[ "$OS_ID" == "centos" || "$OS_ID" == "almalinux" || "$OS_ID" == "rocky" || "$OS_ID" == "fedora" || "$OS_LIKE" == *"rhel"* || "$OS_LIKE" == *"fedora"* ]]; then
    OS_FAMILY="rhel"
    WEB_USER="nginx"
    PHP_SVC="php-fpm"
    PHP_SOCK="unix:/run/php-fpm/www.sock"
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
    echo -e "  ${WHITE}${BOLD}INITIATING CLOUD CONFIG SECURE DEPLOYMENT...${NC}"
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

    echo -e "\n  ${DARK_GRAY}[1/6]${NC} ${YELLOW}UPDATING PACKAGES & DEPENDENCIES (PLEASE WAIT AND WATCH FOR ERRORS)...${NC}\n"
    
    # === التغيير الأساسي هنا: إزالة كتم الصوت لإظهار سبب الفشل ===
    if [ "$OS_FAMILY" == "debian" ]; then
        export DEBIAN_FRONTEND=noninteractive
        apt-get update -y
        apt-get install software-properties-common -y
        LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php -y
        apt-get update -y
        # هذا هو الأمر الذي يفشل، الآن سنرى لماذا يفشل:
        apt-get install nginx php8.2-fpm php8.2-curl ufw iproute2 certbot python3-certbot-nginx -y
        
        if [ ! -d "/etc/nginx/sites-available" ]; then
            mkdir -p /etc/nginx/sites-available
            mkdir -p /etc/nginx/sites-enabled
        fi
    elif [ "$OS_FAMILY" == "rhel" ]; then
        if command -v dnf >/dev/null 2>&1; then PKGMGR="dnf"; else PKGMGR="yum"; fi
        $PKGMGR install epel-release -y
        $PKGMGR install nginx php-fpm php-cli php-curl firewalld iproute certbot python3-certbot-nginx wget nano net-tools procps-ng -y
        systemctl enable firewalld --now
    fi
    # ==============================================================

    echo -e "\n  ${DARK_GRAY}[2/6]${NC} ${CYAN}BUILDING SYSTEM DIRECTORIES...${NC}"
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
        
        echo -e "  ${CYAN}REQUESTING SSL CERTIFICATE FROM LET'S ENCRYPT FOR $DOMAIN...${NC}"
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

    location / {
        try_files \$uri \$uri/ \$uri.php?\$query_string;
    }

    location ~* ^/([a-zA-Z0-9_-]+)\.hc\$ {
        if (\$http_user_agent ~* (curl|wget|python|Scrapy|libwww|HttpClient|Termux|WhatsApp|TelegramBot|facebookexternalhit|Slackbot|postman)) {
            return 403 "ACCESS DENIED: AUTOMATED TOOLS ARE NOT ALLOWED";
        }
        rewrite ^/([a-zA-Z0-9_-]+)\.hc\$ /index.php?c=\$1 last;
    }
    
    location ~ \.php\$ {
        fastcgi_pass $PHP_SOCK;
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

    location / {
        try_files \$uri \$uri/ \$uri.php?\$query_string;
    }

    location ~* ^/([a-zA-Z0-9_-]+)\.hc\$ {
        if (\$http_user_agent ~* (curl|wget|python|Scrapy|libwww|HttpClient|Termux|WhatsApp|TelegramBot|facebookexternalhit|Slackbot|postman)) {
            return 403 "ACCESS DENIED: AUTOMATED TOOLS ARE NOT ALLOWED";
        }
        rewrite ^/([a-zA-Z0-9_-]+)\.hc\$ /index.php?c=\$1 last;
    }
    
    location ~ \.php\$ {
        fastcgi_pass $PHP_SOCK;
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
        ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
        rm -f /etc/nginx/sites-enabled/default
        ufw allow $PORT/tcp > /dev/null 2>&1
    elif [ "$OS_FAMILY" == "rhel" ]; then
        firewall-cmd --add-port=$PORT/tcp --permanent > /dev/null 2>&1
        firewall-cmd --reload > /dev/null 2>&1
    fi
    
    systemctl restart nginx
    systemctl restart $PHP_SVC

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
    echo -e "  ${DARK_GRAY}[${WHITE}09${DARK_GRAY}]${NC} ${RED}WIPE AND DESTROY SYSTEM${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}00${DARK_GRAY}]${NC} ${CYAN}EXIT MANAGER\n${NC}"
    
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

read_choice() {
    echo -ne "\n  ${CYAN}SELECT MODULE [00-09] >${NC} ${WHITE}"
    read choice
    echo -e "${NC}"
    case $choice in
        1|01) install_netcloud ;;
        9|09) 
            echo -ne "  ${RED}WIPE ENTIRE SYSTEM? (Y/N): ${WHITE}"
            read confirm
            if [[ "$confirm" == "y" || "$confirm" == "Y" ]]; then
                rm -rf $WEB_ROOT; rm -f $NGINX_CONF; rm -f /etc/nginx/sites-enabled/netcloud 2>/dev/null; systemctl restart nginx
                echo -e "  ${GREEN}SYSTEM COMPLETELY REMOVED.${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}PRESS [ENTER]...${NC}"; read ;;
        0|00) exit 0 ;;
        *) echo -e "  ${RED}INVALID SELECTION!${NC}"; sleep 1 ;;
    esac
}

if [ "$EUID" -ne 0 ]; then 
    echo -e "  ${RED}${BOLD}ROOT PRIVILEGES REQUIRED! PLEASE RUN WITH SUDO.${NC}"
    exit 1
fi

while true; do
    show_menu
    read_choice
done
