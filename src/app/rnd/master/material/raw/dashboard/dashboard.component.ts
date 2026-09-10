import { Component, OnInit } from '@angular/core';
import { forkJoin, of } from 'rxjs';
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
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  results;
  loading = false;

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

  constructor(
    private service: DataAccessService,
    private rmCustomisation: RmMasterCustomisationService,
    private gmpForm: GmpMaterialFormCustomisationService
  ) {
    this.loggedInDept = localStorage.getItem('department');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
  }

  ngOnInit(): void {
    this.rebuildLayoutSlices();
    this.loadFormLayout();
    this.getMaterialsLog();
    this.get_rights();
  }

  private rebuildLayoutSlices(): void {
    this.runtimeByKey = new Map(this.runtimeFields.map((f) => [f.field_key, f]));
    this.otherFields = sortByGroup(this.runtimeFields, 'other');
  }

  loadFormLayout(): void {
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
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept ).subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
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

  /** Fetches main log (Approved) + In-Active materials and merges so inactivated materials show in log and do not vanish */
  getMaterialsLog() {
    this.loading = true;
    const baseUrl = 'master/rnd_material.php';
    const materialType = encodeURIComponent(this.material_type);
    const mainLog$ = this.service.get(baseUrl + '?type=getMaterials&material_type=' + materialType);
    const inactiveLog$ = this.service.get(baseUrl + '?type=getMaterialsByStatus&material_type=' + materialType + '&status=In-Active');

    forkJoin([mainLog$, inactiveLog$]).subscribe(([mainList, inactiveList]) => {
      const main = Array.isArray(mainList) ? mainList : [];
      const inactive = Array.isArray(inactiveList) ? inactiveList : [];
      const mainIds = new Set(main.map((m: any) => m.id));
      const onlyInactive = inactive.filter((m: any) => !mainIds.has(m.id));
      this.results = [...main, ...onlyInactive];
      this.currentPage = 1;
      this.loading = false;
    }, () => {
      this.loading = false;
    });
  }



  downloadReport() {
    const q = encodeURIComponent((this.searchQuery || '').trim());
    const matType = encodeURIComponent(this.material_type || '');
    this.service.open('master/rnd_material.php?type=downloadMaterialLogExcel&material_type=' + matType + '&search=' + q);
  }
 

  
  view(data: any) {
    this.selectedResult = data || {};
    this.isView = true;
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
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + encodeURIComponent(this.statusAuthPassword) + '&emp_id=' + encodeURIComponent(localStorage.getItem('emp_id') || '')).subscribe((response) => {
      if (response['status'] === 'success') {
        alertify.success('Password verified');
        this.closeStatusAuthModal();
        if (form.reset) form.reset();
        this.changeStatus(id, status);
      } else {
        alertify.error('Invalid password or PIN. Action not allowed.');
      }
    });
  }

  changeStatus(id, status) {
    let url = 'master/rnd_material.php?type=ChangeMaterialStatus&id=' + id + '&status=' + encodeURIComponent(status);
    // When setting In-Active, send date/time and current user so Inactive list can show "In Active Date" and "InActivated By"
    if (status === 'In-Active') {
      const inActiveDate = new Date().toISOString();
      const inactivatedById = localStorage.getItem('emp_id') || '';
      const inactivatedByName = localStorage.getItem('username') || localStorage.getItem('user') || '';
      url += '&in_active_date=' + encodeURIComponent(inActiveDate);
      url += '&inactivated_by_id=' + encodeURIComponent(inactivatedById);
      url += '&inactivated_by_name=' + encodeURIComponent(inactivatedByName);
    }
    this.service.get(url).subscribe((response) => {
      if (response['status']) {
        alertify.success('Material Status Changed Successfully');
        // Update status in local list so row stays in main log (does not vanish); only status column and button change
        if (this.results && Array.isArray(this.results)) {
          const item = this.results.find((m: any) => m.id == id);
          if (item) {
            item.status = status;
            if (status === 'In-Active') {
              item.in_active_date = item.in_active_date || new Date().toISOString();
              item.inactivated_by_id = item.inactivated_by_id || localStorage.getItem('emp_id') || '';
              item.inactivated_by_name = item.inactivated_by_name || localStorage.getItem('username') || localStorage.getItem('user') || '';
            }
          }
        }
      } else {
        alertify.error('some error occured');
      }
    });
  }

 
  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.results) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
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
}
