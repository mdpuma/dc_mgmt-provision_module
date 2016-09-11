# WHMCS Sample Provisioning Module #

## Summary ##

Datacenter management module

## Minimum Requirements ##

python-pexpect
python-argparse

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

