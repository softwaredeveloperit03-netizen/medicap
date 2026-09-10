import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { getRequiredFieldsMessage } from 'src/app/shared/form-validation.helper';
import {
  getDescriptionLines,
  getSavedEntryProductsLabel,
  parseClientServiceEntries,
  SavedServiceEntry,
  ServiceDescriptionEntry,
} from '../../clients/client-service.helper';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  clients;
  selectedProduct=[];

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.get_rights();
    this.getFregrence();
    this.getPackSizes();
    this.getFinishedProducts();
  }

  selectedClient: any = null;
  selectedServiceCategories: string[] = [];
  serviceCategory = '';
  serviceDescription = '';
  serviceDescriptionData = '';
  selectedProductCodes = '';
  categoryDescriptions: Record<string, ServiceDescriptionEntry[]> = {};
  categoryManualText: Record<string, string> = {};
  categoryDraftProducts: Record<string, any[]> = {};
  savedServiceEntries: SavedServiceEntry[] = [];
  serviceDetailsSaved = false;
  isSubmitting = false;
  finishedProducts: any[] = [];
  serviceCategoryOptions: Record<string, string[]> = {
    'Product Development': [
      'Formulation development',
      'process development',
      'method development',
      'scale-up',
      'exhibit batches',
      'validation batches',
    ],
    'Technology Transfer': [
      'Site transfer',
      'process transfer',
      'analytical method transfer',
      'document transfer',
    ],
    'Commercial Manufacturing': [
      'Manufacturing of clinical/commercial batches using client-owned or jointly developed products',
    ],
    'Packaging Services': [
      'Primary packaging',
      'secondary packaging',
      'labeling',
      'serialization',
      'repackaging',
    ],
    'Analytical Testing': [
      'Raw material testing',
      'in-process testing',
      'finished product testing',
      'stability testing',
      'method validation/verification',
    ],
  };
  serviceCategories = [
    'Analytical Testing',
    'Commercial Manufacturing',
    'Technology Transfer',
    'Product Development',
    'Packaging Services',
  ];
 
 
  category = '';

  
  products: any;

  getProductByCategory() {
    const params = `master/product.php?type=getProductByCategory`
      + `&category=${encodeURIComponent(this.category)}`;
    this.service.get(params).subscribe((response) => {
      this.products = response;
    });
  }

   packSizes;

  getPackSizes(){
    this.service.get('common.php?type=getPackSizes').subscribe(response=>{
      this.packSizes=response;
    });
  }
  
  isuser = 'No';
  ischecker = 'No';
  dept_head = 'No';
  rights;

  get_rights() {
   this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department') ).subscribe((response) => {
       this.rights = response;
       this.isuser = this.rights[0].isuser;
       this.ischecker = this.rights[0].ischecker;
       this.dept_head = this.rights[0].dept_head;
        this.getTempClient(this.dept_head);
    });
  }
  

 
  getTempClient(clientFor) {
    this.service.get('marketing/client.php?type=getClientForMarketing&clientFor='+clientFor).subscribe((response: any) => {
        this.clients = response;
    });
  }


  productList=[];

  isAddProduct = false;

  /** Display names for validation message (New Lead main form) */
  leadFormFieldNames: Record<string, string> = {
    client_code: 'Client Name',
    enqGeneratedThrough: 'Enquiry Generated Through',
    reference: 'Reference By',
    country: 'Req. For Country',
    serviceCategory: 'Service Category',
  };

  getFinishedProducts(): void {
    this.service.get('master/product.php?type=getBrandProductsLog').subscribe({
      next: (response: any) => {
        const products = Array.isArray(response) ? response : [];
        if (products.length) {
          this.setFinishedProducts(products);
          return;
        }
        this.service.get('master/product.php?type=getProductApprovedProduct').subscribe({
          next: (res: any) => this.setFinishedProducts(Array.isArray(res) ? res : []),
          error: () => { this.finishedProducts = []; },
        });
      },
      error: () => {
        this.service.get('master/product.php?type=getProductApprovedProduct').subscribe({
          next: (res: any) => this.setFinishedProducts(Array.isArray(res) ? res : []),
          error: () => { this.finishedProducts = []; },
        });
      },
    });
  }

  private setFinishedProducts(products: any[]): void {
    this.finishedProducts = products
      .filter((product) => product?.product_code)
      .map((product) => ({
        ...product,
        displayLabel: this.getProductLabel(product),
      }))
      .sort((a, b) => a.displayLabel.localeCompare(b.displayLabel));
  }

  onClientSelected(clientCode: string): void {
    const client = (this.clients || []).find((c: any) => c.client_code === clientCode);
    this.selectedClient = client || null;
    this.resetServiceCategoryState();
    if (client) {
      this.loadServiceFromClient(client);
    }
  }

  private loadServiceFromClient(client: any): void {
    const entries = parseClientServiceEntries(client);
    if (!entries.length && client.serviceCategory) {
      const categories = String(client.serviceCategory).split(',').map((s: string) => s.trim()).filter(Boolean);
      this.selectedServiceCategories = categories;
      this.serviceCategory = client.serviceCategory || '';
      this.serviceDescription = client.serviceDescription || '';
      this.serviceDescriptionData = client.serviceDescriptionData || '';
      this.selectedProductCodes = client.selectedProductCodes || '';
      return;
    }
    if (entries.length) {
      this.savedServiceEntries = entries.map((entry) => ({
        category: entry.category,
        products: (entry.products || []).map((p) => ({ ...p })),
        descriptions: (entry.descriptions || []).map((d) => ({ ...d })),
      }));
      this.selectedServiceCategories = [...new Set(this.savedServiceEntries.map((e) => e.category))];
      this.serviceDetailsSaved = true;
      this.syncServiceFormFields();
    }
  }

  getCategoryDraftProducts(category: string): any[] {
    if (!this.categoryDraftProducts[category]) {
      this.categoryDraftProducts[category] = [];
    }
    return this.categoryDraftProducts[category];
  }

  setCategoryDraftProducts(category: string, products: any[]): void {
    this.categoryDraftProducts[category] = products || [];
  }

  getProductLabel(product: any): string {
    const name = (product?.product_name || '').toString().trim();
    const code = (product?.product_code || '').toString().trim();
    if (name && code) {
      return `${name} (${code})`;
    }
    return name || code || 'Unnamed Product';
  }

  onServiceCategoriesChange(): void {
    Object.keys(this.categoryDescriptions).forEach((category) => {
      if (!this.selectedServiceCategories.includes(category)) {
        delete this.categoryDescriptions[category];
        delete this.categoryManualText[category];
        delete this.categoryDraftProducts[category];
      }
    });
    this.selectedServiceCategories.forEach((category) => {
      if (!this.categoryDescriptions[category]) {
        this.categoryDescriptions[category] = [];
        this.categoryManualText[category] = '';
      }
      if (category === 'Product Development' && !this.categoryDraftProducts[category]) {
        this.categoryDraftProducts[category] = [];
      }
    });
    this.serviceDetailsSaved = false;
    this.syncServiceFormFields();
  }

  isProductDevelopmentCategory(category: string): boolean {
    return category === 'Product Development';
  }

  private buildDescriptionsForCategory(category: string): ServiceDescriptionEntry[] {
    return (this.categoryDescriptions[category] || []).filter((entry) => (entry.label || '').trim());
  }

  formatEntryDescriptions(descriptions: ServiceDescriptionEntry[]): string {
    return getDescriptionLines(descriptions).join(', ');
  }

  clearCategoryDraft(category: string): void {
    this.categoryDescriptions[category] = [];
    this.categoryManualText[category] = '';
    if (this.isProductDevelopmentCategory(category)) {
      this.categoryDraftProducts[category] = [];
    }
  }

  addServiceCategoryEntry(category: string): void {
    const descriptions = this.buildDescriptionsForCategory(category);
    const products = this.getCategoryDraftProducts(category);
    if (this.isProductDevelopmentCategory(category) && !products.length) {
      alertify.error('Please select at least one finished product');
      return;
    }
    if (!descriptions.length) {
      alertify.error('Please select or enter at least one description');
      return;
    }
    const entry: SavedServiceEntry = {
      category,
      descriptions: descriptions.map((item) => ({ ...item })),
    };
    if (this.isProductDevelopmentCategory(category)) {
      entry.products = products.map((product) => ({
        product_code: product.product_code,
        product_name: product.product_name,
        displayLabel: product.displayLabel || this.getProductLabel(product),
      }));
    }
    this.savedServiceEntries = [...this.savedServiceEntries, entry];
    this.serviceDetailsSaved = false;
    this.clearCategoryDraft(category);
    this.syncServiceFormFields();
    alertify.success(`${category} added successfully`);
  }

  removeSavedServiceEntry(index: number): void {
    this.savedServiceEntries.splice(index, 1);
    this.serviceDetailsSaved = false;
    this.syncServiceFormFields();
  }

  saveAllServiceDetails(): void {
    if (!this.savedServiceEntries.length) {
      alertify.error('Please add at least one service category entry using ADD button');
      return;
    }
    this.serviceDetailsSaved = true;
    this.syncServiceFormFields();
    alertify.success('Service details saved successfully');
  }

  getSavedEntryProductsLabel(entry: SavedServiceEntry): string {
    return getSavedEntryProductsLabel(entry);
  }

  getDescriptionLinesForEntry(entry: SavedServiceEntry): string[] {
    return getDescriptionLines(entry.descriptions);
  }

  getDescriptionItems(category: string): string[] {
    return this.serviceCategoryOptions[category] || [];
  }

  isDescriptionItemSelected(category: string, item: string): boolean {
    return (this.categoryDescriptions[category] || []).some(
      (entry) => entry.label === item && !entry.isManual
    );
  }

  toggleDescriptionItem(category: string, item: string): void {
    if (!this.categoryDescriptions[category]) {
      this.categoryDescriptions[category] = [];
    }
    const existingIndex = this.categoryDescriptions[category].findIndex(
      (entry) => entry.label === item && !entry.isManual
    );
    if (existingIndex >= 0) {
      this.categoryDescriptions[category].splice(existingIndex, 1);
    } else {
      this.categoryDescriptions[category].push({ label: item, details: '' });
    }
    this.syncServiceFormFields();
  }

  getSelectedDescriptionEntries(category: string): ServiceDescriptionEntry[] {
    return this.categoryDescriptions[category] || [];
  }

  onDescriptionDetailsChange(category: string, item: string, details: string): void {
    const entry = (this.categoryDescriptions[category] || []).find((row) => row.label === item);
    if (entry) {
      entry.details = details;
      this.syncServiceFormFields();
    }
  }

  onCategoryManualChange(category: string): void {
    const manual = (this.categoryManualText[category] || '').trim();
    const presetEntries = (this.categoryDescriptions[category] || []).filter((entry) => !entry.isManual);
    if (manual) {
      this.categoryDescriptions[category] = [
        ...presetEntries,
        { label: manual, details: '', isManual: true },
      ];
    } else {
      this.categoryDescriptions[category] = presetEntries;
    }
    this.syncServiceFormFields();
  }

  syncServiceFormFields(): void {
    const savedCategories = [...new Set(this.savedServiceEntries.map((entry) => entry.category))];
    this.serviceCategory = savedCategories.length
      ? savedCategories.join(', ')
      : this.selectedServiceCategories.join(', ');
    const parts: string[] = [];
    const allProducts: any[] = [];
    this.savedServiceEntries.forEach((entry) => {
      let line = entry.category;
      if (entry.products?.length) {
        line += ` [Products: ${this.getSavedEntryProductsLabel(entry)}]`;
        allProducts.push(...entry.products);
      }
      line += `: ${this.formatEntryDescriptions(entry.descriptions)}`;
      parts.push(line);
    });
    this.serviceDescription = parts.join(' | ');
    this.selectedProductCodes = [...new Set(allProducts.map((product) => product.product_code).filter(Boolean))].join(', ');
    this.serviceDescriptionData = JSON.stringify({
      savedEntries: this.savedServiceEntries,
      categories: this.selectedServiceCategories,
      serviceDetailsSaved: this.serviceDetailsSaved,
      descriptions: this.categoryDescriptions,
      manualByCategory: this.categoryManualText,
      categoryDraftProducts: this.categoryDraftProducts,
    });
  }

  resetServiceCategoryState(): void {
    this.selectedServiceCategories = [];
    this.categoryDescriptions = {};
    this.categoryManualText = {};
    this.categoryDraftProducts = {};
    this.savedServiceEntries = [];
    this.serviceDetailsSaved = false;
    this.serviceCategory = '';
    this.serviceDescription = '';
    this.serviceDescriptionData = '';
    this.selectedProductCodes = '';
  }

  /** Display names for validation message (Add Product modal form) */
  productFormFieldNames: Record<string, string> = {
    tentative_launch_dt: 'Tentative Launch Dt.',
    product_name: 'Product Name',
    category: 'Category',
    fg_benchmark_product: 'FG Benchmark Product',
    fragrance_reference: 'Fragrance Reference',
    pack_size: 'Pack Size',
  };

  addProductToList(data: any) {
    if (data?.form?.markAllAsTouched) {
      data.form.markAllAsTouched();
    }
    if (!data.valid) {
      alertify.error(getRequiredFieldsMessage(data, this.productFormFieldNames));
      return;
    }
    let temp = data.value;
    temp['fg_benchmark_productCode'] = this.fg_benchmark_productCode;
    this.productList.push(temp)
    data.resetForm();
    this.isAddProduct = false;
    this.fg_benchmark_productCode = '';
    console.log('temp');
    console.log(temp);
  }
 
  del(index) {
    this.productList.splice(index, 1);
  }


  sample_required = '';
  Reference_sample = '';
 

  other_req: File;

  onFileChanged(event) {
    if (event.target.files.length === 1) {
      this.other_req = event.target.files[0];
    }
  }

  
 

  submit(data: any) {
    if (this.isSubmitting) {
      return;
    }
    if (data?.form?.markAllAsTouched) {
      data.form.markAllAsTouched();
    }
    if (!data.valid) {
      alertify.error(getRequiredFieldsMessage(data, this.leadFormFieldNames));
      return;
    }
    if (!this.selectedServiceCategories.length) {
      alertify.error('Please select at least one Service Category');
      return;
    }
    if (!this.serviceDetailsSaved || !this.savedServiceEntries.length) {
      alertify.error('Please add service details for each category and click Save Service Details');
      return;
    }

    const optionalWarnings = this.getOptionalFieldWarnings(data.value || {});
    if (optionalWarnings.length) {
      const message = `You did not fill: ${optionalWarnings.join(', ')}. Do you still want to proceed?`;
      alertify.confirm(message, () => {
        this.performSubmit(data);
      }, () => {});
      return;
    }

    this.performSubmit(data);
  }

  private getOptionalFieldWarnings(formValue: Record<string, any>): string[] {
    const warnings: string[] = [];
    if (!formValue.sample_required) {
      warnings.push('Sample Required');
    }
    if (formValue.sample_required === 'Yes' && !formValue.sample_qty) {
      warnings.push('Sample Qty. (NOS)');
    }
    if (!formValue.reference_sample) {
      warnings.push('Reference sample');
    }
    if (formValue.reference_sample === 'Provided' && !formValue.refNoOfunits) {
      warnings.push('No of Units');
    }
    if (!this.productList.length) {
      warnings.push('Product Details');
    }
    return warnings;
  }

  private performSubmit(data: any) {
    if (this.isSubmitting) {
      return;
    }
    this.isSubmitting = true;
    this.syncServiceFormFields();
    const temp = data.value;
   
    const uploadData = new FormData();
    for (const key in temp) {
      uploadData.append(key, temp[key]);
    }

    uploadData.set('serviceCategory', this.serviceCategory);
    uploadData.set('serviceDescription', this.serviceDescription);
    uploadData.set('serviceDescriptionData', this.serviceDescriptionData);
    uploadData.set('selectedProductCodes', this.selectedProductCodes);
    uploadData.delete('serviceCategorySelect');

    if (!uploadData.get('sample_required')) {
      uploadData.set('sample_required', this.sample_required || '');
    }
    if (!uploadData.get('reference_sample')) {
      uploadData.set('reference_sample', this.Reference_sample || '');
    }

    if (this.other_req !== undefined) {
      uploadData.append('other_req', this.other_req, this.other_req.name);
    }

    uploadData.append('productsList', JSON.stringify(this.productList));
  
    this.service.postForm('marketing/lead.php?type=saveLeadEnquiry', uploadData).subscribe({
      next: (httpResponse) => {
        const result = this.parseSaveResponse(httpResponse?.body);
        if (result?.status === 'success') {
          alertify.success('Lead saved successfully');
          this.productList = [];
          this.selectedClient = null;
          this.resetServiceCategoryState();
          data.resetForm();
          this.sample_required = '';
          this.Reference_sample = '';
          this.isSubmitting = false;
          this.router.navigate(['/marketing/leads/log']);
          return;
        }
        this.isSubmitting = false;
        alertify.error(result?.message || result?.status || 'Save failed. Please try again.');
      },
      error: (err) => {
        this.isSubmitting = false;
        const message = err?.error?.message || err?.message || 'Save failed. Please try again.';
        alertify.error(message);
      },
    });
  }

  private parseSaveResponse(body: string | null): any {
    if (!body) {
      return null;
    }
    const trimmed = body.trim();
    try {
      return JSON.parse(trimmed);
    } catch {
      const jsonStart = trimmed.indexOf('{');
      if (jsonStart >= 0) {
        try {
          return JSON.parse(trimmed.substring(jsonStart));
        } catch {
          return null;
        }
      }
      return null;
    }
  }
   

  isProductView = false;
  view(data){
    this.selectedProduct = data;
    this.isProductView = true;
  }


  fg_benchmark_productCode = '';
  getProductCode(ind){
    this.fg_benchmark_productCode = '';
    if(ind > 1){
       this.fg_benchmark_productCode = this.products[ind-2]?.product_code;
       console.log(this.fg_benchmark_productCode);
    }
    
  }
 

  countries: string[] = [
    "Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra",
    "Angola", "Anguilla", "Antigua & Barbuda", "Argentina", "Armenia",
    "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas",
    "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium",
    "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia",
    "Bosnia & Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria",
    "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada",
    "Chile", "China", "Colombia", "Costa Rica", "Croatia",
    "Cuba", "Cyprus", "Czech Republic", "Denmark", "Dominican Republic",
    "Ecuador", "Egypt", "El Salvador", "Estonia", "Ethiopia",
    "Fiji", "Finland", "France", "Germany", "Greece",
    "Hong Kong", "Hungary", "Iceland", "India", "Indonesia",
    "Iran", "Iraq", "Ireland", "Israel", "Italy",
    "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya",
    "Kuwait", "Latvia", "Lebanon", "Lithuania", "Luxembourg",
    "Malaysia", "Maldives", "Malta", "Mexico", "Monaco",
    "Mongolia", "Morocco", "Myanmar", "Nepal", "Netherlands",
    "New Zealand", "Nigeria", "Norway", "Oman", "Pakistan",
    "Panama", "Peru", "Philippines", "Poland", "Portugal",
    "Qatar", "Romania", "Russia", "Saudi Arabia", "Singapore",
    "Slovakia", "Slovenia", "South Africa", "South Korea", "Spain",
    "Sri Lanka", "Sweden", "Switzerland", "Syria", "Taiwan",
    "Tanzania", "Thailand", "Trinidad & Tobago", "Tunisia", "Turkey",
    "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America",
    "Uruguay", "Uzbekistan", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe"
  ];






  fregrences;
  getFregrence() {
    this.service.get('master/color.php?type=getFregrence').subscribe((response) => {
        this.fregrences = response;
    });
  }


    checkFregrence(value){
      if(value=='OTHER'){
          const fregrenceName = prompt('Enter Other Fregrence....');
          let temp = {};
          temp['id'] = 0;
          temp['fregrenceName'] = fregrenceName;
          this.fregrences.push(temp);
        }
    }
  

 

}


