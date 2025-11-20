/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.8-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: rohabae1_rota
-- ------------------------------------------------------
-- Server version	11.4.8-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(7,'admin','info@rohab.ae','$2y$10$BMwkmw3O7G82OKbr1VO.6O6GSznZjrEd5yB/FWpBvX47XkzT6PDw2','admin','active','2025-08-24 21:01:26','2025-09-15 10:39:54','admin',NULL,NULL),
(8,'manager','manager@security.com','$2y$10$8X1Xkal0tOpZNILT.B0Gue72DgEq62PVYomuUQzVOc/65zSC.8Jg2','admin','active','2025-08-24 21:01:26','2025-08-30 01:25:59','manager',NULL,NULL),
(9,'john_doe','john.doe@security.com','$2y$10$GU0ZwuMYI/lS0wHp4JAF6e4nw.gSb3Mm5cp7t5e7OgNImh7sLo8.q','officer','active','2025-08-24 21:01:26','2025-08-30 01:25:59','john_doe',NULL,NULL),
(10,'07362434757','muhammad.w@rohab.ae','$2y$10$GU0ZwuMYI/lS0wHp4JAF6e4nw.gSb3Mm5cp7t5e7OgNImh7sLo8.q','officer','active','2025-08-24 21:01:26','2025-09-09 23:55:36','07362434757',NULL,NULL),
(12,'mike_johnson','mike.johnson@security.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','officer','active','2025-08-25 16:36:06','2025-08-30 01:25:59','mike_johnson',NULL,NULL),
(13,'farrukh','farrukh.shaikh81@gmail.com','$2y$10$GU0ZwuMYI/lS0wHp4JAF6e4nw.gSb3Mm5cp7t5e7OgNImh7sLo8.q','admin','active','2025-08-24 21:01:26','2025-08-30 01:25:59','farrukh',NULL,NULL),
(15,'07939326536','Younusdaniyal9@gmail.com','$2y$10$gv2yH58tmaluDt0N0/NLi.c5jNScLPrEcUUpEHTdZvyjv.yKyhNfS','officer','active','2025-09-02 22:32:32','2025-09-09 23:55:36','07939326536','466403','2025-09-02 22:43:02'),
(17,'07901602943','krishnarawat655@outlook.com','$2y$10$EECYBNkR6J7fB/iuM47XNuA3HK5DMLKkhpMM319gXRT3WBpTMw6cy','officer','active','2025-09-08 16:29:07','2025-09-09 23:55:36','07901602943','481912','2025-09-08 16:29:07'),
(18,'07428240702','21020589@lums.edu.pk','$2y$10$3OgwSovmMbI./YVb8GOhi.ewNPM/1uViuewKWT6.x1yv2DCebZpU.','officer','active','2025-09-08 16:49:17','2025-09-09 23:55:36','07428240702','148022','2025-09-08 16:49:17'),
(19,'07378186270','juttmj98@gmail.com','$2y$10$uXpEtlsCnJhn7zfBtxF0RuzIMQpEfSzh.QmmExnqPcFFHEj2AwytO','officer','active','2025-09-08 17:15:20','2025-09-09 23:55:36','07378186270','517208','2025-09-08 17:15:20'),
(20,'07823663416','musadawar0024@gmail.com','$2y$10$wGJ0Gg0JDS.0lHH5AHozO.nEfh3pdZExzEvZOJggPa94ZpCmC4Jxy','officer','active','2025-09-08 17:51:06','2025-09-09 23:55:36','07823663416','414608','2025-09-08 17:51:06'),
(21,'07399847047','abdullahone2001@gmail.com','$2y$10$mjyLNXxQPE3gx4B1uHfyY.IwHfxNmP7wOICb2sfBF6aj8vg5CeeBy','officer','active','2025-09-08 19:31:03','2025-09-09 23:55:36','07399847047','003293','2025-09-08 19:31:03'),
(22,'07459861234','sfox2024k@gmail.com','$2y$10$ia/cbtsGAUNrTIHiRWoFO.sWR6evOH3PclM/EX39diHNvRRFuDOsa','officer','active','2025-09-08 19:37:18','2025-09-09 23:55:36','07459861234','064322','2025-09-08 19:37:18'),
(23,'07424872649','olukoyaemilolu@gmail.com','$2y$10$XwcTqNnLd1nyIBsVEcO.ZOGNOID./HU4K4HqwwGQnmRcF7leqrJeC','officer','active','2025-09-08 19:49:53','2025-09-09 23:55:36','07424872649','213297','2025-09-08 19:49:53'),
(24,'07783516097','Fzn1179@gmail.com','$2y$10$dZF/kk5Rr7AQaCQeMs3n7eZgdkG/mB4fkrZKUuhVhKUrWiTFbIaJS','officer','active','2025-09-08 20:10:35','2025-09-09 23:55:36','07783516097','573444','2025-09-08 20:10:35'),
(25,'07940150010','redoymehedi36@gmail.com','$2y$10$tKTAkjsJ9tnS8GEoR5DHEurF2TvaPNgHQeUPPxX9fDWz/mvtvQhZO','officer','active','2025-09-08 20:17:50','2025-09-09 23:55:36','07940150010','821377','2025-09-08 20:17:50'),
(26,'07462222099','degilot77@gmail.com','$2y$10$bHCXET0Jd21VwfuFzKEgKO0J8NRLiGPMfvgEgax1jN6WDlxVTceE2','officer','active','2025-09-08 20:28:23','2025-09-09 23:55:36','07462222099','016409','2025-09-08 20:28:23'),
(27,'07502131979','mrshamraja@gmail.com','$2y$10$G69ExDcu88aFmzIpLtl9C.dnLLuZu5ntMfwz.4e3oDOPIXG/NCCx2','officer','active','2025-09-08 20:36:25','2025-09-09 23:55:36','07502131979','976420','2025-09-08 20:36:25'),
(28,'07440365776','adigberiovie@gmail.com','$2y$10$ILhwoDa7Jr3So8.nHTIgzeM3KjAVzYQ4Dvlr5HqZa4pIyjsbuDLi.','officer','active','2025-09-08 21:02:24','2025-09-09 23:55:36','07440365776','360984','2025-09-08 21:02:24'),
(29,'07440355419','aneesfarooqkhokhar3@gmail.com','$2y$10$uKgkTlSTliynCxOpKySapelzx6AFYXVgf52kKYu/Bm9UKJdz2oD0q','officer','active','2025-09-08 21:57:37','2025-09-09 23:55:36','07440355419','250374','2025-09-08 21:57:37'),
(30,'07407219450','zak.mullen@icloud.com','$2y$10$7ZQEdeIkb41WblbeKDrGZOYRqbWvvfWYenNgYObXy2uXAD93NQ/P2','officer','active','2025-09-08 22:08:55','2025-09-09 23:55:36','07407219450','603740','2025-09-08 22:08:55'),
(31,'07482752408','asdaerwsdf@gmail.com','$2y$10$V60/Rn98GaeEBcj7rLbvXOQAOT23jrs.RIG4Pg/TBIrDqT0OLMnHW','officer','active','2025-09-08 22:26:41','2025-09-09 23:55:36','07482752408','612539','2025-09-08 22:26:41'),
(32,'07438549296','bsdk@gmail.com','$2y$10$gIZHySZmgzqqJadHOszv5u74gurJaOOoRThYteTwMHb.9.B6aB5Iy','officer','active','2025-09-08 22:39:14','2025-09-09 23:55:36','07438549296','261305','2025-09-08 22:39:14'),
(33,'07350152875','lpc@gmail.com','$2y$10$YZPZ/4sVn0i22vnf0icb/u1hfnXmHZdoYu99gTbazK6hUCizFl4fe','officer','active','2025-09-08 22:48:47','2025-09-09 23:55:36','07350152875','289346','2025-09-08 22:48:47'),
(34,'07438234377','hshahfujdsb@gmail.com','$2y$10$SW7ABirp7vqBxpnT3Vk7j.d211Vmppf4smVKfkWvlOxskRUXt1pI6','officer','active','2025-09-08 23:03:14','2025-09-09 23:55:36','07438234377','185446','2025-09-08 23:03:14'),
(35,'07392151435','dfhjsbtg@gmail.com','$2y$10$sZEf9ef.JK8im30N7EZ2veVotPpHoUQQm4mu/aQUp9jWkBA1QdgL2','officer','active','2025-09-08 23:07:40','2025-09-09 23:55:36','07392151435','249331','2025-09-08 23:07:40'),
(36,'07308219076','ashrfjhe@gmail.com','$2y$10$L85jW4KQg6dMkR2Z7pN2VuTLoOAQS5DCZG0t8bsOcPACv2/yOma/q','officer','active','2025-09-08 23:11:58','2025-09-09 23:55:36','07308219076','800559','2025-09-08 23:11:58'),
(37,'07960906857','dkjieijrfi@gmail.com','$2y$10$vB6AkI/L81KD1A66sAPyR.pT7V9f3VGSGCEdoYKqD9IuVKEOEWbhO','officer','active','2025-09-08 23:17:16','2025-09-09 23:55:36','07960906857','154115','2025-09-08 23:17:16'),
(38,'07747958397','sdklfhjkd@gmail.com','$2y$10$w5BA.A1AE6dwXD0o7j/b4ORb1OJc3eQW/2xUsvuFjli4x9SvRqUlq','officer','active','2025-09-08 23:20:50','2025-09-09 23:55:36','07747958397','289429','2025-09-08 23:20:50'),
(39,'07444152583','kjdfjkbnj@gmail.com','$2y$10$qiP0YjAjBdO9DZqd.ErU1eh50wj78IkpApQwVzvSvEz609epwQnj6','officer','active','2025-09-08 23:24:23','2025-09-09 23:55:36','07444152583','591012','2025-09-08 23:24:23'),
(40,'07440460413','fedsjafbn@gmail.com','$2y$10$JpXMcya8RWOiUDBJ7g/GAeZ4J5MY8YjIKiJkSSMQzAgIEWbhcAN.6','officer','active','2025-09-08 23:30:09','2025-09-09 23:55:36','07440460413','940515','2025-09-08 23:30:09'),
(41,'07506397360','sjhfjashfjs2@gmail.com','$2y$10$1cLY0TFoFEE9PWJaM6VqD.7xiMkq9sWSrl5jvLwHZu4YgnOuyzgza','officer','active','2025-09-08 23:36:23','2025-09-09 23:55:36','07506397360','762474','2025-09-08 23:36:23'),
(42,'07888857507','khfjdshjgew@gmail.com','$2y$10$0fHWMA.T5Sw.Vc4uLYCRC.aDzUe8Bm7jMYPjONOPVnJfdQoCG3euO','officer','active','2025-09-08 23:41:03','2025-09-09 23:55:36','07888857507','800829','2025-09-08 23:41:03'),
(43,'07920709115','kjahsfjkahw@gmail.com','$2y$10$PuyOKL8ktBWBGPSJ71q.vO8xksCEqmK99mKkkaTQKocEho7ccZswa','officer','active','2025-09-08 23:50:50','2025-09-09 23:55:36','07920709115','333516','2025-09-08 23:50:50'),
(44,'07818961870','iadjah@gmail.com','$2y$10$D09Y4SnXiC4FjTrQYdpQtOIGUCLnM5hEil6a8VBCOCfxZsmLMAqLC','officer','active','2025-09-08 23:53:38','2025-09-09 23:55:36','07818961870','169919','2025-09-08 23:53:38'),
(45,'07388296438','ewqrkjyu@gmail.com','$2y$10$AjTyGWQ.2WyEqwJxIQHMmO8J9A6GVNa70T.d11TILOM0sI5SDbHsG','officer','active','2025-09-08 23:57:00','2025-09-09 23:55:36','07388296438','351737','2025-09-08 23:57:00'),
(46,'07572544776','kjdsfhb@gmail.com','$2y$10$FCiZOk9/VelP2IxVoPkpduIVo73GMSC78reS2NZUM7NEp1EDMbJXW','officer','active','2025-09-08 23:58:29','2025-09-09 23:55:36','07572544776','597178','2025-09-08 23:58:29'),
(47,'07511917380','bfewnf@gmail.com','$2y$10$Q3.vVbLX7h9NkpRKamYqnuzwPs.leOomPtc1CL.osZzW042NRnjNi','officer','active','2025-09-09 00:00:07','2025-09-09 23:55:36','07511917380','720121','2025-09-09 00:00:07'),
(48,'07956948534','fvbnejhgf@gmail.com','$2y$10$cfFsqR68T3TT0LTDRHLOSek4cnlKYqEDeaAQnR./mFKqk9FxW/joW','officer','active','2025-09-09 00:01:25','2025-09-09 23:55:36','07956948534','443024','2025-09-09 00:01:25'),
(49,'07917057031','lkdshfakjwb@gmail.com','$2y$10$j9gfTQWSaG4EyitcWYHI9Oj2xmdGB09Q.0ucqf5P7wXrepr8HWjTq','officer','active','2025-09-09 00:02:38','2025-09-09 23:55:36','07917057031','171188','2025-09-09 00:02:38'),
(50,'07940346247','wdvbhfc@gmail.com','$2y$10$SKqCR302VaocMfZaZxZx3.7xl5E5Y8sBjWIgLtMe5AkK2yBCTBud.','officer','active','2025-09-09 00:03:59','2025-09-09 23:55:36','07940346247','629776','2025-09-09 00:03:59'),
(51,'07706671887','ijfdjewhr@gmail.com','$2y$10$GpW3QBn.uOJeKIlARVtLuuW/uHmPEwrr9RlE52mwFH1XGWLiauoY6','officer','active','2025-09-09 21:24:13','2025-09-09 23:55:36','07706671887','397731','2025-09-09 21:24:13'),
(52,'07703324412','jdsfhjhjrfe@gmail.com','$2y$10$1r9QBmoMB588ZacCi0iRku4Oy2xQ508ZfWi4cdUFL.Ma8sFXFLBOe','officer','active','2025-09-09 21:34:08','2025-09-09 23:55:36','07703324412','984101','2025-09-09 21:34:08'),
(53,'07448236262','farrukhshaikh81@hotmail.com','$2y$10$btIgxc3I.ADpSYHA6fvUv.vlN/MYVaxxV1QJvHt/sMfl./cP0IKd.','officer','active','2025-09-10 15:40:55','2025-09-10 15:40:55','07448236262','036785','2025-09-10 15:40:55'),
(54,'07741070912','ksafj@gmail.com','$2y$10$kgirVzjGu//GkclhOmeb6OOCxsAY7PVztsxizsam27RlsNTpqA.He','officer','active','2025-09-12 18:38:38','2025-09-12 18:38:38','07741070912','571893','2025-09-12 18:38:38'),
(55,'07459936165','kedajstiuuh@gmail.com','$2y$10$IKOQGKvm8hZXNl5EZeGNg.6he/rzqcNFt9tYRFV9cN2nPod6/21MC','officer','active','2025-09-12 19:08:10','2025-09-12 19:08:10','07459936165','392446','2025-09-12 19:08:10');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `officers`
--

LOCK TABLES `officers` WRITE;
/*!40000 ALTER TABLE `officers` DISABLE KEYS */;
INSERT INTO `officers` VALUES
(19,10,'Muhammad','Waleed','muhammad.w@rohab.ae','07362 434757','','1014 7812 1811 5525','2027-05-01','Other',NULL,'Full-time',12.21,'20999070','04-29-09','','','','2025-08-25 04:54:11','2025-09-09 05:31:53','10002',NULL,'',NULL,'British',0,'',NULL,NULL,NULL,'','','Muhammad waleed',''),
(21,15,'Daniyal','Younus','Younusdaniyal9@gmail.com','','61 Acomb Court, Gateshead, Newcastle','1016 0265 8875 3812','2026-07-23','Work Visa','2025-11-01','Full-time',12.21,'16706961','11-04-00','','','','2025-09-02 22:32:32','2025-09-02 22:32:32','10004',NULL,'TL714834C',NULL,'Other',0,'','2025-07-01',NULL,NULL,' Newcastle','NE9 7AF','Daniyal Younus','Halifax'),
(23,17,'Krishna','Rawat ','krishnarawat655@outlook.com','','71 Bottetourt road  ','1012 0942 8680 4404','2028-01-15','Student Visa',NULL,'Full-time',12.21,'13614668','80-47-19','Himanshu Kotia','07252996143','','2025-09-08 16:29:07','2025-09-08 16:29:07','10005','2002-03-30','RZ075696A',NULL,'Other',0,'','2025-08-08',NULL,NULL,'Birmingham','B29 5TF','KRISHNA RAWAT','Bank of Scotland '),
(24,18,'Muhammad ','Dawood','21020589@lums.edu.pk','','8 Gledhow Valley Road Leeds','1018878978131443','2028-02-05','Student Visa',NULL,'Part-time',12.21,'57703168','77-14-07','','','','2025-09-08 16:49:17','2025-09-08 16:49:17','10006','1999-02-03','RZ199183D',NULL,'Other',0,'',NULL,NULL,NULL,'Leeds','LS84DP ','Muhammad Dawood','Llyods Bank'),
(25,19,'Mubasher','Hussain','juttmj98@gmail.com','','42 recreation view','1012 2587 2356 8275','2027-12-17','Student Visa',NULL,'Full-time',12.21,'93974995','20-25-40','','','','2025-09-08 17:15:20','2025-09-08 17:15:20','10007','2006-01-25','RZ149157D',NULL,'Other',0,'',NULL,NULL,NULL,'Leeds','LS11 0AP','Mubasher hussain','Barclays'),
(26,20,'Muhammad','Musa Anwar','musadawar0024@gmail.com','','39 Park Street','1015 3976 5509 2087','2026-11-28','Student Visa',NULL,'Part-time',12.21,'69234159','04-29-09','','','','2025-09-08 17:51:06','2025-09-08 17:51:06','10008','1992-05-05','TH352982D',NULL,'Other',0,'',NULL,NULL,NULL,'Coventry','CV6 5AT','Muhammad Anwar','Revolut Ltd'),
(27,21,'Abdullah','Khan','abdullahone2001@gmail.com','','Flat 1 Mull House,Himalayan Way','1018 4539 6868 3820',NULL,'Student Visa',NULL,'Part-time',12.21,'41424098','04-00-03','','','','2025-09-08 19:31:03','2025-09-08 19:31:03','10009','2001-01-17','RZ278850C',NULL,'Other',0,'',NULL,NULL,NULL,'Watford','WD18 6GJ','Abdullah Khan',''),
(28,22,' Muhammad','Qais','sfox2024k@gmail.com','','116 Fourth Avenue Bordesley Green','1019 5449 4192 9077','2028-03-19','Other',NULL,'Part-time',12.21,'54114821','04-00-03','','','','2025-09-08 19:37:18','2025-09-09 03:40:48','10010','1992-05-16','RZ640415D',NULL,'Other',0,'',NULL,NULL,NULL,'Birmingham','B9 5RQ','Muhammad Qais',''),
(29,23,'Emilolu','Olukoya','olukoyaemilolu@gmail.com','','56 Grace Road','1019280292029080','2027-03-25','Work Visa',NULL,'Full-time',12.21,'15256526','40-28-06','','','','2025-09-08 19:49:53','2025-09-09 05:19:09','10011','1982-09-05','TJ862424D',NULL,'Other',0,'',NULL,NULL,NULL,'LEICESTER','LE2 8AE','Emilolu Adebiyi Olukoya',''),
(30,24,'Faizan','Tariq','Fzn1179@gmail.com','','flat 1,12 bankfield road','1016 1933 0989 2170',NULL,'Student Visa',NULL,'Part-time',12.21,'20504563','11-00-82','','','','2025-09-08 20:10:35','2025-09-08 20:10:35','10012','1997-07-09','TH286683D',NULL,'Other',0,'',NULL,NULL,NULL,'Huddersfield ','HD1 3HR','Faizan Tariq',''),
(31,25,'Mehedi',' Hasan','redoymehedi36@gmail.com','','25 Edmund Road Southsea','1013 0297 3738 9294','2028-05-22','Student Visa',NULL,'Part-time',12.21,'74692852','04-29-09','','','','2025-09-08 20:17:50','2025-09-09 05:28:02','10013','1999-01-27','RZ582044A',NULL,'Other',0,'',NULL,NULL,NULL,'Portsmouth','PO4 0LL','Mehedi Hasan Redoy',''),
(32,26,'Degilot','Ngueyissadila','degilot77@gmail.com','','36 gower Street','1018011722923290',NULL,'Other',NULL,'Full-time',13.50,'53394832','20-89-15','','','','2025-09-08 20:28:23','2025-09-08 20:28:23','10014',NULL,'SP104649B',NULL,'British',0,'',NULL,NULL,NULL,'Oldham','OL13UR ','D B NGUEYISSADILA ',''),
(33,27,'Ahtsham','Shabbir','mrshamraja@gmail.com','','23 Oxford Street','1016 8594 4590 7408','2028-01-02','Work Visa',NULL,'Part-time',12.21,'46775757','04-29-09','','','','2025-09-08 20:36:25','2025-09-09 05:13:43','10015','1989-01-25','RZ371394C',NULL,'Other',0,'',NULL,NULL,NULL,'Rotherham','S65 2DR','Ahtsham Shabbir',''),
(34,28,'Ovie','Collins Adigberi','adigberiovie@gmail.com','','14 Close','1013 7960 3122 5356','2027-11-17','British',NULL,'Full-time',13.00,'17885386','11-07-56','','','','2025-09-08 21:02:24','2025-09-09 05:34:16','10016',NULL,'TK262909B',NULL,'Other',0,'',NULL,NULL,NULL,'Sunderland','SR4 6EN','Ovie Collins Adigberi',''),
(35,29,'Anees','Farooq','aneesfarooqkhokhar3@gmail.com','','Flat A3 Block A 54 Grainger Park Road upon Tyne','1017 7985 6411 5741','2027-12-30','Student Visa',NULL,'Full-time',12.21,'52865746','04-29-09','','','','2025-09-08 21:57:37','2025-09-09 03:56:42','10017','2002-02-23','RZ310864A',NULL,'Other',0,'',NULL,NULL,NULL,'Newcastle','NE4 8RQ','Anees Farooq',''),
(36,30,'Zak ','Mullen ','zak.mullen@icloud.com','','2 Shibleys Court, Fishers Lane','1014 7358 0287 4440','2028-04-03','British',NULL,'Part-time',12.21,'07066128','07-08-06','','','','2025-09-08 22:08:55','2025-09-09 05:58:32','10018',NULL,'PL763410C',NULL,'British',0,'',NULL,NULL,NULL,'Norwich','NR2 1EE','Zak Mullen',''),
(37,31,'Syed ','Iftikhar Ali','asdaerwsdf@gmail.com','','225 Alum rock road','1097 9044 5445 8382','2027-05-01','Student Visa',NULL,'Full-time',12.21,'42405260','77-85-59','','','','2025-09-08 22:26:41','2025-09-09 05:49:40','10019','1995-04-06','TH253807D',NULL,'Other',0,'',NULL,NULL,NULL,'Birmingham','B83 BH','Syed Shah',''),
(38,32,'Muhammad','Zaid','bsdk@gmail.com','','748 a stratford road','1011 1227 3156 9634',NULL,'British',NULL,'Full-time',12.21,'00643971','20-25-41','','','','2025-09-08 22:39:14','2025-09-08 22:39:14','10020','1999-10-07','TH249007D',NULL,'Other',0,'',NULL,NULL,NULL,'birmingham','B11 4BP','Muhammad Ziad',''),
(39,33,'ERIC','IREDIA','lpc@gmail.com','','60 Victoria mansions, South Lambeth road. Stockwell','1014 6306 7274 5994','2028-07-08','Work Visa',NULL,'Full-time',12.21,'16916961','23-01-20','','','','2025-09-08 22:48:47','2025-09-09 13:26:12','10021','1991-07-10','RZ970365B',NULL,'British',0,'',NULL,NULL,NULL,'London','SW8 1QX','Eric Iredia','Revolut Ltd'),
(40,34,'Talha','Yasin','hshahfujdsb@gmail.com','','','',NULL,'Student Visa',NULL,'Part-time',12.21,'','','','','','2025-09-08 23:03:14','2025-09-08 23:03:14','10022',NULL,'',NULL,'Other',0,'',NULL,NULL,NULL,'','','',''),
(41,35,'AKEEL','AHMAD','dfhjsbtg@gmail.com','','64, Applegarth Garth Drive','1017 3567 6028 9661','2028-01-23','Work Visa',NULL,'Part-time',12.21,'99460429','04-00-03','','','','2025-09-08 23:07:40','2025-09-09 03:55:18','10023','1993-07-22','RZ368165B',NULL,'Other',0,'',NULL,NULL,NULL,'Ilford','IG2 7TH','Akeel Ahmad',''),
(42,36,'Saair','Hassan','ashrfjhe@gmail.com','',':22 Fitzroy avenue','1018 0972 8306 2636','2027-04-18','Student Visa',NULL,'Full-time',12.21,'18722458','04-29-09','','','','2025-09-08 23:11:58','2025-09-09 05:46:39','10024','1994-03-13','TH220577B',NULL,'Other',0,'',NULL,NULL,NULL,'London',' LU3 1RS','Saair Hassan',''),
(43,37,'Umar','Salar','dkjieijrfi@gmail.com','','12 Hatchgate Gardens','1015 4447 4736 5877','2026-02-16','Dependent Visa',NULL,'Part-time',12.21,'20655759','20-26-89','','','','2025-09-08 23:17:16','2025-09-09 05:57:27','10025',NULL,'SX896869B',NULL,'Other',0,'',NULL,NULL,NULL,'Burnham','SL1 8DD','Mr Muhammad Salar',''),
(44,38,'Abdul','Wahid','sdklfhjkd@gmail.com','','114 windsor road slough flat 2','1014 2262 7238 4609','2028-03-26','British',NULL,'Part-time',12.21,'16875516','04-29-09','','','','2025-09-08 23:20:50','2025-09-09 03:35:19','10026','2003-01-01','RZ485317B',NULL,'Other',0,'',NULL,NULL,NULL,'Slough','SL1 2JA','Chaudhary Abdul Wahid',''),
(45,39,'Paul','Brown','kjdfjkbnj@gmail.com','','7 andoversford court biburyÂ close','1018 2622 2870 7927','2026-06-21','Dependent Visa',NULL,'Full-time',14.00,'28186099','07-02-46','','','','2025-09-08 23:24:23','2025-09-09 05:36:35','10027',NULL,'SE487562C',NULL,'British',0,'',NULL,NULL,NULL,'London','SE15Â 6AE','Paul Brown ',''),
(46,40,'Huraira','Bin Khalid','fedsjafbn@gmail.com','','556 Hanworth Road, Hounslow, ','1019 7130 9493 8908','2028-05-29','Student Visa',NULL,'Part-time',12.21,'85100811','','','','','2025-09-08 23:30:09','2025-09-09 05:24:27','10028',NULL,'RZ581605B',NULL,'Other',0,'',NULL,NULL,NULL,'London','TW4 5LH','Hurraira Khalid',''),
(47,41,'Roman','Robinson','sjhfjashfjs2@gmail.com','','71 Loughborough road','1013 9346 5344 5654','2028-01-12','Other',NULL,'Full-time',12.21,'62973614','60-02-17','','','','2025-09-08 23:36:23','2025-09-09 05:41:39','10029',NULL,'PJ712839D',NULL,'Other',0,'',NULL,NULL,NULL,'Leicester','LE4 5LL','Roman Robinson',''),
(48,42,'Ahmad','Iftikhar','khfjdshjgew@gmail.com','','14 Ffordd Tegid','1013 2840 7263 4504','2028-03-19','Other','2027-10-04','Part-time',12.21,'65837303','23-01-20','','','','2025-09-08 23:41:03','2025-09-09 03:25:41','10030','2001-02-15','RZ245327D',NULL,'Other',0,'',NULL,NULL,NULL,'Bangor','LL57 1AW','Ahmad Iftikhar',''),
(49,43,'Timileyin',' Ogunyemi','kjahsfjkahw@gmail.com','','','',NULL,'Work Visa',NULL,'Part-time',12.21,'','','','','','2025-09-08 23:50:50','2025-09-08 23:50:50','10031',NULL,'',NULL,'Other',0,'',NULL,NULL,NULL,'','','',''),
(50,44,'Pranav','Parag','iadjah@gmail.com','','','',NULL,'Work Visa',NULL,'Full-time',12.21,'','','','','','2025-09-08 23:53:38','2025-09-08 23:53:38','10032',NULL,'',NULL,'Other',0,'',NULL,NULL,NULL,'','','',''),
(51,45,'Kamil','Abdul Kareem','ewqrkjyu@gmail.com','','3 Dunholme Road','1015486556066444',NULL,'Work Visa',NULL,'Full-time',12.21,'','','','','','2025-09-08 23:57:00','2025-09-08 23:57:00','10033',NULL,'',NULL,'Other',0,'',NULL,NULL,NULL,'Leicester','LE4 9BW','',''),
(52,46,'OLUWASEUN','ADEWUNM','kjdsfhb@gmail.com','','','',NULL,'Work Visa',NULL,'Part-time',12.21,'','','','','','2025-09-08 23:58:29','2025-09-08 23:58:29','10034',NULL,'',NULL,'Other',0,'',NULL,NULL,NULL,'','','',''),
(53,47,'Bilal ','Ashraf','bfewnf@gmail.com','','','1015 6936 6293 7493','2027-08-29','Work Visa',NULL,'Full-time',12.21,'75907355','40-00-03','','','','2025-09-09 00:00:07','2025-09-09 03:38:14','10035',NULL,'TH244655D',NULL,'Other',0,'',NULL,NULL,NULL,'','','Bilal Ashraf',''),
(54,48,'Muhammad ','Hassan','fvbnejhgf@gmail.com','','','1013 8954 8679 4937','2026-08-02','Student Visa',NULL,'Full-time',12.21,'67688860','77-19-38','','','','2025-09-09 00:01:25','2025-09-09 03:47:26','10036','1995-11-09','TK851717B',NULL,'Other',0,'',NULL,NULL,NULL,'','','Muhammad Hassan',''),
(55,49,'Ali ','Javed','lkdshfakjwb@gmail.com','','341 stratford','1013 4735 1024 8751','2026-06-18','Student Visa',NULL,'Full-time',12.21,'04733878','40-11-91','','','','2025-09-09 00:02:38','2025-09-09 03:32:09','10037','1996-05-24','TK742472A',NULL,'British',0,'',NULL,NULL,NULL,'Birmingham','B11 4JY','Ali Javed',''),
(56,50,'Tamoor ','ul Hassan','wdvbhfc@gmail.com','','','1012 0505 8439 4586','2026-03-29','Student Visa',NULL,'Full-time',13.00,'76292129','04-00-03','','','','2025-09-09 00:03:59','2025-09-09 05:54:09','10038','2000-11-18','TK321623D',NULL,'Other',0,'',NULL,NULL,NULL,'','','Tamoor Ul Hassan',''),
(57,51,'Arbaz ','Khan','ijfdjewhr@gmail.com','','11 Marlock Street','1034 9261 7599 2326',NULL,'Work Visa',NULL,'Full-time',12.21,'92485812','04-00-75','','','','2025-09-09 21:24:13','2025-09-09 21:24:13','10039',NULL,'SS063259D',NULL,'Other',0,'',NULL,NULL,NULL,' LEICESTER','LE2 0GS','Md Arbaz Khan',''),
(58,52,'Aye\'Jay ','Comeau','jdsfhjhjrfe@gmail.com','','110 Wheeler Street','1013005755562180',NULL,'Student Visa',NULL,'Part-time',12.21,'','','','','','2025-09-09 21:34:08','2025-09-09 21:34:08','10040',NULL,'',NULL,'British',0,'',NULL,NULL,NULL,'Maidstone','ME14 2UL','',''),
(59,53,'Noor ','Asjad','farrukhshaikh81@hotmail.com','','','',NULL,'British',NULL,'Part-time',12.21,'','','','','','2025-09-10 15:40:55','2025-09-10 15:40:55','10041','1995-04-05','',NULL,'British',0,'',NULL,NULL,NULL,'','','',''),
(60,54,'Divyanshu','Daver','ksafj@gmail.com','','31,Belmont roadÂ Ilford','1016752066469904',NULL,'Student Visa',NULL,'Part-time',12.21,'30830400','23-01-20','','','','2025-09-12 18:38:38','2025-09-12 18:38:38','10042','2000-11-08','RY133092A',NULL,'Other',0,'',NULL,NULL,NULL,'London','31 BELMONT RD I','Divyanshu Daver',''),
(61,55,'Arbab','Khan','kedajstiuuh@gmail.com','','House Address 8 salter house','1012 6570 0959 8599',NULL,'Student Visa',NULL,'Part-time',12.21,'88292135','60-84-07','','','','2025-09-12 19:08:10','2025-09-12 19:10:20','10043',NULL,'TJ878459D',NULL,'Other',0,'',NULL,NULL,NULL,'London','SW16 1TX','SYED UR REHMAN','');
/*!40000 ALTER TABLE `officers` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`rohabae1_rota`@`localhost`*/ /*!50003 TRIGGER generate_staff_id 
BEFORE INSERT ON officers
FOR EACH ROW
BEGIN
    IF NEW.staff_id = '' OR NEW.staff_id IS NULL THEN
        SET NEW.staff_id = LPAD((
            SELECT COALESCE(MAX(CAST(staff_id AS UNSIGNED)), 9999) + 1 
            FROM officers 
            WHERE staff_id REGEXP '^[0-9]+$'
        ), 5, '0');
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`rohabae1_rota`@`localhost`*/ /*!50003 TRIGGER format_sort_code 
BEFORE INSERT ON officers
FOR EACH ROW
BEGIN
    IF NEW.sort_code IS NOT NULL AND LENGTH(NEW.sort_code) = 6 AND NEW.sort_code NOT LIKE '%-%' THEN
        SET NEW.sort_code = CONCAT(
            SUBSTRING(NEW.sort_code, 1, 2), '-',
            SUBSTRING(NEW.sort_code, 3, 2), '-',
            SUBSTRING(NEW.sort_code, 5, 2)
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`rohabae1_rota`@`localhost`*/ /*!50003 TRIGGER format_sort_code_update
BEFORE UPDATE ON officers
FOR EACH ROW
BEGIN
    IF NEW.sort_code IS NOT NULL AND LENGTH(NEW.sort_code) = 6 AND NEW.sort_code NOT LIKE '%-%' THEN
        SET NEW.sort_code = CONCAT(
            SUBSTRING(NEW.sort_code, 1, 2), '-',
            SUBSTRING(NEW.sort_code, 3, 2), '-',
            SUBSTRING(NEW.sort_code, 5, 2)
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES
(5,'test','','','','',NULL,'Net 30','active',NULL,'2025-08-24 21:01:26','2025-08-25 04:30:39'),
(6,'K9 new World ','Sadie ','','+44 7477 807233','',13.50,'Net 15','active',NULL,'2025-08-24 21:01:26','2025-08-25 04:31:28'),
(7,'Proces Security ltd ','Rizwan khan','Riz@fortis-uk.co.uk','','',13.00,'Net 30','active',NULL,'2025-08-24 21:01:26','2025-09-08 04:03:34'),
(8,'Pristine Security Services Ltd','Rizwan khan ','accounts@pristinesecurity.co.uk','0333 358 2660','2 Empire Way,Burnley,Lancashire,BB12 6HH',14.60,'Net 21','active',NULL,'2025-08-24 21:01:26','2025-08-25 04:30:22'),
(9,'Optimus Security Ltd','Danny Fisher ','accounts@optimus-security.co.uk',' 07971 316059','Deacon House,\r\n32 Eyre Street,Sheffield,England,\r\nS1 4QZ',14.10,'Net 30','active',NULL,'2025-08-25 03:29:48','2025-08-25 04:28:32'),
(10,'Omega Security ','Andrea ','Andrea@fortis-uk.co.uk','07704156545','',14.10,'Net 30','active',NULL,'2025-08-25 03:34:14','2025-09-08 04:04:45');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
INSERT INTO `sites` VALUES
(10,10,'Franchgate Shopping Centre','St Sepulchre Gate, Doncaster DN1 1SW','Mall security ','01302 368335','Monitor reception area, visitor log, key holder duties',15.50,'active','2025-08-24 21:01:47','2025-09-09 23:08:32',NULL),
(11,8,'End Manchester ','12-14 St Mary\'s Gate, Manchester M1 1PX','Store manager ','0161 552 6722','Patrol grounds, CCTV monitoring, access control',16.00,'active','2025-08-24 21:01:47','2025-09-08 04:28:01',NULL),
(12,9,'Sports Direct Fraser Derby ','Unit 5, The Derbion Centre, Traffic St, Derby DE1 2NL','Site manager ','0343 909 3281','Customer service, incident reporting, emergency procedures',14.75,'active','2025-08-24 21:01:47','2025-09-08 04:25:07',NULL),
(13,9,'Flannels Solihull ','498-510, Stratford Rd, Shirley, Solihull B90 4AY','Site Manager Talha ',' 0343 777 1565','Door Supervisor job ',15.25,'active','2025-08-24 21:01:47','2025-08-25 04:48:10',NULL),
(14,7,'5968 Morrison ZOZRXF Tower ZO','Desside Wales, CH5 ','Rizwan khan','','Patrol, Check call every hour',16.25,'active','2025-08-24 21:01:47','2025-09-08 04:17:45',NULL),
(15,7,'5967 Morrison ZOZRXF Tower ZO','5967 Morrison ZOZRXF Tower ZO','Rizwan khan ','','Patrol, check call every hour ',15.75,'active','2025-08-24 21:01:47','2025-09-15 11:14:20',NULL),
(16,9,'Flannels Cheshunt ','Unit 1, Brookfield Retail Park, Halfhide Ln, Cheshunt, Waltham Cross EN8 0QL','Site manager ','0343 909 2786','Door supervisor ',17.00,'active','2025-08-24 21:01:47','2025-09-08 04:08:50',NULL),
(17,9,'Sports Direct Maidstone ','Unit 44, Fremlin Walk Shopping Centre, Fremlin Walk, Maidstone ME14 1QP','Store manager ','','Door Supervisor ',18.50,'active','2025-08-24 21:01:47','2025-09-08 04:10:34',NULL),
(18,8,'End Washington DC Newcastle ','1 Parsons Rd, Washington NE37 1EZ','Harry ','','',0.00,'active','2025-08-25 02:47:21','2025-09-08 04:21:08',NULL),
(19,9,'Flannels Portsmouth ','244-248 Commercial Rd, Portsmouth PO1 1HH','Store manager',' 0343 909 2782','',0.00,'active','2025-08-25 03:28:03','2025-09-08 04:26:39',NULL),
(20,9,'Flannels Leicester ','Fosspark Leicester ','Sam','','',0.00,'active','2025-08-25 03:30:32','2025-09-08 04:47:05',NULL),
(21,8,'End Gray Street Newcastle ','104-108 Grey St, Newcastle upon Tyne NE1 6JG','Harry','','',0.00,'active','2025-09-08 04:32:10','2025-09-08 04:32:10',NULL),
(22,9,'Sports Direct Fraser Norwich ','Chantry Place Shopping Centre, 40-46 St Stephens St, Norwich NR1 3SH','','','',0.00,'active','2025-09-08 04:34:23','2025-09-08 04:34:23',NULL),
(23,9,'Sports Direct Merryhill ','Store B, Merry Hill Centre Brierley Hill, Brierley Hill, Centre DY5 1QP','Rizwan','','',0.00,'active','2025-09-08 04:35:52','2025-09-08 04:35:52',NULL),
(24,9,'Sport Direct Enfield			','Unit 4, Enfield Retail Park, Enfield EN1 1TH','','','',0.00,'active','2025-09-08 04:37:21','2025-09-08 04:37:49',NULL),
(25,8,'Kindeva Drugb Delivery ','Unit 4 Bank Court, Weldon Road, Loughborough LE11 5RF','Rizwan ','','',0.00,'active','2025-09-08 04:42:29','2025-09-08 04:42:29',NULL),
(26,9,'Flannels Merthyr Tydfil ',' Unit 9, Cyfarthfa Retail Park, Swansea Rd, Merthyr Tydfil CF48 1HY','','','',0.00,'active','2025-09-08 04:44:49','2025-09-08 04:44:49',NULL),
(27,8,'Fat Face','St Davids Dewi Sant, 39 The Hayes Unit LG 67 and R507, Cardiff CF10 1GA','Harry ','','',0.00,'active','2025-09-08 04:46:43','2025-09-08 04:46:43',NULL),
(28,10,'Princesshay Shopping Center','9 Catherine St, Exeter EX1 1QA','Andi','','',0.00,'active','2025-09-08 04:48:34','2025-09-08 04:48:34',NULL),
(29,9,'Flannels Doncaster ','','','','',0.00,'active','2025-09-09 23:10:12','2025-09-10 10:10:19',NULL),
(30,8,'END Manchester Night','','','','',0.00,'active','2025-09-10 00:29:32','2025-09-10 00:29:32',NULL),
(31,7,'5999 Morrison 4ZB Mobile 1','5999 Morrison 4ZB Mobile 1','Rizwan khan','','',0.00,'active','2025-09-10 03:28:30','2025-09-10 03:28:30',NULL),
(32,8,'Miniso Croydon','Unit 3, Allders, Whitgift Shoppng Centre, North End, Croydon, CR10 1LP\r\n','Rizwan ','','',0.00,'active','2025-09-10 13:48:07','2025-09-10 13:48:07',NULL),
(33,9,'Sports Direct Oxford Street','','','','',0.00,'active','2025-09-12 18:18:21','2025-09-12 18:18:21',NULL),
(34,9,'Sports Direct Piccadilly LW','','','','',0.00,'active','2025-09-12 18:49:04','2025-09-12 18:49:04',NULL);
/*!40000 ALTER TABLE `sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
INSERT INTO `shifts` VALUES
(104,13,19,'2025-08-25','10:00:00','19:30:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-08-25 04:54:49','2025-09-11 02:47:06',NULL,NULL,NULL,NULL,NULL),
(107,13,19,'2025-08-26','10:00:00','19:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,1,'cover shift ',12.21,NULL,15.00,'','2025-08-25 04:54:49','2025-09-11 02:47:23',NULL,NULL,NULL,NULL,NULL),
(108,13,19,'2025-09-22','10:00:00','19:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-08-25 04:54:49','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(109,13,19,'2025-09-15','10:00:00','19:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-08-25 04:54:49','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(110,13,19,'2025-09-09','10:00:00','19:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-08-25 04:54:49','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(112,13,19,'2025-09-23','10:00:00','19:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-08-25 04:54:49','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(113,13,19,'2025-09-16','10:00:00','19:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-08-25 04:54:49','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(114,13,19,'2025-09-29','10:00:00','19:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-08-25 04:54:49','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(115,13,19,'2025-09-30','10:00:00','19:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-08-25 04:54:49','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(117,18,21,'2025-09-02','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:35:08','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(118,18,21,'2025-09-04','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:36:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(119,18,21,'2025-09-05','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:36:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(120,18,21,'2025-09-06','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:36:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(122,18,21,'2025-10-15','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(123,18,21,'2025-10-17','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(124,18,21,'2025-10-21','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(125,18,21,'2025-10-16','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(126,18,21,'2025-10-22','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(127,18,21,'2025-10-18','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(128,18,21,'2025-10-09','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(129,18,21,'2025-09-30','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(130,18,21,'2025-09-27','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(131,18,21,'2025-10-10','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(132,18,21,'2025-10-08','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(134,18,21,'2025-10-04','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(135,18,21,'2025-10-03','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(136,18,21,'2025-10-14','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(137,18,21,'2025-10-07','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(138,18,21,'2025-10-11','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(144,18,21,'2025-09-19','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(145,18,21,'2025-09-26','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(146,18,21,'2025-10-02','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(147,18,21,'2025-09-25','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(148,18,21,'2025-10-01','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(149,18,21,'2025-09-17','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(150,18,21,'2025-10-23','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(151,18,21,'2025-10-25','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(152,18,21,'2025-10-24','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(153,18,21,'2025-10-28','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(154,18,21,'2025-09-18','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(155,18,21,'2025-10-30','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(156,18,21,'2025-09-23','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(157,18,21,'2025-09-20','19:00:00','07:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(158,18,21,'2025-10-29','19:00:00','07:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-02 22:38:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(159,14,19,'2025-09-06','08:00:00','20:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'Test','2025-09-06 13:43:57','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(161,28,56,'2025-09-09','06:00:00','14:00:00','Security',1,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-08 15:57:41','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(162,16,27,'2025-09-04','09:30:00','20:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:00:33','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(167,16,27,'2025-09-09','09:30:00','20:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:02:23','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(168,16,27,'2025-09-14','11:00:00','17:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:03:02','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(175,16,27,'2025-09-30','09:30:00','20:15:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:06:44','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(176,16,27,'2025-09-18','09:30:00','20:15:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:07:54','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(177,16,27,'2025-09-25','09:30:00','20:15:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:07:54','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(179,16,27,'2025-09-28','11:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:09:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(180,16,27,'2025-09-21','11:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:09:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(182,20,29,'2025-09-02','09:00:00','21:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:14:06','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(183,20,29,'2025-09-04','09:00:00','21:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:14:06','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(184,20,29,'2025-09-15','11:00:00','21:00:00','Security',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:17:09','2025-09-14 16:38:36',NULL,NULL,NULL,NULL,NULL),
(185,20,29,'2025-09-09','11:00:00','21:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:17:09','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(186,20,29,'2025-09-08','11:00:00','21:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:17:09','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(187,20,29,'2025-09-11','11:00:00','21:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:17:09','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(188,20,29,'2025-09-16','11:00:00','21:00:00','Security',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:17:09','2025-09-14 16:39:09',NULL,NULL,NULL,NULL,NULL),
(189,20,29,'2025-09-29','11:00:00','21:00:00','Security',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:17:09','2025-09-14 16:41:59',NULL,NULL,NULL,NULL,NULL),
(190,20,29,'2025-09-23','11:00:00','21:00:00','Security',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:17:09','2025-09-14 16:41:15',NULL,NULL,NULL,NULL,NULL),
(191,20,29,'2025-09-22','11:00:00','21:00:00','Security',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:17:09','2025-09-14 16:40:52',NULL,NULL,NULL,NULL,NULL),
(192,20,29,'2025-09-18','11:00:00','21:00:00','Security',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:17:09','2025-09-14 16:39:41',NULL,NULL,NULL,NULL,NULL),
(193,20,29,'2025-09-30','11:00:00','21:00:00','Security',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,14.10,'','2025-09-09 21:17:09','2025-09-15 13:30:40',NULL,NULL,NULL,NULL,NULL),
(194,20,29,'2025-09-25','11:00:00','21:00:00','Security',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:17:09','2025-09-14 16:41:36',NULL,NULL,NULL,NULL,NULL),
(195,17,58,'2025-07-09','10:30:00','16:30:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:39:45','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(196,17,58,'2025-09-14','10:30:00','16:30:00','Security',NULL,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:41:20','2025-09-11 03:16:19',NULL,NULL,NULL,NULL,NULL),
(197,17,58,'2025-09-07','10:30:00','16:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:41:20','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(198,20,57,'2025-09-05','09:00:00','21:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:45:54','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(199,20,57,'2025-09-03','09:00:00','21:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:45:54','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(200,20,23,'2025-09-01','09:00:00','21:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:47:52','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(201,20,57,'2025-09-06','09:00:00','20:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:49:15','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(202,20,57,'2025-09-07','11:00:00','17:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:50:18','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(203,20,57,'2025-09-12','11:00:00','21:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:53:14','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(204,20,57,'2025-09-19','11:00:00','21:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,14.10,'','2025-09-09 21:53:14','2025-09-15 13:26:54',NULL,NULL,NULL,NULL,NULL),
(205,20,57,'2025-09-17','11:00:00','21:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,14.10,'','2025-09-09 21:53:14','2025-09-15 13:25:37',NULL,NULL,NULL,NULL,NULL),
(206,20,57,'2025-09-10','11:00:00','21:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:53:14','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(207,20,57,'2025-09-13','10:00:00','21:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 21:58:33','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(208,20,57,'2025-09-20','10:00:00','21:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,14.10,'','2025-09-09 21:58:33','2025-09-15 13:27:40',NULL,NULL,NULL,NULL,NULL),
(209,20,57,'2025-09-27','10:00:00','21:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,14.10,'','2025-09-09 21:58:33','2025-09-15 13:30:02',NULL,NULL,NULL,NULL,NULL),
(214,20,57,'2025-09-14','11:00:00','17:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:01:40','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(215,20,57,'2025-09-21','11:00:00','17:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,14.10,'','2025-09-09 22:01:40','2025-09-15 13:28:01',NULL,NULL,NULL,NULL,NULL),
(216,20,57,'2025-09-28','11:00:00','17:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,14.10,'','2025-09-09 22:01:40','2025-09-15 13:30:20',NULL,NULL,NULL,NULL,NULL),
(217,10,33,'2025-09-01','12:15:00','20:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:07:27','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(218,10,24,'2025-09-05','12:15:00','20:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:09:10','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(219,10,24,'2025-09-10','20:00:00','08:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-12 20:59:06',NULL,NULL,NULL,NULL,NULL),
(220,10,24,'2025-09-14','20:00:00','08:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-15 07:02:02',NULL,NULL,NULL,NULL,NULL),
(221,10,24,'2025-09-12','20:00:00','08:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-14 06:42:58',NULL,NULL,NULL,NULL,NULL),
(224,10,24,'2025-09-11','20:00:00','08:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-14 06:42:46',NULL,NULL,NULL,NULL,NULL),
(225,10,24,'2025-09-16','20:00:00','08:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(226,10,24,'2025-09-15','20:00:00','08:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(227,10,24,'2025-09-06','20:00:00','08:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(228,10,24,'2025-09-07','20:00:00','08:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(229,10,24,'2025-09-13','20:00:00','08:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-14 06:43:05',NULL,NULL,NULL,NULL,NULL),
(230,10,24,'2025-09-20','20:00:00','08:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(231,10,24,'2025-09-18','20:00:00','08:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(233,10,24,'2025-09-19','20:00:00','08:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:10:58','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(237,12,26,'2025-09-01','09:15:00','18:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:22:36','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(238,12,26,'2025-09-02','09:15:00','18:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:22:36','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(239,12,26,'2025-09-03','09:15:00','18:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:22:36','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(240,12,26,'2025-09-04','09:15:00','19:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:24:59','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(241,12,26,'2025-09-05','09:15:00','19:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:24:59','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(242,12,30,'2025-09-06','09:15:00','19:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:26:25','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(243,12,30,'2025-09-07','10:45:00','16:45:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:27:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(244,12,26,'2025-09-08','09:00:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:28:51','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(245,12,26,'2025-09-09','10:45:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:29:42','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(246,12,26,'2025-09-18','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(247,12,26,'2025-09-15','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(248,12,26,'2025-09-17','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(249,12,26,'2025-09-16','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(250,12,26,'2025-09-12','10:00:00','18:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-13 09:42:31',NULL,NULL,NULL,NULL,NULL),
(251,12,26,'2025-09-11','10:00:00','18:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-13 09:42:17',NULL,NULL,NULL,NULL,NULL),
(252,12,26,'2025-09-10','10:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(253,12,26,'2025-09-22','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(254,12,26,'2025-09-23','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(255,12,26,'2025-09-19','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(256,12,26,'2025-09-25','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(257,12,26,'2025-09-24','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(258,12,26,'2025-09-29','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(259,12,26,'2025-09-30','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(260,12,26,'2025-09-26','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:31:46','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(264,12,30,'2025-09-13','11:00:00','19:00:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:33:59','2025-09-13 09:39:50',NULL,NULL,NULL,NULL,NULL),
(265,12,30,'2025-09-20','11:00:00','19:00:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:33:59','2025-09-13 09:40:29',NULL,NULL,NULL,NULL,NULL),
(266,12,30,'2025-09-27','11:00:00','19:00:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:33:59','2025-09-13 09:41:18',NULL,NULL,NULL,NULL,NULL),
(270,12,30,'2025-09-14','10:45:00','16:45:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:35:27','2025-09-13 09:40:07',NULL,NULL,NULL,NULL,NULL),
(271,12,30,'2025-09-21','10:45:00','16:45:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:36:05','2025-09-13 09:40:38',NULL,NULL,NULL,NULL,NULL),
(272,12,30,'2025-09-28','10:45:00','16:45:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:36:05','2025-09-13 09:41:29',NULL,NULL,NULL,NULL,NULL),
(273,19,31,'2025-09-13','10:00:00','18:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:39:01','2025-09-11 15:28:29',NULL,NULL,NULL,NULL,NULL),
(274,19,31,'2025-09-12','10:00:00','18:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:39:01','2025-09-11 15:27:41',NULL,NULL,NULL,NULL,NULL),
(275,19,31,'2025-09-20','10:00:00','18:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:39:01','2025-09-11 15:30:13',NULL,NULL,NULL,NULL,NULL),
(276,19,31,'2025-09-15','10:00:00','18:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:39:01','2025-09-11 15:29:55',NULL,NULL,NULL,NULL,NULL),
(277,19,31,'2025-09-16','10:00:00','18:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:39:01','2025-09-11 15:30:33',NULL,NULL,NULL,NULL,NULL),
(278,19,31,'2025-09-19','10:00:00','18:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:39:01','2025-09-11 15:31:08',NULL,NULL,NULL,NULL,NULL),
(280,19,31,'2025-09-17','10:00:00','18:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:39:01','2025-09-11 15:30:46',NULL,NULL,NULL,NULL,NULL),
(281,19,31,'2025-09-18','10:00:00','18:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:39:01','2025-09-11 15:30:58',NULL,NULL,NULL,NULL,NULL),
(282,11,32,'2025-09-01','10:00:00','20:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:48:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(283,11,32,'2025-09-05','10:00:00','20:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:49:37','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(284,11,32,'2025-09-06','09:15:00','20:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:50:40','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(285,11,32,'2025-09-07','11:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:51:19','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(288,11,32,'2025-09-08','09:45:00','20:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:53:11','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(289,11,32,'2025-09-25','10:00:00','20:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:55:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(290,11,32,'2025-09-19','10:00:00','20:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:55:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(291,11,32,'2025-09-26','10:00:00','20:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:55:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(292,11,32,'2025-10-02','10:00:00','20:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:55:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(293,11,32,'2025-09-18','10:00:00','20:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:55:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(294,11,32,'2025-09-12','10:00:00','20:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:55:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(295,11,32,'2025-09-11','10:00:00','20:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:55:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(296,11,32,'2025-10-03','10:00:00','20:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:55:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(297,11,32,'2025-09-14','11:00:00','18:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:56:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(298,11,32,'2025-09-20','09:15:00','20:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:57:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(299,11,32,'2025-09-21','11:00:00','18:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:57:49','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(300,11,32,'2025-09-23','09:45:00','20:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:58:42','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(301,11,32,'2025-09-24','09:45:00','20:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 22:59:16','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(302,11,32,'2025-10-04','09:15:00','20:15:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:00:32','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(303,11,32,'2025-10-05','11:00:00','18:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:01:16','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(304,29,33,'2025-09-06','10:30:00','17:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:11:41','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(305,29,33,'2025-09-07','10:00:00','16:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:12:18','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(307,16,NULL,'2025-09-16','09:00:00','21:30:00','Security Officer',1,0.00,'unallocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'Test API call','2025-09-09 23:13:24','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(309,18,34,'2025-09-06','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(312,18,34,'2025-09-01','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(313,18,34,'2025-09-05','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(314,18,34,'2025-09-13','19:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-14 06:28:49',NULL,NULL,NULL,NULL,NULL),
(315,18,34,'2025-09-10','19:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-14 06:28:15',NULL,NULL,NULL,NULL,NULL),
(316,18,34,'2025-09-07','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(318,18,34,'2025-09-15','19:00:00','07:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(320,18,34,'2025-09-12','19:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-14 06:28:35',NULL,NULL,NULL,NULL,NULL),
(321,18,34,'2025-09-14','19:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-15 07:03:00',NULL,NULL,NULL,NULL,NULL),
(323,18,34,'2025-09-17','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(324,18,34,'2025-09-22','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(325,18,34,'2025-09-16','19:00:00','07:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(326,18,34,'2025-09-18','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(327,18,34,'2025-09-19','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(328,18,34,'2025-09-25','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(329,18,34,'2025-09-26','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(330,18,34,'2025-09-20','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(331,18,34,'2025-09-21','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(332,18,34,'2025-09-23','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(333,18,34,'2025-09-27','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(334,18,34,'2025-09-29','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(335,18,34,'2025-09-30','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(336,18,34,'2025-09-28','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(337,18,34,'2025-09-24','19:00:00','07:00:00','Security',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(340,18,34,'2025-10-03','19:00:00','07:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(341,18,34,'2025-10-05','19:00:00','07:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(342,18,34,'2025-10-04','19:00:00','07:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:15:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(345,18,21,'2025-09-03','07:00:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:17:36','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(346,16,NULL,'2025-09-16','09:00:00','21:30:00','Security Officer',1,0.00,'unallocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'Test API call','2025-09-09 23:19:45','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(347,18,21,'2025-09-08','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:19:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(348,16,NULL,'2025-09-16','09:00:00','21:30:00','Security Officer',1,0.00,'unallocated',NULL,NULL,0,NULL,12.21,NULL,15.00,'Test API call','2025-09-09 23:23:09','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(349,18,21,'2025-09-21','19:00:00','07:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:25:41','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(350,18,21,'2025-09-28','19:00:00','07:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:27:15','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(351,18,21,'2025-10-05','19:00:00','07:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:28:32','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(352,18,34,'2025-10-01','19:00:00','07:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:30:57','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(353,21,35,'2025-09-04','08:30:00','20:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:46:25','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(354,21,35,'2025-09-11','08:30:00','20:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:46:25','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(355,21,35,'2025-09-18','08:30:00','20:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:46:25','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(356,21,35,'2025-09-25','08:30:00','20:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:46:25','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(357,21,35,'2025-10-02','08:30:00','20:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:46:25','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(358,21,35,'2025-09-19','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(359,21,35,'2025-09-05','08:30:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(360,21,35,'2025-09-17','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(361,21,35,'2025-09-24','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(362,21,35,'2025-09-13','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(363,21,35,'2025-09-03','08:30:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(364,21,35,'2025-10-03','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(365,21,35,'2025-09-10','08:30:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(366,21,35,'2025-09-20','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(367,21,35,'2025-10-01','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(368,21,35,'2025-09-27','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(369,21,35,'2025-09-26','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(370,21,35,'2025-10-04','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(371,21,35,'2025-09-12','08:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(372,21,35,'2025-09-06','08:30:00','19:00:00','Security',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:48:07','2025-09-11 03:25:00',NULL,NULL,NULL,NULL,NULL),
(373,21,35,'2025-10-05','09:00:00','18:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:49:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(374,21,35,'2025-09-28','09:00:00','18:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:49:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(375,21,35,'2025-09-21','09:00:00','18:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:49:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(376,21,35,'2025-09-14','09:00:00','18:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:49:01','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(377,21,35,'2025-09-07','09:00:00','18:30:00','Security',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:49:01','2025-09-11 03:25:05',NULL,NULL,NULL,NULL,NULL),
(378,22,36,'2025-09-03','09:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:56:25','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(379,22,36,'2025-09-04','09:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:56:25','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(380,22,36,'2025-09-18','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:57:44','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(381,22,36,'2025-09-10','10:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:57:44','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(382,22,36,'2025-09-11','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:57:44','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(383,22,36,'2025-09-17','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:57:44','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(384,22,36,'2025-09-24','10:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-09 23:57:44','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(385,13,19,'2025-09-02','10:00:00','19:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:08:49','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(386,13,19,'2025-09-01','10:00:00','19:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:08:49','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(387,13,37,'2025-09-03','10:00:00','19:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:10:10','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(388,13,37,'2025-09-04','10:00:00','19:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:10:10','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(389,13,37,'2025-09-05','10:00:00','19:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:10:10','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(390,13,38,'2025-09-06','09:00:00','18:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:11:51','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(391,13,38,'2025-09-13','09:00:00','18:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:11:51','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(392,13,38,'2025-09-20','09:00:00','18:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:11:51','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(393,13,38,'2025-09-27','09:00:00','18:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:11:51','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(394,13,28,'2025-09-08','09:00:00','19:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:14:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(399,13,38,'2025-09-07','10:00:00','16:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:16:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(400,13,38,'2025-09-21','10:00:00','16:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:16:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(401,13,38,'2025-09-28','10:00:00','16:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:16:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(402,13,38,'2025-09-14','10:00:00','16:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:16:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(403,13,38,'2025-09-10','10:00:00','19:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:19:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(404,13,38,'2025-09-11','10:00:00','19:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:19:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(405,13,38,'2025-09-12','10:00:00','19:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:19:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(406,13,38,'2025-09-18','10:00:00','19:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:19:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(407,13,38,'2025-09-17','10:00:00','19:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:19:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(408,13,38,'2025-09-19','10:00:00','19:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:19:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(409,13,38,'2025-09-25','10:00:00','19:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:19:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(410,13,38,'2025-09-24','10:00:00','19:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:19:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(411,13,38,'2025-09-26','10:00:00','19:30:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:19:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(412,23,28,'2025-09-07','11:00:00','17:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:24:17','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(413,25,47,'2025-09-02','08:00:00','17:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(414,25,47,'2025-09-01','08:00:00','17:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(415,25,47,'2025-09-05','08:00:00','17:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(416,25,47,'2025-09-08','08:00:00','17:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(417,25,47,'2025-09-18','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(418,25,47,'2025-09-03','08:00:00','17:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(419,25,47,'2025-09-09','08:00:00','17:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(420,25,47,'2025-09-04','08:00:00','17:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(421,25,47,'2025-09-15','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(422,25,47,'2025-09-10','08:00:00','17:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(423,25,47,'2025-09-22','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(424,25,47,'2025-09-19','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(425,25,47,'2025-09-16','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(426,25,47,'2025-09-11','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(427,25,47,'2025-09-17','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(428,25,47,'2025-09-12','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(429,25,47,'2025-09-23','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(430,25,47,'2025-09-26','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(431,25,47,'2025-09-24','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(432,25,47,'2025-09-29','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(433,25,47,'2025-09-25','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(434,25,47,'2025-10-01','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(435,25,47,'2025-09-30','08:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:26:22','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(436,30,48,'2025-09-03','20:00:00','10:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:30:36','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(437,26,50,'2025-09-26','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(438,26,50,'2025-09-10','09:30:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(439,26,50,'2025-09-18','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(440,26,50,'2025-09-12','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(441,26,50,'2025-09-19','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(442,26,50,'2025-09-05','09:30:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(443,26,50,'2025-09-24','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(444,26,50,'2025-09-25','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(445,26,50,'2025-09-11','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(446,26,50,'2025-10-01','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(447,26,50,'2025-09-17','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:34:05','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(448,26,50,'2025-09-06','09:30:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:37:27','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(449,26,50,'2025-09-13','09:00:00','18:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:37:27','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(450,26,50,'2025-09-20','09:00:00','18:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:37:27','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(451,26,50,'2025-09-27','09:00:00','18:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:37:27','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(452,26,50,'2025-09-07','10:30:00','16:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:38:51','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(453,26,50,'2025-09-21','10:30:00','16:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:38:51','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(454,26,50,'2025-09-28','10:30:00','16:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:38:51','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(455,26,50,'2025-09-14','10:30:00','16:30:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:38:51','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(456,27,51,'2025-09-16','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(457,27,51,'2025-09-24','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(458,27,51,'2025-09-23','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(459,27,51,'2025-09-11','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(460,27,51,'2025-09-01','09:30:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(461,27,51,'2025-09-17','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(462,27,51,'2025-09-26','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(463,27,51,'2025-09-12','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(464,27,51,'2025-09-25','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(465,27,51,'2025-09-15','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(466,27,51,'2025-09-19','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(467,27,51,'2025-09-18','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(468,27,51,'2025-09-03','09:30:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(469,27,51,'2025-09-22','09:30:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(470,27,51,'2025-09-04','09:30:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(471,27,51,'2025-09-02','09:30:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(472,27,51,'2025-09-10','09:30:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(473,27,51,'2025-09-05','09:30:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(474,27,51,'2025-09-09','09:30:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(475,27,51,'2025-09-08','09:30:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:42:47','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(476,27,52,'2025-09-06','09:30:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:44:40','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(477,27,52,'2025-09-13','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:44:40','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(478,27,52,'2025-09-20','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:44:40','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(479,27,52,'2025-09-27','09:30:00','19:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:44:40','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(480,27,52,'2025-09-07','11:00:00','17:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:46:08','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(481,27,52,'2025-09-21','11:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:46:08','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(482,27,52,'2025-09-28','11:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:46:08','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(483,27,52,'2025-09-14','11:00:00','17:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 00:46:08','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(484,28,56,'2025-09-01','06:00:00','14:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(485,28,56,'2025-09-08','06:00:00','14:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(486,28,56,'2025-09-18','06:00:00','14:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(489,28,56,'2025-09-15','06:00:00','14:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(490,28,56,'2025-09-10','06:00:00','14:00:00','Security',1,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(491,28,56,'2025-09-19','06:00:00','14:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(492,28,56,'2025-09-04','06:00:00','14:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(493,28,56,'2025-09-16','06:00:00','14:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(494,28,56,'2025-09-17','06:00:00','14:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(495,28,56,'2025-09-12','06:00:00','14:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-12 20:53:18',NULL,NULL,NULL,NULL,NULL),
(498,28,56,'2025-09-05','06:00:00','14:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(499,28,56,'2025-09-06','06:00:00','14:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(500,28,56,'2025-09-07','06:00:00','14:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(501,28,56,'2025-09-02','06:00:00','14:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(502,28,56,'2025-09-03','06:00:00','14:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:50:13','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(503,28,56,'2025-09-04','17:00:00','20:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:52:03','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(504,28,56,'2025-09-06','17:00:00','20:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:52:03','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(505,28,56,'2025-09-01','17:00:00','20:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:52:03','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(506,28,56,'2025-09-05','17:00:00','20:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:52:03','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(507,28,56,'2025-09-03','17:00:00','20:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:52:03','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(508,28,56,'2025-09-02','17:00:00','20:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:52:03','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(509,28,56,'2025-09-07','17:00:00','20:30:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 01:52:03','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(510,28,56,'2025-09-11','06:00:00','14:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:08:00','2025-09-12 20:53:13',NULL,NULL,NULL,NULL,NULL),
(511,15,54,'2025-09-02','17:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:18:30','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(512,15,54,'2025-09-03','17:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:18:30','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(513,15,54,'2025-09-01','17:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:18:30','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(514,15,55,'2025-09-01','17:00:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:19:24','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(515,15,55,'2025-09-03','17:00:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:19:24','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(516,15,55,'2025-09-02','17:00:00','19:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:19:24','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(517,15,54,'2025-09-05','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:20:48','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(518,15,54,'2025-09-07','19:00:00','10:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:20:48','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(519,15,54,'2025-09-06','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:20:48','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(520,15,54,'2025-09-04','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:20:48','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(521,15,55,'2025-09-04','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:21:42','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(522,15,55,'2025-09-07','19:00:00','10:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:21:42','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(523,15,55,'2025-09-06','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:21:42','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(524,15,55,'2025-09-05','19:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:21:42','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(525,15,54,'2025-09-10','17:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:26:28','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(526,15,54,'2025-09-11','17:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:26:28','2025-09-14 06:40:24',NULL,NULL,NULL,NULL,NULL),
(527,15,54,'2025-09-09','17:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:26:28','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(528,15,55,'2025-09-09','17:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:27:08','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(529,15,55,'2025-09-10','17:00:00','07:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:27:08','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(530,15,55,'2025-09-11','17:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:27:08','2025-09-14 06:40:33',NULL,NULL,NULL,NULL,NULL),
(531,15,54,'2025-09-12','19:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:29:55','2025-09-14 06:40:41',NULL,NULL,NULL,NULL,NULL),
(532,15,54,'2025-09-13','17:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:29:55','2025-09-14 06:40:48',NULL,NULL,NULL,NULL,NULL),
(533,15,54,'2025-09-14','14:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:29:55','2025-09-15 07:02:18',NULL,NULL,NULL,NULL,NULL),
(534,15,55,'2025-09-12','19:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:30:48','2025-09-14 06:42:18',NULL,NULL,NULL,NULL,NULL),
(535,15,55,'2025-09-14','14:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:30:48','2025-09-15 07:02:24',NULL,NULL,NULL,NULL,NULL),
(536,15,55,'2025-09-13','17:00:00','07:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 02:30:48','2025-09-14 06:40:57',NULL,NULL,NULL,NULL,NULL),
(537,31,53,'2025-09-11','06:00:00','18:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 03:30:20','2025-09-12 20:51:10',NULL,NULL,NULL,NULL,NULL),
(538,31,53,'2025-09-09','06:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 03:30:20','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(539,31,53,'2025-09-08','06:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 03:30:20','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(541,31,53,'2025-09-13','06:00:00','18:00:00','Security',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 03:30:20','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(543,31,53,'2025-09-12','06:00:00','18:00:00','Security',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 03:30:20','2025-09-12 20:51:19',NULL,NULL,NULL,NULL,NULL),
(544,31,53,'2025-09-01','06:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 03:30:20','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(545,31,53,'2025-09-05','06:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 03:30:20','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(547,31,53,'2025-09-04','06:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 03:30:20','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(548,31,53,'2025-09-07','06:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 03:30:20','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(549,31,53,'2025-09-06','06:00:00','18:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 03:30:20','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(555,16,27,'2025-08-28','09:30:00','20:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 16:38:08','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(556,10,24,'2025-08-25','12:15:00','20:15:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 16:49:54','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(557,10,24,'2025-08-28','20:00:00','08:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 16:52:43','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(558,10,24,'2025-08-29','20:00:00','08:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 16:56:02','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(559,10,24,'2025-08-31','12:00:00','20:00:00','Security',1,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-10 16:57:40','2025-09-10 23:09:41',NULL,NULL,NULL,NULL,NULL),
(560,28,56,'2025-08-31','06:00:00','14:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:27:41','2025-09-11 02:28:22',NULL,NULL,NULL,NULL,NULL),
(561,28,56,'2025-08-29','06:00:00','14:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:27:41','2025-09-11 02:27:52',NULL,NULL,NULL,NULL,NULL),
(562,28,56,'2025-08-30','06:00:00','14:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:27:41','2025-09-11 02:28:08',NULL,NULL,NULL,NULL,NULL),
(563,28,56,'2025-08-30','17:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:29:46','2025-09-11 02:30:07',NULL,NULL,NULL,NULL,NULL),
(565,28,56,'2025-08-31','17:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:30:42','2025-09-11 02:30:52',NULL,NULL,NULL,NULL,NULL),
(566,20,29,'2025-08-26','09:00:00','21:30:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:37:22','2025-09-11 02:37:45',NULL,NULL,NULL,NULL,NULL),
(567,20,29,'2025-08-25','09:00:00','21:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:37:22','2025-09-11 02:37:35',NULL,NULL,NULL,NULL,NULL),
(568,20,29,'2025-08-28','09:00:00','21:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:38:42','2025-09-11 02:38:50',NULL,NULL,NULL,NULL,NULL),
(569,20,57,'2025-08-27','09:00:00','21:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:40:17','2025-09-11 02:40:56',NULL,NULL,NULL,NULL,NULL),
(570,20,57,'2025-08-26','09:00:00','21:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:40:17','2025-09-11 02:40:48',NULL,NULL,NULL,NULL,NULL),
(571,20,57,'2025-08-25','09:00:00','21:30:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:40:17','2025-09-11 02:40:42',NULL,NULL,NULL,NULL,NULL),
(572,20,57,'2025-08-29','09:00:00','21:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:42:04','2025-09-11 02:43:58',NULL,NULL,NULL,NULL,NULL),
(573,20,57,'2025-08-30','09:00:00','14:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:42:41','2025-09-11 02:43:48',NULL,NULL,NULL,NULL,NULL),
(574,20,57,'2025-08-31','11:00:00','17:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:43:06','2025-09-11 02:43:31',NULL,NULL,NULL,NULL,NULL),
(575,13,19,'2025-08-30','09:00:00','18:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:48:03','2025-09-11 02:48:34',NULL,NULL,NULL,NULL,NULL),
(576,13,37,'2025-08-27','10:00:00','19:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:50:55','2025-09-11 02:51:29',NULL,NULL,NULL,NULL,NULL),
(577,13,37,'2025-08-28','10:00:00','19:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:50:55','2025-09-11 02:51:14',NULL,NULL,NULL,NULL,NULL),
(578,13,37,'2025-08-29','10:00:00','19:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:50:55','2025-09-11 02:51:20',NULL,NULL,NULL,NULL,NULL),
(579,13,37,'2025-08-31','10:00:00','16:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:52:11','2025-09-11 02:52:18',NULL,NULL,NULL,NULL,NULL),
(580,24,39,'2025-08-27','09:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:56:27','2025-09-11 02:56:36',NULL,NULL,NULL,NULL,NULL),
(581,24,39,'2025-08-29','09:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:56:27','2025-09-11 02:56:48',NULL,NULL,NULL,NULL,NULL),
(582,24,39,'2025-08-28','09:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:56:27','2025-09-11 02:56:41',NULL,NULL,NULL,NULL,NULL),
(583,24,40,'2025-08-31','11:00:00','17:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:57:34','2025-09-11 02:57:41',NULL,NULL,NULL,NULL,NULL),
(584,24,39,'2025-08-30','09:00:00','20:00:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 02:58:47','2025-09-11 02:58:54',NULL,NULL,NULL,NULL,NULL),
(585,24,39,'2025-09-01','09:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:01:04','2025-09-11 03:01:47',NULL,NULL,NULL,NULL,NULL),
(586,24,39,'2025-09-06','09:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:01:04','2025-09-11 03:02:16',NULL,NULL,NULL,NULL,NULL),
(587,24,39,'2025-09-07','11:00:00','17:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:01:04','2025-09-11 03:01:34',NULL,NULL,NULL,NULL,NULL),
(588,24,39,'2025-09-02','09:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:01:04','2025-09-11 03:01:52',NULL,NULL,NULL,NULL,NULL),
(589,24,39,'2025-09-03','09:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:01:04','2025-09-11 03:01:59',NULL,NULL,NULL,NULL,NULL),
(590,24,39,'2025-09-05','09:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:01:04','2025-09-11 03:02:10',NULL,NULL,NULL,NULL,NULL),
(591,24,39,'2025-09-04','09:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:01:04','2025-09-11 03:02:05',NULL,NULL,NULL,NULL,NULL),
(592,12,26,'2025-08-26','09:15:00','18:15:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:04:39','2025-09-11 03:06:24',NULL,NULL,NULL,NULL,NULL),
(593,12,26,'2025-08-25','09:15:00','18:15:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:04:39','2025-09-11 03:06:31',NULL,NULL,NULL,NULL,NULL),
(594,12,26,'2025-08-27','09:15:00','18:15:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:04:39','2025-09-11 03:06:19',NULL,NULL,NULL,NULL,NULL),
(595,12,26,'2025-08-28','09:15:00','19:15:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:05:32','2025-09-11 03:05:51',NULL,NULL,NULL,NULL,NULL),
(596,12,26,'2025-08-29','09:15:00','19:15:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:05:32','2025-09-11 03:06:05',NULL,NULL,NULL,NULL,NULL),
(597,12,30,'2025-08-30','09:15:00','19:15:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:07:53','2025-09-11 03:09:32',NULL,NULL,NULL,NULL,NULL),
(600,12,30,'2025-08-31','10:45:00','16:45:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:09:20','2025-09-11 03:09:41',NULL,NULL,NULL,NULL,NULL),
(601,22,36,'2025-08-27','09:00:00','18:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:13:01','2025-09-11 03:13:11',NULL,NULL,NULL,NULL,NULL),
(602,22,36,'2025-08-28','09:00:00','18:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:13:01','2025-09-11 03:13:18',NULL,NULL,NULL,NULL,NULL),
(603,17,58,'2025-08-31','10:30:00','16:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:15:28','2025-09-11 03:15:42',NULL,NULL,NULL,NULL,NULL),
(604,23,28,'2025-08-31','11:00:00','17:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:17:57','2025-09-11 03:18:05',NULL,NULL,NULL,NULL,NULL),
(605,21,35,'2025-08-29','08:30:00','19:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:21:16','2025-09-11 03:23:40',NULL,NULL,NULL,NULL,NULL),
(606,21,35,'2025-08-27','08:30:00','19:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:21:16','2025-09-11 03:23:30',NULL,NULL,NULL,NULL,NULL),
(607,21,35,'2025-08-28','08:30:00','20:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:21:16','2025-09-11 03:23:35',NULL,NULL,NULL,NULL,NULL),
(608,21,35,'2025-08-26','08:30:00','19:00:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:21:16','2025-09-11 03:21:45',NULL,NULL,NULL,NULL,NULL),
(609,11,32,'2025-08-27','10:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:29:05','2025-09-11 03:29:55',NULL,NULL,NULL,NULL,NULL),
(610,11,32,'2025-08-28','10:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:29:05','2025-09-11 03:30:03',NULL,NULL,NULL,NULL,NULL),
(611,11,32,'2025-08-29','10:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:29:05','2025-09-11 03:30:07',NULL,NULL,NULL,NULL,NULL),
(612,11,32,'2025-08-26','10:00:00','20:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:29:05','2025-09-11 03:29:46',NULL,NULL,NULL,NULL,NULL),
(613,11,32,'2025-08-31','10:30:00','17:30:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:29:38','2025-09-11 03:30:13',NULL,NULL,NULL,NULL,NULL),
(614,18,34,'2025-08-26','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:33:30','2025-09-11 03:36:53',NULL,NULL,NULL,NULL,NULL),
(615,18,34,'2025-08-25','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:33:30','2025-09-11 03:36:49',NULL,NULL,NULL,NULL,NULL),
(616,18,34,'2025-08-31','19:00:00','07:00:00','Security Officer',NULL,0.00,'declined',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:34:55','2025-09-11 03:36:36',NULL,NULL,NULL,NULL,NULL),
(617,18,34,'2025-08-30','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:34:55','2025-09-11 03:37:20',NULL,NULL,NULL,NULL,NULL),
(618,18,34,'2025-08-29','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:34:55','2025-09-11 03:37:09',NULL,NULL,NULL,NULL,NULL),
(619,18,21,'2025-08-29','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:36:23','2025-09-11 03:37:15',NULL,NULL,NULL,NULL,NULL),
(620,18,21,'2025-08-27','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:36:23','2025-09-11 03:36:59',NULL,NULL,NULL,NULL,NULL),
(621,18,21,'2025-08-31','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:36:23','2025-09-11 03:38:29',NULL,NULL,NULL,NULL,NULL),
(622,18,21,'2025-08-30','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:36:23','2025-09-11 03:38:22',NULL,NULL,NULL,NULL,NULL),
(623,18,21,'2025-08-28','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:36:23','2025-09-11 03:37:04',NULL,NULL,NULL,NULL,NULL),
(624,25,47,'2025-08-27','08:00:00','17:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:41:04','2025-09-11 03:41:19',NULL,NULL,NULL,NULL,NULL),
(625,25,47,'2025-08-28','08:00:00','17:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:41:04','2025-09-11 03:41:25',NULL,NULL,NULL,NULL,NULL),
(626,25,47,'2025-08-29','08:00:00','17:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:41:04','2025-09-11 03:41:31',NULL,NULL,NULL,NULL,NULL),
(627,25,47,'2025-08-26','08:00:00','17:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 03:41:04','2025-09-11 03:41:14',NULL,NULL,NULL,NULL,NULL),
(628,15,54,'2025-08-25','17:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:07:01','2025-09-11 04:14:15',NULL,NULL,NULL,NULL,NULL),
(629,15,54,'2025-08-27','17:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:07:01','2025-09-11 04:14:31',NULL,NULL,NULL,NULL,NULL),
(630,15,54,'2025-08-28','17:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:07:01','2025-09-11 04:14:43',NULL,NULL,NULL,NULL,NULL),
(631,15,54,'2025-08-26','17:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:07:01','2025-09-11 04:14:20',NULL,NULL,NULL,NULL,NULL),
(632,15,54,'2025-08-29','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:07:46','2025-09-11 04:14:54',NULL,NULL,NULL,NULL,NULL),
(633,15,54,'2025-08-30','00:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:08:29','2025-09-11 04:15:07',NULL,NULL,NULL,NULL,NULL),
(634,15,54,'2025-08-31','14:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:09:52','2025-09-11 04:15:29',NULL,NULL,NULL,NULL,NULL),
(636,15,55,'2025-08-25','19:00:00','10:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:11:35','2025-09-11 04:15:35',NULL,NULL,NULL,NULL,NULL),
(637,15,55,'2025-08-26','17:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:12:16','2025-09-11 04:14:26',NULL,NULL,NULL,NULL,NULL),
(638,15,55,'2025-08-27','17:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:12:40','2025-09-11 04:14:37',NULL,NULL,NULL,NULL,NULL),
(639,15,55,'2025-08-28','17:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:13:04','2025-09-11 04:14:49',NULL,NULL,NULL,NULL,NULL),
(640,15,55,'2025-08-29','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:13:59','2025-09-11 04:15:00',NULL,NULL,NULL,NULL,NULL),
(641,15,55,'2025-08-31','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:13:59','2025-09-11 04:15:24',NULL,NULL,NULL,NULL,NULL),
(642,15,55,'2025-08-30','19:00:00','07:00:00','Security Officer',NULL,0.00,'completed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-11 04:13:59','2025-09-11 04:15:13',NULL,NULL,NULL,NULL,NULL),
(643,33,60,'2025-09-13','10:00:00','21:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-12 18:39:35','2025-09-12 18:43:44',NULL,NULL,NULL,NULL,NULL),
(651,34,61,'2025-09-13','10:00:00','21:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,15.00,'','2025-09-12 19:11:27','2025-09-12 19:12:20',NULL,NULL,NULL,NULL,NULL),
(654,32,NULL,'2025-09-20','10:00:00','15:00:00','Security Officer',1,0.00,'unallocated',NULL,NULL,0,NULL,0.00,NULL,14.60,'','2025-09-15 11:15:47','2025-09-15 11:15:47',NULL,NULL,NULL,NULL,NULL),
(655,32,61,'2025-09-20','10:00:00','15:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,14.60,'','2025-09-15 11:16:14','2025-09-15 11:17:32',NULL,NULL,NULL,NULL,NULL),
(658,31,53,'2025-09-19','06:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,13.00,'','2025-09-15 13:19:38','2025-09-15 13:20:30',NULL,NULL,NULL,NULL,NULL),
(659,31,53,'2025-09-18','06:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,13.00,'','2025-09-15 13:19:38','2025-09-15 13:20:23',NULL,NULL,NULL,NULL,NULL),
(660,31,53,'2025-09-17','06:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,13.00,'','2025-09-15 13:19:38','2025-09-15 13:19:57',NULL,NULL,NULL,NULL,NULL),
(661,31,53,'2025-09-21','06:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,13.00,'','2025-09-15 13:19:38','2025-09-15 13:20:53',NULL,NULL,NULL,NULL,NULL),
(662,31,53,'2025-09-16','06:00:00','18:00:00','Security Officer',1,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,13.00,'','2025-09-15 13:19:38','2025-09-15 13:19:54',NULL,NULL,NULL,NULL,NULL),
(663,31,53,'2025-09-20','06:00:00','19:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,13.00,'','2025-09-15 13:19:38','2025-09-15 13:20:51',NULL,NULL,NULL,NULL,NULL),
(664,20,57,'2025-09-24','11:00:00','21:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,14.10,'','2025-09-15 13:28:57','2025-09-15 13:29:10',NULL,NULL,NULL,NULL,NULL),
(665,20,57,'2025-09-26','11:00:00','21:00:00','Security Officer',NULL,0.00,'confirmed',NULL,NULL,0,NULL,12.21,NULL,14.10,'','2025-09-15 13:29:27','2025-09-15 13:29:34',NULL,NULL,NULL,NULL,NULL),
(670,10,19,'2025-09-15','09:00:00','17:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,14.10,'Test shift created by API test','2025-09-15 17:13:37','2025-09-15 17:13:37',NULL,NULL,NULL,NULL,NULL),
(671,10,19,'2025-09-15','09:00:00','17:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,14.10,'Test shift created by API test','2025-09-15 17:14:41','2025-09-15 17:14:41',NULL,NULL,NULL,NULL,NULL),
(672,10,19,'2025-09-15','09:00:00','17:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,14.10,'Updated with legacy role field','2025-09-15 17:15:24','2025-09-15 17:15:24',NULL,NULL,NULL,NULL,NULL),
(673,10,19,'2025-09-15','09:00:00','17:00:00','Security Officer',1,0.00,'allocated',NULL,NULL,0,NULL,12.21,NULL,14.10,'Updated with legacy role field','2025-09-15 17:16:07','2025-09-15 17:16:07',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-09-15 23:02:19
