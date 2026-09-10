import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
 declare let alertify;;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  sample_unit='';
  products;
  grades;
  tests;
  isNew = true;
  subtests = [];
  composite = 0;
  micro_qty=0;
  units;
  unit = '';
  productunit='';
  isLimit = false;
  isLessthan = false;
  isMorethan = false;
  companies = [];
  subtest = '';
  product_code = '';
  revisionList = [];
  selectedProduct = [];
  isShowProd = false;
  release_stability = 'Release';
  refers;
  selectedRef=[];
  isYes =false;
  isNo=false;
  test_type='';
  specification='';

  sample_qty = 0;
  @Output() newSpec: EventEmitter<any> = new EventEmitter<any>();
  constructor(private service: DataAccessService, private route: ActivatedRoute,private router:Router) {
   
  }

  ngOnInit() {
    this.getGrades();
   // this.getTests();`
    this.getReferenceType();
  }

  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }

  // getProducts() {
  //   this.service.get('qc/specification/finish.php?type=getProducts').subscribe(response => {
  //     this.products = response;
  //   });
  // }
  checkSpec(value) {
    if (value == 'New') {
      this.isNew = true;
      let date = this.formatDate(new Date());
      this.revisionList[this.revisionList.length] = {"spec_no": "AUTO GENERATE", "ver_no": "00", "change_mode": "NA", "change_reason": "NA", "effective_date": date};
    } else {
      this.isNew = false;
      this.revisionList = [];
    }
  }

  getProductsbyGrade(value){
    this.service.get('qc/specification/finish.php?type=getProducts&grade='+value).subscribe(response => {
         this.products = response;
    });
  }
  selectProduct(index) {
    index = index - 1;
    this.selectedProduct = this.grades[index];
    this.isShowProd = true;
  }

  // getTests() {
  //   this.service.get('qc/specification/finish.php?type=getTests').subscribe(response => {
  //     this.tests = response;
  //   });
  // }

  getSubtest(index) {
    index = index - 1;
    // let sub=this.tests['subtests'];
    this.subtests =this.tests[index].subtests;
    console.log('sub',this.subtests);
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

  getMicroCheck(getqty){
    if(getqty=='Microbiology'){
      this.isYes=true;
      this.isNo=false;
    }else if(getqty=='Chemical'){
      this.isYes=false;
      this.isNo=true;
    }
    console.log('test',getqty);
    this.service.get('qc/specification/finish.php?type=getTests&test_type='+ getqty).subscribe(response => {
      this.tests = response;
    });
  }

  addexperience(data) {
    let temp = data.value;
    temp['test_type']=this.test_type;
    console.log('typetest',this.test_type);
    temp['release_stability'] = this.release_stability;
    temp['sample_unit']=this.sample_unit;
    temp['unit'] = this.unit;
    if (temp['limit'] == "Limits") {
      temp['limits'] =  temp["lower_limit"] + temp["unit"] + " to " + temp["upper_limit"] + temp["unit"];
    } else if (temp['limit'] == "LessThan") {
      temp['limits'] =  "NMT " + temp["lessthan"] + temp["unit"];
    } else if (temp['limit'] == "MoreThan") {
      temp['limits'] =  "NLT " + temp["morethan"] + temp["unit"];
    } else if (temp["limit"] == "Compliances") {
      temp["limits"] = "complies";
    }
    if(this.isYes==true){
      this.micro_qty += parseFloat(temp['sample_qty']);
    }
    if(this.isNo==true){
      this.composite += parseFloat(temp['sample_qty']);
    }
    // this.companies[Object.keys(this.companies).length] = temp;
    this.companies[this.companies.length] = temp;
    data.resetForm();
    const element1 = document.getElementById('test') as HTMLElement;
    element1.focus();
  }

  getLimitcheck(getval) {
    if (getval === 'Limits') {
      this.isLessthan = false;
      this.isMorethan = false;
      this.isLimit = true;
    } else if (getval === 'LessThan') {
      this.isLimit = false;
      this.isMorethan = false;
      this.isLessthan = true;
    } else if (getval === 'MoreThan') {
      this.isLimit = false;
      this.isMorethan = true;
      this.isLessthan = false;
    } else {
      this.isLimit = false;
      this.isMorethan = false;
      this.isLessthan = false;
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
  addRevision(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.revisionList[Object.keys(this.revisionList).length] = temp;
    data.resetForm();
    const element1 = document.getElementById('spec_no') as HTMLElement;
    element1.focus();
  }

  deleteProduct(index) {
    if (this.companies[index].test_type == 'Microbiology') {
      this.micro_qty = this.micro_qty - parseFloat(this.companies[index].sample_qty);
      this.companies.splice(index, 1);
    } else if(this.companies[index].test_type == 'Chemical') {
      this.composite = this.composite - parseFloat(this.companies[index].sample_qty);
      this.companies.splice(index, 1);
    }
  }


  deleteRevision(index) {
    this.revisionList.splice(index, 1);
  }

  saveFPSpecification(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let test = data.value;
    test['specification']=this.specification;
    test['product_code'] = this.product_code;
    test['reference_type'] =this.selectedRef['grade'];
    test['tests'] = this.companies;
    console.log('rrr', this.companies);
    test['revisionHistory'] = this.revisionList;
    this.service.post('qc/specification/finish.php?type=saveSpecification', JSON.stringify(test)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Finish Product Specification Saved Successfully');
        data.resetForm();
        this.router.navigate(['/specifications/finish']);
      }
    });
  }

  onClose() {

  }

}
