import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-induction',
  templateUrl: './induction.component.html',
  styleUrls: ['./induction.component.css']
})
export class InductionComponent implements OnInit {

  trainings;
  isHide;
  constructor(private service: DataAccessService) {
   }

 

  ngOnInit() {
    this.getTranings();
    this.ALLEMP =  false;
  }

  getTranings() {
    this.service.get('employee.php?type=getInductionTrainingEmployee')
    .subscribe(response => {
      this.trainings = response;
    });
  }

  isView = false;
  quality_head = true;
  it = true;
  engineering = true;
  store = true;
  production = true;
  qc = true;
  qa = true;
  admin = true;
  
  checkAll = false;


  check(){
    if(this.checkAll == true){
      this.quality_head = true;
      this.it = true;
      this.engineering = true;
      this.store = true;
      this.production = true;
      this.qc = true;
      this.qa = true;
      this.admin = true;
    }else{
       this.quality_head = false;
      this.it = false;
      this.engineering = false;
      this.store = false;
      this.production = false;
      this.qc = false;
      this.qa = false;
      this.admin = false;
    }
  }



  ALLEMP =  false;

  checkALLEMP(){

    if(this.ALLEMP){
      for (let i = 0; i < this.trainings.length; i++) {
        this.trainings[i].check = true;
      } 
    }else{
      for (let i = 0; i < this.trainings.length; i++) {
        this.trainings[i].check = false;
      } 
    }
  
  }


  selectedEmp =[];


  selectedTraining(){

    this.selectedEmp = this.trainings.filter(train => train.check);

    this.isView = true;
    this.quality_head = true;
    this.it = true;
    this.engineering = true;
    this.store = true;
    this.production = true;
    this.qc = true;
    this.qa = true;
    this.admin = true;
    this.checkAll = true;

  }




  saveInductionTraining(data) {

    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = data.value;
    temp['empData'] = this.selectedEmp;

    this.service.post('training.php?type=saveInductionTraining',JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] == 'success') {
        this.getTranings();
        data.reset();
        this.selectedEmp = [];
        this.isView = false;
         alert('successfully saved');
      } else {
        alert('An error occured');
      }
    });
  }


  downloadreport(){
    this.service.open('pdf1/training.php?type=inductiontraining');
  }


}






















// trainings;
//   isHide;
//   constructor(private service: DataAccessService) {
//    }

 

//   ngOnInit() {
//     this.getTranings();
//   }

//   getTranings() {
//     this.service.get('employee.php?type=getInductionTrainingEmployee')
//     .subscribe(response => {
//       this.trainings = response;
//     });
//   }

//   isView = false;
//   quality_head = true;
//   it = false;
//   engineering = false;
//   store = false;
//   production = false;
//   qc = false;
//   qa = false;
//   admin = false;
  
//   checkAll = false;


//   check(){
//     if(this.checkAll == true){
//       this.quality_head = true;
//       this.it = true;
//       this.engineering = true;
//       this.store = true;
//       this.production = true;
//       this.qc = true;
//       this.qa = true;
//       this.admin = true;
//     }else{
//        this.quality_head = false;
//       this.it = false;
//       this.engineering = false;
//       this.store = false;
//       this.production = false;
//       this.qc = false;
//       this.qa = false;
//       this.admin = false;
//     }
//   }





//   selectedEmp =[];

  
//   selectedTraining(i){
//     this.selectedEmp =   this.trainings[i];
//     this.isView = true;
 
//     this.quality_head = false;
//     this.it = false;
//     this.engineering = false;
//     this.store = false;
//     this.production = false;
//     this.qc = false;
//     this.qa = false;
//     this.admin = false;
//     this.checkAll = false;

//   }




//   saveInductionTraining(data) {

//     if (!data.valid) {
//       alert('All fields are required');
//       return;
//     }

//     let temp = data.value;
//     this.service.post('training.php?type=saveInductionTraining&selemp_id=' + this.selectedEmp['emp_id'],JSON.stringify(temp))
//     .subscribe(response => {
//       if (response['status'] == 'success') {
//         this.getTranings();
//         data.reset();
//         this.isView = false;
//          alert('successfully saved');
//       } else {
//         alert('An error occured');
//       }
//     });
//   }


//   downloadreport(){
//     this.service.open('pdf1/training.php?type=inductiontraining');
//   }


// }
