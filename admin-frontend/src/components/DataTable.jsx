import React, { useState, useMemo } from "react";
import { useTheme } from "../theme";
import { TYPE } from "../theme/typography";
import Button from "./Button";
import Loader from "./Loader";
import EmptyState from "./EmptyState";

export default function DataTable({ columns, data, onEdit, onDelete, onView, loading, emptyIcon, emptyTitle, emptyMsg }) {
    const { tokens: t } = useTheme();
    const [sortCol, setSortCol] = useState(null);
    const [sortDir, setSortDir] = useState("asc");
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [selected, setSelected] = useState([]);
    const pageSize = 6;

    const filtered = useMemo(() => {
        let d = [...data];
        if (search) {
            d = d.filter(row => Object.values(row).some(v => String(v).toLowerCase().includes(search.toLowerCase())));
        }
        if (sortCol) {
            d.sort((a, b) => {
                const va = a[sortCol], vb = b[sortCol];
                return sortDir === "asc" ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va));
            });
        }
        return d;
    }, [data, search, sortCol, sortDir]);

    const paged = filtered.slice((page - 1) * pageSize, page * pageSize);
    const pages = Math.ceil(filtered.length / pageSize);
    const allSelected = paged.length > 0 && paged.every(r => selected.includes(r.id));

    const toggleSort = col => {
        if (sortCol === col) setSortDir(d => d === "asc" ? "desc" : "asc");
        else { setSortCol(col); setSortDir("asc"); }
    };

    const exportCSV = () => {
        const headers = columns.map(c => c.label).join(",");
        const rows = filtered.map(row => columns.map(c => `"${row[c.key] ?? ""}"`).join(",")).join("\n");
        const blob = new Blob([headers + "\n" + rows], { type: "text/csv" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a"); a.href = url; a.download = "export.csv"; a.click();
    };

    if (loading) return <Loader />;
    if (!data.length) return <EmptyState icon={emptyIcon} title={emptyTitle} message={emptyMsg} />;

    return (
        <div style={{ fontFamily: TYPE.fontBody }}>
            {/* Toolbar */}
            <div style={{ display: "flex", gap: 10, marginBottom: 16, alignItems: "center", flexWrap: "wrap" }}>
                <div style={{ flex: 1, minWidth: 220, position: "relative" }}>
                    <span style={{ position: "absolute", left: 11, top: "50%", transform: "translateY(-50%)", color: t.textMuted, fontSize: 13, pointerEvents: "none" }}>🔍</span>
                    <input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }}
                        placeholder="Search records…"
                        style={{
                            width: "100%", height: 34, padding: "0 12px 0 34px",
                            background: t.bgElevated, border: `1px solid ${t.border}`,
                            borderRadius: 6, color: t.text, fontSize: TYPE.sm,
                            fontFamily: TYPE.fontBody, outline: "none", boxSizing: "border-box",
                        }} />
                </div>
                {selected.length > 0 && (
                    <span style={{ fontSize: TYPE.sm, color: t.accent, fontWeight: TYPE.semibold, padding: "0 4px" }}>{selected.length} selected</span>
                )}
                <Button variant="ghost" size="sm" icon="↓" onClick={exportCSV}>Export CSV</Button>
            </div>

            {/* Table */}
            <div style={{ overflowX: "auto", borderRadius: 8, border: `1px solid ${t.border}` }}>
                <table style={{ width: "100%", borderCollapse: "collapse", fontSize: TYPE.base }}>
                    <thead>
                        <tr style={{ background: t.bgElevated, borderBottom: `1px solid ${t.border}` }}>
                            <th style={{ padding: "10px 14px", textAlign: "left", width: 44 }}>
                                <input type="checkbox" checked={allSelected}
                                    onChange={() => setSelected(allSelected ? [] : paged.map(r => r.id))}
                                    style={{ accentColor: t.accent, width: 14, height: 14 }} />
                            </th>
                            {columns.map(c => (
                                <th key={c.key} onClick={() => c.sortable !== false && toggleSort(c.key)}
                                    style={{
                                        padding: "10px 14px", textAlign: "left", color: t.textSecondary,
                                        fontSize: TYPE.xs, fontWeight: TYPE.bold, letterSpacing: "0.07em",
                                        textTransform: "uppercase", cursor: c.sortable !== false ? "pointer" : "default",
                                        whiteSpace: "nowrap", userSelect: "none", fontFamily: TYPE.fontBody,
                                    }}>
                                    {c.label}
                                    {sortCol === c.key && (
                                        <span style={{ marginLeft: 4, color: t.accent, fontSize: 10 }}>{sortDir === "asc" ? "▲" : "▼"}</span>
                                    )}
                                </th>
                            ))}
                            <th style={{
                                padding: "10px 14px", textAlign: "right", color: t.textSecondary,
                                fontSize: TYPE.xs, fontWeight: TYPE.bold, letterSpacing: "0.07em", textTransform: "uppercase"
                            }}>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {paged.map((row, i) => (
                            <tr key={row.id}
                                style={{
                                    borderBottom: i < paged.length - 1 ? `1px solid ${t.border}` : "none",
                                    background: selected.includes(row.id) ? `${t.accent}08` : "transparent",
                                    transition: "background 0.1s",
                                }}
                                onMouseEnter={e => { if (!selected.includes(row.id)) e.currentTarget.style.background = t.bgHover; }}
                                onMouseLeave={e => { e.currentTarget.style.background = selected.includes(row.id) ? `${t.accent}08` : "transparent"; }}
                            >
                                <td style={{ padding: "12px 14px" }}>
                                    <input type="checkbox" checked={selected.includes(row.id)}
                                        onChange={() => setSelected(s => s.includes(row.id) ? s.filter(x => x !== row.id) : [...s, row.id])}
                                        style={{ accentColor: t.accent, width: 14, height: 14 }} />
                                </td>
                                {columns.map(c => (
                                    <td key={c.key} style={{ padding: "12px 14px", color: t.text, whiteSpace: "nowrap", lineHeight: 1.5 }}>
                                        {c.render ? c.render(row[c.key], row) : row[c.key]}
                                    </td>
                                ))}
                                <td style={{ padding: "12px 14px", textAlign: "right" }}>
                                    <div style={{ display: "flex", gap: 6, justifyContent: "flex-end" }}>
                                        {onView && <Button variant="ghost" size="sm" onClick={() => onView(row)}>View</Button>}
                                        {onEdit && <Button variant="ghost" size="sm" onClick={() => onEdit(row)}>Edit</Button>}
                                        {onDelete && <Button variant="danger" size="sm" onClick={() => onDelete(row)}>Delete</Button>}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {pages > 1 && (
                <div style={{
                    display: "flex", justifyContent: "space-between", alignItems: "center",
                    marginTop: 14, fontSize: TYPE.sm, color: t.textMuted, fontFamily: TYPE.fontBody
                }}>
                    <span>Showing {(page - 1) * pageSize + 1}–{Math.min(page * pageSize, filtered.length)} of <strong style={{ color: t.textSecondary }}>{filtered.length}</strong> records</span>
                    <div style={{ display: "flex", gap: 4 }}>
                        {Array.from({ length: pages }, (_, i) => (
                            <button key={i} onClick={() => setPage(i + 1)}
                                style={{
                                    width: 30, height: 30, borderRadius: 6,
                                    border: `1px solid ${i + 1 === page ? t.accent : t.border}`,
                                    background: i + 1 === page ? t.accent : "transparent",
                                    color: i + 1 === page ? "#000" : t.textSecondary,
                                    cursor: "pointer", fontSize: TYPE.sm, fontWeight: TYPE.semibold,
                                    fontFamily: TYPE.fontBody,
                                }}>{i + 1}</button>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};
