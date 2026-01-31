# Manual Testing Guide - Multi-Vendor Rental Platform

## 🚀 Getting Started

### Prerequisites
- XAMPP running (Apache on port 8081, MySQL)
- Database migrated and seeded with demo data
- Browser (Chrome, Firefox, or Edge recommended)

### Base URL
```
http://localhost:8081/Multi-Vendor-Rental-System/public/
```

### Demo Credentials
Located in `LOGIN_CREDENTIALS.txt` file. Default password for all accounts: `password123`

**Vendors:**
- `premium_house` - Premium House Rentals
- `soundwave_audio` - SoundWave Audio
- `driveaway_cars` - DriveAway Cars
- `furnishpro` - FurnishPro
- `techrent` - TechRent

**Customers:**
- `john_doe` - John Doe
- `jane_smith` - Jane Smith

**Administrator:**
- Create one manually or check if seeded

---

## 📋 Task 4.7: Product Image Management Testing

### Setup
1. Login as a vendor (e.g., `premium_house` / `password123`)
2. Navigate to Products page
3. Select any product

### Test Cases

#### 1. Access Image Management
- **URL**: `http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/product-images.php?id={PRODUCT_ID}`
- **Expected**: Image management page loads with upload zone and gallery

#### 2. Upload Single Image
**Steps:**
1. Click on the upload zone
2. Select a single image file (JPEG, PNG, or WebP)
3. Wait for upload to complete

**Expected Results:**
- Progress bar shows upload progress
- Success message appears
- Image appears in gallery
- Thumbnail is generated
- Image is marked as "Primary" (if first image)

#### 3. Upload Multiple Images
**Steps:**
1. Click upload zone
2. Select multiple images (Ctrl+Click or Cmd+Click)
3. Wait for all uploads to complete

**Expected Results:**
- Progress bar updates for each file
- All images appear in gallery
- Success message shows count (e.g., "Successfully uploaded 3 of 3 images")

#### 4. Drag and Drop Upload
**Steps:**
1. Open file explorer
2. Drag image files onto the upload zone
3. Drop files

**Expected Results:**
- Upload zone highlights on drag over
- Files upload automatically
- Same results as click-to-upload

#### 5. Image Validation
**Test Invalid Files:**
- Try uploading a PDF → Should show error
- Try uploading a file >5MB → Should show error
- Try uploading a corrupted image → Should show error

**Expected**: Error messages for each invalid file type

#### 6. Reorder Images
**Steps:**
1. Drag an image to a new position
2. Drop it
3. Observe order changes
4. Click "Save Order" button

**Expected Results:**
- Images reorder visually
- "Save Order" button appears
- After saving, success message appears
- Order persists after page reload

#### 7. View Full-Size Image
**Steps:**
1. Hover over an image
2. Click the eye icon
3. Click outside modal or close button

**Expected Results:**
- Lightbox modal opens with full-size image
- Background is darkened
- Modal closes on click outside or close button

#### 8. Delete Image
**Steps:**
1. Hover over an image
2. Click trash icon
3. Confirm deletion

**Expected Results:**
- Confirmation dialog appears
- After confirming, image is removed from gallery
- Success message appears
- Image file is deleted from server

#### 9. Primary Image Badge
**Expected**: First image in gallery always shows "Primary" badge

#### 10. Empty State
**Steps:**
1. Delete all images
2. Observe empty state

**Expected**: Message "No images uploaded yet" with icon

---

## 📋 Task 4.8: Category Management Testing

### Setup
1. Login as administrator
2. Navigate to Categories page

### Test Cases

#### 1. Access Category Management
- **URL**: `http://localhost:8081/Multi-Vendor-Rental-System/public/admin/categories.php`
- **Expected**: Category management page loads with statistics and tree view

#### 2. View Statistics
**Expected Display:**
- Total Categories count
- Root Categories count
- Subcategories count

#### 3. Create Root Category
**Steps:**
1. Click "Create Category" button
2. Enter name: "Electronics"
3. Enter description: "Electronic devices and gadgets"
4. Leave Parent Category as "None"
5. Click "Create Category"

**Expected Results:**
- Modal opens with form
- After submission, success message appears
- Page reloads
- New category appears in tree
- Statistics update

#### 4. Create Subcategory
**Steps:**
1. Click "Create Category"
2. Enter name: "Laptops"
3. Enter description: "Portable computers"
4. Select Parent Category: "Electronics"
5. Click "Create Category"

**Expected Results:**
- Subcategory appears under parent
- Can be expanded/collapsed
- Indented to show hierarchy

#### 5. Edit Category
**Steps:**
1. Hover over a category
2. Click "Edit" button
3. Change name or description
4. Click "Update Category"

**Expected Results:**
- Modal opens with pre-filled data
- Changes save successfully
- Tree updates with new information

#### 6. Category Tree Navigation
**Steps:**
1. Click chevron icon next to category with children
2. Observe expansion/collapse

**Expected Results:**
- Chevron rotates 90 degrees
- Children show/hide smoothly
- Multiple levels can be expanded

#### 7. Delete Category (Success)
**Steps:**
1. Create a test category with no children or products
2. Click "Delete" button
3. Confirm deletion

**Expected Results:**
- Confirmation dialog appears
- Category is removed
- Page reloads

#### 8. Delete Category (With Children)
**Steps:**
1. Try to delete a category that has subcategories
2. Confirm deletion

**Expected Results:**
- Error message: "Cannot delete category with subcategories"
- Category remains in tree

#### 9. Delete Category (With Products)
**Steps:**
1. Try to delete a category assigned to products
2. Confirm deletion

**Expected Results:**
- Error message: "Cannot delete category with products"
- Category remains in tree

#### 10. Circular Reference Prevention
**Steps:**
1. Edit a parent category
2. Try to set its child as its parent
3. Save

**Expected Results:**
- Error message: "Cannot create circular category hierarchy"
- Change is not saved

#### 11. Duplicate Name Prevention
**Steps:**
1. Create category with name "Test"
2. Try to create another category with name "Test"

**Expected Results:**
- Error message: "A category with this name already exists"

---

## 🔍 General Testing Checklist

### Authentication & Authorization

#### Test Vendor Access
1. Login as vendor
2. Try to access admin pages directly:
   - `http://localhost:8081/Multi-Vendor-Rental-System/public/admin/categories.php`
   - `http://localhost:8081/Multi-Vendor-Rental-System/public/admin/audit-logs.php`

**Expected**: Redirected or access denied

#### Test Customer Access
1. Login as customer
2. Try to access vendor pages:
   - `http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/products.php`
   - `http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/product-images.php?id=xxx`

**Expected**: Redirected or access denied

#### Test Product Ownership
1. Login as vendor A
2. Get product ID from vendor B
3. Try to access: `http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/product-images.php?id={VENDOR_B_PRODUCT_ID}`

**Expected**: Redirected to products page

### Navigation Testing

#### Admin Navigation
1. Login as administrator
2. Check navigation menu shows:
   - Dashboard
   - Users
   - Vendors
   - **Categories** (NEW)
   - Audit Logs
   - Settings

#### Vendor Navigation
1. Login as vendor
2. Check navigation menu shows:
   - Dashboard
   - Products
   - Orders
   - Analytics

### Responsive Design Testing

#### Desktop (1920x1080)
- All pages should display properly
- No horizontal scrolling
- Images and grids display correctly

#### Tablet (768x1024)
- Navigation collapses to mobile menu
- Grids adjust to fewer columns
- Modals remain centered

#### Mobile (375x667)
- Mobile menu works
- Forms are usable
- Images scale appropriately

---

## 🐛 Common Issues & Solutions

### Issue: Images Not Uploading
**Check:**
1. `public/uploads/products/` directory exists and is writable
2. PHP `upload_max_filesize` and `post_max_size` settings
3. Browser console for JavaScript errors

### Issue: Categories Not Saving
**Check:**
1. Database connection
2. Browser console for API errors
3. Network tab for failed requests

### Issue: Access Denied Errors
**Check:**
1. Logged in with correct role
2. Session is active
3. Middleware is working

### Issue: Images Not Displaying
**Check:**
1. File paths are correct
2. Images exist in `public/uploads/products/`
3. Web server has read permissions

---

## 📊 Performance Testing

### Image Upload Performance
1. Upload 10 images simultaneously
2. Measure time to complete
3. Check server CPU/memory usage

**Expected**: All uploads complete within reasonable time (< 30 seconds for 10 images)

### Category Tree Performance
1. Create 50+ categories with nested structure
2. Expand/collapse all categories
3. Check for lag or delays

**Expected**: Smooth animations, no noticeable lag

---

## ✅ Acceptance Criteria

### Task 4.7 (Image Management)
- ✅ Can upload images via click or drag-and-drop
- ✅ Images are optimized and thumbnails generated
- ✅ Can reorder images via drag-and-drop
- ✅ Can delete images
- ✅ Can view full-size images
- ✅ Primary image badge shows on first image
- ✅ Only vendor can manage their own product images

### Task 4.8 (Category Management)
- ✅ Can create root categories
- ✅ Can create subcategories
- ✅ Can edit categories
- ✅ Can delete categories (with validation)
- ✅ Category tree displays hierarchy
- ✅ Can expand/collapse categories
- ✅ Prevents circular references
- ✅ Prevents duplicate names
- ✅ Only administrators can manage categories

---

## 📝 Test Report Template

```
Test Date: ___________
Tester: ___________
Browser: ___________
OS: ___________

Task 4.7 - Image Management:
[ ] Upload single image
[ ] Upload multiple images
[ ] Drag-and-drop upload
[ ] Image validation
[ ] Reorder images
[ ] View full-size
[ ] Delete image
[ ] Primary badge
[ ] Empty state
[ ] Authorization

Task 4.8 - Category Management:
[ ] View statistics
[ ] Create root category
[ ] Create subcategory
[ ] Edit category
[ ] Delete category
[ ] Tree navigation
[ ] Circular reference prevention
[ ] Duplicate name prevention
[ ] Authorization

Issues Found:
1. ___________
2. ___________
3. ___________

Overall Status: [ ] Pass [ ] Fail
```

---

## 🎯 Quick Test Scenarios

### Scenario 1: New Vendor Setup (5 minutes)
1. Login as new vendor
2. Create a product
3. Upload 3-5 product images
4. Reorder images
5. Delete one image
6. Verify primary image

### Scenario 2: Category Organization (5 minutes)
1. Login as admin
2. Create 3 root categories
3. Create 2 subcategories under each
4. Edit one category
5. Try to delete category with children
6. Delete empty category

### Scenario 3: Security Check (3 minutes)
1. Login as vendor
2. Try to access admin categories page
3. Try to manage another vendor's product images
4. Verify access is denied

---

## 📞 Support

If you encounter issues during testing:
1. Check browser console for errors
2. Check PHP error logs
3. Verify database connections
4. Check file permissions
5. Review the completion summary documents

Happy Testing! 🎉
