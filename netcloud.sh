#!/bin/bash

# ==========================================
# 🚀 NET-CLOUD AI-Core Manager
# ==========================================

# --- Default Paths & Settings ---
WEB_ROOT="/var/www/netcloud"
NGINX_CONF="/etc/nginx/sites-available/netcloud"
DEFAULT_DOMAIN="cloud.maxssh.site"
DEFAULT_PORT=8880

# Place your GitHub Raw file links here:
LINK_INDEX="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/index.php"
LINK_ADMIN="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/admin.php"

# Colors for aesthetic terminal
GREEN='\033[0;32m'
CYAN='\033[0;36m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ==========================================
# Install Function
# ==========================================
install_netcloud() {
    clear
    echo -e "${CYAN}==========================================${NC}"
    echo -e "${CYAN}🚀 Initializing NET-CLOUD AI-Core...${NC}"
    echo -e "${CYAN}==========================================${NC}"
    
    # Prompt user for Domain and Port with default fallback
    read -p "🌐 Target Domain [Default: $DEFAULT_DOMAIN]: " DOMAIN
    DOMAIN=${DOMAIN:-$DEFAULT_DOMAIN}

    read -p "🔌 Listening Port [Default: $DEFAULT_PORT]: " PORT
    PORT=${PORT:-$DEFAULT_PORT}

    echo -e "\n${YELLOW}[1/5] Updating packages & installing dependencies...${NC}"
    apt update && apt install nginx php8.2-fpm php8.2-curl ufw -y

    echo -e "${YELLOW}[2/5] Creating secured directories...${NC}"
    mkdir -p $WEB_ROOT/uploads
    chown -R www-data:www-data $WEB_ROOT

    echo -e "${YELLOW}[3/5] Injecting core payloads from GitHub...${NC}"
    wget -q --show-progress $LINK_INDEX -O $WEB_ROOT/index.php
    wget -q --show-progress $LINK_ADMIN -O $WEB_ROOT/admin.php
    chown www-data:www-data $WEB_ROOT/index.php $WEB_ROOT/admin.php

    echo -e "${YELLOW}[4/5] Configuring Nginx Security Protocol...${NC}"
    cat <<EOF > $NGINX_CONF
server {
    listen $PORT;
    server_name $DOMAIN;
    root $WEB_ROOT;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Enhanced Download Firewall (AI Sniffer)
    location ~* ^/([a-zA-Z0-9_-]+)\.hc\$ {
        if (\$http_user_agent ~* (WhatsApp|TelegramBot|facebookexternalhit|Twitterbot|Slackbot)) {
            return 200 "NetCloud System: Direct Access Only";
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

    echo -e "${YELLOW}[5/5] Activating Network Ports & Firewall...${NC}"
    ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    ufw allow $PORT/tcp > /dev/null 2>&1
    
    systemctl restart nginx
    systemctl restart php8.2-fpm

    echo -e "\n${GREEN}✅ DEPLOYMENT SUCCESSFUL!${NC}"
    echo -e "-------------------------------------"
    echo -e "📡 URL : ${CYAN}http://$DOMAIN:$PORT${NC}"
    echo -e "🛡️ Admin: ${CYAN}http://$DOMAIN:$PORT/admin.php${NC}"
    echo -e "-------------------------------------"
    read -p "Press Enter to return to command center..."
}

# ==========================================
# Display Menu Function
# ==========================================
show_menu() {
    clear
    if systemctl is-active --quiet nginx; then
        STATUS="${GREEN}ONLINE${NC}"
    else
        STATUS="${RED}OFFLINE${NC}"
    fi
    
    echo -e "${CYAN}==========================================${NC}"
    echo -e "  ${CYAN}NET-CLOUD COMMAND CENTER${NC}"
    echo -e "  Status: $STATUS"
    echo -e "${CYAN}==========================================${NC}"
    echo " 1) Deploy Core System"
    echo " 2) Modify Domain"
    echo " 3) Modify Port"
    echo " 4) Start Engines (Nginx)"
    echo " 5) Stop Engines"
    echo " 6) Reboot Services (Nginx & PHP)"
    echo " 7) Wipe Data (Uninstall)"
    echo " 8) Edit Source Files"
    echo " 0) Exit Terminal"
    echo -e "${CYAN}------------------------------------------${NC}"
}

# ==========================================
# Read Choice Function
# ==========================================
read_choice() {
    read -p "Select Target Module: " choice
    case $choice in
        1) install_netcloud ;;
        2) 
            read -p "Enter Target Domain: " NEW_DOMAIN
            if [ -f "$NGINX_CONF" ]; then
                sed -i "s/server_name .*/server_name $NEW_DOMAIN;/" $NGINX_CONF
                systemctl restart nginx
                echo -e "${GREEN}✅ Domain synchronized.${NC}"
            else
                echo -e "${RED}❌ System not deployed yet.${NC}"
            fi
            read -p "Press Enter to continue..."
            ;;
        3) 
            read -p "Enter Target Port: " NEW_PORT
            if [ -f "$NGINX_CONF" ]; then
                sed -i "s/listen .*/listen $NEW_PORT;/" $NGINX_CONF
                ufw allow $NEW_PORT/tcp > /dev/null 2>&1
                systemctl restart nginx
                echo -e "${GREEN}✅ Port synchronized & unlocked.${NC}"
            else
                echo -e "${RED}❌ System not deployed yet.${NC}"
            fi
            read -p "Press Enter to continue..."
            ;;
        4) systemctl start nginx; echo -e "${GREEN}✅ Engines running.${NC}"; read -p "..." ;;
        5) systemctl stop nginx; echo -e "${RED}🛑 Engines stopped.${NC}"; read -p "..." ;;
        6) systemctl restart nginx; systemctl restart php8.2-fpm; echo -e "${GREEN}🔄 Reboot complete.${NC}"; read -p "..." ;;
        7) 
            read -p "⚠️ WIPE ALL DATA? (y/n): " confirm
            if [[ "$confirm" == "y" || "$confirm" == "Y" ]]; then
                rm -rf $WEB_ROOT
                rm -f $NGINX_CONF /etc/nginx/sites-enabled/netcloud
                systemctl restart nginx
                echo -e "${GREEN}🗑️ Core wiped successfully.${NC}"
            else
                echo "Abort."
            fi
            read -p "Press Enter..."
            ;;
        8) 
            if [ -d "$WEB_ROOT" ]; then
                nano $WEB_ROOT/index.php
                nano $WEB_ROOT/admin.php
                nano $NGINX_CONF
            else
                echo -e "${RED}❌ Core not found.${NC}"; read -p "..."
            fi
            ;;
        0) echo -e "${CYAN}Connection Terminated.${NC}"; exit 0 ;;
        *) echo -e "${RED}Invalid directive.${NC}"; sleep 1 ;;
    esac
}

# Ensure script is run as Root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}❌ ROOT privileges required. Use sudo.${NC}"
    exit 1
fi

while true; do
    show_menu
    read_choice
done
