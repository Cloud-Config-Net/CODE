#!/bin/bash

# ========================================================
#   CLOUD CONFIG MANAGER PRO - SYSTEM INSTALLER
# ========================================================

# --- MODERN COLORS & STYLING ---
CYAN='\033[0;36m'
LIGHT_CYAN='\033[1;36m'
GREEN='\033[0;32m'
LIGHT_GREEN='\033[1;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
WHITE='\033[1;37m'
DARK_GRAY='\033[1;30m'
NC='\033[0m' # NO COLOR
BOLD='\033[1m'

# --- DEFAULT PATHS & SETTINGS ---
WEB_ROOT="/var/www/netcloud"
NGINX_CONF="/etc/nginx/sites-available/netcloud"
DEFAULT_DOMAIN="cloud.maxssh.site"
DEFAULT_PORT=80
DEFAULT_TZ="Africa/Tunis"

# GITHUB RAW FILE LINKS:
LINK_INDEX="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/index.php"
LINK_ADMIN="https://raw.githubusercontent.com/Cloud-Config-Net/CODE/main/admin.php"

# ==========================================
# HELPER: CHECK & AUTO-SELECT PORT
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
                echo -e "  ${YELLOW}WARNING: PORT $port IS BUSY. ${DARK_GRAY}SCANNING FOR ALTERNATIVE...${NC}"
            fi
            port=$((port+1))
        else
            break
        fi
    done
    
    if [ "$port" != "$1" ]; then
        echo -e "  ${GREEN}SMART-ASSIGNED PORT: ${WHITE}$port${NC}"
    fi
    echo $port
}

# ==========================================
# INSTALL FUNCTION
# ==========================================
install_netcloud() {
    clear
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${WHITE}${BOLD}INITIATING CLOUD CONFIG CORE DEPLOYMENT...${NC}"
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

    # SMART LOGIC: PROMPT FOR SSL ONLY IF PORT IS 443
    if [ "$PORT" == "443" ]; then
        echo -ne "  ${CYAN}ACTIVATE SSL HTTPS CERTIFICATE? (Y/N)  : ${WHITE}"
        read SSL_CHOICE
    else
        SSL_CHOICE="N"
    fi

    echo -e "\n  ${DARK_GRAY}[1/6]${NC} ${CYAN}UPDATING PACKAGES & DEPENDENCIES...${NC}"
    apt update && apt install nginx php8.2-fpm php8.2-curl ufw iproute2 certbot -y > /dev/null 2>&1

    echo -e "  ${DARK_GRAY}[2/6]${NC} ${CYAN}BUILDING SYSTEM DIRECTORIES...${NC}"
    mkdir -p $WEB_ROOT/uploads
    chown -R www-data:www-data $WEB_ROOT

    echo -e "  ${DARK_GRAY}[3/6]${NC} ${CYAN}FETCHING CORE ENGINE FROM REPOSITORY...${NC}"
    wget -q --show-progress $LINK_INDEX -O $WEB_ROOT/index.php
    wget -q --show-progress $LINK_ADMIN -O $WEB_ROOT/admin.php

    echo -e "  ${DARK_GRAY}[4/6]${NC} ${CYAN}INJECTING TIMEZONE (${WHITE}$TZ_INPUT${CYAN}) INTO CORE...${NC}"
    sed -i "/date_default_timezone_set/d" $WEB_ROOT/index.php
    sed -i "/date_default_timezone_set/d" $WEB_ROOT/admin.php
    sed -i "s|session_start();|session_start();\ndate_default_timezone_set('$TZ_INPUT');|" $WEB_ROOT/index.php
    sed -i "s|session_start();|session_start();\ndate_default_timezone_set('$TZ_INPUT');|" $WEB_ROOT/admin.php
    chown www-data:www-data $WEB_ROOT/index.php $WEB_ROOT/admin.php

    echo -e "  ${DARK_GRAY}[5/6]${NC} ${CYAN}COMPILING NGINX SMART-FIREWALL CONFIG...${NC}"
    
    # ----------------------------------------------------
    # BUILD NGINX CONFIGURATION BASED ON PORT & SSL CHOICE
    # ----------------------------------------------------
    if [ "$PORT" == "443" ] && [[ "$SSL_CHOICE" == "y" || "$SSL_CHOICE" == "Y" ]]; then
        echo -e "\n  ${YELLOW}* ATTENTION: PORT 80 MUST BE FREE FOR 10 SECONDS TO VERIFY SSL *${NC}"
        echo -e "  ${DARK_GRAY}Please pause any WebSocket or Payload service running on port 80 temporarily.${NC}"
        echo -ne "  ${CYAN}PRESS [ENTER] WHEN PORT 80 IS READY >${NC} "
        read
        
        # STOP NGINX TO PREVENT PORT CONFLICT DURING CERTIFICATE VALIDATION
        systemctl stop nginx
        
        # REQUEST CERTIFICATE VIA TEMPORARY STANDALONE SERVER (PORT 80)
        echo -e "  ${CYAN}REQUESTING SSL CERTIFICATE FROM LET'S ENCRYPT FOR $DOMAIN...${NC}"
        certbot certonly --standalone -d $DOMAIN --non-interactive --agree-tos --register-unsafely-without-email
        
        # WRITE NGINX CONFIGURATION FOR PORT 443 ONLY (HTTPS)
        cat <<EOF > $NGINX_CONF
server {
    listen 443 ssl;
    server_name $DOMAIN;
    root $WEB_ROOT;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;

    # SECURITY HEADERS
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # CLEAN URL FOR ADMIN PANEL
    location = /admin {
        rewrite ^/admin$ /admin.php last;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # ANTI-SCRAPING & SMART DOWNLOAD FIREWALL
    location ~* ^/([a-zA-Z0-9_-]+)\.hc\$ {
        if (\$http_user_agent ~* (curl|wget|python|Scrapy|libwww|HttpClient|Termux|WhatsApp|TelegramBot|facebookexternalhit|Slackbot)) {
            return 403 "ACCESS DENIED: AUTOMATED TOOLS ARE NOT ALLOWED";
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
    else
        # IF PORT IS 80 OR ANY OTHER NON-SSL PORT
        cat <<EOF > $NGINX_CONF
server {
    listen $PORT;
    server_name $DOMAIN;
    root $WEB_ROOT;
    index index.php index.html;

    # SECURITY HEADERS
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # CLEAN URL FOR ADMIN PANEL
    location = /admin {
        rewrite ^/admin$ /admin.php last;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # ANTI-SCRAPING & SMART DOWNLOAD FIREWALL
    location ~* ^/([a-zA-Z0-9_-]+)\.hc\$ {
        if (\$http_user_agent ~* (curl|wget|python|Scrapy|libwww|HttpClient|Termux|WhatsApp|TelegramBot|facebookexternalhit|Slackbot)) {
            return 403 "ACCESS DENIED: AUTOMATED TOOLS ARE NOT ALLOWED";
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
    fi

    echo -e "  ${DARK_GRAY}[6/6]${NC} ${CYAN}APPLYING RULES & RESTARTING SERVICES...${NC}"
    ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    ufw allow $PORT/tcp > /dev/null 2>&1
    
    systemctl restart nginx
    systemctl restart php8.2-fpm

    echo -e "\n${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${LIGHT_GREEN}SYSTEM DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  TARGET DOMAIN : ${WHITE}${DOMAIN}${NC}"
    echo -e "  ACTIVE PORT   : ${WHITE}${PORT}${NC}"
    echo -e "  TIMEZONE      : ${WHITE}${TZ_INPUT}${NC}"
    if [ "$PORT" == "443" ] && [[ "$SSL_CHOICE" == "y" || "$SSL_CHOICE" == "Y" ]]; then
        echo -e "  SSL STATUS    : ${GREEN}CONFIGURED AND ACTIVATED (HTTPS)${NC}"
        echo -e "  PORT 80 STATUS: ${GREEN}FREE AND AVAILABLE FOR WEBSOCKET${NC}"
    fi
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -ne "\n  ${DARK_GRAY}PRESS [ENTER] TO RETURN TO THE MENU...${NC}"
    read
}

# ==========================================
# DISPLAY MENU FUNCTION
# ==========================================
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
    echo -e "  ${DARK_GRAY}[${WHITE}00${DARK_GRAY}]${NC} ${CYAN}EXIT MANAGER\n${NC}"
    
    echo -e "${LIGHT_CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# ==========================================
# READ CHOICE FUNCTION
# ==========================================
read_choice() {
    echo -ne "\n  ${CYAN}SELECT MODULE [00-09] >${NC} ${WHITE}"
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
                ufw allow $NEW_PORT/tcp > /dev/null 2>&1
                systemctl restart nginx
                echo -e "  ${GREEN}PORT UPDATED TO: ${WHITE}${NEW_PORT}${NC}"
            else
                echo -e "  ${RED}SYSTEM IS NOT INSTALLED!${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}PRESS [ENTER] TO CONTINUE...${NC}"; read
            ;;
        4|04) systemctl start nginx; echo -e "  ${GREEN}NGINX STARTED.${NC}"; echo -ne "\n  ${DARK_GRAY}PRESS [ENTER]...${NC}"; read ;;
        5|05) systemctl stop nginx; echo -e "  ${YELLOW}NGINX STOPPED.${NC}"; echo -ne "\n  ${DARK_GRAY}PRESS [ENTER]...${NC}"; read ;;
        6|06) systemctl restart nginx; systemctl restart php8.2-fpm; echo -e "  ${CYAN}SERVICES RESTARTED.${NC}"; echo -ne "\n  ${DARK_GRAY}PRESS [ENTER]...${NC}"; read ;;
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
                systemctl restart php8.2-fpm
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
                rm -rf $WEB_ROOT; rm -f $NGINX_CONF; rm -f /etc/nginx/sites-enabled/netcloud; systemctl restart nginx
                echo -e "  ${GREEN}SYSTEM COMPLETELY REMOVED.${NC}"
            fi
            echo -ne "\n  ${DARK_GRAY}PRESS [ENTER]...${NC}"; read ;;
        0|00) echo -e "  ${CYAN}TERMINATING SESSION. GOODBYE!${NC}\n"; exit 0 ;;
        *) echo -e "  ${RED}INVALID SELECTION!${NC}"; sleep 1 ;;
    esac
}

# ==========================================
# MAIN SCRIPT EXECUTION
# ==========================================
if [ "$EUID" -ne 0 ]; then 
    echo -e "  ${RED}${BOLD}ROOT PRIVILEGES REQUIRED! PLEASE RUN WITH SUDO.${NC}"
    exit 1
fi

while true; do
    show_menu
    read_choice
done
