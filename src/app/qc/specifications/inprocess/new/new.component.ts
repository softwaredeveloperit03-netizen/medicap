import { Component, OnInit, Output , EventEmitter} from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  
  @Output() newSpec: EventEmitter<any> = new EventEmitter<any>();
  constructor(private service: DataAccessService, private route: ActivatedRoute,private router:Router) { }

  ngOnInit() {
    this.getUnits();
    this.getProducts();
    this.getTestTypes();
  }

  testType: any[] = [];
  spectests: any[] = [];
  spectTests: any[] = [];
  loading = false;
  today = new Date().toISOString().split('T')[0];
  specification: any = '';
  product_code = '';
  limit_type = '';
  limit = '';
  tms = '';
  selectedTest: any = null;
  isStorage = false;
  storage_condition = '';
  material_type = '';
  sampling_plan = '';
  retest = '';
  plant_id = localStorage.getItem('plant_id') || '';
  selMaterialGrade: any[] = [];
  grades1: any[] = [];
  getTestTypes() {
    this.service.get('master/test.php?type=get_S_Test').subscribe((response: any) => {
      const rows = Array.isArray(response) ? response : [];
      this.testType = [{ value: '', label: '' }].concat(
        rows.map((row: any) => ({ value: row.test_type, label: row.test_type }))
      );
      this.spectests = this.testType;
    });
  }
 products: any[] = [];
  getProducts() {
    this.service.get('qc/specification/inprocess.php?type=getProducts').subscribe((response: any) => {
      this.products = Array.isArray(response) ? response : [];
    });
  }
 

  tests: any[] = [];
  getTestByTestType(value) {
    this.tests = [];
    this.specSubTests = [];
    this.testMasterId = '';
    const testType = String(value || '').trim();
    if (!testType) {
      return;
    }
    this.service.get('common.php?type=getTests&test_type=' + encodeURIComponent(testType)).subscribe((response: any) => {
        this.tests = Array.isArray(response) ? response : [];
    });
  }

  specSubTests: any[] = [];
  testMasterId = '';

  getSubTest(index: number): void {
    this.specSubTests = [];
    this.testMasterId = '';
    const i = Number(index) - 1;
    if (i >= 0 && this.tests && this.tests[i]) {
      const row = this.tests[i];
      this.specSubTests = Array.isArray(row.subtests) ? row.subtests : [];
      this.testMasterId = row.id || '';
    }
  }


  units;
  getUnits() {
    this.service.observableUnit.subscribe((response) => {
      this.units = response;
    });
  }

 companies:any=[];
revisionList:any=[];

  checkSpec(value: string): void {
    this.specification = value;
    if (value === 'New' && this.revisionList.length === 0) {
      const date = this.formatDate(new Date());
      this.revisionList.push({
        spec_no: 'AUTO GENERATE',
        version_no: '00',
        change_mode: 'NA',
        change_reason: 'NA',
        effective_date: date,
      });
    } else if (value !== 'New') {
      this.revisionList = [];
    }
  }

  private formatDate(date: Date): string {
    const d = new Date(date);
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const year = d.getFullYear();
    return `${year}-${month}-${day}`;
  }

  getSubtest(event: any): void {
    const testObj = event?.value;
    this.specSubTests = Array.isArray(testObj?.subtests) ? testObj.subtests : [];
  }

  getMicroCheck(testType: string): void {
    this.getTestByTestType(testType);
  }

  addTests(data: any): void {
    if (!data?.valid) {
      alertify.error('All fields are required');
      return;
    }
    const temp = { ...data.value };
    const limitType = String(temp.limit_type || temp.limit || '').trim();
    if (!temp.test && this.selectedTest?.test) {
      temp.test = this.selectedTest.test;
    }
    if (!temp.test_type && this.selectedTest?.test_type) {
      temp.test_type = this.selectedTest.test_type;
    }
    if (limitType === 'Range') {
      temp.limits = `${temp.lower_limit || ''}${temp.unit || ''} to ${temp.upper_limit || ''}${temp.unit || ''}`;
    } else if (limitType === 'Not MoreThan') {
      temp.limits = `NMT ${temp.upper_limit || ''}${temp.unit || ''}`;
    } else if (limitType === 'Not LessThan') {
      temp.limits = `NLT ${temp.lower_limit || ''}${temp.unit || ''}`;
    } else if (limitType === 'Compliances') {
      temp.limits = temp.compliances || 'complies';
    } else {
      temp.limits = temp.description || '';
    }
    temp.limit_type = limitType || temp.limit_type || temp.limit || '';
    temp.limit = temp.limit_type;
    temp.test_method_no = temp.test_method_no || this.tms || 'na';
    if (!temp.subtest) {
      temp.subtest = 'NA';
    }
    const isMainSpecForm = Object.prototype.hasOwnProperty.call(temp, 'testFor');
    if (isMainSpecForm) {
      this.spectTests.push(temp);
    } else {
      this.companies.push(temp);
    }
    data.resetForm();
    this.limit_type = '';
    this.limit = '';
    this.specSubTests = [];
  }

  delTest(index: number): void {
    this.spectTests.splice(index, 1);
  }

  deleteProduct(index: number): void {
    this.delTest(index);
  }

  delAdditionalTest(index: number): void {
    this.companies.splice(index, 1);
  }

  addRevision(data: any): void {
    if (!data?.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.revisionList.push({ ...data.value });
    data.resetForm();
  }

  deleteRevision(index: number): void {
    this.revisionList.splice(index, 1);
  }

  saveStorage(): void {
    this.isStorage = false;
  }
 
  saveInprocessSpec(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
   
     let temp = data.value;
    temp['tests'] = [...this.spectTests, ...this.companies];
    temp['specification']=this.specification;
    temp['revisionHistory'] = this.revisionList;
    this.service.post('qc/specification/inprocess.php?type=saveSpecification',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Specification saved Successfully');
        data.resetForm();
        this.router.navigate(['/qc/specifications/inprocess']);
        this.companies = [];
        this.revisionList = [];
      } else {
        alertify.error('Error! Please try again');
      }
    });
  }

  

}
