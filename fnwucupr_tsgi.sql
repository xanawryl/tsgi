-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 06 Nov 2025 pada 11.27
-- Versi server: 8.0.43-cll-lve
-- Versi PHP: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fnwucupr_tsgi`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `board_members`
--

CREATE TABLE `board_members` (
  `id` int NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `position` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `business_items`
--

CREATE TABLE `business_items` (
  `id` int NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `thumbnail_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `gallery_albums`
--

CREATE TABLE `gallery_albums` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `cover_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Path ke gambar cover album',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `gallery_images`
--

CREATE TABLE `gallery_images` (
  `id` int NOT NULL,
  `album_id` int NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `caption` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(255) NOT NULL,
  `slug_kategori` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `parent_id` int NOT NULL DEFAULT '0' COMMENT 'ID dari menu induk'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `news`
--

CREATE TABLE `news` (
  `id` int NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `kategori_id` int DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_kategori` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `news_tag`
--

CREATE TABLE `news_tag` (
  `id_news` int NOT NULL,
  `id_tag` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `partners`
--

CREATE TABLE `partners` (
  `id` int NOT NULL,
  `partner_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `website_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tag`
--

CREATE TABLE `tag` (
  `id_tag` int NOT NULL,
  `nama_tag` varchar(100) NOT NULL,
  `slug_tag` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `board_members`
--
ALTER TABLE `board_members`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `business_items`
--
ALTER TABLE `business_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indeks untuk tabel `gallery_albums`
--
ALTER TABLE `gallery_albums`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indeks untuk tabel `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `album_id` (`album_id`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `slug_kategori` (`slug_kategori`);

--
-- Indeks untuk tabel `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_news_kategori` (`id_kategori`),
  ADD KEY `fk_kategori_id` (`kategori_id`);

--
-- Indeks untuk tabel `news_tag`
--
ALTER TABLE `news_tag`
  ADD PRIMARY KEY (`id_news`,`id_tag`),
  ADD KEY `fk_newstag_tag` (`id_tag`);

--
-- Indeks untuk tabel `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indeks untuk tabel `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`id_tag`),
  ADD UNIQUE KEY `slug_tag` (`slug_tag`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `board_members`
--
ALTER TABLE `board_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `business_items`
--
ALTER TABLE `business_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `gallery_albums`
--
ALTER TABLE `gallery_albums`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `news`
--
ALTER TABLE `news`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tag`
--
ALTER TABLE `tag`
  MODIFY `id_tag` int NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD CONSTRAINT `gallery_images_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `gallery_albums` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `fk_news_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `news_tag`
--
ALTER TABLE `news_tag`
  ADD CONSTRAINT `fk_newstag_news` FOREIGN KEY (`id_news`) REFERENCES `news` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_newstag_tag` FOREIGN KEY (`id_tag`) REFERENCES `tag` (`id_tag`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
