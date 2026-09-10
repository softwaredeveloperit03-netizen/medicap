import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css', '../../../shared/purchase-vapp-host.css'],
})
export class NewComponent implements OnInit {
  @ViewChild('requirementDateInput') requirementDateInputRef: ElementRef<HTMLInputElement>;

  vendors: any = [];
  departments: any = [];
  materials_data: any = [];
  show_materials: any[] = [];
  selectedMaterial: any;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.getallmaterial();
    this.getDepartment();
    this.getManufactures();
   }

  material_type = 'Stationary';

  getallmaterial() {
    if (!this.material_type) {
      this.materials_data = [];
      return;
    }

    // Handle RM/PM Material - fetch both Raw Material and Packing Material
    if (this.material_type === 'RM/PM Material') {
      this.getRMPMMaterials();
      return;
    }

    // For other material types, use the standard API
    this.service.get('common.php?type=getallmatdataForIndent&material_type=' + encodeURIComponent(this.material_type)).subscribe((response: any) => {
      this.materials_data = Array.isArray(response) ? response : [];
      this.selectedMaterial = null; // Reset selection when material type changes
    }, (error) => {
      console.error('Error fetching materials:', error);
      this.materials_data = [];
    });
  }

  /**
   * Fetch both Raw Material (RM) and Packing Material (PM) when RM/PM Material is selected
   */
  getRMPMMaterials() {
    const rmPromise = this.service.get('common.php?type=getallmatdataForIndent&material_type=' + encodeURIComponent('Raw Material')).toPromise();
    const pmPromise = this.service.get('common.php?type=getallmatdataForIndent&material_type=' + encodeURIComponent('Packing Material')).toPromise();

    Promise.all([rmPromise, pmPromise])
      .then((responses: any[]) => {
        const rmMaterials = Array.isArray(responses[0]) ? responses[0] : [];
        const pmMaterials = Array.isArray(responses[1]) ? responses[1] : [];
        
        // Combine both arrays and remove duplicates based on material_code
        const combinedMaterials = [...rmMaterials, ...pmMaterials];
        const uniqueMaterials = combinedMaterials.filter((material, index, self) =>
          index === self.findIndex((m) => m.material_code === material.material_code)
        );
        
        this.materials_data = uniqueMaterials;
        this.selectedMaterial = null; // Reset selection when material type changes
      })
      .catch((error) => {
        console.error('Error fetching RM/PM materials:', error);
        // Try alternative: single API call with RM/PM Material
        this.service.get('common.php?type=getallmatdataForIndent&material_type=' + encodeURIComponent('RM/PM Material')).subscribe((response: any) => {
          this.materials_data = Array.isArray(response) ? response : [];
          this.selectedMaterial = null;
        }, (err) => {
          console.error('Error with RM/PM Material API:', err);
          this.materials_data = [];
        });
      });
  }

  getDepartment() {
    this.service.get('common.php?type=getDepartments').subscribe((response: any) => {
      this.departments = Array.isArray(response) ? response : [];
    });
  }

  isRmPmMaterialLine(item?: any): boolean {
    if (this.material_type === 'RM/PM Material') {
      return true;
    }
    const t = String(item?.material_type || '').trim().toLowerCase();
    return t === 'raw material' || t === 'packing material' || t === 'rm' || t === 'pm';
  }

  departmentsForIndent(item?: any): any[] {
    const list = Array.isArray(this.departments) ? this.departments : [];
    if (!this.isRmPmMaterialLine(item)) {
      return list;
    }
    return list.filter((dept: any) => this.isRmPmAllowedDepartment(dept?.department_name));
  }

  private isRmPmAllowedDepartment(name: any): boolean {
    const n = String(name || '').trim().toLowerCase().replace(/\s+/g, ' ');
    if (n === 'production') {
      return true;
    }
    if (n === 'purchase' || n === 'purchase department') {
      return true;
    }
    if (n === 'npd' || n === 'npd department' || n === 'new product development') {
      return true;
    }
    const compact = n.replace(/&/g, 'and').replace(/\s+/g, '');
    if (compact === 'randd' || compact === 'rnd') {
      return true;
    }
    if (n === 'r&d department' || n === 'r & d department' || n === 'rnd department') {
      return true;
    }
    if (n.includes('research') && n.includes('development')) {
      return true;
    }
    return false;
  }
    
  searchTerm: string;
  tableSearchQuery = '';

  get filteredItems() {
    const list = Array.isArray(this.materials_data) ? this.materials_data : [];
    const term = (this.searchTerm || '').toLowerCase();
    return term ? list.filter((item: any) => (item.material_name || '').toLowerCase().includes(term)) : list;
  }

  get filteredFinalMaterial(): any[] {
    const list = Array.isArray(this.final_material) ? this.final_material : [];
    const term = (this.tableSearchQuery || '').toLowerCase().trim();
    if (!term) return list;
    return list.filter((item: any) =>
      (item.material_name || '').toLowerCase().includes(term) ||
      (item.material_code || '').toLowerCase().includes(term)
    );
  }

  openRequirementDatePicker(): void {
    const el = this.requirementDateInputRef?.nativeElement;
    if (el) {
      el.focus();
      if (typeof (el as HTMLInputElement & { showPicker?: () => void }).showPicker === 'function') {
        (el as HTMLInputElement & { showPicker: () => void }).showPicker();
      }
    }
  }

  selectItem(item: any) {
    this.selectedMaterial = item;
  }

  private resolveLineMaterialType(material: any): string {
    if (this.material_type !== 'RM/PM Material') {
      return this.material_type;
    }
    let mt = String(material?.material_type || material?.type || '').trim();
    if (mt === 'RM') {
      mt = 'Raw Material';
    } else if (mt === 'PM') {
      mt = 'Packing Material';
    }
    const lower = mt.toLowerCase();
    if (lower.includes('packing')) {
      return 'Packing Material';
    }
    if (lower.includes('raw')) {
      return 'Raw Material';
    }
    return mt || 'Raw Material';
  }

  add() {
    if (!this.selectedMaterial) {
      return;
    }
    if (!this.requirement) {
      alertify.error('Please select requirement date');
      return;
    }
    this.show_materials.push({
      ...this.selectedMaterial,
      material_type: this.resolveLineMaterialType(this.selectedMaterial),
    });
    this.selectedMaterial = null;
    this.searchTerm = '';
  }
 
  getManufactures() {
    this.service.get('common.php?type=getVendorForgenIndednt').subscribe((response: any) => {
      this.vendors = Array.isArray(response) ? response : [];
    });
  }

  requirement = '';

  /** Minimum date for Requirement Date (today) – no past dates allowed. */
  get minRequirementDate(): string {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  final_material: any[] = [];

  addindent(form: NgForm) {
    if (!this.show_materials.length) {
      return;
    }
    if (!form.valid) {
      alertify.error('Please enter required field');
      return;
    }
    const row = this.show_materials[0];
    const temp: any = {
      material_type: row.material_type,
      material_code: row.material_code,
      material_name: row.material_name,
      qty: row.qty,
      unit: row.unit,
      vendor_no: row.vendor_no,
      department: row.department,
      requirement: this.requirement,
    };

    const selectedVendor = this.vendors.find(
      (v: any) => String(v.vendor_no) === String(row.vendor_no)
    );
    if (selectedVendor) {
      temp.vendor_name = selectedVendor.vendor_name;
    } else if (row.vendor_no === 'NA' || row.vendor_no === '' || row.vendor_no == null) {
      temp.vendor_name = 'NA';
    }

    this.final_material.push(temp);
    this.show_materials = [];
    form.resetForm();
  }

  deleteshowmat(item: any) {
    const idx = this.final_material.indexOf(item);
    if (idx >= 0) {
      this.final_material.splice(idx, 1);
    }
  }
 
  save(){

    if (this.final_material.length == 0) {
      alert('Please Add Material!!!!');
      return;
    }

    // Saved with status TO_HOD / To_HOD_RMPM so it appears on /hrfordepthead/indent.
    this.service.post('purchase/indent.php?type=saveIndentPurchase', JSON.stringify(this.final_material)).subscribe((response: any) => {
      if (response['status'] == 'success') {
        const dept = String(this.final_material[0]?.department || '').trim();
        if (dept) {
          try {
            localStorage.setItem('department', dept);
          } catch { /* ignore */ }
        }
        alertify.success('Saved — sent to Dept Head for approval');
        this.final_material = [];
        this.router.navigate(['/hrfordepthead/indent']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
    
  }

}

