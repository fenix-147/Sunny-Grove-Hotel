# Danh sách plugin & cấu hình khuyến nghị

Tổng cộng **10 plugin** (2 plugin tự viết + 8 plugin miễn phí). Không cần plugin trả phí.

| # | Plugin | Bắt buộc? | Vai trò |
|---|---|---|---|
| 1 | **Sunny Grove Core** (trong repo) | Bắt buộc | Phòng, Tiện ích, Địa điểm, Ưu đãi, thông tin khách sạn, nhãn song ngữ, block động, nút chat, vai trò nhân viên, nhập nội dung mẫu |
| 2 | **Sunny Grove Booking** (trong repo) | Bắt buộc | Form yêu cầu đặt phòng, quản lý yêu cầu, email song ngữ |
| 3 | **Polylang** | Bắt buộc | Song ngữ Việt – Anh cho trang, menu, phòng, địa điểm, ưu đãi |
| 4 | **Contact Form 7** | Nên có | Form liên hệ ở trang Liên hệ (mỗi ngôn ngữ một form) |
| 5 | **LiteSpeed Cache** *(hosting LiteSpeed)* hoặc **WP Super Cache** *(hosting khác)* | Nên có | Tăng tốc trang |
| 6 | **FluentSMTP** | Rất nên có | Gửi email qua SMTP để email đặt phòng không vào thư rác |
| 7 | **UpdraftPlus** | Rất nên có | Sao lưu tự động lên Google Drive |
| 8 | **Wordfence Security** | Rất nên có | Tường lửa, chống dò mật khẩu, xác thực 2 lớp cho quản trị |
| 9 | **Rank Math SEO** | Nên có | SEO, sơ đồ trang, Schema khách sạn |
| 10 | **Widgets for Google Reviews** (Trustindex) | Tùy chọn | Hiện đánh giá Google thật thay cho đánh giá mẫu |

Nút chat Zalo/WhatsApp, bản đồ, font chữ, nén ảnh WebP và vai trò nhân viên đã có sẵn trong theme/plugin Sunny Grove – **không cần cài thêm plugin** cho các việc này.

---

## 1. LiteSpeed Cache (khi hosting dùng LiteSpeed)

Cài xong vào **LiteSpeed Cache → Cài đặt chung**:

- **Bật cache:** Bật. Phần còn lại để mặc định.
- **Trang → Cache → Loại trừ:** không cần thêm gì – form đặt phòng tự báo cho LiteSpeed không cache phần gửi dữ liệu.
- **Tối ưu trang (Page Optimization):**
  - CSS Minify: Bật · CSS Combine: Tắt (theme đã gọn).
  - JS Minify: Bật · JS Combine: Tắt · **Load JS Deferred: “Deferred”** (đừng chọn “Delay”, video nền sẽ chạy chậm).
  - Lazy Load Images: **Tắt** (WordPress đã tự lazy-load đúng cách; bật trùng có thể làm ảnh đầu trang tải chậm).
- **Tối ưu ảnh (Image Optimization):** nếu bật “Create WebP Versions” của LiteSpeed, hãy **tắt** ô *“Tự tạo ảnh WebP khi tải ảnh lên”* trong **Sunny Grove → Thông tin khách sạn** để không xử lý hai lần.
- **Polylang:** LiteSpeed tự cache riêng bản `/en/` và bản tiếng Việt, không cần cấu hình thêm.

## 1b. Hosting khác (không phải LiteSpeed)

- Cài **WP Super Cache** → Cài đặt → **Bật bộ nhớ đệm** → chế độ *Đơn giản* là đủ.
- Ảnh WebP: giữ bật ô *“Tự tạo ảnh WebP khi tải ảnh lên”* trong **Sunny Grove → Thông tin khách sạn** (hoạt động trên mọi hosting có PHP GD/Imagick hỗ trợ WebP).

## 2. FluentSMTP – email không vào thư rác

1. **Cài đặt → FluentSMTP → Thêm kết nối.**
2. Chọn dịch vụ: *Gmail / Google Workspace*, *Zoho*, hoặc *Other SMTP* (thông tin do nhà cung cấp email/hosting cấp).
3. Ô **From Email** nên là email cùng tên miền website (ví dụ `booking@tenmien.vn`).
4. Bấm **Send a Test Email** để thử.
5. Nhập cùng email đó vào **Đặt phòng → Cài đặt → Email người gửi**.

> Mật khẩu SMTP chỉ nhập trong trang quản trị, không bao giờ đưa lên GitHub.

## 3. UpdraftPlus – sao lưu tự động

**Cài đặt → UpdraftPlus Backups → Cài đặt:**
- Lịch sao lưu tệp: *Hằng tuần*, giữ 4 bản. Lịch sao lưu cơ sở dữ liệu: *Hằng ngày*, giữ 14 bản.
- Nơi lưu trữ: **Google Drive** → làm theo hướng dẫn kết nối tài khoản.
- Lưu thay đổi → bấm **Sao lưu ngay** một lần để kiểm tra.

## 4. Wordfence – bảo mật & xác thực 2 lớp

1. Kích hoạt, nhập email nhận cảnh báo, chọn bản miễn phí.
2. **Wordfence → Firewall → Optimize the Wordfence Firewall** → làm theo hướng dẫn.
3. **Wordfence → Login Security → Two-Factor Authentication:** quét mã QR bằng ứng dụng *Google Authenticator* hoặc *Microsoft Authenticator*, nhập mã 6 số → **Activate**. Lưu lại các mã khôi phục.
4. Tab **Settings** của Login Security: bật *Require 2FA for “Administrator”*.

## 5. Rank Math SEO – Schema khách sạn

1. Chạy trình hướng dẫn, chọn **Local Business**.
2. **Rank Math → Titles & Meta → Local SEO:** loại hình **Hotel** (hoặc *LodgingBusiness*), nhập tên, địa chỉ, số điện thoại, giờ nhận/trả phòng, logo, link Google Maps.
3. **Rank Math → Sitemap:** bật cho Trang, Bài viết, Phòng, Tiện ích, Địa điểm, Ưu đãi; tắt cho Tệp đính kèm.
4. Rank Math tương thích Polylang: mỗi bản dịch có tiêu đề/mô tả SEO riêng.

## 6. Widgets for Google Reviews (tùy chọn)

1. Cài **Widgets for Google Reviews** → kết nối hồ sơ Google Business của khách sạn → chọn giao diện → sao chép shortcode.
2. Mở trang chủ → khối **Đánh giá khách hàng** → xóa 3 thẻ đánh giá mẫu → chèn khối **Shortcode** → dán shortcode. Làm tương tự với trang chủ bản English.

## 7. Polylang

Công cụ **Nội dung mẫu** đã tự cấu hình: Tiếng Việt mặc định (không có `/vi/` trong đường dẫn), English dùng `/en/`, không tự đoán ngôn ngữ trình duyệt, đồng bộ số liệu (giá, diện tích, khoảng cách, ảnh…) giữa hai bản dịch. Nếu cài thủ công không dùng công cụ, vào **Ngôn ngữ** thêm *Tiếng Việt* (đặt mặc định) và *English*, rồi ở tab **Cài đặt → URL modifications** chọn *“The language is set from the directory name in pretty permalinks”* và *“Hide URL language information for default language”*.
