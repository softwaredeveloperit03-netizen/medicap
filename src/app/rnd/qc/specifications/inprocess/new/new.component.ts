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
  release_stability='';
  dosages;
  products;
  isNew = true;
  sample_qty=0;
  for_microbiology='';
  grades;
  selectedProduct = [];
  isShowProd = false;
  stages;
  units;
  tests;
  subtests = [];
  composite = 0;
  micro_qty=0;
  isYes=false;
  isNo=false;
  sample_unit='';
  unit = '';
  subtest = '';
  companies = [];
  isLimit = false;
  isLessthan = false;
  isMorethan = false;
  refers;
  revisionList = [];
  selectedRef=[];
  test_type='';
  selectProductbygrade=[];
  gradedata;
  specification='';

  @Output() newSpec: EventEmitter<any> = new EventEmitter<any>();
  constructor(private service: DataAccessService, private route: ActivatedRoute,private router:Router) {
    console.log(this.newSpec);
    // let date = this.formatDate(new Date());
    // this.revisionList[this.revisionList.length] = {"spec_no": "AUTO GENERATE", "version_no": "00", "change_mode": "NA", "change_reason": "NA", "effective_date": date};
  }

  ngOnInit() {
    this.getDosageForms();
    this.getUnits();
    this.getTests();
    this.getReferenceType();
  }

  checkSpec(value) {
    if (value == 'New') {
      this.isNew = true;
      let date = this.formatDate(new Date());
      this.revisionList[this.revisionList.length] = {"spec_no": "AUTO GENERATE", "version_no": "00", "change_mode": "NA", "change_reason": "NA", "effective_date": date};
    } else {
      this.isNew = false;
      this.revisionList = [];
    }
  }

  formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) 
        month = '0' + month;
    if (day.length < 2) 
        day = '0' + day;

    return [year, month, day].join('-');
  }

  geProGrade(value){
    this.service.get('qc/specification/inprocess.php?type=getProducts&grade='+value).subscribe(response => {
         this.gradedata = response;
    });
  }

  getDosageForms() {
    this.service.get('qa.php?type=getDosageForms').subscribe(response => {
      this.dosages = response;
    });
  }

  getProducts(dosage) {
    this.service.get('qa.php?type=getProductByDosage&product_type=' + dosage).subscribe(response => {
      this.products = response;
    });
    // this.getManufacturingProcesses(dosage);
  }

  // getProductsbygrade(index){
  //   index = index - 1;
  //   this.selectProductbygrade = this.products[index];
  //   console.log(this.selectProductbygrade);
  // }

  getManufacturingProcesses(dosage) {
    this.service.get('qa.php?type=getManufacturingProcesses&product_type=' + dosage).subscribe(response => {
      this.stages = response;
    });
  }

  getReferenceType() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.refers = response;
    });
  }

  getRef(index){
    index = index - 1;
    this.selectedRef = this.refers[index];
  }
  getProductGrade(index) {
    index = index - 1;
    this.service.get('qa.php?type=getProductGrade&product_name=' + this.products[index].product_name).subscribe(response => {
      this.grades = response;
    });
  }

  selectProduct(index) {
    index = index - 1;
    this.selectedProduct = this.gradedata[index];
    this.isShowProd = true;
  }

  getSubtest(index) {
    index = index - 1;
    if (index !== -1) {
      this.subtests = this.tests[index].subtests;
    }
    this.subtest = '';
  }

  getUnits() {
    this.service.get('qa.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  getTests() {
    this.service.get('qa.php?type=getTestsByClassification&classification=FinishProduct').subscribe(response => {
      this.tests = response;
    });
  }

  addTests(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    if (temp['limit'] == "Limits") {
      temp['limits'] =  temp["lower_limit"] + temp["unit"] + " to " + temp["upper_limit"] + temp["unit"];
    } else if (temp['limit'] == "LessThan") {
      temp['limits'] =  "NMT " + temp["lessthan"] + temp["unit"];
    } else if (temp['limit'] == "MoreThan") {
      temp['limits'] =  "NLT " + temp["morethan"] + temp["unit"];
    } else if (temp["limit"] == "Compliances") {
      temp["limits"] = "complies";
    }
    temp['unit'] = this.unit;
    temp['sample_unit']=this.sample_unit;
    if(this.isYes==true){
      this.micro_qty += parseFloat(temp['sample_qty']);
    }
    if(this.isNo==true){
      this.composite += parseFloat(temp['sample_qty']);
    }
 
    this.companies[Object.keys(this.companies).length] = temp;
    data.resetForm();
    const element1 = document.getElementById('test') as HTMLElement;
    element1.focus();
  }

  getLimitcheck(getval){
    if(getval == 'Limits'){
      this.isLessthan = false;
      this.isMorethan = false;
      this.isLimit = true;
    }else if(getval == 'LessThan'){
      this.isLimit = false;
      this.isMorethan = false;
      this.isLessthan = true;
    }else if(getval == 'MoreThan'){
      this.isLimit = false;
      this.isMorethan = true;
      this.isLessthan = false;
    } else {
      this.isLimit = false;
      this.isMorethan = false;
      this.isLessthan = false;
    }
  }

  getMicroCheck(getqty){
    if(getqty=='Microbiology'){
      this.isYes=true;
      this.isNo=false;
    }else if(getqty=='Chemical'){
      this.isYes=false;
      this.isNo=true;
    }
    console.log('test',getqty);
    this.service.get('qc/specification/raw.php?type=getTests&test_type='+ getqty).subscribe(response => {
      this.tests = response;
    });
  }
  saveInprocessSpec(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['tests'] = this.companies;
    temp['specification']=this.specification;
    temp['revisionHistory'] = this.revisionList;
    temp['product_code'] = this.selectedProduct['product_code'];
    this.service.post('qc/specification/inprocess.php?type=saveSpecification', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Specification saved Successfully');
        data.resetForm();
        this.router.navigate(['/specifications/inprocess']);
        this.companies = [];
        this.revisionList = [];
      } else {
        alertify.error('Error! Please try again');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }

  addexperience(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['sample_unit'] = this.unit;
    if(this.isYes==true){
      this.micro_qty += parseFloat(temp['sample_qty']);
    }
    if(this.isNo==true){
      this.composite += parseFloat(temp['sample_qty']);
    }
 
    this.companies[Object.keys(this.companies).length] = temp;
    data.resetForm();
    const element1 = document.getElementById('test') as HTMLElement;
    element1.focus();
  }
  deleteProduct(index) {
    if (this.companies[index].for_microbiology == 'Yes') {
      this.micro_qty = this.micro_qty - parseFloat(this.companies[index].sample_qty);
      this.companies.splice(index, 1);
    } else {
      this.composite = this.composite - parseFloat(this.companies[index].sample_qty);
      this.companies.splice(index, 1);
    }
  }


  addRevision(data) {
    this.revisionList[Object.keys(this.revisionList).length] = data.value;
    data.reset();
    const element1 = document.getElementById('spec_no') as HTMLElement;
    element1.focus();
  }

  deleteRevision(index) {
    this.revisionList.splice(index, 1);
  }

  onClose() {

  }

}
