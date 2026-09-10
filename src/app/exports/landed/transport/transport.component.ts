import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-transport',
  templateUrl: './transport.component.html',
  styleUrls: ['./transport.component.css']
})
export class TransportComponent implements OnInit {

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
  
  // Transport fields
  transportName: string = '';
  transportBillNo: string = '';
  transportBillDate: string = '';
  inlandTransportation: number = 0;
  unloadingCharges: number = 0;
  gstPercent: number = 0;
  totalTransportAmountWithGst: number = 0;

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
    
    // Load existing transport data if available
    if (this.selectedRecord.transport_id) {
      this.transportName = this.selectedRecord.transport_name || '';
      this.transportBillNo = this.selectedRecord.transport_bill_no || '';
      this.transportBillDate = this.selectedRecord.transport_bill_date || '';
      this.inlandTransportation = this.selectedRecord.inland_transportation ? parseFloat(this.selectedRecord.inland_transportation) : 0;
      this.unloadingCharges = this.selectedRecord.unloading_charges ? parseFloat(this.selectedRecord.unloading_charges) : 0;
      this.gstPercent = this.selectedRecord.transport_gst_percent ? parseFloat(this.selectedRecord.transport_gst_percent) : 0;
      this.totalTransportAmountWithGst = this.selectedRecord.total_transport_amount_with_gst ? parseFloat(this.selectedRecord.total_transport_amount_with_gst) : 0;
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
    // Calculate subtotal = Inland Transportation + Unloading Charges
    const subtotal = this.inlandTransportation + this.unloadingCharges;
    
    // Calculate Total Transport Amount with GST = Subtotal + (Subtotal * GST % / 100)
    if (subtotal > 0 && this.gstPercent >= 0) {
      this.totalTransportAmountWithGst = subtotal + (subtotal * this.gstPercent / 100);
    } else {
      this.totalTransportAmountWithGst = 0;
    }
  }

  save(form: any) {
    const data = {
      id: this.selectedRecord.transport_id || '',
      po_no: this.poNo,
      challan_no: this.challanNo,
      grn_no: this.grnNo,
      item_code: this.itemCode,
      item_description: this.itemDescription,
      quantity: this.quantity,
      uom: this.uom,
      transport_name: this.transportName,
      transport_bill_no: this.transportBillNo,
      transport_bill_date: this.transportBillDate,
      inland_transportation: this.inlandTransportation,
      unloading_charges: this.unloadingCharges,
      gst_percent: this.gstPercent,
      total_transport_amount_with_gst: this.totalTransportAmountWithGst
    };

    this.service.post('exports/exports.php?type=saveTransport', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Transport saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving transport');
      }
    }, error => {
      console.error('Error saving transport:', error);
      alertify.error('Error saving transport');
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
    this.transportName = '';
    this.transportBillNo = '';
    this.transportBillDate = '';
    this.inlandTransportation = 0;
    this.unloadingCharges = 0;
    this.gstPercent = 0;
    this.totalTransportAmountWithGst = 0;
  }

  refresh() {
    this.getImportData();
  }
}
