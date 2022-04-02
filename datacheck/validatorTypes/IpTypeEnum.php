<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\datacheck\validatorTypes;

enum IpTypeEnum
{
	case ip;
	case ipv4;
	case ipv6;
}