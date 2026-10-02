-- ==========================================================================
--  ارتقای پنل مدیریت v2  —  در Supabase ← SQL Editor اجرا کن
--  • بی‌خطر است: فقط جدول‌های جدید می‌سازد و اجازه‌ی خواندن/ویرایش به «کاربران وارد‌شده‌ی ادمین» می‌دهد
--  • چیزی را حذف یا تغییر داده‌ی موجود نمی‌دهد؛ چند بار اجرا کردنش هم مشکلی ندارد
--  • سایت عمومی (هنرجوها) با anon key کار می‌کند و از این اجازه‌ها تأثیر نمی‌گیرد
--  توجه: مثل بقیه‌ی جدول‌های پروژه، «کاربر وارد‌شده» = ادمین (ثبت‌نام عمومی در Supabase Auth خاموش است).
-- ==========================================================================

-- ۱) خواندن تراکنش‌ها و نشست‌ها در پنل (اگر قبلاً policy داشتند، تکراری ساخته نمی‌شود)
alter table public.payments enable row level security;
alter table public.user_sessions enable row level security;

do $$ begin
  if not exists (select 1 from pg_policies where tablename='payments' and policyname='admin_all_payments') then
    create policy admin_all_payments on public.payments for all to authenticated using (true) with check (true);
  end if;
  if not exists (select 1 from pg_policies where tablename='user_sessions' and policyname='admin_all_user_sessions') then
    create policy admin_all_user_sessions on public.user_sessions for all to authenticated using (true) with check (true);
  end if;
end $$;

-- ۲) گزارش فعالیت مدیران
create table if not exists public.admin_audit_log (
  id uuid primary key default gen_random_uuid(),
  created_at timestamptz not null default now(),
  admin_email text,
  action text not null,
  entity text,
  entity_id text,
  detail text
);
create index if not exists admin_audit_log_created_idx on public.admin_audit_log (created_at desc);
alter table public.admin_audit_log enable row level security;
do $$ begin
  if not exists (select 1 from pg_policies where tablename='admin_audit_log' and policyname='admin_all_audit') then
    create policy admin_all_audit on public.admin_audit_log for all to authenticated using (true) with check (true);
  end if;
end $$;

-- ۳) یادداشت‌های داخلی روی هنرجو
create table if not exists public.student_notes (
  id uuid primary key default gen_random_uuid(),
  created_at timestamptz not null default now(),
  user_id uuid not null,
  note text not null,
  admin_email text
);
create index if not exists student_notes_user_idx on public.student_notes (user_id);
alter table public.student_notes enable row level security;
do $$ begin
  if not exists (select 1 from pg_policies where tablename='student_notes' and policyname='admin_all_notes') then
    create policy admin_all_notes on public.student_notes for all to authenticated using (true) with check (true);
  end if;
end $$;

-- ۴) نقش‌ها (فقط وقتی همکار/پشتیبان اضافه کنی لازم می‌شود؛ بدون ردیف، تو «مدیر کل» هستی)
create table if not exists public.admin_roles (
  id uuid primary key default gen_random_uuid(),
  created_at timestamptz not null default now(),
  email text not null unique,
  role text not null check (role in ('owner','support','editor'))
);
alter table public.admin_roles enable row level security;
do $$ begin
  if not exists (select 1 from pg_policies where tablename='admin_roles' and policyname='admin_all_roles') then
    create policy admin_all_roles on public.admin_roles for all to authenticated using (true) with check (true);
  end if;
end $$;

-- ۵) (اختیاری) ستون زمان ساخت نشست‌ها برای نمایش «زمان ورود» در پروفایل هنرجو
alter table public.user_sessions add column if not exists created_at timestamptz default now();
