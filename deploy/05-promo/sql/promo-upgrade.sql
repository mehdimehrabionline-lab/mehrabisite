-- ==========================================================================
--  سیستم کد تخفیف و کد معرف  —  در Supabase ← SQL Editor اجرا کن
--  • فقط جدول جدید می‌سازد و سه ستون «اختیاری» به payments اضافه می‌کند (چیزی حذف/عوض نمی‌شود)
--  • چند بار اجرا کردنش هم مشکلی ندارد
--  • کدها و شماره‌ی معرف‌ها برای عموم (anon) خوانا نیست؛ فقط ادمین پنل و سرور (service key)
-- ==========================================================================

create table if not exists public.promo_codes (
  id uuid primary key default gen_random_uuid(),
  created_at timestamptz not null default now(),
  code text not null unique,
  kind text not null default 'discount' check (kind in ('discount','referral')),
  -- تخفیف خریدار
  discount_type text not null default 'percent' check (discount_type in ('percent','amount')),
  discount_value bigint not null default 0,
  -- محدودیت‌ها
  course_id uuid,
  max_uses integer,
  used_count integer not null default 0,
  expires_at timestamptz,
  is_active boolean not null default true,
  -- فقط برای کد معرف
  referrer_name text,
  referrer_phone text,
  commission_type text check (commission_type in ('percent','amount')),
  commission_value bigint,
  note text
);

create table if not exists public.promo_redemptions (
  id uuid primary key default gen_random_uuid(),
  created_at timestamptz not null default now(),
  payment_id uuid not null unique,
  code_id uuid,
  code text not null,
  kind text,
  referrer_name text,
  referrer_phone text,
  buyer_user_id uuid,
  course_id uuid,
  original_amount bigint,
  discount_amount bigint default 0,
  final_amount bigint,
  commission_amount bigint not null default 0,
  commission_status text not null default 'pending' check (commission_status in ('pending','paid')),
  paid_at timestamptz,
  sms_sent boolean default false
);
create index if not exists promo_redemptions_code_idx on public.promo_redemptions (code);
create index if not exists promo_redemptions_created_idx on public.promo_redemptions (created_at desc);

alter table public.payments add column if not exists promo_code text;
alter table public.payments add column if not exists original_amount bigint;
alter table public.payments add column if not exists discount_amount bigint default 0;

alter table public.promo_codes enable row level security;
alter table public.promo_redemptions enable row level security;
do $$ begin
  if not exists (select 1 from pg_policies where tablename='promo_codes' and policyname='admin_all_promo_codes') then
    create policy admin_all_promo_codes on public.promo_codes for all to authenticated using (true) with check (true);
  end if;
  if not exists (select 1 from pg_policies where tablename='promo_redemptions' and policyname='admin_all_promo_redemptions') then
    create policy admin_all_promo_redemptions on public.promo_redemptions for all to authenticated using (true) with check (true);
  end if;
end $$;
