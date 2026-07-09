<?php
/**
 * Addresses should be in human readable format as a single IP (e.g. 1.2.3.4) or CIDR (e.g. 1.2.3.4/32)
 */
$wfIPWhitelist = array(
	'private' => array(
		//We've modified this and removed some addresses which may be routable on the Net and cause auto-whitelisting.
		//'0.0.0.0/8',			#Broadcast addr
		'10.0.0.0/8',			#Private addrs
		//'100.64.0.0/10',		#carrier-grade-nat for comms between ISP and subscribers
		'127.0.0.0/8',			#loopback
		//'169.254.0.0/16',		#link-local when DHCP fails e.g. os x
		'172.16.0.0/12',		#private addrs
		'192.0.0.0/29',			#used for NAT with IPv6, so basically a private addr
		//'192.0.2.0/24',		#Only for use in docs and examples, not for public use
		//'192.88.99.0/24',		#Used by 6to4 anycast relays
		'192.168.0.0/16',		#Used for local communications within a private network
		//'198.18.0.0/15',		#Used for testing of inter-network communications between two separate subnets
		//'198.51.100.0/24',	#Assigned as "TEST-NET-2" in RFC 5737 for use solely in documentation and example source code and should not be used publicly.
		//'203.0.113.0/24',		#Assigned as "TEST-NET-3" in RFC 5737 for use solely in documentation and example source code and should not be used publicly.
		//'224.0.0.0/4',		#Reserved for multicast assignments as specified in RFC 5771
		//'240.0.0.0/4',		#Reserved for future use, as specified by RFC 6890
		//'255.255.255.255/32',	#Reserved for the "limited broadcast" destination address, as specified by RFC 6890
	),
	'wordfence' => array(
		'54.68.32.247', // Central @ AWS
		'44.235.211.232',
		'54.71.203.174'
	),
);
