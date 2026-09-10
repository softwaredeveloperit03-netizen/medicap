import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-cleaningschd',
  templateUrl: './cleaningschd.component.html',
  styleUrls: ['./cleaningschd.component.css'],
})
export class CleaningschdComponent implements OnInit {

   //stpschedule;
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {

    this.getstpschedule();
  }
stpschedule;
  getstpschedule(){
    this.service.get('ehs/fire.php?type=getsaveCleaningSchedules').subscribe(response =>{
        this.stpschedule =response
      });
  }

  Sections;
  getSection(){
    this.service.get('master/section.php?type=getSection').subscribe(response =>{
      this.Sections = response;
    })
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
  
  submitTankForm(data)
  {
    let temp = data.value;
    console.log(temp);
 
     this.service.post(
        'ehs/fire.php?type=saveCleaningSchedules',JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Save Successfully');
          this.getstpschedule();
        } else {
          alertify.error(response['status']);
        }
      });
 
  }
}
