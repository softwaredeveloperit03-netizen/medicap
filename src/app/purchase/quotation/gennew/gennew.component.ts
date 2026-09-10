import { HttpClient } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { Location } from '@angular/common';
declare let alertify: any;
@Component({
  selector: 'app-gennew',
  templateUrl: './gennew.component.html',
  styleUrls: ['./gennew.component.css']
})
export class GennewComponent implements OnInit {

  
  units: any[] = [];
  vendors: any[] = [];
  gsts: any[] = [];
  selectedVendor: any = null;
  materialList: any;
  selectedFile: File;
  vendor_quotation_no: string;
  gstObj: any;
  gst_applicable: string;
  selectedProduct: any;
  material_subtype = '';
  currencyOptions = [
    { code: 'USD', name: 'US Dollar' }, { code: 'EUR', name: 'Euro' }, { code: 'INR', name: 'Indian Rupee' },
    { code: 'GBP', name: 'British Pound' }, { code: 'AED', name: 'UAE Dirham' }, { code: 'CHF', name: 'Swiss Franc' },
    { code: 'CAD', name: 'Canadian Dollar' }, { code: 'AUD', name: 'Australian Dollar' }
  ];


  constructor(private location: Location,private service: DataAccessService, private router: Router, private http: HttpClient) { }

  ngOnInit() {
    this.service.observableUnit.subscribe((response: any) => {
      this.units = Array.isArray(response) ? response : [];
    });
    this.getGst();
    this.getVendorForGeneralMaterialQuatation();
    this.getequipment_type();
  }
 
  material_type = '';

  types: any[] = [];
  onMaterialTypeChange() {
    this.material_subtype = '';
    this.materials = [];
    this.types = [];
    this.getMaterialType();
  }

  getMaterialType() {
    if (!this.material_type || this.material_type === 'Service' || this.material_type === 'Equipment') {
      return;
    }
    const type = encodeURIComponent(this.material_type);
    this.service.get('master/general.php?type=getGeneralMaterialSubtypes&material_type=' + type).subscribe((response: any) => {
      const rows = Array.isArray(response) ? response : [];
      if (rows.length) {
        this.types = rows;
        return;
      }
      this.loadSubtypesFromTypeMaster(type);
    }, () => {
      this.loadSubtypesFromTypeMaster(type);
    });
  }

  private loadSubtypesFromTypeMaster(type: string) {
    this.service.get('master/materialtype.php?type=getMatTypeByMatType&material_type=' + type).subscribe((response: any) => {
      this.types = Array.isArray(response) ? response : [];
    }, () => {
      this.types = [];
    });
  }

  materials: any[] = [];
  getGeneralMaterials() {
    this.materials = [];
    if (!this.material_type || !this.material_subtype) {
      return;
    }
    const type = encodeURIComponent(this.material_type);
    const subtype = encodeURIComponent(this.material_subtype);
    this.service.get('master/general.php?type=getMaterialForGeneralQuatation&material_type=' + type + '&material_subtype=' + subtype).subscribe((response: any) => {
      const rows = Array.isArray(response) ? response : [];
      if (rows.length) {
        this.materials = rows.map((row: any) => ({
          ...row,
          material_code: String(row.material_code || row.chemical_no || '').trim() || (row.id ? 'GEN-OM-' + row.id : ''),
        }));
        return;
      }
      this.loadMaterialsFromMaster(type);
    }, () => {
      this.loadMaterialsFromMaster(type);
    });
  }

  private loadMaterialsFromMaster(type: string) {
    this.service.get('master/general.php?type=getGeneralMaterials1&material_type=' + type).subscribe((response: any) => {
      const rows = Array.isArray(response) ? response : (Array.isArray(response?.rows) ? response.rows : []);
      const subtype = String(this.material_subtype || '').trim().toLowerCase();
      this.materials = rows
        .filter((row: any) => {
          const rowSubtype = String(row.material_subtype || '').trim().toLowerCase();
          if (!subtype) {
            return true;
          }
          if (rowSubtype === subtype) {
            return true;
          }
          if ((subtype === 'chemical' || subtype === 'chemicals') && (rowSubtype === 'chemical' || rowSubtype === 'chemicals')) {
            return true;
          }
          if ((subtype === 'reagent' || subtype === 'reagents') && (rowSubtype === 'reagent' || rowSubtype === 'reagents')) {
            return true;
          }
          return false;
        })
        .map((row: any) => ({
          ...row,
          material_code: String(row.material_code || row.chemical_no || '').trim() || (row.id ? 'GEN-OM-' + row.id : ''),
          quotation_amt: 0,
          selected: false,
          quotation_per: row.unit || '',
          tax: row.gst || '',
        }));
    }, () => {
      this.materials = [];
    });
  }

  equipment_type_data: any[] = [];
  getequipment_type() {
    this.service.get('master/equipment.php?type=getequipment_type_data').subscribe((response: any) => {
      this.equipment_type_data = Array.isArray(response) ? response : [];
    });
  }

  
  serviceSutType = [
  'Annual Maintenance', 
  'Equipment Service', 
  'Breakdown Maintenance', 
  'Manpower Service',
  'Training Service',
  'Consultancy Service',
  'Development Service',
  'Transport Service',
  'Contracts Service',
  'Other Service',
  ];



  getVendorForGeneralMaterialQuatation() {
    this.service.get('common.php?type=getGeneralMatVendor').subscribe((response: any) => {
      this.vendors = Array.isArray(response) ? response : [];
    });
  }

  getGst() {
    this.service.get('common.php?type=getGST').subscribe((response: any) => {
      this.gsts = Array.isArray(response) ? response : [];
    });
  }




  materials_data: any[] = [];

  addSelectedMaterial(){

    const selectedMaterials = this.materials.filter(mat => mat.selected === true);

    if (selectedMaterials.length === 0) {
      alertify.error('No material is selected');
      return;
    }

    for (const mat of selectedMaterials) {
      const missing: string[] = [];
      if (mat.quotation_amt == null || mat.quotation_amt.toString().trim() === '') { missing.push('Quotation Amt.'); }
      if (mat.tax == null || mat.tax.toString().trim() === '') { missing.push('GST%'); }
      if (!mat.tax_type || mat.tax_type.toString().trim() === '') {
        mat.tax_type = 'Local';
      }
      if (missing.length) {
        alertify.error('Please fill ' + missing.join(', ') + ' for "' + (mat.material_name || mat.material_code) + '"');
        return;
      }
    }

    // Spread the selected materials so they’re added individually, not as an array
    this.materials_data.push(...selectedMaterials);

    // Optional: clear selection after adding
    this.materials.forEach(mat => mat.selected = false);

    // Optional: show success message
    alertify.success('Selected materials added successfully');

 
  }

 
 
  /** Maps a form control name to a human-readable label (handles row-suffixed names). */
  private fieldLabel(controlName: string): string {
    const rowMatch = controlName.match(/^([a-zA-Z_]+?)(\d+)$/);
    const base = rowMatch ? rowMatch[1] : controlName;
    const rowSuffix = rowMatch ? ' (row ' + (Number(rowMatch[2]) + 1) + ')' : '';
    const labels: { [key: string]: string } = {
      vendor_no: 'Name of Vendor',
      vendor_quotation_no: 'Quotation No',
      material_type: 'Material Group/Type',
      material_subtype: 'Sub Group/Type',
      document: 'Upload Document',
      quotation_amt: 'Quotation Amt.',
      tax: 'GST%',
      currency: 'Currency',
      selected: 'Select',
    };
    return (labels[base] || base) + rowSuffix;
  }

  private getInvalidFields(data): string[] {
    const invalid: string[] = [];
    const controls = data && data.controls ? data.controls : {};
    for (const name in controls) {
      if (controls[name] && controls[name].invalid) {
        invalid.push(this.fieldLabel(name));
      }
    }
    return invalid;
  }

  private normalizeMaterialForSave(material: any): any {
    const row = { ...material };
    const code = String(row.material_code || row.chemical_no || '').trim();
    if (code) {
      row.material_code = code;
      row.chemical_no = row.chemical_no || code;
    } else if (row.id) {
      row.material_code = 'GEN-OM-' + row.id;
    }
    if (!row.material_type && this.material_type) {
      row.material_type = this.material_type;
    }
    if (!row.material_subtype && this.material_subtype) {
      row.material_subtype = this.material_subtype;
    }
    if (!row.material_type && row.chemical_no) {
      row.material_type = 'QC Materials';
    }
    if (!row.material_subtype && row.chemical_no) {
      row.material_subtype = 'Chemical';
    }
    if (!row.material_name && row.chemical_name) {
      row.material_name = row.chemical_name;
    }
    return row;
  }

  private buildMaterialsPayload(): any[] {
    return (this.materials_data || []).map((item) => {
      const row = this.normalizeMaterialForSave(item);
      return {
        id: row.id,
        material_code: row.material_code,
        chemical_no: row.chemical_no || row.material_code,
        material_name: row.material_name,
        material_type: row.material_type || this.material_type,
        material_subtype: row.material_subtype || this.material_subtype,
        quotation_amt: row.quotation_amt,
        quotation_per: row.quotation_per || row.unit || '',
        unit: row.unit || '',
        tax: row.tax,
        tax_type: row.tax_type || 'Local',
        currency: row.currency || 'INR',
        pack_size: row.pack_size || 'NA',
        pack_size_unit: row.pack_size_unit || row.unit || 'NA',
        gst: row.gst || row.tax,
      };
    });
  }

  save(data) {

    if (!data.valid) {
      const missing = this.getInvalidFields(data);
      alertify.error(
        missing.length
          ? 'Please fill required field(s): ' + missing.join(', ')
          : 'All Field Required !!!!!!!!'
      );
      return;
    }
  
    const selectedItems = this.materials_data;
  
    if (selectedItems.length === 0) {
      alertify.error('No Material Added');
      return;
    }

    const temp = data.value || {};
    const uploadData = new FormData();

    // Send materials first — large Available Materials grids can exceed PHP max_input_vars
    // if every row ngModel field is posted; materials must not be dropped.
    uploadData.append('materials', JSON.stringify(this.buildMaterialsPayload()));
    uploadData.append('vendor_no', String(temp.vendor_no || ''));
    uploadData.append('vendor_quotation_no', String(temp.vendor_quotation_no || this.vendor_quotation_no || ''));
    uploadData.append('material_type', String(this.material_type || temp.material_type || ''));
    uploadData.append('material_subtype', String(this.material_subtype || temp.material_subtype || ''));

    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
    }

    this.service.post('purchase/quotation.php?type=saveGENQuotation', uploadData).subscribe({
      next: (response: any) => {
        if (response['status'] == 'success') {
          alertify.success('Quotation saved successfully. Sent for comparative approval.');
          this.router.navigate(['/purchase/quotation/comparative'], {
            queryParams: { quotation_category: 'General', mode: 'pending' }
          });
        } else {
          alertify.error(response['status'] || 'Failed: An error occured, please try again!');
        }
      },
      error: () => {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  onFileChanged(event) {
    if(event.target.files.length === 1) {
      this.selectedFile = event.target.files[0];
    }
  }


  
  getVendorDetails(index: number) {
    const i = Number(index);
    if (i <= 0 || !this.vendors || this.vendors.length === 0) {
      this.selectedVendor = null;
      return;
    }
    this.selectedVendor = this.vendors[i - 1];
  }

 
  del(index) {
    this.materialList.splice(index, 1);
  }



  currency_rate: number;
  currency;
 
  lancel(){
    this.http.get('https://open.er-api.com/v6/latest/'+this.currency).subscribe((data: any) => {
        this.currency_rate = data.rates.AUD;
      },
      (error) => {
        console.error('Error fetching USD exchange rate:', error);
        this.currency_rate = 1;  
      }
    );
  }







}
