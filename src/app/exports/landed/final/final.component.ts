import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-final',
  templateUrl: './final.component.html',
  styleUrls: ['./final.component.css']
})
export class FinalComponent implements OnInit {

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

  // Calculated fields (auto-filled)
  totalBasicValue: number = 0;
  totalGst: number = 0;
  landedCostPerUnit: number = 0;

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
    
    // Calculate totals from all components
    this.calculateTotals();
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedRecord = {};
    this.resetForm();
  }

  calculateTotals() {
    let basicValue = 0;
    let totalWithGst = 0;
    let gstAmount = 0;

    // Import Details - Basic Invoice Value
    if (this.selectedRecord.basic_invoice_value) {
      basicValue += parseFloat(this.selectedRecord.basic_invoice_value) || 0;
    }
    if (this.selectedRecord.inv_value_in_rs) {
      basicValue += parseFloat(this.selectedRecord.inv_value_in_rs) || 0;
    }

    // International Logistics
    if (this.selectedRecord.ocean_air_freight_amount) {
      basicValue += parseFloat(this.selectedRecord.ocean_air_freight_amount) || 0;
    }
    if (this.selectedRecord.insurance_value) {
      basicValue += parseFloat(this.selectedRecord.insurance_value) || 0;
    }
    if (this.selectedRecord.miscellaneous_charge) {
      basicValue += parseFloat(this.selectedRecord.miscellaneous_charge) || 0;
    }

    // Customs Import
    if (this.selectedRecord.assessable_value) {
      basicValue += parseFloat(this.selectedRecord.assessable_value) || 0;
    }
    if (this.selectedRecord.bcd_value) {
      basicValue += parseFloat(this.selectedRecord.bcd_value) || 0;
    }
    if (this.selectedRecord.sws_value) {
      basicValue += parseFloat(this.selectedRecord.sws_value) || 0;
    }
    if (this.selectedRecord.other_charges) {
      basicValue += parseFloat(this.selectedRecord.other_charges) || 0;
    }
    if (this.selectedRecord.total_duty_value) {
      basicValue += parseFloat(this.selectedRecord.total_duty_value) || 0;
    }

    // Agency Bills
    if (this.selectedRecord.bill_amount) {
      basicValue += parseFloat(this.selectedRecord.bill_amount) || 0;
    }
    if (this.selectedRecord.cif_term_amount_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.cif_term_amount_with_gst) || 0;
    }

    // CFS
    if (this.selectedRecord.cfs_total_bill_amount) {
      basicValue += parseFloat(this.selectedRecord.cfs_total_bill_amount) || 0;
    }
    if (this.selectedRecord.cfs_cif_term_amount_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.cfs_cif_term_amount_with_gst) || 0;
    }

    // Freight Forwarder
    if (this.selectedRecord.origin_charges) {
      basicValue += parseFloat(this.selectedRecord.origin_charges) || 0;
    }
    if (this.selectedRecord.ocean_freight) {
      basicValue += parseFloat(this.selectedRecord.ocean_freight) || 0;
    }
    if (this.selectedRecord.destination_charges) {
      basicValue += parseFloat(this.selectedRecord.destination_charges) || 0;
    }
    if (this.selectedRecord.total_forwarder_amount) {
      totalWithGst += parseFloat(this.selectedRecord.total_forwarder_amount) || 0;
    }

    // Destination Clearing
    if (this.selectedRecord.cha_clearing_agent_fees) {
      basicValue += parseFloat(this.selectedRecord.cha_clearing_agent_fees) || 0;
    }
    if (this.selectedRecord.document_fee) {
      basicValue += parseFloat(this.selectedRecord.document_fee) || 0;
    }
    if (this.selectedRecord.loading_and_unloading) {
      basicValue += parseFloat(this.selectedRecord.loading_and_unloading) || 0;
    }
    if (this.selectedRecord.other_charges) {
      basicValue += parseFloat(this.selectedRecord.other_charges) || 0;
    }
    if (this.selectedRecord.stamp_duty) {
      basicValue += parseFloat(this.selectedRecord.stamp_duty) || 0;
    }
    if (this.selectedRecord.total_cha_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.total_cha_with_gst) || 0;
    }

    // Transport
    if (this.selectedRecord.inland_transportation) {
      basicValue += parseFloat(this.selectedRecord.inland_transportation) || 0;
    }
    if (this.selectedRecord.unloading_charges) {
      basicValue += parseFloat(this.selectedRecord.unloading_charges) || 0;
    }
    if (this.selectedRecord.total_transport_amount_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.total_transport_amount_with_gst) || 0;
    }

    // Payment Bank
    if (this.selectedRecord.paid_amount) {
      basicValue += parseFloat(this.selectedRecord.paid_amount) || 0;
    }
    if (this.selectedRecord.total_paid_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.total_paid_with_gst) || 0;
    }

    // Warehousing
    if (this.selectedRecord.bill_value) {
      basicValue += parseFloat(this.selectedRecord.bill_value) || 0;
    }
    if (this.selectedRecord.total_warehouse_amount_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.total_warehouse_amount_with_gst) || 0;
    }

    // Inhand
    if (this.selectedRecord.inspection_survey_fees) {
      basicValue += parseFloat(this.selectedRecord.inspection_survey_fees) || 0;
    }
    if (this.selectedRecord.total_amount_with_gst) {
      totalWithGst += parseFloat(this.selectedRecord.total_amount_with_gst) || 0;
    }

    // Calculate GST from amounts with GST
    // GST = Total with GST - Basic Value (for each component)
    // For simplicity, we'll calculate total GST as: Total With GST - Total Basic Value
    this.totalBasicValue = basicValue;
    this.totalGst = totalWithGst - basicValue;
    
    // Calculate Landed Cost Per Unit
    if (this.quantity > 0) {
      this.landedCostPerUnit = (this.totalBasicValue + this.totalGst) / this.quantity;
    } else {
      this.landedCostPerUnit = 0;
    }
  }

  resetForm() {
    this.poNo = '';
    this.challanNo = '';
    this.grnNo = '';
    this.itemCode = '';
    this.itemDescription = '';
    this.quantity = 0;
    this.uom = '';
    this.totalBasicValue = 0;
    this.totalGst = 0;
    this.landedCostPerUnit = 0;
  }

  refresh() {
    this.getImportData();
  }
}
