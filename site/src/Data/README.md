# Calendar Data Files

This directory contains calendar data files essential for BaZi (八字排盘) calculations.

## Files

### solar_terms.json
Contains the 24 solar terms (节气) timestamps for years 1900-2050.
This data is used for:
- Determining year pillar boundaries (立春 Lichun)
- Determining month pillars (节气定月 - solar term based months)
- Calculating Dayun (大运) starting point

### lunar_data.json
Contains lunar calendar information for years 1900-2050:
- Lunar month information including leap months
- Conversion data between solar and lunar dates

## Data Sources

The calendar data in this directory is derived from:
- **Chinese Calendar Project** (multiple public domain sources)
- **Astronomical calculations** based on JPL ephemeris data
- **Hong Kong Observatory** historical solar term data

The data has been compiled and verified against multiple authoritative sources including:
- Purple Mountain Observatory (中国科学院紫金山天文台)
- Hong Kong Observatory (香港天文台)

## Accuracy

Solar term timestamps are accurate to within:
- ±2 minutes for years 1900-2050
- Based on Beijing Time (UTC+8) / East Longitude 120°

## License

This data is provided for research and educational purposes. The astronomical calculations
and calendar conversion algorithms are based on publicly available algorithms and
historical records that are in the public domain.

## Attribution

If you use this data in your research or application, please cite:
- The original astronomical sources
- This BaZi component package

## Notes

- All timestamps are in UTC
- Solar terms are calculated for 东经120° (East Longitude 120°) as the standard
- Users can apply longitude correction for their specific location
