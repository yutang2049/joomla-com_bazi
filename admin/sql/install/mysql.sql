--
-- Table structure for table `#__bazi_charts`
--

CREATE TABLE IF NOT EXISTS `#__bazi_charts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'FK to #__users.id, 0 for guest calculations',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `birth_date` date NOT NULL COMMENT 'Solar birth date',
  `birth_time` time NOT NULL COMMENT 'Birth time (original input)',
  `gender` enum('male','female') NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL COMMENT 'Longitude for true solar time correction',
  `latitude` decimal(8,6) DEFAULT NULL COMMENT 'Latitude (informational)',
  `timezone_offset` decimal(5,2) DEFAULT NULL COMMENT 'Timezone offset in hours',
  `normalized_datetime` datetime NOT NULL COMMENT 'Birth datetime after true solar time correction',
  `options_json` text COMMENT 'JSON snapshot of calculation options used',
  `bazi_json` mediumtext NOT NULL COMMENT 'Complete BaZi calculation result in JSON format',
  `algo_version` varchar(20) NOT NULL DEFAULT '1.0.0' COMMENT 'Algorithm version used for calculation',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created` (`created`),
  KEY `idx_birth_date` (`birth_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `#__bazi_readings`
--

CREATE TABLE IF NOT EXISTS `#__bazi_readings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `chart_id` int unsigned NOT NULL COMMENT 'FK to #__bazi_charts.id',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `created_by` int unsigned NOT NULL DEFAULT 0 COMMENT 'FK to #__users.id',
  `reading_markdown` mediumtext NOT NULL COMMENT 'GPT-generated reading in Markdown',
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `prompt_template` varchar(100) DEFAULT NULL COMMENT 'Template name used for generation',
  PRIMARY KEY (`id`),
  KEY `idx_chart_id` (`chart_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created`),
  CONSTRAINT `fk_reading_chart` FOREIGN KEY (`chart_id`) REFERENCES `#__bazi_charts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `#__bazi_gpt_logs`
--

CREATE TABLE IF NOT EXISTS `#__bazi_gpt_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reading_id` int unsigned DEFAULT NULL COMMENT 'FK to #__bazi_readings.id (nullable)',
  `created` datetime NOT NULL,
  `prompt` mediumtext NOT NULL COMMENT 'Full prompt sent to GPT',
  `response` mediumtext COMMENT 'Full response from GPT',
  `model` varchar(50) NOT NULL COMMENT 'Model used',
  `tokens_prompt` int unsigned DEFAULT NULL,
  `tokens_completion` int unsigned DEFAULT NULL,
  `tokens_total` int unsigned DEFAULT NULL,
  `status` enum('pending','success','error') NOT NULL DEFAULT 'pending',
  `error_message` text COMMENT 'Error message if status is error',
  `duration_ms` int unsigned DEFAULT NULL COMMENT 'API call duration in milliseconds',
  PRIMARY KEY (`id`),
  KEY `idx_reading_id` (`reading_id`),
  KEY `idx_created` (`created`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_gptlog_reading` FOREIGN KEY (`reading_id`) REFERENCES `#__bazi_readings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `#__bazi_rate_limits`
--

CREATE TABLE IF NOT EXISTS `#__bazi_rate_limits` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(100) NOT NULL COMMENT 'IP address or user ID',
  `identifier_type` enum('ip','user') NOT NULL,
  `window_start` datetime NOT NULL,
  `request_count` int unsigned NOT NULL DEFAULT 1,
  `last_request` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_identifier_window` (`identifier`, `identifier_type`, `window_start`),
  KEY `idx_window_start` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
