<?php

class LLAR_cidr_check {

	public function match($ip, $cidr) {

		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {

			return false;
		}

		$c = explode('/', $cidr);
		$subnet = isset($c[0]) ? $c[0] : NULL;
		$mask = isset($c[1]) ? $c[1] : NULL;
		if ($mask === null) {
			$mask = 32;
		}

		return $this->IPv4Match($ip, $subnet, $mask);
	}

	private function IPv4Match($address, $subnetAddress, $subnetMask) {

		if (!filter_var($subnetAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || $subnetMask === NULL || $subnetMask === "" || $subnetMask < 0 || $subnetMask > 32) {
			return false;
		}

		$address = ip2long($address);
		$subnetAddress = ip2long($subnetAddress);
		$mask = -1 << (32 - $subnetMask);
		$subnetAddress &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
		return ($address & $mask) == $subnetAddress;
	}

}