<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = require_auth();
$project = require_project();
$db = db();

$stmt = $db->query('SELECT * FROM tnved_codes ORDER BY code');
$tnved_codes = $stmt->fetchAll();

$currencies = fetch_reference_values('currency');
if (!$currencies) {
    $currencies = [
        ['code' => 'RUB', 'label' => 'RUB'],
        ['code' => 'USD', 'label' => 'USD'],
        ['code' => 'EUR', 'label' => 'EUR'],
    ];
}

$suggested_tnved = null;
$stmt = $db->prepare(
    'SELECT c.tnved_code_id, t.code, t.description
     FROM ddp_calculations c
     JOIN tnved_codes t ON t.id = c.tnved_code_id
     WHERE c.project_id = :project_id
     ORDER BY c.created_at DESC
     LIMIT 1'
);
$stmt->execute([':project_id' => $project['id']]);
$suggested_tnved = $stmt->fetch() ?: null;

$calc_id = get_int($_GET, 'calc_id', 0);
$calc_result = null;
if ($calc_id > 0) {
    $stmt = $db->prepare(
        'SELECT c.*, t.code AS tnved_code, t.description AS tnved_description
         FROM ddp_calculations c
         LEFT JOIN tnved_codes t ON t.id = c.tnved_code_id
         WHERE c.id = :id AND c.project_id = :project_id'
    );
    $stmt->execute([':id' => $calc_id, ':project_id' => $project['id']]);
    $calc_result = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cost = get_float($_POST, 'cost');
    $freight = get_float($_POST, 'freight');
    $insurance = get_float($_POST, 'insurance');
    $other_costs = get_float($_POST, 'other_costs');
    $currency = get_string($_POST, 'currency', 'RUB');
    $fx_rate = max(0.0001, get_float($_POST, 'fx_rate', 1));
    $tnved_code_id = get_int($_POST, 'tnved_code_id', 0);

    if ($tnved_code_id <= 0) {
        set_flash('error', 'Выберите код ТН ВЭД.');
        redirect('ddp_new.php?project_id=' . $project['id']);
    }

    $stmt = $db->prepare('SELECT * FROM tnved_codes WHERE id = :id');
    $stmt->execute([':id' => $tnved_code_id]);
    $tnved = $stmt->fetch();
    if (!$tnved) {
        set_flash('error', 'Код ТН ВЭД не найден.');
        redirect('ddp_new.php?project_id=' . $project['id']);
    }

    $customs_value_foreign = $cost + $freight + $insurance + $other_costs;
    $customs_value = round_money($customs_value_foreign * $fx_rate);
    $duty_rate = (float) $tnved['duty_rate'];
    $vat_rate = (float) $tnved['vat_rate'];
    $duty_amount = round_money($customs_value * $duty_rate / 100);
    $vat_base = $customs_value + $duty_amount;
    $vat_amount = round_money($vat_base * $vat_rate / 100);
    $total_amount = round_money($customs_value + $duty_amount + $vat_amount);

    $breakdown = [
        'currency' => $currency,
        'fx_rate' => $fx_rate,
        'customs_value_foreign' => $customs_value_foreign,
        'customs_value' => $customs_value,
        'duty_rate' => $duty_rate,
        'duty_amount' => $duty_amount,
        'vat_rate' => $vat_rate,
        'vat_base' => $vat_base,
        'vat_amount' => $vat_amount,
        'total' => $total_amount,
    ];

    $stmt = $db->prepare(
        'INSERT INTO ddp_calculations
        (project_id, user_id, tnved_code_id, cost, freight, insurance, other_costs, currency, fx_rate,
         duty_rate, vat_rate, customs_value, duty_amount, vat_amount, total_amount, breakdown_text, status, created_at)
         VALUES (:project_id, :user_id, :tnved_code_id, :cost, :freight, :insurance, :other_costs, :currency, :fx_rate,
         :duty_rate, :vat_rate, :customs_value, :duty_amount, :vat_amount, :total_amount, :breakdown_text, :status, :created_at)'
    );
    $stmt->execute([
        ':project_id' => $project['id'],
        ':user_id' => $user['id'],
        ':tnved_code_id' => $tnved_code_id,
        ':cost' => $cost,
        ':freight' => $freight,
        ':insurance' => $insurance,
        ':other_costs' => $other_costs,
        ':currency' => $currency,
        ':fx_rate' => $fx_rate,
        ':duty_rate' => $duty_rate,
        ':vat_rate' => $vat_rate,
        ':customs_value' => $customs_value,
        ':duty_amount' => $duty_amount,
        ':vat_amount' => $vat_amount,
        ':total_amount' => $total_amount,
        ':breakdown_text' => json_encode($breakdown, JSON_UNESCAPED_UNICODE),
        ':status' => 'confirmed',
        ':created_at' => date('Y-m-d H:i:s'),
    ]);
    $new_calc_id = (int) $db->lastInsertId();

    log_history((int) $project['id'], (int) $user['id'], 'Создан расчёт DDP', [
        'calc_id' => $new_calc_id,
        'tnved_code' => $tnved['code'],
        'total' => $total_amount,
    ]);

    set_flash('success', 'Расчёт сохранён. Результат воспроизводим и привязан к проекту.');
    redirect('ddp_new.php?project_id=' . $project['id'] . '&calc_id=' . $new_calc_id);
}

$selected_tnved_id = get_int($_GET, 'tnved_id', 0);
render_header('Новый расчёт DDP', $user, 'ddp', $project);
?>
<div class="grid grid-2">
    <div class="card">
        <h3>Входные параметры</h3>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-row inline-2">
                <div>
                    <label for="cost">Стоимость товара</label>
                    <input type="number" step="0.01" id="cost" name="cost" required>
                </div>
                <div>
                    <label for="freight">Фрахт</label>
                    <input type="number" step="0.01" id="freight" name="freight" required>
                </div>
            </div>
            <div class="form-row inline-2">
                <div>
                    <label for="insurance">Страхование</label>
                    <input type="number" step="0.01" id="insurance" name="insurance" required>
                </div>
                <div>
                    <label for="other_costs">Прочие расходы</label>
                    <input type="number" step="0.01" id="other_costs" name="other_costs" required>
                </div>
            </div>
            <div class="form-row inline-2">
                <div>
                    <label for="currency">Валюта</label>
                    <select id="currency" name="currency" required>
                        <?php foreach ($currencies as $currency): ?>
                            <option value="<?= e($currency['code']) ?>"><?= e($currency['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="fx_rate">Курс к RUB</label>
                    <input type="number" step="0.0001" id="fx_rate" name="fx_rate" value="1" required>
                </div>
            </div>
            <div class="form-row">
                <label for="tnved_code_id">ТН ВЭД</label>
                <select id="tnved_code_id" name="tnved_code_id" required>
                    <option value="">Выберите код</option>
                    <?php foreach ($tnved_codes as $code): ?>
                        <option value="<?= (int) $code['id'] ?>" <?= $selected_tnved_id === (int) $code['id'] ? 'selected' : '' ?>>
                            <?= e($code['code']) ?> — <?= e($code['description']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($suggested_tnved): ?>
                    <p class="notice">
                        Предложение: <?= e($suggested_tnved['code']) ?> — <?= e($suggested_tnved['description']) ?>
                        (история подтверждённых расчётов по проекту).
                        <a class="link-muted" href="ddp_new.php?project_id=<?= (int) $project['id'] ?>&tnved_id=<?= (int) $suggested_tnved['tnved_code_id'] ?>">
                            Использовать
                        </a>
                    </p>
                <?php endif; ?>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Рассчитать и сохранить</button>
            </div>
        </form>
    </div>
    <div class="card">
        <h3>Правила расчёта</h3>
        <p class="muted">
            Формулы фиксированы: таможенная стоимость = сумма входных затрат, умноженная на курс.
            Пошлина и НДС рассчитываются от таможенной стоимости с фиксированным округлением до 2 знаков.
        </p>
        <p class="muted">
            Результат хранится вместе с исходными параметрами, ставками и курсом — полностью воспроизводим.
        </p>
    </div>
</div>

<?php if ($calc_result): ?>
    <div class="section-title">Результат расчёта</div>
    <div class="card">
        <h3>Итоговая сумма: <?= e(format_money((float) $calc_result['total_amount'], 'RUB')) ?></h3>
        <p class="muted">ТН ВЭД: <?= e($calc_result['tnved_code'] ?? '—') ?> — <?= e($calc_result['tnved_description'] ?? '') ?></p>
        <table class="table">
            <tbody>
                <tr>
                    <td>Таможенная стоимость (RUB)</td>
                    <td><?= e(format_money((float) $calc_result['customs_value'], 'RUB')) ?></td>
                </tr>
                <tr>
                    <td>Пошлина (<?= e($calc_result['duty_rate']) ?>%)</td>
                    <td><?= e(format_money((float) $calc_result['duty_amount'], 'RUB')) ?></td>
                </tr>
                <tr>
                    <td>НДС (<?= e($calc_result['vat_rate']) ?>%)</td>
                    <td><?= e(format_money((float) $calc_result['vat_amount'], 'RUB')) ?></td>
                </tr>
            </tbody>
        </table>
        <div class="actions">
            <form method="post" action="bookmarks.php">
                <?= csrf_field() ?>
                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                <input type="hidden" name="item_type" value="calculation">
                <input type="hidden" name="item_ref" value="<?= (int) $calc_result['id'] ?>">
                <input type="hidden" name="title" value="Расчёт DDP #<?= (int) $calc_result['id'] ?>">
                <input type="hidden" name="scope" value="project">
                <button class="btn btn-secondary" type="submit">Добавить в закладки</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php render_footer(); ?>
