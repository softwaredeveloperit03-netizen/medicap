import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  stocks: any[] = [];
  allStocksData: any[] = [];
  material_type = 'Raw Material';
  loading = false;

  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getAllStock();
  }

  getAllStock() {
    this.loading = true;
    this.service.get('store/packing.php?type=getStock').subscribe(
      (response: any) => {
        this.allStocksData = Array.isArray(response) ? response : [];
        this.applyFilters();
        this.loading = false;
      },
      () => {
        this.allStocksData = [];
        this.stocks = [];
        this.loading = false;
      }
    );
  }

  applyFilters() {
    const type = String(this.material_type || '').trim();
    const rows = Array.isArray(this.allStocksData) ? this.allStocksData : [];
    this.stocks = type ? rows.filter((d) => String(d?.material_type || '').trim() === type) : rows;
  }

  onMaterialTypeChange() {
    this.applyFilters();
  }

  displayGrade(stock: any): string {
    const name = String(stock?.gradeName || '').trim();
    if (name && name !== 'null' && name !== 'undefined') {
      return name;
    }
    const grade = String(stock?.grade || '').trim();
    if (!grade || grade === 'null' || grade === 'undefined') {
      return '—';
    }
    // Hide raw numeric id lists in UI if name resolution failed
    if (/^\d+(,\d+)*$/.test(grade)) {
      return '—';
    }
    return grade;
  }

  statusKey(status: any): string {
    return String(status || '')
      .trim()
      .toLowerCase()
      .replace(/[_-]+/g, ' ');
  }

  statusLabel(status: any): string {
    const key = this.statusKey(status);
    if (key === 'quarantine') {
      return 'Quarantine';
    }
    if (key === 'under test' || key === 'undertest') {
      return 'Under Test';
    }
    if (key === 'approved' || key === 'approve') {
      return 'Approved';
    }
    if (key === 'rejected' || key === 'reject') {
      return 'Rejected';
    }
    return String(status || '—');
  }

  statusClass(status: any): string {
    const key = this.statusKey(status);
    if (key === 'quarantine') {
      return 'status-btn status-btn--quarantine';
    }
    if (key === 'under test' || key === 'undertest') {
      return 'status-btn status-btn--test';
    }
    if (key === 'approved' || key === 'approve') {
      return 'status-btn status-btn--approved';
    }
    if (key === 'rejected' || key === 'reject') {
      return 'status-btn status-btn--rejected';
    }
    return 'status-btn status-btn--default';
  }

  download() {
    this.service.open('store/packing.php?type=downloadStock');
  }
}
