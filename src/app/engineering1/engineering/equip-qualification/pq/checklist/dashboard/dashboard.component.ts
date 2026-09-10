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
  checkList=[];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getChecklist();
  }
  result
getChecklist(){
  this.service.get('engineering/qualification.php?type=getpqChecklist')
  .subscribe(response => { 
    this.result=response;


    });
}
  delData(id) {
   this.service.post('engineering/qualification.php?type=deleteData&id='+id+'&Table=pq_checklist',null)
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
    this.service.open('engineering/qualification.php?type=Downloadpq_checklist');
   }

   save(data){
    let temp=data.value;
    
    this.service.post('engineering/qualification.php?type=savepqChecklist',JSON.stringify(temp))
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
}
