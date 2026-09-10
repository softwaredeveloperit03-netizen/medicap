import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  reportList:any=[];
  constructor(private service: DataAccessService, private router: Router) { }

  
  ngOnInit(): void {
     this.getChecklist();
  }
   getChecklist(){
  this.service.get('engineering/qualification.php?type=getPerformanceReport')
  .subscribe(response => { 
    this.reportList=response;


    });
}
  addData(data) {
   let temp=data.value;
    
    this.service.post('engineering/qualification.php?type=savePerformanceReport',JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('success');
        data.resetForm();
        this.getChecklist()
        
        
      } else {
        alertify.error(response['status']);
      }
      });
  }

  delData(id) {
     this.service.post('engineering/qualification.php?type=deleteData&id='+id+'&Table=PerformanceReport',null)
    .subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('success');
       
        this.getChecklist()
        
        
      } else {
        alertify.error(response['status']);
      }
      });
  }
  download(){
   
      //    this.service.open('common.php?type=geti8tConsumableLog');
  
   // this.service.open('');
   }
}
