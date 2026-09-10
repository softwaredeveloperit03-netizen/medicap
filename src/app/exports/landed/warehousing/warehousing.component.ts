import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-warehousing',
  templateUrl: './warehousing.component.html',
  styleUrls: ['./warehousing.component.css']
})
export class WarehousingComponent implements OnInit {

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
  
  // Warehousing fields
  agencyName: string = '';
  agencyBillNo: string = '';
  agencyBillDate: string = '';
  billValue: number = 0;
  gstPercent: number = 0;
  totalWarehouseAmountWithGst: number = 0;

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
    
    // Load existing warehousing data if available
    if (this.selectedRecord.warehousing_id) {
      this.agencyName = this.selectedRecord.warehousing_agency_name || '';
      this.agencyBillNo = this.selectedRecord.agencyBillNo || '';
      this.agencyBillDate = this.selectedRecord.agencyBillDate || '';
      this.billValue = this.selectedRecord.bill_value ? parseFloat(this.selectedRecord.bill_value) : 0;
      this.gstPercent = this.selectedRecord.warehousing_gst_percent ? parseFloat(this.selectedRecord.warehousing_gst_percent) : 0;
      this.totalWarehouseAmountWithGst = this.selectedRecord.total_warehouse_amount_with_gst ? parseFloat(this.selectedRecord.total_warehouse_amount_with_gst) : 0;
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
    // Calculate Total Warehouse Amount with GST = Bill Value + (Bill Value * GST % / 100)
    if (this.billValue > 0 && this.gstPercent >= 0) {
      this.totalWarehouseAmountWithGst = this.billValue + (this.billValue * this.gstPercent / 100);
    } else {
      this.totalWarehouseAmountWithGst = 0;
    }
  }

  save(form: any) {
    const data = {
      id: this.selectedRecord.warehousing_id || '',
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
      bill_value: this.billValue,
      gst_percent: this.gstPercent,
      total_warehouse_amount_with_gst: this.totalWarehouseAmountWithGst
    };

    this.service.post('exports/exports.php?type=saveWarehousing', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Warehousing saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving warehousing');
      }
    }, error => {
      console.error('Error saving warehousing:', error);
      alertify.error('Error saving warehousing');
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
    this.billValue = 0;
    this.gstPercent = 0;
    this.totalWarehouseAmountWithGst = 0;
  }

  refresh() {
    this.getImportData();
  }
}
