import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import * as ExcelJS from 'exceljs';
declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  importData: any[] = [];
  loading = false;
  isView = false;
  selectedRecord: any = {};

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getImportData();
  }

  getImportData() {
    this.loading = true;
    this.service.get('exports/exports.php?type=getImportDetails').subscribe((response: any) => {
      this.importData = response || [];
      this.loading = false;
    }, error => {
      console.error('Error fetching import details:', error);
      alertify.error('Error fetching import details');
      this.loading = false;
    });
  }

  view(index: number) {
    this.selectedRecord = this.importData[index];
    this.calculateTotals();
    this.isView = true;
  }

  calculateTotals() {
    let basicValue = 0;
    let totalWithGst = 0;

    // Import Details - Basic Invoice Value
    if (this.selectedRecord.basic_invoice_value) {
      basicValue += parseFloat(this.selectedRecord.basic_invoice_value) || 0;
    }
    if (this.selectedRecord.inv_value_in_rs) {
      basicValue += parseFloat(this.selectedRecord.inv_value_in_rs) || 0;
    }

    // International Logistics
    if (this.selectedRecord.ocean_air_freight_amount) {
      basicValue += parseFloat(this.selectedRecord.ocean_air_freight_amount) || 0;
    }
    if (this.selectedRecord.insurance_value) {
      basicValue += parseFloat(this.selectedRecord.insurance_value) || 0;
    }
    if (this.selectedRecord.miscellaneous_charge) {
      basicValue += parseFloat(this.selectedRecord.miscellaneous_charge) || 0;
    }

    // Customs Import
    if (this.selectedRecord.assessable_value) {
      basicValue += parseFloat(this.selectedRecord.assessable_value) || 0;
    }
    if (this.selectedRecord.bcd_value) {
      basicValue += parseFloat(this.selectedRecord.bcd_value) || 0;
    }
    if (this.selectedRecord.sws_value) {
      basicValue += parseFloat(this.selectedRecord.sws_value) || 0;
    }
    if (this.selectedRecord.other_charges) {
      basicValue += parseFloat(this.selectedRecord.other_charges) || 0;
    }
    if (this.selectedRecord.total_duty_value) {
      basicValue += parseFloat(this.selectedRecord.total_duty_value) || 0;
    }

    // Agency Bills
    if (this.selectedRecord.bill_amount) {
      basicValue += parseFloat(this.selectedRecord.bill_amount) || 0;
    }
    if (this.selectedRecord.cif_term_amount_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.cif_term_amount_with_gst) || 0;
    }

    // CFS
    if (this.selectedRecord.cfs_total_bill_amount) {
      basicValue += parseFloat(this.selectedRecord.cfs_total_bill_amount) || 0;
    }
    if (this.selectedRecord.cfs_cif_term_amount_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.cfs_cif_term_amount_with_gst) || 0;
    }

    // Freight Forwarder
    if (this.selectedRecord.origin_charges) {
      basicValue += parseFloat(this.selectedRecord.origin_charges) || 0;
    }
    if (this.selectedRecord.ocean_freight) {
      basicValue += parseFloat(this.selectedRecord.ocean_freight) || 0;
    }
    if (this.selectedRecord.destination_charges) {
      basicValue += parseFloat(this.selectedRecord.destination_charges) || 0;
    }
    if (this.selectedRecord.total_forwarder_amount) {
      totalWithGst += parseFloat(this.selectedRecord.total_forwarder_amount) || 0;
    }

    // Destination Clearing
    if (this.selectedRecord.cha_clearing_agent_fees) {
      basicValue += parseFloat(this.selectedRecord.cha_clearing_agent_fees) || 0;
    }
    if (this.selectedRecord.document_fee) {
      basicValue += parseFloat(this.selectedRecord.document_fee) || 0;
    }
    if (this.selectedRecord.loading_and_unloading) {
      basicValue += parseFloat(this.selectedRecord.loading_and_unloading) || 0;
    }
    if (this.selectedRecord.stamp_duty) {
      basicValue += parseFloat(this.selectedRecord.stamp_duty) || 0;
    }
    if (this.selectedRecord.total_cha_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.total_cha_with_gst) || 0;
    }

    // Transport
    if (this.selectedRecord.inland_transportation) {
      basicValue += parseFloat(this.selectedRecord.inland_transportation) || 0;
    }
    if (this.selectedRecord.unloading_charges) {
      basicValue += parseFloat(this.selectedRecord.unloading_charges) || 0;
    }
    if (this.selectedRecord.total_transport_amount_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.total_transport_amount_with_gst) || 0;
    }

    // Payment Bank
    if (this.selectedRecord.paid_amount) {
      basicValue += parseFloat(this.selectedRecord.paid_amount) || 0;
    }
    if (this.selectedRecord.total_paid_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.total_paid_with_gst) || 0;
    }

    // Warehousing
    if (this.selectedRecord.bill_value) {
      basicValue += parseFloat(this.selectedRecord.bill_value) || 0;
    }
    if (this.selectedRecord.total_warehouse_amount_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.total_warehouse_amount_with_gst) || 0;
    }

    // Inhand
    if (this.selectedRecord.inspection_survey_fees) {
      basicValue += parseFloat(this.selectedRecord.inspection_survey_fees) || 0;
    }
    if (this.selectedRecord.total_amount_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.total_amount_with_gst) || 0;
    }

    // Store calculated values in selectedRecord for display
    this.selectedRecord.calculated_total_basic_value = basicValue;
    this.selectedRecord.calculated_total_gst = totalWithGst - basicValue;
    const quantity = parseFloat(this.selectedRecord.quantity || this.selectedRecord.qty) || 0;
    this.selectedRecord.calculated_landed_cost_per_unit = quantity > 0 ? (basicValue + (totalWithGst - basicValue)) / quantity : 0;
  }

  closeView() {
    this.isView = false;
    this.selectedRecord = {};
  }

  refresh() {
    this.getImportData();
  }

  getValue(value: any): any {
    if (value === null || value === undefined || value === '') {
      return 'N/A';
    }
    return value;
  }

  getNumberValue(value: any): number {
    if (value === null || value === undefined || value === '') {
      return 0;
    }
    return parseFloat(value) || 0;
  }

  /** Compute totals for any record (for Excel export from table row). */
  getRecordWithTotals(record: any): any {
    if (!record) return record;
    const rec = { ...record };
    let basicValue = 0;
    let totalWithGst = 0;
    const add = (val: any) => basicValue += parseFloat(val) || 0;
    const addGst = (val: any) => totalWithGst += parseFloat(val) || 0;
    add(rec.basic_invoice_value); add(rec.inv_value_in_rs);
    add(rec.ocean_air_freight_amount); add(rec.insurance_value); add(rec.miscellaneous_charge);
    add(rec.assessable_value); add(rec.bcd_value); add(rec.sws_value); add(rec.other_charges); add(rec.total_duty_value);
    add(rec.bill_amount); addGst(rec.cif_term_amount_with_gst);
    add(rec.cfs_total_bill_amount); addGst(rec.cfs_cif_term_amount_with_gst);
    add(rec.origin_charges); add(rec.ocean_freight); add(rec.destination_charges); addGst(rec.total_forwarder_amount);
    add(rec.cha_clearing_agent_fees); add(rec.document_fee); add(rec.loading_and_unloading); add(rec.stamp_duty); addGst(rec.total_cha_with_gst);
    add(rec.inland_transportation); add(rec.unloading_charges); addGst(rec.total_transport_amount_with_gst);
    add(rec.paid_amount); addGst(rec.total_paid_with_gst);
    add(rec.bill_value); addGst(rec.total_warehouse_amount_with_gst);
    add(rec.inspection_survey_fees); addGst(rec.total_amount_with_gst);
    rec.calculated_total_basic_value = basicValue;
    rec.calculated_total_gst = totalWithGst - basicValue;
    const qty = parseFloat(rec.quantity || rec.qty) || 0;
    rec.calculated_landed_cost_per_unit = qty > 0 ? (basicValue + (totalWithGst - basicValue)) / qty : 0;
    return rec;
  }

  /** Export Excel for a given record (used from table row or modal). */
  downloadExcelForRecord(record: any): void {
    if (!record) {
      alertify.warning('No record to export.');
      return;
    }
    this.buildAndDownloadExcel(this.getRecordWithTotals(record));
  }

  downloadViewLogExcel(): void {
    if (!this.selectedRecord) {
      alertify.warning('No record selected.');
      return;
    }
    this.calculateTotals();
    this.buildAndDownloadExcel(this.selectedRecord);
  }

  private buildAndDownloadExcel(r: any): void {
    const v = (x: any) => (x == null || x === '') ? 'N/A' : String(x);
    const n = (x: any) => (x == null || x === '') ? 0 : (parseFloat(x) || 0);

    const sections: { title: string; color: string; rows: { label: string; value: string | number }[] }[] = [
      {
        title: 'Basic Information',
        color: 'FF0f766e',
        rows: [
          { label: 'PO No', value: v(r.po_no) },
          { label: 'Challan No', value: v(r.challan_no) },
          { label: 'Receiving no', value: v(r.grn_no) },
          { label: 'Item Code', value: v(r.item_code || r.material_code) },
          { label: 'Item Description', value: v(r.item_description || r.material_name) },
          { label: 'Quantity', value: n(r.quantity || r.qty) },
          { label: 'UOM', value: v(r.uom || r.unit) },
        ],
      },
      {
        title: 'Import Details',
        color: 'FF1e40af',
        rows: [
          { label: 'Date', value: v(r.grn_date) },
          { label: 'Supplier Name', value: v(r.supplier_name || r.import_supplier_name) },
          { label: 'Invoice Date', value: v(r.inv_date || r.invDate) },
          { label: 'Invoice No', value: v(r.inv_no || r.invNo) },
          { label: 'Basic Invoice Value', value: n(r.basic_invoice_value) },
          { label: 'Invoice Value in RS', value: n(r.inv_value_in_rs) },
          { label: 'Port of Loading', value: v(r.port_of_loading) },
          { label: 'Port of Discharge', value: v(r.port_of_discharge) },
        ],
      },
      {
        title: 'International Logistics',
        color: 'FF7c3aed',
        rows: [
          { label: 'Ocean/Air Freight Amount', value: n(r.ocean_air_freight_amount) },
          { label: 'Insurance Value', value: n(r.insurance_value) },
          { label: 'Miscellaneous Charge', value: n(r.miscellaneous_charge) },
          { label: 'CIF Value', value: n(r.cif_value) },
        ],
      },
      {
        title: 'Customs Import',
        color: 'FFb91c1c',
        rows: [
          { label: 'Assessable Value', value: n(r.assessable_value) },
          { label: 'BCD Value', value: n(r.bcd_value) },
          { label: 'SWS Value', value: n(r.sws_value) },
          { label: 'Total Duty Value', value: n(r.total_duty_value) },
          { label: 'GST Value', value: n(r.gst_value) },
          { label: 'Total Paid Custom Duty', value: n(r.total_paid_custom_duty) },
        ],
      },
      {
        title: 'Agency Bills',
        color: 'FFc2410c',
        rows: [
          { label: 'Agency Name', value: v(r.agency_name) },
          { label: 'Agency Bill No', value: v(r.agency_bill_no) },
          { label: 'Bill Date', value: v(r.bill_date) },
          { label: 'Bill Amount', value: n(r.bill_amount) },
          { label: 'GST %', value: n(r.agency_gst_percent) },
          { label: 'CIF Term Amount with GST', value: n(r.cif_term_amount_with_gst) },
        ],
      },
      {
        title: 'CFS (Container Freight Station)',
        color: 'FF047857',
        rows: [
          { label: 'CFS Agency Name', value: v(r.cfs_agency_name) },
          { label: 'Bill Count', value: n(r.cfs_bill_count) },
          { label: 'Bill Date', value: v(r.cfs_bill_date) },
          { label: 'Total Bill Amount', value: n(r.cfs_total_bill_amount) },
          { label: 'GST %', value: n(r.cfs_gst_percent) },
          { label: 'CIF Term Amount with GST', value: n(r.cfs_cif_term_amount_with_gst) },
        ],
      },
      {
        title: 'Freight Forwarder',
        color: 'FF4f46e5',
        rows: [
          { label: 'Forwarder Name', value: v(r.forwarder_name) },
          { label: 'Forwarder Inv Date', value: v(r.forwarder_inv_date) },
          { label: 'Forwarder Inv No', value: v(r.forwarder_inv_no) },
          { label: 'Origin Charges', value: n(r.origin_charges) },
          { label: 'Ocean Freight', value: n(r.ocean_freight) },
          { label: 'Destination Charges', value: n(r.destination_charges) },
          { label: 'Total Forwarder Amount', value: n(r.total_forwarder_amount) },
        ],
      },
      {
        title: 'Destination Clearing (CHA)',
        color: 'FF0d9488',
        rows: [
          { label: 'CHA Name', value: v(r.cha_name) },
          { label: 'Inv Date', value: v(r.destination_clearing_inv_date) },
          { label: 'Inv No', value: v(r.destination_clearing_inv_no) },
          { label: 'CHA/Clearing Agent Fees', value: n(r.cha_clearing_agent_fees) },
          { label: 'Document Fee', value: n(r.document_fee) },
          { label: 'Loading and Unloading', value: n(r.loading_and_unloading) },
          { label: 'Stamp Duty', value: n(r.stamp_duty) },
          { label: 'Total CHA with GST', value: n(r.total_cha_with_gst) },
        ],
      },
      {
        title: 'Transport',
        color: 'FFdc2626',
        rows: [
          { label: 'Transport Name', value: v(r.transport_name) },
          { label: 'Transport Bill No', value: v(r.transport_bill_no) },
          { label: 'Transport Bill Date', value: v(r.transport_bill_date) },
          { label: 'Inland Transportation', value: n(r.inland_transportation) },
          { label: 'Unloading Charges', value: n(r.unloading_charges) },
          { label: 'Total Transport Amount with GST', value: n(r.total_transport_amount_with_gst) },
        ],
      },
      {
        title: 'Payment Bank',
        color: 'FF2563eb',
        rows: [
          { label: 'Ref Document No', value: v(r.ref_document_no) },
          { label: 'Bank Name', value: v(r.payment_bank_name) },
          { label: 'Paid Amount', value: n(r.paid_amount) },
          { label: 'Paid Date', value: v(r.paid_date) },
          { label: 'Total Paid With GST', value: n(r.total_paid_with_gst) },
        ],
      },
      {
        title: 'Warehousing',
        color: 'FF9333ea',
        rows: [
          { label: 'Agency Name', value: v(r.warehousing_agency_name) },
          { label: 'Agency Bill No', value: v(r.agency_bill_no) },
          { label: 'Agency Bill Date', value: v(r.agency_bill_date) },
          { label: 'Bill Value', value: n(r.bill_value) },
          { label: 'Total Warehouse Amount with GST', value: n(r.total_warehouse_amount_with_gst) },
        ],
      },
      {
        title: 'Inhand',
        color: 'FFea580c',
        rows: [
          { label: 'Agency Name', value: v(r.inhand_agency_name) },
          { label: 'Agency Bill No', value: v(r.agency_bill_no) },
          { label: 'Agency Bill Date', value: v(r.agency_bill_date) },
          { label: 'Inspection / Survey Fees', value: n(r.inspection_survey_fees) },
          { label: 'Total Amount with GST', value: n(r.total_amount_with_gst) },
        ],
      },
      {
        title: 'Final Calculations (Auto-calculated)',
        color: 'FF059669',
        rows: [
          { label: 'Total Basic Value', value: n(r.calculated_total_basic_value) },
          { label: 'Total GST', value: n(r.calculated_total_gst) },
          { label: 'Landed Cost Per Unit', value: n(r.calculated_landed_cost_per_unit) },
        ],
      },
    ];

    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Complete Log', { views: [{ state: 'frozen', ySplit: 2 }] });

    ws.columns = [
      { width: 38 },
      { width: 32 },
    ];

    let rowNum = 1;

    ws.mergeCells(rowNum, 1, rowNum, 2);
    const titleCell = ws.getCell(rowNum, 1);
    titleCell.value = 'Complete Log - All Components Data';
    titleCell.font = { bold: true, size: 14 };
    titleCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0f766e' } };
    titleCell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
    titleCell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
    rowNum += 2;

    for (const sec of sections) {
      ws.mergeCells(rowNum, 1, rowNum, 2);
      const headerCell = ws.getCell(rowNum, 1);
      headerCell.value = sec.title;
      headerCell.font = { bold: true, size: 11, color: { argb: 'FFFFFFFF' } };
      headerCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: sec.color } };
      headerCell.alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };
      headerCell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
      rowNum++;

      for (const row of sec.rows) {
        const cellA = ws.getCell(rowNum, 1);
        const cellB = ws.getCell(rowNum, 2);
        cellA.value = row.label;
        cellB.value = row.value;
        cellA.font = { bold: true, size: 10 };
        cellA.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFf1f5f9' } };
        cellA.alignment = { vertical: 'middle', wrapText: true };
        cellB.alignment = { vertical: 'middle', wrapText: true };
        cellA.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
        cellB.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
        rowNum++;
      }
      rowNum++;
    }

    wb.xlsx.writeBuffer().then((buffer: ArrayBuffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `Landed_Log_${v(r.po_no)}_${v(r.challan_no)}.xlsx`;
      a.click();
      URL.revokeObjectURL(url);
      alertify.success('Excel downloaded.');
    }).catch(() => alertify.error('Failed to generate Excel.'));
  }
}
