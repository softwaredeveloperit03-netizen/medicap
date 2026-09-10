import { HttpClient } from '@angular/common/http';
import { Component, OnInit, ViewChild } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  @ViewChild('quatationForm') quatationForm: NgForm;

  units: any[] = [];
  vendors: any[] = [];
  vendor_quotation_date: string;
  gsts: any[] = [];
  selectedVendor: any = null;
  selectedMaterial: any;
  materials: any;
  materialList: any;
  selectedFile: File;
  material_type: string;
  vendor_quotation_no: string;
  gstObj: any;
  gst_applicable: string;
  selectedProduct: any;
  material_subtype: string;
  plant_id: string;
  currency = [
    { "code": "USD", "name": "US Dollar" },
    { "code": "EUR", "name": "Euro" },
    { "code": "CNY", "name": "Chinese Yuan (Renminbi)" },
    { "code": "JPY", "name": "Japanese Yen" },
    { "code": "GBP", "name": "British Pound Sterling" },
    { "code": "AED", "name": "UAE Dirham" },
    { "code": "CHF", "name": "Swiss Franc" },
    { "code": "CAD", "name": "Canadian Dollar" },
    { "code": "AUD", "name": "Australian Dollar" },
    { "code": "INR", "name": "Indian Rupee" },
    { "code": "HKD", "name": "Hong Kong Dollar" },
    { "code": "SGD", "name": "Singapore Dollar" },
    { "code": "KRW", "name": "South Korean Won" },
    { "code": "NZD", "name": "New Zealand Dollar" },
    { "code": "SEK", "name": "Swedish Krona" },
    { "code": "NOK", "name": "Norwegian Krone" },
    { "code": "ZAR", "name": "South African Rand" }
  ];

  constructor(private service: DataAccessService, private http: HttpClient, private router: Router) { }

  ngOnInit() {
    this.plant_id = (this.service.getPlantConfigFields && this.service.getPlantConfigFields('plant_id')) || '';
    this.service.observableUnit.subscribe((response: any) => {
      this.units = Array.isArray(response) ? response : [];
    });
    this.getGst();
    this.getAllVendors();
  }

  /**
   * Get available currencies - merges vendor currencies with default currency list
   * Ensures USD and EUR are always available
   */
  getAvailableCurrencies(): any[] {
    const defaultCurrencies = this.currency || [];
    const vendorCurrencies = this.selectedVendor?.currency || [];
    
    // Create a map to avoid duplicates
    const currencyMap = new Map<string, any>();
    
    // First, add USD and EUR to ensure they're always first
    const usd = defaultCurrencies.find(c => c.code === 'USD') || { code: 'USD', name: 'US Dollar' };
    const eur = defaultCurrencies.find(c => c.code === 'EUR') || { code: 'EUR', name: 'Euro' };
    
    currencyMap.set('USD', usd);
    currencyMap.set('EUR', eur);
    
    // Add vendor currencies if available
    if (Array.isArray(vendorCurrencies) && vendorCurrencies.length > 0) {
      vendorCurrencies.forEach((curr: any) => {
        if (curr && curr.code) {
          currencyMap.set(curr.code, curr);
        }
      });
    }
    
    // Add remaining default currencies
    defaultCurrencies.forEach((curr: any) => {
      if (curr && curr.code && !currencyMap.has(curr.code)) {
        currencyMap.set(curr.code, curr);
      }
    });
    
    // Convert map to array, ensuring USD and EUR are first
    const result: any[] = [];
    if (currencyMap.has('USD')) {
      result.push(currencyMap.get('USD'));
      currencyMap.delete('USD');
    }
    if (currencyMap.has('EUR')) {
      result.push(currencyMap.get('EUR'));
      currencyMap.delete('EUR');
    }
    
    // Add remaining currencies
    currencyMap.forEach((value) => {
      result.push(value);
    });
    
    return result;
  }

  getAllVendors() {
    this.service.get('common.php?type=getVendorByMaterialType&typev=all&material_type=').subscribe((response: any) => {
      this.vendors = Array.isArray(response) ? response : [];
    });
  }

  getGst() {
    this.service.get('common.php?type=getGST').subscribe((response: any) => {
      this.gsts = Array.isArray(response) ? response : [];
    });
  }

  materials_data: any[] = [];

  getMaterialByVendor(value: string) {
    this.materials_data = [];
    const vendor = this.selectedVendor;
    if (!vendor || !value) return;
    this.service.get('purchase/quotation.php?type=getvendorquatationmaterial&vendor_no=' + value)
      .subscribe((response: any) => {
        this.materials_data = Array.isArray(response) ? response : [];
        if (vendor['vendorFor'] === 'SEZ') {
          this.materials_data.forEach((m: any) => {
            m['gst'] = 0;
            m['tax'] = 0;
          });
        }
        this.materials_data.forEach((mat: any) => {
          if (mat['tax_type'] === 'Local') mat['currency'] = 'INR';
          if (mat['tax_type'] === 'Import') mat['currency'] = 'USD';
        });
      });
  }

  isOpen = false;
  selectedquoye = [];

  viewComap(data) {
    this.selectedquoye = data['comparison'];
    this.isOpen = true;
  }

  getVendorDetails(index: number) {
    const i = Number(index);
    if (i <= 0 || !this.vendors || this.vendors.length === 0) {
      this.selectedVendor = null;
      this.materials_data = [];
      return;
    }
    this.selectedVendor = this.vendors[i - 1];
    const vendorNo = this.selectedVendor && this.selectedVendor['vendor_no'];
    if (vendorNo) this.getMaterialByVendor(vendorNo);
  }

  add(data) {

  }

  private hasValue(value: any): boolean {
    if (value === null || value === undefined) {
      return false;
    }
    const normalized = String(value).trim();
    return normalized !== '';
  }

  onMaterialValueChange(item: any) {
    if (!item) {
      return;
    }
    const hasAmount = this.hasValue(item.quotation_amt);
    const hasPackSize = this.hasValue(item.pack_size);
    item.selected = hasAmount && hasPackSize;
  }

  save(data) {

    const selectedItems = this.materials_data.filter((term) => term.selected);
    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }
    if (!data.valid) {
      alert('All Field Required');
      return;
    }

    console.log(selectedItems);

    const uploadData = new FormData();

    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
    }

    uploadData.append("vendor_For", this.selectedVendor['vendor_For']);
    uploadData.append("vendor_quotation_no", this.vendor_quotation_no);
    uploadData.append("vendor_quotation_date", this.vendor_quotation_date);
    uploadData.append('material_type', this.selectedVendor['material_type']);
    uploadData.append('vendor_no', this.selectedVendor['id']);
    uploadData.append('materials', JSON.stringify(selectedItems));


    this.service.post('purchase/quotation.php?type=saveQuotation', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Quotation saved successfully. Sent for comparative approval.');
        this.resetAfterSave();
        this.router.navigate(['/purchase/quotation/comparative'], {
          queryParams: { quotation_category: 'Quotation', mode: 'pending' }
        });
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  private resetAfterSave(): void {
    this.vendor_quotation_no = '';
    this.vendor_quotation_date = undefined;
    this.selectedVendor = null;
    this.materials_data = [];
    this.selectedFile = undefined;
    if (this.quatationForm) {
      this.quatationForm.resetForm();
    }
  }

  onFileChanged(event) {
    if (event.target.files.length === 1) {
      this.selectedFile = event.target.files[0];
    }
  }

  del(index) {
    this.materialList.splice(index, 1);
  }

}

