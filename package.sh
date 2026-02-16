#!/bin/bash
# Package the com_bazi component for installation

COMPONENT_NAME="com_bazi"
VERSION="1.0.0"
OUTPUT_FILE="${COMPONENT_NAME}_v${VERSION}.zip"

echo "Packaging ${COMPONENT_NAME} component..."

# Create temporary directory
TEMP_DIR=$(mktemp -d)
PACKAGE_DIR="${TEMP_DIR}/${COMPONENT_NAME}"
mkdir -p "${PACKAGE_DIR}"

# Copy files
echo "Copying files..."
cp com_bazi.xml "${PACKAGE_DIR}/"
cp -r admin "${PACKAGE_DIR}/"
cp -r site "${PACKAGE_DIR}/"
cp -r media "${PACKAGE_DIR}/" 2>/dev/null || mkdir -p "${PACKAGE_DIR}/media"
cp README.md "${PACKAGE_DIR}/" 2>/dev/null || true

# Create the ZIP file
echo "Creating ZIP archive..."
cd "${TEMP_DIR}"
zip -r "${OUTPUT_FILE}" "${COMPONENT_NAME}" > /dev/null

# Move to current directory
mv "${OUTPUT_FILE}" "${OLDPWD}/"
cd "${OLDPWD}"

# Cleanup
rm -rf "${TEMP_DIR}"

echo "✓ Package created: ${OUTPUT_FILE}"
echo "  Size: $(du -h ${OUTPUT_FILE} | cut -f1)"
echo ""
echo "To install:"
echo "1. Go to Joomla Administrator → System → Install → Extensions"
echo "2. Upload ${OUTPUT_FILE}"
echo "3. Click Install"
