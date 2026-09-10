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
 
  }

  getTranings() {
    this.service.get('training.php?type=getInductionTrainingLog&ForLog=Other')
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

 

  
}
