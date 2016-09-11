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

Add custom fields:

	name:	interface
	type:	text box
	valid:	^(gi|vlan)(?/?\d+)+$

	name:	customerip
	type:	text box
	valid:	^(\d+)\.(\d+)\.(\d+)\.(\d+)$

## TODO ##

* Add support of more switch types
* Use of SSH where is possible