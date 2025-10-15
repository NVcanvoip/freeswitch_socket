#!/usr/bin/bash
#
readonly PROGNAME=$(basename $0)
readonly PROGDIR=$(readlink -m $(dirname $0))

LOGGER=/usr/bin/logger
ECHO=/bin/echo
DATE=/bin/date
MKDIR=/bin/mkdir
SED=/bin/sed
AWS=/usr/bin/aws
GREP=/bin/grep
AWK=/usr/bin/awk
RM=/bin/rm
CAT=/bin/cat
FIND=/usr/bin/find
SORT=/usr/bin/sort
COMM=/usr/bin/comm
WC=/usr/bin/wc
MV=/bin/mv
STAT=/usr/bin/stat

script_logging_opt="FILE"
script_logging_level="debug"

LOG_FILE="/var/tmp/vm-to-s3.log"
lock_file="/var/tmp/.vm-to-s3.lock"

s3_vm_bucket="s3://callertech-files/voicemail/"
s3_vm_domains="/var/tmp/s3_vm_domains.lst"
s3_vm_msgs="/var/tmp/s3_vm_messages.lst"
fs_vm_msgs="/var/tmp/fs_vm_messages.lst"
fs_vm_msgs_sorted="/var/tmp/fs_vm_messages.sorted.lst"
local_vm_msgs="/var/tmp/local_vm_messages.sorted.lst"
vm_local_path="/opt/ctpbx/voicemail/"
vm2del="/var/tmp/local_vm2del.lst"



function log() {
  # $1 - severity (debug|info|warning|error)
  # $2 - log message
  # $3 - LOG_OPT - special switch (e.g. USAGE)
  # EXAMPLE:
  # log debug "Info level log message into local6 syslog facility with prefix [debug]"
  # log info "Info level log message into local6 syslog facility with prefix [info]"
  # log warning "Info level log message into local6 syslog facility with prefix [warning]"
  # log error "Info level log message into local6 syslog facility with prefix [error]"

  # Lets prepare log priority and message:
  local log_priority=$1
  local log_message=$2

  # Check for special output or switch $3
  LOG_OPT="LOCAL6"
  if [[ -n $script_logging_opt ]];then
    LOG_OPT=$script_logging_opt
  fi
  if [[ -n $3 ]];then
    LOG_OPT=$3
  fi

  # USAGE - just echo and exit
  if [[ $LOG_OPT == "USAGE" ]];then   
    ${ECHO} ${log_message}
    log_priority=info
    log_message="Printing out help. ${log_message}"
    LOG_OPT="LOCAL6"
  fi

  # Check if logging level is set
  # if not, set it to info
  if [[ -z $script_logging_level ]];then
    script_logging_level=info
  fi

  # Declare available logging levels with priority
  declare -A levels=([trace]=0 [debug]=1 [info]=2 [warning]=3 [error]=4)

  #check if level exists
  [[ ${levels[$log_priority]} ]] || return 1
  
  #check if level is enough
  (( ${levels[$log_priority]} < ${levels[$script_logging_level]} )) && return 2

  case $LOG_OPT in
    "LOCAL6" )
      ${LOGGER} -i -t $0 -p local6.info "[${log_priority}] ${log_message}"
      ;;
    "FILE" )
      ${ECHO} "[$(${DATE} --rfc-3339=ns)] [${log_priority}] ${log_message}" >> $LOG_FILE
      ;;
    "STDOUT" )
      ${ECHO} "[$(${DATE} --rfc-3339=ns)] [${log_priority}] ${log_message}"
      ;;
  esac
}
    

##########################################################
#
# method: instance_check
#   Checks if the script is running already.
#
# Parameters:
#   (none)
#
# Returns:
#   0 (SUCCESS) if it is first instance of the script.
#   1 (FAIL)    if one instance is already running.
function instance_check()
{
  local fd=200
  eval "exec ${fd}>${lock_file}"
  /usr/bin/flock -n ${fd} && return 0 || return 1
}

function main () {
  log info "Starting VM-to-S3 processing."
  instance_check
  if [[ $? -ne 0 ]]; then
    log warning "Looks like another instance still runs. Exit."
    exit 1
  else
    log debug "Looks like I'm the first instance. Continue."
  fi
  
  log info "Getting list of files already in S3."
  
  ${RM} -f ${s3_vm_domains}
  ${RM} -f ${s3_vm_msgs}
  ${RM} -f ${fs_vm_msgs}
  ${RM} -f ${fs_vm_msgs_sorted}
  
  
  ${AWS} s3 ls ${s3_vm_bucket} | ${GREP} -e "PRE" | ${GREP} -E -e "d[0-9]*.callertech.net" | ${AWK} '{print $2}' > ${s3_vm_domains}

  for d in `${CAT} ${s3_vm_domains}`
  do
    ${AWS} s3 ls ${s3_vm_bucket}${d} | ${AWK} -v dom="$d" '{print dom $NF}' >> ${s3_vm_msgs}
  done

  log info "Launching PHP script to process VMs in FreeSwitch."
  /usr/bin/php -f /var/www/html/local_scripts/ctpbx_fs_get_vm_messages.new.php 
  
  log info "PHP script completed."
  log info "Cleaning up local copy of VMs."
  if [[ -f ${fs_vm_msgs} ]]; then
    ${CAT} ${fs_vm_msgs} | ${SORT} > ${fs_vm_msgs_sorted}
    ${FIND} ${vm_local_path} -type f -name '*.wav' | ${SORT} > ${local_vm_msgs}
    ${COMM} -23 ${local_vm_msgs} ${fs_vm_msgs_sorted} > ${vm2del}
    ret=$(${WC} -l ${vm2del} | ${AWK} '{print $1}')
    log info "Got list of ${ret} files to be removed."
    for f in $(${CAT} ${vm2del})
    do
      log debug "Checking ${f}."
      if [[ -f ${f} ]]; then
        log error "File to be removed ${f} does not exist, skipping."
      else
        ts_file=$(${STAT} -c '%Y' ${f})
        ts_now=$(${DATE} '+%s')
        gap="$((ts_now-ts_file))"
        if [[ "$gap" -gt 3600 ]]; then
          log debug "File ${f} is old enough to be removed."
          ${RM} -f ${f}
        else
          log debug "File ${f} seems to be too fresh, skipping."
        fi
      fi
    done
    log info "Finished local clean up."
  else 
    log info "File with a list of FreeSwitch stored voicemails doesn't exist - will not clean up."
  fi
  
  log info "Housekeeping of the logs."
  ${FIND} /var/tmp/ -type f -name 'vm-to-s3.log.*' -mtime +2 -exec rm -f {} \;
  ${FIND} /var/tmp/ -type f -name 'ctpbx_vm_mon*.log' -mtime +2 -exec rm -f {} \;
  log info "VM-to-S3 finished."
  if [[ -f ${LOG_FILE} ]]; then
    rotate_log_name=${LOG_FILE}.$(date "+%Y%m%d_%H%M%S")
    log debug "Rotating log: /usr/bin/mv ${LOG_FILE} ${rotate_log_name}"
    ${MV} ${LOG_FILE} ${rotate_log_name}
  fi
}

main  "$@"
