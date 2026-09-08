-- Cole no SQL Editor do Supabase (pode executar de novo; é idempotente)

create table if not exists public.products (
  gtin char(14) primary key,
  name text not null,
  brand text,
  description text,
  image_url text,
  ingredients text,
  allergens text,
  origin text,
  manufacturer text,
  website_url text,
  recycling_notes text,
  extra_json jsonb,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

alter table public.products add column if not exists ingredients text;
alter table public.products add column if not exists allergens text;
alter table public.products add column if not exists origin text;
alter table public.products add column if not exists manufacturer text;
alter table public.products add column if not exists website_url text;
alter table public.products add column if not exists recycling_notes text;
alter table public.products add column if not exists extra_json jsonb;
alter table public.products add column if not exists updated_at timestamptz not null default now();
alter table public.products add column if not exists sale_type text not null default 'weight';
alter table public.products add column if not exists package_quantity integer;
alter table public.products add column if not exists net_weight_g numeric(12, 3);

alter table public.products enable row level security;

drop policy if exists products_public_read on public.products;
create policy products_public_read
  on public.products
  for select
  to anon, authenticated
  using (true);

create table if not exists public.nutrition_facts (
  gtin char(14) primary key references public.products(gtin) on delete cascade,
  serving_size text not null,
  servings_per_package text,
  serving_grams numeric(10, 2),
  serving_unit text not null default 'g',
  energy_kcal numeric(10, 2),
  energy_kj numeric(10, 2),
  carbohydrates_g numeric(10, 2),
  sugars_g numeric(10, 2),
  added_sugars_g numeric(10, 2),
  protein_g numeric(10, 2),
  total_fat_g numeric(10, 2),
  saturated_fat_g numeric(10, 2),
  trans_fat_g numeric(10, 2),
  fiber_g numeric(10, 2),
  sodium_mg numeric(10, 2),
  updated_at timestamptz not null default now()
);

alter table public.nutrition_facts add column if not exists serving_grams numeric(10, 2);
alter table public.nutrition_facts add column if not exists serving_unit text not null default 'g';

alter table public.nutrition_facts enable row level security;

drop policy if exists nutrition_facts_public_read on public.nutrition_facts;
create policy nutrition_facts_public_read
  on public.nutrition_facts
  for select
  to anon, authenticated
  using (true);

-- Seed do exemplo GS1 Digital Link
insert into public.products (
  gtin, name, brand, description, image_url,
  ingredients, allergens, origin, manufacturer, website_url, recycling_notes, extra_json
)
values (
  '07893336004902',
  'Produto de exemplo Digitag',
  'Digitag',
  'Cadastro de demonstração associado ao GTIN do Digital Link. Substitua estes dados pelo produto real no painel /admin ou no Table Editor do Supabase.',
  null,
  'Ingredientes de exemplo. Atualize no cadastro.',
  'Verifique a embalagem.',
  'Brasil',
  'Digitag',
  null,
  'Descarte a embalagem conforme a coleta seletiva do município.',
  '{"categoria":"Exemplo"}'::jsonb
)
on conflict (gtin) do nothing;
