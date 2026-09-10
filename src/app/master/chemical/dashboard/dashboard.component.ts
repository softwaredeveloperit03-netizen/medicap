import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { STANDARD_CHEMICAL_TEMPLATES } from '../shared/standard-chemicals.constants';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  reports: any[] = [];
  stock: any[] = [];
  selectedReview: any = {};
  selectedReview1: any = {};
  materials: any[] = [];
  isView = false;
  addStock = false;
  seedingStandards = false;
  loading = false;
  stockLoading = false;
  pageSize = 10;

  grades: any;
  grade = '';
  searchQuery = '';
  fromlevel1: any[] = [];
  unitValue = '';
  unit2Value = '';
  unit: any;

  chemical_name = '';
  molecular_wt: any;
  cas_name = '';
  make: any[] = [];
  manufaturer: any[] = [];
  hsn: any;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getChemiclLog();
    this.getUnit();

    this.service.observableGrade.subscribe((response) => {
      this.grades = response;
    });
  }

  seedStandardChemicals(): void {
    if (this.seedingStandards) {
      return;
    }
    const masterUserName =
      localStorage.getItem('username') ||
      localStorage.getItem('user') ||
      localStorage.getItem('firstname') ||
      'Master User';
    this.seedingStandards = true;
    const payload = {
      masterUserName,
      chemicals: STANDARD_CHEMICAL_TEMPLATES,
    };
    this.service.post('qc/chemical.php?type=seedStandardChemicals', JSON.stringify(payload)).subscribe({
      next: (response: any) => {
        this.seedingStandards = false;
        if (response?.status === 'success') {
          const added = response.added ?? 0;
          const skipped = response.skipped ?? 0;
          alertify.success(`QC chemicals seeded (${added} added, ${skipped} already exist).`);
          this.getChemiclLog();
        } else {
          alertify.error(response?.message || 'Could not seed chemicals.');
        }
      },
      error: () => {
        this.seedingStandards = false;
        alertify.error('Could not seed chemicals. Check your connection / database.');
      },
    });
  }

  updateUnit2() {
    this.unit2Value = this.unitValue;
  }

  getChemiclLog() {
    this.loading = true;
    this.service.get('qc/chemical.php?type=getChemicalsLog').subscribe(
      (response) => {
        this.reports = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.reports = [];
        this.loading = false;
      }
    );
  }

  get filteredItems(): any[] {
    const list = Array.isArray(this.reports) ? this.reports : [];
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return list;
    }
    return list.filter((material) =>
      Object.entries(material || {}).some(([_, value]) => {
        if (value == null || typeof value === 'object') {
          return false;
        }
        return String(value).toLowerCase().includes(q);
      })
    );
  }

  getStock(chemicalNo?: string) {
    const code = chemicalNo || this.selectedReview?.['chemical_no'] || '';
    if (!code) {
      this.stock = [];
      return;
    }
    this.stockLoading = true;
    this.service.get('master/material.php?type=gate_stock&chemical_no=' + encodeURIComponent(code)).subscribe(
      (response) => {
        this.stock = Array.isArray(response) ? response : [];
        this.stockLoading = false;
      },
      () => {
        this.stock = [];
        this.stockLoading = false;
      }
    );
  }

  getUnit() {
    this.service.get('master/material.php?type=gate_Unit').subscribe((response) => {
      this.unit = response;
    });
  }

  addstock(record: any) {
    this.addStock = true;
    this.selectedReview1 = record || {};
    this.chemical_name = this.selectedReview1['chemical_name'] || '';
  }

  download() {
    this.service.open('qc/chemical.php?type=downloadChemicalsLog&grade=' + encodeURIComponent(this.grade || ''));
  }

  private asArray(value: any): any[] {
    if (Array.isArray(value)) {
      return value;
    }
    if (typeof value === 'string' && value.trim() !== '') {
      try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  private normalizeManufacturers(value: any): any[] {
    const rows = this.asArray(value);
    return rows
      .map((row) => {
        if (typeof row === 'string') {
          return { vendor_no: '', vendor_name: row };
        }
        return {
          vendor_no: row?.vendor_no ?? row?.mfg_no ?? row?.code ?? '',
          vendor_name: row?.vendor_name ?? row?.mfg_name ?? row?.manufacturer_name ?? '',
        };
      })
      .filter((row) => row.vendor_no || row.vendor_name);
  }

  private normalizeMakeList(value: any): any[] {
    const rows = this.asArray(value);
    return rows
      .map((row) => {
        if (typeof row === 'string') {
          return { make: row };
        }
        return { make: row?.make ?? row?.name ?? row?.make_name ?? '' };
      })
      .filter((row) => row.make);
  }

  private applyChemicalDetail(record: any): void {
    this.selectedReview = record || {};
    this.chemical_name = this.selectedReview['chemical_name'] || '';
    this.molecular_wt = this.selectedReview['molecular_wt'] || '';
    this.cas_name = this.selectedReview['cas_name'] || '';
    this.grade = this.selectedReview['grade'] || '';
    this.make = this.normalizeMakeList(this.selectedReview['make']);
    this.manufaturer = this.normalizeManufacturers(this.selectedReview['manufaturer']);
    this.hsn = this.selectedReview['hsn'] || '';
    const stockRows = Array.isArray(this.selectedReview['stock']) ? this.selectedReview['stock'] : [];
    this.stock = stockRows;
  }

  viewReviews(record: any) {
    const chemicalNo = record?.['chemical_no'] || '';
    if (!chemicalNo) {
      alertify.error('Chemical number missing.');
      return;
    }
    this.isView = true;
    this.stockLoading = true;
    this.applyChemicalDetail(record);
    this.service
      .get('qc/chemical.php?type=getChemicalDetail&chemical_no=' + encodeURIComponent(chemicalNo))
      .subscribe({
        next: (response: any) => {
          if (response && response.chemical_no) {
            this.applyChemicalDetail(response);
            this.stockLoading = false;
          } else {
            this.getStock(chemicalNo);
          }
        },
        error: () => {
          this.getStock(chemicalNo);
        },
      });
  }

  closeView() {
    this.isView = false;
    this.selectedReview = {};
    this.stock = [];
  }

  saveStock(data) {
    if (!data.valid) {
      alertify.error('All fields are required.');
      return;
    }
    const temp1 = data.value;
    this.fromlevel1.push(temp1);
    this.service
      .post(
        'master/material.php?type=save_stock&chemical_no=' +
          encodeURIComponent(this.selectedReview1['chemical_no'] || ''),
        JSON.stringify(temp1)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('Successfully added stock!');
          data.resetForm();
          this.addStock = false;
          this.getStock(this.selectedReview1['chemical_no']);
        } else {
          alertify.error('Failed: An error occurred, please try again!');
        }
      });
  }

  onPageChange(_page: number) {}
}
