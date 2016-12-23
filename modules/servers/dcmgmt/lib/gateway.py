#!/usr/bin/env python

import argparse
import pexpect

class gateway:
    def __init__(self, routerip,username,password):
        self.hwtype = 'cisco'
        self.p = pexpect.spawn('telnet '+routerip)
        i = self.p.expect(['Username:', 'Press any key to continue'])
        if i==0:
            self.hwtype='cisco'
            self.p.sendline(username)
            self.p.expect_exact('Password:')
            self.p.sendline(password)
        elif i==1:
            self.hwtype='hp-switch'
            self.p.sendline('')
            self.p.expect_exact('Username:')
            self.p.sendline(username)
            self.p.expect_exact('Password:')
            self.p.sendline(password)
        
        i=self.p.expect_exact(['>', '#', 'Authentication failed.', 'Invalid password'])
        if i>=2:
            print "ERR: % Authentication failed."
            exit(1)
        self.p.sendline('')
        
    def disconnect(self):
        self.p.sendline('end')
        self.p.expect_exact(['>', '#'])
        self.p.sendline('exit')
        if self.hwtype == 'hp-switch':
            self.p.sendline('exit')
            self.p.sendline('y')

    def nullroute(self, action,customerip):
        if self.hwtype == 'hp-switch':
            print "Unsupported function nullroute on "+self.hwtype
            return
        
        self.p.expect_exact('>')
        self.p.sendline('conf t')
        self.p.expect_exact('(config)>')
        if action=='suspend':
            self.p.sendline('ip route '+customerip+' 255.255.255.255 null0')
            self.p.expect_exact('(config)>')
            print "OK"
        else:
            self.p.sendline('no ip route '+customerip+' 255.255.255.255 null0')
            i=self.p.expect_exact(['(config)>','%No matching route to delete'])
            if i==1:
                print "ERR: %No matching route to delete"
            else:
                print "OK"
        
    def shutdownport(self, action,interface):
        self.p.expect_exact(['>', '#'])
        self.p.sendline('conf t')
        self.p.expect_exact(['(config)>', '(config)#'])
        if self.hwtype=='hp-switch':
            self.p.sendline('interface '+interface)
            self.p.expect_exact('(eth-'+interface+')#')
            if action=='suspend':
                self.p.sendline('disable')
            else:
                self.p.sendline('enable')
        else:
            self.p.sendline('interface '+interface)
            self.p.expect_exact('(config-if)>')
            if action=='suspend':
                self.p.sendline('shutdown')
            else:
                self.p.sendline('no shutdown')
        print "OK"

def help():
    print "Usage ./gateway.pl --routerip=1.1.1.1 --username=username --password=password --action=(suspend|unsuspend) --type=(shutdownport|nullroute) [--customerip=2.2.2.2] [--interface=gi1/1]"
    exit(0)

parser=argparse.ArgumentParser(description="Gateway to L3 switch")
parser.add_argument("--routerip", required=True)
parser.add_argument("--username", required=True)
parser.add_argument("--password", required=True)
parser.add_argument("--action", required=True)
parser.add_argument("--type", required=True)
parser.add_argument("--customerip")
parser.add_argument("--interface")
args=parser.parse_args()

if args.type == "nullroute":
    if args.customerip == "" or args.customerip == None:
        print "ERR: empty --customerip"
        exit(1)
    
    c = gateway(args.routerip, args.username, args.password)
    c.nullroute(args.action, args.customerip)
    c.disconnect()
elif args.type == "shutdownport":
    if args.interface == "" or args.interface == None:
        print "ERR: empty --interface"
        exit(1)
    
    c = gateway(args.routerip, args.username, args.password)
    c.shutdownport(args.action, args.interface)
    c.disconnect()
else:
    help