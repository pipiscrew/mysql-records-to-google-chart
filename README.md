# mysql-records-to-google-chart

We need to illustrate the progress, right ?

First set the needed parameters

![alt text](https://github.com/pipiscrew/mysql-records-to-google-chart/blob/master/screenshot1.png "Screenshot")


then draw it with - [Google Charts](https://developers.google.com/chart/)<br>
<br>
![alt text](https://github.com/pipiscrew/mysql-records-to-google-chart/blob/master/screenshot2.png "Screenshot2")
<br><br>
-- the linear chart --
<br><br>
![alt text](https://github.com/pipiscrew/mysql-records-to-google-chart/blob/master/screenshot3.png "Screenshot3")

on the last tab you can see the actual records by database with the help of [wenzhixin](https://github.com/wenzhixin/bootstrap-table)<br><br><br>
![alt text](https://github.com/pipiscrew/mysql-records-to-google-chart/blob/master/screenshot5.png "Screenshot5")
<br><br>
all are structured and commented. Optimization is not done.

<br><br>
The sample made with this table :
```sql
CREATE TABLE `jobs` (
  `rec_id` int(11) NOT NULL AUTO_INCREMENT,
  `USER_NAME` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DATE_CREATED` datetime DEFAULT NULL,
  `COUNTRY` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `QUEUE` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DELIVERED` smallint(10) DEFAULT NULL,
  PRIMARY KEY (`rec_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```
<br><br>
##This project uses the following 3rd-party dependencies :<br>
-[Bootstrap](http://getbootstrap.com)<br>
-[bootstrap-selector](https://github.com/pipiscrew/bootstrap-selector)<br>
-[Datepicker for Bootstrap](https://github.com/uxsolutions/bootstrap-datepicker)<br>
-[bootstrap-table](https://github.com/wenzhixin/bootstrap-table)<br>
-[YaLinqo](https://github.com/Athari/YaLinqo)<br>
-[Google Charts](https://developers.google.com/chart/)
<br><br><br>
##This project is no longer maintained
<br>
Copyright (c) 2017 [PipisCrew](http://pipiscrew.com)

Licensed under the [MIT license](http://www.opensource.org/licenses/mit-license.php).
