import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  // isTestname= false;
  is_add = false;
  mtest_name;
  tests;
  test;
  isNewTest;
  classification = 'Raw Material';
  isRawMaterial = true;
  isPackingMaterial = false;
  isFinishProduct = false;
  isInprocess = false;
  test_type='';
  entries = [];
  dosage_form = '';
  test_name;
  testname;
  
  subtestList=[];
  constructor(private service: DataAccessService,private router:Router) {
   }

  ngOnInit() {
    this.getTestname();
    this.get_test1();
  }
  checkClassification(value) {
    if (value === 'Raw Material') {
      this.isRawMaterial = true;
      this.isPackingMaterial = false;
      this.isFinishProduct = false;
      this.isInprocess = false;
    } else if (value === 'Packing Material') {
      this.isRawMaterial = false;
      this.isPackingMaterial = true;
      this.isFinishProduct = false;
      this.isInprocess = false;
    } else if (value === 'Finish Product') {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isFinishProduct = true;
      this.isInprocess = false;
    } else if (value === 'Inprocess') {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isFinishProduct = false;
      this.isInprocess = true;
    }
  }
  
 
  result;

  get_test(){
    this.service.get('master/test.php?type=get_saveTest_medical').subscribe(response => {
     this.result = response;
    })
  }
  results;
  get_test1(){
    this.service.get('master/test.php?type=get_Test_for_medical').subscribe(response => {
     this.results = response;
    })
  }
  

  addSubtest(data){
    if(!data.valid){
      alert("All fiels are required");
      return;
    }
    this.subtestList[this.subtestList.length]=data.value;
    data.resetForm();
  }
  deletesubtest(index){
    this.subtestList.splice(index, 1);
  }
  addTest(testData) {
    this.isNewTest = false;
    let temp=testData;
    // temp['test_type']=this.test_type;
    // temp['subtest']=this.subtestList;
    console.log('type',this.test_type);
    this.service.post('master/test.php?type=saveTest_medical', JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] === 'success') {
       alertify.success('Test successfully send for Approval');
        this.router.navigate(['/master/hra/test-medical']);
      } else {
       alertify.error(this.service.t('common.errorOccurred'));
      }
      },
    (error: Response) => {
      if (error.status === 400) {
       alertify.success('An error has occurred.');
      } else {
       alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }

  addTestname(data){
    if (data == 'Add New') {
      this.is_add = true;
    }
  }

  // saveTest(data) {
  //   if (!data.valid) {
  //     alertify.error('All fields are required');
  //     return;
  //   } 
  //   let temp=data.value;
  //   this.service.post('master/test.php?type=Test_for_medical', JSON.stringify(temp)).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alertify.success('Record Inserted Successfully');
  //       this.getTestname();
  //       this.get_test1();
  //       data.resetForm();
  //        this.is_add=false;
        
        
       
  //     } else {
  //       alert('Please try Again');
  //     }
  //   });
  // }
  saveTest(data){
    
    let temp=data.value;
    this.service.post('master/test.php?type=Test_for_medical',JSON.stringify(temp)).subscribe(response=>{
      if(response['status'] =='success'){
         alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.get_test1();
         this.is_add=false;
      }else{
        alertify.error('Please try Again');
      }
    });
  }
 
  getTestname(){
    this.service.get('master/testname.php?type=getTestname').subscribe(response => {
     this.test = response;
    })
  }
  
   
}