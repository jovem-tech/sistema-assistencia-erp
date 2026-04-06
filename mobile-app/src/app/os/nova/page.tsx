"use client";

import Link from "next/link";
import { useEffect, useMemo, useRef, useState, type KeyboardEvent } from "react";
import { useRouter } from "next/navigation";
import Cropper from "cropperjs";
import {
  ChecklistEntradaField,
  appendChecklistFiles,
  buildChecklistDraft,
  checklistIsComplete,
  serializeChecklistDraft,
  type ChecklistApiPayload,
  type ChecklistDraft
} from "@/components/ChecklistEntradaField";
import { MobileNav } from "@/components/MobileNav";
import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";

type ClientOption = {
  id: number;
  nome_razao: string;
  telefone1: string | null;
  email: string | null;
};

type EquipmentOption = {
  id: number;
  tipo_id: number;
  cliente_id?: number | null;
  tipo_nome?: string | null;
  marca_nome?: string | null;
  modelo_nome?: string | null;
  cor?: string | null;
  foto_url?: string | null;
  fotos?: EquipmentPhotoResponse[];
  label: string;
  numero_serie: string | null;
  imei: string | null;
};

type EquipmentPhotoResponse = {
  id: number;
  url: string;
  is_principal: number;
};

type TechnicianOption = {
  id: number;
  nome: string;
  cargo: string | null;
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

type ReportedDefectItem = {
  id: number;
  texto_relato: string;
  categoria: string;
};

type ReportedDefectGroup = {
  categoria: string;
  icone?: string | null;
  itens: ReportedDefectItem[];
};

type OrderMetaResponse = {
  clients: ClientOption[];
  equipments: EquipmentOption[];
  technicians: TechnicianOption[];
  statuses: StatusOption[];
  priorities: PriorityOption[];
  reported_defects?: ReportedDefectGroup[];
  checklist_entrada?: ChecklistApiPayload | null;
};

type EquipmentCatalogOption = {
  id: number;
  nome: string;
  ativo?: number;
  marca_id?: number;
};

type EquipmentCatalogResponse = {
  tipos: EquipmentCatalogOption[];
  marcas: EquipmentCatalogOption[];
  modelos: EquipmentCatalogOption[];
};

type EquipmentBrandOption = {
  id: number;
  nome: string;
  ativo?: number;
};

type EquipmentModelOption = {
  id: number;
  marca_id: number;
  nome: string;
  ativo?: number;
};

type ClientDetailResponse = ClientOption & {
  tipo_pessoa?: string | null;
  cpf_cnpj?: string | null;
  rg_ie?: string | null;
  telefone2?: string | null;
  nome_contato?: string | null;
  telefone_contato?: string | null;
  cep?: string | null;
  endereco?: string | null;
  numero?: string | null;
  complemento?: string | null;
  bairro?: string | null;
  cidade?: string | null;
  uf?: string | null;
  observacoes?: string | null;
};

type ViaCepResponse = {
  cep?: string;
  logradouro?: string;
  complemento?: string;
  bairro?: string;
  localidade?: string;
  uf?: string;
  erro?: boolean;
};

type EquipmentDetailResponse = EquipmentOption & {
  cliente_id?: number | null;
  marca_id?: number | null;
  modelo_id?: number | null;
  cor?: string | null;
  cor_hex?: string | null;
  cor_rgb?: string | null;
  numero_serie?: string | null;
  imei?: string | null;
  senha_acesso?: string | null;
  estado_fisico?: string | null;
  acessorios?: string | null;
  observacoes?: string | null;
  fotos?: EquipmentPhotoResponse[];
};

type CreateOrderResponse = {
  id: number;
  numero_os: string;
};

type ClientFormState = {
  nome_razao: string;
  telefone1: string;
  telefone2: string;
  email: string;
  nome_contato: string;
  telefone_contato: string;
  cep: string;
  cidade: string;
  uf: string;
  endereco: string;
  numero: string;
  bairro: string;
  complemento: string;
  observacoes: string;
};

type EquipmentFormState = {
  tipo_id: string;
  marca_id: string;
  modelo_id: string;
  marca_nome: string;
  modelo_nome: string;
  numero_serie: string;
  imei: string;
  cor: string;
  cor_hex: string;
  cor_rgb: string;
  senha_acesso: string;
  estado_fisico: string;
  acessorios: string;
  observacoes: string;
};

type FormState = {
  cliente_id: string;
  equipamento_id: string;
  tecnico_id: string;
  prioridade: string;
  status: string;
  relato_cliente: string;
  data_entrada: string;
  data_previsao: string;
  observacoes_cliente: string;
  observacoes_internas: string;
};

type AccessoryConfigField =
  | {
      name: string;
      label: string;
      placeholder?: string;
      type?: "text";
    }
  | {
      name: string;
      label: string;
      type: "select";
      options: Array<{ value: string; label: string }>;
    };

type AccessoryConfig = {
  key: string;
  title: string;
  fields: AccessoryConfigField[];
  format: (values: Record<string, string>) => string;
};

type AccessoryEntry = {
  id: string;
  key: string;
  title: string;
  text: string;
  values: Record<string, string>;
  photos: File[];
};

type ReviewFieldKey =
  | "cliente_id"
  | "equipamento_id"
  | "relato_cliente"
  | "tecnico_id"
  | "prioridade"
  | "status"
  | "data_entrada"
  | "data_previsao"
  | "acessorios"
  | "checklist"
  | "fotos_entrada"
  | "observacoes_cliente"
  | "observacoes_internas";

type ReviewFieldRow = {
  key: ReviewFieldKey;
  label: string;
  required: boolean;
  filled: boolean;
  value: string;
};

type OrderReviewSnapshot = {
  rows: ReviewFieldRow[];
  requiredMissing: ReviewFieldRow[];
  optionalMissing: ReviewFieldRow[];
};

type ClientNotificationMode = "none" | "message" | "message_pdf";

const fallbackStatusOptions: StatusOption[] = [
  { codigo: "triagem", nome: "Triagem", grupo_macro: "aberta", cor: "primary", ordem_fluxo: 1 },
  { codigo: "diagnostico", nome: "Diagnostico", grupo_macro: "aberta", cor: "primary", ordem_fluxo: 2 },
  { codigo: "aguardando_reparo", nome: "Aguardando reparo", grupo_macro: "aberta", cor: "warning", ordem_fluxo: 3 }
];

const fallbackPriorityOptions: PriorityOption[] = [
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

function parseDateTimeLocal(value: string): Date | null {
  if (!value) {
    return null;
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return null;
  }
  return date;
}

function addDays(base: Date, days: number): Date {
  const next = new Date(base.getTime());
  next.setDate(next.getDate() + days);
  return next;
}

function normalizeDigits(value: string): string {
  return value.replace(/\D/g, "");
}

function formatCep(value: string): string {
  const digits = normalizeDigits(value).slice(0, 8);
  if (digits.length <= 5) {
    return digits;
  }
  return `${digits.slice(0, 5)}-${digits.slice(5)}`;
}

function normalizeText(value: string): string {
  return value
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase();
}

const IMAGE_FILE_EXTENSION_REGEX = /\.(jpe?g|jfif|png|webp|gif|bmp|heic|heif|avif|tiff?)$/i;

function isLikelyImageFile(file: File): boolean {
  const mime = (file.type || "").toLowerCase();
  if (mime.startsWith("image/")) {
    return true;
  }
  return IMAGE_FILE_EXTENSION_REGEX.test((file.name || "").toLowerCase());
}

function splitSupportedImageFiles(filesList: FileList | null): { accepted: File[]; rejectedCount: number } {
  const files = Array.from(filesList || []);
  const accepted = files.filter((file) => isLikelyImageFile(file));
  return {
    accepted,
    rejectedCount: Math.max(0, files.length - accepted.length)
  };
}

function normalizeReportedDefectText(value: string): string {
  let output = String(value || "").trim();
  output = output.replace(/^Cliente relata:\s*/i, "");
  output = output.replace(/[.;:,\s]+$/g, "").trim();
  return output;
}

function formatClientLabel(client: ClientOption): string {
  return client.nome_razao;
}

function formatEquipmentPrimaryLine(equipment: EquipmentOption): string {
  const tipo = (equipment.tipo_nome || "").trim();
  const marca = (equipment.marca_nome || "").trim();
  const parts = [tipo, marca].filter(Boolean);
  if (parts.length > 0) {
    return parts.join(" - ");
  }
  return equipment.label || `Equipamento #${equipment.id}`;
}

function formatEquipmentSecondaryLine(equipment: EquipmentOption): string {
  const modelo = (equipment.modelo_nome || "").trim();
  const cor = (equipment.cor || "").trim();
  const parts = [modelo, cor].filter(Boolean);
  return parts.join(" - ");
}

function formatEquipmentIdentityLine(equipment: EquipmentOption): string {
  const serie = (equipment.numero_serie || "").trim();
  const imei = (equipment.imei || "").trim();

  if (serie && imei) {
    return `Nº serie: ${serie} | IMEI: ${imei}`;
  }
  if (serie) {
    return `Nº serie: ${serie}`;
  }
  if (imei) {
    return `IMEI: ${imei}`;
  }
  return "Sem numero de serie ou IMEI";
}

function formatEquipmentSelectionLabel(equipment: EquipmentOption): string {
  const primary = formatEquipmentPrimaryLine(equipment);
  const secondary = formatEquipmentSecondaryLine(equipment);
  const identity = formatEquipmentIdentityLine(equipment);
  return [primary, secondary, identity].filter(Boolean).join(" | ");
}

function formatEquipmentPreferredIdentityLine(equipment: EquipmentOption): string {
  const serie = (equipment.numero_serie || "").trim();
  const imei = (equipment.imei || "").trim();

  if (serie) {
    return `N/S: ${serie}`;
  }
  if (imei) {
    return `IMEI: ${imei}`;
  }
  return "";
}

function getEquipmentPhotoUrls(equipment: EquipmentOption): string[] {
  const collection = (equipment.fotos || [])
    .map((photo) => (photo.url || "").trim())
    .filter(Boolean);

  if (collection.length > 0) {
    return collection;
  }

  const primary = (equipment.foto_url || "").trim();
  return primary ? [primary] : [];
}

function formatEquipmentGalleryTitle(equipment: EquipmentOption): string {
  return [formatEquipmentPrimaryLine(equipment), formatEquipmentSecondaryLine(equipment)].filter(Boolean).join(" | ");
}

function equipmentSearchText(equipment: EquipmentOption): string {
  return [
    equipment.label,
    equipment.tipo_nome,
    equipment.marca_nome,
    equipment.modelo_nome,
    equipment.cor,
    equipment.numero_serie,
    equipment.imei,
    formatEquipmentPrimaryLine(equipment),
    formatEquipmentSecondaryLine(equipment),
    formatEquipmentIdentityLine(equipment)
  ]
    .filter(Boolean)
    .join(" ");
}

function equipmentFallbackLabel(equipment: EquipmentOption): string {
  const preferred = (equipment.tipo_nome || equipment.marca_nome || equipment.modelo_nome || "EQ").trim();
  return preferred.charAt(0).toUpperCase() || "E";
}

function toCollectionItems(rawText: string, key: string): Array<{ text: string; key: string }> {
  return rawText
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .map((text) => ({ text, key }));
}

function composeAccessoryText(base: string, detail = ""): string {
  const cleanDetail = detail.trim();
  return cleanDetail ? `${base} ${cleanDetail}` : base;
}

const accessoryConfigs: AccessoryConfig[] = [
  {
    key: "chip",
    title: "Chip",
    fields: [{ name: "chip_digits", label: "Ultimos 6 digitos", placeholder: "123456" }],
    format: (values) => composeAccessoryText("Chip", values.chip_digits ? `final ${values.chip_digits}` : "")
  },
  {
    key: "capinha",
    title: "Capinha celular",
    fields: [{ name: "cor", label: "Cor", placeholder: "Preta" }],
    format: (values) => composeAccessoryText("Capinha celular", values.cor || "")
  },
  {
    key: "capa",
    title: "Capa",
    fields: [],
    format: () => "Capa"
  },
  {
    key: "mochila",
    title: "Mochila",
    fields: [{ name: "cor", label: "Cor", placeholder: "Preta" }],
    format: (values) => composeAccessoryText("Mochila", values.cor || "")
  },
  {
    key: "bolsa",
    title: "Bolsa notebook",
    fields: [{ name: "cor", label: "Cor", placeholder: "Cinza" }],
    format: (values) => composeAccessoryText("Bolsa notebook", values.cor || "")
  },
  {
    key: "cabo",
    title: "Cabo",
    fields: [
      {
        name: "tipo",
        label: "Tipo",
        type: "select",
        options: [
          { value: "", label: "Selecionar tipo" },
          { value: "USB-C", label: "USB-C" },
          { value: "Micro USB", label: "Micro USB" },
          { value: "Lightning", label: "Lightning" },
          { value: "HDMI", label: "HDMI" },
          { value: "Cabo de forca", label: "Cabo de forca" },
          { value: "Outro", label: "Outro" }
        ]
      },
      { name: "tipo_outro", label: "Outro tipo", placeholder: "Especifique" }
    ],
    format: (values) => composeAccessoryText("Cabo", values.tipo === "Outro" ? values.tipo_outro || "" : values.tipo || "")
  },
  {
    key: "carregador",
    title: "Carregador",
    fields: [
      {
        name: "tipo_equip",
        label: "Tipo de equipamento",
        type: "select",
        options: [
          { value: "", label: "Selecionar tipo" },
          { value: "Celular", label: "Celular" },
          { value: "Notebook", label: "Notebook" },
          { value: "Tablet", label: "Tablet" },
          { value: "Outro", label: "Outro" }
        ]
      }
    ],
    format: (values) => composeAccessoryText("Carregador", values.tipo_equip || "")
  },
  {
    key: "outro",
    title: "Outro acessorio",
    fields: [{ name: "descricao", label: "Descricao", placeholder: "Ex: adaptador, fone, controle..." }],
    format: (values) => values.descricao?.trim() || "Outro acessorio"
  }
];

function emptyAccessoryValues(config: AccessoryConfig | null): Record<string, string> {
  if (!config) {
    return {};
  }
  return Object.fromEntries(config.fields.map((field) => [field.name, ""]));
}

function generateAccessoryId(): string {
  return `acc_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
}

function emptyClientForm(): ClientFormState {
  return {
    nome_razao: "",
    telefone1: "",
    telefone2: "",
    email: "",
    nome_contato: "",
    telefone_contato: "",
    cep: "",
    cidade: "",
    uf: "",
    endereco: "",
    numero: "",
    bairro: "",
    complemento: "",
    observacoes: ""
  };
}

function emptyEquipmentForm(): EquipmentFormState {
  return {
    tipo_id: "",
    marca_id: "",
    modelo_id: "",
    marca_nome: "",
    modelo_nome: "",
    numero_serie: "",
    imei: "",
    cor: "Preto",
    cor_hex: "#1A1A1A",
    cor_rgb: "26, 26, 26",
    senha_acesso: "",
    estado_fisico: "",
    acessorios: "",
    observacoes: ""
  };
}

const EQUIPMENT_PHOTO_MAX_FILES = 4;
const ENTRY_PHOTO_MAX_FILES = 4;

const colorCatalog: Array<{ name: string; hex: string }> = [
  { name: "Preto", hex: "#1A1A1A" },
  { name: "Branco", hex: "#F8F9FA" },
  { name: "Cinza", hex: "#9CA3AF" },
  { name: "Prata", hex: "#C0C0C0" },
  { name: "Dourado", hex: "#D4AF37" },
  { name: "Azul", hex: "#2563EB" },
  { name: "Vermelho", hex: "#DC2626" },
  { name: "Verde", hex: "#16A34A" },
  { name: "Rosa", hex: "#EC4899" },
  { name: "Roxo", hex: "#7C3AED" },
  { name: "Amarelo", hex: "#EAB308" },
  { name: "Marrom", hex: "#92400E" }
];

function catalogColor(name: string): { name: string; hex: string } {
  return colorCatalog.find((item) => item.name === name) || colorCatalog[0];
}

const groupedColorCatalog: Array<{ label: string; items: Array<{ name: string; hex: string }> }> = [
  {
    label: "Neutras",
    items: [catalogColor("Preto"), catalogColor("Branco"), catalogColor("Cinza"), catalogColor("Prata")]
  },
  {
    label: "Metalizadas",
    items: [catalogColor("Dourado")]
  },
  {
    label: "Frias",
    items: [catalogColor("Azul"), catalogColor("Verde"), catalogColor("Roxo")]
  },
  {
    label: "Quentes",
    items: [catalogColor("Vermelho"), catalogColor("Rosa"), catalogColor("Amarelo"), catalogColor("Marrom")]
  }
];

function normalizeHexColor(value: string): string {
  const raw = value.trim().replace(/^#/, "");
  if (!/^[0-9a-fA-F]{3,6}$/.test(raw)) {
    return "#1A1A1A";
  }

  if (raw.length === 3) {
    const expanded = raw
      .split("")
      .map((ch) => `${ch}${ch}`)
      .join("");
    return `#${expanded.toUpperCase()}`;
  }

  return `#${raw.slice(0, 6).toUpperCase()}`;
}

function hexToRgbString(hex: string): string {
  const safeHex = normalizeHexColor(hex);
  const value = safeHex.replace("#", "");
  const r = parseInt(value.slice(0, 2), 16);
  const g = parseInt(value.slice(2, 4), 16);
  const b = parseInt(value.slice(4, 6), 16);
  return `${r}, ${g}, ${b}`;
}

function closestColorName(hex: string): string {
  const safeHex = normalizeHexColor(hex).replace("#", "");
  const targetR = parseInt(safeHex.slice(0, 2), 16);
  const targetG = parseInt(safeHex.slice(2, 4), 16);
  const targetB = parseInt(safeHex.slice(4, 6), 16);

  let winner = colorCatalog[0];
  let bestDistance = Number.POSITIVE_INFINITY;

  for (const color of colorCatalog) {
    const value = color.hex.replace("#", "");
    const r = parseInt(value.slice(0, 2), 16);
    const g = parseInt(value.slice(2, 4), 16);
    const b = parseInt(value.slice(4, 6), 16);
    const distance = Math.sqrt((targetR - r) ** 2 + (targetG - g) ** 2 + (targetB - b) ** 2);
    if (distance < bestDistance) {
      bestDistance = distance;
      winner = color;
    }
  }

  return winner.name;
}

async function detectDominantHexFromImage(file: File): Promise<string | null> {
  try {
    const bitmap = await createImageBitmap(file);
    const sampleSize = 80;
    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext("2d");
    if (!ctx) {
      bitmap.close();
      return null;
    }

    const scale = Math.min(sampleSize / Math.max(bitmap.width, 1), sampleSize / Math.max(bitmap.height, 1), 1);
    const width = Math.max(1, Math.round(bitmap.width * scale));
    const height = Math.max(1, Math.round(bitmap.height * scale));
    canvas.width = width;
    canvas.height = height;
    ctx.drawImage(bitmap, 0, 0, width, height);
    bitmap.close();

    const imageData = ctx.getImageData(0, 0, width, height);
    let totalR = 0;
    let totalG = 0;
    let totalB = 0;
    let count = 0;

    for (let i = 0; i < imageData.data.length; i += 4) {
      const alpha = imageData.data[i + 3];
      if (alpha < 20) {
        continue;
      }
      totalR += imageData.data[i];
      totalG += imageData.data[i + 1];
      totalB += imageData.data[i + 2];
      count += 1;
    }

    if (count <= 0) {
      return null;
    }

    const r = Math.round(totalR / count);
    const g = Math.round(totalG / count);
    const b = Math.round(totalB / count);
    return `#${[r, g, b].map((v) => v.toString(16).padStart(2, "0")).join("").toUpperCase()}`;
  } catch (error) {
    console.error("[Mobile OS] falha ao detectar cor dominante da foto", error);
    return null;
  }
}

export default function NewOrderPage() {
  const router = useRouter();
  const [loadingMeta, setLoadingMeta] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [clientQuery, setClientQuery] = useState("");
  const [clientPickerOpen, setClientPickerOpen] = useState(false);
  const [equipmentQuery, setEquipmentQuery] = useState("");
  const [equipmentPickerOpen, setEquipmentPickerOpen] = useState(false);
  const [headerMenuOpen, setHeaderMenuOpen] = useState(false);
  const [forecastPreset, setForecastPreset] = useState("3");
  const [entryPhotos, setEntryPhotos] = useState<File[]>([]);
  const [entryCropQueue, setEntryCropQueue] = useState<File[]>([]);
  const [entryCropFile, setEntryCropFile] = useState<File | null>(null);
  const [entryCropSource, setEntryCropSource] = useState("");
  const [entryCropOpen, setEntryCropOpen] = useState(false);
  const [entryCropBusy, setEntryCropBusy] = useState(false);
  const [entryPreviewOpen, setEntryPreviewOpen] = useState(false);
  const [entryPreviewSource, setEntryPreviewSource] = useState("");
  const [entryPreviewTitle, setEntryPreviewTitle] = useState("");
  const [equipmentGalleryOpen, setEquipmentGalleryOpen] = useState(false);
  const [equipmentGalleryTitle, setEquipmentGalleryTitle] = useState("");
  const [equipmentGalleryPhotos, setEquipmentGalleryPhotos] = useState<string[]>([]);
  const [equipmentGalleryActiveIndex, setEquipmentGalleryActiveIndex] = useState(0);
  const [clients, setClients] = useState<ClientOption[]>([]);
  const [equipments, setEquipments] = useState<EquipmentOption[]>([]);
  const [technicians, setTechnicians] = useState<TechnicianOption[]>([]);
  const [statuses, setStatuses] = useState<StatusOption[]>(fallbackStatusOptions);
  const [priorities, setPriorities] = useState<PriorityOption[]>(fallbackPriorityOptions);
  const [reportedDefectGroups, setReportedDefectGroups] = useState<ReportedDefectGroup[]>([]);
  const [reportedDefectModalOpen, setReportedDefectModalOpen] = useState(false);
  const [reportedDefectCategory, setReportedDefectCategory] = useState("");
  const [clientModalMode, setClientModalMode] = useState<"create" | "edit" | null>(null);
  const [clientModalSaving, setClientModalSaving] = useState(false);
  const [clientModalError, setClientModalError] = useState("");
  const [clientCepLoading, setClientCepLoading] = useState(false);
  const [clientCepHint, setClientCepHint] = useState("");
  const [clientForm, setClientForm] = useState<ClientFormState>(emptyClientForm);
  const [equipmentModalMode, setEquipmentModalMode] = useState<"create" | "edit" | null>(null);
  const [equipmentModalSaving, setEquipmentModalSaving] = useState(false);
  const [equipmentModalError, setEquipmentModalError] = useState("");
  const [equipmentCatalogLoading, setEquipmentCatalogLoading] = useState(false);
  const [equipmentCatalog, setEquipmentCatalog] = useState<EquipmentCatalogResponse>({
    tipos: [],
    marcas: [],
    modelos: []
  });
  const [brandPickerOpen, setBrandPickerOpen] = useState(false);
  const [brandQuery, setBrandQuery] = useState("");
  const [brandModalOpen, setBrandModalOpen] = useState(false);
  const [brandModalSaving, setBrandModalSaving] = useState(false);
  const [brandModalError, setBrandModalError] = useState("");
  const [brandFormName, setBrandFormName] = useState("");
  const [modelPickerOpen, setModelPickerOpen] = useState(false);
  const [modelQuery, setModelQuery] = useState("");
  const [modelModalOpen, setModelModalOpen] = useState(false);
  const [modelModalSaving, setModelModalSaving] = useState(false);
  const [modelModalError, setModelModalError] = useState("");
  const [modelFormName, setModelFormName] = useState("");
  const [colorModalOpen, setColorModalOpen] = useState(false);
  const [equipmentForm, setEquipmentForm] = useState<EquipmentFormState>(emptyEquipmentForm);
  const [equipmentExistingPhotos, setEquipmentExistingPhotos] = useState<EquipmentPhotoResponse[]>([]);
  const [equipmentNewPhotos, setEquipmentNewPhotos] = useState<File[]>([]);
  const [equipmentSuggestedColorHex, setEquipmentSuggestedColorHex] = useState<string | null>(null);
  const [equipmentCropQueue, setEquipmentCropQueue] = useState<File[]>([]);
  const [equipmentCropFile, setEquipmentCropFile] = useState<File | null>(null);
  const [equipmentCropSource, setEquipmentCropSource] = useState("");
  const [equipmentCropOpen, setEquipmentCropOpen] = useState(false);
  const [equipmentCropBusy, setEquipmentCropBusy] = useState(false);
  const [accessoryEntries, setAccessoryEntries] = useState<AccessoryEntry[]>([]);
  const [accessoryModalOpen, setAccessoryModalOpen] = useState(false);
  const [accessoryModalError, setAccessoryModalError] = useState("");
  const [accessoryDraftKey, setAccessoryDraftKey] = useState("outro");
  const [accessoryDraftValues, setAccessoryDraftValues] = useState<Record<string, string>>(() => {
    const config = accessoryConfigs.find((item) => item.key === "outro") || null;
    return emptyAccessoryValues(config);
  });
  const [accessoryDraftPhotos, setAccessoryDraftPhotos] = useState<File[]>([]);
  const [accessoryEditingId, setAccessoryEditingId] = useState<string | null>(null);
  const [accessoryCropQueue, setAccessoryCropQueue] = useState<File[]>([]);
  const [accessoryCropFile, setAccessoryCropFile] = useState<File | null>(null);
  const [accessoryCropSource, setAccessoryCropSource] = useState("");
  const [accessoryCropOpen, setAccessoryCropOpen] = useState(false);
  const [accessoryCropBusy, setAccessoryCropBusy] = useState(false);
  const [accessoryPreviewOpen, setAccessoryPreviewOpen] = useState(false);
  const [accessoryPreviewSource, setAccessoryPreviewSource] = useState("");
  const [accessoryPreviewTitle, setAccessoryPreviewTitle] = useState("");
  const accessoryGalleryInputRef = useRef<HTMLInputElement | null>(null);
  const accessoryCameraInputRef = useRef<HTMLInputElement | null>(null);
  const accessoryCropImageRef = useRef<HTMLImageElement | null>(null);
  const accessoryCropperRef = useRef<Cropper | null>(null);
  const accessoryCropObjectUrlRef = useRef<string | null>(null);
  const accessoryPreviewObjectUrlRef = useRef<string | null>(null);
  const [form, setForm] = useState<FormState>(() => {
    const now = new Date();
    return {
      cliente_id: "",
      equipamento_id: "",
      tecnico_id: "",
      prioridade: "normal",
      status: "triagem",
      relato_cliente: "",
      data_entrada: formatDateTimeLocal(now),
      data_previsao: formatDateTimeLocal(addDays(now, 3)),
      observacoes_cliente: "",
      observacoes_internas: ""
    };
  });
  const [checklistEntrada, setChecklistEntrada] = useState<ChecklistDraft>(() => buildChecklistDraft(null));
  const [orderReviewOpen, setOrderReviewOpen] = useState(false);
  const [orderReviewStep, setOrderReviewStep] = useState<"summary" | "notify">("summary");
  const [orderReviewSnapshot, setOrderReviewSnapshot] = useState<OrderReviewSnapshot | null>(null);
  const [orderReviewError, setOrderReviewError] = useState("");
  const [clientNotificationMode, setClientNotificationMode] = useState<ClientNotificationMode>("none");

  const clientComboRef = useRef<HTMLDivElement | null>(null);
  const equipmentComboRef = useRef<HTMLDivElement | null>(null);
  const relatoFieldRef = useRef<HTMLTextAreaElement | null>(null);
  const tecnicoFieldRef = useRef<HTMLInputElement | HTMLSelectElement | null>(null);
  const prioridadeFieldRef = useRef<HTMLSelectElement | null>(null);
  const statusFieldRef = useRef<HTMLSelectElement | null>(null);
  const dataEntradaFieldRef = useRef<HTMLInputElement | null>(null);
  const dataPrevisaoFieldRef = useRef<HTMLInputElement | null>(null);
  const acessoriosSectionRef = useRef<HTMLElement | null>(null);
  const checklistSectionRef = useRef<HTMLElement | null>(null);
  const fotosEntradaSectionRef = useRef<HTMLElement | null>(null);
  const observacoesClienteRef = useRef<HTMLTextAreaElement | null>(null);
  const observacoesInternasRef = useRef<HTMLTextAreaElement | null>(null);
  const brandComboRef = useRef<HTMLDivElement | null>(null);
  const modelComboRef = useRef<HTMLDivElement | null>(null);
  const headerMenuRef = useRef<HTMLDivElement | null>(null);
  const clientSearchTimerRef = useRef<number | null>(null);
  const clientCepAbortRef = useRef<AbortController | null>(null);
  const lastClientCepLookupRef = useRef("");
  const equipmentGalleryInputRef = useRef<HTMLInputElement | null>(null);
  const equipmentCameraInputRef = useRef<HTMLInputElement | null>(null);
  const entryGalleryInputRef = useRef<HTMLInputElement | null>(null);
  const entryCameraInputRef = useRef<HTMLInputElement | null>(null);
  const entryCropImageRef = useRef<HTMLImageElement | null>(null);
  const entryCropperRef = useRef<Cropper | null>(null);
  const entryCropObjectUrlRef = useRef<string | null>(null);
  const entryPreviewObjectUrlRef = useRef<string | null>(null);
  const equipmentCropImageRef = useRef<HTMLImageElement | null>(null);
  const equipmentCropperRef = useRef<Cropper | null>(null);
  const equipmentCropObjectUrlRef = useRef<string | null>(null);
  const suppressAutoClientSearchRef = useRef(false);
  const selectedClient = useMemo(
    () => clients.find((client) => String(client.id) === form.cliente_id) ?? null,
    [clients, form.cliente_id]
  );
  const selectedEquipment = useMemo(
    () => equipments.find((equipment) => String(equipment.id) === form.equipamento_id) ?? null,
    [equipments, form.equipamento_id]
  );
  const selectedClientPhone = useMemo(() => (selectedClient?.telefone1 || "").trim(), [selectedClient]);
  const activeReportedDefectGroup = useMemo(() => {
    if (reportedDefectGroups.length === 0) {
      return null;
    }

    return (
      reportedDefectGroups.find((group) => group.categoria === reportedDefectCategory) ||
      reportedDefectGroups[0]
    );
  }, [reportedDefectCategory, reportedDefectGroups]);
  const singleTechnician = technicians.length === 1 ? technicians[0] : null;
  const activeAccessoryConfig = useMemo(
    () => accessoryConfigs.find((item) => item.key === accessoryDraftKey) || accessoryConfigs[accessoryConfigs.length - 1],
    [accessoryDraftKey]
  );
  const selectedBrand = useMemo(
    () => equipmentCatalog.marcas.find((marca) => String(marca.id) === equipmentForm.marca_id) ?? null,
    [equipmentCatalog.marcas, equipmentForm.marca_id]
  );
  const filteredModelsByBrand = useMemo(() => {
    const brandId = Number(equipmentForm.marca_id || "0");
    if (brandId <= 0) {
      return [];
    }
    return equipmentCatalog.modelos.filter((item) => Number(item.marca_id || 0) === brandId);
  }, [equipmentCatalog.modelos, equipmentForm.marca_id]);
  const selectedModel = useMemo(
    () => filteredModelsByBrand.find((modelo) => String(modelo.id) === equipmentForm.modelo_id) ?? null,
    [filteredModelsByBrand, equipmentForm.modelo_id]
  );
  const equipmentNewPhotoPreviews = useMemo(
    () =>
      equipmentNewPhotos.map((file, index) => ({
        file,
        index,
        url: URL.createObjectURL(file)
      })),
    [equipmentNewPhotos]
  );
  const entryPhotoPreviews = useMemo(
    () =>
      entryPhotos.map((file, index) => ({
        file,
        index,
        url: URL.createObjectURL(file)
      })),
    [entryPhotos]
  );
  const normalizedEquipmentColorHex = useMemo(
    () => normalizeHexColor(equipmentForm.cor_hex),
    [equipmentForm.cor_hex]
  );

  const filteredClients = useMemo(() => {
    const query = clientQuery.trim();
    if (!query) {
      return clients;
    }

    const normalizedQuery = normalizeText(query);
    const queryDigits = normalizeDigits(query);
    return clients.filter((client) => {
      const name = normalizeText(client.nome_razao || "");
      const email = normalizeText(client.email || "");
      const phoneDigits = normalizeDigits(client.telefone1 || "");

      return (
        name.includes(normalizedQuery) ||
        email.includes(normalizedQuery) ||
        (queryDigits !== "" && phoneDigits.includes(queryDigits))
      );
    });
  }, [clients, clientQuery]);
  const filteredEquipments = useMemo(() => {
    const query = equipmentQuery.trim();
    if (!query) {
      return equipments;
    }

    const normalizedQuery = normalizeText(query);
    const queryDigits = normalizeDigits(query);
    return equipments.filter((equipment) => {
      const normalizedText = normalizeText(equipmentSearchText(equipment));
      const serieDigits = normalizeDigits(equipment.numero_serie || "");
      const imeiDigits = normalizeDigits(equipment.imei || "");

      return (
        normalizedText.includes(normalizedQuery) ||
        (queryDigits !== "" && (serieDigits.includes(queryDigits) || imeiDigits.includes(queryDigits)))
      );
    });
  }, [equipments, equipmentQuery]);
  const filteredBrands = useMemo(() => {
    const query = normalizeText(brandQuery);
    if (!query) {
      return equipmentCatalog.marcas;
    }
    return equipmentCatalog.marcas.filter((marca) => normalizeText(marca.nome || "").includes(query));
  }, [brandQuery, equipmentCatalog.marcas]);
  const brandModalSuggestions = useMemo(() => {
    const query = normalizeText(brandFormName.trim());
    const candidates = equipmentCatalog.marcas
      .filter((marca) => {
        const name = normalizeText(marca.nome || "");
        return query ? name.includes(query) : true;
      })
      .sort((a, b) => {
        const aName = normalizeText(a.nome || "");
        const bName = normalizeText(b.nome || "");
        const aStarts = query ? aName.startsWith(query) : false;
        const bStarts = query ? bName.startsWith(query) : false;
        if (aStarts !== bStarts) {
          return aStarts ? -1 : 1;
        }
        return (a.nome || "").localeCompare(b.nome || "", "pt-BR");
      });

    return candidates.slice(0, 8);
  }, [brandFormName, equipmentCatalog.marcas]);
  const exactBrandModalMatch = useMemo(() => {
    const query = normalizeText(brandFormName.trim());
    if (!query) {
      return null;
    }

    return (
      equipmentCatalog.marcas.find((marca) => normalizeText(marca.nome || "") === query) ?? null
    );
  }, [brandFormName, equipmentCatalog.marcas]);
  const filteredModelOptions = useMemo(() => {
    const query = normalizeText(modelQuery);
    if (!query) {
      return filteredModelsByBrand;
    }
    return filteredModelsByBrand.filter((modelo) => normalizeText(modelo.nome || "").includes(query));
  }, [filteredModelsByBrand, modelQuery]);
  const modelModalSuggestions = useMemo(() => {
    const query = normalizeText(modelFormName.trim());
    const candidates = filteredModelsByBrand
      .filter((modelo) => {
        const name = normalizeText(modelo.nome || "");
        return query ? name.includes(query) : true;
      })
      .sort((a, b) => {
        const aName = normalizeText(a.nome || "");
        const bName = normalizeText(b.nome || "");
        const aStarts = query ? aName.startsWith(query) : false;
        const bStarts = query ? bName.startsWith(query) : false;
        if (aStarts !== bStarts) {
          return aStarts ? -1 : 1;
        }
        return (a.nome || "").localeCompare(b.nome || "", "pt-BR");
      });

    return candidates.slice(0, 8);
  }, [filteredModelsByBrand, modelFormName]);
  const exactModelModalMatch = useMemo(() => {
    const query = normalizeText(modelFormName.trim());
    if (!query) {
      return null;
    }

    return (
      filteredModelsByBrand.find((modelo) => normalizeText(modelo.nome || "") === query) ?? null
    );
  }, [filteredModelsByBrand, modelFormName]);

  async function loadMeta(params?: { q?: string; clienteId?: string; equipamentoId?: string }) {
    setLoadingMeta(true);
    setError("");
    try {
      const query = new URLSearchParams();
      const q = (params?.q ?? "").trim();
      const clienteId = params?.clienteId ?? form.cliente_id;
      const equipamentoId = params?.equipamentoId ?? form.equipamento_id;

      if (q !== "") {
        query.set("q", q);
      }
      if (clienteId) {
        query.set("cliente_id", clienteId);
      }
      if (equipamentoId) {
        query.set("equipamento_id", equipamentoId);
      }

      const endpoint = query.size > 0 ? `/orders/meta?${query.toString()}` : "/orders/meta";
      const data = await apiRequest<OrderMetaResponse>(endpoint);
      const nextStatuses = data.statuses?.length ? data.statuses : fallbackStatusOptions;
      const nextPriorities = data.priorities?.length ? data.priorities : fallbackPriorityOptions;
      const equipmentById = new Map<number, EquipmentOption>();
      (data.equipments || []).forEach((equipment) => {
        if (!equipmentById.has(equipment.id)) {
          equipmentById.set(equipment.id, equipment);
        }
      });

      setClients(data.clients || []);
      setEquipments(Array.from(equipmentById.values()));
      setTechnicians(data.technicians || []);
      setStatuses(nextStatuses);
      setPriorities(nextPriorities);
      const nextReportedDefects = (data.reported_defects || [])
        .map((group) => ({
          categoria: String(group.categoria || "").trim() || "Outros",
          icone: String(group.icone || "").trim(),
          itens: (group.itens || [])
            .map((item) => ({
              id: Number(item.id || 0),
              texto_relato: String(item.texto_relato || "").trim(),
              categoria: String(item.categoria || group.categoria || "").trim()
            }))
            .filter((item) => item.id > 0 && item.texto_relato !== "")
        }))
        .filter((group) => group.itens.length > 0);
      setReportedDefectGroups(nextReportedDefects);
      setReportedDefectCategory((prev) => {
        if (nextReportedDefects.length === 0) {
          return "";
        }
        const stillExists = nextReportedDefects.some((group) => group.categoria === prev);
        if (stillExists) {
          return prev;
        }
        return nextReportedDefects[0].categoria;
      });
      setChecklistEntrada(buildChecklistDraft(data.checklist_entrada ?? null));

      setForm((prev) => {
        const statusValue = prev.status || nextStatuses[0]?.codigo || "triagem";
        const priorityValue = prev.prioridade || nextPriorities[0]?.codigo || "normal";
        return {
          ...prev,
          status: statusValue,
          prioridade: priorityValue
        };
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha ao carregar dados da OS.");
    } finally {
      setLoadingMeta(false);
    }
  }

  useEffect(() => {
    if (!getSession()?.accessToken) {
      router.replace("/login");
      return;
    }

    loadMeta();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [router]);

  useEffect(() => {
    function onDocumentMouseDown(event: MouseEvent) {
      const target = event.target as Node;
      if (clientComboRef.current && !clientComboRef.current.contains(target)) {
        setClientPickerOpen(false);
      }
      if (equipmentComboRef.current && !equipmentComboRef.current.contains(target)) {
        setEquipmentPickerOpen(false);
      }
      if (brandComboRef.current && !brandComboRef.current.contains(target)) {
        setBrandPickerOpen(false);
      }
      if (modelComboRef.current && !modelComboRef.current.contains(target)) {
        setModelPickerOpen(false);
      }
      if (headerMenuRef.current && !headerMenuRef.current.contains(target)) {
        setHeaderMenuOpen(false);
      }
    }

    document.addEventListener("mousedown", onDocumentMouseDown);
    return () => {
      document.removeEventListener("mousedown", onDocumentMouseDown);
    };
  }, []);

  useEffect(() => {
    return () => {
      equipmentNewPhotoPreviews.forEach((item) => {
        URL.revokeObjectURL(item.url);
      });
    };
  }, [equipmentNewPhotoPreviews]);

  useEffect(() => {
    return () => {
      entryPhotoPreviews.forEach((item) => {
        URL.revokeObjectURL(item.url);
      });
    };
  }, [entryPhotoPreviews]);

  useEffect(() => {
    if (suppressAutoClientSearchRef.current) {
      suppressAutoClientSearchRef.current = false;
      return;
    }

    const query = clientQuery.trim();
    if (query.length < 2) {
      if (clientSearchTimerRef.current !== null) {
        window.clearTimeout(clientSearchTimerRef.current);
      }
      return;
    }

    if (clientSearchTimerRef.current !== null) {
      window.clearTimeout(clientSearchTimerRef.current);
    }

    clientSearchTimerRef.current = window.setTimeout(() => {
      void loadMeta({
        q: query,
        clienteId: "",
        equipamentoId: ""
      });
    }, 350);

    return () => {
      if (clientSearchTimerRef.current !== null) {
        window.clearTimeout(clientSearchTimerRef.current);
      }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [clientQuery]);

  useEffect(() => {
    if (clientPickerOpen) {
      return;
    }

    const nextLabel = selectedClient ? formatClientLabel(selectedClient) : "";
    setClientQuery((prev) => {
      if (prev === nextLabel) {
        return prev;
      }
      suppressAutoClientSearchRef.current = true;
      return nextLabel;
    });
  }, [selectedClient, clientPickerOpen]);

  useEffect(() => {
    if (equipmentPickerOpen) {
      return;
    }

    const nextLabel = selectedEquipment ? formatEquipmentSelectionLabel(selectedEquipment) : "";
    setEquipmentQuery((prev) => (prev === nextLabel ? prev : nextLabel));
  }, [selectedEquipment, equipmentPickerOpen]);

  useEffect(() => {
    if (technicians.length === 1) {
      const technicianId = String(technicians[0].id);
      setForm((prev) => {
        if (prev.tecnico_id === technicianId) {
          return prev;
        }

        return {
          ...prev,
          tecnico_id: technicianId
        };
      });
      return;
    }

    if (technicians.length > 1) {
      setForm((prev) => {
        if (!prev.tecnico_id) {
          return prev;
        }

        const stillExists = technicians.some((technician) => String(technician.id) === prev.tecnico_id);
        if (stillExists) {
          return prev;
        }

        return {
          ...prev,
          tecnico_id: ""
        };
      });
    }
  }, [technicians]);

  useEffect(() => {
    if (!equipmentModalMode) {
      setBrandQuery("");
      setModelQuery("");
      setBrandPickerOpen(false);
      setModelPickerOpen(false);
      return;
    }

    if (!brandPickerOpen) {
      setBrandQuery(selectedBrand?.nome || "");
    }
    if (!modelPickerOpen) {
      setModelQuery(selectedModel?.nome || "");
    }
  }, [equipmentModalMode, selectedBrand, selectedModel, brandPickerOpen, modelPickerOpen]);

  useEffect(() => {
    if (forecastPreset === "custom") {
      return;
    }

    const days = Number(forecastPreset);
    if (!Number.isFinite(days)) {
      return;
    }

    const baseDate = parseDateTimeLocal(form.data_entrada) || new Date();
    const nextForecast = formatDateTimeLocal(addDays(baseDate, days));

    setForm((prev) => {
      if (prev.data_previsao === nextForecast) {
        return prev;
      }
      return {
        ...prev,
        data_previsao: nextForecast
      };
    });
  }, [forecastPreset, form.data_entrada]);

  useEffect(() => {
    if (entryCropFile || entryCropOpen || entryCropQueue.length === 0) {
      return;
    }

    const [nextFile, ...rest] = entryCropQueue;
    const objectUrl = URL.createObjectURL(nextFile);
    entryCropObjectUrlRef.current = objectUrl;
    setEntryCropQueue(rest);
    setEntryCropFile(nextFile);
    setEntryCropSource(objectUrl);
    setEntryCropOpen(true);
  }, [entryCropFile, entryCropOpen, entryCropQueue]);

  useEffect(() => {
    if (!entryCropOpen || !entryCropSource || !entryCropImageRef.current) {
      return;
    }

    let cancelled = false;
    let instance: Cropper | null = null;

    try {
      if (cancelled || !entryCropImageRef.current) {
        return;
      }
      instance = new Cropper(entryCropImageRef.current, {
        aspectRatio: NaN,
        viewMode: 0,
        autoCropArea: 0.9,
        dragMode: "move",
        background: false,
        responsive: true,
        checkOrientation: true,
        guides: true,
        center: true
      });
      entryCropperRef.current = instance;
    } catch (error) {
      console.error("[Mobile OS] falha ao abrir cropper da foto de entrada", error);
      setError("Nao foi possivel abrir o editor de corte da foto de entrada.");
      closeEntryCropper(true);
    }

    return () => {
      cancelled = true;
      if (instance) {
        instance.destroy();
      }
      entryCropperRef.current = null;
    };
  }, [entryCropOpen, entryCropSource]);

  useEffect(() => {
    if (accessoryCropFile || accessoryCropOpen || accessoryCropQueue.length === 0) {
      return;
    }

    const [nextFile, ...rest] = accessoryCropQueue;
    const objectUrl = URL.createObjectURL(nextFile);
    accessoryCropObjectUrlRef.current = objectUrl;
    setAccessoryCropQueue(rest);
    setAccessoryCropFile(nextFile);
    setAccessoryCropSource(objectUrl);
    setAccessoryCropOpen(true);
  }, [accessoryCropFile, accessoryCropOpen, accessoryCropQueue]);

  useEffect(() => {
    if (!accessoryCropOpen || !accessoryCropSource || !accessoryCropImageRef.current) {
      return;
    }

    let cancelled = false;
    let instance: Cropper | null = null;

    try {
      if (cancelled || !accessoryCropImageRef.current) {
        return;
      }
      instance = new Cropper(accessoryCropImageRef.current, {
        aspectRatio: NaN,
        viewMode: 0,
        autoCropArea: 0.9,
        dragMode: "move",
        background: false,
        responsive: true,
        checkOrientation: true,
        guides: true,
        center: true
      });
      accessoryCropperRef.current = instance;
    } catch (error) {
      console.error("[Mobile OS] falha ao abrir cropper da foto do acessorio", error);
      setAccessoryModalError("Nao foi possivel abrir o editor de corte da foto do acessorio.");
      closeAccessoryCropper(true);
    }

    return () => {
      cancelled = true;
      if (instance) {
        instance.destroy();
      }
      accessoryCropperRef.current = null;
    };
  }, [accessoryCropOpen, accessoryCropSource]);

  useEffect(() => {
    return () => {
      if (entryCropObjectUrlRef.current) {
        URL.revokeObjectURL(entryCropObjectUrlRef.current);
        entryCropObjectUrlRef.current = null;
      }
      if (entryPreviewObjectUrlRef.current) {
        URL.revokeObjectURL(entryPreviewObjectUrlRef.current);
        entryPreviewObjectUrlRef.current = null;
      }
      if (accessoryCropObjectUrlRef.current) {
        URL.revokeObjectURL(accessoryCropObjectUrlRef.current);
        accessoryCropObjectUrlRef.current = null;
      }
      if (accessoryPreviewObjectUrlRef.current) {
        URL.revokeObjectURL(accessoryPreviewObjectUrlRef.current);
        accessoryPreviewObjectUrlRef.current = null;
      }
    };
  }, []);

  async function loadEquipmentCatalog(params?: { typeId?: string; brandId?: string }) {
    setEquipmentCatalogLoading(true);
    try {
      const query = new URLSearchParams();
      const typeId = params?.typeId || "";
      const brandId = params?.brandId || "";
      if (typeId && Number(typeId) > 0) {
        query.set("tipo_id", typeId);
      }
      if (brandId && Number(brandId) > 0) {
        query.set("marca_id", brandId);
      }
      const endpoint = query.size > 0 ? `/equipments/catalog?${query.toString()}` : "/equipments/catalog";
      const data = await apiRequest<EquipmentCatalogResponse>(endpoint);
      setEquipmentCatalog({
        tipos: data.tipos || [],
        marcas: data.marcas || [],
        modelos: data.modelos || []
      });
    } catch (err) {
      setEquipmentModalError(err instanceof Error ? err.message : "Falha ao carregar catalogo de equipamentos.");
    } finally {
      setEquipmentCatalogLoading(false);
    }
  }

  function applyEquipmentColor(hexInput: string, providedName?: string) {
    const safeHex = normalizeHexColor(hexInput);
    const resolvedName = (providedName || "").trim() || closestColorName(safeHex);
    setEquipmentForm((prev) => ({
      ...prev,
      cor_hex: safeHex,
      cor_rgb: hexToRgbString(safeHex),
      cor: resolvedName
    }));
  }

  function resetAccessoryDraft(nextKey = "outro") {
    const config = accessoryConfigs.find((item) => item.key === nextKey) || null;
    setAccessoryDraftKey(nextKey);
    setAccessoryDraftValues(emptyAccessoryValues(config));
    setAccessoryDraftPhotos([]);
    setAccessoryEditingId(null);
    setAccessoryModalError("");
  }

  function openAccessoryModal(key = "outro") {
    resetAccessoryDraft(key);
    setAccessoryModalOpen(true);
  }

  function closeAccessoryModal() {
    setAccessoryModalOpen(false);
    resetAccessoryDraft(accessoryDraftKey);
  }

  function updateAccessoryField(name: string, value: string) {
    setAccessoryDraftValues((prev) => ({
      ...prev,
      [name]: value
    }));
  }

  function editAccessoryEntry(entryId: string) {
    const entry = accessoryEntries.find((item) => item.id === entryId);
    if (!entry) {
      return;
    }

    const config = accessoryConfigs.find((item) => item.key === entry.key) || accessoryConfigs[accessoryConfigs.length - 1];
    setAccessoryDraftKey(config.key);
    setAccessoryDraftValues({
      ...emptyAccessoryValues(config),
      ...entry.values
    });
    setAccessoryDraftPhotos(entry.photos);
    setAccessoryEditingId(entry.id);
    setAccessoryModalError("");
    setAccessoryModalOpen(true);
  }

  function removeAccessoryEntry(entryId: string) {
    setAccessoryEntries((prev) => prev.filter((item) => item.id !== entryId));
  }

  function submitAccessoryDraft() {
    const text = activeAccessoryConfig.format(accessoryDraftValues).trim();
    if (!text) {
      setAccessoryModalError("Preencha os dados do acessorio.");
      return;
    }

    const nextEntry: AccessoryEntry = {
      id: accessoryEditingId || generateAccessoryId(),
      key: activeAccessoryConfig.key,
      title: activeAccessoryConfig.title,
      text,
      values: accessoryDraftValues,
      photos: accessoryDraftPhotos
    };

    setAccessoryEntries((prev) => {
      if (!accessoryEditingId) {
        return [...prev, nextEntry];
      }
      return prev.map((item) => (item.id === accessoryEditingId ? nextEntry : item));
    });

    setAccessoryModalOpen(false);
    resetAccessoryDraft(activeAccessoryConfig.key);
  }

  function handleAccessoryFiles(filesList: FileList | null) {
    const { accepted: files, rejectedCount } = splitSupportedImageFiles(filesList);
    if (!files.length) {
      if ((filesList?.length || 0) > 0) {
        setAccessoryModalError("Selecione somente imagens validas (JPG, JPEG, JFIF, PNG, WEBP, HEIC, HEIF, AVIF).");
      }
      return;
    }

    const availableSlots = Math.max(0, 6 - accessoryDraftPhotos.length);
    if (availableSlots <= 0) {
      setAccessoryModalError("Limite de 6 fotos por acessorio atingido.");
      return;
    }

    const accepted = files.slice(0, availableSlots);
    if (files.length > accepted.length) {
      setAccessoryModalError("Algumas fotos foram ignoradas para manter o limite de 6.");
    } else {
      setAccessoryModalError(rejectedCount > 0 ? "Alguns arquivos foram ignorados por nao serem imagem valida." : "");
    }

    setAccessoryCropQueue((prev) => [...prev, ...accepted]);
  }

  function removeAccessoryPhoto(index: number) {
    setAccessoryDraftPhotos((prev) => prev.filter((_, currentIndex) => currentIndex !== index));
  }

  function openAccessoryPreview(file: File, title?: string) {
    if (accessoryPreviewObjectUrlRef.current) {
      URL.revokeObjectURL(accessoryPreviewObjectUrlRef.current);
      accessoryPreviewObjectUrlRef.current = null;
    }

    const objectUrl = URL.createObjectURL(file);
    accessoryPreviewObjectUrlRef.current = objectUrl;
    setAccessoryPreviewSource(objectUrl);
    setAccessoryPreviewTitle(title || file.name || "Foto do acessorio");
    setAccessoryPreviewOpen(true);
  }

  function closeAccessoryPreview() {
    if (accessoryPreviewObjectUrlRef.current) {
      URL.revokeObjectURL(accessoryPreviewObjectUrlRef.current);
      accessoryPreviewObjectUrlRef.current = null;
    }
    setAccessoryPreviewOpen(false);
    setAccessoryPreviewSource("");
    setAccessoryPreviewTitle("");
  }

  async function appendAccessoryPhoto(file: File) {
    setAccessoryDraftPhotos((prev) => [...prev, file].slice(0, 6));
  }

  function closeAccessoryCropper(discardCurrent = false) {
    if (accessoryCropperRef.current) {
      accessoryCropperRef.current.destroy();
      accessoryCropperRef.current = null;
    }
    if (accessoryCropObjectUrlRef.current) {
      URL.revokeObjectURL(accessoryCropObjectUrlRef.current);
      accessoryCropObjectUrlRef.current = null;
    }
    if (discardCurrent) {
      setAccessoryModalError((prev) => prev);
    }
    setAccessoryCropFile(null);
    setAccessoryCropSource("");
    setAccessoryCropOpen(false);
    setAccessoryCropBusy(false);
  }

  async function confirmAccessoryCrop() {
    if (!accessoryCropFile) {
      closeAccessoryCropper(true);
      return;
    }

    setAccessoryCropBusy(true);
    try {
      const cropper = accessoryCropperRef.current;
      if (!cropper) {
        await appendAccessoryPhoto(accessoryCropFile);
        closeAccessoryCropper();
        return;
      }

      const canvas = cropper.getCroppedCanvas({
        width: 1024,
        height: 1024,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: "high"
      });
      const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.92));
      if (!blob) {
        throw new Error("Falha ao gerar a foto recortada do acessorio.");
      }

      const croppedFile = new File([blob], accessoryCropFile.name.replace(/\.[^.]+$/, "") + ".jpg", {
        type: "image/jpeg"
      });
      await appendAccessoryPhoto(croppedFile);
      closeAccessoryCropper();
    } catch (error) {
      console.error("[Mobile OS] falha ao confirmar corte da foto do acessorio", error);
      setAccessoryModalError(error instanceof Error ? error.message : "Falha ao finalizar o corte da foto do acessorio.");
      closeAccessoryCropper(true);
    }
  }

  async function handleEquipmentPhotosSelection(filesList: FileList | null) {
    const { accepted: files, rejectedCount } = splitSupportedImageFiles(filesList);
    if (!files.length) {
      if ((filesList?.length || 0) > 0) {
        setEquipmentModalError("Selecione somente imagens validas (JPG, JPEG, JFIF, PNG, WEBP, HEIC, HEIF, AVIF).");
      }
      return;
    }

    const availableSlots = Math.max(0, EQUIPMENT_PHOTO_MAX_FILES - equipmentExistingPhotos.length - equipmentNewPhotos.length);
    if (availableSlots <= 0) {
      setEquipmentModalError(`Limite de ${EQUIPMENT_PHOTO_MAX_FILES} fotos por equipamento atingido.`);
      return;
    }

    const accepted = files.slice(0, availableSlots);
    if (files.length > accepted.length) {
      setEquipmentModalError(
        `Somente ${accepted.length} foto(s) foram adicionadas agora para manter o limite de ${EQUIPMENT_PHOTO_MAX_FILES}.`
      );
    } else {
      setEquipmentModalError(rejectedCount > 0 ? "Alguns arquivos foram ignorados por nao serem imagem valida." : "");
    }

    setEquipmentCropQueue((prev) => [...prev, ...accepted]);
  }

  function handleEntryFiles(filesList: FileList | null) {
    const { accepted: files, rejectedCount } = splitSupportedImageFiles(filesList);
    if (!files.length) {
      if ((filesList?.length || 0) > 0) {
        setError("Selecione somente imagens validas (JPG, JPEG, JFIF, PNG, WEBP, HEIC, HEIF, AVIF).");
      }
      return;
    }

    const availableSlots = Math.max(0, ENTRY_PHOTO_MAX_FILES - entryPhotos.length - entryCropQueue.length);
    if (availableSlots <= 0) {
      setError(`Limite de ${ENTRY_PHOTO_MAX_FILES} fotos de entrada atingido.`);
      return;
    }

    const accepted = files.slice(0, availableSlots);
    if (files.length > accepted.length) {
      setError(`Somente ${accepted.length} foto(s) foram adicionadas para manter o limite de ${ENTRY_PHOTO_MAX_FILES}.`);
    } else {
      setError(rejectedCount > 0 ? "Alguns arquivos foram ignorados por nao serem imagem valida." : "");
    }

    setEntryCropQueue((prev) => [...prev, ...accepted]);
  }

  function removeEntryPhoto(index: number) {
    setEntryPhotos((prev) => prev.filter((_, currentIndex) => currentIndex !== index));
  }

  async function appendEntryPhoto(file: File) {
    setEntryPhotos((prev) => [...prev, file].slice(0, ENTRY_PHOTO_MAX_FILES));
  }

  function closeEntryCropper(discardCurrent = false) {
    if (entryCropperRef.current) {
      entryCropperRef.current.destroy();
      entryCropperRef.current = null;
    }
    if (entryCropObjectUrlRef.current) {
      URL.revokeObjectURL(entryCropObjectUrlRef.current);
      entryCropObjectUrlRef.current = null;
    }
    if (discardCurrent) {
      setError((prev) => prev);
    }
    setEntryCropFile(null);
    setEntryCropSource("");
    setEntryCropOpen(false);
    setEntryCropBusy(false);
  }

  async function confirmEntryCrop() {
    if (!entryCropFile) {
      closeEntryCropper(true);
      return;
    }

    setEntryCropBusy(true);
    try {
      const cropper = entryCropperRef.current;
      if (!cropper) {
        await appendEntryPhoto(entryCropFile);
        closeEntryCropper();
        return;
      }

      const canvas = cropper.getCroppedCanvas({
        width: 1280,
        height: 1280,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: "high"
      });
      const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.92));
      if (!blob) {
        throw new Error("Falha ao gerar a foto recortada de entrada.");
      }

      const croppedFile = new File([blob], entryCropFile.name.replace(/\.[^.]+$/, "") + ".jpg", {
        type: "image/jpeg"
      });
      await appendEntryPhoto(croppedFile);
      closeEntryCropper();
    } catch (error) {
      console.error("[Mobile OS] falha ao confirmar corte da foto de entrada", error);
      setError(error instanceof Error ? error.message : "Falha ao finalizar o corte da foto de entrada.");
      closeEntryCropper(true);
    }
  }

  function openEntryPreview(file: File, title?: string) {
    if (entryPreviewObjectUrlRef.current) {
      URL.revokeObjectURL(entryPreviewObjectUrlRef.current);
      entryPreviewObjectUrlRef.current = null;
    }

    const objectUrl = URL.createObjectURL(file);
    entryPreviewObjectUrlRef.current = objectUrl;
    setEntryPreviewSource(objectUrl);
    setEntryPreviewTitle(title || file.name || "Foto de entrada");
    setEntryPreviewOpen(true);
  }

  function closeEntryPreview() {
    if (entryPreviewObjectUrlRef.current) {
      URL.revokeObjectURL(entryPreviewObjectUrlRef.current);
      entryPreviewObjectUrlRef.current = null;
    }
    setEntryPreviewOpen(false);
    setEntryPreviewSource("");
    setEntryPreviewTitle("");
  }

  function openEquipmentGallery(equipment: EquipmentOption) {
    const photos = getEquipmentPhotoUrls(equipment);
    if (photos.length === 0) {
      return;
    }

    setEquipmentGalleryTitle(formatEquipmentGalleryTitle(equipment) || "Fotos do equipamento");
    setEquipmentGalleryPhotos(photos);
    setEquipmentGalleryActiveIndex(0);
    setEquipmentGalleryOpen(true);
  }

  function closeEquipmentGallery() {
    setEquipmentGalleryOpen(false);
    setEquipmentGalleryTitle("");
    setEquipmentGalleryPhotos([]);
    setEquipmentGalleryActiveIndex(0);
  }

  function moveEquipmentGallery(step: number) {
    setEquipmentGalleryActiveIndex((prev) => {
      if (equipmentGalleryPhotos.length <= 1) {
        return 0;
      }

      return (prev + step + equipmentGalleryPhotos.length) % equipmentGalleryPhotos.length;
    });
  }

  function openEquipmentPickerFromSelection() {
    setEquipmentQuery("");
    setEquipmentPickerOpen(true);
  }

  function openClientPickerFromSelection() {
    suppressAutoClientSearchRef.current = true;
    setClientQuery("");
    setClientPickerOpen(true);
  }

  function resetOrderForm() {
    const now = new Date();
    const defaultTechnicianId = singleTechnician ? String(singleTechnician.id) : "";
    setHeaderMenuOpen(false);
    setError("");
    setSuccess("");
    setClientQuery("");
    setClientPickerOpen(false);
    setEquipmentQuery("");
    setEquipmentPickerOpen(false);
    setForecastPreset("3");
    setEntryPhotos([]);
    setAccessoryEntries([]);
    setChecklistEntrada(buildChecklistDraft(null));
    setOrderReviewOpen(false);
    setOrderReviewSnapshot(null);
    setOrderReviewStep("summary");
    setOrderReviewError("");
    setClientNotificationMode("none");
    closeAllModals();
    closeEquipmentGallery();
    setForm({
      cliente_id: "",
      equipamento_id: "",
      tecnico_id: defaultTechnicianId,
      prioridade: "normal",
      status: "triagem",
      relato_cliente: "",
      data_entrada: formatDateTimeLocal(now),
      data_previsao: formatDateTimeLocal(addDays(now, 3)),
      observacoes_cliente: "",
      observacoes_internas: ""
    });
    void loadMeta({ clienteId: "", equipamentoId: "" });
  }

  function handleEquipmentOptionKeyDown(
    event: KeyboardEvent<HTMLDivElement>,
    equipment: EquipmentOption
  ) {
    if (event.key !== "Enter" && event.key !== " ") {
      return;
    }

    event.preventDefault();
    selectEquipment(equipment);
  }

  function removeEquipmentNewPhoto(index: number) {
    setEquipmentNewPhotos((prev) => prev.filter((_, itemIndex) => itemIndex !== index));
  }

  function closeAllModals() {
    if (clientCepAbortRef.current) {
      clientCepAbortRef.current.abort();
      clientCepAbortRef.current = null;
    }
    setClientModalMode(null);
    setEquipmentModalMode(null);
    setBrandPickerOpen(false);
    setModelPickerOpen(false);
    setBrandQuery("");
    setModelQuery("");
    setBrandModalOpen(false);
    setModelModalOpen(false);
    setColorModalOpen(false);
    setBrandModalError("");
    setModelModalError("");
    setClientModalError("");
    setClientCepLoading(false);
    setClientCepHint("");
    lastClientCepLookupRef.current = "";
    setEquipmentModalError("");
    setEquipmentExistingPhotos([]);
    setEquipmentNewPhotos([]);
    setEquipmentSuggestedColorHex(null);
    setEquipmentCropQueue([]);
    setEquipmentCropFile(null);
    setEquipmentCropSource("");
    setEquipmentCropOpen(false);
    setEquipmentCropBusy(false);
    setAccessoryModalOpen(false);
    setAccessoryCropQueue([]);
    setAccessoryCropFile(null);
    setAccessoryCropSource("");
    setAccessoryCropOpen(false);
    setAccessoryCropBusy(false);
    setEntryCropQueue([]);
    setEntryCropFile(null);
    setEntryCropSource("");
    setEntryCropOpen(false);
    setEntryCropBusy(false);
    setOrderReviewOpen(false);
    setOrderReviewSnapshot(null);
    setOrderReviewStep("summary");
    setOrderReviewError("");
    setClientNotificationMode("none");
    closeEntryPreview();
    resetAccessoryDraft("outro");
  }

  function openBrandModal() {
    if (!equipmentForm.tipo_id) {
      setEquipmentModalError("Selecione o tipo antes de cadastrar a marca.");
      return;
    }
    setEquipmentModalError("");
    setBrandModalError("");
    setBrandFormName("");
    setBrandModalOpen(true);
  }

  function closeBrandModal() {
    setBrandModalOpen(false);
    setBrandModalError("");
    setBrandFormName("");
  }

  function openModelModal() {
    if (!equipmentForm.marca_id) {
      setEquipmentModalError("Selecione a marca antes de cadastrar um modelo.");
      return;
    }
    setEquipmentModalError("");
    setModelModalError("");
    setModelFormName("");
    setModelModalOpen(true);
  }

  function closeModelModal() {
    setModelModalOpen(false);
    setModelModalError("");
    setModelFormName("");
  }

  function openColorModal() {
    setEquipmentModalError("");
    setColorModalOpen(true);
  }

  function closeColorModal() {
    setColorModalOpen(false);
  }

  function openCreateClientModal() {
    setClientModalError("");
    setClientCepHint("");
    setClientCepLoading(false);
    lastClientCepLookupRef.current = "";
    setClientForm(emptyClientForm());
    setClientModalMode("create");
  }

  async function openEditClientModal() {
    if (!form.cliente_id) {
      setError("Selecione um cliente para editar.");
      return;
    }

    setClientModalError("");
    setClientCepHint("");
    setClientCepLoading(false);
    lastClientCepLookupRef.current = "";
    try {
      const data = await apiRequest<ClientDetailResponse>(`/clients/${form.cliente_id}`);
      setClientForm({
        nome_razao: data.nome_razao || "",
        telefone1: data.telefone1 || "",
        telefone2: data.telefone2 || "",
        email: data.email || "",
        nome_contato: data.nome_contato || "",
        telefone_contato: data.telefone_contato || "",
        cep: formatCep(data.cep || ""),
        cidade: data.cidade || "",
        uf: data.uf || "",
        endereco: data.endereco || "",
        numero: data.numero || "",
        bairro: data.bairro || "",
        complemento: data.complemento || "",
        observacoes: data.observacoes || ""
      });
      setClientModalMode("edit");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha ao carregar cliente para edicao.");
    }
  }

  async function fillAddressByCep(rawCep: string) {
    const cepDigits = normalizeDigits(rawCep).slice(0, 8);
    if (cepDigits.length !== 8) {
      return;
    }

    if (lastClientCepLookupRef.current === cepDigits) {
      return;
    }

    if (clientCepAbortRef.current) {
      clientCepAbortRef.current.abort();
      clientCepAbortRef.current = null;
    }

    const controller = new AbortController();
    clientCepAbortRef.current = controller;
    lastClientCepLookupRef.current = cepDigits;
    setClientCepLoading(true);
    setClientCepHint("Buscando CEP...");

    try {
      const response = await fetch(`https://viacep.com.br/ws/${cepDigits}/json/`, {
        method: "GET",
        signal: controller.signal
      });
      if (!response.ok) {
        throw new Error("Falha ao consultar CEP.");
      }

      const data = (await response.json()) as ViaCepResponse;
      if (data.erro) {
        setClientCepHint("CEP nao encontrado.");
        return;
      }

      setClientForm((prev) => ({
        ...prev,
        cep: formatCep(data.cep || cepDigits),
        endereco: (data.logradouro || "").trim(),
        bairro: (data.bairro || "").trim(),
        cidade: (data.localidade || "").trim(),
        uf: ((data.uf || "").trim() || prev.uf || "").slice(0, 2).toUpperCase(),
        complemento: prev.complemento || (data.complemento || "").trim()
      }));
      setClientCepHint("Endereco preenchido automaticamente pelo CEP.");
    } catch (error) {
      if (controller.signal.aborted) {
        return;
      }
      console.error("[Mobile OS] falha ao consultar CEP no modal de cliente", error);
      setClientCepHint("Nao foi possivel consultar o CEP agora.");
      lastClientCepLookupRef.current = "";
    } finally {
      if (clientCepAbortRef.current === controller) {
        clientCepAbortRef.current = null;
      }
      setClientCepLoading(false);
    }
  }

  async function submitClientModal(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setClientModalError("");

    if (!clientForm.nome_razao.trim() || !clientForm.telefone1.trim()) {
      setClientModalError("Nome e telefone principal sao obrigatorios.");
      return;
    }

    setClientModalSaving(true);
    try {
      const method = clientModalMode === "edit" ? "PUT" : "POST";
      const endpoint = clientModalMode === "edit" ? `/clients/${form.cliente_id}` : "/clients";
      const payload = {
        ...clientForm,
        cep: formatCep(clientForm.cep)
      };
      const saved = await apiRequest<ClientOption>(endpoint, {
        method,
        body: JSON.stringify(payload)
      });

      const savedId = String(saved.id);
      setClients((prev) => {
        const next = prev.filter((item) => item.id !== saved.id);
        return [saved, ...next];
      });
      suppressAutoClientSearchRef.current = true;
      setClientQuery(formatClientLabel(saved));
      setForm((prev) => ({
        ...prev,
        cliente_id: savedId,
        equipamento_id: ""
      }));
      setClientModalMode(null);
      await loadMeta({
        q: "",
        clienteId: savedId,
        equipamentoId: ""
      });
    } catch (err) {
      setClientModalError(err instanceof Error ? err.message : "Falha ao salvar cliente.");
    } finally {
      setClientModalSaving(false);
    }
  }

  async function openCreateEquipmentModal() {
    if (!form.cliente_id) {
      setError("Selecione primeiro o cliente para cadastrar um equipamento.");
      return;
    }

    setEquipmentModalError("");
    setEquipmentForm(emptyEquipmentForm());
    setEquipmentExistingPhotos([]);
    setEquipmentNewPhotos([]);
    setEquipmentSuggestedColorHex(null);
    setEquipmentCropQueue([]);
    setEquipmentCropFile(null);
    setEquipmentCropSource("");
    setEquipmentCropOpen(false);
    setEquipmentModalMode("create");
    await loadEquipmentCatalog();
  }

  async function openEditEquipmentModal() {
    if (!form.equipamento_id) {
      setError("Selecione um equipamento para editar.");
      return;
    }

    setEquipmentModalError("");
    try {
      const equipment = await apiRequest<EquipmentDetailResponse>(`/equipments/${form.equipamento_id}`);
      await loadEquipmentCatalog({
        typeId: equipment.tipo_id ? String(equipment.tipo_id) : "",
        brandId: equipment.marca_id ? String(equipment.marca_id) : ""
      });

      setEquipmentForm({
        tipo_id: equipment.tipo_id ? String(equipment.tipo_id) : "",
        marca_id: equipment.marca_id ? String(equipment.marca_id) : "",
        modelo_id: equipment.modelo_id ? String(equipment.modelo_id) : "",
        marca_nome: "",
        modelo_nome: "",
        numero_serie: equipment.numero_serie || "",
        imei: equipment.imei || "",
        cor: equipment.cor || "Preto",
        cor_hex: normalizeHexColor(equipment.cor_hex || "#1A1A1A"),
        cor_rgb: equipment.cor_rgb || hexToRgbString(equipment.cor_hex || "#1A1A1A"),
        senha_acesso: equipment.senha_acesso || "",
        estado_fisico: equipment.estado_fisico || "",
        acessorios: equipment.acessorios || "",
        observacoes: equipment.observacoes || ""
      });
      setEquipmentExistingPhotos(Array.isArray(equipment.fotos) ? equipment.fotos : []);
      setEquipmentNewPhotos([]);
      setEquipmentSuggestedColorHex(null);
      setEquipmentCropQueue([]);
      setEquipmentCropFile(null);
      setEquipmentCropSource("");
      setEquipmentCropOpen(false);
      setEquipmentModalMode("edit");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha ao carregar equipamento para edicao.");
    }
  }

  async function submitEquipmentModal(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setEquipmentModalError("");

    if (!form.cliente_id) {
      setEquipmentModalError("Selecione um cliente antes de salvar o equipamento.");
      return;
    }
    if (!equipmentForm.tipo_id) {
      setEquipmentModalError("Selecione o tipo do equipamento.");
      return;
    }
    if (!equipmentForm.marca_id) {
      setEquipmentModalError("Selecione a marca do equipamento.");
      return;
    }
    if (!equipmentForm.modelo_id) {
      setEquipmentModalError("Selecione o modelo do equipamento.");
      return;
    }
    if (!equipmentForm.cor.trim() || !equipmentForm.cor_hex.trim()) {
      setEquipmentModalError("Informe a cor correta do equipamento.");
      return;
    }
    if (equipmentModalMode === "create" && equipmentNewPhotos.length <= 0) {
      setEquipmentModalError("Adicione ao menos uma foto de perfil do equipamento.");
      return;
    }

    setEquipmentModalSaving(true);
    try {
      const payload = new FormData();
      payload.set("cliente_id", form.cliente_id);
      payload.set("tipo_id", equipmentForm.tipo_id);
      payload.set("numero_serie", equipmentForm.numero_serie.trim());
      payload.set("imei", equipmentForm.imei.trim());
      payload.set("cor", equipmentForm.cor.trim());
      payload.set("cor_hex", normalizeHexColor(equipmentForm.cor_hex));
      payload.set("cor_rgb", equipmentForm.cor_rgb.trim() || hexToRgbString(equipmentForm.cor_hex));
      payload.set("senha_acesso", equipmentForm.senha_acesso.trim());
      payload.set("estado_fisico", equipmentForm.estado_fisico.trim());
      payload.set("acessorios", equipmentForm.acessorios.trim());
      payload.set("observacoes", equipmentForm.observacoes.trim());

      payload.set("marca_id", equipmentForm.marca_id);
      payload.set("modelo_id", equipmentForm.modelo_id);

      equipmentNewPhotos.forEach((file) => {
        payload.append("fotos[]", file);
      });

      const isEditMode = equipmentModalMode === "edit";
      const endpoint = isEditMode ? `/equipments/${form.equipamento_id}` : "/equipments";
      if (isEditMode) {
        payload.set("_method", "PUT");
      }
      const saved = await apiRequest<EquipmentOption & { id: number }>(endpoint, {
        method: "POST",
        body: payload
      });

      const savedId = String(saved.id);
      closeAllModals();
      await loadMeta({
        q: "",
        clienteId: form.cliente_id,
        equipamentoId: savedId
      });
      setForm((prev) => ({
        ...prev,
        equipamento_id: savedId
      }));
    } catch (err) {
      setEquipmentModalError(err instanceof Error ? err.message : "Falha ao salvar equipamento.");
    } finally {
      setEquipmentModalSaving(false);
    }
  }

  async function submitBrandModal(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nome = brandFormName.trim();
    if (!nome) {
      setBrandModalError("Informe o nome da marca.");
      return;
    }

    if (exactBrandModalMatch) {
      setEquipmentForm((prev) => ({
        ...prev,
        marca_id: String(exactBrandModalMatch.id),
        modelo_id: "",
        marca_nome: "",
        modelo_nome: ""
      }));
      setBrandQuery(exactBrandModalMatch.nome || nome);
      setModelQuery("");
      void loadEquipmentCatalog({
        typeId: equipmentForm.tipo_id,
        brandId: String(exactBrandModalMatch.id)
      });
      closeBrandModal();
      return;
    }

    setBrandModalSaving(true);
    setBrandModalError("");
    try {
      const saved = await apiRequest<EquipmentBrandOption>("/equipments/brands", {
        method: "POST",
        body: JSON.stringify({ nome })
      });

      setEquipmentCatalog((prev) => {
        const nextBrands = [...prev.marcas.filter((item) => item.id !== saved.id), saved].sort((a, b) =>
          (a.nome || "").localeCompare(b.nome || "", "pt-BR")
        );
        return {
          ...prev,
          marcas: nextBrands
        };
      });
      setEquipmentForm((prev) => ({
        ...prev,
        marca_id: String(saved.id),
        modelo_id: "",
        marca_nome: "",
        modelo_nome: ""
      }));
      setBrandQuery(saved.nome || nome);
      setModelQuery("");
      void loadEquipmentCatalog({
        typeId: equipmentForm.tipo_id,
        brandId: String(saved.id)
      });
      closeBrandModal();
    } catch (err) {
      setBrandModalError(err instanceof Error ? err.message : "Falha ao salvar a marca.");
    } finally {
      setBrandModalSaving(false);
    }
  }

  async function submitModelModal(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nome = modelFormName.trim();
    if (!equipmentForm.marca_id) {
      setModelModalError("Selecione a marca antes de cadastrar o modelo.");
      return;
    }
    if (!nome) {
      setModelModalError("Informe o nome do modelo.");
      return;
    }

    if (exactModelModalMatch) {
      setEquipmentForm((prev) => ({
        ...prev,
        modelo_id: String(exactModelModalMatch.id),
        modelo_nome: ""
      }));
      setModelQuery(exactModelModalMatch.nome || nome);
      closeModelModal();
      return;
    }

    setModelModalSaving(true);
    setModelModalError("");
    try {
      const saved = await apiRequest<EquipmentModelOption>("/equipments/models", {
        method: "POST",
        body: JSON.stringify({
          marca_id: equipmentForm.marca_id,
          nome
        })
      });

      setEquipmentCatalog((prev) => {
        const nextModels = [...prev.modelos.filter((item) => item.id !== saved.id), saved].sort((a, b) => {
          const brandDiff = Number(a.marca_id || 0) - Number(b.marca_id || 0);
          if (brandDiff !== 0) {
            return brandDiff;
          }
          return (a.nome || "").localeCompare(b.nome || "", "pt-BR");
        });
        return {
          ...prev,
          modelos: nextModels
        };
      });
      setEquipmentForm((prev) => ({
        ...prev,
        modelo_id: String(saved.id),
        modelo_nome: ""
      }));
      setModelQuery(saved.nome || nome);
      closeModelModal();
    } catch (err) {
      setModelModalError(err instanceof Error ? err.message : "Falha ao salvar o modelo.");
    } finally {
      setModelModalSaving(false);
    }
  }

  function selectClient(client: ClientOption) {
    suppressAutoClientSearchRef.current = true;
    setClientQuery(formatClientLabel(client));
    setClientPickerOpen(false);
    setEquipmentQuery("");
    setEquipmentPickerOpen(false);
    setChecklistEntrada(buildChecklistDraft(null));
    setForm((prev) => ({
      ...prev,
      cliente_id: String(client.id),
      equipamento_id: ""
    }));
    void loadMeta({
      q: "",
      clienteId: String(client.id),
      equipamentoId: ""
    });
  }

  function handleClientInputChange(value: string) {
    setClientQuery(value);
    setClientPickerOpen(true);
    setEquipmentQuery("");
    setEquipmentPickerOpen(false);
    setChecklistEntrada(buildChecklistDraft(null));
    setForm((prev) => ({
      ...prev,
      cliente_id: "",
      equipamento_id: ""
    }));
  }

  function selectEquipment(equipment: EquipmentOption) {
    setEquipmentQuery(formatEquipmentSelectionLabel(equipment));
    setEquipmentPickerOpen(false);
    setForm((prev) => ({
      ...prev,
      equipamento_id: String(equipment.id)
    }));
    void loadMeta({
      q: "",
      clienteId: form.cliente_id,
      equipamentoId: String(equipment.id)
    });
  }

  function handleEquipmentInputChange(value: string) {
    setEquipmentQuery(value);
    setEquipmentPickerOpen(true);
    setChecklistEntrada(buildChecklistDraft(null));
    setForm((prev) => ({
      ...prev,
      equipamento_id: ""
    }));
  }

  function openReportedDefectModal() {
    if (reportedDefectGroups.length > 0 && !reportedDefectGroups.some((group) => group.categoria === reportedDefectCategory)) {
      setReportedDefectCategory(reportedDefectGroups[0].categoria);
    }
    setReportedDefectModalOpen(true);
  }

  function applyReportedDefect(text: string) {
    const normalized = normalizeReportedDefectText(text);
    if (!normalized) {
      return;
    }

    const line = /[.!?]$/.test(normalized) ? normalized : `${normalized}.`;
    setForm((prev) => {
      const current = prev.relato_cliente.trim();
      return {
        ...prev,
        relato_cliente: current ? `${current}\n${line}` : line
      };
    });
    setReportedDefectModalOpen(false);
  }

  function selectBrand(marca: EquipmentCatalogOption) {
    setBrandPickerOpen(false);
    setBrandQuery(marca.nome || "");
    setModelQuery("");
    setEquipmentForm((prev) => ({
      ...prev,
      marca_id: String(marca.id),
      modelo_id: "",
      marca_nome: "",
      modelo_nome: ""
    }));
    void loadEquipmentCatalog({
      typeId: equipmentForm.tipo_id,
      brandId: String(marca.id)
    });
  }

  function handleBrandInputChange(value: string) {
    setBrandQuery(value);
    setBrandPickerOpen(true);
    setModelPickerOpen(false);
    setModelQuery("");
    setEquipmentForm((prev) => ({
      ...prev,
      marca_id: "",
      modelo_id: "",
      marca_nome: "",
      modelo_nome: ""
    }));
  }

  function selectModel(modelo: EquipmentCatalogOption) {
    setModelPickerOpen(false);
    setModelQuery(modelo.nome || "");
    setEquipmentForm((prev) => ({
      ...prev,
      modelo_id: String(modelo.id),
      modelo_nome: ""
    }));
  }

  function handleModelInputChange(value: string) {
    setModelQuery(value);
    setModelPickerOpen(true);
    setEquipmentForm((prev) => ({
      ...prev,
      modelo_id: "",
      modelo_nome: ""
    }));
  }

  function applyExistingModelSuggestion(modelo: EquipmentCatalogOption) {
    setEquipmentForm((prev) => ({
      ...prev,
      modelo_id: String(modelo.id),
      modelo_nome: ""
    }));
    setModelQuery(modelo.nome || "");
    setModelFormName(modelo.nome || "");
    closeModelModal();
  }

  function applyExistingBrandSuggestion(marca: EquipmentCatalogOption) {
    setEquipmentForm((prev) => ({
      ...prev,
      marca_id: String(marca.id),
      modelo_id: "",
      marca_nome: "",
      modelo_nome: ""
    }));
    setBrandQuery(marca.nome || "");
    setBrandFormName(marca.nome || "");
    setModelQuery("");
    void loadEquipmentCatalog({
      typeId: equipmentForm.tipo_id,
      brandId: String(marca.id)
    });
    closeBrandModal();
  }

  useEffect(() => {
    if (equipmentCropFile || equipmentCropOpen || equipmentCropQueue.length === 0) {
      return;
    }

    const [nextFile, ...rest] = equipmentCropQueue;
    const objectUrl = URL.createObjectURL(nextFile);
    equipmentCropObjectUrlRef.current = objectUrl;
    setEquipmentCropQueue(rest);
    setEquipmentCropFile(nextFile);
    setEquipmentCropSource(objectUrl);
    setEquipmentCropOpen(true);
  }, [equipmentCropFile, equipmentCropOpen, equipmentCropQueue]);

  useEffect(() => {
    if (!equipmentCropOpen || !equipmentCropSource || !equipmentCropImageRef.current) {
      return;
    }

    let cancelled = false;
    let instance: Cropper | null = null;

    try {
      if (cancelled || !equipmentCropImageRef.current) {
        return;
      }
      instance = new Cropper(equipmentCropImageRef.current, {
        aspectRatio: NaN,
        viewMode: 0,
        autoCropArea: 0.9,
        dragMode: "move",
        background: false,
        responsive: true,
        checkOrientation: true,
        guides: true,
        center: true
      });
      equipmentCropperRef.current = instance;
    } catch (error) {
      console.error("[Mobile OS] falha ao abrir cropper da foto do equipamento", error);
      setEquipmentModalError("Nao foi possivel abrir o editor de corte da foto.");
      closeEquipmentCropper(true);
    }

    return () => {
      cancelled = true;
      if (instance) {
        instance.destroy();
      }
      equipmentCropperRef.current = null;
    };
  }, [equipmentCropOpen, equipmentCropSource]);

  useEffect(() => {
    return () => {
      if (equipmentCropObjectUrlRef.current) {
        URL.revokeObjectURL(equipmentCropObjectUrlRef.current);
        equipmentCropObjectUrlRef.current = null;
      }
    };
  }, []);

  function closeEquipmentCropper(discardCurrent = false) {
    if (equipmentCropperRef.current) {
      equipmentCropperRef.current.destroy();
      equipmentCropperRef.current = null;
    }
    if (equipmentCropObjectUrlRef.current) {
      URL.revokeObjectURL(equipmentCropObjectUrlRef.current);
      equipmentCropObjectUrlRef.current = null;
    }
    if (discardCurrent) {
      setEquipmentModalError((prev) => prev);
    }
    setEquipmentCropFile(null);
    setEquipmentCropSource("");
    setEquipmentCropOpen(false);
    setEquipmentCropBusy(false);
  }

  async function appendEquipmentPhoto(file: File) {
    const hadAnyPhoto = equipmentExistingPhotos.length + equipmentNewPhotos.length > 0;
    setEquipmentNewPhotos((prev) => [...prev, file]);

    if (!hadAnyPhoto) {
      const suggestedHex = await detectDominantHexFromImage(file);
      if (suggestedHex) {
        setEquipmentSuggestedColorHex(suggestedHex);
      }
    }
  }

  async function confirmEquipmentCrop() {
    if (!equipmentCropFile) {
      closeEquipmentCropper(true);
      return;
    }

    setEquipmentCropBusy(true);
    try {
      const cropper = equipmentCropperRef.current;
      if (!cropper) {
        await appendEquipmentPhoto(equipmentCropFile);
        closeEquipmentCropper();
        return;
      }

      const canvas = cropper.getCroppedCanvas({
        width: 1024,
        height: 1024,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: "high"
      });
      const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.92));
      if (!blob) {
        throw new Error("Falha ao gerar a foto recortada.");
      }

      const croppedFile = new File([blob], equipmentCropFile.name.replace(/\.[^.]+$/, "") + ".jpg", {
        type: "image/jpeg"
      });
      await appendEquipmentPhoto(croppedFile);
      closeEquipmentCropper();
    } catch (error) {
      console.error("[Mobile OS] falha ao confirmar corte da foto do equipamento", error);
      setEquipmentModalError(error instanceof Error ? error.message : "Falha ao finalizar o corte da foto.");
      closeEquipmentCropper(true);
    }
  }

  function formatDateTimeForReview(value: string): string {
    if (!value) {
      return "Nao preenchido";
    }
    return value.replace("T", " ");
  }

  function buildOrderReviewSnapshot(): OrderReviewSnapshot {
    const selectedTechnician = technicians.find((technician) => String(technician.id) === form.tecnico_id) || null;
    const selectedStatus = statuses.find((status) => status.codigo === form.status) || null;
    const selectedPriority = priorities.find((priority) => priority.codigo === form.prioridade) || null;
    const accessoriesSummary = accessoryEntries.length
      ? accessoryEntries
          .slice(0, 3)
          .map((entry) => entry.text)
          .join(", ")
      : "";
    const missingAccessoryCount = Math.max(0, accessoryEntries.length - 3);
    const accessoriesLabel =
      accessoriesSummary !== ""
        ? `${accessoriesSummary}${missingAccessoryCount > 0 ? ` (+${missingAccessoryCount})` : ""}`
        : "Nenhum acessorio adicionado";

    const rows: ReviewFieldRow[] = [
      {
        key: "cliente_id",
        label: "Cliente",
        required: true,
        filled: Boolean(form.cliente_id),
        value: selectedClient
          ? `${selectedClient.nome_razao}${selectedClientPhone ? ` | ${selectedClientPhone}` : ""}`
          : "Nao selecionado"
      },
      {
        key: "equipamento_id",
        label: "Equipamento",
        required: true,
        filled: Boolean(form.equipamento_id),
        value: selectedEquipment
          ? [formatEquipmentPrimaryLine(selectedEquipment), formatEquipmentSecondaryLine(selectedEquipment)]
              .filter(Boolean)
              .join(" | ")
          : "Nao selecionado"
      },
      {
        key: "relato_cliente",
        label: "Relato do cliente",
        required: true,
        filled: form.relato_cliente.trim() !== "",
        value: form.relato_cliente.trim() || "Nao informado"
      },
      {
        key: "tecnico_id",
        label: "Tecnico atribuido",
        required: true,
        filled: technicians.length > 0 && form.tecnico_id !== "",
        value: selectedTechnician?.nome || (technicians.length > 0 ? "Nao atribuido" : "Sem tecnicos cadastrados")
      },
      {
        key: "prioridade",
        label: "Prioridade",
        required: true,
        filled: form.prioridade !== "",
        value: selectedPriority?.nome || form.prioridade || "Nao informado"
      },
      {
        key: "status",
        label: "Status",
        required: true,
        filled: form.status !== "",
        value: selectedStatus?.nome || form.status || "Nao informado"
      },
      {
        key: "data_entrada",
        label: "Data de entrada",
        required: true,
        filled: form.data_entrada !== "",
        value: formatDateTimeForReview(form.data_entrada)
      },
      {
        key: "data_previsao",
        label: "Data de previsao",
        required: true,
        filled: form.data_previsao !== "",
        value: formatDateTimeForReview(form.data_previsao)
      },
      {
        key: "acessorios",
        label: "Acessorios",
        required: true,
        filled: accessoryEntries.length > 0,
        value: accessoriesLabel
      },
      {
        key: "checklist",
        label: "Checklist de entrada",
        required: true,
        filled: checklistIsComplete(checklistEntrada),
        value: checklistEntrada.resumoLabel || "Nao preenchido"
      },
      {
        key: "fotos_entrada",
        label: "Fotos de entrada",
        required: true,
        filled: entryPhotos.length > 0,
        value:
          entryPhotos.length > 0
            ? `${entryPhotos.length} foto(s) anexada(s)`
            : "Nenhuma foto de entrada anexada"
      },
      {
        key: "observacoes_cliente",
        label: "Observacoes do cliente",
        required: false,
        filled: form.observacoes_cliente.trim() !== "",
        value: form.observacoes_cliente.trim() || "Nao preenchido"
      },
      {
        key: "observacoes_internas",
        label: "Observacoes internas",
        required: false,
        filled: form.observacoes_internas.trim() !== "",
        value: form.observacoes_internas.trim() || "Nao preenchido"
      }
    ];

    return {
      rows,
      requiredMissing: rows.filter((row) => row.required && !row.filled),
      optionalMissing: rows.filter((row) => !row.required && !row.filled)
    };
  }

  function focusReviewField(key: ReviewFieldKey) {
    const focusNode = (node: HTMLElement | null, focus = false) => {
      if (!node) {
        return;
      }
      node.scrollIntoView({ behavior: "smooth", block: "center" });
      if (focus && "focus" in node) {
        window.setTimeout(() => {
          (node as HTMLElement).focus();
        }, 120);
      }
    };

    switch (key) {
      case "cliente_id":
        openClientPickerFromSelection();
        focusNode(clientComboRef.current, true);
        return;
      case "equipamento_id":
        openEquipmentPickerFromSelection();
        focusNode(equipmentComboRef.current, true);
        return;
      case "relato_cliente":
        focusNode(relatoFieldRef.current, true);
        return;
      case "tecnico_id":
        focusNode(tecnicoFieldRef.current, true);
        return;
      case "prioridade":
        focusNode(prioridadeFieldRef.current, true);
        return;
      case "status":
        focusNode(statusFieldRef.current, true);
        return;
      case "data_entrada":
        focusNode(dataEntradaFieldRef.current, true);
        return;
      case "data_previsao":
        focusNode(dataPrevisaoFieldRef.current, true);
        return;
      case "acessorios":
        focusNode(acessoriosSectionRef.current, false);
        openAccessoryModal("outro");
        return;
      case "checklist": {
        focusNode(checklistSectionRef.current, false);
        const trigger = checklistSectionRef.current?.querySelector("button.secondary-inline-button") as HTMLButtonElement | null;
        trigger?.click();
        return;
      }
      case "fotos_entrada":
        focusNode(fotosEntradaSectionRef.current, false);
        return;
      case "observacoes_cliente":
        focusNode(observacoesClienteRef.current, true);
        return;
      case "observacoes_internas":
        focusNode(observacoesInternasRef.current, true);
        return;
      default:
        return;
    }
  }

  function openOrderReviewModal() {
    const snapshot = buildOrderReviewSnapshot();
    setOrderReviewSnapshot(snapshot);
    setOrderReviewError("");
    setClientNotificationMode("none");
    setOrderReviewStep("summary");
    setOrderReviewOpen(true);
  }

  function closeOrderReviewModal() {
    setOrderReviewOpen(false);
    setOrderReviewSnapshot(null);
    setOrderReviewStep("summary");
    setOrderReviewError("");
  }

  function resolveMissingField(row: ReviewFieldRow | undefined) {
    if (!row) {
      return;
    }
    closeOrderReviewModal();
    focusReviewField(row.key);
  }

  async function submitOrderRequest() {
    setSubmitting(true);
    setOrderReviewError("");
    setError("");
    setSuccess("");
    try {
      const payload = new FormData();
      payload.set("cliente_id", form.cliente_id);
      payload.set("equipamento_id", form.equipamento_id);
      payload.set("relato_cliente", form.relato_cliente.trim());
      payload.set("status", form.status || "triagem");
      payload.set("prioridade", form.prioridade || "normal");
      payload.set("data_entrada", form.data_entrada);
      payload.set("notificar_cliente", clientNotificationMode === "none" ? "0" : "1");
      if (clientNotificationMode !== "none") {
        payload.set("notificacao_cliente_modo", clientNotificationMode);
      }

      if (form.tecnico_id) {
        payload.set("tecnico_id", form.tecnico_id);
      }
      if (form.data_previsao) {
        payload.set("data_previsao", form.data_previsao);
      }
      if (form.observacoes_cliente.trim()) {
        payload.set("observacoes_cliente", form.observacoes_cliente.trim());
      }
      if (form.observacoes_internas.trim()) {
        payload.set("observacoes_internas", form.observacoes_internas.trim());
      }

      if (accessoryEntries.length > 0) {
        payload.set(
          "acessorios_data",
          JSON.stringify(
            accessoryEntries.map((entry) => ({
              id: entry.id,
              text: entry.text,
              key: entry.key,
              values: entry.values
            }))
          )
        );
        accessoryEntries.forEach((entry) => {
          entry.photos.forEach((file) => {
            payload.append(`fotos_acessorios[${entry.id}][]`, file);
          });
        });
      }

      if (checklistEntrada.possuiModelo) {
        payload.set("checklist_entrada_data", JSON.stringify(serializeChecklistDraft(checklistEntrada)));
        appendChecklistFiles(payload, checklistEntrada);
      }

      entryPhotos.forEach((file) => {
        payload.append("fotos_entrada[]", file);
      });

      const created = await apiRequest<CreateOrderResponse>("/orders", {
        method: "POST",
        body: payload
      });

      closeOrderReviewModal();
      setSuccess(`OS ${created.numero_os} criada com sucesso.`);
      router.push(`/os/${created.id}`);
    } catch (err) {
      const message = err instanceof Error ? err.message : "Falha ao criar OS.";
      setOrderReviewError(message);
      setError(message);
    } finally {
      setSubmitting(false);
    }
  }

  async function onSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setSuccess("");
    openOrderReviewModal();
  }

  return (
    <>
      <header className="mobile-header">
        <div className="mobile-header-row">
          <div className="mobile-header-main">
            <h1 className="mobile-title">Nova Ordem de Servico</h1>
            <p className="mobile-subtitle">Abertura inicial com dados essenciais do cliente</p>
          </div>
          <div className="mobile-header-actions">
            <button
              type="button"
              className="mobile-header-reset-btn"
              onClick={resetOrderForm}
              aria-label="Reiniciar cadastro"
              title="Reiniciar cadastro"
            >
              <svg
                aria-hidden="true"
                viewBox="0 0 24 24"
                className="mobile-header-reset-icon"
                focusable="false"
              >
                <path
                  d="M20 12a8 8 0 1 1-2.35-5.65M20 4v5h-5"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="1.9"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />
              </svg>
            </button>
            <div className="mobile-header-menu-wrap" ref={headerMenuRef}>
            <button
              type="button"
              className={`mobile-header-menu-btn ${headerMenuOpen ? "is-open" : ""}`}
              aria-label="Abrir menu de acoes"
              aria-expanded={headerMenuOpen}
              onClick={() => setHeaderMenuOpen((prev) => !prev)}
            >
              <span aria-hidden="true">{"\u2630"}</span>
            </button>
            {headerMenuOpen ? (
              <div className="mobile-header-menu-panel">
                <p className="mobile-header-menu-title">Acoes extras</p>
                <button
                  type="button"
                  className="mobile-header-menu-item"
                  disabled={!selectedClient}
                  onClick={() => {
                    setHeaderMenuOpen(false);
                    void openEditClientModal();
                  }}
                >
                  Editar cliente selecionado
                </button>
                <button
                  type="button"
                  className="mobile-header-menu-item"
                  disabled={!selectedEquipment}
                  onClick={() => {
                    setHeaderMenuOpen(false);
                    void openEditEquipmentModal();
                  }}
                >
                  Editar equipamento selecionado
                </button>
                <div className="mobile-header-menu-divider" />
                <button
                  type="button"
                  className="mobile-header-menu-item"
                  onClick={() => {
                    setHeaderMenuOpen(false);
                    void loadMeta({
                      q: clientQuery,
                      clienteId: form.cliente_id,
                      equipamentoId: form.equipamento_id
                    });
                  }}
                >
                  Atualizar dados da tela
                </button>
              </div>
            ) : null}
            </div>
          </div>
        </div>
      </header>

      <section className="mobile-card">
        <Link href="/os" className="helper-line">
          Voltar para OS
        </Link>

        <p className="helper-line">
          {filteredClients.length} cliente(s) disponivel(is) para selecao.
        </p>

        <form className="form-block" style={{ marginTop: 10 }} onSubmit={onSubmit}>
          <strong>Dados Principais</strong>

          <div className="client-combobox" ref={clientComboRef}>
            {selectedClient && !clientPickerOpen ? (
              <div
                className="client-selected-field"
                role="button"
                tabIndex={0}
                onClick={openClientPickerFromSelection}
                onKeyDown={(event) => {
                  if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    openClientPickerFromSelection();
                  }
                }}
                title="Trocar cliente"
              >
                <strong>{selectedClient.nome_razao || "Cliente selecionado"}</strong>
                <small>{selectedClientPhone || "Sem telefone cadastrado"}</small>
              </div>
            ) : (
              <input
                type="text"
                placeholder="Selecione o cliente (busca por nome ou telefone)"
                value={clientQuery}
                onFocus={() => setClientPickerOpen(true)}
                onChange={(event) => handleClientInputChange(event.target.value)}
              />
            )}
            <button
              type="button"
              className="client-combobox-add"
              onClick={openCreateClientModal}
              title="Adicionar novo cliente"
            >
              <span aria-hidden="true">+</span>
            </button>
            {clientPickerOpen ? (
              <div className="client-combobox-menu">
                {filteredClients.length === 0 ? (
                  <div className="client-combobox-empty">Nenhum cliente encontrado.</div>
                ) : (
                  filteredClients.map((client) => (
                    <button
                      key={client.id}
                      type="button"
                      className={`client-combobox-option ${form.cliente_id === String(client.id) ? "is-active" : ""}`}
                      onClick={() => selectClient(client)}
                    >
                      <strong>{client.nome_razao}</strong>
                      <small>
                        {(client.telefone1 || "").trim() || "Sem telefone"} {client.email ? `| ${client.email}` : ""}
                      </small>
                    </button>
                  ))
                )}
              </div>
            ) : null}
          </div>
          <div className="field-with-action field-combobox" ref={equipmentComboRef}>
            {selectedEquipment && !equipmentPickerOpen ? (
              <div
                className="equipment-selected-field"
                role="button"
                tabIndex={0}
                onClick={openEquipmentPickerFromSelection}
                onKeyDown={(event) => {
                  if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    openEquipmentPickerFromSelection();
                  }
                }}
              >
                {selectedEquipment.foto_url ? (
                  <button
                    type="button"
                    className="equipment-selected-media"
                    title="Visualizar fotos do perfil do equipamento"
                    onClick={(event) => {
                      event.stopPropagation();
                      openEquipmentGallery(selectedEquipment);
                    }}
                  >
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img src={selectedEquipment.foto_url} alt={formatEquipmentGalleryTitle(selectedEquipment) || "Foto do equipamento"} />
                  </button>
                ) : (
                  <div className="equipment-selected-media" aria-hidden="true">
                    <span className="equipment-combobox-fallback">{equipmentFallbackLabel(selectedEquipment)}</span>
                  </div>
                )}
                <div className="equipment-selected-copy">
                  <strong>{formatEquipmentPrimaryLine(selectedEquipment)}</strong>
                  {formatEquipmentSecondaryLine(selectedEquipment) ? (
                    <small>{formatEquipmentSecondaryLine(selectedEquipment)}</small>
                  ) : null}
                  {formatEquipmentPreferredIdentityLine(selectedEquipment) ? (
                    <small>{formatEquipmentPreferredIdentityLine(selectedEquipment)}</small>
                  ) : null}
                </div>
              </div>
            ) : (
              <input
                type="text"
                required
                disabled={!form.cliente_id}
                placeholder="Selecione o equipamento"
                value={equipmentQuery}
                onFocus={() => {
                  if (form.cliente_id) {
                    setEquipmentPickerOpen(true);
                  }
                }}
                onChange={(event) => handleEquipmentInputChange(event.target.value)}
              />
            )}
            <button
              type="button"
              className="field-action-btn"
              onClick={() => void openCreateEquipmentModal()}
              title="Adicionar novo equipamento"
              disabled={!selectedClient}
            >
              <span aria-hidden="true">+</span>
            </button>
            {equipmentPickerOpen && form.cliente_id ? (
              <div className="client-combobox-menu equipment-combobox-menu">
                {filteredEquipments.length === 0 ? (
                  <div className="client-combobox-empty">Nenhum equipamento encontrado para este cliente.</div>
                ) : (
                  filteredEquipments.map((equipment) => (
                    <div
                      key={equipment.id}
                      className={`client-combobox-option equipment-combobox-option ${form.equipamento_id === String(equipment.id) ? "is-active" : ""}`}
                      onClick={() => selectEquipment(equipment)}
                      onKeyDown={(event) => handleEquipmentOptionKeyDown(event, equipment)}
                      role="button"
                      tabIndex={0}
                    >
                      {equipment.foto_url ? (
                        <button
                          type="button"
                          className="equipment-combobox-media equipment-combobox-media-btn"
                          title="Visualizar fotos do perfil do equipamento"
                          onClick={(event) => {
                            event.stopPropagation();
                            openEquipmentGallery(equipment);
                          }}
                        >
                          {/* eslint-disable-next-line @next/next/no-img-element */}
                          <img src={equipment.foto_url} alt={formatEquipmentGalleryTitle(equipment) || "Foto do equipamento"} />
                        </button>
                      ) : (
                        <div className="equipment-combobox-media" aria-hidden="true">
                          <span className="equipment-combobox-fallback">{equipmentFallbackLabel(equipment)}</span>
                        </div>
                      )}
                      <div className="equipment-combobox-content">
                        <strong className="equipment-combobox-primary">{formatEquipmentPrimaryLine(equipment)}</strong>
                        <small className="equipment-combobox-secondary">
                          {formatEquipmentSecondaryLine(equipment) || "Modelo ou cor nao informados"}
                        </small>
                        {formatEquipmentPreferredIdentityLine(equipment) ? (
                          <small className="equipment-combobox-identity">{formatEquipmentPreferredIdentityLine(equipment)}</small>
                        ) : null}
                      </div>
                    </div>
                  ))
                )}
              </div>
            ) : null}
          </div>
          <div className="field-with-action field-with-action-top">
            <textarea
              ref={relatoFieldRef}
              rows={3}
              required
              placeholder="Relato do cliente"
              value={form.relato_cliente}
              onChange={(event) =>
                setForm((prev) => ({
                  ...prev,
                  relato_cliente: event.target.value
                }))
              }
            />
            <button
              type="button"
              className="field-action-btn"
              onClick={openReportedDefectModal}
              title="Selecionar defeitos relatados"
              aria-label="Selecionar defeitos relatados"
            >
              <span aria-hidden="true">+</span>
            </button>
          </div>

          <section className="collection-block operational-data-block">
            <div className="collection-block-header">
              <div>
                <strong>Dados operacionais</strong>
              </div>
            </div>

            {singleTechnician ? (
              <label>
                Tecnico atribuido
                <input
                  ref={(node) => {
                    tecnicoFieldRef.current = node;
                  }}
                  type="text"
                  value={singleTechnician.nome}
                  readOnly
                />
              </label>
            ) : (
              <label>
                Tecnico atribuido
                <select
                  ref={(node) => {
                    tecnicoFieldRef.current = node;
                  }}
                  required
                  value={form.tecnico_id}
                  onChange={(event) =>
                    setForm((prev) => ({
                      ...prev,
                      tecnico_id: event.target.value
                    }))
                  }
                >
                  <option value="">Selecione o tecnico</option>
                  {technicians.map((technician) => (
                    <option key={technician.id} value={String(technician.id)}>
                      {technician.nome}
                    </option>
                  ))}
                </select>
              </label>
            )}

            <label>
              Prioridade
              <select
                ref={prioridadeFieldRef}
                required
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
            </label>

            <label>
              Status
              <select
                ref={statusFieldRef}
                required
                value={form.status}
                onChange={(event) =>
                  setForm((prev) => ({
                    ...prev,
                    status: event.target.value
                  }))
                }
              >
                {statuses.map((status) => (
                  <option key={status.codigo} value={status.codigo}>
                    {status.nome}
                  </option>
                ))}
              </select>
            </label>

            <label>
              Data de entrada
              <input
                ref={dataEntradaFieldRef}
                type="datetime-local"
                required
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
              Previsao (dias corridos)
              <select
                required
                value={forecastPreset}
                onChange={(event) => {
                  const preset = event.target.value;
                  setForecastPreset(preset);
                  if (preset === "custom") {
                    return;
                  }
                  const days = Number(preset);
                  if (!Number.isFinite(days)) {
                    return;
                  }
                  const baseDate = parseDateTimeLocal(form.data_entrada) || new Date();
                  setForm((prev) => ({
                    ...prev,
                    data_previsao: formatDateTimeLocal(addDays(baseDate, days))
                  }));
                }}
              >
                <option value="1">1 dia</option>
                <option value="3">3 dias</option>
                <option value="7">7 dias</option>
                <option value="30">30 dias</option>
                <option value="custom">Personalizado</option>
              </select>
            </label>

            <label>
              Data de previsao
              <input
                ref={dataPrevisaoFieldRef}
                type="datetime-local"
                required
                value={form.data_previsao}
                onChange={(event) => {
                  setForecastPreset("custom");
                  setForm((prev) => ({
                    ...prev,
                    data_previsao: event.target.value
                  }));
                }}
              />
            </label>
          </section>

          <section ref={acessoriosSectionRef} className="collection-block">
            <div className="collection-block-header">
              <div>
                <strong>Acessorios</strong>
              </div>
              <button type="button" className="secondary-inline-button" onClick={() => openAccessoryModal("outro")}>
                + Adicionar
              </button>
            </div>
            {accessoryEntries.length > 0 ? (
              <div className="collection-entry-list">
                {accessoryEntries.map((entry) => (
                  <article key={entry.id} className="collection-entry-card">
                    <div className="collection-entry-top">
                      <div>
                        <strong>{entry.text}</strong>
                        <small>
                          {entry.photos.length > 0
                            ? `${entry.photos.length} foto(s) vinculada(s)`
                            : "Sem fotos vinculadas"}
                        </small>
                      </div>
                      <div className="collection-entry-actions">
                        <button type="button" className="mini-action-btn" onClick={() => editAccessoryEntry(entry.id)}>
                          Editar
                        </button>
                        <button type="button" className="mini-action-btn is-danger" onClick={() => removeAccessoryEntry(entry.id)}>
                          Remover
                        </button>
                      </div>
                    </div>
                    {entry.photos.length > 0 ? (
                      <div className="collection-photo-row">
                        {entry.photos.slice(0, 4).map((file, index) => (
                          <div key={`${entry.id}_${index}`} className="collection-photo-thumb">
                            {/* eslint-disable-next-line @next/next/no-img-element */}
                            <img src={URL.createObjectURL(file)} alt={file.name} />
                          </div>
                        ))}
                      </div>
                    ) : null}
                  </article>
                ))}
              </div>
            ) : (
              <p className="helper-line">Nenhum acessorio adicionado ainda.</p>
            )}
          </section>

          <section ref={checklistSectionRef}>
            <ChecklistEntradaField value={checklistEntrada} onChange={setChecklistEntrada} />
          </section>

          <section ref={fotosEntradaSectionRef} className="collection-block">
            <div className="collection-block-header">
              <div>
                <strong>Fotos de entrada</strong>
              </div>
            </div>
            <div className="photo-entry-actions">
              <button
                type="button"
                className="photo-entry-btn is-primary"
                onClick={() => entryCameraInputRef.current?.click()}
              >
                Tirar foto
              </button>
              <button
                type="button"
                className="photo-entry-btn"
                onClick={() => entryGalleryInputRef.current?.click()}
              >
                Galeria
              </button>
            </div>
            <input
              ref={entryGalleryInputRef}
              type="file"
              accept="image/*"
              multiple
              hidden
              onChange={(event) => {
                handleEntryFiles(event.target.files);
                event.currentTarget.value = "";
              }}
            />
            <input
              ref={entryCameraInputRef}
              type="file"
              accept="image/*"
              capture="environment"
              multiple
              hidden
              onChange={(event) => {
                handleEntryFiles(event.target.files);
                event.currentTarget.value = "";
              }}
            />
            {entryPhotos.length > 0 ? (
              <>
                <p className="helper-line">
                  {entryPhotos.length} de {ENTRY_PHOTO_MAX_FILES} foto(s) de entrada selecionada(s).
                </p>
                <div className="collection-photo-row">
                  {entryPhotoPreviews.map((preview) => (
                    <div key={`${preview.file.name}_${preview.index}`} className="collection-photo-thumb">
                      <button
                        type="button"
                        className="collection-photo-preview"
                        onClick={() => openEntryPreview(preview.file, `Foto de entrada ${preview.index + 1}`)}
                        aria-label={`Visualizar foto de entrada ${preview.index + 1}`}
                      >
                        {/* eslint-disable-next-line @next/next/no-img-element */}
                        <img src={preview.url} alt={preview.file.name} />
                      </button>
                      <button
                        type="button"
                        className="collection-photo-remove"
                        onClick={(event) => {
                          event.stopPropagation();
                          removeEntryPhoto(preview.index);
                        }}
                      >
                        x
                      </button>
                    </div>
                  ))}
                </div>
              </>
            ) : (
              <div className="photo-empty-state">
                <strong>Nenhuma foto de entrada selecionada</strong>
                <small>Use Tirar foto ou Galeria para anexar ate {ENTRY_PHOTO_MAX_FILES} imagens iniciais da OS.</small>
              </div>
            )}
          </section>

          <section className="collection-block">
            <div className="collection-block-header">
              <div>
                <strong>Observacoes</strong>
              </div>
            </div>
            <textarea
              ref={observacoesClienteRef}
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
              ref={observacoesInternasRef}
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
          </section>

          <button type="submit" disabled={submitting}>
            {submitting ? "Salvando..." : "Criar OS"}
          </button>
        </form>

        {success ? <p className="helper-line">{success}</p> : null}
        {error ? <p className="error-line">{error}</p> : null}
      </section>

      {orderReviewOpen && orderReviewSnapshot ? (
        <div className="modal-overlay" onClick={closeOrderReviewModal}>
          <section className="inline-modal os-review-modal" onClick={(event) => event.stopPropagation()}>
            <header className="inline-modal-header">
              <h3>Resumo da OS antes de criar</h3>
              <button type="button" className="inline-modal-close" onClick={closeOrderReviewModal}>
                x
              </button>
            </header>

            {orderReviewStep === "summary" ? (
              <>
                <p className="inline-modal-subtitle">
                  Revise todos os dados preenchidos. Toque em qualquer item pendente para corrigir no formulario.
                </p>
                <div className="os-review-counters">
                  <span className="os-review-counter is-required">
                    Obrigatorios pendentes: {orderReviewSnapshot.requiredMissing.length}
                  </span>
                  <span className="os-review-counter is-optional">
                    Opcionais pendentes: {orderReviewSnapshot.optionalMissing.length}
                  </span>
                </div>

                <div className="os-review-list">
                  {orderReviewSnapshot.rows.map((row) => (
                    <button
                      key={row.key}
                      type="button"
                      className={`os-review-row ${row.filled ? "is-filled" : "is-missing"}`}
                      onClick={() => {
                        if (!row.filled) {
                          resolveMissingField(row);
                        }
                      }}
                    >
                      <div className="os-review-row-main">
                        <strong>{row.label}</strong>
                        <small>{row.value}</small>
                      </div>
                      <div className="os-review-row-tags">
                        <span className={`os-review-chip ${row.required ? "is-required" : "is-optional"}`}>
                          {row.required ? "Obrigatorio" : "Opcional"}
                        </span>
                        <span className={`os-review-chip ${row.filled ? "is-ok" : "is-missing"}`}>
                          {row.filled ? "Preenchido" : "Pendente"}
                        </span>
                      </div>
                    </button>
                  ))}
                </div>

                {accessoryEntries.length > 0 ? (
                  <div className="os-review-media-block">
                    <p className="helper-line">Acessorios registrados</p>
                    <div className="os-review-accessory-list">
                      {accessoryEntries.map((entry) => (
                        <article key={entry.id} className="os-review-accessory-card">
                          <strong>{entry.text}</strong>
                          <small>
                            {entry.photos.length > 0
                              ? `${entry.photos.length} foto(s) vinculada(s)`
                              : "Sem fotos vinculadas"}
                          </small>
                        </article>
                      ))}
                    </div>
                  </div>
                ) : null}

                {entryPhotos.length > 0 ? (
                  <div className="os-review-media-block">
                    <p className="helper-line">Fotos anexadas na OS</p>
                    <div className="collection-photo-row">
                      {entryPhotoPreviews.map((preview) => (
                        <button
                          key={`${preview.file.name}_${preview.index}`}
                          type="button"
                          className="collection-photo-thumb collection-photo-thumb-btn"
                          onClick={() => openEntryPreview(preview.file, `Foto de entrada ${preview.index + 1}`)}
                        >
                          {/* eslint-disable-next-line @next/next/no-img-element */}
                          <img
                            src={preview.url}
                            alt={preview.file.name || `Foto de entrada ${preview.index + 1}`}
                          />
                        </button>
                      ))}
                    </div>
                  </div>
                ) : null}
              </>
            ) : (
              <>
                <p className="inline-modal-subtitle">
                  Antes de salvar, escolha se deseja notificar o cliente sobre a abertura da OS.
                </p>
                <div className="os-notify-option-list">
                  <label className={`os-notify-option ${clientNotificationMode === "none" ? "is-active" : ""}`}>
                    <input
                      type="radio"
                      name="client_notification_mode"
                      value="none"
                      checked={clientNotificationMode === "none"}
                      onChange={() => setClientNotificationMode("none")}
                    />
                    <span>Nao notificar o cliente agora</span>
                  </label>
                  <label className={`os-notify-option ${clientNotificationMode === "message" ? "is-active" : ""}`}>
                    <input
                      type="radio"
                      name="client_notification_mode"
                      value="message"
                      checked={clientNotificationMode === "message"}
                      onChange={() => setClientNotificationMode("message")}
                    />
                    <span>Notificar com mensagem de abertura</span>
                  </label>
                  <label className={`os-notify-option ${clientNotificationMode === "message_pdf" ? "is-active" : ""}`}>
                    <input
                      type="radio"
                      name="client_notification_mode"
                      value="message_pdf"
                      checked={clientNotificationMode === "message_pdf"}
                      onChange={() => setClientNotificationMode("message_pdf")}
                    />
                    <span>Notificar com mensagem + PDF da OS</span>
                  </label>
                </div>
                <p className="helper-line">
                  A preferencia de notificacao sera enviada junto da criacao da OS para processamento no ERP.
                </p>
              </>
            )}

            {orderReviewError ? <p className="error-line">{orderReviewError}</p> : null}

            <div className="floating-modal-footer">
              {orderReviewStep === "summary" ? (
                <>
                  <button
                    type="button"
                    className="floating-footer-btn is-muted"
                    onClick={() => {
                      if (orderReviewSnapshot.requiredMissing.length > 0) {
                        resolveMissingField(orderReviewSnapshot.requiredMissing[0]);
                        return;
                      }
                      if (orderReviewSnapshot.optionalMissing.length > 0) {
                        resolveMissingField(orderReviewSnapshot.optionalMissing[0]);
                        return;
                      }
                      closeOrderReviewModal();
                    }}
                  >
                    {orderReviewSnapshot.requiredMissing.length > 0
                      ? "Corrigir obrigatorios"
                      : orderReviewSnapshot.optionalMissing.length > 0
                        ? "Preencher opcionais"
                        : "Fechar"}
                  </button>
                  <button
                    type="button"
                    className="floating-footer-btn is-primary"
                    onClick={() => setOrderReviewStep("notify")}
                    disabled={orderReviewSnapshot.requiredMissing.length > 0}
                  >
                    {orderReviewSnapshot.optionalMissing.length > 0 ? "Prosseguir sem opcionais" : "Continuar"}
                  </button>
                </>
              ) : (
                <>
                  <button
                    type="button"
                    className="floating-footer-btn is-muted"
                    onClick={() => setOrderReviewStep("summary")}
                    disabled={submitting}
                  >
                    Voltar
                  </button>
                  <button
                    type="button"
                    className="floating-footer-btn is-primary"
                    onClick={() => void submitOrderRequest()}
                    disabled={submitting}
                  >
                    {submitting ? "Salvando..." : "Confirmar e criar OS"}
                  </button>
                </>
              )}
            </div>
          </section>
        </div>
      ) : null}

      {reportedDefectModalOpen ? (
        <div className="modal-overlay" onClick={() => setReportedDefectModalOpen(false)}>
          <section className="inline-modal" onClick={(event) => event.stopPropagation()}>
            <header className="inline-modal-header">
              <h3>Defeitos relatados</h3>
              <button type="button" className="inline-modal-close" onClick={() => setReportedDefectModalOpen(false)}>
                x
              </button>
            </header>
            <p className="inline-modal-subtitle">
              Selecione um item cadastrado em Gestao de Conhecimento &gt; Defeitos Relatados para preencher o relato.
            </p>

            {reportedDefectGroups.length > 0 ? (
              <>
                <div className="quick-chip-row">
                  {reportedDefectGroups.map((group) => (
                    <button
                      key={group.categoria}
                      type="button"
                      className={`quick-chip-btn ${activeReportedDefectGroup?.categoria === group.categoria ? "is-active" : ""}`}
                      onClick={() => setReportedDefectCategory(group.categoria)}
                    >
                      {(group.icone || "").trim() ? `${group.icone} ` : ""}
                      {group.categoria}
                    </button>
                  ))}
                </div>

                <div className="modal-suggestion-list">
                  {(activeReportedDefectGroup?.itens || []).map((item) => (
                    <button
                      key={item.id}
                      type="button"
                      className="modal-suggestion-item"
                      onClick={() => applyReportedDefect(item.texto_relato)}
                    >
                      <strong>{item.texto_relato}</strong>
                      <small>{activeReportedDefectGroup?.categoria || item.categoria || "Relato rapido"}</small>
                    </button>
                  ))}
                </div>
              </>
            ) : (
              <div className="photo-empty-state">
                <strong>Nenhum defeito relatado ativo</strong>
                <small>Cadastre ou ative itens no ERP para usar a selecao rapida.</small>
              </div>
            )}

            <div className="floating-modal-footer is-single">
              <button
                type="button"
                className="floating-footer-btn is-primary"
                onClick={() => setReportedDefectModalOpen(false)}
              >
                Fechar
              </button>
            </div>
          </section>
        </div>
      ) : null}

      {accessoryModalOpen ? (
        <div className="modal-overlay" onClick={closeAccessoryModal}>
          <section className="inline-modal" onClick={(event) => event.stopPropagation()}>
            <header className="inline-modal-header">
              <h3>{accessoryEditingId ? "Editar acessorio" : "Novo acessorio"}</h3>
              <button type="button" className="inline-modal-close" onClick={closeAccessoryModal}>
                x
              </button>
            </header>
            <p className="inline-modal-subtitle">
              Cliente: {selectedClient?.nome_razao || "Selecione um cliente"}{" "}
              {selectedClientPhone ? `| ${selectedClientPhone}` : ""}
            </p>
            <div className="quick-chip-row">
              {accessoryConfigs.map((config) => (
                <button
                  key={config.key}
                  type="button"
                  className={`quick-chip-btn ${accessoryDraftKey === config.key ? "is-active" : ""}`}
                  onClick={() => {
                    setAccessoryDraftKey(config.key);
                    setAccessoryDraftValues(emptyAccessoryValues(config));
                    setAccessoryDraftPhotos([]);
                    setAccessoryModalError("");
                  }}
                >
                  {config.title}
                </button>
              ))}
            </div>
            <div className="form-block nested-modal-form">
              {activeAccessoryConfig.fields.map((field) => (
                <label key={field.name}>
                  {field.label}
                  {field.type === "select" ? (
                    <select
                      value={accessoryDraftValues[field.name] || ""}
                      onChange={(event) => updateAccessoryField(field.name, event.target.value)}
                    >
                      {field.options.map((option) => (
                        <option key={`${field.name}_${option.value}`} value={option.value}>
                          {option.label}
                        </option>
                      ))}
                    </select>
                  ) : (
                    <input
                      type="text"
                      placeholder={field.placeholder || ""}
                      value={accessoryDraftValues[field.name] || ""}
                      onChange={(event) => updateAccessoryField(field.name, event.target.value)}
                    />
                  )}
                </label>
              ))}

              <div className="photo-upload-block">
                <label>Fotos do acessorio</label>
                <div className="photo-entry-actions">
                  <button
                    type="button"
                    className="photo-entry-btn is-primary"
                    onClick={() => accessoryCameraInputRef.current?.click()}
                  >
                    Tirar foto
                  </button>
                  <button
                    type="button"
                    className="photo-entry-btn"
                    onClick={() => accessoryGalleryInputRef.current?.click()}
                  >
                    Galeria
                  </button>
                </div>
                <input
                  ref={accessoryGalleryInputRef}
                  type="file"
                  accept="image/*"
                  multiple
                  hidden
                  onChange={(event) => {
                    handleAccessoryFiles(event.target.files);
                    event.currentTarget.value = "";
                  }}
                />
                <input
                  ref={accessoryCameraInputRef}
                  type="file"
                  accept="image/*"
                  capture="environment"
                  multiple
                  hidden
                  onChange={(event) => {
                    handleAccessoryFiles(event.target.files);
                    event.currentTarget.value = "";
                  }}
                />
                {accessoryDraftPhotos.length > 0 ? (
                  <div className="collection-photo-row">
                    {accessoryDraftPhotos.map((file, index) => (
                      <div key={`${file.name}_${index}`} className="collection-photo-thumb">
                        <button
                          type="button"
                          className="collection-photo-preview"
                          onClick={() => openAccessoryPreview(file, `Foto ${index + 1} do acessorio`)}
                          aria-label={`Visualizar foto ${index + 1} do acessorio`}
                        >
                          {/* eslint-disable-next-line @next/next/no-img-element */}
                          <img src={URL.createObjectURL(file)} alt={file.name} />
                        </button>
                        <button
                          type="button"
                          className="collection-photo-remove"
                          onClick={(event) => {
                            event.stopPropagation();
                            removeAccessoryPhoto(index);
                          }}
                        >
                          x
                        </button>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="photo-empty-state">
                    <strong>Nenhuma foto vinculada</strong>
                    <small>As fotos deste acessorio serao salvas no mesmo padrao do ERP.</small>
                  </div>
                )}
              </div>
              {accessoryModalError ? <p className="error-line">{accessoryModalError}</p> : null}
            </div>
            <div className="floating-modal-footer">
              <button type="button" className="floating-footer-btn is-muted" onClick={closeAccessoryModal}>
                Fechar
              </button>
              <button type="button" className="floating-footer-btn is-primary" onClick={submitAccessoryDraft}>
                {accessoryEditingId ? "Salvar acessorio" : "Adicionar acessorio"}
              </button>
            </div>

            {accessoryCropOpen ? (
              <div
                className="nested-modal-overlay"
                onClick={(event) => {
                  event.stopPropagation();
                  closeAccessoryCropper(true);
                }}
              >
                <section
                  className="nested-inline-modal nested-inline-modal-wide has-floating-footer"
                  onClick={(event) => event.stopPropagation()}
                >
                  <header className="inline-modal-header">
                    <h3>Ajustar foto do acessorio</h3>
                    <button type="button" className="inline-modal-close" onClick={() => closeAccessoryCropper(true)}>
                      x
                    </button>
                  </header>

                  <div className="cropper-modal-body">
                    <div className="cropper-canvas-wrap">
                      <img ref={accessoryCropImageRef} src={accessoryCropSource} alt="Foto em corte do acessorio" />
                    </div>
                  </div>

                  <div className="floating-modal-footer">
                    <button
                      type="button"
                      className="floating-footer-btn is-muted"
                      onClick={() => closeAccessoryCropper(true)}
                      disabled={accessoryCropBusy}
                    >
                      Cancelar foto
                    </button>
                    <button
                      type="button"
                      className="floating-footer-btn is-primary"
                      onClick={() => void confirmAccessoryCrop()}
                      disabled={accessoryCropBusy}
                    >
                      {accessoryCropBusy ? "Processando..." : "Confirmar corte"}
                    </button>
                  </div>
                </section>
              </div>
            ) : null}

            {accessoryPreviewOpen ? (
              <div
                className="nested-modal-overlay"
                onClick={(event) => {
                  event.stopPropagation();
                  closeAccessoryPreview();
                }}
              >
                <section
                  className="nested-inline-modal nested-inline-modal-wide has-floating-footer"
                  onClick={(event) => event.stopPropagation()}
                >
                  <header className="inline-modal-header">
                    <h3>{accessoryPreviewTitle || "Visualizar foto"}</h3>
                    <button type="button" className="inline-modal-close" onClick={closeAccessoryPreview}>
                      x
                    </button>
                  </header>

                  <div className="cropper-modal-body">
                    <div className="cropper-canvas-wrap image-preview-wrap">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img src={accessoryPreviewSource} alt={accessoryPreviewTitle || "Foto do acessorio"} />
                    </div>
                  </div>

                  <div className="floating-modal-footer">
                    <button type="button" className="floating-footer-btn is-primary" onClick={closeAccessoryPreview}>
                      Fechar visualizacao
                    </button>
                  </div>
                </section>
              </div>
            ) : null}
          </section>
        </div>
      ) : null}

      {equipmentGalleryOpen ? (
        <div
          className="nested-modal-overlay"
          onClick={(event) => {
            event.stopPropagation();
            closeEquipmentGallery();
          }}
        >
          <section
            className="nested-inline-modal nested-inline-modal-wide has-floating-footer"
            onClick={(event) => event.stopPropagation()}
          >
            <header className="inline-modal-header">
              <h3>{equipmentGalleryTitle || "Fotos do equipamento"}</h3>
              <button type="button" className="inline-modal-close" onClick={closeEquipmentGallery}>
                x
              </button>
            </header>

            <div className="cropper-modal-body">
              <div className="cropper-canvas-wrap image-preview-wrap">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={equipmentGalleryPhotos[equipmentGalleryActiveIndex] || ""}
                  alt={equipmentGalleryTitle || "Foto do equipamento"}
                />
              </div>
              {equipmentGalleryPhotos.length > 1 ? (
                <>
                  <div className="image-carousel-controls">
                    <button type="button" className="image-carousel-nav" onClick={() => moveEquipmentGallery(-1)}>
                      ‹ Anterior
                    </button>
                    <span className="image-carousel-status">
                      Foto {equipmentGalleryActiveIndex + 1} de {equipmentGalleryPhotos.length}
                    </span>
                    <button type="button" className="image-carousel-nav" onClick={() => moveEquipmentGallery(1)}>
                      Proxima ›
                    </button>
                  </div>
                  <div className="collection-photo-row">
                    {equipmentGalleryPhotos.map((photo, index) => (
                      <button
                        key={`${photo}_${index}`}
                        type="button"
                        className={`collection-photo-thumb collection-photo-thumb-btn ${
                          equipmentGalleryActiveIndex === index ? "is-active" : ""
                        }`}
                        onClick={() => setEquipmentGalleryActiveIndex(index)}
                      >
                        {/* eslint-disable-next-line @next/next/no-img-element */}
                        <img src={photo} alt={`Foto ${index + 1} do equipamento`} />
                      </button>
                    ))}
                  </div>
                </>
              ) : null}
            </div>

            <div className="floating-modal-footer">
              <button type="button" className="floating-footer-btn is-primary" onClick={closeEquipmentGallery}>
                Fechar visualizacao
              </button>
            </div>
          </section>
        </div>
      ) : null}

      {entryCropOpen ? (
        <div
          className="nested-modal-overlay"
          onClick={(event) => {
            event.stopPropagation();
            closeEntryCropper(true);
          }}
        >
          <section
            className="nested-inline-modal nested-inline-modal-wide has-floating-footer"
            onClick={(event) => event.stopPropagation()}
          >
            <header className="inline-modal-header">
              <h3>Ajustar foto de entrada</h3>
              <button type="button" className="inline-modal-close" onClick={() => closeEntryCropper(true)}>
                x
              </button>
            </header>

            <div className="cropper-modal-body">
              <div className="cropper-canvas-wrap">
                <img ref={entryCropImageRef} src={entryCropSource} alt="Foto em corte de entrada" />
              </div>
            </div>

            <div className="floating-modal-footer">
              <button
                type="button"
                className="floating-footer-btn is-muted"
                onClick={() => closeEntryCropper(true)}
                disabled={entryCropBusy}
              >
                Cancelar foto
              </button>
              <button
                type="button"
                className="floating-footer-btn is-primary"
                onClick={() => void confirmEntryCrop()}
                disabled={entryCropBusy}
              >
                {entryCropBusy ? "Processando..." : "Confirmar corte"}
              </button>
            </div>
          </section>
        </div>
      ) : null}

      {entryPreviewOpen ? (
        <div
          className="nested-modal-overlay"
          onClick={(event) => {
            event.stopPropagation();
            closeEntryPreview();
          }}
        >
          <section
            className="nested-inline-modal nested-inline-modal-wide has-floating-footer"
            onClick={(event) => event.stopPropagation()}
          >
            <header className="inline-modal-header">
              <h3>{entryPreviewTitle || "Visualizar foto de entrada"}</h3>
              <button type="button" className="inline-modal-close" onClick={closeEntryPreview}>
                x
              </button>
            </header>

            <div className="cropper-modal-body">
              <div className="cropper-canvas-wrap image-preview-wrap">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={entryPreviewSource} alt={entryPreviewTitle || "Foto de entrada"} />
              </div>
            </div>

            <div className="floating-modal-footer">
              <button type="button" className="floating-footer-btn is-primary" onClick={closeEntryPreview}>
                Fechar visualizacao
              </button>
            </div>
          </section>
        </div>
      ) : null}

      {clientModalMode ? (
        <div className="modal-overlay" onClick={closeAllModals}>
          <section className="inline-modal" onClick={(event) => event.stopPropagation()}>
            <header className="inline-modal-header">
              <h3>{clientModalMode === "create" ? "Novo cliente" : "Editar cliente"}</h3>
              <button type="button" className="inline-modal-close" onClick={closeAllModals}>
                x
              </button>
            </header>
            <form className="form-block" onSubmit={submitClientModal}>
              <input
                type="text"
                placeholder="Nome do cliente"
                value={clientForm.nome_razao}
                onChange={(event) => setClientForm((prev) => ({ ...prev, nome_razao: event.target.value }))}
                required
              />
              <input
                type="text"
                placeholder="Telefone principal"
                value={clientForm.telefone1}
                onChange={(event) => setClientForm((prev) => ({ ...prev, telefone1: event.target.value }))}
                required
              />
              <input
                type="text"
                placeholder="Telefone secundario"
                value={clientForm.telefone2}
                onChange={(event) => setClientForm((prev) => ({ ...prev, telefone2: event.target.value }))}
              />
              <input
                type="email"
                placeholder="E-mail"
                value={clientForm.email}
                onChange={(event) => setClientForm((prev) => ({ ...prev, email: event.target.value }))}
              />
              <input
                type="text"
                placeholder="Nome do contato (esposa, filho, sogra, vizinho...)"
                value={clientForm.nome_contato}
                onChange={(event) => setClientForm((prev) => ({ ...prev, nome_contato: event.target.value }))}
              />
              <input
                type="text"
                inputMode="numeric"
                placeholder="Telefone do contato"
                value={clientForm.telefone_contato}
                onChange={(event) => setClientForm((prev) => ({ ...prev, telefone_contato: event.target.value }))}
              />
              <input
                type="text"
                inputMode="numeric"
                placeholder="CEP"
                value={clientForm.cep}
                onChange={(event) => {
                  const nextCep = formatCep(event.target.value);
                  setClientForm((prev) => ({ ...prev, cep: nextCep }));
                  setClientCepHint("");
                  if (normalizeDigits(nextCep).length < 8) {
                    lastClientCepLookupRef.current = "";
                  }
                }}
                onBlur={() => {
                  void fillAddressByCep(clientForm.cep);
                }}
              />
              {clientCepHint ? <p className="helper-line">{clientCepHint}</p> : null}
              {clientCepLoading ? <p className="helper-line">Preenchendo endereco pelo CEP...</p> : null}
              <div className="mini-grid">
                <label>
                  Cidade
                  <input
                    type="text"
                    value={clientForm.cidade}
                    onChange={(event) => setClientForm((prev) => ({ ...prev, cidade: event.target.value }))}
                  />
                </label>
                <label>
                  UF
                  <input
                    type="text"
                    maxLength={2}
                    value={clientForm.uf}
                    onChange={(event) => setClientForm((prev) => ({ ...prev, uf: event.target.value.toUpperCase() }))}
                  />
                </label>
              </div>
              <div className="mini-grid">
                <label>
                  Endereco
                  <input
                    type="text"
                    value={clientForm.endereco}
                    onChange={(event) => setClientForm((prev) => ({ ...prev, endereco: event.target.value }))}
                  />
                </label>
                <label>
                  Numero
                  <input
                    type="text"
                    value={clientForm.numero}
                    onChange={(event) => setClientForm((prev) => ({ ...prev, numero: event.target.value }))}
                  />
                </label>
              </div>
              <input
                type="text"
                placeholder="Bairro"
                value={clientForm.bairro}
                onChange={(event) => setClientForm((prev) => ({ ...prev, bairro: event.target.value }))}
              />
              <input
                type="text"
                placeholder="Complemento"
                value={clientForm.complemento}
                onChange={(event) => setClientForm((prev) => ({ ...prev, complemento: event.target.value }))}
              />
              <textarea
                rows={2}
                placeholder="Observacoes"
                value={clientForm.observacoes}
                onChange={(event) => setClientForm((prev) => ({ ...prev, observacoes: event.target.value }))}
              />
              <button type="submit" disabled={clientModalSaving}>
                {clientModalSaving ? "Salvando..." : clientModalMode === "create" ? "Salvar cliente" : "Atualizar cliente"}
              </button>
              {clientModalError ? <p className="error-line">{clientModalError}</p> : null}
            </form>
          </section>
        </div>
      ) : null}

      {equipmentModalMode ? (
        <div className="modal-overlay" onClick={closeAllModals}>
          <section className="inline-modal" onClick={(event) => event.stopPropagation()}>
            <header className="inline-modal-header">
              <h3>{equipmentModalMode === "create" ? "Novo equipamento" : "Editar equipamento"}</h3>
              <button type="button" className="inline-modal-close" onClick={closeAllModals}>
                x
              </button>
            </header>
            {selectedClient ? (
              <p className="inline-modal-subtitle">
                Cliente: {selectedClient.nome_razao || "Cliente selecionado"}
              </p>
            ) : null}
            <form className="form-block" onSubmit={submitEquipmentModal}>
              <label>
                Tipo *
                <select
                  required
                  value={equipmentForm.tipo_id}
                  onChange={(event) => {
                    const nextTypeId = event.target.value;
                    setBrandQuery("");
                    setModelQuery("");
                    setBrandPickerOpen(false);
                    setModelPickerOpen(false);
                    setEquipmentForm((prev) => ({
                      ...prev,
                      tipo_id: nextTypeId,
                      marca_id: "",
                      modelo_id: "",
                      marca_nome: "",
                      modelo_nome: ""
                    }));
                    void loadEquipmentCatalog({ typeId: nextTypeId });
                  }}
                >
                  <option value="">Selecione o tipo</option>
                  {equipmentCatalog.tipos.map((tipo) => (
                    <option key={tipo.id} value={String(tipo.id)}>
                      {tipo.nome}
                    </option>
                  ))}
                </select>
              </label>

              <label>
                Marca *
                <div className="client-combobox field-combobox" ref={brandComboRef}>
                  <input
                    type="text"
                    placeholder={equipmentForm.tipo_id ? "Buscar ou selecionar marca" : "Selecione primeiro o tipo"}
                    value={brandQuery}
                    disabled={!equipmentForm.tipo_id}
                    onFocus={() => {
                      if (!equipmentForm.tipo_id) {
                        return;
                      }
                      setBrandPickerOpen(true);
                    }}
                    onChange={(event) => handleBrandInputChange(event.target.value)}
                  />
                  <button
                    type="button"
                    className="client-combobox-add"
                    title="Cadastrar nova marca"
                    onClick={openBrandModal}
                    disabled={!equipmentForm.tipo_id}
                  >
                    <span aria-hidden="true">+</span>
                  </button>
                  {brandPickerOpen ? (
                    <div className="client-combobox-menu">
                      {filteredBrands.length === 0 ? (
                        <div className="client-combobox-empty">Nenhuma marca encontrada.</div>
                      ) : (
                        filteredBrands.map((marca) => (
                          <button
                            key={marca.id}
                            type="button"
                            className={`client-combobox-option ${equipmentForm.marca_id === String(marca.id) ? "is-active" : ""}`}
                            onClick={() => selectBrand(marca)}
                          >
                            <strong>{marca.nome}</strong>
                            <small>Marca cadastrada</small>
                          </button>
                        ))
                      )}
                    </div>
                  ) : null}
                </div>
              </label>

              <label>
                Modelo *
                <div className="client-combobox field-combobox" ref={modelComboRef}>
                  <input
                    type="text"
                    placeholder={
                      !equipmentForm.tipo_id
                        ? "Selecione primeiro o tipo"
                        : !equipmentForm.marca_id
                          ? "Selecione a marca primeiro"
                          : "Buscar ou selecionar modelo"
                    }
                    value={modelQuery}
                    disabled={!equipmentForm.tipo_id || !equipmentForm.marca_id}
                    onFocus={() => {
                      if (!equipmentForm.tipo_id || !equipmentForm.marca_id) {
                        return;
                      }
                      setModelPickerOpen(true);
                    }}
                    onChange={(event) => handleModelInputChange(event.target.value)}
                  />
                  <button
                    type="button"
                    className="client-combobox-add"
                    title="Cadastrar novo modelo"
                    onClick={openModelModal}
                    disabled={!equipmentForm.tipo_id || !equipmentForm.marca_id}
                  >
                    <span aria-hidden="true">+</span>
                  </button>
                  {modelPickerOpen ? (
                    <div className="client-combobox-menu">
                      {filteredModelOptions.length === 0 ? (
                        <div className="client-combobox-empty">Nenhum modelo encontrado para esta marca.</div>
                      ) : (
                        filteredModelOptions.map((modelo) => (
                          <button
                            key={modelo.id}
                            type="button"
                            className={`client-combobox-option ${equipmentForm.modelo_id === String(modelo.id) ? "is-active" : ""}`}
                            onClick={() => selectModel(modelo)}
                          >
                            <strong>{modelo.nome}</strong>
                            <small>{selectedBrand?.nome || "Modelo vinculado a marca selecionada"}</small>
                          </button>
                        ))
                      )}
                    </div>
                  ) : null}
                </div>
              </label>

              <div className="mini-grid">
                <label>
                  Numero de serie
                  <input
                    type="text"
                    value={equipmentForm.numero_serie}
                    onChange={(event) => setEquipmentForm((prev) => ({ ...prev, numero_serie: event.target.value }))}
                  />
                </label>
                <label>
                  IMEI
                  <input
                    type="text"
                    value={equipmentForm.imei}
                    onChange={(event) => setEquipmentForm((prev) => ({ ...prev, imei: event.target.value }))}
                  />
                </label>
              </div>

              <label>
                Cor *
                <button type="button" className="selector-trigger selector-trigger-color" onClick={openColorModal}>
                  <span className="equipment-color-swatch" style={{ backgroundColor: normalizedEquipmentColorHex }} />
                  <span className="selector-trigger-copy">
                    <strong>{equipmentForm.cor || "Selecionar cor"}</strong>
                    <small>
                      {normalizedEquipmentColorHex} | RGB{" "}
                      {equipmentForm.cor_rgb || hexToRgbString(normalizedEquipmentColorHex)}
                    </small>
                  </span>
                  <span className="selector-trigger-icon" aria-hidden="true">
                    +
                  </span>
                </button>
              </label>

              {equipmentSuggestedColorHex ? (
                <button
                  type="button"
                  className="inline-secondary-btn"
                  onClick={() => {
                    applyEquipmentColor(equipmentSuggestedColorHex);
                    setColorModalOpen(false);
                  }}
                >
                  Aplicar cor sugerida da foto ({equipmentSuggestedColorHex})
                </button>
              ) : null}

              <input
                type="text"
                placeholder="Senha de acesso"
                value={equipmentForm.senha_acesso}
                onChange={(event) => setEquipmentForm((prev) => ({ ...prev, senha_acesso: event.target.value }))}
              />

              <textarea
                rows={2}
                placeholder="Estado fisico do equipamento"
                value={equipmentForm.estado_fisico}
                onChange={(event) => setEquipmentForm((prev) => ({ ...prev, estado_fisico: event.target.value }))}
              />

              <textarea
                rows={2}
                placeholder="Acessorios recebidos"
                value={equipmentForm.acessorios}
                onChange={(event) => setEquipmentForm((prev) => ({ ...prev, acessorios: event.target.value }))}
              />

              <div className="photo-upload-block">
                <label>Foto de perfil do equipamento *</label>
                <div className="photo-entry-actions">
                  <button
                    type="button"
                    className="photo-entry-btn is-primary"
                    onClick={() => equipmentCameraInputRef.current?.click()}
                  >
                    Tirar foto
                  </button>
                  <button
                    type="button"
                    className="photo-entry-btn"
                    onClick={() => equipmentGalleryInputRef.current?.click()}
                  >
                    Galeria
                  </button>
                </div>
                <input
                  ref={equipmentGalleryInputRef}
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  multiple
                  hidden
                  onChange={(event) => {
                    void handleEquipmentPhotosSelection(event.target.files);
                    event.target.value = "";
                  }}
                />
                <input
                  ref={equipmentCameraInputRef}
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  capture="environment"
                  hidden
                  onChange={(event) => {
                    void handleEquipmentPhotosSelection(event.target.files);
                    event.target.value = "";
                  }}
                />
              </div>

              {equipmentExistingPhotos.length === 0 && equipmentNewPhotoPreviews.length === 0 ? (
                <div className="photo-empty-state">
                  <strong>Nenhuma imagem selecionada</strong>
                  <small>Use Tirar foto ou Galeria para adicionar as imagens do equipamento.</small>
                </div>
              ) : null}

              {equipmentExistingPhotos.length > 0 ? (
                <div className="photo-existing-wrap">
                  <div className="photo-block-caption">Fotos ja cadastradas neste equipamento</div>
                  <div className="equipment-photo-grid">
                    {equipmentExistingPhotos.map((photo, index) => (
                      <div key={`existing-${photo.id}`} className="equipment-photo-card">
                        <img src={photo.url} alt="Foto existente do equipamento" />
                        {Number(photo.is_principal) === 1 || index === 0 ? (
                          <span className="equipment-photo-badge">Principal</span>
                        ) : null}
                      </div>
                    ))}
                  </div>
                </div>
              ) : null}

              {equipmentNewPhotoPreviews.length > 0 ? (
                <div className="equipment-photo-grid">
                  {equipmentNewPhotoPreviews.map((preview, index) => {
                    const isPrincipal = equipmentExistingPhotos.length === 0 && index === 0;
                    return (
                      <div key={`new-${preview.index}-${preview.file.name}`} className="equipment-photo-card">
                        <img src={preview.url} alt="Nova foto do equipamento" />
                        {isPrincipal ? <span className="equipment-photo-badge">Principal</span> : null}
                        <button
                          type="button"
                          className="equipment-photo-remove"
                          onClick={() => removeEquipmentNewPhoto(preview.index)}
                          aria-label="Remover foto selecionada"
                        >
                          x
                        </button>
                      </div>
                    );
                  })}
                </div>
              ) : null}

              <p className="helper-line">
                Envie ate {EQUIPMENT_PHOTO_MAX_FILES} fotos. A primeira foto sera usada como perfil principal.
              </p>

              <textarea
                rows={2}
                placeholder="Observacoes do equipamento"
                value={equipmentForm.observacoes}
                onChange={(event) => setEquipmentForm((prev) => ({ ...prev, observacoes: event.target.value }))}
              />
              <div className="inline-action-row">
                <button
                  type="button"
                  className="inline-action-btn"
                  onClick={closeAllModals}
                  disabled={equipmentModalSaving}
                >
                  Fechar
                </button>
                <button
                  type="submit"
                  className="inline-action-btn is-primary"
                  disabled={equipmentModalSaving || equipmentCatalogLoading}
                >
                  {equipmentModalSaving
                    ? "Salvando..."
                    : equipmentModalMode === "create"
                      ? "Salvar equipamento"
                      : "Atualizar equipamento"}
                </button>
              </div>
              {equipmentModalError ? <p className="error-line">{equipmentModalError}</p> : null}
            </form>
          </section>

          {brandModalOpen ? (
            <div
              className="nested-modal-overlay"
              onClick={(event) => {
                event.stopPropagation();
                closeBrandModal();
              }}
            >
              <section className="nested-inline-modal" onClick={(event) => event.stopPropagation()}>
                <header className="inline-modal-header">
                  <h3>Nova marca</h3>
                  <button type="button" className="inline-modal-close" onClick={closeBrandModal}>
                    x
                  </button>
                </header>
                <form className="form-block nested-modal-form" onSubmit={submitBrandModal}>
                  <input
                    type="text"
                    placeholder="Nome da marca"
                    value={brandFormName}
                    onChange={(event) => setBrandFormName(event.target.value)}
                    autoFocus
                    required
                  />
                  {brandFormName.trim() ? (
                    <div className="modal-suggestion-group">
                      <p className="helper-line">
                        Marcas parecidas ja cadastradas para este tipo
                      </p>
                      {brandModalSuggestions.length > 0 ? (
                        <div className="modal-suggestion-list">
                          {brandModalSuggestions.map((marca) => (
                            <button
                              key={marca.id}
                              type="button"
                              className={`modal-suggestion-item ${exactBrandModalMatch?.id === marca.id ? "is-exact" : ""}`}
                              onClick={() => applyExistingBrandSuggestion(marca)}
                            >
                              <strong>{marca.nome}</strong>
                              <small>
                                {exactBrandModalMatch?.id === marca.id
                                  ? "Ja existe exatamente essa marca. Toque para usar a cadastrada."
                                  : "Marca ja cadastrada"}
                              </small>
                            </button>
                          ))}
                        </div>
                      ) : (
                        <p className="helper-line">Nenhuma marca parecida encontrada.</p>
                      )}
                    </div>
                  ) : null}
                  <button type="submit" disabled={brandModalSaving}>
                    {brandModalSaving
                      ? "Salvando..."
                      : exactBrandModalMatch
                        ? "Usar marca existente"
                        : "Salvar marca"}
                  </button>
                  {brandModalError ? <p className="error-line">{brandModalError}</p> : null}
                </form>
              </section>
            </div>
          ) : null}

          {modelModalOpen ? (
            <div
              className="nested-modal-overlay"
              onClick={(event) => {
                event.stopPropagation();
                closeModelModal();
              }}
            >
              <section className="nested-inline-modal" onClick={(event) => event.stopPropagation()}>
                <header className="inline-modal-header">
                  <h3>Novo modelo</h3>
                  <button type="button" className="inline-modal-close" onClick={closeModelModal}>
                    x
                  </button>
                </header>
                <form className="form-block nested-modal-form" onSubmit={submitModelModal}>
                  <p className="helper-line">
                    Marca atual:{" "}
                    {equipmentCatalog.marcas.find((marca) => String(marca.id) === equipmentForm.marca_id)?.nome || "Nao definida"}
                  </p>
                  <input
                    type="text"
                    placeholder="Nome do modelo"
                    value={modelFormName}
                    onChange={(event) => setModelFormName(event.target.value)}
                    autoFocus
                    required
                  />
                  {modelFormName.trim() ? (
                    <div className="modal-suggestion-group">
                      <p className="helper-line">
                        Modelos parecidos ja cadastrados para esta marca
                      </p>
                      {modelModalSuggestions.length > 0 ? (
                        <div className="modal-suggestion-list">
                          {modelModalSuggestions.map((modelo) => (
                            <button
                              key={modelo.id}
                              type="button"
                              className={`modal-suggestion-item ${exactModelModalMatch?.id === modelo.id ? "is-exact" : ""}`}
                              onClick={() => applyExistingModelSuggestion(modelo)}
                            >
                              <strong>{modelo.nome}</strong>
                              <small>
                                {exactModelModalMatch?.id === modelo.id
                                  ? "Ja existe exatamente esse modelo. Toque para usar o cadastrado."
                                  : selectedBrand?.nome || "Modelo ja cadastrado"}
                              </small>
                            </button>
                          ))}
                        </div>
                      ) : (
                        <p className="helper-line">Nenhum modelo parecido encontrado.</p>
                      )}
                    </div>
                  ) : null}
                  <button type="submit" disabled={modelModalSaving}>
                    {modelModalSaving
                      ? "Salvando..."
                      : exactModelModalMatch
                        ? "Usar modelo existente"
                        : "Salvar modelo"}
                  </button>
                  {modelModalError ? <p className="error-line">{modelModalError}</p> : null}
                </form>
              </section>
            </div>
          ) : null}

          {colorModalOpen ? (
            <div
              className="nested-modal-overlay"
              onClick={(event) => {
                event.stopPropagation();
                closeColorModal();
              }}
            >
              <section
                className="nested-inline-modal nested-inline-modal-wide has-floating-footer"
                onClick={(event) => event.stopPropagation()}
              >
                <header className="inline-modal-header">
                  <h3>Selecionar cor</h3>
                  <button type="button" className="inline-modal-close" onClick={closeColorModal}>
                    x
                  </button>
                </header>

                <div className="color-modal-body">
                  <div className="color-modal-summary">
                    <span className="equipment-color-swatch" style={{ backgroundColor: normalizedEquipmentColorHex }} />
                    <div className="equipment-color-meta">
                      <strong>{equipmentForm.cor || "Cor nao definida"}</strong>
                      <small>
                        {normalizedEquipmentColorHex} | RGB{" "}
                        {equipmentForm.cor_rgb || hexToRgbString(normalizedEquipmentColorHex)}
                      </small>
                    </div>
                  </div>

                  {equipmentSuggestedColorHex ? (
                    <button
                      type="button"
                      className="inline-secondary-btn"
                      onClick={() => applyEquipmentColor(equipmentSuggestedColorHex)}
                    >
                      Usar cor sugerida da foto ({equipmentSuggestedColorHex})
                    </button>
                  ) : null}

                  <div className="color-group-list">
                    {groupedColorCatalog.map((group) => (
                      <section key={group.label} className="color-group-card">
                        <p className="color-group-title">{group.label}</p>
                        <div className="color-option-grid">
                          {group.items.map((color) => {
                            const isActive = normalizedEquipmentColorHex === normalizeHexColor(color.hex);
                            return (
                              <button
                                key={`${group.label}-${color.hex}`}
                                type="button"
                                className={`color-option-card ${isActive ? "is-active" : ""}`}
                                onClick={() => applyEquipmentColor(color.hex, color.name)}
                              >
                                <span className="color-option-card-swatch" style={{ backgroundColor: color.hex }} />
                                <span className="color-option-card-copy">
                                  <strong>{color.name}</strong>
                                  <small>{color.hex}</small>
                                </span>
                              </button>
                            );
                          })}
                        </div>
                      </section>
                    ))}
                  </div>

                  <div className="mini-grid">
                    <label>
                      Nome da cor
                      <input
                        type="text"
                        value={equipmentForm.cor}
                        onChange={(event) =>
                          setEquipmentForm((prev) => ({
                            ...prev,
                            cor: event.target.value
                          }))
                        }
                        placeholder="Ex: Preto fosco"
                      />
                    </label>
                    <label>
                      Hex da cor
                      <input
                        type="color"
                        value={normalizedEquipmentColorHex}
                        onChange={(event) => applyEquipmentColor(event.target.value, equipmentForm.cor)}
                      />
                    </label>
                  </div>
                </div>

                <div className="floating-modal-footer">
                  <button type="button" className="floating-footer-btn is-muted" onClick={closeColorModal}>
                    Fechar
                  </button>
                  <button type="button" className="floating-footer-btn is-primary" onClick={closeColorModal}>
                    Confirmar cor
                  </button>
                </div>
              </section>
            </div>
          ) : null}

          {equipmentCropOpen ? (
            <div
              className="nested-modal-overlay"
              onClick={(event) => {
                event.stopPropagation();
                closeEquipmentCropper(true);
              }}
            >
              <section
                className="nested-inline-modal nested-inline-modal-wide has-floating-footer"
                onClick={(event) => event.stopPropagation()}
              >
                <header className="inline-modal-header">
                  <h3>Ajustar foto do equipamento</h3>
                  <button type="button" className="inline-modal-close" onClick={() => closeEquipmentCropper(true)}>
                    x
                  </button>
                </header>

                <div className="cropper-modal-body">
                  <div className="cropper-canvas-wrap">
                    <img ref={equipmentCropImageRef} src={equipmentCropSource} alt="Foto em corte do equipamento" />
                  </div>
                </div>

                <div className="floating-modal-footer">
                  <button
                    type="button"
                    className="floating-footer-btn is-muted"
                    onClick={() => closeEquipmentCropper(true)}
                    disabled={equipmentCropBusy}
                  >
                    Cancelar foto
                  </button>
                  <button
                    type="button"
                    className="floating-footer-btn is-primary"
                    onClick={() => void confirmEquipmentCrop()}
                    disabled={equipmentCropBusy}
                  >
                    {equipmentCropBusy ? "Processando..." : "Confirmar corte"}
                  </button>
                </div>
              </section>
            </div>
          ) : null}
        </div>
      ) : null}

      <MobileNav />
    </>
  );
}
