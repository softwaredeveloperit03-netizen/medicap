import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';
declare let alertify: any;

@Component({
  selector: 'app-map',
  templateUrl: './map.component.html',
  styleUrls: ['./map.component.css'],
})
export class MapComponent implements OnInit {
  vendor_id;
  vendor_type = '';
  vendor_name;
  selectedMaterial: any = {};
  isView = false;
  item = [];
  reports;
  materials: any[] = [];

  results: any[] = [];
  loading = false;
  searchQuery = '';
  is_corporate: any = localStorage.getItem('is_corporate') || 0;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getProductsLog();
    this.get_rights();
  }

  getProductsLog() {
    this.loading = true;
    this.service
      .get('master/material.php?type=get_supplier_by_materials_log')
      .subscribe(
        (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        () => { this.loading = false; }
      );
  }

  get filteredMaterials(): any[] {
    if (!this.results || this.results.length === 0) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') return this.results;
    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((row) =>
      Object.entries(row).some(([, value]) =>
        value != null && String(value).toLowerCase().includes(query)
      )
    );
  }

  viewMaterial(data: any, event?: Event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    this.selectedMaterial = data || {};
    this.isView = true;
    this.getProducts();
  }

  closeView(): void {
    this.isView = false;
    this.materials = [];
    this.selectedMaterial = {};
  }

  exportToExcel(): void {
    this.service
      .get('master/material.php?type=get_supplier_by_materials_log_material&value=')
      .subscribe(
        (response: any) => {
          const vendors = Array.isArray(response) ? response : [];
          this.buildExcel(this.applySearch(vendors));
        },
        () => { this.buildExcel([]); }
      );
  }

  /** Filter the fetched vendor+material list by the on-screen search query. */
  private applySearch(vendors: any[]): any[] {
    if (!this.searchQuery || !this.searchQuery.trim()) {
      return vendors;
    }
    const q = this.searchQuery.toLowerCase().trim();
    return vendors.filter((v) =>
      [v.vendor_no, v.vendor_type, v.vendor_name, v.entry_by]
        .some((f) => f != null && String(f).toLowerCase().includes(q))
    );
  }

  private async buildExcel(vendors: any[]): Promise<void> {
    const headers = [
      'Sr.No', 'Entry Date', 'Entry By', 'Vendor Code', 'Vendor Type', 'Vendor Name',
      'No of Product', 'Material Code', 'Material Name', 'Material Type', 'Material Subtype',
    ];
    const colWidths = [7, 16, 14, 14, 16, 30, 13, 18, 32, 18, 18];

    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Vendor Material Map');

    // Title row with company name
    ws.mergeCells(1, 1, 1, headers.length);
    const titleCell = ws.getCell(1, 1);
    titleCell.value = 'Medicap Laboratories — Vendor Material Mapping';
    titleCell.font = { bold: true, size: 14, color: { argb: 'FF0E4370' } };
    titleCell.alignment = { vertical: 'middle', horizontal: 'center' };
    ws.getRow(1).height = 24;

    // Header row (colored background)
    const headerRow = ws.addRow(headers);
    headerRow.height = 20;
    headerRow.eachCell((cell) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0E4370' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
      cell.border = {
        top: { style: 'thin' }, bottom: { style: 'thin' },
        left: { style: 'thin' }, right: { style: 'thin' },
      };
    });

    const thin = { style: 'thin' as const, color: { argb: 'FFB8C4D6' } };
    const border = { top: thin, bottom: thin, left: thin, right: thin };

    let sr = 0;
    vendors.forEach((v) => {
      sr++;
      const products = Array.isArray(v.products) ? v.products : [];
      const vendorCols = [
        sr,
        v.entry_date || 'NA',
        v.entry_by || 'NA',
        v.vendor_no || 'NA',
        v.vendor_type || 'NA',
        v.vendor_name || 'NA',
        v.no_of_products != null ? v.no_of_products : (products.length || 'NA'),
      ];

      if (!products.length) {
        ws.addRow([...vendorCols, 'NA', 'NA', 'NA', 'NA']);
        return;
      }

      products.forEach((p: any, pi: number) => {
        const head = pi === 0 ? vendorCols : ['', '', '', '', '', '', ''];
        ws.addRow([
          ...head,
          p.material_code || 'NA',
          p.material_name || 'NA',
          p.material_type || 'NA',
          p.material_subtype || 'NA',
        ]);
      });
    });

    // Style + width all data rows
    ws.columns.forEach((col, i) => { col.width = colWidths[i] ?? 14; });
    for (let r = 3; r <= ws.rowCount; r++) {
      const row = ws.getRow(r);
      row.eachCell((cell, c) => {
        cell.border = border;
        cell.alignment = {
          vertical: 'top',
          horizontal: (c === 1 || c === 7) ? 'center' : 'left',
          wrapText: true,
        };
      });
    }

    const buffer = await wb.xlsx.writeBuffer();
    const blob = new Blob([buffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'vendor_material_map.xlsx';
    a.click();
    URL.revokeObjectURL(a.href);
  }

  getProducts() {
    this.materials = [];
    const vendorNo = this.selectedMaterial && this.selectedMaterial['vendor_no'];
    if (!vendorNo) {
      return;
    }
    this.service
      .get('master/material.php?type=get_materials_by_supplier&vendor_no=' + encodeURIComponent(vendorNo))
      .subscribe(
        (response: any) => {
          this.materials = Array.isArray(response) ? response : [];
        },
        () => {
          this.materials = [];
          alertify.error('Could not load mapped materials for this vendor.');
        }
      );
  }

  download() {
    const url =
      this.service.url +
      'purchase/vendor.php?type=download_material_map_pdf' +
      '&token=' +
      encodeURIComponent(localStorage.getItem('token') || '') +
      '&plant_id=' +
      encodeURIComponent(localStorage.getItem('plant_id') || '');
    const popup = window.open(url, '_blank');
    if (!popup) {
      alertify.error('PDF blocked by browser. Allow pop-ups for this site and try again.');
    }
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id='
      + localStorage.getItem('emp_id') + '&dep_name=' + this.loggedInDept)
      .subscribe((response: any) => {
        this.rights = response;
        const r = Array.isArray(response) && response.length ? response[0] : {};
        this.isuser = r.isuser || 'No';
        this.ischecker = r.ischecker || 'No';
        this.isapprover = r.isapprover || 'No';
        this.qms_approver = r.qms_approver || 'No';
        this.dept_head = r.dept_head || 'No';
        this.isauditor = r.isauditor || 'No';
        this.plant_head = r.plant_head || 'No';
        this.shift_allocator = r.shift_allocator || 'No';
      });
  }


}
