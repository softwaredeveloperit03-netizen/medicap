import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 declare let alertify: any;

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

  


  
  minToTime: string = '';

  updateToTimeMin(): void {
    this.minToTime = this.checkList['fromTime'];
    // Reset toTime if it is less than fromTime
    if (this.checkList['fromTime'] && this.checkList['toTime'] < this.minToTime) {
      this.checkList['toTime'] = '';
      alert('To Time Can Not Be Less Than From Time');
    }
  }





  getTranings() {
    this.service.get('training.php?type=getInductionTrainingForDepartment&deptName=Quality Head')
    .subscribe(response => {
      this.trainings = response;
    });
  }

  cordinator;

  getTrainingCordinator() {
    this.service.get('training.php?type=getTrainingCordinator&deptName=Quality Assurance')
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


  sopData =[];


  addSOp(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.sopData.push(temp);
    data.reset();
  }



  delSOp(i){
    this.sopData.splice(i,1);
  }

  responsibility1 = 'NA';
  readORexplained = 'Explained By';

  savedeptinductionTraining(data) {

    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = {};
    temp['checklist'] = this.checkList;
    temp['sopData'] = this.sopData;
    
    let temp2 = data.value;
    temp2['checklist'] = temp;

    this.service.post('training.php?type=savedeptinductionTraining&tnNo=' + this.selectedEmp['tnNo']+
      '&deptName=Quality Head' ,JSON.stringify(temp2))
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
