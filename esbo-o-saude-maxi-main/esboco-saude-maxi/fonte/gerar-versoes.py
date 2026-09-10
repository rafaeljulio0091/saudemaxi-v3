#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Gera as duas versões derivadas do protótipo Saúde Maxi a partir do original.

37 telas: A1 a J3, mais X1, a tela de ajuda imediata.

Fonte da verdade:
    fonte/index.html

Derivados, nunca editar à mão:
    index.html                     versão publicada na Vercel
    fonte/artifact-saude-max.html  versão publicada como Artifact

Uso, a partir de qualquer diretório:
    python fonte/gerar-versoes.py

Depois de rodar, publicar:
    vercel deploy --prod --yes

Os caminhos acima valem para esta pasta do repositório. No repositório de
trabalho original a fonte mora em esboco/ e o publicado em esboco-saude-maxi/.
Só os três caminhos abaixo mudaram, a verificação é a mesma.
"""

import io
import os
import re
import sys

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ORIGEM = os.path.join(RAIZ, 'fonte', 'index.html')
DEST_VERCEL = os.path.join(RAIZ, 'index.html')
DEST_ARTIFACT = os.path.join(RAIZ, 'fonte', 'artifact-saude-max.html')

TITULO_ARTIFACT = 'Saúde Maxi Camada Nova'

META_NOINDEX = (
    '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">\n'
    '<meta name="referrer" content="no-referrer">\n'
)
ANCORA_VIEWPORT = '<meta name="viewport" content="width=device-width, initial-scale=1">\n'


def ler_origem():
    if not os.path.exists(ORIGEM):
        sys.exit('Origem não encontrada: ' + ORIGEM)
    return io.open(ORIGEM, encoding='utf-8').read()


def gerar_vercel(s):
    """Versão pública: acrescenta bloqueio de indexação no próprio HTML."""
    if 'noindex' in s:
        saida = s
    else:
        saida = s.replace(ANCORA_VIEWPORT, ANCORA_VIEWPORT + META_NOINDEX, 1)
    os.makedirs(os.path.dirname(DEST_VERCEL), exist_ok=True)
    io.open(DEST_VERCEL, 'w', encoding='utf-8').write(saida)
    return saida


def gerar_artifact(s):
    """
    Versão do Artifact: o host injeta doctype, html, head e body.
    Entregamos só o conteúdo, sem as tags externas.
    """
    i = s.index('<body class="vp-desktop">')
    i = s.index('>', i) + 1
    j = s.rindex('</body>')
    corpo = s[i:j]

    estilo = re.search(r'<style>.*?</style>', s, re.S).group(0)

    # O sistema visual já declara color-scheme e pinta o fundo do html na
    # própria origem, então não há nada a injetar aqui. O tema é único e claro,
    # por fidelidade ao produto real do cliente.
    if 'color-scheme: light' not in estilo:
        sys.exit('A origem perdeu a declaração de color-scheme. Conferir o CSS.')
    if 'html{ background: var(--neutro-050); }' not in estilo:
        sys.exit('A origem perdeu o fundo explícito do html. Conferir o CSS.')

    # A origem já usa classList e não sobrescreve body.className inteiro,
    # então nada precisa ser ajustado aqui. Sobrescrever a classe do body
    # apagaria a classe que oculta as notas de projeto.

    # aplicar a visualização já na leitura do script, sem esperar DOMContentLoaded
    corpo = corpo.replace(
        'var estado = {',
        "document.body.classList.add('vp-desktop');\n\nvar estado = {", 1)

    saida = '<title>' + TITULO_ARTIFACT + '</title>\n' + estilo + '\n' + corpo
    io.open(DEST_ARTIFACT, 'w', encoding='utf-8').write(saida)
    return saida


def conferir(nome, texto, exige_tags_externas):
    """Roda o mesmo checklist de verificação em cada versão gerada."""
    problemas = []

    telas = len(re.findall(r'class="tela"', texto))
    if telas != 37:
        problemas.append('telas: %d, esperado 37' % telas)

    ids = re.findall(r'<section class="tela" id="([^"]+)"', texto)
    esperado = ['A1', 'A2', 'A3', 'A4', 'B1', 'B2',
                'C1', 'C2', 'C3', 'C4', 'C5', 'C6',
                'D1', 'D2', 'D3', 'D4', 'D5', 'D6',
                'E1', 'E2', 'E3', 'F1', 'F2', 'F3', 'F4',
                'G1', 'G2', 'G3', 'G4', 'G5',
                'H1', 'H2', 'I1', 'J1', 'J2', 'J3', 'X1']
    faltando = [e for e in esperado if e not in ids]
    if faltando:
        problemas.append('ids faltando: ' + ', '.join(faltando))

    for termo in ['diagn', 'campanha']:
        n = len(re.findall(termo, texto, re.I))
        if n:
            problemas.append('termo vetado "%s": %d ocorrências' % (termo, n))

    # Os caracteres são montados por código de propósito: escrever o travessão
    # literal aqui violaria a própria regra que esta função fiscaliza.
    for cod in [chr(8212), chr(8211)]:
        if cod in texto:
            problemas.append('travessão encontrado')

    # A data URI é interna e não conta como referência externa. Precisa sair
    # inteira da varredura, payload incluído: a sequência base64 contém letras
    # soltas que casam com "cdn" por acaso e geram falso positivo.
    sem_datauri = re.sub(r'data:image/[a-z]+;base64,[A-Za-z0-9+/=]+', '', texto)
    externas = len(re.findall(r'https?://|cdn|src=', sem_datauri, re.I))
    if externas:
        problemas.append('referências externas: %d' % externas)

    todos = set(re.findall(r'id="([^"]+)"', texto))
    alvos = re.findall(r'data-ir-para="([^"]+)"', texto)
    quebradas = sorted(set(a for a in alvos if a not in todos))
    if quebradas:
        problemas.append('ligações quebradas: ' + ', '.join(quebradas))

    tem_tags = '<!DOCTYPE' in texto or '<html' in texto
    if exige_tags_externas and not tem_tags:
        problemas.append('faltam as tags de documento')
    if not exige_tags_externas and tem_tags:
        problemas.append('tags de documento presentes, o Artifact injeta as dele')

    marca = 'data:image/png;base64,' in texto
    if not marca:
        problemas.append('logo oficial ausente')

    estado = 'OK' if not problemas else 'FALHOU'
    print('%-38s %-7s %6d KB  %d telas  %d ligações' % (
        nome, estado, len(texto.encode('utf-8')) // 1024, telas, len(alvos)))
    for p in problemas:
        print('    ! ' + p)
    return not problemas


def main():
    s = ler_origem()
    print('Origem: fonte/index.html\n')

    resultados = [
        conferir('fonte/index.html', s, True),
        conferir('index.html', gerar_vercel(s), True),
        conferir('fonte/artifact-saude-max.html', gerar_artifact(s), False),
    ]

    print('')
    if all(resultados):
        print('Tudo certo. Para publicar na Vercel:')
        print('    vercel deploy --prod --yes')
    else:
        sys.exit('Alguma versão falhou na verificação. Nada foi publicado.')


if __name__ == '__main__':
    main()
