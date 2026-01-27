-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 18, 2026 at 02:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `streamflix`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-categories.list', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:1:{i:0;O:19:\"App\\Models\\Category\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:10:\"categories\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:1;s:4:\"name\";s:6:\"muziki\";s:4:\"slug\";s:14:\"hshshshhshshsh\";s:11:\"description\";s:9:\"ahhahahha\";s:10:\"created_at\";N;s:10:\"updated_at\";N;}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:1;s:4:\"name\";s:6:\"muziki\";s:4:\"slug\";s:14:\"hshshshhshshsh\";s:11:\"description\";s:9:\"ahhahahha\";s:10:\"created_at\";N;s:10:\"updated_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1768745104);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'muziki', 'hshshshhshshsh', 'ahhahahha', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `video_id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment_likes`
--

CREATE TABLE `comment_likes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `comment_id` bigint(20) UNSIGNED NOT NULL,
  `is_like` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_12_31_155823_create_personal_access_tokens_table', 1),
(5, '2025_12_31_160235_create_extended_users_table', 2),
(6, '2025_12_31_165043_create_categories_table', 2),
(7, '2025_12_31_165718_create_videos_table', 2),
(8, '2025_12_31_170000_create_playlists_table', 2),
(9, '2025_12_31_170001_create_playlist_videos_table', 2),
(10, '2025_12_31_170002_create_watch_history_table', 2),
(11, '2025_12_31_180000_add_performance_indexes', 3),
(12, '2026_01_12_095829_create_roles_table', 4),
(13, '2026_01_12_095837_create_role_user_table', 4),
(14, '2025_12_31_190000_create_video_upload_sessions_table', 5),
(15, '2025_12_31_200000_create_video_likes_table', 5),
(16, '2026_01_16_120545_fix_views_count_column', 6),
(17, '2026_01_16_120545_fix_views_count_column2', 7),
(18, '2025_12_31_210000_add_visibility_to_videos', 8),
(19, '2025_12_31_220000_create_comments_table', 9);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 1, 'auth-token', '401868608c1fc31e2c4863862b235fff1222850d012f8630b8a498cac36d46bb', '[\"*\"]', NULL, NULL, '2026-01-02 07:49:44', '2026-01-02 07:49:44'),
(2, 'App\\Models\\User', 2, 'auth-token', '0d2d9d71afe546ac4baffa832b1c422fa69de8ef9b82cabe886d78c284a341cd', '[\"*\"]', NULL, NULL, '2026-01-06 08:19:43', '2026-01-06 08:19:43'),
(3, 'App\\Models\\User', 2, 'auth-token', '0863297561ebd322a5f0a50495cceabf48af6a323f146f716013bb7fc2a50d0d', '[\"*\"]', NULL, NULL, '2026-01-06 08:23:04', '2026-01-06 08:23:04'),
(4, 'App\\Models\\User', 3, 'auth-token', '795fe3144cf7ddc2f459a67da0bc86943f2c8a00d10fc9e621f8dc2edd3ea5f6', '[\"*\"]', NULL, NULL, '2026-01-06 09:15:56', '2026-01-06 09:15:56'),
(5, 'App\\Models\\User', 3, 'auth-token', '641633973dbb36b809db706ead2cb502143f2c1fbfedc74d8cd76c028126d1b6', '[\"*\"]', NULL, NULL, '2026-01-06 09:16:13', '2026-01-06 09:16:13'),
(6, 'App\\Models\\User', 4, 'auth-token', '73324c22089e0cf3d32a8ac24b9892832270ab4ae612612b00464cd509d63490', '[\"*\"]', NULL, NULL, '2026-01-08 06:35:16', '2026-01-08 06:35:16'),
(7, 'App\\Models\\User', 3, 'auth-token', '405d62fc4a719d87da917179ab243f2b6aa639b1a9bb12e449250b9cad6cb546', '[\"*\"]', NULL, NULL, '2026-01-08 06:35:38', '2026-01-08 06:35:38'),
(8, 'App\\Models\\User', 3, 'auth-token', '728daf25421c95d0611aca175d1e91782ddd994859153dd73b7ad1a9c1f3508c', '[\"*\"]', NULL, NULL, '2026-01-08 06:35:45', '2026-01-08 06:35:45'),
(9, 'App\\Models\\User', 3, 'auth-token', '35d72772dbe38e05b517a395352744c9fb442491a8ca8668dc021d780e849b1f', '[\"*\"]', NULL, NULL, '2026-01-08 06:35:52', '2026-01-08 06:35:52'),
(10, 'App\\Models\\User', 3, 'auth-token', 'dd57a10a3fb25cd8674963a6b8377a3240692cbe4d786215235c64741b0097bd', '[\"*\"]', NULL, NULL, '2026-01-08 06:45:13', '2026-01-08 06:45:13'),
(12, 'App\\Models\\User', 2, 'auth-token', '55bd293df18272e9ed7da364700e6547bbe444fdcac366ec873609cac6e612dd', '[\"*\"]', NULL, NULL, '2026-01-08 11:08:17', '2026-01-08 11:08:17'),
(15, 'App\\Models\\User', 2, 'auth-token', 'e892f391fb0ad19ac60763dd8a613dde97bdff40b9ffcfa1f9fee14afa1c865f', '[\"*\"]', NULL, NULL, '2026-01-08 12:04:17', '2026-01-08 12:04:17'),
(16, 'App\\Models\\User', 2, 'auth-token', '7cb06362ad03644d7b3d1d94e560e21ba7e04af65088fb6a4d8aef640e5f5cd2', '[\"*\"]', NULL, NULL, '2026-01-12 06:19:14', '2026-01-12 06:19:14'),
(17, 'App\\Models\\User', 2, 'auth-token', 'b996cbc6a05be4d6ebe27f87be8f139d36783fd6916b3ee69d5f6bc090ed93c0', '[\"*\"]', NULL, NULL, '2026-01-12 06:23:24', '2026-01-12 06:23:24'),
(18, 'App\\Models\\User', 2, 'auth-token', 'aa9f335bf664872924b41e620f18b898380a104dea6e92d064043d0e1a2b3708', '[\"*\"]', NULL, NULL, '2026-01-12 06:50:00', '2026-01-12 06:50:00'),
(20, 'App\\Models\\User', 2, 'auth-token', 'c1a355bc602c0202f314052e7e1a900ed1a7fd95b0b464c5e0f2d926a6ecb8e1', '[\"*\"]', NULL, NULL, '2026-01-15 06:54:29', '2026-01-15 06:54:29'),
(21, 'App\\Models\\User', 3, 'auth-token', 'ae86f8e7c28364a24cd6a4a2b7ee173939b25a2ca4f2e81b2bd84f0892de8165', '[\"*\"]', '2026-01-15 10:50:51', NULL, '2026-01-15 10:22:04', '2026-01-15 10:50:51'),
(22, 'App\\Models\\User', 3, 'auth-token', '63dcaf2b2ece8349afb9a8cbd1f2c10d05beed8320d4d784a6d7b13bdfee012a', '[\"*\"]', '2026-01-16 08:26:01', NULL, '2026-01-16 05:41:58', '2026-01-16 08:26:01'),
(25, 'App\\Models\\User', 7, 'auth-token', '072c387a96d155df3efea9d32f835053a9361b3dbbf896212637a4b3801c7c8d', '[\"*\"]', '2026-01-18 10:05:04', NULL, '2026-01-18 10:00:20', '2026-01-18 10:05:04');

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `playlists`
--

INSERT INTO `playlists` (`id`, `user_id`, `name`, `description`, `thumbnail_path`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 3, 'gossip', 'gossip', NULL, 1, '2026-01-15 10:30:21', '2026-01-15 10:30:21'),
(2, 3, 'gossip', 'gossip', NULL, 1, '2026-01-15 10:30:22', '2026-01-15 10:30:22'),
(3, 3, 'gossip', 'gossip', NULL, 1, '2026-01-15 10:30:22', '2026-01-15 10:30:22'),
(4, 3, 'gossip', 'gossip', NULL, 1, '2026-01-15 10:30:23', '2026-01-15 10:30:23'),
(5, 3, 'gossip', 'gossip', NULL, 1, '2026-01-15 10:30:23', '2026-01-15 10:30:23'),
(6, 3, 'gossip', 'gossip', NULL, 1, '2026-01-15 10:30:23', '2026-01-15 10:30:23'),
(7, 3, 'gossip', 'gossip', NULL, 1, '2026-01-15 10:30:30', '2026-01-15 10:30:30');

-- --------------------------------------------------------

--
-- Table structure for table `playlist_videos`
--

CREATE TABLE `playlist_videos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `playlist_id` bigint(20) UNSIGNED NOT NULL,
  `video_id` bigint(20) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_user`
--

CREATE TABLE `role_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('0lWWx6qTgaWrR97gNHL5HPV2Xmu8MAvwrHLWNbPH', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibFFTRmNmdGh1cTVvN05TMnZOV2JCaEJIMXVRUzg3Qkd4UFJSVnphVCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1767700236),
('8QdtVI7RtN0EIiwlzVZi0mY5liZJT49QpFJEB9E7', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaERtVVJ0cmYwMDJ4eVBKQWp4c0hBQ2JhcnJNTFQ1MUxwZ05Tc3JJTSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1767884633),
('BmcJvymebcNn9QSwCm8uQbcjHTDWRudtEDLE8Ud2', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidm5WNEwwemtOTXNBSFlLR0dSYm9CdnFaZTdNVWhvb25tT0RlN01TRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1768482850),
('bX2tLnDoe690ELpkJlHh4Bju73dLX8biqj51aPLb', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQWxnZ3lyWkpNVTlDZWFxOEJhempoOWJIVjFsbUdTNjU0NEtKOEVpSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1767864769),
('DMXHXuUQTYPEISmWgAyzEXmQzyeEDPO4RG8aFiyA', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNUxldFVLaUtCc2laWWZJWlFmb0dSZmdSd3hORE9NQlhYbEVhY3NvOSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1768053092),
('j5sQKQ1H8r7UsMfsWwjytQb4VLjyuiRDENTNGZyv', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidWdwaHR3NG95S2J1N2xmdVNHQm9lRWZ5MlEyYnVPY3cybXlOVmNYQSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1767257257),
('JdLugp6AUoBQ05Wl2jGy6lfV9s1fpdkXjmCXWd19', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:147.0) Gecko/20100101 Firefox/147.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOFVObTZiNXpEZnlXREVVZEpYdXBZVEY1U210YjNEMjU3ZUJaYzRWMSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1768739547),
('OG1n83mc5zLugjQgSCeVACYppbnHzgwW6rA3kL9K', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaDVuWmpvczNDc3N2bmVvWlZ0Zk1kTUhxQ3huZWp5NmttTUlEelIxZyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1767384434),
('ukgVobdcB2idK2O4bxXRAea3ueKep78uh6hxQsyP', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUk9oVWF5Y1BORm9yWnBjaGFGOU9CaEEwZG95Rjd6S0xWWTlaVVkwbyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1767349280);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('user','admin','creator') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `avatar`, `role`, `is_active`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Prosper Test', 'prosper@test.com', NULL, 'user', 1, NULL, '$2y$12$SEuUg5w/jAxpwZFTxb14b.9vgX.BIHFmeA67iNVhlHQbbVWwTRBp.', NULL, '2026-01-02 07:49:44', '2026-01-02 07:49:44'),
(2, 'Test User', 'testuser@example.com', NULL, 'user', 1, NULL, '$2y$12$3g3sxxp2IGbAg0PH1HochuNMku/IRXahxcD/MOA6DQgxZd2b1Zcpq', NULL, '2026-01-06 08:19:43', '2026-01-06 08:19:43'),
(3, 'mwasile', 'mwasile@gmail.com', NULL, 'user', 1, NULL, '$2y$12$T1AvkCEoCTmXQUnA1NZNEeykQifz3HqB2NudSn4/DhedLdFOZecx6', NULL, '2026-01-06 09:15:56', '2026-01-06 09:15:56'),
(4, 'mwalupani', 'popermwasile173@gmail.com', NULL, 'user', 1, NULL, '$2y$12$NMCgCWRpJi5.528EzSqZ9uxVKMytdpVnaaGi87kKy4oCUxy38Q2bW', NULL, '2026-01-08 06:35:16', '2026-01-08 06:35:16'),
(5, 'mwamposa', 'sai@gmail.com', NULL, 'user', 1, NULL, '$2y$12$iBWivtt4l5O2NoZMKTbzLeYUSobqKREwfUqg0ob8DgLZNRqyuMqmm', NULL, '2026-01-08 06:45:42', '2026-01-08 06:45:42'),
(6, 'mwasile', 'mkuu@gmail.com', NULL, 'user', 1, NULL, '$2y$12$gexo3jcv45EFm6ktsOUy/.yBk89lX21aZHn0zSGyj2DEtUPhosfHy', NULL, '2026-01-15 06:44:46', '2026-01-15 06:44:46'),
(7, 'mwamposa', 'mwamposa@gmail.com', NULL, 'user', 1, NULL, '$2y$12$f.iC8Bsy97./jFajhjX96O/5lBu8oxHsebfV08EU1BawS4o1sOCPa', NULL, '2026-01-18 08:47:24', '2026-01-18 08:47:24');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `duration` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `views_count` bigint(20) DEFAULT 0,
  `status` enum('processing','active','inactive') NOT NULL DEFAULT 'processing',
  `visibility` enum('public','private','unlisted') NOT NULL DEFAULT 'public',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `title`, `description`, `user_id`, `category_id`, `file_path`, `thumbnail_path`, `file_size`, `duration`, `views_count`, `status`, `visibility`, `created_at`, `updated_at`) VALUES
(7, 'mwamposa', 'gggg', 3, 1, 'videos/ad8f5431-0ff5-4600-9e89-8489b786ecce.avi', 'thumbnails/03b67845-32be-44a4-8215-c21b7d9d53b1.jpg', 75212924, 0, 2, 'active', 'public', '2026-01-08 11:51:10', '2026-01-16 09:53:45'),
(8, 'mwamposa', 'gggg', 3, 1, 'videos/21b8a6ff-ebe0-4c07-aab4-09de6ea1cdc3.avi', 'thumbnails/db61c0af-0c73-49d2-b0d4-e5df82aae98c.jpg', 75212924, 0, 3, 'active', 'public', '2026-01-08 11:51:23', '2026-01-18 09:33:36'),
(9, 'mwamposa', 'we code', 3, 1, 'videos/71ab0173-5aa0-45e2-9b77-e82202642e7a.avi', NULL, 178849518, 0, 4, 'active', 'public', '2026-01-08 12:06:04', '2026-01-16 08:49:03'),
(10, 'mwamposa', 'hhshhshs', 3, 1, 'videos/8c329958-ce05-43c7-abd6-51751934e556.avi', NULL, 91485770, 0, 11, 'active', 'public', '2026-01-08 12:20:51', '2026-01-16 08:48:36'),
(11, 'worship', 'wwww', 3, 1, 'videos/033522b2-db82-425a-bfd2-0fd77bf82da4.avi', 'thumbnails/818d3bdc-e70f-4c01-bd54-e52f37937e4f.jpg', 75212924, 0, 4, 'active', 'public', '2026-01-08 13:22:01', '2026-01-17 12:21:59'),
(12, 'video', 'muziki mnene', 6, 1, 'videos/251f4641-da7e-4b1d-8fa0-568a33b4a17a.avi', 'thumbnails/05736728-6273-4e6b-961d-183b5aff4f40.jpg', 75212924, 0, 3, 'active', 'public', '2026-01-15 06:46:36', '2026-01-16 05:46:24'),
(13, 'maisha', 'plus', 6, 1, 'videos/9bd8a9f5-14c1-46ec-9add-90b0e8528a92.avi', 'thumbnails/31c5e3af-a9d7-42b4-bb49-6f3ac0400a68.jpg', 75212924, 0, 6, 'active', 'public', '2026-01-15 09:55:55', '2026-01-17 12:21:33'),
(14, 'video', 'vvvvvvvvvvv', 6, 1, 'videos/9ad1bdc4-550a-49a4-b489-babf74607eff.avi', 'thumbnails/56ed1468-bf4f-4482-86e6-1626b07eb55b.jpg', 75212924, 0, 27, 'active', 'public', '2026-01-15 10:15:35', '2026-01-18 09:51:48'),
(15, '12eba7fe-24d8-4d18-96b9-5f61a6853a82', 'maisha', 3, 1, 'videos/12eba7fe-24d8-4d18-96b9-5f61a6853a82.avi', NULL, 75212924, 0, 28, 'active', 'public', '2026-01-17 12:22:44', '2026-01-18 09:59:04'),
(16, 'ddd', 'ddd', 7, 1, 'videos/af42f460-3a3c-42a9-b081-27842f89b614.avi', 'thumbnails/9610e9e9-cc43-4636-87ea-8b1c4fa7f79d.png', 91485770, 0, 0, 'active', 'public', '2026-01-18 10:00:59', '2026-01-18 10:00:59'),
(17, 'cccc', 'eeee', 7, 1, 'videos/c8361cb9-370c-4bee-93ce-aef7c41bdac6.mp4', 'thumbnails/24bd3efc-92d8-4cba-8b63-4343334d4b9b.png', 253602768, 0, 0, 'active', 'public', '2026-01-18 10:04:40', '2026-01-18 10:04:40');

-- --------------------------------------------------------

--
-- Table structure for table `video_likes`
--

CREATE TABLE `video_likes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `video_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `video_upload_sessions`
--

CREATE TABLE `video_upload_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` varchar(64) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `chunk_size` int(10) UNSIGNED NOT NULL DEFAULT 10485760,
  `total_chunks` smallint(5) UNSIGNED NOT NULL,
  `uploaded_chunks` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_chunk_indices` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`uploaded_chunk_indices`)),
  `status` enum('pending','uploading','assembling','completed','failed','expired') NOT NULL DEFAULT 'pending',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `watch_history`
--

CREATE TABLE `watch_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `video_id` bigint(20) UNSIGNED NOT NULL,
  `progress` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `duration` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_slug_index` (`slug`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comments_video_id_created_at_index` (`video_id`,`created_at`),
  ADD KEY `comments_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `comments_parent_id_index` (`parent_id`);

--
-- Indexes for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `comment_likes_user_id_comment_id_unique` (`user_id`,`comment_id`),
  ADD KEY `comment_likes_comment_id_index` (`comment_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `playlists_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `playlists_is_public_created_at_index` (`is_public`,`created_at`);

--
-- Indexes for table `playlist_videos`
--
ALTER TABLE `playlist_videos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `playlist_videos_playlist_id_video_id_unique` (`playlist_id`,`video_id`),
  ADD KEY `playlist_videos_video_id_foreign` (`video_id`),
  ADD KEY `playlist_videos_playlist_id_position_index` (`playlist_id`,`position`),
  ADD KEY `playlist_videos_playlist_id_video_id_index` (`playlist_id`,`video_id`),
  ADD KEY `playlist_videos_playlist_id_video_id_position_index` (`playlist_id`,`video_id`,`position`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_user_role_id_foreign` (`role_id`),
  ADD KEY `role_user_user_id_foreign` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `videos_category_id_status_index` (`category_id`,`status`),
  ADD KEY `videos_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `videos_views_count_created_at_index` (`views_count`,`created_at`),
  ADD KEY `videos_title_index` (`title`);
ALTER TABLE `videos` ADD FULLTEXT KEY `videos_title_description_fulltext` (`title`,`description`);

--
-- Indexes for table `video_likes`
--
ALTER TABLE `video_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_video_like` (`user_id`,`video_id`),
  ADD KEY `video_likes_video_id_index` (`video_id`),
  ADD KEY `video_likes_user_id_index` (`user_id`);

--
-- Indexes for table `video_upload_sessions`
--
ALTER TABLE `video_upload_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `video_upload_sessions_session_id_unique` (`session_id`),
  ADD KEY `video_upload_sessions_user_id_status_index` (`user_id`,`status`),
  ADD KEY `video_upload_sessions_session_id_index` (`session_id`),
  ADD KEY `video_upload_sessions_expires_at_index` (`expires_at`);

--
-- Indexes for table `watch_history`
--
ALTER TABLE `watch_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `watch_history_user_id_video_id_unique` (`user_id`,`video_id`),
  ADD KEY `watch_history_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `watch_history_user_id_completed_index` (`user_id`,`completed`),
  ADD KEY `watch_history_user_id_completed_created_at_index` (`user_id`,`completed`,`created_at`),
  ADD KEY `watch_history_video_id_user_id_index` (`video_id`,`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `playlist_videos`
--
ALTER TABLE `playlist_videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_user`
--
ALTER TABLE `role_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `video_likes`
--
ALTER TABLE `video_likes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `video_upload_sessions`
--
ALTER TABLE `video_upload_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `watch_history`
--
ALTER TABLE `watch_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_video_id_foreign` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_comment_id_foreign` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `playlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `playlist_videos`
--
ALTER TABLE `playlist_videos`
  ADD CONSTRAINT `playlist_videos_playlist_id_foreign` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `playlist_videos_video_id_foreign` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `videos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_likes`
--
ALTER TABLE `video_likes`
  ADD CONSTRAINT `video_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_likes_video_id_foreign` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_upload_sessions`
--
ALTER TABLE `video_upload_sessions`
  ADD CONSTRAINT `video_upload_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `watch_history`
--
ALTER TABLE `watch_history`
  ADD CONSTRAINT `watch_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watch_history_video_id_foreign` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
