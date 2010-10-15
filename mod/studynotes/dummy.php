<?php
/*
 * 	Copyright (C) 2008 Fabian Gebert <fabiangebert@mediabird.net>
 *
 *	This file is part of Mediabird X.
 *
 *	Mediabird X is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	Mediabird X is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with Mediabird X.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Define the proxy or cache expire time 
$ExpireTime = 3600; // seconds (= one hour)
// Set cache/proxy informations:
header('Cache-Control: max-age=' . $ExpireTime); // must-revalidate
header('Expires: '.gmdate('D, d M Y H:i:s', time()+$ExpireTime).' GMT');

 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" type="text/css" href="css/dummy.css">
		<title>Mediabird Web2.0-Learning</title>
	</head>
	<body>
		<span></span>
	</body>
</html>