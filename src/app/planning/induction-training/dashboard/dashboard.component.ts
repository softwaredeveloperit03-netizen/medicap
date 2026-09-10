import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  equipments=[];
  inductions;
  selectedResult=[];
  isProceed=false;
  departments;
  


  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {

    this.getLog();
    this.getHOD();
  }

  getHOD(){
    this.service.get('common.php?type=getHOD').subscribe(response => {
      this.departments = response;
    })
  }

  getLog() {
    this.service.get('hr/induction.php?type=getInductionLog')
    .subscribe(response => {
      this.inductions = response;
    });
  }

  save(data) {
    if (!data.valid) {
      alertify.error("all field are required");
      return;
    }
    let temp=[];
    temp=data.value;
    temp['equipments']=this.equipments;
    this.service.post('hr/induction.php?type=saveInduction',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        this.router.navigate(['/hr/induction-training'])
        alertify.success("Save successfully");
      }else{
        alertify.error(response);
      }
    });
  }
  proceed(index) {
    this.selectedResult = this.inductions[index];
    this.equipments = this.selectedResult['equipments'];
    this.isProceed = true;
  }


}
