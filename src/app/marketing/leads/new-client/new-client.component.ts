import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { getRequiredFieldsMessage } from 'src/app/shared/form-validation.helper';
import { stripToCanadianPhoneDigits, formatCanadianPhone } from 'src/app/shared/validators/canadian-phone.validator';
import {
  getDescriptionLines,
  getSavedEntryProductsLabel,
  parseClientServiceEntries,
  SavedServiceEntry,
  ServiceDescriptionEntry,
} from '../../clients/client-service.helper';
import * as ExcelJS from 'exceljs';
declare let alertify;

@Component({
  selector: 'app-new-client',
  templateUrl: './new-client.component.html',
  styleUrls: ['./new-client.component.css']
})
export class NewClientComponent implements OnInit {

  isView = false;
  isNew = false;
  isEdit = false;
  loading = false;

  refered_by = 'Direct Customer';
  country = 'India';
  b_country = 'India';
  email = '';
  mobNo = '';

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

  agents;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.get_rights();
    this.getAgents();
    this.getFinishedProducts();
  }

  isuser = 'No';
  ischecker = 'No';
  dept_head = 'No';
  rights;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') + '&dep_name=' + localStorage.getItem('department')).subscribe((response) => {
      this.rights = response;
      this.isuser = this.rights[0].isuser;
      this.ischecker = this.rights[0].ischecker;
      this.dept_head = this.rights[0].dept_head;
      this.getTempClient(this.dept_head);
    });
  }

  getAgents() {
    this.service.get('marketing/agent.php?type=getApprovedAgents').subscribe((response) => {
      this.agents = response;
    });
  }

  clients: any[] = [];
  clientSearch = '';

  getTempClient(clientFor) {
    this.loading = true;
    this.service.get('marketing/client.php?type=getTempClient&clientFor=' + clientFor).subscribe({
      next: (response: any) => {
        this.clients = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.clients = [];
        this.loading = false;
      },
    });
  }

  getFilteredClients(): any[] {
    if (!this.clients || !Array.isArray(this.clients)) {
      return [];
    }
    const q = (this.clientSearch || '').trim().toLowerCase();
    if (!q) {
      return this.clients;
    }
    return this.clients.filter((c: any) => {
      const name = (c.LglNm || '').toLowerCase();
      const brand = (c.TrdNm || '').toLowerCase();
      return name.indexOf(q) !== -1 || brand.indexOf(q) !== -1;
    });
  }

  downloadClientList() {
    const list = this.getFilteredClients();
    if (!list || list.length === 0) {
      alertify.warning('No data to download.');
      return;
    }
    const formatDate = (d: any) => {
      if (!d) return '-';
      const dt = new Date(d);
      return isNaN(dt.getTime()) ? '-' : dt.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
    };
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('New Client List', { pageSetup: { orientation: 'landscape' } });
    const headers = ['Sr.No.', 'Enquiry Client Code', 'Client Name', 'Brand Name', 'Type of Client', 'Status', 'Entry By', 'Entry On'];
    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0e4370' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = {
        top: { style: 'thin' },
        bottom: { style: 'thin' },
        left: { style: 'thin' },
        right: { style: 'thin' },
      };
    });
    ws.getRow(1).height = 22;
    list.forEach((c: any, i: number) => {
      ws.addRow([
        i + 1,
        c.client_code || '-',
        c.LglNm || '-',
        c.TrdNm || '-',
        c.client_type || '-',
        (c.status || '-').toUpperCase(),
        c.entryByName || '-',
        formatDate(c.entry_date)
      ]);
    });
    const thinBorder = { top: { style: 'thin' as const }, bottom: { style: 'thin' as const }, left: { style: 'thin' as const }, right: { style: 'thin' as const } };
    for (let r = 2; r <= list.length + 1; r++) {
      const row = ws.getRow(r);
      row.height = 20;
      row.eachCell((cell) => {
        cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
        cell.border = thinBorder;
      });
    }
    const colWidths = [8, 18, 18, 18, 14, 10, 14, 14];
    ws.columns.forEach((col, idx) => { col.width = colWidths[idx] ?? 14; });
    wb.xlsx.writeBuffer().then((buffer: ArrayBuffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `new-client-list-${new Date().toISOString().slice(0, 10)}.xlsx`;
      a.click();
      URL.revokeObjectURL(url);
    });
  }

  newClientFormFieldNames: Record<string, string> = {
    LglNm: 'Client Name (Legal Name)',
    TrdNm: 'Brand Name',
    client_type: 'Type of Client',
    category: 'Client Category',
    serviceCategory: 'Service Category',
    serviceDescription: 'Service Description',
    selectedProductCodes: 'Finished Products',
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
    refered_by: 'Referred By',
    agent_no: 'Agent Name',
  };

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

  openNewForm(): void {
    this.resetServiceCategoryState();
    this.mobNo = '';
    this.isNew = true;
  }

  closeNewForm(): void {
    this.isNew = false;
    this.mobNo = '';
    this.resetServiceCategoryState();
  }

  formatPhoneForDisplay(phone: string): string {
    const digits = stripToCanadianPhoneDigits(phone || '');
    return digits.length === 10 ? formatCanadianPhone(digits) : (phone || '');
  }

  formatMobNoOnBlur(): void {
    if (this.mobNo) {
      this.mobNo = this.formatPhoneForDisplay(this.mobNo);
    }
  }

  onMobNoFocus(): void {
    if (!this.mobNo) {
      return;
    }
    const digits = stripToCanadianPhoneDigits(this.mobNo);
    if (digits.length === 10) {
      this.mobNo = digits;
    }
  }

  formatSelectedClientMobNo(): void {
    if (this.selectedClient?.['mobNo']) {
      this.selectedClient['mobNo'] = this.formatPhoneForDisplay(this.selectedClient['mobNo']);
    }
  }

  private sanitizeText(value: unknown): string {
    return String(value ?? '').replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '');
  }

  private buildSavePayload(formValue: Record<string, any>, phoneDigits: string): Record<string, unknown> {
    return {
      LglNm: this.sanitizeText(formValue.LglNm),
      TrdNm: this.sanitizeText(formValue.TrdNm),
      client_type: this.sanitizeText(formValue.client_type),
      category: this.sanitizeText(formValue.category),
      contactPerson: this.sanitizeText(formValue.contactPerson),
      designation: this.sanitizeText(formValue.designation),
      mobNo: phoneDigits,
      email: this.sanitizeText(formValue.email),
      communicationPreference: this.sanitizeText(formValue.communicationPreference),
      address: this.sanitizeText(formValue.address),
      billingAddress: this.sanitizeText(formValue.billingAddress),
      country: this.sanitizeText(formValue.country),
      state: this.sanitizeText(formValue.state),
      city: this.sanitizeText(formValue.city),
      pincode: this.sanitizeText(formValue.pincode),
      refered_by: this.sanitizeText(formValue.refered_by),
      agent_no: this.sanitizeText(formValue.agent_no),
      enquiryTicketSize: this.sanitizeText(formValue.enquiryTicketSize),
      NoOfProducts: this.sanitizeText(formValue.NoOfProducts),
      approxTurnover: this.sanitizeText(formValue.approxTurnover),
      companySize: this.sanitizeText(formValue.companySize),
      exitingBussiness: this.sanitizeText(formValue.exitingBussiness),
      serviceCategory: this.sanitizeText(this.serviceCategory),
      serviceDescription: this.sanitizeText(this.serviceDescription),
      serviceDescriptionData: {
        savedEntries: this.savedServiceEntries.map((entry) => ({
          category: entry.category,
          products: (entry.products || []).map((product) => ({
            product_code: product.product_code || '',
            product_name: product.product_name || '',
            displayLabel: product.displayLabel || '',
          })),
          descriptions: (entry.descriptions || []).map((desc) => ({
            label: desc.label || '',
            details: this.sanitizeText(desc.details),
            isManual: !!desc.isManual,
          })),
        })),
      },
      selectedProductCodes: this.sanitizeText(this.selectedProductCodes),
    };
  }

  private saveTempClientRequest(formRef: any, phoneDigits: string) {
    this.syncServiceFormFields();

    const nativeForm: HTMLFormElement | null = formRef?.form?.nativeElement ?? null;
    let postData: FormData | string;

    if (nativeForm) {
      const formData = new FormData(nativeForm);
      formData.set('mobNo', phoneDigits);
      if (this.email) {
        formData.set('email', this.email);
      }
      formData.delete('serviceCategorySelect');
      formData.delete('clientSearch');
      postData = formData;
    } else {
      const formValue = formRef?.value || {};
      postData = JSON.stringify(this.buildSavePayload(formValue, phoneDigits));
    }

    this.service.post('marketing/client.php?type=saveTempClient', postData).subscribe({
      next: (response: any) => this.handleSaveTempClientResponse(response, formRef),
      error: (err) => {
        const message = err?.error?.message || err?.message || 'Save failed. Please check your connection and try again.';
        alertify.error(message);
      },
    });
  }

  private handleSaveTempClientResponse(response: any, formRef?: any) {
    let result = response;
    if (typeof response === 'string') {
      try {
        result = JSON.parse(response);
      } catch {
        alertify.error('Unexpected server response. Please try again.');
        return;
      }
    }
    if (result?.['status'] === 'success') {
      alertify.success('Client saved successfully');
      formRef?.resetForm?.();
      this.mobNo = '';
      this.resetServiceCategoryState();
      this.isView = false;
      this.isNew = false;
      this.getTempClient(this.dept_head);
      return;
    }
    const msg = result?.['message'] || result?.['status'] || 'Save failed. Please try again.';
    alertify.error(msg);
  }

  saveTempClient(data: any) {
    if (data?.form?.markAllAsTouched) {
      data.form.markAllAsTouched();
    }
    if (!data.valid) {
      alertify.error(getRequiredFieldsMessage(data, this.newClientFormFieldNames));
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

    const phoneDigits = stripToCanadianPhoneDigits(this.mobNo || data.value?.mobNo || '');
    if (phoneDigits.length !== 10) {
      alertify.error('Please enter a valid 10 digit phone number');
      return;
    }

    this.saveTempClientRequest(data, phoneDigits);
  }

  selectedClient: any = {};
  viewServiceEntries: SavedServiceEntry[] = [];

  view(item) {
    this.selectedClient = { ...item };
    if (this.selectedClient['mobNo']) {
      this.selectedClient['mobNo'] = this.formatPhoneForDisplay(this.selectedClient['mobNo']);
    }
    this.viewServiceEntries = parseClientServiceEntries(this.selectedClient);
    this.isView = true;
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
