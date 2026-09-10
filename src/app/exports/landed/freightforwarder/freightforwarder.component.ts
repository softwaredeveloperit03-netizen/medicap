import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-freightforwarder',
  templateUrl: './freightforwarder.component.html',
  styleUrls: ['./freightforwarder.component.css']
})
export class FreightforwarderComponent implements OnInit {

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
  
  // Freight Forwarder fields
  forwarderName: string = '';
  forwarderInvDate: string = '';
  forwarderInvNo: string = '';
  originCharges: number = 0;
  originChargesGstPercent: number = 0;
  originChargesWithGst: number = 0;
  oceanFreight: number = 0;
  oceanFreightGstPercent: number = 0;
  oceanFreightWithGst: number = 0;
  destinationInvDate: string = '';
  destinationInvNo: string = '';
  destinationCharges: number = 0;
  destinationChargesGstPercent: number = 0;
  destinationChargesWithGst: number = 0;
  totalForwarderAmount: number = 0;

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
    
    // Load existing freight forwarder data if available
    if (this.selectedRecord.freight_forwarder_id) {
      this.forwarderName = this.selectedRecord.forwarder_name || '';
      this.forwarderInvDate = this.selectedRecord.forwarder_inv_date || '';
      this.forwarderInvNo = this.selectedRecord.forwarder_inv_no || '';
      this.originCharges = this.selectedRecord.origin_charges ? parseFloat(this.selectedRecord.origin_charges) : 0;
      this.originChargesGstPercent = this.selectedRecord.origin_charges_gst_percent ? parseFloat(this.selectedRecord.origin_charges_gst_percent) : 0;
      this.originChargesWithGst = this.selectedRecord.origin_charges_with_gst ? parseFloat(this.selectedRecord.origin_charges_with_gst) : 0;
      this.oceanFreight = this.selectedRecord.ocean_freight ? parseFloat(this.selectedRecord.ocean_freight) : 0;
      this.oceanFreightGstPercent = this.selectedRecord.ocean_freight_gst_percent ? parseFloat(this.selectedRecord.ocean_freight_gst_percent) : 0;
      this.oceanFreightWithGst = this.selectedRecord.ocean_freight_with_gst ? parseFloat(this.selectedRecord.ocean_freight_with_gst) : 0;
      this.destinationInvDate = this.selectedRecord.destination_inv_date || '';
      this.destinationInvNo = this.selectedRecord.destination_inv_no || '';
      this.destinationCharges = this.selectedRecord.destination_charges ? parseFloat(this.selectedRecord.destination_charges) : 0;
      this.destinationChargesGstPercent = this.selectedRecord.destination_charges_gst_percent ? parseFloat(this.selectedRecord.destination_charges_gst_percent) : 0;
      this.destinationChargesWithGst = this.selectedRecord.destination_charges_with_gst ? parseFloat(this.selectedRecord.destination_charges_with_gst) : 0;
      this.totalForwarderAmount = this.selectedRecord.total_forwarder_amount ? parseFloat(this.selectedRecord.total_forwarder_amount) : 0;
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
    // Calculate Origin Charges with GST
    if (this.originCharges > 0 && this.originChargesGstPercent >= 0) {
      this.originChargesWithGst = this.originCharges + (this.originCharges * this.originChargesGstPercent / 100);
    } else {
      this.originChargesWithGst = 0;
    }
    
    // Calculate Ocean Freight with GST
    if (this.oceanFreight > 0 && this.oceanFreightGstPercent >= 0) {
      this.oceanFreightWithGst = this.oceanFreight + (this.oceanFreight * this.oceanFreightGstPercent / 100);
    } else {
      this.oceanFreightWithGst = 0;
    }
    
    // Calculate Destination Charges with GST
    if (this.destinationCharges > 0 && this.destinationChargesGstPercent >= 0) {
      this.destinationChargesWithGst = this.destinationCharges + (this.destinationCharges * this.destinationChargesGstPercent / 100);
    } else {
      this.destinationChargesWithGst = 0;
    }
    
    // Calculate Total Forwarder Amount = Origin Charges with GST + Ocean Freight with GST + Destination Charges with GST
    this.totalForwarderAmount = this.originChargesWithGst + this.oceanFreightWithGst + this.destinationChargesWithGst;
  }

  save(form: any) {
    const data = {
      id: this.selectedRecord.freight_forwarder_id || '',
      po_no: this.poNo,
      challan_no: this.challanNo,
      grn_no: this.grnNo,
      item_code: this.itemCode,
      item_description: this.itemDescription,
      quantity: this.quantity,
      uom: this.uom,
      forwarder_name: this.forwarderName,
      forwarder_inv_date: this.forwarderInvDate,
      forwarder_inv_no: this.forwarderInvNo,
      origin_charges: this.originCharges,
      origin_charges_gst_percent: this.originChargesGstPercent,
      origin_charges_with_gst: this.originChargesWithGst,
      ocean_freight: this.oceanFreight,
      ocean_freight_gst_percent: this.oceanFreightGstPercent,
      ocean_freight_with_gst: this.oceanFreightWithGst,
      destination_inv_date: this.destinationInvDate,
      destination_inv_no: this.destinationInvNo,
      destination_charges: this.destinationCharges,
      destination_charges_gst_percent: this.destinationChargesGstPercent,
      destination_charges_with_gst: this.destinationChargesWithGst,
      total_forwarder_amount: this.totalForwarderAmount
    };

    this.service.post('exports/exports.php?type=saveFreightForwarder', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Freight forwarder saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving freight forwarder');
      }
    }, error => {
      console.error('Error saving freight forwarder:', error);
      alertify.error('Error saving freight forwarder');
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
    this.forwarderName = '';
    this.forwarderInvDate = '';
    this.forwarderInvNo = '';
    this.originCharges = 0;
    this.originChargesGstPercent = 0;
    this.originChargesWithGst = 0;
    this.oceanFreight = 0;
    this.oceanFreightGstPercent = 0;
    this.oceanFreightWithGst = 0;
    this.destinationInvDate = '';
    this.destinationInvNo = '';
    this.destinationCharges = 0;
    this.destinationChargesGstPercent = 0;
    this.destinationChargesWithGst = 0;
    this.totalForwarderAmount = 0;
  }

  refresh() {
    this.getImportData();
  }
}
