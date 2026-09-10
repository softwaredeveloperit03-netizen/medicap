import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  trainings;
  isHide;
  constructor(private service: DataAccessService) {
   }

 
  ngOnInit() {
    this.getTranings();
 
  }

  getTranings() {
    this.service.get('training.php?type=getInductionTrainingLog&ForLog=Hr')
    .subscribe(response => {
      this.trainings = response;
    });
  }

  

  download(id) {
    this.service.open('purchase/training.php?type=trainingLogPdf&id='+id);  
   }

  isShown1: boolean = false; // hidden by default
  toggleShow1() {
    this.isShown1 = !this.isShown1;
  }

  isView = false;
  
  selectedEmp =[];
  selectedTraining(i){
    this.selectedEmp =   this.trainings[i];
    this.isView = true;

  }


  dropEmployee(id) {
 
    let temp = {};
  
    this.service.post('training.php?type=dropFormInduction&id='+id,JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] == 'success') {
        this.getTranings();
         alert('successfully saved');
      } else {
        alert('An error occured');
      }
    });
  }







  
}
