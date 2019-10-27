/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50553
 Source Host           : localhost:3306
 Source Schema         : bankpay

 Target Server Type    : MySQL
 Target Server Version : 50553
 File Encoding         : 65001

 Date: 20/07/2019 17:10:51
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for s_channel_account
-- ----------------------------
DROP TABLE IF EXISTS `s_channel_account`;
CREATE TABLE `s_channel_account`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `device_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备id',
  `client_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '客户端Id',
  `channel_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '渠道id',
  `ali_account` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '阿里账户',
  `account_status` tinyint(1) NULL DEFAULT NULL COMMENT '账户状态1:绑定中,2:未绑定',
  `device_status` tinyint(1) NULL DEFAULT NULL COMMENT '客户端状态1：在线2：离线',
  `add_time` int(30) NULL DEFAULT NULL COMMENT '绑定时间',
  `success_time` int(30) NULL DEFAULT NULL COMMENT '成功时间',
  `binding_times` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for s_channel_calculation
-- ----------------------------
DROP TABLE IF EXISTS `s_channel_calculation`;
CREATE TABLE `s_channel_calculation`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '渠道',
  `money` decimal(20, 3) NULL DEFAULT NULL COMMENT '统计时间',
  `start_time` int(11) NULL DEFAULT NULL COMMENT '统计开始时间',
  `end_time` int(11) NULL DEFAULT NULL COMMENT '统计结束时间',
  `type` tinyint(1) NULL DEFAULT NULL COMMENT '统计类型1：正常订单2：掉单',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 38 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for s_check_order
-- ----------------------------
DROP TABLE IF EXISTS `s_check_order`;
CREATE TABLE `s_check_order`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `check_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '后台登陆id',
  `order_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '查询订单号',
  `order_ju` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '付呗订单号',
  `status` tinyint(1) NULL DEFAULT 0 COMMENT '查询状态,0:查询中,1:查询成功2:查询失败',
  `order_status` tinyint(1) NULL DEFAULT 0 COMMENT '订单状态,0:查询中,1:已付款,2:未付款。',
  `add_time` int(11) NULL DEFAULT NULL COMMENT '查询时间',
  `check_times` int(10) NULL DEFAULT 1 COMMENT '查询次数',
  `update_time` int(11) NULL DEFAULT 0 COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for s_device
-- ----------------------------
DROP TABLE IF EXISTS `s_device`;
CREATE TABLE `s_device`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_type` int(1) NULL DEFAULT NULL COMMENT '区分猫池或者短信app 1：猫池2：短信app',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '电话号码/账号',
  `bank_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '银行名称',
  `bank_mark` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '银行简称',
  `card` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '银行卡号',
  `name` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '持卡人姓名',
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '版本号',
  `device_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备id',
  `client_id` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '客户端id',
  `use_times` int(11) NULL DEFAULT 0 COMMENT '使用次数',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '上线时间',
  `status` tinyint(1) NULL DEFAULT NULL COMMENT '0空闲 1工作中',
  `lock_time` int(11) NULL DEFAULT 0 COMMENT '锁定时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间\r\n',
  `channel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '渠道id',
  `is_online` tinyint(1) UNSIGNED NULL DEFAULT 1 COMMENT '1:在线 ：2不在线 3：禁用',
  `total_money` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '当前总收益',
  `today_money` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '当日总收益',
  `is_prohibit` int(1) NULL DEFAULT 2 COMMENT '1:正常：2禁用',
  `last_use_time` int(11) NULL DEFAULT 0 COMMENT '上次使用时间',
  `pay_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'pay_url',
  `url_updatetime` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '二维码更新时间',
  `warnings_times` int(20) UNSIGNED NULL DEFAULT 0 COMMENT '警告次数',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `x_phone`(`phone`) USING BTREE,
  INDEX `x_card`(`card`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 290 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for s_diaodan
-- ----------------------------
DROP TABLE IF EXISTS `s_diaodan`;
CREATE TABLE `s_diaodan`  (
  `orderNo` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `money` decimal(10, 2) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for s_loseorder_notify_callback_log
-- ----------------------------
DROP TABLE IF EXISTS `s_loseorder_notify_callback_log`;
CREATE TABLE `s_loseorder_notify_callback_log`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `PayHash` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '唯一码',
  `PayCardNo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '打款卡号',
  `PayMoney` decimal(10, 2) NULL DEFAULT NULL COMMENT '打款金额',
  `PayCardUser` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '打款人姓名',
  `PayCardType` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '打款银行名称',
  `PayTime` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '打款时间',
  `PayComment` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '打款备注或打款留言',
  `RecvCardNo` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '收款账号',
  `RecvCardType` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '收款银行名称',
  `RecvCardMark` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '收款银行简称',
  `RecvCardBalance` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '收款卡号余额',
  `Channel` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '工作室标识',
  `orderNo` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '匹配到的订单号',
  `createTime` int(10) NULL DEFAULT NULL COMMENT '添加时间',
  `matchTime` int(10) NULL DEFAULT NULL COMMENT '匹配成功时间',
  `status` tinyint(1) NULL DEFAULT 0 COMMENT '0:收到此消息1:匹配到并回调,2:收到未匹配到未回调',
  `times` int(10) NULL DEFAULT 1 COMMENT '收到请求次数',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `x_PayHash`(`PayHash`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 600 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for s_merchant
-- ----------------------------
DROP TABLE IF EXISTS `s_merchant`;
CREATE TABLE `s_merchant`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `merchant_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '商户ID',
  `day_quota` decimal(20, 3) NOT NULL DEFAULT 1000000000000000.000 COMMENT '商户日限额',
  `quota` decimal(20, 3) NOT NULL DEFAULT 1000000000000000.000 COMMENT '商户总限额',
  `today_sum` decimal(20, 3) NULL DEFAULT 0.000 COMMENT '商户今日总收款金额',
  `total_sum` decimal(20, 3) NULL DEFAULT 0.000 COMMENT '商户当前总收款金额',
  `token` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '协议token',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_merchant_id`(`merchant_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of s_merchant
-- ----------------------------
INSERT INTO `s_merchant` VALUES (1, 'test01', 1000000000000000.000, 1000000000000000.000, 0.000, 324275.688, '0e698a8ffc1a0af622c7b4db3cb750cc');
INSERT INTO `s_merchant` VALUES (2, 'dd', 9999999.999, 1000000000000000.000, 0.000, 154122.080, '0e698a8ffc1a0af622c7b4db3cb750cc');
INSERT INTO `s_merchant` VALUES (3, 'cc', 1000000000000000.000, 1000000000000000.000, 0.000, 0.000, 'd8dfdeaceb53befb1a55207359295345');
INSERT INTO `s_merchant` VALUES (4, 'bb', 1000000000000000.000, 1000000000000000.000, 0.000, 0.000, 'cf028432dd50814d3a5ee628f3b541e2');

-- ----------------------------
-- Table structure for s_node
-- ----------------------------
DROP TABLE IF EXISTS `s_node`;
CREATE TABLE `s_node`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_name` varchar(155) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '节点名称',
  `control_name` varchar(155) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '控制器名',
  `action_name` varchar(155) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '方法名',
  `is_menu` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否是菜单项 1不是 2是',
  `type_id` int(11) NOT NULL COMMENT '父级节点id',
  `style` varchar(155) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '菜单样式',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 68 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of s_node
-- ----------------------------
INSERT INTO `s_node` VALUES (1, '用户管理', '#', '#', 2, 0, 'fa fa-users');
INSERT INTO `s_node` VALUES (2, '管理员管理', 'user', 'index', 2, 1, '');
INSERT INTO `s_node` VALUES (3, '添加管理员', 'user', 'useradd', 1, 2, '');
INSERT INTO `s_node` VALUES (4, '编辑管理员', 'user', 'useredit', 1, 2, '');
INSERT INTO `s_node` VALUES (5, '删除管理员', 'user', 'userdel', 1, 2, '');
INSERT INTO `s_node` VALUES (6, '角色管理', 'role', 'index', 2, 1, '');
INSERT INTO `s_node` VALUES (7, '添加角色', 'role', 'roleadd', 1, 6, '');
INSERT INTO `s_node` VALUES (8, '编辑角色', 'role', 'roleedit', 1, 6, '');
INSERT INTO `s_node` VALUES (9, '删除角色', 'role', 'roledel', 1, 6, '');
INSERT INTO `s_node` VALUES (10, '分配权限', 'role', 'giveaccess', 1, 6, '');
INSERT INTO `s_node` VALUES (11, '系统管理', '#', '#', 2, 0, 'fa fa-desktop');
INSERT INTO `s_node` VALUES (12, '数据备份/还原', 'data', 'index', 2, 11, '');
INSERT INTO `s_node` VALUES (13, '备份数据', 'data', 'importdata', 1, 12, '');
INSERT INTO `s_node` VALUES (14, '还原数据', 'data', 'backdata', 1, 12, '');
INSERT INTO `s_node` VALUES (15, '节点管理', 'node', 'index', 2, 1, '');
INSERT INTO `s_node` VALUES (16, '添加节点', 'node', 'nodeadd', 1, 15, '');
INSERT INTO `s_node` VALUES (17, '编辑节点', 'node', 'nodeedit', 1, 15, '');
INSERT INTO `s_node` VALUES (18, '删除节点', 'node', 'nodedel', 1, 15, '');
INSERT INTO `s_node` VALUES (19, '文章管理', 'articles', 'index', 1, 0, 'fa fa-book');
INSERT INTO `s_node` VALUES (20, '文章列表', 'articles', 'index', 2, 19, '');
INSERT INTO `s_node` VALUES (21, '添加文章', 'articles', 'articleadd', 1, 19, '');
INSERT INTO `s_node` VALUES (22, '编辑文章', 'articles', 'articleedit', 1, 19, '');
INSERT INTO `s_node` VALUES (23, '删除文章', 'articles', 'articledel', 1, 19, '');
INSERT INTO `s_node` VALUES (24, '上传图片', 'articles', 'uploadImg', 1, 19, '');
INSERT INTO `s_node` VALUES (25, '个人中心', '#', '#', 1, 0, '');
INSERT INTO `s_node` VALUES (26, '编辑信息', 'profile', 'index', 1, 25, '');
INSERT INTO `s_node` VALUES (27, '编辑头像', 'profile', 'headedit', 1, 25, '');
INSERT INTO `s_node` VALUES (28, '上传头像', 'profile', 'uploadheade', 1, 25, '');
INSERT INTO `s_node` VALUES (29, '渠道设备管理', '#', '#', 1, 0, 'fa fa-desktop');
INSERT INTO `s_node` VALUES (30, '支付宝账户列表', 'alichannel', 'index', 2, 29, '');
INSERT INTO `s_node` VALUES (31, '在线设备列表', 'devices', 'onlinedevice', 2, 29, '');
INSERT INTO `s_node` VALUES (32, '获取渠道设备', 'alichannel', 'channelaccountadd', 1, 29, '');
INSERT INTO `s_node` VALUES (33, '订单管理', '#', '#', 2, 0, 'fa fa-desktop');
INSERT INTO `s_node` VALUES (34, '订单列表', 'order', 'index', 2, 33, 'fa fa-desktop');
INSERT INTO `s_node` VALUES (35, '支付宝列表', 'account', 'index', 2, 29, '');
INSERT INTO `s_node` VALUES (36, '删除支付宝', 'account', 'accountdel', 1, 29, '');
INSERT INTO `s_node` VALUES (37, '编辑支付宝', 'account', 'accountedit', 1, 29, '');
INSERT INTO `s_node` VALUES (38, '商户列表', 'merchant', 'index', 2, 29, '');
INSERT INTO `s_node` VALUES (39, '删除商户', 'merchant', 'merchantdel', 1, 29, '');
INSERT INTO `s_node` VALUES (40, '添加商户', 'merchant', 'merchantadd', 1, 29, '');
INSERT INTO `s_node` VALUES (41, '系统设置', 'system', 'index', 2, 29, '');
INSERT INTO `s_node` VALUES (42, '工作室专用', '#', '#', 2, 0, 'fa fa-coffee');
INSERT INTO `s_node` VALUES (43, '银行卡列表', 'bank', 'banklist', 2, 42, '');
INSERT INTO `s_node` VALUES (44, '绑定银行卡', 'bank', 'createbank', 2, 42, '');
INSERT INTO `s_node` VALUES (45, '编辑银行卡', 'bank', 'bankedit', 1, 42, '');
INSERT INTO `s_node` VALUES (46, '删除银行卡', 'bank', 'bankdel', 1, 42, '');
INSERT INTO `s_node` VALUES (47, '订单费率（锁账户）', 'ratemanagement', 'accountfloatingedit', 1, 33, '');
INSERT INTO `s_node` VALUES (48, '掉单列表', 'order', 'loseorder', 1, 33, '');
INSERT INTO `s_node` VALUES (49, '30分钟成功率', 'devices', 'onlinedevicethirty', 2, 29, '');
INSERT INTO `s_node` VALUES (50, '设备列表', 'bankdevice', 'index', 2, 42, '');
INSERT INTO `s_node` VALUES (51, '工作室收益列表', 'bankdevice', 'channelincome', 2, 42, '');
INSERT INTO `s_node` VALUES (52, '回调订单', 'order', 'orderedit', 1, 33, '');
INSERT INTO `s_node` VALUES (53, '确认回调', 'order', 'notify', 1, 33, '');
INSERT INTO `s_node` VALUES (54, '启动/停止收款', 'bankdevice', 'changestatus', 1, 42, '');
INSERT INTO `s_node` VALUES (55, '企业支付宝配置列表', 'enterprisealipay', 'alilist', 1, 42, '');
INSERT INTO `s_node` VALUES (56, '添加企业支付宝配置', 'enterprisealipay', 'createaliconfig', 1, 42, '');
INSERT INTO `s_node` VALUES (57, '上传收款二维码图片', 'devices', 'uppaymentqrcode', 1, 42, '');
INSERT INTO `s_node` VALUES (58, '测试下单', 'order', 'testcreateorder', 2, 33, '');
INSERT INTO `s_node` VALUES (59, '查单列表', 'order', 'checkorderlist', 1, 33, '');
INSERT INTO `s_node` VALUES (60, '订单查询', 'order', 'checkorder', 1, 33, '');
INSERT INTO `s_node` VALUES (61, '流水列表', 'notify', 'index', 2, 33, '');
INSERT INTO `s_node` VALUES (62, '未匹配流水', 'notify', 'loseorder', 2, 33, '');
INSERT INTO `s_node` VALUES (63, '删除流水', 'notify', 'deletelog', 1, 42, '');
INSERT INTO `s_node` VALUES (64, '删除流水', 'notify', 'deletelog', 1, 33, '');
INSERT INTO `s_node` VALUES (65, '回调成功订单导出只EXCEL', 'order', 'exportallorder', 1, 42, '');
INSERT INTO `s_node` VALUES (66, '短信格式查验', 'messageinfo', 'index', 2, 42, '');
INSERT INTO `s_node` VALUES (67, '短信列表', 'messageinfo', 'smslist', 2, 42, '');

-- ----------------------------
-- Table structure for s_order
-- ----------------------------
DROP TABLE IF EXISTS `s_order`;
CREATE TABLE `s_order`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `merchant_id` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '商户id',
  `amount` decimal(10, 2) NULL DEFAULT NULL COMMENT '订单金额',
  `payable_amount` decimal(10, 2) NULL DEFAULT NULL COMMENT '应付金额',
  `actual_amount` decimal(10, 2) NULL DEFAULT NULL COMMENT '实际付款金额',
  `order_status` tinyint(1) NULL DEFAULT 0 COMMENT '订单状态 1:已付款 2：付款失败3：用户取消0：未付款4：超时未统计',
  `channel` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '渠道识别',
  `player_name` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '付款人真实姓名',
  `card` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '收款银行卡号',
  `name` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '收款者姓名',
  `bank_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '收款银行卡名称',
  `bank_mark` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '收款银行卡简称',
  `add_time` int(12) NULL DEFAULT NULL COMMENT '订单创建时间',
  `pay_time` int(12) NULL DEFAULT 0 COMMENT '订单支付时间',
  `order_no` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '商户订单号',
  `order_me` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '通道订单号',
  `orderme` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '自定订单号',
  `notify_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '回调地址',
  `payment` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '付款方式',
  `account` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '收款账号 （ali/wechat）',
  `payerloginid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '收款人登陆id',
  `payeruserid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '付款人id',
  `payersessionid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'payerSessionId',
  `time_update` int(11) NULL DEFAULT NULL COMMENT '最后修改时间',
  `qr_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '付款地址',
  `msgid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'fff ',
  `ali_order` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '支付宝订单号',
  `userId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '收款账号识别id',
  `payuserid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用户ID',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_payersessionid`(`payersessionid`(191)) USING BTREE,
  INDEX `idx_order_no`(`order_no`) USING BTREE,
  INDEX `idx_account`(`account`) USING BTREE,
  INDEX `idx_msgid`(`msgid`(191)) USING BTREE,
  INDEX `idx_card`(`card`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 103433 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for s_prohibited_user
-- ----------------------------
DROP TABLE IF EXISTS `s_prohibited_user`;
CREATE TABLE `s_prohibited_user`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'userid',
  `no_pay_times` int(10) NULL DEFAULT 1,
  `add_time` int(11) NULL DEFAULT 0 COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 50790 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for s_receivables_bind
-- ----------------------------
DROP TABLE IF EXISTS `s_receivables_bind`;
CREATE TABLE `s_receivables_bind`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receivables` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '收款卡号',
  `payuserid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '付款userid',
  `number_of_use` int(11) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19302 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for s_role
-- ----------------------------
DROP TABLE IF EXISTS `s_role`;
CREATE TABLE `s_role`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `role_name` varchar(155) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '角色名称',
  `rule` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '权限节点数据',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of s_role
-- ----------------------------
INSERT INTO `s_role` VALUES (1, '超级管理员', '*');
INSERT INTO `s_role` VALUES (2, '系统维护员', '1,2,3,4,5,6,7,8,9,10,33,34,47,48,52,53,58,59,60,42,43,44,45,46,50');
INSERT INTO `s_role` VALUES (3, '渠道用户一测试', '29,30');
INSERT INTO `s_role` VALUES (4, '渠道用户', '25,26,27,28,29,31,49,33,34,42,43,50,51');
INSERT INTO `s_role` VALUES (5, '工作室', '29,31,33,34,47,48,52,53,58,42,43,44,45,50,51,54,66');
INSERT INTO `s_role` VALUES (6, '订单查看', '33,34,47,61,62,42,50,51');
INSERT INTO `s_role` VALUES (7, '内部测试查单', '29,31,38,49,33,34,48,42,50,51');
INSERT INTO `s_role` VALUES (8, '财务', '33,34,42,50');
INSERT INTO `s_role` VALUES (9, '手动回调', '33,34,52,53');
INSERT INTO `s_role` VALUES (10, '测试下单', '33,58');

-- ----------------------------
-- Table structure for s_serverlist
-- ----------------------------
DROP TABLE IF EXISTS `s_serverlist`;
CREATE TABLE `s_serverlist`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '服务器ip',
  `remarks` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `update_time` int(20) NULL DEFAULT NULL COMMENT '更新时间',
  `add_time` int(20) NULL DEFAULT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of s_serverlist
-- ----------------------------
INSERT INTO `s_serverlist` VALUES (1, '1.1.1.1', '测试', 1561346396, 1561345925);

-- ----------------------------
-- Table structure for s_sms
-- ----------------------------
DROP TABLE IF EXISTS `s_sms`;
CREATE TABLE `s_sms`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `sms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '短信内容',
  `use_state` int(2) UNSIGNED NULL DEFAULT 0 COMMENT '使用状态 默认为0 未使用，使用过的为1',
  `add_time` int(20) NULL DEFAULT NULL COMMENT '短信入库时间',
  `use_time` int(20) NULL DEFAULT NULL COMMENT '短信使用时间',
  `level` int(2) UNSIGNED NULL DEFAULT 0 COMMENT '短信级别，1：匹配到订单的短信，2：未匹配到订单的短信，3：垃圾短信',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '手机号',
  `card` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '银行卡号',
  `order_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '匹配到的单号',
  `return_msg` varchar(9999) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '返回的message',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2417 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for s_system_config
-- ----------------------------
DROP TABLE IF EXISTS `s_system_config`;
CREATE TABLE `s_system_config`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `config_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '配置名称',
  `config_data` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '配置配置',
  `config_info` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '配置详情',
  `config_status` tinyint(1) NULL DEFAULT NULL COMMENT '配置状态1：后台显示2：不可显示',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_config_name`(`config_name`(171)) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of s_system_config
-- ----------------------------
INSERT INTO `s_system_config` VALUES (1, 'money_format', '10,20,50,100,200,300,400,500,1000', '下单金额限制', 2);
INSERT INTO `s_system_config` VALUES (2, 'url_use_times', '30', '收款链接限制(条数)', 2);
INSERT INTO `s_system_config` VALUES (3, 'account_create_times', '30', '支付宝账户生成url次数', 2);
INSERT INTO `s_system_config` VALUES (4, 'order_limit_time', '180', '支付宝锁定时间', 1);
INSERT INTO `s_system_config` VALUES (5, 'account_amount_floating', '0.1', '锁支付宝金额浮动（%）', 2);
INSERT INTO `s_system_config` VALUES (6, 'account_amount_floating_start', '50', '大于指定金额支付宝手续费', 1);
INSERT INTO `s_system_config` VALUES (7, 'account_amount_floating_end', '5000', '小于指定金额支付宝手续费', 1);
INSERT INTO `s_system_config` VALUES (8, 'account_money_amount_floating', '0.1', '支付宝锁金额浮动（%）', 1);
INSERT INTO `s_system_config` VALUES (9, 'account_money_presence_floating', '0.05', '支付宝锁金额金额存在修改浮动(<=)', 1);
INSERT INTO `s_system_config` VALUES (11, 'account_money_amount_floating_start', '1000', '大于指定金额支付宝手续费(锁金额)', 1);
INSERT INTO `s_system_config` VALUES (12, 'account_money_amount_floating_end', '20000', '小于指定金额支付宝手续费(锁金额)', 1);
INSERT INTO `s_system_config` VALUES (13, 'order_pay_limit_time', '600', '转账倒计时', 1);
INSERT INTO `s_system_config` VALUES (14, 'order_pay_limit_time_start', '1800', '查询指定时间段内订单(开始时间)', 1);

-- ----------------------------
-- Table structure for s_user
-- ----------------------------
DROP TABLE IF EXISTS `s_user`;
CREATE TABLE `s_user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '密码',
  `head` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT '' COMMENT '头像',
  `login_times` int(11) NOT NULL DEFAULT 0 COMMENT '登陆次数',
  `last_login_ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `last_login_time` int(11) NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `real_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '真实姓名',
  `status` int(1) NOT NULL DEFAULT 0 COMMENT '状态',
  `role_id` int(11) NOT NULL DEFAULT 1 COMMENT '用户角色id',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_name`(`user_name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8 COLLATE = utf8_bin ROW_FORMAT = Compact;

-- ----------------------------
-- Records of s_user
-- ----------------------------
INSERT INTO `s_user` VALUES (1, 'admin', '6af2d5a5fb3dc3e59895a9f532af83a4', '/static/admin/images/profile_small.jpg', 722, '103.102.6.3', 1563609747, 'admin', 1, 1);
INSERT INTO `s_user` VALUES (2, 'channel-test-1', '84386805f4fa719c7023544210fea50c', '/static/admin/images/profile_small.jpg', 0, '', 0, '###', 1, 3);
INSERT INTO `s_user` VALUES (3, 'dd', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 0, '', 0, '渠道dd', 1, 5);
INSERT INTO `s_user` VALUES (6, 'order', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 21, '103.242.132.43', 1561688752, 'order', 1, 6);
INSERT INTO `s_user` VALUES (7, 'studio_13', '17884a6b255066b86b212f3907507a64', '/static/admin/images/profile_small.jpg', 1, '182.239.82.105', 1547642856, 'studio_13', 1, 5);
INSERT INTO `s_user` VALUES (8, 'studio_7', '9f8d4aefc0fac6ceef9b99aca8377c13', '/static/admin/images/profile_small.jpg', 1, '210.195.194.39', 1547622731, 'studio_7', 1, 5);
INSERT INTO `s_user` VALUES (9, 'studio_5', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 1, '223.104.254.172', 1547764521, 'studio_5', 1, 5);
INSERT INTO `s_user` VALUES (10, 'studio_8', '34e49fbd34d03c6ad61d9d651d1b61d5', '/static/admin/images/profile_small.jpg', 1, '113.89.238.213', 1551961410, 'studio_8', 1, 5);
INSERT INTO `s_user` VALUES (11, 'studio_ts', '84386805f4fa719c7023544210fea50c', '/static/admin/images/profile_small.jpg', 0, '', 0, 'studio_ts', 1, 5);
INSERT INTO `s_user` VALUES (12, 'neibu_1', '84386805f4fa719c7023544210fea50c', '/static/admin/images/profile_small.jpg', 0, '', 0, 'neibu_1', 1, 7);
INSERT INTO `s_user` VALUES (13, 'chadan_1', 'd0b3fe8f273ad47106191fb98793b41f', '/static/admin/images/profile_small.jpg', 0, '', 0, 'chadan_1', 1, 7);
INSERT INTO `s_user` VALUES (14, 'cw', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 0, '', 0, '123123', 1, 8);
INSERT INTO `s_user` VALUES (15, 'order_callback', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 0, '', 0, '工作室回调订单', 1, 9);
INSERT INTO `s_user` VALUES (16, 'studio_dc', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 0, '', 0, '大锤', 1, 5);
INSERT INTO `s_user` VALUES (17, 'test', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 0, '', 0, '123123', 1, 5);
INSERT INTO `s_user` VALUES (18, 'createorder', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 0, '', 0, '123123', 1, 10);
INSERT INTO `s_user` VALUES (19, 'studio_maomao', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 0, '', 0, 'studio_maomao', 1, 5);
INSERT INTO `s_user` VALUES (20, 'studio_aj', '84386805f4fa719c7023544210fea50c', '/static/admin/images/profile_small.jpg', 0, '', 0, 'studio_aj', 1, 5);
INSERT INTO `s_user` VALUES (21, 'studio_yd', '84386805f4fa719c7023544210fea50c', '/static/admin/images/profile_small.jpg', 0, '', 0, 'studio_yd', 1, 5);
INSERT INTO `s_user` VALUES (22, 'studio_lk', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 0, '', 0, 'studio_lk', 1, 5);
INSERT INTO `s_user` VALUES (23, 'studio_am', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 0, '', 0, '阿木', 1, 5);
INSERT INTO `s_user` VALUES (24, 'studio_sp', '17a30769ff8f58e57de69c49d302a757', '/static/admin/images/profile_small.jpg', 0, '', 0, '三胖', 1, 5);

SET FOREIGN_KEY_CHECKS = 1;
