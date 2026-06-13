#!/bin/bash

# ========================================================
#   NET-CLOUD MANAGER (ENHANCED V3.0)
#   RADAR LINK PRO - SYSTEM INSTALLER
# ========================================================

# --- Modern Colors & Styling ---
CYAN='\033[0;36m'
LIGHT_CYAN='\033[1;36m'
GREEN='\033[0;32m'
LIGHT_GREEN='\033[1;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
WHITE='\033[1;37m'
DARK_GRAY='\033[1;30m'
NC='\033[0m' # No Color
BOLD='\033[1m'

# --- Default Paths & Settings ---
WEB_ROOT="/var/www/netcloud"
NGINX_CONF="/etc/nginx/sites-available/netcloud"
DEFAULT_DOMAIN="cloud.maxssh.site"
DEFAULT_PORT=80
DEFAULT_TZ="Africa/Tunis"

# Place your GitHub Raw file links here:
LINK_INDEX="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/index.php"
LINK_ADMIN="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/admin.php"

# ==========================================
# Helper: Check & Auto-Select Port (Safe Version)
# ==========================================
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
            if [ "$port" == "$1" ]; then
                echo -e "  ${YELLOW}⚠️  Warning: Port $port is busy. ${DARK_GRAY}Scanning for alternative...${NC}"
            fi
            port=$((port+1))
        else
            break
        fi
    done
    
    if [ "$port" != "$1" ]; then
        echo -e "  ${GREEN}✅ Smart-Assigned Port: ${WHITE}$port${NC}"
    fi
    echo $port
}

# ==========================================
# Install Function
# ==========================================
install_netcloud() {
    clear
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${WHITE}${BOLD}🚀 INITIATING NET-CLOUD CORE DEPLOYMENT...${NC}"
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n${NC}"
    
    echo -ne "  ${CYAN}[?]${NC} 🌐 Enter Domain (Default: ${WHITE}${DEFAULT_DOMAIN}${NC}): "
    read DOMAIN
    DOMAIN=${DOMAIN:-$DEFAULT_DOMAIN}

    echo -ne "  ${CYAN}[?]${NC} 🔌 Enter Port (Default: ${WHITE}${DEFAULT_PORT}${NC}): "
    read PORT_INPUT
    PORT_INPUT=${PORT_INPUT:-$DEFAULT_PORT}
    PORT=$(check_port $PORT_INPUT)

    echo -ne "  ${CYAN}[?]${NC} 🌍 Enter Timezone (Default: ${WHITE}${DEFAULT_TZ}${NC}): "
    read TZ_INPUT
    TZ_INPUT=${TZ_INPUT:-$DEFAULT_TZ}

    echo -e "\n  ${DARK_GRAY}[1/6]${NC} ${CYAN}🔄 Updating packages & dependencies...${NC}"
    apt update && apt install nginx php8.2-fpm php8.2-curl ufw iproute2 -y > /dev/null 2>&1

    echo -e "  ${DARK_GRAY}[2/6]${NC} ${CYAN}📁 Building system directories...${NC}"
    mkdir -p $WEB_ROOT/uploads
    chown -R www-data:www-data $WEB_ROOT

    echo -e "  ${DARK_GRAY}[3/6]${NC} ${CYAN}⬇️ Fetching core engine from repository...${NC}"
    wget -q --show-progress $LINK_INDEX -O $WEB_ROOT/index.php
    wget -q --show-progress $LINK_ADMIN -O $WEB_ROOT/admin.php

    echo -e "  ${DARK_GRAY}[4/6]${NC} ${CYAN}⏱️ Injecting Timezone (${WHITE}$TZ_INPUT${CYAN}) into core...${NC}"
    sed -i "/date_default_timezone_set/d" $WEB_ROOT/index.php
    sed -i "/date_default_timezone_set/d" $WEB_ROOT/admin.php
    sed -i "s/session_start();/session_start();\ndate_default_timezone_set('$TZ_INPUT');/" $WEB_ROOT/index.php
    sed -i "s/session_start();/session_start();\ndate_default_timezone_set('$TZ_INPUT');/" $WEB_ROOT/admin.php
    chown www-data:www-data $WEB_ROOT/index.php $WEB_ROOT/admin.php

    echo -e "  ${DARK_GRAY}[5/6]${NC} ${CYAN}⚙️ Compiling Nginx Smart-Firewall config...${NC}"
    cat <<EOF > $NGINX_CONF
server {
    listen $PORT;
    server_name $DOMAIN;
    root $WEB_ROOT;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Smart Download Firewall
    location ~* ^/([a-zA-Z0-9_-]+)\.hc\$ {
        if (\$http_user_agent ~* (WhatsApp|TelegramBot|facebookexternalhit|Twitterbot|Slackbot)) {
            return 200 "NetCloud Preview Blocked Safely";
        }
        rewrite ^/([a-zA-Z0-9_-]+)\.hc\$ /index.php?c=\$1 last;
    }
    
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location /db.json { deny all; return 404; }
    location /uploads/ { deny all; return 404; }
}
EOF

    echo -e "  ${DARK_GRAY}[6/6]${NC} ${CYAN}🔗 Applying rules & restarting services...${NC}"
    ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    ufw allow $PORT/tcp > /dev/null 2>&1
    
    systemctl restart nginx
    systemctl restart php8.2-fpm

    echo -e "\n${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${LIGHT_GREEN}✔️ SYSTEM DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  🌐 Target Domain : ${WHITE}${DOMAIN}${NC}"
    echo -e "  🔌 Active Port   : ${WHITE}${PORT}${NC}"
    echo -e "  ⏱️ Timezone      : ${WHITE}${TZ_INPUT}${NC}"
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -ne "\n  ${DARK_GRAY}Press [ENTER] to return to the menu...${NC}"
    read
}

# ==========================================
# Display Menu Function
# ==========================================
show_menu() {
    clear
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "             ${WHITE}${BOLD}NET-CLOUD MANAGER ${LIGHT_CYAN}PRO${NC}"
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
    
    echo -e "  ${DARK_GRAY}[${WHITE}01${DARK_GRAY}]${NC} ${CYAN}🚀 INSTALL NET-CLOUD SETUP${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}02${DARK_GRAY}]${NC} ${CYAN}🌐 RE-CONFIGURE DOMAIN${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}03${DARK_GRAY}]${NC} ${CYAN}🔌 RE-CONFIGURE PORT (AUTO-CHECK)${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}04${DARK_GRAY}]${NC} ${CYAN}▶️  START NGINX SERVICE${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}05${DARK_GRAY}]${NC} ${CYAN}🛑 STOP NGINX SERVICE${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}06${DARK_GRAY}]${NC} ${CYAN}🔄 RESTART NGINX & PHP${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}07${DARK_GRAY}]${NC} ${CYAN}📝 EDIT FILES SITE${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}08${DARK_GRAY}]${NC} ${CYAN}🌍 SET/UPDATE TIMEZONE${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}09${DARK_GRAY}]${NC} ${RED}⚠️  TERMINATE & WIPE DATA${NC}"
    echo -e "  ${DARK_GRAY}[${WHITE}00${DARK_GRAY}]${NC} ${CYAN}🚪 EXIT\n${NC}"
    
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# ==========================================
# Read Choice Function
# ==========================================
read_choice() {
    echo -ne "\n  ${CYAN}SELECT MODULE [00-09] >${NC} ${WHITE}"
    read choice
    echo -e "${NC}"
    case $choice in
        1|01) install_netcloud ;;
        2|02) 
            echo -ne "  ${CYAN}[?]${NC} Enter New Domain: ${WHITE}"
            read NEW_DOMAIN
            if [ -f "$NGINX_CONF" ]; then
                sed -i "s/server_name .*/server_name $NEW_DOMAIN;/" $NGINX_CONF
                systemctl restart nginx
                echo -e "  ${GREEN}✔️ Domain updated to: ${WHITE}${NEW_DOMAIN}${NC}"
            else
                echo -e "  ${RED}❌ System is not installed!${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}Press [ENTER] to continue...${NC}"; read
            ;;
        3|03) 
            echo -ne "  ${CYAN}[?]${NC} Enter New Port: ${WHITE}"
            read NEW_PORT_INPUT
            NEW_PORT=$(check_port $NEW_PORT_INPUT)
            if [ -f "$NGINX_CONF" ]; then
                sed -i -E "s/listen [0-9]+;/listen $NEW_PORT;/" $NGINX_CONF
                ufw allow $NEW_PORT/tcp > /dev/null 2>&1
                systemctl restart nginx
                echo -e "  ${GREEN}✔️ Port updated to: ${WHITE}${NEW_PORT}${NC}"
            else
                echo -e "  ${RED}❌ System is not installed!${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}Press [ENTER] to continue...${NC}"; read
            ;;
        4|04) systemctl start nginx; echo -e "  ${GREEN}▶️  Nginx Started.${NC}"; echo -ne "\n  ${DARK_GRAY}Press [ENTER]...${NC}"; read ;;
        5|05) systemctl stop nginx; echo -e "  ${YELLOW}🛑 Nginx Stopped.${NC}"; echo -ne "\n  ${DARK_GRAY}Press [ENTER]...${NC}"; read ;;
        6|06) systemctl restart nginx; systemctl restart php8.2-fpm; echo -e "  ${CYAN}🔄 Services Restarted.${NC}"; echo -ne "\n  ${DARK_GRAY}Press [ENTER]...${NC}"; read ;;
        7|07) 
            if [ -d "$WEB_ROOT" ]; then nano $WEB_ROOT/admin.php; nano $WEB_ROOT/index.php; nano $NGINX_CONF; 
            else echo -e "  ${RED}❌ Not installed.${NC}"; echo -ne "\n  ${DARK_GRAY}Press [ENTER]...${NC}"; read; fi ;;
        8|08)
            echo -ne "  ${CYAN}[?]${NC} Enter Timezone (e.g., Africa/Tunis): ${WHITE}"
            read TZ_INPUT
            TZ_INPUT=${TZ_INPUT:-Africa/Tunis}
            if [ -f "$WEB_ROOT/index.php" ]; then
                sed -i "/date_default_timezone_set/d" $WEB_ROOT/index.php
                sed -i "/date_default_timezone_set/d" $WEB_ROOT/admin.php
                sed -i "s/session_start();/session_start();\ndate_default_timezone_set('$TZ_INPUT');/" $WEB_ROOT/index.php
                sed -i "s/session_start();/session_start();\ndate_default_timezone_set('$TZ_INPUT');/" $WEB_ROOT/admin.php
                systemctl restart php8.2-fpm
                echo -e "  ${GREEN}✔️ Timezone updated to ${WHITE}${TZ_INPUT}${NC}"
            else
                echo -e "  ${RED}❌ Not installed.${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}Press [ENTER] to continue...${NC}"; read
            ;;
        9|09) 
            echo -ne "  ${RED}⚠️  WIPE ENTIRE SYSTEM? (y/n): ${WHITE}"
            read confirm
            if [[ "$confirm" == "y" || "$confirm" == "Y" ]]; then
                rm -rf $WEB_ROOT; rm -f $NGINX_CONF; rm -f /etc/nginx/sites-enabled/netcloud; systemctl restart nginx
                echo -e "  ${GREEN}🗑️  System completely removed.${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}Press [ENTER]...${NC}"; read ;;
        0|00) echo -e "  ${CYAN}👋 Terminating session. Goodbye!${NC}\n"; exit 0 ;;
        *) echo -e "  ${RED}❌ Invalid selection!${NC}"; sleep 1 ;;
    esac
}

# ==========================================
# Main Script Execution
# ==========================================
if [ "$EUID" -ne 0 ]; then 
    echo -e "  ${RED}${BOLD}❌ Root privileges required! Please run with sudo.${NC}"
    exit 1
fi

while true; do
    show_menu
    read_choice
done
