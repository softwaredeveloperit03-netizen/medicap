import { ChangeDetectorRef, Component, OnInit, AfterViewInit, ViewChild } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new-product-formulation',
  templateUrl: './new-product-formulation.component.html',
  styleUrls: ['./new-product-formulation.component.css']
})
export class NewProductFormulationComponent implements OnInit, AfterViewInit {
  @ViewChild('productForm') productForm: NgForm;

  Product_type = 'Generic Product';
  type = '';
  unit = '';
  pv_blister = '';
  Combination = '';
  Blister = '';
  primary_subtype = '';
  private primarySubtypeUserSelected = false;
  ismono = 'yes';
  isouter = 'no';
  pvc_color = '';
  copyFromProducts: any[] = [];
  selectedCopyFromId = '';
  packingConfigTemplates: any[] = [];
  packingConfigTemplatesLoading = false;
  packing_configuration_master_id: string = '';
  configuration = '';
  primary_qty: string | number = '';
  mono_qty: string | number = '';
  outer_qty: string | number = '';
  shipper_qty: string | number = '';
  primaryPackingOptions: string[] = [
    'Blister',
    'Strip',
    'Bottle',
    'Tube',
    'Vial',
    'Ampoule',
    'Syringe',
    'Cartriage',
    'Infusion',
    'Sachet',
    'Pouch',
    'Jar',
    'Can',
    'Other',
  ];

 

  short_code1;
short_code2;
short_code3;
short_code4;
short_code5;
short_code6;
short_code7;
  selectedFile1: File;
  selectedFile2: File;
  selectedFile3: File;
  selectedFile4: File;
  selectedFile5: File;
  selectedFile6: File;
  ech_title = '';
  grades;
  qty = 0;
  strength = 0;
  factor;
  clients1;
  dosages;
  clients;
  generics;
  selected;
  isProduct = false;
  gstList;
  add_primary_packing=false;
  add_secondary_packing=false;
  add_pack_size=false;
  add_no_pouch=false;
  add_capsule_size=false;
  add_mono_cartain=false;
  add_mono_qty=false;
  add_master_mono_qty=false;
  Addtertiary_packing=false;
  isShown = false;
  isShown1 = false;
  productList = [];

  styles = [];
  isStyle = false;
  packing_style = '';
  style = '';
  show_view = false;
  equivalancy_applicable = 'No';
  equalant_to = '';
  selected_material = [];
  selectedResult = [];
  dose_unit_name = '';
  dose_unit_qty = '';
  dose_unit_type = '';
  apperance = '';
  dose_unit_name_txt = '';
  dose_unit_qty_unit = '';
  // category = 'Branded';
  copy_from = 'Similar Generic';
  products;
  product_each_units;
  dosage_type = '';
  dosageFormOptions: string[] = [];
  category = '';
  dosage_form = '';
  grade = '';
  manufacture_under = 'Own';

  shelf_life = '';
  isShelf = false;
  shelf = '';
  shelfs: any[] = [];
  clientName2 ;
  storage_condition = '';
  newStorageConditionDraft = '';
  selectedEquivalent = [];
  color = '';
  colors: any[] = [];
  doseunits;
  isColor = false;
  isDoseUnit = false;
  dose_unit = '';
  product_color = '';
  each_unit_type = '';
  labels = [];
  storages;
  isStorage = false;
  mrps;
  isMrp = false;
  product_group;
  market_group;
  materials;
  isProductgroup = false;
  isMarketgroup = false;
  productgroups;
  mastergroups;
  isBlister = false;
  isOther = false;
  isBottel = false;
  isStrip = false;
  isyes = false;
  isNo = false;
  isyesouter = false;
  isNoouter = false;
  addpacking = false;
  shapes;
  artworkList = [];
  list = [];
  isCashsales: boolean;
  cash: string;
  units;
  fg_material_list = [];
  fg_shapes = [];
  fg_sizes = [];
  active_materials;
  active_material;
  fg_sub_types = [];
  fg_sub_materials = []
  mrp_list = [];
  sale_price_list = [];
  change_part_items = [];
  punch_tools;
  change_part;
  equivalancy_factor = 0;
  equivalent_to = '';
  material_grade = ''
material_type: any;
 fssai_number: any;
dosage_sub_type: any;
other_product_description: any;
hsn: any;
gst: any;
fg_material: any[] = [];
clicked = false;
material_grades: any[] = [];
  label_c=[];
  plant_id:any;

  constructor(
    private service: DataAccessService,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
   }

  ngAfterViewInit(): void {
    // Re-fetch after plant/session is ready (first load can run before plant_id is set)
    setTimeout(() => {
      this.loadPackingConfigTemplates();
      this.loadStorageConditionsForForm();
      this.getShelf();
    }, 300);
  }

  ngOnInit() {
    this.get_fg_material();
    this.get_Grade_fg();
    this.getDoseUnits();
      this.initSelectedProduct();
      this.getUnits();
     
      this.dosage_form = 'TABLET';
      //this.getProducts();
      this.getShelf();
      this.getMarketgroup();
      this.getProductgroup();
      this.getShape();
      this.getStyles();
      this.getGst();
      this.getActiveMaterials();
      this.loadStorageConditionsForForm();
      this.loadPackingConfigTemplates();
      this.syncBrandGenericFromProductType();
      this.getClients1();
      this.getMrp();
      this.getpacking_type();
      this.getprimary_packing_type();
      this. getadd_secondary_packing();
      this. getadd_pack_size();
      this. get_capsule_size();
      this. getadd_nos_pouch();
      this. get_add_master_mono_qtys();
      this. get_add_mono_qtys();
      this. get_add_mono_cartains();
      this. get_save_tertiary_packing();

  
    this.getPunchTools();
    this.getChangePart();
        this.getColors();
      this.getMaterials();
      this.getGrades();
      this.getApprovedGenericProductsList();
    // this.service.observableGrade.subscribe(response => {
    //   // this.grades = response;
    //   this.getColors();
    //   this.getMaterials();
    //   this.getGrades();
    //   this.getApprovedGenericProductsList();
    //   this.service.observableDosage.subscribe(response => {
    //     this.dosages = response;
    //   });
    // });

    // this.service.observableStyle.subscribe(response => {
    //   this.styles = response;
    // });

    // this.service.observableShelf.subscribe(response => {
    //   this.shelfs = response;
    // });

    // this.service.observableStorage.subscribe(response => {
    //   this.storages = response;
    // });

  }





  storage_conditions: any[] = [];
  storageConditionsLoading = false;

  loadStorageConditionsForForm(): void {
    this.storageConditionsLoading = true;
    this.service.fetchStorageConditionsList().subscribe({
      next: (list) => {
        const rows = Array.isArray(list) ? list : [];
        this.storage_conditions = rows.filter(
          (r) => !!this.storageRowValue(r) || !!this.storageRowLabel(r)
        );
        this.storages = this.storage_conditions;
        this.storageConditionsLoading = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.storage_conditions = [];
        this.storages = [];
        this.storageConditionsLoading = false;
        this.cdr.detectChanges();
      },
    });
  }

  storageRowValue(row: any): string {
    if (!row) {
      return '';
    }
    return (
      row.storage_condition ||
      row.storage_display_name ||
      row.temperature ||
      (row.id != null ? String(row.id) : '')
    ).toString();
  }

  storageRowLabel(row: any): string {
    if (!row) {
      return '';
    }
    return (
      row.storage_display_name ||
      row.storage_condition ||
      row.temperature ||
      (row.id != null ? `Storage #${row.id}` : '')
    ).toString();
  }

  trackStorageRow(index: number, row: any): string | number {
    if (row && row.id != null) {
      return row.id;
    }
    return this.storageRowValue(row) || index;
  }

  /** @deprecated use loadStorageConditionsForForm */
  getSorageConditions(): void {
    this.loadStorageConditionsForForm();
  }




  initSelectedProduct() {
    this.selected = {
      "id": "78",
      "user_no": "",
      "plant_id": "",
      "product_code": "",
      "product_type": "",
      "product_name": "",
      "generic_name": "",
      "grade": "",
      "packing_style": "",
      "testing_time": "0.00",
      "retest_period": "0.00",
      "hsn": "",
      "gst": "0.00",
      "category": "",
      "pack_desc": "",
      "tsize": "",
      "storage_condition": "",
      "market": "",
      "mrp": "",
      "manufactured_under": "",
      "manufactured_for": "",
      "product_lic": "",
      "fsc": "",
      "copp": "",
      "photo": "",
      "artwork": "",
      "artwork_status": "",
      "manufactured_type": "",
      "approve_by": "",
      "approve_date": null,
      "unit": "Kg",
      "rate": "0.00",
      "similar_name": "",
      "tshape": "",
      "apperance": "",
      "division": "",
      "product_apperance": "",
      "micro": "",
      "type": "",
      "product_nature": "",
      "dosage_type": "",
      "dosage_form": "",
      "packing_mode": "",
      "label_claim": "",
      "shelf_life": "",
      "min_shelf": "",
      "thera": "",
      "gtin": "",
      "mfg_lic": "",
      "plant": "In House",
      "sale_type": "",
      "expiry": "",
      "product_group": "",
      "market_group": "",
      "asin_no": "",
      "nrv_value": "",
      "sku_no": "",
      "packing_charges": "",
      "retain_qty": "",
      "retest": "",
      "min_qty": "",
      "mfg_charges": "",
      "max_qty": "",
      "configuration": "",
      "primary_packing": "",
      "primary_subtype": "",
      "ismono": "yes",
      "primary_qty": "",
      "unit_wt": "",
      "mono_qty": "",
      "outer_qty": "",
      "shipper_qty": "",
      "isouter": "no"
    }
    this.show_view = true;

  }
  getParts(idx) {
    this.change_part_items = [];
    this.change_part_items = this.change_part[idx - 1]['change_parts']
  }

  get_fg_material() {
    this.fg_materialLoading = true;
    this.service.get('master/master.php?type=get_fg_material').subscribe({
      next: (response: unknown) => {
        const list = this.normalizeFgMaterialList(response);
        if (list.length > 0) {
          this.fg_material = list;
          this.fg_materialLoading = false;
          this.cdr.detectChanges();
          return;
        }
        this.loadFgMaterialFromActiveMaterials();
      },
      error: () => this.loadFgMaterialFromActiveMaterials(),
    });
  }

  fg_materialLoading = false;

  private loadFgMaterialFromActiveMaterials(): void {
    this.service.get('master/material.php?type=getActiveMaterials').subscribe({
      next: (response: unknown) => {
        this.fg_material = this.normalizeFgMaterialList(response);
        this.fg_materialLoading = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.fg_material = [];
        this.fg_materialLoading = false;
        this.cdr.detectChanges();
      },
    });
  }

  private normalizeFgMaterialList(response: unknown): any[] {
    if (!Array.isArray(response)) {
      return [];
    }
    return response
      .filter((row) => row != null && typeof row === 'object')
      .map((row) => {
        const copy = { ...row } as Record<string, unknown>;
        let eq = copy['equivalent_to'];
        if (typeof eq === 'string' && eq.trim()) {
          try {
            eq = JSON.parse(eq);
          } catch {
            eq = [];
          }
        }
        if (!Array.isArray(eq)) {
          eq = [];
        }
        copy['equivalent_to'] = eq;
        return copy;
      });
  }

  onLabelMaterialSelectChange(event: Event): void {
    const select = event.target as HTMLSelectElement;
    const name = (select?.value || '').trim();
    if (!name) {
      this.resetLabelMaterialRow();
      return;
    }
    const material = (this.fg_material || []).find(
      (m) => String(m.material_name) === name
    );
    if (material) {
      this.applyLabelMaterialSelection(material);
    }
  }

  /** @deprecated use onLabelMaterialSelected — kept for template compatibility */
  getEquivalent(index: number | string) {
    const selectedIndex = Number(index) - 1;
    if (!Array.isArray(this.fg_material) || selectedIndex < 0 || !this.fg_material[selectedIndex]) {
      this.resetLabelMaterialRow();
      return;
    }
    this.applyLabelMaterialSelection(this.fg_material[selectedIndex]);
  }

  private resetLabelMaterialRow(): void {
    this.selected_material_grade = null;
    this.selectedEquivalent = [];
    this.equivalancy_applicable = 'No';
    this.equivalent_to = '';
    this.equivalancy_factor = 0;
    this.qty = 0;
    this.grade1 = '';
  }

  private applyLabelMaterialSelection(material: any): void {
    this.selected_material_grade = material;
    this.selectedEquivalent = Array.isArray(material.equivalent_to)
      ? material.equivalent_to
      : [];
    this.equivalancy_applicable =
      material.equivalancy_applicable === 'Yes' ? 'Yes' : 'No';
    this.equivalent_to = '';
    this.equivalancy_factor = 0;
    this.qty = 0;

    const gradeName = String(material.gradeName || '').trim();
    if (gradeName) {
      this.grade1 = gradeName;
      this.cdr.detectChanges();
      return;
    }

    const gradeRaw = String(material.grade ?? '').trim();
    if (!gradeRaw) {
      this.grade1 = '';
      this.cdr.detectChanges();
      return;
    }

    // Material master often stores grade name (e.g. USP), not numeric id
    if (!/^\d+(\s*,\s*\d+)*$/.test(gradeRaw)) {
      this.grade1 = gradeRaw;
      this.cdr.detectChanges();
      return;
    }

    this.service
      .getJsonArray(
        'master/master.php?type=get_Grade&grade=' + encodeURIComponent(gradeRaw)
      )
      .subscribe({
        next: (response) => {
          this.get_grades = response;
          this.grade1 =
            Array.isArray(response) && response.length > 0
              ? response.map((g) => g.grade).filter(Boolean).join(', ')
              : gradeRaw;
          this.cdr.detectChanges();
        },
        error: () => {
          this.grade1 = gradeRaw;
          this.cdr.detectChanges();
        },
      });
  }

  equivalentOptionLabel(row: any): string {
    if (!row) {
      return '';
    }
    return (
      row.equivalency ??
      row.equivalent_to ??
      row.name ??
      ''
    ).toString();
  }

  onEquivalentToSelected(indexStr: string): void {
    const idx = Number(indexStr);
    if (Number.isNaN(idx) || !this.selectedEquivalent[idx]) {
      this.equivalancy_factor = 0;
      this.qty = 0;
      return;
    }
    const sele = this.selectedEquivalent[idx];
    this.equivalent_to = this.equivalentOptionLabel(sele);
    this.equivalancy_factor =
      Number(sele.equivalancy_factor ?? sele.factor ?? 0) || 0;
    this.calculation();
  }

   getEquivalentTo(index: number | string) {
     this.onEquivalentToSelected(String(Number(index) - 1));
   }

  get_Grade_fg() {
    this.service.getJsonArray('master/master.php?type=get_Grade_fg').subscribe({
      next: (response) => {
        this.material_grades = Array.isArray(response) ? response : [];
        if (this.material_grades.length === 0) {
          this.loadGradesFallback();
        }
      },
      error: () => this.loadGradesFallback(),
    });
  }

  private loadGradesFallback() {
    this.service.getJsonArray('common.php?type=getGrades').subscribe({
      next: (response) => {
        this.material_grades = Array.isArray(response) ? response : [];
        if (
          this.material_grades.length === 0 &&
          Array.isArray(this.service.grades) &&
          this.service.grades.length > 0
        ) {
          this.material_grades = this.service.grades;
        }
      },
      error: () => {
        this.material_grades = Array.isArray(this.service.grades)
          ? this.service.grades
          : [];
      },
    });
  }
  getActiveMaterials() {
    this.service.get('master/material.php?type=getActiveMaterials').subscribe(response => {
      this.active_materials = response;
    });
  }
  // getFGMaterials(value) {
  //   this.service.observableFGTypes.subscribe(response => {

  //     if (value == "") {
  //       return;
  //     }
  //     let idx = -1;
  //     for (let i = 0; i < response.length; i++) {
  //       if (response[i]['material_subtype'] == value) {
  //         idx = i;
  //       }
  //     }
  //     if (idx >= 0) {
  //       let data = response[idx]
  //       this.fg_sub_materials = data['sub_materials'];
  //       this.checkCopyFrom();
  //     } else {
  //       this.fg_sub_materials = []
  //     }
  //   });
  // }
  onProductTypeChange(): void {
    this.syncBrandGenericFromProductType();
    this.resetCopyFromSelection();
    this.loadCopyFromProducts();
    if (this.Product_type === 'Brand') {
      this.getApprovedGenericProductsList();
    }
  }

  onMarketTypeChange(): void {
    this.resetCopyFromSelection();
    this.loadCopyFromProducts();
  }

  onDosageTypeChange(value: string) {
    this.dosage_type = value || '';
    this.dosage_form = '';
    this.dosageFormOptions = [];
    this.fg_sub_materials = [];
    this.fg_sizes = [];
    this.fg_shapes = [];
    this.fg_sub_types = [];
    this.resetCopyFromSelection();
    if (this.dosage_type) {
      this.getDosageByNature(this.dosage_type);
    } else {
      this.copyFromProducts = [];
    }
  }

  onDosageFormChange(value: string): void {
    this.getSizeAndShape(value);
    this.resetCopyFromSelection();
    this.loadCopyFromProducts();
  }

  private syncBrandGenericFromProductType(): void {
    this.brand_generic = this.Product_type === 'Brand' ? 'Brand' : 'Generic';
  }

  canLoadCopyFromProducts(): boolean {
    return !!(
      this.Product_type &&
      this.type &&
      this.dosage_type &&
      this.dosage_form
    );
  }

  private resetCopyFromSelection(): void {
    this.selectedCopyFromId = '';
    this.copy_from = '';
  }

  loadCopyFromProducts(): void {
    this.syncBrandGenericFromProductType();
    if (!this.canLoadCopyFromProducts()) {
      this.copyFromProducts = [];
      return;
    }
    const url =
      'master/product.php?type=getProducts_copy' +
      '&dosage_type=' + encodeURIComponent(this.dosage_type) +
      '&dosage_form=' + encodeURIComponent(this.dosage_form) +
      '&brand_generic=' + encodeURIComponent(this.brand_generic) +
      '&market_type=' + encodeURIComponent(this.type) +
      '&product_type=' + encodeURIComponent(this.Product_type);
    this.service.get(url).subscribe((response: any) => {
      this.copyFromProducts = Array.isArray(response) ? response : [];
    });
  }

  onCopyFromSelect(productId: string): void {
    if (!productId) {
      this.resetCopyFromSelection();
      return;
    }
    this.service
      .get('master/product.php?type=getProductCopyTemplate&id=' + encodeURIComponent(productId))
      .subscribe((product: any) => {
        if (product && product.id) {
          this.applyCopyFromProduct(product);
        } else {
          alertify.error('Unable to load selected product details');
        }
      });
  }

  private normalizeLabelClaim(raw: any): any[] {
    if (Array.isArray(raw)) {
      return raw.map((row) => ({ ...row }));
    }
    if (typeof raw === 'string' && raw.trim()) {
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed.map((row) => ({ ...row })) : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  applyCopyFromProduct(product: any): void {
    this.copy_from = product.product_name || product.product_code || '';

    this.grade = product.grade || '';
    this.shelf_life = product.shelf_life || '';
    this.storage_condition = product.storage_condition || '';
    this.primary_packing = product.primary_packing || '';
    this.pv_blister = product.pv_blister || '';
    this.Combination = product.Combination || '';
    this.Blister = product.Blister || '';
    this.refreshPrimarySubtypeOptions();
    const copySubtype = this.normalizePrimarySubtype(product.primary_subtype || '');
    if (copySubtype && this.primarySubtypeOptions.indexOf(copySubtype) < 0) {
      this.primarySubtypeOptions = [...this.primarySubtypeOptions, copySubtype];
    }
    this.primary_subtype = copySubtype;
    this.pvc_color = product.pvc_color || '';
    this.ismono = product.ismono || 'yes';
    this.isouter = product.isouter || 'no';
    this.unit = this.normalizeBatchUnit(product.unit);
    this.configuration = product.configuration || '';
    this.packing_configuration_master_id =
      product.packing_configuration_master_id || '';
    this.primary_qty = product.primary_qty || '';
    this.mono_qty = product.mono_qty || '';
    this.outer_qty = product.outer_qty || '';
    this.shipper_qty = product.shipper_qty || '';
    this.each_unit_type = product.each_unit_type || '';
    this.dose_unit_type = product.dose_unit_type || '';
    this.dose_unit_qty = product.dose_unit_qty || '';
    this.dose_unit_qty_unit = product.dose_unit_qty_unit || '';
    this.dose_unit_name_txt = product.dose_unit_name_txt || '';
    this.labels = this.normalizeLabelClaim(product.label_claim);
    this.apperance = product.color_index || product.apperance || '';
    this.color = this.apperance;
    this.packing_style = product.packing_style || '';

    if (this.productForm?.form) {
      this.productForm.form.patchValue({
        pack_unit: product.pack_unit || product.packing_mode || '',
        unit: this.unit,
        storage_condition: product.storage_condition || '',
        Description: product.other_description || product.Description || product.pack_desc || '',
        configuration: product.configuration || '',
        primary_qty: product.primary_qty || '',
        mono_qty: product.mono_qty || '',
        outer_qty: product.outer_qty || '',
        shipper_qty: product.shipper_qty || '',
        pvc_color_name: product.pvc_color_name || '',
        similar_name: product.similar_name || product.genericProductCode || '',
        copy_from: this.copy_from,
        brand_generic: product.brand_generic || this.brand_generic,
      });
    }

    if (this.each_unit_type) {
      this.setEachUnit(this.each_unit_type);
    }
  }

  getCopyFromLabel(product: any): string {
    const name = product?.product_name || product?.generic_name || 'Unnamed';
    const code = product?.product_code ? ` (${product.product_code})` : '';
    return `${name}${code}`;
  }

  getDosageByNature(value) {
    this.service
      .get('master/materialtype.php?type=getDosageByNature&product_nature=' + value)
      .subscribe((response: any) => {
        const rows = Array.isArray(response?.data)
          ? response.data
          : Array.isArray(response)
          ? response
          : [];
        this.fg_sub_materials = rows;
        this.dosageFormOptions = Array.from(
          new Set(
            rows
              .map((r: any) => String(r?.dosage_form || '').trim())
              .filter((x: string) => !!x)
          )
        );
        this.loadCopyFromProducts();
      });
  }


  getSizeAndShape(value) {
    let idx = 0;
    let data = null;
    this.fg_sizes = [];
    this.fg_shapes = [];
    this.fg_sub_types = [];
    for (let i = 0; i < this.fg_sub_materials.length; i++) {
      if (this.fg_sub_materials[i]['dosage_form'] == value) {
        data = this.fg_sub_materials[i];
      }
    }
    if (data != null) {
      this.fg_sizes = JSON.parse(data['dosage_sizes']);
      this.fg_shapes = JSON.parse(data['dosage_shapes']);
      this.fg_sub_types = JSON.parse(data['dosage_sub_form']);
    }
  }
  getColors() {
    this.service.getJsonArray('master/color.php?type=getColors').subscribe({
      next: (rows: any[]) => {
        const list = (rows || [])
          .map((c) => ({
            ...c,
            color: String(c?.color ?? c?.Color ?? '').trim(),
          }))
          .filter((c) => c.color !== '');
        const seen = new Set<string>();
        this.colors = list.filter((c) => {
          const key = c.color.toLowerCase();
          if (seen.has(key)) {
            return false;
          }
          seen.add(key);
          return true;
        });
      },
      error: () => {
        // keep any locally added colors if API fails
        if (!Array.isArray(this.colors)) {
          this.colors = [];
        }
      },
    });
  }

  normalizeBatchUnit(value: any): string {
    const raw = String(value ?? '').trim();
    if (!raw) {
      return '';
    }
    const key = raw.toLowerCase();
    if (key === 'kg' || key === 'kilogram' || key === 'kilograms') {
      return 'Kg';
    }
    if (
      key === 'ltr' ||
      key === 'l' ||
      key === 'lt' ||
      key === 'liter' ||
      key === 'litre' ||
      key === 'liters' ||
      key === 'litres'
    ) {
      return 'Ltr';
    }
    if (raw === 'Kg' || raw === 'Ltr') {
      return raw;
    }
    return '';
  }
  getDoseUnits() {
    this.service.get('master/doseunit.php?type=get_dose_units').subscribe(response => {
      this.doseunits = response;
    });
  }
  getUnits() {
    this.service.get('common.php?type=Getunit').subscribe(response => {
      this.units = response;
    });
  }

  getGrades() {
    this.service.observableGrade.subscribe((response) => {
      this.grades = response;
      if (
        (!this.material_grades || this.material_grades.length === 0) &&
        Array.isArray(response) &&
        response.length > 0
      ) {
        this.material_grades = response;
      }
    });
  }
  selected_material_grade;
  grade1;
  labelMaterialId = '';
  get_grades;
  getGrade(){


      // this.service.get('master/master.php?type=get_Grade&grade='+this.fg_material['grade']).subscribe(response => {
      //   this.get_grades = response;
      // });


  }



  toggleShow() {
    this.isShown = !this.isShown;
  }
  toggleShow1() {
    this.isShown1 = !this.isShown1;
  }

  addArtwork(data) {
    if (!data.valid) {
      alertify.error("All fiels are required");
      return;
    }
    let temp = data.value;
    this.artworkList[this.artworkList.length] = temp;
    data.reset();
  }
  addMocupwork(data) {
    if (!data.valid) {
      alertify.error("All fiels are required");
      return;
    }
    let temp = data.value;
    this.list[this.list.length] = temp;
    data.reset();
  }

  deleteArtwork(index) {
    this.artworkList.splice(index, 1);
  }
  DeleteMocup(index) {
    this.list.splice(index, 1);
  }

  addProductgroup() {
    if (this.product_group == 'Add New') {
      this.isProductgroup = true;
    } else {
      this.isProductgroup = false;
    }
  }

  saveProductgroup(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['mrp_list'] = this.mrp_list;
    temp['selling_price_list'] = this.sale_price_list;
    this.service.post('master/productgroup.php?type=saveProductgroup', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getProductgroup();
        this.isProductgroup = false;
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Please try Again');
      }
    });
  }
  getStorageConditions() {
    this.loadStorageConditionsForForm();
  }
  getProductgroup() {
    this.service.get('master/productgroup.php?type=getProductgroup').subscribe(response => {
      this.productgroups = response;
    })
  }

  getShape() {
    this.service.get('master/shapemaster.php?type=getShape').subscribe(response => {
      this.shapes = response;
    })
  }

  getPunchTools() {
    this.service.get('master/tools.php?type=getTools').subscribe(response => {
      this.punch_tools = response;
    })
  }

  getChangePart() {
    this.change_part_items = [];
    this.service.get('master/blister.php?type=getChangeParts&token').subscribe(response => {
      this.change_part = response;
    })
  }

  addMarketgroup() {
    if (this.market_group == 'Add New') {
      this.isMarketgroup = true;
    } else {
      this.isMarketgroup = false;
    }
  }
  // addCashsales(){
  //   if(this.cash=='Add New'){
  //     this.isCashsales=true;
  //   }else{
  //     this.isCashsales=false;
  //   }
  // }
  saveMarketgroup(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;

    this.service.post('master/marketgroup.php?type=saveMarketgroup', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getMarketgroup();
        this.isMarketgroup = false;
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Please try Again');
      }
    });
  }
  getMarketgroup() {
    this.service.get('master/marketgroup.php?type=getMarketgroup').subscribe(response => {
      this.mastergroups = response;
    })
  }




  getMaterials() {
    this.service.get('common.php?type=getMaterialsByType&material_subtype=API').subscribe(response => {
      this.materials = response;
    });
  }

brand_generic = 'Generic';

  checkCopyFrom() {
    if (this.brand_generic == 'Generic') {
      this.getGeneric();
    } else {
      if (this.copy_from == 'Similar Brand') {
        this.getProducts();
      } else {
        this.getProducts();
        // this.getLabelClaims();
      }
    }
  }



  getProducts() {
    this.service.get('master/product.php?type=getProducts_copy&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade+'&brand_generic='+this.brand_generic).subscribe(response => {
      this.products = response;
    });
  }

  getGeneric() {
    this.service.get('master/product.php?type=getApprovedGenericProducts&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade+ '&category=' + this.category+'&brand_generic='+this.brand_generic).subscribe(response => {
      this.products = response;
    });
  }
  Gen_products: any[] = [];
  getApprovedGenericProductsList() {
    this.service.get('master/product.php?type=getApprovedGenericProductsList').subscribe((response: any) => {
      this.Gen_products = Array.isArray(response) ? response : [];
    });
  }

  getGenericOptionLabel(product: any): string {
    const name = product?.product_name || product?.generic_name || 'Unnamed';
    const code = product?.product_code ? ` (${product.product_code})` : '';
    return `${name}${code}`;
  }

  getLabelClaims() {
    this.service.get('master/product.php?type=getLabelClaims&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade+'&brand_generic='+this.brand_generic).subscribe(response => {
      this.products = response;
    });
  }
  get_gen_name() {
    this.service.get('master/product.php?type=getApprovedGenericProducts&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade+ '&category=' + this.category+'&brand_generic='+this.brand_generic).subscribe(response => {
      this.products = response;
    });
  }
  ismaterial_code(value) {

    this.service.get('master/product.php?type=check_material_code&material_code='+value).subscribe(response => {
      if (response['status'] == 'already exists') {
        alertify.error('Code Already Exists');
      }
    });

  }


  newStorage(value) {
    if (value == 'ADD NEW') {
      this.newStorageConditionDraft = '';
      this.isStorage = true;
    }
  }

  checkColor(value) {
    if (value == 'ADD NEW') {
      this.apperance = '';
      this.color = '';
      this.isColor = true;
    } else {
      this.color = value || '';
    }
  }

  addpackingType(value) {
    if (value == 'Add New') {
      this.packing_type = '';
      this.addpacking = true;
    }
  }
  primary_packing = '';
  primarySubtypeOptions: string[] = [];
  packingConfigHint = '';

  private normalizePrimarySubtype(value: any): string {
    const raw = String(value || '').trim();
    if (!raw) {
      return '';
    }
    const key = raw.toLowerCase().replace(/\s+/g, '').replace(/–|—/g, '-');
    const map: { [k: string]: string } = {
      'alu-alu': 'Alu - Alu',
      'alualu': 'Alu - Alu',
      'alu-pvc': 'Alu - PVC',
      'alupvc': 'Alu - PVC',
      'hdpe': 'HDPE',
      'glass': 'Glass',
      'na': 'NA',
    };
    return map[key] || raw;
  }

  refreshPrimarySubtypeOptions(): void {
    if (this.primary_packing === 'Bottle') {
      this.primarySubtypeOptions = ['HDPE', 'Glass', 'NA'];
    } else if (this.primary_packing === 'Blister' || this.primary_packing === 'Strip') {
      this.primarySubtypeOptions = ['Alu - Alu', 'Alu - PVC', 'NA'];
    } else {
      this.primarySubtypeOptions = [];
    }
  }

  onPrimaryPackingChange(value: string): void {
    this.primary_packing = value || '';
    this.refreshPrimarySubtypeOptions();
    this.primarySubtypeUserSelected = false;
    const current = this.normalizePrimarySubtype(this.primary_subtype);
    this.primary_subtype = this.primarySubtypeOptions.indexOf(current) >= 0 ? current : '';
  }

  onPrimarySubtypeChange(value: string): void {
    this.primarySubtypeUserSelected = true;
    this.primary_subtype = this.normalizePrimarySubtype(value);
  }

  loadPackingConfigTemplates(): void {
    this.packingConfigTemplatesLoading = true;
    // Same fetch path as Packing Configuration Master log (proven working)
    this.service
      .get(
        'master/packing_config_master.php?type=listPackingConfigMaster&includeInactive=1'
      )
      .subscribe({
        next: (res: any) => {
          let list: any[] = [];
          if (Array.isArray(res)) {
            list = res;
          } else if (res && typeof res === 'object') {
            if (Array.isArray(res.data)) {
              list = res.data;
            } else if (Array.isArray(res.results)) {
              list = res.results;
            }
          }
          const active = list.filter(
            (r) =>
              r &&
              (String(r.is_active) === '1' ||
                r.is_active === true ||
                r.is_active == null ||
                r.is_active === '')
          );
          const chosen = active.length > 0 ? active : list;
          this.packingConfigTemplates = chosen
            .filter((r) => r && (r.configuration_title || r.id))
            .map((r) => ({
              id: String(r.id),
              configuration_title: String(r.configuration_title || '').trim(),
              dosage_nature: String(r.dosage_nature || ''),
              combination_title: String(r.combination_title || ''),
              pattern_hint: String(r.pattern_hint || ''),
              is_active: r.is_active,
              config: r.config || null,
              configuration_json: r.configuration_json || null,
            }));
          this.packingConfigTemplatesLoading = false;
          this.cdr.detectChanges();
        },
        error: () => {
          this.packingConfigTemplates = [];
          this.packingConfigTemplatesLoading = false;
          this.cdr.detectChanges();
        },
      });
  }

  packingConfigOptionLabel(tmpl: any): string {
    if (!tmpl) {
      return '';
    }
    const title = String(tmpl.configuration_title || '').trim() || ('Template #' + tmpl.id);
    const nature = String(tmpl.dosage_nature || '').trim();
    return nature ? title + ' — ' + nature : title;
  }

  onPackingConfigTemplateSelect(id: string | number): void {
    const sid = String(id || '').trim();
    if (!sid) {
      this.packing_configuration_master_id = '';
      this.configuration = '';
      this.packingConfigHint = '';
      return;
    }
    this.packing_configuration_master_id = sid;
    const tmpl = (this.packingConfigTemplates || []).find(
      (r) => String(r.id) === sid
    );
    if (tmpl) {
      this.configuration = String(tmpl.configuration_title || '').trim();
    }
    // Always load full JSON from packing-configuration-master
    this.service
      .get(
        'master/packing_config_master.php?type=getPackingConfigMasterById&id=' +
          encodeURIComponent(sid)
      )
      .subscribe({
        next: (row: any) => {
          if (row && row.id) {
            this.applyPackingConfigTemplate(row);
          } else if (tmpl) {
            this.applyPackingConfigTemplate(tmpl);
          }
        },
        error: () => {
          if (tmpl) {
            this.applyPackingConfigTemplate(tmpl);
          }
        },
      });
  }

  applyPackingConfigTemplate(tmpl: any): void {
    if (!tmpl) {
      return;
    }
    this.packing_configuration_master_id = tmpl.id;
    let cfg: any = tmpl.config;
    if (!cfg && typeof tmpl.configuration_json === 'string' && tmpl.configuration_json.trim()) {
      try {
        cfg = JSON.parse(tmpl.configuration_json);
      } catch {
        cfg = {};
      }
    }
    if (!cfg || typeof cfg !== 'object') {
      cfg = {};
    }

    this.configuration =
      String(cfg.configuration || tmpl.configuration_title || '').trim();
    this.packingConfigHint = String(
      cfg.pack_desc || tmpl.pattern_hint || ''
    ).trim();

    if (cfg.primary_packing) {
      this.primary_packing = String(cfg.primary_packing);
      if (
        this.primary_packing &&
        !this.primaryPackingOptions.includes(this.primary_packing)
      ) {
        this.primaryPackingOptions = [
          ...this.primaryPackingOptions,
          this.primary_packing,
        ];
      }
    }
    this.refreshPrimarySubtypeOptions();
    if (!this.primarySubtypeUserSelected && cfg.primary_subtype != null && cfg.primary_subtype !== '') {
      const normalized = this.normalizePrimarySubtype(cfg.primary_subtype);
      if (normalized && this.primarySubtypeOptions.indexOf(normalized) < 0) {
        this.primarySubtypeOptions = [...this.primarySubtypeOptions, normalized];
      }
      this.primary_subtype = normalized;
    }
    if (cfg.ismono) {
      this.ismono = String(cfg.ismono).toLowerCase() === 'yes' ? 'yes' : 'no';
    }
    if (cfg.isouter) {
      this.isouter = String(cfg.isouter).toLowerCase() === 'yes' ? 'yes' : 'no';
    }
    // Do NOT apply packing cfg.unit here — that is packing count unit (Nos/ml),
    // not Batch (Bulk) Unit (Kg/Ltr). Overwriting clears the user's selection.
    if (cfg.primary_qty != null && cfg.primary_qty !== '') {
      this.primary_qty = cfg.primary_qty;
    }
    if (cfg.mono_qty != null && cfg.mono_qty !== '') {
      this.mono_qty = cfg.mono_qty;
    }
    if (cfg.outer_qty != null && cfg.outer_qty !== '') {
      this.outer_qty = cfg.outer_qty;
    }
    if (cfg.shipper_qty != null && cfg.shipper_qty !== '') {
      this.shipper_qty = cfg.shipper_qty;
    }
    if (cfg.Combination != null && cfg.Combination !== '') {
      this.Combination = String(cfg.Combination);
    } else if (tmpl.combination_title) {
      this.Combination = String(tmpl.combination_title);
    }

    if (this.productForm?.form) {
      this.productForm.form.patchValue({
        configuration: this.configuration,
        packing_configuration_master_id: this.packing_configuration_master_id,
        primary_packing: this.primary_packing,
        primary_subtype: this.primary_subtype,
        ismono: this.ismono,
        primary_qty: this.primary_qty,
        mono_qty: this.mono_qty,
        outer_qty: this.outer_qty,
        shipper_qty: this.shipper_qty,
        Combination: this.Combination,
      });
    }
    this.cdr.detectChanges();
  }

  addprimary_packing(value) {
    if (value == 'Add New') {
      this.primary_packing = '';
      this.add_primary_packing = true;
    }
  }
  secondary_packing;
  addsecondary_packing(value) {
    if (value == 'Add New') {
      this.secondary_packing = '';
      this.add_secondary_packing = true;
    }
  }pack_size;
  addpack_size(value) {
    if (value == 'Add New') {
      this.pack_size = '';
      this.add_pack_size = true;
    }
  }no_pouch;
  addno_pouch(value) {
    if (value == 'Add New') {
      this.no_pouch = '';
      this.add_no_pouch = true;
    }
  }capsule_size;
  addcapsule_size(value) {
    if (value == 'Add New') {
      this.capsule_size = '';
      this.add_capsule_size = true;
    }
  }mono_cartain;
  addmono_cartain(value) {
    if (value == 'Add New') {
      this.mono_cartain = '';
      this.add_mono_cartain = true;
    }
  }
  addmono_qty(value) {
    if (value == 'Add New') {
      this.add_mono_qtys = '';
      this.add_mono_qty = true;
    }
  }master_mono_qty;
  addmaster_mono_qty(value) {
    if (value == 'Add New') {
      this.master_mono_qty = '';
      this.add_master_mono_qty = true;
    }
  }tertiary_packing_data
  Add_tertiary_packing(value) {
    if (value == 'Add New') {
      this.tertiary_packing_data = '';
      this.Addtertiary_packing = true;
    }
  }







  checkDoseUnit(value) {
    if (value == 'ADD NEW') {
      this.dose_unit = '';
      this.isDoseUnit = true;
    } else {
      this.dose_unit_type = value;
      this.dose_unit_qty = '';
      this.dose_unit_qty_unit = '';
      this.ech_title = this.dose_unit_type + ' ';
    }
  }

  saveStorage() {
    const draft = (this.newStorageConditionDraft || '').trim();
    if (draft.length === 0) {
      return;
    }
    this.service
      .get(
        'qa/master.php?type=saveStorageCondition&storage_condition=' +
          encodeURIComponent(draft)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.loadStorageConditionsForForm();
          this.isStorage = false;
          this.newStorageConditionDraft = '';
          this.storage_condition = draft;
          alertify.success('Storage Condition saved successfully');
        } else {
          alertify.error('Failed: An error occured');
        }
      });
  }


  getDetails(index, data) {
    index = index - 1;
    // if (index !== -1) {
    //   let product =this.products[index];
    //   for (let key in data.value) {
    //       data.value[key]= product[key];
    //   }

      this.selected = this.Gen_products[index];
      this.labels=this.selected['label_claim'];
      this.shelf_life = this.selected['shelf_life'];
      this.packing_style = this.selected['packing_style'];
      this.storage_condition = this.selected['storage_condition'];
      this.color = this.selected['apperance'];
     // }
  }

 
  getOuter(show) {
    if (show == 'yes') {
      this.isyesouter = true;
      this.isNoouter = false;
    } else if (show == 'no') {
      this.isyesouter = false;
      this.isNoouter = true;
    }
  }


  getClients(value) {
    if (value !== 'Own') {
      this.service.get('common.php?type=getClients').subscribe(response => {
        this.clients = response;
      });
    }
  }

  getClients1() {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients1 = response;
    });
  }


  onFileChanged1(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile1 = event.target.files[0];
    }
  }

  onFileChanged2(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile2 = event.target.files[0];
    }
  }
  getGst() {
    this.service.observableGst.subscribe(response => {
      this.gstList = response;
    });

  }
  onFileChanged3(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile3 = event.target.files[0];
    }
  }

  onFileChanged4(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile4 = event.target.files[0];
    }
  }
  onFileChanged5(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile5 = event.target.files[0];
    }
  }
  onFileChanged6(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile6 = event.target.files[0];
    }
  }

  save(data) {
    let temp = data.value;
    console.log(temp);


    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    uploadData.append("product_type", "Formulation");
    uploadData.append("generic_name", this.selected['product_name']);
    uploadData.append("genericProductCode", this.selected['genericProductCode']);
    uploadData.append(
      'packing_configuration_master_id',
      String(this.packing_configuration_master_id || '')
    );
    uploadData.append('configuration', this.configuration || '');
    uploadData.append('unit', this.normalizeBatchUnit(this.unit) || this.unit || temp['unit'] || '');
    if (this.selectedFile1 !== undefined) {
      uploadData.append('product_lic', this.selectedFile1, this.selectedFile1.name);
    }

    // if (this.selectedFile2 !== undefined) {
    //   uploadData.append('fsc', this.selectedFile2, this.selectedFile2.name);
    // }

    // if (this.selectedFile3 !== undefined) {
    //   uploadData.append('copp', this.selectedFile3, this.selectedFile3.name);
    // }
    if (this.selectedFile4 !== undefined) {
      uploadData.append('photo', this.selectedFile4, this.selectedFile4.name);
    }
    if (this.selectedFile5 !== undefined) {
      uploadData.append('artwork_file', this.selectedFile5, this.selectedFile5.name);
    }
    if (this.selectedFile6 !== undefined) {
      uploadData.append('mopcup_file', this.selectedFile6, this.selectedFile6.name);
    }
    uploadData.append('artworkList', JSON.stringify(this.artworkList));
    uploadData.append('list', JSON.stringify(this.list));

    //  if (this.dosage_form == 'TABLET' || this.dosage_form == 'CAPSULE') {
    let label_claim = '';
    for (let i = 0; i < this.labels.length; i++) {
      let label = this.labels[i];
      if (label.equivalent_to !== '') {
        label_claim = label['material_name'] + ' ' + label['grade'] + ' ' + label['strength'] + ' ' + label['unit'] + '\n';
      }
      if (label.equivalent_to == '') {
        label_claim = label['material_name'] + ' ' + label['grade'] + '' + label['strength'] + ' ' + label['unit'] + '\n';
      }
    }
    this.color = this.apperance || this.color || '';
    label_claim += 'Excipient QS\nColor ' + this.color;
    uploadData.append('apperance', this.apperance || '');
    uploadData.append('color_index', this.apperance || this.color || '');
    uploadData.append('dose_unit_type', this.dose_unit_type);
    uploadData.append('dose_unit_qty', this.dose_unit_qty);
    uploadData.append('dose_unit_qty_unit', this.dose_unit_qty_unit);
    uploadData.append('label_claim', JSON.stringify(this.labels));
    // }

    this.service.post('master/product.php?type=saveBrandProductZuma', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Product saved successfully');
        data.resetForm();
        // this.router.navigate(['/master/product/brand'])
        this.router.navigate(['/master/product/new-formulation-list']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  save_Saipro(data) {
    let temp = data.value;
    console.log(temp);

    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    uploadData.append("product_type", "Formulation");
    uploadData.append("product_type", "Formulation");
    if (this.selectedFile1 !== undefined) {
      uploadData.append('product_lic', this.selectedFile1, this.selectedFile1.name);
    }

    if (this.selectedFile2 !== undefined) {
      uploadData.append('fsc', this.selectedFile2, this.selectedFile2.name);
    }

    if (this.selectedFile3 !== undefined) {
      uploadData.append('copp', this.selectedFile3, this.selectedFile3.name);
    }
    if (this.selectedFile4 !== undefined) {
      uploadData.append('photo', this.selectedFile4, this.selectedFile4.name);
    }
    if (this.selectedFile5 !== undefined) {
      uploadData.append('artwork_file', this.selectedFile5, this.selectedFile5.name);
    }
    if (this.selectedFile6 !== undefined) {
      uploadData.append('mopcup_file', this.selectedFile6, this.selectedFile6.name);
    }
    uploadData.append('artworkList', JSON.stringify(this.artworkList));
    uploadData.append('list', JSON.stringify(this.list));

    //  if (this.dosage_form == 'TABLET' || this.dosage_form == 'CAPSULE') {
    let label_claim = '';
    for (let i = 0; i < this.labels.length; i++) {
      let label = this.labels[i];
      if (label.equivalent_to !== '') {
        label_claim = label['material_name'] + ' ' + label['grade'] + ' ' + label['strength'] + ' ' + label['unit'] + '\n';
      }
      if (label.equivalent_to == '') {
        label_claim = label['material_name'] + ' ' + label['grade'] + '' + label['strength'] + ' ' + label['unit'] + '\n';
      }
    }
    label_claim += 'Excipient QS\nColor ' + this.color;
    uploadData.append('dose_unit_type', this.dose_unit_type);
    uploadData.append('dose_unit_qty', this.dose_unit_qty);
    uploadData.append('dose_unit_qty_unit', this.dose_unit_qty_unit);
    uploadData.append('label_claim', JSON.stringify(this.labels));
    // }

    this.service.post('master/product.php?type=saveBrandProduct_saipro', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Product saved successfully');
        data.resetForm();
        // this.router.navigate(['/master/product/brand'])
        this.router.navigate(['/master/product/new-formulation-list']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  checkStyle(value) {
    if (value == 'ADD NEW') {
      this.isStyle = true;
    } else {
      this.isStyle = false;
    }
  }

  saveStyle() {
    if (this.style.length !== 0) {
      this.service.get('qa/master.php?type=saveStyle&style=' + this.style).subscribe(response => {
        if (response['status'] == 'success') {
          alertify.success('packing Style Saved Successfully!');
          this.getStyles();
          this.isStyle = false;
          this.style = '';
          this.packing_style = '';
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
    }
  }

  close(value) {
    if (value == 'style') {
      this.isStyle = false;
    }
  }

  getStyles() {
    this.service.get('qa/master.php?type=getStyles').subscribe((response: any) => {
      this.styles = response;
    });
  }

  saveShelf() {
    this.shelf_life = this.shelf;
    if (this.shelf.length !== 0) {
      this.service.get('qa/master.php?type=saveShelf&shelf=' + this.shelf).subscribe(response => {
        if (response['status'] == 'success') {
          this.getShelf();
          this.isShelf = false;
          this.shelf = '';
          this.shelf_life = '';
          alertify.success('Shelf life saved successfully');
        } else {
          alertify.error('Failed: An error occured');
        }
      });
    }
  }

  newShelf(value) {
    if (value == 'ADD NEW') {
      this.shelf_life = '';
      this.isShelf = true;
    }
  }

  getShelf() {
    this.service.get('qa/master.php?type=getShelfs').subscribe({
      next: (response: any) => {
        const list = Array.isArray(response) ? response : [];
        const seen = new Set<string>();
        this.shelfs = list
          .map((row) => {
            const shelf = String(row?.shelf ?? row?.shelf_life ?? '').trim();
            return shelf ? { ...row, shelf } : null;
          })
          .filter((row) => {
            if (!row) {
              return false;
            }
            const key = row.shelf.toLowerCase();
            if (seen.has(key)) {
              return false;
            }
            seen.add(key);
            return true;
          });
        this.cdr.detectChanges();
      },
      error: () => {
        this.shelfs = [];
        this.cdr.detectChanges();
      },
    });
  }
  saveDoseUnit() {
    if (this.dose_unit.length !== 0) {
      this.service.get('master/doseunit.php?type=saveDoseUnit&dose_unit=' + this.dose_unit).subscribe(response => {
        if (response['status'] == 'success') {
          this.getDoseUnits();
          this.isDoseUnit = false;
          this.dose_unit = '';
          alertify.success('Dose Unit aved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
    } else {
      alertify.error('Enter Dose Unit');
    }
  }
  saveColor() {
    const newColor = (this.product_color || '').trim();
    this.color = newColor;

    if (newColor.length !== 0) {
      this.service.get('master/color.php?type=saveColor&color=' + encodeURIComponent(newColor)).subscribe((response: any) => {
        if (response && response['status'] == 'success') {
          this.apperance = newColor;
          this.color = newColor;
          if (!Array.isArray(this.colors)) {
            this.colors = [];
          }
          if (!this.colors.some((c) => String(c?.color || '').toLowerCase() === newColor.toLowerCase())) {
            this.colors = [...this.colors, { color: newColor }];
          }
          this.isColor = false;
          this.product_color = '';
          alertify.success('Product Color saved successfully');
          setTimeout(() => this.getColors(), 200);
        } else {
          alertify.error((response && response['status']) || 'Failed to save color');
        }
      });
    } else {
      alertify.error('Enter Color');
    }
  }
  packing_type;
  savepacking_type() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=savePacking&pack_type=' + this.packing_type).subscribe(response => {
        if (response['status'] == 'success') {
          this. getpacking_type();
          this.addpacking = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  getpacking_types;
  getpacking_type() {
    this.service.get('master/color.php?type=getpacking_type').subscribe(response => {
      this.getpacking_types = response;
    });
  }
  primary_packing_type;
  saveprimary_packing_type() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=saveprimary_packing_type&primary_packing_type=' + this.primary_packing_type).subscribe(response => {
        if (response['status'] == 'success') {
          this. getprimary_packing_type();
          this.add_primary_packing = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  getprimary_packing_types;
  getprimary_packing_type() {
    this.service.get('master/color.php?type=getprimary_packing_type').subscribe(response => {
      this.getprimary_packing_types = response;
    });
  }
  add_sec_packing;
  saveadd_sec_packing() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=save_secondary_packings&save_secondary_packings=' + this.add_sec_packing).subscribe(response => {
        if (response['status'] == 'success') {
          this. getadd_secondary_packing();
          this.add_secondary_packing = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  getadd_secondary_packings;
  getadd_secondary_packing() {
    this.service.get('master/color.php?type=getsecondary_packings').subscribe(response => {
      this.getadd_secondary_packings = response;
    });
  }
  add_packs_size;
  saveadd_packs_size() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=save_pack_size&add_pack_size=' + this.add_packs_size).subscribe(response => {
        if (response['status'] == 'success') {
          this. getadd_pack_size();
          this.add_pack_size = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  getadd_packs_sizes;
  getadd_pack_size() {
    this.service.get('master/color.php?type=getpack_size').subscribe(response => {
      this.getadd_packs_sizes = response;
    });
  }



  add_nos_pouch;
  saveadd_nos_pouch() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=saveadd_nos_pouch&add_nos_pouch=' + this.add_nos_pouch).subscribe(response => {
        if (response['status'] == 'success') {
          this. getadd_nos_pouch();
          this.add_no_pouch = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  getadd_nos_pouchs;
  getadd_nos_pouch() {
    this.service.get('master/color.php?type=getnos_pouch').subscribe(response => {
      this.getadd_nos_pouchs = response;
    });
  }





  save_capsule_sizess;
  add_capsule_sizes() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=add_capsule_size&add_capsule_size=' + this.save_capsule_sizess).subscribe(response => {
        if (response['status'] == 'success') {
          this. get_capsule_size();
          this.add_capsule_size = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  get_capsule_sizes;
  get_capsule_size() {
    this.service.get('master/color.php?type=get_capsule_size').subscribe(response => {
      this.get_capsule_sizes = response;
    });
  }



  add_mono_cartains;
  saveadd_mono_cartains() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=add_mono_cartains&add_mono_cartains=' + this.add_mono_cartains).subscribe(response => {
        if (response['status'] == 'success') {
          this.get_add_mono_cartains();
          this.add_mono_cartain = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  mono_cartains;
  get_add_mono_cartains() {
    this.service.get('master/color.php?type=get_add_mono_cartains').subscribe(response => {
      this.mono_cartains = response;
    });
  }






  add_mono_qtys;
  saveadd_mono_qty() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=saveadd_mono_qty&add_mono_qtys=' + this.add_mono_qtys).subscribe(response => {
        if (response['status'] == 'success') {
          this.get_add_mono_qtys();
          this.add_mono_qty = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  mono_qtys;
  get_add_mono_qtys() {
    this.service.get('master/color.php?type=get_add_mono_qtys').subscribe(response => {
      this.mono_qtys = response;
    });
  }




  add_master_mono_qtys;
  saveadd_master_mono_qty() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=add_master_mono_qtys&add_master_mono_qtys=' + this.add_master_mono_qtys).subscribe(response => {
        if (response['status'] == 'success') {
          this.get_add_master_mono_qtys();
          this.add_master_mono_qty = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  master_mono_qtyss;
  get_add_master_mono_qtys() {
    this.service.get('master/color.php?type=get_add_master_mono_qtys').subscribe(response => {
      this.master_mono_qtyss = response;
    });
  }




  save_tertiary_packing;
  add_tertiary_packings() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=save_tertiary_packing&save_tertiary_packing=' + this.save_tertiary_packing).subscribe(response => {
        if (response['status'] == 'success') {
          this.get_save_tertiary_packing();
          this.Addtertiary_packing = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  get_tertiary_packing;
  get_save_tertiary_packing() {
    this.service.get('master/color.php?type=get_save_tertiary_packing').subscribe(response => {
      this.get_tertiary_packing = response;
    });
  }





















  addLabel(data,data1) {
    if (!this.selected_material_grade) {
      alertify.error('Please select material name');
      return;
    }
    if (!data.valid) {
      alert('All fields are required!');
      return;
    }
    if (!data1.valid) {
      alert('All fields are required!');
      return;
    }

    if (data.value['equivalent_to'] != '' && data.value['strength'] == '') {
      alert('Plase enter strength!');
      return;
    }

    data.value['id'] = this.selected_material_grade['id']
    data.value['material_code'] = this.selected_material_grade['material_code'];
    data.value['grade'] = this.grade1;
    data.value['gradeName'] = this.grade1;
    data.value['dose_unit_qty_unit'] = this.dose_unit_qty_unit;
    data.value['dose_unit_qty'] = this.dose_unit_qty;
    data.value['dose_unit_name_txt'] = this.dose_unit_name_txt;
    data.value['dose_unit_type'] = this.dose_unit_type;
    data.value['each_unit_type'] = this.each_unit_type;
    data.value['apperance'] = this.apperance;
    if (data.value['label_unit'] != null && data.value['label_unit'] !== '') {
      data.value['unit'] = data.value['label_unit'];
    }
    this.labels[this.labels.length] = { ...data.value };
    console.log(this.labels)
    data.resetForm();
    console.log(this.labels);
  }

  delLabel(index) {
    this.labels.splice(index, 1);
  }


  saveMrp(data) {
    this.service.post('master/mrp.php?type=saveMrp', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        data.reset();
        this.isMrp = false;
        alertify.success(' saved successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getMrp() {
    this.service.get('master/mrp.php?type=getMrp').subscribe((response: any) => {
      this.mrps = response;
    });
  }

  calculation() {
    const factor = Number(this.equivalancy_factor) || 0;
    const strength = Number(this.strength) || 0;
    this.qty = parseFloat((factor * strength).toFixed(2));


  }
  addMrp(data) {
    if (!data.valid) {
      alert("All fiels are required");
      return;
    }
    this.mrp_list[this.mrp_list.length] = data.value;
    data.resetForm();
  }
  addSellingPrices(data) {
    if (!data.valid) {
      alert("All fiels are required");
      return;
    }
    this.sale_price_list[this.sale_price_list.length] = data.value;
    data.resetForm();
  }
  setEachUnit(each_unit_type) {
    if (each_unit_type == 'Qty') {
      this.dose_unit_type = '';
      this.ech_title = this.dose_unit_qty + '' + this.dose_unit_qty_unit;
    } else {
      if (this.dose_unit_type == 'Others') {
        this.dose_unit_type = this.dose_unit_name_txt;
      }
      this.dose_unit_qty = '';
      this.dose_unit_qty_unit = '';
      this.ech_title = this.dose_unit_type + ' ';
    }
  }

}
