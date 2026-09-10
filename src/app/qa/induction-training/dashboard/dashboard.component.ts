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
  eh=[];


  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {

    this.getLog();
    
  }

  
  getLog() {
    this.service.get('hr/induction.php?type=getInductionQALog').subscribe(response => {
      this.inductions = response;
    });
  }

  proceed(index) {
    this.selectedResult = this.inductions[index];
    this.eh = this.selectedResult['qa'];
    // this.hr = hrr[index] ;
    console.log('tt' , this.eh);
    this.isProceed = true;
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
        this.router.navigate(['/ehs/induction-training'])
        alertify.success("Save successfully");
      }else{
        alertify.error(response);
      }
    });
  }
  


}

  


