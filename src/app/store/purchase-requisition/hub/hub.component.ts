import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-purchase-requisition-hub',
  templateUrl: './hub.component.html',
  styleUrls: ['./hub.component.css']
})
export class PurchaseRequisitionHubComponent implements OnInit {

  results: any[] = [];
  searchQuery = '';
  materialType = 'RM/PM Material';
  loading = false;

  isView = false;
  materials: any[] = [];
  selectedResult: any = {};

  currentPage = 1;
  pageSize = 10;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getMaterialRequisitions(this.materialType);
  }

  getMaterialRequisitions(value: string) {
    this.materialType = value;
    this.isView = false;
    this.loading = true;
    const endpoint = value === 'RM/PM Material'
      ? 'store/material_requisition.php?type=getRmpmRequisitions'
      : 'store/material_requisition.php?type=getGeneralRequisitions&department_name=' +
          encodeURIComponent(localStorage.getItem('department') || '');

    this.service.get(endpoint).subscribe((response: any) => {
      if (Array.isArray(response)) {
        this.results = response.map((row) => this.normalizeRow(row));
      } else {
        this.results = [];
      }
      this.loading = false;
    }, () => {
      this.results = [];
      this.loading = false;
    });
  }

  private normalizeRow(row: any): any {
    if (!row || typeof row !== 'object') {
      return row;
    }
    let materials = row.materials;
    if (typeof materials === 'string') {
      try {
        materials = JSON.parse(materials);
      } catch {
        materials = [];
      }
    }
    if (!Array.isArray(materials)) {
      materials = [];
    }
    return { ...row, materials };
  }

  get filteredResults(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }
    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((row) =>
      Object.values(row).some(value =>
        value != null && typeof value !== 'object' && value.toString().toLowerCase().includes(query)
      )
    );
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  onPageChange(page: number) {
    this.currentPage = page;
  }

  view(indend: any) {
    this.selectedResult = indend || {};
    const raw = (this.selectedResult && this.selectedResult['materials']) || [];
    this.materials = Array.isArray(raw)
      ? raw.map((m: any) => this.normalizeMaterialRow(m))
      : [];
    this.isView = true;
  }

  private normalizeMaterialRow(m: any): any {
    if (!m) {
      return {};
    }
    const vendor =
      m.vendor_name ||
      (m.specific_vendor === 'NA' ? 'NA' : m.specific_vendor) ||
      m.vendor_no ||
      '';
    return {
      ...m,
      grade: m.grade || m.grade_name || m.gradeName || '',
      department: m.department || '',
      vendor_name: vendor,
      req_qty: m.req_qty != null && m.req_qty !== '' ? m.req_qty : m.qty,
    };
  }

  displayValue(value: any): string {
    return value == null || value === '' ? '—' : String(value);
  }

  viewf() {
    this.isView = false;
    this.currentPage = 1;
    this.pageSize = 10;
  }

  check(item: any) {
    if (!item || !item.request_no) {
      return;
    }
    this.service.get('store/material_requisition.php?type=checkRequisition&request_no=' +
      encodeURIComponent(item.request_no)).subscribe((response: any) => {
      if (response && response['status'] === 'success') {
        alertify.success('Requisition ' + item.request_no + ' checked successfully');
        this.getMaterialRequisitions(this.materialType);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
