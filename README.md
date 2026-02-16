# BaZi Component (com_bazi) for Joomla 6

## Overview

`com_bazi` is a Joomla 6 component for calculating and managing 八字排盘 (BaZi / Chinese Four Pillars) charts. It provides a complete MVP implementation with calculation engine, anti-spam protections, and administrative interface.

## Features

### Frontend (Site)
- **Calculation Page**: Both guests and logged-in users can calculate BaZi charts
- **Guest Calculations**: Calculations are performed but NOT saved to database
- **Logged-in User Saves**: Authenticated users can save their calculations
- **Results Display**:
  - 基本盘 (四柱八字) - Four Pillars (Year/Month/Day/Hour stems and branches)
  - 大运 (Major Luck Cycles) - 10-year cycles with forward/backward calculation
  - 流年 (Annual Fortunes) - Yearly predictions (configurable range)
  - 神煞 (Spiritual Influences) - Including 天乙贵人, 太极贵人, 文昌贵人, 桃花, 驿马
- **True Solar Time Support**:
  - Enabled by default
  - Longitude correction
  - Equation of Time (EoT) correction
  - Both configurable via component parameters

### Anti-bot / Anti-spam Protections
- **CSRF Token Validation**: All calculation POST requests require valid token
- **Honeypot Field**: Configurable hidden field (default: `website`) to catch bots
- **Rate Limiting**:
  - Guests: Limited by IP address (configurable window and max count)
  - Logged-in users: Limited by user ID with higher thresholds
  - Returns HTTP 429 when limit exceeded
- **Optional CAPTCHA**: Integration with Joomla's CAPTCHA system for guests

### Backend (Administrator)
- **Charts List View**: Browse all saved BaZi charts
- **Readings List View**: Skeleton for GPT-generated readings (future milestone)
- **Component Configuration**: Full parameter control via Joomla's config system
  - Algorithm settings (year ranges, liunian range, true solar time options)
  - Anti-spam settings (honeypot, rate limits, CAPTCHA)
  - GPT API settings (placeholders for future milestone)

### Access Control (ACL)
Comprehensive permission system via `access.xml`:
- `core.admin` - Administer component
- `core.manage` - Manage component
- `bazi.chart.create` - Create and save charts
- `bazi.chart.view.own` - View own charts
- `bazi.chart.view.all` - View all charts
- `bazi.reading.view` - View readings
- `bazi.reading.generate` - Generate new readings
- `bazi.settings.edit` - Edit component settings

### Database
Four main tables:
- `#__bazi_charts` - Stores user input, normalized times, calculation results (JSON cache), algorithm version
- `#__bazi_readings` - Stores GPT-generated readings (draft/published status)
- `#__bazi_gpt_logs` - Audit log for GPT API calls (prompt, response, tokens, status)
- `#__bazi_rate_limits` - Rate limiting tracking by IP/user

### Calendar Data (1900-2050)
- Packaged solar terms data for accurate calculations
- Includes 24 solar terms timestamps for determining:
  - Year pillar boundaries (立春 Lichun)
  - Month pillars (节气定月)
  - Dayun starting points
- Sample data for 2024-2026 included; production system should include complete 1900-2050 range
- See `site/src/Data/README.md` for data sources and licensing

### Calculation Engine
- **Year Pillar**: Uses 立春 (Lichun) boundary
- **Month Pillar**: Uses 节气定月 (solar term based months)
- **Day Pillar**: Uses Julian Day Number for accuracy
- **Hour Pillar**: Chinese double-hour system (地支)
- **Dayun Rules**:
  - 顺逆 (Direction): 阳男阴女顺; 阴男阳女逆
  - 起运 (Starting point): Forward takes next Jie Qi; backward takes previous Jie Qi
  - Age calculation: 3 days = 1 year with months/days breakdown
- **Liunian**: Yearly Ganzhi calculation for configurable range
- **Shensha**: Framework with initial set of spiritual influences

## Installation

### Requirements
- Joomla 6.x
- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+

### Steps
1. Download or clone this repository
2. Create a ZIP file of the component:
   ```bash
   zip -r com_bazi.zip com_bazi.xml admin/ site/ media/
   ```
3. In Joomla Administrator:
   - Go to System → Install → Extensions
   - Upload the ZIP file
   - Click "Install"

4. Configure the component:
   - Go to Components → BaZi → Options
   - Set your preferred algorithm parameters
   - Configure anti-spam settings
   - (Optional) Configure GPT API settings for future reading generation

## Usage

### Frontend Usage

1. **Access the Calculator**:
   - Create a menu item pointing to "Components → BaZi → Calculate"
   - Or visit: `index.php?option=com_bazi&view=calculate`

2. **Fill in Birth Information**:
   - Birth Date (required)
   - Birth Time (required)
   - Gender (required)
   - Name (optional)
   - Location (optional but recommended for true solar time)
   - Longitude/Latitude (optional, for true solar time correction)
   - Timezone offset (optional)

3. **Calculate**:
   - Click "Calculate (计算)" button
   - Results will display showing Four Pillars, Dayun, Liunian, and Shensha

4. **Save Chart** (logged-in users only):
   - Check "Save Chart" checkbox before calculating
   - Requires `bazi.chart.create` permission
   - Chart will be saved to database and can be viewed in admin area

### Administrator Usage

1. **View Charts**:
   - Components → BaZi → Charts
   - Browse all saved charts
   - Filter by user, gender, date

2. **View Readings** (skeleton):
   - Components → BaZi → Readings
   - View GPT-generated readings (future milestone)

3. **Configure Component**:
   - Components → BaZi → Options
   - Adjust settings as needed

## Calculation Algorithm

### Year Pillar (年柱)
- Uses 立春 (Lichun, Start of Spring) as year boundary
- If birth is before Lichun of that solar year, uses previous year's Ganzhi
- Example: Born Jan 15, 2024 (before Lichun Feb 4) → Uses 2023 year pillar

### Month Pillar (月柱)
- Uses 节气定月 (solar term based months)
- Each month starts at a Jie Qi (节气 - odd-numbered solar term):
  - Month 1: 立春 (Lichun) to 惊蛰 (Jingzhe)
  - Month 2: 惊蛰 to 清明 (Qingming)
  - etc.
- Month stem derived from year stem using traditional formula

### Day Pillar (日柱)
- Uses Julian Day Number for consistent calculation
- Independent of year/month boundaries

### Hour Pillar (时柱)
- Uses Chinese double-hour system (一个时辰 = 2 hours)
- 子时 (23:00-01:00), 丑时 (01:00-03:00), etc.
- Hour stem derived from day stem using traditional formula

### Dayun (大运) Calculation
1. **Direction** (顺逆):
   - 阳年干生男: Forward (顺)
   - 阴年干生女: Forward (顺)
   - 阴年干生男: Backward (逆)
   - 阳年干生女: Backward (逆)

2. **Starting Age** (起运):
   - Forward: Days from birth to next Jie Qi
   - Backward: Days from birth to previous Jie Qi
   - 3 days = 1 year; remainder days converted to months

3. **Cycles**: 8 major cycles of 10 years each

### True Solar Time
When enabled and longitude provided:
1. **Longitude Correction**: Adjusts for difference from standard meridian (东经120°)
   - 4 minutes per degree of longitude
2. **Equation of Time**: Adjusts for Earth's orbital eccentricity
   - Uses simplified Spencer formula
   - Varies throughout the year (-16 to +16 minutes)

## Limitations (MVP)

- Calendar data includes sample for 2024-2026; production needs complete 1900-2050
- GPT reading generation is placeholder (future milestone)
- No lunar date input support (solar dates only in MVP)
- Limited set of Shensha (神煞) - can be expanded
- No chart editing after save (future enhancement)
- No chart comparison/analysis tools (future enhancement)

## Configuration Reference

### Algorithm Settings
- **Minimum Year**: Earliest supported birth year (default: 1900)
- **Maximum Year**: Latest supported birth year (default: 2050)
- **Liunian Range**: Years ± current year to display (default: 5)
- **True Solar Time**: Enable/disable true solar time correction (default: enabled)
- **Longitude Correction**: Apply longitude adjustment (default: enabled)
- **EoT Correction**: Apply Equation of Time adjustment (default: enabled)

### Anti-spam Settings
- **Enable Honeypot**: Use honeypot field (default: enabled)
- **Honeypot Field Name**: Name of hidden field (default: "website")
- **Rate Limit Window**: Time window in seconds (default: 3600 = 1 hour)
- **Guest Max Requests**: Max requests per IP in window (default: 5)
- **User Max Requests**: Max requests per user in window (default: 20)
- **Enable CAPTCHA**: Use Joomla CAPTCHA for guests (default: disabled)

### GPT Settings (Placeholder)
- **API Base URL**: OpenAI API endpoint (default: https://api.openai.com/v1)
- **API Key**: Your API key (empty by default)
- **Model**: GPT model to use (default: gpt-4)

## Security

- All POST requests validated with CSRF tokens
- Honeypot field to catch simple bots
- Rate limiting to prevent abuse
- Optional CAPTCHA integration
- SQL injection protection via prepared statements
- XSS protection via output escaping
- Permission checks for all sensitive operations

## License

GNU General Public License version 2 or later

## Support

For issues, questions, or contributions, please visit the GitHub repository.

## Credits

- Calendar data compiled from public domain astronomical calculations
- Solar term calculations based on traditional Chinese astronomical methods
- BaZi calculation rules follow traditional Chinese astrology principles

## Version History

### 1.0.0 (MVP)
- Initial release
- Complete calculation pipeline
- Anti-spam protections
- Admin interface
- ACL implementation
- True solar time support
