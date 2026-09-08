<?php

declare(strict_types=1);

namespace App;

final class AdminController
{
    public static function handle(string $path): void
    {
        AdminAuth::start();
        $method = Http::method();

        if ($path === '/admin/logout' && $method === 'POST') {
            if (!AdminAuth::csrfOk(Http::post('csrf'))) {
                http_response_code(400);
                self::render('admin-login', [
                    'error' => 'Sessão inválida. Tente de novo.',
                    'title' => 'Entrar',
                ]);
                return;
            }
            AdminAuth::logout();
            Http::redirect('/admin/login');
        }

        if ($path === '/admin/login') {
            if (AdminAuth::check()) {
                Http::redirect('/admin');
            }
            if ($method === 'POST') {
                if (!AdminAuth::configured()) {
                    self::render('admin-login', [
                        'error' => 'Defina APP_ADMIN_TOKEN no arquivo .env.',
                        'title' => 'Entrar',
                    ]);
                    return;
                }
                if (AdminAuth::attempt(Http::post('token'))) {
                    Http::redirect('/admin');
                }
                self::render('admin-login', [
                    'error' => 'Token incorreto.',
                    'title' => 'Entrar',
                ]);
                return;
            }
            self::render('admin-login', [
                'error' => AdminAuth::configured() ? null : 'Defina APP_ADMIN_TOKEN no arquivo .env.',
                'title' => 'Entrar',
            ]);
            return;
        }

        if (!AdminAuth::check()) {
            Http::redirect('/admin/login');
        }

        $repo = new ProductRepository();

        if ($path === '/admin' && $method === 'GET') {
            $products = $repo->all();
            self::render('admin-list', [
                'products' => $products,
                'dbError' => $repo->lastError(),
                'notice' => isset($_GET['saved']) ? 'Produto gravado.' : (isset($_GET['deleted']) ? 'Produto excluído.' : null),
                'title' => 'Cadastro',
            ]);
            return;
        }

        if ($path === '/admin/new' && $method === 'GET') {
            self::render('admin-form', [
                'product' => self::emptyProduct(),
                'error' => null,
                'title' => 'Novo produto',
                'isNew' => true,
            ]);
            return;
        }

        if (preg_match('#^/admin/edit/([0-9]{8,14})$#', $path, $m)) {
            $gtin = str_pad($m[1], 14, '0', STR_PAD_LEFT);
            $product = $repo->findByGtin($gtin);
            if ($product === null) {
                http_response_code(404);
                self::render('admin-list', [
                    'products' => $repo->all(),
                    'dbError' => $repo->lastError() ?: 'Produto não encontrado.',
                    'notice' => null,
                    'title' => 'Cadastro',
                ]);
                return;
            }
            self::render('admin-form', [
                'product' => $product,
                'error' => null,
                'title' => 'Editar produto',
                'isNew' => false,
            ]);
            return;
        }

        if ($path === '/admin/save' && $method === 'POST') {
            if (!AdminAuth::csrfOk(Http::post('csrf'))) {
                http_response_code(400);
                self::render('admin-form', [
                    'product' => self::fromPost(),
                    'error' => 'Sessão inválida. Tente de novo.',
                    'title' => 'Produto',
                    'isNew' => Http::post('is_new') === '1',
                ]);
                return;
            }

            $parsed = self::parseForm();
            if ($parsed['error'] !== null) {
                self::render('admin-form', [
                    'product' => $parsed['data'],
                    'error' => $parsed['error'],
                    'title' => 'Produto',
                    'isNew' => Http::post('is_new') === '1',
                ]);
                return;
            }

            if (!$repo->save($parsed['data'])) {
                self::render('admin-form', [
                    'product' => $parsed['data'],
                    'error' => $repo->lastError() ?: 'Não foi possível gravar.',
                    'title' => 'Produto',
                    'isNew' => Http::post('is_new') === '1',
                ]);
                return;
            }

            Http::redirect('/admin?saved=1');
        }

        if ($path === '/admin/delete' && $method === 'POST') {
            if (!AdminAuth::csrfOk(Http::post('csrf'))) {
                http_response_code(400);
                Http::redirect('/admin');
            }
            $gtin = preg_replace('/\D/', '', Http::post('gtin'));
            if (!is_string($gtin) || $gtin === '') {
                Http::redirect('/admin');
            }
            $gtin = str_pad($gtin, 14, '0', STR_PAD_LEFT);
            $repo->delete($gtin);
            Http::redirect('/admin?deleted=1');
        }

        http_response_code(404);
        echo 'Não encontrado';
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyProduct(): array
    {
        return [
            'gtin' => '',
            'name' => '',
            'brand' => '',
            'description' => '',
            'image_url' => '',
            'ingredients' => '',
            'allergens' => '',
            'origin' => '',
            'manufacturer' => '',
            'website_url' => '',
            'recycling_notes' => '',
            'extra_json' => '',
            'sale_type' => 'weight',
            'package_quantity' => '',
            'net_weight_g' => '',
            'nutrition' => NutritionFields::empty(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fromPost(): array
    {
        $data = self::emptyProduct();
        foreach (array_keys($data) as $key) {
            if ($key === 'nutrition') {
                continue;
            }
            $data[$key] = Http::post($key);
        }
        $nutrition = NutritionFields::empty();
        $posted = Http::postArray('nutrition');
        foreach ($nutrition as $key => $_) {
            if (isset($posted[$key])) {
                $nutrition[$key] = $posted[$key];
            }
        }
        $data['nutrition'] = $nutrition;
        return $data;
    }

    /**
     * @return array{data: array<string, mixed>, error: string|null}
     */
    private static function parseForm(): array
    {
        $data = self::fromPost();
        $digits = preg_replace('/\D/', '', (string) $data['gtin']);
        if (!is_string($digits)) {
            $digits = '';
        }
        if (strlen($digits) < 8 || strlen($digits) > 14) {
            return ['data' => $data, 'error' => 'Informe um GTIN com 8 a 14 dígitos.'];
        }
        $data['gtin'] = str_pad($digits, 14, '0', STR_PAD_LEFT);

        if ($data['name'] === '') {
            return ['data' => $data, 'error' => 'Informe o nome do produto.'];
        }

        $data['sale_type'] = $data['sale_type'] === 'unit' ? 'unit' : 'weight';
        if ($data['sale_type'] === 'unit') {
            $qty = preg_replace('/\D/', '', (string) $data['package_quantity']);
            if (!is_string($qty) || $qty === '' || (int) $qty < 1) {
                return ['data' => $data, 'error' => 'Informe a quantidade na embalagem para produto vendido por unidade.'];
            }
            $data['package_quantity'] = (string) (int) $qty;
            $data['net_weight_g'] = '';
        } else {
            $data['package_quantity'] = '';
            $net = self::parseDecimal((string) $data['net_weight_g']);
            if ($net === false) {
                return ['data' => $data, 'error' => 'Peso de referência inválido.'];
            }
            $data['net_weight_g'] = $net === null ? '' : $net;
        }

        $json = (string) $data['extra_json'];
        if ($json !== '') {
            json_decode($json);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['data' => $data, 'error' => 'JSON adicional inválido.'];
            }
        }

        $nutrition = $data['nutrition'];
        if (NutritionFields::hasValues($nutrition)) {
            $grams = self::parseDecimal((string) $nutrition['serving_grams']);
            if ($grams === false || $grams === null || (float) $grams <= 0) {
                return ['data' => $data, 'error' => 'Informe a quantidade da porção (g ou ml) para gravar a tabela nutricional.'];
            }
            $nutrition['serving_grams'] = $grams;
            $unit = strtolower((string) $nutrition['serving_unit']);
            $nutrition['serving_unit'] = $unit === 'ml' ? 'ml' : 'g';
            $nutrition['serving_size'] = $grams . ' ' . $nutrition['serving_unit'];
            foreach (array_keys(NutritionFields::NUMBERS) as $key) {
                $parsed = self::parseDecimal((string) $nutrition[$key]);
                if ($parsed === false) {
                    return ['data' => $data, 'error' => 'Valor inválido em ' . NutritionFields::NUMBERS[$key] . '.'];
                }
                $nutrition[$key] = $parsed === null ? '' : $parsed;
            }
            $data['nutrition'] = $nutrition;
        }

        return ['data' => $data, 'error' => null];
    }

    /**
     * @return string|null|false null empty, false invalid, string normalized
     */
    private static function parseDecimal(string $raw)
    {
        $raw = trim(str_replace(' ', '', $raw));
        if ($raw === '') {
            return null;
        }
        $raw = str_replace(',', '.', $raw);
        if (!is_numeric($raw)) {
            return false;
        }
        return $raw;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private static function render(string $template, array $vars): void
    {
        $appName = Env::get('APP_NAME', 'Smart Label Urano');
        $logoUrl = Env::get('APP_LOGO_URL');
        $csrf = AdminAuth::csrfToken();
        extract($vars, EXTR_SKIP);
        $inner = dirname(__DIR__) . '/templates/' . $template . '.php';
        require dirname(__DIR__) . '/templates/admin-layout.php';
    }
}
