import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-int-logistics',
  templateUrl: './int-logistics.component.html',
  styleUrls: ['./int-logistics.component.css']
})
export class IntLogisticsComponent implements OnInit {

  importData: any[] = [];
  loading = false;
  isView = false;
  selectedRecord: any = {};

  // Form fields for International Logistics
  poNo: string = '';
  challanNo: string = '';
  grnNo: string = '';
  itemCode: string = '';
  itemDescription: string = '';
  quantity: number = 0;
  uom: string = '';
  
  // Logistics charges
  oceanAirFreightPercent: number = 0;
  oceanAirFreightAmount: number = 0;
  insuranceValue: number = 0;
  miscellaneousCharge: number = 0;
  originPortCharges: number = 0;
  exportCustomsCharges: number = 0;
  
  // Calculated values
  assessableValueCIF: number = 0; // Invoice Value + Freight + Misc + Insurance + Or Port + Ex Custom Charges
  cifValue: number = 0;
  
  // Weight and volume
  weight: number = 0;
  volume: number = 0;
  weightVolume: string = ''; // Combined field
  
  // Base values from import_details
  invoiceValue: number = 0;
  exchangeRate: number = 0;
  currency: string = '';
  
  // File uploads
  selectedFiles: { [key: string]: File | null } = {
    freightDocument: null,
    insuranceDocument: null,
    customsDocument: null,
    otherDocument: null
  };
  
  // Existing file URLs
  existingFiles: { [key: string]: string } = {
    freightDocument: '',
    insuranceDocument: '',
    customsDocument: '',
    otherDocument: ''
  };

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
    
    // Populate form fields from import_details
    this.poNo = this.selectedRecord.po_no || '';
    this.challanNo = this.selectedRecord.challan_no || '';
    this.grnNo = this.selectedRecord.grn_no || '';
    this.itemCode = this.selectedRecord.item_code || this.selectedRecord.material_code || '';
    this.itemDescription = this.selectedRecord.item_description || this.selectedRecord.material_name || '';
    this.quantity = this.selectedRecord.quantity || this.selectedRecord.qty ? parseFloat(this.selectedRecord.quantity || this.selectedRecord.qty) : 0;
    this.uom = this.selectedRecord.uom || this.selectedRecord.unit || '';
    this.invoiceValue = this.selectedRecord.basic_invoice_value ? parseFloat(this.selectedRecord.basic_invoice_value) : (this.selectedRecord.net_total ? parseFloat(this.selectedRecord.net_total) : 0);
    this.exchangeRate = this.selectedRecord.exchange_rate ? parseFloat(this.selectedRecord.exchange_rate) : 0;
    this.currency = this.selectedRecord.currency || this.selectedRecord.currency1 || '';
    
    // Load existing logistics data if available
    if (this.selectedRecord.logistics_id) {
      this.oceanAirFreightPercent = this.selectedRecord.ocean_air_freight_percent ? parseFloat(this.selectedRecord.ocean_air_freight_percent) : 0;
      this.oceanAirFreightAmount = this.selectedRecord.ocean_air_freight_amount ? parseFloat(this.selectedRecord.ocean_air_freight_amount) : 0;
      this.insuranceValue = this.selectedRecord.insurance_value ? parseFloat(this.selectedRecord.insurance_value) : 0;
      this.miscellaneousCharge = this.selectedRecord.miscellaneous_charge ? parseFloat(this.selectedRecord.miscellaneous_charge) : 0;
      this.originPortCharges = this.selectedRecord.origin_port_charges ? parseFloat(this.selectedRecord.origin_port_charges) : 0;
      this.exportCustomsCharges = this.selectedRecord.export_customs_charges ? parseFloat(this.selectedRecord.export_customs_charges) : 0;
      this.assessableValueCIF = this.selectedRecord.assessable_value_cif ? parseFloat(this.selectedRecord.assessable_value_cif) : 0;
      this.cifValue = this.selectedRecord.cif_value ? parseFloat(this.selectedRecord.cif_value) : 0;
      this.weight = this.selectedRecord.weight ? parseFloat(this.selectedRecord.weight) : 0;
      this.volume = this.selectedRecord.volume ? parseFloat(this.selectedRecord.volume) : 0;
      this.weightVolume = this.selectedRecord.weight_volume || '';
      
      // Load existing file URLs
      if (this.selectedRecord.freight_document_url) {
        this.existingFiles['freightDocument'] = this.selectedRecord.freight_document_url.startsWith('http') 
          ? this.selectedRecord.freight_document_url 
          : this.service.domain.replace('/php/', '/') + this.selectedRecord.freight_document_url.replace('../../', '');
      }
      if (this.selectedRecord.insurance_document_url) {
        this.existingFiles['insuranceDocument'] = this.selectedRecord.insurance_document_url.startsWith('http') 
          ? this.selectedRecord.insurance_document_url 
          : this.service.domain.replace('/php/', '/') + this.selectedRecord.insurance_document_url.replace('../../', '');
      }
      if (this.selectedRecord.customs_document_url) {
        this.existingFiles['customsDocument'] = this.selectedRecord.customs_document_url.startsWith('http') 
          ? this.selectedRecord.customs_document_url 
          : this.service.domain.replace('/php/', '/') + this.selectedRecord.customs_document_url.replace('../../', '');
      }
      if (this.selectedRecord.other_document_url) {
        this.existingFiles['otherDocument'] = this.selectedRecord.other_document_url.startsWith('http') 
          ? this.selectedRecord.other_document_url 
          : this.service.domain.replace('/php/', '/') + this.selectedRecord.other_document_url.replace('../../', '');
      }
    }
    
    this.calculateValues();
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedRecord = {};
    this.resetForm();
  }

  onFileChange(event: any, fileType: string) {
    if (event.target.files && event.target.files.length > 0) {
      this.selectedFiles[fileType] = event.target.files[0];
    }
  }

  calculateValues() {
    // Calculate Ocean/Air Freight Amount from percentage
    if (this.oceanAirFreightPercent > 0 && this.invoiceValue > 0) {
      this.oceanAirFreightAmount = (this.invoiceValue * this.oceanAirFreightPercent) / 100;
    }
    
    // Calculate Assessable Value (CIF) = Invoice Value + Freight + Misc + Insurance + Or Port + Ex Custom Charges
    this.assessableValueCIF = this.invoiceValue + 
                              this.oceanAirFreightAmount + 
                              this.miscellaneousCharge + 
                              this.insuranceValue + 
                              this.originPortCharges + 
                              this.exportCustomsCharges;
    
    // CIF Value is same as Assessable Value
    this.cifValue = this.assessableValueCIF;
  }

  save(form: any) {
    const formData = new FormData();
    
    // Basic data
    formData.append('id', this.selectedRecord.logistics_id || '');
    formData.append('po_no', this.poNo);
    formData.append('challan_no', this.challanNo);
    formData.append('grn_no', this.grnNo);
    formData.append('item_code', this.itemCode);
    formData.append('item_description', this.itemDescription);
    formData.append('quantity', this.quantity.toString());
    formData.append('uom', this.uom);
    
    // Logistics charges
    formData.append('ocean_air_freight_percent', this.oceanAirFreightPercent.toString());
    formData.append('ocean_air_freight_amount', this.oceanAirFreightAmount.toString());
    formData.append('insurance_value', this.insuranceValue.toString());
    formData.append('miscellaneous_charge', this.miscellaneousCharge.toString());
    formData.append('origin_port_charges', this.originPortCharges.toString());
    formData.append('export_customs_charges', this.exportCustomsCharges.toString());
    
    // Calculated values
    formData.append('assessable_value_cif', this.assessableValueCIF.toString());
    formData.append('cif_value', this.cifValue.toString());
    
    // Weight and volume
    formData.append('weight', this.weight.toString());
    formData.append('volume', this.volume.toString());
    formData.append('weight_volume', this.weightVolume);
    
    // File uploads
    if (this.selectedFiles['freightDocument']) {
      formData.append('freight_document', this.selectedFiles['freightDocument']);
    }
    if (this.selectedFiles['insuranceDocument']) {
      formData.append('insurance_document', this.selectedFiles['insuranceDocument']);
    }
    if (this.selectedFiles['customsDocument']) {
      formData.append('customs_document', this.selectedFiles['customsDocument']);
    }
    if (this.selectedFiles['otherDocument']) {
      formData.append('other_document', this.selectedFiles['otherDocument']);
    }

    this.service.post('exports/exports.php?type=saveIntLogistics', formData).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('International logistics saved successfully');
        this.closeView();
        this.getImportData();
      } else {
        alertify.error(response.message || 'Error saving international logistics');
      }
    }, error => {
      console.error('Error saving international logistics:', error);
      alertify.error('Error saving international logistics');
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
    this.oceanAirFreightPercent = 0;
    this.oceanAirFreightAmount = 0;
    this.insuranceValue = 0;
    this.miscellaneousCharge = 0;
    this.originPortCharges = 0;
    this.exportCustomsCharges = 0;
    this.assessableValueCIF = 0;
    this.cifValue = 0;
    this.weight = 0;
    this.volume = 0;
    this.weightVolume = '';
    this.invoiceValue = 0;
    this.exchangeRate = 0;
    this.currency = '';
    this.selectedFiles = {
      freightDocument: null,
      insuranceDocument: null,
      customsDocument: null,
      otherDocument: null
    };
  }

  refresh() {
    this.getImportData();
  }
}
