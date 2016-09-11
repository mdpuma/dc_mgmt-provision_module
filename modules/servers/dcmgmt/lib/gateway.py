#!/usr/bin/env python

import argparse
import pexpect
from sys import argv

login='danroot'
password='acREd91Sim@'

def connect(routerip):
	global process
	process=pexpect.spawn('telnet '+routerip)
	process.expect_exact('Username:')
	process.sendline(login)
	process.expect_exact('Password:')
	process.sendline(password)

def disconnect():
	global process
	process.sendline('end')
	process.expect_exact('#')
	process.sendline('exit')

def nullroute(action,customerip):
	global process
	process.expect_exact('#')
	process.sendline('conf t')
	process.expect_exact('(config)#')
	if action=='suspend':
		process.sendline('ip route '+customerip+' 255.255.255.255 null0')
		process.expect_exact('(config)#')
		print "OK"
	else:
		process.sendline('no ip route '+customerip+' 255.255.255.255 null0')
		i=process.expect_exact(['(config)#','%No matching route to delete'])
		if i==1:
			print "ERR: %No matching route to delete"
		else:
			print "OK"
	
def shutdownport(action,interface):
	global process
	process.expect_exact('#')
	process.sendline('conf t')
	process.expect_exact('(config)#')
	if action=='suspend':
		process.sendline('interface '+interface)
		process.expect_exact('(config-if)#')
		process.sendline('shutdown')
	else:
		process.sendline('interface '+interface)
		process.expect_exact('(config-if)#')
		process.sendline('no shutdown')
	print "OK"

def help():
	print "Usage ./gateway.pl --routerip=1.1.1.1 --action=(suspend|unsuspend) --type=(shutdownport|nullroute) [--customerip=2.2.2.2] [--interface=gi1/1]"
	exit(0)

parser=argparse.ArgumentParser(description="Gateway to cisco router")
parser.add_argument("--routerip", required=True)
parser.add_argument("--action", required=True)
parser.add_argument("--type", required=True)
parser.add_argument("--customerip")
parser.add_argument("--interface")
args=parser.parse_args()
process=False;

if args.type == "nullroute":
	if args.customerip == "" or args.customerip == None:
		print "ERR: empty --customerip"
		exit(1)
	
	connect(args.routerip)
	nullroute(args.action,args.customerip)
	disconnect()
elif args.type == "shutdownport":
	if args.interface == "" or args.interface == None:
		print "ERR: empty --interface"
		exit(1)
	
	connect(args.routerip)
	shutdownport(args.action,args.interface)
	disconnect()
else:
	help