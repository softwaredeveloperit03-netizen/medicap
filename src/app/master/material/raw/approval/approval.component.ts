import { Component, OnInit } from '@angular/core';
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
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  loggedInDept = localStorage.getItem('department');
  plant_id = this.service.getPlantConfigFields('plant_id');
  plant_type = this.service.getPlantConfigFields('plant_type');

  /** Same GMP layout as material/new — drives Other Information visibility */
  runtimeFields: MaterialFormFieldRuntime[] = buildDefaultRuntimeFields();
  private runtimeByKey = new Map<string, MaterialFormFieldRuntime>();
  otherFields: MaterialFormFieldRuntime[] = [];
  readonly knownFieldKeys = new Set(buildDefaultRuntimeFields().map((f) => f.field_key));

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

  get showOtherInformationSection(): boolean {
    return this.otherFields.some((f) => this.isMaterialFieldActive(f.field_key));
  }

  isMaterialFieldActive(layoutKey: string): boolean {
    const fc = this.runtimeByKey.get(layoutKey);
    if (!fc) {
      return true;
    }
    if (fc.applicable === 'Not Applicable') {
      return false;
    }
    return this.canShowLayoutField(fc);
  }

  private canShowLayoutField(fc: MaterialFormFieldRuntime): boolean {
    const r = this.selectedResult as any;
    if (!r || (Array.isArray(r) && r.length === 0) || Object.keys(r).length === 0) {
      return true;
    }
    const k = fc.field_key;
    const mt = r.material_type;
    const isPacking = mt === 'Packing Material';
    const subType = r.sub_type || '';
    const category = r.category;
    const matIs = r.matIs;
    const taxType = r.tax_type || r.taxType;
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

  trackByFieldKey(_i: number, f: MaterialFormFieldRuntime): string {
    return f.field_key;
  }

  isCustomField(fc: MaterialFormFieldRuntime): boolean {
    return !this.knownFieldKeys.has(fc.field_key);
  }

  private customFieldModelKey(fc: MaterialFormFieldRuntime): string {
    return 'custom_' + fc.field_key;
  }

  getCustomFieldValue(fc: MaterialFormFieldRuntime): any {
    const r = this.selectedResult as any;
    if (!r) {
      return '';
    }
    return r[this.customFieldModelKey(fc)] ?? r[fc.field_key] ?? '';
  }

  results;
  material_type = 'Raw Material';

  getMaterialsLog() {
    this.service.get('master/material.php?type=getMaterialsForApproval&material_type='+this.material_type).subscribe((response) => {
        this.results = response;
      });
  }

  selectedResult: any = {};
  isView = false;

  view(data) {
    this.selectedResult = data;
    this.isView = true;
  }

  viewMsds(url) {
    url = this.service.url + '../../upload/material/' + url;
    window.open(url, '_blank');
  }

  ApproveMaterial(){

    let temp ={};

    this.service.post('master/material.php?type=approveMaterial&id=' + this.selectedResult['id'] , JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] == 'success') {
        alertify.success('Material Approved Successfully');
        this.isView = false;
        this.getMaterialsLog();
      } else {
        alertify.error(response['status']);
      }
    });

  }

  searchQuery;

  /** Newest entries first (new pending material at row 1). */
  private sortNewestFirst(materials: any[]): any[] {
    if (!materials || !Array.isArray(materials)) {
      return [];
    }
    return [...materials].sort((a, b) => {
      const idA = Number(a?.id) || 0;
      const idB = Number(b?.id) || 0;
      if (idB !== idA) {
        return idB - idA;
      }
      const dateA = new Date(a?.entry_date || a?.created_date || 0).getTime();
      const dateB = new Date(b?.entry_date || b?.created_date || 0).getTime();
      return dateB - dateA;
    });
  }

  get filteredMaterials(): any[] {
    const sorted = this.sortNewestFirst(this.results);
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return sorted;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return sorted.filter((material) => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

}
