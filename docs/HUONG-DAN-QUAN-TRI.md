# Hướng dẫn quản trị website Sunny Grove

Tài liệu dành cho nhân viên khách sạn – **không cần biết code**. Mọi thao tác đều làm trong trang quản trị: `tên-miền-của-bạn/wp-admin`.

> **Mẹo chung**
> - Sửa xong nhớ bấm nút xanh **Cập nhật** (hoặc **Lưu thay đổi**) ở góc trên bên phải.
> - Mọi chỗ có chữ **[CẦN THAY]** là thông tin giả – hãy thay bằng thông tin thật trước khi mở website.
> - Website có 2 ngôn ngữ. Khi sửa nội dung Tiếng Việt, đừng quên sửa bản English tương ứng (xem mục 8).
> - **Trang chủ Tiếng Việt và Home (English) là 2 trang riêng.** Muốn sửa phòng, ưu đãi, tiện ích, địa điểm thì vào menu **Phòng / Ưu đãi / Tiện ích / Khám phá Phú Quốc** – không sửa trong trang chủ (xem [mục 8](#trang-chủ-tiếng-việt-và-home-english-là-2-trang-riêng)).

## Mục lục
1. [Bản đồ trang quản trị](#1-bản-đồ-trang-quản-trị)
2. [Thông tin khách sạn: hotline, Zalo, địa chỉ, bản đồ](#2-thông-tin-khách-sạn)
3. [Đổi video/ảnh banner (hero)](#3-đổi-videoảnh-banner-hero)
4. [Đổi màu và font chữ](#4-đổi-màu-và-font-chữ)
5. [Thêm / sửa phòng, giá, mùa cao điểm](#5-thêm--sửa-phòng-giá-mùa-cao-điểm)
6. [Tiện ích](#6-tiện-ích)
7. [Thêm địa điểm Khám phá Phú Quốc và khoảng cách](#7-thêm-địa-điểm-khám-phá-phú-quốc)
8. [Dịch nội dung sang tiếng Anh](#8-dịch-nội-dung-sang-tiếng-anh)
9. [Tạo ưu đãi mới](#9-tạo-ưu-đãi-mới)
10. [Viết bài blog](#10-viết-bài-blog)
11. [Quản lý yêu cầu đặt phòng](#11-quản-lý-yêu-cầu-đặt-phòng)
12. [Sửa chữ trên form đặt phòng và nội dung email](#12-sửa-chữ-trên-form-và-email)
13. [Menu, nút chat, nhãn hiển thị](#13-menu-nút-chat-nhãn-hiển-thị)
14. [Tạo tài khoản cho nhân viên](#14-tạo-tài-khoản-cho-nhân-viên)
15. [Sao lưu và khôi phục](#15-sao-lưu-và-khôi-phục)
16. [Danh sách kiểm tra trước khi mở website](#16-danh-sách-kiểm-tra-trước-khi-mở-website)

---

## 1. Bản đồ trang quản trị

| Menu bên trái | Dùng để |
|---|---|
| **Đặt phòng** | Xem và xử lý yêu cầu đặt phòng của khách; **Cài đặt** chữ trên form và email |
| **Sunny Grove → Thông tin khách sạn** | Hotline, Zalo, WhatsApp, email, địa chỉ, bản đồ, slogan |
| **Sunny Grove → Nhãn hiển thị** | Các chữ nhỏ dùng chung: nút “Xem chi tiết”, “từ khách sạn”, đơn vị giá… |
| **Phòng** | Danh sách phòng, giá, diện tích, ảnh |
| **Tiện ích** | Hồ bơi, Kids Club, nhà hàng… |
| **Khám phá Phú Quốc** | Các địa điểm tham quan và khoảng cách |
| **Ưu đãi** | Combo, khuyến mãi |
| **Bài viết** | Blog |
| **Trang** | Trang chủ, Phòng & Giá, Liên hệ… (nội dung trang) |
| **Thư viện** | Ảnh và video |
| **Ngôn ngữ** | Cài đặt Polylang (chỉ quản trị viên) |

## 2. Thông tin khách sạn

**Sunny Grove → Thông tin khách sạn**

- Tab **Chung (mọi ngôn ngữ):** hotline, số Zalo, số WhatsApp (ghi kèm +84), email, link Google Maps, link nhúng bản đồ, bật/tắt nút chat nổi.
- Tab **Tiếng Việt** và **English:** tên khách sạn, **slogan** (hiện to ở trang chủ), địa chỉ, giờ nhận/trả phòng, giới thiệu ở chân trang.

**Lấy link nhúng bản đồ:** mở Google Maps → tìm khách sạn → **Chia sẻ** → **Nhúng bản đồ** → sao chép đoạn mã → chỉ dán phần nằm giữa `src="` và `"` vào ô *Link nhúng bản đồ*.

Sửa ở đây là đầu trang, chân trang, trang Liên hệ, nút chat và form đặt phòng đều cập nhật theo.

## 3. Đổi video/ảnh banner (hero)

Mỗi trang có một **Banner video** ở đầu trang.

1. **Trang** → di chuột vào trang cần sửa (ví dụ *Trang chủ*) → **Chỉnh sửa**.
2. Bấm vào vùng banner. Nếu thấy chọn nhầm chữ bên trong, bấm biểu tượng **chọn khối cha** (mũi tên lên) trên thanh công cụ nhỏ cho tới khi thanh bên phải ghi **Banner video**.
3. Ở thanh bên phải, mục **Video & ảnh nền**:
   - **Đổi ảnh poster:** ảnh hiện ngay khi trang vừa mở (nên chọn ảnh ngang, rộng ít nhất 1600px).
   - **Video cho máy tính:** bấm **Chọn video từ Thư viện** → tải video MP4 lên.
   - **Video nhẹ cho điện thoại** (tùy chọn): bản nhỏ hơn cho mạng di động.
4. Mục **Bố cục:** chỉnh độ tối lớp phủ (để chữ dễ đọc), chiều cao banner.
5. Bấm vào chữ trên banner để sửa tiêu đề/mô tả. *(Riêng tiêu đề lớn ở trang chủ là slogan – sửa tại Thông tin khách sạn.)*
6. Bấm **Cập nhật**.

**Chuẩn video tốt nhất:** MP4 (H.264), 10–20 giây, không tiếng, **dưới 5MB**, ngang 1280–1920px. Có thể nén miễn phí bằng HandBrake (preset *Web → Vimeo YouTube 720p30*) hoặc trang <https://www.freeconvert.com/video-compressor>.

Website tự động: hiện ảnh poster trước (trang nhanh), chỉ tải video sau khi trang hiển thị xong, **không** chạy video khi khách bật chế độ giảm chuyển động, tiết kiệm dữ liệu hoặc mạng chậm; luôn có nút tạm dừng video.

## 4. Đổi màu và font chữ

*(Cần tài khoản Quản trị viên.)*

1. **Giao diện → Trình chỉnh sửa → Phong cách** (biểu tượng nửa hình tròn).
2. **Màu sắc → Bảng màu:** bấm vào màu muốn đổi, ví dụ **Xanh ngọc biển (chính)** → chọn màu mới → **Lưu**. Toàn bộ website (nút, liên kết, bộ lọc, form đặt phòng, email) đổi theo.
   - **Vàng nắng** là màu nút “Đặt phòng”.
   - **Cát** là màu nền chính.
3. Muốn thử nhanh một bộ màu khác: trong **Phong cách → Duyệt phong cách**, chọn **Hoàng hôn**.
4. **Kiểu chữ:** chọn **Tiêu đề** hoặc **Văn bản** để đổi font/cỡ chữ. Font có sẵn: Lora (tiêu đề) và Be Vietnam Pro (nội dung), đều hỗ trợ đầy đủ tiếng Việt.
5. Lỡ tay? Bấm biểu tượng **⋮ → Đặt lại** để về mặc định.

## 5. Thêm / sửa phòng, giá, mùa cao điểm

### Sửa phòng
**Phòng** → bấm tên phòng. Bạn sẽ thấy:
- **Tiêu đề** và **nội dung mô tả** (đoạn văn, danh sách).
- **Đoạn trích** (thanh bên phải): mô tả ngắn hiện trên thẻ phòng.
- **Ảnh đại diện** (thanh bên phải): ảnh trên thẻ phòng.
- **Nhóm khách**: tích *Gia đình / Cặp đôi / Nhóm bạn* – dùng cho bộ lọc ở trang Phòng & Giá.
- **Tiện nghi trong phòng**: tích các tiện nghi (có thể thêm tiện nghi mới).
- **Thứ tự** (mục *Thuộc tính trang*): số nhỏ hiện trước. 3 phòng đầu tiên hiện ở trang chủ.
- Hộp **Thông tin phòng** ở dưới cùng: **Giá từ** (chỉ nhập số, ví dụ `1290000`), diện tích, số người lớn/trẻ em tối đa, loại giường, hướng nhìn, **Thư viện ảnh/video thực tế** (bấm *Chọn ảnh / video*, có thể chọn nhiều ảnh cùng lúc), video ngắn.

### Thêm phòng mới
**Phòng → Thêm phòng** → điền như trên → **Đăng**. Phòng mới tự hiện ở trang Phòng & Giá, trang chủ (nếu nằm trong 3 phòng đầu) và ô chọn phòng trong form đặt phòng. Sau đó tạo bản English (mục 8).

### Giá mùa cao điểm
Website nhận **yêu cầu** đặt phòng (khách sạn gọi lại báo giá chính xác), nên không cần bảng giá theo ngày. Cách làm cho mùa cao điểm:
- **Đổi giá “Giá từ”** của phòng khi vào mùa (ví dụ Tết, hè). Giá ở bản English **tự đồng bộ**.
- Thêm một dòng trong mô tả phòng, ví dụ “Giá mùa hè (01/6–31/8) từ 1.590.000đ/đêm”.
- Hoặc tạo một **Ưu đãi** cho mùa thấp điểm (mục 9) – ưu đãi tự ẩn khi hết hạn.

Chữ “Từ … đ / đêm” được định dạng tự động; muốn đổi cách hiển thị (ví dụ “/ phòng / đêm”), sửa nhãn **Giá phòng** tại **Sunny Grove → Nhãn hiển thị**.

## 6. Tiện ích

**Tiện ích** → sửa hoặc **Thêm tiện ích**. Các ô quan trọng:
- **Mô tả ngắn**, **Giờ hoạt động** (ví dụ `06:00 – 20:00 hằng ngày`).
- **Thư viện ảnh / video thực tế**: chọn nhiều ảnh/video để khách thấy rõ thực tế.
- **Video ngắn cho thẻ**: video tự chạy (không tiếng) trên thẻ tiện ích ở trang chủ.
- **Thứ tự**: 4 tiện ích đầu tiên hiện ở trang chủ.

## 7. Thêm địa điểm Khám phá Phú Quốc

**Khám phá Phú Quốc → Thêm địa điểm**

1. Nhập **tên**, **mô tả** (nội dung), **đoạn trích** (mô tả ngắn trên thẻ), **ảnh đại diện**.
2. Hộp **Thông tin địa điểm**:
   - **Khoảng cách (km)**: ví dụ `12` hoặc `1.5`.
   - **Thời gian di chuyển (phút)** và **Phương tiện** (Đi bộ / Ô tô / Xe máy) → website tự hiện “20 phút đi ô tô”.
   - **Dòng so sánh vui** (tùy chọn): ví dụ “Không cần tới Ý?”.
   - **Link Google Maps**: mở Google Maps → tìm địa điểm → **Chia sẻ → Sao chép đường liên kết**.
3. **Nhãn lọc địa điểm** (thanh bên phải): tích *Đi bộ được*, *Dưới 30 phút đi xe*, *Phù hợp trẻ em* cho đúng.
4. **Thứ tự**: quyết định vị trí trên băng chuyền.
5. **Đăng** → địa điểm tự hiện ở băng chuyền trang chủ và trang Khám phá Phú Quốc. Đừng quên bản English.

## 8. Dịch nội dung sang tiếng Anh

Website dùng **Polylang**: mỗi trang/phòng/địa điểm/ưu đãi/bài viết có **2 bản riêng** (Tiếng Việt và English) được liên kết với nhau.

**Tạo bản English cho một mục mới:**
1. Mở danh sách (ví dụ **Phòng**). Cột cờ 🇬🇧 có dấu **+** ở mục chưa dịch.
2. Bấm **+** → WordPress mở trang soạn thảo bản English, đã **sao chép sẵn** ảnh, giá, diện tích, khoảng cách, thư viện ảnh và nhóm.
3. Nhập tiêu đề, nội dung, đoạn trích, loại giường, hướng nhìn… bằng tiếng Anh → **Đăng**.

**Sửa bản English đã có:** bấm biểu tượng ✏️ ở cột cờ 🇬🇧.

**Tự đồng bộ giữa hai bản:** giá, diện tích, số khách, khoảng cách, thời gian, phương tiện, link bản đồ, ảnh, thư viện, ngày ưu đãi, thứ tự, nhóm. **Không đồng bộ (dịch riêng):** tiêu đề, nội dung, loại giường, hướng nhìn, mô tả ngắn, giờ hoạt động, dòng so sánh, giá ưu đãi dạng chữ, điều kiện.

### Trang chủ Tiếng Việt và Home (English) là 2 trang riêng

Trong **Trang** có hai trang: **Trang chủ** (Tiếng Việt) và **Home** (English). Sửa tiêu đề, đoạn giới thiệu, ảnh, nút… ở trang nào thì chỉ trang đó thay đổi – nhớ sửa cả hai.

Các danh sách **Phòng tiêu biểu, Tiện ích, Khám phá Phú Quốc, Ưu đãi** trên trang chủ được **lấy tự động** từ dữ liệu:

| Muốn sửa | Vào menu |
|---|---|
| Tên phòng, giá, ảnh, số khách | **Phòng** → bấm tên phòng |
| Ưu đãi, giá ưu đãi, ngày hết hạn | **Ưu đãi** → bấm tên ưu đãi |
| Hồ bơi, Kids Club, giờ mở cửa | **Tiện ích** |
| Địa điểm, khoảng cách | **Khám phá Phú Quốc** |

**Không sửa các thẻ này ngay trong trang chủ**: ở đó thẻ chỉ để xem trước. Bấm vào thẻ rồi gõ chữ thì sẽ đổi **khuôn mẫu chung của mọi thẻ**, không phải dữ liệu của một phòng.

Khi mở trang chủ trong trình soạn thảo:
- **Trang chủ** chỉ hiện phòng/ưu đãi Tiếng Việt, **Home** chỉ hiện bản English – giống hệt ngoài website.
- Giá, ngày đã được định dạng sẵn (ví dụ “Từ 1.290.000đ / đêm”, “Áp dụng đến 30/04/2027”).
- Ô nào chưa nhập sẽ để trống. Khi bấm vào thẻ, ô đó hiện chữ xám gợi ý, ví dụ “Chưa nhập giá cũ” – đây chỉ là gợi ý trong trình soạn thảo, **khách không thấy**. Muốn điền thì vào menu tương ứng ở bảng trên.
- Khi sửa template trong **Giao diện → Trình chỉnh sửa**, danh sách mẫu hiển thị bằng Tiếng Việt (ngôn ngữ mặc định).

**Các chữ khác:**
- Thông tin khách sạn, nhãn hiển thị, chữ trên form và email đặt phòng: mỗi trang cài đặt đều có **tab Tiếng Việt / English**.
- Menu: **Giao diện → Menu** – mỗi ngôn ngữ một menu (xem mục 13).
- Nhóm khách, tiện nghi, nhãn lọc: vào nhóm (ví dụ **Phòng → Nhóm khách**), bấm **+** ở cột 🇬🇧 để thêm tên tiếng Anh.

## 9. Tạo ưu đãi mới

**Ưu đãi → Thêm ưu đãi**
1. Tiêu đề, nội dung, đoạn trích, ảnh đại diện.
2. Hộp **Thông tin ưu đãi**: **Giá ưu đãi** (ví dụ “4.990.000đ / gia đình” hoặc “Giảm 15%”), **Giá gốc** (hiện gạch ngang), **Nhãn nổi bật** (ví dụ “-15%”), **Áp dụng từ / đến ngày**, **Điều kiện áp dụng**.
3. **Đăng** → ưu đãi hiện ở trang Ưu đãi và trang chủ (3 ưu đãi đầu theo Thứ tự).
4. **Hết hạn tự ẩn:** sau ngày “Áp dụng đến”, ưu đãi tự biến mất khỏi website (vẫn còn trong quản trị để dùng lại – chỉ cần đổi ngày).
5. Nút **“Nhận ưu đãi này”** dẫn khách tới form đặt phòng và tự điền tên ưu đãi vào ô ghi chú.

## 10. Viết bài blog

**Bài viết → Viết bài mới**
1. Nhập tiêu đề, gõ nội dung. Gõ `/` để chèn nhanh: `/tiêu đề`, `/ảnh`, `/danh sách`, `/video`…
2. Thanh bên phải: **Chuyên mục** (Cẩm nang, Ẩm thực…), **Ảnh đại diện** (bắt buộc – hiện trên thẻ bài viết), **Đoạn trích** (1–2 câu tóm tắt).
3. Ảnh trong bài: luôn điền **Văn bản thay thế (alt)** mô tả ảnh – tốt cho người khiếm thị và SEO.
4. **Đăng** → bài tự hiện ở trang Blog. Tạo bản English bằng dấu **+** (mục 8) nếu muốn khách quốc tế đọc.

## 11. Quản lý yêu cầu đặt phòng

Khi khách gửi form:
- Yêu cầu được lưu ở menu **Đặt phòng** (số màu đỏ cạnh menu = số yêu cầu **Mới**).
- Khách sạn nhận email thông báo; khách nhận email “đã nhận yêu cầu” **đúng ngôn ngữ khách đang xem**.

**Quy trình xử lý:**
1. Vào **Đặt phòng → Tất cả yêu cầu**. Dùng bộ lọc **Mới / Đã liên hệ / Đã xác nhận / Đã hủy** phía trên danh sách.
2. Bấm số điện thoại để gọi (trên điện thoại) hoặc xem cách khách muốn được liên hệ (Gọi / Zalo / WhatsApp).
3. Đổi trạng thái nhanh: di chuột vào dòng → bấm **Đã liên hệ**, **Đã xác nhận** hoặc **Đã hủy**. Hoặc tích nhiều dòng → **Hành động hàng loạt → Chuyển sang: …** → **Áp dụng**.
4. Bấm tên khách để xem chi tiết: sửa ngày, phòng, số khách nếu khách đổi ý; ghi **Ghi chú nội bộ** (khách không thấy); **Gửi lại email xác nhận**.
5. Khách đặt qua điện thoại? Bấm **Thêm yêu cầu (đặt qua điện thoại)** để lưu lại cùng một chỗ.
6. **Tải danh sách (CSV/Excel)** để báo cáo hoặc lưu trữ.

**Thanh toán:** form ghi rõ *chưa cần thanh toán – trả tại khách sạn hoặc chuyển khoản đặt cọc*. Khi xác nhận với khách, nhân viên gửi thông tin chuyển khoản qua Zalo/email. (Sau này có thể gắn cổng thanh toán – cần lập trình viên.)

**Không nhận được email?** Mở chi tiết yêu cầu, dòng cuối ghi *Email cho khách sạn: Đã gửi / Gửi lỗi*. Nếu “Gửi lỗi”, cần cài FluentSMTP (xem `docs/PLUGINS.md`). Yêu cầu **không bao giờ bị mất** vì đã lưu trong quản trị.

## 12. Sửa chữ trên form và email

**Đặt phòng → Cài đặt**
- Tab **Chung:** email nhận thông báo (nhiều email: mỗi email một dòng), tên/email người gửi, trang chứa form, hotline/Zalo hiển thị sau khi gửi, giới hạn số khách/số đêm, **chống spam** (số yêu cầu tối đa mỗi giờ, khóa reCAPTCHA tùy chọn).
- Tab **Chữ & email – Tiếng Việt** và **– English:** nhãn từng ô trên form, thông báo cảm ơn, thông báo lỗi, **tiêu đề và nội dung email gửi khách**. Email gửi khách sạn chỉ có ở tab ngôn ngữ mặc định.

Chữ trong ngoặc nhọn như `{ten_khach}`, `{ngay_nhan}`, `{phong}`, `{ma_yeu_cau}`, `{hotline}` sẽ được thay bằng thông tin thật – **giữ nguyên ngoặc nhọn**. Danh sách đầy đủ hiện ngay trên trang cài đặt.

## 13. Menu, nút chat, nhãn hiển thị

**Menu** *(Quản trị viên)*: **Giao diện → Menu**. Có 4 menu: *Menu chính (Tiếng Việt)*, *Main menu (English)*, *Menu chân trang (Tiếng Việt)*, *Footer menu (English)*. Chọn menu → kéo thả để sắp xếp, tích trang bên trái → **Thêm vào menu** → **Lưu menu**. Nên giữ menu chính **tối đa 6 mục** cho dễ dùng.

**Nút chat nổi** (Zalo, WhatsApp, Gọi): bật/tắt và chọn góc trái/phải tại **Sunny Grove → Thông tin khách sạn → Chung**. Số điện thoại lấy từ các ô Zalo/WhatsApp/Hotline.

**Nhãn hiển thị:** **Sunny Grove → Nhãn hiển thị** – các chữ dùng chung như nút “Đặt phòng”, “Xem chi tiết”, “Chỉ đường”, “từ khách sạn”, trang 404, dòng bản quyền… mỗi ngôn ngữ một tab.

**Thêm khối có sẵn vào trang:** trong trình soạn thảo bấm **+** (góc trên trái) → tab **Mẫu** → nhóm **Sunny Grove – Khối có sẵn**: Hero video, Điểm nổi bật, Phòng tiêu biểu, Tiện ích nổi bật, Băng chuyền Khám phá, Ưu đãi, Đánh giá, Lời kêu gọi đặt phòng… Khi tạo **trang mới**, WordPress gợi ý các **Trang mẫu** dựng sẵn.

## 14. Tạo tài khoản cho nhân viên

**Tài khoản → Thêm mới** → điền email, tên đăng nhập → **Vai trò: Biên tập viên khách sạn** → **Thêm người dùng mới**.

Biên tập viên khách sạn **được**: sửa trang, bài viết, phòng, tiện ích, địa điểm, ưu đãi, ảnh; xử lý yêu cầu đặt phòng; sửa Thông tin khách sạn, Nhãn hiển thị, Cài đặt đặt phòng.
**Không được**: cài/xóa plugin, đổi theme, sửa màu/font/menu toàn site, quản lý tài khoản, cài đặt hệ thống.

## 15. Sao lưu và khôi phục

*(Cần plugin UpdraftPlus – xem `docs/PLUGINS.md`.)*

**Sao lưu thủ công** (nên làm trước mỗi lần thay đổi lớn): **Cài đặt → UpdraftPlus Backups → Sao lưu ngay** → tích cả cơ sở dữ liệu và tệp → **Sao lưu ngay**.

**Khôi phục:**
1. **Cài đặt → UpdraftPlus Backups** → kéo xuống **Bản sao lưu hiện có**.
2. Chọn bản sao lưu theo ngày → **Khôi phục**.
3. Tích những phần cần khôi phục (Cơ sở dữ liệu = nội dung & cài đặt; Uploads = ảnh/video; Plugins, Themes) → **Tiếp theo** → **Khôi phục**.
4. Đợi báo “Khôi phục thành công”, đăng nhập lại.

Nếu không vào được trang quản trị: cài WordPress mới, cài UpdraftPlus, kết nối lại Google Drive, bấm **Quét lại bộ nhớ từ xa** rồi khôi phục như trên.

## 16. Danh sách kiểm tra trước khi mở website

- [ ] **Sunny Grove → Thông tin khách sạn:** hotline, Zalo, WhatsApp, email, địa chỉ, bản đồ thật (cả tab English).
- [ ] **Đặt phòng → Cài đặt:** email nhận thông báo thật; email người gửi cùng tên miền; thử gửi 1 yêu cầu VI và 1 yêu cầu EN.
- [ ] Cài **FluentSMTP**, gửi email thử thành công.
- [ ] Thay giá phòng, giá ưu đãi, giá dịch vụ có ghi [CẦN THAY].
- [ ] Thay toàn bộ **ảnh và video minh họa** bằng hình thật của khách sạn (mục 3, 5, 6, 7).
- [ ] Thay 3 **đánh giá mẫu** bằng đánh giá thật hoặc Google Reviews.
- [ ] Xóa ưu đãi mẫu “Hè rực rỡ 2025” (ví dụ ưu đãi hết hạn).
- [ ] Cài LiteSpeed Cache (hoặc WP Super Cache), UpdraftPlus, Wordfence (bật xác thực 2 lớp), Rank Math.
- [ ] **Cài đặt → Đọc:** bỏ tích “Ngăn chặn các công cụ tìm kiếm…”.
- [ ] Thử website trên điện thoại: menu, nút Đặt phòng, nút Zalo/WhatsApp, form.
- [ ] Chạy kiểm tra tốc độ tại <https://pagespeed.web.dev> (mục tiêu điểm Mobile ≥ 80).
