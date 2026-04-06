"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import {
  ChecklistEntradaField,
  appendChecklistFiles,
  buildChecklistDraft,
  serializeChecklistDraft,
  type ChecklistApiPayload,
  type ChecklistDraft
} from "@/components/ChecklistEntradaField";
import { MobileNav } from "@/components/MobileNav";
import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";

type OrderDetail = {
  id: number;
  numero_os: string;
  status: string;
  prioridade: string;
  cliente_nome: string | null;
  cliente_id: number;
  equipamento_id: number;
  tecnico_id: number | null;
  relato_cliente: string | null;
  diagnostico_tecnico: string | null;
  solucao_aplicada: string | null;
  data_entrada: string | null;
  data_previsao: string | null;
  valor_mao_obra: number | null;
  valor_pecas: number | null;
  desconto: number | null;
  valor_total: number | null;
  valor_final: number | null;
  forma_pagamento: string | null;
  garantia_dias: number | null;
  garantia_validade: string | null;
  observacoes_cliente: string | null;
  observacoes_internas: string | null;
  defeitos_ids?: number[];
  checklist_entrada?: ChecklistApiPayload | null;
};

type StatusOption = {
  codigo: string;
  nome: string;
  grupo_macro: string;
  cor: string;
  ordem_fluxo: number;
};

type PriorityOption = {
  codigo: string;
  nome: string;
};

type TechnicianOption = {
  id: number;
  nome: string;
  cargo: string | null;
};

type DefectOption = {
  id: number;
  nome: string;
  descricao: string | null;
  classificacao: string | null;
};

type OrderMetaResponse = {
  technicians: TechnicianOption[];
  statuses: StatusOption[];
  priorities: PriorityOption[];
  defects: DefectOption[];
  checklist_entrada?: ChecklistApiPayload | null;
};

type FormState = {
  status: string;
  prioridade: string;
  tecnico_id: string;
  relato_cliente: string;
  diagnostico_tecnico: string;
  solucao_aplicada: string;
  data_entrada: string;
  data_previsao: string;
  valor_mao_obra: string;
  valor_pecas: string;
  desconto: string;
  valor_total: string;
  valor_final: string;
  forma_pagamento: string;
  garantia_dias: string;
  garantia_validade: string;
  observacoes_cliente: string;
  observacoes_internas: string;
};

const fallbackStatuses: StatusOption[] = [
  { codigo: "triagem", nome: "Triagem", grupo_macro: "aberta", cor: "primary", ordem_fluxo: 1 },
  { codigo: "diagnostico", nome: "Diagnostico", grupo_macro: "aberta", cor: "primary", ordem_fluxo: 2 },
  { codigo: "aguardando_reparo", nome: "Aguardando reparo", grupo_macro: "aberta", cor: "warning", ordem_fluxo: 3 }
];

const fallbackPriorities: PriorityOption[] = [
  { codigo: "baixa", nome: "Baixa" },
  { codigo: "normal", nome: "Normal" },
  { codigo: "alta", nome: "Alta" },
  { codigo: "urgente", nome: "Urgente" }
];

function pad2(value: number): string {
  return String(value).padStart(2, "0");
}

function formatDateTimeLocal(date: Date): string {
  const year = date.getFullYear();
  const month = pad2(date.getMonth() + 1);
  const day = pad2(date.getDate());
  const hour = pad2(date.getHours());
  const minute = pad2(date.getMinutes());
  return `${year}-${month}-${day}T${hour}:${minute}`;
}

function toInputDateTime(value: string | null | undefined): string {
  if (!value) {
    return "";
  }
  const normalized = value.includes("T") ? value : value.replace(" ", "T");
  const date = new Date(normalized);
  if (Number.isNaN(date.getTime())) {
    return "";
  }
  return formatDateTimeLocal(date);
}

function toInputDecimal(value: number | null | undefined): string {
  if (value === null || value === undefined) {
    return "";
  }
  return String(value);
}

export default function OrderDetailPage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const orderId = Number(params?.id || 0);
  const [item, setItem] = useState<OrderDetail | null>(null);
  const [form, setForm] = useState<FormState>({
    status: "triagem",
    prioridade: "normal",
    tecnico_id: "",
    relato_cliente: "",
    diagnostico_tecnico: "",
    solucao_aplicada: "",
    data_entrada: "",
    data_previsao: "",
    valor_mao_obra: "",
    valor_pecas: "",
    desconto: "",
    valor_total: "",
    valor_final: "",
    forma_pagamento: "",
    garantia_dias: "",
    garantia_validade: "",
    observacoes_cliente: "",
    observacoes_internas: ""
  });
  const [statuses, setStatuses] = useState<StatusOption[]>(fallbackStatuses);
  const [priorities, setPriorities] = useState<PriorityOption[]>(fallbackPriorities);
  const [technicians, setTechnicians] = useState<TechnicianOption[]>([]);
  const [defects, setDefects] = useState<DefectOption[]>([]);
  const [selectedDefects, setSelectedDefects] = useState<number[]>([]);
  const [checklistEntrada, setChecklistEntrada] = useState<ChecklistDraft>(() => buildChecklistDraft(null));
  const [metaLoading, setMetaLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  async function loadMeta(orderData: OrderDetail) {
    if (!orderData.cliente_id || !orderData.equipamento_id) {
      return;
    }

    setMetaLoading(true);
    try {
      const query = new URLSearchParams();
      query.set("cliente_id", String(orderData.cliente_id));
      query.set("equipamento_id", String(orderData.equipamento_id));

      const data = await apiRequest<OrderMetaResponse>(`/orders/meta?${query.toString()}`);
      const nextStatuses = data.statuses?.length ? data.statuses : fallbackStatuses;
      const nextPriorities = data.priorities?.length ? data.priorities : fallbackPriorities;

      setStatuses(nextStatuses);
      setPriorities(nextPriorities);
      setTechnicians(data.technicians || []);
      setDefects(data.defects || []);
      setChecklistEntrada(buildChecklistDraft(data.checklist_entrada ?? null));
    } catch (_err) {
      setStatuses(fallbackStatuses);
      setPriorities(fallbackPriorities);
      setDefects([]);
      setChecklistEntrada(buildChecklistDraft(null));
    } finally {
      setMetaLoading(false);
    }
  }

  async function loadOrder() {
    try {
      const data = await apiRequest<OrderDetail>(`/orders/${orderId}`);
      setItem(data);
      setForm({
        status: data.status || "triagem",
        prioridade: data.prioridade || "normal",
        tecnico_id: data.tecnico_id ? String(data.tecnico_id) : "",
        relato_cliente: data.relato_cliente || "",
        diagnostico_tecnico: data.diagnostico_tecnico || "",
        solucao_aplicada: data.solucao_aplicada || "",
        data_entrada: toInputDateTime(data.data_entrada),
        data_previsao: toInputDateTime(data.data_previsao),
        valor_mao_obra: toInputDecimal(data.valor_mao_obra),
        valor_pecas: toInputDecimal(data.valor_pecas),
        desconto: toInputDecimal(data.desconto),
        valor_total: toInputDecimal(data.valor_total),
        valor_final: toInputDecimal(data.valor_final),
        forma_pagamento: data.forma_pagamento || "",
        garantia_dias: data.garantia_dias === null || data.garantia_dias === undefined ? "" : String(data.garantia_dias),
        garantia_validade: toInputDateTime(data.garantia_validade),
        observacoes_cliente: data.observacoes_cliente || "",
        observacoes_internas: data.observacoes_internas || ""
      });
      setSelectedDefects((data.defeitos_ids || []).map((id) => Number(id)).filter((id) => id > 0));
      setChecklistEntrada(buildChecklistDraft(data.checklist_entrada ?? null));
      await loadMeta(data);
      setError("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha ao carregar OS.");
    }
  }

  useEffect(() => {
    if (!getSession()?.accessToken) {
      router.replace("/login");
      return;
    }
    if (!orderId) {
      router.replace("/os");
      return;
    }
    loadOrder();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [router, orderId]);

  function toggleDefect(defectId: number) {
    setSelectedDefects((prev) => {
      if (prev.includes(defectId)) {
        return prev.filter((id) => id !== defectId);
      }
      return [...prev, defectId];
    });
  }

  async function onSave() {
    setSaving(true);
    setSuccess("");
    setError("");
    try {
      const payload = new FormData();
      payload.set("status", form.status);
      payload.set("prioridade", form.prioridade);
      payload.set("tecnico_id", form.tecnico_id ? String(Number(form.tecnico_id)) : "");
      payload.set("relato_cliente", form.relato_cliente);
      payload.set("diagnostico_tecnico", form.diagnostico_tecnico);
      payload.set("solucao_aplicada", form.solucao_aplicada);
      payload.set("data_entrada", form.data_entrada || "");
      payload.set("data_previsao", form.data_previsao || "");
      payload.set("valor_mao_obra", form.valor_mao_obra || "");
      payload.set("valor_pecas", form.valor_pecas || "");
      payload.set("desconto", form.desconto || "");
      payload.set("valor_total", form.valor_total || "");
      payload.set("valor_final", form.valor_final || "");
      payload.set("forma_pagamento", form.forma_pagamento || "");
      payload.set("garantia_dias", form.garantia_dias || "");
      payload.set("garantia_validade", form.garantia_validade || "");
      payload.set("observacoes_cliente", form.observacoes_cliente || "");
      payload.set("observacoes_internas", form.observacoes_internas || "");
      selectedDefects.forEach((defectId) => payload.append("defeitos[]", String(defectId)));

      if (checklistEntrada.possuiModelo) {
        payload.set("checklist_entrada_data", JSON.stringify(serializeChecklistDraft(checklistEntrada)));
        appendChecklistFiles(payload, checklistEntrada);
      }

      await apiRequest(`/orders/${orderId}`, {
        method: "PUT",
        body: payload
      });
      setSuccess("OS atualizada com sucesso.");
      await loadOrder();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha ao atualizar OS.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <header className="mobile-header">
        <h1 className="mobile-title">{item?.numero_os || `OS #${orderId}`}</h1>
        <p className="mobile-subtitle">{item?.cliente_nome || "Edicao completa da OS"}</p>
      </header>

      <section className="mobile-card">
        <Link href="/os" className="helper-line">
          Voltar para OS
        </Link>

        <div className="form-block" style={{ marginTop: 10 }}>
          <h2 className="section-title">Dados operacionais</h2>

          <select
            value={form.status}
            onChange={(event) =>
              setForm((prev) => ({
                ...prev,
                status: event.target.value
              }))
            }
          >
            {statuses.map((option) => (
              <option key={option.codigo} value={option.codigo}>
                {option.nome}
              </option>
            ))}
          </select>

          <select
            value={form.prioridade}
            onChange={(event) =>
              setForm((prev) => ({
                ...prev,
                prioridade: event.target.value
              }))
            }
          >
            {priorities.map((priority) => (
              <option key={priority.codigo} value={priority.codigo}>
                {priority.nome}
              </option>
            ))}
          </select>

          <select
            value={form.tecnico_id}
            onChange={(event) =>
              setForm((prev) => ({
                ...prev,
                tecnico_id: event.target.value
              }))
            }
          >
            <option value="">Tecnico nao atribuido</option>
            {technicians.map((technician) => (
              <option key={technician.id} value={String(technician.id)}>
                {technician.nome}
              </option>
            ))}
          </select>

          <label>
            Data de entrada
            <input
              type="datetime-local"
              value={form.data_entrada}
              onChange={(event) =>
                setForm((prev) => ({
                  ...prev,
                  data_entrada: event.target.value
                }))
              }
            />
          </label>

          <label>
            Data de previsao
            <input
              type="datetime-local"
              value={form.data_previsao}
              onChange={(event) =>
                setForm((prev) => ({
                  ...prev,
                  data_previsao: event.target.value
                }))
              }
            />
          </label>

          <textarea
            rows={2}
            placeholder="Relato do cliente"
            value={form.relato_cliente}
            onChange={(event) =>
              setForm((prev) => ({
                ...prev,
                relato_cliente: event.target.value
              }))
            }
          />

          <h2 className="section-title">Defeitos e tecnico (somente edicao)</h2>

          <div className="defect-list">
            {defects.length === 0 ? (
              <p className="helper-line">{metaLoading ? "Carregando defeitos..." : "Sem defeitos cadastrados para o equipamento."}</p>
            ) : (
              defects.map((defect) => (
                <label className="defect-item" key={defect.id}>
                  <input
                    type="checkbox"
                    checked={selectedDefects.includes(defect.id)}
                    onChange={() => toggleDefect(defect.id)}
                  />
                  <span>
                    <strong>{defect.nome}</strong>
                    <small>{defect.descricao || defect.classificacao || "Defeito padrao"}</small>
                  </span>
                </label>
              ))
            )}
          </div>

          <ChecklistEntradaField
            value={checklistEntrada}
            onChange={setChecklistEntrada}
            disabled={!item?.equipamento_id}
          />

          <textarea
            rows={2}
            placeholder="Diagnostico tecnico"
            value={form.diagnostico_tecnico}
            onChange={(event) =>
              setForm((prev) => ({
                ...prev,
                diagnostico_tecnico: event.target.value
              }))
            }
          />

          <textarea
            rows={2}
            placeholder="Solucao aplicada"
            value={form.solucao_aplicada}
            onChange={(event) =>
              setForm((prev) => ({
                ...prev,
                solucao_aplicada: event.target.value
              }))
            }
          />

          <h2 className="section-title">Valores e garantia (somente edicao)</h2>

          <div className="mini-grid">
            <label>
              Mao de obra
              <input
                type="text"
                inputMode="decimal"
                value={form.valor_mao_obra}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    valor_mao_obra: event.target.value
                  }))
                }
              />
            </label>

            <label>
              Pecas
              <input
                type="text"
                inputMode="decimal"
                value={form.valor_pecas}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    valor_pecas: event.target.value
                  }))
                }
              />
            </label>

            <label>
              Desconto
              <input
                type="text"
                inputMode="decimal"
                value={form.desconto}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    desconto: event.target.value
                  }))
                }
              />
            </label>

            <label>
              Valor total
              <input
                type="text"
                inputMode="decimal"
                value={form.valor_total}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    valor_total: event.target.value
                  }))
                }
              />
            </label>

            <label>
              Valor final
              <input
                type="text"
                inputMode="decimal"
                value={form.valor_final}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    valor_final: event.target.value
                  }))
                }
              />
            </label>

            <label>
              Forma de pagamento
              <input
                type="text"
                value={form.forma_pagamento}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    forma_pagamento: event.target.value
                  }))
                }
              />
            </label>

            <label>
              Garantia (dias)
              <input
                type="number"
                min={0}
                value={form.garantia_dias}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    garantia_dias: event.target.value
                  }))
                }
              />
            </label>

            <label>
              Garantia validade
              <input
                type="datetime-local"
                value={form.garantia_validade}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    garantia_validade: event.target.value
                  }))
                }
              />
            </label>
          </div>

          <textarea
            rows={2}
            placeholder="Observacoes do cliente"
            value={form.observacoes_cliente}
            onChange={(event) =>
              setForm((prev) => ({
                ...prev,
                observacoes_cliente: event.target.value
              }))
            }
          />

          <textarea
            rows={2}
            placeholder="Observacoes internas"
            value={form.observacoes_internas}
            onChange={(event) =>
              setForm((prev) => ({
                ...prev,
                observacoes_internas: event.target.value
              }))
            }
          />

          <button type="button" onClick={onSave} disabled={saving}>
            {saving ? "Salvando..." : "Atualizar OS"}
          </button>
        </div>

        {success ? <p className="helper-line">{success}</p> : null}
        {error ? <p className="error-line">{error}</p> : null}
      </section>

      <MobileNav />
    </>
  );
}
