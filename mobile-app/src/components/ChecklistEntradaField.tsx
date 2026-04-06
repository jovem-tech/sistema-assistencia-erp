"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import type Cropper from "cropperjs";

export type ChecklistApiPhoto = {
  id: number;
  url: string | null;
  arquivo?: string | null;
  arquivo_original?: string | null;
  ordem?: number;
};

export type ChecklistApiItem = {
  id: number;
  descricao: string;
  ordem: number;
  status: string;
  observacao: string;
  resposta_id?: number | null;
  fotos?: ChecklistApiPhoto[];
};

export type ChecklistApiPayload = {
  tipo?: { id: number; codigo: string; nome: string };
  modelo?: { id: number; nome: string } | null;
  numero_os?: string;
  possui_modelo: boolean;
  execucao?: Record<string, unknown> | null;
  itens: ChecklistApiItem[];
  resumo?: {
    preenchido: boolean;
    total_discrepancias: number;
    label: string;
    variant: string;
  };
};

export type ChecklistDraftPhoto = {
  kind: "existing" | "new";
  id?: number;
  url: string;
  name: string;
  file?: File;
};

export type ChecklistDraftItem = {
  item_id: number;
  descricao: string;
  status: "ok" | "discrepancia" | "nao_verificado";
  observacao: string;
  resposta_id: number | null;
  photos: ChecklistDraftPhoto[];
  removedPhotoIds: number[];
};

export type ChecklistDraft = {
  tipoCodigo: string;
  tipoNome: string;
  modeloNome: string | null;
  possuiModelo: boolean;
  resumoLabel: string;
  resumoVariant: "idle" | "secondary" | "success" | "warning";
  totalDiscrepancias: number;
  preenchido: boolean;
  itens: ChecklistDraftItem[];
};

type PreviewState = {
  title: string;
  photos: ChecklistDraftPhoto[];
  index: number;
};

type ChecklistEntradaFieldProps = {
  value: ChecklistDraft;
  onChange: (next: ChecklistDraft) => void;
  disabled?: boolean;
  buttonLabel?: string;
  disabledMessage?: string;
};

type CropQueueItem = {
  itemId: number;
  file: File;
};

function normalizeStatus(value: string): ChecklistDraftItem["status"] {
  if (value === "ok" || value === "discrepancia") {
    return value;
  }

  return "nao_verificado";
}

function summarizeDraft(
  draft: Pick<ChecklistDraft, "possuiModelo" | "itens">
): Pick<ChecklistDraft, "resumoLabel" | "resumoVariant" | "totalDiscrepancias" | "preenchido"> {
  if (!draft.possuiModelo) {
    return {
      resumoLabel: "Checklist nao configurado para este tipo",
      resumoVariant: "secondary",
      totalDiscrepancias: 0,
      preenchido: false
    };
  }

  const unresolved = draft.itens.some((item) => item.status === "nao_verificado");
  const totalDiscrepancias = draft.itens.filter((item) => item.status === "discrepancia").length;
  const preenchido = draft.itens.length > 0 && !unresolved;

  if (!preenchido) {
    return {
      resumoLabel: "Checklist nao preenchido",
      resumoVariant: "secondary",
      totalDiscrepancias,
      preenchido: false
    };
  }

  if (totalDiscrepancias <= 0) {
    return {
      resumoLabel: "0 discrepancias",
      resumoVariant: "success",
      totalDiscrepancias: 0,
      preenchido: true
    };
  }

  return {
    resumoLabel: totalDiscrepancias === 1 ? "1 item com discrepancia" : `${totalDiscrepancias} discrepancias registradas`,
    resumoVariant: "warning",
    totalDiscrepancias,
    preenchido: true
  };
}

export function buildChecklistDraft(payload?: ChecklistApiPayload | null): ChecklistDraft {
  if (!payload) {
    return {
      tipoCodigo: "entrada",
      tipoNome: "Checklist de entrada",
      modeloNome: null,
      possuiModelo: false,
      resumoLabel: "Selecione um equipamento",
      resumoVariant: "idle",
      totalDiscrepancias: 0,
      preenchido: false,
      itens: []
    };
  }

  const draft: ChecklistDraft = {
    tipoCodigo: payload.tipo?.codigo || "entrada",
    tipoNome: payload.tipo?.nome || "Checklist de entrada",
    modeloNome: payload.modelo?.nome || null,
    possuiModelo: Boolean(payload.possui_modelo),
    resumoLabel: "",
    resumoVariant: "secondary",
    totalDiscrepancias: 0,
    preenchido: false,
    itens: (payload.itens || [])
      .slice()
      .sort((left, right) => Number(left.ordem || 0) - Number(right.ordem || 0))
      .map((item) => ({
        item_id: Number(item.id),
        descricao: item.descricao || "",
        status: normalizeStatus(item.status || "nao_verificado"),
        observacao: item.observacao || "",
        resposta_id: item.resposta_id ? Number(item.resposta_id) : null,
        photos: (item.fotos || [])
          .filter((photo) => Boolean(photo?.url))
          .map((photo) => ({
            kind: "existing" as const,
            id: Number(photo.id),
            url: String(photo.url),
            name: photo.arquivo_original || photo.arquivo || `Foto ${photo.ordem || photo.id}`
          })),
        removedPhotoIds: []
      }))
  };

  if (!draft.possuiModelo) {
    draft.resumoLabel = "Checklist nao configurado para este tipo";
    draft.resumoVariant = "secondary";
    return draft;
  }

  const summary: Pick<ChecklistDraft, "resumoLabel" | "resumoVariant" | "totalDiscrepancias" | "preenchido"> = payload.resumo
    ? {
        resumoLabel: payload.resumo.label || "Checklist nao preenchido",
        resumoVariant:
          payload.resumo.variant === "success" || payload.resumo.variant === "warning" || payload.resumo.variant === "secondary"
            ? payload.resumo.variant
            : "secondary",
        totalDiscrepancias: Number(payload.resumo.total_discrepancias || 0),
        preenchido: Boolean(payload.resumo.preenchido)
      }
    : summarizeDraft(draft);

  return {
    ...draft,
    ...summary
  };
}

export function cloneChecklistDraft(draft: ChecklistDraft): ChecklistDraft {
  return {
    ...draft,
    itens: draft.itens.map((item) => ({
      ...item,
      photos: item.photos.map((photo) => ({ ...photo })),
      removedPhotoIds: [...item.removedPhotoIds]
    }))
  };
}

export function checklistIsComplete(draft: ChecklistDraft): boolean {
  if (!draft.possuiModelo) {
    return true;
  }

  return draft.itens.every((item) => item.status !== "nao_verificado");
}

export function serializeChecklistDraft(draft: ChecklistDraft): { itens: Array<Record<string, unknown>> } {
  return {
    itens: draft.itens.map((item) => ({
      item_id: item.item_id,
      status: item.status,
      observacao: item.observacao.trim(),
      resposta_id: item.resposta_id,
      retained_photo_ids: item.photos
        .filter((photo) => photo.kind === "existing" && photo.id)
        .map((photo) => Number(photo.id)),
      deleted_photo_ids: item.removedPhotoIds
    }))
  };
}

export function appendChecklistFiles(formData: FormData, draft: ChecklistDraft, fieldBase = "fotos_checklist_entrada") {
  draft.itens.forEach((item) => {
    item.photos.forEach((photo) => {
      if (photo.kind !== "new" || !photo.file) {
        return;
      }

      formData.append(`${fieldBase}[${item.item_id}][]`, photo.file);
    });
  });
}

function summaryClassName(variant: ChecklistDraft["resumoVariant"]): string {
  return `is-${variant}`;
}

function refreshDraftSummary(draft: ChecklistDraft): ChecklistDraft {
  const summary = summarizeDraft(draft);
  return {
    ...draft,
    ...summary
  };
}

export function ChecklistEntradaField({
  value,
  onChange,
  disabled = false,
  buttonLabel = "Checklist",
  disabledMessage = "Selecione um equipamento para carregar o checklist."
}: ChecklistEntradaFieldProps) {
  const [mounted, setMounted] = useState(false);
  const [modalOpen, setModalOpen] = useState(false);
  const [draft, setDraft] = useState<ChecklistDraft>(() => cloneChecklistDraft(value));
  const [activeItemId, setActiveItemId] = useState<number | null>(null);
  const [cropQueue, setCropQueue] = useState<CropQueueItem[]>([]);
  const [cropTarget, setCropTarget] = useState<CropQueueItem | null>(null);
  const [cropSource, setCropSource] = useState("");
  const [cropOpen, setCropOpen] = useState(false);
  const [cropBusy, setCropBusy] = useState(false);
  const [preview, setPreview] = useState<PreviewState | null>(null);
  const [modalError, setModalError] = useState("");
  const galleryInputRef = useRef<HTMLInputElement | null>(null);
  const cameraInputRef = useRef<HTMLInputElement | null>(null);
  const cropImageRef = useRef<HTMLImageElement | null>(null);
  const cropperRef = useRef<Cropper | null>(null);
  const cropObjectUrlRef = useRef<string | null>(null);

  useEffect(() => {
    setMounted(true);
    return () => {
      if (cropObjectUrlRef.current) {
        URL.revokeObjectURL(cropObjectUrlRef.current);
        cropObjectUrlRef.current = null;
      }
    };
  }, []);

  useEffect(() => {
    if (!modalOpen) {
      setDraft(cloneChecklistDraft(value));
      setModalError("");
    }
  }, [modalOpen, value]);

  useEffect(() => {
    if (cropTarget || cropOpen || cropQueue.length === 0) {
      return;
    }

    const [nextTarget, ...rest] = cropQueue;
    const objectUrl = URL.createObjectURL(nextTarget.file);
    cropObjectUrlRef.current = objectUrl;
    setCropQueue(rest);
    setCropTarget(nextTarget);
    setCropSource(objectUrl);
    setCropOpen(true);
  }, [cropOpen, cropQueue, cropTarget]);

  useEffect(() => {
    if (!cropOpen || !cropSource || !cropImageRef.current) {
      return;
    }

    let cancelled = false;
    let instance: Cropper | null = null;

    (async () => {
      try {
        const cropperModule = await import("cropperjs");
        if (cancelled || !cropImageRef.current) {
          return;
        }

        instance = new cropperModule.default(cropImageRef.current, {
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
        cropperRef.current = instance;
      } catch (error) {
        console.error("[ChecklistEntrada] falha ao abrir cropper", error);
        setModalError("Nao foi possivel abrir o editor de corte da foto.");
        closeCropper(true);
      }
    })();

    return () => {
      cancelled = true;
      if (instance) {
        instance.destroy();
      }
      cropperRef.current = null;
    };
  }, [cropOpen, cropSource]);

  const hasConfig = draft.possuiModelo;
  const waitingEquipmentSelection = value.resumoVariant === "idle";
  const currentSummary = useMemo(() => {
    if (disabled) {
      return {
        label: "Selecione um equipamento",
        variant: "idle" as const
      };
    }

    return {
      label: value.resumoLabel,
      variant: value.resumoVariant
    };
  }, [disabled, value]);

  function openModal() {
    setDraft(cloneChecklistDraft(value));
    setModalError(disabled || waitingEquipmentSelection ? disabledMessage : "");
    setModalOpen(true);
  }

  function closeModal() {
    setModalOpen(false);
    setModalError("");
    setPreview(null);
    setCropQueue([]);
    closeCropper(true);
  }

  function updateDraft(mutator: (base: ChecklistDraft) => ChecklistDraft) {
    setDraft((prev) => refreshDraftSummary(mutator(cloneChecklistDraft(prev))));
  }

  function updateItem(
    itemId: number,
    updater: (item: ChecklistDraftItem) => ChecklistDraftItem
  ) {
    updateDraft((base) => ({
      ...base,
      itens: base.itens.map((item) => (item.item_id === itemId ? updater({ ...item, photos: item.photos.map((photo) => ({ ...photo })), removedPhotoIds: [...item.removedPhotoIds] }) : item))
    }));
  }

  function setItemStatus(itemId: number, status: ChecklistDraftItem["status"]) {
    updateItem(itemId, (item) => ({
      ...item,
      status,
      observacao: status === "ok" ? "" : item.observacao
    }));
  }

  function setItemObservation(itemId: number, observation: string) {
    updateItem(itemId, (item) => ({
      ...item,
      observacao: observation
    }));
  }

  function triggerPhotoPicker(itemId: number, source: "camera" | "gallery") {
    setActiveItemId(itemId);
    if (source === "camera") {
      cameraInputRef.current?.click();
      return;
    }

    galleryInputRef.current?.click();
  }

  function handleSelectedFiles(fileList: FileList | null) {
    if (!fileList || !activeItemId) {
      return;
    }

    const files = Array.from(fileList).filter((file) => file.type.startsWith("image/"));
    if (files.length === 0) {
      return;
    }

    setCropQueue((prev) => [...prev, ...files.map((file) => ({ itemId: activeItemId, file }))]);
  }

  function closeCropper(discardCurrent = false) {
    if (cropperRef.current) {
      cropperRef.current.destroy();
      cropperRef.current = null;
    }
    if (cropObjectUrlRef.current) {
      URL.revokeObjectURL(cropObjectUrlRef.current);
      cropObjectUrlRef.current = null;
    }
    setCropBusy(false);
    setCropOpen(false);
    setCropSource("");
    setCropTarget(null);
  }

  async function confirmCrop() {
    if (!cropTarget) {
      closeCropper(true);
      return;
    }

    setCropBusy(true);
    try {
      const cropper = cropperRef.current;
      if (!cropper) {
        throw new Error("Cropper indisponivel.");
      }

      const canvas = cropper.getCroppedCanvas({
        width: 1200,
        height: 1200,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: "high"
      });
      const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.92));
      if (!blob) {
        throw new Error("Falha ao gerar a foto recortada.");
      }

      const nextFile = new File([blob], cropTarget.file.name.replace(/\.[^.]+$/, "") + ".jpg", {
        type: "image/jpeg"
      });
      const objectUrl = URL.createObjectURL(nextFile);
      updateItem(cropTarget.itemId, (item) => ({
        ...item,
        photos: [...item.photos, { kind: "new", url: objectUrl, name: nextFile.name, file: nextFile }]
      }));
      closeCropper(true);
    } catch (error) {
      console.error("[ChecklistEntrada] falha ao confirmar corte", error);
      setModalError(error instanceof Error ? error.message : "Falha ao concluir o corte da foto.");
      closeCropper(true);
    }
  }

  function removePhoto(itemId: number, photoIndex: number) {
    updateItem(itemId, (item) => {
      const nextPhotos = [...item.photos];
      const [removed] = nextPhotos.splice(photoIndex, 1);
      const removedPhotoIds = [...item.removedPhotoIds];

      if (removed?.kind === "existing" && removed.id) {
        removedPhotoIds.push(Number(removed.id));
      }

      return {
        ...item,
        photos: nextPhotos,
        removedPhotoIds
      };
    });
  }

  function openPreview(item: ChecklistDraftItem, index: number) {
    setPreview({
      title: item.descricao,
      photos: item.photos.map((photo) => ({ ...photo })),
      index
    });
  }

  function saveChecklist() {
    const next = refreshDraftSummary(cloneChecklistDraft(draft));
    onChange(next);
    setModalOpen(false);
  }

  const modalNode = modalOpen ? (
    <div className="modal-overlay" onClick={closeModal}>
      <section className="inline-modal checklist-inline-modal" onClick={(event) => event.stopPropagation()}>
        <header className="inline-modal-header">
          <div>
            <h3>{buttonLabel}</h3>
            <p className="inline-modal-subtitle">
              {draft.modeloNome ? `${draft.tipoNome} • ${draft.modeloNome}` : draft.tipoNome}
            </p>
          </div>
          <button type="button" className="inline-modal-close" onClick={closeModal}>
            x
          </button>
        </header>

        {!hasConfig ? (
          <div className="checklist-empty-state">
            <strong>{disabled || waitingEquipmentSelection ? "Selecione um equipamento" : "Checklist nao configurado"}</strong>
            <small>
              {disabled || waitingEquipmentSelection
                ? disabledMessage
                : "Nao existe checklist de entrada cadastrado para este tipo de equipamento."}
            </small>
          </div>
        ) : (
          <div className="checklist-item-list">
            {draft.itens.map((item) => (
              <article key={item.item_id} className={`checklist-item-card is-${item.status}`}>
                <div className="checklist-item-head">
                  <strong>{item.descricao}</strong>
                  <span className={`checklist-item-status is-${item.status}`}>
                    {item.status === "ok" ? "OK" : item.status === "discrepancia" ? "Com discrepancia" : "Pendente"}
                  </span>
                </div>
                <div className="checklist-item-actions">
                  <button
                    type="button"
                    className={`checklist-chip ${item.status === "ok" ? "is-active" : ""}`}
                    onClick={() => setItemStatus(item.item_id, "ok")}
                  >
                    OK
                  </button>
                  <button
                    type="button"
                    className={`checklist-chip is-warning ${item.status === "discrepancia" ? "is-active" : ""}`}
                    onClick={() => setItemStatus(item.item_id, "discrepancia")}
                  >
                    Com discrepancia
                  </button>
                  <button
                    type="button"
                    className={`checklist-chip is-muted ${item.status === "nao_verificado" ? "is-active" : ""}`}
                    onClick={() => setItemStatus(item.item_id, "nao_verificado")}
                  >
                    Pendente
                  </button>
                </div>

                {item.status === "discrepancia" ? (
                  <div className="checklist-discrepancy-body">
                    <textarea
                      rows={2}
                      placeholder="Observacao da discrepancia (opcional)"
                      value={item.observacao}
                      onChange={(event) => setItemObservation(item.item_id, event.target.value)}
                    />
                    <div className="photo-entry-actions">
                      <button
                        type="button"
                        className="photo-entry-btn is-primary"
                        onClick={() => triggerPhotoPicker(item.item_id, "camera")}
                      >
                        Tirar foto
                      </button>
                      <button
                        type="button"
                        className="photo-entry-btn"
                        onClick={() => triggerPhotoPicker(item.item_id, "gallery")}
                      >
                        Galeria
                      </button>
                    </div>
                    {item.photos.length > 0 ? (
                      <div className="collection-photo-row">
                        {item.photos.map((photo, index) => (
                          <div key={`${item.item_id}_${photo.id || photo.name}_${index}`} className="collection-photo-thumb">
                            <button
                              type="button"
                              className="collection-photo-preview"
                              onClick={() => openPreview(item, index)}
                            >
                              {/* eslint-disable-next-line @next/next/no-img-element */}
                              <img src={photo.url} alt={photo.name} />
                            </button>
                            <button
                              type="button"
                              className="collection-photo-remove"
                              onClick={(event) => {
                                event.stopPropagation();
                                removePhoto(item.item_id, index);
                              }}
                            >
                              x
                            </button>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="helper-line">Nenhuma foto vinculada a esta discrepancia.</p>
                    )}
                  </div>
                ) : null}
              </article>
            ))}
          </div>
        )}

        {modalError ? <p className="error-line">{modalError}</p> : null}

        <input
          ref={galleryInputRef}
          type="file"
          accept="image/*"
          hidden
          multiple
          onChange={(event) => {
            handleSelectedFiles(event.target.files);
            event.currentTarget.value = "";
          }}
        />
        <input
          ref={cameraInputRef}
          type="file"
          accept="image/*"
          capture="environment"
          hidden
          multiple
          onChange={(event) => {
            handleSelectedFiles(event.target.files);
            event.currentTarget.value = "";
          }}
        />

        <div className="floating-modal-footer">
          <button type="button" className="floating-footer-btn is-muted" onClick={closeModal}>
            Fechar
          </button>
          <button type="button" className="floating-footer-btn is-primary" onClick={saveChecklist}>
            Salvar checklist
          </button>
        </div>

        {cropOpen ? (
          <div className="nested-modal-overlay" onClick={() => closeCropper(true)}>
            <section className="nested-inline-modal nested-inline-modal-wide" onClick={(event) => event.stopPropagation()}>
              <header className="inline-modal-header">
                <h3>Ajustar foto da discrepancia</h3>
                <button type="button" className="inline-modal-close" onClick={() => closeCropper(true)}>
                  x
                </button>
              </header>
              <div className="cropper-modal-body">
                <div className="cropper-canvas-wrap">
                  <img ref={cropImageRef} src={cropSource} alt="Foto em corte do checklist" />
                </div>
              </div>
              <div className="floating-modal-footer">
                <button type="button" className="floating-footer-btn is-muted" onClick={() => closeCropper(true)} disabled={cropBusy}>
                  Cancelar foto
                </button>
                <button type="button" className="floating-footer-btn is-primary" onClick={() => void confirmCrop()} disabled={cropBusy}>
                  {cropBusy ? "Processando..." : "Confirmar corte"}
                </button>
              </div>
            </section>
          </div>
        ) : null}

        {preview ? (
          <div className="nested-modal-overlay" onClick={() => setPreview(null)}>
            <section className="nested-inline-modal nested-inline-modal-wide" onClick={(event) => event.stopPropagation()}>
              <header className="inline-modal-header">
                <h3>{preview.title}</h3>
                <button type="button" className="inline-modal-close" onClick={() => setPreview(null)}>
                  x
                </button>
              </header>
              <div className="cropper-modal-body">
                <div className="cropper-canvas-wrap image-preview-wrap">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={preview.photos[preview.index]?.url || ""} alt={preview.photos[preview.index]?.name || preview.title} />
                </div>
                {preview.photos.length > 1 ? (
                  <>
                    <div className="image-carousel-controls">
                      <button
                        type="button"
                        className="image-carousel-nav"
                        onClick={() =>
                          setPreview((current) =>
                            current
                              ? {
                                  ...current,
                                  index: current.index <= 0 ? current.photos.length - 1 : current.index - 1
                                }
                              : current
                          )
                        }
                      >
                        ‹ Anterior
                      </button>
                      <span className="image-carousel-status">
                        Foto {preview.index + 1} de {preview.photos.length}
                      </span>
                      <button
                        type="button"
                        className="image-carousel-nav"
                        onClick={() =>
                          setPreview((current) =>
                            current
                              ? {
                                  ...current,
                                  index: current.index >= current.photos.length - 1 ? 0 : current.index + 1
                                }
                              : current
                          )
                        }
                      >
                        Proxima ›
                      </button>
                    </div>
                    <div className="collection-photo-row">
                      {preview.photos.map((photo, index) => (
                        <button
                          key={`${photo.id || photo.name}_${index}`}
                          type="button"
                          className={`collection-photo-thumb collection-photo-thumb-btn ${preview.index === index ? "is-active" : ""}`}
                          onClick={() => setPreview((current) => (current ? { ...current, index } : current))}
                        >
                          {/* eslint-disable-next-line @next/next/no-img-element */}
                          <img src={photo.url} alt={photo.name} />
                        </button>
                      ))}
                    </div>
                  </>
                ) : null}
              </div>
              <div className="floating-modal-footer">
                <button type="button" className="floating-footer-btn is-primary" onClick={() => setPreview(null)}>
                  Fechar visualizacao
                </button>
              </div>
            </section>
          </div>
        ) : null}
      </section>
    </div>
  ) : null;

  return (
    <>
      <section className="collection-block checklist-trigger-block">
        <div className="collection-block-header">
          <div>
            <strong>{buttonLabel}</strong>
            <p className={`checklist-summary-pill ${summaryClassName(currentSummary.variant)}`}>{currentSummary.label}</p>
          </div>
          <button type="button" className="secondary-inline-button" onClick={openModal}>
            {buttonLabel}
          </button>
        </div>
      </section>
      {mounted && modalNode ? createPortal(modalNode, document.body) : null}
    </>
  );
}
