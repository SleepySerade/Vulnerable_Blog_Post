#!/bin/bash
#
# Security Monitor Script
# Monitors for potential security threats and sends notifications via WebhookBeam
#

set -o errexit  # Exit on error
set -o nounset  # Exit on unset variables
set -o pipefail # Exit if any command in a pipe fails

# -----------------------------------------------------------------------------
# Configuration
# -----------------------------------------------------------------------------
WATCH_DIR="/var/www/html/uploads/images"
LOG_FILE="/var/log/security_monitor.log"
AUDIT_LOG="/var/log/audit/audit.log"

# Replace with your actual WebhookBeam URL
WEBHOOK_URL="https://webhookbeam.com/webhook/ntcmhnqf4c/ehprompt"

# Dangerous file extensions to monitor
DANGEROUS_EXTENSIONS=("php" "phtml" "php3" "php4" "php5" "php7" "phar" "inc")

# -----------------------------------------------------------------------------
# Logging Functions
# -----------------------------------------------------------------------------
log_info() {
    local timestamp
    timestamp=$(date "+%Y-%m-%d %H:%M:%S")
    echo "[INFO] $timestamp: $1" | tee -a "$LOG_FILE"
}

log_warning() {
    local timestamp
    timestamp=$(date "+%Y-%m-%d %H:%M:%S")
    echo "[WARNING] $timestamp: $1" | tee -a "$LOG_FILE"
}

log_error() {
    local timestamp
    timestamp=$(date "+%Y-%m-%d %H:%M:%S")
    echo "[ERROR] $timestamp: $1" | tee -a "$LOG_FILE"
}

# -----------------------------------------------------------------------------
# Webhook Notification Function
# -----------------------------------------------------------------------------
send_notification() {
    local message="$1"
    local severity="${2:-high}"
    local timestamp
    timestamp=$(date -u "+%Y-%m-%dT%H:%M:%SZ")
    local hostname
    hostname=$(hostname)
    
    # Log the alert locally
    log_info "ALERT: $message"
    
    # Create properly escaped JSON for WebhookBeam
    # WebhookBeam expects a 'text' field with the main message
    # and optional metadata in a 'metadata' object
    if ! webhook_data=$(jq -n \
        --arg text "$message" \
        --arg server "$hostname" \
        --arg timestamp "$timestamp" \
        --arg severity "$severity" \
        '{
            text: $text,
            metadata: {
                server: $server,
                timestamp: $timestamp,
                source: "security_monitor",
                severity: $severity
            }
        }'); then
        log_error "Failed to create JSON data for webhook"
        return 1
    fi
    
    # Log the JSON payload being sent
    log_info "Sending webhook payload: $webhook_data"
    
    # Format the JSON for better readability in logs
    echo "JSON Payload:" >> "$LOG_FILE"
    echo "$webhook_data" | jq '.' >> "$LOG_FILE"
    
    # Send the notification to WebhookBeam
    if ! curl -s -X POST \
        -H "Content-Type: application/json" \
        -d "$webhook_data" \
        --max-time 10 \
        "$WEBHOOK_URL" > /dev/null; then
        log_error "Failed to send webhook notification"
        return 1
    fi
    
    log_info "Webhook notification sent successfully"
    
    return 0
}

# -----------------------------------------------------------------------------
# Check Dependencies
# -----------------------------------------------------------------------------
check_dependencies() {
    local missing_deps=()
    
    # Check for required commands
    for cmd in jq curl inotifywait grep tail; do
        if ! command -v "$cmd" &> /dev/null; then
            missing_deps+=("$cmd")
        fi
    done
    
    # If any dependencies are missing, log and exit
    if [ ${#missing_deps[@]} -gt 0 ]; then
        log_error "Missing required dependencies: ${missing_deps[*]}"
        log_info "Please install missing dependencies. For example:"
        log_info "  - jq: For JSON processing"
        log_info "  - curl: For sending HTTP requests"
        log_info "  - inotifywait: For file monitoring (inotify-tools package)"
        exit 1
    fi
    
    # Check if log directory exists and is writable
    log_dir=$(dirname "$LOG_FILE")
    if [ ! -d "$log_dir" ]; then
        log_warning "Log directory $log_dir does not exist. Attempting to create it..."
        if ! mkdir -p "$log_dir"; then
            log_error "Failed to create log directory $log_dir"
            exit 1
        fi
    fi
    
    if [ ! -w "$log_dir" ]; then
        log_error "Log directory $log_dir is not writable"
        exit 1
    fi
    
    # Check if watch directory exists
    if [ ! -d "$WATCH_DIR" ]; then
        log_warning "Watch directory $WATCH_DIR does not exist. Attempting to create it..."
        if ! mkdir -p "$WATCH_DIR"; then
            log_error "Failed to create watch directory $WATCH_DIR"
            exit 1
        fi
    fi
    
    # Check if audit log exists
    if [ ! -f "$AUDIT_LOG" ]; then
        log_warning "Audit log $AUDIT_LOG does not exist. Shell monitoring will be disabled."
    fi
    
    return 0
}

# -----------------------------------------------------------------------------
# File Monitoring Function
# -----------------------------------------------------------------------------
monitor_files() {
    log_info "Starting file monitoring on $WATCH_DIR"
    
    # Create regex pattern for dangerous extensions
    local ext_pattern
    ext_pattern=$(IFS="|"; echo "${DANGEROUS_EXTENSIONS[*]}")
    
    # Start monitoring for file creation/movement
    inotifywait -m -q -e create,moved_to --format '%e %w%f' "$WATCH_DIR" | while read -r event file; do
        # Check if file has a dangerous extension
        if [[ "$file" =~ \.(${ext_pattern})$ ]]; then
            # Get file details
            local filename
            filename=$(basename "$file")
            local extension="${filename##*.}"
            local file_info
            
            if file_info=$(stat -c "Owner: %U, Group: %G, Permissions: %A, Size: %s bytes" "$file" 2>/dev/null); then
                log_info "Suspicious file details - $file_info"
            fi
            
            # Send alert notification
            send_notification "🚨 POTENTIAL WEBSHELL UPLOADED: A .$extension file named '$filename' was created in $WATCH_DIR."
            
            # Optional: You could add additional actions here, such as:
            # - Moving the file to a quarantine directory
            # - Changing permissions to prevent execution
            # - Running a malware scan on the file
        fi
    done
}

# -----------------------------------------------------------------------------
# Shell Process Monitoring Function
# -----------------------------------------------------------------------------
monitor_shell() {
    # Skip if audit log doesn't exist
    if [ ! -f "$AUDIT_LOG" ]; then
        log_warning "Skipping shell monitoring because $AUDIT_LOG does not exist"
        return 1
    fi
    
    log_info "Starting shell monitoring using $AUDIT_LOG"
    
    # Monitor audit log for shell creation by www-data user
    tail -f -n 0 "$AUDIT_LOG" | grep --line-buffered -E "key=www-data-shell" | while read -r line; do
        # Extract useful information from the log entry
        local pid
        pid=$(echo "$line" | grep -oP 'pid=\K[0-9]+' || echo "unknown")
        local timestamp
        timestamp=$(date)
        
        # Send alert notification
        send_notification "🚨 UNAUTHORIZED SHELL: User www-data appears to have started a shell process (PID: $pid). Detected at $timestamp."
        
        # Log the full audit entry for reference
        log_info "Full audit log entry: $line"
        
        # Optional: You could add additional actions here, such as:
        # - Automatically killing the suspicious process
        # - Taking a snapshot of system state for forensics
    done
}

# -----------------------------------------------------------------------------
# Cleanup Function (for signal handling)
# -----------------------------------------------------------------------------
cleanup() {
    log_info "Shutting down security monitor..."
    
    # Kill all background processes in this script's process group
    kill "$(jobs -p)" 2>/dev/null
    
    exit 0
}

# -----------------------------------------------------------------------------
# Main Function
# -----------------------------------------------------------------------------
main() {
    log_info "Starting security monitor script..."
    
    # Check dependencies before proceeding
    check_dependencies
    
    # Register signal handlers for graceful shutdown
    trap cleanup SIGINT SIGTERM
    
    # Start monitoring functions in background
    monitor_files &
    FILE_MONITOR_PID=$!
    
    monitor_shell &
    SHELL_MONITOR_PID=$!
    
    # Verify monitors started successfully
    sleep 1
    if ! ps -p $FILE_MONITOR_PID > /dev/null; then
        log_error "File monitoring failed to start"
    else
        log_info "File monitoring started successfully (PID: $FILE_MONITOR_PID)"
    fi
    
    if ! ps -p $SHELL_MONITOR_PID > /dev/null; then
        log_warning "Shell monitoring failed to start"
    else
        log_info "Shell monitoring started successfully (PID: $SHELL_MONITOR_PID)"
    fi
    
    log_info "Security monitor running. Press Ctrl+C to stop."
    
    # Wait for all background jobs to finish (they won't unless terminated)
    wait
}

# Start the script
main