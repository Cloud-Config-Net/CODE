#!/bin/bash

# ==========================================
# 🚀 NET-CLOUD All-In-One Manager
# ==========================================

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
    echo "-------------------------------------"
    echo "🚀 Starting NET-CLOUD professional installation..."
    
    # Prompt user for Domain and Port with default fallback
    read -p "🌐 Enter Domain (Press Enter for default $DEFAULT_DOMAIN): " DOMAIN
    DOMAIN=${DOMAIN:-$DEFAULT_DOMAIN}

    read -p "🔌 Enter Port (Press Enter for default $DEFAULT_PORT): " PORT
    PORT=${PORT:-$DEFAULT_PORT}

    echo "🔄 1. Updating packages and installing requirements..."
    apt update && apt install nginx php8.2-fpm php8.2-curl -y

    echo "📁 2. Setting up directories..."
    mkdir -p $WEB_ROOT/uploads
    chown -R www-data:www-data $WEB_ROOT

    echo "⬇️ 3. Fetching files from GitHub..."
    wget $LINK_INDEX -O $WEB_ROOT/index.php
    wget $LINK_ADMIN -O $WEB_ROOT/admin.php
    chown www-data:www-data $WEB_ROOT/index.php $WEB_ROOT/admin.php

    echo "⚙️ 4. Creating and configuring Nginx file..."
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

    echo "🔗 5. Enabling site and configuring firewall..."
    ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    ufw allow $PORT/tcp
    systemctl restart nginx
    systemctl restart php8.2-fpm

    echo "✅ Installation completed successfully! Your site is now running on:"
    echo "Domain: $DOMAIN"
    echo "Port: $PORT"
    echo "-------------------------------------"
    read -p "Press Enter to return to the menu..."
}

# ==========================================
# Display Menu Function
# ==========================================
show_menu() {
    clear
    # Check Nginx status
    if systemctl is-active --quiet nginx; then
        STATUS="✅ Active (Running)"
    else
        STATUS="🛑 Inactive (Stopped)"
    fi
    
    echo "-------------------------------------"
    echo "🚀 NET-CLOUD CONTROL PANEL"
    echo "📊 Status: $STATUS"
    echo "-------------------------------------"
    echo "1. Install NET - CLOUD"
    echo "2. Set Domain"
    echo "3. Set Port"
    echo "4. Start Service"
    echo "5. Stop Service"
    echo "6. Restart Service Nginx & PHP"
    echo "7. Uninstall & Remove Data"
    echo "8. Settings Edit Project Files"
    echo "0. Exit"
    echo "-------------------------------------"
}

# ==========================================
# Read Choice Function
# ==========================================
read_choice() {
    read -p "Select an option: " choice
    case $choice in
        1) 
            install_netcloud 
            ;;
        2) 
            read -p "Enter New Domain: " NEW_DOMAIN
            if [ -f "$NGINX_CONF" ]; then
                sed -i "s/server_name .*/server_name $NEW_DOMAIN;/" $NGINX_CONF
                systemctl restart nginx
                echo "✅ Domain updated successfully."
            else
                echo "❌ System is not installed! Please install first."
            fi
            read -p "Press Enter to continue..."
            ;;
        3) 
            read -p "Enter New Port: " NEW_PORT
            if [ -f "$NGINX_CONF" ]; then
                sed -i "s/listen .*/listen $NEW_PORT;/" $NGINX_CONF
                ufw allow $NEW_PORT/tcp
                systemctl restart nginx
                echo "✅ Port updated successfully."
            else
                echo "❌ System is not installed! Please install first."
            fi
            read -p "Press Enter to continue..."
            ;;
        4) 
            systemctl start nginx
            echo "✅ Service started."
            read -p "Press Enter to continue..."
            ;;
        5) 
            systemctl stop nginx
            echo "🛑 Service stopped."
            read -p "Press Enter to continue..."
            ;;
        6) 
            systemctl restart nginx
            systemctl restart php8.2-fpm
            echo "🔄 Services restarted."
            read -p "Press Enter to continue..."
            ;;
        7) 
            read -p "⚠️ Are you sure you want to completely remove the system? (y/n): " confirm
            if [[ "$confirm" == "y" || "$confirm" == "Y" ]]; then
                rm -rf $WEB_ROOT
                rm -f $NGINX_CONF
                rm -f /etc/nginx/sites-enabled/netcloud
                systemctl restart nginx
                echo "🗑️ Successfully uninstalled & removed data."
            else
                echo "Cancelled."
            fi
            read -p "Press Enter to continue..."
            ;;
        8) 
            if [ -d "$WEB_ROOT" ]; then
                nano $WEB_ROOT/admin.php
                nano $WEB_ROOT/index.php
                nano $NGINX_CONF
            else
                echo "❌ Project not found or not installed yet."
                read -p "Press Enter to continue..."
            fi
            ;;
        0) 
            echo "👋 Goodbye!"
            exit 0 
            ;;
        *) 
            echo "❌ Invalid option!"
            sleep 1
            ;;
    esac
}

# ==========================================
# Main Script Execution
# ==========================================

# Ensure script is run as Root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Please run the script with root privileges (use sudo)."
    exit 1
fi

while true; do
    show_menu
    read_choice
done
