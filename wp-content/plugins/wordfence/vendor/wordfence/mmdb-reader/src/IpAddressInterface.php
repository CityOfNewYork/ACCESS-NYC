<?php

namespace Wordfence\MmdbReader;

interface IpAddressInterface {

	/**
	 * Get the human-readable (presentation format) version of the address
	 * @return string
	 * @see inet_ntop
	 */
	public function getHumanReadable();

	/**
	 * Get the binary (network format) version of the address
	 * @return string
	 * @see inet_pton
	 */
	public function getBinary();

	/**
	 * Get the type of the address (IPv4 or IPv6)
	 * @return int 4 for IPv4 and 6 for IPv6
	 */
	public function getType();

}