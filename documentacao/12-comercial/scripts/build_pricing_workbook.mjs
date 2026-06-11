import fs from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, "..");
const outputDir = path.join(rootDir, "artefatos");
const workbookPath = path.join(outputDir, "planilha-precificacao-margem.xlsx");
const qaImagePath = path.join(outputDir, "qa-planilha-resumo.png");

await fs.mkdir(outputDir, { recursive: true });

const workbook = Workbook.create();

const summary = workbook.worksheets.add("Resumo");
const client = workbook.worksheets.add("Cliente_Modelo");
const pricing = workbook.worksheets.add("Precificacao");
const breakeven = workbook.worksheets.add("Ponto_Equilibrio");
const plans = workbook.worksheets.add("Pacotes_Referencia");

function styleTitle(sheet, range) {
  const r = sheet.getRange(range);
  r.merge();
  r.values = [["MODELO FINANCEIRO - CONTRATO DE MANUTENCAO"]];
  r.format = {
    fill: { type: "solid", color: "#1F4D78" },
    font: { name: "Calibri", size: 16, bold: true, color: "#FFFFFF" },
    horizontalAlignment: "center",
    verticalAlignment: "center",
  };
}

function styleSection(sheet, range, text) {
  const r = sheet.getRange(range);
  r.merge();
  r.values = [[text]];
  r.format = {
    fill: { type: "solid", color: "#E8EEF5" },
    font: { name: "Calibri", size: 11, bold: true, color: "#0B2545" },
    horizontalAlignment: "left",
    verticalAlignment: "center",
  };
}

function applyCurrency(sheet, range) {
  sheet.getRange(range).format.numberFormat = '"R$" #,##0.00';
}

function applyPercent(sheet, range) {
  sheet.getRange(range).format.numberFormat = "0.0%";
}

function softBorders(sheet, range) {
  sheet.getRange(range).format.borders = {
    preset: "outside",
    style: "thin",
    color: "#D1D5DB",
  };
}

function fillInputs(range) {
  range.format.fill = { type: "solid", color: "#FFF8DB" };
}

function fillOutputs(range) {
  range.format.fill = { type: "solid", color: "#F3F8FF" };
}

styleTitle(summary, "A1:H1");
summary.getRange("A3:A9").values = [[
  "Receita mensal total",
], [
  "Custo direto total",
], [
  "Lucro bruto",
], [
  "Margem bruta",
], [
  "Lucro operacional",
], [
  "Margem operacional",
], [
  "Fee de onboarding sugerido",
]];
summary.getRange("B3:B9").formulas = [[
  "='Cliente_Modelo'!B20",
], [
  "='Cliente_Modelo'!B21",
], [
  "='Cliente_Modelo'!B22",
], [
  "='Cliente_Modelo'!B23",
], [
  "='Cliente_Modelo'!B24",
], [
  "='Cliente_Modelo'!B25",
], [
  "='Cliente_Modelo'!E20",
]];
summary.getRange("D3:D8").values = [[
  "Leitura executiva",
], [
  "Cliente modelo",
], [
  "Plano de referencia",
], [
  "Faixa de decisao",
], [
  "Receita recorrente necessaria",
], [
  "Contratos medios para equilibrio",
]];
summary.getRange("E3:E8").formulas = [[
  '=IF(B8<0.15,"Margem operacional critica",IF(B8<0.25,"Margem operacional ajustavel","Margem operacional saudavel"))',
], [
  "='Cliente_Modelo'!B4",
], [
  "='Cliente_Modelo'!B5",
], [
  '=TEXT(\'Precificacao\'!B4,"0%")&" a "&TEXT(\'Precificacao\'!E4,"0%")',
], [
  "='Ponto_Equilibrio'!B10",
], [
  "='Ponto_Equilibrio'!B11",
]];
styleSection(summary, "A11:E11", "Precos minimos por margem alvo");
summary.getRange("A12:E16").values = [
  ["Indicador", "45%", "50%", "55%", "60%"],
  ["Preco minimo mensal", "", "", "", ""],
  ["Lucro bruto", "", "", "", ""],
  ["Margem bruta", "", "", "", ""],
  ["Preco com folga comercial", "", "", "", ""],
];
summary.getRange("B13:E16").formulas = [
  ["='Precificacao'!B6", "='Precificacao'!C6", "='Precificacao'!D6", "='Precificacao'!E6"],
  ["='Precificacao'!B7", "='Precificacao'!C7", "='Precificacao'!D7", "='Precificacao'!E7"],
  ["='Precificacao'!B8", "='Precificacao'!C8", "='Precificacao'!D8", "='Precificacao'!E8"],
  ["='Precificacao'!B9", "='Precificacao'!C9", "='Precificacao'!D9", "='Precificacao'!E9"],
];

styleTitle(client, "A1:F1");
styleSection(client, "A3:B3", "Dados do cliente e receita");
client.getRange("A4:A10").values = [
  ["Nome do cliente"],
  ["Plano de referencia"],
  ["Mensalidade base do plano"],
  ["Qtd. ativos extras comuns"],
  ["Valor por ativo extra"],
  ["Qtd. ativos criticos"],
  ["Valor por ativo critico"],
];
client.getRange("B4:B10").values = [
  ["Cliente Exemplo LTDA"],
  ["Continuidade"],
  [2990],
  [12],
  [95],
  [2],
  [350],
];
styleSection(client, "A12:B12", "Custos e estrutura");
client.getRange("A13:A17").values = [
  ["Onboarding (multiplicador)"],
  ["Mao de obra direta alocada"],
  ["Ferramentas e licencas"],
  ["Deslocamento e logistica"],
  ["Reserva de risco/retrabalho"],
];
client.getRange("B13:B17").values = [[1.2], [950], [320], [180], [250]];
client.getRange("D13:D13").values = [["Rateio de estrutura"]];
client.getRange("E13:E13").values = [[700]];

styleSection(client, "A19:B19", "Resultados automaticos");
client.getRange("A20:A25").values = [
  ["Receita mensal total"],
  ["Custo direto total"],
  ["Lucro bruto"],
  ["Margem bruta"],
  ["Lucro operacional"],
  ["Margem operacional"],
];
client.getRange("B20:B25").formulas = [
  ["=SUM(B6,B7*B8,B9*B10)"],
  ["=SUM(B14:B17)"],
  ["=B20-B21"],
  ['=IFERROR(B22/B20,0)'],
  ["=B22-E13"],
  ['=IFERROR(B24/B20,0)'],
];
client.getRange("D20:D24").values = [
  ["Fee de onboarding sugerido"],
  ["Receita anual recorrente"],
  ["Lucro bruto anual"],
  ["Lucro operacional anual"],
  ["Ticket anual com onboarding"],
];
client.getRange("E20:E24").formulas = [
  ["=B20*B13"],
  ["=B20*12"],
  ["=B22*12"],
  ["=B24*12"],
  ["=(B20*12)+E20"],
];

styleTitle(pricing, "A1:F1");
styleSection(pricing, "A3:E3", "Simulador de margem alvo");
pricing.getRange("A4:E9").values = [
  ["Indicador", 0.45, 0.50, 0.55, 0.60],
  ["Custo direto base", "", "", "", ""],
  ["Preco minimo mensal", "", "", "", ""],
  ["Lucro bruto esperado", "", "", "", ""],
  ["Margem bruta", "", "", "", ""],
  ["Preco com folga comercial (5%)", "", "", "", ""],
];
pricing.getRange("B5:E9").formulas = [
  ["='Cliente_Modelo'!B21", "='Cliente_Modelo'!B21", "='Cliente_Modelo'!B21", "='Cliente_Modelo'!B21"],
  ["=IFERROR(B5/(1-B4),0)", "=IFERROR(C5/(1-C4),0)", "=IFERROR(D5/(1-D4),0)", "=IFERROR(E5/(1-E4),0)"],
  ["=B6-B5", "=C6-C5", "=D6-D5", "=E6-E5"],
  ["=IFERROR(B7/B6,0)", "=IFERROR(C7/C6,0)", "=IFERROR(D7/D6,0)", "=IFERROR(E7/E6,0)"],
  ["=B6*1.05", "=C6*1.05", "=D6*1.05", "=E6*1.05"],
];
pricing.getRange("A12:B17").values = [
  ["Recomendacao de uso", "Acao"],
  ["Abaixo de 45% de margem", "Revisar preco, escopo ou elegibilidade do cliente"],
  ["Entre 45% e 50%", "Operacao possivel, mas com baixa folga"],
  ["Entre 50% e 55%", "Faixa saudavel para contrato recorrente"],
  ["Acima de 55%", "Alta folga para crescimento e qualidade"],
  ["Projetos avulsos", "Nao misturar com a mensalidade recorrente"],
];

styleTitle(breakeven, "A1:F1");
styleSection(breakeven, "A3:B3", "Premissas da empresa");
breakeven.getRange("A4:A7").values = [
  ["Custos fixos mensais"],
  ["Margem bruta media"],
  ["Receita mensal prevista com projetos/avulsos"],
  ["Ticket medio mensal por contrato"],
];
breakeven.getRange("B4:B7").values = [[38000], [0.55], [10000], [3000]];
styleSection(breakeven, "A9:B9", "Ponto de equilibrio");
breakeven.getRange("A10:A12").values = [
  ["Receita recorrente necessaria"],
  ["Contratos medios necessarios"],
  ["Leitura de capacidade"],
];
breakeven.getRange("B10:B12").formulas = [
  ["=MAX((B4-B6)/B5,0)"],
  ["=CEILING(B10/B7,1)"],
  ['=IF(B11<=10,"Operacao enxuta","Reforcar vendas ou aumentar ticket medio")'],
];
styleSection(breakeven, "D3:G3", "Cenarios por ticket medio");
breakeven.getRange("D4:G8").values = [
  ["Indicador", 2500, 3000, 4000],
  ["Receita recorrente necessaria", "", "", ""],
  ["Contratos necessarios", "", "", ""],
  ["Lucro operacional por contrato (cliente modelo)", "", "", ""],
  ["Observacao", "", "", ""],
];
breakeven.getRange("E5:G8").formulas = [
  ["=$B$10", "=$B$10", "=$B$10"],
  ["=CEILING(E5/E4,1)", "=CEILING(F5/F4,1)", "=CEILING(G5/G4,1)"],
  ["='Cliente_Modelo'!B24", "='Cliente_Modelo'!B24", "='Cliente_Modelo'!B24"],
  ['=IF(E6<=10,"Meta possivel com carteira enxuta","Exige volume comercial alto")', '=IF(F6<=10,"Meta possivel com carteira enxuta","Exige volume comercial alto")', '=IF(G6<=10,"Meta possivel com carteira enxuta","Exige volume comercial alto")'],
];

styleTitle(plans, "A1:F1");
plans.getRange("A3:F6").values = [
  ["Plano", "Perfil", "Mensalidade base", "Cobertura de referencia", "SLA", "Observacao"],
  ["Essencial", "Pequenas empresas", 1490, "Ate 10 ativos", "Resposta em ate 4h uteis", "Remoto, preventiva bimestral"],
  ["Continuidade", "PMEs que nao podem parar", 2990, "Ate 25 ativos", "Resposta em ate 2h uteis", "Monitoramento 24x7 e visita mensal"],
  ["Missao Critica", "Operacoes sensiveis", 5490, "Ate 50 ativos", "Resposta em ate 30 min", "Prioridade maxima e revisao executiva"],
];

// Core formatting
for (const sheet of [summary, client, pricing, breakeven, plans]) {
  sheet.getRange("A1:H40").format.font = { name: "Calibri", size: 11, color: "#0B2545" };
}

fillOutputs(summary.getRange("B3:B9"));
fillOutputs(summary.getRange("E3:E8"));
fillOutputs(summary.getRange("B13:E16"));
fillInputs(client.getRange("B4:B10"));
fillInputs(client.getRange("B13:B17"));
fillInputs(client.getRange("E13:E13"));
fillOutputs(client.getRange("B20:B25"));
fillOutputs(client.getRange("E20:E24"));
fillOutputs(pricing.getRange("B5:E9"));
fillInputs(breakeven.getRange("B4:B7"));
fillOutputs(breakeven.getRange("B10:B12"));
fillOutputs(breakeven.getRange("E5:G8"));

applyCurrency(summary, "B3:B5");
applyPercent(summary, "B6:B6");
applyCurrency(summary, "B7:B7");
applyPercent(summary, "B8:B8");
applyCurrency(summary, "B9:B9");
applyCurrency(summary, "E7:E7");
summary.getRange("E8:E8").format.numberFormat = "0";
applyCurrency(summary, "B13:E14");
applyPercent(summary, "B15:E15");
applyCurrency(summary, "B16:E16");

applyCurrency(client, "B6:B10");
applyPercent(client, "B13:B13");
applyCurrency(client, "B14:B17");
applyCurrency(client, "E13:E13");
applyCurrency(client, "B20:B22");
applyPercent(client, "B23:B23");
applyCurrency(client, "B24:B24");
applyPercent(client, "B25:B25");
applyCurrency(client, "E20:E24");

applyPercent(pricing, "B4:E4");
applyCurrency(pricing, "B5:E7");
applyPercent(pricing, "B8:E8");
applyCurrency(pricing, "B9:E9");

applyCurrency(breakeven, "B4:B4");
applyPercent(breakeven, "B5:B5");
applyCurrency(breakeven, "B6:B7");
applyCurrency(breakeven, "B10:B10");
applyCurrency(breakeven, "E5:G5");
applyCurrency(breakeven, "E7:G7");
applyCurrency(plans, "C4:C6");

summary.getRange("A3:A16").format.font = { bold: true };
client.getRange("A4:A25").format.font = { bold: true };
pricing.getRange("A4:A17").format.font = { bold: true };
breakeven.getRange("A4:A12").format.font = { bold: true };
plans.getRange("A3:F3").format.font = { bold: true, color: "#FFFFFF" };
plans.getRange("A3:F3").format.fill = { type: "solid", color: "#2E74B5" };

summary.getRange("A12:E16").format.wrapText = true;
pricing.getRange("A12:B17").format.wrapText = true;
plans.getRange("A3:F6").format.wrapText = true;
breakeven.getRange("D4:G8").format.wrapText = true;

softBorders(summary, "A3:B9");
softBorders(summary, "D3:E8");
softBorders(summary, "A12:E16");
softBorders(client, "A4:B10");
softBorders(client, "A13:B17");
softBorders(client, "D13:E13");
softBorders(client, "A20:B25");
softBorders(client, "D20:E24");
softBorders(pricing, "A4:E9");
softBorders(pricing, "A12:B17");
softBorders(breakeven, "A4:B7");
softBorders(breakeven, "A10:B12");
softBorders(breakeven, "D4:G8");
softBorders(plans, "A3:F6");

summary.getRange("B6").conditionalFormats.add("Custom", {
  formula: "=B6<0.45",
  format: { fill: "#FECACA", font: { color: "#991B1B", bold: true } },
});
summary.getRange("B6").conditionalFormats.add("Custom", {
  formula: '=AND(B6>=0.45,B6<0.55)',
  format: { fill: "#FEF3C7", font: { color: "#92400E", bold: true } },
});
summary.getRange("B6").conditionalFormats.add("Custom", {
  formula: "=B6>=0.55",
  format: { fill: "#DCFCE7", font: { color: "#166534", bold: true } },
});
summary.getRange("B8").conditionalFormats.add("Custom", {
  formula: "=B8<0.15",
  format: { fill: "#FECACA", font: { color: "#991B1B", bold: true } },
});
summary.getRange("B8").conditionalFormats.add("Custom", {
  formula: '=AND(B8>=0.15,B8<0.25)',
  format: { fill: "#FEF3C7", font: { color: "#92400E", bold: true } },
});
summary.getRange("B8").conditionalFormats.add("Custom", {
  formula: "=B8>=0.25",
  format: { fill: "#DCFCE7", font: { color: "#166534", bold: true } },
});

workbook.recalculate();

// Focused autofit after content is populated
summary.getRange("A1:H20").format.autofitColumns();
summary.getRange("A1:H20").format.autofitRows();
client.getRange("A1:F30").format.autofitColumns();
client.getRange("A1:F30").format.autofitRows();
pricing.getRange("A1:F20").format.autofitColumns();
pricing.getRange("A1:F20").format.autofitRows();
breakeven.getRange("A1:G20").format.autofitColumns();
breakeven.getRange("A1:G20").format.autofitRows();
plans.getRange("A1:F10").format.autofitColumns();
plans.getRange("A1:F10").format.autofitRows();

// Basic verification before export
const revenue = client.getRange("B20").values?.[0]?.[0];
const grossMargin = client.getRange("B23").values?.[0]?.[0];
const operationalMargin = client.getRange("B25").values?.[0]?.[0];
if (!Number.isFinite(revenue) || revenue <= 0) {
  throw new Error("Receita mensal nao foi calculada corretamente.");
}
if (!Number.isFinite(grossMargin) || grossMargin <= 0) {
  throw new Error("Margem bruta nao foi calculada corretamente.");
}
if (!Number.isFinite(operationalMargin)) {
  throw new Error("Margem operacional nao foi calculada corretamente.");
}

const xlsx = await SpreadsheetFile.exportXlsx(workbook);
await xlsx.save(workbookPath);

try {
  const preview = await workbook.render({ sheetName: "Resumo", range: "A1:H16", scale: 2 });
  if (typeof preview.save === "function") {
    await preview.save(qaImagePath);
  }
} catch (error) {
  // Preview is useful for QA but not critical to the final deliverable.
  console.warn("Nao foi possivel gerar a imagem de QA da planilha:", error?.message ?? error);
}
