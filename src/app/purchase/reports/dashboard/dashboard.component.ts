import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css', '../../shared/purchase-vapp-host.css']
})
export class DashboardComponent implements OnInit {
  raw_report: any;
  packing_report: any;
  download: any;
  materials: any[] = [];
  loading = false;
  searchTerm = '';
  isViewstock = false;
  isViewresult = false;
  isViewedit = false;
  isViewtype = false;
  isViewOrder = false;
  isViewindend = false;
  isViewhold = false;
  isViewapprove = false;
  isViewpro = false;
  isViewMaterial = false;
  isViewstatement = false;
  isViewtaking = false;
  isViewuser = false;
  isViewvendor = false;
  isViewmis = false;
  isViewpo = false;
  isIndView = false;
  isViewapprovevendor = false;
  
  results: any[] = [];
  Taking: any[] = [];
  Takings: any[] = [];
  stocks: any[] = [];
  item: any[] = [];
  types: any[] = [];
  materialList: any[] = [];
  selectedResult: any = null;
  selectedcomparative: any = null;
  material_subtype = '';
  material_type = '';
  orders: any[] = [];
  orders1: any[] = [];
  Approved: any[] = [];
  departments: any[] = [];
  vendor_no = '';
  vendors: any[] = [];
  selectedOrder: any = null;
  selectedOrder1: any = null;
  disc_amt = 0;
  Statements: any[] = [];
  total = 0;
  terms_condition: any = null;
  po_status = '';
  isView = false;
  selectedMaterial: any = null;
  gsts: any[] = [];
  from_date = '';
  to_date = '';
  today = '';
  status = '';
  plant_id: any = null;
  material: any = null;
  request_no = '';
  indendResults: any[] = [];
  Users: any[] = [];
  otherCharges: any = null;
  shippingHandling: any = null;
  Vendor: any[] = [];
  quatationvendor: any[] = [];
  materialquatation: any[] = [];
  postatus: any[] = [];
  selectedvendorquation: any = null;
  selectedMaterialquatation: any = null;
  billcompany_code = '';
  shipcompany_code = '';
  selectedBill: any = {};
  selectedShip: any = {};
  selectedBill1: any = {};
  selectedShip1: any = {};
  filteredVendor: any[] = [];
  vendor_for = '';
  vendor_name1 = '';

  // Pagination
  currentPage: number = 1;
  pageSize: number = 10;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    
    // Set default dates
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    this.from_date = this.formatDateForInput(firstDay);
    this.to_date = this.formatDateForInput(today);
    this.today = this.formatDateForInput(today);

    // Load all reports data
    this.loadAllReports();

    // Default report view
    this.raw_report = 'MIS Report';
    this.onReportChange();
  }

  formatDateForInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  /**
   * Load all reports data on initialization
   */
  loadAllReports(): void {
    this.getComparatives();
    this.getMaterialSub();
    this.getQuotationLog();
    this.getQuotationByVendor();
    this.getPendingPO();
    this.getPendingPO1();
    this.poStatus();
    this.getMisPO();
    this.getVendors();
    this.getDepartment();
    this.getQuotationBymat();
    this.approvedVendor();
    this.getProductsLog1();
    this.getApprovedPO();
    this.getDeptIndendsLog();
    this.getMaterialTypeWisePO();
  }

  /**
   * When report type changes, ensure data is loaded
   */
  closeAllView(): void {
    this.isViewstock = false;
    this.isViewresult = false;
    this.isViewedit = false;
    this.isViewtype = false;
    this.isViewindend = false;
    this.isViewuser = false;
    this.isViewhold = false;
    this.isViewapprove = false;
    this.isViewpo = false;
    this.isViewpro = false;
    this.isViewstatement = false;
    this.isViewtaking = false;
    this.isViewvendor = false;
    this.isViewmis = false;
    this.isViewMaterial = false;
    this.isViewapprovevendor = false;
    this.isIndView = false;
    this.currentPage = 1;
    this.pageSize = 10;
  }

  /**
   * Called when report dropdown changes - ensures data is loaded
   */
  onReportChange(): void {
    this.closeAllView();
    if (this.raw_report) {
      // Small delay to ensure view is closed first
      setTimeout(() => {
        this.loadReportData(this.raw_report);
      }, 100);
    }
  }

  searchCurrentReport(): void {
    if (!this.raw_report) {
      return;
    }
    // Refresh source for date-sensitive APIs where available.
    this.loadReportData(this.raw_report);
    this.currentPage = 1;
  }

  /**
   * Load specific report data based on selection
   * Always loads data to ensure fresh data is displayed
   */
  loadReportData(reportType: string): void {
    console.log('Loading report data for:', reportType);
    switch (reportType) {
      case 'Quotation Report':
        this.getQuotationLog();
        break;
      case 'Quotation Comparative Report':
        this.getComparatives();
        break;
      case 'Vendor Wise Quotation Report':
        this.getQuotationByVendor();
        break;
      case 'Purchase Report':
        this.getApprovedPO();
        break;
      case 'MIS Report':
        this.getMisPO();
        break;
      case 'Vendor Wise Purchase Report':
        this.getVendorWisePurchaseFromPurchaseLog();
        break;
      case 'Material Type Wise PO Report':
        this.getMaterialTypeWisePO();
        break;
      case 'Cancelled PO Report':
        this.getPendingPO1();
        break;
      case 'PO Status Report':
        this.poStatus();
        break;
      case 'Monthly PO Report':
        this.getPendingPO();
        break;
      case 'Indend Report':
        this.getDeptIndendsLog();
        break;
      case 'On Hold Purchase Report':
        this.getPendingPO();
        break;
      case 'Approved Vendor List':
        this.approvedVendor();
        break;
      case 'Provisional Approved Vendor':
        this.getProvisionalVendors();
        break;
      case 'Material Vendor Mapping Report':
        this.getProductsLog1();
        break;
      default:
        console.warn('Unknown report type:', reportType);
    }
  }

  na(val: any): string {
    if (val == null || val === undefined || val === '') return 'NA';
    const s = String(val).trim();
    return s === '' ? 'NA' : s;
  }

  /**
   * Get material type display string from a material object
   * Handles various possible field names
   */
  getMaterialTypeDisplay(material: any): string {
    if (!material) return 'NA';
    
    // Try different possible field names
    const materialType = material.material_type || material.materialType || material.type || '';
    const materialSubtype = material.material_subtype || material.materialSubtype || material.subtype || '';
    
    if (materialType && materialSubtype) {
      return `${materialType} - ${materialSubtype}`;
    } else if (materialType) {
      return materialType;
    } else if (materialSubtype) {
      return materialSubtype;
    }
    
    return 'NA';
  }

  formatDate(val: any): string {
    if (val == null || val === undefined) return 'NA';
    try {
      const d = new Date(val);
      if (isNaN(d.getTime())) return 'NA';
      const day = ('0' + d.getDate()).slice(-2);
      const month = ('0' + (d.getMonth() + 1)).slice(-2);
      return `${day}-${month}-${d.getFullYear()}`;
    } catch {
      return 'NA';
    }
  }

  clearFilter(): void {
    this.searchTerm = '';
    this.material_subtype = '';
    this.filterMaterial();
  }

  getCurrentReportData(): any[] {
    const r = this.raw_report;
    let data: any[] = [];
    
    if (r === 'Quotation Report') data = this.results || [];
    else if (r === 'Quotation Comparative Report') data = this.item || [];
    else if (r === 'Vendor Wise Quotation Report') data = this.quatationvendor || [];
    else if (r === 'Material Wise Quotation Report') data = this.materialquatation || [];
    else if (r === 'Purchase Report') data = this.Approved || [];
    else if (r === 'MIS Report') data = this.Taking || [];
    else if (r === 'Vendor Wise Purchase Report') data = this.Takings || this.Taking || [];
    else if (r === 'Material Type Wise PO Report') data = this.Users || [];
    else if (r === 'Cancelled PO Report') data = this.orders1 || [];
    else if (r === 'PO Status Report') data = this.postatus || [];
    else if (r === 'Monthly PO Report') data = this.orders || [];
    else if (r === 'Indend Report') data = this.indendResults || [];
    else if (r === 'On Hold Purchase Report') data = this.orders || [];
    else if (r === 'Approved Vendor List') data = this.filteredVendor || [];
    else if (r === 'Provisional Approved Vendor') data = this.Vendor || [];
    else if (r === 'Material Vendor Mapping Report') data = this.results || [];
    
    console.log('getCurrentReportData - Report:', r, 'Data length:', data.length);
    return data;
  }

  getFilteredReportData(): any[] {
    let list = this.getCurrentReportData();
    if (!list || list.length === 0) return [];

    list = this.applyDateFilter(list);
    
    const q = (this.searchTerm || '').trim().toLowerCase();
    if (!q) return list;
    
    return list.filter((row: any) => {
      if (!row) return false;
      return Object.keys(row || {}).some(k => {
        const v = row[k];
        if (v == null) return false;
        if (Array.isArray(v)) return false;
        if (typeof v === 'object') return false;
        return String(v).toLowerCase().includes(q);
      });
    });
  }

  private applyDateFilter(list: any[]): any[] {
    const from = this.from_date ? new Date(this.from_date + 'T00:00:00') : null;
    const to = this.to_date ? new Date(this.to_date + 'T23:59:59') : null;
    if (!from && !to) {
      return list;
    }
    return list.filter((row: any) => {
      const d = this.getRowDate(row);
      if (!d) {
        return true;
      }
      if (from && d < from) {
        return false;
      }
      if (to && d > to) {
        return false;
      }
      return true;
    });
  }

  private getRowDate(row: any): Date | null {
    if (!row || typeof row !== 'object') {
      return null;
    }
    const possibleKeys = ['entry_date', 'po_date', 'quotation_date', 'challan_date', 'request_date', 'created_at', 'date'];
    for (const key of possibleKeys) {
      if (row[key]) {
        const d = new Date(row[key]);
        if (!isNaN(d.getTime())) {
          return d;
        }
      }
    }
    return null;
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  onPageChange(page: number): void {
    this.currentPage = page;
  }

  onPageSizeChange(event: any): void {
    this.pageSize = parseInt(event.target.value, 10);
    this.currentPage = 1;
  }

  // Data fetching methods
  results1: any[] = [];

  getComparatives(): void {
    this.loading = true;
    this.service.get('purchase/quotation.php?type=getComparatives').subscribe({
      next: (response: any) => {
        console.log('getComparatives response:', response);
        this.item = Array.isArray(response) ? response : [];
        this.results1 = Array.isArray(response) ? response : [];
        this.filterMaterial();
        this.loading = false;
        console.log('item array length:', this.item.length);
      },
      error: (error) => {
        console.error('Error fetching comparatives:', error);
        this.item = [];
        this.results1 = [];
        this.loading = false;
      }
    });
  }

  getQuotationLog(): void {
    this.loading = true;
    this.service.get('purchase/quotation.php?type=getQuotationLog').subscribe({
      next: (response: any) => {
        console.log('getQuotationLog response:', response);
        // Process response to ensure materials array is properly formatted
        this.results = Array.isArray(response) ? response.map((item: any) => {
          // If materials is a string, try to parse it
          if (item.materials && typeof item.materials === 'string') {
            try {
              item.materials = JSON.parse(item.materials);
            } catch (e) {
              console.error('Error parsing materials JSON:', e);
              item.materials = [];
            }
          }
          // Ensure materials is an array
          if (!Array.isArray(item.materials)) {
            item.materials = [];
          }
          return item;
        }) : [];
        this.loading = false;
        console.log('results array length:', this.results.length);
        if (this.results.length > 0 && this.results[0].materials) {
          console.log('First result materials:', this.results[0].materials);
          if (this.results[0].materials.length > 0) {
            console.log('First material object:', this.results[0].materials[0]);
          }
        }
      },
      error: (error) => {
        console.error('Error fetching quotation log:', error);
        this.results = [];
        this.loading = false;
      }
    });
  }

  getQuotationByVendor(): void {
    this.loading = true;
    this.service.get('purchase/quotation.php?type=getQuotationByVendor').subscribe({
      next: (response: any) => {
        console.log('getQuotationByVendor response:', response);
        this.quatationvendor = Array.isArray(response) ? response : [];
        this.loading = false;
        console.log('quatationvendor array length:', this.quatationvendor.length);
      },
      error: (error) => {
        console.error('Error fetching quotation by vendor:', error);
        this.quatationvendor = [];
        this.loading = false;
      }
    });
  }

  getQuotationBymat(): void {
    this.loading = true;
    this.service.get('purchase/quotation.php?type=getQuotationBymat').subscribe({
      next: (response: any) => {
        this.materialquatation = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: (error) => {
        console.error('Error fetching quotation by material:', error);
        this.materialquatation = [];
        this.loading = false;
      }
    });
  }

  getPendingPO(): void {
    this.loading = true;
    this.service.get('purchase/po/raw.php?type=getAllPOLog').subscribe({
      next: (response: any) => {
        console.log('getPendingPO response:', response);
        // Ensure all records have vendor_name
        this.orders = Array.isArray(response) ? response.map((order: any) => {
          if (!order.vendor_name && order.vendor_no) {
            // Try to find vendor name from vendors list
            const vendor = this.vendors.find((v: any) => v.vendor_no === order.vendor_no);
            if (vendor) {
              order.vendor_name = vendor.vendor_name;
            }
          }
          return order;
        }) : [];
        this.loading = false;
        console.log('orders array length:', this.orders.length);
      },
      error: (error) => {
        console.error('Error fetching pending PO:', error);
        this.orders = [];
        this.loading = false;
      }
    });
  }

  getApprovedPO(): void {
    this.loading = true;
    const from = encodeURIComponent(this.from_date || '');
    const to = encodeURIComponent(this.to_date || '');
    this.service.get('purchase/po/raw.php?type=getPurchaseReportLog&from_date=' + from + '&to_date=' + to).subscribe({
      next: (response: any) => {
        console.log('getApprovedPO response:', response);
        this.Approved = Array.isArray(response) ? response : [];
        this.loading = false;
        console.log('Approved array length:', this.Approved.length);
      },
      error: (error) => {
        console.error('Error fetching approved PO:', error);
        this.Approved = [];
        this.loading = false;
      }
    });
  }

  getPendingPO1(): void {
    this.loading = true;
    this.service.get('purchase/po/raw.php?type=getAllPOLog2').subscribe({
      next: (response: any) => {
        console.log('getPendingPO1 response:', response);
        this.orders1 = Array.isArray(response) ? response : [];
        this.loading = false;
        console.log('orders1 array length:', this.orders1.length);
      },
      error: (error) => {
        console.error('Error fetching cancelled PO:', error);
        this.orders1 = [];
        this.loading = false;
      }
    });
  }

  poStatus(): void {
    this.loading = true;
    this.service.get('purchase/po/raw.php?type=postatus').subscribe({
      next: (response: any) => {
        this.postatus = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: (error) => {
        console.error('Error fetching PO status:', error);
        this.postatus = [];
        this.loading = false;
      }
    });
  }

  getMisPO(): void {
    this.loading = true;
    this.service.get('purchase/po/raw.php?type=getMisLog').subscribe({
      next: (response: any) => {
        console.log('getMisPO response:', response);
        this.Taking = Array.isArray(response) ? response : [];
        this.Takings = this.Taking;
        this.loading = false;
        console.log('Taking array length:', this.Taking.length);
      },
      error: (error) => {
        console.error('Error fetching MIS PO:', error);
        this.Taking = [];
        this.Takings = [];
        this.loading = false;
      }
    });
  }

  getVendorWisePurchaseFromPurchaseLog(): void {
    this.loading = true;
    this.service.get('purchase/po/raw.php?type=getAllPOLog').subscribe({
      next: (response: any) => {
        const poLog = Array.isArray(response) ? response : [];
        const datedLog = this.applyDateFilter(poLog);
        this.Taking = datedLog;

        const byVendor = new Map<string, any>();
        for (const row of datedLog) {
          const vendorNo = row?.vendor_no || row?.vendor_code || 'NA';
          const vendorName = row?.vendor_name || this.getVendorNameByNo(vendorNo) || 'NA';
          const vendorType = row?.vendor_type || row?.vendor_Is || 'NA';
          const key = String(vendorNo);
          if (!byVendor.has(key)) {
            byVendor.set(key, {
              vendor_code: vendorNo,
              vendor_type: vendorType,
              vendor_name: vendorName,
              __poNos: new Set<string>(),
              __rows: [],
              entry_date: row?.entry_date || row?.po_date || null,
            });
          }
          const bucket = byVendor.get(key);
          bucket.__rows.push(row);
          const poNo = row?.po_no ? String(row.po_no) : '';
          if (poNo !== '') {
            bucket.__poNos.add(poNo);
          }
          const dNew = this.getRowDate(row);
          const dOld = this.getRowDate({ entry_date: bucket.entry_date });
          if (dNew && (!dOld || dNew > dOld)) {
            bucket.entry_date = row?.entry_date || row?.po_date || bucket.entry_date;
          }
        }

        this.Takings = Array.from(byVendor.values()).map((v: any) => ({
          vendor_code: v.vendor_code,
          vendor_type: v.vendor_type,
          vendor_name: v.vendor_name,
          po: v.__poNos.size,
          entry_date: v.entry_date,
          __rows: v.__rows,
          __firstPo: v.__rows[0] || null,
        }));
        this.loading = false;
      },
      error: (error) => {
        console.error('Error fetching vendor wise purchase from PO log:', error);
        this.Taking = [];
        this.Takings = [];
        this.loading = false;
      }
    });
  }

  private getVendorNameByNo(vendorNo: string): string {
    const v = (this.vendors || []).find((x: any) => String(x.vendor_no) === String(vendorNo));
    return v ? (v.vendor_name || '') : '';
  }

  MisMonth: any;
  MIS_BY_MONTH(): void {
    this.loading = true;
    const Month = this.MisMonth;
    this.service.get('purchase/po/raw.php?type=getMisLogByMonth&mont=' + (Month ? encodeURIComponent(String(Month)) : '')).subscribe({
      next: (response: any) => {
        this.Taking = Array.isArray(response) ? response : [];
        this.Takings = this.Taking;
        this.loading = false;
      },
      error: (error) => {
        console.error('Error fetching MIS by month:', error);
        this.Taking = [];
        this.Takings = [];
        this.loading = false;
      }
    });
  }

  getMaterialTypeWisePO(): void {
    this.loading = true;
    this.service.get('purchase/po/raw.php?type=getMaterialTypeWisePO').subscribe({
      next: (response: any) => {
        console.log('getMaterialTypeWisePO response:', response);
        this.Users = Array.isArray(response) ? response : [];
        this.loading = false;
        console.log('Users array length:', this.Users.length);
      },
      error: (error) => {
        console.error('Error fetching material type wise PO:', error);
        this.Users = [];
        this.loading = false;
      }
    });
  }

  getDeptIndendsLog(): void {
    this.loading = true;
    this.service.get('purchase/indent.php?type=getPurchaseIndendsLog&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe({
      next: (response: any) => {
        console.log('getDeptIndendsLog response:', response);
        this.indendResults = Array.isArray(response) ? response : [];
        this.loading = false;
        console.log('indendResults array length:', this.indendResults.length);
      },
      error: (error) => {
        console.error('Error fetching indent log:', error);
        this.indendResults = [];
        this.loading = false;
      }
    });
  }

  getProvisionalVendors(): void {
    this.loading = true;
    this.service.get('purchase/vendor.php?type=getProvisionalVendors').subscribe({
      next: (response: any) => {
        console.log('getProvisionalVendors response:', response);
        this.Vendor = Array.isArray(response) ? response : [];
        this.loading = false;
        console.log('Vendor array length:', this.Vendor.length);
      },
      error: (error) => {
        console.error('Error fetching provisional vendors:', error);
        this.Vendor = [];
        this.loading = false;
      }
    });
  }

  approvedVendor(): void {
    this.loading = true;
    const vFor = encodeURIComponent(this.vendor_for || '');
    const vName = encodeURIComponent(this.vendor_name1 || '');
    this.service.get('purchase/vendor.php?type=getVendorLog&vendor_for=' + vFor + '&vendor_name=' + vName).subscribe({
      next: (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.searchVendor();
        this.loading = false;
      },
      error: (error) => {
        console.error('Error fetching approved vendors:', error);
        this.results = [];
        this.filteredVendor = [];
        this.loading = false;
      }
    });
  }

  searchVendor(): void {
    const list = this.results || [];
    const vName = (this.vendor_name1 || '').trim().toUpperCase();
    const vFor = (this.vendor_for || '').trim().toUpperCase();
    this.filteredVendor = list.filter((r: any) =>
      (!vName || (r && r.vendor_name && String(r.vendor_name).toUpperCase().includes(vName))) &&
      (!vFor || (r && r.vendor_for && String(r.vendor_for).toUpperCase().includes(vFor)))
    );
  }

  filterMaterial(): void {
    const list = this.results1 || [];
    const sub = (this.material_subtype || '').trim().toUpperCase();
    this.item = sub ? list.filter((m: any) => (m && m.material_subtype && String(m.material_subtype).toUpperCase().includes(sub))) : [...list];
  }

  getMaterials(value: string): void {
    this.service.get('common.php?type=getMaterialsByType&material_subtype=' + value).subscribe((response: any) => {
      this.materials = Array.isArray(response) ? response : [];
    });
  }

  getMaterialSub(): void {
    this.service.get('master/materialtype.php?type=getMaterialtype').subscribe((response: any) => {
      this.types = Array.isArray(response) ? response : [];
    });
  }

  uploadQuatation(url: string): void {
    if (!url) return;
    const fullUrl = this.service.url + '../../upload/quotation/' + url;
    window.open(fullUrl, '_blank');
  }

  // View methods
  Viewstock(index: number): void {
    const list = this.getFilteredReportData();
    this.selectedResult = list[index] ?? null;
    
    if (this.selectedResult) {
      // Ensure materials array is properly formatted
      if (this.selectedResult.materials) {
        // If materials is a string, try to parse it
        if (typeof this.selectedResult.materials === 'string') {
          try {
            this.selectedResult.materials = JSON.parse(this.selectedResult.materials);
          } catch (e) {
            console.error('Error parsing materials JSON:', e);
            this.selectedResult.materials = [];
          }
        }
        
        // Ensure materials is an array
        if (!Array.isArray(this.selectedResult.materials)) {
          this.selectedResult.materials = [];
        }
        
        // Log for debugging
        console.log('Selected Result:', this.selectedResult);
        console.log('Materials:', this.selectedResult.materials);
        if (this.selectedResult.materials.length > 0) {
          console.log('First Material:', this.selectedResult.materials[0]);
        }
      } else {
        this.selectedResult.materials = [];
      }
    }
    
    this.isViewstock = true;
  }

  viewcamperative(index: number): void {
    const list = this.item || [];
    this.selectedcomparative = list[index] ?? null;
    this.isViewresult = true;
  }

  viewOrder(index: number): void {
    const list = this.getFilteredReportData();
    this.selectedOrder = list[index] ?? null;
    if (!this.selectedOrder) return;
    this.isViewedit = true;
    this.otherCharges = this.selectedOrder['other_charges'] ? this.selectedOrder['other_charges'] : 'NA';
    this.shippingHandling = this.selectedOrder['shipping_handling'] ? this.selectedOrder['shipping_handling'] : 'NA';
    this.disc_amt = (this.selectedOrder['net_total'] * 1 * (this.selectedOrder['discount'] || 0)) / 100;
    this.total = (this.selectedOrder['net_total'] * 1) - (this.disc_amt * 1);
    this.getBillCompany(this.selectedOrder['billcompany_code']);
    this.getShipCompany(this.selectedOrder['shipcompany_code']);
    try {
      const tc = this.selectedOrder['terms_conditions'];
      this.terms_condition = (tc != null && tc.length > 0 && typeof tc === 'string') ? JSON.parse(tc) : null;
    } catch {
      this.terms_condition = null;
    }
    this.isViewOrder = true;
    const st = this.selectedOrder['status'];
    this.po_status = st === 'Hold' ? 'This PO Is On Hold' : st === 'Cancel' ? 'This PO Is Cancelled' : '';
  }

  viewOrder1(index: number): void {
    const list = this.getFilteredReportData();
    this.selectedOrder1 = list[index] ?? null;
    if (!this.selectedOrder1) return;
    this.disc_amt = (this.selectedOrder1['net_total'] * 1 * (this.selectedOrder1['discount'] || 0)) / 100;
    this.total = (this.selectedOrder1['net_total'] * 1) - (this.disc_amt * 1);
    this.getBillCompany1(this.selectedOrder1['billcompany_code']);
    this.getShipCompany1(this.selectedOrder1['shipcompany_code']);
    this.isViewOrder = true;
    const st = this.selectedOrder1['status'];
    this.po_status = st === 'Hold' ? 'This PO Is On Hold' : st === 'Cancel' ? 'This PO Is Cancelled' : '';
  }

  viewtype(index: number): void {
    const list = this.getFilteredReportData();
    this.selectedResult = list[index] ?? null;
    this.isViewtype = true;
    if (this.selectedResult) {
      this.getBillCompany(this.selectedResult['billcompany_code']);
      this.getShipCompany(this.selectedResult['shipcompany_code']);
      try {
        const tc = this.selectedResult['terms_conditions'];
        this.terms_condition = (tc != null && tc.length > 0 && typeof tc === 'string') ? JSON.parse(tc) : null;
      } catch {
        this.terms_condition = null;
      }
    }
  }

  viewindend(index: number): void {
    const list = this.indendResults || [];
    this.selectedResult = list[index] ?? null;
    this.isViewindend = true;
    this.isIndView = true;
  }

  viewhold(index: number): void {
    const list = this.getFilteredReportData();
    this.selectedOrder = list[index] ?? null;
    if (!this.selectedOrder) return;
    
    // Ensure vendor_name is present
    if (!this.selectedOrder.vendor_name && this.selectedOrder.vendor_no) {
      const vendor = this.vendors.find((v: any) => v.vendor_no === this.selectedOrder.vendor_no);
      if (vendor) {
        this.selectedOrder.vendor_name = vendor.vendor_name;
      }
    }
    
    this.isViewhold = true;
    this.getBillCompany(this.selectedOrder['billcompany_code']);
    this.getShipCompany(this.selectedOrder['shipcompany_code']);
    try {
      const tc = this.selectedOrder['terms_conditions'];
      this.terms_condition = (tc != null && tc.length > 0 && typeof tc === 'string') ? JSON.parse(tc) : null;
    } catch {
      this.terms_condition = null;
    }
  }

  viewpo(index: number): void {
    const list = this.getFilteredReportData();
    this.selectedOrder = list[index] ?? null;
    if (!this.selectedOrder) return;
    
    // Ensure vendor_name is present
    if (!this.selectedOrder.vendor_name && this.selectedOrder.vendor_no) {
      const vendor = this.vendors.find((v: any) => v.vendor_no === this.selectedOrder.vendor_no);
      if (vendor) {
        this.selectedOrder.vendor_name = vendor.vendor_name;
      }
    }
    
    this.isViewpo = true;
    this.getBillCompany(this.selectedOrder['billcompany_code']);
    this.getShipCompany(this.selectedOrder['shipcompany_code']);
    try {
      const tc = this.selectedOrder['terms_conditions'];
      this.terms_condition = (tc != null && tc.length > 0 && typeof tc === 'string') ? JSON.parse(tc) : null;
    } catch {
      this.terms_condition = null;
    }
  }

  viewapprovevendor(index: number): void {
    const list = this.filteredVendor || [];
    this.selectedResult = list[index] ?? null;
    this.isViewapprovevendor = true;
  }

  viewpro(index: number): void {
    const list = this.Vendor || [];
    this.selectedResult = list[index] ?? null;
    this.isViewpro = true;
  }

  viewvendorvisequation(index: number): void {
    const list = this.getFilteredReportData();
    this.selectedvendorquation = list[index] ?? null;
    this.isViewstatement = true;
  }

  viewvendor(index: number): void {
    const list = this.getFilteredReportData();
    const row = list[index] ?? null;
    if (!row) return;
    this.selectedResult = row.__firstPo || (Array.isArray(row.__rows) ? row.__rows[0] : row);
    this.isViewvendor = true;
  }

  viewmatvisequatation(index: number): void {
    const list = this.getFilteredReportData();
    this.selectedMaterialquatation = list[index] ?? null;
    this.isViewtaking = true;
  }

  viewMaterial(index: number): void {
    const list = this.results || [];
    this.selectedMaterial = list[index] ?? null;
    this.isViewMaterial = true;
    this.getProducts1();
  }

  getProducts1(): void {
    this.materials = [];
    const vno = this.selectedMaterial && this.selectedMaterial['vendor_no'];
    if (!vno) return;
    this.service.get('master/material.php?type=get_materials_by_supplier&vendor_no=' + encodeURIComponent(vno)).subscribe({
      next: (response: any) => {
        this.materials = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.materials = [];
      }
    });
  }

  getProductsLog1(): void {
    this.loading = true;
    this.service.get('master/material.php?type=get_supplier_by_materials_log').subscribe({
      next: (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: (error) => {
        console.error('Error fetching products log:', error);
        this.results = [];
        this.loading = false;
      }
    });
  }

  getBillCompany(val: any): void {
    if (val == null || val === '') return;
    this.service.get('master/company.php?type=getCompanyByCode&company_code=' + encodeURIComponent(String(val))).subscribe({
      next: (response: any) => {
        this.selectedBill = Array.isArray(response) && response[0] ? response[0] : {};
      },
      error: () => {
        this.selectedBill = {};
      }
    });
  }

  getShipCompany(val: any): void {
    if (val == null || val === '') return;
    this.service.get('master/company.php?type=getCompanyByCode&company_code=' + encodeURIComponent(String(val))).subscribe({
      next: (response: any) => {
        this.selectedShip = Array.isArray(response) && response[0] ? response[0] : {};
      },
      error: () => {
        this.selectedShip = {};
      }
    });
  }

  getBillCompany1(val: any): void {
    if (val == null || val === '') return;
    this.service.get('master/company.php?type=getCompanyByCode&company_code=' + encodeURIComponent(String(val))).subscribe({
      next: (response: any) => {
        this.selectedBill1 = Array.isArray(response) && response[0] ? response[0] : {};
      },
      error: () => {
        this.selectedBill1 = {};
      }
    });
  }

  getShipCompany1(val: any): void {
    if (val == null || val === '') return;
    this.service.get('master/company.php?type=getCompanyByCode&company_code=' + encodeURIComponent(String(val))).subscribe({
      next: (response: any) => {
        this.selectedShip1 = Array.isArray(response) && response[0] ? response[0] : {};
      },
      error: () => {
        this.selectedShip1 = {};
      }
    });
  }

  getDepartment(): void {
    this.service.get('common.php?type=getDepartments').subscribe((response: any) => {
      this.departments = Array.isArray(response) ? response : [];
    });
  }

  getVendors(): void {
    this.service.get('common.php?type=getVendors').subscribe({
      next: (response: any) => {
        this.vendors = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.vendors = [];
      }
    });
  }

  getGst(): void {
    this.service.get('common.php?type=getGST').subscribe((response: any) => {
      this.gsts = Array.isArray(response) ? response : [];
    });
  }

  viewf(): void {
    this.isViewstock = false;
    this.isViewresult = false;
    this.isViewstatement = false;
    this.isViewtaking = false;
    this.isViewedit = false;
    this.isViewvendor = false;
    this.isViewtype = false;
    this.isViewOrder = false;
    this.isViewpo = false;
    this.isViewindend = false;
    this.isViewhold = false;
    this.isViewpro = false;
    this.isViewapprovevendor = false;
    this.isViewMaterial = false;
    this.isIndView = false;
    this.currentPage = 1;
    this.pageSize = 10;
  }

  downloadPDF(): void {
    // Implementation for PDF download
  }

  // Excel export methods
  private writeStyledExcel(sheetName: string, headers: string[], rows: any[][], fileName: string): void {
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet(sheetName, { views: [{ rightToLeft: false }] });
    const colCount = Math.max(1, headers.length);
    const thin = { top: { style: 'thin' as const }, bottom: { style: 'thin' as const }, left: { style: 'thin' as const }, right: { style: 'thin' as const } };

    // Title row
    ws.mergeCells(1, 1, 1, colCount);
    const titleCell = ws.getCell(1, 1);
    titleCell.value = sheetName;
    titleCell.font = { bold: true, size: 14, color: { argb: 'FF0F172A' } };
    titleCell.alignment = { horizontal: 'center', vertical: 'middle' };
    ws.getRow(1).height = 24;

    // Meta row
    ws.mergeCells(2, 1, 2, colCount);
    const metaCell = ws.getCell(2, 1);
    metaCell.value = `Date Range: ${this.from_date || 'NA'} to ${this.to_date || 'NA'}   |   Generated: ${new Date().toLocaleString()}`;
    metaCell.font = { size: 10, color: { argb: 'FF475569' } };
    metaCell.alignment = { horizontal: 'center', vertical: 'middle' };
    ws.getRow(2).height = 18;

    // Spacer row
    ws.addRow([]);

    // Header row
    const headerRow = ws.addRow(headers);
    const colors = ['FF0d9488', 'FF059669', 'FF0891b2', 'FF7c3aed', 'FFdc2626', 'FFea580c', 'FF2563eb', 'FF16a34a', 'FF4f46e5', 'FF0d9488', 'FF059669', 'FF0891b2'];
    headerRow.eachCell((cell, colNumber) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors[colNumber - 1] || 'FF64748b' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = thin;
    });
    headerRow.height = 22;

    // Data rows
    const safeRows = rows && rows.length ? rows : [['No data available']];
    ws.addRows(safeRows);
    const dataStart = 5;
    const dataEnd = dataStart + safeRows.length - 1;
    for (let r = dataStart; r <= dataEnd; r++) {
      ws.getRow(r).eachCell((c) => {
        c.border = thin;
        c.alignment = { wrapText: true, vertical: 'middle', horizontal: 'left' };
      });
      ws.getRow(r).height = 20;
    }

    // Width calculation from headers + data
    const widths: number[] = headers.map((h) => Math.max(12, String(h || '').length + 4));
    safeRows.forEach((row: any[]) => {
      headers.forEach((_, i) => {
        const cellVal = row && row[i] != null ? String(row[i]) : '';
        widths[i] = Math.min(50, Math.max(widths[i], cellVal.length + 2));
      });
    });
    ws.columns.forEach((col, i) => {
      if (i < headers.length) col.width = widths[i];
    });

    // Freeze after title/meta/spacer/header rows
    ws.views = [{ state: 'frozen', ySplit: 4 }];

    wb.xlsx.writeBuffer().then(buffer => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = fileName;
      a.click();
      URL.revokeObjectURL(a.href);
    });
  }

  download10(): void {
    const list = this.getFilteredReportData();
    const headers = ['Sr. No', 'PO No', 'PO Date', 'Vendor Name', 'Vendor Code', 'Gross Total', 'GST Total', 'Final Total', 'Status'];
    const rows = list.map((u: any, i: number) => [
      i + 1,
      this.na(u.po_no),
      this.formatDate(u.po_date || u.entry_date),
      this.na(u.vendor_name),
      this.na(u.vendor_no),
      this.na(u.gross_total),
      this.na(u.gst_total),
      this.na(u.final_total),
      this.na(u.status),
    ]);
    this.writeStyledExcel('Purchase Report', headers, rows, 'purchase_report.xlsx');
  }

  download2(): void {
    const list = this.getFilteredReportData();
    const headers = ['Sr.No', 'Quotation No', 'Vendor Name', 'Vendor No', 'No of Material', 'Quotation Date', 'Status'];
    const rows = list.map((u: any, i: number) => [
      i + 1,
      this.na(u.quotation_no),
      this.na(u.vendor_name),
      this.na(u.vendor_no),
      this.na(u.no_of_products),
      this.formatDate(u.entry_date),
      this.na(u.status),
    ]);
    this.writeStyledExcel('Quotation Report', headers, rows, 'quotation_report.xlsx');
  }

  download4(): void {
    const list = this.quatationvendor || [];
    const headers = ['Sr.No', 'Vendor No.', 'Vendor Type', 'Vendor Name'];
    const rows = list.map((u: any, i: number) => [i + 1, this.na(u.vendor_no), this.na(u.vendor_type), this.na(u.vendor_name)]);
    this.writeStyledExcel('Vendor Wise Quotation', headers, rows, 'vendor_wise_quotation_report.xlsx');
  }

  download5(): void {
    const list = this.materialquatation || [];
    const headers = ['Sr.No', 'Material Code', 'Material Name', 'Vendor Name'];
    const rows = list.map((u: any, i: number) => [i + 1, this.na(u.material_code), this.na(u.material_name), this.na(u.vendor_name)]);
    this.writeStyledExcel('Material Wise Quotation', headers, rows, 'material_wise_quotation_report.xlsx');
  }

  download13(): void {
    const list = this.postatus || [];
    const headers = [
      'Sr.No',
      'PO Date',
      'PO No',
      'Material Type',
      'Material Receiving Status',
      'PO Status',
      'Receiving no',
      'Sampling Status',
      'QC Status'
    ];
    const rows = list.map((u: any, i: number) => [
      i + 1,
      this.formatDate(u.entry_date),
      this.na(u.po_no),
      this.na(u.po_type),
      this.na(u.receiving),
      this.na(u.status),
      this.na(u.grn_no),
      this.na(u.sample_status),
      this.na(u.qc_status)
    ]);
    this.writeStyledExcel('PO Status', headers, rows, 'po_status_report.xlsx');
  }

  download19(): void {
    const list = this.Vendor || [];
    const headers = ['Sr.No', 'Vendor No.', 'Vendor Type', 'Vendor Name', 'Status'];
    const rows = list.map((u: any, i: number) => [i + 1, this.na(u.vendor_no), this.na(u.vendor_type), this.na(u.vendor_name), this.na(u.status)]);
    this.writeStyledExcel('Provisional Approved Vendor', headers, rows, 'provisional_approved_vendor.xlsx');
  }

  download310(): void {
    const list = this.getFilteredReportData();
    const headers = ['Sr.No', 'PO No', 'PO Date', 'Vendor Name', 'Vendor Code', 'Material Code', 'Material Name', 'Qty', 'Final Total', 'Status'];
    const rows = list.map((u: any, i: number) => [
      i + 1,
      this.na(u.po_no),
      this.formatDate(u.po_date || u.entry_date),
      this.na(u.vendor_name),
      this.na(u.vendor_no || u.vendor_code),
      this.na(u.material_code),
      this.na(u.material_name),
      this.na(u.quantity || u.qty),
      this.na(u.final_total),
      this.na(u.status),
    ]);
    this.writeStyledExcel('MIS Report', headers, rows, 'mis_report.xlsx');
  }

  downloadCurrentReportExcel(): void {
    const r = this.raw_report;
    if (r === 'Quotation Report') this.download2();
    else if (r === 'Vendor Wise Quotation Report') this.download4();
    else if (r === 'Material Wise Quotation Report') this.download5();
    else if (r === 'Purchase Report') this.download10();
    else if (r === 'MIS Report') this.download310();
    else if (r === 'Vendor Wise Purchase Report') this.exportToExcelVendorWisePurchase();
    else if (r === 'Material Type Wise PO Report') this.exportToExcelMaterialTypeWisePOReport();
    else if (r === 'Cancelled PO Report') this.exportToExcelCancelledPOReport();
    else if (r === 'PO Status Report') this.download13();
    else if (r === 'Monthly PO Report') this.exportToExcelMonthlyPOReport();
    else if (r === 'Indend Report') this.exportToExcelIndendReport();
    else if (r === 'On Hold Purchase Report') this.exportToExcelOnHoldPurchaseReport();
    else if (r === 'Approved Vendor List') this.exportToExcelApprovedVendorList();
    else if (r === 'Provisional Approved Vendor') this.download19();
    else if (r === 'Material Vendor Mapping Report') this.exportToExcelMaterialVendorMappingReport();
  }

  exportToExcelMaterialVendorMappingReport(): void {
    const list = this.getFilteredReportData();
    const headers = ['Sr.No', 'Vendor Code', 'Vendor Type', 'Vendor Name', 'No of Product'];
    const rows = list.map((record: any, index: number) => [
      index + 1,
      this.na(record.vendor_no),
      this.na(record.vendor_type),
      this.na(record.vendor_name),
      this.na(record.no_of_products)
    ]);
    this.writeStyledExcel('Material Vendor Mapping', headers, rows, 'MaterialVendorMappingReport.xlsx');
  }

  exportToExcelVendorWisePurchase(): void {
    const list = (this.Takings || this.Taking || []).slice();
    const headers = ['Vendor Code', 'Vendor Type', 'Vendor Name', 'No of PO'];
    const rows = list.map((taking: any) => [
      this.na(taking.vendor_code),
      this.na(taking.vendor_type),
      this.na(taking.vendor_name),
      this.na(taking.po)
    ]);
    this.writeStyledExcel('Vendor Wise Purchase', headers, rows, 'vendor_wise_purchase_report.xlsx');
  }

  exportToExcelMaterialTypeWisePOReport(): void {
    const list = this.Users || [];
    const headers = ['Po No', 'P.O Date', 'Material Type', 'Material Code', 'Material Name', 'Vendor Name'];
    const rows = list.map((user: any) => [
      this.na(user.po_no),
      this.formatDate(user.entry_date),
      this.na(user.material_type),
      this.na(user.material_code),
      this.na(user.material_name),
      this.na(user.vendor_name)
    ]);
    this.writeStyledExcel('Material Type Wise PO', headers, rows, 'material_type_wise_po_report.xlsx');
  }

  exportToExcelCancelledPOReport(): void {
    const list = this.orders1 || [];
    const headers = ['Sr. No', 'Po Type', 'Approval Date', 'P.O Date', 'Po No', 'Purchase Requisition No', 'Vendor Name', 'Gross Total', 'Tax Total', 'Net Total', 'Status'];
    const rows = list.map((user: any, index: number) => [
      index + 1,
      this.na(user.po_type),
      this.formatDate(user.entry_date),
      this.formatDate(user.entry_date),
      this.na(user.po_no),
      user.indent_no ? this.na(user.indent_no) : 'NA',
      this.na(user.vendor_name),
      this.na(user.gross_total),
      this.na(user.gst_total),
      this.na(user.final_total),
      this.na(user.status)
    ]);
    this.writeStyledExcel('Cancelled PO', headers, rows, 'cancelled_po_report.xlsx');
  }

  exportToExcelMonthlyPOReport(): void {
    const list = this.orders || [];
    const headers = ['Sr. No', 'Po Type', 'Approval Date', 'P.O Date', 'Po No', 'Purchase Requisition No', 'Vendor Name', 'Gross Total', 'Tax Total', 'Net Total', 'Status'];
    const rows = list.map((user: any, index: number) => {
      // Ensure vendor_name is present
      let vendorName = user.vendor_name;
      if (!vendorName && user.vendor_no) {
        const vendor = this.vendors.find((v: any) => v.vendor_no === user.vendor_no);
        vendorName = vendor ? vendor.vendor_name : 'NA';
      }
      
      return [
        index + 1,
        this.na(user.po_type),
        this.formatDate(user.entry_date),
        this.formatDate(user.po_date || user.entry_date),
        this.na(user.po_no),
        (user.indent_no && (user.indent_no.length > 0 || (typeof user.indent_no === 'string' && user.indent_no))) ? this.na(user.indent_no) : 'NA',
        this.na(vendorName),
        this.na(user.gross_total),
        this.na(user.gst_total),
        this.na(user.final_total),
        this.na(user.status)
      ];
    });
    this.writeStyledExcel('Monthly PO', headers, rows, 'monthly_po_report.xlsx');
  }

  exportToExcelIndendReport(): void {
    const list = this.indendResults || [];
    const headers = ['Sr.', 'Date', 'Request No', 'No Of Items', 'Entered By'];
    const rows = list.map((indend: any, index: number) => [
      index + 1,
      this.formatDate(indend.entry_date),
      this.na(indend.request_no),
      Array.isArray(indend.materials) ? indend.materials.length : 0,
      this.na(indend.entry_by)
    ]);
    this.writeStyledExcel('Indend Report', headers, rows, 'indend_report.xlsx');
  }

  exportToExcelOnHoldPurchaseReport(): void {
    const list = this.orders || [];
    const headers = ['Sr.', 'Po Type', 'Approval Date', 'P.O Date', 'Po No', 'Purchase Requisition No', 'Vendor Name', 'Gross Total', 'Tax Total', 'Net Total', 'Status'];
    const rows = list.map((order: any, index: number) => {
      // Ensure vendor_name is present
      let vendorName = order.vendor_name;
      if (!vendorName && order.vendor_no) {
        const vendor = this.vendors.find((v: any) => v.vendor_no === order.vendor_no);
        vendorName = vendor ? vendor.vendor_name : 'NA';
      }
      
      return [
        index + 1,
        this.na(order.po_type),
        this.formatDate(order.entry_date),
        this.formatDate(order.entry_date),
        this.na(order.po_no),
        (order.indent_no && (order.indent_no.length > 0 || (typeof order.indent_no === 'string' && order.indent_no))) ? this.na(order.indent_no) : 'NA',
        this.na(vendorName),
        this.na(order.gross_total),
        this.na(order.gst_total),
        this.na(order.final_total),
        this.na(order.status)
      ];
    });
    this.writeStyledExcel('On Hold Purchase', headers, rows, 'on_hold_purchase_report.xlsx');
  }

  exportToExcelApprovedVendorList(): void {
    const list = this.filteredVendor || [];
    const headers = ['Sr.No', 'Vendor No.', 'Vendor Type', 'Vendor Name', 'Status'];
    const rows = list.map((vendor: any, index: number) => [
      index + 1,
      this.na(vendor.vendor_no),
      this.na(vendor.vendor_type),
      this.na(vendor.vendor_name),
      this.na(vendor.status)
    ]);
    this.writeStyledExcel('Approved Vendor List', headers, rows, 'approved_vendor_list.xlsx');
  }

  update_vendor_status(status: string): void {
    // Implementation for updating vendor status
  }

  is_password = false;
}

