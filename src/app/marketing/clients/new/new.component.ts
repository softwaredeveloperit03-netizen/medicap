import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { getRequiredFieldsMessage } from 'src/app/shared/form-validation.helper';
import { stripToCanadianPhoneDigits } from 'src/app/shared/validators/canadian-phone.validator';
import {
  getDescriptionLines,
  getSavedEntryProductsLabel,
  SavedServiceEntry,
  ServiceDescriptionEntry,
} from '../client-service.helper';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {

  refered_by = 'Direct Customer';
  branches = [];
  isPerson = false;
  isBranch = false;
  personData = [];
  agents;

  clients;
  country = 'India';
  b_country = 'India';

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.getAgents();
    this.getclientlist();
    this.getTempClientForRegisterByLeadRegiId();
    this.getFinishedProducts();
  }

  createGroup = 'NO';
 
  openPerson(){
    this.isPerson = !this.isPerson;
  }
  openBranch(){
    this.isBranch = !this.isBranch;
  }

  getAgents() {
    this.service.get('marketing/agent.php?type=getApprovedAgents').subscribe((response) => {
        this.agents = response;
    });
  }

  clientFrom = 'Existing';

  getclientlist() {
    this.service.get('marketing/client.php?type=getClientsLog').subscribe((response: any) => {
      this.clients = response;
    });
  }

  tempClients;
  getTempClientForRegisterByLeadRegiId() {
    this.service.get('marketing/client.php?type=getTempClientForRegisterByLeadRegiId').subscribe((response: any) => {
      this.tempClients = response;
    });
  }





  LglNm = '';
  TrdNm = '';
  client_type = '';
  category = '';
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
  finishedProducts: any[] = [];
  pendingFinishedProductCodes: string[] = [];
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
  contactPerson = '';
  designation = '';
  mobNo = '';
  email = '';
  communicationPreference = '';
  address = '';
  billingAddress = '';
  state = '';
  city = '';
  pincode = '';
  agent_no = '';



  selectedResult = [];

  fetchDataToClientForm(ind){

    this.selectedResult = this.tempClients[ind-1];

    this.LglNm = this.selectedResult['LglNm'];
    this.TrdNm = this.selectedResult['TrdNm'];
    this.client_type = this.selectedResult['client_type'];
    this.category = this.selectedResult['category'];
    this.serviceCategory = this.selectedResult['serviceCategory'] || '';
    this.serviceDescription = this.selectedResult['serviceDescription'] || '';
    this.applyServiceDataFromSaved();
    this.contactPerson = this.selectedResult['contactPerson'];
    this.designation = this.selectedResult['designation'];
    this.mobNo = this.selectedResult['mobNo'];
    this.email = this.selectedResult['email'];
    this.communicationPreference = this.selectedResult['communicationPreference'];
    this.address = this.selectedResult['address'];
    this.billingAddress = this.selectedResult['billingAddress'];
    this.country = this.selectedResult['country'];
    this.state = this.selectedResult['state'];
    this.city = this.selectedResult['city'];
    this.pincode = this.selectedResult['pincode'];
    this.refered_by = this.selectedResult['refered_by'];
    this.agent_no = this.selectedResult['agent_no'];

  }





 

  getFinishedProducts(): void {
    this.service.get('master/product.php?type=getBrandProductsLog').subscribe({
      next: (response: any) => {
        const products = Array.isArray(response) ? response : [];
        if (products.length) {
          this.setFinishedProducts(products);
          return;
        }
        this.loadApprovedProductsFallback();
      },
      error: () => this.loadApprovedProductsFallback(),
    });
  }

  private loadApprovedProductsFallback(): void {
    this.service.get('master/product.php?type=getProductApprovedProduct').subscribe({
      next: (response: any) => {
        const products = Array.isArray(response) ? response : [];
        this.setFinishedProducts(products);
      },
      error: () => {
        this.finishedProducts = [];
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

    if (this.pendingFinishedProductCodes.length) {
      this.restoreSavedProductsFromCodes();
      this.pendingFinishedProductCodes = [];
      this.syncServiceFormFields();
    }
  }

  private restoreSavedProductsFromCodes(): void {
    const products = this.finishedProducts.filter((product) =>
      this.pendingFinishedProductCodes.includes(product.product_code)
    );
    if (products.length) {
      this.categoryDraftProducts['Commercial Manufacturing'] = products;
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
      if (category === 'Commercial Manufacturing' && !this.categoryDraftProducts[category]) {
        this.categoryDraftProducts[category] = [];
      }
    });

    this.serviceDetailsSaved = false;
    this.syncServiceFormFields();
  }

  isCommercialManufacturingCategory(category: string): boolean {
    return category === 'Commercial Manufacturing';
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
    if (this.isCommercialManufacturingCategory(category)) {
      this.categoryDraftProducts[category] = [];
    }
  }

  addServiceCategoryEntry(category: string): void {
    const descriptions = this.buildDescriptionsForCategory(category);
    const products = this.getCategoryDraftProducts(category);

    if (this.isCommercialManufacturingCategory(category) && !products.length) {
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

    if (this.isCommercialManufacturingCategory(category)) {
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

  getDescriptionLines(descriptions: ServiceDescriptionEntry[]): string[] {
    return getDescriptionLines(descriptions);
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

  applyServiceDataFromSaved(): void {
    this.selectedServiceCategories = [];
    this.categoryDescriptions = {};
    this.categoryManualText = {};
    this.categoryDraftProducts = {};
    this.savedServiceEntries = [];
    this.serviceDetailsSaved = false;

    if (!this.serviceCategory && !this.serviceDescription) {
      this.syncServiceFormFields();
      return;
    }

    const savedData = this.selectedResult['serviceDescriptionData'];
    if (savedData) {
      try {
        const parsed = typeof savedData === 'string' ? JSON.parse(savedData) : savedData;
        this.selectedServiceCategories = parsed.categories || [];
        this.savedServiceEntries = parsed.savedEntries || [];
        this.serviceDetailsSaved = !!parsed.serviceDetailsSaved;
        this.categoryDescriptions = parsed.descriptions || {};
        this.categoryManualText = parsed.manualByCategory || {};
        this.categoryDraftProducts = parsed.categoryDraftProducts || {};
        // Migrate older drafts saved under Product Development
        if (
          this.categoryDraftProducts['Product Development']?.length &&
          !this.categoryDraftProducts['Commercial Manufacturing']?.length
        ) {
          this.categoryDraftProducts['Commercial Manufacturing'] =
            this.categoryDraftProducts['Product Development'];
          delete this.categoryDraftProducts['Product Development'];
        }

        const savedProducts = (parsed.savedEntries || [])
          .flatMap((entry: SavedServiceEntry) => entry.products || []);
        this.pendingFinishedProductCodes = savedProducts
          .map((product: any) => product.product_code)
          .filter(Boolean);

        if (!this.finishedProducts.length && this.pendingFinishedProductCodes.length) {
          this.getFinishedProducts();
        } else if (this.pendingFinishedProductCodes.length) {
          this.restoreSavedProductsFromCodes();
          this.pendingFinishedProductCodes = [];
        }

        if (!this.selectedServiceCategories.length && this.serviceCategory) {
          this.selectedServiceCategories = this.serviceCategory
            .split(',')
            .map((part) => part.trim())
            .filter(Boolean);
        }

        this.syncServiceFormFields();
        return;
      } catch {
        // fall back to legacy parsing below
      }
    }

    this.selectedServiceCategories = this.serviceCategory
      .split(',')
      .map((part) => part.trim())
      .filter(Boolean);

    this.selectedServiceCategories.forEach((category) => {
      this.categoryDescriptions[category] = [];
      this.categoryManualText[category] = '';
      if (this.isCommercialManufacturingCategory(category)) {
        this.categoryDraftProducts[category] = [];
      }
    });

    this.syncServiceFormFields();
  }

  /** Display names for form controls (for validation message) */
  clientFormFieldNames: Record<string, string> = {
    clientFrom: 'Client From',
    tempClientCode: 'Select Temp. Client',
    LglNm: 'Client Name (Legal Name)',
    TrdNm: 'Brand Name',
    client_type: 'Type of Client',
    category: 'Client Category',
    serviceCategory: 'Service Category',
    serviceDescription: 'Service Description',
    selectedProductCodes: 'Finished Products',
    dateOfOnboarding: 'Date of Onboarding',
    clientGroup: 'Select Client Group',
    contactPerson: 'Contact Person Name',
    designation: 'Designation',
    mobNo: 'Phone (Mobile / Landline)',
    email: 'Email ID',
    communicationPreference: 'Communication Preference',
    address: 'Registered Office Address',
    billingAddress: 'Billing Address',
    country: 'Country',
    state: 'State / Province',
    city: 'City',
    pincode: 'Postal Code',
    state_code: 'State Code',
    gst_no: 'GST / VAT / Tax ID',
    refered_by: 'Referred By',
    agent_no: 'Agent Name',
  };

  submit(data: any) {
    if (data?.form?.markAllAsTouched) {
      data.form.markAllAsTouched();
    }
    if (!data.valid) {
      alertify.error(getRequiredFieldsMessage(data, this.clientFormFieldNames));
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
    let temp = data.value;
    temp['clientGroup'] = temp['clientGroup'] || 'NA';
    temp['serviceCategory'] = this.serviceCategory;
    temp['serviceDescription'] = this.serviceDescription;
    temp['serviceDescriptionData'] = this.serviceDescriptionData;
    temp['selectedProductCodes'] = this.selectedProductCodes;
    temp['branches'] = this.branches;
    temp['personData'] = this.personData;
    temp['agree_name'] = this.selectedResult?.['agree_name'] || '';
    temp['clientFrom'] = this.clientFrom;

    this.service.postJson('marketing/client.php?type=saveClient', JSON.stringify(temp)).subscribe({
      next: (response) => {
        if (response?.['status'] === 'success') {
          alertify.success('Client saved successfully');
          data.resetForm();
          this.branches = [];
          this.personData = [];
          this.selectedServiceCategories = [];
          this.categoryDescriptions = {};
          this.categoryManualText = {};
          this.categoryDraftProducts = {};
          this.savedServiceEntries = [];
          this.serviceDetailsSaved = false;
          this.syncServiceFormFields();
          this.router.navigate(['/marketing/clients/approval']);
        } else {
          const msg = response?.['message'] || response?.['status'] || 'Save failed. Please try again.';
          alertify.error(msg);
        }
      },
      error: () => {
        alertify.error('Save failed. Please check your connection and try again.');
      },
    });
  }



  personFormFieldNames: Record<string, string> = {
    personContactPerson: 'Contact Person Name',
    personDesignation: 'Designation',
    personMobNo: 'Phone',
    personEmail: 'Email ID',
    personCommunicationPreference: 'Communication Preference',
  };

  addPerson(data: any) {
    if (data?.form?.markAllAsTouched) {
      data.form.markAllAsTouched();
    }
    if (!data.valid) {
      alertify.error(getRequiredFieldsMessage(data, this.personFormFieldNames));
      return;
    }
    const temp = {
      contactPerson: data.value.personContactPerson,
      designation: data.value.personDesignation,
      mobNo: stripToCanadianPhoneDigits(data.value.personMobNo),
      email: data.value.personEmail,
      communicationPreference: data.value.personCommunicationPreference,
    };
    this.personData.push(temp);
    data.resetForm();
    this.isPerson = false;
  }

  delPerson(index) {
    this.personData.splice(index, 1);
  }

  branchFormFieldNames: Record<string, string> = {
    branch_name: 'Branch Name',
    address: 'Address',
    country: 'Country',
    state: 'State / Province',
    city: 'City',
    pincode: 'Postal Code',
    gst_no: 'GST / VAT / Tax ID',
  };

  add(data: any) {
    if (data?.form?.markAllAsTouched) {
      data.form.markAllAsTouched();
    }
    if (!data.valid) {
      alertify.error(getRequiredFieldsMessage(data, this.branchFormFieldNames));
      return;
    }
    let temp = data.value;
    this.branches.push(temp)
    data.resetForm();
    this.isBranch = false;
  }

  delBranch(ind) {
    this.branches.splice(ind, 1);
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



  states: string[] = ["Andhra Pradesh","Andaman and Nicobar Islands","Arunachal Pradesh","Assam",
    "Bihar","Chandigarh","Chhattisgarh","Dadra and Nagar Haveli","Daman and Diu","Delhi",
    "Lakshadweep","Puducherry","Goa","Gujarat","Haryana","Himachal Pradesh","Jammu and Kashmir",
    "Jharkhand","Karnataka","Kerala","Madhya Pradesh","Maharashtra","Manipur","Meghalaya","Mizoram",
    "Nagaland","Odisha","Punjab","Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura",
    "Uttar Pradesh","Uttarakhand","West Bengal"
  ];




  
}