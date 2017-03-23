## Summary ##

Datacenter management module

## Minimum Requirements ##

	python-pexpect
	python-argparse
	telnet
	net-snmp
	net-snmp-utils

## Functions ##

* Ability to suspend/unsuspend products like dedicated server or colocation with port shutdown or null-route.

## Installation

Copy files in to root directory of WHMCS

Add crontab record

0 1 * * *  php -q /root-directory-of-your-whmcs/modules/servers/dcmgmt/cron.php

## Configure

1. Add custom fields in to WHMCS:

	name:	interface
	type:	text box
	valid:	^(gi|vlan|vl)(\/?\d+)+$

	name:	customerip
	type:	text box
	valid:	^(\d+)\.(\d+)\.(\d+)\.(\d+)$

2. Configure unprivileged user to use interface shutdown

	aaa new-model
	aaa authentication login default local
	aaa authorization exec default local

	privilege interface level 1 shutdown
	privilege configure level 1 interface
	privilege configure level 1 ip route
	privilege exec level 1 configure terminal

	username whmcs privilege 1 secret YOUR_SECRET_PASSWORD

3. Configure login and password in to WHMCS -> Servers

	Access hash is used for community and other parameters

	<dcmgmt>
	  <snmpver>1|2c|3</snmpver>
	  <community>public</community>
	</dcmgmt>
	
## Known Issues

* cant suspend service if called from cron

- ERROR: Manual Suspension Required - ERROR from backend: Traceback (most recent call last): 
  File "/home/innovanet/public_html/modules/servers/dcmgmt/lib/gateway.py", line 85, in connect(args.routerip,args.username,args.password) 
  File "/home/innovanet/public_html/modules/servers/dcmgmt/lib/gateway.py", line 8, in connect process=pexpect.spawn('telnet '+routerip) 
  File "/usr/lib/python2.6/site-packages/pexpect.py", line 429, in __init__ self._spawn (command, args) 
  File "/usr/lib/python2.6/site-packages/pexpect.py", line 529, in _spawn raise ExceptionPexpect('Error! pty.fork() failed: ' + str(e)) pexpect.ExceptionPexpect: Error! pty.fork() failed: out of pty devices
	
* Check if SHELL variable in crontab is equal to not restricted shell like jailshell or just add SHELL="/bin/bash"
## TODO ##

* Add support of more switch types
* Use of SSH where is possible