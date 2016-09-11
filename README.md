## Summary ##

Datacenter management module

## Minimum Requirements ##

	python-pexpect
	python-argparse
	telnet

## Functions ##

* Ability to suspend/unsuspend products like dedicated server or colocation with port shutdown or null-route.

## Installation

Copy files in to root directory of WHMCS

## Configure

1. Add custom fields in to WHMCS:

	name:	interface
	type:	text box
	valid:	^(gi|vlan)(?/?\d+)+$

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

## TODO ##

* Add support of more switch types
* Use of SSH where is possible