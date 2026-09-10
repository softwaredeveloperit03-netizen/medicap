import { Component, OnInit } from '@angular/core';
import { finalize } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
})
export class LogComponent implements OnInit {
  loading = false;
  /** Sent to API as `search_text` (challan / PO / vendor / material / invoice). */
  serverSearch = '';
  /** Quick filter on already-loaded rows (client-side). */
  clientFilter = '';
  groupedResults: any[] = [];
  listRows: any[] = [];
  isView = false;
  selectedChallan: any = null;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getChallansLog();
  }

  getChallansLog(): void {
    this.loading = true;
    this.service
      .get(
        'security/inward.php?type=getChallansLogNootan&search_text=' +
          encodeURIComponent((this.serverSearch || '').trim())
      )
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: (response) => {
          this.groupedResults = Array.isArray(response) ? response : [];
          this.rebuildListRows();
          if (this.isView && this.selectedChallan) {
            const id = this.selectedChallan.challan_id;
            const updated = this.groupedResults.find((g) => g.challan_id === id);
            if (updated) {
              this.selectedChallan = updated;
            } else {
              this.closeView();
            }
          }
        },
        error: () => {
          this.groupedResults = [];
          this.listRows = [];
        },
      });
  }

  download(): void {
    this.service.open(
      'security/inward.php?type=downloadInwordLog&search_text=' +
        encodeURIComponent((this.serverSearch || '').trim())
    );
  }

  get filteredGroups(): any[] {
    const list = Array.isArray(this.groupedResults) ? this.groupedResults : [];
    const q = (this.clientFilter || '').trim().toLowerCase();
    if (!q) {
      return list;
    }
    return list.filter((g) => {
      try {
        return JSON.stringify(g).toLowerCase().includes(q);
      } catch {
        return false;
      }
    });
  }

  onClientFilterChange(): void {
    this.rebuildListRows();
  }

  /** One grid row per material line (same inward can appear more than once). */
  rebuildListRows(): void {
    const rows: any[] = [];
    for (const g of this.filteredGroups) {
      const lines = this.usableMaterials(g);
      if (!lines.length) {
        rows.push(g);
        continue;
      }
      for (const m of lines) {
        rows.push({
          challan_id: g.challan_id,
          displayMaterialName: this.lineMaterialName(m),
          material_type: g.material_type,
          challan_no: g.challan_no,
          ch_no: g.ch_no,
          po_no: g.po_no,
          po_date: g.po_date,
          vendor_name: g.vendor_name,
          vendor_no: g.vendor_no,
          inward_date: g.inward_date,
          transport: g.transport,
          status: g.status,
        });
      }
    }
    this.listRows = rows;
  }

  view(row: any, ev?: Event): void {
    if (ev) {
      ev.preventDefault();
      ev.stopPropagation();
    }
    const id = row && (row.challan_id || row.id);
    const full = this.groupedResults.find((g) => String(g.challan_id) === String(id)) || row;
    this.selectedChallan = full || null;
    this.isView = !!this.selectedChallan;
  }

  closeView(): void {
    this.isView = false;
    this.selectedChallan = null;
  }

  printChallan(): void {
    this.downloadPdf();
  }

  downloadPdf(): void {
    const id = this.selectedChallan?.challan_id || this.selectedChallan?.id;
    if (!id) {
      return;
    }
    this.service.open('security/inward.php?type=downloadInwardChallanPdf&id=' + encodeURIComponent(id));
  }

  materialsCount(g: any): number {
    return this.usableMaterials(g).length;
  }

  usableMaterials(row: any): any[] {
    if (!row) {
      return [];
    }
    let materials = row.materials;
    if (typeof materials === 'string' && materials) {
      try {
        materials = JSON.parse(materials);
      } catch {
        materials = [];
      }
    }
    if (!Array.isArray(materials)) {
      return [];
    }
    return materials.filter((m) => !!this.lineMaterialName(m));
  }

  lineMaterialName(m: any): string {
    if (!m) {
      return '';
    }
    const n = String(m.material_name || m.chemical_name || m.name || '').trim();
    const code = String(m.material_code || '').trim();
    if (!n || n === '0' || n === '-' || /^na$/i.test(n) || /^-?\d+(\.\d+)?$/.test(n)) {
      return '';
    }
    if (!/[A-Za-z]/.test(n)) {
      return '';
    }
    if (code && n.toLowerCase() === code.toLowerCase()) {
      return '';
    }
    return n;
  }

  singleMaterialName(row: any): string {
    if (row && row.displayMaterialName) {
      return row.displayMaterialName;
    }
    const names = this.getMaterialNames(row);
    return names && names !== '—' ? names : '—';
  }

  getMaterialNames(row: any): string {
    const names = this.usableMaterials(row).map((m) => this.lineMaterialName(m)).filter((n) => !!n);
    if (names.length) {
      return Array.from(new Set(names)).join(', ');
    }
    return '—';
  }
}
