import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import { RmMasterCustomisationService } from 'src/app/qa/soft-restriction/rm-master-customisation/rm-master-customisation.service';
import { GmpMaterialFormCustomisationService } from 'src/app/qa/soft-restriction/rm-master-customisation/gmp-material-form-customisation.service';
import {
  RM_KEY_TO_LAYOUT_KEY,
  MaterialFormFieldRuntime,
  MaterialFieldType,
  buildDefaultRuntimeFields,
  mergeGmpLayout,
  applyRmVisibilityFallback,
  sortByGroup,
} from 'src/app/qa/soft-restriction/rm-master-customisation/material-master-form-layout.constants';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  specification_type = 'Single Grade';
  msdsFile: File;
  category='In Active'; 
  equivalancy_applicable = 'No';
  grades;
  grade;
  types;
  units;
  material_sub_type_id=0;
  msds_file_path: any;
  sub_type = 'Non Printed';
  gst='0';
  tax='0';
  matIs = 'OWN';

  /** Dynamic form layout (GMP) + fallback RM visibility */
  runtimeFields: MaterialFormFieldRuntime[] = buildDefaultRuntimeFields();
  private runtimeByKey = new Map<string, MaterialFormFieldRuntime>();
  coreFields: MaterialFormFieldRuntime[] = [];
  fullFields: MaterialFormFieldRuntime[] = [];
  gridFields: MaterialFormFieldRuntime[] = [];
  otherFields: MaterialFormFieldRuntime[] = [];
  readonly knownFieldKeys = new Set(buildDefaultRuntimeFields().map((f) => f.field_key));

  constructor(
    private service: DataAccessService,
    private router: Router,
    private rmCustomisation: RmMasterCustomisationService,
    private gmpForm: GmpMaterialFormCustomisationService
  ) { }

  ngOnInit(): void {
    this.rebuildLayoutSlices();
    this.loadFormLayout();
    this.getMaterialType();
    this.getUnits();
    this.getGrades();
    this.getSorageConditions();
    this.getPackSizes();
    this.getProduct();
    this.getActiveClient();
  }

  private rebuildLayoutSlices(): void {
    this.runtimeByKey = new Map(this.runtimeFields.map((f) => [f.field_key, f]));
    this.coreFields = sortByGroup(this.runtimeFields, 'core');
    this.fullFields = sortByGroup(this.runtimeFields, 'full');
    this.gridFields = sortByGroup(this.runtimeFields, 'grid');
    this.otherFields = sortByGroup(this.runtimeFields, 'other');
  }

  /** Load approved GMP layout, else defaults + legacy RM applicability */
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

  ftype(key: string): MaterialFieldType {
    return this.runtimeByKey.get(key)?.field_type ?? 'STANDARD';
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

  canShowLayoutField(fc: MaterialFormFieldRuntime): boolean {
    const k = fc.field_key;
    const mt = this.material_type;
    const isPacking = mt === 'Packing Material';

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
        return isPacking && this.sub_type === 'Printed';
      case 'salt_equivalency':
      case 'assay_calculation':
        return this.category === 'Active' && mt === 'Pharma Raw Material';
      case 'equiv_table':
        return this.category === 'Active' && mt === 'Pharma Raw Material' && this.equivalancy_applicable === 'Yes';
      case 'mother_material_code':
        return this.matIs === 'Client';
      case 'indent_type':
        return this.matIs === 'Client';
      case 'tax_gst':
        return this.taxType === 'Local' || this.taxType === 'Import' || this.taxType === 'Local/Import';
      case 'density':
        return this.selectedMaterial['material_nature'] === 'LIQUID';
      case 'specific_gravity':
        return !isPacking;
      case 'texture':
        return isPacking;
      default:
        return true;
    }
  }

  /** Legacy RM field keys used in template (maps to layout keys) */
  isFieldApplicable(fieldKey: string): boolean {
    if (fieldKey === 'other_information') {
      return this.showOtherInformationSection;
    }
    const layoutKey = RM_KEY_TO_LAYOUT_KEY[fieldKey] ?? fieldKey;
    return this.isMaterialFieldActive(layoutKey);
  }

  isCbTrue(prop: string): boolean {
    const v = this.selectedMaterial[prop];
    return v === true || v === 'Yes' || v === '1' || v === 1;
  }

  onCbChange(e: Event, prop: string): void {
    const checked = (e.target as HTMLInputElement).checked;
    this.selectedMaterial[prop] = checked ? 'Yes' : 'No';
  }

  /** Checkbox binding for root string fields (e.g. type, color) */
  isRootCbYes(prop: string): boolean {
    const v = (this as any)[prop];
    return v === true || v === 'Yes' || v === '1' || v === 1;
  }

  onRootCbChange(e: Event, prop: string): void {
    const checked = (e.target as HTMLInputElement).checked;
    (this as any)[prop] = checked ? 'Yes' : '';
  }

  trackByFieldKey(_i: number, f: MaterialFormFieldRuntime): string {
    return f.field_key;
  }

  isCustomField(fc: MaterialFormFieldRuntime): boolean {
    return !this.knownFieldKeys.has(fc.field_key);
  }

  customFieldModelKey(fc: MaterialFormFieldRuntime): string {
    return 'custom_' + fc.field_key;
  }

  customFieldNameAttr(fc: MaterialFormFieldRuntime): string {
    return 'custom_' + fc.field_key;
  }

  getCustomFieldValue(fc: MaterialFormFieldRuntime): any {
    return this.selectedMaterial[this.customFieldModelKey(fc)];
  }

  setCustomFieldValue(fc: MaterialFormFieldRuntime, value: any): void {
    this.selectedMaterial[this.customFieldModelKey(fc)] = value;
  }

  /** Returns true if HSN value is non-empty and not 4-8 digits (for template use; regex cannot be in template) */
  isHsnInvalid(value: string): boolean {
    if (value == null || typeof value !== 'string') return false;
    const v = value.trim();
    if (v === '') return false;
    return !/^\d{4,8}$/.test(v);
  }




      clients;
    getActiveClient() {
        this.service.get('marketing/client.php?type=getActiveClient').subscribe(response => {
          this.clients = response;
        });
    }

    clientsSubGrps;
    getClientSeries(client_code) {
        this.service.get('marketing/client.php?type=getClientSeries&client_code='+client_code).subscribe(response => {
          this.clientsSubGrps = response;
        });
    }
 














  material_subtype = '';

  extMaterials;
  getExistingMaterial() {
    this.service.get('master/rnd_material.php?type=getExistingMaterial&material_type='+this.material_type + '&material_subtype='+this.material_subtype).subscribe((response) => {
        this.extMaterials = response;
    });
  }
 
  taxType=''

  material_name_report = '';

  type = '';
  madeOf = '';
  color = '';
  dimension = '';
  pack_size = '';
  makeMaterialName(){
      this.material_name_report = this.type+'_'+this.madeOf+'_'+this.color+'_'+this.dimension+'_'+this.pack_size;
  }

getTaxList(value) {
this.getGST(value)
}

Functional_category='';
Functional_categoryList=[];
AddFunctional_category(){
 let temp = {}
    if(this.Functional_category==''){
      alertify.error("Functional_category Field is Required!!!!!!!");
      return;
    }
    temp['Functional_category']=this.Functional_category
    this.Functional_categoryList.push(temp);
    this.Functional_category = '';
  }
   gst_list;
   tax_list;

  getGST(value) {
    this.service.get('common.php?type=getGST').subscribe(response => {
       this.gst_list = response;
    });
  }

  products;
  getProduct() {
    this.service.get('master/product.php?type=getProductsForMaterialsMaster').subscribe((response) => {
        this.products = response;
    });
  }
 
 
  /** Comma-separated grade values sent to API (same as before) */
  selectedGrades = '';
  /** Multi-select grades via checkboxes */
  private readonly selectedGradeValues = new Set<string>();

  isGradeSelected(grade: string | number): boolean {
    return this.selectedGradeValues.has(String(grade));
  }

  toggleGrade(grade: string | number, checked: boolean): void {
    const g = String(grade);
    if (checked) {
      this.selectedGradeValues.add(g);
    } else {
      this.selectedGradeValues.delete(g);
    }
    this.selectedGrades = Array.from(this.selectedGradeValues).join(', ');
  }

  selectAllGrades(): void {
    if (!Array.isArray(this.grades)) {
      return;
    }
    this.grades.forEach((d: any) => {
      if (d && d.grade != null && d.grade !== '') {
        this.selectedGradeValues.add(String(d.grade));
      }
    });
    this.selectedGrades = Array.from(this.selectedGradeValues).join(', ');
  }

  clearAllGrades(): void {
    this.selectedGradeValues.clear();
    this.selectedGrades = '';
  }


  isSaltEqu = false;

  chnageSalt(value){
    if(value == 'Yes'){
      this.isSaltEqu = true;
    }else{
      this.isSaltEqu = false;
    }
  }

  packSizes;

  getPackSizes(){
    this.service.get('common.php?type=getPackSizes&for=Material').subscribe(response=>{
      this.packSizes=response;
    });
  }
 
  
 
  isGenerateClientMatCode = false;


  selectedMaterial = {};
  mother_material_code = '';
  copyFromExtMaterial(){
    this.selectedMaterial = this.extMaterials.find(emat => emat.material_code === this.mother_material_code);

    this.category = this.selectedMaterial['category'];
    this.material_name_report = this.selectedMaterial['material_name_report'];
    this.type = this.selectedMaterial['type'];
    this.pack_size = this.selectedMaterial['pack_size'];
    this.color = this.selectedMaterial['color'];
    this.dimension = this.selectedMaterial['dimension'];
    this.madeOf = this.selectedMaterial['madeOf'];
    this.taxType = this.selectedMaterial['taxType'];
    this.equivalancy_applicable = this.selectedMaterial['equivalancy_applicable'];
    this.taxType = this.selectedMaterial['taxType'];
  }

 
  indent_type = 'Mother Code';
  material_type = 'Raw Material';

  getMaterialType(){
    this.service.get('master/materialtype.php?type=getMatTypeByMatType&material_type='+this.material_type).subscribe(response => {
      this.types= response;
     });

     this.category = 'In Active';
     if(this.matIs == 'OWN'){
        this.indent_type = 'Mother Code';
     }else{
        this.indent_type = 'Client Code';
     }
      
  }

  eqmaterials =[];
  AddEqMaterial(data){
    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }
    let temp = data.value;
    this.eqmaterials.push(temp);
    data.reset();
  }

  DelEqMaterial(index){
    this.eqmaterials.splice(index,1);
  }

  materialSubTypeCode = '';

  setSubMaterialType() {
    if (!this.types || !this.material_subtype) return;
    const found = this.types.find((t: any) => t.material_subtype === this.material_subtype);
    if (found) {
      this.material_sub_type_id = found.id;
      this.materialSubTypeCode = found.Short_Code || '';
      this.getExistingMaterial();
    } else {
      this.materialSubTypeCode = '';
      this.material_sub_type_id = 0;
    }
  }

  packSizeCode = '';
  getpackSiezeCode() {
    if (!this.packSizes || !this.pack_size) {
      this.packSizeCode = '';
      this.makeMaterialName();
      return;
    }
    const found = this.packSizes.find((p: any) => (p.pack_size + ' ' + (p.unit || '')).trim() === this.pack_size.trim());
    this.packSizeCode = found ? (found.psCode || '') : '';
    this.makeMaterialName();
  }
 

  onFileChanged(event) {
    if (event.target.files.length === 1) {
      this.msdsFile = event.target.files[0];
    }
  }

  
  getUnits() {
    this.service.get('common.php?type=getUnits_List').subscribe(response => {
      this.units= response;
    });
  }
 
 
  /** Returns list of mandatory field labels that are missing or invalid */
  getMissingMandatoryFields(form: NgForm): string[] {
    const missing: string[] = [];
    const v = form.value;
    const c = form.controls;
    const invalid = (ctrl: any) => ctrl && ctrl.invalid && ctrl.errors?.required;

    if (invalid(c.matIs)) missing.push('Material (OWN/Client)');
    if (this.isMaterialFieldActive('material_group_type') && invalid(c.material_type)) missing.push('Material Group/Type');
    if (this.isMaterialFieldActive('sub_group_type') && invalid(c.material_subtype)) missing.push('Sub Group/Type');
    if (v.material_type && v.material_type !== 'Packing Material') {
      if (this.isMaterialFieldActive('nature_of_material') && invalid(c.material_nature)) missing.push('Nature Of Material');
      if (this.isMaterialFieldActive('category') && invalid(c.category)) missing.push('Category');
    }
    if (this.isMaterialFieldActive('material_name') && invalid(c.material_name)) missing.push('Material Name');
    if (v.material_type === 'Packing Material' && this.isMaterialFieldActive('material_name_report') && invalid(c.material_name_report)) {
      missing.push('Material Name Report');
    }
    if (this.isMaterialFieldActive('item_unit') && invalid(c.uom)) missing.push('Item Unit');
    if (this.isMaterialFieldActive('billing_unit') && invalid(c.alternate_uom)) missing.push('Billing Unit');
    if (v.material_type === 'Packing Material' && this.isMaterialFieldActive('packing_size') && invalid(c.pack_size)) {
      missing.push('Size/Specification');
    }
    if (this.isMaterialFieldActive('tax_type') && invalid(c.taxType)) missing.push('Tax Type');
    if (this.isMaterialFieldActive('tax_gst') && (v.taxType === 'Local' || v.taxType === 'Import' || v.taxType === 'Local/Import') && invalid(c.gst)) {
      missing.push(v.taxType === 'Import' ? 'Tax' : 'GST/Tax');
    }
    if (v.material_type === 'Packing Material' && this.isMaterialFieldActive('packing_sub_type') && invalid(c.sub_type)) {
      missing.push('Sub Type');
    }
    if (v.sub_type === 'Printed' && this.isMaterialFieldActive('artwork') && invalid(c.artwork)) missing.push('Artwork');
    if (v.sub_type === 'Printed' && this.isMaterialFieldActive('packing_product') && invalid(c.product_n)) missing.push('Product');
    if (v.matIs === 'Client' && invalid(c.client_code)) missing.push('Client');
    if (this.isMaterialFieldActive('retest_month') && invalid(c.retest_month)) missing.push('Retest Month');
    if (v.matIs === 'Client' && this.isMaterialFieldActive('indent_type') && invalid(c.indent_type)) missing.push('Purchase Requisition Type');
    if (this.isMaterialFieldActive('grade') && (!this.selectedGrades || this.selectedGrades.trim() === '')) missing.push('Grade');
    return missing;
  }

  addMaterial(data: NgForm) {
    data.form.markAllAsTouched();
    const missing = this.getMissingMandatoryFields(data);
    if (missing.length > 0) {
      alertify.error('Missing mandatory fields: ' + missing.join(', '));
      return;
    }
    const hsn = (data.value && data.value.hsn) ? String(data.value.hsn).trim() : '';
    if (this.isMaterialFieldActive('hsn') && hsn !== '') {
      if (!/^\d{4,8}$/.test(hsn)) {
        alertify.error('HSN Code must be 4 to 8 digits only.');
        return;
      }
    }

    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    uploadData.append('plant_code', localStorage.getItem('plant_code'));
    if(temp['material_type'] == 'Raw Material'){
      uploadData.append('materialTypeCode', 'R');
    }else{
      uploadData.append('materialTypeCode', 'P');
    }
    uploadData.append('materialSubTypeCode', this.materialSubTypeCode);
    uploadData.append('packSizeCode', this.packSizeCode);

    uploadData.append('grade', this.selectedGrades);
    uploadData.append('matIs', this.matIs);
  
    uploadData.append('indent_type', this.indent_type);
    uploadData.append('tax', 0+'');
    uploadData.append('material_sub_type_id', this.material_sub_type_id+'');
   
    if (this.msdsFile !== undefined) {
      uploadData.append('msds_file', this.msdsFile, this.msdsFile.name);
    }
  
    uploadData.append('eqmaterials', JSON.stringify(this.eqmaterials));
    uploadData.append('Functional_categoryList', JSON.stringify(this.Functional_categoryList));

    this.service.post('master/rnd_material.php?type=saveMaterial', uploadData).subscribe(response => {
      if(response['status'] === 'success') {
        this.router.navigate(['/master/material/raw']);
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      }else {    
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  saveClientMatCode(data) {

    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }

    const temp = this.selectedMaterial;
    temp['client_code'] = data.value.client_code;

    this.service.post('master/rnd_material.php?type=saveClientMatCode', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
        this.router.navigate(['/master/material/raw']);
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.isGenerateClientMatCode = false;
      }else {    
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  
 

  storage_conditions;

  getSorageConditions() {
     this.service.get('common.php?type=getStorageConditions').subscribe(response => {
      this.storage_conditions = response
    })
  } 

  getGrades(){
    this.grades =[];
    this.service.get('master/product.php?type=getGrades').subscribe(response => {
      this.grades = Array.isArray(response) ? response : [];

      // this.service.observableGrade.subscribe(response => {
      //   this.grades = response;
      // });
    
    })
  }

}
