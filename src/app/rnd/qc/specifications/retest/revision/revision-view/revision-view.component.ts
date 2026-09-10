import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-revision-view',
  templateUrl: './revision-view.component.html',
  styleUrls: ['./revision-view.component.css']
})
export class RevisionViewComponent implements OnInit {

  selectedIndex = 0;
  specifications;
  materialType;
  proposedChanges = [];
  proposedChangesView = [];
  specificationsRevision;
  revisionList1=[];
  


  isNew = true;
  for_microbiology='';
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
  materials;
  selectedProd = [];

  material_subtype = 'API';
  material_code = '';
  chemical_name = '';
  selectedqty=0;
  selectMicroQty=0;
  companies = [];
  subtest = '';
  revisionList = [];
  selectedSpec;
  selectedSpecification;


  isChanges = false;
  isEdit = false;
  viewSpecification = false;

  constructor(private service: DataAccessService, private fb: FormBuilder,private router: Router ) {
    let date = this.formatDate(new Date());
   // this.revisionList[this.revisionList.length] = {"spec_no": "AUTO GENERATE", "ver_no": "00", "change_mode": "NA", "change_reason": "NA", "effective_date": date};

  }

  ngOnInit() {
    this.getSpecificationRevision();
    this.getTests();
  }

  getSpecificationRevision() {
    this.service.get('qc/specification/finish.php?type=getPendingRevisions').subscribe(response => {
      this.specificationsRevision = response;
    });
  }

  getTests() {
    this.service.get('qcDepartment.php?type=getTestsOnly&classification=Raw Material').subscribe(response => {
      this.tests = response;
    });
  }

  onEdit(index) {
    this.isEdit = true;
    this.selectedSpec=this.specificationsRevision[index];
    this.revisionList1=this.selectedSpec['revision_history'];
    this.companies=this.selectedSpec['tests'];
  }

 
  
  getSubtest(value){
    this.subtest = '';
    this.service.get('qcDepartment.php?type=getSubtestoftest&test='+value + '&classification=Raw Material').subscribe(response => {
      this.subtests = response;
    });
  }


  addTests(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['sample_unit'] = this.unit;
    if(this.isYes==true){
     
      this.selectedSpec['micro_qty'] = this.selectedSpec['micro_qty']*1 +(temp['sample_qty'])*1;
    }
    if(this.isNo==true){
     
      this.selectedSpec['sample_qty'] = this.selectedSpec['sample_qty']*1 +(temp['sample_qty'])*1;
    }
 
    this.companies[Object.keys(this.companies).length] = temp;
    
    data.resetForm();
    const element1 = document.getElementById('test') as HTMLElement;
    element1.focus();
  }

  deleteProduct(index) {
    if (this.companies[index].for_micro == 'Yes') {
      this.selectedSpec['micro_qty'] = this.selectedSpec['micro_qty'] - parseFloat(this.companies[index].sample_qty);
      this.companies.splice(index, 1);
    } else {
      this.selectedSpec['sample_qty'] = this.selectedSpec['sample_qty'] - parseFloat(this.companies[index].sample_qty);
      this.companies.splice(index, 1);
    }
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
    if(getqty=='Yes'){
      this.isYes=true;
      this.isNo=false;
    }else{
      this.isYes=false;
      this.isNo=true;
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


 save(data) {
    let temp = data.value;
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    
    this.selectedSpec['tests'] = this.companies;
    this.selectedSpec['revision_history'] = this.revisionList1;
    this.service.post('qc/specification/finish.php?type=saveEditSpecification', JSON.stringify(this.selectedSpec)).subscribe(response => {
      if(response['status'] === 'success') {
        this.getSpecificationRevision();
        alertify.success("Data saved Successfully");
       this.router.navigate(['/specifications/finish/revision/']);
      } else {
        alertify.error("Error! Please try again");
      }
    });
  }

}
