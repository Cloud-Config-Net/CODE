#!/bin/bash

# ==========================================
# NET-CLOUD MANAGER (ENHANCED V2.1)
# <RADAR-LINK-PRO-NEW-CODE> - Fixed lsof & Port Check
# ==========================================

# --- Colors & Styling ---
CYAN='\033[0;36m'
LIGHT_CYAN='\033[1;36m'
GREEN='\033[0;32m'
LIGHT_GREEN='\033[1;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
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
# Helper: Check & Auto-Select Port
# ==========================================
check_port() {
    local port=$1
    # <RADAR-LINK-PRO-NEW-CODE> - Using 'ss' or 'netstat' as alternatives to 'lsof'
    # This prevents the "lsof: command not found" error
    while (ss -tuln | grep -q ":$port ") || (netstat -tuln | grep -q ":$port ") 2>/dev/null; do
        if [ "$port" == "$1" ]; then
            echo -e "  ${YELLOW}⚠️  Warning: Port $port is already in use.${NC}"
            echo -e "  ${LIGHT_CYAN}🔍 Searching for an alternative port...${NC}"
        fi
        port=$((port+1))
    done
    
    if [ "$port" != "$1" ]; then
        echo -e "  ${GREEN}✅ Selected alternative port: $port${NC}"
    fi
    echo $port
}

# ==========================================
# Install Function
# ==========================================
install_netcloud() {
    clear
    echo -e "${LIGHT_CYAN}========================================================${NC}"
    echo -e "  ${LIGHT_GREEN}🚀 INITIATING NET-CLOUD CORE DEPLOYMENT...${NC}"
    echo -e "${LIGHT_CYAN}========================================================\n${NC}"
    
    echo -ne "  ${YELLOW}[?]${NC} 🌐 Enter Domain (Default: ${LIGHT_CYAN}${DEFAULT_DOMAIN}${NC}): "
    read DOMAIN
    DOMAIN=${DOMAIN:-$DEFAULT_DOMAIN}

    echo -ne "  ${YELLOW}[?]${NC} 🔌 Enter Port (Default: ${LIGHT_CYAN}${DEFAULT_PORT}${NC}): "
    read PORT_INPUT
    PORT_INPUT=${PORT_INPUT:-$DEFAULT_PORT}
    
    # Port auto-check
    PORT=$(check_port $PORT_INPUT)

    echo -ne "  ${YELLOW}[?]${NC} 🌍 Enter Timezone (Default: ${LIGHT_CYAN}${DEFAULT_TZ}${NC}): "
    read TZ_INPUT
    TZ_INPUT=${TZ_INPUT:-$DEFAULT_TZ}

    echo -e "\n  ${LIGHT_CYAN}[1/6]${NC} 🔄 Updating packages and installing requirements..."
    # <RADAR-LINK-PRO-NEW-CODE> - Added 'lsof' and 'iproute2' to requirements for future use
    apt update && apt install nginx php8.2-fpm php8.2-curl ufw lsof iproute2 -y > /dev/null 2>&1

    echo -e "  ${LIGHT_CYAN}[2/6]${NC} 📁 Setting up system directories & permissions..."
    mkdir -p $WEB_ROOT/uploads
    chown -R www-data:www-data $WEB_ROOT

    echo -e "  ${LIGHT_CYAN}[3/6]${NC} ⬇️ Fetching core engine files from GitHub..."
    wget -q --show-progress $LINK_INDEX -O $WEB_ROOT/index.php
    wget -q --show-progress $LINK_ADMIN -O $WEB_ROOT/admin.php

    echo -e "  ${LIGHT_CYAN}[4/6]${NC} ⏱️ Injecting Timezone ($TZ_INPUT) into PHP files..."
    sed -i "/date_default_timezone_set/d" $WEB_ROOT/index.php
    sed -i "/date_default_timezone_set/d" $WEB_ROOT/admin.php
    sed -i "s/session_start();/session_start();\ndate_default_timezone_set('$TZ_INPUT');/" $WEB_ROOT/index.php
    sed -i "s/session_start();/session_start();\ndate_default_timezone_set('$TZ_INPUT');/" $WEB_ROOT/admin.php
    chown www-data:www-data $WEB_ROOT/index.php $WEB_ROOT/admin.php

    echo -e "  ${LIGHT_CYAN}[5/6]${NC} ⚙️ Generating & compiling Nginx configuration..."
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

    echo -e "  ${LIGHT_CYAN}[6/6]${NC} 🔗 Applying firewall rules and restarting core services..."
    ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    ufw allow $PORT/tcp > /dev/null 2>&1
    
    systemctl restart nginx
    systemctl restart php8.2-fpm

    echo -e "\n${LIGHT_CYAN}========================================================${NC}"
    echo -e "  ${LIGHT_GREEN}✔️ SYSTEM DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
    echo -e "${LIGHT_CYAN}========================================================${NC}"
    echo -e "  🌐 Target Domain : ${LIGHT_CYAN}${DOMAIN}${NC}"
    echo -e "  🔌 Active Port   : ${LIGHT_CYAN}${PORT}${NC}"
    echo -e "  ⏱️ Timezone      : ${LIGHT_CYAN}${TZ_INPUT}${NC}"
    echo -e "${LIGHT_CYAN}========================================================${NC}"
    echo -ne "\n  Press [ENTER] to return to the menu..."
    read
}

# ==========================================
# Display Menu Function
# ==========================================
show_menu() {
    clear
    echo -e "${LIGHT_CYAN}========================================================${NC}"
    echo -e "           ${LIGHT_GREEN} NET-CLOUD MANAGER V 2.1 (ENHANCED) ${NC}"
    echo -e "${LIGHT_CYAN}========================================================${NC}\n"
    
    echo -e "  ${LIGHT_CYAN}[01]${NC} INSTALL NET-CLOUD SETUP"
    echo -e "  ${LIGHT_CYAN}[02]${NC} RE-CONFIGURE DOMAIN"
    echo -e "  ${LIGHT_CYAN}[03]${NC} RE-CONFIGURE PORT (WITH AUTO-CHECK)"
    echo -e "  ${LIGHT_CYAN}[04]${NC} START NGINX SERVICE"
    echo -e "  ${LIGHT_CYAN}[05]${NC} STOP NGINX SERVICE"
    echo -e "  ${LIGHT_CYAN}[06]${NC} RESTART NGINX & PHP"
    echo -e "  ${LIGHT_CYAN}[07]${NC} EDIT FILES SITE"
    echo -e "  ${LIGHT_CYAN}[08]${NC} SET/UPDATE TIMEZONE"
    echo -e "  ${LIGHT_CYAN}[09]${NC} TERMINATE & WIPE DATA"
    echo -e "  ${LIGHT_CYAN}[00]${NC} EXIT\n"
    
    echo -e "${LIGHT_CYAN}========================================================${NC}"
}

# ==========================================
# Read Choice Function
# ==========================================
read_choice() {
    echo -ne "\n  ${LIGHT_CYAN}SELECT:${NC} ${LIGHT_GREEN}"
    read choice
    echo -e "${NC}"
    case $choice in
        1|01) 
            install_netcloud 
            ;;
        2|02) 
            echo -ne "  ${YELLOW}[?]${NC} Enter New Domain: "
            read NEW_DOMAIN
            if [ -f "$NGINX_CONF" ]; then
                sed -i "s/server_name .*/server_name $NEW_DOMAIN;/" $NGINX_CONF
                systemctl restart nginx
                echo -e "  ${GREEN}✔️ Domain updated successfully to: ${NEW_DOMAIN}${NC}"
            else
                echo -e "  ${RED}❌ System is not installed! Please run installation first.${NC}"
            fi
            echo -ne "\n  Press [ENTER] to continue..."
            read
            ;;
        3|03) 
            echo -ne "  ${YELLOW}[?]${NC} Enter New Port (e.g., 80, 8080, 9999): "
            read NEW_PORT_INPUT
            # Port auto-check
            NEW_PORT=$(check_port $NEW_PORT_INPUT)
            
            if [ -f "$NGINX_CONF" ]; then
                sed -i -E "s/listen [0-9]+;/listen $NEW_PORT;/" $NGINX_CONF
                ufw allow $NEW_PORT/tcp > /dev/null 2>&1
                systemctl restart nginx
                echo -e "  ${GREEN}✔️ Port updated and firewall opened successfully for port: ${NEW_PORT}${NC}"
            else
                echo -e "  ${RED}❌ System is not installed! Please run installation first.${NC}"
            fi
            echo -ne "\n  Press [ENTER] to continue..."
            read
            ;;
        4|04) 
            systemctl start nginx
            echo -e "  ${GREEN}▶️  Nginx Service Started.${NC}"
            echo -ne "\n  Press [ENTER] to continue..."
            read
            ;;
        5|05) 
            systemctl stop nginx
            echo -e "  ${YELLOW}🛑 Nginx Service Stopped.${NC}"
            echo -ne "\n  Press [ENTER] to continue..."
            read
            ;;
        6|06) 
            systemctl restart nginx
            systemctl restart php8.2-fpm
            echo -e "  ${LIGHT_CYAN}🔄 Nginx & PHP Services Restarted successfully.${NC}"
            echo -ne "\n  Press [ENTER] to continue..."
            read
            ;;
        7|07) 
            if [ -d "$WEB_ROOT" ]; then
                nano $WEB_ROOT/admin.php
                nano $WEB_ROOT/index.php
                nano $NGINX_CONF
            else
                echo -e "  ${RED}❌ Project files not found. Not installed yet.${NC}"
                echo -ne "\n  Press [ENTER] to continue..."
                read
            fi
            ;;
        8|08)
            echo -ne "  ${YELLOW}[?]${NC} Enter Timezone (Default: Africa/Tunis): "
            read TZ_INPUT
            TZ_INPUT=${TZ_INPUT:-Africa/Tunis}
            
            if [ -f "$WEB_ROOT/index.php" ] && [ -f "$WEB_ROOT/admin.php" ]; then
                sed -i "/date_default_timezone_set/d" $WEB_ROOT/index.php
                sed -i "/date_default_timezone_set/d" $WEB_ROOT/admin.php
                sed -i "s/session_start();/session_start();\ndate_default_timezone_set('$TZ_INPUT');/" $WEB_ROOT/index.php
                sed -i "s/session_start();/session_start();\ndate_default_timezone_set('$TZ_INPUT');/" $WEB_ROOT/admin.php
                
                systemctl restart php8.2-fpm
                echo -e "  ${GREEN}✔️ Timezone seamlessly injected & updated to: ${TZ_INPUT}${NC}"
            else
                echo -e "  ${RED}❌ Project files not found. Please install the system first.${NC}"
            fi
            echo -ne "\n  Press [ENTER] to continue..."
            read
            ;;
        9|09) 
            echo -ne "  ${RED}⚠️  WARNING: Are you sure you want to completely WIPE the system? (y/n): ${NC}"
            read confirm
            if [[ "$confirm" == "y" || "$confirm" == "Y" ]]; then
                rm -rf $WEB_ROOT
                rm -f $NGINX_CONF
                rm -f /etc/nginx/sites-enabled/netcloud
                systemctl restart nginx
                echo -e "  ${GREEN}🗑️  System successfully uninstalled and all data wiped.${NC}"
            else
                echo -e "  ${LIGHT_CYAN}Cancel... System safe.${NC}"
            fi
            echo -ne "\n  Press [ENTER] to continue..."
            read
            ;;
        0|00) 
            echo -e "  ${LIGHT_CYAN}👋 System Terminated. Goodbye!${NC}\n"
            exit 0 
            ;;
        *) 
            echo -e "  ${RED}❌ Invalid command code!${NC}"
            sleep 1
            ;;
    esac
}

# ==========================================
# Main Script Execution
# ==========================================

if [ "$EUID" -ne 0 ]; then 
    echo -e "  ${RED}${BOLD}❌ SECURITY ALERT: Please run the script with root privileges (sudo bash script.sh).${NC}"
    exit 1
fi

while true; do
    show_menu
    read_choice
done
