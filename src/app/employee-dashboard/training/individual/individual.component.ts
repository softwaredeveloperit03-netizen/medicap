import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-individual',
  templateUrl: './individual.component.html',
  styleUrls: ['./individual.component.css']
})
export class IndividualComponent implements OnInit {

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
    this.service.get('training.php?type=getInductionTrainingForempReport&loger_id='+localStorage.getItem('loger_id'))
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

    let temp = data.value;
  

    this.service.post('training.php?type=saveInductionTrainingEMpReport&id=' + this.selectedEmp['id']
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
