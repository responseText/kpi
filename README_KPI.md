# ระบบตัวชี้วัดผลงาน (KPI) — โรงพยาบาลทองแสนขัน

ระบบติดตามตัวชี้วัดผลงาน 3 ระดับ (โรงพยาบาล / จังหวัด / กระทรวง) บน **Laravel 12 + Tailwind CSS v4 + MySQL/MariaDB (`coretsk`)**
รองรับการแสดงผลทั้งมือถือ แท็บเล็ต คอมพิวเตอร์ และจอ Monitor (ทีวี LCD)

## คุณสมบัติ
- โครงสร้างแผน: **ยุทธศาสตร์ → กลยุทธ์ → ตัวชี้วัด** (ยุทธศาสตร์เปลี่ยนรายปี พ.ศ.)
- ตัวชี้วัดแบบ **ปี พ.ศ.** หรือ **ปีงบประมาณ** × **รายปี** หรือ **รายไตรมาส** (ระบบสร้างช่วงเวลาให้อัตโนมัติ)
- ค่าเป้าหมาย/เกณฑ์รายช่วง (`> ≥ < ≤ ≠ =` และ ผ่าน/ไม่ผ่าน) + ประเมินผ่าน/ไม่ผ่านอัตโนมัติ
- ผู้รับผิดชอบตัวชี้วัด (หลายคน), ผู้ตรวจสอบกลยุทธ์, ผู้รับผิดชอบ/ผู้กำหนดตัวชี้วัดแต่ละระดับ
- จัดการสิทธิ์รายบุคคลผ่านตาราง `users_on_menu` (ดู/เพิ่ม/แก้ไข/ลบ ต่อเมนู)
- แดชบอร์ด + กราฟ (Chart.js) + โหมด Monitor เต็มจอ auto-refresh + รายงานพิมพ์ได้

## สถาปัตยกรรม
- **Repository pattern**: `app/Repositories/Contracts` (interface) ↔ `app/Repositories/Eloquent` (implementation) ผูกใน `app/Providers/RepositoryServiceProvider.php`
- **Services**: `PeriodCalculator` (คำนวณช่วงเวลา), `KpiEvaluator` (ประเมินเกณฑ์), `PermissionService` (สิทธิ์ + แถบนำทาง)
- **Security**: login throttling, CSRF, Eloquent (ไม่มี raw SQL), Blade escaping, FormRequest validation, ตรวจสิทธิ์ทุก action ด้วย middleware `menu:<code>,<action>`, soft deletes

## ฐานข้อมูล
ใช้ฐานข้อมูล `coretsk` เดิม (มีระบบความเสี่ยง/เอกสารคุณภาพอยู่แล้ว)
- ตารางใหม่: `menus`, `users_on_menu`, `kpi_strategies`, `kpi_sub_strategies`, `kpi_sub_strategy_reviewers`, `kpi_indicators`, `kpi_indicator_owners`, `kpi_targets`, `kpi_results`, `kpi_level_managers`
- ใช้ตารางเดิม: `users` (login ด้วย **ชื่อผู้ใช้ = users.name**), `employee` (ข้อมูลเจ้าหน้าที่), `division`/`subdivision`/`positions`/`prefix`

## ติดตั้ง / รัน
```bash
composer install
npm install
npm run build          # หรือ npm run dev ระหว่างพัฒนา

php artisan migrate    # สร้างตาราง kpi_* (idempotent ไม่ชนตารางเดิม)
php artisan db:seed    # สร้างเมนู + ให้สิทธิ์เต็มแก่ admin (id 1) และ dogtorart (id 2)

# (ทางเลือก) ข้อมูลตัวอย่างปี 2569
php artisan db:seed --class=KpiDemoSeeder
```
เปิดใช้งานผ่าน XAMPP ที่ `http://localhost/kpi/public` (ตั้ง `APP_URL` ไว้แล้วใน `.env`)
หรือระหว่างพัฒนา: `php artisan serve` แล้วตั้ง `APP_URL=http://127.0.0.1:8000` ชั่วคราว

## การให้สิทธิ์ผู้ใช้
1. เข้าเมนู **สิทธิ์ผู้ใช้งาน** (ต้องมีสิทธิ์ `kpi.permission`)
2. เลือกผู้ใช้ → ติ๊ก ดู/เพิ่ม/แก้ไข/ลบ ในแต่ละเมนู → บันทึก

เมนูของระบบ (`menus.system = 'kpi'`): `kpi.dashboard, kpi.strategy, kpi.sub_strategy, kpi.indicator, kpi.target, kpi.result, kpi.level_manager, kpi.report, kpi.permission`
> ตาราง `menus`/`users_on_menu` ออกแบบให้ต่อยอดระบบอื่นได้ด้วยการเพิ่มแถวที่ `system` ใหม่

## ทดสอบ
```bash
php artisan test        # 8 tests (รวม end-to-end ครบ flow + ตรวจสิทธิ์ 403)
```
หมายเหตุ: `phpunit.xml` ตั้ง `APP_URL=http://localhost` สำหรับการทดสอบ (เพราะ test client ผนวก path จาก APP_URL)

## หมายเหตุก่อนขึ้นใช้งานจริง
- ตั้ง `APP_DEBUG=false` และ `APP_ENV=production`
- รัน `php artisan config:cache route:cache view:cache`
- ลบข้อมูลตัวอย่าง: `KpiStrategy::where('code','like','DEMO%')->forceDelete();` (ลบลูกตามมาด้วย cascade)
