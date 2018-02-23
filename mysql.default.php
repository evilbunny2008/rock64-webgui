<?php
	$link = mysqli_connect("localhost", "username", "password", "database");

/*
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `dnslog` (
  `qid` int(10) UNSIGNED NOT NULL,
  `when` datetime NOT NULL,
  `qtype` enum('A','AAAA') NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `client` varchar(255) NOT NULL,
  `status` enum('cached','blocked','forwarded') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `dnslog`
  ADD PRIMARY KEY (`qid`,`when`);
*/
