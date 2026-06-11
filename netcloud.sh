#!/bin/bash

# ==========================================
# 🚀 NET-CLOUD All-In-One Manager (CYBERPUNK EDITION)
# ==========================================

# --- Colors & Styling (Neon Vibes) ---
CYAN='\033[0;36m'
NEON_CYAN='\033[1;36m'
GREEN='\033[0;32m'
NEON_GREEN='\033[1;32m'
RED='\033[0;31m'
NEON_RED='\033[1;31m'
YELLOW='\033[1;33m'
DARK_GRAY='\033[1;30m'
NC='\033[0m' # No Color
BOLD='\033[1m'

# --- Default Paths & Settings ---
WEB_ROOT="/var/www/netcloud"
NGINX_CONF="/etc/nginx/sites-available/netcloud"
DEFAULT_DOMAIN="cloud.maxssh.site"
DEFAULT_PORT=8880

# Place your GitHub Raw file links here:
LINK_INDEX="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/index.php"
LINK_ADMIN="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/admin.php"

# ==========================================
# Install Function
# ==========================================
install_netcloud() {
    clear
    echo -e "${NEON_CYAN}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${NEON_GREEN}  🚀 INITIATING NET-CLOUD CORE DEPLOYMENT...${NC}"
    echo -e "${NEON_CYAN}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
    
    # Prompt user for Domain and Port with default fallback
    echo -ne "${YELLOW}[?]${NC} 🌐 Enter Domain (Press Enter for default ${NEON_CYAN}${DEFAULT_DOMAIN}${NC}): "
    read DOMAIN
    DOMAIN=${DOMAIN:-$DEFAULT_DOMAIN}

    echo -ne "${YELLOW}[?]${NC} 🔌 Enter Port (Press Enter for default ${NEON_CYAN}${DEFAULT_PORT}${NC}): "
    read PORT
    PORT=${PORT:-$DEFAULT_PORT}

    echo -e "\n${NEON_CYAN}[1/5]${NC} 🔄 Updating packages and installing requirements (Nginx, PHP8.2, UFW)..."
    apt update && apt install nginx php8.2-fpm php8.2-curl ufw -y

    echo -e "${NEON_CYAN}[2/5]${NC} 📁 Setting up system directories & permissions..."
    mkdir -p $WEB_ROOT/uploads
    chown -R www-data:www-data $WEB_ROOT

    echo -e "${NEON_CYAN}[3/5]${NC} ⬇️ Fetching core engine files from GitHub..."
    wget -q --show-progress $LINK_INDEX -O $WEB_ROOT/index.php
    wget -q --show-progress $LINK_ADMIN -O $WEB_ROOT/admin.php
    chown www-data:www-data $WEB_ROOT/index.php $WEB_ROOT/admin.php

    echo -e "${NEON_CYAN}[4/5]${NC} ⚙️ Generating & compiling Nginx configuration..."
    cat <<EOF > $NGINX_CONF
server {
    listen $PORT;
    server_name $DOMAIN;
    root $WEB_ROOT;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # جدار الحماية المطور لروابط التحميل
    location ~* ^/([a-zA-Z0-9_-]+)\.hc\$ {
        # Block social media bots from accessing the link and ruining the 1-download limit
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

    echo -e "${NEON_CYAN}[5/5]${NC} 🔗 Applying firewall rules and restarting core services..."
    ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    ufw allow $PORT/tcp > /dev/null 2>&1
    
    systemctl restart nginx
    systemctl restart php8.2-fpm

    echo -e "\n${NEON_GREEN}${BOLD}============================================================${NC}"
    echo -e "${NEON_GREEN} ✔️ SYSTEM DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
    echo -e "${NEON_GREEN}${BOLD}============================================================${NC}"
    echo -e " 🌐 ${BOLD}Target Domain :${NC} ${NEON_CYAN}${DOMAIN}${NC}"
    echo -e " 🔌 ${BOLD}Active Port   :${NC} ${NEON_CYAN}${PORT}${NC}"
    echo -e "${DARK_GRAY}------------------------------------------------------------${NC}"
    echo -ne "\nPress ${YELLOW}[ENTER]${NC} to return to the command center..."
    read
}

# ==========================================
# Display Menu Function
# ==========================================
show_menu() {
    clear
    # Check Nginx status
    if systemctl is-active --quiet nginx; then
        STATUS="${NEON_GREEN}■ ACTIVE & SECURE${NC}"
    else
        STATUS="${NEON_RED}■ OFFLINE (Stopped)${NC}"
    fi
    
    echo -e "${NEON_CYAN}${BOLD}"
    echo "  ╔╗╔╔═╗╔╦╗  ╔═╗╦  ╔═╗╦ ╦╔╦╗"
    echo "  ║║║║╣  ║───║  ║  ║ ║║ ║ ║║"
    echo "  ╝╚╝╚═╝ ╩   ╚═╝╩═╝╚═╝╚═╝═╩╝"
    echo -e "${NC}"
    
    echo -e " ${DARK_GRAY}─────────────────────────────────────────────${NC}"
    echo -e "  ⚡ ${BOLD}CYBER-INJECTION ENGINE${NC} - MAIN CONSOLE"
    echo -e "  📡 Radar Status : ${STATUS}"
    echo -e " ${DARK_GRAY}─────────────────────────────────────────────${NC}\n"
    
    echo -e "  ${NEON_CYAN}[1]${NC} Install NET-CLOUD Setup"
    echo -e "  ${NEON_CYAN}[2]${NC} Re-Configure Domain"
    echo -e "  ${NEON_CYAN}[3]${NC} Re-Configure Port"
    echo -e "  ${DARK_GRAY}-----------------------------------${NC}"
    echo -e "  ${NEON_GREEN}[4]${NC} Start Nginx Service"
    echo -e "  ${YELLOW}[5]${NC} Stop Nginx Service"
    echo -e "  ${NEON_CYAN}[6]${NC} Restart Nginx & PHP Engines"
    echo -e "  ${DARK_GRAY}-----------------------------------${NC}"
    echo -e "  ${NEON_CYAN}[8]${NC} Edit Project Config Files"
    echo -e "  ${NEON_RED}[7]${NC} Terminate & Wipe System Data"
    echo -e "  ${DARK_GRAY}-----------------------------------${NC}"
    echo -e "  ${NEON_RED}[0]${NC} Exit Console\n"
}

# ==========================================
# Read Choice Function
# ==========================================
read_choice() {
    echo -ne " ${YELLOW}❯ Select a command:${NC} "
    read choice
    echo ""
    case $choice in
        1) 
            install_netcloud 
            ;;
        2) 
            echo -ne "${YELLOW}[?]${NC} Enter New Domain: "
            read NEW_DOMAIN
            if [ -f "$NGINX_CONF" ]; then
                sed -i "s/server_name .*/server_name $NEW_DOMAIN;/" $NGINX_CONF
                systemctl restart nginx
                echo -e "${NEON_GREEN}✔️ Domain updated successfully to: ${NEW_DOMAIN}${NC}"
            else
                echo -e "${NEON_RED}❌ System is not installed! Please run installation first.${NC}"
            fi
            echo -ne "\nPress ${YELLOW}[ENTER]${NC} to continue..."
            read
            ;;
        3) 
            echo -ne "${YELLOW}[?]${NC} Enter New Port: "
            read NEW_PORT
            if [ -f "$NGINX_CONF" ]; then
                sed -i "s/listen .*/listen $NEW_PORT;/" $NGINX_CONF
                ufw allow $NEW_PORT/tcp > /dev/null 2>&1
                systemctl restart nginx
                echo -e "${NEON_GREEN}✔️ Port updated successfully to: ${NEW_PORT}${NC}"
            else
                echo -e "${NEON_RED}❌ System is not installed! Please run installation first.${NC}"
            fi
            echo -ne "\nPress ${YELLOW}[ENTER]${NC} to continue..."
            read
            ;;
        4) 
            systemctl start nginx
            echo -e "${NEON_GREEN}▶️  Nginx Service Started.${NC}"
            echo -ne "\nPress ${YELLOW}[ENTER]${NC} to continue..."
            read
            ;;
        5) 
            systemctl stop nginx
            echo -e "${YELLOW}🛑 Nginx Service Stopped.${NC}"
            echo -ne "\nPress ${YELLOW}[ENTER]${NC} to continue..."
            read
            ;;
        6) 
            systemctl restart nginx
            systemctl restart php8.2-fpm
            echo -e "${NEON_CYAN}🔄 Nginx & PHP Services Restarted successfully.${NC}"
            echo -ne "\nPress ${YELLOW}[ENTER]${NC} to continue..."
            read
            ;;
        7) 
            echo -ne "${NEON_RED}⚠️  WARNING: Are you sure you want to completely WIPE the system? (y/n): ${NC}"
            read confirm
            if [[ "$confirm" == "y" || "$confirm" == "Y" ]]; then
                rm -rf $WEB_ROOT
                rm -f $NGINX_CONF
                rm -f /etc/nginx/sites-enabled/netcloud
                systemctl restart nginx
                echo -e "${NEON_GREEN}🗑️  System successfully uninstalled and all data wiped.${NC}"
            else
                echo -e "${NEON_CYAN}Cancel... System safe.${NC}"
            fi
            echo -ne "\nPress ${YELLOW}[ENTER]${NC} to continue..."
            read
            ;;
        8) 
            if [ -d "$WEB_ROOT" ]; then
                nano $WEB_ROOT/admin.php
                nano $WEB_ROOT/index.php
                nano $NGINX_CONF
            else
                echo -e "${NEON_RED}❌ Project files not found. Not installed yet.${NC}"
                echo -ne "\nPress ${YELLOW}[ENTER]${NC} to continue..."
                read
            fi
            ;;
        0) 
            echo -e "${NEON_CYAN}👋 System Terminated. Goodbye!${NC}\n"
            exit 0 
            ;;
        *) 
            echo -e "${NEON_RED}❌ Invalid command code!${NC}"
            sleep 1
            ;;
    esac
}

# ==========================================
# Main Script Execution
# ==========================================

# Ensure script is run as Root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${NEON_RED}${BOLD}❌ SECURITY ALERT: Please run the script with root privileges (sudo bash script.sh).${NC}"
    exit 1
fi

while true; do
    show_menu
    read_choice
done
