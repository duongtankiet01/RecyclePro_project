-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th12 02, 2025 lúc 11:45 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `kltn2025`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `business_orders`
--

CREATE TABLE `business_orders` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL COMMENT 'ID doanh nghiệp tạo đơn',
  `waste_type` varchar(50) NOT NULL COMMENT 'Loại rác: Nhựa PET, Giấy carton, Kim loại, Thủy tinh',
  `quantity` decimal(10,2) NOT NULL COMMENT 'Khối lượng (kg)',
  `points_per_kg` decimal(10,2) NOT NULL COMMENT 'Điểm tích lũy/kg',
  `total_points` decimal(10,2) NOT NULL COMMENT 'Tổng điểm = quantity * points_per_kg',
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending' COMMENT 'Trạng thái đơn hàng - pending: chờ nhận, active: đang xử lý, completed: hoàn tất, cancelled: đã hủy',
  `notes` text DEFAULT NULL COMMENT 'Ghi chú từ DN',
  `confirmed_at` datetime DEFAULT NULL COMMENT 'Thời gian DN xác nhận và thanh toán',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantity_accepted` decimal(10,2) DEFAULT 0.00 COMMENT 'Tổng kg đã được nhận',
  `quantity_remaining` decimal(10,2) GENERATED ALWAYS AS (`quantity` - `quantity_accepted`) STORED COMMENT 'Số kg còn lại'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Đơn hàng thu gom rác của doanh nghiệp';

--
-- Đang đổ dữ liệu cho bảng `business_orders`
--

INSERT INTO `business_orders` (`order_id`, `business_id`, `waste_type`, `quantity`, `points_per_kg`, `total_points`, `status`, `notes`, `confirmed_at`, `created_at`, `updated_at`, `quantity_accepted`) VALUES
(5, 17, 'Nhựa PET', 100.00, 2.00, 200.00, 'pending', '', NULL, '2025-11-18 14:31:28', '2025-11-19 14:08:10', 100.00),
(6, 17, 'Nhựa PET', 100.00, 2.00, 200.00, 'completed', '', NULL, '2025-11-19 14:09:47', '2025-11-23 03:51:53', 100.00),
(7, 17, 'Nhựa PET', 50.00, 2.00, 100.00, 'completed', '', NULL, '2025-11-19 14:38:20', '2025-11-23 03:51:14', 25.00),
(8, 17, 'Nhựa PET', 10.00, 2.00, 20.00, 'completed', '', NULL, '2025-11-23 03:49:13', '2025-11-23 03:51:55', 10.00),
(9, 32, 'Nhựa PET', 100.00, 2.00, 200.00, 'pending', '', NULL, '2025-11-26 12:44:11', '2025-11-26 12:45:19', 50.00),
(10, 32, 'Nhựa PET', 10.00, 2.00, 20.00, 'completed', '', NULL, '2025-11-26 12:58:08', '2025-11-26 12:58:30', 10.00),
(11, 32, 'Nhựa PET', 100.00, 2.00, 200.00, 'completed', '', NULL, '2025-11-26 13:13:16', '2025-11-26 13:15:08', 100.00),
(12, 32, 'Giấy carton', 50.00, 2.00, 100.00, 'active', '', NULL, '2025-11-27 07:22:51', '2025-12-02 10:38:26', 50.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `business_profiles`
--

CREATE TABLE `business_profiles` (
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `tax_code` varchar(20) DEFAULT NULL,
  `business_license` varchar(50) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `current_credit` decimal(15,2) DEFAULT 0.00 COMMENT 'Số điểm hiện tại của doanh nghiệp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `business_profiles`
--

INSERT INTO `business_profiles` (`business_id`, `user_id`, `company_name`, `tax_code`, `business_license`, `contact_person`, `current_credit`) VALUES
(5, 27, 'DN11', '0254178963', NULL, 'HIC', 0.00),
(6, 32, 'DN test hom nay', '0254136974', NULL, 'Nhan LE', 480.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `collector_point_transactions`
--

CREATE TABLE `collector_point_transactions` (
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `collector_id` int(11) NOT NULL,
  `participant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Li??n k???t v???i order_participants n???u t??? ????n h??ng',
  `topup_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Li??n k???t v???i collector_topup_transactions n???u t??? n???p ??i???m',
  `transaction_type` enum('advance_out','commission','topup','adjustment','penalty') NOT NULL COMMENT 'Lo???i giao d???ch',
  `amount` decimal(10,2) NOT NULL COMMENT 'S??? ??i???m (+ ho???c -)',
  `balance_after` decimal(10,2) NOT NULL COMMENT 'S??? d?? sau giao d???ch',
  `description` varchar(255) DEFAULT NULL COMMENT 'M?? t??? giao d???ch',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='L???ch s??? giao d???ch ??i???m CTV';

--
-- Đang đổ dữ liệu cho bảng `collector_point_transactions`
--

INSERT INTO `collector_point_transactions` (`transaction_id`, `collector_id`, `participant_id`, `topup_id`, `transaction_type`, `amount`, `balance_after`, `description`, `created_at`) VALUES
(7, 18, 22, NULL, '', -30.00, 70.00, 'Ứng điểm cho đơn #22', '2025-11-19 14:39:53'),
(8, 18, 22, NULL, '', 47.00, 117.00, 'Nhận lại ứng + hoa hồng 35% cho đơn #22', '2025-11-23 03:51:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `collector_profiles`
--

CREATE TABLE `collector_profiles` (
  `collector_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `citizen_id` varchar(20) DEFAULT NULL,
  `vehicle_type` enum('motorbike','truck','three_wheeler','other') DEFAULT NULL,
  `current_points` decimal(10,2) DEFAULT 0.00,
  `total_orders_collected` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 5.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `collector_profiles`
--

INSERT INTO `collector_profiles` (`collector_id`, `user_id`, `citizen_id`, `vehicle_type`, `current_points`, `total_orders_collected`, `rating`, `created_at`, `updated_at`) VALUES
(6, 33, '1254236871', 'truck', 0.00, 0, 5.00, '2025-11-26 14:48:20', '2025-11-26 14:48:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `collector_topup_transactions`
--

CREATE TABLE `collector_topup_transactions` (
  `topup_id` bigint(20) UNSIGNED NOT NULL,
  `collector_id` bigint(20) UNSIGNED NOT NULL,
  `topup_code` varchar(20) NOT NULL COMMENT 'Mã nạp điểm để ghi trong nội dung CK',
  `amount_vnd` decimal(15,2) NOT NULL COMMENT 'Số tiền nạp (VND)',
  `points_received` decimal(10,2) NOT NULL COMMENT 'Số điểm nhận được (1 điểm = 100 VND)',
  `status` enum('pending','completed','failed','expired') DEFAULT 'pending',
  `bank_transaction_code` varchar(50) DEFAULT NULL COMMENT 'Mã giao dịch từ ngân hàng',
  `bank_trace` varchar(100) DEFAULT NULL COMMENT 'Trace từ SePay webhook',
  `payment_method` varchar(50) DEFAULT 'bank_transfer',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Hết hạn sau 24h',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `credit_transactions`
--

CREATE TABLE `credit_transactions` (
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL COMMENT 'ID doanh nghiệp nạp tiền',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền nạp (VNĐ)',
  `credits` decimal(15,2) NOT NULL COMMENT 'Số điểm nhận được (1000 VNĐ = 1 điểm)',
  `payment_method` enum('bank_transfer','momo','vnpay','zalopay','manual') DEFAULT 'bank_transfer' COMMENT 'Phương thức thanh toán',
  `status` enum('pending','processing','completed','failed','refunded') DEFAULT 'pending' COMMENT 'Trạng thái giao dịch',
  `transaction_code` varchar(100) DEFAULT NULL COMMENT 'Mã giao dịch từ ngân hàng/ví',
  `bank_transaction_id` varchar(255) DEFAULT NULL COMMENT 'Mã GD từ bên thứ 3 (unique từ webhook)',
  `payment_reference` varchar(255) DEFAULT NULL COMMENT 'Nội dung chuyển khoản',
  `webhook_data` text DEFAULT NULL COMMENT 'Raw data từ webhook (JSON)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP người nạp',
  `user_agent` text DEFAULT NULL COMMENT 'User agent',
  `completed_at` datetime DEFAULT NULL COMMENT 'Thời gian hoàn thành',
  `failed_at` datetime DEFAULT NULL COMMENT 'Thời gian thất bại',
  `failure_reason` text DEFAULT NULL COMMENT 'Lý do thất bại',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử giao dịch nạp điểm';

--
-- Đang đổ dữ liệu cho bảng `credit_transactions`
--

INSERT INTO `credit_transactions` (`transaction_id`, `business_id`, `amount`, `credits`, `payment_method`, `status`, `transaction_code`, `bank_transaction_id`, `payment_reference`, `webhook_data`, `ip_address`, `user_agent`, `completed_at`, `failed_at`, `failure_reason`, `created_at`, `updated_at`) VALUES
(7, 32, 50000.00, 50.00, 'bank_transfer', 'pending', 'TXN20251126155021815', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, NULL, '2025-11-26 14:50:21', '2025-11-26 14:50:21'),
(8, 32, 50000.00, 50.00, 'bank_transfer', 'pending', 'TXN20251126155358289', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, NULL, '2025-11-26 14:53:58', '2025-11-26 14:53:58'),
(9, 33, 50000.00, 50.00, 'bank_transfer', 'pending', 'TXN20251126155442170', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-11-26 14:54:42', '2025-11-26 14:54:42'),
(10, 33, 50000.00, 50.00, 'bank_transfer', 'pending', 'TXN20251126155932390', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-11-26 14:59:32', '2025-11-26 14:59:32'),
(11, 33, 50000.00, 50.00, 'bank_transfer', 'pending', 'TXN20251126160159819', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-11-26 15:01:59', '2025-11-26 15:01:59');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `household_point_transactions`
--

CREATE TABLE `household_point_transactions` (
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `household_id` bigint(20) UNSIGNED NOT NULL,
  `participant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Li??n k???t v???i order_participants n???u t??? ????n h??ng',
  `transaction_type` enum('advance','adjustment','withdrawal','bonus','penalty') NOT NULL COMMENT 'Lo???i giao d???ch',
  `amount` decimal(10,2) NOT NULL COMMENT 'S??? ??i???m (+ ho???c -)',
  `balance_after` decimal(10,2) NOT NULL COMMENT 'S??? d?? sau giao d???ch',
  `description` varchar(255) DEFAULT NULL COMMENT 'M?? t??? giao d???ch',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='L???ch s??? giao d???ch ??i???m h??? gia ????nh';

--
-- Đang đổ dữ liệu cho bảng `household_point_transactions`
--

INSERT INTO `household_point_transactions` (`transaction_id`, `household_id`, `participant_id`, `transaction_type`, `amount`, `balance_after`, `description`, `created_at`) VALUES
(14, 31, 28, 'advance', 95.00, 95.00, 'Nhận điểm từ DN (tự giao) cho đơn #28', '2025-11-26 12:45:49'),
(15, 31, 29, 'advance', 19.00, 64.00, 'Nhận điểm từ DN (tự giao) cho đơn #29', '2025-11-26 12:58:30'),
(16, 31, 30, 'advance', 190.00, 204.00, 'Nhận điểm từ DN (tự giao) cho đơn #30', '2025-11-26 13:15:08'),
(17, 31, NULL, 'withdrawal', -140.00, 64.00, 'Rút tiền - Yêu cầu #6926fde4d2f62', '2025-11-26 13:17:24'),
(18, 31, NULL, 'withdrawal', -64.00, 0.00, 'Rút tiền - Yêu cầu #6926ff9830553', '2025-11-26 13:24:40'),
(19, 35, 32, 'advance', 76.00, 76.00, 'Nhận điểm từ DN (tự giao) cho đơn #32', '2025-12-02 10:38:57'),
(20, 35, NULL, '', -1.00, 0.00, 'Đổi 1x test', '2025-12-02 10:40:01'),
(21, 35, NULL, '', -5.00, 0.00, 'Đổi 1x test 2', '2025-12-02 10:42:15');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `household_profiles`
--

CREATE TABLE `household_profiles` (
  `household_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `current_points` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `household_profiles`
--

INSERT INTO `household_profiles` (`household_id`, `user_id`, `current_points`) VALUES
(10, 21, 0.00),
(11, 22, 0.00),
(12, 23, 0.00),
(13, 24, 0.00),
(14, 25, 0.00),
(15, 26, 0.00),
(16, 28, 0.00),
(17, 29, 0.00),
(18, 30, 0.00),
(19, 31, 0.00),
(20, 34, 0.00),
(21, 35, 70.00),
(22, 36, 0.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_participants`
--

CREATE TABLE `order_participants` (
  `participant_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `household_id` bigint(20) UNSIGNED NOT NULL,
  `collector_id` int(11) DEFAULT NULL COMMENT 'ID của CTV thu hộ',
  `quantity` decimal(10,2) NOT NULL COMMENT 'Số kg hộ này nhận',
  `delivery_method` enum('self','ctv') DEFAULT 'self',
  `status` enum('pending','accepted','in_transit','delivered','confirmed','cancelled') DEFAULT 'pending',
  `accepted_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `advance_given` decimal(10,2) DEFAULT 0.00 COMMENT 'Số điểm CTV ứng trước cho hộ (100%)',
  `final_household_points` decimal(10,2) DEFAULT NULL COMMENT 'Điểm hộ nhận được cuối cùng (60% nếu dùng CTV, 95% nếu tự giao)',
  `final_collector_points` decimal(10,2) DEFAULT NULL COMMENT 'Điểm CTV nhận được (35%)',
  `system_fee` decimal(10,2) DEFAULT NULL COMMENT 'Phí hệ thống (5%)',
  `collected_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian CTV nhận đơn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_participants`
--

INSERT INTO `order_participants` (`participant_id`, `order_id`, `household_id`, `collector_id`, `quantity`, `delivery_method`, `status`, `accepted_at`, `delivered_at`, `cancelled_at`, `created_at`, `updated_at`, `advance_given`, `final_household_points`, `final_collector_points`, `system_fee`, `collected_at`) VALUES
(27, 9, 31, NULL, 100.00, 'self', 'cancelled', '2025-11-26 12:44:47', NULL, '2025-11-26 12:44:50', '2025-11-26 12:44:47', '2025-11-26 12:44:50', 0.00, NULL, NULL, NULL, NULL),
(28, 9, 31, NULL, 50.00, 'self', 'confirmed', '2025-11-26 12:45:19', '2025-11-26 12:45:49', NULL, '2025-11-26 12:45:19', '2025-11-26 12:45:49', 0.00, 95.00, 0.00, 5.00, NULL),
(29, 10, 31, NULL, 10.00, 'self', 'confirmed', '2025-11-26 12:58:23', '2025-11-26 12:58:30', NULL, '2025-11-26 12:58:23', '2025-11-26 12:58:30', 0.00, 19.00, 0.00, 1.00, NULL),
(30, 11, 31, NULL, 100.00, 'self', 'confirmed', '2025-11-26 13:13:39', '2025-11-26 13:15:08', NULL, '2025-11-26 13:13:39', '2025-11-26 13:15:08', 0.00, 190.00, 0.00, 10.00, NULL),
(31, 12, 34, NULL, 10.00, 'self', 'accepted', '2025-11-27 07:24:40', NULL, NULL, '2025-11-27 07:24:40', '2025-11-27 07:24:40', 0.00, NULL, NULL, NULL, NULL),
(32, 12, 35, NULL, 40.00, 'self', 'confirmed', '2025-12-02 10:38:26', '2025-12-02 10:38:57', NULL, '2025-12-02 10:38:26', '2025-12-02 10:38:57', 0.00, 76.00, 0.00, 4.00, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reward_products`
--

CREATE TABLE `reward_products` (
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID doanh nghi?p (NULL = s?n ph?m c?a admin)',
  `product_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('household','food','personal','eco','other') DEFAULT 'other',
  `required_points` int(10) UNSIGNED NOT NULL,
  `stock_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0,
  `total_redeemed` int(10) UNSIGNED DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `reward_products`
--

INSERT INTO `reward_products` (`product_id`, `business_id`, `product_name`, `description`, `category`, `required_points`, `stock_quantity`, `image_url`, `status`, `is_featured`, `total_redeemed`, `created_at`, `updated_at`) VALUES
(5, NULL, 'Sản phẩm A', '', '', 50, 13, '', 'inactive', 0, 2, '2025-11-19 21:43:18', '2025-11-27 13:27:26'),
(9, NULL, 'Test nha nha khóc', 'lẹ lẹ', '', 50, 9, '', 'inactive', 0, 1, '2025-11-26 19:57:31', '2025-11-27 13:27:22'),
(10, NULL, 'test', '', '', 1, 0, '', 'active', 0, 1, '2025-12-02 17:39:56', '2025-12-02 17:40:01'),
(11, NULL, 'test 2', '11', '', 5, 5, '', 'active', 0, 1, '2025-12-02 17:42:04', '2025-12-02 17:42:15');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reward_redemptions`
--

CREATE TABLE `reward_redemptions` (
  `redemption_id` bigint(20) UNSIGNED NOT NULL,
  `redemption_code` varchar(20) DEFAULT NULL,
  `household_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID doanh nghi?p (NULL = s?n ph?m admin)',
  `points_spent` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED DEFAULT 1,
  `status` enum('pending','confirmed','delivered','cancelled') DEFAULT 'pending',
  `delivery_address` text DEFAULT NULL,
  `delivery_phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `redeemed_at` datetime DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `reward_redemptions`
--

INSERT INTO `reward_redemptions` (`redemption_id`, `redemption_code`, `household_id`, `product_id`, `business_id`, `points_spent`, `quantity`, `status`, `delivery_address`, `delivery_phone`, `notes`, `redeemed_at`, `confirmed_at`, `delivered_at`, `cancelled_at`) VALUES
(6, NULL, 31, 5, NULL, 50, 1, 'delivered', '', '', '', '2025-11-26 19:46:45', NULL, '2025-11-26 20:02:09', NULL),
(7, NULL, 31, 9, NULL, 50, 1, 'delivered', '', '', '', '2025-11-26 19:59:11', NULL, '2025-11-26 20:00:12', NULL),
(8, NULL, 35, 10, NULL, 1, 1, 'delivered', '', '', '', '2025-12-02 17:40:01', NULL, '2025-12-02 17:40:18', NULL),
(9, NULL, 35, 11, NULL, 5, 1, 'delivered', '', '', '', '2025-12-02 17:42:15', NULL, '2025-12-02 17:42:25', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password_hash` char(60) NOT NULL,
  `password_algo` enum('bcrypt','argon2id') NOT NULL DEFAULT 'bcrypt',
  `role` enum('household','business','collector','admin') NOT NULL,
  `status` enum('active','inactive','locked','pending','onboarding','suspended') NOT NULL DEFAULT 'pending',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone_number`, `password_hash`, `password_algo`, `role`, `status`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at`) VALUES
(5, 'Administrator', 'admin@kltn.com', '0999999999', '$2y$10$HAKuuj56SZkAEvhpE6FyCez5M.78YlDHWnDNaAoJikGgs89w5Fsr6', 'bcrypt', 'admin', 'active', '2025-12-02 17:39:28', '::1', '2025-11-04 11:19:31', '2025-12-02 17:39:28'),
(21, 'Dương Tấn Kiệt', 'tankietduong01@gmail.com', '0365415236', '$2y$10$SUj4ggEszRwvBwAIoXZaf.Qt0VaRzGnPdt0pHA.R3eRWToN.0Mrcy', 'bcrypt', 'household', 'active', NULL, NULL, '2025-11-26 15:42:29', '2025-11-26 15:42:29'),
(22, 'Dương Tấn Kiệt', 'tankietduong011@gmail.com', '0352415698', '$2y$10$0cwhd4Rs4VM6Sc1huPJr7eAXLSj3MtduJXRPj0H.jCI3xw/w2D74K', 'bcrypt', 'household', 'active', NULL, NULL, '2025-11-26 15:43:51', '2025-11-26 15:43:51'),
(23, 'Hộ Gia Đình 1', 'tankietduong0111@gmail.com', '0365741258', '$2y$10$MpMkaBGDSChMp5kj0zkXfulhjh7Y4WBRmJfIuXxIONddyGfW4hTde', 'bcrypt', 'household', 'active', NULL, NULL, '2025-11-26 15:52:40', '2025-11-26 15:52:40'),
(24, 'Duong Tan Kiet', 'hickhoc@gmail.com', '0541236987', '$2y$10$Ivf0zvbHHVDlnhNhG9e.Bex0uQw0VmMuV/IVBNnvbSXCOtXOgNCd.', 'bcrypt', 'household', 'active', NULL, NULL, '2025-11-26 17:18:27', '2025-11-26 17:18:27'),
(25, 'test khic', 'hichic@gmail.com', '0365741236', '$2y$10$1331a.I9XIPJA7eKxU10yuW7AwRjjA6YIhO33KTu9W1NFyxMKTM22', 'bcrypt', 'household', 'active', '2025-11-26 17:20:12', '::1', '2025-11-26 17:19:54', '2025-11-26 17:20:12'),
(26, 'Hộ Gia Đình 1', 'metquadi@gmail.com', '0368741236', '$2y$10$wo3PpwTLhLLrJIuBjr5khOxWnMzCJAFzSuUKPHYTKXQVU4FVFegW2', 'bcrypt', 'household', 'active', '2025-11-26 17:32:13', '::1', '2025-11-26 17:32:04', '2025-11-26 17:32:13'),
(27, 'HIC', 'dn1@gmail.com', '0365412365', '$2y$10$dhzEGCC9QdILR8qTVeAjOerBjXPYzbGQFhVoA1h9AquK5fN.78Q.K', 'bcrypt', 'business', 'active', NULL, NULL, '2025-11-26 17:46:00', '2025-11-26 17:46:00'),
(28, 'buc minh roi nha nha', 'hichiccccc@gmail.com', '0365741238', '$2y$10$/oXfMTZVoYEHr0qu8uHXB.YjLOvcKRgHqzEW/VV.9vLlpXb7bLvXK', 'bcrypt', 'household', 'active', NULL, NULL, '2025-11-26 17:46:39', '2025-11-26 17:46:39'),
(29, 'buc minh roi nha nha', 'hichicccccc@gmail.com', '0365741237', '$2y$10$sj95QyIdNYI5nluNsYq3T.4H0iuV5g57AY6UeAsd4RyrrsKuc/CdO', 'bcrypt', 'household', 'active', NULL, NULL, '2025-11-26 17:47:45', '2025-11-26 17:47:45'),
(30, 'Dương Tấn Kiệt hộ gia đình 1', 'gdgdgd@gmail.com', '0365741245', '$2y$10$xcK4f2iM2N2wnxW0wt3Mru67JXMyf6HWhZH9QBqNcf0.7v0NG5jyO', 'bcrypt', 'household', 'active', '2025-11-26 18:19:06', '::1', '2025-11-26 17:48:11', '2025-11-26 18:33:20'),
(31, 'test lạnh', 'kbietnua@gmail.com', '0365215696', '$2y$10$1mCu/ahsftGrv65eBz3rfOiDevSAXCv.smuq0o5Ojr7jdjrZbzTfW', 'bcrypt', 'household', 'active', '2025-11-26 19:27:27', '::1', '2025-11-26 18:36:48', '2025-11-26 19:31:57'),
(32, 'Nhan LE', 'dndndn@gmail.com', '0365412369', '$2y$10$sbQxOuDJfjtHJhbnjMaD4uYpHBq/8QhdRKAEedxwH2.l59R6SQ8XW', 'bcrypt', 'business', 'active', '2025-12-02 17:38:53', '::1', '2025-11-26 19:37:26', '2025-12-02 17:38:53'),
(33, 'CTV nha nha', 'xinchaomn@gmail.com', '0365412578', '$2y$10$SEn.HU.WnPG697Z71FoyweDLXgJmpard4U8/WKWP/E6FgUWhWHR3.', 'bcrypt', 'collector', 'active', '2025-11-26 21:48:31', '::1', '2025-11-26 21:48:20', '2025-11-26 21:48:31'),
(34, 'Hộ gia đình 2', 'hihi@gmail.com', '0345835385', '$2y$10$iAPyf7BXxbZXRCWUYrRrzu0WPir/Oxe3XX.MBz3dYLHk0YptBYt9S', 'bcrypt', 'household', 'active', '2025-11-27 14:24:10', '::1', '2025-11-27 14:24:01', '2025-11-27 14:24:10'),
(35, 'Hộ Gia Đình 1', 'ggdggh@hihi.com', '0365423654', '$2y$10$MopSq7wlm2oNLxmjq3et6.q18I8I7.5oSiinP2I/oBZ88evrR2NUa', 'bcrypt', 'household', 'active', '2025-12-02 17:36:49', '::1', '2025-12-02 14:10:31', '2025-12-02 17:36:49'),
(36, 'buc minh roi nha', 'okoknha@gmail.com', '0321456325', '$2y$10$hObMsd6pEo5.iFXFtYtiyuSvVovvSrpcTcBn9UfjDGexxcrXna5o2', 'bcrypt', 'household', 'active', '2025-12-02 14:32:45', '::1', '2025-12-02 14:24:28', '2025-12-02 14:32:45');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_addresses`
--

CREATE TABLE `user_addresses` (
  `address_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `province_code` varchar(6) NOT NULL,
  `ward_code` varchar(10) NOT NULL,
  `address_line` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  `is_head_office` tinyint(1) NOT NULL DEFAULT 0,
  `contact_phone` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `user_addresses`
--

INSERT INTO `user_addresses` (`address_id`, `user_id`, `province_code`, `ward_code`, `address_line`, `note`, `postal_code`, `is_primary`, `is_head_office`, `contact_phone`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(18, 21, '1', '1', '37 Trần Bá Giao', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 15:42:29', '2025-11-26 15:42:29'),
(19, 22, '1', '1', '37 Trần Bá Giao', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 15:43:51', '2025-11-26 15:43:51'),
(20, 23, '1', '1', '55 TTT', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 15:52:40', '2025-11-26 15:52:40'),
(21, 24, '1', '1', '60 TTT', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 17:18:27', '2025-11-26 17:18:27'),
(22, 25, '1', '1', '50 TTTT', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 17:19:54', '2025-11-26 17:19:54'),
(23, 26, '1', '1', '6646 dfdd', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 17:32:04', '2025-11-26 17:32:04'),
(24, 27, '1', '1', '55 TTT', NULL, NULL, 1, 1, NULL, NULL, NULL, '2025-11-26 17:46:00', '2025-11-26 17:46:00'),
(25, 28, '1', '1', '55 NNN', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 17:46:39', '2025-11-26 17:46:39'),
(26, 29, '1', '1', '55 NNN', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 17:47:45', '2025-11-26 17:47:45'),
(27, 30, '1', '34', '55 vvv', '', NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 17:48:11', '2025-11-26 17:48:39'),
(28, 31, '1', '1', 'hic hic 150', '', NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 18:36:48', '2025-11-26 19:44:35'),
(29, 32, '1', '1', 'hic hic', NULL, NULL, 1, 1, NULL, NULL, NULL, '2025-11-26 19:37:26', '2025-11-26 19:37:26'),
(30, 33, '1', '1', '33 TTT', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-11-26 21:48:20', '2025-11-26 21:48:20'),
(31, 34, '1', '1', '55 Nguyễn Hải Phong', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-11-27 14:24:01', '2025-11-27 14:24:01'),
(32, 35, '1', '1', 'gdgdbdfb', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-12-02 14:10:31', '2025-12-02 14:10:31'),
(33, 36, '1', '1', '43 gfg', NULL, NULL, 1, 0, NULL, NULL, NULL, '2025-12-02 14:24:28', '2025-12-02 14:24:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('household','collector') NOT NULL,
  `household_id` int(11) NOT NULL,
  `points_deducted` decimal(10,2) NOT NULL,
  `amount_requested` decimal(15,2) NOT NULL,
  `bank_account_number` varchar(50) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_holder_name` varchar(100) NOT NULL,
  `status` enum('pending','processing','completed','rejected','cancelled') DEFAULT 'pending',
  `request_code` varchar(20) DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `processing_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `withdrawal_requests`
--

INSERT INTO `withdrawal_requests` (`request_id`, `user_id`, `user_type`, `household_id`, `points_deducted`, `amount_requested`, `bank_account_number`, `bank_name`, `account_holder_name`, `status`, `request_code`, `requested_at`, `processed_at`, `processing_at`, `completed_at`, `rejected_at`, `cancelled_at`, `processed_by`, `admin_notes`) VALUES
(3, 0, 'household', 31, 140.00, 140000.00, '6619205661494', 'Agribank', 'le thanh nhan', 'completed', 'WD17641630448917146', '2025-11-26 13:17:24', NULL, NULL, '2025-11-26 13:18:02', NULL, NULL, 5, 'Đã chuyển khoản thành công'),
(4, 0, 'household', 31, 64.00, 64000.00, '6619205661494', 'OCB', 'Dương Tấn Kiệt', 'pending', 'WD17641634801835032', '2025-11-26 13:24:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `business_orders`
--
ALTER TABLE `business_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_business_status` (`business_id`,`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_waste_type` (`waste_type`);

--
-- Chỉ mục cho bảng `business_profiles`
--
ALTER TABLE `business_profiles`
  ADD PRIMARY KEY (`business_id`),
  ADD UNIQUE KEY `uk_business_tax_code` (`tax_code`),
  ADD KEY `fk_business_profile_user` (`user_id`);

--
-- Chỉ mục cho bảng `collector_point_transactions`
--
ALTER TABLE `collector_point_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_collector` (`collector_id`),
  ADD KEY `idx_participant` (`participant_id`),
  ADD KEY `idx_topup` (`topup_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_collector_created` (`collector_id`,`created_at`),
  ADD KEY `idx_transaction_type` (`transaction_type`);

--
-- Chỉ mục cho bảng `collector_profiles`
--
ALTER TABLE `collector_profiles`
  ADD PRIMARY KEY (`collector_id`),
  ADD UNIQUE KEY `uk_collector_citizen` (`citizen_id`),
  ADD KEY `fk_collector_profile_user` (`user_id`);

--
-- Chỉ mục cho bảng `collector_topup_transactions`
--
ALTER TABLE `collector_topup_transactions`
  ADD PRIMARY KEY (`topup_id`),
  ADD UNIQUE KEY `topup_code` (`topup_code`),
  ADD KEY `idx_collector` (`collector_id`),
  ADD KEY `idx_topup_code` (`topup_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_requested_at` (`requested_at`);

--
-- Chỉ mục cho bảng `credit_transactions`
--
ALTER TABLE `credit_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD UNIQUE KEY `uk_bank_transaction` (`bank_transaction_id`),
  ADD KEY `idx_business_id` (`business_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transaction_code` (`transaction_code`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_business_status` (`business_id`,`status`);

--
-- Chỉ mục cho bảng `household_point_transactions`
--
ALTER TABLE `household_point_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_household` (`household_id`),
  ADD KEY `idx_participant` (`participant_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_household_created` (`household_id`,`created_at`),
  ADD KEY `idx_transaction_type` (`transaction_type`);

--
-- Chỉ mục cho bảng `household_profiles`
--
ALTER TABLE `household_profiles`
  ADD PRIMARY KEY (`household_id`),
  ADD KEY `fk_household_profile_user` (`user_id`);

--
-- Chỉ mục cho bảng `order_participants`
--
ALTER TABLE `order_participants`
  ADD PRIMARY KEY (`participant_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_household` (`household_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_collector` (`collector_id`),
  ADD KEY `idx_delivery_method` (`delivery_method`),
  ADD KEY `idx_household_status` (`household_id`,`status`),
  ADD KEY `idx_collector_status` (`collector_id`,`status`),
  ADD KEY `idx_order_status` (`order_id`,`status`),
  ADD KEY `idx_accepted_at` (`accepted_at`);

--
-- Chỉ mục cho bảng `reward_products`
--
ALTER TABLE `reward_products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`);

--
-- Chỉ mục cho bảng `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  ADD PRIMARY KEY (`redemption_id`),
  ADD UNIQUE KEY `redemption_code` (`redemption_code`),
  ADD KEY `idx_household` (`household_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_redemption_code` (`redemption_code`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uk_user_email` (`email`),
  ADD UNIQUE KEY `uk_user_phone` (`phone_number`);

--
-- Chỉ mục cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `fk_address_user` (`user_id`);

--
-- Chỉ mục cho bảng `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `request_code` (`request_code`),
  ADD KEY `idx_household_status` (`household_id`,`status`),
  ADD KEY `idx_request_code` (`request_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_status_requested` (`status`,`requested_at`),
  ADD KEY `idx_user` (`user_id`,`user_type`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `business_orders`
--
ALTER TABLE `business_orders`
  MODIFY `order_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `business_profiles`
--
ALTER TABLE `business_profiles`
  MODIFY `business_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `collector_point_transactions`
--
ALTER TABLE `collector_point_transactions`
  MODIFY `transaction_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `collector_profiles`
--
ALTER TABLE `collector_profiles`
  MODIFY `collector_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `collector_topup_transactions`
--
ALTER TABLE `collector_topup_transactions`
  MODIFY `topup_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `credit_transactions`
--
ALTER TABLE `credit_transactions`
  MODIFY `transaction_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `household_point_transactions`
--
ALTER TABLE `household_point_transactions`
  MODIFY `transaction_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT cho bảng `household_profiles`
--
ALTER TABLE `household_profiles`
  MODIFY `household_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `order_participants`
--
ALTER TABLE `order_participants`
  MODIFY `participant_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT cho bảng `reward_products`
--
ALTER TABLE `reward_products`
  MODIFY `product_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  MODIFY `redemption_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `address_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT cho bảng `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `business_profiles`
--
ALTER TABLE `business_profiles`
  ADD CONSTRAINT `fk_business_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `collector_profiles`
--
ALTER TABLE `collector_profiles`
  ADD CONSTRAINT `fk_collector_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `collector_topup_transactions`
--
ALTER TABLE `collector_topup_transactions`
  ADD CONSTRAINT `fk_collector_topup_user` FOREIGN KEY (`collector_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `credit_transactions`
--
ALTER TABLE `credit_transactions`
  ADD CONSTRAINT `fk_credit_transaction_business` FOREIGN KEY (`business_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `household_point_transactions`
--
ALTER TABLE `household_point_transactions`
  ADD CONSTRAINT `household_point_transactions_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `household_profiles`
--
ALTER TABLE `household_profiles`
  ADD CONSTRAINT `fk_household_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `order_participants`
--
ALTER TABLE `order_participants`
  ADD CONSTRAINT `order_participants_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `business_orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_participants_ibfk_2` FOREIGN KEY (`household_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reward_products`
--
ALTER TABLE `reward_products`
  ADD CONSTRAINT `reward_products_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  ADD CONSTRAINT `reward_redemptions_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reward_redemptions_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `reward_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reward_redemptions_ibfk_3` FOREIGN KEY (`business_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
