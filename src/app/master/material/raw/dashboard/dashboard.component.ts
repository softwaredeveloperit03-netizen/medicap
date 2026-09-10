import { Component, Injector, OnInit } from '@angular/core';
import { of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import { RmMasterCustomisationService } from 'src/app/qa/soft-restriction/rm-master-customisation/rm-master-customisation.service';
import { GmpMaterialFormCustomisationService } from 'src/app/qa/soft-restriction/rm-master-customisation/gmp-material-form-customisation.service';
import {
  MaterialFormFieldRuntime,
  buildDefaultRuntimeFields,
  mergeGmpLayout,
  applyRmVisibilityFallback,
  sortByGroup,
} from 'src/app/qa/soft-restriction/rm-master-customisation/material-master-form-layout.constants';
declare let alertify;

@Component({
  selector: 'app-rmpm-material-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  results: any[] = [];
  loading = false;
  showCodingPattern = false;

  material_subtype = '';
  material_nature = '';
  grade = '';
  id = '';
  uom = '';
  /** Row opened in the view (eye) panel */
  selectedResult: any = {};
  isEdit = false;
  grades;
  material_code = '';
  material_name = '';
  alternate_uom = '';
  equivalancy_applicable = '';
  materials = [];
  density = '';
  order_qty = '';
  inventory = '';
  max_limit = '';
  inv_unit = '';
  location = '';
  isView = false;
  gst: Object;
  cas_number = '';
  structure_file_path = '';
  molecular_wt = '';
  molecular_formula = '';
  storage_condition = '';
  safety = '';
  material_appearance = '';
  description = '';
  packing_requirement = '';
  color_index = '';
  msds_file_path = '';
  equivalancy_factor = '';
  category = '';
  lead_time = '';
  unit = '';
  pack_size = '';
  types;
  plant_id: any;
  plant_type: any;
  units;
  Cigst:any
  Cihsn: any;
  /** Same GMP layout as master/material/new — drives read-only view visibility */
  runtimeFields: MaterialFormFieldRuntime[] = buildDefaultRuntimeFields();
  private runtimeByKey = new Map<string, MaterialFormFieldRuntime>();
  otherFields: MaterialFormFieldRuntime[] = [];

  private rmCustomisation: RmMasterCustomisationService | null = null;
  private gmpForm: GmpMaterialFormCustomisationService | null = null;

  constructor(
    private service: DataAccessService,
    private injector: Injector
  ) {
    this.loggedInDept = localStorage.getItem('department');
    try {
      this.plant_id = this.service.getPlantConfigFields('plant_id');
      this.plant_type = this.service.getPlantConfigFields('plant_type');
    } catch {
      this.plant_id = localStorage.getItem('plant_id');
      this.plant_type = null;
    }
    try {
      this.rmCustomisation = this.injector.get(RmMasterCustomisationService, null);
      this.gmpForm = this.injector.get(GmpMaterialFormCustomisationService, null);
    } catch {
      this.rmCustomisation = null;
      this.gmpForm = null;
    }
  }

  ngOnInit(): void {
    try {
      this.rebuildLayoutSlices();
      this.loadFormLayout();
    } catch (e) {
      console.error('RM/PM layout init failed', e);
    }
    this.getMaterialsLog();
    this.get_rights();
  }

  private rebuildLayoutSlices(): void {
    this.runtimeByKey = new Map(this.runtimeFields.map((f) => [f.field_key, f]));
    this.otherFields = sortByGroup(this.runtimeFields, 'other');
  }

  loadFormLayout(): void {
    if (!this.gmpForm || !this.rmCustomisation) {
      this.runtimeFields = buildDefaultRuntimeFields();
      this.rebuildLayoutSlices();
      return;
    }
    this.gmpForm
      .getActiveLayout()
      .pipe(catchError(() => of(null)))
      .subscribe((gmp: any) => {
        this.rmCustomisation
          .getActiveCustomisation()
          .pipe(catchError(() => of(null)))
          .subscribe((rm: any) => {
            let fields: MaterialFormFieldRuntime[];
            if (gmp && Array.isArray(gmp.fields) && gmp.fields.length > 0) {
              fields = mergeGmpLayout(gmp);
            } else {
              fields = applyRmVisibilityFallback(mergeGmpLayout(null), rm && typeof rm === 'object' ? rm : null);
            }
            this.runtimeFields = fields;
            this.rebuildLayoutSlices();
          });
      });
  }

  labelFor(key: string): string {
    return this.runtimeByKey.get(key)?.field_label ?? key;
  }

  /** Show “Other Information” card only if at least one other_* field applies to this row (same idea as new form). */
  get showViewOtherInformationSection(): boolean {
    return this.otherFields.some((f) => this.viewFieldApplicable(f.field_key));
  }

  /**
   * Read-only view: same visibility rules as new material form (`NewComponent.canShowLayoutField`),
   * using the opened row as context.
   */
  viewFieldApplicable(layoutKey: string): boolean {
    const fc = this.runtimeByKey.get(layoutKey);
    if (!fc) {
      return true;
    }
    if (fc.applicable === 'Not Applicable') {
      return false;
    }
    return this.canShowViewLayoutField(fc);
  }

  private canShowViewLayoutField(fc: MaterialFormFieldRuntime): boolean {
    const r = this.selectedResult as any;
    if (!r || Object.keys(r).length === 0) {
      return true;
    }
    const k = fc.field_key;
    const mt = r.material_type;
    const isPacking = mt === 'Packing Material';
    const subType = r.sub_type || '';
    const category = r.category;
    const matIs = r.matIs;
    const taxType = r.tax_type;
    const materialNature = r.material_nature;
    const eqApp = r.equivalancy_applicable;

    switch (k) {
      case 'nature_of_material':
      case 'category':
        return !isPacking;
      case 'material_name_report':
      case 'packing_color':
      case 'packing_dimension':
      case 'packing_made_of':
      case 'packing_sub_type':
        return isPacking;
      case 'packing_type':
      case 'packing_size':
        return false;
      case 'artwork':
      case 'packing_product':
        return isPacking && subType === 'Printed';
      case 'salt_equivalency':
      case 'assay_calculation':
        return category === 'Active' && mt === 'Pharma Raw Material';
      case 'equiv_table':
        return category === 'Active' && mt === 'Pharma Raw Material' && eqApp === 'Yes';
      case 'mother_material_code':
        return matIs === 'Client';
      case 'indent_type':
        return matIs === 'Client';
      case 'tax_gst':
        return taxType === 'Local' || taxType === 'Import' || taxType === 'Local/Import';
      case 'density':
        return materialNature === 'LIQUID';
      case 'specific_gravity':
        return !isPacking;
      case 'texture':
        return isPacking;
      default:
        return true;
    }
  }

  /** GST/Tax column label — matches new form */
  get viewTaxGstHeading(): string {
    const tt = this.selectedResult?.['tax_type'];
    if (tt === 'Import') {
      return this.labelFor('tax_gst') + ' (Tax)';
    }
    if (tt === 'Local/Import') {
      return this.labelFor('tax_gst') + ' (GST/Tax)';
    }
    return this.labelFor('tax_gst') + ' (GST)';
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    const empId = localStorage.getItem('emp_id');
    if (!empId) {
      return;
    }
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          encodeURIComponent(empId) +
          '&dep_name=' +
          encodeURIComponent(this.loggedInDept || '')
      )
      .subscribe({
        next: (response: any) => {
          this.rights = response;
          const r = Array.isArray(response) && response[0] ? response[0] : {};
          this.isuser = r.isuser || 'No';
          this.ischecker = r.ischecker || 'No';
          this.isapprover = r.isapprover || 'No';
          this.qms_approver = r.qms_approver || 'No';
          this.dept_head = r.dept_head || 'No';
          this.isauditor = r.isauditor || 'No';
          this.plant_head = r.plant_head || 'No';
          this.shift_allocator = r.shift_allocator || 'No';
        },
        error: () => {
          /* list must still render */
        },
      });
  }
   
 
 

  material_type = 'Raw Material';
  pageSize = 25;
  currentPage = 1;

  /** Log list / Excel export align with new material form: raw vs packing columns */
  get isRawMaterialLog(): boolean {
    return this.material_type === 'Raw Material';
  }

  get isPackingMaterialLog(): boolean {
    return this.material_type === 'Packing Material';
  }

  /** Main list: approved/active materials only (In-Active → In-Active tab). */
  getMaterialsLog() {
    this.loading = true;
    const materialType = encodeURIComponent(this.material_type);
    this.service.get('master/material.php?type=getMaterials&material_type=' + materialType).subscribe({
      next: (response) => {
        this.results = Array.isArray(response) ? response : [];
        this.currentPage = 1;
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.results = [];
      },
    });
  }



  downloadReport() {
    const q = encodeURIComponent((this.searchQuery || '').trim());
    const matType = encodeURIComponent(this.material_type || '');
    this.service.open('master/material.php?type=downloadMaterialLogExcel&material_type=' + matType + '&search=' + q);
  }
 

  
  view(data: any) {
    this.selectedResult = data || {};
    this.isView = true;
    this.isEdit = false;
  }

  edit(data: any) {
    // No separate edit panel in template — open read-only view (avoid blank page from isEdit gate)
    this.view(data);
  }

  closeDetailPanel() {
    this.isView = false;
    this.isEdit = false;
    this.selectedResult = {};
  }

  submitForApproval(form: { valid: boolean }) {
    if (!form.valid) {
      alertify.error('Please fill all required fields');
      return;
    }
    const r = this.selectedResult;
    if (!r?.id) {
      alertify.error('Invalid material record');
      return;
    }
    const uploadData = new FormData();
    const keys = [
      'id', 'material_subtype', 'material_nature', 'category', 'material_name', 'material_name_report',
      'grade', 'uom', 'alternate_uom', 'color_index', 'dimension', 'madeOf', 'tax_type', 'gst',
      'equivalancy_applicable', 'assayCalculation', 'sub_type', 'artwork', 'product_n', 'retest_month',
      'indent_type', 'density', 'storage_condition', 'inventory', 'maxInventory', 'specificGravity',
      'texture', 'moq', 'plasticType', 'inventoryValueMax', 'premixItem', 'lead_time', 'description', 'safety',
    ];
    keys.forEach((key) => {
      const value = r[key];
      if (value != null && value !== '') {
        uploadData.append(key, String(value));
      }
    });
    if (r['equivalent_to'] != null) {
      const eq = r['equivalent_to'];
      uploadData.append(
        'equivalent_to',
        typeof eq === 'string' ? eq : JSON.stringify(eq)
      );
    }
    this.loading = true;
    this.service
      .post('master/material.php?type=updateMaterialForApproval&id=' + r['id'], uploadData)
      .subscribe({
        next: (response: any) => {
          this.loading = false;
          if (response?.status === 'success') {
            alertify.success('Material updated and sent for approval');
            this.closeDetailPanel();
            this.getMaterialsLog();
          } else {
            alertify.error(response?.message || response?.status || 'Failed to submit for approval');
          }
        },
        error: () => {
          this.loading = false;
          alertify.error('Failed to update material. Please try again.');
        },
      });
  }

 
 
  viewMsds(url) {
    url = this.service.url + '../../upload/material/' + url;
    window.open(url, '_blank');
  }

  /** Format entry date for display; returns 'NA' when null/undefined/invalid */
  formatEntryDate(val: any): string {
    if (val == null || val === '') return 'NA';
    const d = typeof val === 'string' ? new Date(val) : val;
    if (isNaN(d.getTime())) return 'NA';
    return d.toLocaleDateString();
  }

  /** Display "Name (ID)" for Entry By column; supports common API field names */
  getEntryByDisplay(result: any): string {
    if (!result) return 'NA';
    const name = result.entry_by_name || result.created_by_name || result.entry_by || result.created_by || '';
    const id = result.entry_by_id || result.created_by_id || '';
    if (name && id) return name + ' (' + id + ')';
    if (name) return name;
    if (id) return 'ID: ' + id;
    return 'NA';
  }

  /** Password modal for Inactive / Active: open and store pending action */
  isStatusPasswordModal = false;
  statusAuthPassword = '';
  pendingStatusChange: { id: number | null; status: string | null } = { id: null, status: null };

  get pendingStatusActionLabel(): string {
    if (!this.pendingStatusChange.status) return '';
    return this.pendingStatusChange.status === 'In-Active' ? 'Inactive' : 'Active';
  }

  openStatusAuthModal(id: number, status: string) {
    this.pendingStatusChange = { id, status };
    this.statusAuthPassword = '';
    this.isStatusPasswordModal = true;
  }

  closeStatusAuthModal() {
    this.isStatusPasswordModal = false;
    this.pendingStatusChange = { id: null, status: null };
    this.statusAuthPassword = '';
  }

  submitStatusAuth(form: { valid: boolean; reset: () => void }) {
    if (!form.valid || !this.statusAuthPassword || !this.pendingStatusChange.id || !this.pendingStatusChange.status) {
      alertify.error('Please enter password or PIN');
      return;
    }
    const id = this.pendingStatusChange.id;
    const status = this.pendingStatusChange.status;
    this.service.verifyAuthCredential(this.statusAuthPassword).subscribe({
      next: (response) => {
        if (response.status === 'success') {
          alertify.success('Password verified');
          this.closeStatusAuthModal();
          if (form.reset) form.reset();
          this.changeStatus(id, status);
        } else {
          alertify.error(response.message || 'Invalid password or PIN. Action not allowed.');
        }
      },
      error: () => {
        alertify.error('Could not verify password. Check your connection.');
      },
    });
  }

  changeStatus(id, status) {
    let url = 'master/material.php?type=ChangeMaterialStatus&id=' + id + '&status=' + encodeURIComponent(status);
    if (status === 'In-Active') {
      const inActiveDate = new Date().toISOString();
      const inactivatedById = localStorage.getItem('emp_id') || '';
      const inactivatedByName = localStorage.getItem('username') || localStorage.getItem('user') || '';
      url += '&in_active_date=' + encodeURIComponent(inActiveDate);
      url += '&inactivated_by_id=' + encodeURIComponent(inactivatedById);
      url += '&inactivated_by_name=' + encodeURIComponent(inactivatedByName);
    }
    this.service.get(url).subscribe({
      next: (response: any) => {
        const ok = response && String(response['status']).toLowerCase() === 'success';
        if (ok) {
          alertify.success('Material Status Changed Successfully');
          this.getMaterialsLog();
        } else {
          const msg = response && response['status'] ? String(response['status']) : 'Update failed';
          alertify.error(msg);
        }
      },
      error: () => {
        alertify.error('Failed to change material status. Please try again.');
      },
    });
  }

  /** Approved in DB may be "Approved" or legacy "approve". */
  isApprovedMaterialStatus(status: string): boolean {
    const s = (status || '').toString().trim().toLowerCase();
    return s === 'approved' || s === 'approve';
  }

 
  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.results) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      return Object.entries(material || {}).some(([key, value]) => {
        if (value == null) {
          return false;
        }
        if (key === 'entry_date' || key === 'created_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          if (!(dateValue instanceof Date) || isNaN(dateValue.getTime())) {
            return String(value).toLowerCase().includes(query);
          }
          return dateValue.toISOString().slice(0, 10).includes(query);
        }
        return String(value).toLowerCase().includes(query);
      });
    });
  }

  get totalEntries(): number {
    return this.filteredMaterials.length;
  }

  get totalPages(): number {
    const total = this.filteredMaterials.length;
    return Math.max(1, Math.ceil(total / this.pageSize));
  }

  get pageNumbers(): number[] {
    return Array.from({ length: this.totalPages }, (_, i) => i + 1);
  }

  get pagedMaterials(): any[] {
    const start = (this.currentPage - 1) * this.pageSize;
    return this.filteredMaterials.slice(start, start + this.pageSize);
  }

  onFilterChange() {
    this.currentPage = 1;
  }

  onPageNoChange(value: any) {
    const p = Number(value) || 1;
    this.currentPage = Math.min(Math.max(1, p), this.totalPages);
  }

  onPageSizeChange(value: any) {
    const s = Number(value) || 25;
    this.pageSize = s;
    this.currentPage = 1;
  }

  get paginationStart(): number {
    if (this.totalEntries === 0) {
      return 0;
    }
    return (this.currentPage - 1) * this.pageSize + 1;
  }

  get paginationEnd(): number {
    return Math.min(this.currentPage * this.pageSize, this.totalEntries);
  }

  get canGoPrevious(): boolean {
    return this.currentPage > 1;
  }

  get canGoNext(): boolean {
    return this.currentPage < this.totalPages;
  }

  goToFirstPage(): void {
    this.currentPage = 1;
  }

  goToPreviousPage(): void {
    if (this.canGoPrevious) {
      this.currentPage--;
    }
  }

  goToNextPage(): void {
    if (this.canGoNext) {
      this.currentPage++;
    }
  }

  goToLastPage(): void {
    this.currentPage = this.totalPages;
  }
}
