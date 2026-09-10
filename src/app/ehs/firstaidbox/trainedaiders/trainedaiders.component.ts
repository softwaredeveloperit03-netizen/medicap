import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-trainedaiders',
  templateUrl: './trainedaiders.component.html',
  styleUrls: ['./trainedaiders.component.css']
})
export class TrainedaidersComponent implements OnInit {
  
  aiders;
 

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    // this.getEmpdata();
    this.GET_InvolvedPersons();
  }

  selectedEmp=[];
    getEmpdata(i){

      this.selectedEmp=this.InvolvedPersons[i-1];
    }

   InvolvedPersons: any = [];
  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }

  
  isNew;
    saveAiders(data){
   let temp = data.value;
    console.log(temp);
  this.service.post('ehs/statuscard.php?type=saveTrainedAiders', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Save Successfully');
            this.isNew=false;
        
        } else {
          alertify.error(response['status']);
        }
      });
  }


}
