CREATE TABLE IF NOT EXISTS `stocks`
(
`symbol` varchar(10) NOT NULL,
`company` varchar(100) NOT NULL,
`price` float(5) NOT NULL,
`high` float(5) NOT NULL,
`low` float(5) NOT NULL,
`volume` bigint(15) NOT NULL,
`change` varchar(10) NOT NULL,
`timestamp` timestamp NOT NULL,

PRIMARY KEY (`stock_symbol`)

) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;