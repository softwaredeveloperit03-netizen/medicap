import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-destination-clearing',
  templateUrl: './destination-clearing.component.html',
  styleUrls: ['./destination-clearing.component.css']
})
export class DestinationClearingComponent implements OnInit {

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
  
  // Destination Clearing (CHA) fields
  chaName: string = '';
  invDate: string = '';
  invNo: string = '';
  chaClearingAgentFees: number = 0;
  documentFee: number = 0;
  loadingAndUnloading: number = 0;
  otherCharges: number = 0;
  gstPercent: number = 0;
  stampDuty: number = 0;
  stampDutyGstPercent: number = 0;
  totalChaWithGst: number = 0;

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
    
    // Load existing destination clearing data if available
    if (this.selectedRecord.destination_clearing_id) {
      this.chaName = this.selectedRecord.cha_name || '';
      this.invDate = this.selectedRecord.destination_clearing_inv_date || '';
      this.invNo = this.selectedRecord.destination_clearing_inv_no || '';
      this.chaClearingAgentFees = this.selectedRecord.cha_clearing_agent_fees ? parseFloat(this.selectedRecord.cha_clearing_agent_fees) : 0;
      this.documentFee = this.selectedRecord.document_fee ? parseFloat(this.selectedRecord.document_fee) : 0;
      this.loadingAndUnloading = this.selectedRecord.loading_and_unloading ? parseFloat(this.selectedRecord.loading_and_unloading) : 0;
      this.otherCharges = this.selectedRecord.other_charges ? parseFloat(this.selectedRecord.other_charges) : 0;
      this.gstPercent = this.selectedRecord.destination_clearing_gst_percent ? parseFloat(this.selectedRecord.destination_clearing_gst_percent) : 0;
      this.stampDuty = this.selectedRecord.stamp_duty ? parseFloat(this.selectedRecord.stamp_duty) : 0;
      this.stampDutyGstPercent = this.selectedRecord.stamp_duty_gst_percent ? parseFloat(this.selectedRecord.stamp_duty_gst_percent) : 0;
      this.totalChaWithGst = this.selectedRecord.total_cha_with_gst ? parseFloat(this.selectedRecord.total_cha_with_gst) : 0;
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
    // Calculate subtotal before GST
    const subtotal = this.chaClearingAgentFees + this.documentFee + this.loadingAndUnloading + this.otherCharges;
    
    // Calculate GST amount on subtotal
    const gstAmount = subtotal > 0 && this.gstPercent >= 0 ? (subtotal * this.gstPercent / 100) : 0;
    
    // Calculate subtotal with GST
    const subtotalWithGst = subtotal + gstAmount;
    
    // Calculate Stamp Duty with GST
    const stampDutyWithGst = this.stampDuty > 0 && this.stampDutyGstPercent >= 0 
      ? this.stampDuty + (this.stampDuty * this.stampDutyGstPercent / 100) 
      : 0;
    
    // Calculate Total CHA with GST = Subtotal with GST + Stamp Duty with GST
    this.totalChaWithGst = subtotalWithGst + stampDutyWithGst;
  }

  save(form: any) {
    const data = {
      id: this.selectedRecord.destination_clearing_id || '',
      po_no: this.poNo,
      challan_no: this.challanNo,
      grn_no: this.grnNo,
      item_code: this.itemCode,
      item_description: this.itemDescription,
      quantity: this.quantity,
      uom: this.uom,
      cha_name: this.chaName,
      inv_date: this.invDate,
      inv_no: this.invNo,
      cha_clearing_agent_fees: this.chaClearingAgentFees,
      document_fee: this.documentFee,
      loading_and_unloading: this.loadingAndUnloading,
      other_charges: this.otherCharges,
      gst_percent: this.gstPercent,
      stamp_duty: this.stampDuty,
      stamp_duty_gst_percent: this.stampDutyGstPercent,
      total_cha_with_gst: this.totalChaWithGst
    };

    this.service.post('exports/exports.php?type=saveDestinationClearing', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Destination clearing saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving destination clearing');
      }
    }, error => {
      console.error('Error saving destination clearing:', error);
      alertify.error('Error saving destination clearing');
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
    this.chaName = '';
    this.invDate = '';
    this.invNo = '';
    this.chaClearingAgentFees = 0;
    this.documentFee = 0;
    this.loadingAndUnloading = 0;
    this.otherCharges = 0;
    this.gstPercent = 0;
    this.stampDuty = 0;
    this.stampDutyGstPercent = 0;
    this.totalChaWithGst = 0;
  }

  refresh() {
    this.getImportData();
  }
}
