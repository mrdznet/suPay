-- MySQL dump 10.14  Distrib 5.5.60-MariaDB, for Linux (x86_64)
--
-- Host: 172.16.0.200    Database: shoukuan
-- ------------------------------------------------------
-- Server version	10.1.9-MariaDBV1.0R012D003-20180427-1600

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

create database if not exists shoukuan;

use shoukuan;
--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章id',
  `title` varchar(155) NOT NULL COMMENT '文章标题',
  `description` varchar(255) NOT NULL COMMENT '文章描述',
  `keywords` varchar(155) NOT NULL COMMENT '文章关键字',
  `thumbnail` varchar(255) NOT NULL COMMENT '文章缩略图',
  `content` text NOT NULL COMMENT '文章内容',
  `add_time` datetime NOT NULL COMMENT '发布时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `node`
--

DROP TABLE IF EXISTS `node`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `node` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_name` varchar(155) NOT NULL DEFAULT '' COMMENT '节点名称',
  `control_name` varchar(155) NOT NULL DEFAULT '' COMMENT '控制器名',
  `action_name` varchar(155) NOT NULL COMMENT '方法名',
  `is_menu` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否是菜单项 1不是 2是',
  `type_id` int(11) NOT NULL COMMENT '父级节点id',
  `style` varchar(155) DEFAULT '' COMMENT '菜单样式',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `role_name` varchar(155) NOT NULL COMMENT '角色名称',
  `rule` varchar(255) DEFAULT '' COMMENT '权限节点数据',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_ali`
--

DROP TABLE IF EXISTS `s_ali`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `s_ali` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `alinumber` varchar(255) NOT NULL COMMENT '支付宝号',
  `quota` decimal(20,3) DEFAULT '1000000000000000.000' COMMENT '总限',
  `day_quota` decimal(20,3) DEFAULT '1000000000000000.000' COMMENT '日限',
  `total_sum` decimal(20,3) DEFAULT '0.000' COMMENT '当前该支付宝总交易额',
  PRIMARY KEY (`id`),
  KEY `idx_alinumber` (`alinumber`(191))
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_articles`
--

DROP TABLE IF EXISTS `s_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `s_articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章id',
  `title` varchar(155) NOT NULL COMMENT '文章标题',
  `description` varchar(255) NOT NULL COMMENT '文章描述',
  `keywords` varchar(155) NOT NULL COMMENT '文章关键字',
  `thumbnail` varchar(255) NOT NULL COMMENT '文章缩略图',
  `content` text NOT NULL COMMENT '文章内容',
  `add_time` datetime NOT NULL COMMENT '发布时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_channel_account`
--

DROP TABLE IF EXISTS `s_channel_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `s_channel_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `device_id` varchar(100) NOT NULL COMMENT '设备id',
  `client_id` varchar(100) NOT NULL COMMENT '客户端Id',
  `channel_id` int(100) NOT NULL COMMENT '渠道id',
  `ali_account` varchar(255) NOT NULL COMMENT '阿里账户',
  `account_status` tinyint(1) NOT NULL COMMENT '账户状态',
  `device_status` tinyint(1) NOT NULL COMMENT '客户端状态',
  `add_time` datetime NOT NULL COMMENT '绑定时间',
  `success_time` datetime NOT NULL COMMENT '成功时间',
  `binding_times` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_device`
--

DROP TABLE IF EXISTS `s_device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `s_device` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `device_id` varchar(255) NOT NULL COMMENT '设备ID',
  `client_id` varchar(255) NOT NULL COMMENT '客户端ID',
  `binding_state` int(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否已经绑定支付宝 1：空闲 2：已绑定',
  `ali_qr` varchar(255) NOT NULL COMMENT '当前设备绑定的支付宝qr',
  `account` varchar(255) NOT NULL COMMENT '账号',
  `payment` varchar(10) NOT NULL COMMENT 'ali/wechat',
  `lock_time` int(30) DEFAULT '0' COMMENT '锁定时间（时间戳）',
  PRIMARY KEY (`id`),
  KEY `idx_account` (`account`(191))
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_merchant`
--

DROP TABLE IF EXISTS `s_merchant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `s_merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `merchant_id` varchar(255) NOT NULL COMMENT '商户ID',
  `day_quota` decimal(20,3) NOT NULL DEFAULT '1000000000000000.000' COMMENT '商户日限额',
  `quota` decimal(20,3) NOT NULL DEFAULT '1000000000000000.000' COMMENT '商户总限额',
  `total_sum` decimal(20,3) DEFAULT '0.000' COMMENT '商户当前总收款金额',
  `token` char(32) NOT NULL COMMENT '协议token',
  PRIMARY KEY (`id`),
  KEY `idx_merchant_id` (`merchant_id`(191))
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_node`
--

DROP TABLE IF EXISTS `s_node`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `s_node` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_name` varchar(155) NOT NULL DEFAULT '' COMMENT '节点名称',
  `control_name` varchar(155) NOT NULL DEFAULT '' COMMENT '控制器名',
  `action_name` varchar(155) NOT NULL COMMENT '方法名',
  `is_menu` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否是菜单项 1不是 2是',
  `type_id` int(11) NOT NULL COMMENT '父级节点id',
  `style` varchar(155) DEFAULT '' COMMENT '菜单样式',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_order`
--

DROP TABLE IF EXISTS `s_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `s_order` (
  `id` int(1) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `merchant_id` varchar(11) NOT NULL COMMENT '商户id',
  `amount` decimal(11,3) NOT NULL COMMENT '订单金额',
  `payable_amount` decimal(10,3) NOT NULL COMMENT '应付金额',
  `actual_amount` decimal(10,3) NOT NULL COMMENT '实际付款金额',
  `order_no` varchar(255) NOT NULL COMMENT '商户订单号',
  `orderme` varchar(255) NOT NULL COMMENT '自定订单号',
  `subject` varchar(255) NOT NULL COMMENT '为空 待定',
  `info` varchar(255) NOT NULL COMMENT '玩家信息 可为空',
  `add_time` int(20) NOT NULL COMMENT '订单创建时间',
  `order_status` tinyint(1) DEFAULT '0' COMMENT '订单状态 1:已付款 2：付款失败3：用户取消0：未付款4：超时未统计',
  `notify_url` varchar(255) NOT NULL COMMENT '回调地址',
  `payment` varchar(255) NOT NULL COMMENT '付款方式',
  `account` varchar(255) NOT NULL COMMENT '收款账号 （ali/wechat）',
  `payerusername` varchar(255) NOT NULL COMMENT '付款姓名',
  `payerloginid` varchar(255) NOT NULL COMMENT '收款人登陆id',
  `payeruserid` varchar(255) NOT NULL COMMENT '付款人id',
  `payersessionid` varchar(255) NOT NULL,
  `time_update` int(30) NOT NULL COMMENT '最后修改时间',
  `qr_url` varchar(255) NOT NULL COMMENT '付款地址',
  PRIMARY KEY (`id`),
  KEY `idx_payersessionid` (`payersessionid`(191)),
  KEY `idx_account` (`account`(191))
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_order_callback`
--

DROP TABLE IF EXISTS `s_order_callback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `s_order_callback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `merchant_id` varchar(11) NOT NULL COMMENT '商户id',
  `amount` decimal(11,3) NOT NULL COMMENT '订单金额',
  `payable_amount` decimal(10,3) NOT NULL COMMENT '应付金额',
  `actual_amount` decimal(10,3) NOT NULL COMMENT '实际付款金额',
  `order_no` varchar(255) NOT NULL COMMENT '商户订单号',
  `orderme` varchar(255) NOT NULL COMMENT '自定订单号',
  `subject` varchar(255) NOT NULL COMMENT '为空 待定',
  `info` varchar(255) NOT NULL COMMENT '玩家信息 可为空',
  `add_time` int(20) NOT NULL COMMENT '订单创建时间',
  `order_status` tinyint(1) DEFAULT '0' COMMENT '订单状态 1:已回调 ',
  `notify_url` varchar(255) NOT NULL COMMENT '回调地址',
  `payment` varchar(255) NOT NULL COMMENT '付款方式',
  `account` varchar(255) NOT NULL COMMENT '收款账号 （ali/wechat）',
  `payerusername` varchar(255) NOT NULL COMMENT '付款姓名',
  `payerloginid` varchar(255) NOT NULL COMMENT '收款人登陆id',
  `payeruserid` varchar(255) NOT NULL COMMENT '付款人id',
  `payersessionid` varchar(255) NOT NULL COMMENT 'payerSessionId',
  `time_update` int(30) NOT NULL COMMENT '最后修改时间',
  `qr_url` varchar(255) NOT NULL COMMENT '付款地址',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_role`
--

DROP TABLE IF EXISTS `s_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `s_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `role_name` varchar(155) NOT NULL COMMENT '角色名称',
  `rule` varchar(255) DEFAULT '' COMMENT '权限节点数据',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_user`
--

DROP TABLE IF EXISTS `s_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `s_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '密码',
  `head` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '头像',
  `login_times` int(11) NOT NULL DEFAULT '0' COMMENT '登陆次数',
  `last_login_ip` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `real_name` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '真实姓名',
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '状态',
  `role_id` int(11) NOT NULL DEFAULT '1' COMMENT '用户角色id',
  PRIMARY KEY (`id`),
  KEY `idx_user_name` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '密码',
  `head` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '头像',
  `login_times` int(11) NOT NULL DEFAULT '0' COMMENT '登陆次数',
  `last_login_ip` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `real_name` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '真实姓名',
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '状态',
  `role_id` int(11) NOT NULL DEFAULT '1' COMMENT '用户角色id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 ;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-12-17 23:05:08
