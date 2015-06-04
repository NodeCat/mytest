--
-- Table structure for table `stock_bill_out_container`
--

USE wms;
DROP TABLE IF EXISTS `stock_bill_out_container`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_bill_out_container` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `refer_code` varchar(45) NOT NULL,
      `pro_code` varchar(45) NOT NULL,
      `batch` varchar(45) NOT NULL,
      `wh_id` int(11) NOT NULL,
      `location_id` int(11) NOT NULL,
      `created_time` datetime NOT NULL,
      `updated_time` datetime NOT NULL,
      `created_user` int(10) unsigned NOT NULL,
      `updated_user` int(10) unsigned NOT NULL,
      `is_deleted` tinyint(1) unsigned NOT NULL,
      `qty` int(11) NOT NULL,
      PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
-- Dump completed on 2015-06-04 16:49:08
