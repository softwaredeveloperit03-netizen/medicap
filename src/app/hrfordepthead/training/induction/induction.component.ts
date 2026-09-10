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
    this.getTrainingCordinator();
  }


  isShown1: boolean = false; // hidden by default
  toggleShow1() {
    this.isShown1 = !this.isShown1;
  }

  getTranings() {
    this.service.get('training.php?type=inductiontrainingReportForDeptHead&deptName='+localStorage.getItem('department'))
    .subscribe(response => {
      this.trainings = response;
    });
  }

  cordinator;

  getTrainingCordinator() {
    this.service.get('training.php?type=getTrainingCordinator&deptName='+localStorage.getItem('department'))
    .subscribe(response => {
      this.cordinator = response;
    });
  }


  isView = false;
  


  selectedEmp =[];
  checkList =[];
  selectedTraining(i){
    this.selectedEmp =   this.trainings[i];
    this.checkList =   this.selectedEmp['cheklist'];
    this.isView = true;
 
  }


 
 

  savedeptinductionTraining(data) {

    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = data.value;
  

    this.service.post('training.php?type=saveDeptHeadEvaluation&id=' + this.selectedEmp['id']
     ,JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] == 'success') {
        this.getTranings();
        data.reset();
        this.isView = false;
         alert('successfully saved');
      } else {
        alert('An error occured');
      }
    });
  }


 

}
