import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-dutypayment',
  templateUrl: './dutypayment.component.html',
  styleUrls: ['./dutypayment.component.css']
})
export class DutypaymentComponent implements OnInit {

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

  // Payment fields
  icegateRefNo: string = '';
  paymentDateTime: string = '';
  bankTransactionNo: string = '';
  documentNo: string = '';
  challanNoPayment: string = '';
  paymentAmount: number = 0;
  paymentStatus: string = 'Pending';
  paymentMode: string = '';
  bankName: string = '';
  remarks: string = '';

  // Payment modes
  paymentModes: string[] = ['Online', 'NEFT', 'RTGS', 'IMPS', 'Cheque', 'DD', 'Cash', 'Other'];
  paymentStatuses: string[] = ['Pending', 'Paid', 'Failed', 'Cancelled'];

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

    // Populate basic info
    this.poNo = this.selectedRecord.po_no || '';
    this.challanNo = this.selectedRecord.challan_no || '';
    this.grnNo = this.selectedRecord.grn_no || '';
    this.itemCode = this.selectedRecord.item_code || this.selectedRecord.material_code || '';
    this.itemDescription = this.selectedRecord.item_description || this.selectedRecord.material_name || '';
    this.quantity = this.selectedRecord.quantity || this.selectedRecord.qty ? parseFloat(this.selectedRecord.quantity || this.selectedRecord.qty) : 0;
    this.uom = this.selectedRecord.uom || this.selectedRecord.unit || '';

    // Load existing payment data if available
    if (this.selectedRecord.duty_payment_id) {
      this.icegateRefNo = this.selectedRecord.icegate_ref_no || '';
      this.paymentDateTime = this.selectedRecord.payment_date_time || '';
      this.bankTransactionNo = this.selectedRecord.bank_transaction_no || '';
      this.documentNo = this.selectedRecord.document_no || '';
      this.challanNoPayment = this.selectedRecord.challan_no_payment || '';
      this.paymentAmount = this.selectedRecord.payment_amount ? parseFloat(this.selectedRecord.payment_amount) : 0;
      this.paymentStatus = this.selectedRecord.payment_status || 'Pending';
      this.paymentMode = this.selectedRecord.payment_mode || '';
      this.bankName = this.selectedRecord.bank_name || '';
      this.remarks = this.selectedRecord.remarks || '';
    } else {
      // Set default payment amount from customs import if available
      if (this.selectedRecord.total_paid_custom_duty) {
        this.paymentAmount = parseFloat(this.selectedRecord.total_paid_custom_duty);
      }
    }

    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedRecord = {};
    this.resetForm();
  }

  save(form: any) {
    const data = {
      id: this.selectedRecord.duty_payment_id || '',
      po_no: this.poNo,
      challan_no: this.challanNo,
      grn_no: this.grnNo,
      item_code: this.itemCode,
      item_description: this.itemDescription,
      quantity: this.quantity,
      uom: this.uom,
      icegate_ref_no: this.icegateRefNo,
      payment_date_time: this.paymentDateTime,
      bank_transaction_no: this.bankTransactionNo,
      document_no: this.documentNo,
      challan_no_payment: this.challanNoPayment,
      payment_amount: this.paymentAmount,
      payment_status: this.paymentStatus,
      payment_mode: this.paymentMode,
      bank_name: this.bankName,
      remarks: this.remarks
    };

    this.service.post('exports/exports.php?type=saveDutyPayment', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Duty payment saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving duty payment');
      }
    }, error => {
      console.error('Error saving duty payment:', error);
      alertify.error('Error saving duty payment');
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
    this.icegateRefNo = '';
    this.paymentDateTime = '';
    this.bankTransactionNo = '';
    this.documentNo = '';
    this.challanNoPayment = '';
    this.paymentAmount = 0;
    this.paymentStatus = 'Pending';
    this.paymentMode = '';
    this.bankName = '';
    this.remarks = '';
  }

  refresh() {
    this.getImportData();
  }
}
