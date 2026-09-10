import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-direct-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class DirectLogComponent implements OnInit {
  results: any[] = [];
  isView = false;
  selectedResult: any = {};
  loading = false;
  searchText = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getLog();
  }

  getLog() {
    this.loading = true;
    this.service
      .get(
        'security/inward.php?type=getDirectChallansLog&search_text=' +
          encodeURIComponent((this.searchText || '').trim())
      )
      .subscribe({
        next: (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.loading = false;
        }
      });
  }

  view(row: any) {
    this.selectedResult = row || {};
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedResult = {};
  }

  getMaterialNames(row: any): string {
    if (!row) {
      return '-';
    }
    if (row.material_name && String(row.material_name).trim()) {
      return String(row.material_name).trim();
    }
    let materials = row.materials;
    if (typeof materials === 'string' && materials) {
      try {
        materials = JSON.parse(materials);
      } catch {
        materials = [];
      }
    }
    if (!Array.isArray(materials) || materials.length === 0) {
      return '-';
    }
    const names = materials
      .map((m) => (m && (m.material_name || m.chemical_name || m.name || m.material_code)) || '')
      .map((n) => String(n).trim())
      .filter((name) => !!name);
    return names.length ? Array.from(new Set(names)).join(', ') : '-';
  }
}
