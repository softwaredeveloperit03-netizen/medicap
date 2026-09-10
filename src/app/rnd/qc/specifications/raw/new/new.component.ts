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
  grades;
  materials;

  grade = '';
  isNew = true;
  sample_qty=0;
  tests;
  subtests;
  units;
  unit = '';
  composite = 0;
  micro_qty=0;
  isLimit = false;
  isLessthan = false;
  isMorethan = false;
  isYes=false;
  isNo=false;
  selectedProd = [];
  sample_unit='';
  material_subtype = 'API';
  material_code = '';
  chemical_name = '';
  selectedqty=0;
  selectMicroQty=0;
  companies = [];
  subtest = '';
  revisionList = [];
  refers;
  selectedRef=[];
  release_stability='';
  test_type = '';
  revisionDataList=[];
  specification='';

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getGrades();
    this.getReferenceType();
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
    this.service.get('qc/specification/raw.php?type=getMaterials&material_subtype=' + this.material_subtype + '&grade=' + this.grade).subscribe(response => {
      this.materials = response;
    });
  }
  // getMaterials(value) {
  //   this.service.get('qc/specification/raw.php?type=getMaterials&grade=' + value).subscribe(response => {
  //     this.materials = response;
  //   });
  // }
  getDetails(index){
    index = index - 1;
    if(index !== -1){
      this.selectedProd = this.materials[index];
    }
  }

  getUnits(material_nature) {
    this.service.get('qc/specification.php?type=getUnitByMaterial&material_nature=' + material_nature).subscribe(response => {
      this.units = response;
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
  getSubtest(index) {
    index = index - 1;
    this.subtests = this.tests[index].subtests;
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
 
  addMaterial(data) {
    let temp = data.value;
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    temp["specification"] = this.specification;
    temp['material_code'] = this.selectedProd['material_code'];
    temp['chemical_name'] = this.chemical_name;
    temp['material_name']=this.selectedProd['material_name'];
    temp['grade']=this.grade;
    temp['material_type']=this.material_subtype;
    temp['reference_type'] =this.selectedRef['grade'];
    temp['tests'] = this.companies;
    temp['revisionHistory'] = this.revisionList;
    this.service.post('qc/specification/raw.php?type=saveSpecification', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success("Data saved Successfully");
        this.router.navigate(['/specifications/raw']);
      } else {
        alertify.error("Error! Please try again");
      }
      });
  }
  

}
