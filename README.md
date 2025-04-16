عالیه! چون در حال ساخت قالب حرفه‌ای "سئوکار" هستی که روی سئو، هوش مصنوعی، تنظیمات پیشرفته، و ویژگی‌های حرفه‌ای تمرکز داره، لیست کامل و دقیق‌تری از فایل‌ها و پوشه‌های پیشنهادی قالب رو برات آماده کردم.


---

ساختار نهایی فایل‌ها و پوشه‌های قالب حرفه‌ای "سئوکار"

فایل‌های اصلی در ریشه قالب:

style.css                  ← اطلاعات قالب و CSS اصلی
rtl.css                    ← استایل راست‌چین
functions.php              ← توابع اصلی و هسته قالب
index.php                  ← قالب اصلی سایت
screenshot.png             ← پیش‌نمایش قالب در پیشخوان
readme.txt                 ← توضیحات قالب
theme.json                 ← تنظیمات عمومی (برای گوتنبرگ)


---

پوشه‌های اصلی سازماندهی شده

/assets/ – منابع استاتیک

/assets/
├── /css/                  ← فایل‌های CSS سفارشی
├── /js/                   ← اسکریپت‌های JS سفارشی (مثل انیمیشن تایپ، لودر)
├── /fonts/                ← فونت‌های سفارشی
├── /images/               ← آیکن‌ها، لوگو، تصاویر قالب
└── /icons/                ← آیکن‌های SVG یا Font Awesome سفارشی

/inc/ – توابع، ساختار قالب و تنظیمات

/inc/
├── setup.php              ← رجیستر کردن منو، پشتیبانی‌ها و ...
├── enqueue.php            ← بارگذاری استایل و اسکریپت
├── theme-options.php      ← تنظیمات پیشرفته قالب (پنل تنظیمات)
├── seo-functions.php      ← توابع سئو حرفه‌ای
├── ai-assistant.php       ← اتصال به API هوش مصنوعی
├── custom-post-types.php  ← تعریف CPT (مثلاً برای مقالات سئو، ابزارها)
├── custom-taxonomies.php  ← دسته‌بندی‌های سفارشی
├── shortcodes.php         ← شورت‌کدهای مفید
├── widgets.php            ← ابزارک‌های اختصاصی
└── analytics.php          ← اتصال به Google Analytics (فایل JSON)

/admin/ – پنل تنظیمات در پیشخوان

/admin/
├── panel.php              ← رابط تنظیمات گرافیکی
├── settings-ui.php        ← ظاهر فرم‌ها و تب‌ها
├── license-manager.php    ← فعال‌سازی قالب
├── api-settings.php       ← کلید اتصال API
└── token-manager.php      ← مدیریت توکن‌های دسترسی

/template-parts/ – اجزای قابل تکرار در صفحات

/template-parts/
├── header/                ← هدرهای مختلف (مثلاً header-default.php)
├── footer/                ← فوترهای مختلف
├── sidebar/               ← سایدبارهای متنوع
├── content/               ← ساختار محتوا (پست، صفحه، ابزار سئو و ...)
└── seo-preview/           ← ویجت پیش‌نمایش اسنیپت گوگل

/templates/ – قالب‌های سفارشی

/templates/
├── page-landing.php       ← صفحه فرود برای معرفی ابزار سئوکار
├── page-contact.php       ← تماس با ما
├── page-about.php         ← درباره ما
├── page-tools.php         ← صفحه ابزارهای سئو
├── page-ai-assistant.php  ← صفحه رابط هوش مصنوعی
└── page-dashboard.php     ← داشبورد سئو سایت

/languages/ – فایل‌های ترجمه

/languages/
├── fa_IR.po
├── fa_IR.mo
└── seokar.pot

/woocommerce/ – برای فروشگاه (در صورت استفاده از ووکامرس)

/woocommerce/
├── archive-product.php
├── single-product.php
├── cart.php
├── checkout.php
└── myaccount.php

/acf-json/ – تنظیمات ACF برای فیلدهای سفارشی

/acf-json/
├── group-theme-options.json
├── group-seo-settings.json
└── group-ai-settings.json


---

فایل‌های قالب‌بندی استاندارد

header.php
footer.php
sidebar.php
single.php
single-post.php
page.php
archive.php
search.php
404.php
comments.php
category.php
tag.php
author.php
home.php
front-page.php


---
