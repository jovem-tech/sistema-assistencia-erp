"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { MobileNav } from "@/components/MobileNav";
import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";

type Order = {
  id: number;
  numero_os: string;
  status: string;
  prioridade: string;
  cliente_nome: string | null;
  cliente_telefone: string | null;
  equip_tipo?: string | null;
  equip_marca?: string | null;
  equip_modelo?: string | null;
  equip_foto_url?: string | null;
  equip_fotos?: Array<{
    url: string | null;
    is_principal: number;
  }>;
  created_at: string | null;
};

type OrderResponse = {
  items: Order[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    total_pages: number;
  };
};

export default function OrdersPage() {
  const router = useRouter();
  const [items, setItems] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [searchQuery, setSearchQuery] = useState("");
  const [debouncedSearchQuery, setDebouncedSearchQuery] = useState("");
  const [photoGalleryOpen, setPhotoGalleryOpen] = useState(false);
  const [photoGalleryTitle, setPhotoGalleryTitle] = useState("");
  const [photoGalleryPhotos, setPhotoGalleryPhotos] = useState<string[]>([]);
  const [photoGalleryActiveIndex, setPhotoGalleryActiveIndex] = useState(0);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setDebouncedSearchQuery(searchQuery.trim());
    }, 320);

    return () => {
      window.clearTimeout(timer);
    };
  }, [searchQuery]);

  async function loadData(query = debouncedSearchQuery) {
    setLoading(true);
    setError("");
    try {
      const params = new URLSearchParams({
        per_page: "50",
        page: "1"
      });

      if (query) {
        params.set("q", query);
      }

      const data = await apiRequest<OrderResponse>(`/orders?${params.toString()}`);
      setItems(data.items || []);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Falha ao carregar OS.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (!getSession()?.accessToken) {
      router.replace("/login");
      return;
    }
    void loadData(debouncedSearchQuery);
  }, [debouncedSearchQuery, router]);

  function openPhotoGallery(item: Order) {
    const photos = (item.equip_fotos || [])
      .map((photo) => photo.url || "")
      .filter(Boolean);
    if (photos.length === 0) {
      return;
    }

    setPhotoGalleryTitle(
      [item.equip_marca, item.equip_modelo].filter(Boolean).join(" - ") || item.equip_tipo || "Fotos do equipamento"
    );
    setPhotoGalleryPhotos(photos);
    setPhotoGalleryActiveIndex(0);
    setPhotoGalleryOpen(true);
  }

  function closePhotoGallery() {
    setPhotoGalleryOpen(false);
    setPhotoGalleryTitle("");
    setPhotoGalleryPhotos([]);
    setPhotoGalleryActiveIndex(0);
  }

  return (
    <>
      <header className="mobile-header">
        <h1 className="mobile-title">Ordens de Servico</h1>
        <p className="mobile-subtitle">Listagem e operacao mobile de OS</p>
      </header>

      <section className="mobile-card">
        <div className="mobile-card-actions">
          <button onClick={() => void loadData(searchQuery.trim())} type="button">
            Atualizar OS
          </button>
          <Link href="/os/nova" className="primary-link-button">
            Nova OS
          </Link>
        </div>

        <div className="mobile-search-block">
          <div className="form-inline-row form-inline-row-smart">
            <input
              type="search"
              placeholder="Buscar OS, cliente, telefone, equipamento, IMEI, serie, status..."
              value={searchQuery}
              onChange={(event) => setSearchQuery(event.target.value)}
              autoComplete="off"
              spellCheck={false}
            />
            {searchQuery ? (
              <button type="button" className="secondary-inline-button" onClick={() => setSearchQuery("")}>
                Limpar
              </button>
            ) : null}
          </div>
          <p className="helper-line">
            Busca inteligente por numero da OS, cliente, telefone, e-mail, marca, modelo, IMEI, serie, status,
            prioridade e observacoes.
          </p>
        </div>

        {loading ? <p className="helper-line">Carregando OS...</p> : null}
        {error ? <p className="error-line">{error}</p> : null}
        {!loading && !error && debouncedSearchQuery ? (
          <p className="helper-line">
            {items.length} resultado(s) para &quot;{debouncedSearchQuery}&quot;.
          </p>
        ) : null}
        {!loading && !error && items.length === 0 ? (
          <p className="helper-line">
            {debouncedSearchQuery
              ? "Nenhuma ordem de servico encontrada com esse termo."
              : "Nenhuma ordem de servico encontrada no momento."}
          </p>
        ) : null}

        <div className="list-stack" style={{ marginTop: 10 }}>
          {items.map((item) => (
            <article
              key={item.id}
              className="list-item list-item-clickable"
              role="button"
              tabIndex={0}
              onClick={() => router.push(`/os/${item.id}`)}
              onKeyDown={(event) => {
                if (event.key === "Enter" || event.key === " ") {
                  event.preventDefault();
                  router.push(`/os/${item.id}`);
                }
              }}
            >
              <h3 className="list-item-title">{item.numero_os || `OS #${item.id}`}</h3>
              <p className="list-item-subtitle">
                {item.cliente_nome || "Sem cliente"} {item.created_at ? `| ${item.created_at}` : ""}
              </p>
              <div className="order-equipment-row">
                <button
                  type="button"
                  className="order-equipment-thumb order-equipment-thumb-btn"
                  onClick={(event) => {
                    event.stopPropagation();
                    openPhotoGallery(item);
                  }}
                  aria-label="Abrir fotos do equipamento"
                >
                  {item.equip_foto_url ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={item.equip_foto_url} alt="Foto do equipamento" />
                  ) : (
                    <span>OS</span>
                  )}
                </button>
                <div className="order-equipment-copy">
                  <strong>
                    {[item.equip_marca, item.equip_modelo].filter(Boolean).join(" - ") || item.equip_tipo || "Equipamento sem descricao"}
                  </strong>
                  <small>{item.equip_tipo || "Tipo nao informado"}</small>
                </div>
              </div>
              <div className="chip-row">
                <span className="chip status">Status: {item.status}</span>
                <span className="chip priority">Prioridade: {item.prioridade}</span>
              </div>
            </article>
          ))}
        </div>
      </section>

      {photoGalleryOpen ? (
        <div className="modal-overlay" onClick={closePhotoGallery}>
          <section className="inline-modal" onClick={(event) => event.stopPropagation()}>
            <header className="inline-modal-header">
              <h3>{photoGalleryTitle || "Fotos do equipamento"}</h3>
              <button type="button" className="inline-modal-close" onClick={closePhotoGallery}>
                x
              </button>
            </header>
            <div className="cropper-modal-body">
              <div className="cropper-canvas-wrap image-preview-wrap">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={photoGalleryPhotos[photoGalleryActiveIndex] || ""}
                  alt={photoGalleryTitle || "Foto do equipamento"}
                />
              </div>
            </div>
            {photoGalleryPhotos.length > 1 ? (
              <div className="collection-photo-row">
                {photoGalleryPhotos.map((photo, index) => (
                  <button
                    key={`${photo}_${index}`}
                    type="button"
                    className={`collection-photo-thumb collection-photo-thumb-btn ${photoGalleryActiveIndex === index ? "is-active" : ""}`}
                    onClick={() => setPhotoGalleryActiveIndex(index)}
                  >
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img src={photo} alt={`Foto ${index + 1} do equipamento`} />
                  </button>
                ))}
              </div>
            ) : null}
          </section>
        </div>
      ) : null}

      <MobileNav />
    </>
  );
}
