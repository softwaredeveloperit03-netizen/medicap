import { Component, OnInit, ViewChild } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { NgForm } from '@angular/forms';
import * as XLSX from 'xlsx';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
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
  @ViewChild('poEntry') poEntry: NgForm;
  @ViewChild('productForm') productForm: NgForm;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getAllClients();
    this.getAllProducts();
    this.loadMasterPackSizes();
    this.getFinishedProducts();
  }

  mainGroup = '';
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

  pack_size;
  product_code = '';
  billing_type = '';
  allPackSizesMaster: any[] = [];


  clients;
  clients1;
  getAllClients() {
    this.service.get('marketing/ponewform.php?type=getAllClients').subscribe(response => {
      this.clients = response;
      this.clients1 = response;
    });
  }

    selectedClient1 = [];
    subGroupSeris = [];
    mainGroupclient_code = [];
    mainGroupName:any;
  getSubGroup(index){
    const client = this.clients1?.[index - 1];
    if (!client) {
      this.selectedClient1 = [];
      this.mainGroupName = '';
      this.mainGroup = '';
      this.mainGroupclient_code = [];
      this.subGroupSeris = [];
      this.client_code = '';
      this.resetServiceCategoryState();
      return;
    }
    this.selectedClient1 = client;
    this.mainGroupName = client['LglNm'];
    this.mainGroupclient_code = client['client_code'];
    this.subGroupSeris = client?.clientGroups || [];
    this.client_code = client?.client_code;
    this.resetServiceCategoryState();
    this.loadServiceFromClient(client);
  }

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

  products: any[] = [];
  getAllProducts() {
    this.service.get('marketing/ponewform.php?type=getAllProducts').subscribe((response: any) => {
      this.products = Array.isArray(response) ? response : [];
    }, () => {
      this.products = [];
    });
  }

  private ensureProductsLoaded(): Promise<any[]> {
    if (Array.isArray(this.products) && this.products.length) {
      return Promise.resolve(this.products);
    }
    return new Promise((resolve) => {
      this.service.get('marketing/ponewform.php?type=getAllProducts').subscribe({
        next: (response: any) => {
          this.products = Array.isArray(response) ? response : [];
          resolve(this.products);
        },
        error: () => {
          this.products = [];
          resolve([]);
        },
      });
    });
  }

  loadMasterPackSizes() {
    this.service.get('common.php?type=getPackSizes').subscribe((response) => {
      this.allPackSizesMaster = Array.isArray(response) ? response : [];
    });
  } 
 
  // getPackSize() {
  //   this.service.get('common.php?type=getPackSizesmar').subscribe(response => {
  //     this.pack_size = response;
  //   });
  // }
 

  productUnit = '';
  product_name = '';
  category='';
  getProductName(index){
    const product = this.products?.[index - 1];
    this.product_name = product?.product_name || '';
    this.productUnit = product?.unit || '';
    this.category = product?.category || '';
    this.loadPackSizesForProduct(product);
    this.calculateDeliveryDate();
  }

  /** Pack sizes from latest unit formula (unitformula_pm_dtl), same source as unit formula log. */
  private loadPackSizesForProduct(product: any): void {
    this.pack_size = [];
    this.selectedPackLabel = '';
    this.pack_sizes = '';
    this.packingStyle = 0;
    this.packingUnit = '';
    if (!product?.product_code) {
      return;
    }
    const cached = product.unit_formula_pack_sizes;
    if (Array.isArray(cached) && cached.length) {
      this.pack_size = this.normalizePackSizeList(cached);
      this.setFirstPackingStyle();
      return;
    }
    this.service
      .get(
        'marketing/ponewform.php?type=getPackSizesByProductCode&product_code=' +
          encodeURIComponent(product.product_code)
      )
      .subscribe((response) => {
        const list = Array.isArray(response) ? response : [];
        product.unit_formula_pack_sizes = list;
        this.pack_size = this.normalizePackSizeList(list);
        this.setFirstPackingStyle();
      });
  }


  pack_sizes = '';
  packingStyle = 0;
  packingUnit = '';
  selectedPackLabel = '';

  /** Normalize product.pack_sizes JSON (supports pack_size / pack_sizes keys). */
  private normalizePackSizeList(value: any): any[] {
    if (value === null || value === undefined || value === '') {
      return [];
    }
    if (typeof value === 'string') {
      const trimmed = value.trim();
      if (!trimmed || trimmed === 'NA') {
        return [];
      }
      try {
        return this.normalizePackSizeList(JSON.parse(trimmed));
      } catch {
        return [{ pack_size: trimmed }];
      }
    }
    if (Array.isArray(value)) {
      return value
        .map((item) => {
          if (typeof item === 'string') {
            const size = item.trim();
            let unit = '';
            if (size && this.allPackSizesMaster?.length) {
              const master = this.allPackSizesMaster.find(
                (m) => String(m?.pack_size ?? '').trim() === size
              );
              unit = String(master?.unit ?? '').trim();
            }
            return size ? { pack_size: size, pack_sizes: size, unit } : null;
          }
          if (!item || typeof item !== 'object') {
            return null;
          }
          const size = String(item.pack_size ?? item.pack_sizes ?? '').trim();
          let unit = String(item.unit ?? item.pack_unit ?? '').trim();
          if (!unit && size && this.allPackSizesMaster?.length) {
            const master = this.allPackSizesMaster.find(
              (m) => String(m?.pack_size ?? '').trim() === size
            );
            unit = String(master?.unit ?? '').trim();
          }
          if (!size && !unit) {
            return null;
          }
          return { pack_size: size, pack_sizes: size, unit };
        })
        .filter(Boolean) as any[];
    }
    if (typeof value === 'object') {
      return this.normalizePackSizeList([value]);
    }
    return [];
  }

  /** Display label like unit formula: 250- G */
  getPackSizeLabel(pack: any): string {
    if (!pack) {
      return '';
    }
    const size = String(pack.pack_size ?? pack.pack_sizes ?? '').trim();
    const unit = String(pack.unit ?? pack.pack_unit ?? '').trim();
    if (size && unit) {
      if (size.includes('-') || /[A-Za-z]/.test(size)) {
        return size;
      }
      return `${size}- ${unit}`;
    }
    return size || unit;
  }

  /** Default to the first available pack size for the selected product. */
  setFirstPackingStyle(){
    this.applyPackingStyle(0);
  }

  /** Called from pack-size select (selectedIndex: 0 = empty option). */
  getPackingStyleUnit(index){
    this.applyPackingStyle(index > 0 ? index - 1 : 0);
  }

  private applyPackingStyle(pos: number){
    this.pack_sizes = '';
    this.packingStyle = 0;
    this.packingUnit = '';
    this.selectedPackLabel = '';
    const list = this.pack_size || [];
    const pack = list[pos];
    if (!pack) {
      return;
    }
    const label = this.getPackSizeLabel(pack);
    const sizeRaw = String(pack.pack_size ?? pack.pack_sizes ?? label).trim();
    if (!sizeRaw) {
      return;
    }
    this.selectedPackLabel = label;
    this.pack_sizes = sizeRaw;
    this.packingStyle = parseInt(sizeRaw, 10) || 0;
    const unitFromField = String(pack.unit ?? pack.pack_unit ?? '').trim();
    this.packingUnit = unitFromField || sizeRaw.match(/[A-Za-z]+/g)?.[0] || '';
  }
   
  file: File;
  onFileChange($event) {
    this.file = $event.target.files[0];
  }
 
 
  productList = [];
  deliveryDateCalcPending = false;
  excelImportPending = false;
  deliveryDateBreakdown: any = null;
  deliveryCalcModalOpen = false;

  openDeliveryCalcModal(): void {
    this.deliveryCalcModalOpen = true;
  }

  private getTodayDateString(): string {
    const d = new Date();
    const y = d.getFullYear();
    const m = (d.getMonth() + 1).toString().padStart(2, '0');
    const day = d.getDate().toString().padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  /** Convert calendar value (Date or dd/mm/yyyy string) to yyyy-mm-dd for API. */
  dateValueToIso(value: any): string {
    if (value === null || value === undefined || value === '') {
      return '';
    }
    if (value instanceof Date && !isNaN(value.getTime())) {
      const y = value.getFullYear();
      const m = (value.getMonth() + 1).toString().padStart(2, '0');
      const d = value.getDate().toString().padStart(2, '0');
      return `${y}-${m}-${d}`;
    }
    const str = value.toString().trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
      return str;
    }
    const slash = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (slash) {
      const dd = slash[1].padStart(2, '0');
      const mm = slash[2].padStart(2, '0');
      return `${slash[3]}-${mm}-${dd}`;
    }
    const parsed = new Date(str);
    if (!isNaN(parsed.getTime())) {
      return this.dateValueToIso(parsed);
    }
    return '';
  }

  /** Parse yyyy-mm-dd (or similar) to Date for p-calendar. */
  isoToDateValue(value: any): Date | null {
    if (!value) {
      return null;
    }
    if (value instanceof Date && !isNaN(value.getTime())) {
      return value;
    }
    const iso = this.dateValueToIso(value);
    if (!iso) {
      return null;
    }
    const [y, m, d] = iso.split('-').map((x) => parseInt(x, 10));
    if (!y || !m || !d) {
      return null;
    }
    return new Date(y, m - 1, d);
  }

  private getOrderDateForCalc(): string {
    const raw = this.poEntry?.value?.po_date;
    return this.dateValueToIso(raw) || this.getTodayDateString();
  }

  private fetchDeliveryDateCalc(
    productCode: string,
    planQty: number,
    planUnit: string,
    orderDate: string
  ) {
    const url =
      'marketing/po.php?type=calculateForecastDeliveryDate' +
      '&product_code=' + encodeURIComponent(productCode) +
      '&plan_qty=' + planQty +
      '&plan_unit=' + encodeURIComponent(planUnit || '') +
      '&order_date=' + encodeURIComponent(orderDate || '');
    return this.service.get(url).pipe(catchError(() => of({ status: 'error' })));
  }

  calculateDeliveryDate(): void {
    const planQty = Number(this.productForm?.value?.planQty);
    if (!this.product_code || !planQty || planQty <= 0) {
      return;
    }

    this.deliveryDateCalcPending = true;
    this.fetchDeliveryDateCalc(
      this.product_code,
      planQty,
      this.productUnit || '',
      this.getOrderDateForCalc()
    ).subscribe((response: any) => {
      this.deliveryDateCalcPending = false;
      if (response?.status === 'success') {
        this.deliveryDateBreakdown = response;
        if (response?.delivery_date && this.productForm) {
          this.productForm.form.patchValue({
            deliveryDate: response.delivery_date,
          });
        }
      }
    });
  }

  addProduct(data) {
    if(!data.valid){
      alertify.error("All Field Required!!!!!!!!");
      return;
    }
 
    const temp = { ...data.value };
    temp.planMonth = this.dateValueToIso(temp.planMonth);
    temp.deliveryDate = this.dateValueToIso(temp.deliveryDate);
    temp['packingStyle'] = this.packingStyle;
    temp['product_name'] = this.product_name;
    temp['packingUnit'] = this.packingUnit;
    temp['packSizeLabel'] = this.selectedPackLabel;
    temp['category'] = this.category || '';
    this.productList.push(temp);
    data.reset();
    this.category = '';
    this.product_code = '';
    this.product_name = '';
    this.pack_size = [];
    this.packingStyle = 0;
    this.packingUnit = '';
    this.selectedPackLabel = '';
    this.productUnit = '';
  }
   
  deleteProduct(index) {
    this.productList.splice(index, 1);
  }

  downloadProductTemplate(): void {
    const headers = [
      'Plan Month',
      'Product Code',
      'Product Name',
      'Plan Qty',
      'Remark',
    ];
    const sample = [
      ['01/05/2026', 'P9999047', 'Travel Kit', '1000', ''],
    ];
    const ws = XLSX.utils.aoa_to_sheet([headers, ...sample]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Forecast Products');
    XLSX.writeFile(wb, 'forecast_product_upload_template.xlsx');
  }

  onProductExcelSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input?.files?.[0];
    if (!file) {
      return;
    }

    if (!this.mainGroup) {
      alertify.error('Please select Main Group first.');
      input.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = (e: ProgressEvent<FileReader>) => {
      try {
        const data = new Uint8Array(e.target?.result as ArrayBuffer);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheet = workbook.Sheets[workbook.SheetNames[0]];
        const rows: any[][] = XLSX.utils.sheet_to_json(sheet, {
          header: 1,
          defval: '',
        });
        this.ensureProductsLoaded().then((products) => {
          if (!products.length) {
            alertify.error('Product master list is empty. Cannot match Excel product codes.');
            return;
          }
          this.importProductsFromExcelRows(rows);
        });
      } catch {
        alertify.error('Unable to read Excel file.');
      } finally {
        input.value = '';
      }
    };
    reader.onerror = () => {
      alertify.error('Unable to read Excel file.');
      input.value = '';
    };
    reader.readAsArrayBuffer(file);
  }

  private getExcelColumnMap(headerRow: any[]): {
    planMonth: number;
    productCode: number;
    planQty: number;
    deliveryDate: number;
    remark: number;
  } {
    const map: Record<string, number> = {};
    (headerRow || []).forEach((cell, i) => {
      const key = (cell ?? '').toString().trim().toLowerCase();
      if (key.includes('plan month')) {
        map.planMonth = i;
      } else if (key.includes('product code') || key === 'code') {
        map.productCode = i;
      } else if (key.includes('plan qty') || key === 'qty' || key.includes('quantity')) {
        map.planQty = i;
      } else if (key.includes('delivery')) {
        map.deliveryDate = i;
      } else if (key.includes('remark')) {
        map.remark = i;
      }
    });

    const hasNameCol =
      (headerRow || []).some((c) =>
        (c ?? '').toString().trim().toLowerCase().includes('product name')
      );
    const hasDeliveryCol =
      map.deliveryDate !== undefined ||
      (headerRow || []).some((c) =>
        (c ?? '').toString().trim().toLowerCase().includes('delivery')
      );

    return {
      planMonth: map.planMonth ?? 0,
      productCode: map.productCode ?? 1,
      planQty: map.planQty ?? (hasNameCol ? 3 : 2),
      deliveryDate: hasDeliveryCol ? (map.deliveryDate ?? (hasNameCol ? 4 : 3)) : -1,
      remark:
        map.remark ??
        (hasNameCol ? (hasDeliveryCol ? 5 : 4) : hasDeliveryCol ? 4 : 3),
    };
  }

  private normalizeExcelProductCode(value: any): string {
    if (value === null || value === undefined || value === '') {
      return '';
    }
    if (typeof value === 'number' && !isNaN(value)) {
      const n = Math.round(value);
      return n.toString();
    }
    return value.toString().trim();
  }

  private importProductsFromExcelRows(rows: any[][]): void {
    if (!rows?.length) {
      alertify.error('No product rows found in Excel.');
      return;
    }

    const col = this.getExcelColumnMap(rows[0]);
    const dataRows = rows
      .slice(1)
      .filter((r) => r.some((c) => (c ?? '').toString().trim() !== ''));

    if (!dataRows.length) {
      alertify.error('No product rows found in Excel.');
      return;
    }

    const validRows: any[] = [];
    const skippedRows: string[] = [];
    let addedCount = 0;

    dataRows.forEach((r, idx) => {
      const excelRowNo = idx + 2;
      const productCode = this.normalizeExcelProductCode(r[col.productCode]);
      const planQty = Number(r[col.planQty]);
      const planMonth = this.normalizeExcelDate(r[col.planMonth]);
      const deliveryDate =
        col.deliveryDate >= 0
          ? this.normalizeExcelDate(r[col.deliveryDate])
          : '';
      const remark = (r[col.remark] ?? '').toString().trim();

      if (!productCode) {
        skippedRows.push(`Row ${excelRowNo}: Product Code is missing`);
        return;
      }
      if (!planMonth) {
        skippedRows.push(`Row ${excelRowNo}: Invalid Plan Month`);
        return;
      }
      if (!planQty || planQty <= 0) {
        skippedRows.push(`Row ${excelRowNo}: Invalid Plan Qty`);
        return;
      }

      const product = this.findProductByCode(productCode);
      if (!product) {
        skippedRows.push(
          `Row ${excelRowNo}: Product Code "${productCode}" is not in the product dropdown`
        );
        return;
      }

      validRows.push({
        product,
        planMonth,
        planQty,
        deliveryDate,
        remark,
        excelRowNo,
      });
    });

    if (!validRows.length) {
      if (skippedRows.length) {
        const msg = skippedRows.slice(0, 10).join('\n');
        alertify.error(
          skippedRows.length > 10
            ? `${msg}\n...and ${skippedRows.length - 10} more row(s) skipped.`
            : msg
        );
      }
      return;
    }

    const orderDate = this.getOrderDateForCalc();
    if (!this.poEntry?.value?.po_date) {
      alertify.warning(
        'Order Date is not set — today is used as base date for delivery calculation.'
      );
    }

    this.excelImportPending = true;
    const calcRequests = validRows.map((row) =>
      this.fetchDeliveryDateCalc(
        row.product.product_code,
        row.planQty,
        row.product.unit || '',
        orderDate
      )
    );

    forkJoin(calcRequests).subscribe({
      next: (responses: any[]) => {
        this.excelImportPending = false;

        validRows.forEach((row, idx) => {
          const calc = responses[idx];
          let resolvedDeliveryDate = '';

          if (calc?.status === 'success' && calc?.delivery_date) {
            resolvedDeliveryDate = calc.delivery_date;
          } else if (row.deliveryDate) {
            resolvedDeliveryDate = row.deliveryDate;
          } else {
            resolvedDeliveryDate = this.resolveDeliveryDateFallback(row.planMonth);
          }

          if (!resolvedDeliveryDate) {
            skippedRows.push(`Row ${row.excelRowNo}: Could not calculate Delivery Date`);
            return;
          }

          const builtRow = this.buildProductRowFromMaster(row.product, {
            planMonth: row.planMonth,
            planQty: row.planQty,
            deliveryDate: resolvedDeliveryDate,
            remark: row.remark,
          });

          const duplicate = this.productList.some(
            (p) =>
              p.product_code === builtRow.product_code &&
              p.planMonth === builtRow.planMonth &&
              p.deliveryDate === builtRow.deliveryDate
          );
          if (!duplicate) {
            this.productList.push(builtRow);
            addedCount++;
          }
        });

        if (skippedRows.length) {
          const msg = skippedRows.slice(0, 10).join('\n');
          alertify.error(
            skippedRows.length > 10
              ? `${msg}\n...and ${skippedRows.length - 10} more row(s) skipped.`
              : msg
          );
        }

        if (addedCount > 0) {
          alertify.success(
            `${addedCount} product row(s) added from Excel with calculated delivery dates.`
          );
        } else if (!skippedRows.length) {
          alertify.error('No new rows to add (all rows may be duplicates).');
        } else if (!addedCount && skippedRows.length) {
          alertify.error('No rows were added. Fix skipped rows and upload again.');
        }
      },
      error: () => {
        this.excelImportPending = false;
        alertify.error('Delivery date calculation failed during Excel import.');
      },
    });
  }

  private resolveDeliveryDateFallback(planMonth: string): string {
    const planIso = this.dateValueToIso(planMonth);
    if (planIso) {
      return planIso;
    }
    return this.getOrderDateForCalc();
  }

  private normalizeProductCodeKey(code: string): string {
    const raw = (code || '').toString().trim().toLowerCase();
    if (!raw) {
      return '';
    }
    const withoutPrefix = raw.replace(/^p(?=\d)/, '');
    const digits = withoutPrefix.replace(/\D/g, '');
    if (digits) {
      return digits.replace(/^0+/, '') || '0';
    }
    return withoutPrefix;
  }

  private findProductByCode(code: string): any {
    const codeKey = this.normalizeProductCodeKey(code);
    if (!codeKey) {
      return null;
    }
    const list = this.products || [];

    return list.find((p: any) => {
      const pCode = this.normalizeProductCodeKey(p.product_code || '');
      if (!pCode) {
        return false;
      }
      if (pCode === codeKey) {
        return true;
      }
      return pCode.endsWith(codeKey) || codeKey.endsWith(pCode);
    });
  }

  private buildProductRowFromMaster(product: any, temp: any): any {
    const packSizes = this.normalizePackSizeList(
      product.unit_formula_pack_sizes ?? product.pack_sizes
    );
    const firstPack = packSizes[0] || {};
    const firstLabel = this.getPackSizeLabel(firstPack);
    const firstRaw = String(firstPack.pack_size ?? firstPack.pack_sizes ?? firstLabel).trim();
    const packingStyle = parseInt(firstRaw, 10) || 0;
    const packingUnit = String(firstPack.unit ?? firstPack.pack_unit ?? '').trim()
      || firstRaw.match(/[A-Za-z]+/g)?.[0] || '';
    const category = (product.category || '').toString();

    return {
      planMonth: temp.planMonth,
      product_code: product.product_code,
      product_name: product.product_name,
      planQty: temp.planQty,
      planUnit: product.unit || '',
      packingStyle,
      packingUnit,
      packSizeLabel: firstLabel,
      deliveryDate: temp.deliveryDate,
      remark: temp.remark || '',
      category,
    };
  }

  private normalizeExcelDate(value: any): string {
    if (value === null || value === undefined || value === '') {
      return '';
    }

    if (typeof value === 'number') {
      const parsed = XLSX.SSF.parse_date_code(value);
      if (parsed?.y && parsed?.m && parsed?.d) {
        const mm = parsed.m.toString().padStart(2, '0');
        const dd = parsed.d.toString().padStart(2, '0');
        return `${parsed.y}-${mm}-${dd}`;
      }
    }

    const str = value.toString().trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
      return str;
    }

    const slash = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (slash) {
      const dd = slash[1].padStart(2, '0');
      const mm = slash[2].padStart(2, '0');
      return `${slash[3]}-${mm}-${dd}`;
    }

    const dash = str.match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
    if (dash) {
      const dd = dash[1].padStart(2, '0');
      const mm = dash[2].padStart(2, '0');
      return `${dash[3]}-${mm}-${dd}`;
    }

    const monthYear = str.match(/^(\d{1,2})[\/\-](\d{4})$/);
    if (monthYear) {
      const mm = monthYear[1].padStart(2, '0');
      return `${monthYear[2]}-${mm}-01`;
    }

    const yearMonth = str.match(/^(\d{4})[\/\-](\d{1,2})$/);
    if (yearMonth) {
      const mm = yearMonth[2].padStart(2, '0');
      return `${yearMonth[1]}-${mm}-01`;
    }

    const dateObj = new Date(str);
    if (!isNaN(dateObj.getTime())) {
      const y = dateObj.getFullYear();
      const m = (dateObj.getMonth() + 1).toString().padStart(2, '0');
      const d = dateObj.getDate().toString().padStart(2, '0');
      return `${y}-${m}-${d}`;
    }

    return '';
  }
 
 
  conisgnee ='';
  client_code ='';
  po_type ='';
  subClient = '';
  isSubmitting = false;

  getResolvedFoEntryType(): 'FO Service' | 'FO Product' {
    return (this.productList && this.productList.length > 0) ? 'FO Product' : 'FO Service';
  }

  saveForm(data) {
    const v = (data && data.value) ? data.value : {};
    if (!this.billing_type) {
      alertify.error('Please select Order For');
      return;
    }
    if (!this.po_type) {
      alertify.error('Please select Sales Order Type');
      return;
    }
    if (!this.mainGroup) {
      alertify.error('Please select Main Group');
      return;
    }
    if (!(v.po_no || '').toString().trim()) {
      alertify.error('Please enter Customer Order No');
      return;
    }
    if (!v.po_date) {
      alertify.error('Please select Order Date');
      return;
    }
    if (this.billing_type === 'PO for Billing' && !this.client_code) {
      alertify.error('Client Group required');
      return;
    }
    if (this.billing_type === 'PO for Billing' && !this.conisgnee) {
      alertify.error('Consignee Name required');
      return;
    }
    if (!this.selectedServiceCategories.length && !this.savedServiceEntries.length) {
      alertify.error('Please select at least one Service Category');
      return;
    }
    if (!this.savedServiceEntries.length) {
      alertify.error('Please add at least one service category using ADD');
      return;
    }
    this.serviceDetailsSaved = true;
    if (this.isSubmitting) {
      return;
    }

    this.syncServiceFormFields();
    const formData = new FormData();
    const skipKeys = {
      serviceCategorySelect: true,
      file: true,
    };

    const temp = v;
    for (const key of Object.keys(temp || {})) {
      if (skipKeys[key] || key.indexOf('finishedProducts_') === 0) {
        continue;
      }
      let value = temp[key];
      if (key === 'po_date') {
        value = this.dateValueToIso(value);
      }
      if (value === null || value === undefined) {
        continue;
      }
      if (typeof value === 'object') {
        continue;
      }
      formData.append(key, String(value));
    }

    formData.set('billing_type', this.billing_type || '');
    formData.set('po_type', this.po_type || '');
    formData.set('client_code', this.client_code || '');
    formData.set('conisgnee', this.conisgnee || '');
    formData.set('groupcode', this.mainGroup || '');
    formData.set('subClient', this.subClient || '');
    formData.set('mainGroupName', this.mainGroupName || '');
    const foEntryType = this.getResolvedFoEntryType();
    formData.set('fo_entry_type', foEntryType);
    formData.set('client_type', foEntryType);
    formData.set('serviceCategory', this.serviceCategory || '');
    formData.set('serviceDescription', this.serviceDescription || '');
    formData.set('serviceDescriptionData', this.serviceDescriptionData || '');
    formData.set('selectedProductCodes', this.selectedProductCodes || '');
    formData.set('products', JSON.stringify(this.productList || []));

    if (this.file !== undefined && this.file !== null) {
      formData.append('fileUp', this.file, this.file.name);
    }

    this.isSubmitting = true;
    this.service.postForm('marketing/ponew.php?type=receivePO', formData).subscribe({
      next: (httpResponse) => {
        this.isSubmitting = false;
        const result = this.parseSaveResponse(httpResponse && httpResponse.body);
        if (result && result.status === 'success') {
          data.resetForm();
          this.productList = [];
          this.products = [];
          this.subGroupSeris = [];
          this.selectedClient1 = [];
          this.client_code = '';
          this.conisgnee = '';
          this.subClient = '';
          this.product_code = '';
          this.product_name = '';
          this.packingStyle = 0;
          this.packingUnit = '';
          this.productUnit = '';
          this.file = undefined;
          this.deliveryDateBreakdown = null;
          this.mainGroup = '';
          this.resetServiceCategoryState();
          alertify.success('Saved successfully');
          return;
        }
        alertify.error((result && (result.message || result.status)) || 'Save failed, please try again');
      },
      error: () => {
        this.isSubmitting = false;
        alertify.error('Save failed, please try again');
      },
    });
  }

  private parseSaveResponse(body: string | null): any {
    if (!body) {
      return null;
    }
    const trimmed = String(body).trim();
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
 
 
  
 


}
