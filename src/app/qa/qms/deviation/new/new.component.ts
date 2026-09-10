import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

/** dd/mm/yyyy regex for validation */
const DATE_DD_MM_YYYY = /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  department_name = localStorage.getItem('department');
  employees: any;
  departments: any;
  materials: any[] = [];
  devScope: string = '';
  RelatedTo: string = '';
  Other: string = '';
  prefillData: any = {
    sourceDocument: '',
    scopeItem: '',
    ScopeCode: '',
    detailsOfDev: '',
    reasonForDeviation: '',
    briefInvestigation: '',
  };

  /** Date strings in dd/mm/yyyy for binding (display/input) – legacy */
  devIdentifiedDateDdMmYyyy = '';

  /** Deviation Occurred Date: native date picker (yyyy-mm-dd), max = today */
  devOccuredDateIso = '';

  /** Deviation Identified Date: native date picker (yyyy-mm-dd), max = today */
  devIdentifiedDateIso = '';

  /** Max date for Occurred and Identified date pickers (today) – blocks future dates */
  get maxOccuredDate(): string {
    const t = new Date();
    const y = t.getFullYear();
    const m = String(t.getMonth() + 1).padStart(2, '0');
    const d = String(t.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }
  get maxIdentifiedDate(): string {
    return this.maxOccuredDate;
  }

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  /** Convert dd/mm/yyyy to yyyy-mm-dd for API */
  static ddMmYyyyToIso(ddMmYyyy: string): string {
    if (!ddMmYyyy || !DATE_DD_MM_YYYY.test(ddMmYyyy.trim())) return '';
    const [d, m, y] = ddMmYyyy.trim().split('/');
    return `${y}-${m}-${d}`;
  }

  /** Validate dd/mm/yyyy string */
  static isValidDdMmYyyy(value: string): boolean {
    if (!value || typeof value !== 'string') return false;
    return DATE_DD_MM_YYYY.test(value.trim());
  }

  /** For template: validate date string dd/mm/yyyy */
  isValidDate(value: string): boolean {
    return NewComponent.isValidDdMmYyyy(value);
  }

  ngOnInit() {
    this.getDepartments();
    this.getEmployees();
    this.getEquipments();
    this.getProducts();
    this.getInitiatByData();
    // Load materials after a short delay to ensure other data is loaded first
    setTimeout(() => {
      this.getMaterialsByTypes();
    }, 500);
    this.applyPrefillFromQuery();
  }

  private applyPrefillFromQuery(): void {
    this.route.queryParams.subscribe((params) => {
      if (!params || String(params['prefill'] || '') !== '1') {
        return;
      }
      this.devScope = params['devScope'] || this.devScope;
      this.prefillData.scopeItem = params['scopeItem'] || this.prefillData.scopeItem;
      this.prefillData.ScopeCode = params['scopeCode'] || this.prefillData.ScopeCode;
      this.prefillData.sourceDocument = params['sourceDocument'] || this.prefillData.sourceDocument;
      this.prefillData.detailsOfDev = params['detailsOfDev'] || this.prefillData.detailsOfDev;
      this.prefillData.reasonForDeviation =
        params['reasonForDeviation'] || this.prefillData.reasonForDeviation;
      this.prefillData.briefInvestigation =
        params['briefInvestigation'] || this.prefillData.briefInvestigation;
      this.RelatedTo = params['relatedTo'] || 'Document';

      if (!this.devOccuredDateIso) {
        this.devOccuredDateIso = this.maxOccuredDate;
      }
      if (!this.devIdentifiedDateIso) {
        this.devIdentifiedDateIso = this.maxIdentifiedDate;
      }
    });
  }

  devDetDoc: File;
  standProceSysDoc: File;
  
  onFileChanged(event: any) {
    if (event.target.files.length === 1) {
      this.devDetDoc = event.target.files[0];
    }
  }
  
  onFileChanged1(event: any) {
    if (event.target.files.length === 1) {
      this.standProceSysDoc = event.target.files[0];
    }
  }

  getDepartments() {
    return new Promise((res, rej) => {
      this.service
        .get('hr/employee.php?type=get_department_by_designationMeha')
        .subscribe((response: any) => {
          this.departments = response;
          res(response);
        });
    });
  }

  getEmployees() {
    this.service
      .get(
        'hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' +
          localStorage.getItem('department')
      )
      .subscribe((response: any) => {
        this.employees = response;
      });
  }

  getMaterialsByTypes() {
    // Get plant_id from localStorage (required parameter)
    const plant_id = localStorage.getItem('plant_id') || '1'; // Default to '1' if not set
    
    // First, try to get all material types, then fetch materials for each type
    // Or try alternative endpoints
    this.service.get('common.php?type=getMaterialTypes').subscribe(
      (materialTypes: any) => {
        if (materialTypes && materialTypes.length > 0) {
          // If we have material types, fetch materials for the first type or all types
          const allMaterials: any[] = [];
          let completed = 0;
          
          materialTypes.forEach((type: any, index: number) => {
            this.service.get(`common.php?type=getMaterialsByTypes&plant_id=${plant_id}&material_type=${type.material_type || type.type || type.name}`).subscribe(
              (response: any) => {
                if (response && response.length > 0) {
                  allMaterials.push(...response);
                }
                completed++;
                if (completed === materialTypes.length) {
                  this.materials = allMaterials;
                  console.log('Materials loaded:', this.materials);
                }
              },
              (error) => {
                completed++;
                if (completed === materialTypes.length) {
                  this.materials = allMaterials;
                  if (allMaterials.length === 0) {
                    this.tryAlternativeMaterialEndpoints();
                  }
                }
              }
            );
          });
        } else {
          // No material types found, try alternative endpoints
          this.tryAlternativeMaterialEndpoints();
        }
      },
      (error) => {
        // If getMaterialTypes fails, try alternative endpoints
        this.tryAlternativeMaterialEndpoints();
      }
    );
  }

  tryAlternativeMaterialEndpoints() {
    const plant_id = localStorage.getItem('plant_id') || '1';
    
    // Try common material types as fallback
    const commonMaterialTypes = ['Raw Material', 'Packaging Material', 'Material', 'RM', 'PM'];
    let typeIndex = 0;
    
    const tryNextMaterialType = () => {
      if (typeIndex < commonMaterialTypes.length) {
        this.service.get(`common.php?type=getMaterialsByTypes&plant_id=${plant_id}&material_type=${commonMaterialTypes[typeIndex]}`).subscribe(
          (response: any) => {
            if (response && Array.isArray(response) && response.length > 0) {
              this.materials = response;
              console.log(`Materials loaded with type ${commonMaterialTypes[typeIndex]}:`, this.materials.length);
            } else {
              typeIndex++;
              tryNextMaterialType();
            }
          },
          (error) => {
            typeIndex++;
            tryNextMaterialType();
          }
        );
      } else {
        // If all common types fail, try alternative endpoints
        this.service.get(`common.php?type=getMaterials&plant_id=${plant_id}`).subscribe(
          (response: any) => {
            if (response && Array.isArray(response) && response.length > 0) {
              this.materials = response;
              console.log('Materials loaded from getMaterials:', this.materials.length);
            } else {
              this.service.get('common.php?type=getAllMaterials').subscribe((response: any) => {
                this.materials = response || [];
                console.log('Materials loaded from getAllMaterials:', this.materials.length);
              }, (error) => {
                console.error('All material endpoints failed:', error);
                alertify.warning('Could not load materials. Please ensure plant_id and material_type are set correctly.');
              });
            }
          },
          (error) => {
            console.error('Error fetching materials:', error);
            this.service.get('common.php?type=getAllMaterials').subscribe((response: any) => {
              this.materials = response || [];
            }, (err) => {
              console.error('Failed to load materials:', err);
            });
          }
        );
      }
    };
    
    tryNextMaterialType();
  }

  getProducts() {
    this.service.get('common.php?type=devGetProduct').subscribe((response: any) => {
      this.products = response || [];
    });
  }

  getEquipments() {
    this.service
      .get('common.php?type=get_Equipments&depart=' + localStorage.getItem('department'))
      .subscribe((response: any) => {
        this.equipments = response || [];
      });
  }

  getInitiatByData() {
    this.service.get('common.php?type=getInitiatByData').subscribe((response: any) => {
      this.identifiedBy = response['identifiedBy'] || '';
    });
  }
  
  identifiedBy = '';
  equipments: any[] = [];
  products: any[] = [];
  devScope1 = '';

  View(url: string) {
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
  }

  saveDeviation(data: any) {
    const form = data && data.form ? data.form : data;
    if (form && form.markAllAsTouched) form.markAllAsTouched();
    const v = (form && form.value) ? form.value : (data && data.value) ? data.value : {};

    const missing: string[] = [];
    // Use component-bound value first (date picker updates devOccuredDateIso directly)
    const occuredDate = (this.devOccuredDateIso || v['devOccuredDateIso'] || '').trim();
    if (!occuredDate) missing.push('Deviation Occurred Date');
    else if (occuredDate > this.maxOccuredDate) missing.push('Deviation Occurred Date (cannot be a future date)');
    const identifiedDate = (this.devIdentifiedDateIso || v['devIdentifiedDateIso'] || '').trim();
    if (!identifiedDate) missing.push('Deviation Identified Date');
    else if (identifiedDate > this.maxIdentifiedDate) missing.push('Deviation Identified Date (cannot be a future date)');
    if (!v['timeOfDev']?.trim()) missing.push('Time Of Deviation');
    if (!v['detailsOfDev']?.trim()) missing.push('Description Of Deviation');
    if (!v['sourceDocument']?.trim()) missing.push('Source Document');
    if (!v['devScope']?.trim()) missing.push('Product/Material/Equipment Name');
    if (v['devScope'] && !v['scopeItem']?.trim()) missing.push('Scope selection (Material/Product/Equipment)');
    if (!v['ScopeCode']?.trim()) missing.push('Lot No./Medicap Lot No/Equipment ID');
    if (!v['relatedTo']?.trim()) missing.push('Related To');
    if (this.RelatedTo === 'Other' && !this.Other?.trim()) missing.push('Related To (Other)');
    if (!v['DeviationType']?.trim()) missing.push('Deviation Type');
    if (!v['reasonForDeviation']?.trim()) missing.push('Reason For Deviation');
    if (!v['briefInvestigation']?.trim()) missing.push('Brief Investigation');

    if (missing.length > 0) {
      alertify.error('Please fill mandatory fields: ' + missing.join(', '));
      return;
    }

    const temp = { ...v };
    temp['devOccuredDate'] = occuredDate;
    temp['devIdentifiedDate'] = identifiedDate;
    temp['department'] = localStorage.getItem('department');

    const formData = new FormData();
    for (const key of Object.keys(temp)) {
      if (temp[key] != null && temp[key] !== undefined && key !== 'devOccuredDateDdMmYyyy' && key !== 'devIdentifiedDateDdMmYyyy' && key !== 'devIdentifiedDateIso' && key !== 'devOccuredDateIso') {
        formData.append(key, temp[key]);
      }
    }
    formData.set('devOccuredDate', occuredDate);
    formData.set('devIdentifiedDate', identifiedDate);

    if (temp['devScope'] === 'Other') {
      formData.append('devScope', this.devScope1);
    }
    if (this.devDetDoc) {
      formData.append('devDetDoc', this.devDetDoc, this.devDetDoc.name);
    }
    if (this.standProceSysDoc) {
      formData.append('standProceSysDoc', this.standProceSysDoc, this.standProceSysDoc.name);
    }
    if (this.RelatedTo === 'Other') {
      formData.append('relatedTo', this.Other);
    }

    this.service
      .post('deviation2.php?type=saveQmsDeviationsMeha', formData)
      .subscribe((response: any) => {
        if (response['status'] === 'success') {
          this.router.navigate(['/qa/qms/deviation']);
          alertify.success('Deviation Initiated Successfully. Proceed...');
          data.resetForm();
          this.devOccuredDateIso = '';
          this.devIdentifiedDateIso = '';
        } else {
          alertify.error('Failed: An error occurred, please try again!');
        }
      }, () => {
        alertify.error('Error saving deviation');
      });
  }
}

