import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-cfs',
  templateUrl: './cfs.component.html',
  styleUrls: ['./cfs.component.css']
})
export class CfsComponent implements OnInit {

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
  
  // CFS fields
  agencyName: string = '';
  billCount: number = 0;
  billDate: string = '';
  totalBillAmount: number = 0;
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
    
    // Load existing CFS data if available
    if (this.selectedRecord.cfs_id) {
      this.agencyName = this.selectedRecord.cfs_agency_name || '';
      this.billCount = this.selectedRecord.cfs_bill_count ? parseFloat(this.selectedRecord.cfs_bill_count) : 0;
      this.billDate = this.selectedRecord.cfs_bill_date || '';
      this.totalBillAmount = this.selectedRecord.cfs_total_bill_amount ? parseFloat(this.selectedRecord.cfs_total_bill_amount) : 0;
      this.gstPercent = this.selectedRecord.cfs_gst_percent ? parseFloat(this.selectedRecord.cfs_gst_percent) : 0;
      this.cifTermAmountWithGst = this.selectedRecord.cfs_cif_term_amount_with_gst ? parseFloat(this.selectedRecord.cfs_cif_term_amount_with_gst) : 0;
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
    // Calculate CIF Term Amount with GST = Total Bill Amount + (Total Bill Amount * GST % / 100)
    if (this.totalBillAmount > 0 && this.gstPercent >= 0) {
      this.cifTermAmountWithGst = this.totalBillAmount + (this.totalBillAmount * this.gstPercent / 100);
    } else {
      this.cifTermAmountWithGst = 0;
    }
  }

  save(form: any) {
    const data = {
      id: this.selectedRecord.cfs_id || '',
      po_no: this.poNo,
      challan_no: this.challanNo,
      grn_no: this.grnNo,
      item_code: this.itemCode,
      item_description: this.itemDescription,
      quantity: this.quantity,
      uom: this.uom,
      agency_name: this.agencyName,
      bill_count: this.billCount,
      bill_date: this.billDate,
      total_bill_amount: this.totalBillAmount,
      gst_percent: this.gstPercent,
      cif_term_amount_with_gst: this.cifTermAmountWithGst
    };

    this.service.post('exports/exports.php?type=saveCFS', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('CFS saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving CFS');
      }
    }, error => {
      console.error('Error saving CFS:', error);
      alertify.error('Error saving CFS');
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
    this.billCount = 0;
    this.billDate = '';
    this.totalBillAmount = 0;
    this.gstPercent = 0;
    this.cifTermAmountWithGst = 0;
  }

  refresh() {
    this.getImportData();
  }
}
