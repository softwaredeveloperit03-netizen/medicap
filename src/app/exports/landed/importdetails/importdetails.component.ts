import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import * as ExcelJS from 'exceljs';
declare let alertify: any;

@Component({
  selector: 'app-importdetails',
  templateUrl: './importdetails.component.html',
  styleUrls: ['./importdetails.component.css']
})
export class ImportdetailsComponent implements OnInit {

  importData: any[] = [];
  loading = false;
  isView = false;
  selectedRecord: any = null;
  searchQuery = '';
  pageSize = 10;
  currentPage = 1;

  // Form fields based on image description
  grnNo: string = '';
  grnDate: string = '';
  supplierName: string = '';
  invDate: string = '';
  invNo: string = '';
  poNo: string = '';
  blAwbNo: string = '';
  portOfLoading: string = '';
  portOfDischarge: string = '';
  incoterms: string = '';
  mode: string = '';
  currency: string = '';
  exchangeRate: number = 0;
  basicInvoiceValue: number = 0;
  packingCharges: number = 0;
  designMouldCost: number = 0;
  invValueInRs: number = 0;
  packingChargesInRs: number = 0;
  designMouldCostInRs: number = 0;
  itemCode: string = '';
  itemDescription: string = '';
  quantity: number = 0;
  uom: string = '';
  invRate: number = 0;
  rateInRs: number = 0;

  // Incoterms dropdown options
  incotermsList: string[] = [
    'EXW - Ex Works',
    'FCA - Free Carrier',
    'CPT - Carriage Paid To',
    'CIP - Carriage and Insurance Paid To',
    'DAP - Delivered At Place',
    'DPU - Delivered at Place Unloaded',
    'DDP - Delivered Duty Paid',
    'FAS - Free Alongside Ship',
    'FOB - Free On Board',
    'CFR - Cost and Freight',
    'CIF - Cost, Insurance and Freight'
  ];

  // Ports list
  allPorts: string[] = [
    // Indian Ports
    'Mumbai Port', 'Chennai Port', 'Kolkata Port', 'Kandla Port', 'Cochin Port',
    'JNPT (Nhava Sheva)', 'Tuticorin Port', 'Visakhapatnam Port', 'Paradip Port',
    'Mormugao Port', 'Mangalore Port', 'Haldia Port', 'Ennore Port', 'Krishnapatnam Port',
    'Pipavav Port', 'Mundra Port', 'Hazira Port', 'Dahej Port', 'Dhamra Port',
    // International Ports - Asia
    'Port of Shanghai', 'Port of Ningbo-Zhoushan', 'Port of Shenzhen', 'Port of Guangzhou',
    'Port of Qingdao', 'Port of Tianjin', 'Port of Hong Kong', 'Port of Singapore',
    'Port of Busan', 'Port of Tokyo', 'Port of Yokohama', 'Port of Osaka',
    'Port of Kaohsiung', 'Port of Manila', 'Port of Bangkok', 'Port of Ho Chi Minh',
    'Port of Jakarta', 'Port of Colombo', 'Port of Chittagong', 'Port of Karachi',
    // International Ports - Europe
    'Port of Rotterdam', 'Port of Antwerp', 'Port of Hamburg', 'Port of Bremen',
    'Port of London', 'Port of Liverpool', 'Port of Southampton', 'Port of Le Havre',
    'Port of Marseille', 'Port of Genoa', 'Port of Barcelona', 'Port of Valencia',
    'Port of Amsterdam', 'Port of Ghent', 'Port of Zeebrugge', 'Port of Gothenburg',
    'Port of Copenhagen', 'Port of Helsinki', 'Port of Gdansk', 'Port of Hamburg',
    // International Ports - Americas
    'Port of Los Angeles', 'Port of Long Beach', 'Port of New York & New Jersey',
    'Port of Savannah', 'Port of Seattle', 'Port of Tacoma', 'Port of Houston',
    'Port of Miami', 'Port of Charleston', 'Port of Norfolk', 'Port of Baltimore',
    'Port of Vancouver', 'Port of Montreal', 'Port of Santos', 'Port of Buenos Aires',
    'Port of Callao', 'Port of Cartagena', 'Port of Manzanillo', 'Port of Veracruz',
    // International Ports - Middle East & Africa
    'Port of Jebel Ali', 'Port of Dubai', 'Port of Dammam', 'Port of Jeddah',
    'Port of Aqaba', 'Port of Haifa', 'Port of Ashdod', 'Port of Durban',
    'Port of Cape Town', 'Port of Lagos', 'Port of Mombasa', 'Port of Dar es Salaam',
    // Australia & Oceania
    'Port of Sydney', 'Port of Melbourne', 'Port of Brisbane', 'Port of Fremantle',
    'Port of Auckland', 'Port of Wellington', 'Port of Tauranga'
  ];

  // Filtered ports for search
  filteredPortsLoading: string[] = [];
  filteredPortsDischarge: string[] = [];
  searchTextLoading: string = '';
  searchTextDischarge: string = '';
  showPortsLoading: boolean = false;
  showPortsDischarge: boolean = false;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getImportData();
    this.filteredPortsLoading = this.allPorts;
    this.filteredPortsDischarge = this.allPorts;
  }

  get filteredData(): any[] {
    if (!this.importData?.length) return [];
    const q = (this.searchQuery || '').toLowerCase().trim();
    if (!q) return this.importData;
    return this.importData.filter((row: any) =>
      Object.values(row).some((v: any) => v != null && String(v).toLowerCase().includes(q))
    );
  }

  get totalFiltered(): number {
    return this.filteredData.length;
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil(this.totalFiltered / this.pageSize));
  }

  get paginatedData(): any[] {
    const start = (this.currentPage - 1) * this.pageSize;
    return this.filteredData.slice(start, start + this.pageSize);
  }

  get paginationEnd(): number {
    return Math.min((this.currentPage - 1) * this.pageSize + this.pageSize, this.totalFiltered);
  }

  get pageNumbers(): number[] {
    const total = this.totalPages;
    const maxShow = 7;
    if (total <= maxShow) return Array.from({ length: total }, (_, i) => i + 1);
    const cur = this.currentPage;
    let start = Math.max(1, cur - 3);
    let end = Math.min(total, start + maxShow - 1);
    if (end - start + 1 < maxShow) start = Math.max(1, end - maxShow + 1);
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
  }

  onPageSizeChange(): void {
    this.currentPage = 1;
  }

  goToPage(p: number): void {
    if (p >= 1 && p <= this.totalPages) this.currentPage = p;
  }

  nextPage(): void {
    if (this.currentPage < this.totalPages) this.currentPage++;
  }

  prevPage(): void {
    if (this.currentPage > 1) this.currentPage--;
  }

  getRecordIndex(record: any): number {
    return this.importData.findIndex((r: any) => r === record);
  }

  getImportData() {
    this.loading = true;
    this.service.get('exports/exports.php?type=getImportDetails').subscribe({
      next: (response: any) => {
        this.importData = Array.isArray(response) ? response : [];
        this.currentPage = 1;
        this.loading = false;
      },
      error: () => {
        alertify.error('Error fetching import details');
        this.loading = false;
      }
    });
  }

  view(index: number) {
    const record = this.importData[index];
    if (!record) return;
    this.selectedRecord = record;
    
    // Populate form fields - use saved import_details data if available, otherwise use base data
    this.poNo = this.selectedRecord.po_no || '';
    this.grnNo = this.selectedRecord.grn_no || '';
    this.grnDate = this.selectedRecord.grn_date || '';
    this.supplierName = this.selectedRecord.supplier_name || this.selectedRecord.vendor_name || '';
    this.invDate = this.selectedRecord.inv_date || this.selectedRecord.invDate || '';
    this.invNo = this.selectedRecord.inv_no || this.selectedRecord.invNo || '';
    this.blAwbNo = this.selectedRecord.bl_awb_no || this.selectedRecord.blAwbNo || '';
    this.portOfLoading = this.selectedRecord.port_of_loading || '';
    this.portOfDischarge = this.selectedRecord.port_of_discharge || '';
    this.searchTextLoading = this.portOfLoading;
    this.searchTextDischarge = this.portOfDischarge;
    this.incoterms = this.selectedRecord.incoterms || '';
    this.mode = this.selectedRecord.mode || '';
    this.currency = this.selectedRecord.currency || this.selectedRecord.currency1 || '';
    this.exchangeRate = this.selectedRecord.exchange_rate ? parseFloat(this.selectedRecord.exchange_rate) : 0;
    this.basicInvoiceValue = this.selectedRecord.basic_invoice_value ? parseFloat(this.selectedRecord.basic_invoice_value) : (this.selectedRecord.net_total ? parseFloat(this.selectedRecord.net_total) : 0);
    this.packingCharges = this.selectedRecord.packing_charges ? parseFloat(this.selectedRecord.packing_charges) : 0;
    this.designMouldCost = this.selectedRecord.design_mould_cost ? parseFloat(this.selectedRecord.design_mould_cost) : 0;
    this.invValueInRs = this.selectedRecord.inv_value_in_rs ? parseFloat(this.selectedRecord.inv_value_in_rs) : 0;
    this.packingChargesInRs = this.selectedRecord.packing_charges_in_rs ? parseFloat(this.selectedRecord.packing_charges_in_rs) : 0;
    this.designMouldCostInRs = this.selectedRecord.design_mould_cost_in_rs ? parseFloat(this.selectedRecord.design_mould_cost_in_rs) : 0;
    this.itemCode = this.selectedRecord.item_code || this.selectedRecord.material_code || '';
    this.itemDescription = this.selectedRecord.item_description || this.selectedRecord.material_name || '';
    this.quantity = this.selectedRecord.quantity || this.selectedRecord.qty ? parseFloat(this.selectedRecord.quantity || this.selectedRecord.qty) : 0;
    this.uom = this.selectedRecord.uom || this.selectedRecord.unit || '';
    this.invRate = this.selectedRecord.inv_rate ? parseFloat(this.selectedRecord.inv_rate) : (this.selectedRecord.rate ? parseFloat(this.selectedRecord.rate) : 0);
    this.rateInRs = this.selectedRecord.rate_in_rs ? parseFloat(this.selectedRecord.rate_in_rs) : 0;
    
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedRecord = null;
    this.resetForm();
  }

  save(form: any) {
    if (!this.selectedRecord || (!this.selectedRecord.po_no && !this.selectedRecord.grn_no && !this.selectedRecord.challan_no)) {
      alertify.warning('No record selected.');
      return;
    }
    // Use searchText values if portOfLoading/portOfDischarge are empty
    const portLoading = this.portOfLoading || this.searchTextLoading;
    const portDischarge = this.portOfDischarge || this.searchTextDischarge;

    const data = {
      id: this.selectedRecord.import_details_id || '',
      po_no: this.poNo,
      challan_no: this.selectedRecord.challan_no || '',
      grn_no: this.grnNo,
      grn_date: this.grnDate,
      supplier_name: this.supplierName,
      inv_date: this.invDate,
      inv_no: this.invNo,
      bl_awb_no: this.blAwbNo,
      port_of_loading: portLoading,
      port_of_discharge: portDischarge,
      incoterms: this.incoterms,
      mode: this.mode,
      currency: this.currency,
      exchange_rate: this.exchangeRate,
      basic_invoice_value: this.basicInvoiceValue,
      packing_charges: this.packingCharges,
      design_mould_cost: this.designMouldCost,
      inv_value_in_rs: this.invValueInRs,
      packing_charges_in_rs: this.packingChargesInRs,
      design_mould_cost_in_rs: this.designMouldCostInRs,
      item_code: this.itemCode,
      item_description: this.itemDescription,
      quantity: this.quantity,
      uom: this.uom,
      inv_rate: this.invRate,
      rate_in_rs: this.rateInRs
    };

    this.service.post('exports/exports.php?type=saveImportDetails', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Import details saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving import details');
      }
    }, error => {
      console.error('Error saving import details:', error);
      alertify.error('Error saving import details');
    });
  }

  resetForm() {
    this.grnNo = '';
    this.grnDate = '';
    this.supplierName = '';
    this.invDate = '';
    this.invNo = '';
    this.poNo = '';
    this.blAwbNo = '';
    this.portOfLoading = '';
    this.portOfDischarge = '';
    this.searchTextLoading = '';
    this.searchTextDischarge = '';
    this.incoterms = '';
    this.mode = '';
    this.currency = '';
    this.exchangeRate = 0;
    this.basicInvoiceValue = 0;
    this.packingCharges = 0;
    this.designMouldCost = 0;
    this.invValueInRs = 0;
    this.packingChargesInRs = 0;
    this.designMouldCostInRs = 0;
    this.itemCode = '';
    this.itemDescription = '';
    this.quantity = 0;
    this.uom = '';
    this.invRate = 0;
    this.rateInRs = 0;
    this.showPortsLoading = false;
    this.showPortsDischarge = false;
  }

  refresh() {
    this.getImportData();
  }

  calculateRsValues() {
    if (this.exchangeRate > 0) {
      this.invValueInRs = this.basicInvoiceValue * this.exchangeRate;
      this.packingChargesInRs = this.packingCharges * this.exchangeRate;
      this.designMouldCostInRs = this.designMouldCost * this.exchangeRate;
      this.rateInRs = this.invRate * this.exchangeRate;
    }
  }

  // Port search functions
  filterPortsLoading() {
    if (!this.searchTextLoading) {
      this.filteredPortsLoading = this.allPorts;
    } else {
      this.filteredPortsLoading = this.allPorts.filter(port =>
        port.toLowerCase().includes(this.searchTextLoading.toLowerCase())
      );
    }
    this.showPortsLoading = true;
  }

  filterPortsDischarge() {
    if (!this.searchTextDischarge) {
      this.filteredPortsDischarge = this.allPorts;
    } else {
      this.filteredPortsDischarge = this.allPorts.filter(port =>
        port.toLowerCase().includes(this.searchTextDischarge.toLowerCase())
      );
    }
    this.showPortsDischarge = true;
  }

  selectPortLoading(port: string) {
    this.portOfLoading = port;
    this.searchTextLoading = port;
    this.showPortsLoading = false;
  }

  selectPortDischarge(port: string) {
    this.portOfDischarge = port;
    this.searchTextDischarge = port;
    this.showPortsDischarge = false;
  }

  onPortLoadingFocus() {
    this.searchTextLoading = this.portOfLoading;
    this.filterPortsLoading();
  }

  onPortDischargeFocus() {
    this.searchTextDischarge = this.portOfDischarge;
    this.filterPortsDischarge();
  }

  closePortDropdowns() {
    setTimeout(() => {
      this.showPortsLoading = false;
      this.showPortsDischarge = false;
    }, 200);
  }

  private na(val: any): string {
    return val != null && String(val).trim() !== '' ? String(val) : '—';
  }

  exportToExcel(): void {
    const headers = ['Sr. No', 'PO No', 'Challan No', 'Supplier Name', 'Item Code', 'Item Description', 'Quantity', 'UOM'];
    const list = this.filteredData;
    const rows = list.map((record: any, idx: number) => [
      idx + 1,
      this.na(record.po_no),
      this.na(record.challan_no),
      this.na(record.supplier_name || record.vendor_name),
      this.na(record.item_code || record.material_code),
      this.na(record.item_description || record.material_name),
      record.quantity ?? record.qty ?? 0,
      this.na(record.uom || record.unit)
    ]);

    const headerColors = ['FF0ea5e9', 'FF8b5cf6', 'FF059669', 'FFdc2626', 'FFea580c', 'FF0891b2', 'FF4f46e5', 'FF16a34a'];
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Import Details', { views: [{ rightToLeft: false }] });
    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell, colNumber) => {
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: headerColors[colNumber - 1] || 'FF64748b' }
      };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
    });
    ws.getRow(1).height = 22;

    const colWidths = [8, 14, 14, 28, 14, 32, 12, 8];
    ws.columns.forEach((col, i) => {
      if (i < headers.length) col.width = colWidths[i] ?? 14;
    });

    ws.addRows(rows);
    const thinBorder = {
      top: { style: 'thin' as const },
      bottom: { style: 'thin' as const },
      left: { style: 'thin' as const },
      right: { style: 'thin' as const }
    };
    const minRowHeight = 20;
    const maxRowHeight = 120;
    const pointsPerLine = 14;
    for (let r = 2; r <= rows.length + 1; r++) {
      const row = ws.getRow(r);
      const rowData = rows[r - 2] as (string | number)[];
      let maxLines = 1;
      rowData.forEach((val, c) => {
        const str = val != null ? String(val) : '';
        const colW = colWidths[c] ?? 14;
        const charsPerLine = Math.max(1, Math.floor(colW * 1.8));
        const lines = Math.max(1, Math.ceil(str.length / charsPerLine));
        if (lines > maxLines) maxLines = lines;
      });
      row.height = Math.min(maxRowHeight, Math.max(minRowHeight, maxLines * pointsPerLine));
      row.eachCell((cell) => {
        cell.alignment = { vertical: 'top', horizontal: 'left', wrapText: true };
        cell.border = thinBorder;
      });
    }

    wb.xlsx.writeBuffer().then((buffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = `Import_Details_${new Date().toISOString().slice(0, 10)}.xlsx`;
      a.click();
      URL.revokeObjectURL(a.href);
      alertify.success('Excel downloaded');
    }).catch(() => alertify.error('Export failed'));
  }
}
