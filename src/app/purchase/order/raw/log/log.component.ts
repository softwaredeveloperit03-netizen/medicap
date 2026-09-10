import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';
declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css', '../../../shared/purchase-vapp-host.css'],
  providers: [DatePipe]
})
export class LogComponent implements OnInit {

  from_date = '';
  to_date = '';
  today = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01') || '';
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    this.getAllPendingPO();
  }

  po_type = 'All';
  results: any[] = [];
  loading = false;
  searchQuery = '';

  getAllPendingPO(): void {
    this.loading = true;
    this.service
      .getJsonArray(
        'purchase/po/raw.php?type=getAllPOLog&po_type=' + encodeURIComponent(this.po_type || 'All')
      )
      .subscribe({
        next: (list) => {
          this.results = Array.isArray(list) ? list : [];
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.loading = false;
        },
      });
  }

  downloadExcel(): void {
    const list = this.filteredMaterials || [];
    if (list.length === 0) return;
    const headers = ['Sr.No', 'Material Type', 'PO No.', 'Po Dt.', 'Materials', 'Entered By', 'Vendor Name', 'Status', 'Security Inward', 'Landing Price'];
    const rows = list.map((row: any, index: number) => {
      const matNames = (row.materials || []).map((m: any) => m.material_name).join(', ');
      return [
        index + 1,
        row.po_type ?? '',
        row.po_no ?? '',
        this.formatDate(row.entry_date),
        matNames,
        row.entry_by ?? '',
        (row.vendor_name || '') + (row.vendor_no ? ' - ' + row.vendor_no : ''),
        (row.status || '').toUpperCase(),
        this.getSecurityInwardStatus(row),
        row.landingCost ?? row.landing_price ?? '',
      ];
    });

    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Purchase Order Log', { views: [{ rightToLeft: false }] });
    const headerRow = ws.addRow(headers);
    const headerColors = ['FF0d9488', 'FF059669', 'FF0891b2', 'FF7c3aed', 'FFdc2626', 'FFea580c', 'FF2563eb', 'FF16a34a', 'FFca8a04', 'FF4f46e5'];
    headerRow.eachCell((cell, colNumber) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: headerColors[colNumber - 1] || 'FF64748b' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
    });
    ws.getRow(1).height = 22;
    const colWidths = [8, 18, 14, 12, 36, 14, 28, 12, 16, 14];
    ws.columns.forEach((col, i) => { if (i < headers.length) col.width = colWidths[i] ?? 14; });
    ws.addRows(rows);
    const thinBorder = { top: { style: 'thin' as const }, bottom: { style: 'thin' as const }, left: { style: 'thin' as const }, right: { style: 'thin' as const } };
    for (let r = 2; r <= rows.length + 1; r++) {
      ws.getRow(r).eachCell((cell) => { cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true }; cell.border = thinBorder; });
      ws.getRow(r).height = 20;
    }

    wb.xlsx.writeBuffer().then((buffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'Purchase_Order_Log.xlsx';
      a.click();
      URL.revokeObjectURL(a.href);
    });
  }

  formatDate(dateString: string): string {
    if (!dateString) return '';
    const d = new Date(dateString);
    const day = ('0' + d.getDate()).slice(-2);
    const month = ('0' + (d.getMonth() + 1)).slice(-2);
    return `${day}-${month}-${d.getFullYear()}`;
  }

  getSecurityInwardStatus(row: any): string {
    const value = (row?.is_security_receive ?? '').toString().trim();
    if (value === 'Yes') {
      return 'Received';
    }
    if (value === 'No') {
      return 'Pending';
    }
    return 'Pending';
  }



  isView = false;
  selectedBill: any = {};
  selectedShip: any = {};
  selectedPO: any = null;

  view(data: any): void {
    this.selectedPO = data;
    this.selectedBill = this.selectedPO && this.selectedPO['selectedBill'] ? this.selectedPO['selectedBill'] : {};
    this.selectedShip = this.selectedPO && this.selectedPO['selectedShip'] ? this.selectedPO['selectedShip'] : {};
    this.isView = true;
  }

 

  updatePO(status) {
 
    let temp = {};
    temp['id'] = this.selectedPO['id'];
    temp['status'] = status;

    this.service.post('purchase/po/raw.php?type=approvePO', JSON.stringify(temp) ).subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('PO Sent For '+status+ ' Approval.....');
          this.getAllPendingPO();
          this.isView = false;
        } else {
          alertify.error('Failed to Update PO, Please try again!');
        }
      });
  }

   
  get filteredMaterials(): any[] {
    const list = this.results || [];
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) return list;
    return list.filter((row: any) => {
      if (row.entry_date && typeof row.entry_date === 'string' && row.entry_date.toLowerCase().includes(q)) return true;
      if (row.po_no && String(row.po_no).toLowerCase().includes(q)) return true;
      if (row.po_type && String(row.po_type).toLowerCase().includes(q)) return true;
      if (row.vendor_name && String(row.vendor_name).toLowerCase().includes(q)) return true;
      if (row.vendor_no && String(row.vendor_no).toLowerCase().includes(q)) return true;
      if (row.entry_by && String(row.entry_by).toLowerCase().includes(q)) return true;
      if (row.status && String(row.status).toLowerCase().includes(q)) return true;
      if (this.getSecurityInwardStatus(row).toLowerCase().includes(q)) return true;
      if (row.is_security_receive && String(row.is_security_receive).toLowerCase().includes(q)) return true;
      const materials = row.materials || [];
      if (materials.some((m: any) => (m.material_name && String(m.material_name).toLowerCase().includes(q)) || (m.material_code && String(m.material_code).toLowerCase().includes(q)))) return true;
      return false;
    });
  }

  clearFilter(): void {
    this.searchQuery = '';
  }
  
 

  
   calculatePercentage() {
    this.transportTaxAmt = (this.transportCost * this.gstPerOnTransport) / 100;
    this.totalTransportationCose = +this.transportCost + +this.transportTaxAmt;
  }
 
 
  download(){
    this.service.open('purchase/po/raw.php?type=downloadPOLog&from_date='+this.from_date+'&to_date='+this.to_date)
  }

  downloadPOReport(): void {
    if (!this.selectedPO || !this.selectedPO['id']) return;
    this.service.open('purchase/po_print.php?type=downloadPOReport&id=' + this.selectedPO['id']);
  }


 
  purchaseOrderLogActionForStatusUpdate(status: string, what: string): void {
    if (!this.selectedPO || !this.selectedPO['id']) return;
    const check = confirm('Are you sure you want to ' + what + ' this Purchase Order?');
    if (check) {
      const updateRemark = prompt('Please Enter ' + what + ' Remark.....');
      const obj: any = { what, status, updateRemark, id: this.selectedPO['id'] };

      this.service.post('purchase/po/raw.php?type=purchaseOrderLogActionForStatusUpdate',JSON.stringify(obj)).subscribe(response => {
        if (response['status'] == 'success') {
          alertify.success('Purchase Order '+ what + ' Successfully!!!!!!!');
          this.isView = false;
          this.getAllPendingPO();
        } else {
          alertify.error(response['status']);
        }
      });

    }

  }



  isLanding = false;
  totalLandingCost = 0;

  transportCost = 0;
  gstPerOnTransport = 0;
  totalTransportationCose = 0;
  transportTaxAmt = 0;
  landingCost = 0;


  viewLandingPrice(data: any): void {
    this.transportCost = 0;
    this.gstPerOnTransport = 0;
    this.totalTransportationCose = 0;
    this.transportTaxAmt = 0;
    this.selectedPO = data;
    const materials = this.selectedPO && this.selectedPO['materials'] ? this.selectedPO['materials'] : [];
    const totalQty = materials.reduce((sum: number, m: any) => sum + (+m.qty || 0), 0);
    this.isLanding = true;
    this.totalLandingCost = +(this.selectedPO['final_total'] || 0);
    this.landingCost = totalQty > 0 ? parseFloat((this.totalLandingCost / totalQty).toFixed(2)) : 0;
  }

 
  transportCosts = [];


  totalTransAmt = 0;
  totalTransGstAmt = 0;
  finalTransAmt = 0;


  addTransportCost(data){
    if(!data.valid){
      alertify.error('All Field Required !!!!!!!!!!');
      return;
    }
    let temp = data.value;
    this.transportCosts.push(temp);

    this.totalTransAmt = parseFloat((this.totalTransAmt + +temp['transportCost']).toFixed(2));
    this.totalTransGstAmt = parseFloat((this.totalTransGstAmt + +temp['transportTaxAmt']).toFixed(2));
    this.finalTransAmt = parseFloat((this.finalTransAmt + +temp['totalTransportationCose']).toFixed(2));
    data.reset();
  }

  delTraspotCost(index){
    this.totalTransAmt = parseFloat((this.totalTransAmt - +this.transportCosts[index]['transportCost']).toFixed(2));
    this.totalTransGstAmt = parseFloat((this.totalTransGstAmt - +this.transportCosts[index]['transportTaxAmt']).toFixed(2));
    this.finalTransAmt = parseFloat((this.finalTransAmt - +this.transportCosts[index]['totalTransportationCose']).toFixed(2));
    this.transportCosts.splice(index , 1);
  }


  saveTransportCost(){

    let temp = {};
    temp['poId'] = this.selectedPO['poId'];
    temp['transportCosts'] = this.transportCosts;
    temp['transportCost'] = this.totalTransAmt;
    temp['transportTaxAmt'] = this.totalTransGstAmt;
    temp['totalTransportationCose'] = this.finalTransAmt;
 
    this.service.post('purchase/po/raw.php?type=saveTransportCost',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Transport Cost Saved Succeffully....');
        this.isLanding = false;
        this.getAllPendingPO();
        this.transportCosts = [];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

  


  }


