"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { APP_VERSION } from "@/lib/app-version";

const items = [
  { href: "/conversas" as const, label: "Conversas" },
  { href: "/os" as const, label: "OS" },
  { href: "/notificacoes" as const, label: "Avisos" }
];

export function MobileNav() {
  const pathname = usePathname();

  return (
    <nav className="mobile-nav" aria-label="Navegacao principal mobile">
      <div className="mobile-nav-links">
        {items.map((item) => {
          const active = pathname === item.href || pathname.startsWith(item.href + "/");
          return (
            <Link key={item.href} href={item.href} className={active ? "active" : ""}>
              {item.label}
            </Link>
          );
        })}
      </div>
      <p className="mobile-nav-version">App {APP_VERSION.version} | ERP minimo {APP_VERSION.erpMinVersion}</p>
    </nav>
  );
}
