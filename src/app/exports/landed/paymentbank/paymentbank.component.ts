import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-paymentbank',
  templateUrl: './paymentbank.component.html',
  styleUrls: ['./paymentbank.component.css']
})
export class PaymentbankComponent implements OnInit {

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
  
  // Payment Bank fields
  refDocumentNo: string = '';
  bankName: string = '';
  paidAmount: number = 0;
  paidDate: string = '';
  gstPercent: number = 0;
  totalPaidWithGst: number = 0;

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
    
    // Load existing payment bank data if available
    if (this.selectedRecord.payment_bank_id) {
      this.refDocumentNo = this.selectedRecord.ref_document_no || '';
      this.bankName = this.selectedRecord.payment_bank_name || '';
      this.paidAmount = this.selectedRecord.paid_amount ? parseFloat(this.selectedRecord.paid_amount) : 0;
      this.paidDate = this.selectedRecord.paid_date || '';
      this.gstPercent = this.selectedRecord.payment_bank_gst_percent ? parseFloat(this.selectedRecord.payment_bank_gst_percent) : 0;
      this.totalPaidWithGst = this.selectedRecord.total_paid_with_gst ? parseFloat(this.selectedRecord.total_paid_with_gst) : 0;
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
    // Calculate Total Paid With GST = Paid Amount + (Paid Amount * GST % / 100)
    if (this.paidAmount > 0 && this.gstPercent >= 0) {
      this.totalPaidWithGst = this.paidAmount + (this.paidAmount * this.gstPercent / 100);
    } else {
      this.totalPaidWithGst = 0;
    }
  }

  save(form: any) {
    const data = {
      id: this.selectedRecord.payment_bank_id || '',
      po_no: this.poNo,
      challan_no: this.challanNo,
      grn_no: this.grnNo,
      item_code: this.itemCode,
      item_description: this.itemDescription,
      quantity: this.quantity,
      uom: this.uom,
      ref_document_no: this.refDocumentNo,
      bank_name: this.bankName,
      paid_amount: this.paidAmount,
      paid_date: this.paidDate,
      gst_percent: this.gstPercent,
      total_paid_with_gst: this.totalPaidWithGst
    };

    this.service.post('exports/exports.php?type=savePaymentBank', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Payment bank saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving payment bank');
      }
    }, error => {
      console.error('Error saving payment bank:', error);
      alertify.error('Error saving payment bank');
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
    this.refDocumentNo = '';
    this.bankName = '';
    this.paidAmount = 0;
    this.paidDate = '';
    this.gstPercent = 0;
    this.totalPaidWithGst = 0;
  }

  refresh() {
    this.getImportData();
  }
}
