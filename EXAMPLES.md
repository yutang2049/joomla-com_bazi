# BaZi Component - Usage Examples

## Example 1: Guest User - Quick Calculation

**Scenario**: A visitor wants to check their BaZi chart without registering.

**Steps**:
1. Navigate to the BaZi calculator page
2. Fill in required fields:
   - Birth Date: 1985-07-20
   - Birth Time: 14:30
   - Gender: Female
3. Optional: Enter location data for true solar time
   - Location: Beijing, China
   - Longitude: 116.4074
   - Latitude: 39.9042
4. Click "Calculate (计算)"

**Result**:
- Chart is calculated and displayed
- Results show:
  - Four Pillars (Year: 乙丑, Month: 癸未, Day: XX, Hour: XX)
  - Dayun cycles starting from age X
  - Liunian for years around current year
  - Shensha influences
- Chart is NOT saved to database
- Can calculate up to 5 times per hour (rate limit)

**Anti-spam protection in action**:
- CSRF token validated (transparent)
- Honeypot field checked (transparent)
- Rate limit tracked by IP address
- If 6th calculation attempted within 1 hour → HTTP 429 error

---

## Example 2: Logged-in User - Calculate and Save

**Scenario**: A registered user wants to calculate and save their family member's chart.

**Steps**:
1. Login to the site
2. Navigate to the BaZi calculator page
3. Fill in birth information:
   - Name: "Little Sister"
   - Birth Date: 2010-08-15
   - Birth Time: 09:15
   - Gender: Female
   - Location: Shanghai, China
   - Longitude: 121.4737
4. **Check the "Save Chart" checkbox**
5. Click "Calculate (计算)"

**Result**:
- Chart is calculated with true solar time correction
  - Original time: 09:15
  - After longitude correction: ~09:21 (about 6 minutes)
  - After EoT correction: ~09:21 ± few seconds
- Results displayed with correction info
- Success message: "Chart saved successfully!"
- Chart stored in database with:
  - User ID
  - All input data
  - Normalized datetime
  - Complete calculation result (JSON)
  - Algorithm version
- User can calculate up to 20 times per hour (higher limit)

**Permission check**:
- Requires `bazi.chart.create` permission
- If user lacks permission → warning message, chart not saved
- Chart still displayed for viewing

---

## Example 3: Birth on Lichun Boundary

**Scenario**: User born near 立春 (Start of Spring) wants accurate year pillar.

**Case A: Before Lichun**
- Birth: 2024-02-03 10:00:00
- Lichun 2024: 2024-02-04 16:26:53 UTC
- **Result**: Year pillar uses 2023 (癸卯)
- Reason: Birth is before Lichun, so belongs to previous lunar year

**Case B: After Lichun**
- Birth: 2024-02-05 10:00:00
- Lichun 2024: 2024-02-04 16:26:53 UTC
- **Result**: Year pillar uses 2024 (甲辰)
- Reason: Birth is after Lichun, so belongs to current lunar year

This demonstrates the component's accurate implementation of traditional BaZi rules!

---

## Example 4: True Solar Time Correction

**Scenario**: Calculate accurate time for someone born far from 东经120° (standard meridian).

**Birth in Ürümqi, Xinjiang (westernmost China)**:
- Input time: 12:00:00 noon
- Location: Ürümqi
- Longitude: 87.6168° E
- Latitude: 43.8256° N

**Calculation**:
1. Longitude difference: 87.6168 - 120 = -32.3832°
2. Time correction: -32.3832° × 4 minutes/degree = -129.5 minutes
3. After longitude correction: ~09:51
4. After EoT correction: ~09:51 ± (0-16 minutes depending on date)

**Result**:
- Original input shows: 12:00 (hour pillar: 丙午)
- After correction: 09:51 (hour pillar changes to: 己巳)
- Demonstrates importance of true solar time for accurate hour pillar

---

## Example 5: Male vs Female Dayun Direction

**Birth Data**:
- Date: 1990-05-15
- Time: 10:30:00
- Year Stem: 庚 (Yang Metal)

**Case A: Male**
- Gender: Male
- Year Stem: 庚 (Yang)
- Rule: 阳男顺 (Yang male → Forward)
- Direction: **Forward** ✓
- Start: Takes next Jie Qi after birth
- Cycles: 壬未 → 癸申 → 甲酉 → ...

**Case B: Female**
- Gender: Female
- Year Stem: 庚 (Yang)
- Rule: 阳女逆 (Yang female → Backward)
- Direction: **Backward** ✓
- Start: Takes previous Jie Qi before birth
- Cycles: 庚辰 → 己卯 → 戊寅 → ...

Same birth date/time, different gender → Different Dayun direction!

---

## Example 6: Administrator - View Saved Charts

**Scenario**: Admin wants to review all saved charts in the system.

**Steps**:
1. Login to Joomla Administrator
2. Navigate to: Components → BaZi → Charts
3. View list of all saved charts with:
   - Chart ID
   - User name (or "Guest" if orphaned)
   - Birth date and time
   - Gender
   - Creation date
4. Optional: Filter by user or gender
5. Optional: Sort by any column

**Use Cases**:
- Monitor component usage
- Audit saved charts
- Support user inquiries
- Generate usage reports
- Data export for analysis

---

## Example 7: Rate Limiting in Action

**Scenario**: Enthusiastic user tries multiple calculations.

**Timeline**:
```
13:00 - Calculation #1 ✓ Success
13:05 - Calculation #2 ✓ Success
13:10 - Calculation #3 ✓ Success
13:15 - Calculation #4 ✓ Success
13:20 - Calculation #5 ✓ Success
13:25 - Calculation #6 ✗ Error: "Rate limit exceeded"
```

**What happened**:
- Guest user (tracked by IP: 192.168.1.100)
- Rate limit: 5 requests per 3600 seconds (1 hour)
- 6th request at 13:25 is blocked
- HTTP 429 response returned
- User must wait until 14:00 (1 hour after first request)

**If user had logged in**:
- Would have 20 requests per hour limit
- Calculation #6 would succeed
- Demonstrates benefit of user registration

---

## Example 8: Honeypot Catches Bot

**Scenario**: Bot tries to submit spam calculations.

**Bot behavior**:
- Automatically fills ALL form fields (including hidden ones)
- Honeypot field "website" gets filled with spam URL

**Server-side check**:
```php
if (!empty($_POST['website'])) {
    // Honeypot triggered!
    return error("Invalid form submission");
}
```

**Result**:
- Bot submission rejected
- Error message: "Invalid form submission"
- Legitimate users never see honeypot field
- Protects system from automated spam

---

## Example 9: Configuration Customization

**Scenario**: Site admin wants to adjust component behavior.

**Configuration Changes**:

1. **Increase Rate Limits** (busy site):
   ```
   Guest Max: 5 → 10
   User Max: 20 → 50
   ```

2. **Extend Liunian Range** (detailed analysis):
   ```
   Liunian Range: ±5 → ±10
   (Shows 20 years instead of 10)
   ```

3. **Enable CAPTCHA** (extra protection):
   ```
   CAPTCHA Enabled: No → Yes
   ```

4. **Change Honeypot Field** (periodic refresh):
   ```
   Field Name: "website" → "company"
   (Confuses bots that learned old field name)
   ```

**Steps**:
1. Components → BaZi → Options
2. Modify settings
3. Save & Close
4. Changes take effect immediately

---

## Example 10: Error Handling

**Scenario**: User makes various input mistakes.

**Error Case 1: Missing Required Field**
- Input: Birth date empty
- Result: "Invalid input. Please check all fields."
- Form validation prevents submission

**Error Case 2: Year Out of Range**
- Input: Birth date 1850-01-01
- Min Year: 1900
- Result: "Birth year must be between 1900 and 2050"

**Error Case 3: Invalid Token (expired session)**
- User leaves form open for 2 hours
- Submits form
- Result: "Security token expired. Please refresh and try again."
- User refreshes page and resubmits

**Error Case 4: Permission Denied**
- User without `bazi.chart.create` tries to save
- Result: "Permission denied" (warning, not error)
- Chart still displays but not saved

---

## Example 11: Multi-language Support (Future)

**Current**: English only
```
Birth Date: 1990-05-15
Gender: Male
Calculate
```

**Future**: Chinese support
```
出生日期: 1990-05-15
性别: 男
计算
```

**Implementation Ready**:
- All strings use language keys
- Easy to add new language packs
- Example: `COM_BAZI_FIELD_BIRTH_DATE` → "Birth Date" or "出生日期"

---

## Example 12: Performance Considerations

**Cached Calculations**:
- Calculation result stored in `bazi_json` field
- If same chart calculated again → retrieve from cache
- No need to recalculate (future optimization)

**Calendar Data Loading**:
- Solar terms loaded once and cached in memory
- Multiple calculations reuse same data
- Efficient for batch processing

**Rate Limit Cleanup**:
- Old rate limit records auto-cleaned
- Cleanup runs on each calculation check
- Keeps table size manageable

---

## Example 13: Troubleshooting Scenarios

**Problem**: "Calculation failed"
**Possible Causes**:
1. Birth year outside supported range
2. Calendar data missing for year
3. Invalid date format

**Solution**:
1. Check year is 1900-2050
2. Verify calendar data exists
3. Use YYYY-MM-DD format

---

**Problem**: Charts not appearing in admin list
**Possible Causes**:
1. User is guest (charts not saved)
2. Database permissions issue
3. No charts saved yet

**Solution**:
1. Check "Save Chart" was checked
2. Verify user was logged in
3. Check database table exists

---

**Problem**: Rate limit too restrictive
**Solution**:
1. Go to Component Options
2. Increase `rate_limit_guest_max` or `rate_limit_user_max`
3. Or increase `rate_limit_window` (seconds)
4. Save configuration

---

## Summary

The BaZi component handles a wide variety of use cases:
- ✅ Guest and registered user workflows
- ✅ Accurate astronomical calculations
- ✅ True solar time corrections
- ✅ Anti-spam protections
- ✅ Traditional BaZi rules (立春, 节气定月, 顺逆)
- ✅ Flexible configuration
- ✅ Error handling and validation
- ✅ Admin management interface

All examples demonstrate real scenarios where the component provides accurate, secure, and user-friendly BaZi calculations!
