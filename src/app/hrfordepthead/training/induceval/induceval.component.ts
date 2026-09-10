import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-induceval',
  templateUrl: './induceval.component.html',
  styleUrls: ['./induceval.component.css']
})
export class InducevalComponent implements OnInit {

  
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
    this.service.get('training.php?type=inductionforQaReview')
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

    

    let temp = {};
  

    this.service.post('training.php?type=inductionFromQamanagerApproval&id=' + this.selectedEmp['id']
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
