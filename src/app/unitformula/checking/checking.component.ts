import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  results;
  packingList;
  raw_materials;
  plant_id: any;
  plant_type: any;
  packing_List_All = [];
  selectedResult: any = {};
  rawMaterialsList: any[] = [];
  packingConfigurations: any[] = [];
  packagingMaterialsList: any[] = [];
  revisionHistory: any[] = [];
  versionControl: any = {};

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingUnitFormulas();
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.get_rights();
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;

  get rawMaterialsColspan(): number {
    let cols = 8; // sr, type, code, name, qty, unit, total, yield
    if (this.plant_id !== '59' && this.plant_id !== '58') {
      cols += 2; // overages %, percent qty
    }
    if (this.plant_type === 'Formulation') {
      cols += 2; // assay fields
    }
    cols += 1; // LOD
    return cols;
  }

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id')).subscribe(response => {
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

  getPendingUnitFormulas() {
    this.service.get('production/unitformula.php?type=getUnitFormulasforCheckingzUMA').subscribe(response => {
      this.results = response;
    });
  }

  private asArray(value: any): any[] {
    if (Array.isArray(value)) {
      return value;
    }
    if (typeof value === 'string' && value.trim() !== '') {
      try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  view(index) {
    this.selectedResult = this.results[index] || {};
    this.rawMaterialsList = this.asArray(this.selectedResult['raw_materials']);
    this.packingConfigurations = this.asArray(this.selectedResult['packing_configuration']).filter(
      (cfg) => Array.isArray(cfg?.packing_materials) && cfg.packing_materials.length > 0
    );
    this.packagingMaterialsList = this.asArray(this.selectedResult['primary_pm_list']);
    this.raw_materials = this.rawMaterialsList;
    this.revisionHistory = this.asArray(this.selectedResult['revison_history'] || this.selectedResult['revisionList']);
    this.versionControl = this.buildVersionControl(this.selectedResult, this.revisionHistory);
    this.isView = true;
  }

  private buildVersionControl(result: any, history: any[]): any {
    const last = history && history.length ? history[history.length - 1] : {};
    return {
      supersede_no: result?.['supersede_no'] || last?.['supersede_no'] || '',
      supersede_ver_no: result?.['supersede_ver_no'] || last?.['supersede_ver_no'] || last?.['ver_no'] || '',
      review_date: result?.['review_date'] || last?.['review_date'] || last?.['effective_date'] || '',
    };
  }

  displayValue(value: any): string {
    if (value === null || value === undefined || String(value).trim() === '') {
      return 'NA';
    }
    return String(value);
  }

  displayPercent(value: any): string {
    if (value === null || value === undefined || String(value).trim() === '') {
      return 'NA';
    }
    const num = Number(value);
    if (isNaN(num)) {
      return String(value);
    }
    return num.toFixed(2);
  }

  displayYieldQty(savedQty: any, percent: any): string {
    if (savedQty !== null && savedQty !== undefined && String(savedQty).trim() !== '') {
      return String(savedQty);
    }
    const batch = Number(this.selectedResult?.['batch_size']);
    const per = Number(percent);
    if (!isNaN(batch) && !isNaN(per) && batch > 0) {
      return String((batch * per) / 100);
    }
    return 'NA';
  }

  displayPackQty(qty: any, unit: any): string {
    if (qty === null || qty === undefined || String(qty).trim() === '') {
      return 'NA';
    }
    const unitText = unit ? ' ' + unit : '';
    return String(qty) + unitText;
  }

  approveUnitFormula(status) {
    this.service.get('production/unitformula.php?type=checkingUnitFormula&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Data Updated Successfully!');
        this.isView = false;
        this.getPendingUnitFormulas();
      } else {
        alert('An Error Occured, Please try again!');
      }
    });
  }

}
