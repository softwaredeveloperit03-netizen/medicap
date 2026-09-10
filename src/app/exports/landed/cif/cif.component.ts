import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-cif',
  templateUrl: './cif.component.html',
  styleUrls: ['./cif.component.css']
})
export class CifComponent implements OnInit {

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
  
  // Agency bill fields
  agencyName: string = '';
  agencyBillNo: string = '';
  billDate: string = '';
  billAmount: number = 0;
  gstPercent: number = 0;
  cifTermAmountWithGst: number = 0;

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
    
    // Load existing agency bill data if available
    if (this.selectedRecord.agency_bills_id) {
      this.agencyName = this.selectedRecord.agency_name || '';
      this.agencyBillNo = this.selectedRecord.agency_bill_no || '';
      this.billDate = this.selectedRecord.bill_date || '';
      this.billAmount = this.selectedRecord.bill_amount ? parseFloat(this.selectedRecord.bill_amount) : 0;
      this.gstPercent = this.selectedRecord.gst_percent ? parseFloat(this.selectedRecord.gst_percent) : 0;
      this.cifTermAmountWithGst = this.selectedRecord.cif_term_amount_with_gst ? parseFloat(this.selectedRecord.cif_term_amount_with_gst) : 0;
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
    // Calculate CIF Term Amount with GST = Bill Amount + (Bill Amount * GST % / 100)
    if (this.billAmount > 0 && this.gstPercent >= 0) {
      this.cifTermAmountWithGst = this.billAmount + (this.billAmount * this.gstPercent / 100);
    } else {
      this.cifTermAmountWithGst = 0;
    }
  }

  save(form: any) {
    const data = {
      id: this.selectedRecord.agency_bills_id || '',
      po_no: this.poNo,
      challan_no: this.challanNo,
      grn_no: this.grnNo,
      item_code: this.itemCode,
      item_description: this.itemDescription,
      quantity: this.quantity,
      uom: this.uom,
      agency_name: this.agencyName,
      agency_bill_no: this.agencyBillNo,
      bill_date: this.billDate,
      bill_amount: this.billAmount,
      gst_percent: this.gstPercent,
      cif_term_amount_with_gst: this.cifTermAmountWithGst
    };

    this.service.post('exports/exports.php?type=saveAgencyBills', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Agency bills saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving agency bills');
      }
    }, error => {
      console.error('Error saving agency bills:', error);
      alertify.error('Error saving agency bills');
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
    this.billDate = '';
    this.billAmount = 0;
    this.gstPercent = 0;
    this.cifTermAmountWithGst = 0;
  }

  refresh() {
    this.getImportData();
  }
}
