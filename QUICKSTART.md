# BaZi Component - Visual Quick Start

## Component Overview

```
com_bazi
├── Frontend (Site)
│   ├── Calculate View (/index.php?option=com_bazi&view=calculate)
│   │   ├── Input Form
│   │   │   ├── Name (optional)
│   │   │   ├── Birth Date (required) *
│   │   │   ├── Birth Time (required) *
│   │   │   ├── Gender (required) *
│   │   │   ├── Location (optional)
│   │   │   ├── Longitude/Latitude (for true solar time)
│   │   │   └── Save Chart checkbox (logged-in only)
│   │   └── Results Display
│   │       ├── Input Summary
│   │       ├── True Solar Time Correction Info
│   │       ├── Four Pillars (四柱八字)
│   │       │   └── Year | Month | Day | Hour
│   │       │       Stem  |  Stem |  Stem | Stem
│   │       │       Branch| Branch|Branch|Branch
│   │       ├── Dayun (大运) - Major Luck Cycles
│   │       │   ├── Direction (forward/backward)
│   │       │   ├── Start Age
│   │       │   └── 8 cycles × 10 years each
│   │       ├── Liunian (流年) - Annual Fortunes
│   │       │   └── Years ± N (configurable)
│   │       └── Shensha (神煞) - Spiritual Influences
│   │           ├── 天乙贵人 (Tianyi Nobleman)
│   │           ├── 太极贵人 (Taiji Nobleman)
│   │           ├── 文昌贵人 (Wenchang Nobleman)
│   │           ├── 桃花 (Peach Blossom)
│   │           └── 驿马 (Post Horse)
│   └── Anti-spam Protection (transparent)
│       ├── CSRF Token Validation
│       ├── Honeypot Field (hidden)
│       ├── Rate Limiting (IP/User)
│       └── Optional CAPTCHA
│
└── Backend (Administrator)
    ├── Charts List (/administrator?option=com_bazi&view=charts)
    │   └── Table View
    │       ├── ID
    │       ├── Name
    │       ├── Birth Date
    │       ├── Birth Time
    │       ├── Gender
    │       ├── User
    │       └── Created Date
    ├── Readings List (skeleton for future)
    └── Component Configuration
        ├── Algorithm Settings
        │   ├── Year Range (min/max)
        │   ├── Liunian Range
        │   └── True Solar Time Options
        ├── Anti-spam Settings
        │   ├── Honeypot Configuration
        │   ├── Rate Limits
        │   └── CAPTCHA Toggle
        └── GPT Settings (placeholder)
```

## Calculation Flow

```
User Input → Controller → Calculation Engine → Result

1. User submits form
   ↓
2. Anti-spam checks
   ├─ CSRF token valid?
   ├─ Honeypot empty?
   └─ Rate limit OK?
   ↓
3. Input validation
   ├─ Required fields present?
   ├─ Date/time valid?
   └─ Year in supported range?
   ↓
4. True Solar Time correction (if enabled)
   ├─ Longitude correction
   └─ Equation of Time correction
   ↓
5. BaZi Calculation
   ├─ Year Pillar (立春 boundary)
   ├─ Month Pillar (节气定月)
   ├─ Day Pillar (Julian Day)
   ├─ Hour Pillar (double-hour system)
   ├─ Dayun (大运 with 顺逆 rules)
   ├─ Liunian (流年)
   └─ Shensha (神煞)
   ↓
6. Save to DB (if logged in + save checked)
   ├─ Check ACL permission
   └─ Insert to #__bazi_charts
   ↓
7. Display results to user
```

## Database Schema

```sql
#__bazi_charts
├─ id (PK)
├─ user_id (FK to users, 0 for guests)
├─ birth_date, birth_time
├─ gender, name
├─ location_name, longitude, latitude
├─ normalized_datetime (after true solar time)
├─ options_json (calculation settings)
├─ bazi_json (complete result cache)
├─ algo_version
└─ created, modified

#__bazi_readings (future use)
├─ id (PK)
├─ chart_id (FK to charts)
├─ reading_markdown
├─ status (draft/published)
└─ created, modified

#__bazi_gpt_logs (future use)
├─ id (PK)
├─ reading_id (FK to readings)
├─ prompt, response
├─ model, tokens
└─ status

#__bazi_rate_limits
├─ id (PK)
├─ identifier (IP or user_id)
├─ identifier_type (ip/user)
├─ window_start
├─ request_count
└─ last_request
```

## ACL Permissions

```
com_bazi component
├─ core.admin (Administer)
├─ core.manage (Manage)
├─ bazi.chart.create (Create charts)
├─ bazi.chart.view.own (View own charts)
├─ bazi.chart.view.all (View all charts)
├─ bazi.reading.view (View readings)
├─ bazi.reading.generate (Generate readings)
└─ bazi.settings.edit (Edit settings)
```

## Key Features

### For Guests
✓ Calculate BaZi charts (not saved)
✓ View complete results
✓ Subject to rate limiting (default: 5 per hour)
✗ Cannot save charts

### For Logged-in Users
✓ Calculate BaZi charts
✓ Save charts to database (with permission)
✓ Higher rate limits (default: 20 per hour)
✓ View saved charts (own or all, based on permission)

### For Administrators
✓ Configure all settings
✓ View all saved charts
✓ Manage permissions
✓ Access readings/logs views (skeleton)

## Configuration Examples

### Algorithm Settings
- **Minimum Year**: 1900 (calendar data dependent)
- **Maximum Year**: 2050 (calendar data dependent)
- **Liunian Range**: ±5 years (shows 2019-2029 if current year is 2024)
- **True Solar Time**: Enabled (recommended)
- **Longitude Correction**: Enabled
- **EoT Correction**: Enabled

### Anti-spam Settings
- **Honeypot**: Enabled, field name "website"
- **Rate Limit Window**: 3600 seconds (1 hour)
- **Guest Max**: 5 calculations per window
- **User Max**: 20 calculations per window
- **CAPTCHA**: Disabled (can be enabled)

## Example Results

```
Birth: 1990-05-15 10:30:00 (Male)

四柱八字 (Four Pillars):
┌──────┬──────┬──────┬──────┐
│ Year │Month │ Day  │ Hour │
├──────┼──────┼──────┼──────┤
│  庚  │  辛  │  辛  │  癸  │
│  午  │  午  │  丑  │  巳  │
└──────┴──────┴──────┴──────┘

大运 (Dayun): Forward (顺)
Start Age: 7 years 0 months
├─ 7-16:   壬未
├─ 17-26:  癸申
├─ 27-36:  甲酉
└─ ...

流年 (Liunian):
├─ 2023: 癸卯
├─ 2024: 甲辰
├─ 2025: 乙巳
└─ ...

神煞 (Shensha):
├─ 天乙贵人: 寅, 午
├─ 太极贵人: 寅, 亥
├─ 文昌贵人: 亥
├─ 桃花: 卯
└─ 驿马: 申
```

## Installation Steps

1. **Download/Create Package**
   ```bash
   ./package.sh
   # Creates: com_bazi_v1.0.0.zip
   ```

2. **Install in Joomla**
   - Go to: Administrator → System → Install → Extensions
   - Upload: com_bazi_v1.0.0.zip
   - Click: Install

3. **Configure Component** (optional)
   - Go to: Components → BaZi → Options
   - Adjust settings as needed

4. **Create Menu Item**
   - Go to: Menus → [Your Menu]
   - New → Components → BaZi → Calculate
   - Save & Close

5. **Test**
   - Visit frontend menu item
   - Enter birth information
   - Click Calculate

## Troubleshooting

### "Rate limit exceeded"
- Wait for rate limit window to expire (default 1 hour)
- Or increase limits in component configuration
- Or login (users have higher limits)

### "Year out of range"
- Check component configuration (year_min, year_max)
- Ensure calendar data covers the birth year
- Current sample data: 2024-2026
- Production should include complete 1900-2050 data

### "Invalid security token"
- Refresh the page to get new token
- Ensure browser allows cookies

### Charts not saving
- Check user is logged in
- Verify "Save Chart" checkbox is checked
- Confirm user has `bazi.chart.create` permission

## Future Enhancements (Beyond MVP)

- [ ] Complete calendar data for 1900-2050
- [ ] GPT reading generation
- [ ] Lunar date input support
- [ ] Chart comparison tools
- [ ] More Shensha types
- [ ] Chart editing capability
- [ ] Export to PDF/image
- [ ] Mobile app integration
- [ ] Multi-language support (beyond English)
