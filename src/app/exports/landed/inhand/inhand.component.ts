import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-inhand',
  templateUrl: './inhand.component.html',
  styleUrls: ['./inhand.component.css']
})
export class InhandComponent implements OnInit {

  importData: any[] = [];
  loading = false;
  isView = false;
  selectedRecord: any = {};

  // Form fields
  poNo: string = '';
  challanNo: string = '';
  grnNo: string = '';
  itemCode: string = '';
  itemDescription: string = '';
  quantity: number = 0;
  uom: string = '';
  
  // Inhand fields
  agencyName: string = '';
  agencyBillNo: string = '';
  agencyBillDate: string = '';
  inspectionSurveyFees: number = 0;
  gstPercent: number = 0;
  totalAmountWithGst: number = 0;

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
    
    // Populate form fields
    this.poNo = this.selectedRecord.po_no || '';
    this.challanNo = this.selectedRecord.challan_no || '';
    this.grnNo = this.selectedRecord.grn_no || '';
    this.itemCode = this.selectedRecord.item_code || this.selectedRecord.material_code || '';
    this.itemDescription = this.selectedRecord.item_description || this.selectedRecord.material_name || '';
    this.quantity = this.selectedRecord.quantity || this.selectedRecord.qty ? parseFloat(this.selectedRecord.quantity || this.selectedRecord.qty) : 0;
    this.uom = this.selectedRecord.uom || this.selectedRecord.unit || '';
    
    // Load existing inhand data if available
    if (this.selectedRecord.inhand_id) {
      this.agencyName = this.selectedRecord.inhand_agency_name || '';
      this.agencyBillNo = this.selectedRecord.agency_bill_no || '';
      this.agencyBillDate = this.selectedRecord.agency_bill_date || '';
      this.inspectionSurveyFees = this.selectedRecord.inspection_survey_fees ? parseFloat(this.selectedRecord.inspection_survey_fees) : 0;
      this.gstPercent = this.selectedRecord.inhand_gst_percent ? parseFloat(this.selectedRecord.inhand_gst_percent) : 0;
      this.totalAmountWithGst = this.selectedRecord.total_amount_with_gst ? parseFloat(this.selectedRecord.total_amount_with_gst) : 0;
    }
    
    this.calculateValues();
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedRecord = {};
    this.resetForm();
  }

  calculateValues() {
    // Calculate Total Amount with GST = Inspection/Survey Fees + (Inspection/Survey Fees * GST % / 100)
    if (this.inspectionSurveyFees > 0 && this.gstPercent >= 0) {
      this.totalAmountWithGst = this.inspectionSurveyFees + (this.inspectionSurveyFees * this.gstPercent / 100);
    } else {
      this.totalAmountWithGst = 0;
    }
  }

  save(form: any) {
    const data = {
      id: this.selectedRecord.inhand_id || '',
      po_no: this.poNo,
      challan_no: this.challanNo,
      grn_no: this.grnNo,
      item_code: this.itemCode,
      item_description: this.itemDescription,
      quantity: this.quantity,
      uom: this.uom,
      agency_name: this.agencyName,
      agency_bill_no: this.agencyBillNo,
      agency_bill_date: this.agencyBillDate,
      inspection_survey_fees: this.inspectionSurveyFees,
      gst_percent: this.gstPercent,
      total_amount_with_gst: this.totalAmountWithGst
    };

    this.service.post('exports/exports.php?type=saveInhand', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Inhand saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving inhand');
      }
    }, error => {
      console.error('Error saving inhand:', error);
      alertify.error('Error saving inhand');
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
    this.agencyName = '';
    this.agencyBillNo = '';
    this.agencyBillDate = '';
    this.inspectionSurveyFees = 0;
    this.gstPercent = 0;
    this.totalAmountWithGst = 0;
  }

  refresh() {
    this.getImportData();
  }
}
