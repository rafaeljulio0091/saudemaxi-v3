import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
const { chromium } = await import(
    process.env.PLAYWRIGHT_MODULE || 'playwright'
);
const base = process.env.UI_BASE_URL || 'http://127.0.0.1:8097';
const output = process.env.UI_OUTPUT || '/tmp/healthcare-ui';
await fs.mkdir(output, { recursive: true });
const browser = await chromium.launch({
    headless: true,
    ...(process.env.CHROMIUM_PATH
        ? { executablePath: process.env.CHROMIUM_PATH }
        : {}),
});
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors = [];
page.on('pageerror', (error) => errors.push(error.message));
async function scenario(profile, tenant = 'cetid', network = 'normal') {
    await page.goto(base + '/demonstracao');
    await page.waitForLoadState('networkidle');
    await page.getByLabel('Cenário de cliente').selectOption(tenant);
    await page.getByLabel('Perfil demonstrativo').selectOption(profile);
    await page.getByLabel('Estado dos serviços').selectOption(network);
    await page.getByRole('button', { name: /Explorar demonstração/ }).click();
    await page.waitForURL(
        profile === 'manager' ? '**/gestor/painel' : '**/inicio',
    );
    await page.waitForLoadState('networkidle');
}
async function visit(path, width, screenshot = false) {
    await page.setViewportSize({ width, height: 1000 });
    const response = await page.goto(base + '/demonstracao' + path);
    assert.equal(response.status(), 200, path);
    await page.waitForLoadState('networkidle');
    await page.locator('main h1').waitFor();
    assert.equal(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= innerWidth + 1,
        ),
        true,
        `Overflow ${path} at ${width}`,
    );
    assert.equal(
        await page.locator('dialog[open]').count(),
        0,
        `Unexpected modal ${path}`,
    );
    if (path === '/gestor/painel') {
        const bottoms = await page
            .locator('.sm-hour-chart > div > div')
            .evaluateAll((bars) =>
                bars.map((bar) => bar.getBoundingClientRect().bottom),
            );
        assert.equal(bottoms.length, 24);
        assert.ok(
            Math.max(...bottoms) - Math.min(...bottoms) <= 1,
            'Chart bars must share a baseline',
        );
    }
    if (screenshot)
        await page.screenshot({
            path: `${output}/${path.replaceAll('/', '-')}-${width}.png`,
            fullPage: true,
        });
}
try {
    await scenario('patient', 'queimados');
    for (const width of [375, 768, 1024, 1440]) {
        for (const path of [
            '/inicio',
            '/orientacao',
            '/atendimento',
            '/agendamento',
            '/farmacia',
            '/receita/RC-2',
            '/farmacias',
            '/consultas',
            '/conta',
            '/nr1',
            '/ajuda',
        ]) {
            await visit(
                path,
                width,
                ['/inicio', '/receita/RC-2'].includes(path),
            );
        }
        console.log(`Paciente: 11 páginas verificadas em ${width}px`);
    }
    await visit('/inicio', 375);
    await page.getByRole('button', { name: 'Abrir menu' }).click();
    assert.equal(await page.locator('dialog[open]').count(), 1);
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('dialog[open]').count(), 0);
    assert.equal(
        await page
            .getByRole('button', { name: 'Abrir menu' })
            .evaluate((element) => element === document.activeElement),
        true,
        'Drawer must restore focus',
    );
    await page.getByRole('button', { name: 'Ⓜ MAX', exact: true }).click();
    await page.getByLabel('Escreva para o MAX').fill('Estou com dor no peito');
    await page.getByRole('button', { name: 'Enviar mensagem' }).click();
    await page
        .getByRole('link', { name: 'Ligar 192, SAMU', exact: true })
        .waitFor();
    assert.equal(
        await page.evaluate(() =>
            Object.keys(localStorage).some((key) => /max|saude/i.test(key)),
        ),
        false,
    );
    await page.keyboard.press('Escape');
    await visit('/receita/RC-2', 1440);
    await page
        .getByRole('button', { name: 'Conferir ou corrigir' })
        .first()
        .click();
    await page
        .getByRole('button', { name: 'Confirmar texto', exact: true })
        .click();
    await page
        .getByText('Texto confirmado. Cobertura ainda não verificada.')
        .waitFor();
    await scenario('patient', 'cetid');
    await visit('/agendamento', 375, true);
    await page.getByRole('button', { name: /Clínica Médica/ }).click();
    await page.locator('.sm-option').first().click();
    await page.locator('.sm-option').first().click();
    await page
        .getByRole('button', { name: /Primeiro profissional disponível/ })
        .click();
    await page
        .getByRole('button', { name: 'Confirmar agendamento demonstrativo' })
        .click();
    await page
        .getByRole('heading', { name: 'Agendamento demonstrativo registrado' })
        .waitFor();
    await scenario('manager');
    for (const width of [375, 768, 1024, 1440]) {
        for (const path of [
            '/gestor/painel',
            '/gestor/pacientes',
            '/gestor/pacientes/107',
            '/gestor/consultas',
            '/gestor/planos',
            '/gestor/identidade',
            '/gestor/integracao',
        ]) {
            await visit(
                path,
                width,
                ['/gestor/painel', '/gestor/identidade'].includes(path),
            );
        }
        console.log(`Gestor: 7 páginas verificadas em ${width}px`);
    }
    await visit('/gestor/identidade', 1440);
    await page.getByLabel('Cor em hexadecimal').fill('#1D4E89');
    assert.equal(
        await page
            .locator('section.sm-app')
            .evaluate((el) =>
                getComputedStyle(el).getPropertyValue('--marca').trim(),
            ),
        '#1D4E89',
    );
    await page.getByRole('button', { name: 'Salvar identidade' }).click();
    await page.getByText('Identidade atualizada na demonstração.').waitFor();
    await visit('/gestor/planos', 1440);
    const toggle = page.getByRole('switch', {
        name: 'Farmácia popular no plano Cetid Familiar',
    });
    await toggle.click();
    await page.waitForFunction(
        () =>
            document
                .querySelector(
                    '[aria-label="Farmácia popular no plano Cetid Familiar"]',
                )
                .getAttribute('aria-checked') === 'false',
    );
    await scenario('patient', 'cetid');
    await visit('/farmacia', 1440);
    await page
        .getByRole('heading', { name: 'Não incluído no seu plano' })
        .waitFor();
    await scenario('patient', 'cetid', 'error');
    await visit('/consultas', 375);
    await page.getByRole('button', { name: 'Tentar novamente' }).waitFor();
    await scenario('patient', 'cetid', 'empty');
    await visit('/consultas', 375);
    await page
        .getByText('Nenhuma consulta registrada neste cenário.')
        .waitFor();
    for (const width of [375, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 1000 });
        for (const path of ['/', '/login', '/register', '/forgot-password']) {
            await page.goto(base + path);
            await page.waitForLoadState('networkidle');
            assert.equal(
                await page.evaluate(
                    () =>
                        document.documentElement.scrollWidth <= innerWidth + 1,
                ),
                true,
                `Overflow ${path} at ${width}`,
            );
        }
    }
    assert.deepEqual(errors, [], 'Browser runtime errors');
    console.log(
        'PASS: 72 verificações de páginas, 16 de entrada, menu, foco, MAX, agenda, confirmação de leitura, white label, módulos e estados de falha/vazio.',
    );
} catch (error) {
    console.error('Página na falha:', page.url());
    console.error(
        'Erros de formulário:',
        await page.locator('.sm-error').allTextContents(),
    );
    console.error('Erros de execução:', errors);
    await page.screenshot({ path: `${output}/failure.png`, fullPage: true });
    throw error;
} finally {
    await browser.close();
}
