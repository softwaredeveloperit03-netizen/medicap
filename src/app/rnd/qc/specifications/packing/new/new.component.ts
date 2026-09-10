import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  specification='';
  test_type='';
  isNew = true;
  materials;
  material_code='';
  tests;
  subtests = [];
  units;
  unit = '';
  composite = 0;
  isLimit = false;
  isLessthan = false;
  isMorethan = false;
  selectedProd=[];
  companies = [];
  subtest = '';
  revisionList = [];
  refers;
  selectedRef=[];
  isNo=false;
  isYes=false;
  micro_qty=0;
  sample_unit='';
  release_stability='';
  grades;
  grade='';
  material_subtype='';

  sample_qty = 0;
  constructor(private service: DataAccessService, private router: Router) {
    // let date = this.formatDate(new Date());
    //   this.revisionList[this.revisionList.length] = {"spec_no": "AUTO GENERATE", "ver_no": "00", "change_mode": "NA", "change_reason": "NA", "effective_date": date};
  }

  ngOnInit() {
    this.getTests();
    this.getUnits();
    this.getReferenceType();
    this.getGrades();
  }

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

  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }

  getMaterials(value) {
    this.service.get('qc/specification/packing.php?type=getMaterials&material_subtype=' + this.material_subtype).subscribe(response => {
      this.materials = response;
    });
  }


  getDetails(index){
    index = index - 1;
    if(index !== -1){
      this.selectedProd = this.materials[index];
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

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }


  getTests() {
    this.service.get('qc/specification/packing.php?type=getTests').subscribe(response => {
      this.tests = response;
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

  getSubtest(index) {
    index = index - 1;
    if (index !== -1) {
      this.subtests = this.tests[index].subtests;
    }
    this.subtest = '';
  }

  addMaterial(data) {
    let temp = data.value;
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    temp["specification"] =this.specification;
    temp['tests'] = this.companies;
    temp['revisionHistory'] = this.revisionList;
    temp['reference_type'] =this.selectedRef['grade'];
    this.service.post('qc/specification/packing.php?type=saveSpecification', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success("Data saved Successfully");
        this.router.navigate(['/specifications/packing']);
      } else {
        alertify.error("Error! Please try again");
      }
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
    if (this.companies[index].for_microbiology == 'Yes') {
      this.micro_qty = this.micro_qty - parseFloat(this.companies[index].sample_qty);
      this.companies.splice(index, 1);
    } else {
      this.composite = this.composite - parseFloat(this.companies[index].sample_qty);
      this.companies.splice(index, 1);
    }
  }

  deleteRevision(index) {
    this.revisionList.splice(index, 1);
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

}
