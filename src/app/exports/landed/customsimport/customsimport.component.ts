import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-customsimport',
  templateUrl: './customsimport.component.html',
  styleUrls: ['./customsimport.component.css']
})
export class CustomsimportComponent implements OnInit {

  importData: any[] = [];
  loading = false;
  isView = false;
  selectedRecord: any = {};

  // Basic info
  poNo: string = '';
  challanNo: string = '';
  grnNo: string = '';
  itemCode: string = '';
  itemDescription: string = '';
  quantity: number = 0;
  uom: string = '';

  // Values
  assessableValue: number = 0; // CIF assessable value
  bcdPercent: number = 0;
  bcdValue: number = 0;
  swsPercent: number = 0;
  swsValue: number = 0;
  otherCharges: number = 0;
  totalDutyValue: number = 0; // TCD
  gstPercent: number = 0;
  gstValue: number = 0; // IGST / VAT / GST
  assessableValueGstBase: number = 0; // Assessable + TCD (GST base)
  antiDumpingDuty: number = 0;
  penaltyPercent: number = 0;
  penaltyValue: number = 0;
  advanceLicenseNo: string = '';
  advanceLicenseBenefits: number = 0;
  roadTapeNo: string = '';
  rtBenefits: number = 0;
  totalPaidCustomDuty: number = 0;
  qtyValue: number = 0;

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

    this.poNo = this.selectedRecord.po_no || '';
    this.challanNo = this.selectedRecord.challan_no || '';
    this.grnNo = this.selectedRecord.grn_no || '';
    this.itemCode = this.selectedRecord.item_code || this.selectedRecord.material_code || '';
    this.itemDescription = this.selectedRecord.item_description || this.selectedRecord.material_name || '';
    this.quantity = this.selectedRecord.quantity || this.selectedRecord.qty ? parseFloat(this.selectedRecord.quantity || this.selectedRecord.qty) : 0;
    this.uom = this.selectedRecord.uom || this.selectedRecord.unit || '';
    this.assessableValue = this.selectedRecord.assessable_value_cif
      ? parseFloat(this.selectedRecord.assessable_value_cif)
      : (this.selectedRecord.basic_invoice_value ? parseFloat(this.selectedRecord.basic_invoice_value) : 0);
    this.assessableValueGstBase = this.selectedRecord.assessable_value_gst_base
      ? parseFloat(this.selectedRecord.assessable_value_gst_base)
      : 0;
    this.qtyValue = this.selectedRecord.qty_value ? parseFloat(this.selectedRecord.qty_value) : 0;

    // Load existing customs data if present
    if (this.selectedRecord.customs_id) {
      this.bcdPercent = this.selectedRecord.bcd_percent ? parseFloat(this.selectedRecord.bcd_percent) : 0;
      this.bcdValue = this.selectedRecord.bcd_value ? parseFloat(this.selectedRecord.bcd_value) : 0;
      this.swsPercent = this.selectedRecord.sws_percent ? parseFloat(this.selectedRecord.sws_percent) : 0;
      this.swsValue = this.selectedRecord.sws_value ? parseFloat(this.selectedRecord.sws_value) : 0;
      this.otherCharges = this.selectedRecord.other_charges ? parseFloat(this.selectedRecord.other_charges) : 0;
      this.totalDutyValue = this.selectedRecord.total_duty_value ? parseFloat(this.selectedRecord.total_duty_value) : 0;
      this.gstPercent = this.selectedRecord.gst_percent ? parseFloat(this.selectedRecord.gst_percent) : 0;
      this.gstValue = this.selectedRecord.gst_value ? parseFloat(this.selectedRecord.gst_value) : 0;
      this.assessableValueGstBase = this.selectedRecord.assessable_value_gst_base ? parseFloat(this.selectedRecord.assessable_value_gst_base) : 0;
      this.qtyValue = this.selectedRecord.qty_value ? parseFloat(this.selectedRecord.qty_value) : 0;
      this.antiDumpingDuty = this.selectedRecord.anti_dumping_duty ? parseFloat(this.selectedRecord.anti_dumping_duty) : 0;
      this.penaltyPercent = this.selectedRecord.penalty_percent ? parseFloat(this.selectedRecord.penalty_percent) : 0;
      this.penaltyValue = this.selectedRecord.penalty_value ? parseFloat(this.selectedRecord.penalty_value) : 0;
      this.advanceLicenseNo = this.selectedRecord.advance_license_no || '';
      this.advanceLicenseBenefits = this.selectedRecord.advance_license_benefits ? parseFloat(this.selectedRecord.advance_license_benefits) : 0;
      this.roadTapeNo = this.selectedRecord.road_tape_no || '';
      this.rtBenefits = this.selectedRecord.rt_benefits ? parseFloat(this.selectedRecord.rt_benefits) : 0;
      this.totalPaidCustomDuty = this.selectedRecord.total_paid_custom_duty ? parseFloat(this.selectedRecord.total_paid_custom_duty) : 0;
    } else {
      // reset specific customs fields
      this.bcdPercent = 0;
      this.swsPercent = 0;
      this.otherCharges = 0;
      this.gstPercent = 0;
      this.antiDumpingDuty = 0;
      this.penaltyPercent = 0;
      this.advanceLicenseNo = '';
      this.advanceLicenseBenefits = 0;
      this.roadTapeNo = '';
      this.rtBenefits = 0;
    }

    this.calculateValues();
    this.isView = true;
  }

  calculateValues() {
    // BCD value
    this.bcdValue = this.assessableValue * (this.bcdPercent || 0) / 100;
    // SWS value
    this.swsValue = this.bcdValue * (this.swsPercent || 0) / 100;
    // Total Duty Value (TCD)
    this.totalDutyValue = this.bcdValue + this.swsValue + (this.otherCharges || 0);
    // GST base = Assessable + TCD
    this.assessableValueGstBase = this.assessableValue + this.totalDutyValue;
    // Penalty Value
    this.penaltyValue = this.assessableValue * (this.penaltyPercent || 0) / 100;
    // GST/IGST value on (assessable + TCD) + anti-dumping + penalty
    const gstBase = this.assessableValueGstBase + (this.antiDumpingDuty || 0) + this.penaltyValue;
    this.gstValue = gstBase * (this.gstPercent || 0) / 100;
    // Total Paid Custom Duty = TCD + GST + Penalty - Advance License Benefits - RT Benefits
    this.totalPaidCustomDuty = this.totalDutyValue + this.gstValue + this.penaltyValue - (this.advanceLicenseBenefits || 0) - (this.rtBenefits || 0);
  }

  closeView() {
    this.isView = false;
    this.selectedRecord = {};
    this.resetForm();
  }

  save(form: any) {
    const data = {
      id: this.selectedRecord.customs_id || '',
      po_no: this.poNo,
      challan_no: this.challanNo,
      grn_no: this.grnNo,
      item_code: this.itemCode,
      item_description: this.itemDescription,
      quantity: this.quantity,
      uom: this.uom,
      assessable_value: this.assessableValue,
      bcd_percent: this.bcdPercent,
      bcd_value: this.bcdValue,
      sws_percent: this.swsPercent,
      sws_value: this.swsValue,
      other_charges: this.otherCharges,
      total_duty_value: this.totalDutyValue,
      gst_percent: this.gstPercent,
      gst_value: this.gstValue,
      assessable_value_gst_base: this.assessableValueGstBase,
      anti_dumping_duty: this.antiDumpingDuty,
      penalty_percent: this.penaltyPercent,
      penalty_value: this.penaltyValue,
      advance_license_no: this.advanceLicenseNo,
      advance_license_benefits: this.advanceLicenseBenefits,
      road_tape_no: this.roadTapeNo,
      rt_benefits: this.rtBenefits,
      total_paid_custom_duty: this.totalPaidCustomDuty,
      qty_value: this.qtyValue
    };

    this.service.post('exports/exports.php?type=saveCustomsImport', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Customs import saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving customs import');
      }
    }, error => {
      console.error('Error saving customs import:', error);
      alertify.error('Error saving customs import');
    });
  }

  resetForm() {
    this.poNo = '';
    this.challanNo = '';
    this.grnNo = '';
    this.itemCode = '';
    this.itemDescription = '';
    this.quantity = 0;
    this.uom = '';
    this.assessableValue = 0;
    this.bcdPercent = 0;
    this.bcdValue = 0;
    this.swsPercent = 0;
    this.swsValue = 0;
    this.otherCharges = 0;
    this.totalDutyValue = 0;
    this.gstPercent = 0;
    this.gstValue = 0;
    this.assessableValueGstBase = 0;
    this.antiDumpingDuty = 0;
    this.penaltyPercent = 0;
    this.penaltyValue = 0;
    this.advanceLicenseNo = '';
    this.advanceLicenseBenefits = 0;
    this.roadTapeNo = '';
    this.rtBenefits = 0;
    this.totalPaidCustomDuty = 0;
    this.qtyValue = 0;
  }

  refresh() {
    this.getImportData();
  }
}
