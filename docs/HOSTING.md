# Việc cần làm với hosting

Website Sunny Grove **không dùng plugin bên thứ ba**. Chỉ có:

| Thành phần | Vai trò |
|---|---|
| Theme **Sunny Grove** | Giao diện |
| Plugin **Sunny Grove Core** (tự viết) | Phòng, Tiện ích, Địa điểm, Ưu đãi, Đánh giá; đa ngôn ngữ; SEO; nút chat Zalo/WhatsApp/Gọi; bảo mật; xác thực 2 lớp; ảnh WebP; vai trò nhân viên; nội dung mẫu |
| Plugin **Sunny Grove Booking** (tự viết) | Form yêu cầu đặt phòng, form liên hệ, email, chống spam |

Không cài thêm plugin SEO, cache, chat, chống spam, sao lưu hay ACF – các việc đó đã có sẵn ở trên hoặc dùng tính năng của hosting như hướng dẫn dưới đây.

> Hầu hết hosting tại Việt Nam (và quốc tế) có các tính năng này trong **cPanel / DirectAdmin / trang quản lý hosting**. Nếu không tìm thấy, gửi tài liệu này cho bộ phận hỗ trợ của hosting và nhờ họ bật giúp.

---

## 1. Bật cache trang (tăng tốc)

Website **không tự viết hệ thống cache trang** – dùng cache có sẵn của máy chủ, nhanh và ổn định hơn.

**Hosting dùng LiteSpeed** (phổ biến ở Việt Nam): nhờ hosting bật **LiteSpeed Cache ở cấp máy chủ** (server-level / LSCache), hoặc thêm vào đầu file `.htaccess`:

```apache
<IfModule LiteSpeed>
CacheLookup on
RewriteEngine On
RewriteCond %{HTTP_COOKIE} !wordpress_logged_in [NC]
RewriteCond %{REQUEST_METHOD} ^GET$
RewriteCond %{REQUEST_URI} !^/(wp-admin|wp-login\.php|wp-json) [NC]
RewriteRule .* - [E=Cache-Control:max-age=3600]
</IfModule>
```

**Hosting dùng Nginx:** nhờ hosting bật **FastCGI cache** cho website, loại trừ: người đã đăng nhập (cookie `wordpress_logged_in_*`), `/wp-admin/`, `/wp-login.php`, `/wp-json/`.

**Lưu ý chung:**
- Bản tiếng Việt (`/…`) và tiếng Anh (`/en/…`) là hai đường dẫn khác nhau nên cache tự tách riêng, không cần cấu hình thêm.
- Form đặt phòng và form liên hệ lấy mã bảo mật mới mỗi lần mở trang (qua `/wp-json/`) nên **vẫn gửi được khi trang được cache**.
- Sau khi sửa nội dung, nếu khách chưa thấy thay đổi: xóa cache trong trang quản lý hosting (thường có nút *Purge / Xóa cache*), hoặc chờ hết thời gian cache (ví dụ 1 giờ).
- Có thể bật thêm cache sau này bằng một plugin cache nếu chủ website đồng ý – website không phụ thuộc vào nó.

## 2. Bật sao lưu tự động

Website **không tự viết chức năng sao lưu**. Dùng sao lưu của hosting:

1. Vào trang quản lý hosting → tìm mục **Backup / Sao lưu** (cPanel: *JetBackup* hoặc *Backup Wizard*; DirectAdmin: *Create/Restore Backups*).
2. Bật **sao lưu tự động hằng ngày**, giữ ít nhất **7–14 bản**, gồm cả **cơ sở dữ liệu** và **thư mục `wp-content/uploads`** (ảnh, video).
3. Nếu hosting cho phép, chọn nơi lưu **khác máy chủ website** (Google Drive, máy chủ backup riêng).
4. Thử **khôi phục** một lần (ví dụ khôi phục 1 file) để chắc chắn bản sao lưu dùng được.

**Xuất dữ liệu thủ công** (nên làm trước mỗi thay đổi lớn): trong trang quản trị WordPress vào **Công cụ → Xuất → Toàn bộ nội dung → Tải xuống tập tin xuất**. File `.xml` này chứa trang, bài, phòng, ưu đãi, tin nhắn… (cả chữ tiếng Việt và tiếng Anh). Ảnh/video nằm ở `wp-content/uploads` – tải về bằng File Manager của hosting.

## 3. Email không vào thư rác (SMTP)

Mặc định WordPress gửi email bằng hàm `mail` của hosting – nhiều khi bị vào thư rác. Nên gửi qua **SMTP** của email tên miền (ví dụ `booking@tenmien.vn`).

Nhờ hosting (hoặc tự mở **File Manager**) thêm các dòng sau vào file `wp-config.php`, **ngay trên dòng** `/* That's all, stop editing! */`:

```php
define( 'SG_SMTP_HOST', 'smtp.tenmien.vn' );   // máy chủ SMTP do nhà cung cấp email cho
define( 'SG_SMTP_PORT', 587 );                 // 587 hoặc 465
define( 'SG_SMTP_SECURE', 'tls' );             // 'tls' (cổng 587) hoặc 'ssl' (cổng 465)
define( 'SG_SMTP_USER', 'booking@tenmien.vn' );
define( 'SG_SMTP_PASS', 'mat-khau-email' );
```

Sau đó vào **Đặt phòng → Cài đặt → Chung**, điền **Email người gửi** đúng bằng email ở trên, rồi gửi thử một yêu cầu đặt phòng.

> Mật khẩu chỉ nằm trong `wp-config.php` trên hosting – không lưu trong cơ sở dữ liệu, không bao giờ đưa lên GitHub.

## 4. Tắt sửa file trong trang quản trị

Thêm dòng sau vào `wp-config.php` (cùng vị trí như trên):

```php
define( 'DISALLOW_FILE_EDIT', true );
```

Trang **Sunny Grove → Bảo mật** hiện ✓ khi dòng này đã có hiệu lực.

## 5. Ảnh WebP

Ảnh JPEG/PNG tải lên được tự tạo bản WebP nếu PHP của hosting có **GD hoặc Imagick hỗ trợ WebP** (hầu hết hosting hiện nay đều có). Bật/tắt tại **Sunny Grove → Thông tin khách sạn**. Nếu hosting có tính năng tối ưu ảnh riêng (ví dụ LiteSpeed *Image Optimization*), chỉ bật **một** trong hai để không xử lý ảnh hai lần.

## 6. Phiên bản PHP và HTTPS

- Chọn **PHP 8.1 trở lên** (khuyên dùng 8.2 hoặc 8.3) trong trang quản lý hosting (*Select PHP Version / MultiPHP Manager*).
- Bật **SSL miễn phí (Let's Encrypt)** và chuyển toàn bộ website sang `https://`.

## 7. Trước khi mở website cho khách

- [ ] Cache trang đã bật (mục 1) – mở website ở cửa sổ ẩn danh, tải lại 2 lần, trang hiện nhanh.
- [ ] Sao lưu tự động hằng ngày đã bật và đã thử khôi phục (mục 2).
- [ ] Email gửi thử vào Gmail không nằm trong thư rác (mục 3).
- [ ] `DISALLOW_FILE_EDIT` đã bật (mục 4).
- [ ] **Sunny Grove → SEO:** bỏ tích *Chặn Google lập chỉ mục*.
