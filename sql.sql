-- 1) 2025_09_29_170000_create_pets_tables

CREATE TABLE `pets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `service_type` varchar(255) NOT NULL DEFAULT 'передержка',
  `description` text NULL,
  `pluses` json NULL,
  `minuses` json NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pet_photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pet_id` bigint unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `order` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `pet_photos_pet_id_foreign` (`pet_id`),
  CONSTRAINT `pet_photos_pet_id_foreign`
    FOREIGN KEY (`pet_id`) REFERENCES `pets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) 2025_09_29_180000_update_pets_add_owner_and_services

ALTER TABLE `pets`
  ADD `owner_name` varchar(255) NULL AFTER `name`,
  ADD `owner_phone` varchar(255) NULL AFTER `owner_name`,
  ADD `animal_type` varchar(255) NULL AFTER `owner_phone`,
  ADD `services` json NULL AFTER `animal_type`;
