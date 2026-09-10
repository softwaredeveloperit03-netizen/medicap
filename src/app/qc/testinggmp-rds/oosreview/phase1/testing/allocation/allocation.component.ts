import { Component, ElementRef, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { ActivatedRoute, Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css']
})
export class AllocationComponent implements OnInit {

  materials;
  selectedMaterial;
  isViewForm = false;
 

  employees;
  labs;

  microPerson;
  testing_type = 1;
  testing_person_alt = '';
  testing_person = '' ;
  allocation  = 'Single';
    emp_id: string;
    isDIGI: boolean=false
    loggedInDept;
    isbutton: boolean=true
  constructor(private service: DataAccessService ,  private _router: Router,) {
    this.loggedInDept = localStorage.getItem('department');
   
  }

  ngOnInit() {
    this.getTestings();
    this.getMicroPerson();
    this.getSoftCust()


  }
  param_value;
  restrictions;
  getSoftCust() {
    
    this.service.get('qa/custimize.php?type=getSoftware_restrication_dep&dep_name='+this.loggedInDept+'&module=Testing').subscribe(response => {
      this.restrictions = response;

      for(let i=0;i < this.restrictions.length;i++){
        this.param_value=this.restrictions[i]['param_value']
      }

      if(this.param_value=='With RDS'){
        this.testing_type=1;
      }else if(this.param_value=='Without RDS'){
        this.testing_type=0;
      }

        console.log('this.param_value :>> ', this.param_value);
      console.log(this.materials);
    });
  }
  getTestings() {
    
    this.service.get('qc/testing/raw.php?type=getPendingAllocationTestings_oos').subscribe(response => {
      this.materials = response;
      console.log(this.materials);
    });
  }

  tempSelectedMat:any ;
  viewForm(index) {
    this.selectedMaterial = this.materials[index];

    this.selectedMaterial.spec_tests.forEach((element,i) => {
      this.selectedMaterial.spec_tests[i].isVisiable = true ;
      if(element.outside_testing=="Applicable")  this.selectedMaterial.spec_tests[i].isoutside = "Yes" ; else this.selectedMaterial.spec_tests[i].isoutside = "No" ;
    });

    this.tempSelectedMat = index ;
    this.isViewForm = true;
    this.getTestingPersons();
    this.getLabs();
    this.checkMAthodAvl();
  }

  getTestingPersons() {
    this.service.get('common.php?type=getTestingPersons').subscribe(response => {
      this.employees = response;
    });
  }

  getLabs() {
    this.service.get('common.php?type=getLabs').subscribe(response => {
      this.labs = response;
    });
  }

  getMicroPerson(){
    this.service.get('common.php?type=getMicroPersons').subscribe(response => {
      this.microPerson = response;
    });
  }

  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
        this.allocateTestingPerson()

      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
  




  allocateTestingPerson() {

    let isValidate = true ;
   

 
     const mergedArray  =  this.extraTests.concat(this.selectedMaterial['spec_tests']);

     this.selectedMaterial['spec_tests1']  = mergedArray;

 

    if(!isValidate) { alertify.error('Please allocate all material'); ; return false ; }
     this.selectedMaterial.testing_type_s =  this.testing_type;
  
    console.log(this.selectedMaterial);

    this.service.post('qc/testing/raw.php?type=allocateTestingPerson&testing_type=+'+this.testing_type+'&sampling_no='+ this.selectedMaterial['sampling_no'] , JSON.stringify(this.selectedMaterial)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Testing Person allocated successfully');
        this.isbutton=true
        this.extraTests =[];

         if(this.testing_type==1)
        this._router.navigate(['/qc/testrds']);
        else
        this._router.navigate(['/qc/testing-erp']);
        this.isViewForm = false;
        this.getTestings();
      } else {
        alertify.error('An error occured, ' + response['error']);
      }
    });

  }


  

 


  extraTests =[];
  
   AddTest(index){

  // Get a copy of the test object at the specified index
  const test = { ...this.selectedMaterial['spec_tests'][index] };

  // Increment the test count
  test.testCount++;

  // Add the copied test object to the extraTests array
  this.extraTests.push(test);

  console.log(this.extraTests);

  // Update the testCount of the selectedMaterial
  this.selectedMaterial['spec_tests'][index].testCount = test.testCount;
 
  }


  detspec_test(index){
    this.extraTests.splice(index,1);
  }






  updateTests(index, action, value) {
    let test = this.selectedMaterial['spec_tests'];
    if (action === 'outside') {
      test[index].isoutside = value;
    } else if (action === 'person') {
      test[index].person = value;
    }else if (action === 'lab') {
      test[index].lab_name = value;
    }
    this.selectedMaterial['spec_tests'] = test;
  }
  testing_per=2;


  showAsPer(){



     if(this.testing_per==1){

      let grn = this.selectedMaterial['grn_grade_name'].split(",");
      this.selectedMaterial.spec_tests.forEach((element,i) => {

        this.selectedMaterial.spec_tests[i].isVisiable = true ;
        console.log(this.selectedMaterial['grn_grade_name'].search(element.reference_type));
        if(this.selectedMaterial['grn_grade_name'].search(element.reference_type)==-1) {
          this.selectedMaterial.spec_tests[i].isVisiable = false ;
        }
        
    });

  }else{



  }

    
  }

  checkMAthodAvl(){
    let isMethodAvl =  true ;
    this.selectedMaterial.spec_tests.forEach((element,i) => {
              if(element.methodID==null){
                isMethodAvl = false ;
              }
    });

    if(!isMethodAvl){
      alertify.error("Method not available");
    }

    this.selectedMaterial.spec_tests.forEach((element,i) => {
      this.selectedMaterial.spec_tests[i].isVisiable = true ;
    });

  }

  addSpecfication(){


    console.log( this.selectedMaterial.spec_tests);
    let IsValidate = true ;

    this.selectedMaterial.spec_tests.forEach((element,i) => {
     
      if(element?.box && element.box==true){


      if(element.isoutside=='Yes' &&  (element?.lab_name==undefined || element.lab_name=='')) {
        IsValidate =  false ;
        alertify.error("Please select lab name");
      } 
      else{

          if(this.testing_person==''){

            IsValidate =  false ;
            alertify.error("Please select chemist");

          }
      }


    }

    });

    if(!IsValidate)  return false ;
    

    this.selectedMaterial.spec_tests.forEach((element,i) => {

      if(element?.box && element.box==true && element.added != 1){

        element.added = 1 ;
   
        if(element.isoutside=='No'){
        element.testing_person_alt = this.testing_person_alt;
        element.testing_person = this.testing_person;
        }else{

          element.testing_person_alt = '';
          element.testing_person = '';

        }


      }

    });

  }

  removeMaterial(index){

    this.selectedMaterial.spec_tests[index].added=0;

    

      
      this.selectedMaterial.spec_tests[index].testing_person_alt = '';
      this.selectedMaterial.spec_tests[index].testing_person = '';
      this.selectedMaterial.spec_tests[index].lab_name = '';
    
    
  }

}
